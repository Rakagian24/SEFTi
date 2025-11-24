<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { formatDate, formatNumber } from '@/lib/formatters';

const props = defineProps<{
  pengeluaranBarang: any;
  show: boolean;
}>();

const emit = defineEmits(['close']);

const items = computed(() => props.pengeluaranBarang?.items || []);

const searchQuery = ref('');
const debouncedQuery = ref('');
let searchTimeout: ReturnType<typeof setTimeout> | null = null;
const pageSize = ref<number>(10);
const currentPage = ref<number>(1);

const filteredItems = computed(() => {
  const q = debouncedQuery.value.trim().toLowerCase();
  if (!q) return items.value;

  return items.value.filter((item: any) => {
    const name = (item.barang?.nama_barang || '').toLowerCase();
    const note = (item.keterangan || '').toLowerCase();
    return name.includes(q) || note.includes(q);
  });
});

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredItems.value.length / pageSize.value))
);

const pagedItems = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return filteredItems.value.slice(start, start + pageSize.value);
});

watch([items, pageSize, searchQuery], () => {
  currentPage.value = 1;
});

watch(searchQuery, (value) => {
  if (searchTimeout) {
    clearTimeout(searchTimeout);
  }

  searchTimeout = setTimeout(() => {
    debouncedQuery.value = value;
  }, 300);
});

function handleClose() {
  emit('close');
}
</script>

<template>
  <div
    v-if="show && pengeluaranBarang"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/40"
    @click.self="handleClose"
  >
    <div class="bg-white rounded-xl shadow-xl max-w-5xl w-full mx-4 overflow-hidden">
      <!-- Header -->
      <div class="flex items-start justify-between px-6 pt-6 pb-4 border-b border-gray-200">
        <div>
          <h2 class="text-lg font-semibold text-gray-900">
            {{ pengeluaranBarang.no_pengeluaran }}
          </h2>
          <p class="text-sm text-gray-500 mt-1">
            {{ formatDate(pengeluaranBarang.tanggal) }}
          </p>
        </div>
        <button
          type="button"
          class="text-gray-400 hover:text-gray-600 transition-colors"
          @click="handleClose"
        >
          <span class="sr-only">Close</span>
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Table + Toolbar -->
      <div class="px-6 py-4">
        <!-- Toolbar -->
        <div class="mb-3 flex items-center gap-3 justify-end">
          <div class="text-sm text-gray-600 flex items-center gap-2">
            <span>Show</span>
            <select
              v-model.number="pageSize"
              class="px-2 py-1 text-sm border border-gray-300 rounded-md"
            >
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
            <span>entries</span>
          </div>
          <div class="relative">
            <input
              v-model="searchQuery"
              type="text"
              placeholder="Search..."
              class="pl-7 pr-3 py-1.5 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            />
            <svg
              class="w-4 h-4 absolute left-2 top-1/2 -translate-y-1/2 text-gray-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M21 21l-4.35-4.35M10 18a8 8 0 100-16 8 8 0 000 16z"
              />
            </svg>
          </div>
        </div>

        <div class="overflow-x-auto">
          <table class="min-w-full">
            <thead>
              <tr class="text-left text-xs font-semibold text-gray-600 border-b border-gray-200">
                <th class="px-4 py-3 whitespace-nowrap">Nama Barang</th>
                <!-- <th class="px-4 py-3 whitespace-nowrap">Stok Tersedia</th> -->
                <th class="px-4 py-3 whitespace-nowrap">Satuan</th>
                <th class="px-4 py-3 whitespace-nowrap">Qty Keluar</th>
                <th class="px-4 py-3 whitespace-nowrap">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr
                v-for="(item, index) in pagedItems"
                :key="item.id || index"
                class="border-b border-gray-100 last:border-0"
              >
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ item.barang?.nama_barang || '-' }}
                </td>
                <!-- <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap"> -->
                  <!-- Belum ada informasi stok tersedia per item di detail; tampilkan '-' dulu -->
                  <!-- - -->
                <!-- </td> -->
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ item.barang?.satuan || '-' }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900 whitespace-nowrap">
                  {{ formatNumber(item.qty) }}
                </td>
                <td class="px-4 py-3 text-sm text-gray-900">
                  {{ item.keterangan || '-' }}
                </td>
              </tr>
              <tr v-if="!pagedItems.length">
                <td colspan="5" class="px-4 py-6 text-center text-sm text-gray-500">
                  Tidak ada item pengeluaran.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Footer Pagination -->
      <div class="px-6 pb-4 pt-2 border-t border-gray-200 flex items-center justify-center text-xs text-gray-500">
        <nav class="flex items-center space-x-2" aria-label="Pagination">
            <!-- Previous Button -->
            <button
              type="button"
              @click="currentPage > 1 && (currentPage = currentPage - 1)"
              :disabled="currentPage === 1"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                currentPage === 1
                  ? 'text-gray-400 cursor-not-allowed'
                  : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50',
              ]"
            >
              Previous
            </button>

            <!-- Page Numbers -->
            <button
              v-for="n in totalPages"
              :key="n"
              type="button"
              @click="currentPage = n"
              :class="[
                'w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200',
                currentPage === n
                  ? 'bg-black text-white'
                  : 'bg-gray-200 text-gray-600 hover:bg-gray-300',
              ]"
            >
              {{ n }}
            </button>

            <!-- Next Button -->
            <button
              type="button"
              @click="currentPage < totalPages && (currentPage = currentPage + 1)"
              :disabled="currentPage === totalPages"
              :class="[
                'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
                currentPage === totalPages
                  ? 'text-gray-400 cursor-not-allowed'
                  : 'text-gray-600 hover:text-gray-800 hover:bg-gray-50',
              ]"
            >
              Next
            </button>
          </nav>
      </div>
    </div>
  </div>
</template>
