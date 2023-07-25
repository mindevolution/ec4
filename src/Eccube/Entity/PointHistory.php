<?php

namespace Eccube\Entity;

use Doctrine\ORM\Mapping as ORM;

if (!class_exists('\Eccube\Entity\PointHistory')) {
    /**
     * PointHistory
     *
     * @ORM\Table(name="dtb_point_history")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\PointHistoryRepository")
     */
    class PointHistory extends \Eccube\Entity\AbstractEntity
    {
        const TYPE_NULL = 0;
        const TYPE_ADD = 1;
        const TYPE_USE = 2;
        const TYPE_EXPIRED = 3;
        const TYPE_MIGRATION = 4;

        const EVENT_NULL = 0;
        const EVENT_SHOPPING = 1;
        const EVENT_ENTRY = 2;
        const EVENT_ORDER_CANCEL = 3;
        const EVENT_MANUAL = 4;
        const EVENT_EXPIRED = 5;
        const EVENT_MIGRATION = 6;
        const EVENT_REVIEW = 7;

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
         * @ORM\Column(name="point", type="integer")
         */
        private $point = 0;

        /**
         * @var \Eccube\Entity\Customer
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="Orders",cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        private $Customer;

        /**
         * @var \Eccube\Entity\Order
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order", inversedBy="MailHistories",cascade={"persist"})
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
         * })
         */
        private $Order;

        /**
         * @var \DateTime
         *
         * @ORM\Column(name="create_date", type="datetimetz")
         */
        private $create_date;

        /**
         * @var int
         *
         * @ORM\Column(name="record_type", type="integer", nullable=true)
         */
        private $record_type = self::TYPE_NULL;

        /**
         * @var int
         *
         * @ORM\Column(name="record_event", type="integer", nullable=true)
         */
        private $record_event = self::EVENT_NULL;

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
         * Set point.
         *
         * @param int $point
         *
         * @return PointHistory
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
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return PointHistory
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
         * Set recordType.
         *
         * @param int|null $recordType
         *
         * @return PointHistory
         */
        public function setRecordType($recordType = null)
        {
            $this->record_type = $recordType;

            return $this;
        }

        /**
         * Get recordType.
         *
         * @return int|null
         */
        public function getRecordType()
        {
            return $this->record_type;
        }

        /**
         * Set recordEvent.
         *
         * @param int|null $recordEvent
         *
         * @return PointHistory
         */
        public function setRecordEvent($recordEvent = null)
        {
            $this->record_event = $recordEvent;

            return $this;
        }

        /**
         * Get recordEvent.
         *
         * @return int|null
         */
        public function getRecordEvent()
        {
            return $this->record_event;
        }

        /**
         * Set customer.
         *
         * @param \Eccube\Entity\Customer|null $customer
         *
         * @return PointHistory
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
         * @return PointHistory
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