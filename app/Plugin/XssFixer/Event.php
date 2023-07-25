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

namespace Plugin\XssFixer;

use Eccube\Common\EccubeConfig;
use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public static function getSubscribedEvents()
    {
        return ['@admin/index.twig' => 'alert'];
    }

    public function alert(TemplateEvent $event)
    {
        $dir = $this->eccubeConfig->get('eccube_theme_admin_dir').'/Order';
        $file = 'mail_confirm.twig';
        $path = $dir.'/'.$file;

        if (!file_exists($path)) {
            // 修正ファイル未設置の場合、警告を表示
            $event->addSnippet('@XssFixer/admin/danger.twig');
        }
    }
}
