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

namespace Plugin\Maker4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation as Eccube;

/**
 * @Eccube\EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{
    /**
     * @var \Plugin\Maker4\Entity\Maker
     *
     * @ORM\ManyToOne(targetEntity="Plugin\Maker4\Entity\Maker")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="maker_id", referencedColumnName="id")
     * })
     */
    private $Maker;

    /**
     * @var string
     *
     * @ORM\Column(name="maker_url", type="string", length=1024, nullable=true)
     */
    private $maker_url;

    /**
     * @return Maker
     */
    public function getMaker()
    {
        return $this->Maker;
    }

    /**
     * @param Maker|null $Maker
     *
     * @return $this
     */
    public function setMaker(Maker $Maker = null)
    {
        $this->Maker = $Maker;

        return $this;
    }

    /**
     * @return string
     */
    public function getMakerUrl()
    {
        return $this->maker_url;
    }

    /**
     * @param string $maker_url
     */
    public function setMakerUrl($maker_url)
    {
        $this->maker_url = $maker_url;
    }
}
