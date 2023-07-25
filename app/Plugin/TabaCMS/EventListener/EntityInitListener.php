<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\EventListener;

use Plugin\TabaCMS\Entity\AbstractEntity;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Mapping as ORM;

/**
 *
 * https://symfony.com/doc/master/bundles/DoctrineBundle/entity-listeners.html
 * https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/events.html#entity-listeners
 */
class EntityInitListener
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * コンストラクタ
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->router = $this->container->get('router');
    }

    /**
     *  Entityがロードされたときにコールされ、EntityにServiceContainerとRouteをセットします。
     *
     * @param AbstractEntity $entity
     * @param LifecycleEventArgs $event
     *
     * @ORM\PostLoad
     */
    public function postLoad(AbstractEntity $entity, LifecycleEventArgs $event) {
        $entity->setContainer($this->container);
        $entity->setRouter($this->router);
    }
}