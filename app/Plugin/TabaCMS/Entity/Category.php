<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Entity;

use Plugin\TabaCMS\Common\Constants;
use Doctrine\ORM\Mapping as ORM;

//@ORM\Table(name="plg_taba_banner_manager_area",uniqueConstraints={@ORM\UniqueConstraint(name="plg_taba_banner_manager_area_data_key_index",columns={"data_key"})})

/**
 *
 * @ORM\Table(name="plg_taba_cms_category",uniqueConstraints={@ORM\UniqueConstraint(name="plg_taba_cms_category_unique_data_key",columns={"type_id","data_key"})})
 * @ORM\Entity(repositoryClass="Plugin\TabaCMS\Repository\CategoryRepository")
 */
class Category extends \Eccube\Entity\AbstractEntity
{

    /**
     *
     * @ORM\Id()
     * @ORM\Column(name="category_id",type="integer",nullable=false,options={"unsigned": false})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $categoryId;

    /**
     *
     * @ORM\Column(name="data_key",type="string",length=255,nullable=false,options={"fixed":false})
     *
     * @var string
     */
    private $dataKey;

    /**
     *
     * @ORM\Column(name="category_name",type="string",length=255,nullable=false,options={"fixed":false})
     *
     * @var string
     */
    private $categoryName;

    /**
     *
     * @ORM\Column(name="description",type="text",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $description;

    /**
     *
     * @ORM\Column(name="tag_attributes",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $tagAttributes;

    /**
     *
     * @ORM\Column(name="memo",type="text",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $memo;

    /**
     *
     * @ORM\Column(name="create_date", type="datetime", nullable=false)
     *
     * @var \DateTime
     */
    private $createDate;

    /**
     *
     * @ORM\Column(name="update_date", type="datetime", nullable=false)
     *
     * @var \DateTime
     */
    private $updateDate;

    /**
     * @var \Plugin\TabaCMS\Entity\Type
     *
     * @ORM\ManyToOne(targetEntity="Type",fetch="EAGER")
     * @ORM\JoinColumn(name="type_id",referencedColumnName="type_id")
     */
    private $type;

    /**
     *
     * @ORM\Column(name="type_id",type="integer",nullable=false,options={"unsigned":false})
     *
     * @var integer
     */
    private $typeId;

    /**
     *
     * @ORM\Column(name="order_no",type="integer",nullable=false,options={"unsigned":false})
     *
     * @var integer
     */
    private $orderNo;

    /**
     *
     * @var integer 投稿件数
     */
    private $postCount;

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        $this->categoryName = '';
        $this->orderNo = 0;
        $this->postCount = 0;
    }

    /**
     *
     * @return string
     */
    public function getTagAttributes()
    {
        return $this->tagAttributes;
    }

    /**
     *
     * @param string $tagAttributes
     */
    public function setTagAttributes($tagAttributes)
    {
        $this->tagAttributes = $tagAttributes;
    }

    /**
     *
     * @return mixed
     */
    public function getPostCount()
    {
        return $this->postCount;
    }

    /**
     *
     * @param mixed $postCount
     */
    public function setPostCount($postCount)
    {
        $this->postCount = $postCount;
    }

    /**
     *
     * @return integer
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     *
     * @param integer $typeId
     */
    public function setTypeId($typeId)
    {
        $this->typeId = $typeId;
    }

    /**
     *
     * @param number $categoryId
     */
    public function setCategoryId($categoryId)
    {
        $this->categoryId = $categoryId;
    }

    /**
     * Get categoryId
     *
     * @return integer
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     * Set dataKey
     *
     * @param string $dataKey
     * @return Category
     */
    public function setDataKey($dataKey)
    {
        $this->dataKey = $dataKey;

        return $this;
    }

    /**
     * Get dataKey
     *
     * @return string
     */
    public function getDataKey()
    {
        return $this->dataKey;
    }

    /**
     * Set categoryName
     *
     * @param string $categoryName
     * @return Category
     */
    public function setCategoryName($categoryName)
    {
        $this->categoryName = $categoryName;

        return $this;
    }

    /**
     * Get categoryName
     *
     * @return string
     */
    public function getCategoryName()
    {
        return $this->categoryName;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Category
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set memo
     *
     * @param string $memo
     * @return Category
     */
    public function setMemo($memo)
    {
        $this->memo = $memo;

        return $this;
    }

    /**
     * Get memo
     *
     * @return string
     */
    public function getMemo()
    {
        return $this->memo;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return Category
     */
    public function setCreateDate($createDate)
    {
        $this->createDate = $createDate;

        return $this;
    }

    /**
     * Get createDate
     *
     * @return \DateTime
     */
    public function getCreateDate()
    {
        return $this->createDate;
    }

    /**
     * Set updateDate
     *
     * @param \DateTime $updateDate
     * @return Category
     */
    public function setUpdateDate($updateDate)
    {
        $this->updateDate = $updateDate;

        return $this;
    }

    /**
     * Get updateDate
     *
     * @return \DateTime
     */
    public function getUpdateDate()
    {
        return $this->updateDate;
    }

    /**
     * Set type
     *
     * @param \Plugin\TabaCMS\Entity\Type $type
     * @return Category
     */
    public function setType(\Plugin\TabaCMS\Entity\Type $type = null)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return \Plugin\TabaCMS\Entity\Type
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set orderNo
     *
     * @param integer $orderNo
     * @return Category
     */
    public function setOrderNo($orderNo)
    {
        $this->orderNo = $orderNo;

        return $this;
    }

    /**
     * Get orderNo
     *
     * @return integer
     */
    public function getOrderNo()
    {
        return $this->orderNo;
    }

    /**
     * ルーティング名を取得します。
     *
     * @return string
     */
    public function getRoutingName()
    {
        return Constants::FRONT_BIND_PREFIX . '_list_' . $this->typeId;
    }

    /**
     * @return string
     */
    public function __toString(){
        return $this->categoryName;
    }
}
