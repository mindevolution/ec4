<?php

namespace Plugin\RakutenCard4\Form\Extension;

use Eccube\Entity\Order;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Form\Type\CardOrderPaymentType;
use Plugin\RakutenCard4\Service\CardService;
use Plugin\RakutenCard4\Service\Method\CreditCard;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * 注文手続き画面のFormを拡張し、カード入力フォームを追加する.
 * 支払い方法に応じてエクステンションを作成する.
 */
class CreditCardExtension extends AbstractPaymentFormExtension implements RakutenFormExtensionInterface
{
    const ADD_FORM_COL_NAME = 'rakuten_card4';

    /** @var CardService */
    protected $cardService;

    public function __construct(
        CardService $cardService
    )
    {
        $this->cardService = $cardService;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // ShoppingController::checkoutから呼ばれる場合は, フォーム項目の定義をスキップする.
        if ($this->isSkip($options)) {
            return;
        }

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var Order $Order */
            $form = $event->getForm();
            $Order = $form->getData();

            if ($this->isNotSelfPayment($Order)){
                return;
            }

            // 支払い方法が一致する場合
            $form->add( self::ADD_FORM_COL_NAME, CardOrderPaymentType::class, [
                'mapped' => false,
            ]);

            $ex_form = $form->get(self::ADD_FORM_COL_NAME);
            $OrderPayment = $Order->getRc4OrderPayment();
            if (is_null($OrderPayment)){
                $OrderPayment = $this->cardService->createOrderPaymentRequireSet();
                $OrderPayment->setOrder($Order);
            }
            $ex_form->setData($OrderPayment);
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            // サンプル決済では使用しないが、支払い方法に応じて処理を行う場合は
            // $event->getData()ではなく、$event->getForm()->getData()でOrderエンティティを取得できる

            /** @var Order $Order */
            $Order = $event->getForm()->getData();
            if ($this->isNotSelfPayment($Order)){
                return;
            }
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();

            /** @var Order $Order */
            $Order = $form->getData();
            if ($this->isNotSelfPayment($Order)){
                return;
            }

            // redirect_toで他の支払い方法から通るときの対処
            if (!$form->has(self::ADD_FORM_COL_NAME)){
                return;
            }

            $ex_form = $form->get(self::ADD_FORM_COL_NAME);
            /** @var Rc4OrderPayment $OrderPayment */
            $OrderPayment = $ex_form->getData();
            if ($OrderPayment->isUseCardRegister()){
                // 登録済みカード情報をOrderPaymentに設定する
                $CustomerToken = $ex_form->get('CustomerToken')->getData();
                $OrderPayment->setRegisterCard($CustomerToken);
            }

            $Order->setRc4OrderPayment($OrderPayment);
            $OrderPayment->setOrder($Order);
        });
    }

    /**
     * 支払い方法のメソッドクラスを設定
     */
    protected function setSelfMethodClass()
    {
        $this->self_method_class = CreditCard::class;
    }

}
