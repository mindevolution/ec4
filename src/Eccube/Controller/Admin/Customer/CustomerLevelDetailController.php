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
use Eccube\Entity\CustomerLevelDetail;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\CustomerLevelDetailRegisterType;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\CustomerLevelDetailRepository;
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
class CustomerLevelDetailController extends AbstractController
{
    /**
     * @var CustomerLevelDetailRepository
     */
    protected $customerLevelDetailRepository;

    /**
     * CustomerLevelDetailController constructor.
     *
     * @param CustomerLevelDetailRepository $customerLevelDetailRepository
     */
    public function __construct(CustomerLevelDetailRepository $customerLevelDetailRepository)
    {
        $this->customerLevelDetailRepository = $customerLevelDetailRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/customer/customerLevelDetail", name="admin_customer_level_detail")
     * @Template("@admin/Customer/customer_level_detail.twig")
     */
    public function index(Request $request)
    {
        $CustomerLevelDetails = $this->customerLevelDetailRepository->findAll();

        $event = new EventArgs(
            [
                'CustomerLevelDetails' => $CustomerLevelDetails,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_LEVEL_DETAIL_INDEX_COMPLETE, $event);
        return [
            'CustomerLevelDetails' => $CustomerLevelDetails,
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/customer/customerLevelDetail/new", name="admin_customer_level_detail_new")
     * @Route("/%eccube_admin_route%/customer/customerLevelDetail/{id}/edit", requirements={"id" = "\d+"}, name="admin_customer_level_detail_edit")
     * @Template("@admin/Customer/customer_level_detail_edit.twig")
     */
    public function edit(Request $request, CustomerLevelDetail $CustomerLevelDetail = null)
    {
        if (is_null($CustomerLevelDetail)) {
            $CustomerLevelDetail = new \Eccube\Entity\CustomerLevelDetail();
        }

        $builder = $this->formFactory
            ->createBuilder(CustomerLevelDetailRegisterType::class, $CustomerLevelDetail);

        $event = new EventArgs(
            [
                'builder' => $builder,
                'CustomerLevelDetail' => $CustomerLevelDetail,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_LEVEL_DETAIL_EDIT_INITIALIZE, $event);

        $form = $builder->getForm();

        $form->setData($CustomerLevelDetail);
        $form->handleRequest($request);

        // 登録ボタン押下
        if ($form->isSubmitted() && $form->isValid()) {
            $CustomerLevelDetail = $form->getData();
            echo($CustomerLevelDetail->getLevel());

            $this->entityManager->persist($CustomerLevelDetail);
            $this->entityManager->flush();

            $event = new EventArgs(
                [
                    'form' => $form,
                    'CustomerLevelDetail' => $CustomerLevelDetail,
                ],
                $request
            );
            $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_LEVEL_DETAIL_EDIT_COMPLETE, $event);

            $this->addSuccess('admin.common.save_complete', 'admin');

            return $this->redirectToRoute('admin_customer_level_detail_edit', ['id' => $CustomerLevelDetail->getId()]);
        }

        return [
            'form' => $form->createView(),
            'CustomerLevelDetail' => $CustomerLevelDetail
        ];
    }

}
