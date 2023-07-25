<?php

namespace Plugin\RakutenCard4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Common\Constant;

/**
 * Rc4CustomerToken
 *
 * @ORM\Table(name="plg_rakuten_card4_customer_token")
 * @ORM\Entity(repositoryClass="Plugin\RakutenCard4\Repository\Rc4CustomerTokenRepository")
 */
class Rc4CustomerToken extends \Eccube\Entity\AbstractEntity implements Rc4TokenEntityInterface
{
    use Rc4TokenTrait, Rc4PaymentCommonTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Eccube\Entity\Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
     * })
     */
    private $Customer;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz", nullable=true)
     */
    private $create_date;

    /**
     * @var integer
     *
     * @ORM\Column(name="registered", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $registered;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz", nullable=true)
     */
    private $update_date;

    public function getDeleteLabel()
    {
        return ' ';
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Eccube\Entity\Customer
     */
    public function getCustomer(): \Eccube\Entity\Customer
    {
        return $this->Customer;
    }

    /**
     * @param \Eccube\Entity\Customer $Customer
     * @return self
     */
    public function setCustomer(?\Eccube\Entity\Customer $Customer)
    {
        $this->Customer = $Customer;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate(): \DateTime
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $create_date
     * @return self
     */
    public function setCreateDate(?\DateTime $create_date)
    {
        $this->create_date = $create_date;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate(): \DateTime
    {
        return $this->update_date;
    }

    /**
     * @param \DateTime $update_date
     * @return self
     */
    public function setUpdateDate(?\DateTime $update_date)
    {
        $this->update_date = $update_date;
        return $this;
    }

    /**
     * @return int
     */
    public function getRegistered()
    {
        return $this->registered;
    }

    /**
     * @param int $registered
     * @return Rc4CustomerToken
     */
    public function setRegistered(?int $registered)
    {
        $this->registered = $registered;
        return $this;
    }

    /**
     * カードが登録済みかどうか
     *
     * @return bool true: 登録済み
     */
    public function isRegistered()
    {
        return $this->registered == Constant::ENABLED;
    }

    /**
     * 登録済みカードを設定する
     *
     * @param Rc4OrderPayment $OrderPayment
     * @return bool
     */
    public function setRegisterCard($OrderPayment)
    {
        if (is_null($OrderPayment)){
            return false;
        }

        // 登録済みカード情報を設定する
        $this->transaction_id = $OrderPayment->getTransactionId();
        $this->registered = Constant::ENABLED;
        $this->ip_address = $OrderPayment->getIpAddress();
        $this->setCommonRegisterCard($OrderPayment);

        return true;
    }
}
