<?php

namespace Plugin\SoftbankPayment4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * PayMethod
 *
 * @ORM\Table(name="plg_softbank_payment4_pay_method")
 * @ORM\Entity(repositoryClass="Plugin\SoftbankPayment4\Repository\PayMethodRepository")
 */
class PayMethod
{
    /**
     * @var int
     *
     * @ORM\Column(name="code", type="integer", options={"unsigned":true})
     * @ORM\Id
     */
    private $code;

    /**
     * @var bool
     *
     * @ORM\Column(name="enable", type="boolean", options={"default":false})
     */
    private $enable;

    /**
     * @var \Plugin\SoftbankPayment4\Entity\Config
     *
     * @ORM\ManyToOne(targetEntity="\Plugin\SoftbankPayment4\Entity\Config", inversedBy="PayMethods")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="config_id", referencedColumnName="id")
     * })
     */
   private $Config;

    public function getCode()
    {
        return $this->code;
    }

    public function setCode($code)
    {
        $this->code = $code;

        return $this;
    }

    public function getEnale()
    {
        return $this->enable;
    }

    public function setEnable($enable)
    {
        $this->enable = $enable;

        return $this;
    }

    public function getConfig()
    {
        return $this->Config();
    }

    public function setConfig($Config)
    {
        $this->Config = $Config;
        return $this;
    }
}
