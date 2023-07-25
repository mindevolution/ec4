<?php

/*
 * SoftbankPayment for EC-CUBE4
 * Copyright(c) 2019 IPLOGIC CO.,LTD. All Rights Reserved.
 *
 * http://www.iplogic.co.jp/
 *
 * This program is not free software.
 * It applies to terms of service.
 *
 */

namespace Plugin\SoftbankPayment4;

use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Eccube\Repository\LayoutRepository;
use Eccube\Repository\PageRepository;
use Eccube\Repository\PageLayoutRepository;
use Eccube\Repository\PaymentRepository;
use Plugin\SoftbankPayment4\Entity\Config;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Plugin\SoftbankPayment4\Repository\PayMethodRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Migrations\Migration;
use Doctrine\DBAL\Migrations\Configuration\Configuration;
use Eccube\Entity\Layout;
use Eccube\Entity\Payment;
use Plugin\SoftbankPayment4\Entity\PayMethod;

class PluginManager extends AbstractPluginManager
{
    public function install(array $config, ContainerInterface $container)
    {
        // No Processing
    }

    public function enable(array $config, ContainerInterface $container)
    {
        $this->initConfig($container);
        $this->dispPayments($container);
        $this->createPage($container);
    }

    public function disable(array $config, ContainerInterface $container)
    {
        $this->undispAllPayments($container);
        $this->deletePage($container);
    }

    public function uninstall(array $config, ContainerInterface $container)
    {        
        // No Processing
    }

    protected function initConfig(ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();

        $Config = $em->find(Config::class, 1);
        if ($Config) {
            return;
        }

        // プラグイン情報初期セット NULL
        $Config = new Config();
        $em->persist($Config);
        $em->flush($Config);
    }

    /**
     * 本プラグイン管理の決済方法を全て非表示とする.
     */
    protected function undispAllPayments(ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();
        $paymentRepository = $em->getRepository(Payment::class);

        $codes = PayMethodType::getCodes();
        foreach ($codes as $code) {
            $method_class = PayMethodType::$class[$code];

            $Payment = $paymentRepository->findOneBy([
                'method_class' => $method_class,
                'visible' => 1,
            ]);

            if($Payment === null) {
                continue;
            }

            $Payment->setVisible(false);
            $em->flush($Payment);
        }
    }

    /**
     * PayMethodが有効になっているPaymentを有効にする.
     */
    protected function dispPayments(ContainerInterface $container)
    {
        $em = $container->get('doctrine')->getManager();
        $paymentRepository = $em->getRepository(Payment::class);
        $payMethodRepository = $em->getRepository(PayMethod::class);

        $PayMethods = $payMethodRepository->findBy(['enable' => true]);

        foreach ($PayMethods as $PayMethod) {
            $method_class = PayMethodType::$class[$PayMethod->getCode()];
            $Payment = $paymentRepository->findOneBy(['method_class' => $method_class]);

            if($Payment === null) {
                continue;
            }

            $Payment->setVisible(true);
            $em->flush($Payment);
        }
    }

    private function createPage(ContainerInterface $container)
    {

        $entityManager = $container->get('doctrine')->getManager();
        $pageRepository = $entityManager->getRepository(Page::class);
        $layoutRepository = $entityManager->getRepository(Layout::class);

        // IDを直接指定(2:下層ページ用レイアウト)
        $Layout = $layoutRepository->find(2);

        $pageLayoutRepository = $entityManager->getRepository(PageLayout::class);
        $LastPageLayout = $pageLayoutRepository->findOneBy([], ['sort_no' => 'DESC']);
        $sortNo = $LastPageLayout->getSortNo();

        foreach ($this->getAddPageList() as $page) {
            $Page = $pageRepository->findOneBy(['url' => $page['url']]);
            if ($Page) {
                continue;
            }

            $Page = new Page();
            $Page->setName($page['page_name']);
            $Page->setUrl($page['url']);
            $Page->setFileName($page['file_name']);
            $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
            $Page->setCreateDate(new \DateTime());
            $Page->setUpdateDate(new \DateTime());
            $Page->setMetaRobots('noindex');

            $entityManager->persist($Page);
            $entityManager->flush($Page);

            $PageLayout = new PageLayout();
            $PageLayout->setPage($Page);
            $PageLayout->setPageId($Page->getId());
            $PageLayout->setLayout($Layout);
            $PageLayout->setLayoutId($Layout->getId());
            $PageLayout->setSortNo($sortNo++);

            $entityManager->persist($PageLayout);
            $entityManager->flush($PageLayout);
        }
    }

    private function deletePage(ContainerInterface $container) {
        $entityManager = $container->get('doctrine')->getManager();
        $pageRepository = $entityManager->getRepository(Page::class);
        $pageLayoutRepository = $entityManager->getRepository(PageLayout::class);

        foreach ($this->getAddPageList() as $page) {
            $Page = $pageRepository->findOneBy(['url' => $page['url']]);
            if($Page === null) {
                continue;
            }
            $entityManager->remove($Page);

            $PageLayout = $pageLayoutRepository->findOneBy(['page_id' => $Page->getId()]);
            if($PageLayout === null) {
                continue;
            }
            $entityManager->remove($PageLayout);
        }

        $entityManager->flush();
    }

    private function getAddPageList(): array
    {
        return [
            [
                'page_name' => 'SBPS/クレジット決済画面',
                'url' => 'shopping_checkout',
                'file_name' => '@SoftbankPayment4/default/Shopping/credit_checkout'
            ],
            [
                'page_name' => 'SBPSマイページ/カード情報管理',
                'url' => 'sbps_credit_list',
                'file_name' => '@SoftbankPayment4/default/mypage/credit/index'
            ],
            [
                'page_name' => 'SBPSマイページ/カード情報新規登録',
                'url' => 'sbps_credit_store',
                'file_name' => '@SoftbankPayment4/default/mypage/credit/new'
            ],
            [
                'page_name' => 'SBPSマイページ/カード情報更新',
                'url' => 'sbps_credit_update',
                'file_name' => '@SoftbankPayment4/default/mypage/credit/edit'
            ],
        ];
    }
}
