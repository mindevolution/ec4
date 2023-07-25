<?php

/*
 * This file is part of DesignTag4
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\DesignTag4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;

/**
 * Trait TagTrait
 *
 * @EntityExtension("Eccube\Entity\Tag")
 */
trait TagTrait
{
    /**
     * @var boolean
     * @ORM\Column(type="boolean", nullable=false, options={"default":false})
     */
    private $show_product_list_flg = false;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true, options={})
     */
    private $text_color;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true, options={})
     */
    private $background_color;

    /**
     * @var string
     * @ORM\Column(type="text", nullable=true, options={})
     */
    private $border_color;

    /**
     * @return bool
     */
    public function isShowProductListFlg()
    {
        return $this->show_product_list_flg;
    }

    /**
     * @param bool $show_product_list_flg
     *
     * @return TagTrait
     */
    public function setShowProductListFlg($show_product_list_flg)
    {
        $this->show_product_list_flg = $show_product_list_flg;

        return $this;
    }

    /**
     * @return string
     */
    public function getTextColor()
    {
        return $this->text_color;
    }

    /**
     * @param string $text_color
     *
     * @return TagTrait
     */
    public function setTextColor($text_color)
    {
        $this->text_color = $text_color;

        return $this;
    }

    /**
     * @return string
     */
    public function getBackgroundColor()
    {
        return $this->background_color;
    }

    /**
     * @param string $background_color
     *
     * @return TagTrait
     */
    public function setBackgroundColor($background_color)
    {
        $this->background_color = $background_color;

        return $this;
    }

    /**
     * @return string
     */
    public function getBorderColor()
    {
        return $this->border_color;
    }

    /**
     * @param string $border_color
     *
     * @return TagTrait
     */
    public function setBorderColor($border_color)
    {
        $this->border_color = $border_color;

        return $this;
    }
}
