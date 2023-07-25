<?php

namespace Plugin\SoftbankPayment4\Controller\Admin;

use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Entity\Order;
use Eccube\Exception\ShoppingException;
use Eccube\Repository\OrderRepository;
use Eccube\Repository\Master\PageMaxRepository;
use Eccube\Util\FormUtil;
use Knp\Component\Pager\PaginatorInterface;
use Plugin\SoftbankPayment4\Client\CaptureClient;
use Plugin\SoftbankPayment4\Client\ParticalRefundClient;
use Plugin\SoftbankPayment4\Client\ReauthClient;
use Plugin\SoftbankPayment4\Client\RefundClient;
use Plugin\SoftbankPayment4\Entity\Master\SbpsActionType as ActionType;
use Plugin\SoftbankPayment4\Exception\SbpsException;
use Plugin\SoftbankPayment4\Factory\SbpsExceptionFactory;
use Plugin\SoftbankPayment4\Form\Type\Admin\ActionType as FormActionType;
use Plugin\SoftbankPayment4\Form\Type\Admin\SearchSbpsOrderType;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Repository\SbpsTradeRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

/**
 * 決済状況管理
 */
class TradeStatusController extends AbstractController
{
    /**
     * @var PageMaxRepository
     */
    private $pageMaxRepository;
    /**
     * @var OrderRepository
     */
    private $orderRepository;
    /**
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var CaptureClient
     */
    private $captureClient;
    /**
     * @var ParticalRefundClient
     */
    private $particalRefundClient;
    /**
     * @var RefundClient
     */
    private $refundClient;
    /**
     * @var ReauthClient
     */
    private $reauthClient;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var SbpsTradeRepository
     */
    private $tradeRepository;
    /**
     * @var SbpsExceptionFactory
     */
    private $sbpsExceptionFactory;

    public function __construct(
        CaptureClient $captureClient,
        ConfigRepository $configRepository,
        OrderRepository $orderRepository,
        PageMaxRepository $pageMaxRepository,
        ParticalRefundClient $particalRefundClient,
        ReauthClient $reauthClient,
        RefundClient $refundClient,
        SbpsExceptionFactory $sbpsExceptionFactory,
        SbpsTradeRepository $tradeRepository,
        TradeHelper $tradeHelper
    ) {
        $this->captureClient = $captureClient;
        $this->configRepository = $configRepository;
        $this->orderRepository = $orderRepository;
        $this->pageMaxRepository = $pageMaxRepository;
        $this->particalRefundClient = $particalRefundClient;
        $this->reauthClient = $reauthClient;
        $this->refundClient = $refundClient;
        $this->sbpsExceptionFactory = $sbpsExceptionFactory;
        $this->tradeHelper = $tradeHelper;
        $this->tradeRepository = $tradeRepository;
    }

    /**
     * クレジットカード決済状況一覧画面
     *
     * @Route("/%eccube_admin_route%/order/sbps/status", name="sbps_admin_trade_status")
     * @Route("/%eccube_admin_route%/order/sbps/status/{page_no}", requirements={"page_no" = "\d+"}, name="sbps_admin_trade_status_pageno")
     * @Template("@SoftbankPayment4/admin/trade_status.twig")
     *
     * @param Request $request
     * @param null $page_no
     * @param PaginatorInterface $paginator
     * @return array
     */
    public function index(Request $request, $page_no = null, PaginatorInterface $paginator): array
    {
        $searchForm = $this->formFactory
            ->createBuilder(SearchSbpsOrderType::class)
            ->getForm();

        $actionForm = $this->formFactory
            ->createNamedBuilder('', FormActionType::class)
            ->getForm();

        /**
         * ページの表示件数は, 以下の順に優先される.
         * - リクエストパラメータ
         * - セッション
         * - デフォルト値
         * また, セッションに保存する際は mtb_page_maxと照合し, 一致した場合のみ保存する.
         **/
        $page_count = $this->session->get('sbps.admin.trade_status.search.page_count',
            $this->eccubeConfig->get('eccube_default_page_count'));

        $page_count_param = (int) $request->get('page_count');
        $pageMaxis = $this->pageMaxRepository->findAll();

        if ($page_count_param) {
            foreach ($pageMaxis as $pageMax) {
                if ((string)$page_count_param === $pageMax->getName()) {
                    $page_count = $pageMax->getName();
                    $this->session->set('sbps.admin.trade_status.search.page_count', $page_count);
                    break;
                }
            }
        }

        if ($request->getMethod() === 'POST') {
            $searchForm->handleRequest($request);

            if ($searchForm->isValid()) {
                /**
                 * 検索が実行された場合は, セッションに検索条件を保存する.
                 * ページ番号は最初のページ番号に初期化する.
                 */
                $page_no = 1;
                $searchData = $searchForm->getData();

                // 検索条件, ページ番号をセッションに保持.
                $this->session->set('sbps.admin.trade_status.search', FormUtil::getViewData($searchForm));
                $this->session->set('sbps.admin.trade_status.search.page_no', $page_no);
            } else {
                // 検索エラーの際は, 詳細検索枠を開いてエラー表示する.
                return [
                    'searchForm' => $searchForm->createView(),
                    'actionForm' => $actionForm->createView(),
                    'pagination' => [],
                    'pageMaxis' => $pageMaxis,
                    'page_no' => $page_no,
                    'page_count' => $page_count,
                    'has_errors' => true,
                ];
            }
        } else if (null !== $page_no || $request->get('resume')) {
            /*
             * ページ送りの場合または、他画面から戻ってきた場合は, セッションから検索条件を復旧する.
             */
            if ($page_no) {
                // ページ送りで遷移した場合.
                $this->session->set('sbps.admin.trade_status.search.page_no', (int) $page_no);
            } else {
                // 他画面から遷移した場合.
                $page_no = $this->session->get('sbps.admin.trade_status.search.page_no', 1);
            }
            $viewData = $this->session->get('sbps.admin.trade_status.search', []);
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
            $this->session->set('sbps.admin.trade_status.search', $viewData);
            $this->session->set('sbps.admin.trade_status.search.page_no', $page_no);
        }
        $qb = $this->tradeRepository->createQueryBuilderForManageTrade($searchData);
        $pagination = $paginator->paginate($qb, $page_no, $page_count);

        $captureType = $this->configRepository->get()->getCaptureType();


        return [
            'searchForm' => $searchForm->createView(),
            'actionForm' => $actionForm->createView(),
            'pagination' => $pagination,
            'pageMaxis' => $pageMaxis,
            'page_no' => $page_no,
            'page_count' => $page_count,
            'has_errors' => false,
            'capture_type' => $captureType,
        ];
    }

    /**
     * 一括売上
     *
     * @Route("/%eccube_admin_route%/sbps/trade/action/bulk", name="sbps_admin_trade_action_bulk", methods={"POST"})
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function bulkExecAction(Request $request): RedirectResponse
    {
        $action = $request->get('action');

        if ($action === 'capture') {
            $Handler = $this->captureClient;
        } elseif ($action === 'refund') {
            $Handler = $this->refundClient;
        } else {
            throw new BadRequestHttpException();
        }

        $Orders = $this->orderRepository->findBy(['id' => $request->get('ids')]);
        try {
            $e = null;
            $arrException = [];

            foreach ($Orders as $Order) {
                $Handler->isBulk = true;
                $Handler->setOrder($Order);

                if ($Handler->can()) {
                    $e = $Handler->handle();
                } else {
                    $e = new SbpsException('受注番号：' . $Order->getOrderNo() . ' は処理できない受注です。', $e);
                }

                if ($e instanceof SbpsException) {
                    $arrException[$Order->getOrderNo()] = $e;
                }
            }

            if (!empty($arrException)) {
                throw end($arrException);
            } else {
                $this->addSuccess('正常に処理が終了しました。', 'admin');
            }

        } catch (SbpsException $e) {
            foreach ($arrException as $id => $e) {
                $message = '受注番号:' . $id . ' ' . $e->getMessage();
                $message .= '(エラーコード：' . $e->getCode() . ')';
                $this->addError($message, 'admin');
            }
        }

        return $this->redirectToRoute('sbps_admin_trade_status_pageno', ['resume' => Constant::ENABLED]);
    }

    /**
     * 決済操作
     * 受注の状態から判断し、操作を実行する
     *
     * @Route("/%eccube_admin_route%/sbps/trade/action", name="sbps_admin_trade_action", methods={"POST"})
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function execAction(Request $request): RedirectResponse
    {
        $Order = $this->orderRepository->findOneBy(['id' => $request ->get('id')]);
        if ($Order === null) {
            $this->addError('受注が見つかりません。', 'admin');
            return $this->redirectToRoute('sbps_admin_trade_status_pageno', ['resume' => Constant::ENABLED]);
        }
        $Client = $this->dispatchRequest($Order, $this->configRepository->get()->getCaptureType());

        try {
            $Client->setOrder($Order);
            $response = $Client->handle();

            $this->addSuccess('正常に処理が終了しました。', 'admin');
        } catch (ShoppingException $e) {
            $message = '受注番号:' . $Order->getOrderNo() . ' ';
            $message.= $e->getMessage() . '操作を中断しました。';

            $this->addError($message, 'admin');

        } catch (SbpsException $e) {
            $message = '受注番号:' . $Order->getOrderNo() . ' ';
            $message.= $this->sbpsExceptionFactory->parseErrorCodeToMessage($e->getCode());
            $message .= '(エラーコード：' . $e->getCode() . ')';

            logs('sbps')->error('orderNo:' . $Order->getOrderNo() . ' Action is failed. error_code=' . $e->getCode());

            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('sbps_admin_trade_status_pageno', ['resume' => Constant::ENABLED]);
    }

    private function dispatchRequest(Order $Order, $captureType)
    {
        $Trade = $Order->getSbpsTrade();

        switch ($Trade->getActable($captureType)) {
            case ActionType::CAPTURE:
            case ActionType::CAPTURE_PARTICAL:
                return $this->captureClient;
            case ActionType::PARTICAL_REFUND:
                return $this->particalRefundClient;
            case ActionType::REAUTH:
            case ActionType::MODIFY_AUTH:
                return $this->reauthClient;
            default:
                throw new BadRequestHttpException();
        }
    }

    /**
     * 個別取消・返金を実行する.
     *
     * @Route("/%eccube_admin_route%/sbps/trade/action/refund", name="sbps_admin_trade_action_refund", methods={"POST"})
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws Exception
     */
    public function execCancelAction(Request $request): RedirectResponse
    {
        $Order = $this->orderRepository->findOneBy(['id' => $request ->get('id')]);
        if ($Order === null) {
            $this->addError('受注が見つかりません。', 'admin');
            return $this->redirectToRoute('sbps_admin_trade_status_pageno', ['resume' => Constant::ENABLED]);
        }

        $Client = $this->refundClient;

        $Client->setOrder($Order);
        try {
            $response = $Client->handle();

            if ($response instanceof Exception) {
                throw $response;
            }

            $this->addSuccess('正常に処理が終了しました。', 'admin');
        } catch (SbpsException $e) {
            $message = '受注番号:' . $Order->getOrderNo() . ' ';
            $message.= $this->sbpsExceptionFactory->parseErrorCodeToMessage($e->getCode());
            $message .= '(エラーコード：' . $e->getCode() . ')';

            logs('sbps')->error('orderNo:' . $Order->getOrderNo() . 'FULL_REFUND failed. error_code=' . $e->getCode());

            $this->addError($message, 'admin');
        }

        return $this->redirectToRoute('sbps_admin_trade_status_pageno', ['resume' => Constant::ENABLED]);
    }
}
