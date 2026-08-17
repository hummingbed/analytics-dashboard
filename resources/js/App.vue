<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';
import EventStream from './components/EventStream.vue';
import MetricCard from './components/MetricCard.vue';
import SourceBreakdown from './components/SourceBreakdown.vue';
import TrafficChart from './components/TrafficChart.vue';

const snapshot = ref(null);
const error = ref('');
let timer;
async function loadDashboard() {
    try {
        const response = await fetch('/api/dashboard', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`Dashboard request failed (${response.status})`);
        snapshot.value = (await response.json()).data;
        error.value = '';
    } catch (exception) { error.value = exception.message; }
}
onMounted(() => { loadDashboard(); timer = window.setInterval(loadDashboard, 5000); });
onBeforeUnmount(() => window.clearInterval(timer));
</script>

<template>
    <main class="shell">
        <header><div class="brand"><div class="logo">P</div>Pulse</div><div class="live" :class="{ offline: error }"><span class="dot"></span><span v-if="error">Reconnecting…</span><span v-else-if="snapshot">Updated {{ new Date(snapshot.updated_at).toLocaleTimeString() }}</span><span v-else>Connecting…</span></div></header>
        <h1>Your business, right now.</h1><p class="intro">Kafka-powered sales, traffic, clicks and operations in one live view.</p>
        <div v-if="error" class="alert">{{ error }} <button @click="loadDashboard">Retry</button></div>
        <div v-if="!snapshot" class="loading"><span></span> Loading analytics…</div>
        <template v-else>
            <section class="cards"><MetricCard v-for="card in snapshot.cards" :key="card.label" :card="card" /></section>
            <section class="grid"><TrafficChart :series="snapshot.series" /><SourceBreakdown :sources="snapshot.sources" /></section>
            <EventStream :events="snapshot.recent" />
        </template>
    </main>
</template>
