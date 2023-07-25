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

namespace Plugin\Maker4;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Eccube\Event\TemplateEvent;

class MakerEvent implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Product/detail.twig' => ['onTemplateProductDetail', 10],
        ];
    }

    /**
     * Append JS to display maker
     *
     * @param TemplateEvent $templateEvent
     */
    public function onTemplateProductDetail(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@Maker4/default/maker.twig');
    }
}
