<?php

namespace Plugin\SoftbankPayment4\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{
    /**
     * @var \Plugin\SoftbankPayment4\Entity\SbpsTrade
     *
     * @ORM\OneToOne(targetEntity="Plugin\SoftbankPayment4\Entity\SbpsTrade", mappedBy="Order", cascade={"persist","remove"})
     */
    private $SbpsTrade;

    public function getSbpsTrade()
    {
        return $this->SbpsTrade;
    }

    public function setSbpsTrade($sbps_trade)
    {
        $this->SbpsTrade = $sbps_trade;
        return $this;
    }
}
