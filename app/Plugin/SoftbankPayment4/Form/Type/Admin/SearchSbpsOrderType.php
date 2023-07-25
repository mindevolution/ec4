<?php

namespace Plugin\SoftbankPayment4\Form\Type\Admin;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Eccube\Common\EccubeConfig;
use Eccube\Form\Type\Master\OrderStatusType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

use Plugin\SoftbankPayment4\Repository\PayMethodRepository;

class SearchSbpsOrderType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;
    /**
     * @var PayMethodRepository
     */
    private $payMethodRepository;

    public function __construct(EccubeConfig $eccubeConfig, PayMethodRepository $payMethodRepository)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->payMethodRepository = $payMethodRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('status', OrderStatusType::class, [
                'label' => 'admin.order.order_status',
                'expanded' => true,
                'multiple' => true,
            ])
            ->add('pay_methods', ChoiceType::class, [
                'label' => 'admin.common.payment_method',
                'choices' => $this->payMethodRepository->getStoredList(),
                'data' => [],
                'mapped' => true,
                'multiple' => true,
                'expanded' => true,
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_search_order';
    }
}
