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


namespace Plugin\StripePaymentGateway\Controller\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Plugin\StripePaymentGateway\Repository\StripeConfigRepository;
use Plugin\StripePaymentGateway\Repository\StripeOrderRepository;
use Plugin\StripePaymentGateway\Entity\StripeCustomer;
use Plugin\StripePaymentGateway\Entity\StripeOrder;
use Plugin\StripePaymentGateway\Repository\StripeCustomerRepository;
use Plugin\StripePaymentGateway\Service\Method\StripeCreditCard;
use Plugin\StripePaymentGateway\Entity\StripeLog;
use Plugin\StripePaymentGateway\StripeClient;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\Routing\RouterInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class OrderController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /**
     * @var OrderStatusRepository
     */
    private $orderStatusRepository;

    /**
     * @var StripeConfigRepository
     */
    protected $stripeConfigRepository;

    /**
     * @var StripeOrderRepository
     */
    private $stripeOrderRepository;

    /**
     * @var StripeCustomerRepository
     */
    private $stripeCustomerRepository;

    /**
     * ConfigController constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param OrderRepository $orderRepository
     * @param OrderStatusRepository $orderStatusRepository,
     * @param StripeConfigRepository $stripeConfigRepository
     * @param StripeOrderRepository $stripeOrderRepository
     * @param StripeCustomerRepository $stripeCustomerRepository
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        OrderRepository $orderRepository,
        OrderStatusRepository $orderStatusRepository,
        StripeConfigRepository $stripeConfigRepository,
        StripeOrderRepository $stripeOrderRepository,
        StripeCustomerRepository $stripeCustomerRepository
    )
    {
        $this->eccubeConfig=$eccubeConfig;
        $this->orderRepository = $orderRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->stripeConfigRepository = $stripeConfigRepository;
        $this->stripeOrderRepository = $stripeOrderRepository;
        $this->stripeCustomerRepository = $stripeCustomerRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/stripe_payment_gateway/order_payment/{id}/capture_transaction", requirements={"id" = "\d+"}, name="stripe_payment_gateway_admin_order_capture")
     */
    public function charge(Request $request, $id = null, RouterInterface $router)
    {
        //$StripeConfig = $this->stripeConfigRepository->get();
//        $isAuthAndCaptureOn=$StripeConfig->getIsAuthAndCaptureOn();
//        if(!$isAuthAndCaptureOn) {
//            $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
//            return $this->redirectToRoute('admin_order');
//        }

        //BOC check if order exist
        /** @var Order $Order */
        $Order = $this->orderRepository->find($id);
        if (null === $Order) {
            $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }

		$StripeConfig = $this->stripeConfigRepository->getConfigByOrder($Order);
        //EOC check if order exist

        //BOC check if Stripe Order
        /** @var StripeOrder $stripeOrder **/
        $stripeOrder = $this->stripeOrderRepository->findOneBy(array('Order' => $Order));
        if (null === $stripeOrder) {
            $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
        }
        //EOC check if Stripe Order

        //BOC check if refunded
        if ($stripeOrder->getIsChargeRefunded()) {
            $this->addError('stripe_payment_gateway.admin.order.error.refunded', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
        }
        //EOC check if refunded

        //BOC check if already captured
        if ($stripeOrder->getIsChargeCaptured()) {
            $this->addError('stripe_payment_gateway.admin.order.error.already_captured', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
        }
        //EOC check if already captured

        //BOC retrieve and check if captured for order_id already
        $stripeClient = new StripeClient($StripeConfig->secret_key);
        if($stripeClient->isPaymentIntentId($stripeOrder->getStripePaymentIntentId())) { // new version for 3ds2
            $paymentIntent = $stripeClient->retrievePaymentIntent($stripeOrder->getStripePaymentIntentId());
            if( is_array($paymentIntent) && isset($paymentIntent['error']) ) {
                $this->addError(StripeClient::getErrorMessageFromCode($paymentIntent['error'], $this->eccubeConfig['locale']), 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }

            if($paymentIntent->metadata->order==$Order->getId() && $paymentIntent->status=='succeeded'){
                //BOC update charge id and capture status
                foreach($paymentIntent->charges as $charge) {
                    $stripeOrder->setStripeChargeId($charge->id);
                    break;
                }
                $stripeOrder->setIsChargeCaptured(true);
                $this->entityManager->persist($stripeOrder);
                $this->entityManager->flush($stripeOrder);
                //EOC update charge id and capture status

                //BOC update payment status
                $stripeChargeID = $stripeOrder->getStripeChargeId();
                if (!empty($stripeChargeID)) {
                    $Today = new \DateTime();
                    $Order->setPaymentDate($Today);
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);
                }
                //EOC update payment status

                $this->addError('stripe_payment_gateway.admin.order.error.already_captured', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC retrieve and check if captured for order_id already

            //BOC capture payment
            $this->writeRequestLog($Order, 'capturePaymentIntent');
            $paymentIntent = $stripeClient->capturePaymentIntent($paymentIntent, $Order->getPaymentTotal(), $Order->getCurrencyCode());
            $this->writeResponseLog($Order, 'capturePaymentIntent', $paymentIntent);
            //EOC capture payment

            //BOC check if error
            if (is_array($paymentIntent) && isset($paymentIntent['error'])) {
                $errorMessage = StripeClient::getErrorMessageFromCode($paymentIntent['error'], $this->eccubeConfig['locale']);

                $this->addError($errorMessage, 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            } //EOC check if error
            else {
                //BOC update charge id and capture status
                foreach($paymentIntent->charges as $charge) {
                    $stripeOrder->setStripeChargeId($charge->id);
                    break;
                }
                $stripeOrder->setIsChargeCaptured(true);
                $this->entityManager->persist($stripeOrder);
                $this->entityManager->flush($stripeOrder);
                //EOC update charge id and capture status

                //BOC update payment status
                $stripeChargeID = $stripeOrder->getStripeChargeId();
                if (!empty($stripeChargeID)) {
                    $Today = new \DateTime();
                    $Order->setPaymentDate($Today);
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);
                }
                //EOC update payment status

                $this->addSuccess('stripe_payment_gateway.admin.order.success.capture', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
        } else if ($stripeClient->isStripeToken($stripeOrder->getStripePaymentIntentId())) {
            //BOC check if Stripe Customer
            $Customer = $Order->getCustomer();
            $isEcCustomer = false;
            $isStripeCustomer = false;
            if ($Customer instanceof Customer) {
                $isEcCustomer = true;
                $StripeCustomer = $this->stripeCustomerRepository->findOneBy(array('Customer' => $Customer));
                if ($StripeCustomer instanceof StripeCustomer) {
                    $isStripeCustomer = true;
                }
            }
            //EOC check if Stripe Customer

            //BOC retrieve stripe customer id
            if ($isStripeCustomer) {
                $stripeCustomerId = $StripeCustomer->getStripeCustomerId();
            } else if (!$isEcCustomer && $stripeOrder->getStripeCustomerIdForGuestCheckout()) {
                $stripeCustomerId = $stripeOrder->getStripeCustomerIdForGuestCheckout();
            } else {
                $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC retrieve stripe customer id

            //BOC capture payment
            $this->writeRequestLog($Order, 'createChargeWithCustomer');
            $chargeResult = $stripeClient->createChargeWithCustomer($Order->getPaymentTotal(), $stripeCustomerId, $Order->getId(), true);
            $this->writeResponseLog($Order, 'createChargeWithCustomer', $chargeResult);
            //EOC capture payment

            //BOC check if error
            if (is_array($chargeResult) && isset($chargeResult['error'])) {
                $errorMessage = StripeClient::getErrorMessageFromCode($chargeResult['error'], $this->eccubeConfig['locale']);

                $this->addError($errorMessage, 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            } //EOC check if error
            else {

                //BOC update charge id and capture status
                $stripeOrder->setStripeChargeId($chargeResult->__get('id'));
                $stripeOrder->setIsChargeCaptured(true);
                $this->entityManager->persist($stripeOrder);
                $this->entityManager->flush($stripeOrder);
                //EOC update charge id and capture status

                //BOC update payment status
                $stripeChargeID = $stripeOrder->getStripeChargeId();
                if (!empty($stripeChargeID)) {
                    $Today = new \DateTime();
                    $Order->setPaymentDate($Today);
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::PAID);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);
                }
                //EOC update payment status

                $this->addSuccess('stripe_payment_gateway.admin.order.success.capture', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
        } else {
            $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }

    }

    /**
     * @Route("/%eccube_admin_route%/stripe_payment_gateway/order_payment/{id}/refund_transaction", requirements={"id" = "\d+"}, name="stripe_payment_gateway_admin_order_refund")
     */
    public function refund(Request $request, $id = null, RouterInterface $router)
    {

        //$StripeConfig = $this->stripeConfigRepository->get();

        //BOC check if order exist
        $Order = $this->orderRepository->find($id);
        if (null === $Order) {
            $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }
        //EOC check if order exist

		$StripeConfig = $this->stripeConfigRepository->getConfigByOrder($Order);

        if ($request->getMethod() == 'POST'){

            //BOC check if Stripe Order
            $stripeOrder = $this->stripeOrderRepository->findOneBy(array('Order' => $Order));
            if (null === $stripeOrder) {
                $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC check if Stripe Order

            //BOC check if refunded
            if ($stripeOrder->getIsChargeRefunded()) {
                $this->addError('stripe_payment_gateway.admin.order.error.refunded', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC check if refunded

            //BOC retrieve and check if valid charge id and not already refunded
            $stripeClient = new StripeClient($StripeConfig->secret_key);
            $chargeForOrder = $stripeClient->retrieveCharge($stripeOrder->getStripeChargeId());
            if (isset($chargeForOrder)) {
                if ($chargeForOrder->refunded) {

                    //BOC update charge id and capture status
                    $stripeOrder->setIsChargeRefunded(true);
                    $this->entityManager->persist($stripeOrder);
                    $this->entityManager->flush($stripeOrder);
                    //EOC update charge id and capture status

                    //BOC update Order Status
                    $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
                    $Order->setOrderStatus($OrderStatus);
                    $this->entityManager->persist($Order);
                    $this->entityManager->flush($Order);
                    //EOC update Order Status

                    $this->addError('stripe_payment_gateway.admin.order.error.refunded', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                }
            } else {
                $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC retrieve and check if valid charge id and not already refunded

            //BOC refund option and amount calculation
            $refund_option = $request->request->get('refund_option');
            $refund_amount = 0;
            //BOC partial refund
            if ($refund_option == 3) {
                $refund_amount = $request->request->get('refund_amount');
                if (empty($refund_amount) || !is_int($refund_amount+0)) {
                    $this->addError('stripe_payment_gateway.admin.order.refund_amount.error.invalid', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                } else if($refund_amount>$Order->getPaymentTotal()){
                    $this->addError('stripe_payment_gateway.admin.order.refund_amount.error.exceeded', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                }
            }
            //EOC partial refund
            //BOC calculate refund amount based on fees entered
            if($refund_option==2){
                if($StripeConfig->stripe_fees_percent == 0){
                    $this->addError('stripe_payment_gateway.admin.order.refund_option.error.invalid', 'admin');
                    return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
                }
                $refund_amount=floor($Order->getPaymentTotal()-($Order->getPaymentTotal()*($StripeConfig->stripe_fees_percent/100)));
            }
            //EOC calculate refund amount based on fees entered
            //BOC full refund option
            if($refund_option==1){
                $refund_amount=floor($Order->getPaymentTotal());
            }
            //EOC full refund option
            //BOC refund option and amount calculation

            //BOC refund payment
            $this->writeRequestLog($Order, 'createRefundForCharge');
            $chargeResult = $stripeClient->createRefund($stripeOrder->getStripeChargeId(),$refund_amount,$Order->getCurrencyCode());
            $this->writeResponseLog($Order, 'createRefundForCharge', $chargeResult);
            //EOC refund payment

            //BOC check if error
            if (is_array($chargeResult) && isset($chargeResult['error'])) {
                $errorMessage = StripeClient::getErrorMessageFromCode($chargeResult['error'], $this->eccubeConfig['locale']);

                $this->addError($errorMessage, 'admin');
                return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
            }
            //EOC check if error

            //BOC update charge id and capture status
            $stripeOrder->setIsChargeRefunded(true);
            $stripeOrder->setSelectedRefundOption($refund_option);
            $stripeOrder->setRefundedAmount($refund_amount);
            $this->entityManager->persist($stripeOrder);
            $this->entityManager->flush($stripeOrder);
            //EOC update charge id and capture status

            //BOC update Order Status
            $OrderStatus = $this->orderStatusRepository->find(OrderStatus::CANCEL);
            $Order->setOrderStatus($OrderStatus);
            $this->entityManager->persist($Order);
            $this->entityManager->flush($Order);
            //EOC update Order Status

            $this->addSuccess('stripe_payment_gateway.admin.order.success.capture', 'admin');
            return $this->redirectToRoute('admin_order_edit', ['id' => $Order->getId()]);
        } else {
            $this->addError('stripe_payment_gateway.admin.order.error.invalid_request', 'admin');
            return $this->redirectToRoute('admin_order');
        }

    }

    private function writeRequestLog(Order $order, $api) {
        $logMessage = '[Order' . $order->getId() . '][' . $api . '] リクエスト実行';
        log_info($logMessage);

        $stripeLog = new StripeLog();
        $stripeLog->setMessage($logMessage);
        $stripeLog->setCreatedAt(new \DateTime());
        $this->entityManager->persist($stripeLog);
    }

    private function writeResponseLog(Order $order, $api, $result) {
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
    }
}
