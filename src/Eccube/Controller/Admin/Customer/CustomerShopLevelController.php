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

namespace Eccube\Controller\Admin\Customer;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Payment;
use Eccube\Entity\CustomerShopLevel;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\CustomerShopLevelRegisterType;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\CustomerShopLevelRepository;
use Eccube\Service\Payment\Method\Cash;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class PaymentController
 */
class CustomerShopLevelController extends AbstractController
{
    /**
     * @var CustomerShopLevelRepository
     */
    protected $customerShopLevelRepository;

    /**
     * CustomerShopLevelController constructor.
     *
     * @param CustomerShopLevelRepository $customerShopLevelRepository
     */
    public function __construct(CustomerShopLevelRepository $customerShopLevelRepository)
    {
        $this->customerShopLevelRepository = $customerShopLevelRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/customer/customerShopLevel", name="admin_customer_shop_level")
     * @Template("@admin/Customer/customer_shop_level.twig")
     */
    public function index(Request $request)
    {
        $CustomerShopLevels = $this->customerShopLevelRepository->findAll();

        $event = new EventArgs(
            [
                'CustomerShopLevels' => $CustomerShopLevels,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_SHOP_LEVEL_INDEX_COMPLETE, $event);
        return [
            'CustomerShopLevels' => $CustomerShopLevels,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/customer/customerShopLevel/new", name="admin_customer_shop_level_new")
     * @Route("/%eccube_admin_route%/customer/customerShopLevel/{id}/edit", requirements={"id" = "\d+"}, name="admin_customer_shop_level_edit")
     * @Template("@admin/Customer/customer_shop_level_edit.twig")
     */
    public function edit(Request $request, CustomerShopLevel $CustomerShopLevel = null)
    {
        if (is_null($CustomerShopLevel)) {
            $CustomerShopLevel = new \Eccube\Entity\CustomerShopLevel();
        }

        $builder = $this->formFactory
            ->createBuilder(CustomerShopLevelRegisterType::class, $CustomerShopLevel);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'CustomerShopLevel' => $CustomerShopLevel,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_SHOP_LEVEL_EDIT_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->setData($CustomerShopLevel);
        $form->handleRequest($request);

        // 登録ボタン押下
        if ($form->isSubmitted() && $form->isValid()) {
            $CustomerShopLevel = $form->getData();
            echo($CustomerShopLevel->getLevel());

            $this->entityManager->persist($CustomerShopLevel);
            $this->entityManager->flush();

            $event = new EventArgs(
                [
                    'form' => $form,
                    'CustomerShopLevel' => $CustomerShopLevel,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_SHOP_LEVEL_EDIT_COMPLETE, $event);

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_customer_shop_level_edit', ['id' => $CustomerShopLevel->getId()]);
        }

        return [
            'form' => $form->createView(),
            'CustomerShopLevel' => $CustomerShopLevel
        ];
    }

    // /**
    //  * @Route("/%eccube_admin_route%/setting/shop/payment/image/add", name="admin_payment_image_add")
    //  */
    // public function imageAdd(Request $request)
    // {
    //     if (!$request->isXmlHttpRequest()) {
    //         throw new BadRequestHttpException();
    //     }

    //     $images = $request->files->get('payment_register');
    //     $allowExtensions = ['gif', 'jpg', 'jpeg', 'png'];
    //     $filename = null;
    //     if (isset($images['payment_image_file'])) {
    //         $image = $images['payment_image_file'];

    //         //ファイルフォーマット検証
    //         $mimeType = $image->getMimeType();
    //         if (0 !== strpos($mimeType, 'image')) {
    //             throw new UnsupportedMediaTypeHttpException();
    //         }

    //         // 拡張子
    //         $extension = $image->getClientOriginalExtension();
    //         if (!in_array(strtolower($extension), $allowExtensions)) {
    //             throw new UnsupportedMediaTypeHttpException();
    //         }

    //         $filename = date('mdHis').uniqid('_').'.'.$extension;
    //         $image->move($this->getParameter('eccube_temp_image_dir'), $filename);
    //     }
    //     $event = new EventArgs(
    //         [
    //             'images' => $images,
    //             'filename' => $filename,
    //         ],
    //         $request
    //     );
    //     $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_SETTING_SHOP_PAYMENT_IMAGE_ADD_COMPLETE, $event);
    //     $filename = $event->getArgument('filename');

    //     return $this->json(['filename' => $filename], 200);
    // }

    // /**
    //  * @Route("/%eccube_admin_route%/setting/shop/payment/{id}/delete", requirements={"id" = "\d+"}, name="admin_setting_shop_payment_delete", methods={"DELETE"})
    //  *
    //  * @param Request $request
    //  * @param Payment $TargetPayment
    //  *
    //  * @return \Symfony\Component\HttpFoundation\RedirectResponse
    //  */
    // public function delete(Request $request, Payment $TargetPayment)
    // {
    //     $this->isTokenValid();

    //     $sortNo = 1;
    //     $Payments = $this->paymentRepository->findBy([], ['sort_no' => 'ASC']);
    //     foreach ($Payments as $Payment) {
    //         $Payment->setSortNo($sortNo++);
    //     }

    //     try {
    //         $this->paymentRepository->delete($TargetPayment);
    //         $this->entityManager->flush();

    //         $event = new EventArgs(
    //             [
    //                 'Payment' => $TargetPayment,
    //             ],
    //             $request
    //         );
    //         $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_SETTING_SHOP_PAYMENT_DELETE_COMPLETE, $event);

    //         $this->addSuccess('admin.common.delete_complete', 'admin');
    //     } catch (ForeignKeyConstraintViolationException $e) {
    //         $this->entityManager->rollback();

    //         $message = trans('admin.common.delete_error_foreign_key', ['%name%' => $TargetPayment->getMethod()]);
    //         $this->addError($message, 'admin');
    //     }

    //     return $this->redirectToRoute('admin_setting_shop_payment');
    // }

    // /**
    //  * @Route("/%eccube_admin_route%/setting/shop/payment/{id}/visible", requirements={"id" = "\d+"}, name="admin_setting_shop_payment_visible", methods={"PUT"})
    //  */
    // public function visible(Payment $Payment)
    // {
    //     $this->isTokenValid();

    //     $Payment->setVisible(!$Payment->isVisible());

    //     $this->entityManager->flush();

    //     if ($Payment->isVisible()) {
    //         $this->addSuccess(trans('admin.common.to_show_complete', ['%name%' => $Payment->getMethod()]), 'admin');
    //     } else {
    //         $this->addSuccess(trans('admin.common.to_hide_complete', ['%name%' => $Payment->getMethod()]), 'admin');
    //     }

    //     return $this->redirectToRoute('admin_setting_shop_payment');
    // }

    // /**
    //  * @Route("/%eccube_admin_route%/setting/shop/payment/sort_no/move", name="admin_setting_shop_payment_sort_no_move", methods={"POST"})
    //  *
    //  * @param Request $request
    //  *
    //  * @return Response
    //  */
    // public function moveSortNo(Request $request)
    // {
    //     if (!$request->isXmlHttpRequest()) {
    //         throw new BadRequestHttpException();
    //     }

    //     if ($this->isTokenValid()) {
    //         $sortNos = $request->request->all();
    //         foreach ($sortNos as $paymentId => $sortNo) {
    //             /** @var Payment $Payment */
    //             $Payment = $this->paymentRepository
    //                 ->find($paymentId);
    //             $Payment->setSortNo($sortNo);
    //             $this->entityManager->persist($Payment);
    //         }
    //         $this->entityManager->flush();

    //         return new Response();
    //     }
    // }
}
