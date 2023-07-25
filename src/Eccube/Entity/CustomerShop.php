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

if (!class_exists('\Eccube\Entity\CustomerShop')) {
    /**
     * CustomerShop
     *
     * @ORM\Table(name="dtb_customer_shop")
     * @ORM\InheritanceType("SINGLE_TABLE")
     * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
     * @ORM\HasLifecycleCallbacks()
     * @ORM\Entity(repositoryClass="Eccube\Repository\CustomerShopRepository")
     */
    class CustomerShop extends AbstractEntity
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
         * @ORM\Column(name="num", type="string", length=255)
         */
        private $num;
        
        /**
         * @var \Eccube\Entity\Customer
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\Customer", inversedBy="CustomerShop")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_id", referencedColumnName="id")
         * })
         */
        private $Customer;

        /**
         * @var \Eccube\Entity\CustomerShopLevel
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\CustomerShopLevel", inversedBy="CustomerShop")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="customer_shop_level_id", referencedColumnName="id")
         * })
         */
        private $CustomerShopLevel;

        /**
         * @var \Eccube\Entity\CustomerShopLevel
         *
         * @ORM\ManyToOne(targetEntity="Eccube\Entity\CustomerShopLevel", inversedBy="CustomerShop")
         * @ORM\JoinColumns({
         *   @ORM\JoinColumn(name="last_customer_shop_level_id", referencedColumnName="id")
         * })
         */
        private $LastCustomerShopLevel;

        /**
         * @var string|null
         *
         * @ORM\Column(name="status", type="string", length=255)
         */
        private $status;

        /**
         * @var string|null
         *
         * @ORM\Column(name="shop_name", type="string", length=255)
         */
        private $shop_name;
        

        /**
         * @var string|null
         *
         * @ORM\Column(name="manager1", type="string", length=255)
         */
        private $manager1;

        /**
         * @var string|null
         *
         * @ORM\Column(name="address", type="string", length=255)
         */
        private $address;

        /**
         * @var string|null
         *
         * @ORM\Column(name="tel", type="string", length=255)
         */
        private $tel;

        /**
         * @var string|null
         *
         * @ORM\Column(name="email", type="string", length=255)
         */
        private $email;

        /**
         * @var string|null
         *
         * @ORM\Column(name="page", type="string", length=255)
         */
        private $page;

        /**
         * @var string|null
         *
         * @ORM\Column(name="manager2", type="string", length=255)
         */
        private $manager2;

        /**
         * @var string|null
         *
         * @ORM\Column(name="job", type="string", length=255)
         */
        private $job;

        /**
         * @var string|null
         *
         * @ORM\Column(name="code", type="string", length=255)
         */
        private $code;

        // /**
        //  * @var \DateTime
        //  *
        //  * @ORM\Column(name="register_date", type="datetimetz")
        //  */
        // private $register_date;

        // /**
        //  * @var \DateTime
        //  *
        //  * @ORM\Column(name="effective_date", type="datetimetz")
        //  */
        // private $effective_date;
        
        /**
         * @var string|null
         *
         * @ORM\Column(name="upload_file", type="string", length=255)
         */
        private $upload_file;

        /**
         * @var string|null
         *
         * @ORM\Column(name="upload_file2", type="string", length=255)
         */
        private $upload_file2;

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
         * Set num.
         *
         * @param string|null $num
         *
         * @return CustomerShop
         */
        public function setNum($num = null)
        {
            $this->num = $num;

            return $this;
        }

        /**
         * Get num.
         *
         * @return string|null
         */
        public function getNum()
        {
            return $this->num;
        }



        /**
         * Set status.
         *
         * @param string|null $status
         *
         * @return CustomerShop
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
         * Set shopName.
         *
         * @param string|null $shopName
         *
         * @return CustomerShop
         */
        public function setShopName($shopName = null)
        {
            $this->shop_name = $shopName;

            return $this;
        }

        /**
         * Get shopName.
         *
         * @return string|null
         */
        public function getShopName()
        {
            return $this->shop_name;
        }

        /**
         * Set manager1.
         *
         * @param string|null $manager1
         *
         * @return CustomerShop
         */
        public function setManager1($manager1 = null)
        {
            $this->manager1 = $manager1;

            return $this;
        }

        /**
         * Get manager1.
         *
         * @return string|null
         */
        public function getManager1()
        {
            return $this->manager1;
        }

        /**
         * Set address.
         *
         * @param string|null $address
         *
         * @return CustomerShop
         */
        public function setAddress($address = null)
        {
            $this->address = $address;

            return $this;
        }

        /**
         * Get address.
         *
         * @return string|null
         */
        public function getAddress()
        {
            return $this->address;
        }

        /**
         * Set tel.
         *
         * @param string|null $tel
         *
         * @return CustomerShop
         */
        public function setTel($tel = null)
        {
            $this->tel = $tel;

            return $this;
        }

        /**
         * Get tel.
         *
         * @return string|null
         */
        public function getTel()
        {
            return $this->tel;
        }

        /**
         * Set email.
         *
         * @param string|null $email
         *
         * @return CustomerShop
         */
        public function setEmail($email = null)
        {
            $this->email = $email;

            return $this;
        }

        /**
         * Get email.
         *
         * @return string|null
         */
        public function getEmail()
        {
            return $this->email;
        }

        /**
         * Set page.
         *
         * @param string|null $page
         *
         * @return CustomerShop
         */
        public function setPage($page = null)
        {
            $this->page = $page;

            return $this;
        }

        /**
         * Get page.
         *
         * @return string|null
         */
        public function getPage()
        {
            return $this->page;
        }

        /**
         * Set manager2.
         *
         * @param string|null $manager2
         *
         * @return CustomerShop
         */
        public function setManager2($manager2 = null)
        {
            $this->manager2 = $manager2;

            return $this;
        }

        /**
         * Get manager2.
         *
         * @return string|null
         */
        public function getManager2()
        {
            return $this->manager2;
        }

        /**
         * Set job.
         *
         * @param string|null $job
         *
         * @return CustomerShop
         */
        public function setJob($job = null)
        {
            $this->job = $job;

            return $this;
        }

        /**
         * Get job.
         *
         * @return string|null
         */
        public function getJob()
        {
            return $this->job;
        }

        /**
         * Set code.
         *
         * @param string|null $code
         *
         * @return CustomerShop
         */
        public function setCode($code = null)
        {
            $this->code = $code;

            return $this;
        }

        /**
         * Get code.
         *
         * @return string|null
         */
        public function getCode()
        {
            return $this->code;
        }

        // /**
        //  * Set registerDate.
        //  *
        //  * @param \DateTime $registerDate
        //  *
        //  * @return CustomerShop
        //  */
        // public function setRegisterDate($registerDate)
        // {
        //     $this->register_date = $registerDate;

        //     return $this;
        // }

        // /**
        //  * Get registerDate.
        //  *
        //  * @return \DateTime
        //  */
        // public function getRegisterDate()
        // {
        //     return $this->register_date;
        // }

        // /**
        //  * Set effectiveDate.
        //  *
        //  * @param \DateTime $effectiveDate
        //  *
        //  * @return CustomerShop
        //  */
        // public function setEffectiveDate($effectiveDate)
        // {
        //     $this->effective_date = $effectiveDate;

        //     return $this;
        // }

        // /**
        //  * Get effectiveDate.
        //  *
        //  * @return \DateTime
        //  */
        // public function getEffectiveDate()
        // {
        //     return $this->effective_date;
        // }

        /**
         * Set uploadFile.
         *
         * @param string|null $uploadFile
         *
         * @return CustomerShop
         */
        public function setUploadFile($uploadFile = null)
        {
            $this->upload_file = $uploadFile;

            return $this;
        }

        /**
         * Get uploadFile.
         *
         * @return string|null
         */
        public function getUploadFile()
        {
            return $this->upload_file;
        }

        /**
         * Set uploadFile2.
         *
         * @param string|null $uploadFile2
         *
         * @return CustomerShop
         */
        public function setUploadFile2($uploadFile2 = null)
        {
            $this->upload_file2 = $uploadFile2;

            return $this;
        }

        /**
         * Get uploadFile2.
         *
         * @return string|null
         */
        public function getUploadFile2()
        {
            return $this->upload_file2;
        }

        /**
         * Set money.
         *
         * @param int $money
         *
         * @return CustomerShop
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
         * @return CustomerShop
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
         * Set createDate.
         *
         * @param \DateTime $createDate
         *
         * @return CustomerShop
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
         * @return CustomerShop
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
         * @return CustomerShop
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
         * Set CustomerShopLevel.
         *
         * @param \Eccube\Entity\Customer|null $customerShopLevel
         *
         * @return CustomerShop
         */
        public function setCustomerShopLevel(\Eccube\Entity\CustomerShopLevel $customerShopLevel = null)
        {
            $this->CustomerShopLevel = $customerShopLevel;

            return $this;
        }

        /**
         * Get CustomerShopLevel.
         *
         * @return \Eccube\Entity\CustomerShopLevel|null
         */
        public function getCustomerShopLevel()
        {
            return $this->CustomerShopLevel;
        }





        /**
         * Set LastCustomerShopLevel.
         *
         * @param \Eccube\Entity\Customer|null $lastCustomerShopLevel
         *
         * @return CustomerShop
         */
        public function setLastCustomerShopLevel(\Eccube\Entity\CustomerShopLevel $lastCustomerShopLevel = null)
        {
            $this->LastCustomerShopLevel = $lastCustomerShopLevel;

            return $this;
        }

        /**
         * Get LastCustomerShopLevel.
         *
         * @return \Eccube\Entity\LastCustomerShopLevel|null
         */
        public function getLastCustomerShopLevel()
        {
            return $this->LastCustomerShopLevel;
        }




    }
}
