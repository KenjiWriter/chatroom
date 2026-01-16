<script setup lang="ts">
import { computed } from 'vue';
import UserPopOver from './UserPopOver.vue';

const props = withDefaults(defineProps<{
    rank?: any;
    name: string;
    message?: string;
    showMessage?: boolean;
    showModerationTools?: boolean;
    userId?: number;
    roomId?: number;
}>(), {
    rank: () => ({}),
    showMessage: true,
    showModerationTools: false,
    userId: undefined,
});

const emit = defineEmits(['moderate']);

const rankColors = computed(() => {
    return {
        prefix: props.rank.color_prefix || '#666666',
        name: props.rank.color_name || '#000000',
        text: props.rank.color_text || '#333333',
    };
});

const formattedPrefix = computed(() => {
    if (!props.rank.prefix) return '';
    // Strip existing brackets if they exist and re-wrap
    const stripped = props.rank.prefix.toString().replace(/^\[+|\]+$/g, '');
    return `[${stripped}]`;
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
                {{ formattedPrefix }}
            </span>
            
            <!-- Name -->
            <component 
                :is="userId ? UserPopOver : 'div'" 
                :user-id="userId" 
                :name="name"
                :room-id="roomId"
                class="inline-flex"
            >
                <div class="inline-flex items-center gap-1">
                    <button
                        v-if="showModerationTools"
                        type="button"
                        class="inline-flex items-center gap-1 p-0 m-0 border-none bg-transparent cursor-pointer hover:underline focus:outline-none"
                        @click.stop="emit('moderate', name)"
                    >
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
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4 text-gray-500">
                            <path fill-rule="evenodd" d="M12 2.25c-5.385 0-9.75 4.365-9.75 9.75s4.365 9.75 9.75 9.75 9.75-4.365 9.75-9.75S17.385 2.25 12 2.25ZM12.75 6a.75.75 0 0 0-1.5 0v6a.75.75 0 0 0 .75.75h4.5a.75.75 0 0 0 0-1.5h-3.75V6Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <span 
                        v-else
                        class="cursor-pointer"
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
            </component>
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
