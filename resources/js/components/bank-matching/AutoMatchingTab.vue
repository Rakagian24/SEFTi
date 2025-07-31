<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { RefreshCw, Save, Download } from 'lucide-vue-next';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';
import { useSecureDownload } from '@/composables/useSecureDownload';
import axios from 'axios';

interface MatchingResult {
  no_invoice: string;
  tanggal_invoice: string;
  nilai_invoice: number;
  no_bank_masuk: string;
  tanggal_bank_masuk: string;
  nilai_bank_masuk: number;
  sj_no: string;
  bank_masuk_id: number;
  currency?: string; // Added currency to the interface
}

const props = defineProps({
  matchingResults: {
    type: Array as () => MatchingResult[],
    default: () => []
  },
  filters: {
    type: Object,
    default: () => ({})
  }
});

const { addSuccess, addError } = useMessagePanel();
const { downloadFile } = useSecureDownload();

const isSubmitting = ref(false);
const showConfirmDialog = ref(false);
const confirmMessage = ref('');

function formatCurrency(value: number | string, currency: string = 'IDR') {
  if (value === 'N/A' || value === '-') return value;

  const numValue = Number(value);
  if (isNaN(numValue)) return value;

  switch (currency?.toUpperCase()) {
    case 'USD':
      return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD',
        minimumFractionDigits: 0,
      }).format(numValue);
    case 'EUR':
      return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: 'EUR',
        minimumFractionDigits: 0,
      }).format(numValue);
    case 'SGD':
      return new Intl.NumberFormat('en-SG', {
        style: 'currency',
        currency: 'SGD',
        minimumFractionDigits: 0,
      }).format(numValue);
    case 'IDR':
    default:
      return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
      }).format(numValue);
  }
}


function formatDate(date: string) {
  if (!date || date === 'N/A' || date === '-') return date;
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
}

function formatDateForBackend(date: string | Date) {
  if (!date || date === 'N/A' || date === '-') return date;
  if (date instanceof Date) {
    return date.toISOString().split('T')[0];
  }
  return new Date(date).toISOString().split('T')[0];
}

function performMatch() {
  const params: Record<string, any> = {
    perform_match: 'true'
  };

  if (props.filters.start_date) params.start_date = props.filters.start_date;
  if (props.filters.end_date) params.end_date = props.filters.end_date;

  router.get('/bank-matching', params, {
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function saveAllMatches() {
  if (props.matchingResults.length === 0) {
    addError('Tidak ada data yang dapat disimpan.');
    return;
  }

  confirmMessage.value = `Apakah Anda yakin ingin menyimpan ${props.matchingResults.length} data matching?`;
  showConfirmDialog.value = true;
}

function confirmSaveMatches() {
  isSubmitting.value = true;

  const matchesToSave = props.matchingResults.map((match: MatchingResult) => {
    return {
      sj_no: String(match.sj_no || match.no_invoice || ''),
      bank_masuk_id: parseInt(String(match.bank_masuk_id)) || 0,
      no_invoice: String(match.no_invoice || ''),
      tanggal_invoice: formatDateForBackend(match.tanggal_invoice),
      nilai_invoice: parseFloat(String(match.nilai_invoice)) || 0,
      no_bank_masuk: String(match.no_bank_masuk || ''),
      tanggal_bank_masuk: formatDateForBackend(match.tanggal_bank_masuk),
      nilai_bank_masuk: parseFloat(String(match.nilai_bank_masuk)) || 0,
    };
  });

  const requestData = {
    matches: matchesToSave,
    start_date: formatDateForBackend(props.filters.start_date),
    end_date: formatDateForBackend(props.filters.end_date)
  };

  axios.post('/bank-matching', requestData)
  .then(() => {
    addSuccess('Data bank matching berhasil disimpan.');
    performMatch(); // Refresh data
  })
  .catch((error) => {
    if (error.response && error.response.data) {
      const errorData = error.response.data;
      if (errorData.errors) {
        const errorMessages = Object.values(errorData.errors).flat().join(', ');
        addError(`Validasi error: ${errorMessages}`);
      } else if (errorData.message) {
        addError(`Error: ${errorData.message}`);
      } else {
        addError('Terjadi kesalahan saat menyimpan data.');
      }
    } else {
      addError('Terjadi kesalahan saat menyimpan data.');
    }
  })
  .finally(() => {
    isSubmitting.value = false;
    showConfirmDialog.value = false;
  });
}

function cancelSaveMatches() {
  showConfirmDialog.value = false;
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
</script>

<template>
  <div class="space-y-4">
    <!-- Action Buttons -->
    <div class="bg-white border-t border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <button
            @click="performMatch"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[#101010] border border-[#101010] rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
          >
            <RefreshCw class="w-4 h-4" />
            Match
          </button>

          <button
            v-if="matchingResults.length > 0"
            @click="saveAllMatches"
            :disabled="matchingResults.length === 0 || isSubmitting"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Save class="w-4 h-4" />
            {{ isSubmitting ? 'Menyimpan...' : 'Save All Matches' }}
          </button>

          <button
            @click="exportUnmatchedInvoices"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200"
          >
            <Download class="w-4 h-4" />
            Export Unmatched
          </button>
        </div>

        <div v-if="matchingResults.length > 0" class="text-sm text-gray-600">
          Total Match: {{ matchingResults.length }} data
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="matchingResults.length === 0" class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-12 text-center">
        <div class="mx-auto h-12 w-12 text-gray-400">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data matching</h3>
        <p class="mt-1 text-sm text-gray-500">Tekan tombol "Match" untuk melihat data yang bisa dimatch.</p>
        <p class="mt-2 text-xs text-blue-600">
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Data yang sudah dimatch sebelumnya otomatis difilter dan tidak ditampilkan
        </p>
      </div>
    </div>

    <!-- Matching Results Table -->
    <div v-if="matchingResults.length > 0" class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
      <div class="overflow-x-auto rounded-lg">
        <table class="min-w-full">
          <thead class="bg-[#FFFFFF] border-b border-gray-200">
            <tr>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No Invoice</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Tanggal Invoice</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nilai Invoice</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No Bank Masuk</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Tanggal Bank Masuk</th>
              <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nilai Bank Masuk</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-200">
            <tr v-for="(match, index) in matchingResults" :key="index" class="alternating-row">
              <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {{ match.no_invoice }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
                {{ formatDate(match.tanggal_invoice) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
                {{ formatCurrency(match.nilai_invoice, match.currency || 'IDR') }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
                {{ match.no_bank_masuk }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
                {{ formatDate(match.tanggal_bank_masuk) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
                {{ formatCurrency(match.nilai_bank_masuk, match.currency || 'IDR') }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ConfirmDialog
      :show="showConfirmDialog"
      :message="confirmMessage"
      @confirm="confirmSaveMatches"
      @cancel="cancelSaveMatches"
    />
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
