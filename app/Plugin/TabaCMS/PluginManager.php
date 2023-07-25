<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Entity\Type;

use Eccube\Plugin\AbstractPluginManager;

use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{

    /**
     * プラグインインストール時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function install(array $meta, ContainerInterface $container) {
    }

    /**
     * プラグイン削除時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function uninstall(array $meta, ContainerInterface $container) {
    }

    /**
     * プラグイン有効時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function enable(array $meta, ContainerInterface $container) {
        // プラグインデータディレクトリを作成します。
        if (($rootPath = $container->getParameter('kernel.project_dir'))) {
            $plugin_data_dir = $rootPath . Constants::PLUGIN_DATA_DIR;
            if (!file_exists($plugin_data_dir)) mkdir($plugin_data_dir,0775,true);
        } else {
            throw new \Exception('kernel.project_dir が取得が出来ませんでした。');
        }

        try {
            $em = $container->get('doctrine.orm.entity_manager');

            // 初期レコード設定
            $type = new Type();
            if (!$em->getRepository(Constants::$ENTITY['TYPE'])->get('news')) {
                $type->setDataKey('news');
                $type->setPublicDiv(Type::PUBLIC_DIV_PUBLIC);
                $type->setTypeName('新着情報');
                $em->getRepository(Constants::$ENTITY['TYPE'])->save($type);
            }
            $type = new Type();
            if (!$em->getRepository(Constants::$ENTITY['TYPE'])->get('blog')) {
                $type->setDataKey('blog');
                $type->setPublicDiv(Type::PUBLIC_DIV_PUBLIC);
                $type->setTypeName('ブログ');
                $em->getRepository(Constants::$ENTITY['TYPE'])->save($type);
            }
        } catch (\Exception $e) {
            log_error("TabaCMSプラグインの有効化中にエラーが発生しました。" . $e->getMessage());
        }
    }

    /**
     * プラグイン無効時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function disable(array $meta, ContainerInterface $container) {
    }

    /**
     * プラグイン更新時の処理
     *
     * @param array  $meta
     * @param ContainerInterface $container
     *
     * @throws \Exception
     */
    public function update(array $meta, ContainerInterface $container) {
    }
}
