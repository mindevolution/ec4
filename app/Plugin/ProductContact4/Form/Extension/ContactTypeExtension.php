<?php

namespace Plugin\ProductContact4\Form\Extension;

use Eccube\Entity\Product;
use Eccube\Form\DataTransformer\EntityToIdTransformer;
use Eccube\Form\Type\Front\ContactType;
use Symfony\Bridge\Doctrine\ManagerRegistry;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class ContactTypeExtension extends AbstractTypeExtension
{
    protected $doctrine;

    public function __construct(ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add($builder->create('Product', HiddenType::class, [
            'required' => false,
            'eccube_form_options' => [
                'auto_render' => true,
                'form_theme' => '@ProductContact4/Form/product_contact_layout.twig',
            ]
            ])
        ->addModelTransformer(new EntityToIdTransformer($this->doctrine->getManager(), Product::class)));

        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $data = $event->getData();
            if (isset($data['Product']) && !is_null($data['Product'])) {
                $form = $event->getForm();
                $form['contents']->setData('「'.$data['Product']->getName().'」についての問い合わせです。');
            }
        });
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getExtendedType()
    {
        return ContactType::class;
    }
}
