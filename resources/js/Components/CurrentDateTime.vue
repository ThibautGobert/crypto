<script setup>
import { onMounted, onUnmounted, ref, computed } from 'vue'
import { format } from 'date-fns'
import { fr, enUS, de } from 'date-fns/locale'

const props = defineProps({
    locale: {
        required: true,
        type: String
    }
})

console.log(props.locale)

const getLang = () => {
    switch (props.locale) {
        case 'fr':
            return fr
        case 'en':
            return enUS
        case 'de':
            return de
        default: return fr
    }
}

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
    return format(now.value, "PP pp", { locale: getLang() })
})
</script>

<template>
<div>{{ formattedDate }}</div>
</template>

<style scoped>

</style>
