<script setup lang="ts">

interface SummaryData {
  total_count: number;
  total_idr: number;
  total_usd: number;
  total_matched: number;
}

interface Props {
  summary: SummaryData;
  loading?: boolean;
}

withDefaults(defineProps<Props>(), {
  loading: false
});

const formatCurrency = (amount: number, currency: string) => {
  if (currency === 'IDR') {
    return new Intl.NumberFormat('id-ID', {
      style: 'currency',
      currency: 'IDR',
      minimumFractionDigits: 0,
      maximumFractionDigits: 0
    }).format(amount);
  } else {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: 'USD',
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    }).format(amount);
  }
};

const formatNumber = (num: number) => {
  return new Intl.NumberFormat('id-ID').format(num);
};
</script>

<template>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
    <!-- Total Bank Masuk Created -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">Total BM Dibuat</p>
          <div v-if="loading" class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-16"></div>
          </div>
          <p v-else class="text-2xl font-semibold text-gray-900">
            {{ formatNumber(summary.total_count) }}
          </p>
        </div>
      </div>
    </div>

    <!-- Total Nilai BM (IDR) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
            </svg>
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">Total Nilai BM (IDR)</p>
          <div v-if="loading" class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-24"></div>
          </div>
          <p v-else class="text-2xl font-semibold text-green-600">
            {{ formatCurrency(summary.total_idr, 'IDR') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Total Nilai BM (USD) -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-purple-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
            </svg>
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">Total Nilai BM (USD)</p>
          <div v-if="loading" class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-24"></div>
          </div>
          <p v-else class="text-2xl font-semibold text-purple-600">
            {{ formatCurrency(summary.total_usd, 'USD') }}
          </p>
        </div>
      </div>
    </div>

    <!-- Total BM Sudah Dimatch -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
      <div class="flex items-center">
        <div class="flex-shrink-0">
          <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center">
            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
          </div>
        </div>
        <div class="ml-4">
          <p class="text-sm font-medium text-gray-500">Total BM Sudah Dimatch</p>
          <div v-if="loading" class="animate-pulse">
            <div class="h-6 bg-gray-200 rounded w-16"></div>
          </div>
          <p v-else class="text-2xl font-semibold text-orange-600">
            {{ formatNumber(summary.total_matched) }}
          </p>
        </div>
      </div>
    </div>
  </div>
</template>
