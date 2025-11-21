<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  rows: any[];
  loading?: boolean;
  currentPage: number;
  perPage: number;
  total: number;
  saldoAwal: number;
  saldoAkhir: number;
  totalMasuk: number;
  totalKeluar: number;
}>();

const emit = defineEmits<{
  'change-page': [page: number];
}>();

const from = computed(() => {
  if (props.total === 0) return 0;
  return (props.currentPage - 1) * props.perPage + 1;
});

const lastPage = computed(() => {
  if (props.perPage <= 0) return 1;
  return Math.max(1, Math.ceil(props.total / props.perPage));
});

function go(page: number) {
  if (page < 1 || page > lastPage.value) return;
  emit('change-page', page);
  window.dispatchEvent(new CustomEvent('table-changed'));
}

function formatDate(val: string) {
  if (!val) return '-';
  const d = new Date(val);
  if (isNaN(d.getTime())) return val;
  return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short', year: 'numeric' });
}

function formatNumber(val: number) {
  return Number(val || 0).toLocaleString('id-ID');
}
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              No
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Referensi
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Tanggal
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-emerald-600 uppercase tracking-wider whitespace-nowrap">
              Masuk
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-rose-600 uppercase tracking-wider whitespace-nowrap">
              Keluar
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Saldo
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="!loading && rows.length === 0">
            <td colspan="6" class="px-6 py-8 text-center text-sm text-gray-500">
              Pilih departemen, barang, dan rentang tanggal untuk menampilkan kartu stock.
            </td>
          </tr>

          <!-- Saldo Awal -->
          <tr v-if="rows.length > 0" class="bg-gray-50 font-medium text-sm text-gray-700">
            <td class="px-6 py-4" colspan="5">Saldo Awal</td>
            <td class="px-6 py-4 text-left font-medium tabular-nums">{{ formatNumber(saldoAwal) }}</td>
          </tr>

          <!-- Mutasi -->
          <tr
            v-for="(row, idx) in rows"
            :key="`${row.referensi}-${idx}`"
            class="alternating-row"
          >
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ from + idx }}
            </td>
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.referensi || '-' }}
            </td>
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatDate(row.tanggal) }}
            </td>
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-emerald-600 font-medium tabular-nums">
              + {{ row.masuk ? '+' + formatNumber(row.masuk) : '-' }}
            </td>
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-rose-600 font-medium tabular-nums">
              - {{ row.keluar ? '-' + formatNumber(row.keluar) : '-' }}
            </td>
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-[#101010] font-medium tabular-nums">
              {{ formatNumber(row.saldo) }}
            </td>
          </tr>

          <!-- Saldo Akhir -->
          <tr v-if="rows.length > 0" class="bg-gray-50 font-medium text-sm text-gray-700">
            <td class="px-6 py-4" colspan="5">Saldo Akhir</td>
            <td class="px-6 py-4 text-left font-medium tabular-nums">{{ formatNumber(saldoAkhir) }}</td>
          </tr>

          <!-- Total -->
          <tr v-if="rows.length > 0" class="bg-gray-100 font-semibold text-sm text-gray-800">
            <td class="px-6 py-4" colspan="3">Total</td>
            <td class="px-6 py-4 text-left text-emerald-600 font-medium tabular-nums">{{ formatNumber(totalMasuk) }}</td>
            <td class="px-6 py-4 text-left text-rose-600 font-medium tabular-nums">{{ formatNumber(totalKeluar) }}</td>
            <td class="px-6 py-4 text-left font-medium tabular-nums">{{ formatNumber(saldoAkhir) }}</td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="go(currentPage - 1)"
          :disabled="currentPage <= 1"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            currentPage > 1
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <!-- Page Numbers -->
        <div class="w-10 h-10 flex items-center justify-center text-sm font-medium rounded-lg bg-black text-white">
          {{ currentPage }}
        </div>
        <div class="text-sm text-gray-600">dari {{ lastPage }}</div>

        <!-- Next Button -->
        <button
          @click="go(currentPage + 1)"
          :disabled="currentPage >= lastPage"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            currentPage < lastPage
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Next
        </button>
      </nav>
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

/* Pagination styling enhancements */
nav button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Disabled button styling */
nav button:disabled {
  opacity: 0.5;
}

/* Hover effects for pagination buttons */
nav button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
