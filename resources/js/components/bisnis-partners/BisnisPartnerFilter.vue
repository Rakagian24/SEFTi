<script setup lang="ts">
import { defineProps, defineEmits } from 'vue';

defineProps({
  filters: Object,
  search: String,
  jenisBp: String,
  termsOfPayment: String,
  entriesPerPage: Number
});

const emit = defineEmits(['update:search', 'update:jenisBp', 'update:termsOfPayment', 'update:entriesPerPage', 'reset']);

function updateSearch(value: string) {
  emit('update:search', value);
}

function updateJenisBp(value: string) {
  emit('update:jenisBp', value);
}

function updateTermsOfPayment(value: string) {
  emit('update:termsOfPayment', value);
}

function updateEntriesPerPage(value: number) {
  emit('update:entriesPerPage', value);
}

function resetFilters() {
  emit('reset');
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
            placeholder="Search Bisnis Partner..."
            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm"
          />
          <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
          </div>
        </div>

        <!-- Jenis BP Filter -->
        <div class="min-w-48">
          <select
            :value="jenisBp"
            @change="updateJenisBp(($event.target as HTMLSelectElement).value)"
            class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none"
          >
            <option value="">All Jenis BP</option>
            <option value="Customer">Customer</option>
            <option value="Karyawan">Karyawan</option>
            <option value="Cabang">Cabang</option>
          </select>
        </div>

        <!-- Terms Of Payment Filter -->
        <div class="min-w-48">
          <select
            :value="termsOfPayment"
            @change="updateTermsOfPayment(($event.target as HTMLSelectElement).value)"
            class="w-full pl-3 pr-10 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm appearance-none"
          >
            <option value="">All Terms of Payment</option>
            <option value="7 Hari">7 Hari</option>
            <option value="15 Hari">15 Hari</option>
            <option value="30 Hari">30 Hari</option>
            <option value="45 Hari">45 Hari</option>
            <option value="60 Hari">60 Hari</option>
            <option value="90 Hari">90 Hari</option>
          </select>
        </div>

        <!-- Reset Button -->
        <button
          @click="resetFilters"
          class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
        >
          Reset
        </button>
      </div>
    </div>
  </div>
</template>
