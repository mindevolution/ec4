<?php

namespace Plugin\SoftbankPayment4\Client;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\OrderStateMachine;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\SoftbankPayment4\Adapter\SbpsAdapter as Adapter;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeType as TradeType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeDetailResultType as ResultType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType as StatusType;
use Plugin\SoftbankPayment4\Factory\SbpsExceptionFactory as ExceptionFactory;
use Plugin\SoftbankPayment4\Factory\SbpsTradeDetailFactory;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class ParticalRefundClient
{
    private static $action_code = [
        PayMethodType::CREDIT       => 'ST02-00307-101',    // リンク型、API型でAPIコードは共通
        PayMethodType::CREDIT_API   => 'ST02-00307-101',    // 〃
        PayMethodType::UNION_PAY    => 'ST02-00308-514',
        PayMethodType::DOCOMO       => 'ST02-00303-401',
        PayMethodType::AU           => 'ST02-00303-402',
    ];

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
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var ExceptionFactory
     */
    private $exceptionFactory;
    /**
     * @var SbpsTradeDetailFactory
     */
    private $sbpsTradeDetailFactory;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var OrderStateMachine
     */
    private $orderStateMachine;

    public function __construct(
        Adapter $adapter,
        ConfigRepository $configRepository,
        EntityManagerInterface $entityManager,
        ExceptionFactory $exceptionFactory,
        TradeHelper $tradeHelper,
        SbpsTradeDetailFactory $sbpsTradeDetailFactory,
        OrderStateMachine $orderStateMachine,
        OrderStatusRepository $orderStatusRepository
    )
    {
        $this->adapter = $adapter;
        $this->configRepository = $configRepository;
        $this->em = $entityManager;
        $this->exceptionFactory = $exceptionFactory;
        $this->tradeHelper = $tradeHelper;
        $this->sbpsTradeDetailFactory = $sbpsTradeDetailFactory;
        $this->orderStateMachine = $orderStateMachine;
        $this->orderStatusRepository = $orderStatusRepository;
    }

    public function can(): bool
    {
        $Trade = $this->Order->getSbpsTrade();
        return $Trade->canRefund() && $Trade->getCapturedAmount() > $this->Order->getPaymentTotal();
    }

    public function handle()
    {
        $code = PayMethodType::getCodeByClass($this->Order->getPayment()->getMethodClass());

        $param = $this->createParameter();

        $sxe = $this->adapter->initSxe(self::$action_code[$code]);
        $xmlParam = $this->adapter->arrayToXml($sxe, $param);
        $response = $this->adapter->request($xmlParam);

        $Trade = $this->Order->getSbpsTrade();
        $refundAmount = -1 * ($Trade->getCapturedAmount() - $this->Order->getPaymentTotal());

        $Detail = $this->sbpsTradeDetailFactory->createByResponse($response, $Trade, TradeType::REFUND, $refundAmount);
        $this->em->persist($Detail);

        $Trade->addSbpsTradeDetail($Detail);

        if ($response['res_result'] !== 'OK') {
            if ($this->tradeHelper->isExpired($response['res_err_code'])) {
                $Trade->setStatus(StatusType::EXPIRED);
            }

            $this->em->flush();

            throw $this->exceptionFactory->create($response['res_err_code']);
        }

        $Trade->changeCapturedAmount($this->Order->getPaymentTotal());

        if ($this->Order->getPaymentTotal() == 0 ||
            PayMethodType::getCodeByClass($this->Order->getPayment()->getMethodClass()) === PayMethodType::CREDIT ||
            PayMethodType::getCodeByClass($this->Order->getPayment()->getMethodClass()) === PayMethodType::DOCOMO
        )
        {
            $Trade->changeStatus(StatusType::REFOUND);
        }

        if ($this->Order->getPaymentTotal() == 0) {
            $Order = $Trade->getOrder();
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
            if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                $this->orderStateMachine->apply($Order, $OrderStatus);
            }
        }

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
            'pay_option_manage' => [
                'amount' => $this->Order->getSbpsTrade()->getCapturedAmount() - $this->Order->getPaymentTotal(),
            ],
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];

        if (PayMethodType::getCodeByClass($this->Order->getPayment()->getMethodClass()) === PayMethodType::UNION_PAY) {
            $param['pay_option_manage']['refund_rowno'] = $this->calculateRowNo();
        }

        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        return $param;
    }

    public function setOrder(Order $Order): ParticalRefundClient
    {
        $this->Order = $Order;
        return $this;
    }

    private function calculateRowNo(): string
    {
        $Trade = $this->Order->getSbpsTrade();
        $Details = $Trade->getSbpsTradeDetails();

        $count = 1;
        foreach ($Details as $Detail) {
            if ($Detail->getTradeType() === TradeType::REFUND && $Detail->getResult() === ResultType::CORRECT) {
                $count++;
            }
        }
        $count = str_pad($count, 2, 0, STR_PAD_LEFT);

        return (string)$count;
    }
}
