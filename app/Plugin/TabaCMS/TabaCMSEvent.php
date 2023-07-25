<?php
/*
 * Copyright (C) 2018 SPREAD WORKS Inc.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Repository\TypeRepository;
use Plugin\TabaCMS\Repository\PostRepository;

use Eccube\Event\TemplateEvent;
use Eccube\Request\Context;
use Eccube\Common\EccubeConfig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use Doctrine\ORM\EntityManagerInterface;

class TabaCMSEvent implements EventSubscriberInterface
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     *
     * @var Context
     */
    private $requestContext;

    /**
     *
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var array
     */
    private $eccubeConfig;

    /**
     * @var TypeRepository
     */
    private $typeRepo;

    /**
     * @var PostRepository
     */
    private $postRepo;


    public function __construct(
        ContainerInterface $container,
        EntityManagerInterface $entityManager,
        EventDispatcherInterface $eventDispatcher,
        Context $requestContext,
        CsrfTokenManagerInterface $csrfTokenManager,
        EccubeConfig $eccubeConfig,
        TypeRepository $typeRepo,
        PostRepository $postRepo)
    {
        $this->container = $container;
        $this->entityManager = $entityManager;
        $this->eventDispatcher = $eventDispatcher;
        $this->requestContext = $requestContext;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->typeRepo = $typeRepo;
        $this->postRepo = $postRepo;
    }

    /**
     *
     * {@inheritdoc}
     *
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => [
                [
                    'onKernelController',
                    100000000
                ],
            ]
        ];
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {
        //
        // 管理画面イベント
        //
        if ($this->requestContext->isAdmin()) {
            //
            // テンプレートイベント
            //
            if ($event->getRequest()->attributes->has('_template')) {

                $template = $event->getRequest()->attributes->get('_template');

                $this->eventDispatcher->addListener($template->getTemplate(), function (TemplateEvent $templateEvent) {
                    // 管理画面のナビゲーションにtaba app のメニューを差し込みます。
                    $taba = $this->container->get(Constants::CONTAINER_KEY_NAME);
                    if (!$taba->get(Constants::PLUGIN_CATEGORY_ID . ".menu")) {
                        $templateEvent->addSnippet('@TabaCMS/admin/snippet/nav_taba_app.twig');
                        $taba->set(Constants::PLUGIN_CATEGORY_ID . ".menu",true);
                    }

                    // Taba CMSのメニューを差し込みます。
                    $templateEvent->setParameter("type_list",$this->typeRepo->findAll()); // 投稿タイプリストをセット
                    $templateEvent->addSnippet('@TabaCMS/admin/snippet/nav.twig');
                });
            }
        }
    }
}
