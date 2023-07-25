<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\StripePaymentGateway;

use Eccube\Common\EccubeNav;

class StripePaymentGatewayNav implements EccubeNav
{
    /**
     * @return array
     */
    public static function getNav()
    {
        return [
            'stripe' => [
                'name' => 'stripe_payment_gateway.admin.nav.stripe',
                'icon' => 'fa-cc-stripe',
                'children' => [
                    'stripe_log' => [
                        'name' => 'stripe_payment_gateway.admin.nav.stripe_log',
                        'url' => 'stripe_payment_gateway_admin_log',
                    ],
                    'stripe_config' => [
                        'name' => 'stripe_payment_gateway.admin.nav.stripe_config',
                        'url' => 'stripe_payment_gateway_admin_config',
                    ]
                ],
            ],
        ];
    }
}