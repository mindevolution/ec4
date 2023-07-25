<?php

namespace Plugin\RakutenCard4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Symfony\Bridge\Doctrine\RegistryInterface;

class Rc4OrderPaymentRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Rc4OrderPayment::class);
    }
}
