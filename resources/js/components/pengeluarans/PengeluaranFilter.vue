<script setup lang="ts">
import { defineProps, defineEmits, computed } from 'vue';
import CustomSelectFilter from "../ui/CustomSelectFilter.vue";

const props = defineProps({
  filters: Object,
  search: String,
  entriesPerPage: [String, Number]
});

// Convert entriesPerPage to number if it's a string
const entriesPerPageNumber = computed(() => {
  if (typeof props.entriesPerPage === 'string') {
    return parseInt(props.entriesPerPage) || 10;
  }
  return props.entriesPerPage || 10;
});

const emit = defineEmits(['update:search', 'update:entriesPerPage', 'reset']);

function updateSearch(value: string) {
  emit('update:search', value);
  // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
  window.dispatchEvent(new CustomEvent('filter-changed'));
}

function updateEntriesPerPage(value: number) {
  emit('update:entriesPerPage', value);
  // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
  window.dispatchEvent(new CustomEvent('table-changed'));
}

</script>

<template>
  <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
    <div class="px-6 py-4">
      <div class="flex items-center gap-4 flex-wrap justify-between">
        <!-- LEFT: Filter Icon (Static) -->
        <div class="flex flex-col self-end gap-0 flex-1 min-w-0">

        </div>

        <!-- RIGHT: Show entries & Search -->
        <div class="flex items-center gap-4 flex-wrap flex-shrink-0">

<!-- Show entries per page -->
<div class="flex items-center text-sm text-gray-700">
            <span class="mr-2">Show</span>
            <div class="relative">
              <CustomSelectFilter
                :model-value="entriesPerPageNumber"
                @update:modelValue="updateEntriesPerPage"
                :options="[
                  { label: '10', value: 10 },
                  { label: '25', value: 25 },
                  { label: '50', value: 50 },
                  { label: '100', value: 100 }
                ]"
                width="5.5rem"
              />
            </div>
            <span class="ml-2">entries</span>
          </div>
          <!-- Search -->
          <div class="relative flex-1 min-w-64">
            <input
              :value="search"
              @input="updateSearch(($event.target as HTMLInputElement).value)"
              type="text"
              placeholder="Search Pengeluaran..."
              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:border-transparent text-sm"
            />
            <div
              class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none"
            >
              <svg
                class="h-4 w-4 text-gray-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
                />
              </svg>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Responsive filter layout */
@media (max-width: 768px) {
  .filter-dropdowns {
    flex-direction: column;
    align-items: stretch;
  }

  .filter-dropdowns > div {
    width: 100%;
  }
}

/* Custom style for select to match CustomSelect */
select {
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  background-color: #fff;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding-left: 0.75rem;
  padding-right: 1.25rem;
  padding-top: 0.5rem;
  padding-bottom: 0.5rem;
  font-size: 0.875rem;
  transition: box-shadow 0.2s, border-color 0.2s;
  outline: none;
}
select:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 2px #3b82f633;
}
</style>
