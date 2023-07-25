<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

use Plugin\SoftbankPayment4\Entity\Master\SbpsCredit3dUseType as SbpsCredit3dUseType;

class PayMethodType
{
    public const CREDIT         = 101;
    public const UNION_PAY      = 102;
    public const SB             = 202;
    public const DOCOMO         = 204;
    public const AU             = 205;
    public const PayPay         = 408;
    public const CVS_DEFERRED   = 701;

    // API型は4桁0埋め
    public const CREDIT_API         = 1010;
    public const CVS_DEFERRED_API   = 7010;

    public const METHOD_REAL_DIR = 'Plugin\SoftbankPayment4\Service\Method\\';

    /**
     * SBPS側へのリクエスト名.(リンク型)
     */
    public static $method = [
        self::CREDIT                => 'credit3d',    // 3Dセキュアを強制
        self::UNION_PAY             => 'unionpay',
        self::SB                    => 'softbank2',
        self::DOCOMO                => 'docomo',
        self::AU                    => 'auone',
        self::PayPay                => 'paypay',
        self::CVS_DEFERRED          => 'webcvs',
        self::CREDIT_API            => 'credit_api',
        self::CVS_DEFERRED_API      => 'cvs_api',
    ];

    /**
     * SBPS側へのリクエスト名.(リンク型でクレジット払いの場合、設定を考慮)
     */
    public static $method_credit = [
        SbpsCredit3dUseType::SBPSCREDIT3D_NOUSE => 'credit',
        SbpsCredit3dUseType::SBPSCREDIT3D_USE   => 'credit3d',    // 3Dセキュア(1.0)を強制
        SbpsCredit3dUseType::SBPSCREDIT3D_USE_2 => 'credit3d2',   // 3Dセキュア(2.0)を強制
    ];

    /**
     * EC-CUBE上での呼称.
     */
    public static $name = [
        self::CREDIT        => 'クレジットカード（リンク型）',
        self::CREDIT_API    => 'クレジットカード(トークン型)',
        self::UNION_PAY     => '銀聯ネット決済',
        self::SB            => 'ソフトバンクまとめて支払い',
        self::DOCOMO        => 'ドコモ払い',
        self::AU            => 'auかんたん決済',
        self::PayPay        => 'PayPayオンライン',
        self::CVS_DEFERRED  => 'WEBコンビニ決済',
        self::CVS_DEFERRED_API  => 'WEBコンビニ決済(API)',
    ];

    /**
     * 決済時に実行されるクラスの名前.
     */
    public static $class = [
        self::CREDIT        => self::METHOD_REAL_DIR.'Link\Credit',
        self::UNION_PAY     => self::METHOD_REAL_DIR.'Link\UnionPay',
        self::SB            => self::METHOD_REAL_DIR.'Link\SbCarrier',
        self::DOCOMO        => self::METHOD_REAL_DIR.'Link\DocomoCarrier',
        self::AU            => self::METHOD_REAL_DIR.'Link\AuCarrier',
        self::PayPay        => self::METHOD_REAL_DIR.'Link\PayPay',
        self::CVS_DEFERRED  => self::METHOD_REAL_DIR.'Link\CvsDeferred',
        self::CVS_DEFERRED_API  => self::METHOD_REAL_DIR.'Api\CvsDeferredApi',
        self::CREDIT_API    => self::METHOD_REAL_DIR.'Api\CreditApi',
    ];

    /**
     * 本クラスのリフレクションを行い、決済コードのリストを取得する.
     */
    private static function getConstants(): array
    {
        $ReflectionPayMaster = new \ReflectionClass(__CLASS__);
        return $ReflectionPayMaster->getConstants();
    }

    public static function getCodes(): array
    {
        $constants = self::getConstants();
        $codes = [];
        foreach ($constants as $constant) {
            if(is_int($constant)) {
                $codes[] = $constant;
            }
        }

        return $codes;
    }

    /**
     * 呼称 => コード で突合して返す.
     *
     * @return array
     */
    public static function getPayMethodList(): array
    {
        $code_list = self::getCodes();
        $list = [];
        foreach ($code_list as $code) {
            $list[self::$name[$code]] = $code;
        }

        return $list;
    }

    public static function getCodeByClass($method_class): int
    {
        return (int) array_search($method_class, self::$class, true);
    }

    public static function getMethodByClass($method_class, $sbps_credit3d_use): string
    {        
        if ($method_class === self::$class[self::CREDIT]) {
            // クレジット払いの場合、設定を考慮した値を返却
            return self::$method_credit[$sbps_credit3d_use];
        } else {
            // クレジット払い以外の場合
            return self::$method[self::getCodeByClass($method_class)];            
        }        
    }

    public static function getClassesByCodes(array $codes): array
    {
        $classes = [];
        foreach($codes as $code){
            $classes[] = self::$class[$code];
        }
        return $classes;
    }

    public static function isCsv($payMethod): bool
    {
        return $payMethod === self::$method[PayMethodType::CVS_DEFERRED] || $payMethod === self::$method[PayMethodType::CVS_DEFERRED_API];
    }
}
