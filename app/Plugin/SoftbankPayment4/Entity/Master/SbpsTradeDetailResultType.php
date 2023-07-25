<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

class SbpsTradeDetailResultType
{
    public const CORRECT        = 1;
    public const FAILED         = 2;
    public const BILLING_CANCEL = 3;
    public const CAREER_CANCEL  = 4;
    public const DEPOSITED        = 5;
    public const EXPIRED_CANCEL = 6;
    public const EXPIRED        = 7;

    public static function getResultType($result_code): ?int
    {
        switch($result_code) {
            case 'OK':
                return self::CORRECT;
            case 'NG':
                return self::FAILED;
            case 'CR':
                return self::BILLING_CANCEL;
            case 'CC':
                return self::CAREER_CANCEL;
            case 'PY':
                return self::DEPOSITED;
            case 'CN':
                return self::EXPIRED_CANCEL;
            case 'CL':
                return self::EXPIRED;
            default:
                return null;
        }
    }
}
