<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Entity;

use Plugin\TabaCMS\Common\Constants;

use Symfony\Component\Routing\RouterInterface;

use Doctrine\ORM\Mapping as ORM;


//@ORM\Table(name="plg_taba_banner_manager_area",uniqueConstraints={@ORM\UniqueConstraint(name="plg_taba_banner_manager_area_data_key_index",columns={"data_key"})})

/**
 * 投稿タイプ
 *
 * @ORM\Table(name="plg_taba_cms_type")
 * @ORM\Entity(repositoryClass="Plugin\TabaCMS\Repository\TypeRepository")
 */
class Type extends AbstractEntity
{

    /**
     * @var string 公開区分/公開
     */
    const PUBLIC_DIV_PUBLIC = 1;

    /**
     * @var string 公開区分/非公開
     */
    const PUBLIC_DIV_PRIVATE = 0;

    /**
     * @var string 公開区分/デフォルト値
     */
    const PUBLIC_DIV_DEFAULT = self::PUBLIC_DIV_PUBLIC;

    /**
     * @var string 公開区分/フォーム生成用配列
     */
    public static $PUBLIC_DIV_ARRAY = array(
        '公開' => self::PUBLIC_DIV_PUBLIC,
        '非公開' => self::PUBLIC_DIV_PRIVATE,
    );

    /**
     *
     * @var integer PageLayout.pageId のデフォルト値
     */
    const DEFAULT_PAGE_ID = 2;

    /**
     *
     * @ORM\Id()
     * @ORM\Column(name="type_id",type="integer",nullable=false,options={"unsigned": false})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @var integer
     */
    private $typeId;

    /**
     *
     * @ORM\Column(name="data_key",type="string",length=255,nullable=false,options={"fixed":false},unique=true)
     *
     * @var string
     */
    private $dataKey;

    /**
     *
     * @ORM\Column(name="public_div",type="integer",nullable=false,options={"unsigned":false})
     *
     * @var integer
     */
    private $publicDiv;

    /**
     *
     * @ORM\Column(name="type_name",type="string",length=255,nullable=false,options={"fixed":false})
     *
     * @var string
     */
    private $typeName;

    /**
     *
     * @ORM\Column(name="edit_div",type="integer",nullable=false,options={"unsigned":false})
     *
     * @var integer
     */
    private $editDiv;

    /**
     *
     * @ORM\Column(name="memo",type="text",nullable=true,options={"fixed":false})
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
     * @var integer
     *
     * @ORM\Column(name="public_id",type="integer",nullable=false,options={"unsigned":false})
     */
    private $pageId;

    /**
     *
     * @var integer
     */
    private $categoryCount;

    /**
     *
     * @var integer
     */
    private $postCount;

    /**
     * コンストラクタ
     *
     * 引数に true を指定するとプロパティにデフォルト値を設定します。
     *
     * @param boolean $default_setting
     */
    public function __construct()
    {
        $this->typeName = '';
        $this->publicDiv = self::PUBLIC_DIV_DEFAULT;
        $this->editDiv = 0;
        $this->categoryCount = 0;
        $this->postCount = 0;
        $this->pageId = self::DEFAULT_PAGE_ID;
    }

    /**
     *
     * @return mixed
     */
    public function getPageId()
    {
        return $this->pageId;
    }

    /**
     *
     * @param mixed $pageId
     */
    public function setPageId($pageId)
    {
        $this->pageId = $pageId;
    }

    /**
     *
     * @return mixed
     */
    public function getCategoryCount()
    {
        return $this->categoryCount;
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
     * @param mixed $categoryCount
     */
    public function setCategoryCount($categoryCount)
    {
        $this->categoryCount = $categoryCount;
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
     * Get typeId
     *
     * @return integer
     */
    public function getTypeId()
    {
        return $this->typeId;
    }

    /**
     * Set dataKey
     *
     * @param string $dataKey
     * @return Type
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
     * Set publicDiv
     *
     * @param integer $publicDiv
     * @return Type
     */
    public function setPublicDiv($publicDiv)
    {
        $this->publicDiv = $publicDiv;

        return $this;
    }

    /**
     * Get publicDiv
     *
     * @return integer
     */
    public function getPublicDiv()
    {
        return $this->publicDiv;
    }

    /**
     * Set typeName
     *
     * @param string $typeName
     * @return Type
     */
    public function setTypeName($typeName)
    {
        $this->typeName = $typeName;

        return $this;
    }

    /**
     * Get typeName
     *
     * @return string
     */
    public function getTypeName()
    {
        return $this->typeName;
    }

    /**
     * Set memo
     *
     * @param string $memo
     * @return Type
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
     * Set editDiv
     *
     * @param integer $editDiv
     * @return Type
     */
    public function setEditDiv($editDiv)
    {
        $this->editDiv = $editDiv;

        return $this;
    }

    /**
     * Get editDiv
     *
     * @return integer
     */
    public function getEditDiv()
    {
        return $this->editDiv;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return Type
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
     * @return Type
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
     * 公開区分名を取得します。
     *
     * @return integer
     */
    public function getPublicDivName()
    {
        if (($index = array_search($this->publicDiv,self::$PUBLIC_DIV_ARRAY))) {
            return $index;
        } else {
            return null;
        }
    }

    /**
     * 投稿リストページのルーティング名を取得します。
     *
     * @return string
     */
    public function getListRoutingName()
    {
        return Constants::FRONT_BIND_PREFIX . '_list_' . $this->typeId;
    }

    /**
     * 投稿リストページのURIを取得します。
     *
     * @return string
     */
    public function getListURI()
    {
        if ($this->router->getRouteCollection()->get($this->getListRoutingName()) != null) {
            return $this->router->generate($this->getListRoutingName());
        } else {
            return null;
        }
    }

    /**
     * 投稿リストページのURLを取得します。
     *
     * @return string
     */
    public function getListURL()
    {
        if ($this->router->getRouteCollection()->get($this->getListRoutingName())) {
            return $this->router->generate($this->getListRoutingName(), [], RouterInterface::ABSOLUTE_URL);
        } else {
            return null;
        }
    }
}
