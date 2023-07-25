<?php

namespace Plugin\RakutenCard4\Service\Method;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Service\PaymentServiceInterface;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\Form\FormInterface;

/**
 * 決済用の基底クラス
 */
abstract class AbstractMethod implements PaymentMethodInterface
{
    /** @var Order */
    protected $Order;

    /** @var FormInterface */
    protected $form;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var OrderStatusRepository */
    protected $orderStatusRepository;

    /** @var PurchaseFlow */
    protected $purchaseFlow;

    /** @var PaymentServiceInterface */
    protected $paymentService;

    protected $pay_result_error_key = '';
    protected $pay_result_error_common_key = '';

    /**
     * CreditCard constructor.
     *
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param EntityManagerInterface $entityManager
     * @param PaymentServiceInterface $paymentService
     */
    public function __construct(
        OrderStatusRepository $orderStatusRepository
        , PurchaseFlow $shoppingPurchaseFlow
        , EntityManagerInterface $entityManager
        , PaymentServiceInterface $paymentService
    ) {
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->entityManager = $entityManager;
        $this->paymentService = $paymentService;
        $this->setPayResultErrorCommonKey();
    }

    /**
     * 各メソッドで継承して設定する
     */
    protected function setPayResultErrorCommonKey()
    {
        $this->pay_result_error_common_key = 'rakuten_card4.shopping.checkout.error';
    }

    /**
     * PayResultで返すエラーの内容
     *
     * @param string $pay_result_error_key
     */
    protected function setPayResultErrorKey($pay_result_error_key)
    {
        $this->pay_result_error_key = $pay_result_error_key;
    }

    /**
     * エラー時のメッセージの出力
     *
     * @return string
     */
    protected function getPayResultErrorKey()
    {
        return empty($this->pay_result_error_key) ?
            $this->pay_result_error_common_key :
            $this->pay_result_error_key
        ;
    }

    /**
     * エラー情報とPaymentResultへの内容を設定
     *
     * @param string $message
     */
    function setErrorContents($message){
        $display_error = $this->paymentService->getDisplayErrorMsg();
        list($errorCode, $errorMessage) = $this->paymentService->getError();
        Rc4LogUtil::error($message, [
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage,
            'display_error' => $display_error,
        ]);
        $this->setPayResultErrorKey($display_error);
    }

    /**
     * ログ用のメッセージ出力
     *
     * @return string
     */
    protected function getMethodNameForLog()
    {
        return '[' . get_class($this) . ']';
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * @return PaymentResult
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function verify()
    {
        Rc4LogUtil::info('verify start ' . $this->getMethodNameForLog());

        // 受注のチェック
        $PaymentResult = $this->checkOrder();
        if (!is_null($PaymentResult)){
            return $PaymentResult;
        }

        // 決済情報の作成
        $this->createOrderPayment();

        // Formの情報を取り込む必要がある
        $this->getInputFromData();

        $api_result = $this->verifyUnique();

        if ($api_result) {
            $result = new PaymentResult();
            $result->setSuccess(true);
        } else {
            $result = new PaymentResult();
            $result->setSuccess(false);
            $result->setErrors([trans($this->getPayResultErrorKey())]);

            Rc4LogUtil::info('verify error ' . $this->getMethodNameForLog());
        }

        Rc4LogUtil::info('verify end ' . $this->getMethodNameForLog());

        return $result;
    }

    /**
     * 各決済のverifyの独自処理を記載
     *
     * @return bool
     */
    abstract protected function verifyUnique();

    /**
     * 注文時に呼び出される.
     *
     * 受注ステータス, 決済ステータスを更新する.
     * ここでは決済サーバとの通信は行わない.
     *
     * @return PaymentDispatcher|null
     */
    public function apply()
    {
        Rc4LogUtil::info('apply start ' . $this->getMethodNameForLog());

        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // 各決済の独自処理があれば
        $result = $this->applyUnique();

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        Rc4LogUtil::info('apply end ' . $this->getMethodNameForLog());
        return null;
    }

    /**
     * 各決済のverifyの独自処理を記載
     *
     * @return bool
     */
    abstract protected function applyUnique();

    /**
     * 注文時に呼び出される.
     *
     * クレジットカードの決済処理を行う.
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        Rc4LogUtil::info('checkout start ' . $this->getMethodNameForLog());

        // この前にチェックが必要か

        // TODO 決済サーバに仮売上のリクエスト送る(設定等によって送るリクエストは異なる)
        $api_result = $this->sendRequest();
        if (!$api_result){
            // 受注ステータスを購入処理中へ変更
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PROCESSING);
            $this->Order->setOrderStatus($OrderStatus);

            // 決済ステータスを未決済へ変更
            $this->Order->getRc4OrderPayment()->setPaymentStatus(ConstantPaymentStatus::First);

            // 失敗時はpurchaseFlow::rollbackを呼び出す.
            $this->purchaseFlow->rollback($this->Order, new PurchaseContext());

            $result = new PaymentResult();
            $result->setSuccess(false);
            $result->setErrors([trans($this->getPayResultErrorKey())]);
            Rc4LogUtil::info('checkout error ' . $this->getMethodNameForLog());

            return $result;
        }

        // 3Dセキュアなどのレスポンスを返す処理
        $PaymentResult = $this->getResponseAfterRequest();
        if (!is_null($PaymentResult)){
            Rc4LogUtil::info('checkout response ' . $this->getMethodNameForLog());
            return $PaymentResult;
        }

        // 受注ステータスを新規受付へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
        $this->Order->setOrderStatus($OrderStatus);

        // 決済ステータスを仮売上へ変更
        $this->Order->getRc4OrderPayment()->setPaymentStatus(ConstantPaymentStatus::Authorized);

        // 注文完了画面/注文完了メールにメッセージを追加
        // 各メソッドで設定する想定
        $this->setCompleteMessage();

        // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        $result = new PaymentResult();
        $result->setSuccess(true);
        Rc4LogUtil::info('checkout end ' . $this->getMethodNameForLog());

        return $result;
    }

    /**
     * 与信等のcheckout時のAPI通信
     *
     * @return bool
     */
    abstract protected function sendRequest();

    /**
     * 与信等のcheckout時のAPI通信
     *
     * @return PaymentResult
     */
    abstract protected function getResponseAfterRequest();

    /**
     * 購入完了画面およびメールのメッセージの設定
     */
    protected function setCompleteMessage()
    {
        $message = '決済完了メッセージ' . $this->getMethodNameForLog();
        $this->Order->appendCompleteMessage($message);
        $this->Order->appendCompleteMailMessage($message);
    }

    /**
     * 受注のチェック
     *
     * @return PaymentResult|null
     */
    protected function checkOrder()
    {
        if (is_null($this->Order)){
            $result = new PaymentResult();
            $result->setSuccess(false);
            $result->setErrors([trans('rakuten_card4.shopping.verify.error')]);

            Rc4LogUtil::info('受注が存在しない');
            return $result;
        }

        return null;
    }

    /**
     * 受注に作成した決済情報を紐付ける
     */
    protected function createOrderPayment()
    {
        $OrderPayment = $this->Order->getRc4OrderPayment();
        if (is_null($OrderPayment)){
            $OrderPayment = $this->paymentService->createOrderPaymentRequireSet();
        }
        $OrderPayment->setOrder($this->Order);
        $this->Order->setRc4OrderPayment($OrderPayment);
        $OrderPayment->setMethodClass(get_class($this));
        $OrderPayment->setPaymentStatus(ConstantPaymentStatus::First);
        $this->paymentService->setTokenEntityCommon($OrderPayment, $this->Order, $this->Order->getCustomer());
        $this->entityManager->persist($OrderPayment);
        $this->entityManager->flush($OrderPayment);
    }

    /**
     * formの値を取得して対象カラムにセットする
     */
    protected function getInputFromData()
    {
        $Rc4OrderPayment = $this->Order->getRc4OrderPayment();
        if ($Rc4OrderPayment instanceof Rc4OrderPayment) {
            // コンビニ決済
            switch ($Rc4OrderPayment->getMethodClass()) {
                case Convenience::class:
                    $cvsKind = $this->form->get('cvs_kind')->getData();
                    $Rc4OrderPayment->setCvsKind($cvsKind);
                    break;
            }
            $this->entityManager->persist($Rc4OrderPayment);
            $this->entityManager->flush($Rc4OrderPayment);
        }

    }

    /**
     * @inheritDoc
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * @inheritDoc
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
}
