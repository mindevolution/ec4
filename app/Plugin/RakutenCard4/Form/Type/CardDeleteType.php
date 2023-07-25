<?php

namespace Plugin\RakutenCard4\Form\Type;

use Plugin\RakutenCard4\Entity\Rc4CustomerToken;
use Plugin\RakutenCard4\Service\CardService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class CardDeleteType extends AbstractType
{
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
        $builder
            ->add('CustomerToken', ChoiceType::class, [
                'label' => false,
                'choice_label' => 'DeleteLabel',
                'multiple' => true,
                'mapped' => true,
                'expanded' => true,
                'choices' => $this->cardService->getFrontRegisterCards(),
                'choice_value' => function (Rc4CustomerToken $CustomerToken = null) {
                    return $CustomerToken ? $CustomerToken->getId() : null;
                },
            ])
        ;
    }

}
