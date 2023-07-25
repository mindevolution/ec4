<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\StripePaymentGateway;

use Eccube\Entity\Payment;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Eccube\Repository\PaymentRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Plugin\StripePaymentGateway\Service\Method\StripeCreditCard;
use Plugin\StripePaymentGateway\Service\Method\StripeGA;
use Eccube\Common\EccubeConfig;
use Plugin\StripePaymentGateway\Service\Method\StripeKonbini;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createTokenPayment($container);
        
    }
    public function install(array $meta, ContainerInterface $container){
        $this->createConfig($container);
        $this->noWarmUpRouteCache($container);
    }
    /**
     * プラグインアップデート時の処理
     *
     * @param array              $meta
     * @param ContainerInterface $container
     */
    public function update(array $meta, ContainerInterface $container)
    {
        // $this->registerPageForUpdate($container);
        try{
            $this->noWarmUpRouteCache($container);
            $entityManager = $container->get('doctrine')->getManager();
            if(\method_exists($this, 'migration')){
                $this->migration($entityManager->getConnection(), $meta['code']);
            }
        }catch(\Exception $e){}

        // remove unnecessary things
        $project_root = $container->get(EccubeConfig::class)->get('kernel.project_dir');
        try{
            $dir = $project_root . "/app/Plugin/StripePaymentGateway/Form/Extension";
            $this->removeDir($dir);

            $this->removeFile($project_root . "/app/Plugin/StripePaymentGateway/Entity/CheckoutSession.php");
            $this->removeFile($project_root . "/app/Plugin/StripePaymentGateway/Repository/CheckoutSessionRepository.php");
            $this->removeFile($project_root . '/app/Plugin/StripePaymentGateway/Resource/template/default/Shopping/stripe_credit_card_confirm.twig');
            $this->removeFile($project_root . '/app/Plugin/StripePaymentGateway/Resource/template/default/Shopping/stripe_ga_confirm.twig');
            $this->removeFile($project_root . '/app/Plugin/StripePaymentGateway/Resource/template/default/index_test.twig');
        }catch(\Exception $e){}

        $this->createTokenPayment($container);
    }

    private function createTokenPayment(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $paymentRepository = $entityManager->getRepository(Payment::class);
        $Payment = $paymentRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $Payment ? $Payment->getSortNo() + 1 : 1;
        $Payment = $paymentRepository->findOneBy(['method_class' => StripeCreditCard::class]);
        if (empty($Payment)) {
            $Payment = new Payment();
            $Payment->setCharge(0);
            $Payment->setSortNo($sortNo);
            $Payment->setVisible(true);
            $sortNo++;
            
            $Payment->setMethodClass(StripeCreditCard::class);
            $Payment->setMethod('クレジットカード');
            $entityManager->persist($Payment);
            $entityManager->flush($Payment);
        }
        $Payment = $paymentRepository->findOneBy(['method_class' => StripeKonbini::class]);
        if (empty($Payment)) {
            $Payment = new Payment();
            $Payment->setCharge(0);
            $Payment->setSortNo($sortNo);
            $Payment->setVisible(false);
            
            $Payment->setMethodClass(StripeKonbini::class);
            $Payment->setMethod('コンビニ払い');
            $entityManager->persist($Payment);
            $entityManager->flush($Payment);
        }
    }

    private function createConfig(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Config = $entityManager->find(StripeConfig::class, 1);
        if ($Config) {
            return;
        }
        $Config = new StripeConfig();
        $Config->setPublishableKey('公開可能なキー');
        $Config->setSecretKey('秘密鍵');
        $Config->setStripeFeesPercent('0');
        $entityManager->persist($Config);
        $entityManager->flush($Config);
    }

    protected function noWarmUpRouteCache($container) {

        $router = $container->get('router');
        $filesystem = $container->get('filesystem');
        $kernel = $container->get('kernel');
        $cacheDir = $kernel->getCacheDir();
        
        foreach (array('matcher_cache_class', 'generator_cache_class') as $option) {
            $className = $router->getOption($option);
            $cacheFile = $cacheDir . DIRECTORY_SEPARATOR . $className . '.php';            
            $filesystem->remove($cacheFile);
        }
        
        // $router->warmUp($cacheDir);    
    }

    protected function removeDir($dirPath){
        if(\is_dir($dir)){
            $dir = $dirPath;
            $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new \RecursiveIteratorIterator($it,
                        \RecursiveIteratorIterator::CHILD_FIRST);
            foreach($files as $file) {
                if ($file->isDir()){
                    rmdir($file->getRealPath());
                } else {
                    unlink($file->getRealPath());
                }
            }
        }
        rmdir($dir);
    }
    protected function removeFile($file_path){
        if(\file_exists($file_path)){
            \unlink($file_path);
        }
    }

}