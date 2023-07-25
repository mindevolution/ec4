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

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

if (!class_exists('\Plugin\ContactManagement4\Entity\MailHistory')) {
    /**
     * MailHistory
     *
     * @ORM\Table(name="plg_contact_management4_mail_history", options={"comment": "お問い合わせ回答メール履歴"})
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\MailHistoryRepository")
     */
    class MailHistory extends AbstractEntity
    {
        /**
         * @return string
         */
        public function __toString()
        {
            return (string) $this->getMailSubject();
        }

        /**
         * @var int
         *
         * @ORM\Column(name="id", type="integer", options={"unsigned":true, "comment":"メール履歴ID"})
         * @ORM\Id
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string
         *
         * @ORM\Column(type="text", nullable=true, options={"comment":"メールアドレス"})
         */
        private $to_email;

        /**
         * @var \DateTime|null
         *
         * @ORM\Column(name="send_date", type="datetimetz", nullable=true, options={"comment":"送信日時"})
         */
        private $send_date;

        /**
         * @var string|null
         *
         * @ORM\Column(name="mail_subject", type="string", length=255, nullable=true, options={"comment":"題名"})
         */
        private $mail_subject;

        /**
         * @var string|null
         *
         * @ORM\Column(name="mail_body", type="text", nullable=true, options={"comment":"本文"})
         */
        private $mail_body;

        /**
         * @var string|null
         *
         * @ORM\Column(name="mail_html_body", type="text", nullable=true, options={"comment":"HTML本文"})
         */
        private $mail_html_body;

        /**
         * @var \Plugin\ContactManagement4\Entity\ContactReply
         *
         * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement4\Entity\ContactReply")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="contact_reply_id", referencedColumnName="id")
         * })
         */
        private $ContactReply;

        /**
         * @var \Plugin\ContactManagement4\Entity\Contact
         *
         * @ORM\ManyToOne(targetEntity="Plugin\ContactManagement4\Entity\Contact")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="contact_id", referencedColumnName="id")
         * })
         */
        private $Contact;

        /**
         * @var \Eccube\Entity\Member
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Member")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="creator_id", referencedColumnName="id", nullable=true)
         * })
         */
        private $Creator;

        /**
         * Get id.
         *
         * @return int
         */
        public function getId()
        {
            return $this->id;
        }

        /**
         * @return string
         */
        public function getToEmail()
        {
            return $this->to_email;
        }

        /**
         * @param string $to_email
         *
         * @return MailHistory
         */
        public function setToEmail($to_email)
        {
            $this->to_email = $to_email;

            return $this;
        }

        /**
         * Set sendDate.
         *
         * @param \DateTime|null $sendDate
         *
         * @return MailHistory
         */
        public function setSendDate($sendDate = null)
        {
            $this->send_date = $sendDate;

            return $this;
        }

        /**
         * Get sendDate.
         *
         * @return \DateTime|null
         */
        public function getSendDate()
        {
            return $this->send_date;
        }

        /**
         * Set mailSubject.
         *
         * @param string|null $mailSubject
         *
         * @return MailHistory
         */
        public function setMailSubject($mailSubject = null)
        {
            $this->mail_subject = $mailSubject;

            return $this;
        }

        /**
         * Get mailSubject.
         *
         * @return string|null
         */
        public function getMailSubject()
        {
            return $this->mail_subject;
        }

        /**
         * Set mailBody.
         *
         * @param string|null $mailBody
         *
         * @return MailHistory
         */
        public function setMailBody($mailBody = null)
        {
            $this->mail_body = $mailBody;

            return $this;
        }

        /**
         * Get mailBody.
         *
         * @return string|null
         */
        public function getMailBody()
        {
            return $this->mail_body;
        }

        /**
         * Set mailHtmlBody.
         *
         * @param string|null $mailHtmlBody
         *
         * @return MailHistory
         */
        public function setMailHtmlBody($mailHtmlBody = null)
        {
            $this->mail_html_body = $mailHtmlBody;

            return $this;
        }

        /**
         * Get mailHtmlBody.
         *
         * @return string|null
         */
        public function getMailHtmlBody()
        {
            return $this->mail_html_body;
        }

        /**
         * @return ContactReply
         */
        public function getContactReply()
        {
            return $this->ContactReply;
        }

        /**
         * @param ContactReply $ContactReply
         *
         * @return MailHistory
         */
        public function setContactReply($ContactReply)
        {
            $this->ContactReply = $ContactReply;

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
         * @return MailHistory
         */
        public function setContact($Contact)
        {
            $this->Contact = $Contact;

            return $this;
        }

        /**
         * Set creator.
         *
         * @return MailHistory
         */
        public function setCreator(\Eccube\Entity\Member $creator = null)
        {
            $this->Creator = $creator;

            return $this;
        }

        /**
         * Get creator.
         *
         * @return \Eccube\Entity\Member|null
         */
        public function getCreator()
        {
            return $this->Creator;
        }
    }
}
