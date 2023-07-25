<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/


namespace Plugin\StripePaymentGateway\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * トークンを保持するカラム.
     * 永続化は行わず, 注文確認画面で表示する.
     *
     *
     * @var string
     */
    private $stripe_payment_intent_id;

    private $is_save_card_on;

    /**
     * @var string
     *
     * @ORM\Column(name="temp_intent_id", type="string", length=255, nullable=true)
     */
    private $temp_intent_id;

    
    public function getTempIntentId(){
        return $this->temp_intent_id;
    }
    public function setTempIntentId($temp_intent_id){
        $this->temp_intent_id = $temp_intent_id;
        return $this;
    }

    /**
     * @return string
     */
    public function getStripePaymentIntentId()
    {
        return $this->stripe_payment_intent_id;
    }

    /**
     * @param string $stripe_payment_intent_id
     *
     * @return $this;
     */
    public function setStripePaymentIntentId($stripe_payment_intent_id)
    {
        $this->stripe_payment_intent_id = $stripe_payment_intent_id;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsSaveCardOn()
    {
        return $this->is_save_card_on;
    }

    /**
     * @param boolean $is_save_card_on
     *
     * @return $this;
     */
    public function setIsSaveCardOn($is_save_card_on)
    {
        $this->is_save_card_on = $is_save_card_on;

        return $this;
    }
}
