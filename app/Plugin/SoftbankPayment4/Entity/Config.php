<?php

namespace Plugin\SoftbankPayment4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Plugin\SoftbankPayment4\Entity\Master\SbpsCredit3dUseType;

/**
 * Config
 *
 * @ORM\Table(name="plg_softbank_payment4_config")
 * @ORM\Entity(repositoryClass="Plugin\SoftbankPayment4\Repository\ConfigRepository")
 */
class Config
{
    public function __construct()
    {
        $this->PayMethods = new ArrayCollection();
    }

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="marchant_id", type="integer", options={"unsigned":true}, nullable=true)
     */
    private $merchant_id;

    /**
     * @var int
     *
     * @ORM\Column(name="service_id", type="string", length=8, nullable=true)
     */
    private $service_id;

    /**
     * @var string
     *
     * @ORM\Column(name="hash_key", type="string", length=255, nullable=true)
     */
    private $hash_key;

    /**
     * @var string
     *
     * @ORM\Column(name="secret_key_3des", type="string", length=24, nullable=true)
     */
    private $secret_key_3des;

    /**
     * @var string
     *
     * @ORM\Column(name="initial_vector", type="string", length=8, nullable=true)
     */
    private $initial_vector;

    /**
     * @var string
     *
     * @ORM\Column(name="sbps_ip_address", type="string", length=64, nullable=true)
     */
    private $sbps_ip_address;

    /**
     * @var int
     *
     * @ORM\Column(name="capture_type", type="smallint", length=8, nullable=true, options={"unsigned":true})
     */
    private $capture_type;

    /**
     * @var string
     *
     * @ORM\Column(name="link_request_url", type="text", length=65535, nullable=true)
     */
    private $link_request_url;

    /**
     * @var string
     *
     * @ORM\Column(name="api_request_url", type="text", length=65535, nullable=true)
     */
    private $api_request_url;

     /**
     * @var int
     *
     * @ORM\Column(name="sbps_credit3d_use", type="smallint", length=8, nullable=true, options={"unsigned":true})
     */
    private $sbps_credit3d_use;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\SoftbankPayment4\Entity\PayMethod", mappedBy="Config", cascade={"persist","remove"})
     */
    private $PayMethods;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set merchantId.
     *
     * @param string $merchantId
     *
     * @return Config
     */
    public function setMerchantId($merchantId): Config
    {
        $this->merchant_id = $merchantId;

        return $this;
    }

    /**
     * Get merchantId.
     *
     * @return string
     */
    public function getMerchantId(): ?string
    {
        return $this->merchant_id;
    }

    /**
     * Set serviceId.
     *
     * @param string $serviceId
     *
     * @return Config
     */
    public function setServiceId($serviceId): Config
    {
        $this->service_id = $serviceId;

        return $this;
    }

    /**
     * Get serviceId.
     *
     * @return string
     */
    public function getServiceId(): ?string
    {
        return $this->service_id;
    }

    /**
     * Set hashKey.
     *
     * @param string $hashKey
     *
     * @return Config
     */
    public function setHashKey($hashKey): Config
    {
        $this->hash_key = $hashKey;

        return $this;
    }

    /**
     * Get hashKey.
     *
     * @return string
     */
    public function getHashKey(): ?string
    {
        return $this->hash_key;
    }

    /**
     * Set secretKey3des.
     *
     * @param string $secretKey3des
     *
     * @return Config
     */
    public function setSecretKey3des($secretKey3des): Config
    {
        $this->secret_key_3des = $secretKey3des;

        return $this;
    }

    /**
     * Get secretKey3des.
     *
     * @return string
     */
    public function getSecretKey3des(): ?string
    {
        return $this->secret_key_3des;
    }

    /**
     * Set initialVector.
     *
     * @param string $initialVector
     *
     * @return Config
     */
    public function setInitialVector($initialVector): Config
    {
        $this->initial_vector = $initialVector;

        return $this;
    }

    /**
     * Get initialVector.
     *
     * @return string
     */
    public function getInitialVector(): ?string
    {
        return $this->initial_vector;
    }

    /**
     * Set sbpsIpAddress.
     *
     * @param string $sbpsIpAddress
     *
     * @return Config
     */
    public function setSbpsIpAddress($sbpsIpAddress): Config
    {
        $this->sbps_ip_address = $sbpsIpAddress;

        return $this;
    }

    /**
     * Get sbpsIpAddress.
     *
     * @return string
     */
    public function getSbpsIpAddress(): ?string
    {
        return $this->sbps_ip_address;
    }

    /**
     * Set linkRequestUrl.
     *
     * @param string $linkRequestUrl
     *
     * @return Config
     */
    public function setLinkRequestUrl($linkRequestUrl): Config
    {
        $this->link_request_url = $linkRequestUrl;

        return $this;
    }

    /**
     * Get linkRequestUrl.
     *
     * @return string
     */
    public function getLinkRequestUrl(): ?string
    {
        return $this->link_request_url;
    }

    /**
     * Set capture_type.
     *
     * @param int $capture_type
     *
     * @return Config
     */
    public function setCaptureType($capture_type): Config
    {
        $this->capture_type = $capture_type;

        return $this;
    }

    /**
     * Get capture_type.
     *
     * @return int
     */
    public function getCaptureType(): ?int
    {
        return $this->capture_type;
    }

    /**
     * Set apiRequestUrl.
     *
     * @param string $apiRequestUrl
     *
     * @return Config
     */
    public function setApiRequestUrl($apiRequestUrl): Config
    {
        $this->api_request_url = $apiRequestUrl;

        return $this;
    }

    /**
     * Get apiRequestUrl.
     *
     * @return string
     */
    public function getApiRequestUrl(): ?string
    {
        return $this->api_request_url;
    }

    /**
     * Add PayMethod.
     *
     * @param PayMethod $PayMethod
     *
     * @return Config
     */
    public function addPayMethod(PayMethod $PayMethod): Config
    {
        $this->PayMethods[] = $PayMethod;

        return $this;
    }

    /**
     * Remove PayMethod.
     *
     * @param PayMethod $PayMethod
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removePayMethod(PayMethod $PayMethod): bool
    {
        return $this->PayMethods->removeElement($PayMethod);
    }

    public function getPayMethods()
    {
        return $this->PayMethods->getValues();
    }

    /**
     * Get the value of sbps_credit3d_use
     *
     * @return  int
     */
    public function getSbpsCredit3dUse(): ?int
    {
        return ($this->sbps_credit3d_use ? $this->sbps_credit3d_use : SbpsCredit3dUseType::SBPSCREDIT3D_USE);
    }

    /**
     * Set the value of sbps_credit3d_use
     *
     * @param   int  $sbps_credit3d_use  
     *
     * @return  Config
     */
    public function setSbpsCredit3dUse($sbps_credit3d_use): Config
    {
        $this->sbps_credit3d_use = $sbps_credit3d_use;

        return $this;
    }
}
