<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
  value: string;
  class?: HTMLAttributes['class'];
}>();

const emit = defineEmits<{
  (e: 'select', value: string): void;
}>();
</script>

<template>
  <div
    @click="emit('select', value)"
    :class="cn(
      'relative flex w-full cursor-pointer select-none items-center rounded-sm py-1.5 px-2 text-sm outline-none hover:bg-accent hover:text-accent-foreground focus:bg-accent focus:text-accent-foreground data-[disabled]:pointer-events-none data-[disabled]:opacity-50',
      props.class,
    )"
  >
    <slot />
  </div>
</template>
