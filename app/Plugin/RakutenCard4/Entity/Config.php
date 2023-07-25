<?php

namespace Plugin\RakutenCard4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Common\Constant;
use Plugin\RakutenCard4\Common\ConstantCard;

/**
 * Config
 *
 * @ORM\Table(name="plg_rakuten_card4_config")
 * @ORM\Entity(repositoryClass="Plugin\RakutenCard4\Repository\ConfigRepository")
 */
class Config extends AbstractRc4Entity
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var int
     *
     * @ORM\Column(name="connection_mode", type="smallint", options={"unsigned":true,"default":2})
     */
    private $connection_mode;

    /**
     * @var string
     *
     * @ORM\Column(name="card_service_id", type="string", length=255, nullable=true)
     */
    private $card_service_id;

    /**
     * @var string
     *
     * @ORM\Column(name="card_auth_key", type="string", length=255, nullable=true)
     */
    private $card_auth_key;

    /**
     * @var string
     *
     * @ORM\Column(name="cvs_sub_service_id_seven", type="string", length=255, nullable=true)
     */
    private $cvs_sub_service_id_seven;

    /**
     * @var string
     *
     * @ORM\Column(name="cvs_sub_service_id_lawson", type="string", length=255, nullable=true)
     */
    private $cvs_sub_service_id_lawson;

    /**
     * @var string
     *
     * @ORM\Column(name="cvs_sub_service_id_yamazaki", type="string", length=255, nullable=true)
     */
    private $cvs_sub_service_id_yamazaki;

    /**
     * @var string
     *
     * @ORM\Column(name="cvs_kind", type="string", length=255, nullable=true)
     */
    private $cvs_kind;

    /**
     * @var string
     *
     * @ORM\Column(name="cvs_limit_day", type="integer", nullable=true)
     */
    private $cvs_limit_day;

    /**
     * @var string
     *
     * @ORM\Column(name="card_merchant_id_visa", type="string", length=255, nullable=true)
     */
    private $card_merchant_id_visa;

    /**
     * @var string
     *
     * @ORM\Column(name="card_merchant_id_master_card", type="string", length=255, nullable=true)
     */
    private $card_merchant_id_master_card;

    /**
     * @var int
     *
     * @ORM\Column(name="card_challenge_type", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_challenge_type;

    /**
     * @var string
     *
     * @ORM\Column(name="card_installments", type="text", nullable=true)
     */
    private $card_installments;

    /**
     * @var int
     *
     * @ORM\Column(name="card_3d_secure_use", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_3d_secure_use;

    /**
     * @var string
     *
     * @ORM\Column(name="card_3d_store_name", type="string", length=255, nullable=true)
     */
    private $card_3d_store_name;

    /**
     * @var int
     *
     * @ORM\Column(name="card_buy_api", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_buy_api;

    /**
     * @var int
     *
     * @ORM\Column(name="card_cvv_use", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_cvv_use;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return int
     */
    public function getConnectionMode(): int
    {
        return $this->connection_mode;
    }

    /**
     * @param int $connection_mode
     * @return self
     */
    public function setConnectionMode(?int $connection_mode)
    {
        $this->connection_mode = $connection_mode;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardServiceId()
    {
        return $this->card_service_id;
    }

    /**
     * @param string|null $card_service_id
     * @return self
     */
    public function setCardServiceId(?string $card_service_id)
    {
        $this->card_service_id = $card_service_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardAuthKey()
    {
        return $this->card_auth_key;
    }

    /**
     * @param string|null $card_auth_key
     * @return self
     */
    public function setCardAuthKey(?string $card_auth_key)
    {
        $this->card_auth_key = $card_auth_key;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCvsServiceId()
    {
        return $this->getCardServiceId();
    }

    /**
     * @return string|null
     */
    public function getCvsAuthKey()
    {
        return $this->getCardAuthKey();
    }

    /**
     * @return string
     */
    public function getCvsSubServiceIdSeven()
    {
        return $this->cvs_sub_service_id_seven;
    }

    /**
     * @param string $cvs_sub_service_id_seven
     * @return self
     */
    public function setCvsSubServiceIdSeven(?string $cvs_sub_service_id_seven)
    {
        $this->cvs_sub_service_id_seven = $cvs_sub_service_id_seven;
        return $this;
    }

    /**
     * @return string
     */
    public function getCvsSubServiceIdLawson()
    {
        return $this->cvs_sub_service_id_lawson;
    }

    /**
     * @param string $cvs_sub_service_id_lawson
     * @return self
     */
    public function setCvsSubServiceIdLawson(?string $cvs_sub_service_id_lawson)
    {
        $this->cvs_sub_service_id_lawson = $cvs_sub_service_id_lawson;
        return $this;
    }

    /**
     * @return string
     */
    public function getCvsSubServiceIdYamazaki()
    {
        return $this->cvs_sub_service_id_yamazaki;
    }

    /**
     * @param string $cvs_sub_service_id_yamazaki
     * @return self
     */
    public function setCvsSubServiceIdYamazaki(?string $cvs_sub_service_id_yamazaki)
    {
        $this->cvs_sub_service_id_yamazaki = $cvs_sub_service_id_yamazaki;
        return $this;
    }

    /**
     * @return string
     */
    public function getCvsKind()
    {
        return $this->cvs_kind;
    }

    /**
     * @param string $cvs_kind
     * @return self
     */
    public function setCvsKind(?string $cvs_kind)
    {
        $this->cvs_kind = $cvs_kind;
        return $this;
    }

    /**
     * 利用コンビニ種類をJsonデコードして取得する
     *
     * @param array $data データ配列
     * @return mixed
     */
    public function getDecodeCvsKindcode()
    {
        return $this->getDecode($this->getCvsKind());
    }

    /**
     * 利用コンビニ種類をJsonエンコードしてセットする
     *
     * @param array $data データ配列
     * @return self
     */
    public function setEncodeCvsKind($arrayData)
    {
        $this->setCvsKind($this->getEncode($arrayData));
        return $this;
    }

    /**
     * @return string
     */
    public function getCvsLimitDay()
    {
        return $this->cvs_limit_day;
    }

    /**
     * @param string $cvs_limit_day
     * @return self
     */
    public function setCvsLimitDay(?int $cvs_limit_day)
    {
        $this->cvs_limit_day = $cvs_limit_day;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardMerchantIdVisa()
    {
        return $this->card_merchant_id_visa;
    }

    /**
     * @param string $card_merchant_id_visa
     * @return self
     */
    public function setCardMerchantIdVisa(?string $card_merchant_id_visa)
    {
        $this->card_merchant_id_visa = $card_merchant_id_visa;
        return $this;
    }

    /**
     * @return string
     */
    public function getCardMerchantIdMasterCard()
    {
        return $this->card_merchant_id_master_card;
    }

    /**
     * @param string $card_merchant_id_master_card
     * @return self
     */
    public function setCardMerchantIdMasterCard(?string $card_merchant_id_master_card)
    {
        $this->card_merchant_id_master_card = $card_merchant_id_master_card;
        return $this;
    }

    /**
     * @return int
     */
    public function getCardChallengeType()
    {
        return $this->card_challenge_type;
    }

    /**
     * @param int $card_challenge_type
     * @return self
     */
    public function setCardChallengeType(?int $card_challenge_type)
    {
        $this->card_challenge_type = $card_challenge_type;
        return $this;
    }

    /**
     * 追加認証タイプの出力
     *
     * @return string
     */
    public function getCardChallengeIndicator()
    {
        if (!isset(ConstantCard::CHALLENGE_TYPE_COL[$this->card_challenge_type])){
            return ConstantCard::CHALLENGE_TYPE_COL[ConstantCard::CHALLENGE_DEFAULT];
        }

        return ConstantCard::CHALLENGE_TYPE_COL[$this->card_challenge_type];
    }

    /**
     * @return string
     */
    public function getCardInstallments()
    {
        return $this->card_installments;
    }

    /**
     * @param string $card_installments
     * @return self
     */
    public function setCardInstallments(?string $card_installments)
    {
        $this->card_installments = $card_installments;
        return $this;
    }

    /**
     * @return int
     */
    public function getCard3dSecureUse()
    {
        return $this->card_3d_secure_use;
    }

    /**
     * @param int $card_3d_secure_use
     * @return self
     */
    public function setCard3dSecureUse(?int $card_3d_secure_use)
    {
        $this->card_3d_secure_use = $card_3d_secure_use;
        return $this;
    }

    /**
     * 3Dセキュア利用
     *
     * @return bool true: 3Dセキュア利用
     */
    public function isCard3dSecureUse()
    {
        return $this->card_3d_secure_use == Constant::ENABLED;
    }

    /**
     * @return string
     */
    public function getCard3dStoreName()
    {
        return $this->card_3d_store_name;
    }

    /**
     * @param string $card_3d_store_name
     * @return self
     */
    public function setCard3dStoreName(?string $card_3d_store_name)
    {
        $this->card_3d_store_name = $card_3d_store_name;
        return $this;
    }

    public function getCardInstallmentsEx()
    {
        return $this->getDecode($this->card_installments);
    }

    public function setCardInstallmentsEx($card_installments)
    {
        $this->card_installments = $this->getEncode($card_installments);
        return $this;
    }

    /**
     * 処理区分が仮売上か即時売上か
     *
     * @return boolean true: 仮売上
     */
    public function isCardApiAuth()
    {
        return $this->card_buy_api == ConstantCard::BUY_API_AUTHORIZE;
    }

    /**
     * @return int
     */
    public function getCardBuyApi()
    {
        return $this->card_buy_api;
    }

    /**
     * @param int $card_buy_api
     * @return self
     */
    public function setCardBuyApi(?int $card_buy_api)
    {
        $this->card_buy_api = $card_buy_api;
        return $this;
    }

    /**
     * @return int
     */
    public function getCardCvvUse()
    {
        return $this->card_cvv_use;
    }

    /**
     * @param int $card_cvv_use
     * @return self
     */
    public function setCardCvvUse(?int $card_cvv_use)
    {
        $this->card_cvv_use = $card_cvv_use;
        return $this;
    }

    /**
     * @return bool true: セキュリティコードを利用する
     */
    public function isCardCvvUse()
    {
        return $this->card_cvv_use == Constant::ENABLED;
    }
}
