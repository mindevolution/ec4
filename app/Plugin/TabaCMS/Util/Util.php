<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Util;

use Plugin\TabaCMS\Common\Constants;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class Util
{

    /**
     * テンプレートのファイルパスを取得します。
     *
     * @param string $file_name
     * @param array $data_keys
     * @param ContainerInterface $container
     * @return string
     */
    public static function getTemplatePath($file_name, $data_keys = array(),ContainerInterface $container)
    {
        $template_list = array();
        $dir_list = array();

        // テンプレートコード
        $eccubeConfig = $container->get('Eccube\Common\EccubeConfig');
        $themeCode = $eccubeConfig['eccube_theme_code'];

        // テンプレートディレクトリ
        $pluginDataTemplateDir = $eccubeConfig['plugin_data_realdir'] . DIRECTORY_SEPARATOR . Constants::PLUGIN_CODE . DIRECTORY_SEPARATOR . 'template';
        $pluginTemplateDir = $eccubeConfig['plugin_realdir'] . DIRECTORY_SEPARATOR . Constants::TEMPLATE_PATH;
        $templatePluginDir = null;

        // ディレクトリリスト生成
        $dir_list[] = 'default';
        if ($themeCode != 'default') {
            $dir_list[] = $themeCode;
            $templatePluginDir = $eccubeConfig['eccube_theme_front_dir'] . DIRECTORY_SEPARATOR . "Plugin" . DIRECTORY_SEPARATOR . Constants::PLUGIN_CODE;
        }
        foreach ($dir_list as $dir) {
            $template_list[] = $pluginTemplateDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file_name;
            if ($templatePluginDir) $template_list[] = $templatePluginDir . DIRECTORY_SEPARATOR . $file_name;
            $template_list[] = $pluginDataTemplateDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . $file_name;
            foreach ($data_keys as $data_key) {
                $template_list[] = $pluginTemplateDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . str_replace('.twig', '_' . $data_key . '.twig', $file_name);
                if ($templatePluginDir) $template_list[] = $templatePluginDir . DIRECTORY_SEPARATOR . str_replace('.twig', '_' . $data_key . '.twig', $file_name);
                $template_list[] = $pluginDataTemplateDir . DIRECTORY_SEPARATOR . $dir . DIRECTORY_SEPARATOR . str_replace('.twig', '_' . $data_key . '.twig', $file_name);
            }
        }

        $template_count = count($template_list) - 1;
        for ($i = 0; $i <= $template_count; $i ++) {
            $template_list[$template_count - $i];
            if (file_exists($template_list[$template_count - $i])) {
                return $template_list[$template_count - $i];
            }
        }

        return Constants::TEMPLATE_PATH . '/default/' . $file_name;
    }

    public static function removeJS($html)
    {
        $html = preg_replace('/< *script\b[^>]*>(.*?)< *\/ *script *>/is', "", $html);
        $event_list = array(
            "onBlur",
            "onFocus",
            "onChange",
            "onSelect",
            "onSelectStart",
            "onSubmit",
            "onReset",
            "onAbort",
            "onError",
            "onLoad",
            "onUnload",
            "onClick",
            "onDblClick",
            "onKeyUp",
            "onKeyDown",
            "onKeyPress",
            "onMouseOut",
            "onMouseOver",
            "onMouseUp",
            "onMouseDown",
            "onMouseMove",
            "onDragDrop"
        );
        foreach ($event_list as $event) {
            // $html = preg_replace('/' . $event . ' *= *[\"\'][^\"\']+[\"\']/is',"",$html);
            // $html = preg_replace('/' . $event . '[ \r\n]*=[ \r\n]*[\"\'][^\"\']*[\"\']/is',"",$html);
            $html = preg_replace('/' . $event . '[\s\r\n]*=[\s\r\n]*\"[^\"]*\"/is', "", $html);
            $html = preg_replace('/' . $event . "[\s\r\n]*=[\s\r\n]*\'[^\']*\'/is", "", $html);
        }
        return $html;
    }
}
