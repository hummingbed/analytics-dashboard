<script setup>
defineProps({ transactions: { type: Array, required: true } });

const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'NGN' });
</script>

<template>
    <section class="transactions-panel">
        <div class="panel-heading">
            <div><h2>Recent transactions</h2><p>The latest transactions processed through Kafka</p></div>
            <span class="live-badge">Live</span>
        </div>
        <div v-if="!transactions.length" class="empty-state">
            No transactions yet. Send a POST request to <code>/api/transactions</code>.
        </div>
        <div v-else class="table-wrap">
            <table>
                <thead><tr><th>User</th><th>Transaction ID</th><th>Type</th><th>Status</th><th>Amount</th><th>Time</th></tr></thead>
                <tbody>
                    <tr v-for="transaction in transactions" :key="transaction.transaction_id">
                        <td><strong>{{ transaction.user_name }}</strong><small>{{ transaction.description || 'No description' }}</small></td>
                        <td class="transaction-id">{{ transaction.transaction_id.slice(0, 8) }}…</td>
                        <td><span class="type" :class="transaction.type">{{ transaction.type }}</span></td>
                        <td><span class="status" :class="transaction.status">{{ transaction.status }}</span></td>
                        <td class="amount">{{ money.format(transaction.amount) }}</td>
                        <td>{{ new Date(transaction.transacted_at).toLocaleString([], { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' }) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</template>
