<?php

namespace Plugin\SSNext;

use Doctrine\ORM\EntityManager;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\MailHistory;
use Eccube\Entity\Order;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Eccube\Repository\BaseInfoRepository;
use Eccube\Repository\MailHistoryRepository;
use Eccube\Service\PointHelper;
use Plugin\SSNext\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SSNextEvent implements EventSubscriberInterface
{

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @var MailHistoryRepository
     */
    protected $mailHistoryRepository;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @var BaseInfo
     */
    protected $baseInfo;

    protected $pointHelper;

    public function __construct(ConfigRepository $configRepository,
                                \Twig_Environment $twig,
                                \Swift_Mailer $mailer,
                                EntityManager $entityManager,
                                BaseInfoRepository $baseInfoRepository,
                                MailHistoryRepository $mailHistoryRepository,
                                PointHelper $pointHelper)
    {
        $this->configRepository = $configRepository;
        $this->mailer = $mailer;
        $this->twig = $twig;
        $this->mailHistoryRepository = $mailHistoryRepository;
        $this->entityManager = $entityManager;
        $this->baseInfo = $baseInfoRepository->get();
        $this->pointHelper = $pointHelper;
    }

    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::MAIL_ORDER => 'onMailOrder',
            '@admin/Product/index.twig' => 'onAdminProductIndexTwig',
        ];
    }

    public function onAdminProductIndexTwig(TemplateEvent $event)
    {
        $event->addSnippet('@SSNext/admin/Product/index.twig');
    }

    public function onMailOrder(EventArgs $eventArgs)
    {
        if ($this->configRepository->get()->getOrderMail() == "") {
            return;
        }

        /** @var Order $Order */
        $Order = $eventArgs->getArgument('Order');
        if ($Order->isNextSendFlg()) {
            return;
        }

        $body = $this->twig->render('@SSNext/default/Mail/order.twig', [
            'Order' => $Order,
            'point_price' => -$this->pointHelper->pointToDiscount($Order->getUsePoint()),
        ]);

        $message = (new \Swift_Message())
            ->setSubject('NE受注取り込み用メール')
            ->setFrom(array($this->baseInfo->getEmail01() => $this->baseInfo->getShopName()))
            ->setTo($this->configRepository->get()->getOrderMail())
            ->setBcc($this->baseInfo->getEmail04())
            ->setBody($body);

        $this->mailer->send($message);

        /*
        $MailHistory = new MailHistory();
        $MailHistory->setMailSubject($message->getSubject())
            ->setMailBody($message->getBody())
            ->setOrder($Order)
            ->setSendDate(new \DateTime());

        $this->mailHistoryRepository->save($MailHistory);
        */
        $this->entityManager->persist($Order);
    }
}