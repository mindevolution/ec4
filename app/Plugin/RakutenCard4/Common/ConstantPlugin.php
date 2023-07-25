<?php

namespace Plugin\RakutenCard4\Common;

class ConstantPlugin{
    const PLUGIN_CODE = 'RakutenCard4';
    static public function path_resource(){
        return __DIR__ . '/../Resource/';
    }
    static public function path_resource_config(){
        return self::path_resource() . 'config/';
    }
    static public function path_plugin_data(){
        return __DIR__ . '/../../../PluginData/' . self::PLUGIN_CODE . '/';
    }
}
