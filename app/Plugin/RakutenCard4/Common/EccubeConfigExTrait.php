<?php

namespace Plugin\RakutenCard4\Common;

use Eccube\Common\Constant;

trait EccubeConfigExTrait
{
    private function getParamKeyOnlyValue($key)
    {
        return self::PLUGIN_KEY . $key;
    }

    private function getParamKeyConfig($key)
    {
        return self::PLUGIN_KEY . self::CONFIG_KEY . $key;
    }

    /**
     * 値の取得(プラグインの定数のみ取得する)
     *
     * @param string $key
     * @return mixed|null
     */
    public function getValue($key)
    {
        if ($this->isConfig($key)){
            $config_key = $this->getParamKeyConfig($key);
            $config_param = $this->get($config_key);
            if (isset($config_param['value'])){
                return $config_param['value'];
            }else{
                return null;
            }
        }

        $param_key = $this->getParamKeyOnlyValue($key);

        if (!$this->has($param_key)){
            return null;
        }
        return $this->get($param_key);
    }

    private function isConfig($key)
    {
        $param_key = $this->getParamKeyConfig($key);

        return $this->has($param_key);
    }

    /**
     * スネークケース変換
     *
     * @param string $str
     * @return string
     */
    public function changeSnake($str)
    {
        return ltrim(strtolower(preg_replace('/[A-Z]/', '_\0', $str)), '_');
    }

    /**
     * キャメルケース変換
     *
     * @param string $str
     * @return string
     */
    public function changeCamel($str)
    {
        return lcfirst(strtr(ucwords(strtr($str, ['_' => ' '])), [' ' => '']));
    }

    /**
     * 先頭大文字キャメルケース変換
     *
     * @param string $str
     * @return string
     */
    public function changeUcfirstCamel($str)
    {
        return ucfirst($this->changeCamel($str));
    }

    /**
     * パスの形にして出力する
     *
     * @param string $key
     * @param bool $trim_only true 最後のスラッシュのトリムのみ false トリム後にスラッシュを付ける
     * @return string|null
     */
    private function getPath($key, $trim_only=false)
    {
        $path = $this->getValue($key);
        if (is_null($path)){
            return null;
        }

        return rtrim($path, '/') . ($trim_only ? '' : '/');
    }

    /**
     * 配列の形にして返す
     *
     * @param $key
     * @return array|mixed|null[]|null
     */
    private function getArray($key)
    {
        $array = $this->getValue($key);
        if (!is_array($array)){
            return [$array];
        }
        return $array;
    }

    /**
     * 0 より大きい数値を指定する場合エラーチェックを兼ねた初期値入力
     *
     * @param string $const_name
     * @param int $ini_value
     * @return int
     */
    private function intValEx($const_name, $ini_value)
    {
        // 数字じゃない場合は初期値に変更する
        $value = $this->getValue($const_name);
        if (!is_numeric($value)){
            $value = $ini_value;
        }
        return intval($value) > 0 ? intval($value) : $ini_value;
    }

    /**
     * Exは1以上に変換されるので、マイナス値も許容するものを作成
     *
     * @param string $const_name
     * @param int $ini_value
     * @return int
     */
    private function intValAll($const_name, $ini_value)
    {
        // 数字じゃない場合は初期値に変更する
        $value = $this->getValue($const_name);
        if (!is_numeric($value)){
            $value = $ini_value;
        }
        return intval($value);
    }

    /**
     * 0 より大きい数値を指定する場合エラーチェックを兼ねた初期値入力
     *
     * @param string $const_name
     * @param int $float_value
     * @return int
     */
    private function floatValEx($const_name, $float_value)
    {
        // 数字じゃない場合は初期値に変更する
        $value = $this->getValue($const_name);
        if (!is_numeric($value)){
            $value = $float_value;
        }
        return $value > 0 ? $value : $float_value;
    }
    /**
     * 0以外の数値との比較
     * （0は正しい応答ができない
     *
     * @param string $key
     * @param int $compare_val
     * @return bool
     */
    private function getBoolean($key, $compare_val = Constant::ENABLED)
    {
        $value = $this->getValue($key);
        // 比較値と同じかどうかでtrue, falseを返す
        return $value == $compare_val;
    }

    /**
     * 配列で返す
     *
     * @param $key
     * @param string $separate
     * @return false|string[]
     */
    private function changeArray($key, $separate=',')
    {
        $value = $this->getValue($key);
        return explode($separate, $value);
    }
}
