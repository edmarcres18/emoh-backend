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
  title: 'Revoke All Tokens',
  message: 'Are you sure you want to revoke all tokens for this client? This will log them out of all devices.',
  confirmText: 'Revoke',
  cancelText: 'Cancel',
  isLoading: false,
});

const emit = defineEmits<Emits>();

const modalRef = ref<HTMLElement>();

const closeModal = () => { if (!props.isLoading) emit('close'); };
const confirmAction = () => { if (!props.isLoading) emit('confirm'); };

const handleBackdropClick = (event: MouseEvent) => {
  if (event.target === modalRef.value) closeModal();
};

const handleEscapeKey = (event: KeyboardEvent) => {
  if (event.key === 'Escape' && props.show && !props.isLoading) closeModal();
};

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
    <div v-if="show" ref="modalRef" @click="handleBackdropClick" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm">
      <div class="bg-white dark:bg-gray-900 rounded-xl shadow-2xl ring-1 ring-gray-200 dark:ring-gray-800 w-full max-w-md" @click.stop>
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div class="flex items-center gap-3">
            <div class="flex-shrink-0 h-10 w-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
              <svg class="h-5 w-5 text-orange-600 dark:text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
              </svg>
            </div>
            <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">{{ title }}</h3>
          </div>
        </div>
        <!-- Content -->
        <div class="px-6 py-4">
          <p class="text-sm text-gray-600 dark:text-gray-300 leading-relaxed">{{ message }}</p>
        </div>
        <!-- Actions -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 flex items-center justify-end gap-3">
          <button @click="closeModal" :disabled="isLoading" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed">
            {{ cancelText }}
          </button>
          <button @click="confirmAction" :disabled="isLoading" class="inline-flex items-center gap-2 px-4 py-2 bg-orange-600 hover:bg-orange-700 disabled:bg-orange-400 text-white text-sm font-medium rounded-lg shadow-sm transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 disabled:cursor-not-allowed">
            <svg v-if="isLoading" class="h-4 w-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
            </svg>
            <svg v-else class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2" />
            </svg>
            {{ isLoading ? 'Revoking...' : confirmText }}
          </button>
        </div>
      </div>
    </div>
  </Teleport>
</template>
