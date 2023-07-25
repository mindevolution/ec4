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

/**
 * Config
 *
 * @ORM\Table(name="plg_stripe_config")
 * @ORM\Entity(repositoryClass="Plugin\StripePaymentGateway\Repository\StripeConfigRepository")
 */
class StripeConfig
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="publishable_key", type="string", length=255, nullable=true)
     */
    private $publishable_key;

    /**
     * @var string
     *
     * @ORM\Column(name="secret_key", type="string", length=255, nullable=true)
     */
    private $secret_key;

    /**
     * @var string
     *
     * @ORM\Column(name="webhook_signature", type="string", length=255, nullable=true)
     */
    private $webhook_signature;

    
    /**
     * @var int
     *
     * @ORM\Column(name="is_auth_and_capture_on", type="integer", options={"default" : 0}, nullable=true)
     */
    private $is_auth_and_capture_on;

    /**
     * @var string
     *
     * @ORM\Column(name="stripe_fees_percent", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $stripe_fees_percent;
    
    /**
     * @var boolean
     *
     * @ORM\Column(name="prod_detail_ga_enable", type="integer", options={"default" : 0}, nullable=true)
     */
    private $prod_detail_ga_enable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="cart_ga_enable", type="integer", options={"default" : 0}, nullable=true)
     */
    private $cart_ga_enable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="checkout_ga_enable", type="integer", options={"default" : 0}, nullable=true)
     */
    private $checkout_ga_enable;

    /**
     * @var boolean
     *
     * @ORM\Column(name="konbini_enable", type="integer", options={"default" : 0}, nullable=true)
     */
    private $konbini_enable;

    public function setProdDetailGaEnable($prod_detail_ga_enable){
        $this->prod_detail_ga_enable = $prod_detail_ga_enable > 0;
        return $this;
    }
    public function getProdDetailGaEnable(){
        return $this->prod_detail_ga_enable > 0;
    }
    public function setCartGaEnable($cart_ga_enable){
        $this->cart_ga_enable = $cart_ga_enable > 0;
        return $this;
    }
    public function getCartGaEnable(){
        return $this->cart_ga_enable > 0;
    }
    public function setCheckoutGaEnable($checkout_ga_enable){
        $this->checkout_ga_enable = $checkout_ga_enable > 0;
        return $this;
    }
    public function getCheckoutGaEnable(){
        return $this->checkout_ga_enable > 0;
    }
    public function setKonbiniEnable($konbini_enable) {
        $this->konbini_enable = $konbini_enable > 0;
        return $this;
    }
    public function getKonbiniEnable() {
        return $this->konbini_enable > 0;
    }
    public function getWebhookSignature() {
        return $this->webhook_signature;
    }
    public function setWebhookSignature($webhook_signature) {
        $this->webhook_signature = $webhook_signature;
        return $this;
    }
    
    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPublishableKey()
    {
        return $this->publishable_key;
    }

    /**
     * @param string $publishable_key
     *
     * @return $this;
     */
    public function setPublishableKey($publishable_key)
    {
        $this->publishable_key = $publishable_key;

        return $this;
    }

    /**
     * @return string
     */
    public function getSecretKey()
    {
        return $this->secret_key;
    }

    /**
     * @param string $secret_key
     *
     * @return $this;
     */
    public function setSecretKey($secret_key)
    {
        $this->secret_key = $secret_key;

        return $this;
    }

    /**
     * @return boolean
     */
    public function getIsAuthAndCaptureOn()
    {
        return $this->is_auth_and_capture_on > 0? true:false;
    }

    /**
     * @param boolean $is_auth_and_capture_on
     *
     * @return $this;
     */
    public function setIsAuthAndCaptureOn($is_auth_and_capture_on)
    {
        $this->is_auth_and_capture_on = $is_auth_and_capture_on? 1:0;

        return $this;
    }

    /**
     * @return string
     */
    public function getStripeFeesPercent()
    {
        return $this->stripe_fees_percent;
    }

    /**
     * @param string $stripe_fees_percent
     *
     * @return $this;
     */
    public function setStripeFeesPercent($stripe_fees_percent)
    {
        $this->stripe_fees_percent = $stripe_fees_percent;

        return $this;
    }
}
