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
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\EntryType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\CustomerLevelRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerPointRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Encoder\EncoderFactoryInterface;

class ChangeController extends AbstractController
{
    /**
     * @var TokenStorage
     */
    protected $tokenStorage;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

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
     * @var EncoderFactoryInterface
     */
    protected $encoderFactory;

    public function __construct(
        CustomerRepository $customerRepository,
        EncoderFactoryInterface $encoderFactory,
        CustomerLevelRepository $customerLevelRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerPointRepository $customerPointRepository,
        TokenStorageInterface $tokenStorage
    ) {
        $this->customerRepository = $customerRepository;
        $this->encoderFactory = $encoderFactory;
        $this->customerLevelRepository = $customerLevelRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->tokenStorage = $tokenStorage;
        $this->customerPointRepository = $customerPointRepository;
    }

    /**
     * 会員情報編集画面.
     *
     * @Route("/mypage/change", name="mypage_change")
     * @Template("Mypage/change.twig")
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



        $Customer = $this->getUser();
        $LoginCustomer = clone $Customer;
        $this->entityManager->detach($LoginCustomer);

        $previous_password = $Customer->getPassword();
        $Customer->setPassword($this->eccubeConfig['eccube_default_password']);

        /* @var $builder \Symfony\Component\Form\FormBuilderInterface */
        $builder = $this->formFactory->createBuilder(EntryType::class, $Customer);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_INITIALIZE, $event);

        /* @var $form \Symfony\Component\Form\FormInterface */
        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('会員編集開始');

            if ($Customer->getPassword() === $this->eccubeConfig['eccube_default_password']) {
                $Customer->setPassword($previous_password);
            } else {
                $encoder = $this->encoderFactory->getEncoder($Customer);
                if ($Customer->getSalt() === null) {
                    $Customer->setSalt($encoder->createSalt());
                }
                $Customer->setPassword(
                    $encoder->encodePassword($Customer->getPassword(), $Customer->getSalt())
                );
            }
            $this->entityManager->flush();

            log_info('会員編集完了');

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Customer' => $Customer,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_CHANGE_INDEX_COMPLETE, $event);

            return $this->redirect($this->generateUrl('mypage_change_complete'));
        }

        $this->tokenStorage->getToken()->setUser($LoginCustomer);

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
     * 会員情報編集完了画面.
     *
     * @Route("/mypage/change_complete", name="mypage_change_complete")
     * @Template("Mypage/change_complete.twig")
     */
    public function complete(Request $request)
    {
        return [];
    }
}
