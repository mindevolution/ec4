<?php

namespace Plugin\SSNext\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Shipping")
 */
trait ShippingTrait
{
    public function getShippingZip01()
    {
        return substr($this->postal_code, 0, 3);
    }

    public function getShippingZip02()
    {
        return substr($this->postal_code, 3);
    }
}