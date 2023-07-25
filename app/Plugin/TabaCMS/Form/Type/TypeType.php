<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Form\Type;

use Plugin\TabaCMS\Common\Constants;
use Plugin\TabaCMS\Entity\Type;
use Plugin\TabaCMS\Util\Validator;

use Eccube\Common\EccubeConfig;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Doctrine\ORM\EntityManagerInterface;

class TypeType extends AbstractType
{

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EccubeConfig
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
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('data_key', TextType::class, array(
            'label' => 'データキー(スラッグ)',
            'required' => true,
            'help' => Constants::PLUGIN_CODE_LC . '.form.type.data_key.help',
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array(
                    'max' => 255
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        Validator::class,
                        'unique'
                    ),
                    'payload' => array(
                        'entity' => Constants::$ENTITY['TYPE'],
                        'column' => 'dataKey',
                        'id' => 'typeId'
                    )
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        Validator::class,
                        'validDataKey'
                    )
                )),
                new Assert\Callback(array(
                    'callback' => array(
                        Validator::class,
                        'validStartsWith'
                    ),
                    'payload' => array(
                        'valid_string' => array(
                            'admin',
                            'admin_'
                        )
                    )
                ))
            )
        ))
        ->add('public_div', ChoiceType::class, array(
            'label' => '公開区分',
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank()
            ),
            'choices' => Type::$PUBLIC_DIV_ARRAY,
            'multiple' => false,
            'expanded' => true
        ))
            ->add('type_name', TextType::class, array(
            'label' => '投稿タイプ名',
            'required' => true,
            'constraints' => array(
                new Assert\NotBlank(),
                new Assert\Length(array(
                    'max' => 255
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
        ))
       ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return Constants::PLUGIN_CODE_LC . '_type';
    }
}
