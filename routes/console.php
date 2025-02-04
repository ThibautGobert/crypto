<?php

use App\Console\Commands\CalculateCryptoCandles;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;


Schedule::command(CalculateCryptoCandles::class, ['--interval=15'])->everyMinute();
