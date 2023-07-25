<?php

namespace Plugin\SoftbankPayment4\Factory;

use Plugin\SoftbankPayment4\Exception\SbpsException;

class SbpsExceptionFactory
{
    public function create($errorCode): SbpsException
    {
        $message = $this->parseErrorCodeToMessage($errorCode);
        return new SbpsException($message, $errorCode);
    }

    /**
     * 汎用メッセージ.
     */
    const GENERIC_MESSAGE = '決済操作でエラーが発生しました。';

    const CAPTURE_TARGET_NOT_EXIST      = '与信が存在しないため、売上処理を中止しました。';
    const CAPTURE_TARGET_CANCELED       = '与信取消済みのため、売上処理を中止しました。 ';
    const CAPTURE_UNNECESSARY           = 'お客様は自動売上の設定をご利用のため、お取引のため売上要求は不要です';
    const CAPTURE_AMOUNT_IS_EXCEEDED    = '指定金額が与信額を超えているため売上処理を中止しました。';
    const HAS_BEEN_CAPTURED             = '既に売上完了済みのため処理を中止しました';
    const REFUND_TARGET_NOT_EXIST       = '与信結果が存在しないため、取消返金処理を中止しました。';
    const REFUND_TARGET_CANCELED        = '与信取消済みのため、取消返金処理を中止しました。 ';
    const REFUND_TARGET_REFUNDED        = '返金処理済みのため、返金処理を中止しました。';

    /**
     * auかんたん決済用エラーメッセージ配列.
     */
    public static $auErrorMessages = [
        '20' => self::CAPTURE_TARGET_NOT_EXIST,
        '21' => self::CAPTURE_TARGET_CANCELED,
        '22' => self::CAPTURE_AMOUNT_IS_EXCEEDED,
        '23' => self::HAS_BEEN_CAPTURED,
        '24' => self::CAPTURE_UNNECESSARY,
        '25' => self::REFUND_TARGET_NOT_EXIST,
        '26' => self::REFUND_TARGET_CANCELED,
        '27' => '指定された月の売上データが存在しないため、返金処理を中止しました',
        '28' => '部分返金処理は、売上確定日の翌月から有効です。',
        '29' => 'au かんたん決済センターにて請求処理が未実施のため、返金処理を中止しました。',
        '30' => self::REFUND_TARGET_REFUNDED,
        '31' => '返金処理は、売上確定日から翌々月末日まで有効です。',
        '32' => 'ご指定の継続課金は既に解約済みです。',
        '33' => '売上確定可能期間を過ぎています。',
        '34' => self::GENERIC_MESSAGE,
        '36' => '既に売上処理中のため、売上処理を中止しました。',
        '37' => '既に売上処理中のため、取消処理を中止しました。 ',
        '38' => '既に返金処理中のため、返金処理を中止しました。',
        '40' => '自動売上の場合、返金処理を使用して下さい。 ',
        '41' => '返金処理は、本決済では使用できません。',
        '42' => '指定された金額が、与信時金額と違うため、売上処理を中止しました。 ',
        '43' => 'ご指定の決済は、お客様のご都合により与信取消されたため、売上処理を中止しました。',
        '75' => '指定された金額が、売上時金額を越えているため、返金処理を中止しました。',
        'L0' => '初回申込結果が存在しないため、継続課金処理を中止しました。',
        'L1' => '初回申込結果と加盟店顧客 ID が異なるため、継続課金処理を中止しました。',
        'L2' => '既に解約済みのため、継続課金処理を中止しました。',
        'L3' => 'ご指定の継続課金はご都合により利用できません。継続課金契約を解約しました。',
        'L4' => 'ご利用可能額を超えているか、au かんたん決済センターでエラーが検知されました｡しばらく経ってから操作して下さい｡',
        'L5' => '初回申込結果が存在しないため、解約処理を中止しました。',
        'L6' => '初回申込結果と加盟店顧客 ID が異なるため、解約処理を中止しました。',
        'L7' => '既に解約済みのため、解約処理を中止しました。',
        'U0' => '有効なユーザでありません。',
        'U1' => 'ユーザ様事由によるエラーです。',
    ];

    /**
     * エラーコードを解析してメッセージを返す。
     *
     * @var string $errorCode SBPSからのエラーコードのレスポンス.
     * @return string $message エラーメッセージ.
     */
    public function parseErrorCodeToMessage($errorCode): string
    {
        // エラーコードから理由コードを抽出
        $method = substr($errorCode,0, 3);
        $reason = substr($errorCode, 3, 2);

        switch ($method) {
            case '402':
                return self::$auErrorMessages[$reason] ?? self::GENERIC_MESSAGE;
            default:
                break;
        }

        switch ($reason) {
            case '37':
                $message = self::CAPTURE_UNNECESSARY;
                break;
            case '39':
            case '44':
            case '51':
                $message = self::CAPTURE_TARGET_NOT_EXIST;
                break;
            case '40':
            case '42':
            case '45':
            case '52':
                $message = self::CAPTURE_TARGET_CANCELED;
                break;
            case '41':
                $message = self::HAS_BEEN_CAPTURED;
                break;
            case '42':
            case '56':
                $message = '有効期限を過ぎているため、処理を中止しました。';
                break;
            case '46':
            case '71':
                $message = '返金済みのため処理を中止しました。';
                break;
            case '49':
                $message = '自動売上のお取引には返金要求をご利用ください。';
                break;
            case '79':
                $message = self::CAPTURE_AMOUNT_IS_EXCEEDED;
                break;
            case '90':
                $message = 'APIの処理中に想定外のエラーが発生しました。';
                break;
            case '95':
                $message = 'お客様情報に不整合が発生しました。';
            case '96':
                $message = '決済が重複して実行されました。';
                break;
            case 'K0':
                $message = '売上処理未実施のため部分返金を中止しました。';
                break;
            case 'K1':
                $message = '返金金額の合計が売上額を超えるため部分返金を中止しました。';
                break;
            default:
                $message = self::GENERIC_MESSAGE;
                break;
        }

        return $message;
    }
}
