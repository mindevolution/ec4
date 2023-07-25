<?php

namespace Plugin\SoftbankPayment4\Client;

use Eccube\Exception\ShoppingException;
use Doctrine\ORM\EntityManagerInterface;
use Plugin\SoftbankPayment4\Adapter\SbpsAdapter;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Plugin\SoftbankPayment4\Factory\SbpsTradeFactory;
use Plugin\SoftbankPayment4\Factory\SbpsTradeDetailFactory;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Service\TradeHelper;

class CvsCheckoutClient
{
    private const ACTION_CODE = 'ST01-00101-701';

    private $Order;

    /**
     * @var SbpsAdapter
     */
    private $adapter;
    /**
     * @var ConfigRepository
     */
    private $configRepository;

    /**
     * @var TradeHelper
     */
    private $tradeHelper;
    /**
     * @var SbpsTradeFactory
     */
    private $tradeFactory;
    /**
     * @var SbpsTradeDetailFactory
     */
    private $tradeDetailFactory;
    /**
     * @var EntityManagerInterface
     */
    private $em;

    public function __construct(
        ConfigRepository $configRepository,
        EntityManagerInterface $em,
        SbpsAdapter $adapter,
        SbpsTradeFactory $tradeFactory,
        SbpsTradeDetailFactory $tradeDetailFactory,
        TradeHelper $tradeHelper
    )
    {
        $this->adapter = $adapter;
        $this->configRepository = $configRepository;
        $this->em = $em;
        $this->tradeHelper = $tradeHelper;
        $this->tradeFactory = $tradeFactory;
        $this->tradeDetailFactory = $tradeDetailFactory;
    }

    public function implement($cvsInfo)
    {
        $sxe = $this->adapter->initSxe(self::ACTION_CODE);
        $xmlParam = $this->adapter->arrayToXml($sxe, $this->createParameter($cvsInfo));
        $response = $this->adapter->request($xmlParam);

        if ($response['res_result'] !== 'OK') {
            throw new ShoppingException();
        }

        $Trade = $this->tradeFactory->createByResponse($response, $this->Order, PayMethodType::$method[PayMethodType::CVS_DEFERRED_API]);
        $this->em->persist($Trade);

        $Detail = $this->tradeDetailFactory->createByResponse($response, $Trade, null, (int)$this->Order->getPaymentTotal());
        $this->em->persist($Detail);
    }

    private function createParameter($cvsInfo): array
    {
        $Config = $this->configRepository->get();

        $param = [
            'merchant_id' => $Config->getMerchantId(),
            'service_id' => $Config->getServiceId(),
            'cust_code' => $this->tradeHelper->payoutCustCode($this->Order),
            'order_id' => $this->tradeHelper->createOrderId($this->Order),
            'item_id' => 'ec-cube',
            'tax' => (int) $this->tradeHelper->calculateOrderTax($this->Order),
            'amount' => (int) $this->Order->getPaymentTotal(),
            'pay_method_info' => [
                'issue_type' => 0,
                'last_name' => mb_convert_kana($this->Order->getName01(), 'KVAS'),
                'first_name' => mb_convert_kana($this->Order->getName02(), 'KVAS'),
                'first_zip' => substr($this->Order->getPostalCode(), 0, 3),
                'second_zip' => substr($this->Order->getPostalCode(), 3, 4),
                'add1' => $this->Order->getPref()->getName(),
                'add2' => mb_convert_kana($this->Order->getAddr01(), 'KVAS'),
                'add3' => mb_convert_kana($this->Order->getAddr02(), 'KVAS'),
                'tel' => $this->Order->getPhoneNumber(),
                'mail' => $this->Order->getEmail(),
                'seiyakudate' => $this->tradeHelper->createRequestDate('Ymd'),
                'webcvstype' => $cvsInfo['cvs_type'],
            ],
            'encrypted_flg' => 1,
            'request_date' => $this->tradeHelper->createRequestDate(),
            'limit_second' => 600,
        ];
        $param = $this->normalize($param);

        $param['sps_hashcode'] = $this->tradeHelper->createCheckSum($param, $Config->getHashKey(), 'sjis-win');

        $param['pay_method_info'] = $this->encryptData($param['pay_method_info'], $Config);

        return $param;
    }

    public function setOrder($Order): CvsCheckoutClient
    {
        $this->Order = $Order;
        return $this;
    }

    private function normalize($params)
    {
        foreach ($params as $key => $param) {
            if (is_array($param)) {
                $param = $this->normalize($param);
            } else {
                if (substr($param, 0, 4) == 'http') {
                    $param = trim($param);
                } else {
                    $param = $this->convNgChar($param);
                    $param = $this->convertProhibitedChar($param);
                    $param = $this->convertProhibitedKigo($param);
                    $param = trim($param);
                }
                $params[$key] = $param;
            }
        }

        return $params;
    }

    public function isNgChar($char) {
        if ($char == '') return false;
        // unicode base
        if (bin2hex($char) == 'e28094') return true; // ダッシュ亜種
        if (bin2hex($char) == 'e28095') return true; // ダッシュ
        if (bin2hex($char) == '5c') return true; // バックスラッシュ　＝　￥マーク
        if (bin2hex($char) == 'e3809c') return true; // 波ダッシュ
        if (bin2hex($char) == 'e28096') return true; // パラレル
        if (bin2hex($char) == 'e28892') return true; // 二分ダッシュ
        if (bin2hex($char) == 'c2a2') return true; // セント
        if (bin2hex($char) == 'c2a3') return true; // ポンド
        if (bin2hex($char) == 'c2ac') return true; // 否定算術記号

        $check_char = mb_convert_encoding($char, "SJIS-win");
        // convert sjis base
        if ($check_char == '') return true;
        if (bin2hex($check_char) == '815d') return true;
        if (bin2hex($check_char) == '815f') return true;
        if (bin2hex($check_char) == '8160') return true;
        if (bin2hex($check_char) == '8161') return true;
        if (bin2hex($check_char) == '817c') return true;
        if (bin2hex($check_char) == '8191') return true;
        if (bin2hex($check_char) == '8192') return true;
        if (bin2hex($check_char) == '81ca') return true;
        return false;
    }

    private function convNgChar($str) {
        $ret = '';
        for ($i = 0; $i < mb_strlen($str); $i++) {
            $tmp = mb_substr($str, $i , 1);
            if ($this->isNgChar($tmp)) {
                $ret .= ' ';
            } else {
                $ret .= $tmp;
            }
        }
        return $ret;
    }

    public function convertProhibitedChar($value) {
        $ret = $value;
        for ($i = 0; $i < mb_strlen($value); $i++) {
            $tmp = mb_substr($value, $i , 1);
            if ($this->isProhibitedChar($tmp)) {
                $ret = str_replace($tmp, "　", $value);
            }
        }
        return $ret;
    }

    public function isProhibitedChar($value) {
        $check_char = mb_convert_encoding($value, "SJIS-win");
        if (hexdec('8740') <= hexdec(bin2hex($check_char)) && hexdec('879E') >= hexdec(bin2hex($check_char))) {
            return true;
        }
        if ((hexdec('ED40') <= hexdec(bin2hex($check_char)) && hexdec('ED9E') >= hexdec(bin2hex($check_char))) ||
            (hexdec('ED9F') <= hexdec(bin2hex($check_char)) && hexdec('EDFC') >= hexdec(bin2hex($check_char))) ||
            (hexdec('EE40') <= hexdec(bin2hex($check_char)) && hexdec('EE9E') >= hexdec(bin2hex($check_char))) ||
            (hexdec('FA40') <= hexdec(bin2hex($check_char)) && hexdec('FA9E') >= hexdec(bin2hex($check_char))) ||
            (hexdec('FA9F') <= hexdec(bin2hex($check_char)) && hexdec('FAFC') >= hexdec(bin2hex($check_char))) ||
            (hexdec('FB40') <= hexdec(bin2hex($check_char)) && hexdec('FB9E') >= hexdec(bin2hex($check_char))) ||
            (hexdec('FB9F') <= hexdec(bin2hex($check_char)) && hexdec('FBFC') >= hexdec(bin2hex($check_char))) ||
            (hexdec('FC40') <= hexdec(bin2hex($check_char)) && hexdec('FC4B') >= hexdec(bin2hex($check_char)))){
            return true;
        }
        if ((hexdec('EE9F') <= hexdec(bin2hex($check_char)) && hexdec('EEFC') >= hexdec(bin2hex($check_char))) ||
            (hexdec('F040') <= hexdec(bin2hex($check_char)) && hexdec('F9FC') >= hexdec(bin2hex($check_char)))) {
            return true;
        }

        return false;
    }

    public function convertProhibitedKigo($value) {
        $arrProhiditedKigo = array('^','`','{','|','}','~','&','<','>','"','\'');
        foreach ($arrProhiditedKigo as $prohidited_kigo) {
            if(strstr($value, $prohidited_kigo)) {
                $value = str_replace($prohidited_kigo, " ", $value);
            }
        }
        return $value;
    }

    private function encryptData($arrData, $Config) {

        foreach ($arrData as $key => $val) {
            $arrData[$key] = mb_convert_encoding($val , 'sjis-win');
        }

        // パディング（最後の8バイトブロックに半角スペースを補完）
        foreach ($arrData as $key => $val) {
            $val_len = strlen($val);
            if ($val_len > 0) {
                $val_last_8byte = $val_len % 8;
                $val_null_8byte = ($val_last_8byte != 0) ? 8 - $val_last_8byte : 0;
                for ($i = 0; $i < $val_null_8byte; $i++) {
                    $arrData[$key] .= " ";
                }
            }
        }
        $key3des = $Config->getSecretKey3des();
        $keyiv = $Config->getInitialVector();

        foreach ($arrData as $key => $val) {
            if (!strlen($val) > 0) continue;

            $arrData[$key] = base64_encode(openssl_encrypt($val, 'des-ede3-cbc', $key3des, OPENSSL_RAW_DATA, $keyiv));
        }

        return $arrData;
    }
}
