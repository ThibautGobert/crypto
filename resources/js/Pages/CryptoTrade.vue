<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import Chart from 'chart.js/auto';
import 'chartjs-adapter-date-fns'; // Gestion des dates pour l'axe X
import { CandlestickController, CandlestickElement  } from 'chartjs-chart-financial';
import Echo from 'laravel-echo';
import axios from 'axios';

// 🛠️ Enregistrer les composants de Chart.js
Chart.register(CandlestickController, CandlestickElement );

const chartCanvas = ref(null);
let chart = null;
let candles = ref([]); // Stockage local des bougies
let currentPrice = ref(0)
let currentPriceColor = ref('black')
// 📌 Charger l'historique des bougies une seule fois
const loadCandles = async () => {
    const response = await axios.get('/crypto-trade/candles', {
        params: { cryptoType: 1, interval: 15 }
    });

    return response.data.map(c => ({
        x: c.period * 1000,//new Date(c.period * 1000), // Convertir le timestamp en objet Date
        o: parseFloat(c.open),       // Prix d'ouverture
        h: parseFloat(c.high),       // Prix le plus haut
        l: parseFloat(c.low),        // Prix le plus bas
        c: parseFloat(c.close)       // Prix de clôture
    }));
};


/*
const currentPrice = computed(() => {
    if (candles.value.length === 0) return 0;
    return candles.value[candles.value.length - 1].c;
});

 */



onMounted(async () => {
    await nextTick(); // Attendre que le DOM soit rendu

    if (!chartCanvas.value) {
        console.error("❌ Le canvas Chart.js n'est pas disponible !");
        return;
    }

    // 🟢 Charger l'historique des bougies au démarrage
    candles.value = await loadCandles();

    console.log("Données envoyées au graphique :", candles.value);

    // 🟢 Initialiser Chart.js avec ces bougies
    chart = new Chart(chartCanvas.value, {
        type: 'candlestick', // Type de graphique
        data: {
            datasets: [{
                label: 'BTC/USDT',
                data: candles.value,
                borderColor: 'black',
                color: {
                    up: 'green', // Bougies haussières
                    down: 'red', // Bougies baissières
                    unchanged: 'gray'
                },
                //barPercentage: 0.6,
                //categoryPercentage: 0.8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false // Désactiver la légende
                }
            },
            scales: {
                x: {
                    type: 'timeseries',
                    time: {
                        unit: 'minute',
                        stepSize: 15,
                        displayFormats: {
                            minute: 'HH:mm'
                        }
                    },
                    ticks: {
                        major: {
                            enabled: true,
                        },
                        color: '#ffffff',
                        source: 'auto',
                        maxRotation: 0,
                        autoSkip: true,
                        autoSkipPadding: 75,
                        sampleSize: 100
                    },
                    grid: {
                        color: '#131313' // Couleur de la grille (lignes) de l'axe X
                    }
                },
                y: {
                    type: 'linear',
                    ticks: {
                        color: '#ffffff' // Couleur des ticks (étiquettes) de l'axe Y
                    },
                    grid: {
                        color: '#131313' // Couleur de la grille (lignes) de l'axe Y
                    }
                }
            },
            layout: {
                backgroundColor: '#000000' // Fond du graphique (noir)
            }
        }
    });

    // 📡 Écouter les mises à jour en temps réel via WebSocket
    const echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY,
        wsHost: import.meta.env.VITE_REVERB_HOST,
        wsPort: import.meta.env.VITE_REVERB_PORT,
        wssPort: import.meta.env.VITE_REVERB_PORT,
        forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
        enabledTransports: ['ws', 'wss'],
    });

    let updateTimeout = null;

    const throttledChartUpdate = () => {
        if (!updateTimeout) {
            updateTimeout = setTimeout(() => {
                chart.update();
                updateTimeout = null;
                if (candles.value.length > 0) {
                    let lastPrice = currentPrice.value
                    currentPrice.value =  candles.value[candles.value.length - 1].c
                    if(currentPrice.value > lastPrice) {
                        currentPriceColor.value = 'green'
                    } else {
                        currentPriceColor.value = 'red'
                    }
                }
            }, 1500); // Limite à une mise à jour toutes les 500 ms
        }
    };

    echo.channel('crypto-trades')
        .listen('.bitcoin.trade.updated', (data) => {
            const lastCandle = candles.value[candles.value.length - 1];

            // Convertir le timestamp en entier Unix (millisecondes)
            const tradeTime = Date.parse(data.trade.timestamp); // Convertit "2025-02-02 15:18:33" en millisecondes
            const tradePrice = parseFloat(data.trade.price);

            // Vérifie si le trade appartient à la période de la dernière bougie
            if (tradeTime >= lastCandle.x && tradeTime < lastCandle.x + 15 * 60 * 1000) {
                const updatedCandle = {
                    ...lastCandle,
                    h: Math.max(lastCandle.h, tradePrice),
                    l: Math.min(lastCandle.l, tradePrice),
                    c: tradePrice
                };
                candles.value[candles.value.length - 1] = updatedCandle;
            } else {
                candles.value = [...candles.value, {
                    x: tradeTime,
                    o: tradePrice,
                    h: tradePrice,
                    l: tradePrice,
                    c: tradePrice
                }]
                chart.data.datasets[0].data = candles.value;
            }
            throttledChartUpdate()
        });
});
</script>

<template>
    <div style="position:relative;width: 100%;height: 100vh">
        <div style="width: 300px;position:absolute;top:0;right: 0; text-align: right;" :style="{color: currentPriceColor}">
            <digit-animation-group
                v-if="currentPrice"
                size="3em"
                format="00000.00"
                use-ease="Quit.easeInOut"
                stagger
                :digits="currentPrice"
                :duration="200"
            />
        </div>

        <canvas ref="chartCanvas" style="width: 100%; height: 100%"></canvas>
    </div>
</template>

<style scoped>
canvas {
    background-color: #000; /* Fond noir */
}
</style>
