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

namespace Eccube\Service\PurchaseFlow\Processor;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\DeliveryFee;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Master\TaxType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Entity\CustomerLevel;
use Eccube\Entity\CustomerShop;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\DeliveryFeeRepository;
use Eccube\Repository\TaxRuleRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerLevelRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * 送料明細追加.
 */
class DeliveryFeePreprocessor implements ItemHolderPreprocessor
{
    /** @var BaseInfo */
    protected $BaseInfo;

    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     * @var TaxRuleRepository
     */
    protected $taxRuleRepository;

    /**
     * @var DeliveryFeeRepository
     */
    protected $deliveryFeeRepository;

    /**
     * @var CustomerLevelRepository
     */
    protected $customerLevelRepository;

    /**
     * @var CustomerShopRepository
     */
    protected $customerShopRepository;

    /**
     * DeliveryFeePreprocessor constructor.
     *
     * @param BaseInfoRepository $baseInfoRepository
     * @param EntityManagerInterface $entityManager
     * @param TaxRuleRepository $taxRuleRepository
     * @param DeliveryFeeRepository $deliveryFeeRepository
     */
    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        EntityManagerInterface $entityManager,
        TaxRuleRepository $taxRuleRepository,
        DeliveryFeeRepository $deliveryFeeRepository,
        CustomerLevelRepository $customerLevelRepository,
        CustomerShopRepository $customerShopRepository
    ) {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->entityManager = $entityManager;
        $this->taxRuleRepository = $taxRuleRepository;
        $this->deliveryFeeRepository = $deliveryFeeRepository;
        $this->customerLevelRepository = $customerLevelRepository;
        $this->customerShopRepository = $customerShopRepository;
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     *
     * @throws \Doctrine\ORM\NoResultException
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        $this->removeDeliveryFeeItem($itemHolder);
        $this->saveDeliveryFeeItem($itemHolder);
    }

    private function removeDeliveryFeeItem(ItemHolderInterface $itemHolder)
    {
        foreach ($itemHolder->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $item) {
                if ($item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    $Shipping->removeOrderItem($item);
                    $itemHolder->removeOrderItem($item);
                    $this->entityManager->remove($item);
                }
            }
        }
    }

    /**
     * @param ItemHolderInterface $itemHolder
     *
     * @throws \Doctrine\ORM\NoResultException
     */
    private function saveDeliveryFeeItem(ItemHolderInterface $itemHolder)
    {
        $DeliveryFeeType = $this->entityManager
            ->find(OrderItemType::class, OrderItemType::DELIVERY_FEE);
        $TaxInclude = $this->entityManager
            ->find(TaxDisplayType::class, TaxDisplayType::INCLUDED);
        $Taxation = $this->entityManager
            ->find(TaxType::class, TaxType::TAXATION);

        /** @var Order $Order */
        $Order = $itemHolder;
        /* @var Shipping $Shipping */
        foreach ($Order->getShippings() as $Shipping) {
            // 送料の計算
            $deliveryFeeProduct = 0;
            if ($this->BaseInfo->isOptionProductDeliveryFee()) {
                /** @var OrderItem $item */
                foreach ($Shipping->getOrderItems() as $item) {
                    if (!$item->isProduct()) {
                        continue;
                    }
                    $deliveryFeeProduct += $item->getProductClass()->getDeliveryFee() * $item->getQuantity();
                }
            }

            /** @var DeliveryFee $DeliveryFee */
            $DeliveryFee = $this->deliveryFeeRepository->findOneBy([
                'Delivery' => $Shipping->getDelivery(),
                'Pref' => $Shipping->getPref(),
            ]);
            

            $OrderItem = new OrderItem();

            //edit by gzy 计算运费
            $CustomerLevel = $this->customerLevelRepository->getCustomerLevelByCustomer($itemHolder->getCustomer());
            $level = $CustomerLevel->getCustomerLevelDetail()->getLevel();
            $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($itemHolder->getCustomer());
            $isShop = "N";
            if($CustomerShop && $CustomerShop->getStatus() == "Y"){
                $isShop = "Y";
            }
            if($level == "GOLD11" || $level == "PLATINUM11" || $level == "DIANOND11"){//暂时取消高等级用户的免运费
                $OrderItem->setProductName($DeliveryFeeType->getName())
                    ->setPrice(0)
                    ->setQuantity(0)
                    ->setOrderItemType($DeliveryFeeType)
                    ->setShipping($Shipping)
                    ->setOrder($itemHolder)
                    ->setTaxDisplayType($TaxInclude)
                    ->setTaxType($Taxation)
                    ->setProcessorName(DeliveryFeePreprocessor::class);
            }
            else{
                $OrderItem->setProductName($DeliveryFeeType->getName())
                    ->setPrice($DeliveryFee->getFee() + $deliveryFeeProduct)
                    ->setQuantity(1)
                    ->setOrderItemType($DeliveryFeeType)
                    ->setShipping($Shipping)
                    ->setOrder($itemHolder)
                    ->setTaxDisplayType($TaxInclude)
                    ->setTaxType($Taxation)
                    ->setProcessorName(DeliveryFeePreprocessor::class);
            }
            

            $itemHolder->addItem($OrderItem);
            $Shipping->addOrderItem($OrderItem);




        }
        // 普通会员判断价格>设定值，邮费设置为零
        $CustomerLevel = $this->customerLevelRepository->getCustomerLevelByCustomer($itemHolder->getCustomer());
        $level = $CustomerLevel->getCustomerLevelDetail()->getLevel();
        $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($itemHolder->getCustomer());
        $isShop = "N";
        if($CustomerShop && $CustomerShop->getStatus() == "Y"){
            $isShop = "Y";
        }
        else if($Order->getSubtotal()>= $this->BaseInfo->getDeliveryFreeAmount()){
            foreach ($Order->getShippings() as $Shipping) {
                $OrderItem->setPrice(0);
            }
        }
    }
}
