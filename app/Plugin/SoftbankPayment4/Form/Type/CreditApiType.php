<?php

namespace Plugin\SoftbankPayment4\Form\Type;

use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CreditApiType extends AbstractType
{
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    public function __construct(ConfigRepository $configRepository)
    {
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $Config = $this->configRepository->get();
        // JSで制御して、トークンのみを送信するので、バリデーションは無し.
        $builder
            ->add('merchant_id', HiddenType::class, [
                'mapped' => false,
                'data' => $Config->getMerchantId(),
            ])

            ->add('service_id', HiddenType::class, [
                'mapped' => false,
                'data' => $Config->getServiceId(),
            ])

            ->add('card_no_1', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'maxlength' => 4,
                ],
            ])
            ->add('card_no_2', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'maxlength' => 4,
                ],
            ])
            ->add('card_no_3', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'maxlength' => 4,
                ],
            ])
            ->add('card_no_4', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'maxlength' => 4,
                ],
            ])

            ->add('expiration_date', DateType::class, [
                'label' => '有効期限',
                'mapped' => false,
                'required' => false,
                'format' => 'yyyy / MM dd',    // NOTE: dd は消せないようなのでデザインで消す.
                'years'=> range(date('Y'), date('Y', strtotime('+19 year'))),    // 今の年を合せて20年分.
            ])

            ->add('security_code', TextType::class, [
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'maxlength' => 4,
                ],
            ])

            ->add('token', HiddenType::class, [
                'mapped' => false,
            ])

            ->add('token_key', HiddenType::class, [
                'mapped' => false,
            ])

            ->add('use_stored_card', ChoiceType::class, [
                'choices' => ['登録済みのカードを使う' => true],
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])

            ->add('store_card', ChoiceType::class, [
                'choices' => ['このカードを登録する' => true],
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
           'csrf_protection' => true,
           'csrf_field_name' => '_token',
        ]);
    }


}
