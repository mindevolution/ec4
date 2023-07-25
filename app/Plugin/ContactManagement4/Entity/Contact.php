<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Contact
 *
 * @ORM\Table(name="plg_contact_management4_contact")
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement4\Repository\ContactRepository")
 */
class Contact extends \Eccube\Entity\AbstractEntity
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
     * @ORM\Column(name="name01", type="string", length=255)
     */
    private $name01;

    /**
     * @var string
     *
     * @ORM\Column(name="name02", type="string", length=255)
     */
    private $name02;

    /**
     * @var string|null
     *
     * @ORM\Column(name="kana01", type="string", length=255, nullable=true)
     */
    private $kana01;

    /**
     * @var string|null
     *
     * @ORM\Column(name="kana02", type="string", length=255, nullable=true)
     */
    private $kana02;

    /**
     * @var string|null
     *
     * @ORM\Column(name="postal_code", type="string", length=8, nullable=true)
     */
    private $postal_code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr01", type="string", length=255, nullable=true)
     */
    private $addr01;

    /**
     * @var string|null
     *
     * @ORM\Column(name="addr02", type="string", length=255, nullable=true)
     */
    private $addr02;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255)
     */
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(name="phone_number", type="string", length=14, nullable=true)
     */
    private $phone_number;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $contents;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $extra;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $note;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    private $contact_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="create_date", type="datetimetz")
     */
    private $create_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="update_date", type="datetimetz")
     */
    private $update_date;

    /**
     * @var \Eccube\Entity\Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $Customer;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="staff_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     */
    private $Staff;

    /**
     * @var \Eccube\Entity\Customer
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Product")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="product_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $Product;

    /**
     * @var \Eccube\Entity\Master\Pref
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Master\Pref")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pref_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $Pref;

    /**
     * @var \Plugin\ContactManagement4\Entity\ContactStatus
     *
     * @ORM\ManyToOne(targetEntity="\Plugin\ContactManagement4\Entity\ContactStatus")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_status_id", referencedColumnName="id", nullable=true)
     * })
     */
    private $Status;

    /**
     * @var \Doctrine\Common\Collections\Collection|ContactReply[]
     *
     * @ORM\OneToMany(targetEntity="Plugin\ContactManagement4\Entity\ContactReply", mappedBy="Contact", cascade={"persist"})
     * @ORM\OrderBy({
     *    "reply_date"="DESC"
     * })
     */
    private $ContactReplies;

    public function __construct()
    {
        $this->ContactReplies = new ArrayCollection();
    }

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
    public function getName01()
    {
        return $this->name01;
    }

    /**
     * @param string $name01
     *
     * @return Contact
     */
    public function setName01($name01)
    {
        $this->name01 = $name01;

        return $this;
    }

    /**
     * @return string
     */
    public function getName02()
    {
        return $this->name02;
    }

    /**
     * @param string $name02
     *
     * @return Contact
     */
    public function setName02($name02)
    {
        $this->name02 = $name02;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKana01()
    {
        return $this->kana01;
    }

    /**
     * @param string|null $kana01
     *
     * @return Contact
     */
    public function setKana01($kana01)
    {
        $this->kana01 = $kana01;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getKana02()
    {
        return $this->kana02;
    }

    /**
     * @param string|null $kana02
     *
     * @return Contact
     */
    public function setKana02($kana02)
    {
        $this->kana02 = $kana02;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPostalCode()
    {
        return $this->postal_code;
    }

    /**
     * @param string|null $postal_code
     *
     * @return Contact
     */
    public function setPostalCode($postal_code)
    {
        $this->postal_code = $postal_code;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddr01()
    {
        return $this->addr01;
    }

    /**
     * @param string|null $addr01
     *
     * @return Contact
     */
    public function setAddr01($addr01)
    {
        $this->addr01 = $addr01;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAddr02()
    {
        return $this->addr02;
    }

    /**
     * @param string|null $addr02
     *
     * @return Contact
     */
    public function setAddr02($addr02)
    {
        $this->addr02 = $addr02;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return Contact
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPhoneNumber()
    {
        return $this->phone_number;
    }

    /**
     * @param string|null $phone_number
     *
     * @return Contact
     */
    public function setPhoneNumber($phone_number)
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    /**
     * @return \Eccube\Entity\Customer
     */
    public function getCustomer()
    {
        return $this->Customer;
    }

    /**
     * @param \Eccube\Entity\Customer $Customer
     *
     * @return Contact
     */
    public function setCustomer($Customer)
    {
        $this->Customer = $Customer;

        return $this;
    }

    /**
     * @return \Eccube\Entity\Customer
     */
    public function getProduct()
    {
        return $this->Product;
    }

    /**
     * @param \Eccube\Entity\Customer $Product
     *
     * @return Contact
     */
    public function setProduct($Product)
    {
        $this->Product = $Product;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getContactDate()
    {
        return $this->contact_date;
    }

    /**
     * @param \DateTime $contact_date
     *
     * @return Contact
     */
    public function setContactDate($contact_date)
    {
        $this->contact_date = $contact_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->create_date;
    }

    /**
     * @param \DateTime $create_date
     *
     * @return Contact
     */
    public function setCreateDate($create_date)
    {
        $this->create_date = $create_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->update_date;
    }

    /**
     * @param \DateTime $update_date
     *
     * @return Contact
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * @return \Eccube\Entity\Master\Pref
     */
    public function getPref()
    {
        return $this->Pref;
    }

    /**
     * @param \Eccube\Entity\Master\Pref $Pref
     *
     * @return Contact
     */
    public function setPref($Pref)
    {
        $this->Pref = $Pref;

        return $this;
    }

    /**
     * @return ContactStatus
     */
    public function getStatus()
    {
        return $this->Status;
    }

    /**
     * @param ContactStatus $Status
     *
     * @return Contact
     */
    public function setStatus($Status)
    {
        $this->Status = $Status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getContents()
    {
        return $this->contents;
    }

    /**
     * @param string|null $contents
     *
     * @return Contact
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getExtra()
    {
        return $this->extra;
    }

    /**
     * @param string|null $extra
     *
     * @return Contact
     */
    public function setExtra($extra)
    {
        $this->extra = $extra;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getNote()
    {
        return $this->note;
    }

    /**
     * @param string|null $note
     *
     * @return Contact
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * @return \Eccube\Entity\Member
     */
    public function getStaff()
    {
        return $this->Staff;
    }

    /**
     * @param \Eccube\Entity\Member $Staff
     *
     * @return Contact
     */
    public function setStaff($Staff)
    {
        $this->Staff = $Staff;

        return $this;
    }

    /**
     * @return \Doctrine\Common\Collections\Collection|ContactReply[]
     */
    public function getContactReplies()
    {
        return $this->ContactReplies;
    }

    /**
     * @param \Doctrine\Common\Collections\Collection|ContactReply[] $ContactReplies
     *
     * @return Contact
     */
    public function setContactReplies($ContactReplies)
    {
        $this->ContactReplies = $ContactReplies;

        return $this;
    }
}
