<?php

/*
 * This file is part of DesignTag4
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DesignTag4;

use Eccube\Common\Constant;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\TagRepository;
use Plugin\DesignTag4\Service\TwigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @var TwigService
     */
    private $twigService;

    /**
     * @var TagRepository
     */
    private $tagRepository;

    public function __construct(
        TwigService $twigService,
        TagRepository $tagRepository
    ) {
        $this->tagRepository = $tagRepository;
        $this->twigService = $twigService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/tag.twig' => 'onAdminProductTagTwig',
            'Product/detail.twig' => 'onProductDetailTwig',
            'Product/list.twig' => 'onProductListTwig',
        ];
    }

    public function onAdminProductTagTwig(TemplateEvent $event)
    {
        $event->addSnippet('@DesignTag4/admin/Product/tag.twig');
        if (version_compare(Constant::VERSION, '4.0.3') <= 0) {
            $event->addSnippet('@DesignTag4/admin/Product/tag_lte_4_0_3.twig');
        }
    }

    public function onProductDetailTwig(TemplateEvent $event)
    {
        $Product = $event->getParameter('Product');
        $DesignTags = $Product->getTags();
        $event->setParameter('DesignTags', $DesignTags);
        $event->addAsset('@DesignTag4/tag_detail_css.twig');
    }

    public function onProductListTwig(TemplateEvent $event)
    {
//        $insert = $this->twigService->getTwigFile('@DesignTag4/tag_list.twig');
//        $this->twigService->setSource($event->getSource());
//        $this->twigService->insert('<p>{{ Product.name }}</p>', $insert);
//        $event->setSource($this->twigService->getSource());

        $DesignTags = $this->tagRepository->findBy(['show_product_list_flg' => true]);
        $event->setParameter('DesignTags', $DesignTags);
        $event->addAsset('@DesignTag4/tag_list_css.twig');
    }
}
