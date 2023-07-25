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

use Eccube\Entity\BaseInfo;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * 送料無料条件を適用する.
 * お届け先ごとに条件判定を行う.
 */
class DeliveryFeeFreeByShippingPreprocessor implements ItemHolderPreprocessor
{
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * DeliveryFeeProcessor constructor.
     *
     * @param BaseInfoRepository $baseInfoRepository
     */
    public function __construct(BaseInfoRepository $baseInfoRepository)
    {
        $this->BaseInfo = $baseInfoRepository->get();
    }

    /**
     * @param ItemHolderInterface $itemHolder
     * @param PurchaseContext $context
     */
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context)
    {
        if (!($this->BaseInfo->getDeliveryFreeAmount() || $this->BaseInfo->getDeliveryFreeQuantity())) {
            return;
        }

        // Orderの場合はお届け先ごとに判定する.
        if ($itemHolder instanceof Order) {
            /** @var Order $Order */
            $Order = $itemHolder;
            $Customer = $Order->getCustomer();
            $isShop = false;
            $discount = 0; //这里设置折扣的大小。
            if(count($Customer->getCustomerShops()) > 0){
                $CustomerShop = $Customer->getCustomerShops()[0];
                if($CustomerShop->getStatus() == "Y"){
                    $isShop = true;
                    $discount=$CustomerShop->getCustomerShopLevel()->getDiscount(); # 专业会员会有折扣信息
                }
            }
            foreach ($Order->getShippings() as $Shipping) {
                $isFree = false;
                $total = 0;
                $quantity = 0;
                foreach ($Shipping->getProductOrderItems() as $Item) {
                    $total += $Item->getPriceIncTax() * $Item->getQuantity();
                    $quantity += $Item->getQuantity();
                }
                
                if($isShop){
                    // edit by gzy 商户达到一定金额免运费
                    if ($this->BaseInfo->getDeliveryShopFreeAmount()) {//这个地方应该是折扣后的总价格，这里的total计算的是折扣前的价格。
                        if (($total * (1-$discount/100.0)) >= $this->BaseInfo->getDeliveryShopFreeAmount()) {
                            $isFree = true;
                        }
                    }
                }
                else{
                    // 送料無料（金額）を超えている
                    if ($this->BaseInfo->getDeliveryFreeAmount()) {
                        if ($total >= $this->BaseInfo->getDeliveryFreeAmount()) {
                            $isFree = true;
                        }
                    }
                    
                    // 送料無料（個数）を超えている
                    if ($this->BaseInfo->getDeliveryFreeQuantity()) {
                        if ($quantity >= $this->BaseInfo->getDeliveryFreeQuantity()) {
                            $isFree = true;
                        }
                    }

                }
                
                
                // edit by gzy 免运费
                if ($isFree) {
                    foreach ($Shipping->getOrderItems() as $Item) {
                        if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                            $Item->setQuantity(0);
                        }
                    }
                }
            }
        }
    }
}
