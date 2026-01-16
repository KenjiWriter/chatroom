<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    rank?: any;
    name: string;
    message?: string;
    showMessage?: boolean;
}>(), {
    rank: () => ({}),
    showMessage: true,
});

const rankColors = computed(() => {
    return {
        prefix: props.rank.color_prefix || '#666666',
        name: props.rank.color_name || '#000000',
        text: props.rank.color_text || '#333333',
    };
});
</script>

<template>
    <div class="flex flex-col">
        <div class="flex items-center gap-2">
            <!-- Prefix -->
            <span 
                v-if="rank.prefix" 
                :style="{ color: rankColors.prefix }"
                class="font-medium"
            >
                [{{ rank.prefix }}]
            </span>
            
            <!-- Name -->
            <span 
                :class="{
                    'font-bold': rank.effects?.bold,
                    'italic': rank.effects?.italic,
                    'text-glow': rank.effects?.glow,
                    'text-rainbow': rank.effects?.rainbow
                }"
                :style="{ 
                    color: rankColors.name,
                    '--glow-color': rankColors.name
                }"
            >
                {{ name }}
            </span>
        </div>

        <!-- Message -->
        <div 
            v-if="showMessage && message"
            :class="{
                'font-bold': rank.effects?.bold,
                'italic': rank.effects?.italic,
                'text-glow': rank.effects?.glow,
                'text-rainbow': rank.effects?.rainbow
            }"
            :style="{ 
                color: rankColors.text,
                '--glow-color': rankColors.text
            }"
            class="text-sm break-words"
        >
            {{ message }}
        </div>
    </div>
</template>
