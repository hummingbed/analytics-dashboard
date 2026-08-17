<script setup>
import { computed } from 'vue';
const props = defineProps({ card: { type: Object, required: true } });
const displayValue = computed(() => props.card.format === 'currency' ? new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(props.card.value) : props.card.format === 'percent' ? `${props.card.value}%` : new Intl.NumberFormat('en-US').format(props.card.value));
</script>
<template>
    <article class="card">
        <div class="label">{{ card.label }}</div>
        <div class="value">{{ displayValue }}</div>
        <div class="change" :class="{ down: card.change < 0 }">{{ card.change >= 0 ? '↑' : '↓' }} {{
            Math.abs(card.change) }}% vs yesterday</div>
    </article>
</template>
