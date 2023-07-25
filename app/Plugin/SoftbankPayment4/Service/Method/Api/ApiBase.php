<?php

namespace Plugin\SoftbankPayment4\Service\Method\Api;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Order;
use Eccube\Repository\Master\OrderStatusRepository;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Symfony\Component\Form\FormInterface;

/**
 * API型決済 基底クラス
 */
class ApiBase implements PaymentMethodInterface
{
    public function verify() {
        // nop.
    }

    public function apply() {
        // nop.
    }

    public function checkout() {
        // nop.
    }

    public function setFormType(FormInterface $form) {
        $this->form = $form;
    }

    public function setOrder(Order $Order) {
        $this->Order = $Order;
    }
}
