<?php

namespace Plugin\SoftbankPayment4\Entity\Master;

class SbpsTradeType
{
    public const AR      = 1;
    public const CAPTURE = 2;
    public const CANCEL  = 3;
    public const REFUND  = 4;
    public const REAUTH  = 5;
    public const DEPOSIT = 6;
    public const EXPIRED = 7;
    public const COMMIT  = 8;
}
