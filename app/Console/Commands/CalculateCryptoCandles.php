<?php

namespace App\Console\Commands;

use App\Enums\CryptoType;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateCryptoCandles extends Command
{
    protected $signature = 'crypto:calculate-candles {--interval=15}';
    protected $description = 'Calculer les bougies pour chaque crypto et insérer dans la table crypto_candles';

    public function handle()
    {
        $interval = $this->option('interval');
        $cryptoTypes = CryptoType::cases(); // Récupère tous les types de cryptos définis dans l'enum

        foreach ($cryptoTypes as $cryptoType) {
            $this->info("Calcul des bougies pour {$cryptoType->name} avec un intervalle de {$interval} minutes...");

            // Récupérer la dernière période calculée
            $lastPeriod = $this->getLastPeriod($cryptoType->value);

            // Si aucune bougie n'existe encore, calculer les dernières 24 heures
            $startTime = $lastPeriod ?: now()->subHours(24)->timestamp;

            // Requête pour les nouvelles données
            $query = sprintf("
                WITH grouped_trades AS (
                    SELECT
                        FLOOR(UNIX_TIMESTAMP(timestamp) / (%d * 60)) * (%d * 60) AS period,
                        MIN(price) AS low,
                        MAX(price) AS high
                    FROM crypto_trades
                    WHERE crypto_type = %d
                    AND timestamp >= FROM_UNIXTIME(%d) -- Filtrer uniquement les nouvelles périodes
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
                    AND timestamp >= FROM_UNIXTIME(%d) -- Filtrer uniquement les nouvelles périodes
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
            ", $interval, $interval, $cryptoType->value, $startTime, $interval, $interval, $interval, $interval, $cryptoType->value, $startTime);

            // Exécution directe sans placeholders
            $candles = DB::select($query);

            // Insérer les résultats dans la table crypto_candles
            foreach ($candles as $candle) {
                DB::table('crypto_candles')->updateOrInsert(
                    ['crypto_type' => $cryptoType->value, 'period' => $candle->period],
                    [
                        'open' => $candle->open,
                        'high' => $candle->high,
                        'low' => $candle->low,
                        'close' => $candle->close,
                        'updated_at' => now()
                    ]
                );
            }

            $this->info("Bougies pour {$cryptoType->name} calculées et insérées avec succès !");
        }

        $this->info('Tous les calculs sont terminés.');
    }

    protected function getLastPeriod(int $cryptoType): ?int
    {
        return DB::table('crypto_candles')
            ->where('crypto_type', $cryptoType)
            ->max('period'); // Récupère la période la plus récente
    }
}
