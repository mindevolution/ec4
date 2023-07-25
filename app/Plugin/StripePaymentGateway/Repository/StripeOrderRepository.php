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

use Eccube\Repository\AbstractRepository;
use Plugin\StripePaymentGateway\Entity\StripeOrder;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StripeOrderRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StripeOrder::class);
    }

    public function getStripeOrderByOrders($Orders)
    {
        $StripeOrders = $this->findby(array('Order' => $Orders));
        return $StripeOrders;
    }
}