<?php

namespace Eccube\Repository;

use Eccube\Entity\Customer;
use Eccube\Entity\PointHistory;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * PointHistoryRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PointHistoryRepository extends AbstractRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, PointHistory::class);
    }

    public function getQueryBuilder(Customer $Customer)
    {
        $qb = $this->createQueryBuilder('ph')
            ->select('ph')
            ->where('ph.Customer = :Customer')
            ->setParameter('Customer', $Customer)
            ->addOrderBy('ph.create_date', 'DESC');

        return $qb;
    }
}