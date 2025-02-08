<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { format } from 'date-fns'
import { fr } from 'date-fns/locale'

const now = ref(new Date())

let intervalId = null

onMounted(() => {
    intervalId = setInterval(() => {
        now.value = new Date()
    }, 1000)
})

onUnmounted(() => {
    clearInterval(intervalId)
})

const formattedDate = computed(() => {
    return format(now.value, "PP pp", { locale: fr })
})
</script>

<template>
<div>{{ formattedDate }}</div>
</template>

<style scoped>

</style>
