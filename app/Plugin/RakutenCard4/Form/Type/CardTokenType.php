<?php

namespace Plugin\RakutenCard4\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

class CardTokenType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $col_list = [
            'resultType',
            'timestamp',
            'errorCode',
            'errorMessage',
            'cardToken',
            'iin',
            'last4digits',
            'expirationMonth',
            'expirationYear',
            'brandCode',
            'issuerCode',
            'cardType',
            'cvvToken',
            'signature',
            'keyVersion',
        ];

        foreach ($col_list as $col)
        {
            $builder->add($col, HiddenType::class, []);
        }
    }
}
