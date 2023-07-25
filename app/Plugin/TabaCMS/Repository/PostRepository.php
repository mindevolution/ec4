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
use Eccube\Repository\AbstractRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

class PostRepository extends AbstractRepository
{

    private $diff_time = "";

    /**
     *
     * @param ManagerRegistry $registry
     * @param string $entityClass
     */
    public function __construct(ManagerRegistry $registry, $entityClass = Post::class)
    {
        parent::__construct($registry, $entityClass);

        // datetime型はDB登録時に強制的にUTCに変更される。
        // そのため、SQL発行時に、時間を指定する場合は
        // ズレれている分を増減する必要がある。
        if (($diff = date("P")) != "+00:00") {
            $sign = (strstr($diff,"+") ? "-" : "+");
            $diff = str_replace(array("+","-"),"",$diff);
            $hour = (int) strstr($diff,":",true);
            $minute = (int) substr(strrchr($diff,":"),1);
            $this->diff_time = " " . $sign . $hour . " hour";
            if ($minute) $this->diff_time .= " " . $sign . $minute . " minute";
        }
    }

    /**
     * 投稿リストのQueryBuilderを生成します。
     *
     * @param integer $type_id
     *            投稿タイプID
     * @return mixed Doctrine\ORM\Query::getResult()
     */
    public function createListQueryBuilder($condition = null, $sort = null)
    {
        $qb_post = $this->getEntityManager()->createQueryBuilder();
        $qb_post->from(Constants::$ENTITY['POST'], 'p');
        $qb_post->select('p');
        // 抽出条件
        if (! empty($condition)) {
            // 投稿タイプ
            if (! empty($condition['type_id'])) {
                $qb_post->andWhere('p.typeId = :type_id')->setParameter('type_id', $condition['type_id']);
            }
            // データキー
            if (! empty($condition['data_key'])) {
                $qb_post->andWhere('p.dataKey = :data_key')->setParameter('data_key', $condition['data_key']);
            }
            // 公開
            if (!empty($condition['is_public']) && $condition['is_public']) {
                if ($this->diff_time) {
                    $now_date = date('Y-m-d H:i:s',strtotime($this->diff_time));
                } else {
                    $now_date = date('Y-m-d H:i:s');
                }
                $qb_post->andWhere('p.publicDiv = :publicDiv')->setParameter('publicDiv', Post::PUBLIC_DIV_PUBLIC);
                $qb_post->andWhere('p.publicDate <= :publicDate')->setParameter('publicDate',$now_date);
            }
            // カテゴリー
            if (! empty($condition['category_id'])) {
                $qb_post->andWhere('p.categoryId = :category_id')->setParameter('category_id', $condition['category_id']);
            }
            // ルーティング名が設定されているデータを抽出
            if (! empty($condition['is_overwrite_route'])) {
                $qb_post->andWhere('p.overwriteRoute IS NOT NULL');
            }
        }
        // ソート
        if (! empty($sort) && ! empty($sort['key'])) {
            if (! empty($sort['order'])) {
                $qb_post->addOrderBy('p.' . $sort['key'], $sort['order']);
            } else {
                $qb_post->addOrderBy('p.' . $sort['key'], 'DESC');
            }
        } else {
            $qb_post->addOrderBy('p.publicDate', 'DESC');
        }
        return $qb_post;
    }

    /**
     * 投稿リストデータを取得します。
     *
     * @param integer $type_id 投稿タイプID
     * @return mixed Doctrine\ORM\Query::getResult()
     */
    public function getList($condition = null, $sort = null)
    {
        $qb_post = $this->createListQueryBuilder($condition, $sort);
        $query_post = $qb_post->getQuery();
        $res_post = $query_post->getResult();
        return $res_post;
    }

    /**
     * @param string|array $data_key データキー|検索条件配列
     */
    public function get($data_key)
    {
        $list = null;
        if (is_array($data_key)) {
            if (($list = $this->getList($data_key)) && count($list) >= 1) {
                return $list[0];
            }
        } else {
            if (($list = $this->getList(array(
                "data_key" => $data_key
            ))) && count($list) >= 1) {
                return $list[0];
            }
        }
        return null;
    }

    /**
     * 保存
     *
     * @param \Plugin\TabaCMS\Entity\Post $entity
     * @return boolean 成功した場合 true
     */
    public function save($entity)
    {
        $em = $this->getEntityManager();
        $em->getConnection()->beginTransaction();
        try {
            $em->persist($entity);
            $em->flush();
            $em->getConnection()->commit();
        } catch (\Exception $e) {
            log_error('投稿登録エラー', array(
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
     * @param \Plugin\TabaCMS\Entity\Post $entity
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
            log_error('投稿削除エラー', array(
                $e->getMessage()
            ));
            $em->getConnection()->rollback();
            return false;
        }
        return true;
    }
}
