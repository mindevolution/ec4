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

use Eccube\Common\Constant;
use Faker\Generator;
use Plugin\Maker4\Tests\Web\MakerWebCommon;
use Symfony\Component\DomCrawler\Crawler;
use Plugin\Maker4\Repository\MakerRepository;

/**
 * Class MakerControllerTest.
 */
class MakerControllerTest extends MakerWebCommon
{
    /**
     * @var MakerRepository
     */
    protected $makerRepository;

    /**
     * Set up function.
     */
    public function setUp()
    {
        parent::setUp();
        $this->deleteAllRows(['plg_maker']);

        $this->makerRepository = self::$container->get(MakerRepository::class);
    }

    /**
     * Test render maker.
     */
    public function testMakerRender()
    {
        $crawler = $this->client->request('GET', $this->generateUrl('maker_admin_index'));
        $this->assertEquals(0, $crawler->filter('.sortable-item')->count());
    }

    /**
     * Test maker list
     */
    public function testMakerList()
    {
        $numberTest = 100;
        for ($i = 1; $i <= $numberTest; ++$i) {
            $this->createMaker($i);
        }

        $crawler = $this->client->request('GET', $this->generateUrl('maker_admin_index'));
        $number = count($crawler->filter('.sortable-container .sortable-item'));

        $this->actual = $number;
        $this->expected = $numberTest;
        $this->verify();
    }

    /**
     * Test maker create.
     */
    public function testMakerCreateNameIsEmpty()
    {
        $formData = $this->createMakerFormData();
        $formData['name'] = '';
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('maker_admin_index'),
            ['maker' => $formData]
        );
        // Check message
        $this->assertContains('入力されていません。', $crawler->filter('#form1 .form-error-message')->html());
    }

    /**
     * Test maker create.
     */
    public function testMakerCreateNameIsDuplicate()
    {
        // Exist maker
        $Maker = $this->createMaker(1);
        $formData = $this->createMakerFormData();
        $formData['name'] = $Maker->getName();
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('maker_admin_index'),
            ['maker' => $formData]
        );

        // Check message
        $this->assertContains('既に使用されています。', $crawler->filter('#form1 .form-error-message')->html());
    }

    /**
     * Test maker create.
     */
    public function testMakerCreate()
    {
        $formData = $this->createMakerFormData();
        $this->client->request(
            'POST',
            $this->generateUrl('maker_admin_index'),
            ['maker' => $formData]
        );

        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('maker_admin_index')));

        /**
         * @var Crawler
         */
        $crawler = $this->client->followRedirect();
        // Check message
        $this->assertContains('メーカーを保存しました。', $crawler->filter('.alert')->html());

        // check item name
        $addItem = $crawler->filter('.sortable-container .sortable-item')->first()->text();
        $this->assertContains($formData['name'], $addItem);
    }

    /**
     * Test maker edit.
     */
    public function testMakerInlineEditNameIsEmpty()
    {
        $Maker = $this->createMaker(1);
        $formData = $this->createMakerFormData($Maker->getId());
        $formData['name'] = '';

        /**
         * @var Crawler
         */
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('maker_admin_index', ['id' => $Maker->getId()]),
            ['maker_'.$Maker->getId() => $formData]
        );

        // Check message
        $this->assertContains('入力されていません。', $crawler->filter('#formInline'.$Maker->getId().' .form-error-message')->html());
    }

    /**
     * Test maker edit.
     */
    public function testMakerInlineEditNameIsDuplicate()
    {
        $MakerBefore = $this->createMaker(1);
        $Maker = $this->createMaker(1);
        $formData = $this->createMakerFormData($Maker->getId());

        $formData['name'] = $MakerBefore->getName();

        /**
         * @var Crawler
         */
        $crawler = $this->client->request(
            'POST',
            $this->generateUrl('maker_admin_index', ['id' => $Maker->getId()]),
            ['maker_'.$Maker->getId() => $formData]
        );

        // Check message
        $this->assertContains('既に使用されています。', $crawler->filter('#formInline'.$Maker->getId().' .form-error-message')->html());
    }

//    /**
//     * Test maker edit.
//     */
//    public function testMakerEditIdIsNotFound()
//    {
//        $Maker = $this->createMaker(1);
//        $editId = $Maker->getId() + 1;
//        $formData = $this->createMakerFormData($editId);
//
//        $this->client->request(
//            'POST',
//            $this->generateUrl('maker_admin_index', ['id' => $editId]),
//            ['_maker' => $formData]
//        );
//
//        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
//    }

    /**
     * Test maker edit.
     */
    public function testMakerInlineEdit()
    {
        $Maker = $this->createMaker(1);
        $formData = $this->createMakerFormData($Maker->getId());

        $this->client->request(
            'POST',
            $this->generateUrl('maker_admin_index', ['id' => $Maker->getId()]),
            ['maker_'.$Maker->getId() => $formData]
        );

        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('maker_admin_index')));

        $crawler = $this->client->followRedirect();
        // Check message
        $this->assertContains('メーカーを保存しました。', $crawler->filter('.alert')->html());

        // Check item name
        $html = $crawler->filter('.sortable-container .sortable-item')->first()->text();
        $this->assertContains($formData['name'], $html);
    }

    /**
     * Test maker delete.
     */
    public function testMakerDeleteGetMethod()
    {
        $Maker = $this->createMaker();

        $this->client->request(
            'GET',
            $this->generateUrl('maker_admin_delete', ['id' => $Maker->getId()])
        );

        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test maker delete.
     */
    public function testMakerDeleteIdIsNull()
    {
        $this->client->request(
            'DELETE',
            $this->generateUrl('maker_admin_delete', ['id' => null])
        );

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test maker delete.
     */
    public function testMakerDeleteIdIsNotExist()
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();
        $id = $faker->randomNumber(3);

        $this->client->request('DELETE', $this->generateUrl('maker_admin_delete', ['id' => $id]));

        $this->assertEquals(404, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Test maker edit.
     */
    public function testMakerDelete()
    {
        $Maker = $this->createMaker();

        $this->client->request(
            'DELETE',
            $this->generateUrl('maker_admin_delete', ['id' => $Maker->getId()])
        );
        // Check redirect
        $this->assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('maker_admin_index')));

        $crawler = $this->client->followRedirect();

        // Check message
        $this->assertContains('メーカーを削除しました。', $crawler->filter('.alert')->html());

        // Check item name
        $this->assertEquals(0, $crawler->filter('.sortable-item')->count());

        $this->assertNull($Maker->getId());
    }

    /**
     * Test rank move
     */
    public function testMoveRankTestIsNotPostAjax()
    {
        $Maker01 = $this->createMaker(1);
        $oldSortNo = $Maker01->getSortNo();
        $Maker02 = $this->createMaker(2);
        $newSortNo = $Maker02->getSortNo();

        $request = [
            $Maker01->getId() => $newSortNo,
            $Maker02->getId() => $oldSortNo,
        ];

        $this->client->request(
            'GET',
            $this->generateUrl('maker_admin_move_sort_no'),
            $request,
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->actual = $Maker01->getSortNo();
        $this->expected = $oldSortNo;
        $this->verify();

        $this->assertEquals(405, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Move rank test
     */
    public function testMoveRank()
    {
        $Maker01 = $this->createMaker(1);
        $oldSortNo = $Maker01->getSortNo();
        $Maker02 = $this->createMaker(2);
        $newSortNo = $Maker02->getSortNo();

        $request = [
            $Maker01->getId() => $newSortNo,
            $Maker02->getId() => $oldSortNo,
        ];

        $this->client->request(
            'POST',
            $this->generateUrl('maker_admin_move_sort_no'),
            $request,
            [],
            [
                'HTTP_X-Requested-With' => 'XMLHttpRequest',
                'CONTENT_TYPE' => 'application/json',
            ]
        );

        $this->actual = $Maker01->getSortNo();
        $this->expected = $newSortNo;
        $this->verify();
    }

    /**
     * Create data form.
     *
     * @param null $makerId
     *
     * @return array
     */
    private function createMakerFormData($makerId = null)
    {
        /**
         * @var Generator
         */
        $faker = $this->getFaker();

        $form = [
            Constant::TOKEN_NAME => 'dummy',
            'name' => $faker->word,
        ];

        return $form;
    }
}
