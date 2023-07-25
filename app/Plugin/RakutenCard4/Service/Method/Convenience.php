<?php

namespace Plugin\RakutenCard4\Service\Method;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\RakutenCard4\Service\ConvenienceService;
use Plugin\RakutenCard4\Util\Rc4LogUtil;

/**
 * コンビニ払いの決済処理を行う
 */
class Convenience extends AbstractMethod implements PaymentMethodInterface
{
    /** @var ConvenienceService */
    protected $paymentService;

    /**
     * コンビニ決済 constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param EntityManagerInterface $entityManager
     * @param ConvenienceService $paymentService
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository
        , PurchaseFlow $shoppingPurchaseFlow
        , EntityManagerInterface $entityManager
        , ConvenienceService $paymentService
    ) {
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
        $this->pay_result_error_common_key = 'rakuten_card4.shopping.cvs.error';
    }

    protected function checkOrder()
    {
        $PaymentResult = parent::checkOrder();
        if (!is_null($PaymentResult)){
            return $PaymentResult;
        }

        // 機種既存文字チェック
        $subject = $this->Order->getName01() . $this->Order->getName02();
        $invalid_words = $this->paymentService->checkWordJisx0208($subject);
        if (count($invalid_words) > 0){
            $result = new PaymentResult();
            $result->setSuccess(false);
            $result->setErrors([trans('rakuten_card4.shopping.cvs.invalid.word.error')]);

            Rc4LogUtil::error('機種依存文字が存在します。', [
                'invalid_word' => implode(" ", $invalid_words)
            ]);
            return $result;
        }

        return null;
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * コンビニ決済はほぼ不要？
     *
     * @return bool
     */
    protected function verifyUnique()
    {
        return true;
    }

    /**
     * 注文時に呼び出される.(コンビニ決済独自処理)
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
     * コンビニ決済の与信処理を行う.
     *
     * @return boolean
     */
    protected function sendRequest()
    {
        // TODO 決済サーバに仮売上のリクエスト送る(設定等によって送るリクエストは異なる)
        // ...
        //
        $api_result = $this->paymentService->Authorize($this->Order);

        if ($this->paymentService->isResultFailure()){
            $this->setErrorContents('send request： Convenience で failure 戻る');
            return false;
        }
        // 3Dセキュアであれば、ここでは処理しないなどする


        return $api_result;
    }

    /**
     * 与信処理後、画面遷移や別画面になるときにレスポンスを入れる
     *
     * @return PaymentResult
     */
    protected function getResponseAfterRequest()
    {
        // コンビニ決済ではない想定
        return null;
    }

    /**
     * 購入完了画面およびメールのメッセージの設定
     */
    protected function setCompleteMessage()
    {
        // 画面追加
        $message = $this->paymentService->createShippingCompleteCvsMessage(false);
        $page_message = "<div style='text-align:left;'>{$message}</div>";
        $this->Order->appendCompleteMessage(nl2br($page_message));

        // メール追加
        $message = $this->paymentService->createShippingCompleteCvsMessage(true);
        $this->Order->appendCompleteMailMessage($message);
    }
}
