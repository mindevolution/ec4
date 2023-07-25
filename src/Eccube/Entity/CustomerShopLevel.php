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

if (!class_exists('\Eccube\Entity\CustomerShopLevel')) {
    /**
     * CustomerShopLevel
     *
     * @ORM\Table(name="dtb_customer_shop_level")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\CustomerShopLevelRepository")
     */
    class CustomerShopLevel extends AbstractEntity
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
         * @ORM\Column(name="min", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
         */
        private $min = 0;

        /**
         * @var string
         *
         * @ORM\Column(name="max", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
         */
        private $max = 0;

        /**
         * @var int
         *
         * @ORM\Column(name="discount", type="integer", length=100)
         */
        private $discount;

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
         * @return CustomerShopLevel
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
         * Set min.
         *
         * @param int $min
         *
         * @return CustomerShopLevel
         */
        public function setMin($min)
        {
            $this->min = $min;

            return $this;
        }

        /**
         * Get min.
         *
         * @return int
         */
        public function getMin()
        {
            return $this->min;
        }

        /**
         * Set max.
         *
         * @param int $max
         *
         * @return CustomerShopLevel
         */
        public function setMax($max)
        {
            $this->max = $max;

            return $this;
        }

        /**
         * Get max.
         *
         * @return int
         */
        public function getMax()
        {
            return $this->max;
        }


        /**
         * Set discount.
         *
         * @param int $discount
         *
         * @return CustomerShopLevel
         */
        public function setDiscount($discount)
        {
            $this->discount = $discount;

            return $this;
        }

        /**
         * Get discount.
         *
         * @return int
         */
        public function getDiscount()
        {
            return $this->discount;
        }



               
        /**
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return CustomerShopLevel
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
         * @return CustomerShopLevel
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

        
    }
}
