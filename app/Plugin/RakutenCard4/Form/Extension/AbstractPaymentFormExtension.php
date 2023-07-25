<?php

namespace Plugin\RakutenCard4\Form\Extension;

use Eccube\Entity\Order;
use Eccube\Form\Type\Shopping\OrderType;
use Symfony\Component\Form\AbstractTypeExtension;

/**
 * 注文手続き画面のFormを拡張し、カード入力フォームを追加する.
 * 支払い方法に応じてエクステンションを作成する.
 */
abstract class AbstractPaymentFormExtension extends AbstractTypeExtension implements RakutenFormExtensionInterface
{
    protected $self_method_class = null;

    /**
     * @param array $options
     * @return bool true form処理をスキップする
     */
    protected function isSkip($options)
    {
        return boolval($options['skip_add_form']);
    }

    /**
     * 支払い方法がこのFormのものかどうか
     *
     * @param Order $Order
     */
    protected function isNotSelfPayment($Order)
    {
        $Payment = $Order->getPayment();
        if (is_null($Payment)){
            return false;
        }

        $this->setSelfMethodClass();
        return $Payment->getMethodClass() != $this->self_method_class;
    }

    /**
     * 以下などFormで設定する項目
     *
     * $this->self_method_class = CreditCard::class;
     */
    abstract protected function setSelfMethodClass();

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
    public static function getExtendedTypes(): iterable
    {
        return [OrderType::class];
    }
}
