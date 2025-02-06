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
let isFullScreen = ref(false)

const loadCandles = async () => {
    const response = await axios.get('/crypto-trade/candles', {
        params: { cryptoType: 1, interval: 15 }
    });

    return response.data.map(c => ({
        x: c.period * 1000,
        o: parseFloat(c.open),       // Prix d'ouverture
        h: parseFloat(c.high),       // Prix le plus haut
        l: parseFloat(c.low),        // Prix le plus bas
        c: parseFloat(c.close)       // Prix de clôture
    }))
}

const toggleFullScreen = ()=> {
    const element = document.getElementById("chart-wrapper")
    if (!document.fullscreenElement) {
        isFullScreen.value = true
        element?.requestFullscreen().catch((err) => {
            isFullScreen.value = false
            console.error("Erreur lors de l'activation du plein écran :", err)
        });
        return
    }

    document.exitFullscreen()
        .then(()=> isFullScreen.value = false)
        .catch((err) => {
            isFullScreen.value = true
            console.error("Erreur lors de la sortie du plein écran :", err);
        });
}
const handleFullscreenChange = ()=> {
    if(!document.fullscreenElement) {
        isFullScreen.value = false
    }
}

const resizeChart = () => {
    if (chart) {
        console.log('resize chart')
        chart.resize();
    }
};

onMounted(async () => {
    await nextTick();
    const element = document.getElementById("chart-wrapper")
    element.addEventListener('fullscreenchange', handleFullscreenChange)

    if (!chartCanvas.value) {
        console.error("❌ Le canvas Chart.js n'est pas disponible !");
        return;
    }

    candles.value = await loadCandles();

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
            }]
        },
        options: {
           // responsive: true,
            plugins: {
                legend: {
                    display: false
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
                        color: '#131313'
                    }
                },
                y: {
                    type: 'linear',
                    ticks: {
                        color: '#ffffff'
                    },
                    grid: {
                        color: '#131313'
                    }
                }
            },
            layout: {
                backgroundColor: '#000000'
            }
        }
    });

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
            }, 1500)
        }
    };

    echo.channel('crypto-trades')
        .listen('.bitcoin.trade.updated', (data) => {
            const tradePeriod = data.trade.period * 1000; // ✅ Période alignée avec la DB
            const tradePrice = parseFloat(data.trade.price);

            // Vérifie si une bougie pour cette période existe déjà
            const lastCandleIndex = candles.value.findIndex(c => c.x === tradePeriod);

            if (lastCandleIndex !== -1) {
                // 🟢 Mettre à jour la dernière bougie
                candles.value[lastCandleIndex].h = Math.max(candles.value[lastCandleIndex].h, tradePrice);
                candles.value[lastCandleIndex].l = Math.min(candles.value[lastCandleIndex].l, tradePrice);
                candles.value[lastCandleIndex].c = tradePrice;
            } else {
                // 🔴 Ajouter une nouvelle bougie
                candles.value.push({
                    x: tradePeriod, // ✅ Nouvelle période bien alignée
                    o: tradePrice,
                    h: tradePrice,
                    l: tradePrice,
                    c: tradePrice
                });
                candles.value.shift()

                // ✅ Vérification avant de supprimer une bougie
                /*
                if (candles.value.length > 1) {
                    const oldestCandleTime = candles.value[0]?.x;
                    const twentyFourHoursAgo = Date.now() - (24 * 60 * 60 * 1000);

                    if (oldestCandleTime && oldestCandleTime < twentyFourHoursAgo) {
                        candles.value.shift();
                    }
                }

                 */
            }

            // ✅ Vérifier que candles.value n'est pas vide avant d'affecter les données au graphique
            if (candles.value.length > 0) {
                chart.data.datasets[0].data = [...candles.value]; // Clonage pour éviter les problèmes de réactivité
                throttledChartUpdate();
            }
        });

});
</script>

<template>
    <div id="chart-wrapper" style="position:relative;width: 100%;height: 100vh">
        <div v-if="!isFullScreen" style="width: 30px; height: 30px; position: absolute;right: 5px;bottom:5px;cursor:pointer;" @click="toggleFullScreen">
            <svg style="fill: white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M200 32L56 32C42.7 32 32 42.7 32 56l0 144c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l40-40 79 79-79 79L73 295c-6.9-6.9-17.2-8.9-26.2-5.2S32 302.3 32 312l0 144c0 13.3 10.7 24 24 24l144 0c9.7 0 18.5-5.8 22.2-14.8s1.7-19.3-5.2-26.2l-40-40 79-79 79 79-40 40c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8l144 0c13.3 0 24-10.7 24-24l0-144c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2l-40 40-79-79 79-79 40 40c6.9 6.9 17.2 8.9 26.2 5.2s14.8-12.5 14.8-22.2l0-144c0-13.3-10.7-24-24-24L312 32c-9.7 0-18.5 5.8-22.2 14.8s-1.7 19.3 5.2 26.2l40 40-79 79-79-79 40-40c6.9-6.9 8.9-17.2 5.2-26.2S209.7 32 200 32z"/></svg>
        </div>
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
    width: 100% !important;
    height: 100% !important;
    display: block;
}
</style>
