<script setup lang="ts">
import { getIconForPage } from '@/lib/iconMapping';

interface Props {
  title: string;
  description?: string;
  showAddButton?: boolean;
  addButtonText?: string;
  onAddClick?: () => void;
}

const props = withDefaults(defineProps<Props>(), {
  description: '',
  showAddButton: true,
  addButtonText: 'Add New',
  onAddClick: () => {},
});

const pageIcon = getIconForPage(props.title);
</script>

<template>
  <div class="flex items-center justify-between mb-6">
    <div>
      <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
      <div class="flex items-center mt-2 text-sm text-gray-500">
        <component :is="pageIcon" class="w-4 h-4 mr-1" />
        {{ description || `Manage ${title} data` }}
      </div>
    </div>

    <div v-if="showAddButton" class="flex items-center gap-3">
      <!-- Add New Button -->
      <button
        @click="onAddClick"
        class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v16m8-8H4"
          />
        </svg>
        {{ addButtonText }}
      </button>
    </div>
  </div>
</template>
