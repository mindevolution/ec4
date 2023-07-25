<?php

namespace Plugin\SoftbankPayment4\Service\Method\Api;

use Eccube\Service\Payment\PaymentDispatcher;
use Plugin\SoftbankPayment4\Service\Method\Api\ApiBase;

class CvsDeferredApi extends ApiBase
{
    public function apply() {
        $dispatcher = new PaymentDispatcher();
        $dispatcher
            ->setForward(true)
            ->setRoute('sbps_api_cvs_checkout');
        return $dispatcher;
    }
}
