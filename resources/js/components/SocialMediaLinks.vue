<script setup lang="ts">
import { onMounted } from 'vue';
import { useContactInfo } from '@/composables/useContactInfo';

const { 
    facebookUrl, 
    twitterUrl, 
    instagramUrl, 
    linkedinUrl,
    loading, 
    error, 
    fetchContactInfo 
} = useContactInfo();

onMounted(() => {
    fetchContactInfo();
});
</script>

<template>
    <div class="social-media-links">
        <!-- Loading state -->
        <div v-if="loading" class="text-sm text-gray-500">
            Loading social media links...
        </div>
        
        <!-- Error state -->
        <div v-else-if="error" class="text-sm text-red-500">
            Error: {{ error }}
        </div>
        
        <!-- Social media links -->
        <div v-else class="flex gap-4">
            <!-- Facebook Link (Only if available) -->
            <a 
                v-if="facebookUrl" 
                :href="facebookUrl" 
                target="_blank" 
                rel="noopener noreferrer"
                class="text-blue-600 hover:text-blue-800"
            >
                Facebook
            </a>
            
            <!-- Twitter Link -->
            <a 
                v-if="twitterUrl" 
                :href="twitterUrl" 
                target="_blank" 
                rel="noopener noreferrer"
                class="text-blue-400 hover:text-blue-600"
            >
                Twitter
            </a>
            
            <!-- Instagram Link -->
            <a 
                v-if="instagramUrl" 
                :href="instagramUrl" 
                target="_blank" 
                rel="noopener noreferrer"
                class="text-pink-600 hover:text-pink-800"
            >
                Instagram
            </a>
            
            <!-- LinkedIn Link -->
            <a 
                v-if="linkedinUrl" 
                :href="linkedinUrl" 
                target="_blank" 
                rel="noopener noreferrer"
                class="text-blue-700 hover:text-blue-900"
            >
                LinkedIn
            </a>
        </div>
    </div>
</template>
