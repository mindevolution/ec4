<?php

namespace Plugin\SoftbankPayment4;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/confirm.twig'     => 'onShoppingConfirm',

            'Mypage/index.twig'         => 'addCreditInfoAdminNavi',
            'Mypage/favorite.twig'      => 'addCreditInfoAdminNavi',
            'Mypage/change.twig'        => 'addCreditInfoAdminNavi',
            'Mypage/delivery.twig'      => 'addCreditInfoAdminNavi',
            'Mypage/delivery_edit.twig' => 'addCreditInfoAdminNavi',
            'Mypage/withdraw.twig'      => 'addCreditInfoAdminNavi',

            '@SoftbankPayment4/default/mypage/credit/index.twig' => 'addCreditInfoAdminNavi',
            '@SoftbankPayment4/default/mypage/credit/edit.twig' => 'addCreditInfoAdminNavi',

        ];
    }

    public function onShoppingConfirm(TemplateEvent $event)
    {
        $Order = $event->getParameter('Order');
        $method_class = $Order
            ->getPayment()
            ->getMethodClass();

        if(strpos($method_class, 'Plugin\SoftbankPayment4\Service\Method\Link') !== false) {
            $event->addSnippet('@SoftbankPayment4/default/Shopping/confirm_button.twig');
        }
    }

    public function addCreditInfoAdminNavi(TemplateEvent $event)
    {
        $event->addSnippet('@SoftbankPayment4/default/mypage/add_navi.twig');
    }
}
