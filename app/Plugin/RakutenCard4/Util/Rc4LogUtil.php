<?php

namespace Plugin\RakutenCard4\Util;

use Plugin\RakutenCard4\Common\ConstantPlugin;

class Rc4LogUtil
{
    /**
     * @return \Symfony\Bridge\Monolog\Logger
     */
    private static function getLog()
    {
        return logs(ConstantPlugin::PLUGIN_CODE);
    }

    /**
     * ログ出力（エラー）
     *
     * @param string $message
     * @param array $context
     */
    public static function error($message, $context=[])
    {
        self::getLog()->error($message, $context);
    }

    /**
     * ログ出力（情報）
     *
     * @param string $message
     * @param array $context
     */
    public static function info($message, $context=[])
    {
        self::getLog()->info($message, $context);
    }

    /**
     * ログ出力（デバッグ）
     *
     * @param string $message
     * @param array $context
     */
    public static function debug($message, $context=[])
    {
        self::getLog()->debug($message, $context);
    }

}
