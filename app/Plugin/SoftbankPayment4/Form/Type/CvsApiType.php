<?php

namespace Plugin\SoftbankPayment4\Form\Type;

use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsCvsType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CvsApiType extends AbstractType
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
        $builder
            ->add('cvs_type', ChoiceType::class, [
                'choices' => [
                    SbpsCvsType::getPayMethodList(),
                ],
                'mapped' => true,
                'expanded' => true,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
           'csrf_protection' => true,
           'csrf_field_name' => '_token',
        ]);
    }


}
