<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Controller;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Knp\Component\Pager\Paginator;
use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Entity\Category;
use Plugin\TabaCMS\Entity\Post;
use Plugin\TabaCMS\Entity\Type;
use Plugin\TabaCMS\Form\Type\PostType;
use Plugin\TabaCMS\Form\Type\TypeType;
use Plugin\TabaCMS\Form\Type\CategoryType;
use Plugin\TabaCMS\Repository\CategoryRepository;
use Plugin\TabaCMS\Repository\PostRepository;
use Plugin\TabaCMS\Repository\TypeRepository;
use Plugin\TabaCMS\Util\Util;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Finder\Finder;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * 管理画面用コントローラー
 *
 * @Route(Plugin\TabaCMS\Common\Constants::ADMIN_URI_PREFIX,name=Plugin\TabaCMS\Common\Constants::ADMIN_BIND_PREFIX)
 */
class AdminController extends AbstractController
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
     *
     * @var CategoryRepository
     */
    private $categoryRepo;

    /**
     *
     * @var CsrfTokenManagerInterface
     */
    private $csrfTokenManager;

    /**
     * @var AuthorizationCheckerInterface
     */
    private $authCheck;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStore;

    /**
     * コンストラクタ
     *
     * @param TypeRepository $typeRepo
     * @param PostRepository $postRepo
     * @param CategoryRepository $categoryRepo
     * @param CsrfTokenManagerInterface $csrfTokenManager
     * @param AuthorizationCheckerInterface $authCheck
     * @param TokenStorageInterface $tokenStore
     */
    public function __construct(
        TypeRepository $typeRepo,
        PostRepository $postRepo,
        CategoryRepository $categoryRepo,
        CsrfTokenManagerInterface $csrfTokenManager,
        AuthorizationCheckerInterface $authCheck,
        TokenStorageInterface $tokenStore
    ){
        $this->typeRepo = $typeRepo;
        $this->postRepo = $postRepo;
        $this->categoryRepo = $categoryRepo;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->authCheck = $authCheck;
        $this->tokenStore = $tokenStore;
    }

    /**
     * index
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/",name="_index")
     */
    public function index(Request $request)
    {
        return $this->redirectToRoute(Constants::ADMIN_BIND_PREFIX . '_type_list');
    }

    /**
     * 投稿タイプ / リスト
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/type_list",name="_type_list")
     * @Template("@TabaCMS/admin/type_list.twig")
     */
    public function type_list(Request $request)
    {
        // 投稿タイプリスト取得
        $list = $this->typeRepo->getList();

        return [
            'list' => $list
        ];
    }

    /**
     * 投稿タイプ / 新規登録・編集
     *
     * @param Request $request
     * @param integer $type_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/type_new",name="_type_new")
     * @Route("/type_edit/{type_id}",name="_type_edit",requirements={"type_id", "\d+"})
     * @Template("@TabaCMS/admin/type_edit.twig")
     */
    public function type_edit(Request $request, $type_id = null)
    {
        $oldDataKey = null;
        $oldPublicDiv = null;

        // 投稿タイプ操作オブジェクトの生成
        $type = null;
        if ($type_id) {
            if (! ($type = $this->typeRepo->find($type_id))) {
                throw new NotFoundHttpException();
            }
            $oldDataKey = $type->getDataKey();
            $oldPublicDiv = $type->getPublicDiv();
        } else {
            $type = new Type(true);
        }

        // フォームの生成
        $builder = $this->formFactory->createBuilder(TypeType::class, $type);
        $form = $builder->getForm();

        // 登録・変更実行
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($this->typeRepo->save($type)) {
                    // 新規、データキーの変更、公開区分の変更があった場合にキャッシュクリアします
                    if ($type_id == null || $oldDataKey != $type->getDataKey() || $oldPublicDiv != $type->getPublicDiv()) {
                        $this->routingCacheClear();
                    }
                    $this->addSuccess('admin.common.save_complete', 'admin');
                    return $this->redirectToRoute(Constants::ADMIN_BIND_PREFIX . '_type_edit', ['type_id' => $type->getTypeId()]);
                } else {
                    $this->addError('admin.common.save_error', 'admin');
                }
            } else {
                $this->addError('admin.common.save_error', 'admin');
            }
        }

        return [
            'form' => $form->createView(),
            'type' => $type
        ];
    }

    /**
     * 投稿タイプ / 削除
     *
     * @param Request $request
     * @param integer $type_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/type_delete/{type_id}",name="_type_delete",requirements={"type_id", "\d+"})
     */
    public function type_delete(Request $request, $type_id)
    {
        // 投稿タイプ操作オブジェクトの生成
        $status = false;
        if (! empty($type_id) && ($type = $this->typeRepo->find($type_id))) {
            $status = $this->typeRepo->delete($type);
        } else {
            throw new NotFoundHttpException();
        }
        if ($status) {
            $this->routingCacheClear(); // キャッシュクリア
            $this->addSuccess('admin.common.delete_complete', 'admin');
        } else {
            $this->addError('admin.common.delete_error', 'admin');
        }
        return $this->redirectToRoute(Constants::ADMIN_BIND_PREFIX . '_type_list');
    }

    /**
     * カテゴリー / リスト
     *
     * @param Request $request
     * @param integer $type_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/category_list/{type_id}",name="_category_list",requirements={"type_id", "\d+"})
     * @Template("@TabaCMS/admin/category_list.twig")
     */
    public function category_list(Request $request, $type_id = null)
    {
        // 投稿タイプID
        if (empty($type_id)) {
            $path = explode("/", $request->getRequestUri());
            $type_id = end($path);
        }

        // 投稿タイプ取得
        $type = null;
        if (! ($type = $this->typeRepo->find($type_id))) {
            throw new NotFoundHttpException();
        }

        // カテゴリーリスト取得
        $list = $this->categoryRepo->getList(array(
            'type_id' => $type_id
        ));

        return [
            'csrf_token' => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(), // CSRFトークン文字列
            'type' => $type,
            'list' => $list
        ];
    }

    /**
     * カテゴリー / 新規登録・編集
     *
     * @param Request $request
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/category_new/{type_id}",name="_category_new",requirements={"type_id", "\d+"})
     * @Route("/category_edit/{type_id}/{category_id}",name="_category_edit",requirements={"type_id", "\d+","category_id", "\d+"})
     * @Template("@TabaCMS/admin/category_edit.twig")
     */
    public function category_edit(Request $request, $type_id, $category_id = null)
    {
        // 投稿タイプ取得
        $type = null;
        if (! ($type = $this->typeRepo->find($type_id))) {
            throw new NotFoundHttpException();
        }

        // カテゴリー操作オブジェクトの生成
        $category = null;
        if ($category_id) {
            if (! ($category = $this->categoryRepo->find($category_id))) {
                throw new NotFoundHttpException();
            }
        } else {
            $category = new Category(true);
        }
        $category->setTypeId($type_id);
        $category->setType($type);

        // フォームの生成
        $builder = $this->formFactory->createBuilder(CategoryType::class, $category);
        $form = $builder->getForm();

        // 登録・変更実行
        if ('POST' === $request->getMethod()) {
            $form->handleRequest($request);
            if ($form->isValid()) {
                if ($this->categoryRepo->save($category)) {
                    $this->addSuccess('admin.common.save_complete', 'admin');
                    return $this->redirectToRoute(Constants::ADMIN_BIND_PREFIX . '_category_edit', array(
                        'type_id' => $type_id,
                        'category_id' => $category->getCategoryId()
                    ));
                } else {
                    $this->addError('admin.common.save_error', 'admin');
                }
            }
        }

        return [
            'csrf_token' => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(), // CSRFトークン文字列
            'form' => $form->createView(),
            'type' => $type,
            'category' => $category
        ];
    }

    /**
     * カテゴリー / 削除
     *
     * @param Request $request
     * @param
     *            integer ,$category_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/category_delete/{category_id}",name="_category_delete",requirements={"category_id", "\d+"})
     */
    public function category_delete(Request $request, $category_id)
    {
        // 投稿タイプ操作オブジェクトの生成
        $status = false;
        $category = $this->categoryRepo->find($category_id);
        $type_id = $category->getTypeId();
        if (! empty($category)) {
            $status = $this->categoryRepo->delete($category);
        } else {
            throw new NotFoundHttpException();
        }
        if ($status) {
            $this->addSuccess('admin.common.delete_complete', 'admin');
        } else {
            $this->addError('admin.common.delete_error', 'admin');
        }
        return $this->redirectToRoute(Constants::ADMIN_BIND_PREFIX . '_category_list',['type_id' => $type_id]);
    }

    /**
     * カテゴリー / ソート
     *
     * @param Request $request
     * @param integer $type_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/category_sort/{type_id}",name="_category_sort",requirements={"type_id", "\d+"})
     */
    public function category_sort(Request $request, $type_id)
    {
        // CSRF
        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(Constant::TOKEN_NAME, $request->get(Constant::TOKEN_NAME)))) {
            throw new AccessDeniedHttpException('CSRF token is invalid.');
        }

        // XMLHttpRequest
        if (! $request->isXmlHttpRequest()) {
            throw new BadRequestHttpException('Request is invalid.');
        }

        $category_ids = $_POST['category_ids'];
        if (! empty($type_id) && $category_ids) {
            // 投稿タイプ取得
            if (! $this->typeRepo->find($type_id)) {
                throw new NotFoundHttpException();
            }

            $no = 1;
            foreach ($category_ids as $category_id) {
                if (($category = $this->categoryRepo->find($category_id))) {
                    $category->setOrderNo($no++);
                    if (!$this->categoryRepo->save($category)) {
                        log_debug("順番の更新が出来ませんでした");
                    }
                }
            }
        } else {
            throw new BadRequestHttpException('必要なパラメーターが取得できません');
        }

        return new JsonResponse([]);
    }

    /**
     * 投稿 / リスト
     *
     * @param Request $request
     * @param integer $type_id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/post_list",name="_post_list")
     * @Template("@TabaCMS/admin/post_list.twig")
     */
    public function post_list(Request $request, Paginator $paginator)
    {
        $param = $request->query;
        $type_id = $param->get('type_id');
        $page_no = ($param->get('page_no') ? $param->get('page_no') : 1);

        // 投稿タイプID
        if (empty($type_id)) {
            throw new NotFoundHttpException();
        }

        // 投稿タイプ取得
        $type = null;
        if (empty($type_id) || ! ($type = $this->typeRepo->find($type_id))) {
            throw new NotFoundHttpException();
        }

        // 投稿リスト取得
        $list = $paginator->paginate(
            $this->postRepo->createListQueryBuilder(['type_id' => $type_id]),
            $page_no,
            Constants::DEFAULT_PAGE_COUNT,
            ['wrap-queries' => true]
        );

        return [
            'csrf_token' => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(), // CSRFトークン文字列
            'type' => $type,
            'list' => $list
        ];
    }

    /**
     * 投稿 / 新規登録・編集
     *
     * @param Request $request
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/post_new/{type_id}",name="_post_new",requirements={"type_id", "\d+"})
     * @Route("/post_edit/{type_id}/{post_id}",name="_post_edit",requirements={"type_id","\d+","post_id","\d+"})
     * @Template("@TabaCMS/admin/post_edit.twig")
     */
    public function post_edit(Request $request,$type_id,$post_id = null)
    {
        // 投稿タイプID
        $type_id = null;

        // 投稿操作オブジェクトの生成
        $post = null;
        if ($post_id) {
            if (! ($post = $this->postRepo->find($post_id))) {
                throw new NotFoundHttpException();
            }
            $type_id = $post->getTypeId();
            // JS除去
            if ($post->getBody() != null) {
                $post->setBody(Util::removeJS($post->getBody()));
            }
        } else {
            $post = new post(true);
            $path = explode("/", $request->getRequestUri());
            $type_id = end($path);
        }

        $imageEdited = false; // 画像編集済みかチェックするフラグ
        $originThumbnail = $post->getThumbnail();

        // 投稿タイプ取得
        $type = null;
        if (empty($type_id) || ! ($type = $this->typeRepo->find($type_id))) {
            throw new NotFoundHttpException();
        }
        $post->setTypeId($type_id);
        $post->setType($type);

        // フォームの生成
        $builder = $this->formFactory->createBuilder(PostType::class, $post);
        $form = $builder->getForm();

        // 登録・変更実行
        if ('POST' === $request->getMethod()) {
            // データが入力されていない場合、初期値をセットします。
            $params = $request->request->all();
            // 公開日
            if (empty($params[$form->getName()]['public_date'])) {
                $params[$form->getName()]['public_date'] = date('Y-m-d H:i');
            }
            // データキー
            if (empty($params[$form->getName()]['data_key'])) {
                $data_key = $type->getDataKey() . "_" . date('Ymd', strtotime($params[$form->getName()]['public_date']));
                if ($this->postRepo->getList(array(
                    'data_key' => $data_key
                ))) {
                    for ($i = 1; $i <= 100; $i ++) {
                        if (! $this->postRepo->getList(array(
                            'data_key' => $data_key . "_" . $i
                        ))) {
                            $data_key = $data_key . "_" . $i;
                            break;
                        }
                    }
                }
                $params[$form->getName()]['data_key'] = $data_key;
            }
            // JS除去
            if (! empty($params[$form->getName()]['body'])) {
                $params[$form->getName()]['body'] = Util::removeJS($params[$form->getName()]['body']);
            }
            $request->request->replace($params);

            $form->handleRequest($request);
            if ($originThumbnail != $post->getThumbnail()) $imageEdited = true;
            if ($form->isValid()) {
                // データ作成者
                if ($this->tokenStore && $this->tokenStore->getToken() && ($user = $this->tokenStore->getToken()->getUser()) && is_object($user)) {
                    $post->setMember($user);
                }

                // 画像を移動
                if ($post->getThumbnail() !== null && file_exists($this->eccubeConfig['eccube_temp_image_dir'] . '/' . $post->getThumbnail())) {
                    rename($this->eccubeConfig['eccube_temp_image_dir'] . '/' . $post->getThumbnail(), $this->eccubeConfig['eccube_save_image_dir'] . '/' . $post->getThumbnail());
                }
                // 画像削除
                $oldPath = $newPath = null;
                if ($originThumbnail) $oldPath = $this->eccubeConfig['eccube_save_image_dir'] . '/' . $originThumbnail;
                if ($post->getThumbnail()) $newPath = $this->eccubeConfig['eccube_save_image_dir'] . '/' . $post->getThumbnail();
                if (!$post->getThumbnail()) {
                    if (file_exists($newPath)) unlink($newPath);
                    if (file_exists($oldPath)) unlink($oldPath);
                } else if ($originThumbnail != $post->getThumbnail()) {
                    if (file_exists($oldPath)) unlink($oldPath);
                }

                if ($this->postRepo->save($post)) {
                    $this->addSuccess('admin.common.save_complete', 'admin');
                    return $this->redirectToRoute(Constants::ADMIN_BIND_PREFIX . '_post_edit',['type_id' => $type_id,'post_id' => $post->getPostId()]);
                } else {
                    $this->addError('admin.common.save_error', 'admin');
                }
            }
        }

        return [
            'csrf_token' => $this->csrfTokenManager->getToken(Constant::TOKEN_NAME)->getValue(), // CSRFトークン文字列
            'form' => $form->createView(),
            'type' => $type,
            'post' => $post,
            'image_edited' => $imageEdited,
        ];
    }

    /**
     * 投稿 / 削除
     *
     * @param Request $request
     * @param integer $post_id
     * @return \Symfony\Component\HttpFoundation\Response
     * 
     * @Route("/post_delete/{post_id}",name="_post_delete",requirements={"post_id","\d+"})
     */
    public function post_delete(Request $request, $post_id)
    {
        // 投稿タイプ操作オブジェクトの生成
        $status = false;
        $post = $this->postRepo->find($post_id);
        $type_id = null;
        if (! empty($post)) {
            $status = $this->postRepo->delete($post);
            $type_id = $post->getTypeId();
        } else {
            throw new NotFoundHttpException();
        }
        if ($status) {
            $this->addSuccess('admin.common.delete_complete', 'admin');
        } else {
            $this->addError('admin.common.delete_error', 'admin');
        }
        return $this->redirectToRoute(Constants::ADMIN_BIND_PREFIX . '_post_list',['type_id' => $type_id]);
    }

    /**
     * アイキャッチ画像アップロード
     *
     * @param Request $request
     * @throws BadRequestHttpException
     * @throws UnsupportedMediaTypeHttpException
     * @return \Symfony\Component\HttpFoundation\Response
     *
     * @Route("/post_thumbnail_upload",name="_post_thumbnail_upload")
     */
    public function post_thumbnail_upload(Request $request)
    {
        // CSRF
        if (! $this->csrfTokenManager->isTokenValid(new CsrfToken(Constant::TOKEN_NAME, $request->get(Constant::TOKEN_NAME)))) {
            throw new AccessDeniedHttpException('CSRF token is invalid.');
        }

        // XMLHttpRequest
        if (! $request->isXmlHttpRequest()) {
            throw new BadRequestHttpException('Request is invalid.');
        }

        $files = $request->files->get(Constants::PLUGIN_CODE_LC . '_post');

        $file_name = null;
        if (count($files) > 0) {
            foreach ($files as $file) {
                log_debug("[FILE OBJECT] " . print_r($file, true));

                if (0 !== strpos($file->getMimeType(), 'image')) {
                    throw new UnsupportedMediaTypeHttpException('File format is invalid.');
                }
                $extension = $file->getClientOriginalExtension();
                $file_name = date('mdHis') . uniqid('_') . '.' . $extension;
                $file->move($this->eccubeConfig['eccube_temp_image_dir'], $file_name);
                break;
            }
        }

        return new JsonResponse([
            'file' => $file_name
        ], 200);
    }

    /**
     * @Route("/test",name="_test")
     * @Template("@TabaCMS/admin/test.html")
     */
    public function test(Request $request) {
    }

    /**
     * 各種ファイルを出力します。
     *
     * @param Request $request
     * @param string $file
     * @throws NotFoundHttpException
     * @return BinaryFileResponse
     *
     * @Route("/assets/{file}",name="_assets",requirements={"file"="[a-zA-Z0-9-_/\s.]+"})
     */
    public function assets(Request $request,$file) {
        if ($this->container->has('profiler')) $this->container->get('profiler')->disable();

        if (strpos($file,'..')) {
            log_fatal("ディレクトリトラバーサル攻撃の可能性があります。 [FILE] " . $file);
            throw new NotFoundHttpException();
        }

        $file = Constants::TEMPLATE_PATH . DIRECTORY_SEPARATOR .  "admin" . DIRECTORY_SEPARATOR . "assets" .  DIRECTORY_SEPARATOR . $file;
        if (file_exists($this->eccubeConfig['plugin_realdir'] . DIRECTORY_SEPARATOR . $file)) {
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
                "html" => "text/html",
                "map" => "application/json",
            );
            if (in_array($suffix,array_keys($suffix_def))) {
                $fileObject = new \SplFileInfo($this->eccubeConfig['plugin_realdir'] . DIRECTORY_SEPARATOR . $file);
                $response = new BinaryFileResponse($fileObject);
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

    /**
     * ルーティング関連のキャッシュを削除します。
     */
    private function routingCacheClear() {
        $cacheDir = $this->container->getParameter("kernel.cache_dir");
        $filesystem   = $this->container->get('filesystem');
        $finder = new Finder();
        foreach ($finder->files()->depth('== 0')->in($cacheDir) as $file) {
            if (preg_match('/UrlGenerator|UrlMatcher/', $file->getFilename()) == 1) {
                $filesystem->remove($file->getRealpath());
            }
        }
        //$this->router->warmUp($cacheDir);
    }
}
