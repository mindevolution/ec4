<?php

namespace Plugin\StripePaymentGateway\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Repository\OrderRepository;
use Eccube\Service\OrderHelper;
use Eccube\Controller\AbstractShoppingController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Eccube\Entity\DeliveryFee;
use Plugin\StripePaymentGateway\Service\OrderHelperPr;
use Eccube\Controller\AbstractController;

class StripePrController extends AbstractShoppingController{
// class StripePrController extends AbstractController{
    protected $container;
    private $stripe_config;    
    protected $util_service;
    protected $entityManager;
    protected $pr_service;
    protected $order_helper_pr;
    
    protected $mailService;
    protected $orderRepository;
    protected $orderHelper;
    protected $cartService;

    public function __construct(
        ContainerInterface $container,
        CartService $cartService,
        MailService $mailService,
        OrderRepository $orderRepository,
        OrderHelperPr $orderHelper
    ) {
        
        $this->cartService = $cartService;
        $this->mailService = $mailService;
        $this->orderRepository = $orderRepository;
        $this->orderHelper = $orderHelper;
        
        $this->container = $container;
        $this->entityManager = $container->get('doctrine.orm.entity_manager'); 
        $this->stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
        $this->util_service = $this->container->get("plg_stripe_payment.service.util");
        $this->pr_service = $this->container->get('plg_stripe_payment.pr_service');
        
    }

    /**
     * @Route("/plugin/stripe_payment_gateway/pr_shipping", name="plugin_stripe_pr_shipping")
     */
    public function prShipping(Request $request)
    {
        // if(!$this->stripe_config->isGaPayEnabled()){
        //     return $this->returnError('not_suppported_method');
        // }

        $shipping_info = $this->pr_service->validatePrShipping($request);
        if($shipping_info === false){
            return $this->json([
                'status'    =>  'failed',
                'error'     =>  $this->pr_service->getError()
            ]);
        }
        extract($shipping_info);
        
        if(!empty($data['cart_key'])){
            $Carts = $this->cartService->getCarts();
            $Cart = $this->pr_service->findCart($Carts, $data['cart_key']);
            if(empty($Cart) || empty($Cart->getCartItems())){
                return $this->returnError($this->pr_service->getError());
            }
            $items = $this->pr_service->getItemsFromCart($Cart);
        }else{
            $items = $this->pr_service->validateItems($data);
        }
        if(empty($items)){
            return $this->returnError($this->pr_service->getError());
        }
        $deliveries = $this->pr_service->getDeliveries($items);
        $product_amount = $this->pr_service->calcAmountByItems($items);

        $shipping_options = [];
        foreach($deliveries as $delivery){
            $delivery_fee = $this->pr_service->calcAmountByDelivery($items, $delivery, $Pref);
            $shipping_options[] = [
                'id'    =>  "{$delivery->getId()}",
                'label' =>  empty($delivery->getName()) ? "shipping" : $delivery->getName(),
                'detail'=> '',
                'amount'=>  $delivery_fee,
            ];
        }
        return $this->json([
            'status'            =>  'success',
            'shippingOptions'   =>  $shipping_options,
            'amount'            =>  $product_amount,
        ]);
    }

    /**
     * @Route("/plugin/stripe_payment_gateway/pr_pay", name="plugin_stripe_pr_pay")
     */
    public function prPay(Request $request){
        // if(!$this->stripe_config->isGaPayEnabled()){
            
        //     return $this->returnError('not_suppported_method');
        // }
        $res = $this->pr_service->validateMethodRequest($request, $this->cartService->getCarts());
        if($res === false){
            return $this->json(['status' => "failed", "error" => $this->pr_service->getError() ]);
        }

        extract($res);
        if ($this->orderHelper->isLoginRequired()) {
            log_info('[注文手続] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');
            $Customer = $this->orderHelper->getNonMember();
            $this->pr_service->signup($customer_info, $Customer);
            $request->getSession()->migrate(true);
        }

        $Customer = $this->getUser();
        if(!empty($cart_key)){
            $this->cartService->setPrimary($cart_key);
            $Cart = $this->cartService->getCart();

            $item_infos = $this->orderHelper->convertCartToItemInfos($Cart);
            // $Order = $this->orderHelper->initializeOrder($Cart, $Customer);
            $Order = $this->orderHelper->initializeOrderPr($item_infos, $Customer, $Shipping);

            $flowResult = $this->executePurchaseFlow($Order, false);
            $this->entityManager->flush();
            if ($flowResult->hasError()) {
                log_info('[注文手続] Errorが発生したため購入エラー画面へ遷移します.', [$flowResult->getErrors()]);
                return $this->returnError($flowResult->getErrors()[0]);
            }
    
            if ($flowResult->hasWarning()) {
                log_info('[注文手続] Warningが発生しました.', [$flowResult->getWarning()]);
                // 受注明細と同期をとるため, CartPurchaseFlowを実行する
                $cartPurchaseFlow->validate($Cart, new PurchaseContext());
                $this->cartService->save();
            }
            // マイページで会員情報が更新されていれば, Orderの注文者情報も更新する.
            if ($Customer->getId()) {
                $this->orderHelper->updateCustomerInfo($Order, $Customer);
                $this->entityManager->flush();
            }
            $this->entityManager->commit();
        }else{
            $Order = $this->orderHelper->initializeOrderPr($item_infos, $Customer, $Shipping);
        }

        $paytype = $this->getPayType($request);
        $Order->setPaymentMethod($paytype);
        $this->entityManager->flush();

        try{
            $response = $this->executePurchaseFlow($Order);
            $this->entityManager->flush();

            if($response){
                log_error("[purchaseFlow] error");
                return $this->returnError(trans("stripe_payment_gateway.payrequest.purchase_flow.error"));
            }
            $this->pr_service->apply($Order);
            
            // create Intent
            $res = $this->pr_service->createIntent($payment_method_id);
            if($res === false){
                log_info("xxx5");
                log_info($this->pr_service->getError());
                return $this->returnError($this->pr_service->getError());
            }else if(!empty($res['requires_action'])){
                /**
                 * save Order with intent id
                 *  */ 
                log_info("xxx6");
                $Order->setTempIntentId($res['payment_intent_id']);
                $this->persist($Order);
                $this->flush();
                return $this->json([
                    'status'    =>  'requires_action',
                    'secret'    =>  $res['payment_intent_client_secret']
                ]);
            }
            $payment_intent = $res;

            $result = $this->pr_service->checkout($payment_intent->id);
            log_info("xxx7");
        }catch(ShoppingException $e){
            log_error("[注文手続] shoppingError. ", [$e->getMessage]);
            $this->entityManager->rollback();
            return $this->returnError($e->getMessage());
        }catch(\Exception $e){
            log_error("[注文処理] shopping system error. ", [$e->getMessage()]);
            $this->entityManager->rollback();
            return $this->returnError($e->getMessage());
        }

        if(!empty($cart_key)){
            $cart_items = $this->cartService->getCart()->getCartItems();
            foreach($cart_items as $cart_item){
                $this->entityManager->remove($cart_item);
            }
            $this->entityManager->flush();
            $this->cartService->clear();
        }
        $this->session->set(OrderHelper::SESSION_ORDER_ID, $Order->getId());

        log_info('[注文処理] 注文メールの送信を行います.', [$Order->getId()]);
        $this->mailService->sendOrderMail($Order);
        $this->entityManager->flush();

        return $this->json([
            'status'    =>  'success',
        ]);
    }

    /**
     * @Route("/plugin/stripe_payment_gateway/confirm_intent", name="plugin_stripe_confirm_intent")
     */
    public function confirmIntent(Request $request){
        $payment_intent_id = $request->request->get('payment_intent_id');
        if(empty($payment_intent_id)){
            return $this->returnError("invalid_request");
        }
        $Order = $this->entityManager->getRepository(Order::class)->findOneBy(['temp_intent_id' => $payment_intent_id]);
        $this->pr_service->setOrder($Order);
        $PaymentIntent = $this->pr_service->confirmIntent($payment_intent_id);
        if(empty($PaymentIntent)){
            return $this->returnError($this->pr_service->getError());
        }
        
        try{
            $response = $this->executePurchaseFlow($Order);
            $this->entityManager->flush();

            if($response){
                log_error("[purchaseFlow] error");
                return $this->returnError(trans("stripe_payment_gateway.payrequest.purchase_flow.error"));
            }
            $this->pr_service->apply($Order);
            
            $result = $this->pr_service->checkout($payment_intent);
            $this->entityManager->flush();
            
        }catch(ShoppingException $e){
            log_error("[注文手続] shoppingError. ", [$e->getMessage]);
            $this->entityManager->rollback();
            return $this->returnError($e->getMessage());
        }catch(\Exception $e){
            log_error("[注文処理] shopping system error. ", [$e->getMessage()]);
            $this->entityManager->rollback();
            return $this->returnError($e->getMessage());
        }

        $this->session->set(OrderHelper::SESSION_ORDER_ID, $Order->getId());

        log_info('[注文処理] 注文メールの送信を行います.', [$Order->getId()]);
        $this->mailService->sendOrderMail($Order);
        $this->entityManager->flush();

        return $this->json([
            'status'    =>  'success',
        ]);

    }
    private function returnError($error){
        return $this->json([
            'status'    =>  'failed',
            'error'     =>  $error,
        ]);
    }

    private function getPayType($request){
        $is_apple = $request->request->get('applePay');
        
        if($is_apple == "true"){
            return "Apple Pay";
        }else{
            $user_agent = $_SERVER['HTTP_USER_AGENT'];
            if(\strpos($user_agent, "Edg/") === false){
                return "Google Pay";
            }else{
                return "Microsoft Pay";
            }
        }
    }
}