<?php

namespace Plugin\SoftbankPayment4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Plugin\SoftbankPayment4\Entity\Master\SbpsTradeDetailResultType as ResultType;

/**
 * SbpsTradingDetail
 *
 * @ORM\Table(name="plg_softbank_payment4_trade_detail")
 * @ORM\Entity(repositoryClass="Plugin\SoftbankPayment4\Repository\SbpsTradeDetailRepository")
 */
class SbpsTradeDetail extends \Eccube\Entity\AbstractEntity
{
    public static function create($transaction_id, $tradeType, $result, $amount, $error_code, $date, $Trade): SbpsTradeDetail
    {
        $Detail = new self();

        $Detail->transaction_id = $transaction_id;
        $Detail->trade_type = $tradeType;
        $Detail->result = $result;
        $Detail->amount = $amount;
        $Detail->error_code = $error_code;
        $Detail->setTradeDate($date);
        $Detail->SbpsTrade = $Trade;

        return $Detail;
    }

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="transaction_id", type="string", length=255, nullable=true)
     */
    private $transaction_id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="trade_type", type="smallint", nullable=true)
     */
    private $trade_type;

    /**
     * @var int
     *
     * @ORM\Column(name="result", type="smallint")
     */
    private $result;

    /**
     * @var string
     *
     * @ORM\Column(name="amount", type="decimal", precision=12, scale=2, nullable=true)
     */
    private $amount;

    /**
     * @var string|null
     *
     * @ORM\Column(name="error_code", type="string", length=32, nullable=true)
     */
    private $error_code;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="trade_date", type="datetimetz")
     */
    private $trade_date;

    /**
     * @var SbpsTrade
     *
     * @ORM\ManyToOne(targetEntity="\Plugin\SoftbankPayment4\Entity\SbpsTrade", inversedBy="SbpsTradeDetails", cascade={"persist","remove"})
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="sbps_trade_id", referencedColumnName="id")
     * })
     */
    private $SbpsTrade;

    public function getId()
    {
        return $this->id;
    }

    public function getTransactionId()
    {
        return $this->transaction_id;
    }

//    public function setTransactionId($transaction_id)
//    {
//        $this->transaction_id = $transaction_id;
//        return $this;
//    }
//
    public function getTradeType()
    {
        return $this->trade_type;
    }

//    public function setTradeType($trade_type)
//    {
//        $this->trade_type = $trade_type;
//        return $this;
//    }
//
    public function getResult()
    {
        return $this->result;
    }

//    public function setResult($result)
//    {
//        $this->result = $result;
//        return $this;
//    }
//
    public function getAmount()
    {
        return $this->amount;
    }

//    public function setAmount($amount)
//    {
//        $this->amount = $amount;
//        return $this;
//    }
//
//    public function setErrorCode($error_code)
//    {
//        $this->error_code = $error_code;
//        return $this;
//    }

    private function setTradeDate($str_trade_date)
    {
        $this->trade_date = new \DateTime('@'.strtotime($str_trade_date));
        return $this;
    }

//    /**
//     * Set SbpsTrade.
//     */
//    public function setSbpsTrade(SbpsTrade $SbpsTrade)
//    {
//        $this->SbpsTrade = $SbpsTrade;
//
//        return $this;
//    }

    /**
     * Get SbpsTrade.
     */
    public function getSbpsTrade()
    {
        return $this->SbpsTrade;
    }
}
