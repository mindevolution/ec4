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

namespace Plugin\Recommend4\Repository;

use Eccube\Entity\Master\ProductStatus;
use Eccube\Repository\AbstractRepository;
use Plugin\Recommend4\Entity\RecommendProduct;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * RecommendProductRepository.
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RecommendProductRepository extends AbstractRepository
{
    /**
     * CouponRepository constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, RecommendProduct::class);
    }

    /**
     * Find list.
     *
     * @return mixed
     */
    public function getRecommendList()
    {
        $qb = $this->createQueryBuilder('rp')
            ->innerJoin('rp.Product', 'p');
        $qb->where('rp.visible = true');
        $qb->addOrderBy('rp.sort_no', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * Get max rank.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getMaxRank()
    {
        $qb = $this->createQueryBuilder('rp')
            ->select('MAX(rp.sort_no) AS max_rank');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Get recommend product by display status of product.
     *
     * @return array
     */
    public function getRecommendProduct()
    {
        $query = $this->createQueryBuilder('rp')
            ->innerJoin('Eccube\Entity\Product', 'p', 'WITH', 'p.id = rp.Product')
            ->where('p.Status = :Disp')
            ->andWhere('rp.visible = true')
            ->orderBy('rp.sort_no', 'DESC')
            ->setParameter('Disp', ProductStatus::DISPLAY_SHOW)
            ->getQuery();

        return $query->getResult();
    }

    /**
     * Number of recommend.
     *
     * @return mixed
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function countRecommend()
    {
        $qb = $this->createQueryBuilder('rp');
        $qb->select('COUNT(rp)');

        return $qb->getQuery()->getSingleScalarResult();
    }

    /**
     * Move rank.
     *
     * @param array $arrRank
     *
     * @return array
     *
     * @throws \Exception
     */
    public function moveRecommendRank(array $arrRank)
    {
        $this->getEntityManager()->beginTransaction();
        $arrRankMoved = [];
        try {
            foreach ($arrRank as $recommendId => $rank) {
                /* @var $Recommend RecommendProduct */
                $Recommend = $this->find($recommendId);
                if ($Recommend->getSortno() == $rank) {
                    continue;
                }
                $arrRankMoved[$recommendId] = $rank;
                $Recommend->setSortno($rank);
                $this->getEntityManager()->persist($Recommend);
            }
            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }

        return $arrRankMoved;
    }

    /**
     * Save recommend.
     *
     * @param RecommendProduct $RecommendProduct
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function saveRecommend(RecommendProduct $RecommendProduct)
    {
        $this->getEntityManager()->beginTransaction();
        try {
            $this->getEntityManager()->persist($RecommendProduct);
            $this->getEntityManager()->flush($RecommendProduct);
            $this->getEntityManager()->commit();
        } catch (\Exception $e) {
            $this->getEntityManager()->rollback();
            throw $e;
        }

        return true;
    }

    /**
     * Get all id of recommend product.
     *
     * @return array
     */
    public function getRecommendProductIdAll()
    {
        $query = $this->createQueryBuilder('rp')
            ->select('IDENTITY(rp.Product) as id')
            ->where('rp.visible = true')
            ->getQuery();
        $arrReturn = $query->getScalarResult();

        return array_map('current', $arrReturn);
    }

    /**
     * おすすめ商品情報を削除する
     *
     * @param RecommendProduct $RecommendProduct
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function deleteRecommend(RecommendProduct $RecommendProduct)
    {
        // おすすめ商品情報を書き換える
        $RecommendProduct->setVisible(false);

        // おすすめ商品情報を登録する
        return $this->saveRecommend($RecommendProduct);
    }
}
