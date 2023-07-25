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

namespace Eccube\Controller;

use Eccube\Entity\BaseInfo;
use Eccube\Entity\ProductClass;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\ProductClassRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Service\CartService;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Service\PurchaseFlow\PurchaseFlowResult;
use Eccube\Service\OrderHelper;
use SebastianBergmann\CodeCoverage\Report\Xml\Totals;
use Eccube\Repository\CustomerLevelRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CartController extends AbstractController
{
    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * @var CartService
     */
    protected $cartService;

    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var BaseInfo
     */
    protected $baseInfo;

    /**
     * @var CustomerShopRepository
     */
    protected $customerShopRepository;

    /**
     * @var CustomerLevelRepository
     */
    protected $customerLevelRepository;

    /**
     * CartController constructor.
     *
     * @param ProductClassRepository $productClassRepository
     * @param CartService $cartService
     * @param PurchaseFlow $cartPurchaseFlow
     * @param BaseInfoRepository $baseInfoRepository
     */
    public function __construct(
        ProductClassRepository $productClassRepository,
        CartService $cartService,
        PurchaseFlow $cartPurchaseFlow,
        BaseInfoRepository $baseInfoRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerLevelRepository $customerLevelRepository
    ) {
        $this->productClassRepository = $productClassRepository;
        $this->cartService = $cartService;
        $this->purchaseFlow = $cartPurchaseFlow;
        $this->baseInfo = $baseInfoRepository->get();
        $this->customerShopRepository = $customerShopRepository;
        $this->customerLevelRepository = $customerLevelRepository;
    }

    /**
     * カート画面.
     *
     * @Route("/cart", name="cart")
     * @Template("Cart/index.twig")
     */
    public function index(Request $request)
    {
        //edit by gzy 增加登陆判断
        if ($this->getUser() == null) {
            log_info('[リダイレクト] 未ログインもしくはRememberMeログインのため, ログイン画面に遷移します.');

            return $this->redirectToRoute('mypage_login');
        }
        // カートを取得して明細の正規化を実行
        $Carts = $this->cartService->getCarts();
        $this->execPurchaseFlow($Carts);

        // TODO itemHolderから取得できるように
        $least = [];
        $quantity = [];
        $isDeliveryFree = [];
        $totalPrice = 0;
        $totalQuantity = 0;
        $discount = 0; # 普通会员，就为零，专业会员，根据获取的数值进行计算。
        foreach ($Carts as $Cart) {
            $quantity[$Cart->getCartKey()] = 0;
            $isDeliveryFree[$Cart->getCartKey()] = false;

            // TODO　判断登陆的用户身份是否是专业会员　
            $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($this->getUser());
            if ($CustomerShop && $CustomerShop->getStatus() == "Y"){//如果是专业会员
                $discount = $CustomerShop->getCustomerShopLevel()->getDiscount(); # 专业会员会有折扣信息

                if ($this->baseInfo->getDeliveryFreeQuantity()) {
                    if ($this->baseInfo->getDeliveryFreeQuantity() > $Cart->getQuantity()) {
                        $quantity[$Cart->getCartKey()] = $this->baseInfo->getDeliveryFreeQuantity() - $Cart->getQuantity();
                    } else {
                        $isDeliveryFree[$Cart->getCartKey()] = true;
                    }
                }

                if ($this->baseInfo->getDeliveryShopFreeAmount()) {

                    // 这个地方要重新计算价格Cart->getTotalPrice()得到的价格是总价格,后续折扣时候，但是有的产品不是shop产品，不能计入在内，所以，需要单独判断
                    $total=0; // 直接计算折扣后的价格
                    foreach($Cart->getItems() as $Cartiterm){
                        $product_id = $Cartiterm->getProductClass()->getProduct();
                        if($product_id){ //先判断商品存在
                            //获取产品是否为批发商品 
                            if($product_id->getShopDiscount() == 'Y'){
                                $total += $Cartiterm->getPrice() * $Cartiterm->getQuantity()*(1-$discount/100.0);
                            }else{
                                $total += $Cartiterm->getPrice() * $Cartiterm->getQuantity();
                            }
                        }

                    }
                    $Cart->setTotalPrice($total);

                    if (!$isDeliveryFree[$Cart->getCartKey()] && $this->baseInfo->getDeliveryShopFreeAmount() <= $total) { #如果是专业会员，需要判断折扣后的价格大于免费配送的价格
                        $isDeliveryFree[$Cart->getCartKey()] = true;
                    } else {
                        $least[$Cart->getCartKey()] = $this->baseInfo->getDeliveryShopFreeAmount() - $total;#如果是专业会员，差的价格是减去折扣后的价格
                    }
                } 
                // //TODO 增加逻辑，是4-GOLD  5-PLATINUM   6-DIANOND 会员，除了冲绳，其余都包邮
                // $CustomerLevel = $CustomerShop->getCustomerShopLevel()->getLevel(); #  获取shop会员的level
                // if(($CustomerLevel == "GOLD" || $CustomerLevel == "PLATINUM" || $CustomerLevel == "DIANOND")&& ($this->getUser()->getPref()->getId()!='47')){
                //     $isDeliveryFree[$Cart->getCartKey()] = true;
                // }
            }else{
                if ($this->baseInfo->getDeliveryFreeQuantity()) {
                    if ($this->baseInfo->getDeliveryFreeQuantity() > $Cart->getQuantity()) {
                        $quantity[$Cart->getCartKey()] = $this->baseInfo->getDeliveryFreeQuantity() - $Cart->getQuantity();
                    } else {
                        $isDeliveryFree[$Cart->getCartKey()] = true;
                    }
                }
    
                if ($this->baseInfo->getDeliveryFreeAmount()) {
                    if (!$isDeliveryFree[$Cart->getCartKey()] && $this->baseInfo->getDeliveryFreeAmount() <= $Cart->getTotalPrice()) {
                        $isDeliveryFree[$Cart->getCartKey()] = true;
                    } else {
                        $least[$Cart->getCartKey()] = $this->baseInfo->getDeliveryFreeAmount() - $Cart->getTotalPrice();
                    }
                }
                // //TODO 增加逻辑，是4-GOLD  5-PLATINUM   6-DIANOND 会员，除了冲绳，其余都包邮
                // $CustomerLevel = $this->customerLevelRepository->getCustomerLevelByCustomer($this->getUser()); #  获取普通会员的level实体
                // $level = $CustomerLevel->getCustomerLevelDetail()->getLevel();
                // if($level == "GOLD" || $level == "PLATINUM" || $level == "DIANOND" && (($this->getUser()->getPref()->getId()!='47'))){
                //     $isDeliveryFree[$Cart->getCartKey()] = true;
                // }
            }


            $totalPrice += $Cart->getTotalPrice();
            $totalQuantity += $Cart->getQuantity();
        }
        //上面已经拿到折扣价格，这里不再二次计算了
        // if ($CustomerShop && $CustomerShop->getStatus() == "Y"){//如果是专业会员,价格要×折扣率
        //     $totalPrice = $totalPrice * (1-$discount/100.0);
        // }



        // カートが分割された時のセッション情報を削除
        $request->getSession()->remove(OrderHelper::SESSION_CART_DIVIDE_FLAG);

        return [
            'totalPrice' => $totalPrice,
            'totalQuantity' => $totalQuantity,
            // 空のカートを削除し取得し直す
            'Carts' => $this->cartService->getCarts(true),
            'least' => $least,
            'quantity' => $quantity,
            'is_delivery_free' => $isDeliveryFree,
            'discount' => $discount,
        ];
    }

    /**
     * @param $Carts
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function execPurchaseFlow($Carts)
    {
        /** @var PurchaseFlowResult[] $flowResults */
        $flowResults = array_map(function ($Cart) {
            $purchaseContext = new PurchaseContext($Cart, $this->getUser());

            return $this->purchaseFlow->validate($Cart, $purchaseContext);
        }, $Carts);

        // 復旧不可のエラーが発生した場合はカートをクリアして再描画
        $hasError = false;
        foreach ($flowResults as $result) {
            if ($result->hasError()) {
                $hasError = true;
                foreach ($result->getErrors() as $error) {
                    $this->addRequestError($error->getMessage());
                }
            }
        }
        if ($hasError) {
            $this->cartService->clear();

            return $this->redirectToRoute('cart');
        }

        $this->cartService->save();

        foreach ($flowResults as $index => $result) {
            foreach ($result->getWarning() as $warning) {
                if ($Carts[$index]->getItems()->count() > 0) {
                    $cart_key = $Carts[$index]->getCartKey();
                    $this->addRequestError($warning->getMessage(), "front.cart.${cart_key}");
                } else {
                    // キーが存在しない場合はグローバルにエラーを表示する
                    $this->addRequestError($warning->getMessage());
                }
            }
        }
    }

    /**
     * カート明細の加算/減算/削除を行う.
     *
     * - 加算
     *      - 明細の個数を1増やす
     * - 減算
     *      - 明細の個数を1減らす
     *      - 個数が0になる場合は、明細を削除する
     * - 削除
     *      - 明細を削除する
     *
     * @Route(
     *     path="/cart/{operation}/{productClassId}",
     *     name="cart_handle_item",
     *     methods={"PUT"},
     *     requirements={
     *          "operation": "up|down|remove",
     *          "productClassId": "\d+"
     *     }
     * )
     */
    public function handleCartItem($operation, $productClassId)
    {
        log_info('カート明細操作開始', ['operation' => $operation, 'product_class_id' => $productClassId]);

        $this->isTokenValid();

        /** @var ProductClass $ProductClass */
        $ProductClass = $this->productClassRepository->find($productClassId);

        if (is_null($ProductClass)) {
            log_info('商品が存在しないため、カート画面へredirect', ['operation' => $operation, 'product_class_id' => $productClassId]);

            return $this->redirectToRoute('cart');
        }

        // 明細の増減・削除
        switch ($operation) {
            case 'up':
                $this->cartService->addProduct($ProductClass, 1);
                break;
            case 'down':
                $this->cartService->addProduct($ProductClass, -1);
                break;
            case 'remove':
                $this->cartService->removeProduct($ProductClass);
                break;
        }

        // カートを取得して明細の正規化を実行
        $Carts = $this->cartService->getCarts();
        $this->execPurchaseFlow($Carts);

        log_info('カート演算処理終了', ['operation' => $operation, 'product_class_id' => $productClassId]);

        return $this->redirectToRoute('cart');
    }

    /**
     * カートをロック状態に設定し、購入確認画面へ遷移する.
     *
     * @Route("/cart/buystep/{cart_key}", name="cart_buystep", requirements={"cart_key" = "[a-zA-Z0-9]+[_][\x20-\x7E]+"})
     */
    public function buystep(Request $request, $cart_key)
    {
        $Carts = $this->cartService->getCart();
        if (!is_object($Carts)) {
            return $this->redirectToRoute('cart');
        }
        // FRONT_CART_BUYSTEP_INITIALIZE
        $event = new EventArgs(
            [],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CART_BUYSTEP_INITIALIZE, $event);

        $this->cartService->setPrimary($cart_key);
        $this->cartService->save();

        // FRONT_CART_BUYSTEP_COMPLETE
        $event = new EventArgs(
            [],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_CART_BUYSTEP_COMPLETE, $event);

        if ($event->hasResponse()) {
            return $event->getResponse();
        }

        return $this->redirectToRoute('shopping');
    }
}
