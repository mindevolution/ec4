<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Repository;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Entity\Post;
use Plugin\TabaCMS\Entity\Category;

use Eccube\Repository\AbstractRepository;

use Doctrine\Common\Persistence\ManagerRegistry;

class CategoryRepository extends AbstractRepository
{

    /**
     *
     * @param ManagerRegistry $registry
     * @param string $entityClass
     */
    public function __construct(ManagerRegistry $registry, $entityClass = Category::class)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * 投稿タイプ、ヒモ付いている子テーブルのデータ件数を含めたのリストデータを取得します。
     *
     * @param integer $type_id 投稿タイプID
     * @return mixed Doctrine\ORM\Query::getResult()
     */
    public function getList($condition = null, $sort = null)
    {
        // カテゴリーリスト取得
        $qb_category = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(Constants::$ENTITY['CATEGORY'], 'c')
            ->select('c')
            ->addOrderBy('c.orderNo', 'ASC');
        // 抽出条件
        if (! empty($condition)) {
            // 投稿タイプ
            if (! empty($condition['type_id'])) {
                $qb_category->andWhere('c.typeId = :type_id')->setParameter('type_id', $condition['type_id']);
            }
            // データキー
            if (! empty($condition['data_key'])) {
                $qb_category->andWhere('c.dataKey = :data_key')->setParameter('data_key', $condition['data_key']);
            }
        }
        $query_category = $qb_category->getQuery();
        $res_category = $query_category->getResult();

        // 投稿数取得
        $qb_post_count = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(Constants::$ENTITY['POST'], 'p')
            ->select('p.categoryId,COUNT(p.categoryId) AS postCount')
            ->groupBy('p.categoryId');
        // 抽出条件
        if (! empty($condition)) {
            // 公開
            if (! empty($condition['is_public']) && $condition['is_public']) {
                $now_date = date('Y-m-d H:i:s');
                $qb_post_count->andWhere('p.publicDiv = :publicDiv')->setParameter('publicDiv', Post::PUBLIC_DIV_PUBLIC);
                $qb_post_count->andWhere('p.publicDate <= :publicDate')->setParameter('publicDate', $now_date);
            }
        }
        $query_post_count = $qb_post_count->getQuery();
        $res_post_count = $query_post_count->getArrayResult();
        $post_count_list = array();
        foreach ($res_post_count as $post_count) {
            $post_count_list[$post_count['categoryId']] = $post_count['postCount'];
        }

        // 各集計値をリストデータにセットします。
        foreach ($res_category as $row) {
            $row->setPostCount((isset($post_count_list[$row->getCategoryId()]) ? $post_count_list[$row->getCategoryId()] : 0));
        }

        return $res_category;
    }

    /**
     * 保存
     *
     * @param \Plugin\TabaCMS\Entity\Category $entity
     * @return boolean 成功した場合 true
     */
    public function save($entity)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $entity->setUpdateDate(new \DateTime()); // 更新日時
            $em->persist($entity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            log_error('カテゴリー登録エラー', array(
                $e->getMessage()
            ));
            $em->getConnection()->rollback();
            return false;
        }
        return true;
    }

    /**
     * 削除
     *
     * @param \Plugin\TabaCMS\Entity\Category $entity
     * @return boolean
     */
    public function delete($entity)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            // 投稿に設定してあるcategory_idをnullにセットします。
            $qb_update = $em->createQueryBuilder();
            $qb_update->update(Constants::$ENTITY['POST'], 'p');
            $qb_update->set('p.category', ':clear')->setParameter('clear', null);
            $qb_update->where('p.category = :category_id')->setParameter('category_id', $entity->getCategoryId());
            $query_update = $qb_update->getQuery();
            $query_update->execute();

            $em->remove($entity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            log_error('カテゴリー削除エラー', array(
                $e->getMessage()
            ));
            $em->getConnection()->rollback();
            return false;
        }
        return true;
    }
}
