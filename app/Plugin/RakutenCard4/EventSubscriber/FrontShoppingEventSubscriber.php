<?php

namespace Plugin\RakutenCard4\EventSubscriber;

use Eccube\Entity\Order;
use Eccube\Event\TemplateEvent;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FrontShoppingEventSubscriber implements EventSubscriberInterface
{
    /** @var CardService */
    protected $cardService;

    public function __construct(
        CardService $cardService
    )
    {
        $this->cardService = $cardService;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Shopping/index.twig' => 'onTemplateShoppingIndex',
            'Shopping/confirm.twig' => 'onTemplateShoppingConfirm',
        ];
    }

    /**
     * 支払い方法のクラスの取得
     *
     * @param TemplateEvent $templateEvent
     * @return string|null
     */
    private function getMethodClass(TemplateEvent $templateEvent)
    {
        /** @var Order $Order */
        $Order = $templateEvent->getParameter('Order');
        if (!($Order instanceof Order)){
            return '';
        }

        return $Order->getPayment()->getMethodClass();
    }

    /**
     * Append JS to display
     *
     * @param TemplateEvent $templateEvent
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function onTemplateShoppingIndex(TemplateEvent $templateEvent)
    {
        switch ($this->getMethodClass($templateEvent)){
            case CreditCard::class:
                $CustomerTokens = $this->cardService->getFrontRegisterCards();
                $templateEvent->setParameter('token_url', $this->cardService->getPayVaultTokenUrl());
                $templateEvent->setParameter('service_id', $this->cardService->getServiceId());
                $templateEvent->setParameter('CustomerTokens', $CustomerTokens);

                $templateEvent->addSnippet('@RakutenCard4/card.twig');
                break;
            case Convenience::class:
                $templateEvent->addSnippet('@RakutenCard4/cvs.twig');
                break;
            default:
                break;
        }
    }

    /**
     * Append JS to display
     *
     * @param TemplateEvent $templateEvent
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function onTemplateShoppingConfirm(TemplateEvent $templateEvent)
    {
        switch ($this->getMethodClass($templateEvent)){
            case CreditCard::class:
                $templateEvent->addSnippet('@RakutenCard4/card_confirm.twig');
                break;
            case Convenience::class:
                $templateEvent->addSnippet('@RakutenCard4/cvs_confirm.twig');
                break;
            default:
                break;
        }
    }

}