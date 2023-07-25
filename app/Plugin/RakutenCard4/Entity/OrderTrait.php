<?php

namespace Plugin\RakutenCard4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var Rc4OrderPayment
     *
     * @ORM\OneToOne(targetEntity="Plugin\RakutenCard4\Entity\Rc4OrderPayment", mappedBy="Order", cascade={"persist","remove"})
     */
    private $Rc4OrderPayment;

    /**
     * @return Rc4OrderPayment
     */
    public function getRc4OrderPayment()
    {
        return $this->Rc4OrderPayment;
    }

    /**
     * @param Rc4OrderPayment|null $Rc4OrderPayment
     * @return self
     */
    public function setRc4OrderPayment(?Rc4OrderPayment $Rc4OrderPayment)
    {
        $this->Rc4OrderPayment = $Rc4OrderPayment;
        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentTotalInt()
    {
        return intval($this->payment_total);
    }
}
