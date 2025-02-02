<?php

namespace App\Console\Commands;

use App\Services\BinanceWebSocketService;
use Illuminate\Console\Command;

class StartBinanceReverb extends Command
{
    protected $signature = 'crypto:start-binance-reverb';
    protected $description = 'Démarrer l’écoute en temps réel du prix BTC via WebSocket Binance et diffuser via Reverb';

    public function handle()
    {
        $this->info("🚀 Connexion au WebSocket Binance avec Laravel Reverb...");

        $service = new BinanceWebSocketService();
        $service->listen();
    }
}
