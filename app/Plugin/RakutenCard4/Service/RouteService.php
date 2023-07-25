<?php

namespace Plugin\RakutenCard4\Service;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class RouteService.
 */
class RouteService
{
    /** @var ContainerInterface  */
    protected $container;

    public function __construct(
        ContainerInterface $container
    )
    {
        $this->container = $container;
    }

    /**
     * ルートの取得
     *
     * @return mixed|null
     */
    public function getRoute()
    {
        /** @var Request $Request */
        $Request = $this->container->get('request_stack')->getCurrentRequest();
        return $Request->attributes->get('_route');
    }

    /**
     * Requestの取得
     *
     * @param string $kind
     * @return Request
     */
    public function getRequest($kind = 'current')
    {
        switch ($kind){
            case 'current':
                $Request = $this->container->get('request_stack')->getCurrentRequest();
                break;
            default:
                $Request = $this->container->get('request_stack')->getMasterRequest();
                break;
        }

        /** @var Request $Request */
        return $Request;
    }

}
