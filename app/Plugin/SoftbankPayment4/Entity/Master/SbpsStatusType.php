<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

class SbpsStatusType
{
    /**
     * Store Status
     */
    public const UNDEFINED = 0;
    public const ARED      = 1;
    public const CAPTURED  = 2;
    public const CANCELED  = 3;
    public const REFOUND   = 4;
    public const DEPOSITED = 5;
    public const EXPIRED   = 98;
    public const FAILED    = 99;

    /**
     * 取消・返金可能なステータス.
     */
    public static $refundables = [
        self::ARED,
        self::CAPTURED,
        self::DEPOSITED,
    ];

    /**
     * @param string $pay_method SBPS決済方法
     * @param int $captureType 自動売上かどうか
     *
     * @return int 決済ステータス
     */
    public static function getSuccessStatus(string $pay_method, int $captureType): int
    {
      switch ($pay_method) {
          case 'credit':
          case 'credit3d':
          case 'credit3d2':
          case 'credit_api':
          case 'softbank2':
          case 'docomo':
          case 'auone':
              return $captureType === CaptureType::AR ? self::ARED : self::CAPTURED;
          case 'unionpay':
          case 'paypay':
              return self::CAPTURED;
          case 'webcvs':
          case 'cvs_api':
              return self::ARED;
          default:
              return self::UNDEFINED;
      }
    }
}
