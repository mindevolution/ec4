<?php

/*
 * This file is part of ContactManagement4
 *
 * Copyright(c) U-Mebius Inc. All Rights Reserved.
 *
 * https://umebius.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ContactManagement4\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * ContactStatus
 *
 * @ORM\Table(name="plg_contact_management4_contact_status")
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="discriminator_type", type="string", length=255)
 * @ORM\HasLifecycleCallbacks()
 * @ORM\Entity(repositoryClass="Plugin\ContactManagement4\Repository\ContactStatusRepository")
 * @ORM\Cache(usage="NONSTRICT_READ_WRITE")
 */
class ContactStatus extends \Eccube\Entity\Master\AbstractMasterEntity
{
}
