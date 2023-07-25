<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4\Form\Type\Admin;

use Eccube\Common\EccubeConfig;
use Eccube\Util\StringUtil;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ContactReplyType extends AbstractType
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * CustomerType constructor.
     */
    public function __construct(
        EccubeConfig $eccubeConfig,
        RequestStack $requestStack
    ) {
        $this->eccubeConfig = $eccubeConfig;
        $this->requestStack = $requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ])
            ->add('mail_subject', TextType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                    new Assert\Length([
                        'max' => $this->eccubeConfig['eccube_stext_len'],
                    ]),
                ],
            ])
            ->add('contents', TextareaType::class, [
                'required' => true,
                'constraints' => [
                    new Assert\NotBlank(),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            $form = $event->getForm();
            $request = $this->requestStack->getCurrentRequest();
            if ($request->get('mode') === 'send') {
                if (StringUtil::isBlank($form['email']->getData())) {
                    $form['email']->addError(new FormError('メール送信する場合は必須です'));
                }

                if (StringUtil::isBlank($form['mail_subject']->getData())) {
                    $form['mail_subject']->addError(new FormError('メール送信する場合は必須です'));
                }
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Plugin\ContactManagement4\Entity\ContactReply',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'admin_contact_reply';
    }
}
