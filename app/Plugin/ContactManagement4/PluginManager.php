<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4;

use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PluginManager.
 */
class PluginManager extends AbstractPluginManager
{
    const VERSION = '1.0.0';

    /**
     * Install the plugin.
     */
    public function install(array $meta, ContainerInterface $container)
    {
        dump('install '.self::VERSION);
    }

    /**
     * Update the plugin.
     */
    public function update(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        dump('update '.self::VERSION);
        $this->migration($entityManager->getConnection(), $meta['code']);
    }

    /**
     * Enable the plugin.
     */
    public function enable(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        dump('enable '.self::VERSION);
        $this->migration($entityManager->getConnection(), $meta['code']);
    }

    /**
     * Disable the plugin.
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine')->getManager();
        dump('disable '.self::VERSION);
    }

    /**
     * Uninstall the plugin.
     */
    public function uninstall(array $meta, ContainerInterface $container)
    {
        dump('uninstall '.self::VERSION);
        $entityManager = $container->get('doctrine')->getManager();
        $this->migration($entityManager->getConnection(), $meta['code'], 0);
    }
}
