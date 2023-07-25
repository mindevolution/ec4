<?php



namespace Plugin\AdminSecurity4\Form\Type\Admin;

use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints as Assert;

class SearchLoginRecordType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * SearchContactType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     */
    public function __construct(
        EccubeConfig $eccubeConfig
    ) {
        $this->eccubeConfig = $eccubeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // 会員ID・メールアドレス・名前・名前(フリガナ)
            ->add('multi', TextType::class, [
                'label' => 'admin.admin_record.multi_search_label',
                'required' => false,
                'constraints' => [
                    new Assert\Length(['max' => $this->eccubeConfig['eccube_stext_len']]),
                ],
            ])
            ->add('user_name', TextType::class, [
                'label' => 'admin.admin_record.user_name',
                'required' => false,

            ])
            ->add('client_ip', TextType::class, [
                'label' => 'admin.admin_record.client_ip',
                'required' => false,
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'admin.admin_record.success_or_failure',
                'required' => false,
                'multiple' => true,
                'expanded' => true,
                'choices' => [
                    'admin.admin_record.success' => Constant::ENABLED,
                    'admin.admin_record.failure' => Constant::DISABLED,
                ]
            ])
            ->add('create_date_start', DateType::class, [
                'label' => 'admin.admin_record.create_date__start',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_start',
                    'data-toggle' => 'datetimepicker',
                ],
            ])
            ->add('create_date_end', DateType::class, [
                'label' => 'admin.admin_record.create_date__end',
                'required' => false,
                'input' => 'datetime',
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'placeholder' => ['year' => '----', 'month' => '--', 'day' => '--'],
                'attr' => [
                    'class' => 'datetimepicker-input',
                    'data-target' => '#'.$this->getBlockPrefix().'_create_date_end',
                    'data-toggle' => 'datetimepicker',
                ],
            ])

        ;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'plugin_search_login_record';
    }
}
