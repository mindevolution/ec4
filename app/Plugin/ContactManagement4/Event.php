<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Event\EccubeEvents;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Plugin\ContactManagement4\Entity\Contact;
use Plugin\ContactManagement4\Repository\ContactStatusRepository;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @var AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var ContactStatusRepository
     */
    private $contactStatusRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Event constructor.
     */
    public function __construct(
        AuthorizationCheckerInterface $authorizationChecker,
        TokenStorageInterface $tokenStorage,
        EntityManagerInterface $entityManager,
        ContactStatusRepository $contactStatusRepository,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->authorizationChecker = $authorizationChecker;
        $this->tokenStorage = $tokenStorage;
        $this->entityManager = $entityManager;
        $this->contactStatusRepository = $contactStatusRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            EccubeEvents::FRONT_CONTACT_INDEX_COMPLETE => 'onContactComplete',
            '@admin/Customer/edit.twig' => 'onAdminCustomerEditTwig',
        ];
    }

    public function onContactComplete(EventArgs $event)
    {
        $data = $event->getArgument('data');
        $Contact = new Contact();
        // エンティティを更新
        $Contact
            ->setCustomer($this->authorizationChecker->isGranted('ROLE_USER') ? $this->tokenStorage->getToken()->getUser() : null)
            ->setContactDate(new \DateTime())
            ->setName01($data['name01'])
            ->setName02($data['name02'])
            ->setKana01(@$data['kana01'])
            ->setKana02(@$data['kana02'])
            ->setPostalCode(@$data['postal_code'])
            ->setPref(@$data['pref'])
            ->setAddr01(@$data['addr01'])
            ->setAddr02(@$data['addr02'])
            ->setPhoneNumber($data['phone_number'])
            ->setEmail($data['email'])
            ->setContents($data['contents'])
            ->setStatus($this->contactStatusRepository->findOneBy([], ['sort_no' => 'ASC']));

        $event = new EventArgs(
            [
                'form' => $event->getArgument('form'),
                'data' => $event->getArgument('data'),
                'Contact' => $Contact,
            ],
            $event->getRequest()
        );

        $this->eventDispatcher->dispatch('plugin.contact.index.complete', $event);

        if ($event->hasArgument('extra_save_items')) {
            $items = $event->getArgument('extra_save_items');

            if (!empty($items)) {
                $Contact->setExtra(json_encode($items));
            }
        }

        // DB更新
        $this->entityManager->persist($Contact);
        $this->entityManager->flush($Contact);
    }

    public function onAdminCustomerEditTwig(TemplateEvent $templateEvent)
    {
        $templateEvent->addSnippet('@ContactManagement4/admin/Customer/edit_js.twig');
    }
}
