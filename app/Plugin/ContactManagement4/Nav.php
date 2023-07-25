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

use Eccube\Common\EccubeNav;

class Nav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'contact' => [
                'id' => 'contact',
                'name' => 'contact.title',
                'icon' => 'fa-envelope',
                'children' => [
                    'contact' => [
                        'id' => 'contact',
                        'name' => 'admin.contact.index.title',
                        'url' => 'plugin_contact',
                    ],
                    'contact_new' => [
                        'id' => 'contact_new',
                        'name' => 'admin.contact.new.title',
                        'url' => 'plugin_contact_new',
                    ],
                ],
            ],
        ];
    }
}
