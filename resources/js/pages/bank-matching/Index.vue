<script setup lang="ts">
import { ref, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { Calendar, Save, RefreshCw } from "lucide-vue-next";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import axios from 'axios';

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Matching" }
];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const page = usePage();
const matchingResults = (page.props.matchingResults as any[]) || [];
const filters = (page.props.filters as any) || {};

const startDate = ref((filters.start_date as string) || new Date().toISOString().slice(0, 10));
const endDate = ref((filters.end_date as string) || new Date().toISOString().slice(0, 10));

const selectedMatches = ref<any[]>([]);
const isSubmitting = ref(false);
const hasSearched = ref(false);

// Computed untuk data yang sudah dimatch
const matchedData = computed(() => {
  return matchingResults.filter((item: any) => item.is_matched);
});



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

function handleDateChange() {
  // Reset search state when dates change
  hasSearched.value = false;
}

function toggleMatchSelection(match: any) {
  if (!match.is_matched) return;

  const index = selectedMatches.value.findIndex(
    (item: any) => item.kwitansi_id === match.kwitansi_id && item.bank_masuk_id === match.bank_masuk_id
  );

  if (index > -1) {
    selectedMatches.value.splice(index, 1);
  } else {
    selectedMatches.value.push({
      kwitansi_id: match.kwitansi_id,
      bank_masuk_id: match.bank_masuk_id,
      no_invoice: match.no_invoice,
      tanggal_invoice: match.tanggal_invoice,
      nilai_invoice: match.nilai_invoice,
      no_bank_masuk: match.no_bank_masuk,
      tanggal_bank_masuk: match.tanggal_bank_masuk,
      nilai_bank_masuk: match.nilai_bank_masuk,
    });
  }
}

function isMatchSelected(match: any) {
  if (!match.is_matched) return false;

  return selectedMatches.value.some(
    (item: any) => item.kwitansi_id === match.kwitansi_id && item.bank_masuk_id === match.bank_masuk_id
  );
}

function selectAllMatches() {
  selectedMatches.value = matchedData.value.map((match: any) => ({
    kwitansi_id: match.kwitansi_id,
    bank_masuk_id: match.bank_masuk_id,
    no_invoice: match.no_invoice,
    tanggal_invoice: match.tanggal_invoice,
    nilai_invoice: match.nilai_invoice,
    no_bank_masuk: match.no_bank_masuk,
    tanggal_bank_masuk: match.tanggal_bank_masuk,
    nilai_bank_masuk: match.nilai_bank_masuk,
  }));
}

function clearAllSelections() {
  selectedMatches.value = [];
}

function saveMatches() {
  if (selectedMatches.value.length === 0) {
    addError('Pilih minimal satu data untuk disimpan.');
    return;
  }

  isSubmitting.value = true;

  axios.post('/bank-matching', {
    matches: selectedMatches.value
  })
  .then(() => {
    addSuccess('Data bank matching berhasil disimpan.');
    selectedMatches.value = [];
    router.reload();
  })
  .catch((error) => {
    addError('Terjadi kesalahan saat menyimpan data.');
    console.error(error);
  })
  .finally(() => {
    isSubmitting.value = false;
  });
}

function performMatch() {
  hasSearched.value = true;
  router.get('/bank-matching', {
    start_date: startDate.value,
    end_date: endDate.value,
  });
}
</script>

<template>
  <div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-semibold text-gray-900">Bank Matching</h1>
        <p class="text-sm text-gray-600">Matching data Invoice dengan Bank Masuk</p>
      </div>
      <Breadcrumbs :items="breadcrumbs" />
    </div>

    <!-- Filter Section -->
    <div class="bg-white rounded-lg shadow p-6">
      <div class="flex items-center gap-4">
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
            class="w-32"
          />
        </div>

        <button
          @click="performMatch"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-md hover:bg-blue-700"
        >
          <RefreshCw class="w-4 h-4" />
          Match
        </button>
      </div>
    </div>

    <!-- Action Buttons - Only show if data exists -->
    <div v-if="hasSearched && matchingResults.length > 0" class="flex items-center justify-between">
      <div class="flex items-center gap-2">
        <button
          @click="selectAllMatches"
          class="px-4 py-2 text-sm font-medium text-blue-700 bg-blue-100 border border-blue-300 rounded-md hover:bg-blue-200"
        >
          Pilih Semua
        </button>
        <button
          @click="clearAllSelections"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
        >
          Batal Pilih
        </button>
        <button
          @click="saveMatches"
          :disabled="selectedMatches.length === 0 || isSubmitting"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-white bg-green-600 border border-green-600 rounded-md hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          <Save class="w-4 h-4" />
          {{ isSubmitting ? 'Menyimpan...' : 'Save Data' }}
        </button>
      </div>

      <div class="text-sm text-gray-600">
        Dipilih: {{ selectedMatches.length }} dari {{ matchedData.length }} data
      </div>
    </div>

    <!-- Summary Cards - Only show if data exists -->
    <div v-if="hasSearched && matchingResults.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-4">
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm font-medium text-gray-600">Total Data</div>
        <div class="text-2xl font-bold text-blue-600">{{ matchingResults.length }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm font-medium text-gray-600">Auto Match</div>
        <div class="text-2xl font-bold text-green-600">{{ matchedData.length }}</div>
      </div>
      <div class="bg-white rounded-lg shadow p-4">
        <div class="text-sm font-medium text-gray-600">Dipilih</div>
        <div class="text-2xl font-bold text-orange-600">{{ selectedMatches.length }}</div>
      </div>
    </div>

    <!-- Empty State - Show when no search performed -->
    <div v-if="!hasSearched" class="bg-white rounded-lg shadow">
      <div class="px-6 py-12 text-center">
        <div class="mx-auto h-12 w-12 text-gray-400">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tabel Kosong</h3>
        <p class="mt-1 text-sm text-gray-500">Pilih periode waktu dan tekan tombol "Match" untuk melihat data.</p>
      </div>
    </div>

    <!-- Matching Results Table - Only show if data exists -->
    <div v-if="hasSearched && matchingResults.length > 0" class="bg-white rounded-lg shadow">
      <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Hasil Matching ({{ matchingResults.length }})</h3>
        <p class="text-sm text-gray-600">Data Invoice dan Bank Masuk berdasarkan periode yang dipilih</p>
      </div>

      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                <input
                  type="checkbox"
                  :checked="selectedMatches.length === matchedData.length && matchedData.length > 0"
                  @change="selectedMatches.length === matchedData.length ? clearAllSelections() : selectAllMatches()"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
              </th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Invoice</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Invoice</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Invoice</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No Bank Masuk</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal Bank Masuk</th>
              <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Bank Masuk</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(match, index) in matchingResults" :key="index"
                :class="match.is_matched ? 'bg-green-50' : 'bg-gray-50'">
              <td class="px-6 py-4 whitespace-nowrap">
                <input
                  v-if="match.is_matched"
                  type="checkbox"
                  :checked="isMatchSelected(match)"
                  @change="toggleMatchSelection(match)"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span v-else class="text-gray-400">-</span>
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ match.no_invoice }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(match.tanggal_invoice) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatRupiah(match.nilai_invoice) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ match.no_bank_masuk }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatDate(match.tanggal_bank_masuk) }}
              </td>
              <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                {{ formatRupiah(match.nilai_bank_masuk) }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- No Results State -->
    <div v-if="hasSearched && matchingResults.length === 0" class="bg-white rounded-lg shadow">
      <div class="px-6 py-12 text-center">
        <div class="mx-auto h-12 w-12 text-gray-400">
          <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
          </svg>
        </div>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data</h3>
        <p class="mt-1 text-sm text-gray-500">Tidak ditemukan data untuk periode yang dipilih.</p>
      </div>
    </div>
  </div>
</template>
