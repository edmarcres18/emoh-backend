<script setup lang="ts">
import { ref, onMounted } from 'vue';

const facebookUrl = ref<string | null>(null);
const loading = ref(false);

const fetchFacebookUrl = async () => {
    loading.value = true;
    try {
        const response = await fetch('/api/contact-info');
        const data = await response.json();
        facebookUrl.value = data.social_facebook;
    } catch (error) {
        console.error('Error fetching Facebook URL:', error);
    } finally {
        loading.value = false;
    }
};

onMounted(() => {
    fetchFacebookUrl();
});
</script>

<template>
    <div v-if="loading">Loading...</div>
    <a 
        v-else-if="facebookUrl" 
        :href="facebookUrl" 
        target="_blank" 
        rel="noopener noreferrer"
        class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
    >
        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 24 24">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
        Follow us on Facebook
    </a>
</template>
