<?php

namespace Plugin\RakutenCard4\Repository;

use Eccube\Entity\Customer;
use Eccube\Repository\AbstractRepository;
use Plugin\RakutenCard4\Entity\Rc4CustomerToken;
use Symfony\Bridge\Doctrine\RegistryInterface;

class Rc4CustomerTokenRepository extends AbstractRepository
{
    private $failure_list = [];
    private $registered_list = [];
    private $get_card_list_flg = false;

    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Rc4CustomerToken::class);
    }

    /**
     * データリストを取得
     *
     * @param Customer|null $Customer
     * @param bool $registered_flg
     * @param bool $refresh_flg
     * @return array
     */
    public function getCardList(?Customer $Customer, $registered_flg=true, $refresh_flg=false)
    {
        if (is_null($Customer)){
            return [];
        }

        $return_func = function ($registered_flg){
            return $registered_flg ? $this->registered_list : $this->failure_list;
        };

        // リフレッシュなしで、データ取得済みなら再実行しない
        if (!$refresh_flg && $this->get_card_list_flg){
            return $return_func($registered_flg);
        }

        /** @var Rc4CustomerToken[] $temp */
        $temp = $this->findBy(['Customer' => $Customer], ['id' => 'ASC']);
        $this->registered_list = [];
        $this->failure_list = [];
        foreach ($temp as $CustomerToken){
            if ($CustomerToken->isRegistered()){
                $this->registered_list[$CustomerToken->getId()] = $CustomerToken;
            }else{
                $this->failure_list[$CustomerToken->getId()] = $CustomerToken;
            }
        }

        $this->get_card_list_flg = true;
        return $return_func($registered_flg);
    }

    /**
     * 登録済みカードのリストを取得
     *
     * @param Customer $Customer
     * @return array
     */
    public function getRegisterCardList($Customer, $refresh_flg=false)
    {
        return $this->getCardList($Customer, true, $refresh_flg);
    }

    /**
     * カード登録の失敗リストを取得
     *
     * @param Customer $Customer
     * @return array
     */
    public function getFailureCardList($Customer, $refresh_flg=false)
    {
        return $this->getCardList($Customer, false, $refresh_flg);
    }
}
