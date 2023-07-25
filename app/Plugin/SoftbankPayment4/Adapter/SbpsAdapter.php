<?php

namespace Plugin\SoftbankPayment4\Adapter;

use GuzzleHttp\Client;
use Guzzle\Http\Exception\BadResponseException;
use Guzzle\Http\Exception\CurlException;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;

class SbpsAdapter
{
    public const ENCODING = 'SHIFT_JIS';
    public const SECURE_METHOD = 'des-ede3-cbc';

    private $endpoint;
    private $secret_key;
    private $initial_vector;
    private $basic_user;
    private $basic_pass;

    public function __construct(ConfigRepository $configRepository)
    {
        $Config = $configRepository->get();
        $this->endpoint = $Config->getApiRequestUrl();
        $this->secret_key = $Config->getSecretKey3des();
        $this->initial_vector = $Config->getInitialVector();
        $this->basic_user = $Config->getMerchantId() . $Config->getServiceId();
        $this->basic_pass = $Config->getHashKey();
    }

    public function request($xml)
    {
        logs('sbps')->info(print_r('Request body: '.$xml, true));

        $httpConfig = [
            'curl' => [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/xml; charset='.self::ENCODING],
                CURLOPT_USERPWD => $this->basic_user.':'.$this->basic_pass,
            ],
        ];

        $Client = new Client($httpConfig);
        try {
            $HttpResponse = $Client->request('POST', $this->endpoint, ['body' => $xml]);
        } catch(CurlException $e) {
            logs('sbps')->error('CurlException. endpoint='. $this->endpoint . ', error=' . $e);
            return false;
        } catch(BadResponseException $e) {
            logs('sbps')->error('BadResponseException. url=' . $this->endpoint . ' error=' . $e);
            return false;
        } catch(\Exception $e) {
            logs('sbps')->error('Exception. url=' . $this->endpoint . ' error=' . $e);
            return false;
        }

        $httpStatusCode = $HttpResponse->getStatusCode();
        if($httpStatusCode !== 200) {
            logs('sbps')->error('HTTP STATUS CODE ' . $httpStatusCode);
            return false;
        }

        $resXml = $HttpResponse->getBody()->getContents();
        $arrResponse = $this->xmlToArray($resXml);

        $result = $this->formatResponse($arrResponse, [
            'sec_key' => $this->secret_key,
            'iv' => $this->initial_vector,
            'method' => self::SECURE_METHOD,
        ]);

        logs('sbps')->info(print_r('Response body: '.print_r($result, true), true));

        return $result;
    }

    /**
     * レスポンスをフォーマットする.
     *
     * @param array $arrData
     * @param array $decode_setting
     * @return array
     */
    protected function formatResponse(array $arrData, array $decode_setting): array
    {
        if(empty($arrData['res_pay_method_info']) === false) {
            $arrDecode = $this->decodeResponse($arrData['res_pay_method_info'], $decode_setting);
            foreach ($arrDecode as $key => $value) {
                $arrData[$key] = $value;
            }
            unset($arrData['res_pay_method_info']);
        }

        foreach ($arrData as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $skey => $sval) {
                    if (empty($sval) === false) {
                        $arrData[$key][$skey] = trim($sval);
                    }
                }
                continue;
            }
            if (empty($val) === false) {
                $arrData[$key] = trim($val);
            }
        }

        return $arrData;
    }

    /**
     * 暗号化をデコードする.
     * @param array $arrData
     * @param array $decode_setting
     * @return array
     */
    protected function decodeResponse(array $arrData, array $decode_setting): array
    {
        $iv = $decode_setting['iv'];
        $password = $decode_setting['sec_key'];
        $method = $decode_setting['method'];

        $arrRet = [];
        foreach ($arrData as $key => $value) {
            if(is_array($value)) {
                $arrRet[$key] = $this->decodeResponse($arrData[$key], $decode_setting);
            } else {
                $decodeTarget = base64_decode($value);
                $arrRet[$key] = openssl_decrypt(
                    $decodeTarget,
                    $method,
                    $password,
                    OPENSSL_NO_PADDING,
                    $iv
                );
            }
        }

        return $arrRet;
    }
    /**
     * SBPSフォーマットに合わせてXML文書を初期化する.
     *
     * @var string $actionCode
     * @return \SimpleXMLElement SimpleXMLElement
     */
    public function initSxe($actionCode): \SimpleXMLElement
    {
        return new \SimpleXMLElement('<?xml version="1.0" encoding="'. self::ENCODING .'"?><sps-api-request id="'. $actionCode .'"></sps-api-request>');
    }

    /**
     * 配列を再帰的にXMLに変換する.
     * @param \SimpleXMLElement $sxe
     * @param array $array
     * @return mixed
     */
    public function arrayToXml(\SimpleXMLElement &$sxe, array $array)
    {
        foreach($array as $key => $value) {
            if(is_array($value)) {
                if(!is_numeric($key)) {
                    $subNode = $sxe->addChild($key);
                    $this->arrayToXml($subNode, $value);
                } else {
                    $this->arrayToXml($sxe, $value);
                }
            } else {
                $sxe->addChild($key, $value);
            }
        }
        return $sxe->asXML();
    }

    /**
     * XMLを連想配列に変換する.
     * @param $xml
     * @return mixed
     */
    protected function xmlToArray($xml) {
        $xml = mb_convert_encoding($xml, 'UTF-8', 'sjis-win');
        $xml = preg_replace("/<\?xml.*\?>/", '', $xml);

        $Xml = simplexml_load_string($xml);
        $result = json_decode(json_encode($Xml), true);

        return $result;
    }
}
