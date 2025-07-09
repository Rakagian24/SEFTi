<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';

defineProps({
  filters: Object,
  search: String,
  entriesPerPage: Number
});

const emit = defineEmits(['update:search', 'update:entriesPerPage', 'reset']);

function updateSearch(value: string) {
  emit('update:search', value);
}

function updateEntriesPerPage(value: number) {
  emit('update:entriesPerPage', value);
}

</script>

<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6">
    <div class="px-6 py-4">
      <div class="flex items-center gap-4 flex-wrap">
        <!-- Show entries per page -->
        <div class="flex items-center text-sm text-gray-700">
          <span class="mr-2">Show</span>
          <select
            :value="entriesPerPage"
            @change="updateEntriesPerPage(parseInt(($event.target as HTMLSelectElement).value))"
            class="border border-gray-300 rounded-md px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
          >
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
          </select>
          <span class="ml-2">entries</span>
        </div>

        <!-- Search -->
        <div class="relative flex-1 min-w-64">
          <input
            :value="search"
            @input="updateSearch(($event.target as HTMLInputElement).value)"
            type="text"
            placeholder="Search Bank..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
          />
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
