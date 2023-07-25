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

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Entity\Customer;
use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Eccube\Form\Validator\Email;
use Plugin\ContactManagement4\Form\Type\Master\ContactStatusType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * CustomerType constructor.
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        EntityManagerInterface $entityManager
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($builder->create('Customer', HiddenType::class, [
                'required' => false,
            ])->addModelTransformer(new EntityToIdTransformer($this->entityManager, Customer::class)))
            ->add('name', NameType::class, [
                'required' => true,
            ])
            ->add('kana', KanaType::class, [
                'required' => false,
            ])

            ->add('postal_code', PostalType::class, [
                'required' => false,
            ])
            ->add('address', AddressType::class, [
                'required' => false,
            ])
            ->add('phone_number', PhoneNumberType::class, [
                'required' => false,
            ])
            ->add('email', EmailType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                ],
                'attr' => [
                    'placeholder' => 'common.mail_address_sample',
                ],
            ])
            ->add('contents', TextareaType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('status', ContactStatusType::class, [
                'required' => true,
                'expanded' => false,
                'multiple' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('Staff', EntityType::class, [
                'class' => 'Eccube\Entity\Member',
                'required' => false,
                'expanded' => false,
                'multiple' => false,
                'placeholder' => '担当者未選択',
                'constraints' => [
                ],
            ])

            ->add('note', TextareaType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_ltext_len'],
                    ]),
                ],
            ])

        ;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\ContactManagement4\Entity\Contact',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_contact';
    }
}
