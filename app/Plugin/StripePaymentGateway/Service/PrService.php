<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2020 Subspire. All Rights Reserved.
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\StripePaymentGateway\Service;

include_once(dirname(__FILE__).'/../vendor/stripe/stripe-php/init.php');
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Entity\Product;
use Eccube\Entity\ProductClass;
use Eccube\Entity\Delivery;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\SaleType;
use Eccube\Entity\DeliveryFee;
use Eccube\Entity\BaseInfo;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\StripePaymentGateway\StripeClient;
use Plugin\StripePaymentGateway\Entity\StripeCustomer;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Plugin\StripePaymentGateway\Entity\StripeOrder;
use Stripe\PaymentIntent;
class PrService extends StripeService {

    protected $encoderFactory;
    protected $tokenStorage;
    protected $purchaseFlow;

    protected $Order;
    protected $baseInfo;

    protected $error;

    public function __construct(
        ContainerInterface $container,
        EncoderFactoryInterface $encoderFactory,
        TokenStorageInterface $tokenStorage,
        PurchaseFlow $shoppingPurchaseFlow,
        EccubeConfig $eccubeConfig
    ){
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $this->encoderFactory = $encoderFactory;
        $this->tokenStorage = $tokenStorage;
        $this->purchaseFlow = $shoppingPurchaseFlow;

        parent::__construct($container, $entityManager, $eccubeConfig);

        $this->base_info = $entityManager->getRepository(BaseInfo::class)->get();
    }
    public function getError(){
        return $this->error;
    }

    public function validatePrShipping(Request $request){
        $shippingAddress = $request->request->get('shippingAddress');
        
        if(empty($shippingAddress) || empty($shippingAddress['region'])){
            $this->error = "not_valid_region_or_address";
            return false;
        }
        
        
        $prefecture = $shippingAddress['region'];
        $Pref = $this->entityManager->getRepository(Pref::class)->findOneBy(['name' =>  $prefecture]);
        
        if(empty($Pref)){
            $this->error = "invalid_prefecture";
            return false;
        }
        if(empty($request->request->get('data'))){
            $this->error = "empty_data";
            return false;
        }
        // $sale_type = $ProductClass->getSaleType();
        // $deliveries = $this->entityManager->getRepository(Delivery::class)->getDeliveries($sale_type);
        
        return [
            'shippingAddress'   =>  $shippingAddress,
            'Pref'              =>  $Pref,
            'data'              =>  $request->request->get('data')
        ];
    }

    public function validateMethodRequest(Request $request, $Carts){
        $method_name = $request->request->get('methodName');
        $email = $request->request->get('payerEmail');
        $phone = $request->request->get('payerPhone');
        $name = $request->request->get('payerName');
        
        $res = $this->validatePrShipping($request);
        if($res === false){
            return false;
        }
        extract($res);

        if(!empty($data['cart_key'])){
            $Cart = $this->findCart($Carts, $data['cart_key']);
            if(empty($Cart)){
                $this->error = "no_such_key";
                return false;
            }
            if(empty($Cart->getItems())){
                $this->error = "empty_items";
                return false;
            }
            $items = $this->getItemsFromCart($Cart);
        }else{
            $items = $this->validateItems($data);
        }
        if(empty($items)){
            return false;
        }
        $deliveries = $this->getDeliveries($items);

        if(empty($method_name) || empty($email) || empty($name) || empty($shippingAddress)){
            $this->error = "invalid_input";
            return false;
        }
        if(empty($shippingAddress['addressLine']) || empty($shippingAddress['postalCode']) || empty($shippingAddress['region']) || empty($shippingAddress['country'])){
            $this->error = "invalid_input";
            return false;
        }
        if($shippingAddress['country'] !== "JP"){
            $this->error = "invalid_country";
            return false;
        }
        if(empty($shippingAddress['recipient'])){
            $this->error = "invalid_recipient";
            return false;
        }
        if(empty($request->request->get('shippingOption'))){
            $this->error = "invalid_input";
            return false;
        }
        $shippingOption = $request->request->get('shippingOption');
        if(empty($shippingOption['id'])){
            $this->error = "invalid_shiping_option";
            return false;
        }
        if(empty($request->request->get('paymentMethod'))){
            $this->error = "invalid_method";
            return false;
        }
        $payment_method = $request->request->get('paymentMethod');

        foreach($deliveries as $item){
            if($item->getId() == $shippingOption['id']){
                $delivery = $item;
            }
        }
        if(empty($delivery)){
            $this->error = "invalid_shipping_option_id";
            return false;
        }
        

        $customer_info = [];
        $customer_info['name01'] = $name;
        $customer_info['name02'] = "";
        $customer_info['organization'] = empty($shippingAddress['organization']) ? "" : $shippingAddress['organization'];
        $customer_info['postal_code'] = $shippingAddress['postalCode'];
        $customer_info['addr01'] = $shippingAddress['addressLine'][0];
        $customer_info['addr02'] = "";
        if(!empty($shippingAddress['addressLine'][1])){
            $customer_info['addr02'] = $shippingAddress['addressLine'][1];
        }
        $customer_info['phone_number'] = $phone ? $phone : $shippingAddress['phone'];
        $customer_info['email'] = $email;
        $customer_info['Pref'] = $Pref;
        
        $shipping_info = [];
        $shipping_info['name01'] = $shippingAddress['recipient'];
        $shipping_info['name02'] = "";
        $shipping_info['organization'] = "";
        $shipping_info['postal_code'] = $shippingAddress['postalCode'];
        $shipping_info['Pref'] = $Pref;
        $shipping_info['addr01'] = $shippingAddress['addressLine'][0];
        $shipping_info['addr02'] = "";
        if(!empty($shippingAddress['addressLine'][1])){
            $shipping_info['addr02'] = $shippingAddress['addressLine'][1];
        }
        $shipping_info['phone_number'] = $shippingAddress['phone'];
        $Shipping = $this->getShipping($shipping_info, $delivery);

        
        $res = [
            'customer_info' =>  $customer_info,
            'Shipping'      =>  $Shipping,
            'payment_method_id'         =>  $payment_method['id'],
        ];
        if(!empty($data['cart_key'])){
            $res['cart_key'] = $data['cart_key'];
        }else{
            $res['item_infos'] = $items;
        }
        return $res;
    }


    public function signup($customer_info, $Customer = null){
        if(empty($Customer)){
            $Customer = $this->entityManager->getRepository(Customer::class)->newCustomer();
        }
        $Customer->setName01($customer_info['name01']);
        $Customer->setName02($customer_info['name02']);
        if(!empty($customer_info['organization'])){
            $Customer->setCompanyName($customer_info['organization']);
        }
        $Customer->setPostalCode($customer_info['postal_code']);
        $Customer->setAddr01($customer_info['addr01']);
        if(!empty($customer_info['addr02'])){
            $Customer->setAddr02($customer_info['addr02']);
        }
        $Customer->setPhoneNumber( \str_replace("+81", "", $customer_info['phone_number']));
        $Customer->setEmail($customer_info['email']);
        $Customer->setPref($customer_info['Pref']);

        $encoder = $this->encoderFactory->getEncoder($Customer);
        $salt = $encoder->createSalt();
        $password = $encoder->encodePassword("123123123", $salt);
        $secretKey = $this->entityManager->getRepository(Customer::class)->getUniqueSecretKey();

        $Customer
            ->setSalt($salt)
            ->setPassword($password)
            ->setSecretKey($secretKey)
            ->setPoint(0);

        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        $CustomerStatus = $this->entityManager->getRepository(CustomerStatus::class)->find(CustomerStatus::PROVISIONAL);
        $Customer->setStatus($CustomerStatus);
        $this->entityManager->persist($Customer);
        $this->entityManager->flush();

        // 本会員登録してログイン状態にする
        $token = new UsernamePasswordToken($Customer, null, 'customer');
        $this->tokenStorage->setToken($token);
    }

    public function getShipping($shipping_info, $Delivery){
        $Shipping = new Shipping();
        $Shipping
            ->setName01($shipping_info['name01'])
            ->setName02($shipping_info['name02'])
            ->setCompanyName($shipping_info['organization'])
            ->setPhoneNumber($shipping_info['phone_number'])
            ->setPostalCode($shipping_info['postal_code'])
            ->setPref($shipping_info['Pref'])
            ->setAddr01($shipping_info['addr01']);
        if($shipping_info['addr02']){
            $Shipping->setAddr02($shipping_info['addr02']);
        }
        $Shipping->setDelivery($Delivery);
        $Shipping->setShippingDeliveryName($Delivery->getName());
        return $Shipping;
    }

    public function apply($Order){
        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->entityManager->getRepository(OrderStatus::class)->find(OrderStatus::PENDING);
        $Order->setOrderStatus($OrderStatus);

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->purchaseFlow->prepare($Order, new PurchaseContext());
        $this->Order = $Order;
    }

    public function createIntent($payment_method_id){
        if(!$this->Order){
            $this->error = "invalid_request";
            return false;
        }
        // BOC retrieve payment method
        $StripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigByOrder($this->Order);
        $stripeClient = new StripeClient($StripeConfig->secret_key);
        $payment_method = $stripeClient->retrievePaymentMethod($payment_method_id);

        $pay_str = \print_r($payment_method, true);
        log_info(__CLASS__ . "/" . __FUNCTION__ . "/" . __LINE__);
        log_info($pay_str);
        if(empty($payment_method->card->funding) || $payment_method->card->funding === "prepaid"){
            $this->error = trans('stripe_payment_gateway.shopping.card_error');
            return false;
        }
        // EOC retrieve payment method

        //BOC Create temp customer
        $stripeCustomerId=$stripeClient->createCustomerV2($this->Order->getEmail(),0,$this->Order->getId());
        if (is_array($stripeCustomerId) && isset($stripeCustomerId['error'])) {//In case of fail
            $errorMessage=StripeClient::getErrorMessageFromCode($stripeCustomerId['error'], $this->eccubeConfig['locale']);
            $this->error = $errorMessage;
            return false;
        }
        // EOC Create temp customer

        // BOC create paymentIntent
        $amount = $this->Order->getTotal();
        $paymentIntent = $stripeClient->createPaymentIntentWithCustomer(
                $amount, 
                $payment_method_id, 
                $this->Order->getId(), 
                false,
                $stripeCustomerId,
                $this->Order->getCurrencyCode());
        // EOC create paymentIntent

        // BOC make response
        if($paymentIntent->status == "requires_action" && $paymentIntent->next_action->type == "use_stripe_sdk"){
            return [
                'requires_action'   =>  true,
                'payment_intent_client_secret'  =>  $paymentIntent->client_secret,
                'payment_intent_id' =>  $paymentIntent->id
            ];
        }else if($paymentIntent->status == "succeeded" || $paymentIntent->status == "requires_capture") {
            return $paymentIntent;
        }else{
            $this->error = trans("stripe_payment_gateway.payrequest.payment_intent.invalid_status");
            return false;
        }
        // EOC make response
    }

    public function confirmIntent($payment_intent_id){
        $StripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigbyOrder($this->Order);
        $stripeClient = new StripeClient($StripeConfig->secret_key);

        $PaymentIntent = $stripeClient->retrievePaymentIntent($payment_intent_id);
        if(empty($PaymentIntent)){
            $this->error = "invalid_intent";
            return false;
        }
        $PaymentIntent->confirm();
        return $PaymentIntent;
    }
    public function checkout($payment_intent_id){
        $StripeConfig = $this->entityManager->getRepository(StripeConfig::class)->getConfigbyOrder($this->Order);
        $stripeClient = new StripeClient($StripeConfig->secret_key);
        
        $is_capture = $StripeConfig->is_auth_and_capture_on;
        
        // BOC capture payment 
        if($payment_intent_id instanceof PaymentIntent){
            $paymentIntent = $payment_intent_id;
            if($is_capture){
                $this->writeRequestLog($this->Order, 'capturePaymentIntent');
                $paymentIntent = $stripeClient->capturePaymentIntent($payment_intent->id, $this->Order->getTotal(), $this->Order->getCurrencyCode());
                $this->writeResponseLog($this->Order, 'capturePaymentIntent', $payment_intent);

            }
        }else{
            if($is_capture){
                $this->writeRequestLog($this->Order, 'capturePaymentIntent');
                $paymentIntent = $stripeClient->capturePaymentIntent($payment_intent_id, $this->Order->getTotal(), $this->Order->getCurrencyCode());
                $this->writeResponseLog($this->Order, 'capturePaymentIntent', $paymentIntent);
            }else{
                $paymentIntent = $stripeClient->retrievePaymentIntent($payment_intent_id);
            }
        }
        if(is_array($paymentIntent) && isset($paymentIntent['error'])){
            $this->error = StripeClient::getErrorMessageFromCode($paymentIntent['error'], $this->eccubeConfig['locale']);
            $this->purchaseFlow->rollback($this->Order, new PurchaseContext());
            throw new \Exception($this->error);
        }
        // EOC capture payment

        // BOC create stripe order
        $StripeOrder = new StripeOrder();
        $StripeOrder->setOrder($this->Order);
        
        $StripeOrder->setStripePaymentIntentId($paymentIntent->id);
        if ($is_capture) {
            foreach($paymentIntent->charges as $charge) {
                $StripeOrder->setStripeChargeId($charge->id);
                break;
            }
        }
        $StripeOrder->setIsChargeCaptured($is_capture);
        $StripeOrder->setStripeCustomerIdForGuestCheckout($paymentIntent->customer);
        
        $StripeOrder->setCreatedAt(new \DateTime());
        $this->entityManager->persist($StripeOrder);

        // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        if($is_capture){
            $this->Order->setOrderStatus($this->entityManager->getRepository(OrderStatus::class)->find(OrderStatus::PAID));
            $this->entityManager->persist($this->Order);
            $this->entityManager->flush();
        }
        // EOC create stripe order
        return true;
    }

    public function setOrder($Order){
        $this->Order = $Order;
    }
    public function validateItems($item_infos){

        $result = [];
        foreach($item_infos as $item_info){

            $product_id = $item_info['product'];
            $prod_class_id = $item_info['productClass'];
            $quantity = $item_info['quantity'];
            if( empty($product_id) || empty($prod_class_id) || empty($quantity) ){
                $this->error = "not_valid_items";
                return false;
            }
    
            $Product = $this->entityManager->getRepository(Product::class)->find($product_id);
            if(empty($Product)){
                $this->error = "not_valid_product";
                return false;
            }
            $ProductClass = $this->entityManager->getRepository(ProductClass::class)->find($prod_class_id);
            if(empty($ProductClass)){
                $this->error = "not_valid_product_class";
                return false;
            }
    
            if($ProductClass->getProduct() !== $Product){
                $this->error = "not_matched_product_class";
                return false;
            }
            $result[] = [
                'Product'           =>  $Product,
                'ProductClass'        =>  $ProductClass,
                'quantity'          =>  $quantity,
            ];
        }
        return $result;
    }
    public function calcAmountByDelivery($items, $Delivery, $Pref){
        $deliveryFeeProduct = 0;
        if($this->base_info->isOptionProductDeliveryFee()){
            foreach($items as $item){
                if(!$item->isProduct()){
                    continue;
                }
                $deliveryFeeProduct += $item['ProductClass']->getDeliveryFee() * $item->getQuantity();
            }
        }
        $DeliveryFee = $this->entityManager->getRepository(DeliveryFee::class)->findOneBy([
            'Delivery'  =>  $Delivery,
            'Pref'      =>  $Pref,
        ]);
        return $DeliveryFee->getFee() + $deliveryFeeProduct;
    }
    public function calcAmountByItems($items){
        
        $amount = 0;
        foreach($items as $item){
            $amount += $item['ProductClass']->getPrice02IncTax() * $item['quantity'];
        }
        return $amount;
    }
    public function getDeliveries($items){
        $OrderItemsGroupBySaleType = array_reduce($items, function ($result, $item) {
            /* @var OrderItem $item */
            $saleTypeId = $item['ProductClass']->getSaleType()->getId();
            $result[$saleTypeId][] = $item;
            return $result;
        }, []);
        if(count($OrderItemsGroupBySaleType) > 1 || count($OrderItemsGroupBySaleType) < 1){
            $this->error = "not_matched_items";
            return false;
        }
        foreach($OrderItemsGroupBySaleType as $saleTypeId => $order_items){
            $SaleType = $this->entityManager->getRepository(SaleType::class)->find($saleTypeId);
            $deliveries = $this->entityManager->getRepository(Delivery::class)->getDeliveries($SaleType);
        break;
        }
        return $deliveries;

    }

    public function getItemsFromCart($Cart){
        $items = [];
        $cart_items = $Cart->getCartItems();
        foreach($cart_items as $cart_item){
            $ProductClass = $cart_item->getProductClass();
            $Product = $ProductClass->getProduct();
            $quantity = $cart_item->getQuantity();

            $items[] = [
                'Product'   =>  $Product,
                'ProductClass'  =>  $ProductClass,
                'quantity'  =>  $quantity,
            ];
        }
        return $items;
    }
    public function findCart($Carts, $cart_key){
        foreach($Carts as $cart){
            if($cart->getCartKey() === $cart_key){
                $Cart = $cart;
            break;
            }
        }
        if(empty($Cart)){
            $this->error = "no_such_key";
            return false;
        }
        if(empty($Cart->getCartItems())){
            $this->error = "empty_cart";
            return false;
        }
        return $Cart;
    }
}