<script setup lang="ts">
import { ref, watch } from 'vue';

interface Props {
    show: boolean;
    title?: string;
    message?: string;
    confirmText?: string;
    cancelText?: string;
    isLoading?: boolean;
}

interface Emits {
    (e: 'close'): void;
    (e: 'confirm'): void;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Confirm Delete',
    message: 'Are you sure you want to delete this item? This action cannot be undone.',
    confirmText: 'Delete',
    cancelText: 'Cancel',
    isLoading: false,
});

const emit = defineEmits<Emits>();

const modalRef = ref<HTMLElement>();

const closeModal = () => {
    if (!props.isLoading) {
        emit('close');
    }
};

const confirmDelete = () => {
    if (!props.isLoading) {
        emit('confirm');
    }
};

const handleBackdropClick = (event: MouseEvent) => {
    if (event.target === modalRef.value) {
        closeModal();
    }
};

const handleEscapeKey = (event: KeyboardEvent) => {
    if (event.key === 'Escape' && props.show && !props.isLoading) {
        closeModal();
    }
};

// Add/remove event listeners when modal visibility changes
watch(() => props.show, (show) => {
    if (show) {
        document.addEventListener('keydown', handleEscapeKey);
        document.body.style.overflow = 'hidden';
    } else {
        document.removeEventListener('keydown', handleEscapeKey);
        document.body.style.overflow = '';
    }
});
</script>

<template>
    <Teleport to="body">
        <div
            v-if="show"
            ref="modalRef"
            @click="handleBackdropClick"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
        >
            <div
                class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl ring-1 ring-gray-200 dark:ring-gray-800 w-full max-w-md transform transition-all duration-200 scale-100"
                @click.stop
            >
                <!-- Header -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex items-center gap-3">
                        <div class="flex-shrink-0 h-10 w-10 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                            <svg class="h-5 w-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
                        </div>
                    </div>
                </div>

                <!-- Content -->
                <div class="px-6 py-4">
                    <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">
                        {{ message }}
                    </p>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
                    <button
                        @click="closeModal"
                        :disabled="isLoading"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                    >
                        {{ cancelText }}
                    </button>
                    <button
                        @click="confirmDelete"
                        :disabled="isLoading"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 disabled:bg-red-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:cursor-not-allowed"
                    >
                        <svg v-if="isLoading" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        {{ isLoading ? 'Deleting...' : confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
