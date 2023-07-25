<?php
/*
 * Copyright (C) SPREAD WORKS Inc. All Rights Reserved.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Plugin\TabaCMS\Entity;

use Plugin\TabaCMS\Common\Constants;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

use Doctrine\ORM\Mapping as ORM;

/**
 *
 * @ORM\Table(name="plg_taba_cms_post")
 * @ORM\Entity(repositoryClass="Plugin\TabaCMS\Repository\PostRepository")
 */
class Post extends AbstractEntity
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
     * @var integer 表示の仕方 / 本文
     */
    const CONTENT_DIV_BODY = 1;

    /**
     * @var integer 表示の仕方 / リンク
     */
    const CONTENT_DIV_LINK = 2;

    /**
     * @var integer 表示の仕方 / タイトル
     */
    const CONTENT_DIV_TITLE = 3;

    /**
     * @var integer 表示の仕方 / デフォルト値
     */
    const CONTENT_DIV_DEFAULT = self::CONTENT_DIV_BODY;

    /**
     * @var integer 表示の仕方 / フォーム生成用配列
     */
    public static $CONTENT_DIV_ARRAY = array(
        '本文を表示する' => self::CONTENT_DIV_BODY,
        ' リンク先を指定する' => self::CONTENT_DIV_LINK,
        ' タイトルのみ表示する' => self::CONTENT_DIV_TITLE
    );

    /**
     * @var integer
     *
     * @ORM\Id()
     * @ORM\Column(name="post_id",type="integer",nullable=false,options={"unsigned": false})
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $postId;

    /**
     * @var string
     *
     * @ORM\Column(name="data_key",type="string",length=255,nullable=false,options={"fixed":false},unique=true)
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
     * @ORM\Column(name="public_date", type="datetime", nullable=false)
     *
     * @var \DateTime
     */
    private $publicDate;

    /**
     *
     * @ORM\Column(name="content_div",type="integer",nullable=false,options={"unsigned":false})
     *
     * @var integer
     */
    private $contentDiv;

    /**
     *
     * @ORM\Column(name="title",type="string",length=255,nullable=false,options={"fixed":false})
     *
     * @var string
     */
    private $title;

    /**
     *
     * @ORM\Column(name="body",type="text",length=65535,nullable=true,options={"fixed": false})
     *
     * @var string
     */
    private $body;

    /**
     *
     * @ORM\Column(name="link_url",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $linkUrl;

    /**
     *
     * @ORM\Column(name="link_target",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $linkTarget;

    /**
     *
     * @ORM\Column(name="thumbnail",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $thumbnail;

    /**
     *
     * @ORM\Column(name="creator_id",type="integer",nullable=false,options={"unsigned":true})
     *
     * @var integer
     */
    private $creatorId;

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
     *
     * @ORM\Column(name="meta_author",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $metaAuthor;

    /**
     *
     * @ORM\Column(name="meta_description",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $metaDescription;

    /**
     *
     * @ORM\Column(name="meta_keyword",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $metaKeyword;

    /**
     *
     * @ORM\Column(name="meta_robots",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $metaRobots;

    /**
     * @ORM\Column(name="meta_tags",type="text",length=65535,nullable=true,options={"fixed": false})
     *
     * @var string
     */
    private $metaTags;

    /**
     *
     * @ORM\Column(name="type_id",type="integer",nullable=false,options={"unsigned":false})
     *
     * @var integer
     */
    private $typeId;

    /**
     *
     * @ORM\Column(name="category_id",type="integer",nullable=true,options={"unsigned":false})
     *
     * @var integer
     */
    private $categoryId;

    /**
     *
     * @ORM\Column(name="overwrite_route",type="string",length=255,nullable=true,options={"fixed":false})
     *
     * @var string
     */
    private $overwriteRoute;

    /**
     *
     * @ORM\Column(name="script",type="text",length=65535,nullable=true,options={"fixed": false})
     *
     * @var string
     */
    private $script;

    /**
     * @ORM\ManyToOne(targetEntity="Category",fetch="EAGER")
     * @ORM\JoinColumn(name="category_id",referencedColumnName="category_id")
     */
    private $category;

    /**
     * @var \Plugin\TabaCMS\Entity\Type
     *
     * @ORM\ManyToOne(targetEntity="Type",fetch="EAGER")
     * @ORM\JoinColumn(name="type_id",referencedColumnName="type_id")
     */
    private $type;

    /**
     * @var \Eccube\Entity\Member
     *
     * @ORM\ManyToOne(targetEntity="\Eccube\Entity\Member",fetch="EAGER")
     * @ORM\JoinColumn(name="creator_id",referencedColumnName="id")
     */
    private $member;

    /**
     * コンストラクタ
     *
     * 引数に true を指定するとプロパティにデフォルト値を設定します。
     *
     * @param boolean $default_setting
     */
    public function __construct($default_setting = false)
    {
        $this->title = '';
        $this->publicDiv = self::PUBLIC_DIV_DEFAULT;
        $this->contentDiv = self::CONTENT_DIV_DEFAULT;
    }

    /**
     *
     * @return string
     */
    public function getScript()
    {
        return $this->script;
    }

    /**
     *
     * @param string $script
     */
    public function setScript($script)
    {
        $this->script = $script;
    }

    /**
     *
     * @return number
     */
    public function getCategoryId()
    {
        return $this->categoryId;
    }

    /**
     *
     * @return string
     */
    public function getOverwriteRoute()
    {
        return $this->overwriteRoute;
    }

    /**
     *
     * @param string $overwriteRoute
     */
    public function setOverwriteRoute($overwriteRoute)
    {
        $this->overwriteRoute = $overwriteRoute;
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
     *
     * @return string
     */
    public function getMetaAuthor()
    {
        return $this->metaAuthor;
    }

    /**
     *
     * @return string
     */
    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    /**
     *
     * @return string
     */
    public function getMetaKeyword()
    {
        return $this->metaKeyword;
    }

    /**
     *
     * @return string
     */
    public function getMetaRobots()
    {
        return $this->metaRobots;
    }

    /**
     *
     * @return string
     */
    public function getMetaTags()
    {
        return $this->metaTags;
    }

    /**
     *
     * @param string $metaAuthor
     */
    public function setMetaAuthor($metaAuthor)
    {
        $this->metaAuthor = $metaAuthor;
    }

    /**
     *
     * @param string $metaDescription
     */
    public function setMetaDescription($metaDescription)
    {
        $this->metaDescription = $metaDescription;
    }

    /**
     *
     * @param string $metaKeyword
     */
    public function setMetaKeyword($metaKeyword)
    {
        $this->metaKeyword = $metaKeyword;
    }

    /**
     *
     * @param string $metaRobots
     */
    public function setMetaRobots($metaRobots)
    {
        $this->metaRobots = $metaRobots;
    }

    /**
     *
     * @param string $metaTags
     */
    public function setMetaTags($metaTags)
    {
        $this->metaTags = $metaTags;
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
     * Get postId
     *
     * @return integer
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * Set dataKey
     *
     * @param string $dataKey
     * @return Post
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
     * @return Post
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
     * Set publicDate
     *
     * @param \DateTime $publicDate
     * @return Post
     */
    public function setPublicDate($publicDate)
    {
        $this->publicDate = $publicDate;

        return $this;
    }

    /**
     * Get publicDate
     *
     * @return \DateTime
     */
    public function getPublicDate()
    {
        return $this->publicDate;
    }

    /**
     * Set contentDiv
     *
     * @param integer $contentDiv
     * @return Post
     */
    public function setContentDiv($contentDiv)
    {
        $this->contentDiv = $contentDiv;

        return $this;
    }

    /**
     * Get contentDiv
     *
     * @return integer
     */
    public function getContentDiv()
    {
        return $this->contentDiv;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Post
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return Post
     */
    public function setBody($body)
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Set linkUrl
     *
     * @param string $linkUrl
     * @return Post
     */
    public function setLinkUrl($linkUrl)
    {
        $this->linkUrl = $linkUrl;

        return $this;
    }

    /**
     * Get linkUrl
     *
     * @return string
     */
    public function getLinkUrl()
    {
        return $this->linkUrl;
    }

    /**
     * Set linkTarget
     *
     * @param string $linkTarget
     * @return Post
     */
    public function setLinkTarget($linkTarget)
    {
        $this->linkTarget = $linkTarget;

        return $this;
    }

    /**
     * Get linkTarget
     *
     * @return string
     */
    public function getLinkTarget()
    {
        return $this->linkTarget;
    }

    /**
     * Set thumbnail
     *
     * @param string $thumbnail
     * @return Post
     */
    public function setThumbnail($thumbnail)
    {
        $this->thumbnail = $thumbnail;

        return $this;
    }

    /**
     * Get thumbnail
     *
     * @return string
     */
    public function getThumbnail()
    {
        return $this->thumbnail;
    }

    /**
     * Set memo
     *
     * @param string $memo
     * @return Post
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
     * Set creatorId
     *
     * @param integer $creatorId
     * @return Post
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;

        return $this;
    }

    /**
     * Get creatorId
     *
     * @return integer
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * Set createDate
     *
     * @param \DateTime $createDate
     * @return Post
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
     * @return Post
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
     * Set category
     *
     * @param \Plugin\TabaCMS\Entity\Category $category
     * @return Post
     */
    public function setCategory(\Plugin\TabaCMS\Entity\Category $category = null)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return \Plugin\TabaCMS\Entity\Category
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set type
     *
     * @param \Plugin\TabaCMS\Entity\Type $type
     * @return Post
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
     *
     * @return \Eccube\Entity\Member
     */
    public function getMember()
    {
        return $this->member;
    }

    /**
     *
     * @param \Eccube\Entity\Member $member
     */
    public function setMember($member)
    {
        $this->member = $member;
    }

    /**
     * 公開可否を返します。
     *
     * @return boolean
     */
    public function isPublic()
    {
        if ($this->getPublicDiv() == self::PUBLIC_DIV_PUBLIC && $this->getPublicDate()->getTimestamp() <= time()) {
            return true;
        }
        return false;
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
     * ルーティング名を取得します。
     *
     * @return string
     */
    public function getRoutingName()
    {
        return Constants::FRONT_BIND_PREFIX . '_post_' . $this->typeId;
    }

    /**
     * URLを取得します。
     *
     * @return string
     */
    public function getURL()
    {
        if ($this->router->getRouteCollection()->get($this->getRoutingName())) {
            return $this->router->generate($this->getRoutingName(), ['data_key' => $this->getDataKey()], UrlGeneratorInterface::ABSOLUTE_URL);
        } else {
            return null;
        }
    }

    /**
     * URIを取得します。
     *
     * @return string
     */
    public function getURI()
    {
        if ($this->router->getRouteCollection()->get($this->getRoutingName())) {
            return $this->router->generate($this->getRoutingName(),['data_key' => $this->getDataKey()]);
        } else {
            return null;
        }
    }
}
