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

namespace Plugin\MailMagazine4\Tests\Web\Front;

use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Tests\Web\AbstractWebTestCase;
use Eccube\Repository\CustomerRepository;

class MailMagazineEventOnRenderMypageChangeTest extends AbstractWebTestCase
{
    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    public function setUp()
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->customerRepository = $this->entityManager->getRepository(Customer::class);
    }

    protected function createFormData()
    {
        $faker = $this->getFaker();
        $tel = explode('-', $faker->phoneNumber);

        $email = $faker->safeEmail;
        $password = $faker->lexify('????????');
        $birth = $faker->dateTimeBetween;

        $form = [
            'name' => [
                'name01' => $faker->lastName,
                'name02' => $faker->firstName,
            ],
            'kana' => [
                'kana01' => $faker->lastKanaName,
                'kana02' => $faker->firstKanaName,
            ],
            'company_name' => $faker->company,
            'postal_code' => $faker->postcode1().$faker->postcode2(),
            'address' => [
                'pref' => '5',
                'addr01' => $faker->city,
                'addr02' => $faker->streetAddress,
            ],
            'phone_number' => $tel[0].$tel[1].$tel[2],
            'email' => [
                'first' => $email,
                'second' => $email,
            ],
            'password' => [
                'first' => $password,
                'second' => $password,
            ],
            'birth' => [
                'year' => $birth->format('Y'),
                'month' => $birth->format('n'),
                'day' => $birth->format('j'),
            ],
            'sex' => 1,
            'job' => 1,
            '_token' => 'dummy',
        ];

        return $form;
    }

    public function testOnRenderMypageChange_NotLogin()
    {
        $this->client->request('GET',
            $this->generateUrl('mypage_change')
        );

        $this->assertFalse($this->client->getResponse()->isSuccessful());
    }

    public function testOnRenderMypageChange()
    {
        $Customer = $this->createCustomer();
        $this->loginTo($Customer);

        $crawler = $this->client->request('GET',
            $this->generateUrl('mypage_change')
        );

        $this->assertTrue($this->client->getResponse()->isSuccessful());
        $this->assertEquals(1, $crawler->filter('#entry_mailmaga_flg')->count());
    }

    public function testOnRenderMypageChange_Post()
    {
        $Customer = $this->createCustomer();
        $this->loginTo($Customer);
        $updateFlg = Constant::ENABLED;
        $form = $this->createFormData();
        $form['mailmaga_flg'] = $updateFlg;

        $this->client->request('POST',
            $this->generateUrl('mypage_change'),
            [
                'entry' => $form,
            ]
        );

        $this->entityManager->clear();
        $Customer = $this->customerRepository->find($Customer->getId());
        $this->actual = $Customer->getMailmagaFlg();
        $this->expected = $updateFlg;
        $this->verify();
    }
}
