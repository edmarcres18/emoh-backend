<template>
  <div
    ref="progressElement"
    role="progressbar"
    :aria-valuemin="0"
    :aria-valuemax="100"
    :aria-valuenow="value"
    :data-state="getProgressState()"
    :data-value="value"
    :data-max="100"
    class="relative h-4 w-full overflow-hidden rounded-full bg-secondary"
  >
    <div
      class="h-full w-full flex-1 bg-primary transition-all"
      :style="{ transform: `translateX(-${100 - (value || 0)}%)` }"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue'

interface Props {
  value?: number
  max?: number
}

const props = withDefaults(defineProps<Props>(), {
  value: 0,
  max: 100
})

const progressElement = ref<HTMLElement>()

const getProgressState = () => {
  if (props.value === 0) return 'indeterminate'
  if (props.value === props.max) return 'complete'
  return 'loading'
}
</script>
