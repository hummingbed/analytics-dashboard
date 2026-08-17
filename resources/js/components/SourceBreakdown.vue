<script setup>
import { computed } from 'vue';
const props = defineProps({ sources: { type: Object, required: true } });
const entries = computed(() => Object.entries(props.sources));
const maximum = computed(() => Math.max(...entries.value.map(([, count]) => count), 1));
</script>
<template>
    <article class="panel">
        <div class="panel-title">
            <h2>Top sources</h2><span>Today</span>
        </div>
        <div v-if="!entries.length" class="empty">No events yet</div>
        <div v-for="[source, count] in entries" :key="source" class="source"><span>{{ source }}</span>
            <div class="track">
                <div class="fill" :style="{ width: `${count / maximum * 100}%` }"></div>
            </div><b>{{ count }}</b>
        </div>
    </article>
</template>
