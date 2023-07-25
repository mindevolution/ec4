<?php

namespace Plugin\RakutenCard4\Common;

use Eccube\Common\Constant;

class ConstantConfig{
//    const TYPE_TEXT = 'text';
//    const TYPE_BOOL = 'bool';

    const CONNECTION_MODE_PROD = 1; // 本番接続
    const CONNECTION_MODE_STG  = 2; // ステージング接続

    const CONNECTION_MODE_PROD_NAME = 'prod'; // 本番接続名称
    const CONNECTION_MODE_STG_NAME  = 'stg'; // ステージング接続名称

    const API_BASE_URL_PAYVAULT_PROD = 'https://payvault.global.rakuten.com/static/payvault/V7/'; // ペイボルト本番接続
    const API_BASE_URL_PAYVAULT_STG = 'https://payvault-stg.global.rakuten.com/static/payvault/V7/'; // ペイボルトステージング接続URL

    const USE_SELECT_LABEL = [
        Constant::ENABLED => 'rakuten_card4.admin.config.use',
        Constant::DISABLED => 'rakuten_card4.admin.config.no_use',
    ];
}
