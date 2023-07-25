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
use Eccube\Entity\BaseInfo;
use Eccube\Entity\CustomerAddress;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Front\CustomerAddressType;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\CustomerAddressRepository;
use Eccube\Repository\CustomerLevelRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerPointRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;

class DeliveryController extends AbstractController
{
    /**
     * @var BaseInfo
     */
    protected $BaseInfo;

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
     * @var CustomerAddressRepository
     */
    protected $customerAddressRepository;

    public function __construct(
        BaseInfoRepository $baseInfoRepository, 
        CustomerLevelRepository $customerLevelRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerPointRepository $customerPointRepository,
        CustomerAddressRepository $customerAddressRepository
    ) {
        $this->BaseInfo = $baseInfoRepository->get();
        $this->customerAddressRepository = $customerAddressRepository;
        $this->customerLevelRepository = $customerLevelRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->customerPointRepository = $customerPointRepository;
    }

    /**
     * お届け先一覧画面.
     *
     * @Route("/mypage/delivery", name="mypage_delivery")
     * @Template("Mypage/delivery.twig")
     */
    public function index(Request $request)
    {
        $Customer = $this->getUser();

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





        return [
            'Customer' => $Customer,
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
     * お届け先編集画面.
     *
     * @Route("/mypage/delivery/new", name="mypage_delivery_new")
     * @Route("/mypage/delivery/{id}/edit", name="mypage_delivery_edit", requirements={"id" = "\d+"})
     * @Template("Mypage/delivery_edit.twig")
     */
    public function edit(Request $request, $id = null)
    {
        $Customer = $this->getUser();

        // 配送先住所最大値判定
        // $idが存在する際は、追加処理ではなく、編集の処理ため本ロジックスキップ
        if (is_null($id)) {
            $addressCurrNum = count($Customer->getCustomerAddresses());
            $addressMax = $this->eccubeConfig['eccube_deliv_addr_max'];
            if ($addressCurrNum >= $addressMax) {
                throw new NotFoundHttpException();
            }
            $CustomerAddress = new CustomerAddress();
            $CustomerAddress->setCustomer($Customer);
        } else {
            $CustomerAddress = $this->customerAddressRepository->findOneBy(
                [
                    'id' => $id,
                    'Customer' => $Customer,
                ]
            );
            if (!$CustomerAddress) {
                throw new NotFoundHttpException();
            }
        }

        $parentPage = $request->get('parent_page', null);

        // 正しい遷移かをチェック
        $allowedParents = [
            $this->generateUrl('mypage_delivery'),
            $this->generateUrl('shopping_redirect_to'),
        ];

        // 遷移が正しくない場合、デフォルトであるマイページの配送先追加の画面を設定する
        if (!in_array($parentPage, $allowedParents)) {
            // @deprecated 使用されていないコード
            $parentPage = $this->generateUrl('mypage_delivery');
        }

        $builder = $this->formFactory
            ->createBuilder(CustomerAddressType::class, $CustomerAddress);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'Customer' => $Customer,
                'CustomerAddress' => $CustomerAddress,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_DELIVERY_EDIT_INITIALIZE, $event);

        $form = $builder->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            log_info('お届け先登録開始', [$id]);

            $this->entityManager->persist($CustomerAddress);
            $this->entityManager->flush();

            log_info('お届け先登録完了', [$id]);

            $event = new EventArgs(
                [
                    'form' => $form,
                    'Customer' => $Customer,
                    'CustomerAddress' => $CustomerAddress,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_DELIVERY_EDIT_COMPLETE, $event);

            $this->addSuccess('mypage.delivery.add.complete');

            return $this->redirect($this->generateUrl('mypage_delivery'));
        }

        return [
            'form' => $form->createView(),
            'parentPage' => $parentPage,
            'BaseInfo' => $this->BaseInfo,
        ];
    }

    /**
     * お届け先を削除する.
     *
     * @Route("/mypage/delivery/{id}/delete", name="mypage_delivery_delete", methods={"DELETE"})
     */
    public function delete(Request $request, CustomerAddress $CustomerAddress)
    {
        $this->isTokenValid();

        log_info('お届け先削除開始', [$CustomerAddress->getId()]);

        $Customer = $this->getUser();

        if ($Customer->getId() != $CustomerAddress->getCustomer()->getId()) {
            throw new BadRequestHttpException();
        }

        $this->customerAddressRepository->delete($CustomerAddress);

        $event = new EventArgs(
            [
                'Customer' => $Customer,
                'CustomerAddress' => $CustomerAddress,
            ], $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::FRONT_MYPAGE_DELIVERY_DELETE_COMPLETE, $event);

        $this->addSuccess('mypage.address.delete.complete');

        log_info('お届け先削除完了', [$CustomerAddress->getId()]);

        return $this->redirect($this->generateUrl('mypage_delivery'));
    }
}
