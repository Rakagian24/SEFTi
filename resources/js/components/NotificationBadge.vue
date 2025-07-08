<script setup lang="ts">
interface Props {
  count: number;
  variant?: 'red' | 'green' | 'blue';
}

withDefaults(defineProps<Props>(), {
  variant: 'red'
});
</script>

<template>
  <span
    v-if="count > 0"
    :class="[
      'absolute -top-1 -right-1 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-medium',
      {
        'bg-red-500': variant === 'red',
        'bg-green-500': variant === 'green',
        'bg-blue-500': variant === 'blue'
      }
    ]"
  >
    {{ count > 99 ? '99+' : count }}
  </span>
</template>
