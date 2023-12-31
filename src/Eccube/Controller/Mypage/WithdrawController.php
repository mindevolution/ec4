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
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Repository\CustomerLevelRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerPointRepository;
use Eccube\Service\CartService;
use Eccube\Service\MailService;
use Eccube\Service\OrderHelper;
use Eccube\Util\StringUtil;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class WithdrawController extends AbstractController
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
     * @var CustomerPointRepository
     */
    protected $customerPointRepository;

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
     * WithdrawController constructor.
     *
     * @param MailService $mailService
     * @param CustomerStatusRepository $customerStatusRepository
     * @param TokenStorageInterface $tokenStorage
     * @param CartService $cartService
     * @param OrderHelper $orderHelper
     */
    public function __construct(
        MailService $mailService,
        CustomerStatusRepository $customerStatusRepository,
        CustomerLevelRepository $customerLevelRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerPointRepository $customerPointRepository,
        TokenStorageInterface $tokenStorage,
        CartService $cartService,
        OrderHelper $orderHelper
    ) {
        $this->mailService = $mailService;
        $this->customerStatusRepository = $customerStatusRepository;
        $this->customerLevelRepository = $customerLevelRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->customerPointRepository = $customerPointRepository;
        $this->tokenStorage = $tokenStorage;
        $this->cartService = $cartService;
        $this->orderHelper = $orderHelper;
    }

    /**
     * 退会画面.
     *
     * @Route("/mypage/withdraw", name="mypage_withdraw")
     * @Template("Mypage/withdraw.twig")
     */
    public function index(Request $request)
    {
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


        
        $builder = $this->formFactory->createBuilder();

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('退会確認画面表示');

                    return $this->render(
                        'Mypage/withdraw_confirm.twig',
                        [
                            'form' => $form->createView(),
                        ]
                    );

                case 'complete':
                    log_info('退会処理開始');

                    /* @var $Customer \Eccube\Entity\Customer */
                    $Customer = $this->getUser();
                    $email = $Customer->getEmail();

                    // 退会ステータスに変更
                    $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::WITHDRAWING);
                    $Customer->setStatus($CustomerStatus);
                    $Customer->setEmail(StringUtil::random(60).'@dummy.dummy');

                    $this->entityManager->flush();

                    log_info('退会処理完了');

                    $event = new EventArgs(
                        [
                            'form' => $form,
                            'Customer' => $Customer,
                        ], $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_WITHDRAW_INDEX_COMPLETE, $event);

                    // メール送信
                    $this->mailService->sendCustomerWithdrawMail($Customer, $email);

                    // カートと受注のセッションを削除
                    $this->cartService->clear();
                    $this->orderHelper->removeSession();

                    // ログアウト
                    $this->tokenStorage->setToken(null);

                    log_info('ログアウト完了');

                    return $this->redirect($this->generateUrl('mypage_withdraw_complete'));
            }
        }

        return [
            'form' => $form->createView(),
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

    /**
     * 退会完了画面.
     *
     * @Route("/mypage/withdraw_complete", name="mypage_withdraw_complete")
     * @Template("Mypage/withdraw_complete.twig")
     */
    public function complete(Request $request)
    {
        return [];
    }
}
