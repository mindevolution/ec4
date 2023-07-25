<?php

namespace Plugin\SoftbankPayment4\Client;

use Plugin\SoftbankPayment4\Adapter\SbpsAdapter as Adapter;
use Plugin\SoftbankPayment4\Factory\SbpsExceptionFactory;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class CreditCardInfoClient
{
    private const REQUEST_DELETE    = 'MG02-00103-101';
    private const REQUEST_INFO      = 'MG02-00104-101';
    private const REQUEST_STORE     = 'MG02-00131-101';
    private const REQUEST_UPDATE    = 'MG02-00132-101';

    private const ERR_NOT_REGISTERED = '10195999';

    private $Customer = null;
    private $Order = null;

    /**
     * @var Adapter
     */
    private $adapter;
    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var SbpsExceptionFactory
     */
    private $sbpsExceptionFactory;

    public function __construct(
        Adapter $adapter,
        ConfigRepository $configRepository,
        SbpsExceptionFactory $sbpsExceptionFactory,
        TradeHelper $tradeHelper
    )
    {
        $this->adapter = $adapter;
        $this->configRepository = $configRepository;
        $this->sbpsExceptionFactory = $sbpsExceptionFactory;
        $this->tradeHelper = $tradeHelper;
    }

    public function implement()    // TODO: 命名変更すべき.
    {
        $sxe = $this->adapter->initSxe(self::REQUEST_INFO);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter());

        $response = $this->adapter->request($xmlParam);

        if ($response['res_result'] === 'NG') {

            // 顧客情報未登録のエラーは通す.
            if ($response['res_err_code'] === self::ERR_NOT_REGISTERED) {
                return null;
            }

            // それ以外のNGは落とす.
            throw $this->sbpsExceptionFactory->create($response['res_err_code']);
        }

        return $response;
    }

    public function store($cardInfo)
    {
        $sxe = $this->adapter->initSxe(self::REQUEST_STORE);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createStoreParameter($cardInfo));

        $response = $this->adapter->request($xmlParam);

        if ($response['res_result'] !== 'OK') {
            throw $this->sbpsExceptionFactory->create($response['res_err_code']);
        }

        return $response;
    }

    public function update($cardInfo)
    {
        $sxe = $this->adapter->initSxe(self::REQUEST_UPDATE);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createStoreParameter($cardInfo));

        $response = $this->adapter->request($xmlParam);

        if ($response['res_result'] !== 'OK') {
            throw $this->sbpsExceptionFactory->create($response['res_err_code']);
        }

        return $response;
    }

    public function delete()
    {
        $sxe = $this->adapter->initSxe(self::REQUEST_DELETE);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter());

        $response = $this->adapter->request($xmlParam);

        if ($response['res_result'] === 'NG') {
            throw $this->sbpsExceptionFactory->create($response['res_err_code']);
        }

        return $response;
    }

    private function createParameter()
    {
        $Config = $this->configRepository->get();

        $custCode = $this->Customer !== null ? $this->Customer->getId() : $this->tradeHelper->payoutCustCode($this->Order);

        $param = [
            'merchant_id' => $Config->getMerchantId(),
            'service_id' => $Config->getServiceId(),
            'cust_code' => $custCode,
            'response_info_type' => 2,
            'encrypted_flg' => 1,
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];

        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        return $param;
    }

    private function createStoreParameter($cardInfo)
    {
        $param = [
            'merchant_id' => $cardInfo['merchant_id'],
            'service_id' => $cardInfo['service_id'],
            'cust_code' => $this->Customer->getId(),
            'pay_option_manage' => [
                'token' => $cardInfo['token'],
                'token_key' => $cardInfo['token_key'],
            ],
            'encrypted_flg' => 1,
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];
        $Config = $this->configRepository->get();
        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        return $param;
    }

    public function setOrder($Order)
    {
        $this->Order = $Order;
        return $this;
    }

    public function setCustomer($Customer)
    {
        $this->Customer = $Customer;
        return $this;
    }
}