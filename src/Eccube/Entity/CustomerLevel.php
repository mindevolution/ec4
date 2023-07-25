<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Eccube\Entity\CustomerLevel')) {
    /**
     * CustomerLevel
     *
     * @ORM\Table(name="dtb_customer_level")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\CustomerLevelRepository")
     */
    class CustomerLevel extends AbstractEntity
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
         * @var string|null
         *
         * @ORM\Column(name="level", type="string", length=255)
         */
        private $level;

        /**
         * @var string
         *
         * @ORM\Column(name="money", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
         */
        private $money = 0;

        /**
         * @var string|null
         *
         * @ORM\Column(name="exp_time", type="string", length=255)
         */
        private $exp_time;

        /**
         * @var string|null
         *
         * @ORM\Column(name="last_exp_time", type="string", length=255)
         */
        private $last_exp_time;

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
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="CustomerLevel")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        private $Customer;

        /**
         * @var \Eccube\Entity\CustomerLevelDetail
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\CustomerLevelDetail", inversedBy="CustomerLevel")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_level_detail_id", referencedColumnName="id")
         * })
         */
        private $CustomerLevelDetail;

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
         * Set level.
         *
         * @param string|null $level
         *
         * @return CustomerLevel
         */
        public function setLevel($level = null)
        {
            $this->level = $level;

            return $this;
        }

        /**
         * Get level.
         *
         * @return string|null
         */
        public function getLevel()
        {
            return $this->level;
        }

        /**
         * Set money.
         *
         * @param int $money
         *
         * @return CustomerLevel
         */
        public function setMoney($money)
        {
            $this->money = $money;

            return $this;
        }

        /**
         * Get money.
         *
         * @return int
         */
        public function getMoney()
        {
            return $this->money;
        }


        /**
         * Set expTime.
         *
         * @param string|null $expTime
         *
         * @return CustomerLevel
         */
        public function setExpTime($expTime = null)
        {
            $this->exp_time = $expTime;

            return $this;
        }

        /**
         * Get expTime.
         *
         * @return string|null
         */
        public function getExpTime()
        {
            return $this->exp_time;
        }

        /**
         * Set lastExpTime.
         *
         * @param string|null $lastExpTime
         *
         * @return CustomerLevel
         */
        public function setLastExpTime($lastExpTime = null)
        {
            $this->last_exp_time = $lastExpTime;

            return $this;
        }

        /**
         * Get lastExpTime.
         *
         * @return string|null
         */
        public function getLastExpTime()
        {
            return $this->last_exp_time;
        }


               
        /**
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return CustomerLevel
         */
        public function setCreateDate($createDate)
        {
            $this->create_date = $createDate;

            return $this;
        }

        /**
         * Get createDate.
         *
         * @return \DateTime
         */
        public function getCreateDate()
        {
            return $this->create_date;
        }

        /**
         * Set updateDate.
         *
         * @param \DateTime $updateDate
         *
         * @return CustomerLevel
         */
        public function setUpdateDate($updateDate)
        {
            $this->update_date = $updateDate;

            return $this;
        }

        /**
         * Get updateDate.
         *
         * @return \DateTime
         */
        public function getUpdateDate()
        {
            return $this->update_date;
        }

        /**
         * Set customer.
         *
         * @param \Eccube\Entity\Customer|null $customer
         *
         * @return CustomerLevel
         */
        public function setCustomer(\Eccube\Entity\Customer $customer = null)
        {
            $this->Customer = $customer;

            return $this;
        }

        /**
         * Get customer.
         *
         * @return \Eccube\Entity\Customer|null
         */
        public function getCustomer()
        {
            return $this->Customer;
        }




         /**
         * Set customerLevelDetail.
         *
         * @param \Eccube\Entity\CustomerLevelDetail|null $customerLevelDetail
         *
         * @return CustomerLevel
         */
        public function setCustomerLevelDetail(\Eccube\Entity\CustomerLevelDetail $customerLevelDetail = null)
        {
            $this->CustomerLevelDetail = $customerLevelDetail;

            return $this;
        }

        /**
         * Get customerLevelDetail.
         *
         * @return \Eccube\Entity\CustomerLevelDetail|null
         */
        public function getCustomerLevelDetail()
        {
            return $this->CustomerLevelDetail;
        }






    }
}
