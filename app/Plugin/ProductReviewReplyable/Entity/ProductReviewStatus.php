<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ProductReviewReplyable\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\AbstractMasterEntity;

/**
 * ProductReviewStatus
 *
 * @ORM\Table(name="plg_product_review_status")
 * @ORM\Entity(repositoryClass="Plugin\ProductReviewReplyable\Repository\ProductReviewStatusRepository")
 */
class ProductReviewStatus extends AbstractMasterEntity
{
    /**
     * 表示
     */
    const SHOW = 1;

    /**
     * 非表示
     */
    const HIDE = 2;
}
