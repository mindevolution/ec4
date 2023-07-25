<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\EventListener;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Common\UserConfig;
use Plugin\TabaCMS\Repository\TypeRepository;
use Plugin\TabaCMS\Repository\PostRepository;
use Plugin\TabaCMS\Entity\Type;
use Plugin\TabaCMS\Entity\Post;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\EventListener\RouterListener;

use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RequestContext;

use Eccube\Request\Context;

use Doctrine\ORM\EntityManagerInterface;

class DynamicRoutingListener extends RouterListener
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
     * @var RouteCollection
     */
    private $routes;

    /**
     * @var TypeRepository
     */
    private $typeRepo;

    /**
     * @var PostRepository
     */
    private $postRepo;

    /**
     * @var EntityManagerInterface
     */
    private $em;

    /**
     * コンストラクタ
     *
     * @param ContainerInterface $container
     * @param RequestStack $requestStack
     */
    public function __construct(ContainerInterface $container, RequestStack $requestStack)
    {
        $this->container = $container;
        $this->em = $this->container->get('doctrine.orm.entity_manager');
        $this->router = $this->container->get('router');
        $this->routes = $this->router->getRouteCollection();
        $this->typeRepo = $this->em->getRepository(Type::class);
        $this->postRepo = $this->em->getRepository(Post::class);
        $this->load();
        $requestContext = new RequestContext();
        $requestContext->fromRequest($requestStack->getCurrentRequest());
        parent::__construct(new UrlMatcher($this->routes,$requestContext),$requestStack);
    }

    private function load()
    {
        $front_uri_prefix = UserConfig::getInstance()->get("front_uri_prefix");

        // 投稿タイプ毎に動的にルーティングを設定します。
        $type_list = $this->typeRepo->findAll();
        foreach ($type_list as $type) {
            // フロント
            if ($type->getPublicDiv() == Type::PUBLIC_DIV_PUBLIC) {
                // 投稿リスト
                $this->routes->add(Constants::FRONT_BIND_PREFIX . '_list_' . $type->getTypeId(),
                    new Route(
                        $front_uri_prefix . '/' . $type->getDataKey(),
                        ['_controller' => Constants::FRONT_CONTROLLER . "::prepare"]
                    )
                );

                // 投稿データ
                $this->routes->add(Constants::FRONT_BIND_PREFIX . '_post_' . $type->getTypeId(),
                    new Route(
                        $front_uri_prefix . '/' . $type->getDataKey() . '/{data_key}',
                        ['_controller' => Constants::FRONT_CONTROLLER . "::prepare"],
                        ['data_key' => '.+']
                    )
                );

                // ルーティングの上書き設定されている投稿リストを取得します。
                $post_list = $this->postRepo->getList(array(
                    "type_id" => $type->getTypeId(),
                    "is_overwrite_route" => true,
                ));
                foreach ($post_list as $post) {
                    if ($post->getOverwriteRoute()) {
                        $this->routes->add($post->getOverwriteRoute(),
                            new Route(
                                $front_uri_prefix . '/' . $type->getDataKey() . '/' . $post->getDataKey(),
                                ['_controller' => Constants::FRONT_CONTROLLER . "::prepare"]
                            )
                        );
                    }
                }
            }
        }

        // CSS、JS、画像など読み込み
        $this->routes->add(Constants::FRONT_BIND_PREFIX . '_assets',new Route(Constants::FRONT_URI_PREFIX . '/assets/{file}',['_controller' => Constants::FRONT_CONTROLLER . "::assets"],['file' => '[a-zA-Z0-9\/\-\_\.\s]+']));
    }

    /**
     * {@inheritDoc}
     * @see \Symfony\Component\HttpKernel\EventListener\RouterListener::onKernelRequest()
     */
    public function onKernelRequest(GetResponseEvent $event) {
        try {
            parent::onKernelRequest($event);
        }
        // 404のハンドリングをEC-CUBE側のロジックに任すため、エラーを握りつぶす
        catch (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e) {
        }
    }
}