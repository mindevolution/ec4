<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

use Plugin\SoftbankPayment4\Entity\Master\SbpsStatusType as StatusType;

class SbpsActionType
{
    /**
     *  Action Types.
     */
    public const CAPTURE            = 1;
    public const CAPTURE_PARTICAL   = 2;
    public const PARTICAL_REFUND    = 3;
    public const REAUTH             = 4;
    public const MODIFY_AUTH        = 5;
    public const REFUND             = 6;


    /**
     * Disp Name.
     */
    public static $disp_name = [
        self::CAPTURE           => '売上',
        self::CAPTURE_PARTICAL  => '金額変更',
        self::PARTICAL_REFUND   => '金額変更',
        self::REAUTH            => '再与信',
        self::MODIFY_AUTH       => '金額変更',
    ];

    /**
     * 取引の状態から、決済操作を取得する.
     *
     * @param $Trade
     * @param $captureType
     * @return int|null
     */
    public static function getActionType($Trade, $captureType): ?int
    {
        $payMethodClass = $Trade->getOrder()->getPayment()->getMethodClass();

        switch($payMethodClass) {
            case PayMethodType::$class[PayMethodType::CREDIT]:
            case PayMethodType::$class[PayMethodType::CREDIT_API]:
                return $captureType === CaptureType::AR ? self::getCreditAction($Trade) : null;
            case PayMethodType::$class[PayMethodType::SB]:
                return $captureType === CaptureType::AR ? self::getSBAction($Trade) : null;
            case PayMethodType::$class[PayMethodType::DOCOMO]:
            case PayMethodType::$class[PayMethodType::AU]:
                return self::getOtherCareerAction($Trade, $captureType);
            case PayMethodType::$class[PayMethodType::UNION_PAY]:
                return self::getUnionPayAction($Trade);
            case PayMethodType::$class[PayMethodType::CVS_DEFERRED]:
            default:
                return null;
        }
    }

    /**
     * クレジットカードによる取引の状態から、決済操作を取得する.
     *
     * @param $Trade
     * @return int|null
     */
    private static function getCreditAction($Trade): ?int
    {
        switch ($Trade->getStatus()) {
            case StatusType::CANCELED:
            case StatusType::REFOUND:
            case StatusType::EXPIRED:
                return self::REAUTH;
            case StatusType::ARED:
                $eval = $Trade->getOrder()->getPaymentTotal() <=> $Trade->getAuthorizedAmount();
                if($eval === 0) {
                    return self::CAPTURE;
                }
                if($eval === -1) {
                    return self::CAPTURE_PARTICAL;
                }
                if($eval === 1) {
                    return self::MODIFY_AUTH;
                }
                return null;

            case StatusType::CAPTURED:
                $eval = $Trade->getOrder()->getPaymentTotal() <=> $Trade->getCapturedAmount();
                if ($eval === 0) {
                    return null;
                }
                if ($eval === -1) {
                    return self::PARTICAL_REFUND;
                }
                if ($eval === 1) {
                    return self::MODIFY_AUTH;
                }
                return null;

            default:
                return null;
        }
    }

    private static function getSBAction($Trade): ?int
    {
        switch ($Trade->getStatus()) {
            case StatusType::ARED:
                $eval = $Trade->getOrder()->getPaymentTotal() <=> $Trade->getAuthorizedAmount();
                if ($eval === 0) {
                    return self::CAPTURE;
                }
                if($eval === -1) {
                    return self::CAPTURE_PARTICAL;
                }
                return null;
            case StatusType::CAPTURED:
            case StatusType::CANCELED:
            case StatusType::REFOUND:
            case StatusType::EXPIRED:
            case StatusType::FAILED:
            default:
                return null;
        }
    }

    private static function getUnionPayAction($Trade): ?int
    {
        if($Trade->getStatus() === StatusType::CAPTURED && $Trade->getOrder()->getPaymentTotal() < $Trade->getCapturedAmount()) {
            return self::PARTICAL_REFUND;
        }
        return null;
    }

    private static function getOtherCareerAction($Trade, $captureType): ?int
    {
        if ($captureType === CaptureType::CAPTURE) {
            if ($Trade->getStatus() === StatusType::CAPTURED && $Trade->getOrder()->getPaymentTotal() < $Trade->getCapturedAmount()) {
                return self::PARTICAL_REFUND;
            }
            return null;
        }

        switch ($Trade->getStatus()) {
            case StatusType::ARED:
                $eval = $Trade->getOrder()->getPaymentTotal() <=> $Trade->getAuthorizedAmount();
                if ($eval === 0) {
                    return self::CAPTURE;
                }
                if($eval === -1) {
                    return self::CAPTURE_PARTICAL;
                }
                return null;
            case StatusType::CAPTURED:
                if ($Trade->getOrder()->getPaymentTotal() < $Trade->getCapturedAmount()) {
                    return self::PARTICAL_REFUND;
                }
                return null;
            case StatusType::CANCELED:
            case StatusType::REFOUND:
            case StatusType::EXPIRED:
            case StatusType::FAILED:
            default:
                return null;
        }
    }
}
