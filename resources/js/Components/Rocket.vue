<script setup>
import { onMounted, ref } from 'vue';
import {gsap, Sine} from 'gsap'
const random = (min, max) => {
    const delta = max - min;
    return (direction = 1) => (min + delta * Math.random()) * direction;
}
const rocket = ref(null)
const fire = ref(null)
const randomAngle = random(-5, 5)
const randomTime = random(3, 5)
const randomTime2 = random(5, 10)
const randomX = random(1, 2)
const randomY = random(1, 2)

onMounted(()=> {
    let el = rocket.value

    if(!el) return
    gsap.set(el, {
        x: randomX(-5),
        y: randomX(5),
        rotation: randomAngle(-5)
    });
    moveX(el, 5);
    moveY(el, -5);
    rotate(el, 3);
    gsap.fromTo(fire.value,
        { scale: 0.95 },
        {
            scale: 1.05,
            duration: 0.15,    // durée de l'animation
            repeat: -1,       // répétition infinie
            yoyo: true,       // va et vient
            ease: Sine.easeInOut // easing pour une transition fluide
        }
    );
})

const  moveX = (target, direction) => {
    gsap.to(target, randomTime(), {
        x: randomX(direction),
        ease: Sine.easeInOut,
        onComplete: moveX,
        onCompleteParams: [target, direction * -1]
    })
}
const moveY = (target, direction) => {
    gsap.to(target, randomTime(), {
        y: randomY(direction),
        ease: Sine.easeInOut,
        onComplete: moveY,
        onCompleteParams: [target, direction * -1]
    });
}

const rotate = (target, direction) => {
    gsap.to(target, randomTime2(), {
        rotation: randomAngle(direction),
        ease: Sine.easeInOut,
        onComplete: rotate,
        onCompleteParams: [target, direction * -1]
    });
}

</script>

<template>
    <div id="rocketWrapper" ref="rocket" class="rocket-wrapper">
        <svg id="rocket"  xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2l0 82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9l0-107.2c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>
        <svg id="btc" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.-->
            <path d="M504 256c0 137-111 248-248 248S8 393 8 256 119 8 256 8s248 111 248 248zm-141.7-35.3c4.9-33-20.2-50.7-54.6-62.6l11.1-44.7-27.2-6.8-10.9 43.5c-7.2-1.8-14.5-3.5-21.8-5.1l10.9-43.8-27.2-6.8-11.2 44.7c-5.9-1.3-11.7-2.7-17.4-4.1l0-.1-37.5-9.4-7.2 29.1s20.2 4.6 19.8 4.9c11 2.8 13 10 12.7 15.8l-12.7 50.9c.8 .2 1.7 .5 2.8 .9-.9-.2-1.9-.5-2.9-.7l-17.8 71.3c-1.3 3.3-4.8 8.4-12.5 6.5 .3 .4-19.8-4.9-19.8-4.9l-13.5 31.1 35.4 8.8c6.6 1.7 13 3.4 19.4 5l-11.3 45.2 27.2 6.8 11.2-44.7a1038.2 1038.2 0 0 0 21.7 5.6l-11.1 44.5 27.2 6.8 11.3-45.1c46.4 8.8 81.3 5.2 96-36.7 11.8-33.8-.6-53.3-25-66 17.8-4.1 31.2-15.8 34.7-39.9zm-62.2 87.2c-8.4 33.8-65.3 15.5-83.8 10.9l14.9-59.9c18.4 4.6 77.6 13.7 68.8 49zm8.4-87.7c-7.7 30.7-55 15.1-70.4 11.3l13.5-54.3c15.4 3.8 64.8 11 56.8 43z"/></svg>
        <svg ref="fire" class="fire" fill="url(#mon-degrade)"  width="100%" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink"
             viewBox="0 0 611.999 611.999" xml:space="preserve">
          <defs>
            <linearGradient id="mon-degrade" x1="0%" y1="0%" x2="100%" y2="0%">
              <stop offset="0%" style="stop-color: red;">
                <animate attributeName="stop-color"
                         values="red; orange; yellow; red"
                         dur="3s"
                         repeatCount="indefinite" />
              </stop>
                <stop offset="100%" style="stop-color: yellow;">
                <animate attributeName="stop-color"
                         values="yellow; orange; red; yellow"
                         dur="3s"
                         repeatCount="indefinite" />
              </stop>
            </linearGradient>
          </defs>
            <g>
                <path d="M216.02,611.195c5.978,3.178,12.284-3.704,8.624-9.4c-19.866-30.919-38.678-82.947-8.706-149.952
                         c49.982-111.737,80.396-169.609,80.396-169.609s16.177,67.536,60.029,127.585c42.205,57.793,65.306,130.478,28.064,191.029
                         c-3.495,5.683,2.668,12.388,8.607,9.349c46.1-23.582,97.806-70.885,103.64-165.017c2.151-28.764-1.075-69.034-17.206-119.851
                         c-20.741-64.406-46.239-94.459-60.992-107.365c-4.413-3.861-11.276-0.439-10.914,5.413c4.299,69.494-21.845,87.129-36.726,47.386
                         c-5.943-15.874-9.409-43.33-9.409-76.766c0-55.665-16.15-112.967-51.755-159.531c-9.259-12.109-20.093-23.424-32.523-33.073
                         c-4.5-3.494-11.023,0.018-10.611,5.7c2.734,37.736,0.257,145.885-94.624,275.089c-86.029,119.851-52.693,211.896-40.864,236.826
                         C153.666,566.767,185.212,594.814,216.02,611.195z"/>
            </g>
        </svg>
    </div>
</template>

<style scoped lang="scss">
.rocket-wrapper {
   // z-index: 10;
    right: 15px;
    position: absolute;
    height: 80px;
    width: 80px;
    fill: white;
    transition: all 0.5s ease-in-out;
    #rocket {
        position: relative;
        z-index: 10;
        //stroke: yellow;
        //stroke-width: 10px;
    }
    .fire {
        z-index: 5;
        width: 37%;
        rotate: -139deg;
        position: relative;
        bottom: 32px;
        left: 4px;
        height: fit-content;
    }
    #btc {
        position: absolute;
        z-index: 20;
        width: 23px;
        rotate: 31deg;
        top: 30%;
        fill: black;
        right: 29%;
    }
}
</style>
