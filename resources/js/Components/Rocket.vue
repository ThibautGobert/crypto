<script setup>
import { onMounted, ref } from 'vue';
import {gsap, Sine} from 'gsap'
const random = (min, max) => {
    const delta = max - min;
    return (direction = 1) => (min + delta * Math.random()) * direction;
}
const rocket = ref(null)
const randomAngle = random(-5, 5)
const randomTime = random(3, 5)
const randomTime2 = random(5, 10)
const randomX = random(1, 2)
const randomY = random(1, 2)

onMounted(()=> {
    let el = rocket.value//document.getElementById('rocketWrapper')
    if(!el) return
    gsap.set(el, {
        x: randomX(-1),
        y: randomX(1),
        rotation: randomAngle(-1)
    });
    moveX(el, 1);
    moveY(el, -1);
    rotate(el, 1);
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
    <div  class="rocket-wrapper">
        <svg id="rocketWrapper" ref="rocket" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2025 Fonticons, Inc.--><path d="M156.6 384.9L125.7 354c-8.5-8.5-11.5-20.8-7.7-32.2c3-8.9 7-20.5 11.8-33.8L24 288c-8.6 0-16.6-4.6-20.9-12.1s-4.2-16.7 .2-24.1l52.5-88.5c13-21.9 36.5-35.3 61.9-35.3l82.3 0c2.4-4 4.8-7.7 7.2-11.3C289.1-4.1 411.1-8.1 483.9 5.3c11.6 2.1 20.6 11.2 22.8 22.8c13.4 72.9 9.3 194.8-111.4 276.7c-3.5 2.4-7.3 4.8-11.3 7.2l0 82.3c0 25.4-13.4 49-35.3 61.9l-88.5 52.5c-7.4 4.4-16.6 4.5-24.1 .2s-12.1-12.2-12.1-20.9l0-107.2c-14.1 4.9-26.4 8.9-35.7 11.9c-11.2 3.6-23.4 .5-31.8-7.8zM384 168a40 40 0 1 0 0-80 40 40 0 1 0 0 80z"/></svg>
    </div>
</template>

<style scoped>
.rocket-wrapper {
    right: 15px;
    position: absolute;
    height: 80px;
    width: 80px;
    fill: white;
    transition: all 0.5s ease-in-out;
}
</style>
