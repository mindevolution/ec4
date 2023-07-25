<?php

namespace Plugin\AdminSecurity4;

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'admin_record' => [
                'id' => 'admin_record',
                'name' => 'admin_record.title',
                'icon' => 'fa-users',
                'children' => [
                    'admin_record_login_record' => [
                        'id' => 'admin_record_login_record',
                        'name' => 'admin.login_record.index.title',
                        'url' => 'plg_admin_record_login_record',
                    ],
                    'admin_record_config' => [
                        'id' => 'admin_record_config',
                        'name' => 'admin.login_record.config.title',
                        'url' => 'plg_admin_record_config',
                    ],
                ],
            ],
        ];
    }
}
