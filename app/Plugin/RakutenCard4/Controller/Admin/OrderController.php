<?php

namespace Plugin\RakutenCard4\Controller\Admin;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Entity\Shipping;
use Exception;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Service\ConvenienceService;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Annotation\Route;

class OrderController extends AbstractController
{
    /** @var CardService */
    protected $cardService;

    /** @var ConvenienceService */
    protected $convenienceService;

    /** @var EntityManagerInterface */
    protected $entityManager;

    /** @var callable */
    private $apiEvent;
    /** @var mixed */
    private $response;

    const API_AUTHORIZE = 1; // 与信
    const API_CAPTURE = 2; // 売上
    const API_CANCEL = 3; // キャンセル
    const API_MODIFY = 4; // 金額変更

    /**
     * AdminOrderStateEventSubscriber constructor.
     * @param CardService $cardService
     * @param ConvenienceService $convenienceService
     */
    public function __construct(

        CardService      $cardService,
        ConvenienceService $convenienceService,
        EntityManagerInterface $entityManager
    )
    {
        $this->cardService = $cardService;
        $this->convenienceService = $convenienceService;
        $this->entityManager = $entityManager;
        $this->apiEvent = null;
        $this->response = null;
    }

    /**
     * API実行時に個別動作する内容を設定する
     *
     * @param callable $function
     */
    private function setApiEvent($function)
    {
        $this->apiEvent = $function;

        /* 以下のように$Orderを入れた無形関数を登録する
        $function = function($Order) {
            return boolean;
        };
         */
    }

    /**
     * 成功時のレスポンスを返す
     *
     * @param $response
     */
    private function setResponse($response)
    {
        $this->response = $response;
    }

    /**
     * APIの共通の実行処理
     *
     * @param Request $request
     * @param Order $Order
     * @param int $api_kind
     * @return mixed
     */
    private function execApi(Request $request, Order $Order, $api_kind)
    {
        $errorFunc = function ($message = '', $logData = [])
        {
            if (strlen($message) > 0){
                Rc4LogUtil::error($message, $logData);
                $this->addError($message, 'admin');
            }
            return $this->json([]);
        };

        $logData = ['order_id' => $Order->getId()];
        $isValid = $request->isXmlHttpRequest() && $this->isTokenValid();
        if (!$isValid){
            $message = '処理に失敗しました。もう一度お試しください。';
            return $errorFunc($message, $logData);
        }

        $Rc4OrderPayment = $Order->getRc4OrderPayment();
        if (is_null($Rc4OrderPayment)){
            $message = '楽天カード決済の受注ではありません。';
            return $errorFunc($message, $logData);
        }

        $payment_name = $Rc4OrderPayment->getPaymentName();
        if (empty($payment_name)){
            $message = '未決済の受注のため処理できません。';
            return $errorFunc($message, $logData);
        }

        $process_name = '';
        $success_msg = '';
        $error_msg = '';
        $exec_able_api = false;
        switch ($api_kind){
            case self::API_AUTHORIZE:
                $process_name = '与信処理';
                $exec_able_api = $Rc4OrderPayment->execAbleAuthorize();
                $success_msg = 'rakuten_card4.admin.order.authorize.success';
                $error_msg = 'rakuten_card4.admin.order.authorize.failed';
                break;
            case self::API_CAPTURE:
                $process_name = '売上処理';
                $exec_able_api = $Rc4OrderPayment->execAbleCapture();
                $success_msg = 'rakuten_card4.admin.order.capture.success';
                $error_msg = 'rakuten_card4.admin.order.capture.failed';
                break;
            case self::API_CANCEL:
                $process_name = '取消処理';
                $exec_able_api = $Rc4OrderPayment->execAbleCancel();
                $success_msg = 'rakuten_card4.admin.order.cancel.success';
                $error_msg = 'rakuten_card4.admin.order.cancel.failed';
                break;
            case self::API_MODIFY:
                $process_name = '金額変更処理';
                $exec_able_api = $Rc4OrderPayment->execAbleModify();
                $success_msg = 'rakuten_card4.admin.order.change_price.success';
                $error_msg = 'rakuten_card4.admin.order.change_price.failed';
                break;
        }
        if (empty($process_name)){
            // 入っていないのは、プログラムミスのため例外で落とす
            throw $this->createNotFoundException();
        }

        $message_title = "{$payment_name}--{$process_name}：";
        if (!$exec_able_api) {
            $message = '実行可能な決済状況ではありませんでした。';
            Rc4LogUtil::error($message_title . $message, $logData);
            $this->addError($message, 'admin');
            return $errorFunc();
        }

        Rc4LogUtil::info($message_title . 'start', $logData);
        try {
            // APIイベント実行
            $result = false;
            if ($this->apiEvent){
                $result = call_user_func($this->apiEvent, $Order);
            }

            // 実行結果に応じて処理する
            if ($result) {
                $this->addSuccess($success_msg, 'admin');
            } else {
                $this->addError($error_msg, 'admin');
            }
            $this->entityManager->flush($Rc4OrderPayment);
            $this->entityManager->commit();
            Rc4LogUtil::info($message_title . 'OrderPayment登録完了', $logData);
        } catch (Exception $e) {
            $message = 'システムエラーが発生しました。';
            $logData['error_message'] = $e->getMessage();
            Rc4LogUtil::error($message_title . $message, $logData);
            $this->addError($error_msg, 'admin');
            return $errorFunc();
        }

        Rc4LogUtil::info($message_title . '完了', $logData);
        return $this->response;
    }

    /**
     * 受注編集 > 金額変更
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/rakuten_card4/order/change_price/{id}", requirements={"id" = "\d+"}, name="rakuten_card4_admin_order_change_price")
     */
    public function changePrice(Request $request, Order $Order)
    {
        // 独自のAPI処理
        $this->setApiEvent(function ($Order){
            /** @var Rc4OrderPayment $Rc4OrderPayment */
            $Rc4OrderPayment = $Order->getRc4OrderPayment();
            $result = false;
            if($Rc4OrderPayment->execAbleModify()){
                // 金額変更API
                $PaymentTotal = $Order->getPaymentTotal();
                $result = $this->cardService->Modify($Order);

                // レスポンスの設定
                $this->setResponse($this->json([$PaymentTotal]));
            }
            return $result;
        });

        return $this->execApi($request, $Order, self::API_MODIFY);
    }

    /**
     * 受注編集 > 決済の売上処理
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/rakuten_card4/order/captured/{id}", requirements={"id" = "\d+"}, name="rakuten_card4_admin_order_captured")
     */
    public function captured(Request $request, Order $Order)
    {
        // 独自のAPI処理
        $this->setApiEvent(function ($Order){
            /** @var Rc4OrderPayment $Rc4OrderPayment */
            $Rc4OrderPayment = $Order->getRc4OrderPayment();
            $result = false;
            if($Rc4OrderPayment->execAbleCapture()){
                $result = $this->cardService->Capture($Order);
            }

            return $result;
        });

        // 実売上を実行
        return $this->execApi($request, $Order, self::API_CAPTURE);
    }

    /**
     * 受注編集 > 決済のキャンセル処理
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/rakuten_card4/order/cancel/{id}", requirements={"id" = "\d+"}, name="rakuten_card4_admin_order_cancel")
     */
    public function cancel(Request $request, Order $Order)
    {
        // 独自のAPI処理
        $this->setApiEvent(function ($Order){
            /** @var Rc4OrderPayment $Rc4OrderPayment */
            $Rc4OrderPayment = $Order->getRc4OrderPayment();
            $result = false;
            if($Rc4OrderPayment->execAbleCancel()) {
                if($Rc4OrderPayment->isCard()) {
                    $result = $this->cardService->CancelOrRefund($Order);
                } else {
                    $result = $this->convenienceService->CancelOrRefund($Order);
                }
            }
            return $result;
        });

        // 取消を実行
        return $this->execApi($request, $Order, self::API_CANCEL);
    }

    /**
     * 受注編集 > 決済の再与信処理
     *
     * @Method("POST")
     * @Route("/%eccube_admin_route%/rakuten_card4/order/authorize/{id}", requirements={"id" = "\d+"}, name="rakuten_card4_admin_order_authorize")
     */
    public function authorize(Request $request, Order $Order)
    {
        // 独自のAPI処理
        $this->setApiEvent(function ($Order){
            /** @var Rc4OrderPayment $Rc4OrderPayment */
            $Rc4OrderPayment = $Order->getRc4OrderPayment();
            $result = false;
            if($Rc4OrderPayment->execAbleAuthorize()) {
                $this->cardService->setAuthModeOnAuthorize();
                $result = $this->cardService->Authorize($Order);
            }
            return $result;
        });

        // 与信を実行
        return $this->execApi($request, $Order, self::API_AUTHORIZE);
    }

    /**
     * Update to rakuten status
     * @Route("/%eccube_admin_route%/shipping/rakuten_card4/rakuten_status/", name="admin_shipping_update_rakuten_status", methods={"PUT"})
     * @Route("/%eccube_admin_route%/shipping/rakuten_card4/rakuten_status/{id}/", requirements={"id" = "\d+"}, name="admin_shipping_update_rakuten_status_id"), methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $Shipping
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateRakutenStatus(Request $request, Shipping $Shipping)
    {
        $common_message = '----------商品一覧 決済ステータス一括変更処理----------';
        Rc4LogUtil::info($common_message . 'start');

        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            $message = trans('rakuten_card4.admin.order.cvs.capture.error', ['%name%' => $Shipping->getId()]);
            Rc4LogUtil::error($common_message . $message);
            return $this->json(['status' => 'NG', 'message' => $message]);
        }
        if (!$Shipping) {
            $message = trans('rakuten_card4.admin.order.cvs.capture.error', ['%name%' => $Shipping->getId()]);
            Rc4LogUtil::error($common_message . $message);
            return $this->json(['status' => 'NG', 'message' => $message]);
        }

        $admin_shipping_update_rakuten_status = $request->get('admin_shipping_update_rakuten_status');
        if (!$admin_shipping_update_rakuten_status) {
            $message = trans('rakuten_card4.admin.order.cvs.capture.error', ['%name%' => $Shipping->getId()]);
            Rc4LogUtil::error($common_message . $message);
            return $this->json(['status' => 'NG', 'message' => $message]);
        }

        /** @var Order $Order */
        $Order = $Shipping->getOrder();
        /** @var Rc4OrderPayment $Rc4OrderPayment */
        $Rc4OrderPayment = $Order->getRc4OrderPayment();

        if (!$Rc4OrderPayment) {
            // 楽天でない受注はスキップ
            $message = trans('admin.order.skip_change_status', ['%name%' => $Shipping->getId()]);
            Rc4LogUtil::error($common_message . $message);
            return $this->json(['status' => 'OK', 'message' => $message]);
        }

        $result = false;
        try {
            switch ($admin_shipping_update_rakuten_status) {
                case  ConstantPaymentStatus::Captured:
                    Rc4LogUtil::info($common_message . '変更:Captured');
                    if($Rc4OrderPayment->execAbleCapture()) {
                        $result = $this->cardService->Capture($Order);
                    } elseif($Rc4OrderPayment->isConenience()) {
                        $message = ['status' => 'OK', 'message' =>  trans('rakuten_card4.admin.order.cvs.capture.failed', ['%name%' => $Shipping->getId()])];
                        return $this->json($message);
                    } else {
                        return $this->getFailedMessage($Rc4OrderPayment, $Shipping, $admin_shipping_update_rakuten_status);
                    }
                    break;
                case ConstantPaymentStatus::Canceled:
                    Rc4LogUtil::info($common_message . '変更:Canceled');
                    if($Rc4OrderPayment->execAbleCancel()) {
                        if($Rc4OrderPayment->isCard()) {
                            $result = $this->cardService->CancelOrRefund($Order);
                        } elseif($Rc4OrderPayment->isConenience()) {
                            $result = $this->convenienceService->CancelOrRefund($Order);
                        }
                    }else {
                        return $this->getFailedMessage($Rc4OrderPayment, $Shipping, $admin_shipping_update_rakuten_status);
                    }
                    break;
                default:
                    break;
            }
            $this->entityManager->flush($Rc4OrderPayment);
        } catch (\Exception $e) {
            $message = trans('rakuten_card4.admin.order.cvs.capture.error', ['%name%' => $Shipping->getId()]);
            Rc4LogUtil::error($common_message . $message);
            return $this->json(['status' => 'NG', 'message' => $message]);
        }

        if($result){
            $message = 'OrderID' . $Order->getId() . ': 決済ステータス変更 完了';
            Rc4LogUtil::info($common_message . $message);
            $response = $this->json(array_merge(['status' => 'OK',]));
        } else {
            $message = trans('rakuten_card4.admin.order.cvs.capture.error', ['%name%' => $Shipping->getId()]);
            Rc4LogUtil::error($common_message . $message);
            $response = $this->json(['status' => 'NG', 'message' => $message]);
        }

        return $response;
    }

    private function getCapturedOrCanceledName($payment_status_number)
    {
        $trans_key = 'rakuten_card4.payment_status_name.';
        switch ($payment_status_number) {
            case ConstantPaymentStatus::Canceled:
                $trans_key .= 'canceled';
                break;
            case ConstantPaymentStatus::Captured:
                $trans_key .= 'captured';
                break;
            default:
                $trans_key .= 'first';
                break;
        }

        return trans($trans_key);
    }

    private function getFailedMessage($Rc4OrderPayment, $Shipping, $admin_shipping_update_rakuten_status)
    {
        // 与信以外はスキップ
        $from = $Rc4OrderPayment->getPaymentStatusName();
        $to = $this->getCapturedOrCanceledName($admin_shipping_update_rakuten_status);
        $message = [
            'status' => 'OK',
            'message' => trans('admin.order.failed_to_change_status', [
                '%name%' => $Shipping->getId(),
                '%from%' => $from,
                '%to%' => $to,
            ])];
        return $this->json($message);

    }

}
