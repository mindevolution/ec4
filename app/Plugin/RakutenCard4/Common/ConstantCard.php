<?php

namespace Plugin\RakutenCard4\Common;

class ConstantCard{

    const CHALLENGE_DEFAULT = 1; // 1：no_preference : デフォルトリスクベース
    const CHALLENGE_FORCE = 2;   // 2：challenge_requested_mandate：チャレンジ必須

    // PHPのバージョンでこの書き方が使えなければ、値に書き換える
    const CHALLENGE_TYPE_COL = [
        self::CHALLENGE_DEFAULT => 'no_preference',
        self::CHALLENGE_FORCE => 'challenge_requested_mandate',
    ];

    const AUTH_INDICATOR_PAYMENT = 'payment_transaction';
    const AUTH_INDICATOR_INSTALLMENT = 'installment_transaction';
    const MSG_CATEGORY_PAY = 'pa';
    const MSG_CATEGORY_NO_PAY = 'npa';
    const TRANSACTION_TYPE = 'goods_service_purchase';

    const CHALLENGE_TYPE_LABEL = [
        self::CHALLENGE_DEFAULT => 'rakuten_card4.admin.card.config.challenge_type.no_preference',
        self::CHALLENGE_FORCE => 'rakuten_card4.admin.card.config.challenge_type.challenge_requested_mandate',
    ];

    const WITH_BONUS     = 200;
    const WITH_REVOLVING = 300;

    const INSTALLMENTS_LABEL = [
        self::WITH_BONUS => 'rakuten_card4.admin.card.config.installments.with_bonus',
        self::WITH_REVOLVING => 'rakuten_card4.admin.card.config.installments.with_revolving',
    ];

    // 処理区分の選択肢
    const BUY_API_AUTHORIZE = 10;
    const BUY_API_CAPTURE = 20;

    const BUY_API_LABEL = [
        self::BUY_API_CAPTURE => 'rakuten_card4.admin.card.config.buy_api.capture',
        self::BUY_API_AUTHORIZE => 'rakuten_card4.admin.card.config.buy_api.authorize',
    ];

    // 購入時の支払い方法種類
    const USE_KIND_INPUT = 1;
    const USE_KIND_REGISTER = 2;

    const USE_KIND_LABEL = [
        self::USE_KIND_INPUT => 'rakuten_card4.admin.order_edit.payment_card',
        self::USE_KIND_REGISTER => 'rakuten_card4.admin.order_edit.payment_register_card',
    ];

    // 国際ブランド
    const BRAND_VISA = 1;
    const BRAND_MASTER_CARD = 2;
    const BRAND_DINERS_CLUB = 3;
    const BRAND_DISCOVER = 4;
    const BRAND_JCB = 5;
    const BRAND_AMERICAN_EXPRESS = 6;
    const BRAND_MAESTRO = 7;
    const BRAND_LIST = [
        'Visa' => self::BRAND_VISA,
        'MasterCard' => self::BRAND_MASTER_CARD,
        'Diners Club' => self::BRAND_DINERS_CLUB,
        'Discover' => self::BRAND_DISCOVER,
        'JCB' => self::BRAND_JCB,
        'American Express' => self::BRAND_AMERICAN_EXPRESS,
        'Maestro' => self::BRAND_MAESTRO,
    ];

}
