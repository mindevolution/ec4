<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4\Tests\Form\Type\Master;

use Eccube\Tests\Form\Type\AbstractTypeTestCase;
use Plugin\ContactManagement4\Form\Type\Master\ContactStatusType;
use Symfony\Component\Form\Extension\Core\Type\FormType;

class ContactStatusTypeTest extends AbstractTypeTestCase
{
    /** @var \Symfony\Component\Form\FormInterface */
    protected $form;

    /** @var array デフォルト値（正常系）を設定 */
    protected $formData = [
        'status' => 1,
    ];

    public function setUp()
    {
        parent::setUp();

        $this->form = $this->formFactory
            ->createBuilder(FormType::class, null, ['csrf_protection' => false])
            ->add('status', ContactStatusType::class)
            ->getForm();
    }

    public function testValidData()
    {
        $this->form->submit($this->formData);

        $this->assertTrue($this->form->isValid());
    }
}
