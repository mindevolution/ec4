<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

class CaptureType
{
    public const AR      = 1;    // 指定売上(オーソリまで)
    public const CAPTURE = 2;    // 自動売上(売上まで)

    public static $choice = [
        '指定売上' => self::AR,
        '自動売上' => self::CAPTURE,
    ];

    public static function getCapturedAmount($pay_method, $captureType, $authorizedAmount): ?int
    {
        if ($captureType === self::CAPTURE && !PayMethodType::isCsv($pay_method)) {
            return $authorizedAmount;
        }

        switch ($pay_method) {
            case PayMethodType::$method[PayMethodType::CREDIT]:
            case PayMethodType::$method[PayMethodType::CREDIT_API]:
            case PayMethodType::$method[PayMethodType::CVS_DEFERRED]:
            case PayMethodType::$method[PayMethodType::CVS_DEFERRED_API]:
            case PayMethodType::$method[PayMethodType::SB]:
            case PayMethodType::$method[PayMethodType::DOCOMO]:
            case PayMethodType::$method[PayMethodType::AU]:
                return 0;
            case PayMethodType::$method[PayMethodType::UNION_PAY]:
            case PayMethodType::$method[PayMethodType::PayPay]:
                return $authorizedAmount;
        }
    }
}

