<?php

namespace Plugin\RakutenCard4\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

trait UserTrait
{
    /** @var ContainerInterface */
    protected $container;

    /**
     * Get a user from the Security Token Storage.
     *
     * @return UserInterface|object|null
     *
     * @see TokenInterface::getUser()
     *
     * @final since version 3.4
     */
    protected function getUser()
    {
        if (null === $token = $this->container->get('security.token_storage')->getToken()) {
            return null;
        }

        if (!\is_object($user = $token->getUser())) {
            // e.g. anonymous authentication
            return null;
        }

        return $user;
    }

}
