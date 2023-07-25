<?php

namespace Plugin\RakutenCard4\Controller;

use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Service\ConvenienceService;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Rc4MailService;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * コンビニの戻りを処理する
 */
class CvsReceiveController extends abstractNotificationController
{
    /** @var ConvenienceService */
    protected $paymentService;

    /**
     * @param ConvenienceService $paymentService
     * @param Rc4MailService $mailService
     * @param EccubeConfigEx $configEx
     */
    public function __construct(
        ConvenienceService $paymentService
        , Rc4MailService $mailService
        , EccubeConfigEx $configEx
    )
    {
        parent::__construct($paymentService, $mailService, $configEx);
    }

    protected function setCommonMessage()
    {
        $this->common_message = '----------Cvs 通知処理の取得処理----------';
    }

    protected function setMethodClass()
    {
        $this->method_class = Convenience::class;
    }

    /**
     * 結果通知URLを受け取る(コンビニ決済).
     *
     * @Route(
     *     path="/rakuten_card4/receive/cvs_status",
     *     name="rakuten_card4_receive_cvs_status",
     *     methods={"POST"}
     *     )
     */
    public function receiveCvsStatus(Request $request)
    {
        // 共通処理
        $response = $this->receiveNotificationCommon($request);
        if (!is_null($response)){
            return $response;
        }

        $OrderPayment = $this->Order->getRc4OrderPayment();
        // 受注ステータスによって処理を中断し、管理者にメールを送る
        $response = $this->checkOrderStatus();
        if (!is_null($response) && $OrderPayment->isOrderStatusProcessing()){
            // 購入処理中の場合は受け取れないので、戻す
            return $response;
        }
        // それ以外の処理は続行

//        $result = false;
        // TODO 通知処理は、入金時/入金期限切れ、与信時に来る
//        if ($this->convenienceService->isRequestTypeAuthorize()) {
//            $result = $this->convenienceService->Authorize($Order);
//
//        } else
//        if ($this->paymentService->isRequestTypeAuthorize()) {
        // 決済ステータスで判断する
            $this->paymentService->receiveCapture($this->Order);
//        }
//            else if ($this->convenienceService->isRequestTypeCancelOrRefund()) {
//            $result = $this->convenienceService->CancelOrRefund($Order);
//
//        }

        Rc4LogUtil::info($this->common_message . 'end');
//        if ($result) {
            Rc4LogUtil::info($this->common_message . ' 通知処理 完了', $this->log);
            return $this->sendResponse(true);
//        }
    }
}
