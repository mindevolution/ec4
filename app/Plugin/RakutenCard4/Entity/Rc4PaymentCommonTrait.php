<?php

namespace Plugin\RakutenCard4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Plugin\RakutenCard4\Util\Rc4CommonUtil;

/**
 * Rc4PaymentCommonTrait
 */
trait Rc4PaymentCommonTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(name="transaction_id", type="string", length=255, nullable=true)
     */
    private $transaction_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="ip_address", type="string", length=255, nullable=true)
     */
    private $ip_address;

    /**
     *
     * @var string|null
     *
     * @ORM\Column(name="payment_log", type="text", nullable=true)
     */
    private $payment_log;

    /**
     *
     * @var string|null
     *
     * @ORM\Column(name="payment_info", type="text", nullable=true)
     */
    private $payment_info;

    /**
     */
    public function getPaymentInfo()
    {
        return $this->payment_info;
    }

    /**
     * @param $payment_info
     * @return self
     */
    public function setPaymentInfo($payment_info)
    {
        $this->payment_info = $payment_info;
        return $this;
    }

    /**
     * @param $data
     */
    public function setPaymentInfoLog($data)
    {
        $this->setPaymentInfo(Rc4CommonUtil::encodeData($data));
    }

    /**
     * 決済のログ情報を設定する
     * @param $data
     */
    public function addPaymentLog($data)
    {
        $paymentLog = $this->getPaymentLog();
        if (!is_null($paymentLog) && !empty($paymentLog)) {
            $paymentLog = Rc4CommonUtil::decodeData($paymentLog);
        }

        $paymentLog[] = [date('Y-m-d H:i:s') => $data];
        $this->setPaymentLog(Rc4CommonUtil::encodeData($paymentLog));
    }

    /**
     * 決済のログ情報を設定する
     */
    public function getDispPaymentLog()
    {
        $disp_log = [];
        foreach ((array)Rc4CommonUtil::decodeData($this->payment_log) as $record){
            foreach ($record as $time=>$value){
                $disp_log[] = [$time=>Rc4CommonUtil::encodeData($value)];
            }
        }
        return $disp_log;
    }

    /**
     * @return string|null
     */
    public function getTransactionId()
    {
        return $this->transaction_id;
    }

    /**
     * @param string|null $transaction_id
     * @return self
     */
    public function setTransactionId(?string $transaction_id)
    {
        $this->transaction_id = $transaction_id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIpAddress()
    {
        return $this->ip_address;
    }

    /**
     * @param string|null $ip_address
     * @return self
     */
    public function setIpAddress(?string $ip_address)
    {
        $this->ip_address = $ip_address;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentLog()
    {
        return $this->payment_log;
    }

    /**
     * @param string|null $payment_log
     * @return self
     */
    public function setPaymentLog(?string $payment_log)
    {
        $this->payment_log = $payment_log;
        return $this;
    }

}
