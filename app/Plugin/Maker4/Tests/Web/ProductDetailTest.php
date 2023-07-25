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

namespace Plugin\Maker4\Tests\Web;

use Eccube\Entity\Product;
use Faker\Generator;
use Plugin\Maker4\Entity\Maker;
use Symfony\Component\HttpKernel\Client;
use Eccube\Repository\ProductRepository;

/**
 * Class ProductDetailTest
 * Hook point test
 */
class ProductDetailTest extends MakerWebCommon
{
    /**
     * @var Maker
     */
    protected $Maker;

    /**
     * @var Product
     */
    protected $Product;

    /**
     * @var ProductRepository
     */
    protected $productRepository;

    /**
     * Set up function.
     */
    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows(['plg_maker']);

        $this->Maker = $this->createMaker();
        $this->Product = $this->createProductMaker($this->Maker);
        $this->productRepository = self::$container->get(ProductRepository::class);
    }

    /**
     * Product detail render test maker
     */
    public function testProductDetailWhenHasMakerButUnRegister()
    {
        $this->markTestSkipped('Skipped due to need include template on twig file manually');
        $productId = $this->Product->getId();
        $this->Product->setMaker(null);
        $this->Product->setMakerUrl(null);
        $this->entityManager->persist($this->Product);
        $this->entityManager->flush();
        $crawler = $this->client->request('GET', $this->generateUrl('product_detail', ['id' => $productId]));
        $html = $crawler->filter('.ec-productRole__profile')->html();
        $this->assertNotContains('メーカーコード', $html);
        $this->assertNotContains('メーカーURL', $html);
    }

    /**
     * Product detail render test maker
     */
    public function testProductDetailWhenRegisterMakerWithoutMakerUrl()
    {
        $this->markTestSkipped('Skipped due to need include template on twig file manually');
        $productId = $this->Product->getId();
        $this->Product->setMakerUrl('');
        $this->entityManager->persist($this->Product);
        $this->entityManager->flush();

        $crawler = $this->client->request('GET', $this->generateUrl('product_detail', ['id' => $productId]));

        $html = $crawler->filter('.ec-productRole__profile')->html();
        $this->assertContains($this->Product->getMaker()->getName(), $html);
        $this->assertNotContains('メーカーURL', $html);
    }

    /**
     * Product detail render test maker
     */
    public function testProductDetailWhenRegisterMakerAndMakerUrl()
    {
        $this->markTestSkipped('Skipped due to need include template on twig file manually');
        $productId = $this->Product->getId();

        $crawler = $this->client->request('GET', $this->generateUrl('product_detail', ['id' => $productId]));

        $html = $crawler->filter('.ec-productRole__profile')->html();
        $this->assertContains($this->Product->getMaker()->getName(), $html);
        $this->assertContains($this->Product->getMakerUrl(), $html);
    }

    /**
     * Create maker
     *
     * @param Maker   $Maker
     * @param Product $Product
     *
     * @return Product
     */
    protected function createProductMaker(Maker $Maker, $Product = null)
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        if (!$Product) {
            // New product
            /**
             * @var Generator
             */
            $faker = $this->getFaker();
            $formData = $this->createFormData();
            $formData['Maker'] = $Maker->getId();
            $formData['maker_url'] = $faker->url;

            /**
             * @var Client
             */
            $client = $this->client;
            $client->request(
                'POST',
                $this->generateUrl('admin_product_product_new'),
                ['admin_product' => $formData]
            );

            $this->assertTrue($client->getResponse()->isRedirection());

            $arrTmp = explode('/', $client->getResponse()->getTargetUrl());
            $productId = $arrTmp[count($arrTmp) - 2];

            $client->followRedirect();

            $this->productRepository = self::$container->get(ProductRepository::class);
            $Product = $this->productRepository->find($productId);
        }

        return $Product;
    }
}
