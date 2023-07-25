<?php

namespace Plugin\SSNext\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Config
 *
 * @ORM\Table(name="plg_ss_next_config")
 * @ORM\Entity(repositoryClass="Plugin\SSNext\Repository\ConfigRepository")
 */
class Config
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
     * @ORM\Column(name="api_key", type="text", nullable=true)
     */
    private $api_key;

    /**
     * @var boolean
     *
     * @ORM\Column(name="is_product_code", type="boolean", options={"default":false})
     */
    private $is_product_code;

    /**
     * @var string
     *
     * @ORM\Column(name="order_mail", type="text", nullable=true)
     */
    private $order_mail;

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
    public function getApiKey()
    {
        return $this->api_key ? $this->api_key : '';
    }

    /**
     * @param string $api_key
     * @return Config
     */
    public function setApiKey(string $api_key)
    {
        $this->api_key = $api_key;
        return $this;
    }

    /**
     * @return bool
     */
    public function isProductCode()
    {
        return $this->is_product_code;
    }

    /**
     * @param bool $is_product_code
     * @return Config
     */
    public function setIsProductCode($is_product_code)
    {
        $this->is_product_code = $is_product_code ? true : false;
        return $this;
    }

    /**
     * @return string
     */
    public function getOrderMail()
    {
        return $this->order_mail;
    }

    /**
     * @param string $order_mail
     * @return Config
     */
    public function setOrderMail(string $order_mail)
    {
        $this->order_mail = $order_mail;
        return $this;
    }

}