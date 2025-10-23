<script setup lang="ts">
defineProps<{
  placeholder?: string;
}>();
</script>

<template>
  <span>
    <slot>{{ placeholder }}</slot>
  </span>
</template>
