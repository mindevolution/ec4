<?php

namespace Plugin\StripePaymentGateway\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;
use Eccube\Service\CartService;
use Eccube\Service\OrderHelper;
use Eccube\Service\MailService;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Controller\ShoppingController;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Plugin\StripePaymentGateway\StripeClient;
use Plugin\StripePaymentGateway\Entity\StripeCustomer;
use Eccube\Controller\AbstractController;
use Plugin\StripePaymentGateway\Entity\StripeOrder;
use Plugin\StripePaymentGateway\Service\UtilService;

include_once(dirname(__FILE__).'/../vendor/stripe/stripe-php/init.php');

use Stripe\PaymentMethod;
use Stripe\PaymentIntent;

class StripePaymentController extends AbstractController {

    protected $container;
    private $stripe_config;    
    protected $util_service;
    protected $entityManager;
    protected $orderHelper;
    protected $cartService;

    public function __construct(
        ContainerInterface $container,
        CartService $cartService,
        OrderHelper $orderHelper
    ) {
        $this->container = $container;
        $this->entityManager = $container->get('doctrine.orm.entity_manager'); 
        $this->stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
        $this->util_service = $this->container->get("plg_stripe_payment.service.util");
        $this->orderHelper = $orderHelper;
        $this->cartService = $cartService;
    }
    

    /**
     * @Route("/plugin/stripe_payment_gateway/credit_card", name="plugin_stripe_credit_card")
     * @Template("@StripePaymentGateway/default/Shopping/stripe_credit_card.twig")
     */
    public function credit_payment(Request $request)
    {
        // ログイン状態のチェック.
        if ($this->orderHelper->isLoginRequired()) {
            log_info('[注文処理] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');

            return $this->redirectToRoute('shopping_login');
        }
        // 受注の存在チェック
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            log_info('[注文処理] 購入処理中の受注が存在しません.', [$preOrderId]);
            
            return $this->redirectToRoute('shopping_error');
        }
        $StripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigByOrder($Order);
        
        $Customer = $Order->getCustomer();
        // フォームの生成.
        $form = $this->createForm(OrderType::class, $Order,[
            'skip_add_form' => true,
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            if($this->isGranted('ROLE_USER')){
                $stripePaymentMethodObj = $this->checkSaveCardOn($Customer, $StripeConfig);
                if($stripePaymentMethodObj){
                    $exp = \sprintf("%02d/%d", $stripePaymentMethodObj->card->exp_month, $stripePaymentMethodObj->card->exp_year);
                }else{
                    $exp = "";
                }
            }else{
                $stripePaymentMethodObj = false;
                $exp = "";
            }

            $nonmem = false;
            if (!$this->getUser() && $this->orderHelper->getNonMember()) {
                $nonmem = true;
            }

            return [
                'stripeConfig'  =>  $StripeConfig,
                'Order'         =>  $Order,
                'stripePaymentMethodObj'    =>  $stripePaymentMethodObj,
                'exp'       =>  $exp,
                'nonmem'    =>  $nonmem,
                'form'      =>  $form->createView(),
            ];
        }
        return $this->redirectToRoute('shopping');
    }

    /**
     * @Route("/plugin/stripe_payment_gateway/konbini", name="plugin_stripe_konbini")
     * @Template("@StripePaymentGateway/default/Shopping/stripe_konbini.twig")
     */
    public function konbiniPayment(Request $request)
    {
        // ログイン状態のチェック.
        if ($this->orderHelper->isLoginRequired()) {
            log_info('[注文処理] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');
            return $this->redirectToRoute('shopping_login');
        }
        // 受注の存在チェック
        $preOrderId = $this->cartService->getPreOrderId();
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            log_info('[注文処理] 購入処理中の受注が存在しません.', [$preOrderId]);
            
            return $this->redirectToRoute('shopping_error');
        }
        // フォームの生成.
        $form = $this->createForm(OrderType::class, $Order,[
            'skip_add_form' => true,
        ]);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->orderHelper->isLoginRequired()) {
                log_info('[注文処理] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');
    
                return $this->redirectToRoute('shopping_login');
            }
            $orderItems = $Order->getProductOrderItems();
            $products = [];
            foreach ($orderItems as $orderItem) 
            {
                $products[$orderItem->getProduct()->getId()] = $orderItem->getProduct()->getName();
            }
            $description = implode(",", $products);
            $description = UtilService::truncateString($description, 19, "...");
       
            $stripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigByOrder($Order);
            $stripeClient = new StripeClient($stripeConfig->secret_key);
            $paymentIntent = $stripeClient->createKonbiniIntentWithCustomer($Order, $description, 'JPY');
            
            $stripeOrder = $this->entityManager->getRepository(StripeOrder::class)->findOneBy(['Order' => $Order]);
            if (!$stripeOrder) {
                $stripeOrder = new StripeOrder();
                $stripeOrder->setOrder($Order);
            }
            
            $stripeOrder->setStripePaymentIntentId($paymentIntent->id);
            $stripeOrder->setIsChargeCaptured(false);
            $Customer = $Order->getCustomer();
            $isEcCustomer=false;
            if($Customer instanceof Customer ) {
                $isEcCustomer = true;
            }
            if (!$isEcCustomer) {
                $stripeOrder->setStripeCustomerIdForGuestCheckout($paymentIntent->customer);
            } else {
                $stripeOrder->setStripeCustomerIdForGuestCheckout('');
            }
            $stripeOrder->setCreatedAt(new \DateTime());
            $this->entityManager->persist($stripeOrder);
            $this->entityManager->flush();
            
            $clientSecret = $paymentIntent->client_secret;
            return [
                'Order'     =>  $Order,
                'form'      =>  $form->createView(),
                'clientSecret'=> $clientSecret,
                'stripeConfig'=> $stripeConfig
            ];

        }
        return $this->redirectToRoute('shopping');
    }
    /**
     * @Route("/plugin/stripe_payment_gateway/method_detach", name="plugin_stripe_payment_method_detach")
     */
    public function detachMethod(Request $request){
        $method_id = $request->request->get('payment_method_id');
        $Order = $this->getOrder();
        if($Order){
            $Customer = $Order->getCustomer();
            $StripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigByOrder($Order);

            $stripePaymentMethodObj = $this->checkSaveCardOn($Customer, $StripeConfig);
            if($stripePaymentMethodObj && $stripePaymentMethodObj->id == $method_id){
                $stripeClient = new StripeClient($StripeConfig->secret_key);
                $stripeClient->detachMethod($method_id);
                $StripeCustomer=$this->entityManager->getRepository(StripeCustomer::class)->findOneBy(array('Customer'=>$Customer));
                $StripeCustomer->setIsSaveCardOn(false);
                $this->entityManager->persist($StripeCustomer);
                $this->entityManager->flush();
                return $this->json([
                    'success'   =>  true,
                ]);
            }
        }
        return $this->json([
            'success'   =>  false,
            'error'     =>  "そのような済みはありません"
        ]);
    }
    

    /**
     * @Route("/plugin/stripe_payment_gateway/payment_intent", name="plugin_stripe_payment_gateway_payment")
     */
    public function paymentIntent(Request $request){
        $preOrderId = $this->cartService->getPreOrderId();
        /** @var Order $Order */
        $Order = $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
        if (!$Order) {
            return $this->json(['error' => 'true', 'message' => trans('stripe_payment_gateway.admin.order.invalid_request')]);
        }
        
        $StripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigByOrder($Order);
        
        $stripeClient = new StripeClient($StripeConfig->secret_key);
        $paymentMethodId = $request->request->get('payment_method_id');
        $isSaveCardOn = $request->request->get('is_save_on') === "true" ? true : false;
        $stripeCustomerId = $this->procStripeCustomer($stripeClient, $Order, $isSaveCardOn);
        if(is_array($stripeCustomerId)) { // エラー
            return $this->json($stripeCustomerId);
        }
        $amount = $Order->getPaymentTotal();
        $paymentIntent = $stripeClient->createPaymentIntentWithCustomer($amount, $paymentMethodId, $Order->getId(), $isSaveCardOn, $stripeCustomerId,$Order->getCurrencyCode());

        return $this->json($this->genPaymentResponse($paymentIntent));
    }
    
    // public function coupon_check(Request $request){
    //     $coupon_id = $request->request->get('coupon_id');
    //     if(empty($coupon_id)){
    //         return $this->json(['error' => trans('stripe_payment_gateway.shopping.coupon.input_error')]);
    //     }else{
    //         $StripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigByOrder(null);
    //         $coupon_service = $this->container->get('plg_stripe_payment.coupon_service');
    //         $coupon_service->setConfig($StripeConfig);
    //         $coupon_data = $coupon_service->retrieveCoupon($coupon_id);
            
    //         if($coupon_data){
    //             if($coupon_service->couponValidCheck($coupon_data) === true){
    //                 $Order = $this->getOrder();
    //                 $res = $coupon_service->couponAmount($Order->getTotal(), $coupon_id);
    //                 if(false === $res ){
    //                     return $this->json(['error' => $coupon_service->getError()]);
    //                 }
    //                 return $this->json(['success' => true]);
    //             }else{
    //                 return $this->json(['error' => $coupon_service->getError()]);
    //             }
    //         }else{
    //             return $this->json(['error' => $coupon_service->getError()]);
    //         }
    //     }
    // }

    private static function getFormErrorsTree(FormInterface $form): array
    {
        $errors = [];

        if (count($form->getErrors()) > 0) {
            foreach ($form->getErrors() as $error) {
                $errors[] = $error->getMessage();
            }
        } else {
            foreach ($form->all() as $child) {
                $childTree = self::getFormErrorsTree($child);

                if (count($childTree) > 0) {
                    $errors[$child->getName()] = $childTree;
                }
            }
        }

        return $errors;
    }
    private function procStripeCustomer(StripeClient $stripeClient, $Order, $isSaveCardOn) {

        $Customer = $Order->getCustomer();
        $isEcCustomer=false;
        $isStripeCustomer=false;
        $StripeCustomer = false;
        $stripeCustomerId = false;

        if($Customer instanceof Customer ){
            $isEcCustomer=true;
            $StripeCustomer=$this->entityManager->getRepository(StripeCustomer::class)->findOneBy(array('Customer'=>$Customer));
            if($StripeCustomer instanceof StripeCustomer){
                $stripLibCustomer = $stripeClient->retrieveCustomer($StripeCustomer->getStripeCustomerId());
                if(is_array($stripLibCustomer) || isset($stripLibCustomer['error'])) {
                    if(isset($stripLibCustomer['error']['code']) && $stripLibCustomer['error']['code'] == 'resource_missing') {
                        $isStripeCustomer = false;
                    }
                } else {
                    $isStripeCustomer=true;
                }
            }
        }

        if($isEcCustomer) {//Create/Update customer
            if($isSaveCardOn) {
                //BOC check if is StripeCustomer then update else create one
                if($isStripeCustomer) {
                    $stripeCustomerId=$StripeCustomer->getStripeCustomerId();
                    //BOC save is save card
                    $StripeCustomer->setIsSaveCardOn($isSaveCardOn);
                    $this->entityManager->persist($StripeCustomer);
                    $this->entityManager->flush($StripeCustomer);
                    //EOC save is save card

                    $updateCustomerStatus = $stripeClient->updateCustomerV2($stripeCustomerId,$Customer->getEmail());
                    if (is_array($updateCustomerStatus) && isset($updateCustomerStatus['error'])) {//In case of update fail
                        $errorMessage=StripeClient::getErrorMessageFromCode($updateCustomerStatus['error'], $this->eccubeConfig['locale']);
                        return ['error' => true, 'message' => $errorMessage];
                    }
                } else {
                    $stripeCustomerId=$stripeClient->createCustomerV2($Customer->getEmail(),$Customer->getId());
                    if (is_array($stripeCustomerId) && isset($stripeCustomerId['error'])) {//In case of fail
                        $errorMessage=StripeClient::getErrorMessageFromCode($stripeCustomerId['error'], $this->eccubeConfig['locale']);
                        return ['error' => true, 'message' => $errorMessage];
                    } else {
                        if(!$StripeCustomer) {
                            $StripeCustomer = new StripeCustomer();
                            $StripeCustomer->setCustomer($Customer);
                        }
                        $StripeCustomer->setStripeCustomerId($stripeCustomerId);
                        $StripeCustomer->setIsSaveCardOn($isSaveCardOn);
                        $StripeCustomer->setCreatedAt(new \DateTime());
                        $this->entityManager->persist($StripeCustomer);
                        $this->entityManager->flush($StripeCustomer);
                    }
                }
                //EOC check if is StripeCustomer then update else create one
                return $stripeCustomerId;
            }
        }
        //Create temp customer
        $stripeCustomerId=$stripeClient->createCustomerV2($Order->getEmail(),0,$Order->getId());
        if (is_array($stripeCustomerId) && isset($stripeCustomerId['error'])) {//In case of fail
            $errorMessage=StripeClient::getErrorMessageFromCode($stripeCustomerId['error'], $this->eccubeConfig['locale']);
            return ['error' => true, 'message' => $errorMessage];
        }
        return $stripeCustomerId;
    }

    private function genPaymentResponse($intent) {
        if($intent instanceof PaymentIntent ) {
            log_info("genPaymentResponse: " . $intent->status);
            switch($intent->status) {
                case 'requires_action':
                case 'requires_source_action':
                    return [
                        'action'=> 'requires_action',
                        'payment_intent_id'=> $intent->id,
                        'client_secret'=> $intent->client_secret
                    ];
                case 'requires_payment_method':
                case 'requires_source':
                    return [
                        'error' => true,
                        'message' => StripeClient::getErrorMessageFromCode('invalid_number', $this->eccubeConfig['locale'])
                    ];
                case 'requires_capture':
                    return [
                        'action' => 'requires_capture',
                        'payment_intent_id' => $intent->id
                    ];
                default:
                    return ['error' => true, 'message' => trans('stripe_payment_gateway.front.unexpected_error')];
//                    return ['error' => true, 'message' => trans('stripe_payment_gateway.front.unexpected_error')];
            }
        }
        if(isset($intent['error'])) {
            $errorMessage=StripeClient::getErrorMessageFromCode($intent['error'], $this->eccubeConfig['locale']);
        } else {
            $errorMessage = trans('stripe_payment_gateway.front.unexpected_error');
        }
        return ['error' => true, 'message' => $errorMessage];
    }

    private function getOrder(){
         // BOC validation checking
         $preOrderId = $this->cartService->getPreOrderId();
         /** @var Order $Order */
         return $this->orderHelper->getPurchaseProcessingOrder($preOrderId);
    }
    /**
     * PaymentMethodをコンテナから取得する.
     *
     * @param Order $Order
     * @param FormInterface $form
     *
     * @return PaymentMethodInterface
     */
    private function createPaymentMethod(Order $Order)
    {
        $PaymentMethod = $this->container->get($Order->getPayment()->getMethodClass());
        $PaymentMethod->setOrder($Order);

        return $PaymentMethod;
    }

    private function checkSaveCardOn($Customer, $StripeConfig){
        $isStripeCustomer = false;
        $StripeCustomer=$this->entityManager->getRepository(StripeCustomer::class)->findOneBy(array('Customer'=>$Customer));
        $stripeClient = new StripeClient($StripeConfig->secret_key);    
        if($StripeCustomer instanceof StripeCustomer){
            $stripLibCustomer = $stripeClient->retrieveCustomer($StripeCustomer->getStripeCustomerId());
            if(is_array($stripLibCustomer) || isset($stripLibCustomer['error'])) {
                if(isset($stripLibCustomer['error']['code']) && $stripLibCustomer['error']['code'] == 'resource_missing') {
                    $isStripeCustomer = false;
                }
            } else {
                $isStripeCustomer=true;
            }
        }
        if(!$isStripeCustomer) return false;
        if($StripeCustomer->getIsSaveCardOn()){
           $stripePaymentMethodObj = $stripeClient->retrieveLastPaymentMethodByCustomer($StripeCustomer->getStripeCustomerId());
           if( !($stripePaymentMethodObj instanceof PaymentMethod) || !$stripeClient->isPaymentMethodId($stripePaymentMethodObj->id) ) {
                return false;
            }else{
                return $stripePaymentMethodObj;
            }
        }else{
            return false;
        }
    }

}
