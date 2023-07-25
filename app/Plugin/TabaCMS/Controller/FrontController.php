<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Controller;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Common\DataHolder;
use Plugin\TabaCMS\Util\Util;
use Plugin\TabaCMS\Repository\TypeRepository;
use Plugin\TabaCMS\Repository\PostRepository;
use Plugin\TabaCMS\Repository\CategoryRepository;

use Eccube\Controller\AbstractController;
use Eccube\Repository\PageRepository;
use Eccube\Repository\LayoutRepository;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FrontController extends AbstractController
{
    /**
     *
     * @var TypeRepository
     */
    private $typeRepo;

    /**
     *
     * @var PostRepository
     */
    private $postRepo;

    /**
     * @var CategoryRepository
     */
    private $categoryRepo;

    /**
     * @var PageRepository
     */
    private $pageRepo;

    /**
     * @var LayoutRepository
     */
    private $layoutRepo;

    /**
     * コンストラクタ
     *
     * @param TypeRepository $typeRepo
     * @param PostRepository $postRepo
     */
    public function __construct(
        TypeRepository $typeRepo,
        PostRepository $postRepo,
        CategoryRepository $categoryRepo,
        PageRepository $pageRepo,
        LayoutRepository $layoutRepo
    ) {
        $this->typeRepo = $typeRepo;
        $this->postRepo = $postRepo;
        $this->categoryRepo = $categoryRepo;
        $this->pageRepo = $pageRepo;
        $this->layoutRepo = $layoutRepo;
    }

    /**
     * index
     *
     * @param Request $request
     * @return Response
     */
    public function index(Request $request) {
    }

    /**
     *
     * @param Request $request
     *
     * @Template("@TabaCMS/default/test.twig")
     */
    public function test(Request $request)
    {
    }

    /**
     * リストページと詳細ページの振り分けをします。
     *
     * @param Request $request
     * @param string $data_key
     */
    public function prepare(Request $request, $data_key = null)
    {
        // リスト
        if (empty($data_key)) {
            return $this->postList($request);
        }
        // 投稿
        else {
            return $this->postDetail($request, $data_key);
        }
    }

    /**
     * 投稿リストページ
     *
     * @param Request $request
     */
    public function postList(Request $request)
    {
        // listページを実行中であることを保存
        DataHolder::getInstance()->setData(Constants::DATA_HOLDER_KEY_PAGE, Constants::PAGE_LIST);

        // 投稿タイプのデータキー取得
        $type_data_key = "";
        $uri = $request->getRequestUri();
        if (strpos($uri, '?'))
            $uri = strstr($uri, '?', true);
        if (($path = explode("/", $uri))) {
            $type_data_key = end($path);
        } else {
            throw new NotFoundHttpException();
        }

        if ($type = $this->typeRepo->get($type_data_key)) {
            // 投稿タイプのデータキーを保存
            DataHolder::getInstance()->setData(Constants::DATA_HOLDER_KEY_TYPE_DK, $type_data_key);

            // テンプレートファイル
            $template_file = Util::getTemplatePath('list.twig',[$type_data_key],$this->container);

            // ページオブジェクト生成
            $page = $this->pageRepo->newPage();

            // ページレイアウト生成
            $layout = $this->layoutRepo->find(2);

            // ページタイトル
            $page_title = $type->getTypeName();
            if ($request->get("category_id") && ($category = $this->categoryRepo->find($request->get("category_id")))) {
                $page_title .= " - ";
                $page_title .= $category->getCategoryName();
            }

            return $this->render($template_file,[
                'Layout' => $layout,
                'Page' => $page,
                'subtitle' => $page_title,
                'type' => $type
            ]);
        } else {
            throw new NotFoundHttpException();
        }
    }

    /**
     * 投稿ページ
     *
     * @param Request $request
     */
    public function postDetail(Request $request, $data_key)
    {
        // postページを実行中であることを保存
        DataHolder::getInstance()->setData(Constants::DATA_HOLDER_KEY_PAGE, Constants::PAGE_POST);

        // 投稿データ取得
        $post = null;
        if (! $data_key || ! ($post = $this->postRepo->get(array(
            "data_key" => $data_key,
            "is_public" => true
        )))) {
            throw new NotFoundHttpException();
        }

        // 投稿タイプのデータキー取得
        $type_data_key = $post->getType()->getDataKey();

        // 投稿タイプ、投稿のデータキーを保存
        DataHolder::getInstance()->setData(Constants::DATA_HOLDER_KEY_TYPE_DK, $type_data_key);
        DataHolder::getInstance()->setData(Constants::DATA_HOLDER_KEY_POST_DK, $data_key);

        // テンプレートファイル
        $template_file = Util::getTemplatePath('post.twig',[$type_data_key,$data_key],$this->container);

        // ページオブジェクト生成
        $page = $this->pageRepo->newPage();
        $page->setAuthor($post->getMetaAuthor());
        $page->setDescription($post->getMetaDescription());
        $page->setKeyword($post->getMetaKeyword());
        $page->setMetaRobots($post->getMetaRobots());
        $page->setMetaTags($post->getMetaTags());

        // ページレイアウト生成
        $layout = $this->layoutRepo->find(2);

        return $this->render($template_file, [
            'Layout' => $layout,
            'Page' => $page,
            'subtitle' => strip_tags($post->getTitle()),
            'type' => $post->getType()
        ]);
    }

    /**
     * JS,CSS,画像などを出力します。
     *
     * @param Request $request
     */
    public function assets(Request $request, $file)
    {
        $file = Util::getTemplatePath($file,array(),$this->container);
        if (file_exists($file)) {
            log_debug("[ASSETS] [FILE] " . $file);

            // 拡張子によりMIMEを設定します。
            $suffixes = explode(".",$file);
            $suffix = end($suffixes);
            $suffix_def = array(
                "jpeg" => "image/jpg",
                "jpg" => "image/jpg",
                "gif" => "image/gif",
                "png" => "image/png",
                "svg" => "image/svg+xml",
                "js" => "application/x-javascript",
                "css" => "text/css",
            );
            if (in_array($suffix,array_keys($suffix_def))) {
                $response = new BinaryFileResponse($file);
                $response->headers->set('Content-Type',$suffix_def[$suffix]);
                // キャッシュするヘッダーを出力する設定をします
                if ($this->container->has(Constants::CONTAINER_KEY_NAME)) {
                    $this->container->get(Constants::CONTAINER_KEY_NAME)->set(Constants::HTTP_CACHE_STATUS,true);
                }
                return $response;
            } else {
                throw new NotFoundHttpException();
            }
        } else {
            throw new NotFoundHttpException();
        }
    }

}
