<?php

namespace Plugin\SoftbankPayment4\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Exception\ShoppingException;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseException;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\SoftbankPayment4\Client\CreditCheckoutClient;
use Plugin\SoftbankPayment4\Client\CvsCheckoutClient;
use Plugin\SoftbankPayment4\Client\CreditCardInfoClient;
use Plugin\SoftbankPayment4\Exception\SbpsException;
use Plugin\SoftbankPayment4\Form\Type\CvsApiType;
use Plugin\SoftbankPayment4\Form\Type\CreditApiType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class ApiPaymentController extends AbstractController
{
    /**
     * @var CartService
     */
    private $cartService;
    /**
     * @var CreditCheckoutClient
     */
    private $creditCheckoutClient;
    /**
     * @var CvsCheckoutClient
     */
    private $cvsCheckoutClient;
    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;
    /**
     * @var CreditCardInfoClient
     */
    private $creditCardInfoClient;
    /**
     * @var MailService
     */
    private $mailService;

    public function __construct(
        CartService $cartService,
        CreditCardInfoClient $creditCardInfoClient,
        CreditCheckoutClient $creditCheckoutClient,
        CvsCheckoutClient $cvsCheckoutClient,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        PurchaseFlow $shoppingPurchaseFlow,
        MailService $mailService
    )
    {
        $this->cartService = $cartService;
        $this->creditCardInfoClient = $creditCardInfoClient;
        $this->creditCheckoutClient = $creditCheckoutClient;
        $this->cvsCheckoutClient = $cvsCheckoutClient;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->mailService = $mailService;
    }

    /**
     * クレジット決済情報入力画面(トークン型).
     *
     * @Route("shopping/sbps/credit/checkout", name="sbps_api_credit_checkout")
     * @Template("@SoftbankPayment4/default/Shopping/credit_checkout.twig")
     * @param Request $request
     * @return array|RedirectResponse
     * @throws PurchaseException
     * @throws SbpsException
     */
    public function checkoutCredit(Request $request)
    {
        $isGranted = $this->isGranted('IS_AUTHENTICATED_FULLY');

        $form = $this->createForm(CreditApiType::class);
        $form->handleRequest($request);

        $Order = $this->orderRepository->findOneBy(['pre_order_id' => $this->cartService->getPreOrderId()]);
        $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::PENDING));

        $storedCard = null;
        if ($isGranted === true) {
            $this->creditCardInfoClient->setOrder($Order);
            $storedCard = $this->creditCardInfoClient->implement();
        }

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $this->purchaseFlow->prepare($Order, new PurchaseContext());
                $this->entityManager->flush();

                // SBPSにリクエスト
                $this->creditCheckoutClient->setOrder($Order);
                $this->creditCheckoutClient->implement($request->request->get('credit_api'), $isGranted);

            } catch (SbpsException $e) {
                log_error('SBPSでエラーが発生しました。 受注ID: '.$Order->getId().' '.$e->getMessage().' エラーコード: '.$e->getCode());

                $this->entityManager->rollback();

                $this->addError($e->getMessage());

                return $this->redirectToRoute('shopping');
            } catch (ShoppingException $e) {
                log_error('[注文処理] 購入エラーが発生しました.', [$e->getMessage()]);

                $this->entityManager->rollback();

                $this->addError($e->getMessage());

                return $this->redirectToRoute('shopping_error');
            } catch (\Exception $e) {
                log_error('[注文処理] 予期しないエラーが発生しました.', [$e->getMessage()]);

                $this->entityManager->rollback();

                $this->addError('front.shopping.system_error');

                return $this->redirectToRoute('shopping_error');
            }

            // 受注確定
            $this->purchaseFlow->commit($Order, new PurchaseContext());
            $this->entityManager->flush();

            $this->mailService->sendOrderMail($Order);

            // カートを削除する
            $this->cartService->clear();
            // 完了画面表示のためにセッションに受注番号をセットする
            $this->session->set('eccube.front.shopping.order.id', $Order->getOrderNo());

            return $this->redirectToRoute('shopping_complete');
        }

        return [
            'form' => $form->createView(),
            'stored_card_info' => $storedCard,
            'isGranted' => $isGranted,
        ];
    }

    /**
     * WEBコンビニ決済(API).
     *
     * @Route("shopping/sbps/cvs/checkout", name="sbps_api_cvs_checkout")
     * @Template("@SoftbankPayment4/default/Shopping/cvs_checkout.twig")
     * @param Request $request
     * @return array
     * @throws PurchaseException
     */
    public function checkoutCvs(Request $request)
    {
        $form = $this->createForm(CvsApiType::class);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {

            $Order = $this->orderRepository->findOneBy(['pre_order_id' => $this->cartService->getPreOrderId()]);
            $Order->setOrderStatus($this->orderStatusRepository->find(OrderStatus::PENDING));

            try {
                $this->purchaseFlow->prepare($Order, new PurchaseContext());
                $this->entityManager->flush();

                // SBPSにリクエスト
                $this->cvsCheckoutClient->setOrder($Order);
                $this->cvsCheckoutClient->implement($request->get('cvs_api'));

                // 受注確定
                $this->purchaseFlow->commit($Order, new PurchaseContext());
                $this->entityManager->flush();

                $this->mailService->sendOrderMail($Order);

                // カートを削除する
                $this->cartService->clear();
                // 完了画面表示のためにセッションに受注番号をセットする
                $this->session->set('eccube.front.shopping.order.id', $Order->getOrderNo());

                return $this->redirectToRoute('shopping_complete');

            } catch (SbpsException $e) {
                // SBPS系の例外を補足
                // clean up.

            } catch (ShoppingException $e) {
                log_error('[注文処理] 購入エラーが発生しました.', [$e->getMessage()]);

                $this->entityManager->rollback();

                $this->addError($e->getMessage());

                return $this->redirectToRoute('shopping_error');
            } catch (\Exception $e) {


                log_error('[注文処理] 予期しないエラーが発生しました.', [$e->getMessage()]);

                $this->entityManager->rollback();

                $this->addError('front.shopping.system_error');

                return $this->redirectToRoute('shopping_error');
            }
        }

        return [
            'form' => $form->createView(),
        ];
    }

}
