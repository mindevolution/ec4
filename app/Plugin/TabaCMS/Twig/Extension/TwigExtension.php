<?php
/*
  * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\TabaCMS\Twig\Extension;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Common\DataHolder;
use Plugin\TabaCMS\Util\Util;
use Plugin\TabaCMS\Repository\TypeRepository;
use Plugin\TabaCMS\Repository\PostRepository;
use Plugin\TabaCMS\Repository\CategoryRepository;

use Eccube\Request\Context;
use Eccube\Common\EccubeConfig;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RequestStack;

use Knp\Component\Pager\Paginator;

class TwigExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var Paginator
     */
    private $paginator;

    /**
     * @var array
     */
    private $cached;

    /**
     * @var Context
     */
    private $requestContext;

    /**
     * @var TypeRepository
     */
    private $typeRepo;

    /**
     * @var PostRepository
     */
    private $postRepo;

    /**
     * @var CategoryRepository
     */
    private $categoryRepo;

    /**
     * @var Packages
     */
    private $assetPackage;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    /**
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * コンストラクタ
     *
     * @param ContainerInterface $container
     * @param \Twig_Environment $twig
     * @param RequestStack $requestStack
     * @param Paginator $paginator
     * @param Context $requestContext
     * @param TypeRepository $typeRepo
     * @param PostRepository $postRepo
     * @param CategoryRepository $categoryRepo
     * @param Packages $assetPackages
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param EccubeConfig $eccubeConfig
     * @param RouterInterface $router
     */
    public function __construct(
        ContainerInterface $container,
        \Twig_Environment $twig,
        RequestStack $requestStack,
        Paginator $paginator,
        Context $requestContext,
        TypeRepository $typeRepo,
        PostRepository $postRepo,
        CategoryRepository $categoryRepo,
        Packages $assetPackages,
        CsrfTokenManagerInterface $csrfTokenManager,
        EccubeConfig $eccubeConfig,
//        UrlGeneratorInterface $router
        RouterInterface $router
    ) {
        $this->container = $container;
        $this->twig = $twig;
        $this->requestStack = $requestStack;
        $this->paginator = $paginator;
        $this->requestContext = $requestContext;
        $this->typeRepo = $typeRepo;
        $this->postRepo = $postRepo;
        $this->categoryRepo = $categoryRepo;
        $this->assetPackage = $assetPackages;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->eccubeConfig = $eccubeConfig;
        $this->router = $router;

        $this->twig->addGlobal(Constants::PLUGIN_CODE.'Constants', new Constants());
        $this->twig->addGlobal(Constants::PLUGIN_CATEGORY_NAME.'Status', new Constants());
    }

    /**
     * Returns a list of functions to add to the existing list.
     *
     * @return array An array of functions
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction(Constants::PLUGIN_CODE . 'Post', array($this, 'post')),
            new \Twig_SimpleFunction(Constants::PLUGIN_CODE . 'PostList', array($this, 'postList')),
            new \Twig_SimpleFunction(Constants::PLUGIN_CODE . 'Asset', array($this, 'asset')),
            new \Twig_SimpleFunction(Constants::PLUGIN_CODE . 'IsAssetLoad', array($this, 'isAssetLoad')),
            new \Twig_SimpleFunction(Constants::PLUGIN_CODE . 'Widget', array($this, 'widget')),
        );
    }

    /**
     * Name of this extension
     *
     * @return string
     */
    public function getName()
    {
        return Constants::PLUGIN_CODE_LC;
    }

    public function widget($widget_name,$options = array()) {
        $data_holder = DataHolder::getInstance();

        // オプション
        $options = array_merge(array(
            'type_data_key' => $data_holder->getData(Constants::DATA_HOLDER_KEY_TYPE_DK),
        ),$options);

        //
        // カテゴリーリスト
        //
        if ($widget_name == "category") {
            // 投稿タイプの取得
            $type = null;
            if (!$options['type_data_key'] || !($type = $this->typeRepo->get($options['type_data_key']))) {
                return;
            }

            // カテゴリーリストの取得
            $conditions = array();
            $conditions['is_public'] = true;
            if (!empty($type)) $conditions['type_id'] = $type->getTypeId();
            $category_list = $this->categoryRepo->getList($conditions);

            $file_name = 'widget_' . $widget_name . '.twig';
            return $this->twig->render(Util::getTemplatePath($file_name,array(),$this->container),array(
                'type' => $type,
                'options' => $options,
                'category_list' => $category_list,
            ));
        }

        //
        // 投稿リスト , 投稿
        //
        else if ($widget_name == "list") {
            // 投稿タイプの取得
            $type = null;
            if (!$options['type_data_key'] || !($type = $this->typeRepo->get($options['type_data_key']))) {
                return;
            }

            $file_name = 'widget_' . $widget_name . '.twig';
            return $this->twig->render(Util::getTemplatePath($file_name,array($options['type_data_key']),$this->container),array(
                'type' => $type,
                'options' => $options,
            ));
        }
    }

    public function isAssetLoad($key) {
        $data_holder = DataHolder::getInstance();
        if ($data_holder->getData("IS_LOADED_" . $key)) {
            return true;
        } else {
            $data_holder->setData("IS_LOADED_" . $key,true);
            return false;
        }
    }

    /**
     *
     * @param string $file_name
     * @param string $asset_type script|style|img
     * @param boolean $once
     * @param array $attributes tag attribute
     * @return string|NULL
     */
    public function asset($file_name,$asset_type = null,$once = true,$attributes = []) {
        if (!$once || ($once && !$this->isAssetLoad($file_name))) {
            $uri = $this->router->generate(Constants::FRONT_BIND_PREFIX . '_assets',['file' => $file_name]);
            if ($asset_type == "script") {
                return '<script src="' . $uri . '"></script>';
            } else if ($asset_type == "style") {
                return '<link rel="stylesheet" href="' . $uri . '">';
            } else if ($asset_type == "img") {
                return '<img src="' . $uri . '">';
            } else {
                return $uri;
            }
        } else {
            return null;
        }
    }

    public function post($options = array()) {
        $data_holder = DataHolder::getInstance();

        // オプション
        $options = array_merge(array(
            'type_data_key' => $data_holder->getData(Constants::DATA_HOLDER_KEY_TYPE_DK),
            'data_key' => $data_holder->getData(Constants::DATA_HOLDER_KEY_POST_DK),
        ),$options);

        $post = $this->postRepo->get([
            "data_key" => $options['data_key'],
            "is_public" => true
        ]);

        return $post;
    }

    /**
     *
     *
     * @param array $options
     * @return void|\Knp\Component\Pager\Pagination\PaginationInterface
     */
    public function postList($options = array()) {
        $data_holder = DataHolder::getInstance();

        $param = $this->requestStack->getCurrentRequest()->query;

        $options = array_merge(array(
            'type_data_key' => $data_holder->getData(Constants::DATA_HOLDER_KEY_TYPE_DK),
            'page_count' => Constants::DEFAULT_PAGE_COUNT,
        ),$options);

        // 投稿タイプの取得
        $type = null;
        if (!$options['type_data_key'] || !($type = $this->typeRepo->get($options['type_data_key']))) {
            return;
        }

        // 投稿リストの取得
        $qb_post_list = $this->postRepo->createListQueryBuilder(array(
            "type_id" => $type->getTypeId(),
            "category_id" => $param->get("category_id"),
            "is_public" => true,
        ));

        // ページネーション
        $page_no = 1;
        if ($param->get('pageno')) $page_no = $param->get('pageno');
        $post_list = $this->paginator->paginate(
            $qb_post_list,
            $page_no,
            $options['page_count'],
            array('wrap-queries' => true)
            );

        return $post_list;
    }
}
