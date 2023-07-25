<?php

namespace Plugin\RakutenCard4\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Repository\PaymentRepository;
use Eccube\Request\Context;
use Plugin\RakutenCard4\Common\ConstantConfig;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Entity\Config;
use Plugin\RakutenCard4\Entity\Rc4CustomerToken;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Entity\Rc4TokenEntityInterface;
use Plugin\RakutenCard4\Repository\ConfigRepository;
use Plugin\RakutenCard4\Repository\Rc4CustomerTokenRepository;
use Plugin\RakutenCard4\Repository\Rc4OrderPaymentRepository;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Plugin\RakutenCard4\Util\Rc4CommonUtil;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class BasePaymentService
{
    const AUTH_KEY_VERSION = 1;
    const AGENCY_CODE = 'rakutencard';
    const CURRENCY_CODE = 'JPY';
    const ORDER_VERSION = '1';
    const ORDER_EMAIL = 'rakuten@card.cc';
    const ORDER_ITEMS_QUANTITY = 1;
    const CVS_PAYMENT_VERSIN = '1';
    const CARD_TOKEN_VERSION = '2';
    const CARD_TOKEN_VERSION_FOR_AUTH_HTML = '3';
    const ISO_JAPAN_CODE = 392;
    const SEARCH_TYPE_CURRENT = 'current';
    const CONTENT_TYPE_URL = 'application/x-www-form-urlencoded';

    const PAYMENT_KIND_CARD = 'Card';
    const PAYMENT_KIND_PAY_VAULT = 'PayVault';
    const PAYMENT_KIND_CVS = 'Convenience';
    protected $payment_kind;
    protected $connection_mode;

    const API_URL_CARD_SERVICE_PARAM = 'rakuten_card4.api_url.card.';
    const API_URL_CVS_SERVICE_PARAM = 'rakuten_card4.api_url.cvs.';
    const API_URL_PAYVAULT_JS_SERVICE_PARAM = 'rakuten_card4.javascript_url.payvault.';

    const KEY_VERSION_COL_TYPE_API = 1;
    const KEY_VERSION_COL_TYPE_AUTH_HTML = 2;
    const KEY_VERSION_COL_TYPE_CVS_API = 3;
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var Config
     */
    protected $config;

    /** @var PaymentRepository */
    protected $paymentRepository;

    /** @var EccubeConfigEx */
    protected $eccubeConfig;

    /** @var RouteService */
    protected $routeService;

    /** @var Rc4OrderPaymentRepository */
    protected $orderPaymentRepository;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var Context */
    protected $context;

    protected $response;

    /** @var \DateTime */
    protected $dateTime;

    /**
     * ConfigController constructor.
     *
     * @param ConfigRepository $configRepository
     */
    public function __construct(ContainerInterface        $container,
                                ConfigRepository          $configRepository,
                                PaymentRepository         $paymentRepository,
                                EccubeConfigEx            $eccubeConfig,
                                RouteService              $routeService,
                                Rc4OrderPaymentRepository $orderPaymentRepository,
                                EntityManagerInterface    $entityManager,
                                Context                   $context
    )
    {
        $this->container = $container;
        $this->configRepository = $configRepository;
        $this->paymentRepository = $paymentRepository;
        $this->config = $configRepository->get();
        $this->eccubeConfig = $eccubeConfig;
        $this->routeService = $routeService;
        $this->orderPaymentRepository = $orderPaymentRepository;
        $this->entityManager = $entityManager;
        // prod もしくは stgを設定
        $this->setConnectionMode();
        $this->context = $context;

        $this->dateTime = null;
    }

    /**
     * 決済情報に最低限のデータを入れて作成
     *
     * @return Rc4OrderPayment
     */
    public function createOrderPaymentRequireSet()
    {
        $OrderPayment = new Rc4OrderPayment();
        $OrderPayment->setPaymentStatus(ConstantPaymentStatus::First);
        $OrderPayment->setCardCheckRegister(false);
        return $OrderPayment;
    }

    /**
     * 支払い方法種類を設定
     */
    protected function setPaymentKind()
    {
        $this->payment_kind = self::PAYMENT_KIND_CVS;
    }

    /**
     * API接続先の設定 (prod or stg)
     */
    private function setConnectionMode()
    {
        $typeName = '';
        switch ($this->config->getConnectionMode()) {
            case ConstantConfig::CONNECTION_MODE_PROD:
                $typeName = ConstantConfig::CONNECTION_MODE_PROD_NAME;
                break;
            case ConstantConfig::CONNECTION_MODE_STG:
                $typeName = ConstantConfig::CONNECTION_MODE_STG_NAME;
                break;
            default:
                break;
        }
        $this->connection_mode = $typeName;
    }

    /**
     * service.yamlからURLを取得する
     * @param string $field
     * @return mixed|string
     */
    private function getApiUrl($field = '')
    {
        $connectionMode = rtrim($this->connection_mode, '.') . '.';

        switch ($this->payment_kind) {
            case self::PAYMENT_KIND_CARD:
                // カード
                $key = self::API_URL_CARD_SERVICE_PARAM . $connectionMode . $field;
                break;
            case self::PAYMENT_KIND_PAY_VAULT:
                // ペイボルト
                $key = self::API_URL_PAYVAULT_JS_SERVICE_PARAM . $connectionMode . $field;
                break;
            case self::PAYMENT_KIND_CVS:
            default:
                // コンビニ
                $key = self::API_URL_CVS_SERVICE_PARAM . $connectionMode . $field;
                break;
        }

        $url = isset($this->eccubeConfig[$key]) ? $this->eccubeConfig[$key] : '';
        return $url;
    }

    /**
     * 与信URL取得
     * @return string
     */
    public function getAuthorizeUrl()
    {
        return $this->getApiUrl('authorize');
    }

    /**
     * 売上URL取得
     * @return string
     */
    public function getCaptureUrl()
    {
        return $this->getApiUrl('capture');
    }

    /**
     * 金額変更URL取得
     * @return string
     */
    public function getModifyUrl()
    {
        return $this->getApiUrl('modify');
    }

    /**
     * 取消URL取得
     * @return string
     */
    public function getCancelOrRefundUrl()
    {
        return $this->getApiUrl('cancel_or_refund');
    }

    /**
     * 与信同時売上URL取得
     * @return string
     */
    public function getAuthorizeAndCaptureUrl()
    {
        return $this->getApiUrl('authorize_and_capture');
    }

    /**
     * クレカの3Dセキュア与信URL取得
     * @return string
     */
    public function getAuthorizeHtmlUrl()
    {
        return $this->getApiUrl('authorize.html');
    }

    /**
     * クレカの3Dセキュア与信同時売上URL取得
     * @return string
     */
    public function getAuthorizeAndCaptureHtmlUrl()
    {
        return $this->getApiUrl('authorize_and_capture.html');
    }

    /**
     * クレカ、コンビニの3Dセキュア時のタイムアウト処理で利用するURL取得
     * @return string
     */
    public function getFindUrl()
    {
        return $this->getApiUrl('find');
    }

    /**
     * クレカ、コンビニの3Dセキュア時のタイムアウト処理で利用するURL取得
     * @return string
     */
    public function getPayVaultTokenUrl()
    {
        $before_kind = $this->payment_kind;
        $this->payment_kind = self::PAYMENT_KIND_PAY_VAULT;
        $url = $this->getApiUrl('token');
        $this->payment_kind = $before_kind;

        return $url;
    }

    /**
     * リクエストPOST値変換
     *
     * @param array $request_data
     * @param string $authKey
     * @param int $key_type
     * @return array
     */
    protected function changePostDataRequest($request_data, $authKey, $key_type = self::KEY_VERSION_COL_TYPE_API)
    {
        switch ($key_type) {
            case self::KEY_VERSION_COL_TYPE_AUTH_HTML:
            case self::KEY_VERSION_COL_TYPE_CVS_API:
                $key_col = 'key_version';
                break;
            case self::KEY_VERSION_COL_TYPE_API:
            default:
                $key_col = 'keyversion';
                break;
        }

        $request_data = Rc4CommonUtil::encodeJson($request_data);

        $post_data = array(
            'paymentinfo' => $this->base64Enc($request_data),
            'signature' => $this->getSignature($request_data, $authKey),
            $key_col => self::AUTH_KEY_VERSION
        );

        return $post_data;
    }

    /**
     * インタフェースの送受信を行う
     *
     * @param string $url リクエスト先
     * @param array $sendData 送信データ配列
     * @return mixed 処理結果
     */
    protected function sendRequest($url, array $sendData, $contentType = '', $authKey = null, $key_type = self::KEY_VERSION_COL_TYPE_API)
    {
        if (empty($authKey)) {
            return false;
        }
        $curl = curl_init();

        $response = null;
        $error_message = '';
        try {
            $post_data = $this->changePostDataRequest($sendData, $authKey, $key_type);

            curl_setopt($curl, CURLOPT_URL, $url);
            curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));
            curl_setopt($curl, CURLOPT_POST, TRUE);
            curl_setopt($curl, CURLOPT_HTTPHEADER, array($contentType));
            curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // curl_exec() の返り値を 文字列で返すように設定
            curl_setopt($curl, CURLOPT_HEADER, 0); // ヘッダ出力

            $response = curl_exec($curl);
        } catch (\Exception $e) {
            $error_message = $e->getMessage();
            Rc4LogUtil::error($error_message);
        } finally {
            curl_close($curl);
        }

        // エラー処理
        if (strlen($error_message) > 0) {
            $response = [];
            $response['resultType'] = 'failure';
            $response['errorCode'] = 'api_error';
            $response['errorMessage'] = $error_message;
            $this->setResponse($response);
            return false;
        }

        // レスポンスデコード
        $response = Rc4CommonUtil::decodeJson($response);
        if ($response === false) {
            $response = [];
            $response['resultType'] = 'failure';
            $response['errorCode'] = 'api_error';
            $response['errorMessage'] = 'no data';
            $this->setResponse($response);
            return false;
        }

        // レスポンスを設定
        $this->setResponse($response);

        // successだったかどうかを返す
        return $this->isResultSuccess();
    }

// TODO 削除
//    /**
//     * レスポンスのチェック処理（API1.2で仕様が変わり不要になる）
//     *
//     * @param string $response
//     * @param string $authKey
//     * @return array
//     * @throws ResponseCheckException
//     */
//    private function checkResponseData($response, $authKey)
//    {
//        $response = Rc4CommonUtil::decodeData($response);
//        $paymentResult = $this->base64Dec($response['paymentResult']);
//        // チェックはシグネチャーが一致すること
//        $isSuccess = $this->checkSignature($paymentResult, $response['signature'], $authKey);
//
//        if (!$isSuccess){
//            throw new ResponseCheckException();
//        }
//
//        return Rc4CommonUtil::decodeData($paymentResult);
//    }

    /**
     * シグネチャーチェック
     *
     * @param string $recode
     * @param string $signature
     * @param string $auth_key
     * @return bool
     */
    protected function checkSignature($recode, $signature, $auth_key)
    {
        $checkSignature = $this->getSignature($recode, $auth_key);
        // チェックはシグネチャーが一致すること
        $isSuccess = $signature == $checkSignature;

        return $isSuccess;
    }

    /**
     * チェック用の文字列の取得
     *
     * @param string $json_data
     * @param string $authKey
     * @return false|string
     */
    private function getSignature($json_data, $authKey)
    {
        return hash_hmac("sha256", $json_data, $authKey);
    }

    /**
     * 配列、CustomTextキーにセットする文章の取得
     * 会員の場合「会員ID」
     * 会員ID：xxx
     * 非会員の場合「非会員」
     * @param int $textLength
     * @param string $character
     * @return array
     */
    protected function createCustomText($customerId)
    {
        if (is_null($customerId)) {
            $customText = 'non-member';
        } else {
            $customText = $customerId;
        }
        return ['customer_id' => $customText];
    }

    /**
     * 配列、PaymentIdキーにセットする
     * 会員：C＋会員ID（9）＋ランダム文字列（5）＋時間（14）
     * 非会員：O＋受注ID（9）＋ランダム文字列（5）＋時間（14）
     * を生成する
     * @param int $textLength
     * @param string $character
     * @return string
     */
    protected function createPaymentId($customerId, $orderId, $now)
    {
        // ランダム文字＋時間
        $text = $this->createRandTextTypeMt() . $now->format('YmdHis');

        $PaymentId = '';
        if (isset($customerId)) {
            $PaymentId = 'C' . $customerId . $text;
        } else {
            $PaymentId = 'O' . $orderId . $text;
        }
        return $PaymentId;
    }

    /**
     * ルート→URL変換
     *
     * @param string $route
     * @param array $parameters
     * @return string
     */
    protected function getUrl($route, $parameters = [])
    {
        return $this->container->get('router')->generate($route, $parameters, UrlGeneratorInterface::ABSOLUTE_URL);
    }

    /**
     * APIから取得したレスポンスをセット
     * @param $response
     */
    protected function setResponse($response)
    {
        $this->response = $response;
    }

    public function getResponse()
    {
        return $this->response;
    }

    /**
     * レスポンスから通信が成功したかの結果を取得（true / false）
     * @return bool
     */
    protected function isResponseResult()
    {
        $result = false;

        switch ($this->getResultType()) {
            case 'success':
                $result = true;
                break;
        }

        return $result;
    }

    public function isResultSuccess()
    {
        return $this->getResultType() == 'success';
    }

    public function isResultFailure()
    {
        return $this->getResultType() == 'failure';
    }

    public function isResultPending()
    {
        return $this->getResultType() == 'pending';
    }

    /**
     * レスポンスからresultTypeを返す（success/failure/pending）
     * @return false|mixed
     */
    public function getResultType()
    {
        if (!empty($this->response)) {
            if (array_key_exists('resultType', $this->response)) {
                return $this->response['resultType'];
            }
        }
        return false;
    }

    public function isPaymentStatusInitialized()
    {
        return $this->getResponseStatusType() == 'initialized';
    }

    public function isPaymentStatusAuthorized()
    {
        return $this->getResponseStatusType() == 'authorized';
    }

    public function isPaymentStatusCaptured()
    {
        return $this->getResponseStatusType() == 'captured';
    }

    /**
     * レスポンスからpaymentStatusTypeを返す（initialized/authorized/captured）
     * @return false|mixed
     */
    public function getResponseStatusType()
    {
        if (!empty($this->response)) {
            $col = 'paymentStatusType';
            if (isset($this->response[$col])) {
                return $this->response[$col];
            }
        }
        return false;
    }

    /**
     * レスポンスからpaymentIdを返す
     * TransactionIdとしているのはEC内でPaymentIdはすでに存在するため
     *
     * @return false|string
     */
    public function getResponseTransactionId()
    {
        if (!empty($this->response)) {
            $col = 'paymentId';
            if (isset($this->response[$col])) {
                return $this->response[$col];
            }
        }
        return false;
    }

    /**
     * レスポンスからエラー類を返す
     * @return false|mixed
     */
    public function getError()
    {
        $errorCode = '';
        $errorMessage = '';
        if (empty($this->response)) {
            return [];
        }

        if (isset($this->response['errorCode'])) {
            $errorCode = $this->response['errorCode'];
        }
        if (isset($this->response['errorMessage'])) {
            $errorMessage = $this->response['errorMessage'];
        }

        return [$errorCode, $errorMessage];
    }

    /**
     * 表示用のエラーメッセージの取得
     *
     * @return mixed|string|null
     */
    public function getDisplayErrorMsg()
    {
        if (empty($this->response)) {
            return '';
        }

        list($errorCode, $errorMessage) = $this->getError();
        if (empty($errorCode)){
            return '';
        }

        return $this->getErrorMessage($errorCode);
    }

    /**
     * エラー時のログ出力
     *
     * @param string $message
     */
    public function writeLogResponseFailure($message)
    {
        list($errorCode, $errorMessage) = $this->getError();
        $display_error = $this->getDisplayErrorMsg();
        Rc4LogUtil::error($message, [
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage,
            'display_error' => $display_error,
        ]);
    }

    /**
     * エラー時のログ出力
     *
     * @param string $message
     */
    public function writeLogResponsePending($message, $display_error)
    {
        Rc4LogUtil::error($message, [
            'resultType' => 'pending',
            'display_error' => $display_error,
        ]);
    }

    /**
     * レスポンスから対象キーを削除
     *
     * @param array $response
     * @param int $level
     */
    protected function unsetResponseKey(&$response, $level=0)
    {
        if (empty($response)) {
            return;
        }

        if ($level > 10){
            // 10階層までの階層は存在しないため、何らかのエラーがあると判断して戻す
            return;
        }

        $unset_columns = $this->getUnsetResponseColumn();
        foreach ($unset_columns as $column)
        {
            if (isset($response[$column])){
                Rc4LogUtil::debug('unsetカラム削除', [
                    'level' => $level,
                    'column' => $column,
//                    'value' => $response[$column],
                ]);
                unset($response[$column]);
            }
        }

        foreach ($response as $column=>&$value){
            if (is_array($value)){
                $this->unsetResponseKey($value, $level + 1);
            }else if (is_null($value)){
                Rc4LogUtil::debug('nullカラム削除', [
                    'level' => $level,
                    'column' => $column,
                ]);
                unset($response[$column]);
            }
        }
    }

    /**
     * Base64エンコードを行う。
     * エンコード後の文字列を"/"を"-"に、"+"を"_"に、"="を"*"に置換して返却する。
     *
     * @access public
     * @param string $data エンコードする文字列
     * @return string エンコードを行った結果の文字列
     */
    protected function base64Enc($data)
    {
        $data = base64_encode($data);
//        $data = str_replace("/", "-", $data);
//        $data = str_replace("+", "_", $data);
//        $data = str_replace("=", "*", $data);

        return $data;
    }

    /**
     * Base64デコードを行う。
     * base64Encの逆。
     * デコード前の文字列を"-"を"/"に、"_"を"+"に、"*"を"="に置換してからデコードする。
     *
     * @access public
     * @param string $data デコードする文字列
     * @return mixed|string デコードを行った結果の文字列
     */
    protected function base64Dec($data)
    {
//        $data = str_replace("*", "=", $data);
//        $data = str_replace("_", "+", $data);
//        $data = str_replace("-", "/", $data);
        $data = base64_decode($data);

        return $data;
    }

    /**
     * メルセンヌツイスターでのランダム文字を生成
     * @param int $textLength
     * @param string $character
     * @return string
     */
    protected function createRandTextTypeMt(int $textLength = 5, $character = 'abcdefghkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ2345679-_.')
    {

        // 乱数表のシードを決定
        mt_srand((double)microtime() * 54234853); //mt

        // 出力可能文字列の配列を作成
        $output = preg_split('//', $character, 0, PREG_SPLIT_NO_EMPTY);

        $result = '';
        for ($i = 0; $i < $textLength; $i++) {
            $result .= $output[mt_rand(0, count($output) - 1)];
        }

        return $result;
    }

    /**
     * UTCをセットする
     *
     * @param \DateTime $dateTime
     * @return \DateTime
     */
    protected function setUtcForDateTime(\DateTime $dateTime)
    {
        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        return $dateTime;
    }

    /**
     * JSTをセットする
     *
     * @param \DateTime $dateTime
     * @return \DateTime
     */
    protected function setJSTForDateTime(\DateTime $dateTime)
    {
        $dateTime->setTimezone(new \DateTimeZone('Asia/Tokyo'));
        return $dateTime;
    }

    /**
     * 現在時刻のUTC版(yyyyMMddHHmmssSSS)
     *
     * @param \DateTime|null $dateTime
     * @return string
     */
    public function getUtcTimeyyyyMMddHHmmssSSS(\DateTime $dateTime=null)
    {
        return $this->getUtcTime('Y-m-d H:i:s.v', $dateTime);
    }

    /**
     * 現在時刻のUTC版(yyyyMMddHHmmss)
     *
     * @param \DateTime|null $dateTime
     * @return string
     */
    public function getUtcTimeyyyyMMddHHmmss(\DateTime $dateTime=null)
    {
        return $this->getUtcTime('YmdHis', $dateTime);
    }

    /**
     * 現在時刻のUTC版
     *
     * @param string $format
     * @param \DateTime|null $dateTime
     * @return string
     */
    private function getUtcTime($format, \DateTime $dateTime=null)
    {
        if (is_null($dateTime)){
            // 共通化する
            if (is_null($this->dateTime)){
                $this->dateTime = new \DateTime();
            }
            $dateTime = $this->dateTime;
        }

        $changeDate = clone $dateTime;
        $changeDate = $this->setUtcForDateTime($changeDate);

        return $changeDate->format($format);
    }

    /**
     * IPアドレスを取得して返す
     *
     * @return string
     */
    protected function getIpAddress()
    {
        $request = $this->routeService->getRequest();
        return $request->getClientIp();
    }

    /**
     * @param Rc4TokenEntityInterface $TokenEntity
     */
    public function setTokenEntityCommon($TokenEntity, $Order = null, $Customer = null)
    {
        $TokenEntity->setIpAddress($this->getIpAddress());
        $order_id = null;
        if (!is_null($Order)) {
            $order_id = $Order->getId();
        }
        $customer_id = null;
        if (!is_null($Customer)) {
            $customer_id = $Customer->getId();
        }
        if (strlen($TokenEntity->getTransactionId()) > 0){
            // すでに入っていたら更新しない
            return;
        }

        $now = new \DateTime();
        $TokenEntity->setTransactionId($this->createPaymentId($customer_id, $order_id, $now));
    }

    /**
     * 通知時の売上通知の名称
     *
     * @param string $method_class
     * @return string
     */
    public function getNotificationCaptureName($method_class)
    {
        $name = '';
        switch ($method_class){
            case CreditCard::class:
                $name = '売上通知';
                break;
            case Convenience::class:
                $name = '入金通知';
                break;
        }
        return $name;
    }

    /**
     * レスポンスから受注を取得
     *
     * @return \Eccube\Entity\Order|null
     */
    public function getOrderFromResponse()
    {
        /** @var Rc4OrderPayment $OrderPayment */
        $OrderPayment = $this->getTokenEntityFromResponse($this->orderPaymentRepository);
        if (is_null($OrderPayment)){
            return null;
        }

        $response = $this->getResponse();
        $order_id_col = 'serviceReferenceId';

        // サービス管理IDが存在する場合、細かくチェック
        if (isset($response[$order_id_col])) {
            if ($OrderPayment->getOrder()->getOrderNo() != $response[$order_id_col]) {
                Rc4LogUtil::error('通知もしくはAuthorize HTMLのレスポンスにて決済IDから受注データを取得できましたが、注文時の受注番号と異なります。');
                return null;
            }
        }
        return $OrderPayment->getOrder();
    }

    /**
     * responseからデータオブジェクトを生成
     *
     * @param Rc4OrderPaymentRepository|Rc4CustomerTokenRepository $Repository
     * @return Rc4OrderPayment|Rc4CustomerToken|null
     */
    protected function getTokenEntityFromResponse($Repository)
    {
        $response = $this->getResponse();
        $pay_id_col = 'paymentId';

        // 決済IDから受注を取得する
        if (isset($response[$pay_id_col])) {
            $TokenEntity = $Repository->findOneBy(['transaction_id' => $response[$pay_id_col]], ['id' => 'DESC']);
        } else {
            Rc4LogUtil::error('通知もしくはAuthorize HTMLのレスポンスにて決済IDカラム：「' . $pay_id_col . '」が存在しません');
            return null;
        }
        if (is_null($TokenEntity)) {
            Rc4LogUtil::error('通知もしくはAuthorize HTMLのレスポンスにて決済IDから受注データを取得できませんでした。');
            return null;
        }

        return $TokenEntity;
    }

    /**
     * レスポンスの共通登録処理
     *
     * @param Order $Order
     */
    protected function registerResponseCommon($Order)
    {
        $OrderPayment = $Order->getRc4OrderPayment();

        // 共通処理
        $OrderPayment->setLastTransactionDate(new \DateTime());
        $this->registerResponseCommonToken($OrderPayment);
    }

    /**
     * トークンエンティティ
     *
     * @param Rc4TokenEntityInterface $TokenEntity
     */
    public function registerResponseCommonToken($TokenEntity)
    {
        $response = $this->getResponse();
        $this->unsetResponseKey($response);
        $TokenEntity->addPaymentLog($response);
        $TokenEntity->setPaymentInfoLog($response);
    }

    /**
     * レスポンスの共通登録処理（成功）
     *
     * @param $OrderPayment
     * @param $paymentStatus
     */
    protected function registerResponseSuccess($OrderPayment)
    {
        $OrderPayment->setErrorCode(null);
        $OrderPayment->setErrorMessage(null);
    }

    /**
     * レスポンスの共通登録処理（エラー）
     *
     * @param Order $Order
     */
    protected function registerResponseError($Order)
    {
        $OrderPayment = $Order->getRc4OrderPayment();

        $error = $this->getError();
        if (!empty($error)) {
            list($code, $message) = $error;
            $OrderPayment->setErrorCode($code);
            $OrderPayment->setErrorMessage($message);
        }
        // 結果が保留の場合は保留にする
        if ($this->isResultPending()) {
            $OrderPayment->setPaymentStatus(ConstantPaymentStatus::Pending);
        }
    }

    /**
     * 実売上時の登録共通処理
     *
     * @param Order $Order
     */
    protected function registerCaptureResponse($Order)
    {
        $OrderPayment = $Order->getRc4OrderPayment();

        $this->registerResponseSuccess($OrderPayment);
        $OrderPayment->setPaymentStatus(ConstantPaymentStatus::Captured);
        $OrderPayment->setCaptureDate(new \DateTime());
    }

    /**
     * キャンセル時の登録共通処理
     *
     * @param Order $Order
     */
    protected function registerCancelOrRefundResponse($Order)
    {
        $OrderPayment = $Order->getRc4OrderPayment();

        $this->registerResponseSuccess($OrderPayment);
        $OrderPayment->setPaymentStatus(ConstantPaymentStatus::Canceled);
        $OrderPayment->setCancelDate(new \DateTime());
    }

    /**
     * 金額変更時の登録共通処理
     *
     * @param Order $Order
     */
    protected function registerModifyResponse($Order)
    {
        $OrderPayment = $Order->getRc4OrderPayment();

        $this->registerResponseSuccess($OrderPayment);
        $OrderPayment->setPayAmount($Order->getPaymentTotal());
    }

    /**
     * 共通の検索処理
     *
     * @param array $payment_ids
     * @return bool|mixed
     */
    protected function requestCommonFind($payment_ids)
    {
        if (count($payment_ids) == 0) {
            return false;
        }
        return $request_data = $this->getFindData($payment_ids);
    }

    /**
     * マルチバイト文字を分割する
     * 任意の文字数と任意の個数に
     *
     * @param string $subject 文字列
     * @param int $one_length 1つの要素に何文字入れるか
     * @param int $count 配列をいくつ入れるか
     * @return array
     */
    public function splitWords($subject, $one_length, $count){
        $allWords = preg_split("//u", $subject, -1, PREG_SPLIT_NO_EMPTY);
        $word_cnt = count($allWords);
        $max_len = $one_length * $count;
        $word_cnt = $word_cnt < $max_len ? $word_cnt: $max_len;
        $words = array_fill(0, $count, '');
        for($i=0; $i < $word_cnt; $i++){
            $word_idx = intval(floor($i / $one_length));
            $words[$word_idx] .= $allWords[$i];
        }
        return $words;
    }

    /**
     * マルチバイト文字を分割する
     * 任意の文字数と任意の個数に
     *
     * @param string $subject 文字列
     * @param int $one_byte_length 1つの要素に何文字入れるか
     * @param int $count 配列をいくつ入れるか
     * @return array
     */
    public function splitWordsForByte($subject, $one_byte_length, $count){
        $words = array_fill(0, $count, '');
        $start = 0;
        for($i=0; $i < $count; $i++){
            $temp = mb_strcut($subject, $start, $one_byte_length, 'UTF-8');
            $words[$i] = $temp;
            $start += strlen($temp);
        }

        return $words;
    }

    /**
     * APIでバイト制限がある部分でカットするプログラム
     *
     * @param string $subject
     * @param int $byte_len
     * @return string
     */
    public function wordLimitByte($subject, $byte_len)
    {
        return mb_strcut($subject, 0, $byte_len, 'UTF-8');
    }

    /**
     * ログ設定前にresponseから取り消すカラム
     *
     * @return string[]
     */
    protected function getUnsetResponseColumn()
    {
        return [
            'serviceId',
            'subServiceId',
        ];
    }

    /**
     * 管理画面で表示するエラーリスト
     * 各支払い方法で異なる場合はオーバーライドする
     *
     * @return array
     */
    protected function getAdminErrorList()
    {
        return $this->eccubeConfig->admin_error_list_card();
    }

    /**
     * フロントで表示するエラーリスト
     * 各支払い方法で異なる場合はオーバーライドする
     *
     * @return array
     */
    protected function getFrontErrorList()
    {
        return $this->eccubeConfig->front_error_list_card();
    }

    /**
     * エラーリストの全体を取得
     *
     * @return array
     */
    public function getErrorList()
    {
        $error_list = $this->getFrontErrorList();
        if ($this->context->isAdmin()){
            $error_list = $this->getAdminErrorList();
        }
        return $error_list;
    }

    /**
     * エラーメッセージの取得
     *
     * @param string $error_code
     * @return mixed|string|null
     */
    public function getErrorMessage($error_code)
    {
        if (empty($error_code)){
            return '';
        }

        // リスト内にある場合
        $error_list = $this->getErrorList();
        if (isset($error_list[$error_code])){
            return trans($error_list[$error_code]);
        }

        // リスト内にない場合
        $error_message = $this->eccubeConfig->front_error_common();
        if ($this->context->isAdmin()){
            $error_message = $this->eccubeConfig->admin_error_common();
        }
        return trans($error_message);
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
     * AuthorizeHtml・通知の戻りのチェック
     *
     * @param Request $request
     * @param bool $notification_flg true: 通知、false: AuthorizeHtml
     */
    public function checkReceiveRequest(Request $request, $notification_flg=false)
    {
        $response_data_col = $notification_flg ? 'paymentinfo' : 'paymentResult';

        $paymentResult = $request->get($response_data_col);
        $signature = $request->get('signature');

        $decode_result = $this->base64Dec($paymentResult);

        // チェックはシグネチャーが一致すること
        $isSuccess = $this->checkSignature($decode_result, $signature, $this->getAuthKey());
        if (!$isSuccess) {
            $this->setResponseSignatureError();
            return;
        }

        // 成功した場合にレスポンスデータを返す
        $this->setResponse(Rc4CommonUtil::decodeData($decode_result));
    }

    /**
     * シグネチャーエラー時に設定するエラーレスポンス
     */
    public function setResponseSignatureError()
    {
        $this->setResponse([
            'resultType' => 'failure',
            'errorCode' => 'ec_signature_error',
            'errorMessage' => 'signature is not same',
        ]);
    }

    /**
     * 全角変換
     *
     * @param string $subject
     * @param int $byte_len
     * @return string
     */
    public function changeZenkaku($subject, $byte_len=0)
    {
        $convert = mb_convert_kana($subject, 'KASV', 'utf-8');

        $byte_len = intval($byte_len);
        if ($byte_len > 0){
            return $this->wordLimitByte($convert, $byte_len);
        }else{
            return $convert;
        }
    }

    /**
     * 機種依存文字チェック（JISX0208 表記：01区〜08区、16区〜47区、48区〜84区）
     *
     * @param string $subject 文字列
     * @return array ヒットした文字を配列で返す
     */
    public function checkWordJisx0208($subject)
    {
        $wide_word = $this->changeZenkaku($subject);
        $allWords = preg_split("//u", $wide_word, -1, PREG_SPLIT_NO_EMPTY);
        $invalid_words = [];
        for($i=0; $i < count($allWords); $i++){
            $target_utf8 = $allWords[$i];
            $target_win = mb_convert_encoding($target_utf8, "windows-31j", 'utf-8');

            if ((strlen(bin2hex($target_win)) / 2) == 1){
                // 1バイト文字は存在しないはず
                continue;
            }

            $byte1 = ord($target_win{0});
            $byte2 = ord($target_win{1});

            $check_byte = ($byte1 & 0xff) << 8 | $byte2 & 0xff;
            // 1-8 wards
            if ($check_byte >= 0x813f && $check_byte < 0x853f) {
                continue;
            }
            // 13 wards
//            if ($check_byte >= 0x873f && $check_byte < 0x879e) {
//                continue;
//            }
            // 16-84 wards
            if ($check_byte >= 0x889e && $check_byte < 0xeb3f) {
                continue;
            }

            $invalid_words[] = $target_utf8;
        }
        return $invalid_words;
    }

}
