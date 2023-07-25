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

if (!class_exists('\Eccube\Entity\CustomerPoint')) {
    /**
     * CustomerPoint
     *
     * @ORM\Table(name="dtb_customer_point")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\CustomerPointRepository")
     */
    class CustomerPoint extends AbstractEntity
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
         * @ORM\Column(name="status", type="string", length=10)
         */
        private $status;

        /**
         * @var string
         *
         * @ORM\Column(name="point", type="decimal", precision=12, scale=2)
         */
        private $point = 0;

         /**
         * @var string
         *
         * @ORM\Column(name="minus_point", type="decimal", precision=12, scale=2)
         */
        private $minus_point = 0;




        /**
         * @var string|null
         *
         * @ORM\Column(name="get_time", type="string", length=255)
         */
        private $get_time;

        /**
         * @var string|null
         *
         * @ORM\Column(name="exp_time", type="string", length=255)
         */
        private $exp_time;

        


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
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="CustomerPoint")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        private $Customer;

        /**
         * @var \Eccube\Entity\Order
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order", inversedBy="CustomerPoint")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
         * })
         */
        private $Order;

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
         * Set status.
         *
         * @param string|null $status
         *
         * @return CustomerPoint
         */
        public function setStatus($status = null)
        {
            $this->status = $status;

            return $this;
        }

        /**
         * Get status.
         *
         * @return string|null
         */
        public function getStatus()
        {
            return $this->status;
        }

        /**
         * Set point.
         *
         * @param int $point
         *
         * @return CustomerPoint
         */
        public function setPoint($point)
        {
            $this->point = $point;

            return $this;
        }

        /**
         * Get point.
         *
         * @return int
         */
        public function getPoint()
        {
            return $this->point;
        }

         /**
         * Set minusPoint.
         *
         * @param int $minusPoint
         *
         * @return CustomerPoint
         */
        public function setMinusPoint($minusPoint)
        {
            $this->minus_point = $minusPoint;

            return $this;
        }

        /**
         * Get minusPoint.
         *
         * @return int
         */
        public function getMinusPoint()
        {
            return $this->minus_point;
        }






        /**
         * Set getTime.
         *
         * @param string|null $getTime
         *
         * @return CustomerPoint
         */
        public function setGetTime($getTime)
        {
            $this->get_time = $getTime;

            return $this;
        }

        /**
         * Get getTime.
         *
         * @return string|null
         */
        public function getGetTime()
        {
            return $this->get_time;
        }

        /**
         * Set expTime.
         *
         * @param string|null $expTime
         *
         * @return CustomerPoint
         */
        public function setExpTime($expTime)
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
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return CustomerPoint
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
         * @return CustomerPoint
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
         * @return CustomerPoint
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
         * Set order.
         *
         * @param \Eccube\Entity\Order|null $order
         *
         * @return CustomerPoint
         */
        public function setOrder(\Eccube\Entity\Order $order = null)
        {
            $this->Order = $order;

            return $this;
        }

        /**
         * Get order.
         *
         * @return \Eccube\Entity\Order|null
         */
        public function getOrder()
        {
            return $this->Order;
        }






    }
}
