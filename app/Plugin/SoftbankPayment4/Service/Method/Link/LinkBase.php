<?php

namespace Plugin\SoftbankPayment4\Service\Method\Link;

use Eccube\Entity\Order;
use Eccube\Service\Payment\PaymentDispatcher;
use Eccube\Service\Payment\PaymentMethodInterface;
use Symfony\Component\Form\FormInterface;

/**
 * リンク型決済 基底クラス
 */
class LinkBase implements PaymentMethodInterface
{
    public function verify() {
        // nop.
    }

    public function apply() {
        // PaymentController::sbpsLinkRequest()へ移譲
        $dispatcher = new PaymentDispatcher();
        $dispatcher
            ->setForward(true)
            ->setRoute('sbps_link_request');

        return $dispatcher;
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