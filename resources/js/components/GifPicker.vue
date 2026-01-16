<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
import { Search, Loader2 } from 'lucide-vue-next';
import axios from 'axios';

const emit = defineEmits(['select']);

// Use environment variable for Giphy API Key
const GIPHY_API_KEY = import.meta.env.VITE_GIPHY_API_KEY;

if (!GIPHY_API_KEY) {
    console.warn('Giphy API Key is missing. Please add VITE_GIPHY_API_KEY to your .env file.');
}

const TRENDING_URL = `https://api.giphy.com/v1/gifs/trending?api_key=${GIPHY_API_KEY}&limit=20&rating=g`;
const SEARCH_URL = `https://api.giphy.com/v1/gifs/search?api_key=${GIPHY_API_KEY}&limit=20&rating=g&q=`;

const search = ref('');
const gifs = ref<any[]>([]);
const loading = ref(false);
const error = ref(false);

const fetchGifs = async (query = '') => {
    loading.value = true;
    error.value = false;
    try {
        const url = query ? SEARCH_URL + encodeURIComponent(query) : TRENDING_URL;
        const response = await axios.get(url);
        gifs.value = response.data.data;
    } catch (e) {
        console.error('Failed to fetch GIFs', e);
        error.value = true;
    } finally {
        loading.value = false;
    }
};

let debounceTimer: any = null;
watch(search, (val) => {
    if (debounceTimer) clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchGifs(val);
    }, 500);
});

onMounted(() => {
    fetchGifs();
});

const selectGif = (gif: any) => {
    // Send the fixed_height version which is usually a good balance of quality/size
    emit('select', {
        url: gif.images.fixed_height.url,
        width: gif.images.fixed_height.width,
        height: gif.images.fixed_height.height,
        title: gif.title
    });
};
</script>

<template>
    <div class="flex flex-col w-72 sm:w-80 h-96 bg-card border rounded-lg shadow-xl overflow-hidden">
        <div class="p-3 border-b bg-muted/30">
            <div class="relative">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input 
                    v-model="search"
                    placeholder="Search Giphy..."
                    class="pl-9 h-9 text-xs"
                />
            </div>
        </div>

        <div class="flex-1 overflow-y-auto p-2">
            <div v-if="loading && gifs.length === 0" class="flex items-center justify-center h-full">
                <Loader2 class="h-6 w-6 animate-spin text-primary" />
            </div>

            <div v-else-if="error" class="flex flex-col items-center justify-center h-full text-center p-4">
                <p class="text-xs text-destructive">Failed to load GIFs.</p>
                <Button variant="link" size="sm" @click="fetchGifs(search)">Try Again</Button>
            </div>

            <div v-else-if="gifs.length === 0 && !loading" class="flex items-center justify-center h-full text-muted-foreground text-xs italic">
                No GIFs found.
            </div>

            <div v-else class="grid grid-cols-2 gap-2">
                <button 
                    v-for="gif in gifs" 
                    :key="gif.id"
                    @click="selectGif(gif)"
                    class="relative aspect-video group overflow-hidden rounded-md bg-muted hover:ring-2 hover:ring-primary transition-all"
                >
                    <img 
                        :src="gif.images.fixed_height_small.url" 
                        :alt="gif.title"
                        loading="lazy"
                        class="w-full h-full object-cover grayscale-[0.2] group-hover:grayscale-0 transition-all duration-300"
                    />
                </button>
            </div>
        </div>

        <div class="p-2 border-t bg-muted/10 flex justify-between items-center px-3">
             <img src="https://raw.githubusercontent.com/Giphy/giphy-js/master/packages/react-components/static/img/GIPHY_logo.png" class="h-4 opacity-50" />
             <span class="text-[10px] text-muted-foreground opacity-50">Powered by GIPHY</span>
        </div>
    </div>
</template>
