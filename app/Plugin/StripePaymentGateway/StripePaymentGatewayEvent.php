<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\StripePaymentGateway;

include_once(dirname(__FILE__).'/vendor/stripe/stripe-php/init.php');
use Eccube\Application;
use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Eccube\Event\EventArgs;
use Eccube\Entity\Payment;
use Eccube\Event\EccubeEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Plugin\StripePaymentGateway\Repository\StripeConfigRepository;
use Plugin\StripePaymentGateway\Service\Method\StripeCreditCard;
use Plugin\StripePaymentGateway\Entity\StripeOrder;
use Plugin\StripePaymentGateway\Repository\StripeOrderRepository;
use Plugin\StripePaymentGateway\Entity\StripeCustomer;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Plugin\StripePaymentGateway\Repository\StripeCustomerRepository;
use Plugin\StripePaymentGateway\StripeClient;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Customer as Customer;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Eccube\Service\OrderHelper;
use Plugin\StripePaymentGateway\Service\Method\StripeKonbini;

class StripePaymentGatewayEvent implements EventSubscriberInterface
{


    /**
     * @var エラーメッセージ
     */
    private $errorMessage = null;

    /**
     * @var 国際化
     */
    private static $i18n = array();

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var StripeConfigRepository
     */
    protected $stripeConfigRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var StripeOrderRepository
     */
    private $stripeOrderRepository;

    /**
     * @var StripeCustomerRepository
     */
    private $stripeCustomerRepository;

    /**
     * @var string ロケール（jaかenのいずれか）
     */
    private $locale = 'en';

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Session
     */
    protected $session;

    protected $container;
    protected $util_service;    

    public function __construct(
        EccubeConfig $eccubeConfig,
        StripeConfigRepository $stripeConfigRepository,
        StripeOrderRepository $stripeOrderRepository,
        StripeCustomerRepository $stripeCustomerRepository,
        OrderStatusRepository $orderStatusRepository,
        EntityManagerInterface $entityManager,
        SessionInterface $session,
        ContainerInterface $container
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->locale=$this->eccubeConfig['locale'];
        $this->stripeConfigRepository = $stripeConfigRepository;
        $this->stripeOrderRepository = $stripeOrderRepository;
        $this->stripeCustomerRepository = $stripeCustomerRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->entityManager = $entityManager;
        $this->session = $session;
        $this->container = $container;
        $this->util_service = $this->container->get("plg_stripe_payment.service.util");        
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig'   =>  'onShoppingTwig',
            'Shopping/confirm.twig' => 'onShoppingConfirmTwig',
            'Shopping/complete.twig' => 'onShoppingCompleteTwig',
            // '@StripePaymentGateway/default/Shopping/stripe_credit_card.twig' => 'onStripePayment',
            'front.shopping.complete.initialize'=>'onFrontShoppingCompleteInitialize',
            'Product/detail.twig'   =>  'onProductDetailTwig',
            // 'index.twig'            =>  'onIndexTestTwig',
            'Cart/index.twig'       =>  'onCartIndexTwig',
            'Mypage/history.twig'   =>  'onMypageHistoryTwig',
            '@admin/Order/index.twig' => 'onAdminOrderIndexTwig',
            '@admin/Order/edit.twig' => 'onAdminOrderEditTwig',
            // EccubeEvents::FRONT_SHOPPING_COMPLETE_INITIALIZE    =>  'onShoppingComplete',

            // EccubeEvents::MAIL_ORDER => 'sendOrderMailBefore',
            // EccubeEvents::ADMIN_ORDER_MAIL_INDEX_INITIALIZE => 'adminOrderMailInitAfter',
        ];
    }
    
    public function onShoppingTwig(TemplateEvent $event){
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => StripeCreditCard::class]);
        $Order = $event->getParameter('Order');

        $stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
        $checkout_ga_enable = $stripe_config->getCheckoutGaEnable();

        $payment_id = $Payment->getId();
        $stripe_method_name = $Payment->getMethod();

        $event->setParameter("stripe_pay_id", $payment_id);
        $event->setParameter("checkout_ga_enable", $checkout_ga_enable);
        $event->setParameter("stripe_method_name", $stripe_method_name);

        $KonbiniPayment = $paymentRepository->findOneBy(['method_class' => StripeKonbini::class]);
        $event->setParameter("stripe_konbini_pay_id", $KonbiniPayment->getId());
        $event->setParameter("konbini_method_name", $KonbiniPayment->getMethod());

        $event->addSnippet('@StripePaymentGateway/default/Shopping/paymethod_label.js.twig');

    }
    public function onShoppingConfirmTwig(TemplateEvent $event){
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => StripeCreditCard::class]);
        $Order = $event->getParameter('Order');

        $stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
        $checkout_ga_enable = $stripe_config->getCheckoutGaEnable();

        if($Payment && ($Order->getPayment()->getMethodClass() == StripeCreditCard::class 
                || $Order->getPayment()->getMethodClass() == StripeKonbini::class)){
            $payment_id = $Payment->getId();
            $method_name = $Payment->getMethod();

            $event->setParameter("stripe_pay_id", $payment_id);
            $event->setParameter('checkout_ga_enable', $checkout_ga_enable);
            $event->setParameter("method_name", $method_name);
            $event->setParameter("stripe_method_name", $method_name);

            $event->addSnippet('@StripePaymentGateway/default/Shopping/paymethod_label.js.twig');
            $event->addSnippet('@StripePaymentGateway/default/Shopping/shopping.js.twig');
        }

    }

    public function onShoppingCompleteTwig(TemplateEvent $event) {
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => StripeKonbini::class]);
        $Order = $event->getParameter('Order');

        if($Payment && $Order->getPayment()->getMethodClass() == StripeKonbini::class) {
            $stripeOrder = $this->stripeOrderRepository->findOneBy(array('Order' => $Order));
            if ($stripeOrder) {
                $event->setParameter('voucher_url', $stripeOrder->getKonbiniVoucherUrl());
                $event->addSnippet('@StripePaymentGateway/default/Shopping/complete_konbini.twig');
            }
        }
    }
    public function onMypageHistoryTwig(TemplateEvent $event) {
        $paymentRepository = $this->entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy(['method_class' => StripeKonbini::class]);
        $Order = $event->getParameter('Order');

        if($Payment && $Order->getPayment()->getMethodClass() == StripeKonbini::class) {
            $stripeOrder = $this->stripeOrderRepository->findOneBy(array('Order' => $Order));
            if ($stripeOrder) {
                $event->setParameter('voucher_url', $stripeOrder->getKonbiniVoucherUrl());
                $event->addSnippet('@StripePaymentGateway/default/Mypage/history_konbini.twig');
            }
        }
    }

    public function onCartIndexTwig(TemplateEvent $event){
        $stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
        if($stripe_config->getCartGaEnable()){
            $event->setParameter('stripeConfig', $stripe_config);
            $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_official.js.twig');
            $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_request_button.js.twig');
            $event->addSnippet('@StripePaymentGateway/default/Cart/index.js.twig');
        }
    }

    // public function onIndexTestTwig(TemplateEvent $event){
    //     $payrequest = [
    //         'currency_code' =>  'jpy',
    //         'label'         =>  'Pay Now',
    //         'amount'        =>  0,
    //     ];
    //     $stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
    //     $event->setParameter('cartKey', "1_2");
    //     $event->setParameter('payrequest', $payrequest);
    //     $event->setParameter('stripeConfig', $stripe_config);
    //     $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_official.js.twig');
    //     $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_request_button.js.twig');
    //     $event->addSnippet('@StripePaymentGateway/default/index_test.twig');
    // }

    public function onProductDetailTwig(TemplateEvent $event){
        $stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
        if($stripe_config->getProdDetailGaEnable()){
            $product = $event->getParameter('Product');
            $first_pc = $product->getProductClasses()[0];
            
            $payrequest = [
                'currency_code' =>  \strtolower($first_pc->getCurrencyCode()),
                'label'         =>  trans('stripe_payment_gateway.payrequest.button_label'),
                'amount'        =>  $first_pc->getPrice02IncTax(),
            ];
            
    
            $event->setParameter('payrequest', $payrequest);
            $event->setParameter('stripeConfig', $stripe_config);
            $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_official.js.twig');
            $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_request_button.js.twig');
            $event->addSnippet('@StripePaymentGateway/default/Product/detail.js.twig');
        }
    }

    public function onStripePayment(TemplateEvent $event){
        $stripe_config = $this->entityManager->getRepository(StripeConfig::class)->get();
        $checkout_ga_enable = $stripe_config->getCheckoutGaEnable();
        $event->setParameter('checkout_ga_enable', $checkout_ga_enable);
        // $event->addAsset('StripePaymentGateway/Resource/assets/css/base.css.twig');
        // $event->addAsset('StripePaymentGateway/Resource/assets/css/stripe_4.css.twig');
        // $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_official.js.twig');
        // $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_payment.js.twig');
        // $event->addSnippet('StripePaymentGateway/Resource/assets/js/stripe_elements.js.twig');
    }

    /**
     * @param EventArgs $event
     */
    public function onFrontShoppingCompleteInitialize(EventArgs $event){
        $Order=$event->getArgument('Order');
        $this->session->getFlashBag()->set('stripe_payment_gateway_intent_id', false);
        if($Order) {
            if ($Order->getPayment()->getMethodClass() === StripeCreditCard::class) {
                $stripeOrder = $this->stripeOrderRepository->findOneBy(array('Order'=>$Order));
                if($stripeOrder) {
                    $stripeChargeID = $stripeOrder->getStripeChargeId();
                    if (!empty($stripeChargeID)) {
                        $Today = new \DateTime();
                        $Order->setPaymentDate($Today);
                        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                        $Order->setOrderStatus($OrderStatus);
                        $this->entityManager->persist($Order);
                        $this->entityManager->flush($Order);
                    }
                }
            }
        }
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminOrderIndexTwig(TemplateEvent $event)
    {
        // 表示対象の受注一覧を取得
        $pagination = $event->getParameter('pagination');

        if (empty($pagination) || count($pagination) == 0)
        {
            return;
        }

        $OrderToSearch=array();
        foreach ($pagination as $Order){
            $OrderToSearch[] = $Order;
        }
        if (empty($OrderToSearch)) {
            return;
        }

        $StripeOrders = $this->stripeOrderRepository->findBy(array('Order'=>$OrderToSearch));

        if (!$StripeOrders)
        {
            return;
        }

        $StripeOrdersMapping = array();
        foreach($StripeOrders as $stripeOrder) {
            $Order = $stripeOrder->getOrder();
			$OrderId = $Order->getId();
			$StripeConfig = $this->stripeConfigRepository->getConfigByOrder($Order);
			if($StripeConfig) {
                $publishableKey=$StripeConfig->publishable_key;
                if(strpos($publishableKey, 'live') !== false) {
                    $isLive = true;
                } else {
                    $isLive = false;
                }
                $dashboard_url = $this->getStripeChargeDashboardLink($isLive) . $stripeOrder->getStripeChargeId();
            } else {
                $dashboard_url = "#" . $stripeOrder->getStripeChargeId();
            }
            $order_edit_url = $this->container->get('router')->generate('admin_order_edit', array('id' => $OrderId), UrlGeneratorInterface::ABSOLUTE_URL);
            $StripeOrdersMapping[] = (object)[ 
                'order_edit_url' => $order_edit_url, 
                'charge_id' => $stripeOrder->getStripeChargeId(), 
                'dashboard_url' => $dashboard_url,
                'Order'     =>  $Order,
            ];
        }

        $event->setParameter('StripeOrdersMapping', $StripeOrdersMapping);
//        $event->setParameter('StripeChargeDashboardLink',$this->getStripeChargeDashboardLink());

        $asset = 'StripePaymentGateway/Resource/assets/js/admin/order_index.js.twig';
        $event->addAsset($asset);
    }

    /**
     * @param TemplateEvent $event
     */
    public function onAdminOrderEditTwig(TemplateEvent $event)
    {
        // 表示対象の受注情報を取得
        $Order = $event->getParameter('Order');

        if (!$Order)
        {
            return;
        }

		$StripeConfig = $this->stripeConfigRepository->getConfigByOrder($Order);
        // EC-CUBE支払方法の取得
        $Payment = $Order->getPayment();

        if (!$Payment)
        {
            return;
        }

        if ($Order->getPayment()->getMethodClass() === StripeCreditCard::class) {

            $StripeOrder = $this->stripeOrderRepository->findOneBy(array('Order'=>$Order));

            if (!$StripeOrder)
            {
                return;
            }
            if($StripeOrder->getIsChargeRefunded()==1 && $StripeOrder->getSelectedRefundOption()==0 && $StripeOrder->getRefundedAmount()==0) {
                $StripeOrder->setSelectedRefundOption(1);
                $StripeOrder->setRefundedAmount($Order->getPaymentTotal());
                $this->entityManager->persist($StripeOrder);
                $this->entityManager->flush($StripeOrder);
            }

//            $StripeConfig = $this->stripeConfigRepository->get();
            $publishableKey=$StripeConfig->publishable_key;
            if(strpos($publishableKey, 'live') !== false) {
                $isLive = true;
            } else {
                $isLive = false;
            }

            $event->setParameter('StripeConfig', $StripeConfig);
            $event->setParameter('StripeOrder', $StripeOrder);
            $event->setParameter('StripeChargeDashboardLink',$this->getStripeChargeDashboardLink($isLive));

            $event->addSnippet('@StripePaymentGateway/admin/Order/edit.twig');
        }
    }

    private function getScriptDiskPath() {
        return dirname(__FILE__).'/Resource/assets/js/stripe_' . $this->locale . '.js.twig';
    }

    private function makeScript() {
        return;
        $buff = file_get_contents(dirname(__FILE__) . '/Resource/assets/js/stripe_js.twig');
        $out_path = $this->getScriptDiskPath();
        $m = array();
        preg_match_all('/\{\{ (\w+) \}\}/', $buff, $m);
        for ($i = 0; $i < sizeof($m[0]); $i++) {
            //$buff = str_replace($m[0][$i], self::getLocalizedString($m[1][$i], $this->locale), $buff);
            if($m[1][$i]=='locale'){
                $buff = str_replace($m[0][$i], $this->locale, $buff);
            }
        }
        file_put_contents($out_path, $buff);
    }

    private function getStripeChargeDashboardLink($isLive){
            if($isLive){
                $chargeDashboardLink='https://dashboard.stripe.com/payments/';
            } else {
                $chargeDashboardLink='https://dashboard.stripe.com/test/payments/';
        }
        return $chargeDashboardLink;
    }

    public static function getLocalizedString($id, $locale) {
        if (! isset(self::$i18n[$locale])) {
            $tmp_loader = new \Symfony\Component\Translation\Loader\YamlFileLoader();
            $catalogue = $tmp_loader->load(dirname(__FILE__) . "/Resource/locale/messages.$locale.yml", 'ja', 'stripe');
            self::$i18n[$locale] = $catalogue->all('stripe');
        }
        if (isset(self::$i18n[$locale][$id])) {
            return self::$i18n[$locale][$id];
        }
        return '--';
    }

    private function isEligiblePaymentMethod(Payment $Payment,$total){
        $min = $Payment->getRuleMin();
        $max = $Payment->getRuleMax();
        if (null !== $min && $total < $min) {
            return false;
        }
        if (null !== $max && $total > $max) {
            return false;
        }
        return true;
    }
    

}
