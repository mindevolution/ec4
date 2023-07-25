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

namespace Eccube\Service;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\OrderItem;
use Eccube\Entity\PointHistory;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\PurchaseFlow\Processor\PointProcessor;

class PointHelper
{
    /**
     * @var BaseInfoRepository
     */
    protected $baseInfoRepository;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * PointHelper constructor.
     *
     * @param BaseInfoRepository $baseInfoRepository
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(BaseInfoRepository $baseInfoRepository, EntityManagerInterface $entityManager)
    {
        $this->baseInfoRepository = $baseInfoRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * ポイント設定が有効かどうか.
     *
     * @return bool
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function isPointEnabled()
    {
        $BaseInfo = $this->baseInfoRepository->get();

        return $BaseInfo->isOptionPoint();
    }

    /**
     * ポイントを金額に変換する.
     *
     * @param $point ポイント
     *
     * @return float|int 金額
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function pointToPrice($point)
    {
        $BaseInfo = $this->baseInfoRepository->get();

        return intval($point * $BaseInfo->getPointConversionRate());
    }

    /**
     * ポイントを値引き額に変換する. マイナス値を返す.
     *
     * @param $point ポイント
     *
     * @return float|int 金額
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function pointToDiscount($point)
    {
        return $this->pointToPrice($point) * -1;
    }

    /**
     * 金額をポイントに変換する.
     *
     * @param $price
     *
     * @return float ポイント
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function priceToPoint($price)
    {
        $BaseInfo = $this->baseInfoRepository->get();

        return floor($price / $BaseInfo->getPointConversionRate());
    }

    /**
     * 明細追加処理.
     *
     * @param ItemHolderInterface $itemHolder
     * @param integer $discount
     */
    public function addPointDiscountItem(ItemHolderInterface $itemHolder, $discount)
    {
        $DiscountType = $this->entityManager->find(OrderItemType::class, OrderItemType::POINT);
        $TaxInclude = $this->entityManager->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        $Taxation = $this->entityManager->find(TaxType::class, TaxType::NON_TAXABLE);

        $OrderItem = new OrderItem();
        $OrderItem->setProductName($DiscountType->getName())
            ->setPrice($discount)
            ->setQuantity(1)
            ->setTax(0)
            ->setTaxRate(0)
            ->setRoundingType(null)
            ->setOrderItemType($DiscountType)
            ->setTaxDisplayType($TaxInclude)
            ->setTaxType($Taxation)
            ->setOrder($itemHolder)
            ->setProcessorName(PointProcessor::class);
        $itemHolder->addItem($OrderItem);
    }

    /**
     * 既存のポイント明細を削除する.
     *
     * @param ItemHolderInterface $itemHolder
     */
    public function removePointDiscountItem(ItemHolderInterface $itemHolder)
    {
        foreach ($itemHolder->getItems() as $item) {
            if ($item->getProcessorName() == PointProcessor::class) {
                $itemHolder->removeOrderItem($item);
                $this->entityManager->remove($item);
            }
        }
    }

    public function prepare(ItemHolderInterface $itemHolder, $point)
    {
        // ユーザの保有ポイントを減算
        $Customer = $itemHolder->getCustomer();

        // ユーザの保有ポイントを減算する履歴を追加
        $PointHistory = new PointHistory();
        $PointHistory->setRecordType(PointHistory::TYPE_USE);
        $PointHistory->setRecordEvent(PointHistory::EVENT_SHOPPING);
        $PointHistory->setPoint(-$point);
        $PointHistory->setCustomer($Customer);
        $PointHistory->setOrder($itemHolder);
        $this->entityManager->persist($PointHistory);
        $this->entityManager->flush();

        $Customer->setPoint($Customer->getPoint() - $point);
    }

    public function rollback(ItemHolderInterface $itemHolder, $point)
    {
        $Order = $itemHolder;
        $event = ($itemHolder->getOrderNo() == null) ? PointHistory::EVENT_SHOPPING : PointHistory::EVENT_ORDER_CANCEL;
        $Customer = $itemHolder->getCustomer();

        // ユーザの保有ポイントを復元する履歴を追加
        $PointHistory = new PointHistory();
        $PointHistory->setRecordType(PointHistory::TYPE_ADD);
        $PointHistory->setRecordEvent($event);
        $PointHistory->setPoint($point);
        $PointHistory->setCustomer($Customer);
        $PointHistory->setOrder($Order);
        $this->entityManager->persist($PointHistory);
        $this->entityManager->flush();

        // 利用したポイントをユーザに戻す.
        $Customer->setPoint($Customer->getPoint() + $point);
    }

}