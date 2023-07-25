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

namespace Plugin\ContactManagement4\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Master\PrefType;
use Plugin\ContactManagement4\Form\Type\Master\ContactStatusType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

class SearchContactType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * SearchContactType constructor.
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // 会員ID・メールアドレス・名前・名前(フリガナ)
            ->add('multi', TextType::class, [
                'label' => 'admin.contact.multi_search_label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('status', ContactStatusType::class, [
                'label' => 'admin.contact.contact_status',
                'required' => false,
                'expanded' => true,
                'multiple' => true,
                'placeholder' => false,
            ])

            ->add('pref', PrefType::class, [
                'label' => 'admin.common.pref',
                'required' => false,
            ])
            ->add('phone_number', TextType::class, [
                'label' => 'admin.common.phone_number',
                'required' => false,
                'constraints' => [
                    new Assert\Regex([
                        'pattern' => "/^[\d-]+$/u",
                        'message' => 'form_error.graph_and_hyphen_only',
                    ]),
                ],
            ])

            ->add('create_date_start', DateType::class, [
                'label' => 'admin.common.create_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => 'admin.common.create_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_date_start', DateType::class, [
                'label' => 'admin.common.update_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('update_date_end', DateType::class, [
                'label' => 'admin.common.update_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_update_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])

        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_search_contact';
    }
}
