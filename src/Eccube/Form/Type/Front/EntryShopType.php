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

namespace Eccube\Form\Type\Front;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\CustomerShop;
use Eccube\Form\Type\AddressType;
use Eccube\Form\Type\KanaType;
use Eccube\Form\Type\Master\JobType;
use Eccube\Form\Type\Master\SexType;
use Eccube\Form\Type\NameType;
use Eccube\Form\Type\RepeatedEmailType;
use Eccube\Form\Type\RepeatedPasswordType;
use Eccube\Form\Type\PhoneNumberType;
use Eccube\Form\Type\PostalType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\BirthdayType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class EntryShopType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * EntryType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('shop_name', TextType::class, [
                'required' => true,
            ])
            ->add('manager1', TextType::class, [
                'required' => true,
            ])
            ->add('address', TextType::class, [
                'required' => true,
            ])
            ->add('tel', TextType::class, [
                'required' => true,
            ])
            ->add('email', TextType::class, [
                'required' => true,
            ])
            ->add('page', TextType::class, [
                'required' => true,
            ])
            ->add('manager2', TextType::class, [
                'required' => true,
            ])
            ->add('job', TextType::class, [
                'required' => true,
            ])
            ->add('code', TextType::class, [
                'required' => true,
            ])
            // ->add('register_date', BirthdayType::class, [
            //     'required' => false,
            //     'input' => 'datetime',
            //     'years' => range(date('Y'), date('Y') - $this->eccubeConfig['eccube_birth_max']),
            //     'widget' => 'choice',
            //     'format' => 'yyyy/MM/dd',
            //     'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            //     'constraints' => [
            //         new Assert\LessThanOrEqual([
            //             'value' => date('Y-m-d', strtotime('-1 day')),
            //             'message' => 'form_error.select_is_future_or_now_date',
            //         ]),
            //     ],
            // ])
            // ->add('effective_date', BirthdayType::class, [
            //     'required' => false,
            //     'input' => 'datetime',
            //     'years' => range(date('Y'), date('Y') - $this->eccubeConfig['eccube_birth_max']),
            //     'widget' => 'choice',
            //     'format' => 'yyyy/MM/dd',
            //     'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
            //     'constraints' => [
            //         new Assert\LessThanOrEqual([
            //             'value' => date('Y-m-d', strtotime('-1 day')),
            //             'message' => 'form_error.select_is_future_or_now_date',
            //         ]),
            //     ],
            // ])
            ->add('upload_file_file', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('upload_file_file2', FileType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('upload_file', HiddenType::class, [
                'required' => false,
            ])
            ->add('upload_file2', HiddenType::class, [
                'required' => false,
            ]);


            // ->add('upload_file', TextType::class, [
            //     'required' => true,
            // ]);

        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $CustomerShop = $event->getData();
            if ($CustomerShop instanceof CustomerShop && !$CustomerShop->getId()) {
                $form = $event->getForm();

                $form->add('shop_policy_check', CheckboxType::class, [
                        'required' => true,
                        'label' => null,
                        'mapped' => false,
                        'constraints' => [
                            new Assert\NotBlank(),
                        ],
                    ]);
            }
        }
        );

        // $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
        //     $form = $event->getForm();
        //     /** @var Customer $Customer */
        //     // $Customer = $event->getData();
        //     // if ($Customer->getPassword() != '' && $Customer->getPassword() == $Customer->getEmail()) {
        //     //     $form['password']['first']->addError(new FormError(trans('common.password_eq_email')));
        //     // }
        // });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Eccube\Entity\CustomerShop',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        // todo entry,mypageで共有されているので名前を変更する
        return 'entryShop';
    }
}
