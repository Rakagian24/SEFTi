<script setup lang="ts">
import type { Component } from 'vue';
import NotificationBadge from './NotificationBadge.vue';

interface Props {
  icon: Component;
  count?: number;
  variant?: 'red' | 'green' | 'blue';
}

withDefaults(defineProps<Props>(), {
  count: 0,
  variant: 'red'
});

const emit = defineEmits<{
  click: [];
}>();
</script>

<template>
  <div class="relative">
    <button
      @click="emit('click')"
      class="p-2 outline-solid outline-1 outline-[#ccdff2] hover:bg-white/50 rounded-lg transition-colors"
    >
      <component :is="icon" class="w-5 h-5 text-gray-600" />
    </button>
    <NotificationBadge
      :count="count"
      :variant="variant"
    />
  </div>
</template>
