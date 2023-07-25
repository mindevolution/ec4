<?php

namespace Plugin\SoftbankPayment4\Repository;

use Eccube\Repository\AbstractRepository;
use Plugin\SoftbankPayment4\Entity\PayMethod;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Symfony\Bridge\Doctrine\RegistryInterface;

class PayMethodRepository extends AbstractRepository
{
    /**
     * PayMethodRepository constructor.
     *
     * @param RegistryInterface $registry
     */
   public function __construct(RegistryInterface $registry)
   {
       parent::__construct($registry, PayMethod::class);
   }

   /**
    * 保存済みで有効な決済方法コードを取得する.
    *
    * @return array
    */
    public function getEnableCodes(): array
    {
        // HACK: クエリビルダーで一発で取りたい.
        $PayMethods = $this->findBy(['enable' => true]);

        $arrRet = [];
        foreach ($PayMethods as $PayMethod) {
            $arrRet[] = $PayMethod->getCode();
        }

        return $arrRet;
    }

    public function getStoredList(bool $sort = true): array
    {
        $StoredPayMethods = $this->findAll();

        $storedList = [];

        foreach ($StoredPayMethods as $PayMethod) {
            $storedList[PayMethodType::$name[$PayMethod->getCode()]] = $PayMethod->getCode();
        }

        if ($sort === true) {
            asort($storedList);
        }

        return $storedList;
    }
}
