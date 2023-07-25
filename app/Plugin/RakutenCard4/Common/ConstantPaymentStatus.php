<?php

namespace Plugin\RakutenCard4\Common;

/**
 * 決済ステータス用の定数
 */
class ConstantPaymentStatus{
    const First = 1; // 未決済
    const Canceled = 2; // 取り消し
    const ExpiredCvs = 3; // 入金期限切れ
    const Authorized = 10; // 与信
    const Captured = 20; // 売上
    const Pending = 30; // 保留
}
