<script setup lang="ts">
interface Props {
  title?: string;
  description?: string;
  icon?: string;
  actionText?: string;
  showAction?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  title: 'No data found',
  description: 'There are no items to display at the moment.',
  icon: 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4',
  actionText: 'Add new item',
  showAction: false
});

const emit = defineEmits(['action']);

function handleAction() {
  emit('action');
}
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="flex flex-col items-center justify-center py-12 px-6">
      <!-- Icon -->
      <div class="flex-shrink-0 mb-4">
        <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-center">
          <svg
            class="w-8 h-8 text-gray-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
            aria-hidden="true"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              :d="icon"
            />
          </svg>
        </div>
      </div>

      <!-- Content -->
      <div class="text-center">
        <h3 class="text-lg font-medium text-gray-900 mb-2">
          {{ title }}
        </h3>
        <p class="text-sm text-gray-500 mb-6 max-w-sm">
          {{ description }}
        </p>

        <!-- Action Button -->
        <button
          v-if="showAction"
          @click="handleAction"
          class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200"
        >
          <svg
            class="w-4 h-4 mr-2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"
            />
          </svg>
          {{ actionText }}
        </button>
      </div>
    </div>
  </div>
</template>
