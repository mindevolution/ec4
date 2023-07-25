<?php

namespace Plugin\RakutenCard4\Form\Type;

use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Payment;
use Plugin\RakutenCard4\Common\ConstantCard;
use Plugin\RakutenCard4\Entity\Rc4CustomerToken;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Plugin\RakutenCard4\Service\UserTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CardOrderPaymentType extends AbstractType
{
    use UserTrait;
    use FormUtilTrait;

    /** @var CardService */
    protected $cardService;
    /** @var ValidatorInterface */
    protected $validator;

    public function __construct(
        ContainerInterface $container
        , CardService $cardService
        , ValidatorInterface $validator
    )
    {
        $this->container = $container;
        $this->cardService = $cardService;
        $this->validator = $validator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // 設定時には必須チェックを行わない。
        // ShoppingControllerはいろいろなところに遷移してそのたびにチェックが走るため

        /** @var Customer $Customer */
        $Customer = $this->getUser();

        // 登録済みカードがある場合
        if (!is_null($Customer) && $this->cardService->isExistRegisterCard($Customer)){
            $builder
                ->add('CustomerToken', ChoiceType::class, [
                    'label' => false,
                    'required' => false,
                    'choice_label' => 'DeleteLabel',
                    'multiple' => false,
                    'mapped' => false,
                    'expanded' => true,
                    'choices' => $this->cardService->getFrontRegisterCards(),
                    'choice_value' => function (Rc4CustomerToken $CustomerToken = null) {
                        return $CustomerToken ? $CustomerToken->getId() : null;
                    },
                ])
            ;
        }
        $builder
            ->add('card_use_kind', ChoiceType::class, [
                'label' => false,
                'required' => false,
                'multiple' => false,
                'mapped' => true,
                'expanded' => true,
                'choices' => array_flip(ConstantCard::USE_KIND_LABEL),
            ])
            ->add('card_check_register', CheckboxType::class, [
                'label' => 'rakuten_card4.front.shopping.card.register.message',
                'value' => Constant::ENABLED,
                'required' => false,
            ])
        ;

        $installments = array_flip($this->cardService->getConfigInstallments());
        $builder
            ->add('card_installment', ChoiceType::class, [
                'label' => 'rakuten_card4.admin.config.card_installments',
                'mapped' => true,
                'choices' => $installments,
            ])
//            ->add('card_installment_register', ChoiceType::class, [
//                'label' => 'rakuten_card4.admin.config.card_installments',
//                'mapped' => false,
//                'choices' => $installments,
//            ])
        ;

        // エラーチェック
        $builder->addEventListener(FormEvents::POST_SUBMIT,
            function (FormEvent $event){
                $form = $event->getForm();
                /** @var Rc4OrderPayment $OrderPayment */
                $OrderPayment = $form->getData();
                /** @var Payment $Payment */
                $Payment = $form->getParent()->get('Payment')->getData();
                if ($Payment->getMethodClass() != CreditCard::class){
                    // 支払い方法が異なればチェックしない
                    return;
                }

                // 選択別の必須チェック
                $required_list = [];
                $required_list[] = 'card_use_kind';
                $required_list[] = 'card_installment';
                if ($OrderPayment->isUseCardInput()){
                }else{
                    $required_list[] = 'CustomerToken';
//                    $required_list[] = 'card_installment_register';
                }

                foreach ($required_list as $col){
                    $errors = $this->validator->validate($form->get($col)->getData(), [new NotBlank()]);
                    $this->addErrorsIfExists($form[$col], $errors);
                }
            }, 100);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => Rc4OrderPayment::class,
            ]
        );
    }
}
