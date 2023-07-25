<?php

namespace Plugin\RakutenCard4\EventSubscriber;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminOrderEvent implements EventSubscriberInterface
{

    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    )
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * リッスンしたいサブスクライバのイベント名の配列を返します。
     * 配列のキーはイベント名、値は以下のどれかをしてします。
     * - 呼び出すメソッド名
     * - 呼び出すメソッド名と優先度の配列
     * - 呼び出すメソッド名と優先度の配列の配列
     * 優先度を省略した場合は0
     *
     * 例：
     * - array('eventName' => 'methodName')
     * - array('eventName' => array('methodName', $priority))
     * - array('eventName' => array(array('methodName1', $priority), array('methodName2')))
     *
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Order/edit.twig' => 'onAdminOrderEditTwig',
            '@admin/Order/index.twig' => 'onAdminOrderIndexTwig',
        ];
    }

    public function onAdminOrderEditTwig(TemplateEvent $event)
    {
        $event->addSnippet('@RakutenCard4/admin/order_edit.twig');
    }

    public function onAdminOrderIndexTwig(TemplateEvent $event)
    {
        $event->addSnippet('@RakutenCard4/admin/order_index.twig');
        $pagination = $event->getParameter('pagination');

        $OrderIds = [];
        /** @var Order $Order */
        foreach ($pagination as $Order) {
            if ($Order instanceof Order) {
                foreach ($Order->getShippings() as $Shipping) {
                    $OrderIds[] = $Shipping->getId();
                }
            }
        }

        $RakutenPaymentStatuses = [
            ConstantPaymentStatus::Captured => 'rakuten_card4.payment_status_name.captured',
            ConstantPaymentStatus::Canceled => 'rakuten_card4.payment_status_name.canceled'
        ];

        $event->setParameter('order_ids', $OrderIds);
        $event->setParameter('RakutenPaymentStatuses', $RakutenPaymentStatuses);
    }
}
