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

namespace Plugin\StripePaymentGateway\Service\Method;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\RemisePayment4\Form\Type\Admin\ConfigType;
use Symfony\Component\Form\FormInterface;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Plugin\StripePaymentGateway\Repository\StripeConfigRepository;
use Plugin\StripePaymentGateway\Entity\StripeLog;
use Plugin\StripePaymentGateway\Repository\StripeLogRepository;
use Plugin\StripePaymentGateway\Entity\StripeOrder;
use Plugin\StripePaymentGateway\Repository\StripeOrderRepository;
use Plugin\StripePaymentGateway\Entity\StripeCustomer;
use Plugin\StripePaymentGateway\Repository\StripeCustomerRepository;
use Plugin\StripePaymentGateway\StripeClient;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Eccube\Entity\Payment;

/**
 * クレジットカード(トークン決済)の決済処理を行う.
 */
class StripeCreditCard implements PaymentMethodInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Order
     */
    protected $Order;

    /**
     * @var FormInterface
     */
    protected $form;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var StripeConfigRepository
     */
    private $stripeConfigRepository;

    /**
     * @var StripeLogRepository
     */
    private $stripeLogRepository;

    /**
     * @var StripeOrderRepository
     */
    private $stripeOrderRepository;

    /**
     * @var StripeCustomerRepository
     */
    private $stripeCustomerRepository;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var PurchaseFlow
     */
    private $purchaseFlow;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var UtilService
     */
    protected $util_service;

    /**
     * CreditCard constructor.
     *
     * EccubeConfig $eccubeConfig
     * @param EntityManagerInterface $entityManager
     * @param OrderStatusRepository $orderStatusRepository
     * @param StripeConfigRepository $stripeConfigRepository
     * @param StripeLogRepository $stripeLogRepository
     * @param StripeOrderRepository $stripeOrderRepository
     * @param StripeCustomerRepository $stripeCustomerRepository
     * @param ContainerInterface $containerInterface
     * @param PurchaseFlow $shoppingPurchaseFlow
     * @param SessionInterface $session
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        EntityManagerInterface $entityManager,
        OrderStatusRepository $orderStatusRepository,
        StripeConfigRepository $stripeConfigRepository,
        StripeLogRepository $stripeLogRepository,
        StripeOrderRepository $stripeOrderRepository,
        StripeCustomerRepository $stripeCustomerRepository,
        ContainerInterface $containerInterface,
        PurchaseFlow $shoppingPurchaseFlow,
        SessionInterface $session
    ) {
        $this->eccubeConfig=$eccubeConfig;
        $this->entityManager = $entityManager;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->stripeConfigRepository = $stripeConfigRepository;
        $this->stripeLogRepository = $stripeLogRepository;
        $this->stripeOrderRepository = $stripeOrderRepository;
        $this->stripeCustomerRepository = $stripeCustomerRepository;
        $this->container = $containerInterface;
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->session = $session;
    }

    /**
     * 注文確認画面遷移時に呼び出される.
     *
     * クレジットカードの有効性チェックを行う.
     *
     * @return PaymentResult
     *
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function verify()
    {
        $result = new PaymentResult();
        $payment_repo = $this->entityManager->getRepository(Payment::class);
        $stripe_payment = $payment_repo->findOneBy(['method_class' => StripeCreditCard::class]);
        $min = $stripe_payment->getRuleMin();
        $max = $stripe_payment->getRuleMax();

        $total = $this->Order->getPaymentTotal();

        if($min !== null && $total < $min){
            $result->setSuccess(false);
            $result->setErrors(['stripe.shopping.verify.error.payment_total.too_small']);
            return $result;
        }
        if($max !== null && $total > $max){
            $result->setSuccess(false);
            $result->setErrors(['stripe.shopping.verify.error.payment_total.too_much']);
        }
        $result->setSuccess(true);
        return $result;
    }

    /**
     * 注文時に呼び出される.
     *
     * 受注ステータス, 決済ステータスを更新する.
     * ここでは決済サーバとの通信は行わない.
     *
     * @return PaymentDispatcher|null
     */
    public function apply()
    {
        // 受注ステータスを決済処理中へ変更
        $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PENDING);
        $this->Order->setOrderStatus($OrderStatus);

        // purchaseFlow::prepareを呼び出し, 購入処理を進める.
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());
    }

    /**
     * 注文時に呼び出される.
     *
     * クレジットカードの決済処理を行う.
     *
     * @return PaymentResult
     */
    public function checkout()
    {
        $result = new PaymentResult();
        if(empty($_REQUEST['payment_intent_id'])){
            $result->setSuccess(false);
            $result->setErrors(['stripe.shopping.verify.error.unexpected_error']);
            return $result;
        }
        $payment_intent_id = $_REQUEST['payment_intent_id'];
        $stripe_config = $this->stripeConfigRepository->getConfigByOrder($this->Order);
        $stripe_client = new StripeClient($stripe_config->secret_key);

        
        $is_auth_capture = $stripe_config->is_auth_and_capture_on;
        
        //BOC capture payment if auth & pay is off
        if ($is_auth_capture) {//Capture if on
            $this->writeRequestLog($this->Order, 'capturePaymentIntent');
            $payment_intent = $stripe_client->capturePaymentIntent($payment_intent_id, $this->Order->getPaymentTotal(), $this->Order->getCurrencyCode());
            $this->writeResponseLog($this->Order, 'capturePaymentIntent', $payment_intent);
        } else {
            $payment_intent = $stripe_client->retrievePaymentIntent($payment_intent_id);
        }

        // 支払いを作成できなかったらエラー
        if (is_array($payment_intent) && isset($payment_intent['error'])) {
            $errorMessage = StripeClient::getErrorMessageFromCode($payment_intent['error'], $this->eccubeConfig['locale']);
            $this->purchaseFlow->rollback($this->Order, new PurchaseContext());
            $result->setSuccess(false);
            $result->setErrors([$errorMessage]);
            $response = new RedirectResponse($this->generateUrl('shopping', array('stripe_card_error' => $errorMessage)));
            $result->setResponse($response);
            return $result;
        }
        //EOC capture payment if auth & pay is off

        if ($is_auth_capture){
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
            $this->Order->setOrderStatus($OrderStatus);
            $this->entityManager->persist($this->Order);
            $this->entityManager->flush();
        }

        //BOC create stripe Order
        // 注文と関連付ける
        $stripe_order = new StripeOrder();
        $stripe_order->setOrder($this->Order);
        if(!empty($payment_intent_id)) {
            $stripe_order->setStripePaymentIntentId($payment_intent_id);
        }
        if ($is_auth_capture) {
            foreach($payment_intent->charges as $charge) {
                $stripe_order->setStripeChargeId($charge->id);
                break;
            }
        }
        $stripe_order->setIsChargeCaptured($is_auth_capture);
        $Customer = $this->Order->getCustomer();
        $isEcCustomer=false;
        if($Customer instanceof Customer ) {
            $isEcCustomer = true;
        }
        if (!$isEcCustomer) {
            $stripe_order->setStripeCustomerIdForGuestCheckout($payment_intent->customer);
        } else {
            $stripe_order->setStripeCustomerIdForGuestCheckout('');
        }
        $stripe_order->setCreatedAt(new \DateTime());
        $this->entityManager->persist($stripe_order);

        // purchaseFlow::commitを呼び出し, 購入処理を完了させる.
        $this->purchaseFlow->commit($this->Order, new PurchaseContext());

        $result->setSuccess(true);
        // EOC create stripe Order

        return $result;
    }


    /**
     * {@inheritdoc}
     */
    public function setFormType(FormInterface $form)
    {
        $this->form = $form;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }

    protected function writeRequestLog(Order $order, $api) {
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] リクエスト実行';
        log_info($logMessage);

        $stripeLog = new StripeLog();
        $stripeLog->setMessage($logMessage);
        $stripeLog->setCreatedAt(new \DateTime());
        $this->entityManager->persist($stripeLog);
        $this->entityManager->flush($stripeLog);
    }

    protected function writeResponseLog(Order $order, $api, $result) {
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] ';
        if (is_object($result)) {
            $logMessage .= '成功';
        } elseif (! is_array($result)) {
            $logMessage .= print_r($result, true);
        } elseif (isset($result['error'])) {
            $logMessage .= $result['error']['message'];
        } else {
            $logMessage .= '成功';
        }
        log_info($logMessage);
        $stripeLog = new StripeLog();
        $stripeLog->setMessage($logMessage);
        $stripeLog->setCreatedAt(new \DateTime());
        $this->entityManager->persist($stripeLog);
        $this->entityManager->flush($stripeLog);
    }

    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
}
