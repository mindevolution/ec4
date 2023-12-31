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

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Block;
use Eccube\Entity\Master\DeviceType;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * BlocRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class BlockRepository extends AbstractRepository
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * BlockRepository constructor.
     *
     * @param RegistryInterface $registry
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        RegistryInterface $registry,
        EccubeConfig $eccubeConfig
    ) {
        parent::__construct($registry, Block::class);
        $this->eccubeConfig = $eccubeConfig;
    }

    public function newBlock($DeviceType)
    {
        $Block = new \Eccube\Entity\Block();
        $Block
            ->setDeviceType($DeviceType)
            ->setUseController(false)
            ->setDeletable(true);

        return $Block;
    }

    /**
     * ブロック一覧の取得.
     *
     * @param  \Eccube\Entity\Master\DeviceType $DeviceType
     *
     * @return null|\Symfony\Component\HttpFoundation\Request
     */
    public function getList($DeviceType)
    {
        $qb = $this->createQueryBuilder('b')
            ->orderBy('b.id', 'DESC')
            ->where('b.DeviceType = :DeviceType')
            ->setParameter('DeviceType', $DeviceType);

        $Blocks = $qb
            ->getQuery()
            ->getResult();

        return $Blocks;
    }

    /**
     * 未設定のブロックを取得
     *
     * @param  Ecube\Entity\Block[]  $Blocks
     *
     * @return null|Ecube\Entity\Block[]
     */
    public function getUnusedBlocks($Blocks)
    {
        $UnusedBlocks = $this->createQueryBuilder('b')
            ->select('b')
            ->where('b not in (:blocks)')
            ->setParameter('blocks', $Blocks)
            ->getQuery()
            ->getResult();

        return $UnusedBlocks;
    }
}
