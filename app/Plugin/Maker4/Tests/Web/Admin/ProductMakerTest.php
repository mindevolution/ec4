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

namespace Plugin\Maker4\Tests\Web\Admin;

use Faker\Generator;
use Plugin\Maker4\Tests\Web\MakerWebCommon;
use Symfony\Component\HttpKernel\Client;
use Eccube\Repository\ProductRepository;

/**
 * Class ProductMakerTest.
 */
class ProductMakerTest extends MakerWebCommon
{
    const MAKER = 'Maker';
    const MAKER_URL = 'maker_url';

    /**
     * @var int
     */
    protected $productId;

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

        $this->productRepository = self::$container->get(ProductRepository::class);
    }

    /**
     * Test render
     */
    public function testProductNewRender()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('admin_product_product_new'));
        $this->assertContains('メーカー', $crawler->filter('body .c-container')->html());
    }

    /**
     * Test new
     */
    public function testProductNewWithoutMaker()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('admin_product_product_new'));
        $this->assertContains('メーカー', $crawler->filter('body .c-container')->html());
    }

    /**
     * Test new
     */
    public function testProductNewWithMakerWithoutMakerSelect()
    {
        $Maker = $this->createMaker();

        $crawler = $this->client->request('GET', $this->generateUrl('admin_product_product_new'));
        $this->assertContains($Maker->getName(), $crawler->filter('body .c-container')->html());
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerURLWithoutMakerSelect()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = $faker->url;

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

        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check layout
        $this->assertContains($formData[self::MAKER_URL], $crawler->filter('body .c-container')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getMaker();
        $this->expected = null;
        $this->verify();
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerInvalid()
    {
        $Maker = $this->createMaker();
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId() + 1;
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );

        // Check message
        $this->assertContains('有効な値ではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);
        $this->actual = $Product->getMaker();
        $this->expected = null;
        $this->verify();
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerWithoutMakerUrl()
    {
        $Maker = $this->createMaker();

        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = '';

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

        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getMaker()->getId();
        $this->expected = $formData[self::MAKER];
        $this->verify();
    }

    /**
     * Test render
     */
    public function testProductNewWithAddMakerAndMakerUrlInValid()
    {
        $Maker = $this->createMaker();

        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->word;

        /**
         * @var Client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_product_product_new'),
            ['admin_product' => $formData]
        );

        // Check message
        $this->assertContains('有効なURLではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getMaker();
        $this->expected = null;
        $this->verify();
    }

    /**
     * Test new
     */
    public function testProductNewWithAddMakerAndMakerUrlSuccess()
    {
        $Maker = $this->createMaker();

        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->url;

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

        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = [$Product->getMaker()->getId(), $Product->getMakerUrl()];
        $this->expected = [$formData[self::MAKER], $formData[self::MAKER_URL]];
        $this->verify();
    }

    /**
     * Test render
     */
    public function testProductEditRender()
    {
        $Product = $this->createProduct();

        $crawler = $this->client->request('GET', $this->generateUrl('admin_product_product_edit', ['id' => $Product->getId()]));
        $this->assertContains('メーカー', $crawler->filter('body .c-container')->html());
    }

    /**
     * Test render
     */
    public function testProductEditWithMaker()
    {
        $Product = $this->createProduct();
        $Maker = $this->createMaker();

        $crawler = $this->client->request('GET', $this->generateUrl('admin_product_product_edit', ['id' => $Product->getId()]));
        $this->assertContains($Maker->getName(), $crawler->filter('body .c-container')->html());
    }

    /**
     * Test new
     */
    public function testProductEditWithAddMakerURLWithoutMakerSelect()
    {
        $this->createMaker();

        // New product
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_product_product_edit', ['id' => $productId]),
            ['admin_product' => $formData]
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getMaker();
        $this->expected = null;
        $this->verify();
    }

    /**
     * Test Edit
     */
    public function testProductEditWithAddMakerInvalid()
    {
        $this->createMaker();

        // New product
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = 99999;
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client
         */
        $client = $this->client;
        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_product_product_edit', ['id' => $productId]),
            ['admin_product' => $formData]
        );

        // Check message
        $this->assertContains('有効な値ではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getMaker();
        $this->expected = null;
        $this->verify();
    }

    /**
     * Test Edit
     */
    public function testProductEditWithAddMakerWithoutMakerUrl()
    {
        $Maker = $this->createMaker();

        // New product
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = '';

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_product_product_edit', ['id' => $productId]),
            ['admin_product' => $formData]
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = [$Product->getMaker()->getId(), $Product->getMakerUrl()];
        $this->expected = [$Maker->getId(), $formData[self::MAKER_URL]];
        $this->verify();
    }

    /**
     * Test render
     */
    public function testProductEditWithAddMakerAndMakerUrlInValid()
    {
        $Maker = $this->createMaker();

        // New product
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // Edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->word; // invalid

        $crawler = $client->request(
            'POST',
            $this->generateUrl('admin_product_product_edit', ['id' => $productId]),
            ['admin_product' => $formData]
        );

        // Check message
        $this->assertContains('有効なURLではありません。', $crawler->filter('.form-error-message')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = $Product->getMaker();
        $this->expected = null;
        $this->verify();
    }

    /**
     * Test Edit
     */
    public function testProductEditWithAddMakerAndMakerUrlSuccess()
    {
        $Maker = $this->createMaker();
        // New product
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $formData = $this->createFormData();
        $formData[self::MAKER] = '';
        $formData[self::MAKER_URL] = '';

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

        // edit product test
        $formData = $this->createFormData();
        $formData[self::MAKER] = $Maker->getId();
        $formData[self::MAKER_URL] = $faker->url;

        /**
         * @var Client
         */
        $client = $this->client;
        $client->request(
            'POST',
            $this->generateUrl('admin_product_product_edit', ['id' => $productId]),
            ['admin_product' => $formData]
        );

        $this->assertTrue($client->getResponse()->isRedirection());
        $crawler = $client->followRedirect();

        // Check message
        $this->assertContains('保存しました', $crawler->filter('.alert')->html());

        // Check database
        $Product = $this->productRepository->findOneBy([], ['id' => 'DESC']);

        $this->actual = [$Product->getMaker()->getId(), $Product->getMakerUrl()];
        $this->expected = [$Maker->getId(), $formData[self::MAKER_URL]];
        $this->verify();
    }
}
