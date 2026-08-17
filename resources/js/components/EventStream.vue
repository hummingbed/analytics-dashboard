<script setup>
defineProps({ events: { type: Array, required: true } });
const money = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
const number = new Intl.NumberFormat('en-US');
</script>
<template>
    <article class="panel wide">
        <div class="panel-title">
            <h2>Event stream</h2><span class="kafka-badge">Kafka live</span>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Source</th>
                        <th>Value</th>
                        <th class="hide-mobile">Time</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="event in events" :key="event.event_id">
                        <td><span class="pill">{{ event.type.replace('_', ' ') }}</span></td>
                        <td>{{ event.source }}</td>
                        <td>{{ event.type === 'sale' ? money.format(event.value) : number.format(event.value) }}</td>
                        <td class="hide-mobile">{{ new Date(event.occurred_at).toLocaleTimeString([], {
                            hour: '2-digit',
                            minute: '2-digit' }) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </article>
</template>
