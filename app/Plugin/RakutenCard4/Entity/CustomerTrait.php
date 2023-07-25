<?php

namespace Plugin\RakutenCard4\Entity;

use Eccube\Annotation\EntityExtension;
use Doctrine\ORM\Mapping as ORM;

/**
 * @EntityExtension("Eccube\Entity\Customer")
 */
trait CustomerTrait
{
    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\OneToMany(targetEntity="Plugin\RakutenCard4\Entity\Rc4CustomerToken", mappedBy="Customer", cascade={"remove"})
     * @ORM\OrderBy({
     *     "id"="ASC"
     * })
     */
    private $Rc4CustomerTokens;

    /**
     * トークン用配列の初期設定
     */
    private function iniToken()
    {
        if (is_null($this->Rc4CustomerTokens)){
            $this->Rc4CustomerTokens = new \Doctrine\Common\Collections\ArrayCollection();
        }
    }

    /**
     * Add Rc4CustomerToken.
     *
     * @param Rc4CustomerToken $customerToken
     *
     * @return self
     */
    public function addRc4CustomerToken(Rc4CustomerToken $customerToken)
    {
        $this->Rc4CustomerTokens[] = $customerToken;

        return $this;
    }

    /**
     * Remove Rc4CustomerToken.
     *
     * @param Rc4CustomerToken $customerToken
     *
     * @return boolean TRUE if this collection contained the specified element, FALSE otherwise.
     */
    public function removeRc4CustomerToken(Rc4CustomerToken $customerToken)
    {
        return $this->Rc4CustomerTokens->removeElement($customerToken);
    }

    /**
     * Get customerFavoriteProducts.
     *
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getRc4CustomerTokens()
    {
        return $this->Rc4CustomerTokens;
    }
}
