<?php

namespace Plugin\RakutenCard4\Service;

use Eccube\Entity\Order;

interface PaymentServiceInterface
{
    /**
     * 与信
     * @param Order $Order
     * @return bool
     */
    public function Authorize(Order $Order);

    /**
     * 売上
     *
     * @param Order $Order
     * @return bool
     */
    public function Capture(Order $Order);

    /**
     * キャンセル
     *
     * @param Order $Order
     * @return bool
     */
    public function CancelOrRefund(Order $Order);
}
