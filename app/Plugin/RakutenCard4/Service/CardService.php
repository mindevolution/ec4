<?php

namespace Plugin\RakutenCard4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Repository\PaymentRepository;
use Eccube\Request\Context;
use Plugin\RakutenCard4\Common\ConstantCard;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Entity\Rc4CustomerToken;
use Plugin\RakutenCard4\Entity\Rc4TokenEntityInterface;
use Plugin\RakutenCard4\Form\Type\CardTokenType;
use Plugin\RakutenCard4\Repository\ConfigRepository;
use Plugin\RakutenCard4\Repository\Rc4CustomerTokenRepository;
use Plugin\RakutenCard4\Repository\Rc4OrderPaymentRepository;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;

class CardService extends BasePaymentService implements PaymentServiceInterface
{
    use UserTrait;

    const MODE_AUTHORIZE = 1;
    const MODE_REGISTER_CARD = 2;
    const MODE_ORDER_CARD = 3;
    protected $auth_mode = self::MODE_AUTHORIZE;
    protected $find_mode = self::MODE_ORDER_CARD;

    /** @var EccubeConfigEx */
    protected $eccubeConfig;
    /** @var FormFactoryInterface */
    protected $formFactory;
    /** @var \Twig\Environment */
    protected $twigEnvironment;
    /** @var Rc4CustomerTokenRepository */
    protected $customerTokenRepository;

    protected $csvMessage = [];

    public function __construct(
        ContainerInterface          $container
        , ConfigRepository          $configRepository
        , PaymentRepository         $paymentRepository
        , Context                   $context
        , EccubeConfigEx            $eccubeConfig
        , EntityManagerInterface    $entityManager
        , RouteService              $routeService
        , Rc4OrderPaymentRepository $orderPaymentRepository
        , \Twig\Environment         $twigEnvironment
        , Rc4CustomerTokenRepository $customerTokenRepository
    )
    {
        $this->twigEnvironment = $twigEnvironment;
        $this->customerTokenRepository = $customerTokenRepository;
        parent::__construct($container, $configRepository, $paymentRepository, $eccubeConfig, $routeService, $orderPaymentRepository, $entityManager, $context);
        $this->setPaymentKind();
    }

    /**
     * @param FormFactoryInterface $formFactory
     * @required
     */
    public function setFormFactory(FormFactoryInterface $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    public function isAuthModeRegisterCard()
    {
        return $this->auth_mode == self::MODE_REGISTER_CARD;
    }

    public function setAuthModeOnRegisterCard()
    {
        $this->auth_mode = self::MODE_REGISTER_CARD;
    }

    public function setAuthModeOnAuthorize()
    {
        $this->auth_mode = self::MODE_AUTHORIZE;
    }

    public function setFindModeOnRegisterCard()
    {
        $this->find_mode = self::MODE_REGISTER_CARD;
    }

    public function setFindModeOnOrder()
    {
        $this->find_mode = self::MODE_ORDER_CARD;
    }

    /**
     * 支払い方法種類を設定
     */
    protected function setPaymentKind()
    {
        $this->payment_kind = self::PAYMENT_KIND_CARD;
    }

    /**
     * トークン情報の共通項目の設定
     *
     * @param Rc4TokenEntityInterface $TokenEntity
     * @param int $amount
     * @param int $version
     * @param int|null $installments
     * @param boolean $bonus
     * @param boolean $revolving
     * @return array
     */
    public function getTokenCommonParam($TokenEntity, $amount, $version, $installments = null, $bonus = false, $revolving = false)
    {
        $cardToken_data['version'] = $version;
        $cardToken_data['amount'] = intval($amount);
        $cardToken_data['cardToken'] = $TokenEntity->getCardToken();
        if (!empty($TokenEntity->getCardCvvToken())) {
            $cardToken_data['cvvToken'] = $TokenEntity->getCardCvvToken();
        }
        if (!is_null($installments) && $installments >= 2) {
            $cardToken_data['installments'] = $installments;
        } else if ($bonus) {
            $cardToken_data['withBonus'] = boolval($bonus);
        } else if ($revolving) {
            $cardToken_data['withRevolving'] = boolval($revolving);
        }

        return $cardToken_data;
    }

    /**
     * 与信処理（V1用
     *
     * @param Order $Order
     * @return bool
     */
    public function Authorize(Order $Order)
    {
        $logData = ['order_id' => $Order->getId()];
        Rc4LogUtil::info('カード決済--与信処理：start', $logData);

        $OrderPayment = $Order->getRc4OrderPayment();
        $request_data = $this->getAuthorizeCommonData($Order);
        switch ($this->auth_mode) {
            case self::MODE_REGISTER_CARD:
                // 0円決済を行う
                $request_data['grossAmount'] = 0;
                $cardToken_data = $this->getTokenCommonParam($OrderPayment, 0, self::CARD_TOKEN_VERSION);
                $cardToken_data['withThreeDSecure'] = false;
                $request_data['cardToken'] = $cardToken_data;
                break;
            case self::MODE_AUTHORIZE:
            default:
                // V1での与信は管理画面でのキャンセルからの与信になる
                $cardToken_data = $this->getTokenCommonParam($OrderPayment, intval($Order->getPaymentTotal()), self::CARD_TOKEN_VERSION);
                // cvvトークンは期限切れのためここでは利用しない
                unset($cardToken_data['cvvToken']);
                $cardToken_data['withThreeDSecure'] = false;
                $request_data['cardToken'] = $cardToken_data;
                break;
        }
        Rc4LogUtil::info('カード決済--与信処理：リクエストデータ作成', $logData);

        // リクエスト
        $result = $this->sendCardRequest($this->getAuthorizeUrl(), $request_data);

        // 共通の登録処理
        $this->registerResponseCommon($Order);

        if ($result) {
            // 与信での共通処理
            $this->registerAuthorizeResponse($Order);
        } else {
            $this->registerResponseError($Order);
            Rc4LogUtil::info('カード決済--与信処理：失敗もしくは保留', $logData);
        }
        $this->entityManager->flush($OrderPayment);

        Rc4LogUtil::info('カード決済--与信処理：完了', $logData);
        return $result;
    }

    /**
     * 与信の共通情報
     *
     * @param Order|Rc4CustomerToken $Order
     * @return array
     */
    public function getAuthorizeCommonData($Order)
    {
        $is_order = $Order instanceof Order;
        if ($is_order){
            $tokenEntity = $Order->getRc4OrderPayment();
            $Customer    = $Order->getCustomer();
        }else{
            $tokenEntity = $Order;
            $Customer    = $tokenEntity->getCustomer();
        }
        $request_data = [];
        $this->setRequestCommon($request_data);
        $this->setRequestPaymentID($request_data, $tokenEntity);
        $request_data['subServiceId'] = $request_data['serviceId']; // サブサービスID
        if ($is_order) {
            $request_data['serviceReferenceId'] = $Order->getOrderNo(); // サービス側管理 ＩＤ
        }
        $request_data['agencyCode'] = self::AGENCY_CODE; // 会社コード
        $request_data['custom'] = $this->createCustomText($Customer ? $Customer->getId() : null); // カスタム
        $request_data['currencyCode'] = self::CURRENCY_CODE;
        $request_data['grossAmount'] = $is_order ? intval($Order->getPaymentTotal()) : 0; // 決済金額 0円決済の場合は上書きする
        if ($is_order){
            $tokenEntity->setPayAmount($Order->getPaymentTotal()); // API実行後は合計金額を切り替える
        }

        // 注文情報側の内容
        $order_data = [];
        $order_data['version'] = self::ORDER_VERSION;
        $order_data['email'] = self::ORDER_EMAIL;
        $order_data['ipAddress'] = $tokenEntity->getIpAddress();

        $request_data['order'] = $order_data;
        // 通知は救済の通知を受け取る
        $request_data['notificationUrl'] = $this->getUrl('rakuten_card4_card_notification_receive'); // 通知URL

        return $request_data;
    }

    public function Capture(Order $Order)
    {
        $logData = ['order_id' => $Order->getId()];
        Rc4LogUtil::info('カード決済--売上処理：start', $logData);

        // キャンセルと売上は同じリクエスト
        $OrderPayment = $Order->getRc4OrderPayment();

        // リクエストを作成
        $request_data = [];
        $this->setRequestCommon($request_data);
        $this->setRequestPaymentID($request_data, $OrderPayment);
        Rc4LogUtil::info('カード決済--売上処理：リクエストデータ作成', $logData);

        // リクエスト
        $result = $this->sendCardRequest($this->getCaptureUrl(), $request_data);

        // 共通処理
        $this->registerResponseCommon($Order);

        if ($result) {
            // 成功時の処理
            $this->registerCaptureResponse($Order);
        } else {
            $this->registerResponseError($Order);
            Rc4LogUtil::info('カード決済--売上処理：失敗もしくは保留', $logData);
        }
        $this->entityManager->flush($OrderPayment);

        Rc4LogUtil::info('カード決済--売上処理：完了', $logData);
        return $result;
    }

    public function CancelOrRefund(Order $Order)
    {
        $logData = ['order_id' => $Order->getId()];
        Rc4LogUtil::info('カード決済--取消処理：start', $logData);

        // キャンセルと売上は同じリクエスト
        $OrderPayment = $Order->getRc4OrderPayment();

        // リクエストを作成
        $request_data = [];
        $this->setRequestCommon($request_data);
        $this->setRequestPaymentID($request_data, $OrderPayment);
        Rc4LogUtil::info('カード決済--取消処理：リクエストデータ作成', $logData);

        // リクエスト
        $result = $this->sendCardRequest($this->getCancelOrRefundUrl(), $request_data);

        // 共通の登録処理
        $this->registerResponseCommon($Order);

        if ($result) {
            // 成功時の処理
            $this->registerCancelOrRefundResponse($Order);
        } else {
            $this->registerResponseError($Order);
            Rc4LogUtil::info('カード決済--取消処理：失敗もしくは保留', $logData);
        }
        $this->entityManager->flush($OrderPayment);

        Rc4LogUtil::info('カード決済--取消処理：完了', $logData);
        return $result;
    }

    public function Modify(Order $Order)
    {
        $logData = ['order_id' => $Order->getId()];
        Rc4LogUtil::info('カード決済--金額変更処理：start', $logData);
        $OrderPayment = $Order->getRc4OrderPayment();

        // リクエストを作成
        $request_data = [];
        $this->setRequestCommon($request_data);
        $this->setRequestPaymentID($request_data, $OrderPayment);
        $request_data['amount'] = $Order->getPaymentTotalInt();
        Rc4LogUtil::info('カード決済--金額変更処理：リクエストデータ作成', $logData);

        // リクエスト
        $result = $this->sendCardRequest($this->getModifyUrl(), $request_data);

        // 共通の登録処理
        $this->registerResponseCommon($Order);

        if ($result) {
            // 成功時の処理
            // 金額変更特有
            $this->registerModifyResponse($Order);
        } else {
            $this->registerResponseError($Order);
            Rc4LogUtil::info('カード決済--金額変更処理：失敗もしくは保留', $logData);
        }
        $this->entityManager->flush($OrderPayment);

        Rc4LogUtil::info('カード決済--金額変更処理：完了', $logData);
        return $result;
    }

    /**
     * 受注での検索処理
     * 事前に登録済みカードか受注のカードかを設定する必要あり
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
            switch ($this->find_mode) {
                case self::MODE_REGISTER_CARD:
                    $payment_ids[] = $OrderPayment->getCardBaseTransactionId();
                    break;
                case self::MODE_ORDER_CARD:
                default:
                    $payment_ids[] = $OrderPayment->getTransactionId();
                    break;
            }
        }
        $request_data = $this->requestCommonFind($payment_ids);
        return $this->sendCardRequest($this->getFindUrl(), $request_data);
    }

    /**
     * クレジットカード用のリクエスト
     *
     * @param string $url
     * @param array $request_data
     * @return bool
     */
    private function sendCardRequest($url, $request_data)
    {
        return $this->sendRequest($url, $request_data, self::CONTENT_TYPE_URL, $this->getAuthKey());
    }

    /**
     * 登録済みカードの検索処理
     *
     * @param Customer $Customer
     * @return bool|mixed
     */
    public function FindRegisterCard(Customer $Customer)
    {
        $payment_ids = [];

        $request_data = $this->requestCommonFind($payment_ids);
        return $this->sendCardRequest($this->getFindUrl(), $request_data);
    }

    /**
     * サービスIDの取得
     * @return string|null
     */
    public function getServiceId()
    {
        return $this->config->getCardServiceId();
    }

    /**
     * 認証キーの取得
     *
     * @return string|null
     */
    protected function getAuthKey()
    {
        return $this->config->getCardAuthKey();
    }

    /**
     * 仮売上かどうか
     *
     * @return bool true: 仮売上
     */
    public function isBuyApiAuth()
    {
        return $this->config->isCardApiAuth();
    }

    /**
     * 検索用の配列を取得
     *
     * @param array $payment_ids
     * @return array
     */
    private function getFindData($payment_ids)
    {
        if (empty($payment_ids)) {
            return [];
        }

        if (!is_array($payment_ids)) {
            $payment_ids = [$payment_ids];
        }

        $request_data = [];
        $this->setRequestCommon($request_data);
        $request_data['searchType'] = self::SEARCH_TYPE_CURRENT;
        $request_data['paymentIds'] = $payment_ids;

        return $request_data;
    }

    /**
     * 共通のリクエストを設定
     *
     * @param array $request_data
     */
    private function setRequestCommon(&$request_data)
    {
        $request_data['serviceId'] = $this->getServiceId();
        $request_data['timestamp'] = $this->getUtcTimeyyyyMMddHHmmssSSS();
    }

    /**
     * 決済IDを設定
     *
     * @param array $request_data
     * @param Rc4TokenEntityInterface $OrderPayment
     */
    private function setRequestPaymentID(&$request_data, $OrderPayment)
    {
        $request_data['paymentId'] = $OrderPayment->getTransactionId();
    }

    /**
     * @return \Symfony\Component\Form\FormInterface
     */
    public function getCardForm()
    {
        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createNamedBuilder('', CardTokenType::class);
        return $builder->getForm();
    }

    /**
     * トークンのシグネチャーチェック
     *
     * @param $token_data
     * @return bool
     */
    public function checkTokenSignature($token_data)
    {
        $this->setResponse($token_data);
        $check_col = 'signature';
        if (!isset($token_data[$check_col])){
            return false;
        }

        $record = $this->getTokenCheckRecord($token_data);
        return $this->checkSignature($record, $token_data[$check_col], $this->getAuthKey());
    }

    /**
     * トークンのチェック用のレコード
     *
     * @param $token_data
     * @return string
     */
    private function getTokenCheckRecord($token_data)
    {
        $message = [];
        $addMsg = function ($col) use (&$message, $token_data) {
            if (!isset($token_data[$col])) {
                return;
            }

            $message[] = "{$col}=\"{$token_data[$col]}\"";
        };

        // 以下は順番に意味がある
        $addMsg('timestamp');
        $addMsg('cardToken');
        $addMsg('iin');
        $addMsg('last4digits');
        $addMsg('expirationMonth');
        $addMsg('expirationYear');
        $addMsg('brandCode');
        $addMsg('issuerCode');
        $addMsg('cardType');
        $addMsg('cvvToken');

        return implode(';', $message);
    }

    /**
     * トークン情報を設定する
     *
     * @param Rc4TokenEntityInterface $TokenEntity
     * @param array $token_data
     */
    public function registerTokenInfo($TokenEntity, $token_data)
    {
        $col = 'cardToken';
        if (isset($token_data[$col])) {
            $TokenEntity->setCardToken($token_data[$col]);
        }
        $col = 'cvvToken';
        if (isset($token_data[$col])) {
            $TokenEntity->setCardCvvToken($token_data[$col]);
        }
        $col = 'brandCode';
        if (isset($token_data[$col])) {
            $brand = $token_data[$col];
            if (isset(ConstantCard::BRAND_LIST[$brand])) {
                $TokenEntity->setCardBrand(ConstantCard::BRAND_LIST[$brand]);
            }
        }
        $col = 'iin';
        if (isset($token_data[$col])) {
            $TokenEntity->setCardIin($token_data[$col]);
        }
        $col = 'last4digits';
        if (isset($token_data[$col])) {
            $TokenEntity->setCardLast4digits($token_data[$col]);
        }
        $col = 'expirationMonth';
        if (isset($token_data[$col])) {
            $TokenEntity->setCardMonth($token_data[$col]);
        }
        $col = 'expirationYear';
        if (isset($token_data[$col])) {
            $TokenEntity->setCardYear($token_data[$col]);
        }

        // トークン情報をログに設定
        $this->unsetResponseKey($token_data);
        $TokenEntity->addPaymentLog($token_data);
    }

    /**
     * 与信処理のレスポンス用データ取得（V2用）
     *
     * @param Order $Order
     * @return array
     */
    public function AuthorizeHtml(Order $Order)
    {
        $OrderPayment = $Order->getRc4OrderPayment();
        $request_data = $this->getAuthorizeCommonData($Order);
        // API種類を登録（API実行時と戻りでさいが生じないため）
        $OrderPayment->setCardBuyApiKind($this->config->getCardBuyApi());

        // コールバックURLの設定
        $request_data['callbackUrl'] = $this->getUrl('rakuten_card4_authorize_html_receive');

        // トークン用のパラメータを設定
        // 分割、ボーナス払い、リボ払の設定をする
        $installments = null;
        $bonus = false;
        $revolving = false;
        // if文にしなくても
        // どれか一つしか入ることはないが、可能性をなくすために分岐させる
        if ($OrderPayment->isWithBonus()){
            // ボーナス払い
            $bonus = true;
        }elseif ($OrderPayment->isWithRevolving()){
            // リボ払い
            $revolving = true;
        }elseif ($OrderPayment->isInstallments()){
            // 分割回数
            $installments = $OrderPayment->getCardInstallment();
        }
        $token_data = $this->getTokenCommonParam($OrderPayment, $Order->getPaymentTotal(), self::CARD_TOKEN_VERSION_FOR_AUTH_HTML, $installments, $bonus, $revolving);
        $request_data['cardToken'] = $token_data;

        // 3Dセキュア用の設定を行う
        $this->set3dSecureData($request_data, $Order);

        // リクエスト用のデータ
        return $this->getAuthHtmlPostData($request_data);
    }

    /**
     * AuthorizeHtml用のコンテンツを返す
     *
     * @param array $sendData
     * @param string $send_url
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function getContentsAuthHtml($sendData, $send_url)
    {
        return $this->twigEnvironment->render(
            '@RakutenCard4/card_auth_html.twig',
            [
                'post_data' => $sendData,
                'send_url' => $send_url
            ]
        );
    }

    /**
     * AuthorizeHtml用のレスポンスを返す
     *
     * @param array $request_data
     * @return array
     */
    public function getAuthHtmlPostData($request_data)
    {
        $post_data = $this->changePostDataRequest($request_data, $this->getAuthKey(), self::KEY_VERSION_COL_TYPE_AUTH_HTML);
        return $post_data;
    }

    /**
     * 3Dセキュアの項目設定を行う
     *
     * @param array $request_data
     * @param Order|Rc4CustomerToken $Order
     */
    public function set3dSecureData(&$request_data, $Order)
    {
        if ($this->config->isCard3dSecureUse()){
            $request_data['cardToken']['threeDSecure'] = $this->get3dSecureParam($Order);
        }
    }

    /**
     * 3Dセキュアのパラメータの設定
     *
     * @param Order|Rc4CustomerToken $Order
     * @return array
     */
    private function get3dSecureParam($Order)
    {
        $param = [];
        if ($is_order = $Order instanceof Order){
            $tokenEntity = $Order->getRc4OrderPayment();
        }else{
            $tokenEntity = $Order;
            $Order = $tokenEntity->getCustomer();
        }

        // マーチャントID
        switch ($tokenEntity->getCardBrand()){
            case ConstantCard::BRAND_VISA:
                $merchant_id = $this->config->getCardMerchantIdVisa();
                break;
            case ConstantCard::BRAND_MASTER_CARD:
                $merchant_id = $this->config->getCardMerchantIdMasterCard();
                break;
            default:
                $merchant_id = '';
                break;
        }
        if (!empty($merchant_id)){
            $param['merchantId'] = $merchant_id;
        }
        // マーチャント名
        if (strlen($this->config->getCard3dStoreName()) > 0){
            $param['merchantName'] = $this->wordLimitByte($this->config->getCard3dStoreName(), 40);
        }

        $param['authenticationIndicatorType'] = ConstantCard::AUTH_INDICATOR_PAYMENT; // 固定で良いかは確認が必要
        $param['messageCategoryType'] = ConstantCard::MSG_CATEGORY_PAY;
        $param['transactionType'] = ConstantCard::TRANSACTION_TYPE;
        $param['purchaseDate'] = $this->getUtcTimeyyyyMMddHHmmss();
        $param['challengeIndicatorType'] = $this->config->getCardChallengeIndicator();

        $holder['billingAddressCountry'] = self::ISO_JAPAN_CODE;
        $holder['billingAddressState'] = $Order->getPref()->getId();
        if ($this->checkAddrLength($Order->getAddr01(), $Order->getAddr02())){
            // 住所の入力文字数以内であれば入れる
            $holder['billingAddressCity'] = $Order->getAddr01();
            $holder['billingAddressLine1'] = $Order->getAddr02();
        }
//        $holder['billingAddressLine2'] = '';
        $holder['billingAddressPostCode'] = $Order->getPostalCode();
        $Shipping = null;
        if ($is_order){
            foreach ($Order->getShippings() as $value){
                $Shipping = $value;
                break;
            }
        }
        if (!is_null($Shipping)){
            $holder['shippingAddressCountry'] = self::ISO_JAPAN_CODE;
            $holder['shippingAddressState'] = $Shipping->getPref()->getId();
            if ($this->checkAddrLength($Shipping->getAddr01(), $Shipping->getAddr02())) {
                // 住所の入力文字数以内であれば入れる
                $holder['shippingAddressCity'] = $Shipping->getAddr01();
                $holder['shippingAddressLine1'] = $Shipping->getAddr02();
            }
//            $holder['shippingAddressLine2'] = '';
            $holder['shippingAddressPostCode'] = $Shipping->getPostalCode();
        }
        if (false){
            // カード氏名を入力することはないが項目だけ残しておく
            $holder['cardHolderName'] = '';
        }
        $holder['email'] = $Order->getEmail();

        $param['cardHolderInformation'] = $holder;
        return $param;
    }

    /**
     * 住所チェック
     *
     * @param string $addr01
     * @param string $addr02
     * @return bool
     */
    protected function checkAddrLength($addr01, $addr02)
    {
        return strlen($addr01) <= 50 && strlen($addr02) <= 50;
    }

    /**
     * Authorize HTML の戻り対応
     *
     * @param Order $Order
     */
    public function AuthorizeHtmlResponse($Order)
    {
        // 共通のレスポンス取得処理を実行
        $this->registerResponseCommon($Order);
        $this->registerAuthorizeResponse($Order);

        // 即時売上の場合を加味して決済ステータスを上書きする
        $OrderPayment = $Order->getRc4OrderPayment();
        if (!$OrderPayment->isCardBuyApiAuth()){
            $OrderPayment->setPaymentStatus(ConstantPaymentStatus::Captured);
            $OrderPayment->setCaptureDate(new \DateTime());
        }

        $this->entityManager->flush($OrderPayment);
    }

    /**
     * 与信の共通処理
     *
     * @param Order $Order
     */
    private function registerAuthorizeResponse($Order)
    {
        $response = $this->getResponse();
        $OrderPayment = $Order->getRc4OrderPayment();
        if ($this->context->isFront()) {
            // フロント共通処理
            $OrderPayment->setRequestId($response['agencyRequestId']);
            $brand_code = $response['card']['brandCode'];
            if (isset(ConstantCard::BRAND_LIST[$brand_code])) {
                $OrderPayment->setCardBrand(ConstantCard::BRAND_LIST[$brand_code]);
            } else {
                // エラー
            }
            // フロントで通るときは毎回初回にする
            $OrderPayment->setFirstTransactionDate(new \DateTime());
        }

        // 成功時の処理
        // 与信ステータスを入れる
        $this->registerResponseSuccess($OrderPayment);
        $OrderPayment->setAuthorizeDate(new \DateTime());
        $OrderPayment->setPaymentStatus(ConstantPaymentStatus::Authorized);
    }

    /**
     * レスポンスからのデータ取得
     *
     * @return Rc4CustomerToken|null
     */
    public function getCustomerFromResponse()
    {
        /** @var Rc4CustomerToken $CustomerToken */
        $CustomerToken = $this->getTokenEntityFromResponse($this->customerTokenRepository);
        if (is_null($CustomerToken)){
            return null;
        }

        return $CustomerToken;
    }

    /**
     * 登録済みカードの取得（フロント用）
     *
     * @return Rc4CustomerToken[]
     */
    public function getFrontRegisterCards()
    {
        /** @var Customer $Customer */
        $Customer = $this->getUser();
        return $this->customerTokenRepository->getRegisterCardList($Customer);
    }

    /**
     * カードが登録可能かどうか
     *
     * @param Customer $Customer
     * @return bool true: カードが登録可能
     */
    public function ableRegisterCard($Customer, $refresh_flg=false)
    {
        if (is_null($Customer)){
            return false;
        }

        $count = $this->getRegisterCardCount($Customer, $refresh_flg);
        return $count < $this->eccubeConfig->card_register_count();
    }

    /**
     * 会員の登録済みカード数
     *
     * @param Customer $Customer
     * @return int
     */
    public function getRegisterCardCount($Customer, $refresh_flg=false)
    {
        $list = $this->customerTokenRepository->getRegisterCardList($Customer, $refresh_flg);
        return count($list);
    }

    /**
     * カードの登録があるかどうか
     *
     * @param Customer $Customer
     * @return bool true: カード情報あり
     */
    public function isExistRegisterCard($Customer)
    {
        $list = $this->customerTokenRepository->getRegisterCardList($Customer);
        return count($list) > 0;
    }

    /**
     * 設定画面で設定された回数を取得
     *
     * @return array
     */
    public function getConfigInstallments()
    {
        $temp = $this->config->getCardInstallmentsEx();
        $installments = $this->changeInstallments($temp);

        return $installments;
    }

    /**
     * 支払い方法のリストをラベル付のものに変換する
     *
     * @param array $list
     * @return array
     */
    public function changeInstallments($list)
    {
        $installments = [];
        foreach ($list as $value)
        {
            switch ($value){
                case 1:
                    $label = trans('rakuten_card4.admin.card.config.installments.all');
                    break;
                case ConstantCard::WITH_BONUS:
                case ConstantCard::WITH_REVOLVING:
                    $label = trans(ConstantCard::INSTALLMENTS_LABEL[$value]);
                    break;
                default:
                    $label = trans('rakuten_card4.admin.card.config.installments.word', ['%count%' => $value]);
            }
            $installments[$value] = $label;
        }
        ksort($installments);
        return $installments;
    }

    /**
     * ログ設定前にresponseから取り消すカラム
     *
     * @return string[]
     */
    protected function getUnsetResponseColumn()
    {
        $unset_columns = array_merge(
            parent::getUnsetResponseColumn(),
            [
                'cardToken',
                'iin',
                'last4digits',
                'expirationMonth',
                'expirationYear',
                'brandCode',
                'cardBrand',
                'cvvToken',
            ]
        );

        return array_unique($unset_columns);
    }

    /**
     * @return array
     */
    public function getCsvMessage()
    {
        return $this->csvMessage;
    }

    /**
     * @param array $csvMessage
     */
    public function addCsvMessage(?string $message)
    {

        $this->csvMessage[] = $message;
    }
}
