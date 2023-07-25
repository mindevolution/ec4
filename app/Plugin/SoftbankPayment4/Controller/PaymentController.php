<?php

namespace Plugin\SoftbankPayment4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Service\CartService;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\MailService;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseException;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType as StatusType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeDetailResultType as ResultType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeType as TradeType;
use Plugin\SoftbankPayment4\Factory\SbpsTradeFactory;
use Plugin\SoftbankPayment4\Factory\SbpsTradeDetailFactory;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * SBPS決済用コントローラ.
 */
class PaymentController extends AbstractController
{
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;
    /**
     * @var MailService
     */
    private $mailService;
    /**
     * @var OrderStateMachine
     */
    private $orderStateMachine;
    /**
     * @var SbpsTradeFactory
     */
    private $sbpsTradeFactory;
    /**
     * @var SbpsTradeDetailFactory
     */
    private $sbpsTradeDetailFactory;

    public function __construct(
        CartService $cartService,
        ConfigRepository $configRepository,
        OrderRepository $orderRepository,
        OrderStateMachine $orderStateMachine,
        OrderStatusRepository $orderStatusRepository,
        SbpsTradeFactory $sbpsTradeFactory,
        SbpsTradeDetailFactory $sbpsTradeDetailFactory,
        TradeHelper $tradeHelper,
        PurchaseFlow $shoppingPurchaseFlow,
        MailService $mailService
    ) {
        $this->cartService = $cartService;
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->orderStateMachine = $orderStateMachine;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->sbpsTradeFactory = $sbpsTradeFactory;
        $this->sbpsTradeDetailFactory = $sbpsTradeDetailFactory;
        $this->tradeHelper = $tradeHelper;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->mailService = $mailService;
    }

    /**
     * SBPSリンク型決済画面に遷移する.
     *
     * @Route("shopping/sbps/link", name="sbps_link_request")
     * @Template("@SoftbankPayment4/default/Shopping/sbps_confirm.twig")
     */
    public function sbpsLinkRequest() {
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderRepository->findOneBy([
            'pre_order_id' => $preOrderId,
            'OrderStatus' => OrderStatus::PROCESSING,
        ]);

        if($Order === null) {
            logs('sbps')->error('The Order is not found. pre_order_id='.$preOrderId);
            return $this->redirectToRoute('shopping_error');
        }

        $param = $this->tradeHelper->createParam($Order);

        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $Order->setOrderStatus($OrderStatus);
        $this->entityManager->flush();

        $post_to = $this->configRepository->get()->getLinkRequestUrl();

        logs('sbps')->info('Form data: '.print_r($param, true));

        // SBPS側ASPに合わせてフォームをフォーマットするため、テンプレートでフォームを作る。
        return [
            'post_to' => $post_to,
            'param' => $param,
        ];
    }

    /**
     * @Route("/shopping/sbps/link/complete", name="sbps_link_request_complete")
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function complete(Request $request): RedirectResponse
    {
        // カートを削除する
        $this->cartService->clear();
        // 完了画面表示のためにセッションに受注番号をセットする
        $this->session->set('eccube.front.shopping.order.id', $this->tradeHelper->trimOrderId($request->get('order_id')));

        return $this->redirectToRoute('shopping_complete');
    }

    /**
     * @Route("/shopping/sbps/link/back", name="sbps_link_request_back")
     */
    public function back(): RedirectResponse
    {
        logs('sbps')->info('PaymentController::back was completed.');

        return $this->redirectToRoute('shopping');
    }

    /**
     * @Route("/shopping/sbps/error", name="sbps_link_error")
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function error(Request $request): RedirectResponse
    {
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderRepository->findOneBy([
            'pre_order_id' => $preOrderId,
            'OrderStatus' => OrderStatus::PENDING,
        ]);

        if ($Order !== null) {
            // 受注ステータスはPENDINGのまま捨てる。
            $this->purchaseFlow->rollback($Order, new PurchaseContext());
            $this->entityManager->flush();
        }

        logs('sbps')->error('The Order is failed while Payment Processing in SBPS. pre_order_id='.$preOrderId);
        return $this->redirectToRoute('shopping_error');
    }

    /**
     * @Route("/shopping/sbps/link/endpoint", name="sbps_link_endpoint")
     *
     * @param Request $request
     * @return Response
     * @throws PurchaseException
     */
    public function endpoint(Request $request): Response
    {
        if($request->getMethod() !== 'POST') {
            return new Response(Response::$statusTexts[400], Response::HTTP_BAD_REQUEST, ['Content-Type' => 'text/plain']);
        }

        $orderId = $this->tradeHelper->trimOrderId($request->get('order_id'));

        if(empty($orderId)) {
            logs('sbps')->error('order_id is empty.');
            return new Response('NG', Response::HTTP_OK, ['Content-Type' => 'text/plain']);
        }

        $Order = $this->orderRepository->find($orderId);

        if($Order === null) {
            logs('sbps')->error('The Order is not found. order_id='.$orderId);
            return new Response('NG', Response::HTTP_OK, ['Content-Type' => 'text/plain']);
        }

        switch (ResultType::getResultType($request->get('res_result'))) {
            case ResultType::CORRECT:
                try {
                    $this->purchaseFlow->prepare($Order, new PurchaseContext());

                    $Trade = $this->sbpsTradeFactory->create($request, $Order);
                    $this->entityManager->persist($Trade);

                    $Detail = $this->sbpsTradeDetailFactory->create($request, $Trade);
                    $this->entityManager->persist($Detail);

                    $Trade->addSbpsTradeDetail($Detail);
                    $Order->setSbpsTrade($Trade);

                } catch (\Exception $e) {
                    $this->entityManager->rollback();
                    $content = 'NG,'.$e->getMessage();
                    return new Response($content, Response::HTTP_OK, ['Content-Type' =>'text/csv']);
                }

                $this->purchaseFlow->commit($Order, new PurchaseContext());
                $this->entityManager->flush();

                $this->mailService->sendOrderMail($Order);

                logs('sbps')->info('The Order was captured !! order_id='.$orderId);

                break;

            case ResultType::FAILED:
                $Trade = $this->sbpsTradeFactory->create($request, $Order, ResultType::FAILED);
                $this->entityManager->persist($Trade);

                $Detail = $this->sbpsTradeDetailFactory->create($request, $Trade);
                $this->entityManager->persist($Detail);

                $Trade->addSbpsTradeDetail($Detail);
                $Order->setSbpsTrade($Trade);

                logs('sbps')->info('The Order was failed and rollbacked. order_id='.$orderId);

                break;

            case ResultType::DEPOSITED:
                // NOTE: コンビニのみ
                $Trade = $Order->getSbpsTrade();
                $Detail = $this->sbpsTradeDetailFactory->create($request, $Trade, TradeType::DEPOSIT);
                $this->entityManager->persist($Detail);

                $Trade
                    ->setStatus(StatusType::DEPOSITED)
                    ->addSbpsTradeDetail($Detail);

                $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                $Order->setOrderStatus($OrderStatus);
                logs('sbps')->info('The Orde    r has been deposited. order_id='.$orderId);

                break;

            case ResultType::EXPIRED_CANCEL:
                // NOTE: コンビニのみ
                $Trade = $Order->getSbpsTrade();
                $Detail = $this->sbpsTradeDetailFactory->create($request, $Trade, TradeType::DEPOSIT);
                $this->entityManager->persist($Detail);

                $Trade
                    ->setStatus(StatusType::EXPIRED)
                    ->addSbpsTradeDetail($Detail);

                $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
                if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                    $this->orderStateMachine->apply($Order, $OrderStatus);
                }

                logs('sbps')->info('The Order was canceled. order_id='.$orderId);

                break;

            default:
                return new Response(Response::$statusTexts[400], Response::HTTP_BAD_REQUEST, ['Content-Type' => 'text/plain']);
                break;
        }

        $this->entityManager->flush();
        return new Response('OK', Response::HTTP_OK, ['Content-Type' =>'text/plain']);
    }
}
