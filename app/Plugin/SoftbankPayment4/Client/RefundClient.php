<?php

namespace Plugin\SoftbankPayment4\Client;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\OrderStateMachine;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\SoftbankPayment4\Adapter\SbpsAdapter as Adapter;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Plugin\SoftbankPayment4\Factory\SbpsTradeDetailFactory;
use Plugin\SoftbankPayment4\Factory\SbpsExceptionFactory;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeType as TradeType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType as StatusType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeDetailResultType as ResultType;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class RefundClient
{
    private static $action_code = [
        PayMethodType::CREDIT               => 'ST02-00303-101',    // リンク型、API型でAPIコードは共通.
        PayMethodType::CREDIT_API           => 'ST02-00303-101',    // 〃
        PayMethodType::SB                   => 'ST02-00303-405',
        PayMethodType::DOCOMO               => 'ST02-00303-401',
        PayMethodType::AU                   => 'ST02-00303-402',
        PayMethodType::PayPay               => 'ST02-00306-311',
        PayMethodType::UNION_PAY            => 'ST02-00308-514',
        PayMethodType::CVS_DEFERRED         => 'ST02-00306-701',
        PayMethodType::CVS_DEFERRED_API     => 'ST02-00306-701',
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
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var EntityManagerInterface
     */
    private $em;
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
    /**
     * @var SbpsExceptionFactory
     */
    private $sbpsExceptionFactory;

    public function __construct(
        Adapter $adapter,
        ConfigRepository $configRepository,
        EntityManagerInterface $entityManager,
        OrderStatusRepository $orderStatusRepository,
        OrderStateMachine $orderStateMachine,
        TradeHelper $tradeHelper,
        SbpsExceptionFactory $sbpsExceptionFactory,
        SbpsTradeDetailFactory $sbpsTradeDetailFactory
    )
    {
        $this->adapter = $adapter;
        $this->configRepository = $configRepository;
        $this->em = $entityManager;
        $this->orderStateMachine = $orderStateMachine;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->tradeHelper = $tradeHelper;
        $this->sbpsExceptionFactory = $sbpsExceptionFactory;
        $this->sbpsTradeDetailFactory = $sbpsTradeDetailFactory;
    }

    public function can(): bool
    {
        $Trade = $this->Order->getSbpsTrade();

        return $Trade->getStatus() === StatusType::ARED || $Trade->getStatus() === StatusType::CAPTURED;
    }

    public function handle($trackingId = null)
    {
        $sxe = $this->adapter->initSxe(self::$action_code[PayMethodType::getCodeByClass($this->Order->getPayment()->getMethodClass())]);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter($trackingId));
        $response = $this->adapter->request($xmlParam);

        $tradeType = StatusType::ARED ? TradeType::CANCEL : TradeType::REFUND;

        $Trade = $this->Order->getSbpsTrade();
        $refundAmount = -1 * $this->Order->getTotalPrice();
        $Detail = $this->sbpsTradeDetailFactory->createByResponse($response, $Trade, $tradeType, $refundAmount);
        $this->em->persist($Detail);

        $Trade->addSbpsTradeDetail($Detail);

        if ($response['res_result'] !== 'OK') {
            if ($this->tradeHelper->isExpired($response['res_err_code'])) {
                $Trade->setStatus(StatusType::EXPIRED);
            }

            $this->em->flush();

            $Exception = $this->sbpsExceptionFactory->create($response['res_err_code']);
            // 一括操作の時は例外を投げずに返す.
            if ($this->isBulk === true) {
                return $Exception;
            } else {
                throw $Exception;
            }
        }

        // NOTE:再与信実行時に呼ばれる時は通らない
        if (is_null($trackingId)) {

            $status = $Trade->getStatus() === StatusType::CAPTURED ? StatusType::REFOUND : StatusType::CANCELED;

            $Trade
                ->changeStatus($status)
                ->changeCapturedAmount(0);

            $Order = $Trade->getOrder();
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
            if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                $this->orderStateMachine->apply($Order, $OrderStatus);
            }

            $this->em->flush();
        }

        return $response;
    }

    public function createParameter($trackingId = null): array
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

        // NOTE: 銀聯決済では返金後に取消した場合refund_rownoの採番が必要
        if (PayMethodType::getCodeByClass($this->Order->getPayment()->getMethodClass()) === PayMethodType::UNION_PAY) {
            $RowCnt = $this->calculateRowNo();
            if ($RowCnt !== '01') {
                $param['pay_option_manage']['amount'] = (int)$this->Order->getPaymentTotal();
                $param['pay_option_manage']['refund_rowno'] = $RowCnt;
            }
        }

        if (!is_null($trackingId)) {
            $param['tracking_id'] = $trackingId;
        }

        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        return $param;
    }

    public function setOrder(Order $Order): RefundClient
    {
        $this->Order = $Order;
        return $this;
    }

    public function calculateRowNo(): string
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
