<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Filter & Pencarian</h3>
      <button
        @click="resetFilters"
        class="text-sm text-gray-500 hover:text-gray-700 flex items-center gap-1"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
          />
        </svg>
        Reset Filter
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
      <!-- Search -->
      <div class="lg:col-span-2">
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Pencarian
        </label>
        <div class="relative">
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
              />
            </svg>
          </div>
          <input
            v-model="localFilters.search"
            type="text"
            placeholder="Cari berdasarkan No. MB, detail keperluan..."
            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
            @input="debouncedSearch"
          />
        </div>
      </div>

      <!-- Department -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Department
        </label>
        <select
          v-model="localFilters.department_id"
          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          @change="applyFilters"
        >
          <option value="">Semua Department</option>
          <option
            v-for="dept in departments"
            :key="dept.id"
            :value="dept.id"
          >
            {{ dept.name }}
          </option>
        </select>
      </div>

      <!-- Status -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Status
        </label>
        <select
          v-model="localFilters.status"
          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm"
          @change="applyFilters"
        >
          <option value="">Semua Status</option>
          <option value="In Progress">In Progress</option>
          <option value="Verified">Verified</option>
          <option value="Validated">Validated</option>
          <option value="Approved">Approved</option>
          <option value="Rejected">Rejected</option>
        </select>
      </div>
    </div>

    <!-- Entries per page -->
    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-200">
      <div class="flex items-center gap-2">
        <label class="text-sm font-medium text-gray-700">Tampilkan:</label>
        <select
          :value="entriesPerPage"
          @change="updateEntriesPerPage"
          class="px-3 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
        >
          <option value="10">10</option>
          <option value="25">25</option>
          <option value="50">50</option>
          <option value="100">100</option>
        </select>
        <span class="text-sm text-gray-500">entri per halaman</span>
      </div>

      <!-- Quick Status Filters -->
      <div class="flex items-center gap-2">
        <span class="text-sm font-medium text-gray-700">Filter Cepat:</span>
        <button
          @click="setStatusFilter('In Progress')"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
            localFilters.status === 'In Progress'
              ? 'bg-blue-100 text-blue-800'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          In Progress
        </button>
        <button
          @click="setStatusFilter('Verified')"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
            localFilters.status === 'Verified'
              ? 'bg-yellow-100 text-yellow-800'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Verified
        </button>
        <button
          @click="setStatusFilter('Validated')"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
            localFilters.status === 'Validated'
              ? 'bg-purple-100 text-purple-800'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Validated
        </button>
        <button
          @click="setStatusFilter('Approved')"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
            localFilters.status === 'Approved'
              ? 'bg-green-100 text-green-800'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Approved
        </button>
        <button
          @click="setStatusFilter('Rejected')"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
            localFilters.status === 'Rejected'
              ? 'bg-red-100 text-red-800'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Rejected
        </button>
        <button
          @click="setStatusFilter('')"
          :class="[
            'px-3 py-1 text-xs font-medium rounded-full transition-colors',
            localFilters.status === ''
              ? 'bg-gray-200 text-gray-800'
              : 'bg-gray-100 text-gray-600 hover:bg-gray-200'
          ]"
        >
          Semua
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";

// Props
const props = defineProps<{
  filters: any;
  departments: Array<{ id: number; name: string }>;
  entriesPerPage: number;
}>();

// Emits
const emit = defineEmits<{
  filter: [filters: any];
  reset: [];
  "update:entries-per-page": [perPage: number];
}>();

// Local filters
const localFilters = ref({ ...props.filters });

// Debounced search
let searchTimeout: ReturnType<typeof setTimeout>;
const debouncedSearch = () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 300);
};

// Methods
const applyFilters = () => {
  emit("filter", { ...localFilters.value });
};

const resetFilters = () => {
  localFilters.value = {
    search: "",
    department_id: "",
    status: "",
    per_page: props.entriesPerPage,
    page: 1,
  };
  emit("reset");
};

const updateEntriesPerPage = (event: Event) => {
  const target = event.target as HTMLSelectElement;
  const perPage = parseInt(target.value);
  localFilters.value.per_page = perPage;
  emit("update:entries-per-page", perPage);
};

const setStatusFilter = (status: string) => {
  localFilters.value.status = status;
  applyFilters();
};

// Watch for prop changes
watch(
  () => props.filters,
  (newFilters) => {
    localFilters.value = { ...newFilters };
  },
  { deep: true }
);
</script>
