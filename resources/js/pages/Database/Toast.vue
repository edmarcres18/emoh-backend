<script setup lang="ts">
import { computed } from 'vue';
import { X } from 'lucide-vue-next';

interface Props {
  modelValue: boolean;
  type?: 'success' | 'error' | 'warning' | 'info';
  message: string;
  duration?: number;
}

const props = withDefaults(defineProps<Props>(), {
  type: 'success',
  duration: 3000,
});

const emit = defineEmits<{
  'update:modelValue': [value: boolean];
}>();

const typeClasses = computed(() => {
  switch (props.type) {
    case 'success':
      return 'bg-green-50 border-green-200 text-green-800 dark:bg-green-900/20 dark:border-green-800 dark:text-green-300';
    case 'error':
      return 'bg-red-50 border-red-200 text-red-800 dark:bg-red-900/20 dark:border-red-800 dark:text-red-300';
    case 'warning':
      return 'bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-900/20 dark:border-yellow-800 dark:text-yellow-300';
    case 'info':
      return 'bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-900/20 dark:border-blue-800 dark:text-blue-300';
    default:
      return 'bg-neutral-50 border-neutral-200 text-neutral-800 dark:bg-neutral-900/20 dark:border-neutral-800 dark:text-neutral-300';
  }
});

function close() {
  emit('update:modelValue', false);
}
</script>

<template>
  <Transition
    enter-active-class="transform ease-out duration-300 transition"
    enter-from-class="translate-y-2 opacity-0 sm:translate-y-0 sm:translate-x-2"
    enter-to-class="translate-y-0 opacity-100 sm:translate-x-0"
    leave-active-class="transition ease-in duration-100"
    leave-from-class="opacity-100"
    leave-to-class="opacity-0"
  >
    <div
      v-if="modelValue"
      class="pointer-events-auto w-full max-w-sm overflow-hidden rounded-lg border shadow-lg"
      :class="typeClasses"
    >
      <div class="p-4">
        <div class="flex items-start">
          <div class="flex-1">
            <p class="text-sm font-medium">
              {{ message }}
            </p>
          </div>
          <div class="ml-4 flex flex-shrink-0">
            <button
              @click="close"
              class="inline-flex rounded-md p-1.5 hover:opacity-75 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-neutral-50 focus:ring-neutral-600 dark:focus:ring-offset-neutral-900 dark:focus:ring-neutral-400"
            >
              <X class="h-4 w-4" />
            </button>
          </div>
        </div>
      </div>
    </div>
  </Transition>
</template>
