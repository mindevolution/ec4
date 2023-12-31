<?php
/*
* Plugin Name : StripePaymentGateway
*
* Copyright (C) 2018 Subspire Inc. All Rights Reserved.
* http://www.subspire.co.jp/
*
* For the full copyright and license information, please view the LICENSE
* file that was distributed with this source code.
*/
namespace Plugin\StripePaymentGateway;

include_once(dirname(__FILE__).'/vendor/stripe/stripe-php/init.php');

use Eccube\Application;
use Eccube\Common\Constant;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Event\EventArgs;
use Eccube\Event\TemplateEvent;
use Exception;
use Stripe\Charge;
use Stripe\Stripe;
use Stripe\Refund;
use Stripe\Customer as StripeLibCustomer;
use Stripe\PaymentMethod;
use Stripe\PaymentIntent;
use Stripe\Exception\CardException;
use Stripe\Exception\ApiErrorException;
use Plugin\StripePaymentGateway\Entity;
use Plugin\StripePaymentGateway\Entity\StripeCustomer;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Plugin\StripePaymentGateway\Entity\StripeConfig;
use Symfony\Component\HttpFoundation\ParameterBag;

use Eccube\Entity\Payment;
use Eccube\Util\StringUtil;

class StripeClient
{
    public $zeroDecimalCurrencies = ["BIF", "CLP", "DJF", "GNF", "JPY", "KMF", "KRW", "MGA", "PYG", "RWF", "UGX", "VND", "VUV", "XAF", "XOF", "XPF"];

    public static $errorMessages = array(
        'card_declined'=>'カードを請求できませんでした。',
        'charge_already_captured'=>'実行しようとしてる請求はすでに実行が行われました。別IDで実行してください。',
        'charge_already_refunded'=>'返金しようとしてる請求はすでに変更が行われました。別IDで実行してください。',
        'charge_expired_for_capture'=>'オーソリがタイムアウトしたため請求することができませんでした。最初からやり直してください。',
        'expired_card'=>'こちらのカードは有効期限過ぎています。ご確認の上再度ご入力ください。',
        'incorrect_cvc'=>'セキュリティコードに間違いがあります。ご確認の上再度ご入力ください。',
        'incorrect_number'=>'カード番号に間違いがあります。ご確認の上再度ご入力ください。',
        'incorrect_zip'=>'カードの郵便番号に間違いがあります。ご確認の上再度ご入力ください。',
        'instant_payouts_unsupported'=>'ご入力していただいたカードは即請求に対応されていません。即請求に対応しているものをご利用ください。',
        'invalid_card_type'=>'誤入力していただいたカードはデビットカードではありません。デビットカードをご利用ください。',
        'invalid_charge_amount'=>'請求金額に間違いがあります。管理者にご連絡ください。',
        'invalid_cvc'=>'セキュリティコードに間違いがあります。ご確認の上再度ご入力ください。',
        'invalid_expiry_month'=>'カードの有効期限の「月」に間違いがあります。ご確認の上再度ご入力ください。',
        'invalid_expiry_year'=>'カードの有効期限の「年」に間違いがあります。ご確認の上再度ご入力ください。',
        'invalid_number'=>'カード番号に間違いがあります。ご確認の上再度ご入力ください。',
        'token_already_used'=>'こちらのトークンは利用済みです。最初からやり直してください。',
        'token_in_use'=>'こちらのトークンは別リクエストで利用中です。同じトークンで複数リクエストを行う場合にこちらのエラーが表示されます。',
        'parameter_invalid_integer'=>'金額は整数値でなければなりません',
//        '' => 'カード認証に失敗しました。ご確認の上再度ご入力ください。'
    );

    public function __construct($stripe_secret_key)
    {
        $pluginInfo = self::getPluginInfo();
        $pluginVersion = ($pluginInfo && isset($pluginInfo->version)) ? $pluginInfo->version : '1.1.7';
        Stripe::setAppInfo(
            'EC-CUBE 4 Stripe決済プラグイン',
            $pluginVersion,
            'https://subspire.co.jp',
            'pp_partner_H5BHbixrrQxUvA'
        );
        Stripe::setApiKey($stripe_secret_key);
    }

    public static function getPluginInfo()
    {
        $composer_path = __DIR__ . "/composer.json";
        try {
            if (file_exists($composer_path)) {
                $composer = file_get_contents($composer_path);
                return json_decode($composer);
            }
            return false;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getAmountToSentInStripe($amount, $currency)
    {
        if(!in_array($currency, $this->zeroDecimalCurrencies)){
            return (int)($amount*100);
        }
        return (int)$amount;
    }

    public function createChargeWithToken($amount, $stripeToken, $orderId, $capture,$currency='JPY') {

        $params = array(
            'amount' => $this->getAmountToSentInStripe($amount,$currency),
            'currency' => $currency,
            'source' => $stripeToken,
            'metadata' => array(
                'order' => $orderId
            ),
            'capture' => $capture,
        );
        try {
            return Charge::create($params);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function createChargeWithCustomer($amount, $stripeCustomerId, $orderId, $capture,$currency='JPY') {
        $params = array(
            'amount' => $this->getAmountToSentInStripe($amount,$currency),
            'currency' => $currency,
            'customer' => $stripeCustomerId,
            'metadata' => array(
                'order' => $orderId
            ),
            'capture' => $capture,
        );
        try {
            return Charge::create($params);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function createCustomer($customer_email,$stripeToken,$customer_id=0,$order_id=0) {
        $params['email'] = $customer_email;
        $params['source'] = $stripeToken;
        if($customer_id) {
            $params['metadata'] = array('customer_id' => $customer_id);
        } else if($order_id){
            $params['metadata'] = array('order_id' => $order_id);
        }

        try {
            $stripeLibCustomer=StripeLibCustomer::create($params);
            return $stripeLibCustomer->id;
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function updateCustomer($stripeCustomerId,$customer_email,$stripeToken) {
        try {
            $customerToUpdate=StripeLibCustomer::retrieve($stripeCustomerId);
            $customerToUpdate->email = $customer_email;
            $customerToUpdate->source = $stripeToken;
            $customerToUpdate->save();
            return true;
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function retrieveCharge($chargeId) {
        try {
            return Charge::retrieve($chargeId);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function createRefund($chargeId,$refund_amount=0,$currency='JPY') {
        try {
            if($refund_amount>0){
                return Refund::create(["charge" => $chargeId,'amount' =>$this->getAmountToSentInStripe($refund_amount,$currency)]);
            } else {
                return Refund::create(["charge" => $chargeId]);
            }
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function retrieveChargeByCustomer($stripeCustomerId) {
        try {
            return Charge::all(["customer"=>$stripeCustomerId]);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function retrieveCustomer($stripeCustomerId) {
        try {
            return StripeLibCustomer::retrieve(["id" => $stripeCustomerId, "expand" => ["default_source"]]);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public static function getErrorMessageFromCode($error, $locale) {
        if (isset($error['code']) && isset(self::$errorMessages[$error['code']])) {
            $message = self::$errorMessages[$error['code']];
            //$out_message = $locale == 'ja' ? $message : str_replace('_', ' ', $error['code']);
            $out_message = $locale == 'ja' ? $message : $error['message'];
            return $out_message;
        } else if(isset($error['message'])){
            return $error['message'];
        }
        return StripePaymentGatewayEvent::getLocalizedString('stripe_payment_gateway.front.unexpected_error', $locale);
    }

    //for 3ds2
    public function isPaymentIntentId($paymentIntentId) {
        if( !empty($paymentIntentId) && substr($paymentIntentId, 0, 3) == "pi_" ) {
            return true;
        } else {
            return false;
        }
    }

    public function isStripeToken($token) {
        if( !empty($token) && substr($token, 0, 4) == "tok_" ) {
            return true;
        } else {
            return false;
        }
    }

    //for 3ds2
    public function isPaymentMethodId($paymentMethodId) {
        if( !empty($paymentMethodId) && substr($paymentMethodId, 0, 3) == "pm_" ) {
            return true;
        } else {
            return false;
        }
    }

    //for 3ds2
    public function createPaymentIntentWithCustomer($amount, $paymentMethodId, $orderId, $isSaveCardOn, $stripeCustomerId,$currency='JPY') {
        try {
            $params = [
                'amount' => $this->getAmountToSentInStripe($amount,$currency),
                'currency' => $currency,
                'payment_method' => $paymentMethodId,
                'confirmation_method' => 'automatic',
                'capture_method' => 'manual',
                'confirm' => true,
                'metadata' => array(
                    'order' => $orderId
                ),
                'description' => '' . $orderId
            ];

            $params['customer'] = $stripeCustomerId;
//            $params['save_payment_method'] = true;
            $params['save_payment_method'] = $isSaveCardOn? true:false;
            $params['setup_future_usage'] = 'off_session';
            log_info("stripe: createPaymentIntentWithCustomer", $params);
            return PaymentIntent::create($params);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    public function createKonbiniIntentWithCustomer(Order $Order, string $description, $currency = 'JPY') 
    {
        try {
            return PaymentIntent::create([
                'amount'    =>  round($Order->getTotal()),
                'currency'  =>  $currency,
                'payment_method_types' => ['konbini'],
                'payment_method_options'=> [
                    'konbini'   =>  [
                        'product_description'   =>  StringUtil::ellipsis($description, 19), // Stripe description support most 22 characters
                        'expires_after_days'    =>  3
                    ]
                ],
                'metadata'  =>  [
                    'order_id'  =>  $Order->getId(),
                ]
            ]);
        } catch(Exception $e) {
            return $e->getJsonBody();
        }
    }

    //for 3ds2
    public function createCustomerV2($customer_email, $customer_id=0, $order_id=0) {
        $params['email'] = $customer_email;
        if($customer_id) {
            $params['metadata'] = array('customer_id' => $customer_id);
        } else if($order_id){
            $params['metadata'] = array('order_id' => $order_id);
        }

        try {
            $stripeLibCustomer=StripeLibCustomer::create($params);
            return $stripeLibCustomer->id;
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    //for 3ds2
    public function updateCustomerV2($stripeCustomerId, $customer_email) {
        try {
            $customerToUpdate=StripeLibCustomer::retrieve($stripeCustomerId);
            $customerToUpdate->email = $customer_email;
            $customerToUpdate->save();
            return true;
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    //for 3ds2
    public function retrievePaymentIntent($paymentIntentId) {
        try {
            return PaymentIntent::retrieve($paymentIntentId);
        } catch (Exception $e) {
            // return $e->getJsonBody();
            log_info("StripeClient---retrievePaymentIntent");
            log_error($e);
        }
    }

    public function cancelPaymentIntent($paymentIntentId) {
        try {
            $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            if($paymentIntent) {
                $paymentIntent->cancel();
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function capturePaymentIntent($paymentIntentId, $amount, $currency='JPY') {
        try {
            $amount = $this->getAmountToSentInStripe($amount, $currency);
            if( $paymentIntentId instanceof PaymentIntent ) {
                $paymentIntent = $paymentIntentId;
            }  else {
                $paymentIntent = PaymentIntent::retrieve($paymentIntentId);
            }
            $params = array('amount_to_capture' => (int)$amount);
            log_info("stripe: capturePaymentIntent", $params);
            $paymentIntent->capture($params);
            return $paymentIntent;
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    //for 3ds2
    public function retrievePaymentIntentByCustomer($stripeCustomerId) {
        try {
            return PaymentIntent::all(["customer"=>$stripeCustomerId]);
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }

    //for 3ds2
    public function retrieveLastPaymentMethodByCustomer($tripeCustomerId) {
        try {
            $methods = PaymentMethod::all([
                'customer' => $tripeCustomerId,
                'type' => 'card',
                'limit' => 1,
            ]);
            foreach($methods as $method) {
                return $method;
            }
            return false;
        } catch (Exception $e) {
            return $e->getJsonBody();
        }
    }
    public function retrievePaymentMethod($payment_method_id){
        try{
            return PaymentMethod::retrieve($payment_method_id);
        }catch(\Exception $e){
            return $e->getJsonBody();
        }
    }
    public function detachMethod($payment_method_id){
        try{
            $method = PaymentMethod::retrieve($payment_method_id);
            if($method){
                $method->detach();
            }
        }catch(\Exception $e){
            return $e->getJsonBody();
        }
    }
}
