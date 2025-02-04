<?php

namespace App\Models;

use App\Enums\CryptoType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CryptoTrade extends Model
{
    use HasFactory;

    protected $fillable = ['crypto_type', 'price', 'quantity', 'maker', 'timestamp'];

    public $timestamps = false;

    protected $casts = [
        'crypto_type' => CryptoType::class,
        'timestamp' => 'datetime'
    ];

    protected $appends = ['period'];


    public function getPeriod(int $interval)
    {
        return floor($this->timestamp->timestamp / ($interval * 60)) * ($interval * 60);
    }

    public function period(): Attribute
    {
        return Attribute::make(
            get: function() {
                return $this->getPeriod(15);
            }
        );
    }
}
