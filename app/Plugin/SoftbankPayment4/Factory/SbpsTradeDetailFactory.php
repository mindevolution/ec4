<?php

namespace Plugin\SoftbankPayment4\Factory;

use Plugin\SoftbankPayment4\Entity\SbpsTradeDetail;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeType as TradeType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeDetailResultType as ResultType;
use Plugin\SoftbankPayment4\Entity\SbpsTrade;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;

class SbpsTradeDetailFactory
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    /**
     * endpointで受けたリクエストから取引詳細を生成する.
     *
     * @param Request $request
     * @param $Trade
     * @param null $tradeType
     *
     * @return SbpsTradeDetail
     */
    public function create(Request $request, $Trade, $tradeType = null): SbpsTradeDetail
    {
        $captureType = $this->configRepository->get()->getCaptureType();
        if ($tradeType === null) {
            $tradeType = $captureType === TradeType::CAPTURE ? TradeType::CAPTURE : TradeType::AR;
        }

        $Detail = SbpsTradeDetail::create(
            $request->get('res_sps_transaction_id'),
            $tradeType,
            ResultType::getResultType($request->get('res_result')),
            $request->get('amount'),
            $request->get('res_err_code'),
            $request->get('res_date'),
            $Trade
        );

        return $Detail;
    }

    /**
     * API型のレスポンスから取引詳細を生成する.
     *
     * @param array $response
     * @param $Trade
     * @param null $tradeType
     * @param $amount
     *
     * @return SbpsTradeDetail
     */
    public function createByResponse(array $response, $Trade, $tradeType = null, $amount): SbpsTradeDetail
    {
        $captureType = $this->configRepository->get()->getCaptureType();
        if ($tradeType === null) {
            $tradeType = $captureType === TradeType::CAPTURE ? TradeType::CAPTURE : TradeType::AR;
        }

        $error_code = !empty($response['res_err_code']) ? $response['res_err_code'] : null;
        $transaction_id = !empty($response['res_sps_transaction_id']) ? $response['res_sps_transaction_id'] : null;

        return SbpsTradeDetail::create(
            $transaction_id,
            $tradeType,
            ResultType::getResultType($response['res_result']),
            $amount,
            $error_code,
            $response['res_date'],
            $Trade
        );
    }
}
