<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Traits\EnumTrait;

enum CryptoType: int
{
    use EnumTrait;

    #[Description('BTC/USDT')]
    case BTC_USDT = 1;

    public static function fromURL(string $description): self
    {
        return match (mb_strtoupper($description)) {
            'BTC-USDT', 'btc-usdt' => self::BTC_USDT,
        };
    }
}
