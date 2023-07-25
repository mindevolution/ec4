<?php

namespace Plugin\SoftbankPayment4\Client;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\OrderStateMachine;
use Plugin\SoftbankPayment4\Adapter\SbpsAdapter as Adapter;
use Plugin\SoftbankPayment4\Entity\Master\CaptureType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType as StatusType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeType as TradeType;
use Plugin\SoftbankPayment4\Factory\SbpsExceptionFactory as ExceptionFactory;
use Plugin\SoftbankPayment4\Factory\SbpsTradeDetailFactory as DetailFactory;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class ReauthClient
{
    public const ACTION_CODE = 'ST01-00133-101';

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
     * @var CommitClient
     */
    private $commitClient;
    /**
     * @var RefundClient
     */
    private $refundClient;
    /**
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var CaptureClient
     */
    private $captureClient;
    /**
     * @var DetailFactory
     */
    private $detailFactory;
    /**
     * @var ExceptionFactory
     */
    private $exceptionFactory;
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
        CaptureClient $captureClient,
        ConfigRepository $configRepository,
        CommitClient $commitClient,
        DetailFactory $detailFactory,
        EntityManagerInterface $em,
        ExceptionFactory $exceptionFactory,
        OrderStateMachine $orderStateMachine,
        OrderStatusRepository $orderStatusRepository,
        RefundClient $refundClient,
        TradeHelper $tradeHelper
    )
    {
        $this->adapter = $adapter;
        $this->configRepository = $configRepository;
        $this->captureClient = $captureClient;
        $this->commitClient = $commitClient;
        $this->detailFactory = $detailFactory;
        $this->exceptionFactory = $exceptionFactory;
        $this->em = $em;
        $this->orderStateMachine = $orderStateMachine;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->refundClient = $refundClient;
        $this->tradeHelper = $tradeHelper;
    }

    public function can():bool
    {
        return true;
    }

    public function handle()
    {
        // キャンセル状態であれば、ステータスを対応中に戻す
        if ($this->Order->getOrderStatus()->getId() === OrderStatus::CANCEL) {
            $this->orderStateMachine->apply($this->Order, $this->orderStatusRepository->find(OrderStatus::IN_PROGRESS));
        }

        $storedTrackingId = $this->Order->getSbpsTrade()->getTrackingId();

        $sxe = $this->adapter->initSxe(self::ACTION_CODE);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter());
        $response = $this->adapter->request($xmlParam);

        $StoredTrade = $this->Order->getSbpsTrade();

        $Detail = $this->detailFactory->createByResponse($response, $StoredTrade, TradeType::REAUTH, $this->Order->getPaymentTotal());
        $this->em->persist($Detail);

        $StoredTrade->addSbpsTradeDetail($Detail);

        $this->em->flush();

        if($response['res_result'] != 'OK') {
            throw $this->exceptionFactory->create($response['res_err_code']);
        }

        // 確定処理
        $commitResponse = $this->commitClient->handle($response);

        $Detail = $this->detailFactory->createByResponse($commitResponse, $StoredTrade, TradeType::COMMIT, $this->Order->getPaymentTotal());
        $this->em->persist($Detail);

        $StoredTrade->addSbpsTradeDetail($Detail);

        $this->em->flush();

        if($commitResponse['res_result'] != 'OK') {
            throw $this->exceptionFactory->create($commitResponse['res_err_code']);
        }

        $captureType = $this->configRepository->get()->getCaptureType();
        if ($captureType === CaptureType::CAPTURE) {
            $statusType = StatusType::CAPTURED;
            $captureAmount = $this->Order->getPaymentTotal();
        } else {
            $statusType = StatusType::ARED;
            $captureAmount = 0;
        }

        $storedStatus = $StoredTrade->getStatus();

        $StoredTrade
            ->setTrackingId($response['res_tracking_id'])
            ->setStatus($statusType)
            ->setAuthorizedAmount($this->Order->getPaymentTotal())
            ->setCapturedAmount($captureAmount);

        $this->em->flush();

        // 対象取引が売上でなかった場合は、ここまでで抜ける.
        if ($storedStatus !== StatusType::CAPTURED) {
            return $commitResponse;
        }

        // 再与信した取引を売上する.
        $this->captureClient->setOrder($this->Order);
        $captureResponse = $this->captureClient->handle();

        $Detail = $this->detailFactory->createByResponse($captureResponse, $StoredTrade, TradeType::CAPTURE, $this->Order->getPaymentTotal());
        $this->em->persist($Detail);

        $StoredTrade->addSbpsTradeDetail($Detail);

        $this->em->flush();

        if ($captureResponse['res_result'] != 'OK') {
            throw $this->exceptionFactory->create($captureResponse['res_err_code']);
        }

        // 再与信前の取引をリファンドする.
        $this->refundClient->setOrder($this->Order);
        $refundResponse = $this->refundClient->handle($storedTrackingId);

        $Detail = $this->detailFactory->createByResponse($refundResponse, $StoredTrade, TradeType::REFUND, $this->Order->getPaymentTotal());
        $this->em->persist($Detail);

        $StoredTrade->addSbpsTradeDetail($Detail);

        $this->em->flush();

        if ($refundResponse['res_result'] != 'OK') {
            throw $this->exceptionFactory->create($refundResponse['res_err_code']);
        }

        return $refundResponse;
    }

    public function createParameter(): array
    {
        $Config = $this->configRepository->get();

        $rowItems = $this->tradeHelper->getRowItems($this->Order);

        // 顧客コード
        if ($this->Order->getCustomer()) {
            $cust_code = $this->Order->getCustomer()->getId();
        } else {
            // 非会員時の顧客ID：プレフィクス + ランダムかつユニークな文字列のmd5ハッシュ値
            $cust_code = 'reg_sps' . md5(uniqid(rand(), true));
        }

        $param = [
            'merchant_id' => $Config->getMerchantId(),
            'service_id' => $Config->getServiceId(),
            'tracking_id' => $this->Order->getSbpsTrade()->getTrackingId(),
            'cust_code' => $cust_code,
            'order_id' => $this->tradeHelper->createOrderId($this->Order).'_'.$this->tradeHelper->createRequestDate(),
            'item_id' => 'ec-cube',
            'item_name' => '',    // XXX これを含めるとエラーになる.
            'tax' => (int)$this->Order->getTax(),
            'amount' => (int)$this->Order->getPaymentTotal(),
            'sps_cust_info_return_flg' => '1',
            'pay_option_manage' => [
                'cust_manage_flg' => 0,
                'pay_info_control_type' => 'B',
                'pay_info_detail_control_type' => 'B',
            ],
            'encrypted_flg' => '1',
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];

        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        $param['item_name'] = base64_encode($param['item_name']);

        return $param;
    }

    public function setOrder(Order $Order): ReauthClient
    {
        $this->Order = $Order;
        return $this;
    }
}
