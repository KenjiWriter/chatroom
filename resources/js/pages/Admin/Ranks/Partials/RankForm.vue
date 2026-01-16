<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import RankedUserLabel from '@/components/RankedUserLabel.vue';
import InputError from '@/components/InputError.vue'; // From root components
import { Label } from '@/components/ui/label';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Checkbox } from '@/components/ui/checkbox';

const props = defineProps<{
    rank?: any;
    permissions: any[];
    submitRoute: string;
    method?: string; // post or put
}>();

const form = useForm({
    name: props.rank?.name || '',
    priority: props.rank?.priority || 0,
    prefix: props.rank?.prefix || '',
    color_prefix: props.rank?.color_prefix || '#000000',
    color_name: props.rank?.color_name || '#000000',
    color_text: props.rank?.color_text || '#000000',
    effects: props.rank?.effects || {
        bold: false,
        italic: false,
        glow: false,
        rainbow: false,
    },
    permissions: props.rank?.permissions?.map((p: any) => p.id) || [],
});

const isPreviewDark = ref(false);

const submit = () => {
    if (props.method === 'put') {
        form.put(props.submitRoute);
    } else {
        form.post(props.submitRoute);
    }
};

const toggleEffect = (effect: string) => {
    // @ts-ignore
    form.effects[effect] = !form.effects[effect];
};
</script>

<template>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Form Section -->
        <div class="space-y-6">
            <form @submit.prevent="submit">
                <!-- Name -->
                <div>
                    <Label for="name">Rank Name</Label>
                    <Input id="name" v-model="form.name" type="text" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.name" class="mt-2" />
                </div>

                <!-- Priority -->
                <div class="mt-4">
                    <Label for="priority">Priority</Label>
                    <Input id="priority" v-model="form.priority" type="number" class="mt-1 block w-full" required />
                    <InputError :message="form.errors.priority" class="mt-2" />
                </div>

                <!-- Prefix -->
                <div class="mt-4">
                    <Label for="prefix">Prefix (Optional)</Label>
                    <Input id="prefix" v-model="form.prefix" type="text" class="mt-1 block w-full" />
                    <InputError :message="form.errors.prefix" class="mt-2" />
                </div>

                <!-- Colors -->
                <div class="mt-4 grid grid-cols-3 gap-4">
                    <div>
                        <Label for="color_prefix">Prefix Color</Label>
                        <input id="color_prefix" v-model="form.color_prefix" type="color" class="mt-1 block w-full h-10 p-1 rounded border" />
                    </div>
                    <div>
                        <Label for="color_name">Name Color</Label>
                        <input id="color_name" v-model="form.color_name" type="color" class="mt-1 block w-full h-10 p-1 rounded border" />
                    </div>
                    <div>
                        <Label for="color_text">Text Color</Label>
                        <input id="color_text" v-model="form.color_text" type="color" class="mt-1 block w-full h-10 p-1 rounded border" />
                    </div>
                </div>

                <!-- Effects -->
                <div class="mt-4">
                    <Label>Visual Effects</Label>
                    <div class="mt-2 flex gap-4">
                        <label class="flex items-center">
                            <Checkbox :checked="form.effects.bold" @update:checked="form.effects.bold = $event" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Bold</span>
                        </label>
                        <label class="flex items-center">
                            <Checkbox :checked="form.effects.italic" @update:checked="form.effects.italic = $event" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Italic</span>
                        </label>
                        <label class="flex items-center">
                            <Checkbox :checked="form.effects.glow" @update:checked="form.effects.glow = $event" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Glow</span>
                        </label>
                        <label class="flex items-center">
                            <Checkbox :checked="form.effects.rainbow" @update:checked="form.effects.rainbow = $event" />
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Rainbow</span>
                        </label>
                    </div>
                </div>

                <!-- Permissions -->
                <div class="mt-6">
                    <Label>Permissions</Label>
                    <div class="mt-2 grid grid-cols-2 gap-2 max-h-64 overflow-y-auto border p-2 rounded">
                        <label v-for="permission in permissions" :key="permission.id" class="flex items-center">
                            <input type="checkbox" :value="permission.id" v-model="form.permissions" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">{{ permission.name }}</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <Button :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                        Save Rank
                    </Button>
                </div>
            </form>
        </div>

        <!-- Live Preview Section -->
        <div class="space-y-6">
            <div class="bg-white dark:bg-gray-800 p-6 shadow sm:rounded-lg sticky top-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Live Preview</h3>
                    <button 
                        @click="isPreviewDark = !isPreviewDark" 
                        class="text-xs px-2 py-1 rounded border"
                        :class="isPreviewDark ? 'bg-gray-700 text-white' : 'bg-gray-200 text-black'"
                    >
                        Toggle Background
                    </button>
                </div>
                
                <div 
                    class="p-6 rounded border transition-colors duration-300"
                    :class="isPreviewDark ? 'bg-gray-900 border-gray-700' : 'bg-white border-gray-200'"
                >
                    <RankedUserLabel 
                        name="Nickname" 
                        :rank="form" 
                        message="This is how your chat messages will look like!" 
                    />
                </div>
                
                <p class="mt-4 text-sm text-gray-500">
                    Adjust the settings on the left to see instant updates here.
                </p>
            </div>
        </div>
    </div>
</template>
