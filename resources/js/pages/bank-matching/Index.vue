<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { useSecureDownload } from "@/composables/useSecureDownload";
import { getIconForPage } from "@/lib/iconMapping";
import { Calendar, Save, RefreshCw } from "lucide-vue-next";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import axios from 'axios';

// Configure axios to include CSRF token
axios.defaults.headers.common['X-CSRF-TOKEN'] = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Matching" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();
const { downloadFile } = useSecureDownload();

const page = usePage();
const matchingResults = (page.props.matchingResults as any[]) || [];
const filters = (page.props.filters as any) || {};

// Debug: Log the matching results received from backend
console.log('Matching results from backend:', matchingResults);
console.log('Sample match data:', matchingResults[0]);
console.log('Sample match keys:', matchingResults[0] ? Object.keys(matchingResults[0]) : 'No data');

const startDate = ref((filters.start_date as string) || new Date().toISOString().slice(0, 10));
const endDate = ref((filters.end_date as string) || new Date().toISOString().slice(0, 10));

const isSubmitting = ref(false);
// Only show data if there are actual matching results, not just filters
const hasSearched = ref(matchingResults.length > 0);

// Custom confirmation dialog
const showConfirmDialog = ref(false);
const confirmMessage = ref('');

// Watch untuk memastikan hasSearched tetap konsisten
watch(() => matchingResults.length, (newLength) => {
  hasSearched.value = newLength > 0;
});

// Remove the filter watcher since we only want to show data when there are actual results
// watch(() => [filters.start_date, filters.end_date], ([start, end]) => {
//   if (start && end) {
//     hasSearched.value = true;
//   }
// });


function formatRupiah(value: number | string) {
  if (value === 'N/A' || value === '-') return value;
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(Number(value));
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
    return date.toISOString().split('T')[0]; // Format: YYYY-MM-DD
  }
  return new Date(date).toISOString().split('T')[0]; // Format: YYYY-MM-DD
}

function handleDateChange() {
  // Reset search state when dates change
  // hasSearched akan diupdate otomatis oleh watcher
}

function saveAllMatches() {
  if (matchingResults.length === 0) {
    addError('Tidak ada data yang dapat disimpan.');
    return;
  }

  // Show custom confirmation dialog instead of browser alert
  confirmMessage.value = `Apakah Anda yakin ingin menyimpan ${matchingResults.length} data matching?`;
  showConfirmDialog.value = true;
}

function confirmSaveMatches() {
  isSubmitting.value = true;

  const matchesToSave = matchingResults.map((match: any) => {
    console.log('Processing match:', match);
    console.log('Match keys:', Object.keys(match));

    return {
      sj_no: String(match.sj_no || match.no_invoice || ''),
      bank_masuk_id: parseInt(match.bank_masuk_id) || 0,
      no_invoice: String(match.no_invoice || ''),
      tanggal_invoice: formatDateForBackend(match.tanggal_invoice),
      nilai_invoice: parseFloat(match.nilai_invoice) || 0,
      no_bank_masuk: String(match.no_bank_masuk || ''),
      tanggal_bank_masuk: formatDateForBackend(match.tanggal_bank_masuk),
      nilai_bank_masuk: parseFloat(match.nilai_bank_masuk) || 0,
    };
  });

  // Debug: Log the processed data
  console.log('Processed matches data:', matchesToSave);
  console.log('Sample processed match:', matchesToSave[0]);

  // Validate data before sending
  const invalidMatches = matchesToSave.filter(match => {
    const missingFields = [];

    // Check if required fields exist and have valid values
    if (!match.sj_no || match.sj_no === '') missingFields.push('sj_no');
    if (!match.bank_masuk_id || match.bank_masuk_id <= 0) missingFields.push('bank_masuk_id');
    if (!match.no_invoice || match.no_invoice === '') missingFields.push('no_invoice');
    if (!match.tanggal_invoice || match.tanggal_invoice === '') missingFields.push('tanggal_invoice');
    if (match.nilai_invoice === null || match.nilai_invoice === undefined) missingFields.push('nilai_invoice');
    if (!match.no_bank_masuk || match.no_bank_masuk === '') missingFields.push('no_bank_masuk');
    if (!match.tanggal_bank_masuk || match.tanggal_bank_masuk === '') missingFields.push('tanggal_bank_masuk');
    if (match.nilai_bank_masuk === null || match.nilai_bank_masuk === undefined) missingFields.push('nilai_bank_masuk');

    if (missingFields.length > 0) {
      console.log('Invalid match data:', match);
      console.log('Missing fields:', missingFields);
    }

    return missingFields.length > 0;
  });

  if (invalidMatches.length > 0) {
    console.log('Invalid matches details:', invalidMatches);
    addError(`Data tidak valid: ${invalidMatches.length} item memiliki data yang tidak lengkap.`);
    isSubmitting.value = false;
    showConfirmDialog.value = false;
    return;
  }

  // Debug: Log the data being sent
  console.log('Sending matches data:', matchesToSave);

  // Log the request details
  console.log('Request URL:', '/bank-matching');
  console.log('Request method:', 'POST');
  console.log('Request data:', { matches: matchesToSave });

  // Send data with current date filters
  const requestData = {
    matches: matchesToSave,
    start_date: formatDateForBackend(startDate.value),
    end_date: formatDateForBackend(endDate.value)
  };

  axios.post('/bank-matching', requestData)
  .then((response) => {
    console.log('Success response:', response);
    addSuccess('Data bank matching berhasil disimpan.');

    // Refresh data dengan router.get() untuk refresh otomatis tanpa manual refresh
    router.get('/bank-matching', {
      start_date: formatDateForBackend(startDate.value),
      end_date: formatDateForBackend(endDate.value),
      perform_match: 'true'
    }, {
      preserveScroll: true,
      onSuccess: () => {
        // Dispatch event untuk memberitahu komponen lain bahwa tabel telah berubah
        window.dispatchEvent(new CustomEvent('table-changed'));
      }
    });
  })
  .catch((error) => {
    console.error('Error response:', error.response);
    console.error('Error status:', error.response?.status);
    console.error('Error data:', error.response?.data);
    console.error('Error headers:', error.response?.headers);

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

function performMatch() {
  // Format dates properly for backend
  const formattedStartDate = formatDateForBackend(startDate.value);
  const formattedEndDate = formatDateForBackend(endDate.value);

  router.get('/bank-matching', {
    start_date: formattedStartDate,
    end_date: formattedEndDate,
    perform_match: 'true',
  }, {
    preserveScroll: true,
    onSuccess: () => {
      // Dispatch event untuk memberitahu komponen lain bahwa tabel telah berubah
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}



async function exportUnmatchedInvoices() {
  // Format dates properly for backend - convert to string format YYYY-MM-DD
  const formattedStartDate = formatDateForBackend(startDate.value);
  const formattedEndDate = formatDateForBackend(endDate.value);

  console.log('Export dates:', {
    original: { start: startDate.value, end: endDate.value },
    formatted: { start: formattedStartDate, end: formattedEndDate }
  });

  const params = new URLSearchParams({
    start_date: formattedStartDate,
    end_date: formattedEndDate,
  });

  // Show loading state
  const exportButton = document.querySelector('[data-export-button]') as HTMLButtonElement;
  const originalText = exportButton?.textContent;
  if (exportButton) {
    exportButton.disabled = true;
    exportButton.innerHTML = '<svg class="animate-spin -ml-1 mr-3 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Exporting...';
  }

  try {
    await downloadFile(`/bank-matching/export-excel?${params.toString()}`, `bank_matching_unmatched_invoices_${formattedStartDate}_${formattedEndDate}.xlsx`);
    addSuccess('File berhasil diunduh.');
  } catch (error: any) {
    console.error('Export error:', error);

    if (error.response?.status === 404) {
      addError('Endpoint export tidak ditemukan.');
    } else if (error.response?.status === 500) {
      addError('Terjadi kesalahan server saat export.');
    } else if (error.code === 'ECONNABORTED') {
      addError('Export timeout. Silakan coba lagi.');
    } else {
      addError('Gagal mengunduh file. Silakan coba lagi.');
    }
  } finally {
    // Restore button state
    if (exportButton) {
      exportButton.disabled = false;
      exportButton.innerHTML = originalText || '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>Export Unmatched';
    }
  }
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bank Matching</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <component :is="getIconForPage('Bank Matching')" class="w-4 h-4 mr-1" />
            Matching data Invoice dengan Bank Masuk
          </div>
        </div>
      </div>

    <!-- Filter Section -->
    <div class="bg-[#FFFFFF] rounded-t-lg shadow-sm border-t border-gray-200">
      <div class="px-6 py-4">
        <div class="flex items-center gap-4 flex-wrap justify-between">
          <!-- LEFT: Filter Controls -->
          <div class="flex items-center gap-4 flex-wrap">
            <div class="flex items-center gap-2">
              <Calendar class="w-4 h-4 text-gray-500" />
              <span class="text-sm font-medium text-gray-700">Periode:</span>
            </div>

            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">Dari:</span>
              <Datepicker
                v-model="startDate"
                @update:model-value="handleDateChange"
                format="yyyy-MM-dd"
                :enable-time-picker="false"
                :auto-apply="true"
                :close-on-auto-apply="true"
                class="w-32"
              />
            </div>

            <div class="flex items-center gap-2">
              <span class="text-sm text-gray-600">Sampai:</span>
              <Datepicker
                v-model="endDate"
                @update:model-value="handleDateChange"
                format="yyyy-MM-dd"
                :enable-time-picker="false"
                :auto-apply="true"
                :close-on-auto-apply="true"
                class="w-32"
              />
            </div>
          </div>

          <!-- RIGHT: Action Buttons -->
          <div class="flex items-center gap-3">
            <button
              @click="performMatch"
              class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[#101010] border border-[#101010] rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
            >
              <RefreshCw class="w-4 h-4" />
              Match
            </button>

            <button
              @click="exportUnmatchedInvoices"
              data-export-button
              class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200"
            >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0,0,256,256" fill="currentColor">
              <g fill="currentColor" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                <g transform="scale(5.12,5.12)">
                  <path d="M28.875,0c-0.01953,0.00781 -0.04297,0.01953 -0.0625,0.03125l-28,5.3125c-0.47656,0.08984 -0.82031,0.51172 -0.8125,1v37.3125c-0.00781,0.48828 0.33594,0.91016 0.8125,1l28,5.3125c0.28906,0.05469 0.58984,-0.01953 0.82031,-0.20703c0.22656,-0.1875 0.36328,-0.46484 0.36719,-0.76172v-5h17c1.09375,0 2,-0.90625 2,-2v-34c0,-1.09375 -0.90625,-2 -2,-2h-17v-5c0.00391,-0.28906 -0.12109,-0.5625 -0.33594,-0.75391c-0.21484,-0.19141 -0.50391,-0.28125 -0.78906,-0.24609zM28,2.1875v4.34375c-0.13281,0.27734 -0.13281,0.59766 0,0.875v35.40625c-0.02734,0.13281 -0.02734,0.27344 0,0.40625v4.59375l-26,-4.96875v-35.6875zM30,8h17v34h-17v-5h4v-2h-4v-6h4v-2h-4v-5h4v-2h-4v-5h4v-2h-4zM36,13v2h8v-2zM6.6875,15.6875l5.46875,9.34375l-5.96875,9.34375h5l3.25,-6.03125c0.22656,-0.58203 0.375,-1.02734 0.4375,-1.3125h0.03125c0.12891,0.60938 0.25391,1.02344 0.375,1.25l3.25,6.09375h4.96875l-5.75,-9.4375l5.59375,-9.25h-4.6875l-2.96875,5.53125c-0.28516,0.72266 -0.48828,1.29297 -0.59375,1.65625h-0.03125c-0.16406,-0.60937 -0.35156,-1.15234 -0.5625,-1.59375l-2.6875,-5.59375zM36,20v2h8v-2zM36,27v2h8v-2zM36,35v2h8v-2z"></path>
                </g>
              </g>
            </svg>
              Export Unmatched
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons - Only show if data exists -->
    <div v-if="hasSearched && matchingResults.length > 0" class="bg-[#FFFFFF] border-t border-gray-200 px-6 py-4">
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-2">
          <button
            @click="saveAllMatches"
            :disabled="matchingResults.length === 0 || isSubmitting"
            class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-[#101010] border border-[#101010] rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Save class="w-4 h-4" />
            {{ isSubmitting ? 'Menyimpan...' : 'Save All Matches' }}
          </button>
        </div>

        <div class="text-sm text-gray-600">
          Total Match: {{ matchingResults.length }} data
        </div>
      </div>
    </div>



    <!-- Empty State - Show when no search performed -->
    <div v-if="!hasSearched" class="bg-[#FFFFFF] rounded-b-lg shadow-sm border border-gray-200">
      <div class="px-6 py-12 text-center">
        <div class="mx-auto h-12 w-12 text-gray-400">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tabel Kosong</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih periode waktu dan tekan tombol "Match" untuk melihat data matching.</p>
        <p class="mt-2 text-xs text-blue-600">
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Data yang sudah dimatch sebelumnya otomatis difilter dan tidak ditampilkan
        </p>
      </div>
    </div>

    <!-- Matching Results Table - Only show if data exists -->
    <div v-if="hasSearched && matchingResults.length > 0" class="bg-[#FFFFFF] rounded-b-lg shadow-sm border-b border-gray-200">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Data Matching ({{ matchingResults.length }})</h3>
        <p class="text-sm text-gray-600">Data Invoice yang match dengan Bank Masuk berdasarkan periode yang dipilih</p>
        <p class="text-xs text-blue-600 mt-1">
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Data yang sudah dimatch sebelumnya otomatis difilter dan tidak ditampilkan
        </p>
      </div>

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
            <tr v-for="(match, index) in matchingResults" :key="index"
                :class="match.is_matched ? 'bg-green-50' : 'bg-gray-50'" class="alternating-row">
              <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
                {{ match.no_invoice }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
                {{ formatDate(match.tanggal_invoice) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
                {{ formatRupiah(match.nilai_invoice) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
                {{ match.no_bank_masuk }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
                {{ formatDate(match.tanggal_bank_masuk) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
                {{ formatRupiah(match.nilai_bank_masuk) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- No Results State -->
    <div v-if="hasSearched && matchingResults.length === 0" class="bg-[#FFFFFF] rounded-lg shadow-sm border border-gray-200">
      <div class="px-6 py-12 text-center">
        <div class="mx-auto h-12 w-12 text-gray-400">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data matching</h3>
        <p class="mt-1 text-sm text-gray-500">Tidak ditemukan data yang match untuk periode yang dipilih.</p>
        <p class="mt-2 text-xs text-blue-600">
          <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          Data yang sudah dimatch sebelumnya tidak ditampilkan
        </p>
        <p class="mt-1 text-xs text-gray-500">
          Coba ubah periode waktu atau periksa apakah ada data baru yang belum dimatch.
        </p>
      </div>
    </div>
    </div>
  </div>

  <ConfirmDialog
    :show="showConfirmDialog"
    :message="confirmMessage"
    @confirm="confirmSaveMatches"
    @cancel="cancelSaveMatches"
  />
</template>

<style scoped>
.alternating-row:nth-child(even) {
  background-color: #f9fafb;
}

.alternating-row:nth-child(odd) {
  background-color: #ffffff;
}

.alternating-row:hover {
  background-color: #f3f4f6;
}
</style>
