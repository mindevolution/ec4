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

namespace Plugin\ContactManagement4\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use Eccube\Controller\AbstractController;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\ContactManagement4\Form\Type\Admin\SearchContactType;
use Plugin\ContactManagement4\Repository\ContactRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class ContactController
 */
class ContactController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var ContactRepository
     */
    protected $contactRepository;

    /**
     * ContactController constructor.
     *
     * @param CustomerRepository $customerRepository
     */
    public function __construct(
        PageMaxRepository $pageMaxRepository,
        ContactRepository $contactRepository
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->contactRepository = $contactRepository;
    }

    /**
     * 配信内容設定検索画面を表示する.
     * 左ナビゲーションの選択はGETで遷移する.
     *
     * @Route("/%eccube_admin_route%/contact", name="plugin_contact")
     * @Route("/%eccube_admin_route%/contact/{page_no}", requirements={"page_no" = "\d+"}, name="plugin_contact_page")
     * @Template("@ContactManagement4/admin/index.twig")
     *
     * @param integer $page_no
     *
     * @return \Symfony\Component\HttpFoundation\Response|array
     */
    public function index(Request $request, PaginatorInterface $paginator, $page_no = null)
    {
        $session = $request->getSession();
        $pageNo = $page_no;
        $pageMaxis = $this->pageMaxRepository->findAll();
        $pageCount = $session->get('eccube.admin.contact.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        $pageCountParam = $request->get('page_count');
        if ($pageCountParam && is_numeric($pageCountParam)) {
            foreach ($pageMaxis as $pageMax) {
                if ($pageCountParam == $pageMax->getName()) {
                    $pageCount = $pageMax->getName();
                    $session->set('eccube.admin.contact.search.page_count', $pageCount);
                    break;
                }
            }
        }
        $pageMax = $this->eccubeConfig['eccube_default_page_count'];

        $pagination = null;
        $searchForm = $this->formFactory
            ->createBuilder(SearchContactType::class)
            ->getForm();

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);
            if ($searchForm->isValid()) {
                $searchData = $searchForm->getData();
                $pageNo = 1;
                $session->set('eccube.admin.contact.search', FormUtil::getViewData($searchForm));
                $session->set('eccube.admin.contact.search.page_no', $pageNo);
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
                    $session->set('eccube.admin.contact.search.page_no', (int) $pageNo);
                } else {
                    $pageNo = $session->get('eccube.admin.contact.search.page_no', 1);
                }
                $viewData = $session->get('eccube.admin.contact.search', []);
            } else {
                $pageNo = 1;
                $viewData = FormUtil::getViewData($searchForm);
                $session->set('eccube.admin.contact.search', $viewData);
                $session->set('eccube.admin.contact.search.page_no', $pageNo);
            }
            $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        }

        /** @var QueryBuilder $qb */
        $qb = $this->contactRepository->getQueryBuilderBySearchData($searchData);
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
