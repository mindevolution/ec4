<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Service\Composer;

use Eccube\Entity\BaseInfo;
use Eccube\Repository\BaseInfoRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ComposerServiceFactory
{
    public static function createService(ContainerInterface $container)
    {
        return $container->get(ComposerApiService::class);
    }
}
