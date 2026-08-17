<script setup>
import { computed } from 'vue';
const props = defineProps({ series: { type: Array, required: true } });
const maximum = computed(() => Math.max(...props.series.map(point => point.traffic), 1));
</script>
<template><article class="panel"><div class="panel-title"><h2>Traffic today</h2><span>24 hours</span></div><div class="chart"><div v-for="point in series" :key="point.label" class="bar" :style="{ height: `${Math.max(point.traffic / maximum * 100, 2)}%` }" :data-tip="`${point.label} · ${point.traffic} visits`"></div></div></article></template>
