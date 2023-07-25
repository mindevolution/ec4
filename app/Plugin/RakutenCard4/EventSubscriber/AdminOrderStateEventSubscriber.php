<?php

namespace Plugin\RakutenCard4\EventSubscriber;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Exception;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\ConvenienceService;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Workflow\Event\Event;

class AdminOrderStateEventSubscriber implements EventSubscriberInterface
{

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var mixed
     */
    protected $routeName;

    /**
     * @var CardService
     */
    protected $cardService;

    /**
     * @var ConvenienceService
     */
    protected $convenienceService;

    /**
     * 管理画面->受注管理->出荷CSV登録のルート名
     */
    const ADMIN_SHIPPING_RAKUTEN_CSV_IMPORT_ROUTE_NAME = 'admin_shipping_rakuten_csv_import';


    /**
     * AdminOrderStateEventSubscriber constructor.
     * @param EccubeConfig $eccubeConfig
     * @param ContainerInterface $container
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        ContainerInterface     $container,
        EntityManagerInterface $entityManager,
        CardService            $cardService,
        ConvenienceService     $convenienceService
    )
    {
        $this->entityManager = $entityManager;
        $request = $container->get('request_stack')->getCurrentRequest();
        $this->routeName = $request->attributes->get('_route');
        $this->cardService = $cardService;
        $this->convenienceService = $convenienceService;

    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'workflow.order.transition.ship' => ['onOrderShip'],
        ];
    }

    /**
     * 受注ステータス変更時
     * 専用CSVアップ時に実行
     * 新規受付 or 入金済み or 対応中 -> 発送済み
     * @param Event $event
     * @throws \Exception
     */
    public function onOrderShip(Event $event)
    {
        /** @var Order $Order */
        $Order = $event->getSubject()->getOrder();
        $this->changeRakutenPaymentStatusCapture($Order);
    }


    /**
     * @param Order $Order
     * @param string $nextStatus
     * @throws \Exception
     */
    protected function changeRakutenPaymentStatusCapture($Order)
    {
        if ($this->routeName !== self::ADMIN_SHIPPING_RAKUTEN_CSV_IMPORT_ROUTE_NAME) {
            return;
        }

        $common_message = '----------Csvアップロード 決済ステータス変更処理----------';
        Rc4LogUtil::info($common_message . 'start');

        $transData = ['%name%' => trans('rakuten_card4.admin.shipping_csv_upload.order_no.label',  ['%name%' => $Order->getOrderNo()])];
        if (!$this->checkPaymentStatus($Order)) {
            $message = trans('admin.order.skip_change_status', $transData);
            Rc4LogUtil::error($common_message . $message);
            $this->cardService->addCsvMessage($message);
            return;
        }

        /** @var Rc4OrderPayment $Rc4OrderPayment */
        $Rc4OrderPayment = $Order->getRc4OrderPayment();
        // ステータスが未決済のみ処理
        $message = $Order->getId();
        $result = false;
        try {
            // 実売上APIの実行
            if ($Rc4OrderPayment->isCard()) {
                if ($Rc4OrderPayment->execAbleCapture()){
                    $result = $this->cardService->Capture($Order);
                    if (!$result){
                        $message = trans('rakuten_card4.admin.shipping_csv_upload.change_payment_status.failure', $transData);
                    }
                }else{
                    $message = trans('rakuten_card4.admin.shipping_csv_upload.non_rakuten_skip.not_able_capture', $transData);
                }
            } elseif ($Rc4OrderPayment->isConenience()) {
                $message = trans('rakuten_card4.admin.order.cvs.capture.failed', $transData);
                $this->cardService->addCsvMessage($message);
                return;
            } else {
                $message = trans('admin.order.skip_change_status', $transData);
                $this->cardService->addCsvMessage($message);
                return;
            }
        } catch (Exception $e) {
            $message = trans('rakuten_card4.admin.order.cvs.capture.error', $transData);
            $result = false;
        }

        if (!$result) {
            Rc4LogUtil::error($common_message . $message);
            $this->cardService->addCsvMessage($message);
            return;
        }

        $Rc4OrderPayment->setPaymentStatus(ConstantPaymentStatus::Captured);
        $this->entityManager->flush($Rc4OrderPayment);

        $message = trans('rakuten_card4.admin.shipping_csv_upload.change_payment_status.success', $transData);
        Rc4LogUtil::info($common_message . $message);
        $this->cardService->addCsvMessage($message);
    }

    /**
     * @param Order $Order
     * @param string $nextStatus
     * @throws \Exception
     */
    protected function statusValidate($Order, $nextStatus)
    {
        if (!$this->checkPaymentStatus($Order)) {
            return;
        }

    }

    /**
     * 楽天決済の受注かチェック
     *
     * @param Order $Order
     * @param string $nextStatus
     * @throws \Exception
     */
    protected function checkPaymentStatus($Order)
    {
        if (empty($Order) || empty($Order->getId())) {
            return false;
        }

        // 楽天決済ではない場合、処理終了
        $Payment = $Order->getPayment();
        $MethodClass = $Payment->getMethodClass();
        if ($MethodClass === Convenience::class || $MethodClass === CreditCard::class) {
            if (!empty($Order->getRc4OrderPayment())) {
                return true;
            }
        }
        return false;
    }
}