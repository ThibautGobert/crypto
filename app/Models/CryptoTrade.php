<?php

namespace App\Models;

use App\Enums\CryptoType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoTrade extends Model
{
    use HasFactory;

    protected $fillable = ['crypto_type', 'price', 'quantity', 'maker', 'timestamp'];

    public $timestamps = false;

    protected $casts = [
        'crypto_type' => CryptoType::class,
    ];
}
