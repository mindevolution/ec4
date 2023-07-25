<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Common;

/**
 * プラグインが生存している間、各種データを保持すクラスです。
 *
 * @author
 */
class DataHolder
{

    private static $instance;

    private $data;

    private function __construct()
    {}

    public static function getInstance()
    {
        if (! self::$instance) {
            self::$instance = new DataHolder();
            self::$instance->data = array();
        }
        return self::$instance;
    }

    public function setData($key, $val)
    {
        $this->data[$key] = $val;
    }

    public function getData($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
        return null;
    }

    final function __clone()
    {
        throw new \Exception('Clone is not allowed against ' . get_class($this));
    }
}