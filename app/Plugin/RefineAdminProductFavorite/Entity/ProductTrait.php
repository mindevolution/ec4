<?php
/*
 * This file is part of Refine
 *
 * Copyright(c) 2022 Refine Co.,Ltd. All Rights Reserved.
 *
 * https://www.re-fine.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\RefineAdminProductFavorite\Entity;

use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Eccube\Entity\Product")
 */
trait ProductTrait
{


    public function getCustomerFavoriteProductCount()
    {
        return \count($this->getCustomerFavoriteProducts());
    }
}
