<?php

namespace Plugin\RakutenCard4\Entity;

interface Rc4TokenEntityInterface
{
    /**
     */
    public function getPaymentInfo();

    /**
     * @param $payment_info
     * @return self
     */
    public function setPaymentInfo($payment_info);

    /**
     * @param $data
     */
    public function setPaymentInfoLog($data);

    /**
     * 決済のログ情報を設定する
     * @param $data
     */
    public function addPaymentLog($data);

    /**
     * @return string|null
     */
    public function getTransactionId();

    /**
     * @param string|null $transaction_id
     * @return self
     */
    public function setTransactionId(?string $transaction_id);

    /**
     * @return string|null
     */
    public function getIpAddress();

    /**
     * @param string|null $ip_address
     * @return self
     */
    public function setIpAddress(?string $ip_address);

    /**
     * @return string|null
     */
    public function getPaymentLog();

    /**
     * @param string|null $payment_log
     * @return self
     */
    public function setPaymentLog(?string $payment_log);


    /**
     * @return string|null
     */
    public function getCardToken();

    /**
     * @param string|null $card_token
     * @return self
     */
    public function setCardToken(?string $card_token);

    /**
     * @return int
     */
    public function getCardBrand();

    /**
     * @param int $card_brand
     * @return self
     */
    public function setCardBrand(?int $card_brand);

    /**
     * @return string|null
     */
    public function getCardCvvToken();

    /**
     * @param string|null $card_cvv_token
     * @return self
     */
    public function setCardCvvToken(?string $card_cvv_token);

    /**
     * @return string|null
     */
    public function getCardIin();

    /**
     * @param string|null $card_iin
     * @return self
     */
    public function setCardIin(?string $card_iin);

    /**
     * @return string|null
     */
    public function getCardLast4digits();

    /**
     * @param string|null $card_last4digits
     * @return self
     */
    public function setCardLast4digits(?string $card_last4digits);

    /**
     * @return string|null
     */
    public function getCardMonth();

    /**
     * @param string|null $card_month
     * @return self
     */
    public function setCardMonth(?string $card_month);

    /**
     * @return string|null
     */
    public function getCardYear();

    /**
     * @param string|null $card_year
     * @return self
     */
    public function setCardYear(?string $card_year);


}
