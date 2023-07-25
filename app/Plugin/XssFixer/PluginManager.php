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

use Eccube\Common\Constant;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $dir = $container->getParameter('eccube_theme_admin_dir').'/Order';
        $file = 'mail_confirm.twig';
        $path = $dir.'/'.$file;

        $fs = new Filesystem();
        if ($fs->exists($dir.'/'.$file)) {
            // 既にファイルが存在する場合はバックアップ
            $fs->rename($path, $path.'.'.time());
        } else {
            $fs->mkdir($dir);
        }

        switch (Constant::VERSION) {
            case '4.0.0':
            case '4.0.1':
            case '4.0.2':
            case '4.0.3':
            case '4.0.4':
            case '4.0.5':
                $patchDir = __DIR__.'/Resource/patch';
                $patchPath = $patchDir.'/'.Constant::VERSION.'/'.$file;
                $fs->copy($patchPath, $path, true);
                break;
            default:
                // バージョンがマッチしない場合は4.0.5の修正ファイルを強制適用
                $patchDir = __DIR__.'/Resource/patch';
                $patchPath = $patchDir.'/4.0.5/'.$file;
                $fs->copy($patchPath, $path, true);

                break;
        }
    }
}
