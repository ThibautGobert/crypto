<script setup>
import { ref, onMounted, nextTick, computed } from 'vue';
import Chart from 'chart.js/auto';
import 'chartjs-adapter-date-fns'; // Gestion des dates pour l'axe X
import { CandlestickController, CandlestickElement  } from 'chartjs-chart-financial';
import Echo from 'laravel-echo';
import axios from 'axios';
import CurrentDateTime from '@/Components/CurrentDateTime.vue';
import { usePage } from '@inertiajs/vue3';
import Rocket from '@/Components/Rocket.vue';
import {gsap} from 'gsap'

// 🛠️ Enregistrer les composants de Chart.js
Chart.register(CandlestickController, CandlestickElement );

const chartCanvas = ref(null);
const rocketContainer = ref(null)
let chart = null;
let candles = ref([]); // Stockage local des bougies
let currentPrice = ref(0)
let currentPriceRealTime = ref(0)
let currentPriceColor = ref('black')
let isFullScreen = ref(false)
let loadingCandles = ref(false)
const page = usePage()
const env = computed(() => page.props.env)

const loadCandles = async () => {
    /*
    const response = await axios.get(env.value.APP_PRODUCTION_URL + '/candles', {
        params: { cryptoType: 1, interval: 15 }
    });

     */
    const response = await axios.get( '/crypto/candles', {
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

const updateRocketPosition = () => {
    if (!chart || !chartCanvas.value || candles.value.length < 1) return;
    const lastCandle = candles.value[candles.value.length - 1];
    const xScale = chart.scales.x;
    const yScale = chart.scales.y;

    // Position horizontale basée sur la position X de la bougie
    const posX = xScale.getPixelForValue(lastCandle.x);

    // Calcul de la position verticale sur la bougie :
    // On récupère la position en pixels du haut et du bas de la bougie
    const candleHighY = yScale.getPixelForValue(lastCandle.h);
    const candleLowY = yScale.getPixelForValue(lastCandle.l);

    // Calculer un ratio t basé sur le prix actuel dans la plage [bougie basse, bougie haute]
    // t = 0 si currentPrice vaut le prix le plus bas, t = 1 si currentPrice vaut le prix le plus haut
    let t = (currentPriceRealTime.value - lastCandle.l) / (lastCandle.h - lastCandle.l);
    t = Math.max(0, Math.min(t, 1)); // Clamp entre 0 et 1

    // Interpoler la position Y
    // Attention : sur le canvas, une valeur Y plus petite correspond à une position plus haute.
    // Ainsi, si t = 0, posY = candleLowY (position basse) et si t = 1, posY = candleHighY (position haute)
    const posY = candleLowY + (candleHighY - candleLowY) * t;

    // Récupérer l'élément DOM réel de la fusée (si la ref est sur un composant, on passe par $el)
    const rocketEl = rocketContainer.value.$el || rocketContainer.value;
    const rocketHeight = rocketEl.offsetHeight || 0;
    const rocketWidth = rocketEl.offsetWidth || 0;

    // --- Calcul de la rotation ---
    // Ici, on se base sur la différence entre le prix actuel et le prix d'ouverture de la bougie
    const delta = currentPriceRealTime.value - lastCandle.o;
    let rotation = -1.5 * delta;
    rotation = Math.max(Math.min(rotation, 120), -45);

    gsap.to(rocketEl, {
        duration: 0.2,
        ease: 'power1.in',
        x: posX + rocketWidth / 2,
        y: posY - rocketHeight,
        rotation: rotation
    });
}

onMounted(async () => {
    await nextTick();
    const element = document.getElementById("chart-wrapper")
    element.addEventListener('fullscreenchange', handleFullscreenChange)

    if (!chartCanvas.value) {
        console.error("❌ Le canvas Chart.js n'est pas disponible !");
        return;
    }

    candles.value = await loadCandles();
    /*
    Chart.register({
        id: 'lastCandleLines',
        afterDraw: (chart, args, options) => {

            const ctx = chart.ctx;
            const xScale = chart.scales.x;
            const yScale = chart.scales.y;

            // Récupérer la dernière bougie (vérifiez que candles.value est accessible ici)
            const lastCandle = candles.value[candles.value.length - 1];
            if (!lastCandle) return;
            console.log(lastCandle);

            // Utilisez la bonne propriété pour le timestamp (t ou x, selon vos données)
            const xPixel = xScale.getPixelForValue(lastCandle.x); // ou lastCandle.t si c'est le cas
            const yPixel = yScale.getPixelForValue(lastCandle.c);
            console.log(xPixel);
            console.log(yPixel);

            ctx.save();
            ctx.strokeStyle = options.color || '#131313';
            ctx.lineWidth = options.lineWidth || 1;

            // Dessiner la ligne verticale : du haut à du bas de l'axe y
            ctx.beginPath();
            ctx.moveTo(xPixel, yPixel);
            ctx.lineTo(xPixel, yScale.bottom);
            ctx.stroke();

            // Dessiner la ligne horizontale : de la bougie jusqu'au bord droit de la zone de dessin
            ctx.beginPath();
            ctx.moveTo(xPixel, yPixel);
            ctx.lineTo(chart.chartArea.left, yPixel);
            ctx.stroke();

            ctx.restore();
        }
    });

     */

    chart = new Chart(chartCanvas.value, {
        type: 'candlestick',
        data: {
            datasets: [{
                label: 'BTC/USDT',
                data: candles.value,
                borderColor: 'black',
                color: {
                    up: 'green',
                    down: 'red',
                    unchanged: 'gray'
                },
            }]
        },
        options: {
           // responsive: true,
            plugins: {
                legend: {
                    display: false
                },
                /*
                lastCandleLines: {
                    color: 'rgba(255,255,255, 0.5)',      // Couleur des lignes
                    lineWidth: 1       // Épaisseur des lignes
                }

                 */
            },
            scales: {
                x: {
                    type: 'timeseries',
                    //grace: '30%',
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
                    },
                    grid: {
                        display: false,
                        /*
                        color: '#131313',
                        drawTicks: false,
                        drawOnChartArea: true

                         */
                    },
                },
                y: {
                    type: 'linear',
                    grace: 100,
                    ticks: {
                        color: '#ffffff'
                    },
                    grid: {
                        display: false,
                        //color: '#131313'
                    },
                }
            },
            layout: {
                backgroundColor: '#000000',
                padding: {
                    left: 0,
                    right: 150,
                    top: 0,
                    bottom: 0
                }

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

    const throttledPriceUpdate = () => {
        if (!updateTimeout) {
            updateTimeout = setTimeout(() => {
                updateTimeout = null;
                if (candles.value.length > 0) {
                    let lastPrice = currentPrice.value
                    currentPrice.value =  candles.value[candles.value.length - 1].c
                    if(currentPrice.value > lastPrice) {
                        currentPriceColor.value = 'green'
                    } else if(currentPrice.value < lastPrice) {
                        currentPriceColor.value = 'red'
                    } else {
                        currentPriceColor.value = 'white'
                    }

                }
            }, 1500)
        }
    };

    echo.channel('crypto-trades')
        .listen('.bitcoin.trade.updated', async (data) => {

            if (!loadingCandles.value) {
                const tradePeriod = data.trade.period * 1000;
                const tradePrice = parseFloat(data.trade.price);
                currentPriceRealTime.value = tradePrice

                const lastCandleIndex = candles.value.findIndex(c => c.x === tradePeriod);

                if (lastCandleIndex !== -1) {
                    candles.value[lastCandleIndex].h = Math.max(candles.value[lastCandleIndex].h, tradePrice);
                    candles.value[lastCandleIndex].l = Math.min(candles.value[lastCandleIndex].l, tradePrice);
                    candles.value[lastCandleIndex].c = tradePrice;
                    chart.data.datasets[0].data = [...candles.value];
                    chart.update()
                    throttledPriceUpdate();
                } else {
                    loadingCandles.value = true
                    setTimeout(async ()=> {
                        candles.value = await loadCandles()
                        chart.data.datasets[0].data = [...candles.value];
                        chart.update()
                        throttledPriceUpdate();
                        loadingCandles.value = false
                    }, 1500)
                }
            }
            updateRocketPosition();
        });
});
</script>

<template>
    <div id="chart-wrapper" style="position:relative;width: 100%;height: 100vh;background-color: black; overflow: hidden">
        <div ref="rocketContainer" class="rocket-container">
            <rocket />
        </div>
        <div v-if="!isFullScreen" style="width: 30px; height: 30px; position: absolute;right: 5px;bottom:5px;cursor:pointer;" @click="toggleFullScreen">
            <svg style="fill: white" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M200 32L56 32C42.7 32 32 42.7 32 56l0 144c0 9.7 5.8 18.5 14.8 22.2s19.3 1.7 26.2-5.2l40-40 79 79-79 79L73 295c-6.9-6.9-17.2-8.9-26.2-5.2S32 302.3 32 312l0 144c0 13.3 10.7 24 24 24l144 0c9.7 0 18.5-5.8 22.2-14.8s1.7-19.3-5.2-26.2l-40-40 79-79 79 79-40 40c-6.9 6.9-8.9 17.2-5.2 26.2s12.5 14.8 22.2 14.8l144 0c13.3 0 24-10.7 24-24l0-144c0-9.7-5.8-18.5-14.8-22.2s-19.3-1.7-26.2 5.2l-40 40-79-79 79-79 40 40c6.9 6.9 17.2 8.9 26.2 5.2s14.8-12.5 14.8-22.2l0-144c0-13.3-10.7-24-24-24L312 32c-9.7 0-18.5 5.8-22.2 14.8s-1.7 19.3 5.2 26.2l40 40-79 79-79-79 40-40c6.9-6.9 8.9-17.2 5.2-26.2S209.7 32 200 32z"/></svg>
        </div>
        <div style="width: 300px;position:absolute;top:0;right: 0;padding-right: 15px;">
            <div :style="{color: currentPriceColor}" style="text-align: right;">
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
            <current-date-time style="color: white;position: absolute; right: 15px; bottom: -30px;"></current-date-time>
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
.rocket-container {
    position: absolute;
    width: 80px;
    height: 80px;
}

</style>
