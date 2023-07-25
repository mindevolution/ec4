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

namespace Plugin\ContactManagement4\Entity\Extension;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Annotation\EntityExtension;
use Plugin\ContactManagement4\Entity\Contact;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @var Contact[]|Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\ContactManagement4\Entity\Contact", mappedBy="Customer", cascade={"persist", "remove"})
     * @ORM\OrderBy({
     *     "id"="DESC"
     * })
     */
    private $Contacts;

    /**
     * @return Contact[]|Collection
     */
    public function getContacts()
    {
        if (null === $this->Contacts) {
            $this->Contacts = new ArrayCollection();
        }

        return $this->Contacts;
    }

    public function addContact(Contact $Contact)
    {
        if (null === $this->Contacts) {
            $this->Contacts = new ArrayCollection();
        }

        $this->Contacts[] = $Contact;
    }

    /**
     * @return bool
     */
    public function removeContact(Contact $Contact)
    {
        if (null === $this->Contacts) {
            $this->Contacts = new ArrayCollection();
        }

        return $this->Contacts->removeElement($Contact);
    }
}
