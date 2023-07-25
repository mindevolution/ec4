<?php

namespace Plugin\SoftbankPayment4\Form\Type\Admin;

use Plugin\SoftbankPayment4\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ActionType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add('action', ChoiceType::class, [
                'choices' => [
                    '売上確定' => 'capture',
                    '取消返金' => 'refund',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 接続タイプが入力されていません。']),
                ],
                'multiple' => false,
                'expanded' => false,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
