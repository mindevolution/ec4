<?php

namespace Plugin\SoftbankPayment4\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Order;
use Plugin\SoftbankPayment4\Entity\Master\SbpsActionType as ActionType;
use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType as StatusType;

/**
 * SbpsTrading
 *
 * @ORM\Table(name="plg_softbank_payment4_trade")
 * @ORM\Entity(repositoryClass="Plugin\SoftbankPayment4\Repository\SbpsTradeRepository")
 */
class SbpsTrade extends \Eccube\Entity\AbstractEntity
{
    public function __construct()
    {
        $this->SbpsTradeDetails = new ArrayCollection();
    }

    public static function create($trackingId, $statusType, $authorized_amount, $captured_amount, $Order): SbpsTrade
    {
        $Trade = new self();
        $Trade->tracking_id = $trackingId;
        $Trade->status = $statusType;
        $Trade->authorized_amount = $authorized_amount;
        $Trade->captured_amount = $captured_amount;
        $Trade->Order = $Order;

        return $Trade;
    }

    public function canRefund(): bool
    {
        return in_array($this->status, StatusType::$refundables, true);
    }

    public function getActable($captureType)
    {
        return ActionType::getActionType($this, $captureType);
    }

    public function getDispAction($captureType): ?string
    {
        return $this->getActable($captureType) !== null ? ActionType::$disp_name[$this->getActable($captureType)] : null;
    }

    public function getAlert()
    {
        $paymentTotal = $this->getOrder()->getPaymentTotal();
        if ($this->status === StatusType::ARED && $this->authorized_amount !== $paymentTotal) {
            return $paymentTotal - $this->authorized_amount;
        }

        if ($this->status === StatusType::CAPTURED && $this->captured_amount !== $paymentTotal) {
            return $paymentTotal - $this->captured_amount;
        }

        return null;
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
     * @ORM\Column(name="tracking_id", type="string", length=32)
     */
    private $tracking_id;

    /**
     * @var int|null
     *
     * @ORM\Column(name="trade_status", type="smallint", nullable=true)
     */
    private $status;

    /**
     * @var string
     *
     * @ORM\Column(name="authorized_amount", type="decimal", precision=12, scale=2, options={"unsigned":true}, nullable=true)
     */
    private $authorized_amount;

    /**
     * @var string
     *
     * @ORM\Column(name="captured_amount", type="decimal", precision=12, scale=2, options={"unsigned":true}, nullable=true)
     */
    private $captured_amount;

     /**
     * @var Order
     *
     * @ORM\OneToOne(targetEntity="Eccube\Entity\Order", inversedBy="SbpsTrade")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var Collection|SbpsTradeDetail[]
     *
     * @ORM\OneToMany(targetEntity="Plugin\SoftbankPayment4\Entity\SbpsTradeDetail", mappedBy="SbpsTrade")
     */
    private $SbpsTradeDetails;

    public function getId(): int
    {
        return $this->id;
    }

    public function getTrackingId(): string
    {
        return $this->tracking_id;
    }

    public function setTrackingId($tracking_id): SbpsTrade
    {
        $this->tracking_id = $tracking_id;
        return $this;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setStatus($status): SbpsTrade
    {
        $this->status = $status;
        return $this;
    }

    public function getAuthorizedAmount(): string
    {
        return $this->authorized_amount;
    }

    public function setAuthorizedAmount($authorized_amount): SbpsTrade
    {
        $this->authorized_amount = $authorized_amount;
        return $this;
    }

    public function getCapturedAmount(): string
    {
        return $this->captured_amount;
    }

    public function setCapturedAmount($captured_amount): SbpsTrade
    {
        $this->captured_amount = $captured_amount;
        return $this;
    }

    /**
     * Set order.
     *
     * @param Order|null $order
     *
     * @return SbpsTrade
     */
    public function setOrder(Order $order = null): SbpsTrade
    {
        $this->Order = $order;

        return $this;
    }

    /**
     * Get order.
     *
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->Order;
    }

    /**
     * @param string $status
     * @return SbpsTrade
     */
    public function changeStatus($status): SbpsTrade
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @param string $captured_amount
     * @return SbpsTrade
     */
    public function changeCapturedAmount($captured_amount): SbpsTrade
    {
        $this->captured_amount = $captured_amount;
        return $this;
    }

    /**
     * Add SbpsTradeDetail.
     *
     * @param SbpsTradeDetail $SbpsTradeDetail
     *
     * @return SbpsTrade
     */
    public function addSbpsTradeDetail(SbpsTradeDetail $SbpsTradeDetail): SbpsTrade
    {
        $this->SbpsTradeDetails[] = $SbpsTradeDetail;

        return $this;
    }

    public function getSbpsTradeDetails()
    {
        return $this->SbpsTradeDetails->getValues();
    }
}
