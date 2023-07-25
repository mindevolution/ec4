<?php

namespace Plugin\RakutenCard4\Form\Extension;

/**
 * EC-CUBE4.1.0対応用に必須項目はinterfaceで作成する
 */
interface RakutenFormExtensionInterface
{
    /**
     * 4.0.x対応
     *
     * 例
     * return OrderType::class;
     */
    public function getExtendedType();

    /**
     * 4.1.0対応
     *
     * 例
     * return [OrderType::class];
     */
    public static function getExtendedTypes(): iterable;
}
