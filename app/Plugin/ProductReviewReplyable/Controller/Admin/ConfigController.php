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

namespace Plugin\ProductReviewReplyable\Controller\Admin;

use Plugin\ProductReviewReplyable\Form\Type\Admin\ProductReviewConfigType;
use Plugin\ProductReviewReplyable\Repository\ProductReviewConfigRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ConfigController.
 */
class ConfigController extends \Eccube\Controller\AbstractController
{
    /**
     * @Route("/%eccube_admin_route%/product_review/config", name="product_review_replyable_admin_config")
     * @Template("@ProductReviewReplyable/admin/config.twig")
     *
     * @param Request $request
     * @param ProductReviewConfigRepository $configRepository
     *
     * @return array
     */
    public function index(Request $request, ProductReviewConfigRepository $configRepository)
    {
        $Config = $configRepository->get();
        $form = $this->createForm(ProductReviewConfigType::class, $Config);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $Config = $form->getData();
            $this->entityManager->persist($Config);
            $this->entityManager->flush($Config);

            log_info('Product review config', ['status' => 'Success']);
            $this->addSuccess('product_review.admin.save.complete', 'admin');

            return $this->redirectToRoute('product_review4_admin_config');
        }

        return [
            'form' => $form->createView(),
        ];
    }
}
