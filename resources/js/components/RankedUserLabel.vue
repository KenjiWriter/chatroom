<script setup lang="ts">
import { computed } from 'vue';

interface RankData {
    name: string;
    prefix?: string | null;
    color_prefix?: string | null;
    color_name?: string | null;
    color_text?: string | null;
    effects?: {
        bold?: boolean;
        italic?: boolean;
        glow?: boolean;
        rainbow?: boolean;
    } | null;
}

const props = defineProps<{
    rank?: RankData | null;
    name: string;
    message?: string;
}>();

const rankData = computed(() => props.rank || {
    name: 'Guest',
    prefix: null,
    color_prefix: '#cccccc',
    color_name: '#cccccc',
    color_text: '#333333',
    effects: {}
});

const prefixStyle = computed(() => ({
    color: rankData.value.color_prefix || '#cccccc',
}));

const nameStyle = computed(() => ({
    color: rankData.value.color_name || '#cccccc',
}));

const messageStyle = computed(() => {
    const style: Record<string, string> = {
        color: rankData.value.color_text || '#333333',
    };
    
    // Pass the glow color as a CSS variable for the utility class
    if (rankData.value.effects?.glow) {
        style['--glow-color'] = rankData.value.color_text || '#333333';
    }

    return style;
});

const messageClasses = computed(() => {
    return {
        'font-bold': rankData.value.effects?.bold,
        'italic': rankData.value.effects?.italic,
        'text-glow': rankData.value.effects?.glow,
        'text-rainbow': rankData.value.effects?.rainbow,
    };
});
</script>

<template>
    <div class="inline-flex items-baseline break-all gap-1">
        <!-- Rank Prefix -->
        <span 
            v-if="rankData.prefix" 
            class="font-bold whitespace-nowrap"
            :style="prefixStyle"
        >
            {{ rankData.prefix }}
        </span>

        <!-- User Name -->
        <span 
            class="font-semibold whitespace-nowrap"
            :style="nameStyle"
        >
            {{ name }}:
        </span>

        <!-- Message Preview (if provided) -->
        <span 
            v-if="message"
            :style="messageStyle"
            :class="messageClasses"
        >
            {{ message }}
        </span>
    </div>
</template>
