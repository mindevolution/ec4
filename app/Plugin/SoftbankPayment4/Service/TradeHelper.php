<?php

namespace Plugin\SoftbankPayment4\Service;

use Eccube\Common\Constant;
use Eccube\Entity\Order;
use Eccube\Common\EccubeConfig;
use Eccube\Repository\PluginRepository;
use Plugin\SoftbankPayment4\Repository\ConfigRepository;
use Plugin\SoftbankPayment4\Entity\Master\PayMethodType;
use Symfony\Bundle\FrameworkBundle\Controller\ControllerTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class TradeHelper
{
    use ControllerTrait;

    /**
     * @var ConfigRepository
     */
    private $configRepository;
    /**
     * @var EccubeConfig
     */
    private $eccubeConfig;
    /**
     * @var PluginRepository
     */
    private $pluginRepository;

    public function __construct(
        ConfigRepository $configRepository,
        EccubeConfig $eccubeConfig,
        ContainerInterface $container,
        PluginRepository $pluginRepository
    )
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->configRepository = $configRepository;
        $this->container = $container;
        $this->pluginRepository = $pluginRepository;
    }

    /**
     * リンク型で購入リクエストするリクエストパラメータを作成する.
     *
     * @param Order $Order 受注情報
     *
     * @return array $param リクエストパラメータ ['key' => 'value']
     */
    public function createParam(Order $Order): array
    {
        $Config = $this->configRepository->get();

        // 受注詳細データ整形
        $rowItems = $this->getRowItems($Order);

        // 仕様上、必要のないパラメータもNULLでリクエストする必要があるので注意.
        $param = [
            'pay_method' => PayMethodType::getMethodByClass($Order->getPayment()->getMethodClass(), $Config->getSbpsCredit3dUse()),
            'merchant_id' => $Config->getMerchantId(),
            'service_id' => $Config->getServiceId(),
            'cust_code' => $this->payoutCustCode($Order),
            'sps_cust_no' => '',
            'sps_payment_no' => '',
            'order_id' => $this->createOrderId($Order),
            'item_id' => 'ec-cube',
            'pay_item_id' => '',
            'item_name' => '',
            'tax' => $Order->getTax(),
            'amount' => $Order->getPaymentTotal(),
            'pay_type' => 0,    // 都度購入
            'auto_charge_type' => '',
            'service_type' => 0,    // 都度購入の場合は0のみ指定可能
            'div_settele' => '',
            'last_charge_month' => '',
            'camp_type' => '',
            'tracking_id' => '',
            'terminal_type' => 0,    // PC・スマホに対応
            'success_url' => $this->generateUrl('sbps_link_request_complete', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $this->generateUrl('sbps_link_request_back', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'error_url' => $this->generateUrl('sbps_link_error', [], UrlGeneratorInterface::ABSOLUTE_URL),

            // MEMO: POST通知を受け取ってレスポンスすることで決済が確定するので、グローバルネットワークにつながっていないといけない.
            // 開発環境などではngrokなどを利用すること.
            'pagecon_url' => $this->generateUrl('sbps_link_endpoint', [], UrlGeneratorInterface::ABSOLUTE_URL),

            'free1' => $Order->getOrderNo(),
            'free2' => '',
            'free3' => $this->getPluginVersion(),   // プラグインのバージョン
            'dtl' => $rowItems,
            'request_date' => $this->createRequestDate(),
            'limit_second' => 600,
        ];

        $param['sps_hashcode'] = $this->createCheckSum($param, $Config->getHashKey());

        return $param;
    }

    /**
     * EC側のCustomerIdから顧客コードを払い出す.
     * 非会員の場合はプレフィクス+ランダムな値を払い出す.
     *
     * @param $Order
     * @return string
     */
    public function payoutCustCode($Order): string
    {
        // 顧客コード
        if ($Order->getCustomer()) {
            $cust_code = $Order->getCustomer()->getId();
        } else {
            // 非会員時の顧客ID：プレフィクス + ランダムかつユニークな文字列のmd5ハッシュ値
            $cust_code = 'reg_sps' . md5(uniqid(rand(), true));
        }

        return $cust_code;
    }

    public function createOrderId(Order $Order): string
    {
        return $this->eccubeConfig['sbps']['prefix'] . $Order->getId();
    }

    public function trimOrderId(string $sbpsOrderId)
    {
        return substr($sbpsOrderId, mb_strlen($this->eccubeConfig['sbps']['prefix']));
    }

    public function getItemName($OrderItems): string
    {
        return $this->subString($OrderItems[0]->getProductName(), 40);
    }

    /*
     * プラグインのバージョンを取得
     */
    public function getPluginVersion(): string
    {
        $Plugin = $this->pluginRepository->findByCode('SoftbankPayment4');
        return $Plugin->getVersion();
    }

    /**
     * 文字列から指定バイト数を切り出す。
     *
     * @param string $value
     * @param integer $len
     * @return string 結果
     */
    private function subString($value, $len): string
    {
        $value = mb_convert_encoding($value, 'SJIS', 'UTF-8');
        for ($i = 1, $iMax = mb_strlen($value, 'SJIS'); $i <= $iMax; $i++) {
            $tmp = mb_substr($value, 0 , $i, 'SJIS');
            if (strlen($tmp) <= $len) {
                $ret = mb_convert_encoding($tmp, 'UTF-8', 'SJIS');
            } else {
                break;
            }
        }
        return $ret;
    }

    public function getRowItems(Order $Order): array
    {
        $OrderItems = $Order->getItems();

        $rowItems = [];
        foreach($OrderItems as $index => $OrderItem) {
            // 0円の行があると落ちてしまうため.
            if($OrderItem->getTotalPrice() == 0) continue;

            $ret['dtl_rowno'] = $index + 1;
            $ret['dtl_item_id'] = (int)$OrderItem->getId();
            $ret['dtl_item_name'] = trim($this->subString($OrderItem->getProductName(), 40));
            $ret['dtl_item_count'] = (int)$OrderItem->getQuantity();
            $ret['dtl_tax'] = (int)$OrderItem->getTax();
            $ret['dtl_amount'] = (int)$OrderItem->getTotalPrice();
            $ret['dtl_free1'] = '';
            $ret['dtl_free2'] = '';
            $ret['dtl_free3'] = '';
            $rowItems[] = $ret;
        }

        return $rowItems;
    }

    public function createRequestDate(string $date = 'YmdHis'): string
    {
        $d = new \DateTime();
        return $d->format($date);
    }

    public function createCheckSum(array $param, $hash_key, $checksum_encode = 'utf-8'): string
    {
        // REFACTOR: 再帰呼び出しにするべき.
        $str = '';
        foreach ($param as $value) {
            if(is_array($value)) {
                foreach ($value as $val2) {
                    if (is_array($val2) === false) {
                        $str .= $val2;
                    } else {
                        foreach ($val2 as $val3) {
                            if (is_array($val3) === false) {
                                $str .= $val3;
                            } else {
                                foreach ($val3 as $val4) {
                                    if (is_array($val4) === false) {
                                        $str .= $val4;
                                    } else {
                                        foreach ($val4 as $val5) {
                                            $str .= $val5;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            } else {
                $str .= $value;
            }
        }

        $str .= $hash_key;
        $str_encoded = mb_convert_encoding($str, $checksum_encode);
        return sha1($str_encoded, false);
    }

    public function isExpired($errorCode): bool
    {
        $expiredCode = [
            '40231999',
            '40233999',
        ];

        $reason = substr($errorCode, 3, 2);
        return $reason === '42' || $reason === '56' || in_array($errorCode, $expiredCode);
    }

    public function calculateOrderTax($Order)
    {
        if (version_compare(Constant::VERSION, '4.0.3', '>=')) {

            // 4.0.3以降はOrderItemの税を集計
            $OrderItems = $Order->getOrderItems();

            $sum = 0;
            foreach ($OrderItems as $orderItem){
                $sum += $orderItem->getTax() * $orderItem->getQuantity();
            }

            return $sum;
        } else {
            // 4.0.3未満はOrderに紐付く税率を取得
            return $Order->getTax();
        }

    }
}
