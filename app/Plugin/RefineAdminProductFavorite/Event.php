<?php
/*
 * This file is part of Refine
 *
 * Copyright(c) 2022 Refine Co.,Ltd. All Rights Reserved.
 *
 * https://www.re-fine.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\RefineAdminProductFavorite;

use Eccube\Entity\Product;
use Eccube\Event\TemplateEvent;
use Knp\Bundle\PaginatorBundle\Pagination\SlidingPagination;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{

    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/index.twig' => 'productList',
        ];
    }

    public function productList(TemplateEvent $event)
    {
        /** @var  $Pagination SlidingPagination */
        $Pagination = $event->getParameter('pagination');
        /** @var $Products Product[] */
        $Products = $Pagination->getItems();
        // product_idとお気に入り数の配列を作成する
        $favoriteCountList = [];
        foreach ($Products as $product) {
            $favoriteCountList[$product->getId()] = $product->getCustomerFavoriteProductCount();
        }

        $twig = '@RefineAdminProductFavorite/admin/product_list.twig';
        $event->setParameter('favoriteCountList', $favoriteCountList);
        $event->addSnippet($twig);
    }
}
