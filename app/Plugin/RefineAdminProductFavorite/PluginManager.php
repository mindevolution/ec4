<?php
/*
 * This file is part of Refine
 *
 * Copyright(c) 2022 Refine Co.,Ltd. All Rights Reserved.
 *
 * https://www.re-fine.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\RefineAdminProductFavorite;

use Doctrine\ORM\EntityManager;
use Eccube\Entity\Csv;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Product;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\CsvRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    /**
     * Enable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        // CSVダウンロード項目への追加を行います
        // すでに登録済みの場合はスキップ
        /** @var  $entityManager EntityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        /** @var  $CsvRepository CsvRepository */
        $entityName = Product::class;
        $fieldName = 'CustomerFavoriteProductCount';

        $result = $this->getCustomerFavoriteProductCountCsvField($container);
        if ($result) {
            return;
        }

        // 登録処理
        $Csv = new Csv();

        $CsvTypeProduct = $entityManager->getRepository(CsvType::class)
            ->find(CsvType::CSV_TYPE_PRODUCT);
        $sortNoResult = $entityManager->getRepository(Csv::class)
            ->createQueryBuilder('c')
            ->select('MAX(c.sort_no) as sort_no_max')
            ->getQuery()
            ->getResult();
        $sortNo = 1;
        if (\count($sortNoResult) > 0) {
            $row = array_shift($sortNoResult);
            $sortNo = (int)$row['sort_no_max'] + 1;
        }
        $Csv->setCsvType($CsvTypeProduct);
        $Csv->setEntityName($entityName);
        $Csv->setFieldName($fieldName);
        $Csv->setReferenceFieldName(null);
        $Csv->setDispName('お気に入り数');
        $Csv->setEnabled(false);
        $Csv->setSortNo($sortNo);
        $entityManager->persist($Csv);

    }

    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        // プラグインで追加したCSV項目を削除します
        $Csv = $this->getCustomerFavoriteProductCountCsvField($container);
        $entityManager = $container->get('doctrine.orm.entity_manager');
        if ($Csv) {
            $entityManager->remove($Csv);
        }
    }

    /**
     * @param ContainerInterface $container
     * @return Csv|null
     */
    protected function getCustomerFavoriteProductCountCsvField(ContainerInterface $container)
    {
        /** @var  $entityManager EntityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $entityName = Product::class;
        $fieldName = 'CustomerFavoriteProductCount';
        $criteria = [
            'entity_name' => $entityName,
            'field_name' => $fieldName,
        ];
        $result = $entityManager->getRepository(Csv::class)->findBy($criteria);
        if (\count($result) === 0) {
            return null;
        }
        return array_shift($result);
    }

}
