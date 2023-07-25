<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

class SbpsCvsType
{
    public const SEVEN_ELEVEN    = '001';
    public const LAWSON          = '002';
    public const MINISTOP        = '005';
    public const FAMILY_MART     = '016';
    public const SEICO_MART      = '018';

    /**
     * EC-CUBE上での呼称.
     */
    public static $name = [
        self::SEVEN_ELEVEN    => 'セブンイレブン',
        self::LAWSON          => 'ローソン',
        self::MINISTOP        => 'ミニストップ',
        self::FAMILY_MART     => 'ファミリーマート',
        self::SEICO_MART      => 'セイコーマート',
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
            if($constant) {
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

}
