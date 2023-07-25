<?php

namespace Plugin\RakutenCard4;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Eccube\Entity\PageLayout;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\RakutenCard4\Common\ConstantConfig;
use Plugin\RakutenCard4\Entity\Config;
use Symfony\Component\DependencyInjection\ContainerInterface;

class PluginManager extends AbstractPluginManager
{
    const PAGE_LIST = [
        'rakuten_card4_mypage' => [
            'page_name' => 'MYページ/カード情報編集',
            'file_name' => '@RakutenCard4/mypage_register_card.twig',
        ],
    ];

    public function enable(array $meta, ContainerInterface $container)
    {
        $this->createConfig($container);
        $this->deleteCreatePage($container, false);
    }

    private function createConfig(ContainerInterface $container)
    {
        $entityManager = $container->get('doctrine.orm.entity_manager');
        $Config = $entityManager->find(Config::class, 1);
        if ($Config) {
            return;
        }

        $Config = new Config();
        $Config->setConnectionMode( ConstantConfig::CONNECTION_MODE_STG);

        $entityManager->persist($Config);
        $entityManager->flush($Config);
    }

    /**
     * Disable the plugin.
     *
     * @param array $meta
     * @param ContainerInterface $container
     */
    public function disable(array $meta, ContainerInterface $container)
    {
        // quiet.
        $this->deleteCreatePage($container, true);
    }

    /**
     * ページの作成と削除
     *
     * @param ContainerInterface $container
     * @param bool $delete_flg true: 削除の場合
     */
    private function deleteCreatePage(ContainerInterface $container, $delete_flg=true)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager  = $container->get('doctrine.orm.entity_manager');
        $PageRepository = $entityManager->getRepository(Page::class);
        $Layout = $entityManager->find(Layout::class, 2);
        $PageLayoutRepository = $entityManager->getRepository(PageLayout::class);
        $sort_max = 0;
        /** @var PageLayout[] $MaxPageLayouts */
        $MaxPageLayouts = $PageLayoutRepository->findBy([], ['sort_no' => 'DESC'], 1);
        foreach ($MaxPageLayouts as $MaxPageLayout){
            $sort_max = $MaxPageLayout->getSortNo();
            break;
        }

        foreach (self::PAGE_LIST as $url=>$pageData)
        {
            /** @var Page $Page */
            $Page = $PageRepository->findOneBy(['url' => $url]);
            // 削除の場合
            if ($delete_flg){
                if (!is_null($Page)){
                    $PageLayouts = $PageLayoutRepository->findBy([
                        'page_id' => $Page->getId()
                    ]);
                    foreach ($PageLayouts as $pageLayout){
                        $entityManager->remove($pageLayout);
                        $entityManager->flush();
                    }
                    $entityManager->remove($Page);
                    $entityManager->flush();
                }
            }else{
                if (is_null($Page)){
                    $Page = new Page();
                    $Page->setUrl($url);
                }
                $Page->setName($pageData['page_name']);
                $Page->setFileName($pageData['file_name']);
                $Page->setEditType(Page::EDIT_TYPE_DEFAULT);
                $Page->setMetaRobots('noindex');
                $entityManager->persist($Page);
                $entityManager->flush();

                $PageLayouts = $PageLayoutRepository->findBy([
                    'page_id' => $Page->getId()
                ]);
                if (count($PageLayouts) == 0){
                    $sort_max++;
                    $PageLayout = new PageLayout();
                    $PageLayout->setPage($Page);
                    $PageLayout->setPageId($Page->getId());
                    $PageLayout->setLayout($Layout);
                    $PageLayout->setLayoutId($Layout->getId());
                    $PageLayout->setSortNo($sort_max);
                    $entityManager->persist($PageLayout);
                    $entityManager->flush();
                }
            }
        }
    }
}
