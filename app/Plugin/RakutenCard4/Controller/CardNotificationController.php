<?php

namespace Plugin\RakutenCard4\Controller;

use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Plugin\RakutenCard4\Service\Rc4MailService;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * カードの通知の受け取り
 */
class CardNotificationController extends abstractNotificationController
{
    /** @var CardService */
    protected $paymentService;

    /**
     * @param CardService $paymentService
     * @param Rc4MailService $mailService
     * @param EccubeConfigEx $configEx
     */
    public function __construct(
        CardService $paymentService
        , Rc4MailService $mailService
        , EccubeConfigEx $configEx
    )
    {
        parent::__construct($paymentService, $mailService, $configEx);
    }

    protected function setCommonMessage()
    {
        $this->common_message = '---------クレジットカード決済-通知処理の取得処理----------';
    }

    protected function setMethodClass()
    {
        $this->method_class = CreditCard::class;
    }

    /**
     * 通知受け取り
     * 売上確定時に管理者に異常と取り消しを求める通知を送る処理
     *
     * @Route(
     *     path="/rakuten_card4/notification/card/receive",
     *     name="rakuten_card4_card_notification_receive",
     *     methods={"POST"}
     *     )
     */
    public function receiveNotification(Request $request)
    {
        // 共通処理
        $response = $this->receiveNotificationCommon($request);
        if (!is_null($response)){
            return $response;
        }

        // 受注ステータスによって処理を中断し、管理者にメールを送る
        $response = $this->checkOrderStatus();
        if (!is_null($response)){
            return $response;
        }

        // 問題ないことをログに残して200を返す
        $log['paymentStatusType'] = $this->paymentService->getResponseStatusType();
        Rc4LogUtil::info($this->common_message . ' 通知処理 完了', $log);

        $this->entityManager->flush();
        return $this->sendResponse(true);
    }
}
