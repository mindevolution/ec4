<?php

namespace Plugin\SoftbankPayment4\Client;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Plugin\SoftbankPayment4\Adapter\SbpsAdapter as Adapter;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType as StatusType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeType as TradeType;
use Plugin\SoftbankPayment4\Factory\SbpsExceptionFactory as ExceptionFactory;
use Plugin\SoftbankPayment4\Factory\SbpsTradeDetailFactory as DetailFactory;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class CaptureClient
{
    private static $action_code = [
        PayMethodType::CREDIT       => 'ST02-00201-101',    // リンク型、API型でAPIコードは共通.
        PayMethodType::CREDIT_API   => 'ST02-00201-101',    // 〃
        PayMethodType::DOCOMO       => 'ST02-00201-401',
        PayMethodType::AU           => 'ST02-00201-402',
        PayMethodType::SB           => 'ST02-00201-405',
    ];

    public  $isBulk = false;

    private $Order;

    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var DetailFactory
     */
    private $detailFactory;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ExceptionFactory
     */
    private $exceptionFactory;
    /**
     * @var TradeHelper
     */
    private $tradeHelper;

    public function __construct(
        Adapter $adapter,
        ConfigRepository $configRepository,
        DetailFactory $detailFactory,
        EntityManagerInterface $em,
        ExceptionFactory $exceptionFactory,
        TradeHelper $tradeHelper
    )
    {
        $this->adapter = $adapter;
        $this->configRepository = $configRepository;
        $this->detailFactory = $detailFactory;
        $this->em = $em;
        $this->exceptionFactory = $exceptionFactory;
        $this->tradeHelper = $tradeHelper;
    }

    public function can(): bool
    {
        return true;
    }

    public function handle()
    {
        $sxe = $this->adapter->initSxe(self::$action_code[PayMethodType::getCodeByClass($this->Order->getPayment()->getMethodClass())]);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter());
        $response = $this->adapter->request($xmlParam);

        $Trade = $this->Order->getSbpsTrade();

        $Detail = $this->detailFactory->createByResponse($response, $Trade, TradeType::CAPTURE, $this->Order->getPaymentTotal());
        $this->em->persist($Detail);

        $Trade->addSbpsTradeDetail($Detail);

        if ($response['res_result'] !== 'OK') {
            if ($this->tradeHelper->isExpired($response['res_err_code'])) {
                $Trade->setStatus(StatusType::EXPIRED);
            }
            $this->em->flush();

            $Exception = $this->exceptionFactory->create($response['res_err_code']);
            // 一括操作のときは例外を投げずに返す.
            if ($this->isBulk === true) {
                return $Exception;
            } else {
                throw $Exception;
            }
        }

        $Trade
            ->changeCapturedAmount($Detail->getAmount())
            ->changeStatus(StatusType::CAPTURED);

        $this->em->flush();

        return $response;
    }

    public function createParameter(): array
    {
        $Config = $this->configRepository->get();
        $param = [
            'merchant_id' => $Config->getMerchantId(),
            'service_id' => $Config->getServiceId(),
            'tracking_id' => $this->Order->getSbpsTrade()->getTrackingId(),
            'processing_datetime' => $this->tradeHelper->createRequestDate(),
            'pay_option_manage' => [],
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];

        if ($this->Order->getSbpsTrade()->getAuthorizedAmount() !== $this->Order->getPaymentTotal()) {
            $param['pay_option_manage']['amount'] = (int)$this->Order->getPaymentTotal();
        }

        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        return $param;
    }

    public function setOrder(Order $Order): CaptureClient
    {
        $this->Order = $Order;
        return $this;
    }
}
