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

use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Entity\OrderItem;
use Eccube\Repository\Master\OrderItemTypeRepository;
use Eccube\Repository\Master\TaxDisplayTypeRepository;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\TaxDisplayType;
use Eccube\Entity\Order;
use Eccube\Entity\Payment;
use Eccube\Repository\Master\TaxTypeRepository;
use Eccube\Entity\Master\TaxType;

class PaymentChargePreprocessor implements ItemHolderPreprocessor
{
    /**
     * @var OrderItemTypeRepository
     */
    protected $orderItemTypeRepository;

    /**
     * @var TaxDisplayTypeRepository
     */
    protected $taxDisplayTypeRepository;

    /**
     * @var TaxTypeRepository
     */
    protected $taxTypeRepository;

    /**
     * PaymentChargePreprocessor constructor.
     *
     * @param OrderItemTypeRepository $orderItemTypeRepository
     * @param TaxDisplayTypeRepository $taxDisplayTypeRepository
     * @param TaxTypeRepository $taxTypeRepository
     */
    public function __construct(
        OrderItemTypeRepository $orderItemTypeRepository,
        TaxDisplayTypeRepository $taxDisplayTypeRepository,
        TaxTypeRepository $taxTypeRepository
    ) {
        $this->orderItemTypeRepository = $orderItemTypeRepository;
        $this->taxDisplayTypeRepository = $taxDisplayTypeRepository;
        $this->taxTypeRepository = $taxTypeRepository;
    }

    /**
     * {@inheritdoc}
     *
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!$itemHolder instanceof Order) {
            return;
        }
        if (!$itemHolder->getPayment() instanceof Payment || !$itemHolder->getPayment()->getId()) {
            return;
        }

        // foreach ($itemHolder->getItems() as $item) {
        //     if ($item->getProcessorName() == PaymentChargePreprocessor::class) {
        //         $item->setPrice($itemHolder->getPayment()->getCharge());

        //         return;
        //     }
        // }
        foreach ($itemHolder->getItems() as $item) {
            if ($item->getProcessorName() == PaymentChargePreprocessor::class) {
                // eidt by gzy 如果charge<1,就为比率，否则就为金额
                $price = 0;
                if($itemHolder->getPayment()->getCharge() < 1){
                    $price = ($itemHolder->getTotal()-$itemHolder->getCharge())*($itemHolder->getPayment()->getCharge());
                }
                else{
                    $price = $itemHolder->getPayment()->getCharge();
                }
                
                $item->setPrice(intval($price));

                return;
            }
        }

        $this->addChargeItem($itemHolder);
    }

    /**
     * Add charge item to item holder
     *
     * @param ItemHolderInterface $itemHolder
     */
    protected function addChargeItem(ItemHolderInterface $itemHolder)
    {
        // eidt by gzy 如果charge<1,就为比率，否则就为金额
        $price = 0;
        if($itemHolder->getPayment()->getCharge() < 1){
            $price = $itemHolder->getTotal()*($itemHolder->getPayment()->getCharge());
        }
        else{
            $price = $itemHolder->getPayment()->getCharge();
        }
        /** @var Order $itemHolder */
        $OrderItemType = $this->orderItemTypeRepository->find(OrderItemType::CHARGE);
        $TaxDisplayType = $this->taxDisplayTypeRepository->find(TaxDisplayType::INCLUDED);
        $Taxation = $this->taxTypeRepository->find(TaxType::TAXATION);
        $item = new OrderItem();
        $item->setProductName($OrderItemType->getName())
            ->setQuantity(1)
            ->setPrice(intval($price))
            ->setOrderItemType($OrderItemType)
            ->setOrder($itemHolder)
            ->setTaxDisplayType($TaxDisplayType)
            ->setTaxType($Taxation)
            ->setProcessorName(PaymentChargePreprocessor::class);
        $itemHolder->addItem($item);


        // /** @var Order $itemHolder */
        // $OrderItemType = $this->orderItemTypeRepository->find(OrderItemType::CHARGE);
        // $TaxDisplayType = $this->taxDisplayTypeRepository->find(TaxDisplayType::INCLUDED);
        // $Taxation = $this->taxTypeRepository->find(TaxType::TAXATION);
        // $item = new OrderItem();
        // $item->setProductName($OrderItemType->getName())
        //     ->setQuantity(1)
        //     ->setPrice($itemHolder->getPayment()->getCharge())
        //     ->setOrderItemType($OrderItemType)
        //     ->setOrder($itemHolder)
        //     ->setTaxDisplayType($TaxDisplayType)
        //     ->setTaxType($Taxation)
        //     ->setProcessorName(PaymentChargePreprocessor::class);
        // $itemHolder->addItem($item);
    }
}
