<?php

/*
 * This file is part of DesignTag4
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DesignTag4\Form\Extension;

use Eccube\Form\Type\Admin\ProductTag;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

class TagExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('show_product_list_flg', CheckboxType::class, [
                'required' => false,
                'label' => '商品一覧に表示する',
                'eccube_form_options' => [
                    'auto_render' => true,
                ],
            ])
            ->add('text_color', TextType::class, [
                'required' => false,
                'label' => '文字色',
                'eccube_form_options' => [
                    'auto_render' => true,
                ],
            ])
            ->add('background_color', TextType::class, [
                'required' => false,
                'label' => '背景色',
                'eccube_form_options' => [
                    'auto_render' => true,
                ],
            ])
            ->add('border_color', TextType::class, [
                'required' => false,
                'label' => '枠線色',
                'eccube_form_options' => [
                    'auto_render' => true,
                ],
            ])

        ;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return ProductTag::class;
    }

    /**
     * Return the class of the type being extended.
     */
    public static function getExtendedTypes(): iterable
    {
        return [ProductTag::class];
    }
}
