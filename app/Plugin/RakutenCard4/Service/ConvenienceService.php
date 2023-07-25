<?php

namespace Plugin\RakutenCard4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Request\Context;
use Plugin\RakutenCard4\Common\ConstantCvs;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Repository\ConfigRepository;
use Plugin\RakutenCard4\Repository\Rc4OrderPaymentRepository;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ConvenienceService extends BasePaymentService implements PaymentServiceInterface
{
    /** @var \Twig\Environment */
    protected $twig;

    /** @var OrderRepository */
    protected $orderRepository;

    /** @var \DateTime */
    protected $expirationDate;

    public function __construct(
        Context                   $context,
        ContainerInterface        $container,
        ConfigRepository          $configRepository,
        PaymentRepository         $paymentRepository,
        EccubeConfigEx            $eccubeConfig,
        \Twig\Environment         $twig,
        RouteService              $routeService,
        Rc4OrderPaymentRepository $orderPaymentRepository,
        EntityManagerInterface    $entityManager,
        OrderRepository            $orderRepository
    )
    {
        parent::__construct($container, $configRepository, $paymentRepository, $eccubeConfig, $routeService, $orderPaymentRepository, $entityManager, $context);
        $this->twig = $twig;
        $this->orderRepository = $orderRepository;
        $this->setPaymentKind();
    }

    /**
     * 支払い方法種類を設定
     */
    protected function setPaymentKind()
    {
        $this->payment_kind = self::PAYMENT_KIND_CVS;
    }

    /**
     * Server to Server で コンビニ 決済処理リクエストを送信します。
     * コンビニ決済リクエスト が成功した場合、収納番号等の情報が 返却されます 。
     *
     * @param Order $Order
     * @return bool
     */
    public function Authorize(Order $Order)
    {
        if ($this->context->isAdmin()) {
            // 管理画面では与信処理ができなくする
            return false;
        }

        /** @var Rc4OrderPayment $OrderPayment */
        $OrderPayment = $Order->getRc4OrderPayment();
        if (empty($OrderPayment) || $OrderPayment->isCard()) {
            return false;
        }

        $logData = ['order_id' => $Order->getId()];
        Rc4LogUtil::info('コンビニ決済--与信処理：start', $logData);

        $now = new \DateTime();

        $request_data = $this->getAuthorizeData($Order, $now);
        Rc4LogUtil::info('コンビニ決済--与信処理：リクエストデータ作成', $logData);

        // 未決済ステータスは以外は終了
        if ($OrderPayment->getPaymentStatus() === ConstantPaymentStatus::First) {
            // API実行
            $result = $this->sendCvsRequest($this->getAuthorizeUrl(), $request_data);
            // 共通の登録処理
            $this->registerResponseCommon($Order);
            if ($result) {
                // テーブルにセット
                $this->registerCvsAuthorizeResponse($OrderPayment, $request_data, $now);
            } else {
                $this->registerResponseError($Order);
                Rc4LogUtil::info('コンビニ決済--与信処理：失敗もしくは保留', $logData);
            }
            $this->entityManager->flush($OrderPayment);
            Rc4LogUtil::info('コンビニ決済--与信処理：完了', $logData);

            return $result;
        }
        return false;
    }


    /**
     * @param Rc4OrderPayment $OrderPayment
     * @param $sendData
     * @param $responseResult
     * @param \DateTime|null $dateTime
     */
    private function registerCvsAuthorizeResponse(&$OrderPayment, $sendData, \DateTime $dateTime = null)
    {
        $response = $this->getResponse();
        $OrderPayment->setRequestId($response['agencyRequestId']);
        $OrderPayment->setFirstTransactionDate(new \DateTime());
        $OrderPayment->setPayAmount($sendData['cvsPayment']['amount']);
        $OrderPayment->setFirstTransactionDate($dateTime);

        $code = null;
        $CvsInfo = $this->getCvsInfoList();
        $code = $CvsInfo[0]['cvsCode'];
        $cvsNumber = $CvsInfo[0]['reference'];

        $OrderPayment->setCvsCode($code);
        $OrderPayment->setCvsExpirationDate($this->expirationDate);
        $OrderPayment->setCvsNumber($cvsNumber);

        // 成功時の処理
        // 与信ステータスを入れる
        $this->registerResponseSuccess($OrderPayment);
        $OrderPayment->setAuthorizeDate($dateTime);
        $OrderPayment->setPaymentStatus(ConstantPaymentStatus::Authorized);

    }


    public function Capture(Order $Order)
    {
        // コンビニ決済に売上処理はない
        // 結果通知にて売上となる
        return false;
    }

    /**
     * 結果通知用の売上処理
     *
     * @param Order $Order
     */
    public function receiveCapture(Order $Order)
    {
        $logData = ['order_id' => $Order->getId()];
        Rc4LogUtil::info('コンビニ決済--売上処理：start', $logData);

        /** @var Rc4OrderPayment $OrderPayment */
        $OrderPayment = $Order->getRc4OrderPayment();
        // チェックがすべてOKなので、ここでは通知を正とする
        // 与信ステータスは以外は終了
//        if ($OrderPayment->getPaymentStatus() === ConstantPaymentStatus::Authorized) {
//            $result = $this->isResultSuccess();
            // 共通の登録処理
            $this->registerResponseCommon($Order);

//            if ($result) {
                // 成功時の処理
                if ($this->isPaymentStatusCaptured()) {
                    // 入金通知処理
                    $this->registerCaptureResponse($Order);
                } elseif($this->isPaymentStatusInitialized()) {
                    // 期限切れ通知処理
                    $this->registerExpiredResponse($Order);
                } elseif ($this->isPaymentStatusAuthorized()){
                    Rc4LogUtil::info('コンビニ決済--売上処理：authorizedが届く', $logData);
                }
//            } else {
//                $this->registerResponseError($Order);
//                Rc4LogUtil::info('コンビニ決済--売上処理：失敗もしくは保留', $logData);
//            }
            $this->entityManager->flush($OrderPayment);

            Rc4LogUtil::info('コンビニ決済--売上処理：完了', $logData);
//            return $result;
//        }

//        return false;
    }

    /**
     * 決済ステータスが “Authorized”の時にトランザクションの取り消し（キャンセル）を行うことができます。
     *
     * @param Order $Order
     * @return bool
     */
    public function CancelOrRefund(Order $Order)
    {
        /** @var Rc4OrderPayment $OrderPayment */
        $OrderPayment = $Order->getRc4OrderPayment();
        if (empty($OrderPayment) || $OrderPayment->isCard()) {
            return false;
        }

        $logData = ['order_id' => $Order->getId()];
        Rc4LogUtil::info('コンビニ決済--取消処理：start', $logData);

        $request_data = $this->getCancelOrRefundData($Order);
        Rc4LogUtil::info('コンビニ決済--取消処理：リクエストデータ作成', $logData);

        // 与信ステータスは以外は終了
        if ($OrderPayment->getPaymentStatus() === ConstantPaymentStatus::Authorized) {
            // リクエスト
            $result = $this->sendCvsRequest($this->getCancelOrRefundUrl(), $request_data);

            // 共通の登録処理
            $this->registerResponseCommon($Order);

            if ($result) {
                // 成功時の処理
                $this->registerCancelOrRefundResponse($Order);
            } else {
                $this->registerResponseError($Order);
                Rc4LogUtil::info('コンビニ決済--取消処理：失敗もしくは保留', $logData);
            }
            $this->entityManager->flush($OrderPayment);

            Rc4LogUtil::info('コンビニ決済--取消処理：完了', $logData);
            return $result;
        }

        return false;
    }

    /**
     * 受注での検索処理
     *
     * @param Order[] $Orders
     */
    public function Find($Orders)
    {
        if (is_array($Orders)) {
            $Orders = [$Orders];
        }
        $payment_ids = [];
        foreach ($Orders as $order) {
            $OrderPayment = $order->getRc4OrderPayment();
            $payment_ids[] = $order->getId();
        }
        $request_data = $this->requestCommonFind($payment_ids);
        return $this->sendCvsRequest($this->getFindUrl(), $request_data);
    }

    /**
     *　Authorizeデータ項目
     * @param Order $Order
     * @return array
     */
    private function getAuthorizeData(Order $Order, \DateTime $now = null)
    {
        /** @var Customer $Customer */
        $Customer = $Order->getCustomer();
        $customerId = null;
        if(!empty($Customer)){
            $customerId = $Customer->getId();
            $name02 = $Customer->getName02();
            $name01 = $Customer->getName01();
            $phoneNumber = $Customer->getPhoneNumber();
        } else {
            $name02 = $Order->getName02();
            $name01 = $Order->getName01();
            $phoneNumber = $Order->getPhoneNumber();
        }

        $Rc4OrderPayment = $Order->getRc4OrderPayment();

        $request_data = $this->getCvsCommonData($now);

        // コンビニ決済用の弊社より発行、提供されるサブサービスID です。契約したコンビニ決済代行毎にサブサービスID は発行されます 。
        if (isset($Rc4OrderPayment)) {
            switch ($Rc4OrderPayment->getCvsKind()) {
                case ConstantCvs::CVS_KIND_SEVENELEVEN;
                    $request_data['subServiceId'] = $this->config->getCvsSubServiceIdSeven();
                    break;
                case ConstantCvs::CVS_KIND_LAWSON;
                    $request_data['subServiceId'] = $this->config->getCvsSubServiceIdLawson();
                    break;
                case ConstantCvs::CVS_KIND_DAILYYAMAZAKI;
                    $request_data['subServiceId'] = $this->config->getCvsSubServiceIdYamazaki();
                    break;
                default:
                    break;
            }
        }

        $request_data['paymentId'] = $Rc4OrderPayment->getTransactionId();
        $request_data['serviceReferenceId'] = $Order->getOrderNo();
        $request_data['agencyCode'] = self::AGENCY_CODE;
        $request_data['custom'] = $this->createCustomText($customerId);
        $request_data['currencyCode'] = self::CURRENCY_CODE;

        $request_data['grossAmount'] = intval($Order->getPaymentTotal());
        $request_data['notificationUrl'] = $this->getUrl('rakuten_card4_receive_cvs_status'); // 通知先URL 必須　

        $request_data['order']['version'] = self::ORDER_VERSION;
        $request_data['order']['email'] = self::ORDER_EMAIL;
        $request_data['order']['ipAddress'] = $this->getIpAddress(); // ブラウザのIPを確認
        $request_data['order']['customer']['firstName'] = $this->changeZenkaku($name02, 40); // 名
        $request_data['order']['customer']['lastName'] = $this->changeZenkaku($name01, 40); // 性
        $request_data['order']['customer']['phone'] = $phoneNumber;
        $request_data['order']['items'][0]['name'] = $this->eccubeConfig->cvs_items_name();
        $request_data['order']['items'][0]['id'] = $this->eccubeConfig->cvs_items_id();
        $request_data['order']['items'][0]['price'] = intval($Order->getPaymentTotal());
        $request_data['order']['items'][0]['quantity'] = self::ORDER_ITEMS_QUANTITY;

        $request_data['cvsPayment']['version'] = self::CVS_PAYMENT_VERSIN;
        $request_data['cvsPayment']['amount'] = intval($Order->getPaymentTotal());

        // Jst取引日付を生成
        $now = $this->getJtsTime($now);
        $request_data['cvsPayment']['orderDate'] = intval($now->format('Ymd'));
        $this->setExpirationDate($now);
        $request_data['cvsPayment']['expirationDate'] = intval($this->getExpirationDate());
        $request_data['cvsPayment']['addHandlingFee'] = false;

        return $request_data;
    }

    /**
     * コンビニ用のリクエスト
     *
     * @param string $url
     * @param array $request_data
     * @return bool
     */
    private function sendCvsRequest($url, $request_data)
    {
        return $this->sendRequest($url, $request_data, self::CONTENT_TYPE_URL, $this->getAuthKey(),self::KEY_VERSION_COL_TYPE_CVS_API );
    }

    /**
     * サービスIDの取得
     * @return string|null
     */
    public function getServiceId()
    {
        return $this->config->getCvsServiceId();
    }

    /**
     * 認証キーの取得
     *
     * @return string|null
     */
    protected function getAuthKey()
    {
        return $this->config->getCvsAuthKey();
    }

    /**
     *　CancelOrRefundデータ項目
     * @param Order $Order
     * @return array
     */
    private function getCancelOrRefundData(Order $Order)
    {
        /** @var Rc4OrderPayment $OrderPayment */
        $OrderPayment = $Order->getRc4OrderPayment();
        $now = new \DateTime();

        $request_data = $this->getCvsCommonData($now);
        $request_data['paymentId'] = $OrderPayment->getTransactionId();
        return $request_data;
    }

    /**
     *　検索用の配列を取得
     *
     * @param Order $Order
     * @return array
     */
    private function getFindData($Orders)
    {
        $now = new \DateTime();
        $request_data = $this->getCvsCommonData($now);
        $request_data['searchType'] = self::SEARCH_TYPE_CURRENT;

        foreach ($Orders as $Order) {
            /** @var Rc4OrderPayment $OrderPayment */
            $OrderPayment = $Order->getRc4OrderPayment();
            $TransactionId = $OrderPayment->getTransactionId();
            $request_data['paymentIds'][$TransactionId];
        }

        return $request_data;
    }

    /**
     * コンビニ共通データ項目
     * @param \DateTime|null $now
     * @return array
     */
    private function getCvsCommonData(\DateTime $now = null)
    {
        $request_data['serviceId'] = $this->config->getCvsServiceId(); // 弊社より発行し提供される貴社専用のIDです。
        $request_data['timestamp'] = $this->getUtcTimeyyyyMMddHHmmssSSS();;
        return $request_data;
    }


    /**
     * successなレスポンスからコンビニ収納番号リストを取得する
     * @return array
     */
    protected function getCvsInfoList()
    {
        $result = [];
        if (!$this->isResponseResult() || !array_key_exists('reference', $this->response)) {
            return $result;
        }

        $reference = $this->response['reference'];
        if (!array_key_exists('rakutenCardResult', $reference)) {
            return $result;
        }

        $rakutenCardResult = $reference['rakutenCardResult'];
        foreach ($rakutenCardResult as $cvsInfoList) {
            foreach ($cvsInfoList as $cvsInfo) {
                $result[] = $cvsInfo;
            }
        }

        return $result;
    }

    /**
     * JST現在時刻を設定
     *
     * @param \DateTime|null $dateTime
     * @return string
     */
    public function getJtsTime(\DateTime $dateTime = null)
    {
        if (is_null($dateTime)) {
            $dateTime = new \DateTime();
        }
        $dateTime->setTimezone(new \DateTimeZone('JST'));
        return $dateTime;
    }

    /**
     * 現在日時 ＋ n日を設定
     * @return void
     */
    protected function setExpirationDate(\DateTime $dateTime = null)
    {
        $TransactionTime = $this->config->getCvsLimitDay();
        $dateTime = clone $this->getJtsTime($dateTime);
        $dateTime->modify('+' . $TransactionTime . 'day');
        $this->expirationDate = $dateTime;
    }

    /**
     * 現在日時 ＋ n日を取得
     * @param string $format 日にち出力フォーマット
     * @return array
     */
    protected function getExpirationDate($format = 'Ymd')
    {
        return $this->expirationDate->format($format);
    }

    /**
     * 各コンビニの説明文を取得する
     *
     * @param string $cvscode コンビニコード
     * @return string 本文
     */
    protected function getCvsGuidance($cvscode, $email_flg = false)
    {
        if ($email_flg){
            $path = '@RakutenCard4/cvs/cvs_mail_' . $cvscode . '.twig';
        }else{
            $path = '@RakutenCard4/cvs/cvs_' . $cvscode . '.twig';
        }
        return $this->twig->render($path, []);
    }

    /**
     * 購入完了時、各コンビニの説明文章を取得する
     *
     * @param boolean $email_flg
     * @return string
     */
    public function createShippingCompleteCvsMessage($email_flg = false)
    {
        $messageList = [];
        $messageList[] = trans('rakuten_card4.admin.order_edit.payment_cvs');

        if ($this->isResponseResult()) {
            $CvsInfoList = $this->getCvsInfoList();
            foreach ($CvsInfoList as $CvsInfo) {
                if (array_key_exists('cvsCode', $CvsInfo)) {
                    $cvsCode = $CvsInfo['cvsCode'];
                    $message = '';

                    if (array_key_exists('reference', $CvsInfo)) {
                        $message .= '収納番号：' . $CvsInfo['reference'] . "\n";
                    }
                    $message .= 'お支払い期限：' . $this->getExpirationDate('Y/m/d') . "\n";
                    $message .= trans('rakuten_card4.cvs.label.kind' . $cvsCode) . "でのお支払い\n\n";

                    // コンビニ説明文取得
                    $message .= $this->getCvsGuidance($cvsCode, $email_flg) . "\n\n";
                    $messageList[] = $message;
                }
            }
        }
        return implode("\n", $messageList);
    }

    public function isRequestTypeAuthorize()
    {
        return $this->getCvsRequestType() == 'authorize';
    }

    public function isRequestTypeCapture()
    {
        return $this->getCvsRequestType() == 'capture';
    }

    public function isRequestTypeCancelOrRefund()
    {
        return $this->getCvsRequestType() == 'cancel_or_refund';
    }

    /**
     * コンビニレスポンスからrequestTypeを返す（authorize/capture/cancel_or_refundが返る）
     * @return false|mixed
     */
    protected function getCvsRequestType()
    {
        if (!empty($this->response)) {
            if (array_key_exists('requestType', $this->response)) {
                return $this->response['requestType'];
            }
        }
        return false;
    }


    public function isPaymentStatusCaptured()
    {
        return $this->getCvsPaymentStatusType() == 'captured';
    }

    public function isPaymentStatusAuthorized()
    {
        return $this->getCvsPaymentStatusType() == 'authorized';
    }

    public function isPaymentStatusInitialized()
    {
        return $this->getCvsPaymentStatusType() == 'initialized';
    }

    /**
     * コンビニレスポンスからpaymentStatusTypeを返す（initialized/authorized/captured）
     * @return false|mixed
     */
    protected function getCvsPaymentStatusType()
    {
        if (!empty($this->response)) {
            if (array_key_exists('paymentStatusType', $this->response)) {
                return $this->response['paymentStatusType'];
            }
        }
        return false;
    }

    /**
     * コンビニレスポンスから対象受注を取得
     * @return false|mixed
     */
    public function getOrder()
    {
        if (!empty($this->response)) {
            if (array_key_exists('serviceReferenceId', $this->response)) {
                $orderNo = $this->response['serviceReferenceId'];
                return $Order = $this->orderRepository->findOneBy([
                    'order_no' => $orderNo,
                ]);
            }
        }
        return false;
    }

    /**
     * 期限切れ時の登録共通処理
     *
     * @param Order $Order
     */
    protected function registerExpiredResponse($Order)
    {
        $OrderPayment = $Order->getRc4OrderPayment();

        $this->registerResponseSuccess($OrderPayment);
        $OrderPayment->setPaymentStatus(ConstantPaymentStatus::ExpiredCvs);
        $OrderPayment->setCancelDate(new \DateTime());
    }

}
