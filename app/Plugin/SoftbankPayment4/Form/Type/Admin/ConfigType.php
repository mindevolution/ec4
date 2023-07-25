<?php

namespace Plugin\SoftbankPayment4\Form\Type\Admin;

use Plugin\SoftbankPayment4\Entity\Config;
use Plugin\SoftbankPayment4\Entity\Master\CaptureType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsCredit3dUseType;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Plugin\SoftbankPayment4\Repository\PayMethodRepository;
use Plugin\SoftbankPayment4\Service\PayMethodHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ConfigType extends AbstractType
{
    const MERCHANT_ID_LENGTH = 5;
    const SERVICE_ID_LENGTH = 3;
    const SECRET_KEY_3DES_LENGTH = 24;
    const INITIAL_VECTOR_LENGTH = 8;

    public function __construct(
        PayMethodHelper $payMethodHelper,
        PayMethodRepository $payMethodRepository
    )
    {
        $this->payMethodHelper = $payMethodHelper;
        $this->payMethodRepository = $payMethodRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('merchant_id', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ マーチャントIDが入力されていません。']),
                    new Assert\Length([
                        'min' => self::MERCHANT_ID_LENGTH,
                        'max' => self::MERCHANT_ID_LENGTH,
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ マーチャントIDは数字で入力してください。',
                    ])
                ],
                'attr' => [
                    'maxlength' => self::MERCHANT_ID_LENGTH,
                ],
            ])

            ->add('service_id', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ サービスIDが入力されていません。']),
                    new Assert\Length([
                        'min' => self::SERVICE_ID_LENGTH,
                        'max' => self::SERVICE_ID_LENGTH,
                    ]),
                    new Assert\Regex([
                        'pattern' => "/^[0-9]+$/",
                        'match' => true,
                        'message' => '※ サービスIDは数字で入力してください。',
                    ])
                ],
                'attr' => [
                    'maxlength' => self::SERVICE_ID_LENGTH,
                ],
            ])

            ->add('hash_key', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ ハッシュキーが入力されていません。']),
                ],
            ])

            ->add('secret_key_3des', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 3DES暗号化キーが入力されていません。']),
                    new Assert\Length([
                        'max' => self::SECRET_KEY_3DES_LENGTH,
                    ]),
                ],
                'attr' => [
                    'maxlength' => self::SECRET_KEY_3DES_LENGTH,
                ],
            ])

            ->add('initial_vector', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 初期化ベクトルが入力されていません。']),
                    new Assert\Length([
                        'max' => self::INITIAL_VECTOR_LENGTH,
                    ]),
                ],
                'attr' => [
                    'maxlength' => self::INITIAL_VECTOR_LENGTH,
                ],
            ])

            ->add('sbps_ip_address', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Callback(function(
                        $object,
                        ExecutionContextInterface $context
                    ) {
                        if($object) {
                            if(!preg_match('/(([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([1-9]?[0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])/', $object)) {
                                $context
                                    ->buildViolation('※ IPアドレスの形式が不正です。')
                                    ->atPath('sbps_ip_address')
                                    ->addViolation();
                            }
                        }
                    })
                ]
            ])

            ->add('capture_type', ChoiceType::class, [
                'choices' => CaptureType::$choice,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ 売上タイプが入力されていません。']),
                ],
                'expanded' => true,
            ])

            ->add('link_request_url', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Url(['message' => '※ URLの形式が不正です。']),
                ]
            ])

            ->add('api_request_url', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Url(['message' => '※ URLの形式が不正です。']),
                ]
            ])

            ->add('sbps_credit3d_use', ChoiceType::class, [
                'choices' => SbpsCredit3dUseType::$choice,
                'constraints' => [
                    new Assert\NotBlank(['message' => '※ クレジットカード3Dセキュアが入力されていません。']),
                ],
                'expanded' => true,
            ])

            ->add('pay_methods', ChoiceType::class, [
                'choices' => [
                    PayMethodType::getPayMethodList(),
                ],
                'data' => $this->payMethodRepository->getEnableCodes(),
                'mapped' => false,
                'multiple' => true,
                'expanded' => true,
            ])

            ->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
                $form = $event->getForm('pay_methods');
                $inputs = $form['pay_methods']->getData();

                $this->payMethodHelper->commitPayMethods($inputs, $event->getData());

            });
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
