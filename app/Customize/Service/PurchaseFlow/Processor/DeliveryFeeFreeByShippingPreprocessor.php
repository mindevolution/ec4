<?php


namespace Customize\Service\PurchaseFlow\Processor;


use Eccube\Annotation\ShoppingFlow;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;

/**
 * @ShoppingFlow()
 *
 * Class DeliveryFeeFreeByShippingPreprocessor
 * @package Customize\Service\PurchaseFlow\Processor
 */
class DeliveryFeeFreeByShippingPreprocessor implements ItemHolderPreprocessor
{

    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

    /**
     * @var PrefRepository
     */
    private $prefRepository;

    public function __construct(
        BaseInfoRepository $baseInfoRepository,
        PrefRepository $prefRepository
    )
    {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->prefRepository = $prefRepository;
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
            foreach ($Order->getShippings() as $Shipping) {
                $isFree = false;
                $total = 0;
                $quantity = 0;
                foreach ($Shipping->getProductOrderItems() as $Item) {
                    $total += $Item->getPriceIncTax() * $Item->getQuantity();
                    $quantity += $Item->getQuantity();
                }
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
                // 送料無料条件を超えている場合
                if ($isFree) {
                    foreach ($Shipping->getOrderItems() as $Item) {
                        if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                            // 送料無料条件適用を除外する都道府県とマッチしたら送料明細の数量を1とする
                            $Prefs = $this->prefRepository->findBy(['name' => [ '沖縄県']]);
                            foreach ($Prefs as $Pref) {
                                if ($Shipping->getPref() === $Pref) {
                                    $Item->setQuantity(1);
                                    break;
                                }
                            }
                        }
                    }
                }
            }
        }


    }
}