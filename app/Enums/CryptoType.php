<?php

namespace App\Enums;

use App\Enums\Attributes\Description;
use App\Enums\Traits\EnumTrait;

enum CryptoType: int
{
    use EnumTrait;

    #[Description('BitCoin')]
    case BTC = 1;
}
