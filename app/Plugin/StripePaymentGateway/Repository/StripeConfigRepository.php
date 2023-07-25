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

namespace Plugin\StripePaymentGateway\Repository;

use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\AbstractRepository;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class StripeConfigRepository extends AbstractRepository
{

    /**
     * @var EventDispatcher
     */
    protected $eventDispatcher;

    public function __construct(RegistryInterface $registry, EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
        parent::__construct($registry, StripeConfig::class);
    }

    public function get($id = 1)
    {
        return $this->find($id);
    }

    public function getConfigByOrder($Order) {
        $StripeConfig = $this->get();

        $StripeConfig = (object)[
            'publishable_key' => $StripeConfig->getPublishableKey(),
            'secret_key' => $StripeConfig->getSecretKey(),
            'is_auth_and_capture_on' => $StripeConfig->getIsAuthAndCaptureOn(),
            'stripe_fees_percent' => $StripeConfig->getStripeFeesPercent(),
            'prod_detail_ga_enable'     => $StripeConfig->getProdDetailGaEnable(),
            'cart_ga_enable'     => $StripeConfig->getCartGaEnable(),
            'checkout_ga_enable'     => $StripeConfig->getCheckoutGaEnable(),
        ];

        $event = new EventArgs(
            [
                'Order' => $Order,
                'StripeConfig' => $StripeConfig
            ]
        );
        $this->eventDispatcher->dispatch("Stripe/Config/Load", $event);
        return $StripeConfig;
    }
}
