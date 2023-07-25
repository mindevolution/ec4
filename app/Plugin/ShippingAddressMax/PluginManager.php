<?php

namespace Plugin\ShippingAddressMax;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Plugin\AbstractPluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Eccube\Application;
use Eccube\Util\CacheUtil;

class PluginManager extends AbstractPluginManager
{
     /** @var CacheUtil */
    protected $cacheUtil;
    
    public function enable(array $meta, ContainerInterface $container)
    {
        
        $plugin_realdir        = $container->getParameter('plugin_realdir');
        $eccube_deliv_addr_max = $container->getParameter('eccube_deliv_addr_max');
        $fs = new Filesystem();        
        // テンプレートファイルの取得
        $filePath   = $plugin_realdir.'/ShippingAddressMax/Resource/config/services.yaml.template';
        $upFilePath = $plugin_realdir.'/ShippingAddressMax/Resource/config/services.yaml';
        if(!$fs->exists($upFilePath)){
            $source = file_get_contents($filePath);
            $source = str_replace('%eccube_deliv_addr_max%', (int)$eccube_deliv_addr_max, $source);
            $fs->dumpFile($upFilePath, $source);            
        }
    }
    
    /**
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        $plugin_realdir        = $container->getParameter('plugin_realdir');
        $fs = new Filesystem();        
        // テンプレートファイルの取得
        $upFilePath = $plugin_realdir.'/ShippingAddressMax/Resource/config/services.yaml';
        if($fs->exists($upFilePath)){            
            $fs->remove($upFilePath);
        }
    }
    
    public function uninstall(array $meta, ContainerInterface $container)
    {
    }
    
    /**
     * 設定の登録.
     *
     * @param EntityManagerInterface $em
     */
    protected function createConfig(EntityManagerInterface $em)
    {        
    }
}
