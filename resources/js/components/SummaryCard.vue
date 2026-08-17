<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [Number, String], required: true },
    tone: { type: String, default: '' },
});
</script>

<template>
    <article class="summary-card" :class="tone">
        <span>{{ label }}</span>
        <strong>{{ value }}</strong>
        <small>Today</small>
    </article>
</template>
