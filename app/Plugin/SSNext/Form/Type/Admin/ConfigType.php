<?php

namespace Plugin\SSNext\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Validator\Email;
use Plugin\SSNext\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(EccubeConfig $eccubeConfig)
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('is_product_code', ChoiceType::class, [
                'label' => trans('ss_next.admin.config.is_product_code.name'),
                'required' => true,
                'choices'  => [
                    trans('ss_next.config.option.product_class_id') => false,
                    trans('ss_next.config.option.product_class_code') => true,
                ],
                'expanded' => true,
            ])
            ->add('api_key', TextType::class, [
                'label' => trans('ss_next.admin.config.api_key.name'),
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('order_mail', EmailType::class, [
                'label' => trans('ss_next.admin.config.order_mail.name'),
                'required' => true,
                'constraints' => [
                    new Email(['strict' => $this->eccubeConfig['eccube_rfc_email_check']]),
                    new Assert\NotBlank(),
                ],
            ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}