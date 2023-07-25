<?php

namespace Plugin\StripePaymentGateway\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Request;
use SunCat\MobileDetectBundle\DeviceDetector\MobileDetector;
use Eccube\Service\OrderHelper;
use Eccube\Entity\Delivery;
use Eccube\Entity\Shipping;
use Eccube\Entity\Master\DeviceType;
use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\Master\Pref;
use Eccube\Entity\Order;
use Eccube\Entity\Customer;
use Eccube\Entity\Payment;
use Eccube\Entity\OrderItem;
use Eccube\Util\StringUtil;
use Plugin\StripePaymentGateway\Service\Method\StripeCreditCard;

class OrderHelperPr extends OrderHelper{

    public function __construct(
        ContainerInterface $container,
        SessionInterface $session,
        MobileDetector $mobileDetector
    ){
        $entityManager = $container->get("doctrine.orm.entity_manager");
        $orderRepository = $entityManager->getRepository(Order::class);
        $orderStatusRepository = $entityManager->getRepository(OrderStatus::class);
        $orderItemTypeRepository = $entityManager->getRepository(OrderItemType::class);
        $deliveryRepository = $entityManager->getRepository(Delivery::class);
        $paymentRepository = $entityManager->getRepository(Payment::class);
        $deviceTypeRepository = $entityManager->getRepository(DeviceType::class);
        $prefRepository = $entityManager->getRepository(Pref::class);
        
        parent::__construct($container, $entityManager, $orderRepository, $orderItemTypeRepository,
            $orderStatusRepository, $deliveryRepository, $paymentRepository, $deviceTypeRepository, $prefRepository,
            $mobileDetector, $session);
    }

    public function initializeOrderPr($item_infos, Customer $Customer, Shipping $Shipping){
        $Order = new Order($this->orderStatusRepository->find(OrderStatus::PROCESSING));

        $pre_order_id = $this->createPreOrderIdPr();
        $Order->setPreOrderId($pre_order_id);

        $this->setCustomerPr($Order, $Customer);

        $DeviceType = $this->deviceTypeRepository->find($this->mobileDetector->isMobile() ? DeviceType::DEVICE_TYPE_MB : DeviceType::DEVICE_TYPE_PC);
        $Order->setDeviceType($DeviceType);

        $OrderItems = $this->createOrderItemPr($item_infos);
        
        $Shipping->setOrder($Order);
        $this->addOrderItemsPr($Order, $Shipping, $OrderItems);
        $this->entityManager->persist($Shipping);
        $Order->addShipping($Shipping);
        $Payment = $this->paymentRepository->findOneBy(['method_class' => StripeCreditCard::class]);
        $Order->setPayment($Payment);
        // $Order->setPaymentMethod("Inline Pay");

        $this->entityManager->persist($Order);
        return $Order;
    }

    public function convertCartToItemInfos($Cart) 
    {
        $cart_items = $Cart->getCartItems();
        $item_infos = [];
        foreach($cart_items as $cart_item) {
            $item = [
                'Product'   =>  $cart_item->getProductClass()->getProduct(),
                'ProductClass'  =>  $cart_item->getProductClass(),
                'quantity'  =>  $cart_item->getQuantity()
            ];
            $item_infos[] = $item;
        }
        return $item_infos;
    }
    
    private function createOrderItemPr($item_infos){
        $ProductItemType = $this->orderItemTypeRepository->find(OrderItemType::PRODUCT);

        return array_map(function($item) use ($ProductItemType){
            $Product = $item['Product'];
            $ProductClass = $item['ProductClass'];
            $quantity = $item['quantity'];

            $OrderItem = new OrderItem();
            $OrderItem
                ->setProduct($Product)
                ->setProductClass($ProductClass)
                ->setProductName($Product->getName())
                ->setProductCode($ProductClass->getCode())
                ->setPrice($ProductClass->getPrice02())
                ->setQuantity($quantity)
                ->setOrderItemType($ProductItemType);

            $ClassCategory1 = $ProductClass->getClassCategory1();
            if (!is_null($ClassCategory1)) {
                $OrderItem->setClasscategoryName1($ClassCategory1->getName());
                $OrderItem->setClassName1($ClassCategory1->getClassName()->getName());
            }
            $ClassCategory2 = $ProductClass->getClassCategory2();
            if (!is_null($ClassCategory2)) {
                $OrderItem->setClasscategoryName2($ClassCategory2->getName());
                $OrderItem->setClassName2($ClassCategory2->getClassName()->getName());
            }

            return $OrderItem;
        }, $item_infos);
        
    }
    public function createPreOrderIdPr()
    {
        // ランダムなpre_order_idを作成
        do {
            $preOrderId = sha1(StringUtil::random(32));

            $Order = $this->orderRepository->findOneBy(
                [
                    'pre_order_id' => $preOrderId,
                ]
            );
        } while ($Order);

        return $preOrderId;
    }
    protected function setCustomerPr(Order $Order, Customer $Customer)
    {
        if ($Customer->getId()) {
            $Order->setCustomer($Customer);
        }

        $Order->copyProperties(
            $Customer,
            [
                'id',
                'create_date',
                'update_date',
                'del_flg',
            ]
        );
    }

    /**
     * @param Order $Order
     * @param Shipping $Shipping
     * @param array $OrderItems
     */
    protected function addOrderItemsPr(Order $Order, Shipping $Shipping, array $OrderItems)
    {
        foreach ($OrderItems as $OrderItem) {
            $Shipping->addOrderItem($OrderItem);
            $Order->addOrderItem($OrderItem);
            $OrderItem->setOrder($Order);
            $OrderItem->setShipping($Shipping);
        }
    }
}
