<?php

namespace Plugin\SSNext\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Order")
 */
trait OrderTrait
{

    /**
     * @var bool
     * @ORM\Column(name="next_send_flg", type="boolean", nullable=true, options={"default":false})
     */
    protected $next_send_flg;

    /**
     * @return bool
     */
    public function isNextSendFlg()
    {
        return $this->next_send_flg == true;
    }

    /**
     * @param bool $next_send_flg
     * @return $this
     */
    public function setNextSendFlg(bool $next_send_flg)
    {
        $this->next_send_flg = $next_send_flg;
        return $this;
    }

    public function getOrderZip01()
    {
        return substr($this->postal_code, 0, 3);
    }

    public function getOrderZip02()
    {
        return substr($this->postal_code, 3);
    }

}