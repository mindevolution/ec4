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

namespace Plugin\Maker4\Controller;

use Eccube\Controller\AbstractController;
use Plugin\Maker4\Entity\Maker;
use Plugin\Maker4\Form\Type\MakerType;
use Plugin\Maker4\Repository\MakerRepository;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MakerController.
 */
class MakerController extends AbstractController
{
    /**
     * @var MakerRepository
     */
    protected $makerRepository;

    /**
     * MakerController constructor.
     *
     * @param MakerRepository $makerRepository
     */
    public function __construct(MakerRepository $makerRepository)
    {
        $this->makerRepository = $makerRepository;
    }

    /**
     * List, add, edit maker.
     *
     * @param Request $request
     *
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @throws \Doctrine\ORM\NoResultException
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\OptimisticLockException
     *
     * @Route("/%eccube_admin_route%/maker", name="maker_admin_index")
     * @Template("@Maker4/admin/maker.twig")
     */
    public function index(Request $request)
    {
        $Maker = new Maker();
        $Makers = $this->makerRepository->findBy([], ['sort_no' => 'DESC']);

        /**
         * 新規登録フォーム
         */
        $builder = $this->formFactory->createBuilder(MakerType::class, $Maker);

        $form = $builder->getForm();

        /**
         * 編集用フォーム
         */
        $forms = [];
        foreach ($Makers as $item) {
            $id = $item->getId();
            $forms[$id] = $this->formFactory->createNamed('maker_'.$id, MakerType::class, $item);
        }

        if ('POST' === $request->getMethod()) {
            /*
             * 登録処理
             */
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $this->makerRepository->save($form->getData());

                $this->addSuccess('maker.admin.save.complete', 'admin');

                return $this->redirectToRoute('maker_admin_index');
            }

            /*
             * 編集処理
             */
            foreach ($forms as $editForm) {
                $editForm->handleRequest($request);
                if ($editForm->isSubmitted() && $editForm->isValid()) {
                    $this->makerRepository->save($editForm->getData());

                    $this->addSuccess('maker.admin.save.complete', 'admin');

                    return $this->redirectToRoute('maker_admin_index');
                }
            }
        }

        $formViews = [];
        foreach ($forms as $key => $value) {
            $formViews[$key] = $value->createView();
        }

        return [
            'form' => $form->createView(),
            'Makers' => $Makers,
            'Maker' => $Maker,
            'forms' => $formViews,
        ];
    }

    /**
     * Delete Maker.
     *
     * @param Request $request
     * @param Maker $Maker
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     *
     * @Route(
     *     "/%eccube_admin_route%/maker/{id}/delete",
     *     name="maker_admin_delete", requirements={"id":"\d+"},
     *     methods={"DELETE"}
     * )
     */
    public function delete(Request $request, Maker $Maker)
    {
        $this->isTokenValid();

        try {
            $this->makerRepository->delete($Maker);

            $this->addSuccess('maker.admin.delete.complete', 'admin');

            log_info('ブランド削除完了', ['Maker id' => $Maker->getId()]);
        } catch (\Exception $e) {
            log_info('ブランド削除エラー', ['Maker id' => $Maker->getId(), $e]);

            $message = trans('admin.delete.failed.foreign_key', ['%name%' => $Maker->getName()]);
            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('maker_admin_index');
    }

    /**
     * Move sort no with ajax.
     *
     * @param Request $request
     *
     * @return Response
     *
     * @throws \Exception
     *
     * @Route(
     *     "/%eccube_admin_route%/maker/move_sort_no",
     *     name="maker_admin_move_sort_no",
     *     methods={"POST"}
     * )
     */
    public function moveSortNo(Request $request)
    {
        if ($request->isXmlHttpRequest() && $this->isTokenValid()) {
            $sortNos = $request->request->all();
            foreach ($sortNos as $makerId => $sortNo) {
                $Maker = $this->makerRepository->find($makerId);
                $Maker->setSortNo($sortNo);
                $this->entityManager->persist($Maker);
            }
            $this->entityManager->flush();
        }

        return new Response();
    }
}

