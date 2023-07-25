<?php

namespace Plugin\RakutenCard4\Util;

class Rc4CommonUtil
{
    /**
     * 共通のエンコード処理
     *
     * @param mixed $data
     * @return false|string
     */
    public static function encodeData($data)
    {
        $encode = json_encode($data);

        return $encode;
    }

    /**
     * 共通デコード処理
     *
     * @param string $data jsonデータを想定
     * @return mixed 連想配列を想定
     */
    public static function decodeData($data)
    {
        // 連想配列にしてデコード
        $decode = json_decode($data, true);

        return $decode;
    }

    public static function decodeJson($data)
    {
        return self::decodeData($data);
    }

    public static function encodeJson($data)
    {
        return self::encodeData($data);
    }
}
