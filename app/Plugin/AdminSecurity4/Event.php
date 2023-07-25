<?php

namespace Plugin\AdminSecurity4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Member;
use Eccube\Repository\MemberRepository;
use Eccube\Request\Context;
use Eccube\Util\StringUtil;
use Plugin\AdminSecurity4\Entity\Config;
use Plugin\AdminSecurity4\Entity\LoginRecord;
use Plugin\AdminSecurity4\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Security\Core\AuthenticationEvents;
use Symfony\Component\Security\Core\Event\AuthenticationFailureEvent;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

class Event implements EventSubscriberInterface
{
    private $em;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var MemberRepository
     */
    private $memberRepository;

    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;

    /**
     * @var Context
     */
    private $requestContext;

    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(
        EntityManagerInterface $em,
        MemberRepository $memberRepository,
        RequestStack $requestStack,
        Context $requestContext,
        Config $eccubeConfig,
        ConfigRepository $configRepository
    ) {
        $this->em = $em;
        $this->requestStack = $requestStack;
        $this->memberRepository = $memberRepository;
        $this->eccubeConfig = $eccubeConfig;
        $this->requestContext = $requestContext;
        $this->configRepository = $configRepository;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            SecurityEvents::INTERACTIVE_LOGIN => 'onInteractiveLogin',
            AuthenticationEvents::AUTHENTICATION_FAILURE => 'onAuthenticationFailure',
            'kernel.request' => ['onKernelRequest', 512],
        ];
    }

    /**
     * @param InteractiveLoginEvent $event
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $request = $event->getRequest();
        $user = $event
            ->getAuthenticationToken()
            ->getUser();

        if ($user instanceof Member) {
            $LoginRecord = new LoginRecord();
            $LoginRecord
                ->setLoginUser($user)
                ->setUserName($user->getUsername())
                ->setSuccessFlg(Constant::ENABLED)
                ->setClientIp($request->getClientIp())
            ;

            $this->em->persist($LoginRecord);
            $this->em->flush($LoginRecord);
        }
    }

    /**
     * @param AuthenticationFailureEvent $event
     */
    public function onAuthenticationFailure(AuthenticationFailureEvent $event)
    {
        if (!$this->requestContext->isAdmin()) {
            return;
        }

        $request = $this->requestStack->getCurrentRequest();

        $userName = $event->getAuthenticationToken()->getUsername();
        $Member = null;
        if ($userName) {
            $Member = $this->memberRepository->findOneBy(['login_id' => $userName]);
        }

        $LoginRecord = new LoginRecord();
        $LoginRecord
            ->setLoginUser($Member)
            ->setUserName($userName)
            ->setSuccessFlg(Constant::DISABLED)
            ->setClientIp($request->getClientIp())
        ;

        $this->em->persist($LoginRecord);
        $this->em->flush($LoginRecord);
    }

    /**
     * @param GetResponseEvent $event
     */
    public function onKernelRequest(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $Config = $this->configRepository->get();
        if (!$Config) {
            return;
        }
        /* @var $Config Config */
        $denyHosts = array_filter(explode("\n", StringUtil::convertLineFeed($Config->getAdminDenyHosts())), function ($var) {
            return StringUtil::isNotBlank($var);
        });

        if (empty($denyHosts)) {
            return;
        }

        if ($this->requestContext->isAdmin()) {
            if (array_search($event->getRequest()->getClientIp(), $denyHosts) !== false) {
                throw new AccessDeniedHttpException();
            }
        }
    }
}
