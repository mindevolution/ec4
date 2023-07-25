<?php

namespace Plugin\RakutenCard4;

use Eccube\Common\EccubeNav;

class RakutenCard4Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'order' => [
                'children' => [
                    'admin_shipping_rakuten_csv_import' => [
                        'name' => 'rakuten_card4.admin.nav.csv_upload',
                        'url' => 'admin_shipping_rakuten_csv_import',
                    ],
                ],
            ],
        ];
    }
}
