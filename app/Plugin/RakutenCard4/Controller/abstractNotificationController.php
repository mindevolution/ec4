<?php

namespace Plugin\RakutenCard4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Service\BasePaymentService;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Plugin\RakutenCard4\Service\Rc4MailService;
use Plugin\RakutenCard4\Util\Rc4CommonUtil;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 通知の受け取り
 */
abstract class abstractNotificationController extends AbstractController
{
    /** @var BasePaymentService */
    protected $paymentService;
    /** @var Rc4MailService */
    protected $mailService;
    /** @var EccubeConfigEx */
    protected $configEx;
    /** @var array */
    protected $log;
    /** @var array */
    protected $mail_contents;
    /** @var Order */
    protected $Order;
    /** @var string */
    protected $common_message;
    /** @var string */
    protected $method_class;

    /**
     * PaymentController constructor.
     *
     * @param BasePaymentService $paymentService
     * @param Rc4MailService $mailService
     */
    public function __construct(
        BasePaymentService $paymentService
        , Rc4MailService $mailService
        , EccubeConfigEx $configEx
    )
    {
        $this->paymentService = $paymentService;
        $this->mailService = $mailService;
        $this->configEx = $configEx;
        $this->log = [];
        $this->mail_contents = [];
        $this->Order = null;
        $this->common_message = '';
        $this->method_class = '';
        $this->setCommonMessage();
        $this->setMethodClass();
    }

    protected function setOrder($Order)
    {
        $this->Order = $Order;
    }

    protected function setCommonMessage()
    {
        $this->common_message = '';
    }

    protected function setMethodClass()
    {
        $this->method_class = CreditCard::class;
    }

    /**
     * failureチェック
     *
     */
    protected function checkResultFailure()
    {
        if ($this->paymentService->isResultFailure()) {
            $error = implode(':', $this->paymentService->getError());
            $this->addError($error);
            $message = $this->common_message . 'error ' . $error;
            Rc4LogUtil::error($message);
            throw $this->createNotFoundException('failure');
        }
    }

    /**
     * pendingチェック
     *
     */
    protected function checkResultPending()
    {
        if ($this->paymentService->isResultPending()) {
            $display_error = trans($this->configEx->front_error_common());
            $this->addError($display_error);
            $this->paymentService->writeLogResponsePending($this->common_message . ' response pending', $display_error);
            throw $this->createNotFoundException('pending');
        }
    }

    /**
     * success以外チェック
     */
    protected function checkOutOfSuccess()
    {
        if (!$this->paymentService->isResultSuccess()) {
            // 想定外として500を返す
            $error = implode(':', $this->paymentService->getError());
            $message = $this->common_message . 'result type: ' . $this->paymentService->getResultType() . ' error: ' . $error;
            Rc4LogUtil::error($message);
            throw $this->createNotFoundException('not success');
        }
    }

    /**
     * チェック処理全体
     */
    protected function checkResultType()
    {
        $this->checkResultFailure();
        $this->checkResultPending();
        $this->checkOutOfSuccess();
    }

    /**
     * 受注のチェック
     *
     * @return null|Response
     */
    protected function checkOrder()
    {
        $transaction_id = $this->paymentService->getResponseTransactionId();
        $this->log['response_paymentId'] = $transaction_id;
        $this->mail_contents['paymentId'] = $transaction_id;
        if (is_null($this->Order)) {
            // 売上通知エラーメールを送るかログに書き込むだけ
            $this->sendCaptureErrorOrWriteLog('存在しない受注に通知が届きました。');
            return $this->sendResponse(true);
        }
        return null;
    }

    /**
     * @param string $main_msg
     */
    protected function sendCaptureErrorOrWriteLog($main_msg)
    {
        $process_name = $this->paymentService->getNotificationCaptureName($this->method_class);
        if ($this->paymentService->isPaymentStatusCaptured()) {
            // 管理者にメールを送る
            $message = $this->common_message . 'error ' . " {$main_msg}{$process_name}のため、管理者にメールを配信します。";
            $this->mailService->sendCaptureError($main_msg, $this->mail_contents, $this->method_class);
        } else {
            $message = $this->common_message . 'error ' . " {$main_msg}{$process_name}ではないためそのままにします。";
        }
        Rc4LogUtil::error($message, $this->log);
    }

    /**
     * 別の決済になっていないかのチェック
     *
     * @return Response|null
     */
    protected function checkOtherPayment()
    {
        $Order = $this->Order;
        $OrderPayment = $Order->getRc4OrderPayment();
        $this->log['order_id'] = $Order->getId();
        $this->log['order_no'] = $Order->getOrderNo();
        $this->mail_contents['order_no'] = $Order->getOrderNo();

        $is_same_payment = false;
        $method_name = '';
        switch ($this->method_class){
            case CreditCard::class:
                $is_same_payment = $OrderPayment->isCard();
                $method_name = 'クレジットカード決済';
                break;
            case Convenience::class:
                $is_same_payment = $OrderPayment->isConenience();
                $method_name = 'コンビニ決済';
                break;
        }

        // クレカ以外の受注に対して決済通知が来た場合
        if (!$is_same_payment){
            // 売上通知エラーメールを送るかログに書き込むだけ
            $this->sendCaptureErrorOrWriteLog($method_name . '以外の受注に通知が届きました。');
            return $this->sendResponse(true);
        }
        return null;
    }

    /**
     * 受注ステータスによるチェック
     *
     * @return Response|null
     */
    protected function checkOrderStatus()
    {
        $Order = $this->Order;
        $OrderPayment = $Order->getRc4OrderPayment();
        $order_status_id = $order_status_name = '';
        if (!is_null($Order->getOrderStatus())){
            $order_status_id = $Order->getOrderStatus()->getId();
            $order_status_name = $Order->getOrderStatus()->getName();
        }
        $this->log['order_status_id'] = $order_status_id;
        $this->log['order_status_name'] = $order_status_name;
        // 購入処理中・キャンセル・返金はアラートを飛ばす
        if ($OrderPayment->isOrderStatusProcessing()
            || $OrderPayment->isOrderStatusCancel()
            || $OrderPayment->isOrderStatusReturned()
        ){
            // 売上通知エラーメールを送るかログに書き込むだけ
            $this->sendCaptureErrorOrWriteLog($order_status_name . 'の受注に通知が届きました。');
            return $this->sendResponse(true);
        }
        return null;
    }

    /**
     * 通知処理の受け取り部分の共通部分
     *
     * @param Request $request
     * @return Response|null
     */
    protected function receiveNotificationCommon(Request $request)
    {
        Rc4LogUtil::info($this->common_message . 'start');

        // 通知の受け取り
        $this->paymentService->checkReceiveRequest($request, true);
        // result typeのチェックをする
        $this->checkResultType();

        // 受注の取得
        $Order = $this->paymentService->getOrderFromResponse();
        $this->setOrder($Order);

        // 受注のチェック
        $response = $this->checkOrder();
        if (!is_null($response)){
            return $response;
        }

        // カードならコンビニ、コンビニならカードの受注が届いていないかをチェック
        $response = $this->checkOtherPayment();
        if (!is_null($response)){
            return $response;
        }

        return null;
    }
    /**
     *
     * @param $result
     * @return Response
     */
    protected function sendResponse($result)
    {
        if (!$result) {
            $contents = Rc4CommonUtil::encodeData(['resultType' => 'failure']);
        }else{
            $contents = Rc4CommonUtil::encodeData(['resultType' => 'success']);
        }

        return Response::create($contents);
    }
}
