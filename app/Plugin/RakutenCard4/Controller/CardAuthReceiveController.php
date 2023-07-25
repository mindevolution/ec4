<?php

namespace Plugin\RakutenCard4\Controller;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Entity\Rc4CustomerToken;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Util\Rc4LogUtil;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * カードのAuthorize HTMLでの戻りを処理する
 */
class CardAuthReceiveController extends AbstractController
{
    /** @var OrderStatusRepository */
    protected $orderStatusRepository;

    /** @var PurchaseFlow */
    protected $purchaseFlow;

    /** @var CartService */
    protected $cartService;

    /** @var CardService */
    protected $cardService;
    /** @var MailService */
    protected $mailService;
    /** @var EccubeConfigEx */
    protected $configEx;

    /**
     * PaymentController constructor.
     *
     * @param CardService $cardService
     * @param MailService $mailService
     * @param OrderStatusRepository $orderStatusRepository
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param CartService $cartService
     * @param EccubeConfigEx $configEx
     */
    public function __construct(
        CardService $cardService
        , MailService $mailService
        , OrderStatusRepository $orderStatusRepository
        , PurchaseFlow $shoppingPurchaseFlow
        , CartService $cartService
        , EccubeConfigEx $configEx
    )
    {
        $this->cardService = $cardService;
        $this->mailService = $mailService;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->cartService = $cartService;
        $this->configEx = $configEx;
    }

    /**
     * Authorize Htmlの戻りの受け取り
     * @Route(
     *     path="/rakuten_card4/authorize_html/receive",
     *     name="rakuten_card4_authorize_html_receive",
     *     methods={"POST"}
     *     )
     */
    public function receiveAuthorizeHtml(Request $request)
    {
        $common_message = '----------Authorize HTML レスポンス取得処理(購入)----------';
        Rc4LogUtil::info($common_message . 'start');

        $error_response = $this->redirectToRoute('shopping_error');
        $this->cardService->checkReceiveRequest($request);
        // 失敗時の処理
        if ($this->runErrorProcessFailure($common_message)){
            return $error_response;
        }

        // レスポンスがペンディングの場合
        if ($this->runErrorProcessPending($common_message)){
            return $error_response;
        }

        // 受注の取得
        $Order = $this->cardService->getOrderFromResponse();
        $write_log_purchase_error = function ($error_msg, $log = []) use($common_message){
            $message = $common_message . 'error ' .$error_msg;
            Rc4LogUtil::error($message);
            $this->addError(trans('rakuten_card4.front.card.error.purchase_error'), $log);
        };

        if (is_null($Order)){
            $write_log_purchase_error(' レスポンスの決済IDから受注を取得できませんでした。');
            return $error_response;
        }

        // 決済処理中の場合のみ通す（新規受付は、2度POSTの場合がありうる
        $OrderPayment = $Order->getRc4OrderPayment();
        $log = ['order_id' => $Order->getId()];
        if (!$OrderPayment->isOrderStatusPending()){
            if ($OrderPayment->isOrderStatusProcessing()){
                $write_log_purchase_error(' 取得した受注が購入処理中のため、戻します。', $log);
            }else{
                $log['order_status_id'] = $Order->getOrderStatus()->getId();
                $log['order_status_name'] = $Order->getOrderStatus()->getName();
                $write_log_purchase_error(' 取得した受注が購入済みのステータスです。', $log);
            }
            return $error_response;
        }

        // レスポンスを登録
        $this->cardService->AuthorizeHtmlResponse($Order);

        // 受注ステータスを新規受付へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::NEW);
        $Order->setOrderStatus($OrderStatus);

        // カード登録を行う場合
        // 購入時にカード登録をチェックしている
        if ($OrderPayment->isCardCheckRegister()){
            $message = $common_message . '購入時にカード登録にチェックが入れられているため登録処理を行います。';
            Rc4LogUtil::info($message, $log);
            $Customer = $Order->getCustomer();
            if (is_null($Customer)){
                $message = $common_message . '受注に会員が登録されていなかったため、カード登録を中止します。';
                Rc4LogUtil::error($message, $log);
            }else{
                // 会員が登録されている場合
                if (!$this->cardService->ableRegisterCard($Customer, true)){
                    $this->writeLogMaxCardCounts($common_message, $Customer, $log);
                }else{
                    // カードが登録可能
                    $CustomerToken = new Rc4CustomerToken();
                    $CustomerToken->setCustomer($Customer);
                    $CustomerToken->setRegisterCard($OrderPayment);
                    $Customer->addRc4CustomerToken($CustomerToken);
                    $this->entityManager->persist($CustomerToken);
                    $message = $common_message . 'カードを登録しました。';
                    $register_log = $log;
                    $register_log['customer_id'] = $Customer->getId();
                    Rc4LogUtil::info($message, $register_log);
                }
            }
        }

        // 注文完了メールにメッセージを追加
        // 完了メールとメッセージに何も登録しないことになる
//        $message = 'カード購入完了';
//        $Order->appendCompleteMessage($message);
//        $Order->appendCompleteMailMessage($message);
        Rc4LogUtil::info($common_message . 'レスポンスの設定完了', $log);

        // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
        $this->purchaseFlow->commit($Order, new PurchaseContext());
        $this->entityManager->flush();
        Rc4LogUtil::info($common_message . 'purchaseFlow::commit完了', $log);

        // カートを削除する
        $this->cartService->clear();
        Rc4LogUtil::info($common_message . '受注メール配信start', $log);
        $this->mailService->sendOrderMail($Order);
        Rc4LogUtil::info($common_message . '受注メール配信完了', $log);

        // 完了画面を表示するため, 受注IDをセッションに保持する
        $this->session->set('eccube.front.shopping.order.id', $Order->getId());

        $this->entityManager->flush();

        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * Authorize Htmlの戻りの受け取り（カード登録時）
     *
     * @Route(
     *     path="/rakuten_card4/authorize_html/receive/register_card",
     *     name="rakuten_card4_authorize_html_receive_register_card",
     *     methods={"POST"}
     *     )
     */
    public function receiveAuthorizeHtmlRegisterCard(Request $request)
    {
        $common_message = '----------Authorize HTML レスポンス取得処理(カード登録)----------';
        Rc4LogUtil::info($common_message . 'start');
        $redirect_response = $this->redirectToRoute('rakuten_card4_mypage');
        $this->cardService->checkReceiveRequest($request);
        // 失敗時の処理
        if ($this->runErrorProcessFailure($common_message, true)){
            return $redirect_response;
        }

        // レスポンスがペンディングの場合
        if ($this->runErrorProcessPending($common_message, true)){
            return $redirect_response;
        }

        // 登録カード情報の取得
        $CustomerToken = $this->cardService->getCustomerFromResponse();

        // エラー書き込みの共通処理
        $write_log_error = function ($error_msg) use($common_message) {
            $message = $common_message . 'error ' .$error_msg;
            Rc4LogUtil::error($message);
            $this->addDanger(trans('rakuten_card4.front.card.error.now_no_use'));
        };
        if ($CustomerToken->getCustomer() != $this->getUser()){
            if (is_null($CustomerToken)){
                // エラー書き込み
                $write_log_error(' Authorize HTML実行前に決済IDをキーとして登録したカード情報が見つかりません');
                return $redirect_response;
            }
            // エラー書き込み
            $write_log_error(' Authorize HTML実行時と現在の会員が一致しません');

            // 対象は登録しきれていないので、削除する
            $this->entityManager->remove($CustomerToken);
            return $redirect_response;
        }

        if (!$this->cardService->ableRegisterCard($CustomerToken->getCustomer())){
            $this->writeLogMaxCardCounts($common_message, $CustomerToken->getCustomer());
            $this->addDanger(trans('rakuten_card4.front.mypage.card.register.max_count'));

            // 対象は登録しきれていないので、削除する
            $this->entityManager->remove($CustomerToken);
            return $redirect_response;
        }

        // レスポンスを登録
        $this->cardService->registerResponseCommonToken($CustomerToken);

        // カードを登録済みにする
        $CustomerToken->setRegistered(Constant::ENABLED);
        $this->entityManager->flush();

        $this->addSuccess(trans('rakuten_card4.front.mypage.card.register.success'));

        return $redirect_response;
    }

    /**
     * カードの登録上限に達した場合のエラーログの書き込み
     *
     * @param string $common_message
     * @param Customer $Customer
     * @param array $log
     */
    private function writeLogMaxCardCounts($common_message, $Customer, $log = [])
    {
        $message = $common_message . '登録済みカードが上限に達しているため、カード登録を中止します。';
        $register_log = $log;
        $register_log['config_count'] = $this->configEx->card_register_count();
        $register_log['customer_card_count'] = $this->cardService->getRegisterCardCount($Customer);
        Rc4LogUtil::error($message, $register_log);
    }

    /**
     * 失敗時の処理
     *
     * @param string $common_message
     * @param bool $register_card_fig
     * @return bool
     */
    private function runErrorProcessFailure($common_message, $register_card_fig=false)
    {
        if (!$this->cardService->isResultFailure()){
            return false;
        }

        // 失敗時の処理
        $display_error = $this->cardService->getDisplayErrorMsg();
        if ($register_card_fig){
            $this->addDanger($display_error);
        }else{
            $this->addError($display_error);
        }
        $this->cardService->writeLogResponseFailure($common_message . ' response failure');
        return true;
    }

    /**
     * ペンディングの共通処理
     *
     * @param string $common_message
     * @param bool $register_card_fig
     * @return bool
     */
    private function runErrorProcessPending($common_message, $register_card_fig=false)
    {
        if (!$this->cardService->isResultPending()){
            return false;
        }

        // ペンディング処理
        $display_error = trans($this->configEx->front_error_common());
        if ($register_card_fig){
            $this->addDanger($display_error);
        }else{
            $this->addError($display_error);
        }
        $this->cardService->writeLogResponsePending($common_message . ' response pending', $display_error);
        return true;
    }
}
