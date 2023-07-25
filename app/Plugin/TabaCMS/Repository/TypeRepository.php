<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Repository;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Entity\Type;

use Eccube\Repository\AbstractRepository;

use Doctrine\Common\Persistence\ManagerRegistry;

class TypeRepository extends AbstractRepository
{

    /**
     *
     * @param ManagerRegistry $registry
     * @param string $entityClass
     */
    public function __construct(ManagerRegistry $registry, $entityClass = Type::class)
    {
        parent::__construct($registry, $entityClass);
    }

    /**
     * 投稿タイプ、ヒモ付いている子テーブルのデータ件数を含めたのリストデータを取得します。
     *
     * @return mixed Doctrine\ORM\Query::getResult()
     */
    public function getList($condition = null, $sort = null)
    {
        // 投稿タイプリスト取得
        $qb_type = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(Constants::$ENTITY['TYPE'], 't')
            ->select('t')
            ->addOrderBy('t.typeId', 'ASC');
        // 抽出条件
        if (! empty($condition)) {
            // 投稿タイプ
            if (! empty($condition['type_id'])) {
                $qb_type->andWhere('t.typeId = :type_id')->setParameter('type_id', $condition['type_id']);
            }
            // データキー
            if (! empty($condition['data_key'])) {
                $qb_type->andWhere('t.dataKey = :data_key')->setParameter('data_key', $condition['data_key']);
            }
            // 公開
            if (! empty($condition['public_div'])) {
                $qb_type->andWhere('b.publicDiv = :publicDiv')->setParameter('publicDiv', $condition['public_div']);
            }
        }
        $query_type = $qb_type->getQuery();
        $res_type = $query_type->getResult();

        // カテゴリー登録数取得
        $query_category_count = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(Constants::$ENTITY['CATEGORY'], 'c')
            ->select('c.typeId,COUNT(c.typeId) AS categoryCount')
            ->groupBy('c.typeId')
            ->getQuery();
        $res_category_count = $query_category_count->getArrayResult();
        $category_count_list = array();
        foreach ($res_category_count as $category_count) {
            $category_count_list[$category_count['typeId']] = $category_count['categoryCount'];
        }

        // 投稿数取得
        $query_post_count = $this->getEntityManager()
            ->createQueryBuilder()
            ->from(Constants::$ENTITY['POST'], 'p')
            ->select('p.typeId,COUNT(p.typeId) AS postCount')
            ->groupBy('p.typeId')
            ->getQuery();
        $res_post_count = $query_post_count->getArrayResult();
        $post_count_list = array();
        foreach ($res_post_count as $post_count) {
            $post_count_list[$post_count['typeId']] = $post_count['postCount'];
        }

        // 各集計値をリストデータにセットします。
        foreach ($res_type as $row) {
            $row->setCategoryCount((isset($category_count_list[$row->getTypeId()]) ? $category_count_list[$row->getTypeId()] : 0));
            $row->setPostCount((isset($post_count_list[$row->getTypeId()]) ? $post_count_list[$row->getTypeId()] : 0));
        }

        return $res_type;
    }

    /**
     * Typeデータを取得します。
     *
     * @param string|array $data_key データキー|検索条件配列
     * @return NULL|\Plugin\TabaCMS\Entity\Type
     */
    public function get($data_key)
    {
        $list = array();
        if (($list = $this->getList(array(
            "data_key" => $data_key
        ))) && count($list) >= 1) {
            return $list[0];
        }
        return null;
    }

    /**
     * 保存
     *
     * @param \Plugin\TabaCMS\Entity\Type $entity
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
            log_error('投稿タイプ登録エラー [ERROR] ' . $e->getMessage());
            $em->getConnection()->rollback();
            return false;
        }
        return true;
    }

    /**
     * 削除
     *
     * @param \Plugin\TabaCMS\Entity\Type $entity
     * @return boolean
     */
    public function delete($entity)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->remove($entity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            log_error('投稿タイプ削除エラー', array(
                $e->getMessage()
            ));
            $em->getConnection()->rollback();
            return false;
        }
        return true;
    }
}
