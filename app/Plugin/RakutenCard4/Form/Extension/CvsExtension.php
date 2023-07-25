<?php

namespace Plugin\RakutenCard4\Form\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\Order;
use Eccube\Form\Type\Shopping\OrderType;
use Eccube\Repository\PaymentRepository;
use Plugin\RakutenCard4\Common\ConstantCvs;
use Plugin\RakutenCard4\Entity\Config;
use Plugin\RakutenCard4\Entity\Rc4OrderPayment;
use Plugin\RakutenCard4\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;


/**
 * 注文手続き画面のFormを拡張し、コンビニ選択フォームを追加する.
 * 支払い方法に応じてエクステンションを作成する.
 */
class CvsExtension extends AbstractTypeExtension implements RakutenFormExtensionInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var PaymentRepository
     */
    protected $paymentRepository;

    /**
     * @var ConfigRepository
     */
    protected $configRepository;

    public function __construct(
        EccubeConfig     $eccubeConfig,
        PaymentRepository $paymentRepository,
        ConfigRepository $configRepository
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->paymentRepository = $paymentRepository;
        $this->configRepository = $configRepository;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // ShoppingController::checkoutから呼ばれる場合は, フォーム項目の定義をスキップする.
        if ($options['skip_add_form']) {
            return;
        }

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            /** @var Order $Order */
            $Order = $event->getData();
            $form = $event->getForm();

            // プラグイン設定をセット
            /** @var Config $config */
            $config = $this->configRepository->get();
            // フォームに入力項目を追加
            $this->appendFormConvenience($event, $config);

            // 決済入力値をセットする
            $this->setDataConvenience($event, $Order->getRc4OrderPayment());

        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $options = $event->getForm()->getConfig()->getOptions();

            // 注文確認->注文処理時はフォームは定義されない.
            if ($options['skip_add_form']) {
                /** @var Order $Order */
                $Order = $event->getForm()->getData();
                $Order->getPayment()->getId();

                return;
            } else {

                $data = $event->getData();
                $form = $event->getForm();
                /** @var Order $Order */
                $Order = $event->getForm()->getData();
            }
        });
    }

    /**
     * コンビニ決済向けのフォームを追加
     *
     * @param FormEvent $event フォームイベント
     * @param Config $config プラグイン設定
     * @param array $method_config 支払方法設定
     */
    private function appendFormConvenience(FormEvent $event, Config $config)
    {
        $form = $event->getForm();

        $cvsKinds =  $config->getDecodeCvsKindcode();
        $data = [];
        foreach ($cvsKinds as $cvsKind) {
            switch ($cvsKind) {
                case ConstantCvs::CVS_KIND_SEVENELEVEN:
                    $data[ConstantCvs::CVS_KIND_SEVENELEVEN] = 'rakuten_card4.cvs.label.kind001';
                    break;
                case ConstantCvs::CVS_KIND_LAWSON:
                    $data[ConstantCvs::CVS_KIND_LAWSON] = 'rakuten_card4.cvs.label.kind002';
                    break;
                case ConstantCvs::CVS_KIND_DAILYYAMAZAKI:
                    $data[ConstantCvs::CVS_KIND_DAILYYAMAZAKI] = 'rakuten_card4.cvs.label.kind003';
                    break;
                default:
                    break;
            }
        }

        $form
            // コンビニ選択
            ->add('cvs_kind', ChoiceType::class, [
                'choices' => array_flip($data),
                'required' => false,
                'placeholder' => false,
                'expanded' => true,
                'mapped' => false,
            ]);
    }

    private function setDataConvenience(FormEvent $event, ?Rc4OrderPayment $Rc4OrderPayment)
    {
        $form = $event->getForm();
        if(empty($Rc4OrderPayment)) {
            return;
        }
        // コンビニ選択
        $form['cvs_kind']->setData($Rc4OrderPayment->getCvsKind());
    }

    /**
     * 4.0.x対応
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return OrderType::class;
    }

    /**
     * 4.1.0対応
     * {@inheritdoc}
     */
    public static function getExtendedTypes() : iterable
    {
        return [OrderType::class];
    }
}
