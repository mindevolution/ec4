<?php

namespace Plugin\SoftbankPayment4\Factory;

use Plugin\SoftbankPayment4\Entity\SbpsTrade;
use Plugin\SoftbankPayment4\Entity\Master\CaptureType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Symfony\Component\HttpFoundation\Request;

class SbpsTradeFactory
{
    private $captureType;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
        $this->captureType = $this->configRepository->get()->getCaptureType();
    }

    /**
     * SBPSからの通知から明細を生成する.
     * 第三引数を指定しない場合は決済成功時のステータス判断ロジックを呼び出す.
     *
     * @param Request $request
     * @param $Order
     * @param null $status
     * @return SbpsTrade
     */
    public function create(Request $request, $Order, $status = null): SbpsTrade
    {
        $status = $status ?? SbpsStatusType::getSuccessStatus($request->get('pay_method'), $this->captureType);

        return SbpsTrade::create(
            $request->get('res_tracking_id'),
            $status,
            $request->get('amount'),
            CaptureType::getCapturedAmount($request->get('pay_method'), $this->captureType, $request->get('amount')),
            $Order
        );
    }

    /**
     * API型のレスポンスから取引を生成する.
     *
     * @param array $response
     * @param $Order
     * @param string $payMethod
     *
     * @return SbpsTrade
     */
    public function createByResponse(array $response, $Order, string $payMethod): SbpsTrade
    {
        $status = SbpsStatusType::getSuccessStatus($payMethod, $this->captureType);

        $amount = (int) $Order->getPaymentTotal();

        return SbpsTrade::create(
            $response['res_tracking_id'],
            $status,
            $amount,
            CaptureType::getCapturedAmount($payMethod, $this->captureType, $amount),
            $Order
        );
    }
}
