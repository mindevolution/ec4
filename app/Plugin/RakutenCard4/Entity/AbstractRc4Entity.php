<?php

namespace Plugin\RakutenCard4\Entity;

use Plugin\RakutenCard4\Util\Rc4CommonUtil;

abstract class AbstractRc4Entity extends \Eccube\Entity\AbstractEntity
{
    /**
     * エンコード
     *
     * @param array $parameter
     * @return false|string
     */
    protected function getEncode($parameter)
    {
        return Rc4CommonUtil::encodeData($parameter);
    }

    /**
     * デコード
     *
     * @param string $parameter
     * @return array
     */
    protected function getDecode($parameter)
    {
        return Rc4CommonUtil::decodeData($parameter);
    }
}