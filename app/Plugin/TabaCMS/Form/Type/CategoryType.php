<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Form\Type;

use Plugin\TabaCMS\Common\Constants;

use Eccube\Common\EccubeConfig;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

use Doctrine\ORM\EntityManagerInterface;

class CategoryType extends AbstractType
{

    /**
     *
     * @var EntityManagerInterface
     */
    protected $entityManager;

    /**
     *
     * @var array
     */
    private $eccubeConfig;

    /**
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EntityManagerInterface $entityManager, EccubeConfig $eccubeConfig)
    {
        $this->entityManager = $entityManager;
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     *
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('data_key', TextType::class, array(
            'label' => 'データキー(スラッグ)',
            'required' => true,
            'help' => 'このデータを特定するためのキー値です。URLやテンプレートファイル名などで使用します。',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array(
                    'max' => 255
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        'Plugin\TabaCMS\Util\Validator',
                        'unique'
                    ),
                    'payload' => array(
                        'entity' => Constants::$ENTITY['CATEGORY'],
                        'column' => 'dataKey',
                        'id' => 'categoryId',
                        'group_columns' => 'typeId'
                    )
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        'Plugin\TabaCMS\Util\Validator',
                        'validDataKey'
                    )
                ))
            )
        ))
            ->add('category_name', TextType::class, array(
            'label' => 'カテゴリー名',
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array(
                    'max' => 255
                ))
            )
        ))
            ->add('description', TextareaType::class, array(
            'label' => '説明',
            'required' => false,
            'help' => 'カテゴリーの説明文で使用します。表示箇所はデザインによります。',
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('tag_attributes', TextareaType::class, array(
            'label' => 'タグ属性',
            'help' => 'カテゴリーを囲むタグに属性を追加することができます。\n(例) style="border:solid 1px #ff0000;background-color:#ffffff;color:#ff0000;"',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ))
            ->add('memo', TextareaType::class, array(
            'label' => 'メモ',
            'required' => false,
            'constraints' => array(
                new Assert\Length(array(
                    'max' => $this->eccubeConfig['eccube_ltext_len']
                ))
            )
        ));
    }

    /**
     *
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return Constants::PLUGIN_CODE_LC . '_category';
    }
}
