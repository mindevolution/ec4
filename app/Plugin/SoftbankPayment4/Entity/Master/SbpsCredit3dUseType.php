<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

class SbpsCredit3dUseType
{
    public const SBPSCREDIT3D_USE_2 = 2;    // 利用する(2.0)
    public const SBPSCREDIT3D_USE   = 1;    // 利用する(1.0)
    public const SBPSCREDIT3D_NOUSE = 0;    // 利用しない

    public static $choice = [
        '利用する(1.0)' => self::SBPSCREDIT3D_USE,
        '利用する(2.0)' => self::SBPSCREDIT3D_USE_2,
        '利用しない' => self::SBPSCREDIT3D_NOUSE,
    ];

}

