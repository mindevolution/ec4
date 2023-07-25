<?php

namespace Plugin\RakutenCard4\Service\Method;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\RakutenCard4\Entity\Config;
use Plugin\RakutenCard4\Repository\ConfigRepository;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\RouteService;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\HttpFoundation\Response;

/**
 * クレジットカード(トークン決済)の決済処理を行う.
 */
class CreditCard extends AbstractMethod implements PaymentMethodInterface
{
    /** @var CardService */
    protected $paymentService;
    /** @var RouteService */
    protected $routeService;
    /** @var Config */
    protected $config;

    /**
     * CreditCard constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param EntityManagerInterface $entityManager
     * @param CardService $paymentService
     * @param RouteService $routeService
     * @param ConfigRepository $configRepository
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository
        , PurchaseFlow $shoppingPurchaseFlow
        , EntityManagerInterface $entityManager
        , CardService $paymentService
        , RouteService $routeService
        , ConfigRepository $configRepository
    ) {
        $this->routeService = $routeService;
        $this->config = $configRepository->get();
        parent::__construct(
            $orderStatusRepository
            , $shoppingPurchaseFlow
            , $entityManager
            , $paymentService
        );
    }

    /**
     * 各メソッドで継承して設定する
     */
    protected function setPayResultErrorCommonKey()
    {
        $this->pay_result_error_common_key = 'rakuten_card4.shopping.checkout.error';
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * クレジットカードの有効性チェック時の独自処理
     *
     * @return bool
     */
    protected function verifyUnique()
    {
        // 入力タイプもしくは登録済みカードでセキュリティコード入力が必須の場合この処理を通る
        $OrderPayment = $this->Order->getRc4OrderPayment();
        $is_token_use =
            $OrderPayment->isUseCardInput() ||
            $OrderPayment->isUseCardRegister() && $this->config->isCardCvvUse();
        if ($is_token_use){
            $cardForm = $this->paymentService->getCardForm();
            $cardForm->handleRequest($this->routeService->getRequest());
            $token_data = $cardForm->getData();
            // シグネチャーチェック
            $isSuccess = $this->paymentService->checkTokenSignature($token_data);
            if ($this->paymentService->isResultFailure()){
                $this->setErrorContents('verify unique： payvault で failure 戻る');
                return false;
            }
            if (!$isSuccess){
                $this->paymentService->setResponseSignatureError();
                $this->setErrorContents('payvault からの戻りでシグネチャーが一致しない');
                return $isSuccess;
            }

            // テーブルへの登録
            $this->paymentService->registerTokenInfo($OrderPayment, $token_data);
        }
        return true;
    }

    /**
     * 注文時に呼び出される.(カード決済独自処理)
     *
     * 受注ステータス, 決済ステータスを更新する.
     *
     * @return bool
     */
    protected function applyUnique()
    {
        return true;
    }

    /**
     * 注文時に呼び出される.
     *
     * クレジットカードの決済処理を行う.
     *
     * @return boolean
     */
    protected function sendRequest()
    {
        // 3Dセキュアであってもなくてもここでの処理を行わない
        return true;
    }

    /**
     * 与信処理後、画面遷移や別画面になるときにレスポンスを入れる
     *
     * @return PaymentResult
     */
    protected function getResponseAfterRequest()
    {
        Rc4LogUtil::info('Authorize HTMLの処理 開始');

        // リクエストデータ作成
        $sendData = $this->paymentService->AuthorizeHtml($this->Order);

        // URLを設定
        $send_url = $this->paymentService->isBuyApiAuth() ?
            $this->paymentService->getAuthorizeHtmlUrl() :
            $this->paymentService->getAuthorizeAndCaptureHtmlUrl();

        // レスポンスデータ作成
        $contents = $this->paymentService->getContentsAuthHtml($sendData, $send_url);

        $result = new PaymentResult();
        $result->setSuccess(true);
        $result->setResponse(Response::create($contents));

        Rc4LogUtil::info('Authorize HTMLのレスポンス生成');

        return $result;
    }

    /**
     * 購入完了画面およびメールのメッセージの設定
     */
    protected function setCompleteMessage()
    {
        // Authorize HTMLですべてを完結するため、ここは通らないが
        // ご動作しないために何も追記しない
        $message = '';
        $this->Order->appendCompleteMessage($message);
        $this->Order->appendCompleteMailMessage($message);
    }

}
