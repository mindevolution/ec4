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
 * @ORM\Table(name="plg_contact_management4_contact_reply")
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement4\Repository\ContactReplyRepository")
 */
class ContactReply extends \Eccube\Entity\AbstractEntity
{
    public function __construct()
    {
        $this->MailHistories = new ArrayCollection();
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
    private $email;

    /**
     * @var string|null
     *
     * @ORM\Column(type="text", nullable=true)
     */
    private $mail_subject;

    /**
     * @var \DateTime
     *
     * @ORM\Column(type="datetimetz", nullable=true)
     */
    private $reply_date;

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
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="staff_id", referencedColumnName="id", nullable=true, onDelete="SET NULL")
     * })
     */
    private $Staff;

    /**
     * @var \Plugin\ContactManagement4\Entity\Contact
     *
     * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement4\Entity\Contact", inversedBy="ContactReplies")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id", nullable=false, onDelete="CASCADE")
     * })
     */
    private $Contact;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ContactManagement4\Entity\MailHistory", mappedBy="ContactReply", cascade={"remove"})
     * @ORM\OrderBy({
     *     "send_date"="DESC"
     * })
     */
    private $MailHistories;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     * @return ContactReply
     */
    public function setContents($contents)
    {
        $this->contents = $contents;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getReplyDate()
    {
        return $this->reply_date;
    }

    /**
     * @param \DateTime $reply_date
     *
     * @return ContactReply
     */
    public function setReplyDate($reply_date)
    {
        $this->reply_date = $reply_date;

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
     * @return ContactReply
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
     * @return ContactReply
     */
    public function setUpdateDate($update_date)
    {
        $this->update_date = $update_date;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string|null $email
     *
     * @return ContactReply
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getMailSubject()
    {
        return $this->mail_subject;
    }

    /**
     * @param string|null $mail_subject
     *
     * @return ContactReply
     */
    public function setMailSubject($mail_subject)
    {
        $this->mail_subject = $mail_subject;

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
     * @return ContactReply
     */
    public function setStaff($Staff)
    {
        $this->Staff = $Staff;

        return $this;
    }

    /**
     * @return Contact
     */
    public function getContact()
    {
        return $this->Contact;
    }

    /**
     * @param Contact $Contact
     *
     * @return ContactReply
     */
    public function setContact($Contact)
    {
        $this->Contact = $Contact;

        return $this;
    }

    /**
     * Add mailHistory.
     *
     * @return ContactReply
     */
    public function addMailHistory(MailHistory $mailHistory)
    {
        $this->MailHistories[] = $mailHistory;

        return $this;
    }

    /**
     * Remove mailHistory.
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeMailHistory(MailHistory $mailHistory)
    {
        return $this->MailHistories->removeElement($mailHistory);
    }

    /**
     * Get mailHistories.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getMailHistories()
    {
        return $this->MailHistories;
    }
}
