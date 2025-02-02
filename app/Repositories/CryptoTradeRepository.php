<?php

namespace App\Repositories;

use App\Enums\CryptoType;
use Illuminate\Support\Facades\DB;

class CryptoTradeRepository
{
    public function getCandles(CryptoType $cryptoType, $interval = 15)
    {
        // Vérifie si les données pré-calculées sont disponibles
        $candles = DB::table('crypto_candles')
            ->select('period', 'low', 'high', 'open', 'close')
            ->where('crypto_type', $cryptoType->value)
            ->where('period', '>=', now()->subHours(24)->timestamp) // Dernières 24 heures
            ->orderBy('period', 'asc')
            ->get();

        // Si aucune donnée pré-calculée n'est trouvée, utiliser l'ancienne méthode
        if ($candles->isEmpty()) {
            return $this->calculateCandlesFallback($cryptoType, $interval);
        }

        return $candles;
    }

    protected function calculateCandlesFallback(CryptoType $cryptoType, $interval)
    {
        $query = "
        WITH grouped_trades AS (
            SELECT
                FLOOR(UNIX_TIMESTAMP(timestamp) / (%d * 60)) * (%d * 60) AS period,
                MIN(price) AS low,
                MAX(price) AS high
            FROM crypto_trades
            WHERE crypto_type = %d
            AND timestamp >= NOW() - INTERVAL 24 HOUR
            GROUP BY period
        ),
        first_and_last AS (
            SELECT DISTINCT
                FLOOR(UNIX_TIMESTAMP(timestamp) / (%d * 60)) * (%d * 60) AS period,
                FIRST_VALUE(price) OVER (
                    PARTITION BY FLOOR(UNIX_TIMESTAMP(timestamp) / (%d * 60))
                    ORDER BY timestamp ASC
                ) AS open,
                LAST_VALUE(price) OVER (
                    PARTITION BY FLOOR(UNIX_TIMESTAMP(timestamp) / (%d * 60))
                    ORDER BY timestamp ASC
                    ROWS BETWEEN UNBOUNDED PRECEDING AND UNBOUNDED FOLLOWING
                ) AS close
            FROM crypto_trades
            WHERE crypto_type = %d
            AND timestamp >= NOW() - INTERVAL 24 HOUR
        )
        SELECT
            g.period,
            g.low,
            g.high,
            f.open,
            f.close
        FROM grouped_trades g
        JOIN first_and_last f ON g.period = f.period
        ORDER BY g.period ASC
    ";

        // Insérer les valeurs directement dans la requête
        $query = sprintf($query, $interval, $interval, $cryptoType->value, $interval, $interval, $interval, $interval, $cryptoType->value);

        // Exécuter la requête brute
        return DB::select($query);
    }
}
