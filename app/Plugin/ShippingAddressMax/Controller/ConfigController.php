<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ShippingAddressMax\Controller;

use Eccube\Common\EccubeConfig;
use Eccube\Common\Constant;
use Eccube\Controller\AbstractController;
use Eccube\Util\CacheUtil;
use Eccube\Util\FilesystemUtil;
use Symfony\Component\Filesystem\Filesystem;
use Plugin\ShippingAddressMax\Form\Type\ConfigType;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;


/**
 * Class ConfigController
 */
class ConfigController extends AbstractController
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;    
    
    /**
     * @var CacheUtil
     */
    protected $cacheUtil;
    
    /**
     * PluginSampleController constructor.
     * @param EccubeConfig $eccubeConfig
     * @param CacheUtil $cacheUtil
     */
    public function __construct( 
            EccubeConfig $eccubeConfig,
            CacheUtil $cacheUtil
	) 
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->cacheUtil = $cacheUtil;
    }
    
    /**
     * @Route("/%eccube_admin_route%/shipping_address_max/config", name="shipping_address_max_admin_config")
     * @Template("@ShippingAddressMax/admin/config.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function index(Request $request)
    {   
        // 設定情報、フォーム情報を取得
        $form = $this->createForm(ConfigType::class);
        $form->handleRequest($request);
        $fs = new Filesystem();        
        
        // 設定画面で登録ボタンが押されたらこの処理を行う
        if ($form->isSubmitted() && $form->isValid()) {
            // フォームの入力データを取得
            $eccube_deliv_addr_max = $form->get('shipping_address_max')->getData();
            // フォームの入力データを保存
            
            // テンプレートファイルの取得
            $filePath = $this->eccubeConfig['plugin_realdir'].'/ShippingAddressMax/Resource/config/services.yaml.template';
            $upFilePath = $this->eccubeConfig['plugin_realdir'].'/ShippingAddressMax/Resource/config/services.yaml';
            if($fs->exists($filePath)){
                $source = file_get_contents($filePath);
                $source = str_replace('%eccube_deliv_addr_max%', (int)$eccube_deliv_addr_max, $source);
                $fs->dumpFile($upFilePath, $source);
            }            
            
            $this->cacheUtil->clearCache();
        
            // 完了メッセージを表示
            log_info('config', ['status' => 'Success']);
            $this->addSuccess('プラグインの設定を保存しました。', 'admin');

            // 設定画面にリダイレクト
            return $this->redirectToRoute('shipping_address_max_admin_config');
        }
        if (!$form->isSubmitted()){
            $form->get('shipping_address_max')->setData($this->eccubeConfig['eccube_deliv_addr_max']);
        }
        
        // テンプレートにデータを送る
        return [
            'form' => $form->createView(),
        ];
    }
}
