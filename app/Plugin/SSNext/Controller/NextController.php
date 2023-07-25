<?php

namespace Plugin\SSNext\Controller;

use Doctrine\ORM\EntityManager;
use Eccube\Controller\AbstractController;
use Eccube\Entity\ProductClass;
use Eccube\Repository\ProductClassRepository;
use Plugin\SSNext\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class NextController extends AbstractController
{

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var ProductClassRepository
     */
    protected $productClassRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    public function __construct(EntityManager $entityManager,
                                ProductClassRepository $productClassRepository,
                                ConfigRepository $configRepository)
    {
        $this->entityManager = $entityManager;
        $this->productClassRepository = $productClassRepository;
        $this->configRepository = $configRepository;
    }

    /**
     * @Route("/next/stock", name="next_stock")
     *
     * @param Request $request
     * @return Response
     */
    public function stock(Request $request)
    {

        $storeAccount = $request->get('StoreAccount');
        $code = $request->get('Code');
        $stock = $request->get('Stock');
        $ts = $request->get('ts');
        $sig = $request->get('.sig', $request->get('_sig'));

        log_info(sprintf("[ss_next] ip: %s StoreAccount: %s Code: %s ts: %s sig: %s",
            $request->getClientIp(), $storeAccount, $code, $stock, $ts, $sig));

        $respXml = <<<EOF
<?xml version="1.0" encoding="EUC-JP"?>
<ShoppingUpdateStock version="1.0">
<ResultSet TotalResult="1">
<Request>
<Argument Name="StoreAccount" Value="{$storeAccount}" />
<Argument Name="Code" Value="{$code}" />
<Argument Name="Stock" Value="{$stock}" />
<Argument Name="ts" Value="{$ts}" />
<Argument Name="sig" Value="{$sig}" />
</Request>
EOF;

        if ($storeAccount == '' ||
            $code == '' ||
            $stock == '' ||
            $ts == '' ||
            $sig == '') {

            $respXml .= <<<EOF
<Result No="1">
<Processed>1</Processed>
</Result>
<Message>パラメータが不足しています</Message>
EOF;
        } else {
            //パラメータチェック
            $md5_data = md5(sprintf('StoreAccount=%s&Code=%s&Stock=%d&ts=%s',
                    $storeAccount, $code, $stock, $ts) . $this->configRepository->get()->getApiKey());

            /*
            log_info(sprintf("[ss_next]============= %s", sprintf('StoreAccount=%s&Code=%s&Stock=%d&ts=%s',
                $storeAccount, $code, $stock, $ts)));
            */

            if ($sig != $md5_data) {

                $respXml .= <<<EOF
<Result No="1">
<Processed>1</Processed>
</Result>
<Message>認証に失敗</Message>
EOF;
            } else {

                $isOk = false;

                $this->entityManager->beginTransaction();

                try {

                    //if ($this->configRepository->get()->isProductCode()) {
                        $productClass = $this->productClassRepository->findOneBy(['code' => $code, 'visible' => true]);
                    //} else {
                    //    if (is_numeric($code)) {
                    //        $productClass = $this->productClassRepository->find(intval($code));
                    //    } else {
                    //        log_info("[ss_next] code not int");
                    //    }
                    //}

                    /** @var ProductClass $productClass */
                    if ($productClass) {
                        $productClass->setStock($stock);
                        $productClass->getProductStock()->setStock($stock);
                        $this->entityManager->persist($productClass);
                        $this->entityManager->flush();

                        $isOk = true;

                    } else {

                        $respXml .= <<<EOF
<Result No="1">
<Processed>1</Processed>
</Result>
<Message>該当の商品がありません</Message>
EOF;
                    }

                } catch (\Exception $exception) {
                    $this->entityManager->rollback();
                    $respXml .= <<<EOF
<Result No="1">
<Processed>1</Processed>
</Result>
<Message>{$exception->getMessage()}</Message>
EOF;
                    log_error(sprintf("[ss_next] err: %s", $exception->getMessage()));
                }

                if ($isOk) {
                    $this->entityManager->commit();
                    $respXml .= <<<EOF
<Result No="1">
<Processed>0</Processed>
</Result>
EOF;
                }
            }
        }


        $respXml .= <<<EOF
</ResultSet>
</ShoppingUpdateStock>
EOF;

        log_info(sprintf("[ss_next] response %s", $respXml));

        $response = new Response($respXml);
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }
}