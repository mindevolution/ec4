<?php

namespace Plugin\RakutenCard4\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\Master\OrderStatus;
use Plugin\RakutenCard4\Common\ConstantCard;
use Plugin\RakutenCard4\Common\ConstantCvs;
use Plugin\RakutenCard4\Common\ConstantPaymentStatus;
use Plugin\RakutenCard4\Service\Method\Convenience;
use Plugin\RakutenCard4\Service\Method\CreditCard;

/**
 * Rc4OrderPayment
 *
 * @ORM\Table(name="plg_rakuten_card4_order_payment")
 * @ORM\Entity(repositoryClass="Plugin\RakutenCard4\Repository\Rc4OrderPaymentRepository")
 */
class Rc4OrderPayment extends \Eccube\Entity\AbstractEntity implements Rc4TokenEntityInterface
{
    use Rc4TokenTrait, Rc4PaymentCommonTrait;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", options={"unsigned":true})
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Eccube\Entity\Order
     *
     * @ORM\ManyToOne(targetEntity="Eccube\Entity\Order")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="order_id", referencedColumnName="id")
     * })
     */
    private $Order;

    /**
     * @var integer
     *
     * @ORM\Column(name="payment_status", type="smallint", options={"unsigned":true})
     */
    private $payment_status;

    /**
     * メソッドクラス名
     *
     * カード決済やクラス名を制御
     *
     * @var string|null
     *
     * @ORM\Column(name="method_class", type="string", length=255, nullable=true)
     */
    private $method_class;

    /**
     * @var string
     *
     * @ORM\Column(name="pay_amount", type="decimal", precision=12, scale=2, options={"unsigned":true,"default":0})
     */
    private $pay_amount=0;

    /**
     * @var string|null
     *
     * @ORM\Column(name="request_id", type="string", length=255, nullable=true)
     */
    private $request_id;

    /**
     * @var string|null
     *
     * @ORM\Column(name="error_code", type="string", length=255, nullable=true)
     */
    private $error_code;

    /**
     * @var string|null
     *
     * @ORM\Column(name="error_message", type="string", length=2000, nullable=true)
     */
    private $error_message;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="first_transaction_date", type="datetimetz", nullable=true)
     */
    private $first_transaction_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="last_transaction_date", type="datetimetz", nullable=true)
     */
    private $last_transaction_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="authorize_date", type="datetimetz", nullable=true)
     */
    private $authorize_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="capture_date", type="datetimetz", nullable=true)
     */
    private $capture_date;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cancel_date", type="datetimetz", nullable=true)
     */
    private $cancel_date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="card_base_transaction_id", type="string", length=255, nullable=true)
     */
    private $card_base_transaction_id;

    /**
     * @var integer
     *
     * @ORM\Column(name="card_installment", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_installment;

    /**
     * @var integer
     *
     * @ORM\Column(name="card_buy_api_kind", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_buy_api_kind;

    /**
     * @var integer
     *
     * @ORM\Column(name="card_use_kind", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $card_use_kind;

    /**
     * @var boolean
     *
     * @ORM\Column(name="card_check_register", type="boolean", options={"default":false})
     */
    private $card_check_register;

    /**
     * @var integer
     *
     * @ORM\Column(name="cvs_kind", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $cvs_kind;

    /**
     * @var integer
     *
     * @ORM\Column(name="cvs_code", type="smallint", options={"unsigned":true}, nullable=true)
     */
    private $cvs_code;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="cvs_expiration_date", type="datetimetz", nullable=true)
     */
    private $cvs_expiration_date;

    /**
     * @var string|null
     *
     * @ORM\Column(name="cvs_number", type="string", length=255, nullable=true)
     */
    private $cvs_number;

    /**
     * 決済ステータス名の表示
     *
     * @return string
     */
    public function getPaymentStatusName()
    {
        $trans_key = 'rakuten_card4.payment_status_name.';
        switch ($this->payment_status){
            case ConstantPaymentStatus::Canceled:
                $trans_key .= 'canceled';
                break;
            case ConstantPaymentStatus::Authorized:
                $trans_key .= 'authorized';
                if($this->isConenience()) {
                    $trans_key .= '_cvs';
                }
                break;
            case ConstantPaymentStatus::Captured:
                $trans_key .= 'captured';
                if($this->isConenience()) {
                    $trans_key .= '_cvs';
                }
                break;
            case ConstantPaymentStatus::Pending:
                $trans_key .= 'pending';
                break;
            case ConstantPaymentStatus::ExpiredCvs:
                $trans_key .= 'expired_cvs';
                break;
            case ConstantPaymentStatus::First:
            default:
                $trans_key .= 'first';
                break;
        }

        return trans($trans_key);
    }

    /**
     * @return bool true クレジット決済
     */
    public function isCard()
    {
        return $this->method_class == CreditCard::class;
    }

    /**
     * @return bool true コンビニ決済
     */
    public function isConenience()
    {
        return $this->method_class == Convenience::class;
    }

    /**
     * 支払い方法名の取得
     *
     * @return string
     */
    public function getPaymentName()
    {
        $payment_name = '';
        if ($this->isCard()){
            $payment_name = trans('rakuten_card4.admin.order_edit.payment_card');
        }else if($this->isConenience()){
            $payment_name = trans('rakuten_card4.admin.order_edit.payment_cvs');
        }
        return $payment_name;
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return \Eccube\Entity\Order
     */
    public function getOrder(): \Eccube\Entity\Order
    {
        return $this->Order;
    }

    /**
     * @param \Eccube\Entity\Order $Order
     * @return self
     */
    public function setOrder(\Eccube\Entity\Order $Order)
    {
        $this->Order = $Order;
        return $this;
    }

    /**
     * @return int
     */
    public function getPaymentStatus()
    {
        return $this->payment_status;
    }

    /**
     * @param int $payment_status
     * @return self
     */
    public function setPaymentStatus(?int $payment_status)
    {
        $this->payment_status = $payment_status;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getMethodClass()
    {
        return $this->method_class;
    }

    /**
     * @param string|null $method_class_name
     * @return self
     */
    public function setMethodClass(?string $method_class_name)
    {
        $this->method_class = $method_class_name;
        return $this;
    }

    /**
     * @param $error_code
     * @return self
     */
    public function setErrorCode($error_code)
    {
        $this->error_code = $error_code;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorCode()
    {
        return $this->error_code;
    }

    /**
     * @param $error_message
     */
    public function setErrorMessage($error_message)
    {
        $this->error_message = $error_message;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->error_message;
    }

    /**
     * @return string
     */
    public function getPayAmount()
    {
        return $this->pay_amount;
    }

    /**
     * @return int
     */
    public function getPayAmountJpy()
    {
        return intval($this->pay_amount);
    }

    /**
     * @param string $pay_amount
     * @return self
     */
    public function setPayAmount($pay_amount)
    {
        $this->pay_amount = $pay_amount;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getRequestId()
    {
        return $this->request_id;
    }

    /**
     * @param string|null $request_id
     * @return self
     */
    public function setRequestId(?string $request_id)
    {
        $this->request_id = $request_id;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getFirstTransactionDate()
    {
        return $this->first_transaction_date;
    }

    /**
     * @param \DateTime $first_transaction_date
     * @return self
     */
    public function setFirstTransactionDate(?\DateTime $first_transaction_date)
    {
        $this->first_transaction_date = $first_transaction_date;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getLastTransactionDate()
    {
        return $this->last_transaction_date;
    }

    /**
     * @param \DateTime $last_transaction_date
     * @return self
     */
    public function setLastTransactionDate(?\DateTime $last_transaction_date)
    {
        $this->last_transaction_date = $last_transaction_date;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getAuthorizeDate()
    {
        return $this->authorize_date;
    }

    /**
     * @param \DateTime $authorize_date
     * @return self
     */
    public function setAuthorizeDate(?\DateTime $authorize_date)
    {
        $this->authorize_date = $authorize_date;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCaptureDate()
    {
        return $this->capture_date;
    }

    /**
     * @param \DateTime $capture_date
     * @return self
     */
    public function setCaptureDate(?\DateTime $capture_date)
    {
        $this->capture_date = $capture_date;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCancelDate()
    {
        return $this->cancel_date;
    }

    /**
     * @param \DateTime $cancel_date
     * @return self
     */
    public function setCancelDate(?\DateTime $cancel_date)
    {
        $this->cancel_date = $cancel_date;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCardBaseTransactionId()
    {
        return $this->card_base_transaction_id;
    }

    /**
     * @param string|null $card_base_transaction_id
     * @return self
     */
    public function setCardBaseTransactionId(?string $card_base_transaction_id)
    {
        $this->card_base_transaction_id = $card_base_transaction_id;
        return $this;
    }

    /**
     * @return int
     */
    public function getCardInstallment()
    {
        return $this->card_installment;
    }

    /**
     * @param int $card_installment
     * @return self
     */
    public function setCardInstallment(?int $card_installment)
    {
        $this->card_installment = $card_installment;
        return $this;
    }

    /**
     * @return int
     */
    public function getCardBuyApiKind()
    {
        return $this->card_buy_api_kind;
    }

    /**
     * @param int $card_buy_api_kind
     * @return self
     */
    public function setCardBuyApiKind(?int $card_buy_api_kind)
    {
        $this->card_buy_api_kind = $card_buy_api_kind;
        return $this;
    }

    /**
     * 購入時のAPIが与信かどうか
     *
     * @return bool true: 購入時のAPIが与信 false: 即時売上
     */
    public function isCardBuyApiAuth()
    {
        if (is_null($this->card_buy_api_kind)){
            // 値が入っていない場合は、与信とみなす
            return true;
        }

        return $this->card_buy_api_kind == ConstantCard::BUY_API_AUTHORIZE;
    }

    /**
     * @return int
     */
    public function getCardUseKind()
    {
        return $this->card_use_kind;
    }

    /**
     * @param int $card_use_kind
     * @return self
     */
    public function setCardUseKind(?int $card_use_kind)
    {
        $this->card_use_kind = $card_use_kind;
        return $this;
    }

    /**
     * 入力カードを利用
     *
     * @return bool true: 入力カードを利用
     */
    public function isUseCardInput()
    {
        return
            empty($this->card_use_kind) ||
            $this->card_use_kind == ConstantCard::USE_KIND_INPUT
        ;
    }

    /**
     * 登録済みカードを利用
     *
     * @return bool true: 登録済みカード
     */
    public function isUseCardRegister()
    {
        return $this->card_use_kind == ConstantCard::USE_KIND_REGISTER;
    }

    /**
     * ラベルの出力
     *
     * @return string
     */
    public function getUseCardLabel()
    {
        if ($this->isUseCardRegister()){
            return 'rakuten_card4.admin.order_edit.payment_register_card';
        }else{
            return 'rakuten_card4.admin.order_edit.payment_card';
        }
    }

    /**
     * @return bool
     */
    public function isCardCheckRegister()
    {
        return $this->card_check_register;
    }

    /**
     * @param bool $card_check_register
     * @return self
     */
    public function setCardCheckRegister(bool $card_check_register)
    {
        $this->card_check_register = $card_check_register;
        return $this;
    }

    /**
     * カード登録かどうかのラベルの出力
     *
     * @return string
     */
    public function getCardCheckRegisterLabel()
    {
        if ($this->isCardCheckRegister()){
            return 'rakuten_card4.front.shopping.card.register.confirm.yes';
        }else{
            return 'rakuten_card4.front.shopping.card.register.confirm.no';
        }
    }

    /**
     * @return int
     */
    public function getCvsKind()
    {
        return $this->cvs_kind;
    }

    /**
     * @param int $cvs_kind
     * @return self
     */
    public function setCvsKind(?int $cvs_kind)
    {
        $this->cvs_kind = $cvs_kind;
        return $this;
    }

    /**
     * @return int
     */
    public function getCvsCode()
    {
        return $this->cvs_code;
    }

    /**
     * @param int $cvs_code
     * @return self
     */
    public function setCvsCode(?int $cvs_code)
    {
        $this->cvs_code = $cvs_code;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getCvsExpirationDate()
    {
        return $this->cvs_expiration_date;
    }

    /**
     * @param \DateTime $cvs_expiration_date
     * @return self
     */
    public function setCvsExpirationDate(?\DateTime $cvs_expiration_date)
    {
        $this->cvs_expiration_date = $cvs_expiration_date;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getCvsNumber()
    {
        return $this->cvs_number;
    }

    /**
     * @param string|null $cvs_number
     * @return self
     */
    public function setCvsNumber(?string $cvs_number)
    {
        $this->cvs_number = $cvs_number;
        return $this;
    }

    /**
     * 支払い方法回数
     *
     * @return string
     */
    public function getCardInstallmentLabel()
    {
        $label = '';
        switch ($this->card_installment){
            case 1:
                $label = trans('rakuten_card4.admin.card.config.installments.all');
                break;
            case ConstantCard::WITH_BONUS:
                $label = trans('rakuten_card4.admin.card.config.installments.with_bonus');
                break;
            case ConstantCard::WITH_REVOLVING:
                $label = trans('rakuten_card4.admin.card.config.installments.with_revolving');
                break;
            default:
                if (!empty($this->card_installment)){
                    $label = trans('rakuten_card4.admin.card.config.installments.word', ['%count%' => $this->card_installment]);
                }
                break;
        }
        return $label;
    }

    public function isWithBonus()
    {
        if ($this->isConenience()){
            return false;
        }

        return $this->card_installment == ConstantCard::WITH_BONUS;
    }

    public function isWithRevolving()
    {
        if ($this->isConenience()){
            return false;
        }

        return $this->card_installment == ConstantCard::WITH_REVOLVING;
    }

    public function isInstallments()
    {
        if ($this->isConenience()){
            return false;
        }

        if (is_null($this->card_installment)){
            return false;
        }

        switch ($this->card_installment){
            case ConstantCard::WITH_BONUS:
            case ConstantCard::WITH_REVOLVING:
                return false;
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * コンビニの表示名
     *
     * @return string
     */
    public function getCvsKindLabel()
    {
        $label = '';
        switch ($this->cvs_kind){
            case ConstantCvs::CVS_KIND_SEVENELEVEN;
                $label = trans('rakuten_card4.cvs.label.kind001');
                break;
            case ConstantCvs::CVS_KIND_LAWSON;
                $label = trans('rakuten_card4.cvs.label.kind002');
                break;
            case ConstantCvs::CVS_KIND_DAILYYAMAZAKI;
                $label = trans('rakuten_card4.cvs.label.kind003');
                break;
            default:
                break;
        }
        return $label;
    }

    /**
     * 仮売上状態
     *
     * @return bool true: 仮売上
     */
    public function isAuthorized()
    {
        // 10〜19のステータスはauthorized想定
        return $this->payment_status == ConstantPaymentStatus::Authorized;
    }

    public function isCaptured()
    {
        // 20〜29のステータスはcaptured想定
        return $this->payment_status == ConstantPaymentStatus::Captured;
    }

    /**
     * 初期状態
     *
     * @return bool true: レコード生成時のステータス
     */
    public function isFirst()
    {
        // 一番最初のステータスかどうか
        return $this->payment_status == ConstantPaymentStatus::First;
    }

    /**
     * 未決済状態（再与信可能）
     *
     * @return bool true: 未決済
     */
    public function isInitialized()
    {
        // 最初の未決済は除く
        // 2〜9のステータスはinitialized想定
        return $this->payment_status == ConstantPaymentStatus::Canceled;
    }

    /**
     * 保留状態（pendingの戻りの時）
     *
     * @return bool true: 保留
     */
    public function isPending()
    {
        // 30〜39のステータスはpending想定
        return $this->payment_status == ConstantPaymentStatus::Pending;
    }

    /**
     * 管理画面上で動かせないステータス
     *
     * @return bool true
     */
    private function isStopStatus()
    {
        return
            empty($this->method_class) ||
            empty($this->payment_status) ||
            $this->isFirst() ||
            $this->isPending()
        ;
    }

    /**
     * 与信可能かどうか
     *
     * @return bool true 再与信可能
     */
    public function execAbleAuthorize()
    {
        // コンビニの場合はどのステータスにもかかわらずNG
        // 管理画面で新規受注を作成する時のため
        // 位置はここにしておく
        if ($this->isConenience()){
            return false;
        }

        // 動かせないステータス
        if ($this->isStopStatus()){
            return false;
        }

        // カード決済で再オーソリができるステータス
        if ($this->isCard()){
            return $this->isInitialized();
        }

        // 現在ここに来ることはないが、falseにしておく
        return false;
    }

    /**
     * 売上可能かどうか
     *
     * @return bool true 売上可能
     */
    public function execAbleCapture()
    {
        // 動かせないステータス
        if ($this->isStopStatus()){
            return false;
        }

        // コンビニの場合はどのステータスにもかかわらずNG
        if ($this->isConenience()){
            return false;
        }

        // カード決済で売上ができるステータス
        if ($this->isCard()){
            return $this->isAuthorized();
        }

        // 現在ここに来ることはないが、falseにしておく
        return false;
    }

    /**
     * 取消可能かどうか
     *
     * @return bool true 取消可能
     */
    public function execAbleCancel()
    {
        // 動かせないステータス
        if ($this->isStopStatus()){
            return false;
        }

        // コンビニの場合は与信のときに可能
        if ($this->isConenience()){
            return $this->isAuthorized();
        }

        // カード決済で取消ができるステータス
        if ($this->isCard()){
            return
                $this->isAuthorized() ||
                $this->isCaptured()
                ;
        }

        // 現在ここに来ることはないが、falseにしておく
        return false;
    }

    /**
     * 金額変更可能かどうか
     *
     * @return bool true 金額変更可能
     */
    public function execAbleModify()
    {
        // 動かせないステータス
        if ($this->isStopStatus()){
            return false;
        }

        // コンビニの場合はNG
        if ($this->isConenience()){
            return false;
        }

        // 金額が一致する場合は変更できない
        $same_payment_total = $this->Order->getPaymentTotal() == $this->pay_amount;
        if ($same_payment_total){
            return false;
        }

        // カード決済で金額変更ができるステータス
        if ($this->isCard()){
            return
                $this->isAuthorized() ||
                $this->isCaptured()
                ;
        }

        // 現在ここに来ることはないが、falseにしておく
        return false;
    }

    /**
     * 登録済みカードを設定する
     *
     * @param Rc4CustomerToken $CustomerToken
     * @return bool
     */
    public function setRegisterCard($CustomerToken)
    {
        if (is_null($CustomerToken)){
            return false;
        }

        // 登録済みカード情報を設定する
        $this->card_base_transaction_id = $CustomerToken->getTransactionId();
        $this->setCommonRegisterCard($CustomerToken);

        return true;
    }

    /**
     * 同じ受注ステータスかどうかを判定する
     *
     * @param int $id
     * @return bool true 入力されたIDと同じステータス
     */
    private function sameOrderStatus($id)
    {
        if (is_null($this->Order)){
            return false;
        }

        if (is_null($this->Order->getOrderStatus())){
            return false;
        }
        return $this->Order->getOrderStatus()->getId() == $id;
    }

    public function isOrderStatusPending()
    {
        return $this->sameOrderStatus(OrderStatus::PENDING);
    }

    public function isOrderStatusProcessing()
    {
        return $this->sameOrderStatus(OrderStatus::PROCESSING);
    }

    public function isOrderStatusReturned()
    {
        return $this->sameOrderStatus(OrderStatus::RETURNED);
    }

    public function isOrderStatusCancel()
    {
        return $this->sameOrderStatus(OrderStatus::CANCEL);
    }

    public function isOrderStatusNew()
    {
        return $this->sameOrderStatus(OrderStatus::NEW);
    }

}
