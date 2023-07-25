<?php

namespace Plugin\SoftbankPayment4;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'sbps_admin_trade_status' => [
                        'name' => 'SBPS決済管理',
                        'url' => 'sbps_admin_trade_status',
                    ]
                ]
            ]
        ];
    }
}
