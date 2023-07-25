<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Repository;

use Eccube\Entity\CustomerAddress;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * CustomerAddressRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class CustomerAddressRepository extends AbstractRepository
{
    /**
     * CustomerAddressRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, CustomerAddress::class);
    }

    /**
     * お届け先を削除します.
     *
     * @param \Eccube\Entity\CustomerAddress $CustomerAddress
     */
    public function delete($CustomerAddress)
    {
        $em = $this->getEntityManager();
        $em->remove($CustomerAddress);
        $em->flush($CustomerAddress);
    }
}
