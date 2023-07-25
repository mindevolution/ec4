<?php

namespace Plugin\AdminSecurity4\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Plugin\AdminSecurity4\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Ip;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ConfigType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var ValidatorInterface
     */
    protected $validator;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * SecurityType constructor.
     *
     * @param EccubeConfig $eccubeConfig
     * @param ValidatorInterface $validator
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        ValidatorInterface $validator,
        RequestStack $requestStack)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->validator = $validator;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder

            ->add('admin_deny_hosts', TextareaType::class, [
                'required' => false,
                'label' => '拒否IPアドレス',
                'constraints' => [
                    new Length(['max' => $this->eccubeConfig['eccube_ltext_len']]),
                ],
            ])

            ->addEventListener(FormEvents::POST_SUBMIT, function ($event) {
                $form = $event->getForm();
                $data = $form->getData();

                $ips = preg_split("/\R/", $data->getAdminDenyHosts(), null, PREG_SPLIT_NO_EMPTY);

                foreach ($ips as $ip) {
                    $errors = $this->validator->validate($ip, [
                            new Ip(),
                        ]
                    );
                    if ($errors->count() != 0) {
                        $form['admin_deny_hosts']->addError(
                            new FormError(trans('admin.setting.system.security.ip_limit_invalid_ipv4', ['%ip%' => $ip]))
                        );
                    }
                }

            })
        ;
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
