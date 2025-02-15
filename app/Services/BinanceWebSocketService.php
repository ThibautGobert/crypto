<?php

namespace App\Services;

use App\Enums\CryptoType;
use App\Events\BitcoinTradeUpdated;
use App\Models\CryptoTrade;
use Illuminate\Broadcasting\Channel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use WebSocket\Client;

class BinanceWebSocketService
{
    public function listen()
    {
        try {
            // Connexion WebSocket Binance avec le nouveau client
            $client = new Client("wss://stream.binance.com:9443/ws/btcusdt@trade");

            Log::info("✅ Connexion réussie au WebSocket Binance...");

            while (true) {
                $message = $client->receive();

                $data = json_decode($message->getContent(), true);
                if (isset($data['p']) && isset($data['q'])) {
                    $price = floatval($data['p']);
                    $quantity = floatval($data['q']);
                    $timestamp = now();
                    $maker = $data['m']; // true = sell, false = buy

                    $trade = CryptoTrade::create([
                        'crypto_type' => CryptoType::BTC_USDT,
                        'price' => $price,
                        'quantity' => $quantity,
                        'maker' => $maker,
                        'timestamp' => $timestamp
                    ]);

                    broadcast(new BitcoinTradeUpdated($trade));
                   // Broadcast::broadcast([new Channel('crypto-trades')], ''new BitcoinTradeUpdated($trade));

                    Log::info("💰 Nouveau Trade : {$price} USDT pour {$quantity} BTC");
                }
            }

            $client->close();
        } catch (\Exception $e) {
            Log::error("❌ Erreur WebSocket Binance : " . $e->getMessage());
        }
    }
}
