<?php

namespace Plugin\SoftbankPayment4\Client;

use Plugin\SoftbankPayment4\Adapter\SbpsAdapter as Adapter;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class CommitClient
{
    public const ACTION_CODE = 'ST02-00101-101';

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

    public function __construct(
        Adapter $adapter,
        ConfigRepository $configRepository,
        TradeHelper $tradeHelper
    )
    {
        $this->adapter = $adapter;
        $this->configRepository = $configRepository;
        $this->tradeHelper = $tradeHelper;
    }

    public function can():bool
    {
        return true;
    }

    public function handle($response)
    {
        $sxe = $this->adapter->initSxe(self::ACTION_CODE);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter($response));
        $response = $this->adapter->request($xmlParam);

        return $response;
    }


    public function createParameter($response): array
    {
        $Config = $this->configRepository->get();

        $param = [
            'merchant_id' => $Config->getMerchantId(),
            'service_id' => $Config->getServiceId(),
            'tracking_id' => $response['res_tracking_id'],
            'processing_datetime' => $this->tradeHelper->createRequestDate(),
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];
        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'utf-8');

        return $param;
    }
}
