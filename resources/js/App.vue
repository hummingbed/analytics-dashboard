<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Echo from './echo';
import SummaryCard from './components/SummaryCard.vue';
import TransactionTable from './components/TransactionTable.vue';

const snapshot = ref(null);
const error = ref('');
const socketConnected = ref(false);

const money = new Intl.NumberFormat('en-US', {
    style: 'currency',
    currency: 'USD',
    maximumFractionDigits: 0,
});

const cards = computed(() => snapshot.value ? [
    { label: 'Transaction value', value: money.format(snapshot.value.summary.total_value) },
    { label: 'Transactions', value: snapshot.value.summary.transaction_count.toLocaleString() },
    { label: 'Successful', value: snapshot.value.summary.successful_count.toLocaleString(), tone: 'success' },
    { label: 'Failed', value: snapshot.value.summary.failed_count.toLocaleString(), tone: 'danger' },
] : []);

async function loadDashboard() {
    try {
        const response = await fetch('/api/dashboard', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`Dashboard request failed (${response.status})`);
        snapshot.value = (await response.json()).data;
        error.value = '';
    } catch (exception) {
        error.value = exception.message;
    }
}

onMounted(() => {
    loadDashboard();

    Echo.connector.pusher.connection.bind('connected', () => {
        socketConnected.value = true;
    });
    Echo.connector.pusher.connection.bind('disconnected', () => {
        socketConnected.value = false;
    });
    Echo.channel('transactions').listen('.transaction.created', loadDashboard);
});

onBeforeUnmount(() => Echo.leaveChannel('transactions'));
</script>

<template>
    <main class="shell">
        <header>
            <div class="brand"><span class="logo">T</span>Transact</div>
            <div class="connection" :class="{ offline: error || !socketConnected }">
                <span class="dot"></span>
                <span v-if="error">Reconnecting…</span>
                <span v-else-if="!socketConnected">Connecting live updates…</span>
                <span v-else-if="snapshot">Live · Updated {{ new Date(snapshot.updated_at).toLocaleTimeString() }}</span>
                <span v-else>Connecting…</span>
            </div>
        </header>

        <section class="hero">
            <p class="eyebrow">Transaction dashboard</p>
            <h1>User transactions, as they happen.</h1>
            <p>Every transaction sent to the API is processed by Kafka and displayed here automatically.</p>
        </section>

        <div v-if="error" class="alert">{{ error }} <button @click="loadDashboard">Retry</button></div>
        <div v-if="!snapshot" class="loading">Loading transactions…</div>

        <template v-else>
            <section class="summary-grid">
                <SummaryCard v-for="card in cards" :key="card.label" v-bind="card" />
            </section>
            <TransactionTable :transactions="snapshot.transactions" />
        </template>
    </main>
</template>
