<?php


namespace Plugin\AdminSecurity4\Controller\Admin;

use Plugin\AdminSecurity4\Form\Type\Admin\SearchLoginRecordType;
use Plugin\AdminSecurity4\Repository\LoginRecordRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Routing\Annotation\Route;
use Eccube\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Doctrine\ORM\QueryBuilder;

/**
 * Class LoginRecordController
 */
class LoginRecordController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var LoginRecordRepository
     */
    protected $loginRecordRepository;


    /**
     * LoginRecordController constructor.
     *
     * @param PageMaxRepository $pageMaxRepository
     * @param LoginRecordRepository $loginRecordRepository
     */
    public function __construct(
        PageMaxRepository $pageMaxRepository,
        LoginRecordRepository $loginRecordRepository
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->loginRecordRepository = $loginRecordRepository;
    }

    /**
     * 配信内容設定検索画面を表示する.
     * 左ナビゲーションの選択はGETで遷移する.
     *
     * @Route("/%eccube_admin_route%/login_record", name="plg_admin_record_login_record")
     * @Route("/%eccube_admin_route%/login_record/{page_no}", requirements={"page_no" = "\d+"}, name="plg_admin_record_login_record_page")
     * @Template("@AdminSecurity4/admin/index.twig")
     *
     * @param Request $request
     * @param Paginator $paginator
     * @param integer $page_no
     *
     * @return \Symfony\Component\HttpFoundation\Response|array
     */
    public function index(Request $request, Paginator $paginator, $page_no = null)
    {
        $session = $request->getSession();
        $pageNo = $page_no;
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.login_record.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.login_record.search.page_count', $pageCount);
                    break;
                }
            }
        }
        $pageMax = $this->eccubeConfig['eccube_default_page_count'];

        $pagination = null;
        $searchForm = $this->formFactory
            ->createBuilder(SearchLoginRecordType::class)
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $pageNo = 1;
                $session->set('eccube.admin.login_record.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.login_record.search.page_no', $pageNo);
            } else {
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $pageNo ? $pageNo : 1,
                    'page_count' => $pageCount,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $pageNo || $request->get('resume')) {
                if ($pageNo) {
                    $session->set('eccube.admin.login_record.search.page_no', (int) $pageNo);
                } else {
                    $pageNo = $session->get('eccube.admin.login_record.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.login_record.search', []);
            } else {
                $pageNo = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.login_record.search', $viewData);
                $session->set('eccube.admin.login_record.search.page_no', $pageNo);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        /** @var QueryBuilder $qb */
        $qb = $this->loginRecordRepository->getQueryBuilderBySearchData($searchData);
        $pagination = $paginator->paginate(
            $qb,
            $pageNo,
            $pageCount
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_count' => $pageCount,
            'has_errors' => false,
        ];
    }


}
