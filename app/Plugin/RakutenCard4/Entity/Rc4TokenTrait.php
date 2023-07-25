<?php

namespace Plugin\RakutenCard4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Rc4TokenTrait
 */
trait Rc4TokenTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="card_token", type="string", length=255, nullable=true)
     */
    private $card_token;

    /**
     * @var string|null
     *
     * @ORM\Column(name="card_cvv_token", type="string", length=255, nullable=true)
     */
    private $card_cvv_token;

    /**
     * @var integer
     *
     * @ORM\Column(name="card_brand", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_brand;

    /**
     * @var string|null
     *
     * @ORM\Column(name="card_iin", type="string", length=255, nullable=true)
     */
    private $card_iin;

    /**
     * @var string|null
     *
     * @ORM\Column(name="card_last4digits", type="string", length=255, nullable=true)
     */
    private $card_last4digits;

    /**
     * @var string|null
     *
     * @ORM\Column(name="card_month", type="string", length=255, nullable=true)
     */
    private $card_month;

    /**
     * @var string|null
     *
     * @ORM\Column(name="card_year", type="string", length=255, nullable=true)
     */
    private $card_year;

    /**
     * @return string|null
     */
    public function getCardToken()
    {
        return $this->card_token;
    }

    /**
     * @param string|null $card_token
     * @return self
     */
    public function setCardToken(?string $card_token)
    {
        $this->card_token = $card_token;
        return $this;
    }

    /**
     * @return int
     */
    public function getCardBrand()
    {
        return $this->card_brand;
    }

    /**
     * @param int $card_brand
     * @return self
     */
    public function setCardBrand(?int $card_brand)
    {
        $this->card_brand = $card_brand;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardCvvToken()
    {
        return $this->card_cvv_token;
    }

    /**
     * @param string|null $card_cvv_token
     * @return self
     */
    public function setCardCvvToken(?string $card_cvv_token)
    {
        $this->card_cvv_token = $card_cvv_token;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardIin()
    {
        return $this->card_iin;
    }

    /**
     * @param string|null $card_iin
     * @return self
     */
    public function setCardIin(?string $card_iin)
    {
        $this->card_iin = $card_iin;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardLast4digits()
    {
        return $this->card_last4digits;
    }

    /**
     * カード番号
     *
     * @return string
     */
    public function getCardNo()
    {
        return '******' . '******' . $this->getCardLast4digits();
//        return $this->getCardIin() . '******' . $this->getCardLast4digits();
    }

    /**
     * @param string|null $card_last4digits
     * @return self
     */
    public function setCardLast4digits(?string $card_last4digits)
    {
        $this->card_last4digits = $card_last4digits;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardMonth()
    {
        return $this->card_month;
    }

    /**
     * @param string|null $card_month
     * @return self
     */
    public function setCardMonth(?string $card_month)
    {
        $this->card_month = $card_month;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardYear()
    {
        return $this->card_year;
    }

    /**
     * @param string|null $card_year
     * @return self
     */
    public function setCardYear(?string $card_year)
    {
        $this->card_year = $card_year;
        return $this;
    }

    public function getCardExpireDate()
    {
        return trans('rakuten_card4.front.card.expire.date', [
            '%year%' => $this->card_year,
            '%month%' => $this->card_month,
        ]);
    }

    /**
     * @param Rc4TokenEntityInterface $TokenEntity
     */
    public function setCommonRegisterCard($TokenEntity)
    {
        $this->card_token = $TokenEntity->getCardToken();
        $this->card_brand = $TokenEntity->getCardBrand();
        $this->card_iin = $TokenEntity->getCardIin();
        $this->card_last4digits = $TokenEntity->getCardLast4digits();
        $this->card_month = $TokenEntity->getCardMonth();
        $this->card_year = $TokenEntity->getCardYear();
    }
}
