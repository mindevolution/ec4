<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Controller\Admin\Shop;

use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\ORM\QueryBuilder;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\CustomerShop;
use Eccube\Entity\CustomerShopLevel;
use Eccube\Entity\Master\CustomerStatus;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\SearchCustomerType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\CustomerShopLevelRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\PrefRepository;
use Eccube\Repository\Master\SexRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\Master\CustomerStatusRepository;
use Eccube\Service\CsvExportService;
use Eccube\Service\MailService;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\Paginator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Translation\TranslatorInterface;

class CustomerShopController extends AbstractController
{
    /**
     * @var CustomerStatusRepository
     */
    protected $customerStatusRepository;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var PrefRepository
     */
    protected $prefRepository;

    /**
     * @var SexRepository
     */
    protected $sexRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var CustomerShopRepository
     */
    protected $customerShopRepository;

    /**
     * @var CustomerShopLevelRepository
     */
    protected $customerShopLevelRepository;



    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    public function __construct(
        PageMaxRepository $pageMaxRepository,
        CustomerRepository $customerRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerShopLevelRepository $customerShopLevelRepository,
        CustomerStatusRepository $customerStatusRepository,
        SexRepository $sexRepository,
        PrefRepository $prefRepository,
        MailService $mailService,
        CsvExportService $csvExportService
    ) {
        $this->pageMaxRepository = $pageMaxRepository;
        $this->customerRepository = $customerRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->customerShopLevelRepository = $customerShopLevelRepository;
        $this->sexRepository = $sexRepository;
        $this->prefRepository = $prefRepository;
        $this->mailService = $mailService;
        $this->csvExportService = $csvExportService;
        $this->customerStatusRepository = $customerStatusRepository;
    }

    /**
     * @Route("/%eccube_admin_route%/customerShop", name="admin_customer_shop")
     * @Template("@admin/CustomerShop/index.twig")
     */
    public function index(Request $request)
    {
        //edit by gzy
        // $session = $this->session;
        // // $builder = $this->formFactory->createBuilder(SearchCustomerType::class);

        // // $event = new EventArgs(
        // //     [
        // //         'builder' => $builder,
        // //     ],
        // //     $request
        // // );
        // // $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_INITIALIZE, $event);

        // // $searchForm = $builder->getForm();

        // $pageMaxis = $this->pageMaxRepository->findAll();
        // $pageCount = $session->get('eccube.admin.customer.search.page_count', $this->eccubeConfig['eccube_default_page_count']);
        // $pageCountParam = $request->get('page_count');
        // if ($pageCountParam && is_numeric($pageCountParam)) {
        //     foreach ($pageMaxis as $pageMax) {
        //         if ($pageCountParam == $pageMax->getName()) {
        //             $pageCount = $pageMax->getName();
        //             $session->set('eccube.admin.customer.search.page_count', $pageCount);
        //             break;
        //         }
        //     }
        // }

        // if ('POST' === $request->getMethod()) {
        //     $searchForm->handleRequest($request);
        //     if ($searchForm->isValid()) {
        //         $searchData = $searchForm->getData();
        //         $page_no = 1;

        //         $session->set('eccube.admin.customer.search', FormUtil::getViewData($searchForm));
        //         $session->set('eccube.admin.customer.search.page_no', $page_no);
        //     } else {
        //         return [
        //             'searchForm' => $searchForm->createView(),
        //             'pagination' => [],
        //             'pageMaxis' => $pageMaxis,
        //             'page_no' => $page_no,
        //             'page_count' => $pageCount,
        //             'has_errors' => true,
        //         ];
        //     }
        // } else {
        //     if (null !== $page_no || $request->get('resume')) {
        //         if ($page_no) {
        //             $session->set('eccube.admin.customer.search.page_no', (int) $page_no);
        //         } else {
        //             $page_no = $session->get('eccube.admin.customer.search.page_no', 1);
        //         }
        //         $viewData = $session->get('eccube.admin.customer.search', []);
        //     } else {
        //         $page_no = 1;
        //         $viewData = FormUtil::getViewData($searchForm);
        //         $session->set('eccube.admin.customer.search', $viewData);
        //         $session->set('eccube.admin.customer.search.page_no', $page_no);
        //     }
        //     $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
        // }

        // /** @var QueryBuilder $qb */
        // $qb = $this->customerRepository->getQueryBuilderBySearchData($searchData);

        // $event = new EventArgs(
        //     [
        //         'form' => $searchForm,
        //         'qb' => $qb,
        //     ],
        //     $request
        // );
        // $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_INDEX_SEARCH, $event);

        // $pagination = $paginator->paginate(
        //     $qb,
        //     $page_no,
        //     $pageCount
        // );
        $CustomerShops = $this->customerShopRepository->findAll();

        return [
            'CustomerShops' => $CustomerShops,
        ];
    }









    /**
     * @Route("/%eccube_admin_route%/customerShop/{id}/yes", requirements={"id" = "\d+"}, name="admin_customer_shop_yes")
     */
    public function yes(Request $request, $id)
    {
        //edit by gzy
        $this->isTokenValid();

        log_info('会員审核開始', [$id]);

        $CustomerShop = $this->customerShopRepository->find($id);
        $CustomerShopLevel = $this->customerShopLevelRepository->findOneBy(['level' => 'GOLD']);
        try {
            $CustomerShop->setStatus('Y');
            $CustomerShop->setCustomerShopLevel($CustomerShopLevel);
            $this->entityManager->flush($CustomerShop);

            $CustomerStatus = $this->customerStatusRepository->find(CustomerStatus::SHOP);
            $Customer = $CustomerShop->getCustomer();
            $Customer->setStatus($CustomerStatus);
            $this->entityManager->persist($Customer);
            $this->entityManager->flush();

            $this->mailService->sendCustomerShopConfirmMailYes($CustomerShop->getCustomer());
        } catch (ForeignKeyConstraintViolationException $e) {
            log_info('会員审核失败！', [$id]);
        }

        log_info('会員审核完了', [$id]);

        return $this->redirectToRoute('admin_customer_shop');
    }


    /**
     * @Route("/%eccube_admin_route%/customerShop/{id}/no", requirements={"id" = "\d+"}, name="admin_customer_shop_no")
     */
    public function no(Request $request, $id)
    {
        //edit by gzy
        $this->isTokenValid();

        log_info('会員审核開始', [$id]);

        $CustomerShop = $this->customerShopRepository->find($id);

        try {
            $CustomerShop->setStatus('N');

            $this->entityManager->flush($CustomerShop);

            // メール送信
            $this->mailService->sendCustomerShopConfirmMailNo($CustomerShop->getCustomer());


        } catch (ForeignKeyConstraintViolationException $e) {
            log_info('会員审核失败！', [$id]);
        }

        log_info('会員审核完了', [$id]);

        return $this->redirectToRoute('admin_customer_shop');
    }




    /**
     * 会員CSVの出力.
     *
     * @Route("/%eccube_admin_route%/customerShop/export", name="admin_customer_shop_export")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function export(Request $request)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request) {

            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType(CsvType::CSV_TYPE_CUSTOMER_SHOP);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader();

            // 会員データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getCustomerShopQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData(function ($entity, $csvService) use ($request) {
                $Csvs = $csvService->getCsvs();


                /** @var $CustomerShop \Eccube\Entity\CustomerShop */
                $CustomerShop = $entity;

                $ExportCsvRow = new \Eccube\Entity\ExportCsvRow();

                // CSV出力項目と合致するデータを取得.
                foreach ($Csvs as $Csv) {
                    // 会員データを検索.
                    $ExportCsvRow->setData($csvService->getData($Csv, $CustomerShop));

                    $event = new EventArgs(
                        [
                            'csvService' => $csvService,
                            'Csv' => $Csv,
                            'CustomerShop' => $CustomerShop,
                            'ExportCsvRow' => $ExportCsvRow,
                        ],
                        $request
                    );
                    $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_CUSTOMER_SHOP_CSV_EXPORT, $event);

                    $ExportCsvRow->pushData();
                }

                //$row[] = number_format(memory_get_usage(true));
                // 出力.
                $csvService->fputcsv($ExportCsvRow->getRow());
            });
        });

        $now = new \DateTime();
        $filename = 'customer_shop_'.$now->format('YmdHis').'.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);

        $response->send();

        log_info('商户会員CSVファイル名', [$filename]);

        return $response;
    }
    






    
}
