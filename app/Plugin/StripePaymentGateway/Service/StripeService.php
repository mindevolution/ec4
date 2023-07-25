<?php

namespace Plugin\StripePaymentGateway\Service;

include_once(dirname(__FILE__).'/../vendor/stripe/stripe-php/init.php');

use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\StripePaymentGateway\StripeClient;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use Eccube\Entity\Order;
use Eccube\Common\EccubeConfig;
use Plugin\StripePaymentGateway\Entity\StripeLog;
use Doctrine\ORM\EntityManagerInterface;


class StripeService{

    protected $container;
    protected $entityManager;
    protected $eccubeConfig;

    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        EccubeConfig $eccubeConfig
    ){
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
    }

    private function genIntentResponse($intent) {
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
            $errorMessage = StripeClient::getErrorMessageFromCode($intent['error'], $this->eccubeConfig['locale']);
        } else {
            $errorMessage = trans('stripe_payment_gateway.front.unexpected_error');
        }
        return ['error' => true, 'message' => $errorMessage];
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
}