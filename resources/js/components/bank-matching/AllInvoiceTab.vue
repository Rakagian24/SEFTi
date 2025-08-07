<script setup lang="ts">
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';
import { Download } from 'lucide-vue-next';
import Pagination from '@/components/ui/Pagination.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';
import { useSecureDownload } from '@/composables/useSecureDownload';

interface InvoiceData {
  faktur_id: string;
  tanggal: string;
  nominal: number;
  nama_customer: string;
  cabang: string;
  is_matched: boolean;
}

interface PaginationData {
  current_page: number;
  last_page: number;
  per_page: number;
  total: number;
  from?: number;
  to?: number;
}

interface Props {
  filters: {
    start_date: string;
    end_date: string;
    search: string;
    per_page: number;
    department_id?: string;
  };
}

const props = defineProps<Props>();

const invoiceData = ref<InvoiceData[]>([]);
const pagination = ref<PaginationData>({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
});
const loading = ref(false);
const error = ref('');

const { addSuccess, addError } = useMessagePanel();
const { downloadFile } = useSecureDownload();

function formatNumber(value: number | string) {
  if (value === 'N/A' || value === '-') return value;

  const numValue = Number(value);
  if (isNaN(numValue)) return value;

  // Format tanpa rounding - tampilkan decimal sesuai aslinya
  let formattedNumber: string;

  if (Number.isInteger(numValue)) {
    // Jika integer, tampilkan tanpa decimal
    formattedNumber = numValue.toLocaleString('en-US');
  } else {
    // Jika ada decimal, tampilkan sesuai aslinya tanpa rounding
    const decimalPlaces = (numValue.toString().split('.')[1] || '').length;
    formattedNumber = numValue.toLocaleString('en-US', {
      minimumFractionDigits: decimalPlaces,
      maximumFractionDigits: decimalPlaces,
    });
  }

  return formattedNumber;
}

function formatDate(date: string) {
  if (!date || date === 'N/A' || date === '-') return date;
  try {
    const dateObj = new Date(date);
    if (isNaN(dateObj.getTime())) {
      console.warn('Invalid date format:', date);
      return date;
    }
    return dateObj.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric'
    });
  } catch (error) {
    console.error('Error formatting date:', date, error);
    return date;
  }
}

function formatDateForBackend(date: string | Date) {
  if (!date || date === 'N/A' || date === '-') return date;
  if (date instanceof Date) {
    return date.toISOString().split('T')[0];
  }
  return new Date(date).toISOString().split('T')[0];
}

async function exportUnmatchedInvoices() {
  const formattedStartDate = formatDateForBackend(props.filters.start_date);
  const formattedEndDate = formatDateForBackend(props.filters.end_date);

  const params = new URLSearchParams({
    start_date: formattedStartDate,
    end_date: formattedEndDate,
  });

  try {
    await downloadFile(`/bank-matching/export-excel?${params.toString()}`, `bank_matching_unmatched_invoices_${formattedStartDate}_${formattedEndDate}.xlsx`);
    addSuccess('File berhasil diunduh.');
  } catch (error: any) {
    console.error('Export error:', error);
    addError('Gagal mengunduh file. Silakan coba lagi.');
  }
}

async function loadInvoiceData(page = 1) {
  loading.value = true;
  error.value = '';

  try {
    const params = {
      page,
      per_page: props.filters.per_page,
      start_date: props.filters.start_date,
      end_date: props.filters.end_date,
      search: props.filters.search,
      department_id: props.filters.department_id
    };

    const response = await axios.get('/bank-matching/all-invoices', { params });

    invoiceData.value = response.data.data || [];
    pagination.value = {
      current_page: response.data.current_page || 1,
      last_page: response.data.last_page || 1,
      per_page: response.data.per_page || 10,
      total: response.data.total || 0
    };

    // Check if there's a message about database availability
    if (response.data.message) {
      console.warn('Database message:', response.data.message);
    }
  } catch (error: any) {
    console.error('Error loading invoice data:', error);
    error.value = error.response?.data?.message || 'Error loading invoice data: ' + error.message;
    invoiceData.value = [];
  } finally {
    loading.value = false;
  }
}



// Watch for filter changes
watch(() => props.filters, () => {
  loadInvoiceData(1);
}, { deep: true });

onMounted(() => {
  loadInvoiceData(1);
});
</script>

<template>
  <div class="space-y-4">
    <!-- Export Button -->
    <div class="bg-white border-t border-gray-200 px-6 py-4">
      <div class="flex items-center justify-end">
        <button
          @click="exportUnmatchedInvoices"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200"
        >
          <Download class="w-4 h-4" />
          Export Unmatched
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center items-center py-8">
      <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
    </div>

    <!-- Error State -->
    <div v-else-if="error" class="bg-red-50 border border-red-200 rounded-lg p-4">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Error loading invoice data</h3>
          <div class="mt-2 text-sm text-red-700">{{ error }}</div>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-else-if="!invoiceData.length" class="text-center py-12">
      <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-gray-100">
        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </div>
      <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data invoice</h3>
      <p class="mt-1 text-sm text-gray-500">Tidak ditemukan data invoice untuk periode yang dipilih.</p>
      <p class="mt-2 text-xs text-yellow-600">
        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
        </svg>
        Database PostgreSQL Nirwana mungkin tidak tersedia atau tidak dapat diakses
      </p>
    </div>

    <!-- Invoice Data Table -->
    <div v-else class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
      <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full">
          <thead class="bg-[#FFFFFF] border-b border-gray-200">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No Invoice</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Tanggal Invoice</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Customer</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Departemen</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nilai Invoice</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(invoice, index) in invoiceData" :key="index" class="alternating-row">
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900">
                {{ invoice.faktur_id }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-[#101010]">
                {{ formatDate(invoice.tanggal) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
                {{ invoice.nama_customer }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
                {{ invoice.cabang }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-[#101010]">
                {{ formatNumber(invoice.nominal) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm">
                <span
                  :class="invoice.is_matched ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'"
                  class="px-2 py-1 text-xs font-medium rounded-full"
                >
                  {{ invoice.is_matched ? 'Matched' : 'Unmatched' }}
                </span>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination Component -->
      <Pagination
        v-if="Number(pagination.last_page) > 1"
        :pagination="pagination"
        @page-changed="loadInvoiceData"
      />
    </div>
  </div>
</template>

<style scoped>
/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Alternating row colors */
.alternating-row:nth-child(even) {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) {
  background-color: #ffffff;
}

/* Hover effect for alternating rows */
.alternating-row:nth-child(even):hover {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover {
  background-color: #f8fafc;
}
</style>
