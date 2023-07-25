<?php

namespace Plugin\RakutenCard4\Form\Extension\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Admin\SearchOrderType;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Form\Extension\RakutenFormExtensionInterface;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class SearchOrderTypeExtension extends AbstractTypeExtension implements RakutenFormExtensionInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    public function __construct(
        EccubeConfig $eccubeConfig
    )
    {
        $this->eccubeConfig = $eccubeConfig;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $authorized = trans('rakuten_card4.payment_status_name.authorized');
        $authorizedCvs = trans('rakuten_card4.payment_status_name.authorized_cvs');
        $captured = trans('rakuten_card4.payment_status_name.captured');
        $capturedCvs = trans('rakuten_card4.payment_status_name.captured_cvs');

        $data = [
            'rakuten_card4.payment_status_name.first' => ConstantPaymentStatus::First,
            'rakuten_card4.payment_status_name.canceled' => ConstantPaymentStatus::Canceled,
            $authorized . '・' . $authorizedCvs => ConstantPaymentStatus::Authorized,
            $captured . '・' . $capturedCvs => ConstantPaymentStatus::Captured,
            'rakuten_card4.payment_status_name.pending' => ConstantPaymentStatus::Pending,
            'rakuten_card4.payment_status_name.expired_cvs' => ConstantPaymentStatus::ExpiredCvs,
        ];

        $builder->add('rakuten_payment_status', ChoiceType::class, [
            'label' => '楽天決済ステータス',
            'multiple' => true,
            'required' => false,
            'expanded' => true,
            'choices' => $data,
            'eccube_form_options' => [
                'auto_render' => true, // 自動表示フラグ
                'form_theme' => '@RakutenCard4/admin/Order/form/search_order_rakuten_form.twig' // 表示したいtwigファイル
            ]
        ]);
    }

    /**
     * 4.0.x対応
     * {@inheritdoc}
     */
    public function getExtendedType()
    {
        return SearchOrderType::class;
    }


    /**
     * 4.1.0対応
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [SearchOrderType::class];
    }
}