<?php

namespace Plugin\RakutenCard4\EventSubscriber;

use Eccube\Event\TemplateEvent;
use Plugin\RakutenCard4\Service\CardService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FrontMypageEventSubscriber implements EventSubscriberInterface
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
            'Mypage/index.twig' => 'onTemplateMypageNavi',
            'Mypage/history.twig' => 'onTemplateMypageNavi',
            'Mypage/favorite.twig' => 'onTemplateMypageNavi',
            'Mypage/change.twig' => 'onTemplateMypageNavi',
            'Mypage/change_complete.twig' => 'onTemplateMypageNavi',
            'Mypage/delivery.twig' => 'onTemplateMypageNavi',
            'Mypage/delivery_edit.twig' => 'onTemplateMypageNavi',
            'Mypage/withdraw.twig' => 'onTemplateMypageNavi',
            'Mypage/withdraw_confirm.twig' => 'onTemplateMypageNavi',
            '@RakutenCard4/mypage_register_card.twig' => 'onTemplateMypageNavi',
        ];
    }

    /**
     * Append JS to display
     *
     * @param TemplateEvent $templateEvent
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function onTemplateMypageNavi(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@RakutenCard4/mypage_navi_add.twig');
    }

}