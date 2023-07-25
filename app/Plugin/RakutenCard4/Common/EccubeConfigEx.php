<?php

namespace Plugin\RakutenCard4\Common;

use Eccube\Common\Constant;
use Eccube\Common\EccubeConfig;

class EccubeConfigEx extends EccubeConfig
{
    use EccubeConfigExTrait;

    const CONFIG_KEY = 'config_';
    const PLUGIN_KEY = 'rakuten_card4.';

    /**
     * 分割回数を返す
     *
     * @return array
     */
    public function card_installments_kind()
    {
        $temp = $this->changeArray(__FUNCTION__);
        $card_installments_kind = [];
        if ($temp !== false){
            foreach ($temp as $value){
                if (intval($value) > 0){
                    $card_installments_kind[] = intval($value);
                }
            }
        }

        return $card_installments_kind;
    }

    /**
     * 登録済みカードの件数を返す
     *
     * @return int
     */
    public function card_register_count()
    {
        return $this->intValEx(__FUNCTION__, 5);
    }

    /**
     * カードのエラーリストを返す（フロント）
     *
     * @return array
     */
    public function front_error_list_card()
    {
        return $this->getArray(__FUNCTION__);
    }

    /**
     * カードのエラーリストを返す（管理）
     *
     * @return array
     */
    public function admin_error_list_card()
    {
        return $this->getArray(__FUNCTION__);
    }

    /**
     * フロント側の共通エラー
     *
     * @return mixed|null
     */
    public function front_error_common()
    {
        return $this->getValue(__FUNCTION__);
    }

    /**
     * 管理側の共通エラー
     *
     * @return mixed|null
     */
    public function admin_error_common()
    {
        return $this->getValue(__FUNCTION__);
    }

    /**
     * 3dセキュアの店舗名の登録文字数を返す
     *
     * @return int
     */
    public function card_3d_store_name_len()
    {
        return $this->intValEx(__FUNCTION__, 18);
    }

    /**
     * 3dセキュアの店舗名の登録文字数を返す
     *
     * @return int
     */
    public function cvs_items_name()
    {
        $value = $this->getValue(__FUNCTION__);
        return empty($value) ? 'ご注文商品一式' : $value;
    }

    /**
     * 3dセキュアの店舗名の登録文字数を返す
     *
     * @return int
     */
    public function cvs_items_id()
    {
        $value = $this->getValue(__FUNCTION__);
        return empty($value) ? 'A' : $value;
    }

    /**
     * マイページでも3dセキュアを利用するかどうか
     *
     * @return int
     */
    public function card_mypage_3d_use()
    {
        return $this->getBoolean(__FUNCTION__, Constant::ENABLED);
    }

    /**
     * 設定画面で3Dセキュア追加認証に2つ選択肢を出すかどうか（0はデフォルトリスクベースのみ）
     *
     * @return int
     */
    public function card_3d_challenge_type_all()
    {
        return $this->getBoolean(__FUNCTION__, Constant::ENABLED);
    }

}
