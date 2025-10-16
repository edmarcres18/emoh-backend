<script setup lang="ts">
import { ref, onMounted, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface FlashMessage {
    success?: string;
    error?: string;
    warning?: string;
    info?: string;
}

const page = usePage();
const showToast = ref(false);
const toastMessage = ref('');
const toastType = ref<'success' | 'error' | 'warning' | 'info'>('success');

const showToastNotification = (message: string, type: 'success' | 'error' | 'warning' | 'info' = 'success') => {
    toastMessage.value = message;
    toastType.value = type;
    showToast.value = true;
    
    // Auto hide after 5 seconds
    setTimeout(() => {
        hideToast();
    }, 5000);
};

const hideToast = () => {
    showToast.value = false;
};

// Test function for debugging
const testToast = () => {
    showToastNotification('Test toast notification!', 'success');
};

// Watch for flash messages from Laravel
watch(() => page.props.flash as FlashMessage | null, (flash) => {
    if (flash?.success) {
        showToastNotification(flash.success, 'success');
    }
    if (flash?.error) {
        showToastNotification(flash.error, 'error');
    }
    if (flash?.warning) {
        showToastNotification(flash.warning, 'warning');
    }
    if (flash?.info) {
        showToastNotification(flash.info, 'info');
    }
}, { immediate: true, deep: true });

// Component mounted
onMounted(() => {
    // Toast component initialized
});

const getToastClasses = () => {
    const baseClasses = 'fixed top-4 right-4 z-50 flex items-center gap-3 px-4 py-3 rounded-lg shadow-lg border transform transition-all duration-300 ease-in-out';
    
    if (!showToast.value) {
        return `${baseClasses} translate-x-full opacity-0 pointer-events-none`;
    }
    
    const typeClasses = {
        success: 'bg-green-50 dark:bg-green-900/20 border-green-200 dark:border-green-800 text-green-800 dark:text-green-200',
        error: 'bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 text-red-800 dark:text-red-200',
        warning: 'bg-yellow-50 dark:bg-yellow-900/20 border-yellow-200 dark:border-yellow-800 text-yellow-800 dark:text-yellow-200',
        info: 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800 text-blue-800 dark:text-blue-200'
    };
    
    return `${baseClasses} translate-x-0 opacity-100 ${typeClasses[toastType.value]}`;
};

const getIconClasses = () => {
    const iconClasses = {
        success: 'text-green-500 dark:text-green-400',
        error: 'text-red-500 dark:text-red-400',
        warning: 'text-yellow-500 dark:text-yellow-400',
        info: 'text-blue-500 dark:text-blue-400'
    };
    
    return `h-5 w-5 flex-shrink-0 ${iconClasses[toastType.value]}`;
};
</script>

<template>    
    <div :class="getToastClasses()">
        <!-- Success Icon -->
        <svg v-if="toastType === 'success'" :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <!-- Error Icon -->
        <svg v-else-if="toastType === 'error'" :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <!-- Warning Icon -->
        <svg v-else-if="toastType === 'warning'" :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
        </svg>
        
        <!-- Info Icon -->
        <svg v-else :class="getIconClasses()" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
        </svg>
        
        <div class="flex-1">
            <p class="text-sm font-medium">{{ toastMessage }}</p>
        </div>
        
        <button
            @click="hideToast"
            class="flex-shrink-0 ml-2 p-1 rounded-md hover:bg-black/5 dark:hover:bg-white/5 transition-colors duration-200"
        >
            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
</template>
