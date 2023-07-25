<?php

namespace Plugin\SSNext\Controller\Admin;

use Eccube\Controller\AbstractController;
use Eccube\Entity\ExportCsvRow;
use Eccube\Entity\Master\CsvType;
use Eccube\Service\CsvExportService;
use Plugin\SSNext\PluginManager;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductController extends AbstractController
{

    /**
     * @var CsvExportService
     */
    protected $csvExportService;

    public function __construct(CsvExportService $csvExportService)
    {
        $this->csvExportService = $csvExportService;
    }

    /**
     * 商品CSVの出力.
     *
     * @Route("/%eccube_admin_route%/next/product/export", name="ss_next_admin_product_export")
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
            $this->csvExportService->initCsvType(PluginManager::CSV_TYPE);

            // ヘッダ行の出力.
            $this->csvExportService->exportHeader();

            // 商品データ検索用のクエリビルダを取得.
            $qb = $this->csvExportService
                ->getProductQueryBuilder($request);

            // Get stock status
            $isOutOfStock = 0;
            $session = $request->getSession();
            if ($session->has('eccube.admin.product.search')) {
                $searchData = $session->get('eccube.admin.product.search', []);
                if (isset($searchData['stock_status']) && $searchData['stock_status'] === 0) {
                    $isOutOfStock = 1;
                }
            }

            // joinする場合はiterateが使えないため, select句をdistinctする.
            // http://qiita.com/suin/items/2b1e98105fa3ef89beb7
            // distinctのmysqlとpgsqlの挙動をあわせる.
            // http://uedatakeshi.blogspot.jp/2010/04/distinct-oeder-by-postgresmysql.html
            $qb->resetDQLPart('select')
                ->resetDQLPart('orderBy')
                ->orderBy('p.update_date', 'DESC');

            if ($isOutOfStock) {
                $qb->select('p, pc')
                    ->distinct();
            } else {
                $qb->select('p')
                    ->distinct();
            }
            // データ行の出力.
            $this->csvExportService->setExportQueryBuilder($qb);

            $this->csvExportService->exportData(function ($entity, CsvExportService $csvService) use ($request) {
                $Csvs = $csvService->getCsvs();

                /** @var $Product \Eccube\Entity\Product */
                $Product = $entity;

                /** @var $ProductClasses \Eccube\Entity\ProductClass[] */
                $ProductClasses = $Product->getProductClasses();

                foreach ($ProductClasses as $ProductClass) {

                    if ($ProductClass->isVisible()) {
                        $ExportCsvRow = new ExportCsvRow();

                        // CSV出力項目と合致するデータを取得.
                        foreach ($Csvs as $Csv) {
                            // 商品データを検索.
                            $ExportCsvRow->setData($csvService->getData($Csv, $Product));
                            if ($ExportCsvRow->isDataNull()) {
                                // 商品規格情報を検索.
                                $ProductClass->setPrice02(null);
                                $ExportCsvRow->setData($csvService->getData($Csv, $ProductClass));
                            }

                            $ExportCsvRow->pushData();
                        }

                        // $row[] = number_format(memory_get_usage(true));
                        // 出力.
                        $csvService->fputcsv($ExportCsvRow->getRow());
                    }
                }
            });
        });

        $now = new \DateTime();
        $filename = 'product_'.$now->format('YmdHis').'.csv';
        $response->headers->set('Content-Type', 'application/octet-stream');
        $response->headers->set('Content-Disposition', 'attachment; filename='.$filename);
        $response->send();

        log_info('商品CSV出力ファイル名', [$filename]);

        return $response;
    }
}