<?php

namespace Plugin\RakutenCard4\Form\Type\Admin;

use Plugin\RakutenCard4\Common\ConstantCard;
use Plugin\RakutenCard4\Common\ConstantConfig;
use Plugin\RakutenCard4\Common\ConstantCvs;
use Plugin\RakutenCard4\Common\EccubeConfigEx;
use Plugin\RakutenCard4\Entity\Config;
use Plugin\RakutenCard4\Form\Type\FormUtilTrait;
use Plugin\RakutenCard4\Service\CardService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\LessThanOrEqual;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigType extends AbstractType
{
    use FormUtilTrait;

    /** @var ValidatorInterface */
    protected $validator;

    /** @var EccubeConfigEx */
    private $config;
    /** @var CardService */
    private $cardService;

    /**
     * OrderItemType constructor.
     * @param ValidatorInterface $validator
     * @param EccubeConfigEx $config
     * @param CardService $cardService
     */
    public function __construct(
        ValidatorInterface $validator
        , EccubeConfigEx $config
        , CardService $cardService
    )
    {
        $this->validator = $validator;
        $this->config = $config;
        $this->cardService = $cardService;
    }


    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $Config = $options['data'];
        $data = null;
        if($Config instanceof Config) {
          $data = $Config->getDecodeCvsKindcode();
        }

        $card_service_id = 'card_service_id';
        $card_auth_key = 'card_auth_key';
        $cvs_sub_service_id_seven = 'cvs_sub_service_id_seven';
        $cvs_sub_service_id_lawson = 'cvs_sub_service_id_lawson';
        $cvs_sub_service_id_yamazaki = 'cvs_sub_service_id_yamazaki';
        $cvs_limit_day = 'cvs_limit_day';

        $builder
            ->add('connection_mode', ChoiceType::class, [
                'label' => trans('rakuten_card4.admin.config.col1'),
                'required' => true,
                'choices' => [
                    trans('rakuten_card4.admin.config.col1.label1') => ConstantConfig::CONNECTION_MODE_STG,
                    trans('rakuten_card4.admin.config.col1.label2') => ConstantConfig::CONNECTION_MODE_PROD,
                ],
                'expanded' => true,
                'attr' => [
                    'class' => 'form-check-inline',
                ],
                'constraints' => [

                    new NotBlank(),
                ],
            ])
            ->add($card_service_id, TextType::class, ['required' => true, 'constraints' => [new NotBlank(),],])
            ->add($card_auth_key, TextType::class, ['required' => true, 'constraints' => [new NotBlank(),],])
            ->add($cvs_sub_service_id_seven, TextType::class, ['required' => false,])
            ->add($cvs_sub_service_id_lawson, TextType::class, ['required' => false,])
            ->add($cvs_sub_service_id_yamazaki, TextType::class, ['required' => false,])
            ->add($cvs_limit_day, TextType::class, [
                'required' => false,
                'mapped' => true,
                'attr' => [
                    'maxlength' => '2',
                    'pattern' => '\d*',
                ],
                'constraints' => [
                    new LessThanOrEqual([
                        'value' => 60,
                        'message' => trans(
                            trans('rakuten_card4.admin.config.cvs_limit_day_valid_message', ['%max_day%' => 60])
                        ),
                    ]),
                ],
            ]);

        $choices = [
            ConstantCvs::CVS_KIND_SEVENELEVEN => 'rakuten_card4.cvs.label.kind001',
            ConstantCvs::CVS_KIND_LAWSON => 'rakuten_card4.cvs.label.kind002',
            ConstantCvs::CVS_KIND_DAILYYAMAZAKI => 'rakuten_card4.cvs.label.kind003',
        ];

        $builder->add('cvs_kind', ChoiceType::class, [
                    'choices' => array_flip($choices),
                    'data' => $data,
                    'required' => true,
                    'expanded' => true,
                    'multiple' => true,
                    'attr' => [
                        'class' => 'form-check-inline',
                    ],
                    'mapped' => false,
                ]);

        // カード詳細
        $col_installments  = 'card_installments_ex';
        $col_3d_secure_use = 'card_3d_secure_use';
        $col_cvv_use       = 'card_cvv_use';
        $col_challenge_type = 'card_challenge_type';
        $col_3d_store_name = 'card_3d_store_name';
        $col_buy_api = 'card_buy_api';
        $col_3d_secures = [
            $col_3d_store_name => TextType::class,
            'card_merchant_id_visa' => TextType::class,
            'card_merchant_id_master_card' => TextType::class,
        ];

        $challenge_selects = ConstantCard::CHALLENGE_TYPE_LABEL;
        if (!$this->config->card_3d_challenge_type_all()){
            unset($challenge_selects[ConstantCard::CHALLENGE_FORCE]);
        }

        $col_cards = [
            $col_installments => ChoiceType::class,
            $col_challenge_type => [ChoiceType::class, $challenge_selects],
            $col_3d_secure_use => [ChoiceType::class, ConstantConfig::USE_SELECT_LABEL],
            $col_cvv_use => [ChoiceType::class, ConstantConfig::USE_SELECT_LABEL],
            $col_buy_api => [ChoiceType::class, ConstantCard::BUY_API_LABEL],
        ];
        $card_cols = array_merge($col_3d_secures, $col_cards);

        foreach ($card_cols as $card_col=>$type){
            switch ($card_col){
                case $col_installments:
                    $list = $this->config->card_installments_kind();
                    $list[] = 1;
                    $list[] = ConstantCard::WITH_BONUS;
                    $list[] = ConstantCard::WITH_REVOLVING;
                    $list = array_unique($list);
                    $choices = $this->cardService->changeInstallments($list);
                    ksort($choices);
                    // 支払い種別
                    $builder->add($card_col, $type, [
                        'choices' => array_flip($choices),
                        'required' => true,
                        'expanded' => true,
                        'multiple' => true,
                        'attr' => [
//                            'class' => 'form-check-inline',
                        ],
                    ]);
                    break;
                case $col_challenge_type:
                case $col_3d_secure_use:
                case $col_cvv_use:
                case $col_buy_api:
                    // 選択肢
                    $builder->add($card_col, $type[0], [
                        'choices' => array_flip($type[1]),
                        'required' => true,
                        'expanded' => true,
                        'attr' => [
                            'class' => 'form-check-inline',
                        ],
                    ]);
                    break;
                case $col_3d_store_name:
                    $builder->add($card_col, $type, [
                        'required' => false,
                        'attr' => [
                            'maxlength' => $this->config->card_3d_store_name_len(),
                        ],
                        'constraints' => [
                            new Length([
                                'max' => $this->config->card_3d_store_name_len(),
                            ]),
                        ],
                    ]);

                    break;
                default:
                    // 通常のテキスト
                    $builder->add($card_col, $type, ['required' => false,]);
                    break;
            }
        }

        // 登録処理
        $builder->addEventListener(FormEvents::POST_SUBMIT,
            function (FormEvent $event) use (
                $cvs_sub_service_id_yamazaki,
                $cvs_sub_service_id_lawson,
                $cvs_sub_service_id_seven,
                $col_cards,
                $cvs_limit_day
            ) {
                $form = $event->getForm();
                /** @var Config $data */
                $data = $form->getData();

                // カードのチェックは支払い方法が入力されたら必須にする
                if (!empty($data->getCardInstallmentsEx())){
                    foreach ($col_cards as $card_col=>$val){
                        $errors = $this->validator->validate($data[$card_col], [new NotBlank()]);
                        $this->addErrorsIfExists($form[$card_col], $errors);
                    }
                }

                if($data->getCvsKind()) {
                    $cvs_kinds = $form->get('cvs_kind')->getData();
                    foreach ($cvs_kinds as $cvsKind) {
                        switch ($cvsKind) {
                            case ConstantCvs::CVS_KIND_SEVENELEVEN:
                                $errors = $this->validator->validate($data->getCvsSubServiceIdSeven(), [new NotBlank()]);
                                $this->addErrorsIfExists($form[$cvs_sub_service_id_seven], $errors);
                                break;
                            case ConstantCvs::CVS_KIND_LAWSON:
                                $errors = $this->validator->validate($data->getCvsSubServiceIdLawson(), [new NotBlank()]);
                                $this->addErrorsIfExists($form[$cvs_sub_service_id_lawson], $errors);
                                break;
                            case ConstantCvs::CVS_KIND_DAILYYAMAZAKI:
                                $errors = $this->validator->validate($data->getCvsSubServiceIdYamazaki(), [new NotBlank()]);
                                $this->addErrorsIfExists($form[$cvs_sub_service_id_yamazaki], $errors);
                                break;
                            default:
                                break;
                        }
                    }
                }
                if (!empty($data->getCvsSubServiceIdSeven()) ||
                    !empty($data->getCvsSubServiceIdLawson()) ||
                    !empty($data->getCvsSubServiceIdYamazaki())){
                    $errors = $this->validator->validate($data->getCvsLimitDay(), [new NotBlank()]);
                    $this->addErrorsIfExists($form[$cvs_limit_day], $errors);
                }
            });
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
