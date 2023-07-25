<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Entity;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class AbstractEntity extends \Eccube\Entity\AbstractEntity
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @return RouterInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param RouterInterface
     */
    public function setRouter(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @return \Symfony\Component\DependencyInjection\ContainerInterface
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
     */
    public function setContainer($container)
    {
        $this->container = $container;
    }
}