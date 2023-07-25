<?php

namespace Plugin\SoftbankPayment4\Client;

use Doctrine\ORM\EntityManagerInterface;
use Plugin\SoftbankPayment4\Adapter\SbpsAdapter;
use Plugin\SoftbankPayment4\Factory\SbpsTradeFactory;
use Plugin\SoftbankPayment4\Factory\SbpsTradeDetailFactory;
use Plugin\SoftbankPayment4\Factory\SbpsExceptionFactory;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class CreditCheckoutClient
{
    private const ACTION_CODE = 'ST01-00131-101';

    private $Order;

    /**
     * @var SbpsAdapter
     */
    private $adapter;
    /**
     * @var CommitClient
     */
    private $commitClient;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var SbpsExceptionFactory
     */
    private $sbpsExceptionFactory;
    /**
     * @var SbpsTradeFactory
     */
    private $sbpsTradeFactory;
    /**
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var EntityManagerInterface
     */
    private $em;
    /**
     * @var SbpsTradeDetailFactory
     */
    private $sbpsTradeDetailFactory;

    public function __construct(
        CommitClient $commitClient,
        ConfigRepository $configRepository,
        EntityManagerInterface $em,
        SbpsAdapter $adapter,
        SbpsTradeDetailFactory $sbpsTradeDetailFactory,
        SbpsTradeFactory $sbpsTradeFactory,
        SbpsExceptionFactory $sbpsExceptionFactory,
        TradeHelper $tradeHelper
    )
    {
        $this->adapter = $adapter;
        $this->commitClient = $commitClient;
        $this->configRepository = $configRepository;
        $this->em = $em;
        $this->sbpsTradeDetailFactory = $sbpsTradeDetailFactory;
        $this->sbpsTradeFactory = $sbpsTradeFactory;
        $this->sbpsExceptionFactory = $sbpsExceptionFactory;
        $this->tradeHelper = $tradeHelper;
    }

    public function implement($cardInfo, $isGranted): void
    {
        $sxe = $this->adapter->initSxe(self::ACTION_CODE);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter($cardInfo, $isGranted));

        $response = $this->adapter->request($xmlParam);

        if ($response['res_result'] !== 'OK') {
            throw $this->sbpsExceptionFactory->create($response['res_err_code']);
        }

        $Trade = $this->sbpsTradeFactory->createByResponse($response, $this->Order, 'credit_api');
        $this->em->persist($Trade);

        $Detail = $this->sbpsTradeDetailFactory->createByResponse($response, $Trade, null, (int) $this->Order->getPaymentTotal());
        $this->em->persist($Detail);

        $commitResponse = $this->commitClient->handle($response);
        if ($commitResponse['res_result'] !== 'OK') {
            throw $this->sbpsExceptionFactory->create($response['res_err_code']);
        }
    }

    private function createParameter($cardInfo): array
    {
        $param = [
            'merchant_id' => $cardInfo['merchant_id'],
            'service_id' => $cardInfo['service_id'],
            'cust_code' => $this->tradeHelper->payoutCustCode($this->Order),
            'order_id' => $this->tradeHelper->createOrderId($this->Order),
            'item_id' => 'ec-cube',
            'tax' => (int) $this->tradeHelper->calculateOrderTax($this->Order),
            'amount' => (int) $this->Order->getPaymentTotal(),
            'free3' => $this->tradeHelper->getPluginVersion(),
            'pay_option_manage' => [],
            'encrypted_flg' => 1,
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];

        // 登録済カードを使わない場合
        if (!array_key_exists('use_stored_card', $cardInfo)) {
            $param['pay_option_manage'] = [
                'token' => $cardInfo['token'],
                'token_key' => $cardInfo['token_key'],
            ];

            // 今回使うカード情報を登録する.
            if (array_key_exists('store_card', $cardInfo)) {
                $param['pay_option_manage']['cust_manage_flg'] = 1;
            }
        }

        $Config = $this->configRepository->get();
        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        // マルチバイト文字列はBASE64エンコードする
        $param['free3'] = base64_encode($param['free3']);

        return $param;
    }

    public function setOrder($Order): CreditCheckoutClient
    {
        $this->Order = $Order;
        return $this;
    }
}
