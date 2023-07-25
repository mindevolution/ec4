<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/

namespace Plugin\StripePaymentGateway\Form\Type\Admin;

use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Eccube\Form\Type\ToggleSwitchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class StripeConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('publishable_key', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(array(
                            'message' => trans('stripe_payment_gateway.admin.config.publishable_key.error.blank')
                        )
                    ),
                    new Assert\Regex(array(
                            'pattern' => '/^\w+$/',
                            'match' => true,
                            'message' => trans('stripe_payment_gateway.admin.config.publishable_key.error.regex')
                        )
                    )
                ],
            ])
            ->add('secret_key', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(array(
                        'message' => trans('stripe_payment_gateway.admin.config.secret_key.error.blank')
                    )),
                    new Assert\Regex(array(
                            'pattern' => '/^\w+$/',
                            'match' => true,
                            'message' => trans('stripe_payment_gateway.admin.config.secret_key.error.regex')
                        )
                    )
                ],
            ])
            ->add('webhook_signature', TextType::class, [
                'required'=>false,
                'constraints' => [
                    new Assert\Regex(array(
                            'pattern' => '/^\w+$/',
                            'match' => true,
                            'message' => trans('stripe_payment_gateway.admin.config.secret_key.error.regex')
                        )
                    )
                ],
            ])
            ->add('is_auth_and_capture_on', ChoiceType::class,[
                'choices' => [
                    'stripe_payment_gateway.admin.config.authorize' => false, 
                    'stripe_payment_gateway.admin.config.authorize_and_capture' => true
                ]
            ])
            ->add('stripe_fees_percent', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Regex(array(
                            'pattern' => '/^100$|^\d{0,2}(\.\d{1,2})? *%?$/',
                            'match' => true,
                            'message' => trans('stripe_payment_gateway.admin.config.stripe_fees_percent.error.regex')
                        )
                    )
                ],
            ])
            ->add('prod_detail_ga_enable', CheckboxType::class, [
                'label'     =>  '商品詳細ページに表示',
                'required'  =>  false
            ])
            ->add('cart_ga_enable', CheckboxType::class, [
                'label'     =>  'カートページに表示',
                'required'  =>  false,
            ])
            ->add('checkout_ga_enable', CheckboxType::class, [
                'label'     =>  'カード決済ページに表示',
                'required'  =>  false,
            ])
            ->add('konbini_enable', ToggleSwitchType::class)
            
            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm();
                $data = $event->getData();
                $webhook_signature = $data->getWebhookSignature();
                if ($data->getKonbiniEnable() && !$webhook_signature) {
                    $form['webhook_signature']->addError(new FormError(trans('stripe_payment_gateway.admin.config.webhook_signature.empty.error')));
                }
            });
            
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => StripeConfig::class,
        ]);
    }
}