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

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Service\Payment\PaymentResult;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Plugin\StripePaymentGateway\Repository\StripeConfigRepository;
use Plugin\StripePaymentGateway\Repository\StripeOrderRepository;
use Plugin\StripePaymentGateway\StripeClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class StripeKonbini implements PaymentMethodInterface
{
    /** @var Order */
    private $Order;

    /** @var FormInterface */
    private $form;

    /** @var $purchaseFlow */
    private $purchaseFlow;

    private $stripeConfigRepository;

    private $stripeOrderRepository;
    
    private $container;

    private $eccubeConfig;

    private $entityManager;

    /**
     * Cash constructor.
     *
     * @param PurchaseFlow $shoppingPurchaseFlow
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ContainerInterface $containerInterface,
        EntityManagerInterface $entityManager,
        PurchaseFlow $shoppingPurchaseFlow,
        StripeConfigRepository $stripeConfigRepository,
        StripeOrderRepository $stripeOrderRepository
    ) {
        $this->purchaseFlow = $shoppingPurchaseFlow;
        $this->stripeConfigRepository = $stripeConfigRepository;
        $this->stripeOrderRepository = $stripeOrderRepository;
        $this->container = $containerInterface;
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $entityManager;
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
        $voucherUrl = $_REQUEST['voucher_url'];
        if(empty($_REQUEST['voucher_url'])){
            $result->setSuccess(false);
            $result->setErrors(['stripe.shopping.verify.error.unexpected_error']);
            return $result;
        }

        $stripeConfig = $this->stripeConfigRepository->getConfigByOrder($this->Order);
        $stripeClient = new StripeClient($stripeConfig->secret_key);

        
        $stripeOrder = $this->stripeOrderRepository->findOneBy(['Order' => $this->Order]);
        if (!$stripeOrder) {
            $errorUrl = $this->generateUrl('shopping', array('stripe_card_error' => 'Something went wrong'));
            $response = new RedirectResponse($errorUrl);
            $result->setResponse($response);
            return $result;
        }
        $paymentIntent = $stripeClient->retrievePaymentIntent($stripeOrder->getStripePaymentIntentId());
        if (is_array($paymentIntent) && isset($paymentIntent['error'])) {
            $errorMessage = StripeClient::getErrorMessageFromCode($paymentIntent['error'], $this->eccubeConfig['locale']);
            $this->purchaseFlow->rollback($this->Order, new PurchaseContext());
            $result->setSuccess(false);
            $result->setErrors([$errorMessage]);
            $response = new RedirectResponse($this->generateUrl('shopping', array('stripe_card_error' => $errorMessage)));
            $result->setResponse($response);
            return $result;
        }
        
        $stripeOrder->setKonbiniVoucherUrl($voucherUrl);
        $this->entityManager->persist($stripeOrder);
        $this->entityManager->flush();


        $this->purchaseFlow->commit($this->Order, new PurchaseContext());
        $result = new PaymentResult();
        $result->setSuccess(true);

        return $result;
    }

    
    /**
     * {@inheritdoc}
     *
     * @throws \Eccube\Service\PurchaseFlow\PurchaseException
     */
    public function apply()
    {
        $this->purchaseFlow->prepare($this->Order, new PurchaseContext());

        return false;
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
    public function verify()
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function setOrder(Order $Order)
    {
        $this->Order = $Order;
    }
    
    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->container->get('router')->generate($route, $parameters, $referenceType);
    }
}