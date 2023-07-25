<?php

namespace Plugin\StripePaymentGateway\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Exception;
use Plugin\StripePaymentGateway\Repository\StripeConfigRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Webhook;

class WebhookController extends AbstractController {
    private $stripeConfigRepository;
    private $stripeConfig;
    private $orderStatusRepository;
    private $orderRepository;
    private $purchaseFlow;

    public function __construct(
        StripeConfigRepository $stripeConfigRepository,
        OrderStatusRepository $orderStatusRepository,
        OrderRepository $orderRepository,
        PurchaseFlow $purchaseFlow)
    {
        $this->stripeConfigRepository = $stripeConfigRepository;
        $this->stripeConfig = $this->stripeConfigRepository->get();
        $this->orderStatusRepository = $orderStatusRepository;
        $this->orderRepository = $orderRepository;
        $this->purchaseFlow = $purchaseFlow;
    }

    /**
     * @Route("/plugin/stripe_payment_gateway/webhook", name="plugin_stripe_payment_gateway_webhook")
     */
    public function webhook(Request $request) {
        $signature = $this->stripeConfig->getWebhookSignature();
        if (!$signature) {
            return $this->json(['status' => 'failed', 'message' => 'Webhook signature is not configured'], 500);
        }
        try {
            $event = Webhook::constructEvent(
                $request->getContent(), 
                $request->headers->get('stripe-signature'),
                $signature, 800
            );
            $type = $event['type'];
            $object = $event['data']['object'];
            log_info('webhook type: ' . $type . "\n");
            log_info($object);
        } catch(Exception $e) {
            log_error("\n====== [stripe webhook sign error] ====\n");
            return $this->json(['status' => 'failed', 401]);
        }
        if ($type === 'payment_intent.succeeded') {
            if (!empty($object['metadata']['order_id'])) {
                $orderId = $object['metadata']['order_id'];
                if ($orderId)
                    $this->processOrder($orderId, OrderStatus::PAID);
            }
        } else if ($type === 'payment_intent.payment_failed') {
            if (!empty($object['metadata']['order_id'])) {
                $orderId = $object['metadata']['order_id'];
                if ($orderId)
                    $this->processOrder($orderId, OrderStatus::CANCEL);
            }
        }
        return $this->json(['status' => 'success']);
    }

    private function processOrder($orderId, $status) {
        $Order = $this->orderRepository->find($orderId);
        if (!$Order) return;
        $OrderStatus = $this->orderStatusRepository->find($status);
        $Order->setOrderStatus($OrderStatus);
        if ($status === OrderStatus::PAID) {
            $Order->setPaymentDate(new \DateTime());
        }
        $this->entityManager->persist($Order);
        $this->entityManager->flush();
        $this->purchaseFlow->commit($Order, new PurchaseContext());
    }
}