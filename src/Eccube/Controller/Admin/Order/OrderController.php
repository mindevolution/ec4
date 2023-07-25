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

namespace Eccube\Controller\Admin\Order;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\Master\CsvType;
use Eccube\Entity\Master\OrderStatus;
use Eccube\Entity\OrderPdf;
use Eccube\Entity\Shipping;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Form\Type\Admin\OrderPdfType;
use Eccube\Form\Type\Admin\SearchOrderType;
use Eccube\Repository\CustomerRepository;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Repository\Master\ProductStatusRepository;
use Eccube\Repository\Master\SexRepository;
use Eccube\Repository\OrderPdfRepository;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\PaymentRepository;
use Eccube\Repository\ProductStockRepository;
use Eccube\Repository\CustomerShopLevelRepository;
use Eccube\Repository\CustomerShopRepository;
use Eccube\Repository\CustomerPointRepository;
use Eccube\Repository\CustomerLevelRepository;
use Eccube\Repository\CustomerLevelDetailRepository;
use Eccube\Service\CsvExportService;
use Eccube\Service\MailService;
use Eccube\Service\OrderPdfService;
use Eccube\Service\OrderStateMachine;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderController extends AbstractController
{
    /**
     * @var PurchaseFlow
     */
    protected $purchaseFlow;

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var SexRepository
     */
    protected $sexRepository;

    /**
     * @var OrderStatusRepository
     */
    protected $orderStatusRepository;

    /**
     * @var PageMaxRepository
     */
    protected $pageMaxRepository;

    /**
     * @var ProductStatusRepository
     */
    protected $productStatusRepository;

    /**
     * @var OrderRepository
     */
    protected $orderRepository;

    /** @var OrderPdfRepository */
    protected $orderPdfRepository;

    /**
     * @var ProductStockRepository
     */
    protected $productStockRepository;

    /** @var OrderPdfService */
    protected $orderPdfService;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var OrderStateMachine
     */
    protected $orderStateMachine;

    /**
     * @var MailService
     */
    protected $mailService;

    /**
     * @var CustomerShopLevelRepository
     */
    protected $customerShopLevelRepository;

    /**
     * @var CustomerShopRepository
     */
    protected $customerShopRepository;

    /**
     * @var CustomerPointRepository
     */
    protected $customerPointRepository;

    /**
     * @var CustomerLevelRepository
     */
    protected $customerLevelRepository;

    /**
     * @var CustomerLevelDetailRepository
     */
    protected $customerLevelDetailRepository;


    /**
     * OrderController constructor.
     *
     * @param PurchaseFlow $orderPurchaseFlow
     * @param CsvExportService $csvExportService
     * @param CustomerRepository $customerRepository
     * @param PaymentRepository $paymentRepository
     * @param SexRepository $sexRepository
     * @param OrderStatusRepository $orderStatusRepository
     * @param PageMaxRepository $pageMaxRepository
     * @param ProductStatusRepository $productStatusRepository
     * @param ProductStockRepository $productStockRepository
     * @param OrderRepository $orderRepository
     * @param OrderPdfRepository $orderPdfRepository
     * @param ValidatorInterface $validator
     * @param OrderStateMachine $orderStateMachine ;
     */
    public function __construct(
        PurchaseFlow $orderPurchaseFlow,
        CsvExportService $csvExportService,
        CustomerRepository $customerRepository,
        PaymentRepository $paymentRepository,
        SexRepository $sexRepository,
        OrderStatusRepository $orderStatusRepository,
        PageMaxRepository $pageMaxRepository,
        ProductStatusRepository $productStatusRepository,
        ProductStockRepository $productStockRepository,
        OrderRepository $orderRepository,
        OrderPdfRepository $orderPdfRepository,
        ValidatorInterface $validator,
        OrderStateMachine $orderStateMachine,
        MailService $mailService,
        CustomerShopLevelRepository $customerShopLevelRepository,
        CustomerShopRepository $customerShopRepository,
        CustomerPointRepository $customerPointRepository,
        CustomerLevelRepository $customerLevelRepository,
        CustomerLevelDetailRepository $customerLevelDetailRepository
    ) {
        $this->purchaseFlow = $orderPurchaseFlow;
        $this->csvExportService = $csvExportService;
        $this->customerRepository = $customerRepository;
        $this->paymentRepository = $paymentRepository;
        $this->sexRepository = $sexRepository;
        $this->orderStatusRepository = $orderStatusRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->productStatusRepository = $productStatusRepository;
        $this->productStockRepository = $productStockRepository;
        $this->orderRepository = $orderRepository;
        $this->orderPdfRepository = $orderPdfRepository;
        $this->validator = $validator;
        $this->orderStateMachine = $orderStateMachine;
        $this->mailService = $mailService;
        $this->customerShopLevelRepository = $customerShopLevelRepository;
        $this->customerShopRepository = $customerShopRepository;
        $this->customerPointRepository = $customerPointRepository;
        $this->customerLevelRepository = $customerLevelRepository;
        $this->customerLevelDetailRepository = $customerLevelDetailRepository;
    }

    /**
     * 受注一覧画面.
     *
     * - 検索条件, ページ番号, 表示件数はセッションに保持されます.
     * - クエリパラメータでresume=1が指定された場合、検索条件, ページ番号, 表示件数をセッションから復旧します.
     * - 各データの, セッションに保持するアクションは以下の通りです.
     *   - 検索ボタン押下時
     *      - 検索条件をセッションに保存します
     *      - ページ番号は1で初期化し、セッションに保存します。
     *   - 表示件数変更時
     *      - クエリパラメータpage_countをセッションに保存します。
     *      - ただし, mtb_page_maxと一致しない場合, eccube_default_page_countが保存されます.
     *   - ページング時
     *      - URLパラメータpage_noをセッションに保存します.
     *   - 初期表示
     *      - 検索条件は空配列, ページ番号は1で初期化し, セッションに保存します.
     *
     * @Route("/%eccube_admin_route%/order", name="admin_order")
     * @Route("/%eccube_admin_route%/order/page/{page_no}", requirements={"page_no" = "\d+"}, name="admin_order_page")
     * @Template("@admin/Order/index.twig")
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator)
    {
        $builder = $this->formFactory
            ->createBuilder(SearchOrderType::class);

        $event = new EventArgs(
            [
                'builder' => $builder,
            ],
            $request
        );
        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_INITIALIZE, $event);

        $searchForm = $builder->getForm();

        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get('eccube.admin.order.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ($page_count_param == $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('eccube.admin.order.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ('POST' === $request->getMethod()) {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('eccube.admin.order.search', FormUtil::getViewData($searchForm));
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else {
            if (null !== $page_no || $request->get('resume')) {
                /*
                 * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
                 */
                if ($page_no) {
                    // ページ送りで遷移した場合.
                    $this->session->set('eccube.admin.order.search.page_no', (int) $page_no);
                } else {
                    // 他画面から遷移した場合.
                    $page_no = $this->session->get('eccube.admin.order.search.page_no', 1);
                }
                $viewData = $this->session->get('eccube.admin.order.search', []);
                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);
            } else {
                /**
                 * 初期表示の場合.
                 */
                $page_no = 1;
                $viewData = [];

                if ($statusId = (int) $request->get('order_status_id')) {
                    $viewData = ['status' => $statusId];
                }

                $searchData = FormUtil::submitAndGetData($searchForm, $viewData);

                // セッション中の検索条件, ページ番号を初期化.
                $this->session->set('eccube.admin.order.search', $viewData);
                $this->session->set('eccube.admin.order.search.page_no', $page_no);
            }
        }

        $qb = $this->orderRepository->getQueryBuilderBySearchDataForAdmin($searchData);

        $event = new EventArgs(
            [
                'qb' => $qb,
                'searchData' => $searchData,
            ],
            $request
        );

        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_INDEX_SEARCH, $event);

        $pagination = $paginator->paginate(
            $qb,
            $page_no,
            $page_count
        );

        return [
            'searchForm' => $searchForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
            'OrderStatuses' => $this->orderStatusRepository->findBy([], ['sort_no' => 'ASC']),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/order/bulk_delete", name="admin_order_bulk_delete", methods={"POST"})
     */
    public function bulkDelete(Request $request)
    {
        $this->isTokenValid();
        $ids = $request->get('ids');
        foreach ($ids as $order_id) {
            $Order = $this->orderRepository
                ->find($order_id);
            if ($Order) {
                $this->entityManager->remove($Order);
                log_info('受注削除', [$Order->getId()]);
            }
        }

        $this->entityManager->flush();

        $this->addSuccess('admin.common.delete_complete', 'admin');

        return $this->redirect($this->generateUrl('admin_order', ['resume' => Constant::ENABLED]));
    }

    /**
     * 受注CSVの出力.
     *
     * @Route("/%eccube_admin_route%/order/export/order", name="admin_order_export_order")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportOrder(Request $request)
    {
        $filename = 'order_'.(new \DateTime())->format('YmdHis').'.csv';
        $response = $this->exportCsv($request, CsvType::CSV_TYPE_ORDER, $filename);
        log_info('受注CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * 配送CSVの出力.
     *
     * @Route("/%eccube_admin_route%/order/export/shipping", name="admin_order_export_shipping")
     *
     * @param Request $request
     *
     * @return StreamedResponse
     */
    public function exportShipping(Request $request)
    {
        $filename = 'shipping_'.(new \DateTime())->format('YmdHis').'.csv';
        $response = $this->exportCsv($request, CsvType::CSV_TYPE_SHIPPING, $filename);
        log_info('配送CSV出力ファイル名', [$filename]);

        return $response;
    }

    /**
     * @param Request $request
     * @param $csvTypeId
     * @param string $fileName
     *
     * @return StreamedResponse
     */
    protected function exportCsv(Request $request, $csvTypeId, $fileName)
    {
        // タイムアウトを無効にする.
        set_time_limit(0);

        // sql loggerを無効にする.
        $em = $this->entityManager;
        $em->getConfiguration()->setSQLLogger(null);

        $response = new StreamedResponse();
        $response->setCallback(function () use ($request, $csvTypeId) {
            // CSV種別を元に初期化.
            $this->csvExportService->initCsvType($csvTypeId);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader();

            // 受注データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getOrderQueryBuilder($request);

            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);
            $this->csvExportService->exportData(function ($entity, $csvService) use ($request) {
                $Csvs = $csvService->getCsvs();

                $Order = $entity;
                $OrderItems = $Order->getOrderItems();

                foreach ($OrderItems as $OrderItem) {
                    $ExportCsvRow = new ExportCsvRow();

                    // CSV出力項目と合致するデータを取得.
                    foreach ($Csvs as $Csv) {
                        // 受注データを検索.
                        $ExportCsvRow->setData($csvService->getData($Csv, $Order));
                        if ($ExportCsvRow->isDataNull()) {
                            // 受注データにない場合は, 受注明細を検索.
                            $ExportCsvRow->setData($csvService->getData($Csv, $OrderItem));
                        }
                        if ($ExportCsvRow->isDataNull() && $Shipping = $OrderItem->getShipping()) {
                            // 受注明細データにない場合は, 出荷を検索.
                            $ExportCsvRow->setData($csvService->getData($Csv, $Shipping));
                        }

                        $event = new EventArgs(
                            [
                                'csvService' => $csvService,
                                'Csv' => $Csv,
                                'OrderItem' => $OrderItem,
                                'ExportCsvRow' => $ExportCsvRow,
                            ],
                            $request
                        );
                        $this->eventDispatcher->dispatch(EccubeEvents::ADMIN_ORDER_CSV_EXPORT_ORDER, $event);

                        $ExportCsvRow->pushData();
                    }

                    //$row[] = number_format(memory_get_usage(true));
                    // 出力.
                    $csvService->fputcsv($ExportCsvRow->getRow());
                }
            });
        });

        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$fileName);
        $response->send();

        return $response;
    }

    /**
     * Update to order status
     *
     * @Route("/%eccube_admin_route%/shipping/{id}/order_status", requirements={"id" = "\d+"}, name="admin_shipping_update_order_status", methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $Shipping
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function updateOrderStatus(Request $request, Shipping $Shipping)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $Order = $Shipping->getOrder();
        $OrderStatus = $this->entityManager->find(OrderStatus::class, $request->get('order_status'));

        if (!$OrderStatus) {
            return $this->json(['status' => 'NG'], 400);
        }

        $result = [];
        try {
            if ($Order->getOrderStatus()->getId() == $OrderStatus->getId()) {
                log_info('対応状況一括変更スキップ');
                $result = ['message' => trans('admin.order.skip_change_status', ['%name%' => $Shipping->getId()])];
            } else {
                    // 会員の場合、購入回数、購入金額などを更新
                    //edit by caishu 除了更改发货状态，还需要更改对应的会员状态
                    $TargetOrder = $this->orderRepository->find($Order->getId());
                    $OldStatus = $TargetOrder->getOrderStatus();
                    
                if ($this->orderStateMachine->can($Order, $OrderStatus)) {
                    if ($OrderStatus->getId() == OrderStatus::DELIVERED) {
                        if (!$Shipping->isShipped()) {
                            $Shipping->setShippingDate(new \DateTime());
                        }
                        $allShipped = true;
                        foreach ($Order->getShippings() as $Ship) {
                            if (!$Ship->isShipped()) {
                                $allShipped = false;
                                break;
                            }
                        }
                        if ($allShipped) {
                            $this->orderStateMachine->apply($Order, $OrderStatus);
                        }
                    } else {
                        $this->orderStateMachine->apply($Order, $OrderStatus);
                    }

                    if ($request->get('notificationMail')) { // for SimpleStatusUpdate
                        $this->mailService->sendShippingNotifyMail($Shipping);
                        $Shipping->setMailSendDate(new \DateTime());
                        $result['mail'] = true;
                    } else {
                        $result['mail'] = false;
                    }
                    // 対応中・キャンセルの更新時は商品在庫を増減させているので商品情報を更新
                    if ($OrderStatus->getId() == OrderStatus::IN_PROGRESS || $OrderStatus->getId() == OrderStatus::CANCEL) {
                        foreach ($Order->getOrderItems() as $OrderItem) {
                            $ProductClass = $OrderItem->getProductClass();
                            if ($OrderItem->isProduct() && !$ProductClass->isStockUnlimited()) {
                                $this->entityManager->flush($ProductClass);
                                $ProductStock = $this->productStockRepository->findOneBy(['ProductClass' => $ProductClass]);
                                $this->entityManager->flush($ProductStock);
                            }
                        }
                    }
                    $this->entityManager->flush($Order);
                    $this->entityManager->flush($Shipping);

                    // 会員の場合、購入回数、購入金額などを更新
                    if ($Customer = $Order->getCustomer()) {
                        $this->orderRepository->updateOrderSummary($Customer);
                        $this->entityManager->flush($Customer);
                    }
                } else {
                    $from = $Order->getOrderStatus()->getName();
                    $to = $OrderStatus->getName();
                    $result = ['message' => trans('admin.order.failed_to_change_status', [
                        '%name%' => $Shipping->getId(),
                        '%from%' => $from,
                        '%to%' => $to,
                    ])];
                }
                        // // 会員の場合、購入回数、購入金額などを更新
                        // //edit by caishu 除了更改发货状态，还需要更改对应的会员状态
                        // $TargetOrder = $this->orderRepository->find($request->get("id"));
                        // $OldStatus = $TargetOrder->getOrderStatus();
                        // $NewStatus = $Order->getOrderStatus();
                        $NewStatus = $Order->getOrderStatus();

                        if ($Customer = $TargetOrder->getCustomer()) {
                            $CustomerShop = $this->customerShopRepository->getCustomerShopByCustomer($Customer);
                            $CustomerShopLevels = $this->customerShopLevelRepository->findAll();
                            //下面是商户会员的逻辑
                            if($CustomerShop && $CustomerShop->getStatus() == "Y"){
                                // //edit by gzy 计算商户折扣价格
                                // $discount = $CustomerShop->getCustomerShopLevel()->getDiscount();
                                // $total = 0;
                                // foreach ($TargetOrder->getItems() as $item) {
                                //     if($item->isProduct()){
                                //         $total += (($item->getPriceIncTax() * (1-($discount / 100)))) * $item->getQuantity();
                                //     }
                                //     else{
                                //         $total += $item->getPriceIncTax() * $item->getQuantity();
                                //     }         
                                // }

                                // $TargetOrder->setTotal($total);
                                // $TargetOrder->setPaymentTotal($total);
                                // $this->entityManager->flush($TargetOrder);



                                $nowShop = date('Y-m-d H:i:s');
                                if($NewStatus->getId() == 5 && $OldStatus->getId() != 5){
                                    $money = intval($CustomerShop->getMoney()) + intval($TargetOrder->getTotal());
                                    $expDateShop = $CustomerShop->getExpTime();
                                    $is_change_upgrade = false;
                                    if ($nowShop < $expDateShop){//没有过期的话，先看看能不能升级，不能升级的话就不要计算了
                                        if($money > $CustomerShop->getCustomerShopLevel()->getMax()){
                                            foreach ($CustomerShopLevels as $CustomerShopLevel) {
                                                $min = $CustomerShopLevel->getMin();
                                                $max = $CustomerShopLevel->getMax();
                                                if ($money >= $min && $money <= $max) {
                                                    // 如果金额达到本等级的最高金额，则升级，有效日期重新计算一年,money清零
                                                    $CustomerShop->setCustomerShopLevel($CustomerShopLevel);
                                                    $oneYearDate = date( 'Y-m-d', strtotime($nowShop . "+1 year"));
                                                    $oneYearDatetomorrow = date( 'Y-m-d', strtotime($oneYearDate . "+1 day"));
                                                    $dd2 = date( 'Y-m-d H:i:s', strtotime($oneYearDatetomorrow));
                                                    $CustomerShop->setExpTime($dd2);
                                                    $CustomerShop->setMoney(0);
                                                    $is_change_upgrade = true;
                                                }
                                            }
                                        }
                                    }
                                    else{//如果当前的等级过期了，重新计算一下等级
                                        foreach ($CustomerShopLevels as $CustomerShopLevel) {
                                            $min = $CustomerShopLevel->getMin();
                                            $max = $CustomerShopLevel->getMax();
                                            if ($money >= $min && $money <= $max) {
                                                $CustomerShop->setCustomerShopLevel($CustomerShopLevel);
                                                $oneYearDate = date( 'Y-m-d', strtotime($nowShop . "+1 year"));
                                                $oneYearDatetomorrow = date( 'Y-m-d', strtotime($oneYearDate . "+1 day"));
                                                $dd2 = date( 'Y-m-d H:i:s', strtotime($oneYearDatetomorrow));
                                                $CustomerShop->setExpTime($dd2);
                                                $CustomerShop->setMoney(0);
                                                $is_change_upgrade = true;
                                            }
                                        }
                                    }
                                    if(!$is_change_upgrade){//没有升级就把金额加上
                                        $CustomerShop->setMoney($money);
                                    }
                                    $this->entityManager->flush($CustomerShop);
                                }

                                if($NewStatus->getId() == 9 && $OldStatus->getId() == 5){
                                    $money = intval($CustomerShop->getMoney()) - intval($TargetOrder->getTotal());
                                    if($money > 0){//说明在本等级以前买过商品了，并且大于退货金额
                                        $CustomerShop->setMoney($money);
                                    }
                                    else{//说明影响了商户升级，需要降到以前的等级,过期时间为一年后
                                        $CustomerShop->setCustomerShopLevel($CustomerShop->getLastCustomerShopLevel());
                                        $CustomerShop->setMoney(0);
                                        $oneYearDate = date( 'Y-m-d', strtotime($nowShop . "+1 year"));
                                        $oneYearDatetomorrow = date( 'Y-m-d', strtotime($oneYearDate . "+1 day"));
                                        $dd2 = date( 'Y-m-d H:i:s', strtotime($oneYearDatetomorrow));
                                        $CustomerShop->setExpTime($dd2);
                                    }

                                    $this->entityManager->flush($CustomerShop);
                                }

                            } else{
                                //下面是普通会员的逻辑
                                $this->orderRepository->updateOrderSummary($Customer);
                                

                                // 普通会员，在点击发送的时候，需要重新计算。修改CustomerLevel的金额money、level
                                if($NewStatus->getId() == 5 && $OldStatus->getId() != 5){
                                    $CustomerLevel = $this->customerLevelRepository->getCustomerLevelByCustomer($Customer);
                                    $CustomerLevelDetails = $this->customerLevelDetailRepository->findAll();
                                    $money = intval($CustomerLevel->getMoney()) + intval($TargetOrder->getTotal());
                                    foreach ($CustomerLevelDetails as $CustomerLevelDetail) {
                                        $min = $CustomerLevelDetail->getMin();
                                        $max = $CustomerLevelDetail->getMax();
                                        if ($money >= $min && $money <= $max) {
                                            $CustomerLevel->setCustomerLevelDetail($CustomerLevelDetail);
                                        }
                                    }
                                    // 等级过期时间更新
                                    $oneYearDate = date( 'Y-m-d', strtotime("+1 year"));
                                    $oneYearDatetomorrow = date( 'Y-m-d', strtotime($oneYearDate . "+1 day"));
                                    $dd2 = date( 'Y-m-d H:i:s', strtotime($oneYearDatetomorrow));

                                    $CustomerLevel->setMoney($money);
                                    $CustomerLevel->setLevel($CustomerLevel->getCustomerLevelDetail()->getLevel());
                                    if($CustomerLevel->getExpTime()){
                                        $CustomerLevel->setLastExpTime($CustomerLevel->getExpTime());
                                    }
                                    else{
                                        $CustomerLevel->setLastExpTime($dd2);
                                    }
                                    $CustomerLevel->setExpTime($dd2);
                                    

                                    $this->entityManager->flush($Customer);
                                    $this->entityManager->flush($CustomerLevel);

                                }
                                //普通会员，如果老状态为发送5，要改为返品9,需要重新计算。修改CustomerLevel的金额money、level
                                if($NewStatus->getId() == 9 && $OldStatus->getId() == 5){
                                    $CustomerLevel = $this->customerLevelRepository->getCustomerLevelByCustomer($Customer);
                                    $CustomerLevelDetails = $this->customerLevelDetailRepository->findAll();
                                    $money = intval($CustomerLevel->getMoney()) - intval($TargetOrder->getTotal());
                                    foreach ($CustomerLevelDetails as $CustomerLevelDetail) {
                                        $min = $CustomerLevelDetail->getMin();
                                        $max = $CustomerLevelDetail->getMax();
                                        if ($money >= $min && $money <= $max) {
                                            $CustomerLevel->setCustomerLevelDetail($CustomerLevelDetail);
                                        }
                                    }
                                    //如果返品，等级过期时间为上次的。
                                    $CustomerLevel->setExpTime($CustomerLevel->getLastExpTime());
                                    $CustomerLevel->setMoney($money);
                                    $CustomerLevel->setLevel($CustomerLevel->getCustomerLevelDetail()->getLevel());
                                    $this->entityManager->flush($Customer);
                                    $this->entityManager->flush($CustomerLevel);
                                }








                                // 下面修改积分
                                //普通会员，如果要改成发送5，如果已经有积分明细表记录了，就修改，否则才增加point明细记录
                                if($NewStatus->getId() == 5){
                                    $customerPoint = $this->customerPointRepository->getCustomerPointByOrder($TargetOrder);
                                    if($customerPoint){
                                        $customerPoint = $this->customerPointRepository->getCustomerPointByOrder($TargetOrder);
                                        $customerPoint->setStatus("Y");
                                        $this->entityManager->persist($customerPoint);
                                        $this->entityManager->flush();
                                    }
                                    else{
                                        $customerPoint = new \Eccube\Entity\CustomerPoint();
                                        $customerPoint->setCustomer($Customer);
                                        $customerPoint->setOrder($TargetOrder);
                                        $customerPoint->setStatus("Y");
                                        $customerPoint->setPoint($TargetOrder->getAddPoint());
                                        $customerPoint->setMinusPoint($TargetOrder->getUsePoint());

                                        $now = date('Y-m-d');
                                        $oneYearDate = date( 'Y-m-d', strtotime("+1 year"));
                                        $oneYearDatetomorrow = date( 'Y-m-d', strtotime($oneYearDate . "+1 day"));

                                        $dd = date( 'Y-m-d H:i:s', strtotime($now));
                                        $dd2 = date( 'Y-m-d H:i:s', strtotime($oneYearDatetomorrow));

                                        $customerPoint->setGetTime($dd);
                                        $customerPoint->setExpTime($dd2);
                                        $this->entityManager->persist($customerPoint);
                                        $this->entityManager->flush();
                                    }
                                }

                                //普通会员，如果老状态为发送5，要改为返品9，那么修改point明细记录
                                if($NewStatus->getId() == 9 && $OldStatus->getId() == 5){
                                    $customerPoint = $this->customerPointRepository->getCustomerPointByOrder($TargetOrder);
                                    $customerPoint->setStatus("N");
                                    $this->entityManager->persist($customerPoint);
                                    $this->entityManager->flush();
                                }

                            }

                        }
                log_info('対応状況一括変更処理完了', [$Order->getId()]);
            }
        } catch (\Exception $e) {
            log_error('予期しないエラーです', [$e->getMessage()]);

            return $this->json(['status' => 'NG'], 500);
        }

        return $this->json(array_merge(['status' => 'OK'], $result));
    }

    /**
     * Update to Tracking number.
     *
     * @Route("/%eccube_admin_route%/shipping/{id}/tracking_number", requirements={"id" = "\d+"}, name="admin_shipping_update_tracking_number", methods={"PUT"})
     *
     * @param Request $request
     * @param Shipping $shipping
     *
     * @return Response
     */
    public function updateTrackingNumber(Request $request, Shipping $shipping)
    {
        if (!($request->isXmlHttpRequest() && $this->isTokenValid())) {
            return $this->json(['status' => 'NG'], 400);
        }

        $trackingNumber = mb_convert_kana($request->get('tracking_number'), 'a', 'utf-8');
        /** @var \Symfony\Component\Validator\ConstraintViolationListInterface $errors */
        $errors = $this->validator->validate(
            $trackingNumber,
            [
                new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                new Assert\Regex(
                    ['pattern' => '/^[0-9a-zA-Z-]+$/u', 'message' => trans('admin.order.tracking_number_error')]
                ),
            ]
        );

        if ($errors->count() != 0) {
            log_info('送り状番号入力チェックエラー');
            $messages = [];
            /** @var \Symfony\Component\Validator\ConstraintViolationInterface $error */
            foreach ($errors as $error) {
                $messages[] = $error->getMessage();
            }

            return $this->json(['status' => 'NG', 'messages' => $messages], 400);
        }

        try {
            $shipping->setTrackingNumber($trackingNumber);
            $this->entityManager->flush($shipping);
            log_info('送り状番号変更処理完了', [$shipping->getId()]);
            $message = ['status' => 'OK', 'shipping_id' => $shipping->getId(), 'tracking_number' => $trackingNumber];

            return $this->json($message);
        } catch (\Exception $e) {
            log_error('予期しないエラー', [$e->getMessage()]);

            return $this->json(['status' => 'NG'], 500);
        }
    }

    /**
     * @Route("/%eccube_admin_route%/order/export/pdf", name="admin_order_export_pdf")
     * @Template("@admin/Order/order_pdf.twig")
     *
     * @param Request $request
     *
     * @return array|RedirectResponse
     */
    public function exportPdf(Request $request)
    {
        // requestから出荷番号IDの一覧を取得する.
        $ids = $request->get('ids', []);

        if (count($ids) == 0) {
            $this->addError('admin.order.delivery_note_parameter_error', 'admin');
            log_info('The Order cannot found!');

            return $this->redirectToRoute('admin_order');
        }

        /** @var OrderPdf $OrderPdf */
        $OrderPdf = $this->orderPdfRepository->find($this->getUser());

        if (!$OrderPdf) {
            $OrderPdf = new OrderPdf();
            $OrderPdf
                ->setTitle(trans('admin.order.delivery_note_title__default'))
                ->setMessage1(trans('admin.order.delivery_note_message__default1'))
                ->setMessage2(trans('admin.order.delivery_note_message__default2'))
                ->setMessage3(trans('admin.order.delivery_note_message__default3'));
        }

        /**
         * @var FormBuilder
         */
        $builder = $this->formFactory->createBuilder(OrderPdfType::class, $OrderPdf);

        /* @var \Symfony\Component\Form\Form $form */
        $form = $builder->getForm();

        // Formへの設定
        $form->get('ids')->setData(implode(',', $ids));

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/%eccube_admin_route%/order/export/pdf/download", name="admin_order_pdf_download")
     * @Template("@admin/Order/order_pdf.twig")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function exportPdfDownload(Request $request, OrderPdfService $orderPdfService)
    {
        /**
         * @var FormBuilder
         */
        $builder = $this->formFactory->createBuilder(OrderPdfType::class);

        /* @var \Symfony\Component\Form\Form $form */
        $form = $builder->getForm();
        $form->handleRequest($request);

        // Validation
        if (!$form->isValid()) {
            log_info('The parameter is invalid!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }

        $arrData = $form->getData();

        // 購入情報からPDFを作成する
        $status = $orderPdfService->makePdf($arrData);

        // 異常終了した場合の処理
        if (!$status) {
            $this->addError('admin.order.export.pdf.download.failure', 'admin');
            log_info('Unable to create pdf files! Process have problems!');

            return $this->render('@admin/Order/order_pdf.twig', [
                'form' => $form->createView(),
            ]);
        }

        // ダウンロードする
        $response = new Response(
            $orderPdfService->outputPdf(),
            200,
            ['content-type' => 'application/pdf']
        );

        $downloadKind = $form->get('download_kind')->getData();

        // レスポンスヘッダーにContent-Dispositionをセットし、ファイル名を指定
        if ($downloadKind == 1) {
            $response->headers->set('Content-Disposition', 'attachment; filename="'.$orderPdfService->getPdfFileName().'"');
        } else {
            $response->headers->set('Content-Disposition', 'inline; filename="'.$orderPdfService->getPdfFileName().'"');
        }

        log_info('OrderPdf download success!', ['Order ID' => implode(',', $request->get('ids', []))]);

        $isDefault = isset($arrData['default']) ? $arrData['default'] : false;
        if ($isDefault) {
            // Save input to DB
            $arrData['admin'] = $this->getUser();
            $this->orderPdfRepository->save($arrData);
        }

        return $response;
    }
}
