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
use Plugin\StripePaymentGateway\Entity\StripeCustomer;
use Symfony\Bridge\Doctrine\RegistryInterface;

class StripeCustomerRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, StripeCustomer::class);
    }

}