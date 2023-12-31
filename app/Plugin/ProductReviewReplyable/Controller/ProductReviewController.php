<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductReviewReplyable\Controller;

use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\ProductStatus;
use Eccube\Entity\Product;
use Plugin\ProductReviewReplyable\Entity\ProductReview;
use Plugin\ProductReviewReplyable\Entity\ProductReviewStatus;
use Plugin\ProductReviewReplyable\Form\Type\ProductReviewType;
use Plugin\ProductReviewReplyable\Repository\ProductReviewRepository;
use Plugin\ProductReviewReplyable\Repository\ProductReviewStatusRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ProductReviewController front.
 */
class ProductReviewController extends AbstractController
{
    /**
     * @var ProductReviewStatusRepository
     */
    private $productReviewStatusRepository;

    /**
     * @var ProductReviewRepository
     */
    private $productReviewRepository;

    /**
     * ProductReviewController constructor.
     *
     * @param ProductReviewStatusRepository $productStatusRepository
     * @param ProductReviewRepository $productReviewRepository
     */
    public function __construct(
        ProductReviewStatusRepository $productStatusRepository,
        ProductReviewRepository $productReviewRepository
    ) {
        $this->productReviewStatusRepository = $productStatusRepository;
        $this->productReviewRepository = $productReviewRepository;
    }

    /**
     * @Route("/product_review/{id}/review", name="product_review_index", requirements={"id" = "\d+"})
     *
     * @param Request $request
     * @param Product $Product
     *
     * @return RedirectResponse|Response
     */
    public function index(Request $request, Product $Product)
    {
        if (!$this->session->has('_security_admin') && $Product->getStatus()->getId() !== ProductStatus::DISPLAY_SHOW) {
            log_info('Product review', ['status' => 'Not permission']);

            throw new NotFoundHttpException();
        }

        $ProductReview = new ProductReview();
        $form = $this->createForm(ProductReviewType::class, $ProductReview);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var $ProductReview ProductReview */
            $ProductReview = $form->getData();

            switch ($request->get('mode')) {
                case 'confirm':
                    log_info('Product review config confirm');

                    return $this->render('@ProductReviewReplyable/default/confirm.twig', [
                        'form' => $form->createView(),
                        'Product' => $Product,
                        'ProductReview' => $ProductReview,
                    ]);
                    break;

                case 'complete':
                    log_info('Product review complete');
                    if ($this->isGranted('ROLE_USER')) {
                        $Customer = $this->getUser();
                        $ProductReview->setCustomer($Customer);
                    }
                    $ProductReview->setProduct($Product);
                    $ProductReview->setStatus($this->productReviewStatusRepository->find(ProductReviewStatus::HIDE));
                    $this->entityManager->persist($ProductReview);
                    $this->entityManager->flush($ProductReview);

                    log_info('Product review complete', ['id' => $Product->getId()]);

                    return $this->redirectToRoute('product_review_complete', ['id' => $Product->getId()]);
                    break;

                case 'back':
                    // 確認画面から投稿画面へ戻る
                    break;

                default:
                    // do nothing
                    break;
            }
        }

        return $this->render('@ProductReviewReplyable/default/index.twig', [
            'Product' => $Product,
            'ProductReview' => $ProductReview,
            'form' => $form->createView(),
        ]);
    }

    /**
     * Complete.
     *
     * @Route("/product_review/{id}/complete", name="product_review_complete", requirements={"id" = "\d+"})
     * @Template("@ProductReviewReplyable/default/complete.twig")
     *
     * @param $id
     *
     * @return array
     */
    public function complete($id)
    {
        return ['id' => $id];
    }
}
