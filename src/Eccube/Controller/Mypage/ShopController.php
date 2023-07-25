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

namespace Eccube\Controller\Mypage;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Entity\CustomerShop;
use Eccube\Entity\CustomerPoint;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerPointRepository;
use Eccube\Repository\CustomerLevelRepository;
use Eccube\Form\Type\Front\EntryShopType;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ShopController extends AbstractController
{
    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

     /**
     * @var CustomerShopRepository
     */
    protected $customerShopRepository;

    /**
     * @var CustomerLevelRepository
     */
    protected $customerLevelRepository;

    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var CartService
     */
    private $cartService;

    /**
     * @var OrderHelper
     */
    private $orderHelper;

    /**
     * @var CustomerPointRepository
     */
    protected $customerPointRepository;

    /**
     * WithdrawController constructor.edit by gzy
     *
     * @param MailService $mailService
     * @param CustomerStatusRepository $customerStatusRepository
     * @param CustomerShopRepository $customerShopRepository
     * @param TokenStorageInterface $tokenStorage
     * @param CartService $cartService
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        MailService $mailService,
        CustomerStatusRepository $customerStatusRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerLevelRepository $customerLevelRepository,
        TokenStorageInterface $tokenStorage,
        CartService $cartService,
        OrderHelper $orderHelper,
        CustomerPointRepository $customerPointRepository
    ) {
        $this->mailService = $mailService;
        $this->customerStatusRepository = $customerStatusRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->customerLevelRepository = $customerLevelRepository;
        $this->tokenStorage = $tokenStorage;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
        $this->customerPointRepository = $customerPointRepository;
    }

    /**
     * 会员Shop画面.
     *
     * @Route("/mypage/shop", name="mypage_shop")
     * @Template("Mypage/shop.twig")
     */
    public function index(Request $request)
    {
        // edit by gzy 根据积分明细，重新计算积分和过期时间
        $CustomerPoints = $this->customerPointRepository->getCustomerPointByCustomer($this->getUser());
        $Customer = $this->getUser();
        $point = 0;
        $minius = 0;
        $now = date('Y-m-d H:i:s');
        foreach ($CustomerPoints as $CustomerPoint) {
            if($CustomerPoint->getStatus() == "Y"){
                $expDate = $CustomerPoint->getExpTime();
                if($now >= $expDate){
                    $CustomerPoint->setStatus("N");
                    $this->entityManager->flush($CustomerPoint);
                }
                else{
                    $point += $CustomerPoint->getPoint();
                }
            }
            if($CustomerPoint->getMinusPoint() > 0){
                $minius += $CustomerPoint->getMinusPoint();
            }
        }
        $point = $point - $minius;
        if($point < 0){
            $point = 0;
        }
        $Customer->setPoint($point);
        $this->entityManager->flush($Customer);


        $Customer = $this->getUser();
        $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($Customer);
        if($CustomerShop == null){
            $CustomerShop = $this->customerShopRepository->newCustomerShop();
        }





        //edit by gzy 计算积分等级过期时间到页面显示
        $isCustomerShop = "N";
        $level4Shop = "";
        $max4Shop = 0;
        $money4Shop = 0;
        $expTime4Shop = "";

        $CustomerPoints = $this->customerPointRepository->getCustomerPointByCustomer($this->getUser());

        $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($this->getUser());
        if($CustomerShop && $CustomerShop->getStatus() == "Y"){
            $isCustomerShop = "Y";
            $level4Shop = $CustomerShop->getCustomerShopLevel()->getLevel();
            $max4Shop = $CustomerShop->getCustomerShopLevel()->getMax();
            $money4Shop = intval($max4Shop) + 1 - intval($CustomerShop->getMoney());
            $expTime4Shop = date( 'Y-m-d', strtotime($CustomerShop->getExpTime()));
        }


        $CustomerLevel = $this->customerLevelRepository->getCustomerLevelByCustomer($this->getUser());
        $level = $CustomerLevel->getCustomerLevelDetail()->getLevel();
        $max = $CustomerLevel->getCustomerLevelDetail()->getMax();
        $money = intval($max) + 1 - intval($CustomerLevel->getMoney());
        $expTime = date( 'Y-m-d', strtotime($CustomerLevel->getExpTime()));

        $month = date( 'Y-m-d', strtotime($now . "+1 month"));
        $expPoints = 0;
         
        foreach ($CustomerPoints as $CustomerPoint) {
            if($CustomerPoint->getStatus() == "Y"){
                $expDate = $CustomerPoint->getExpTime();
                
                if($month >= $expDate){
                    $expPoints += $CustomerPoint->getPoint();
                }
            }
        }








        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryShopType::class, $CustomerShop);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'CustomerShop' => $CustomerShop,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_SHOP_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        return [
            'form' => $form->createView(),
            'CustomerShop' => $CustomerShop,
            'isCustomerShop' => $isCustomerShop,
            'level' => $level,
            'money' => $money,
            'expPoints' => $expPoints,
            'expTime' => $expTime,
            'level4Shop' => $level4Shop,
            'money4Shop' => $money4Shop,
            'expTime4Shop' => $expTime4Shop
        ];
    }


    

}
