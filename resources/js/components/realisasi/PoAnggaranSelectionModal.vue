<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40" @click="close"></div>

    <!-- Panel -->
    <div
      class="relative mx-auto mt-10 max-w-6xl rounded-xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
    >
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold">PO Anggaran</h2>
        <button type="button" @click="close" class="text-gray-500 hover:text-gray-700">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="w-5 h-5"
          >
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
        </button>
      </div>

      <!-- Toolbar -->
      <div class="px-6 py-3 flex items-center gap-2">
        <div class="ml-auto flex items-center gap-3">
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
              @input="onSearchInput"
              type="text"
              placeholder="Search..."
              class="pl-8 pr-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
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
      </div>

      <!-- Table -->
      <div class="px-6 pb-2 max-h-[28rem] overflow-auto">
        <table class="w-full text-sm table-auto">
          <thead>
            <tr class="text-left text-gray-600">
              <th class="w-10 px-3"></th>
              <th class="py-2 px-3 w-40">No. PO Anggaran</th>
              <th class="py-2 px-3 w-44">Perihal</th>
              <th class="py-2 px-3 w-28">Tanggal</th>
              <th class="py-2 px-3 w-32">Nominal</th>
              <th class="py-2 px-3 w-32">Outstanding</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="po in pagedOrders"
              :key="po.id"
              :class="[
                'border-t border-gray-100',
                isRowChecked(po.id) ? 'bg-gray-50' : 'bg-white',
              ]"
            >
              <td class="py-3 px-3">
                <input
                  type="radio"
                  name="poAnggaranSelection"
                  :checked="isRowChecked(po.id)"
                  @change="selectSingle(po.id)"
                />
              </td>
              <td class="py-3 px-3">
                <span class="font-medium whitespace-normal break-words">{{ po.no_po_anggaran }}</span>
              </td>
              <td class="py-3 px-3">
                <span class="block whitespace-normal break-words" :title="po.perihal?.nama || '-'">
                  {{ po.perihal?.nama || '-' }}
                </span>
              </td>
              <td class="py-3 px-3">{{ formatDate((po as any).tanggal) }}</td>
              <td class="py-3 px-3">{{ formatCurrency(po.nominal ?? 0) }}</td>
              <td class="py-3 px-3">{{ formatCurrency((po as any).outstanding ?? 0) }}</td>
            </tr>
            <tr v-if="poAnggarans.length === 0">
              <td colspan="6" class="py-10 text-center text-gray-500">
                <div class="flex flex-col items-center">
                  <svg
                    class="w-12 h-12 mb-3 text-gray-300"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                  </svg>
                  <div class="text-base font-medium mb-1">
                    Tidak ada PO Anggaran yang tersedia
                  </div>
                  <div class="text-sm">{{ noResultsMessage }}</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer Pagination -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-center">
        <nav class="flex items-center space-x-2" aria-label="Pagination">
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

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import { formatCurrency } from '@/lib/currencyUtils';

const props = defineProps<{
  open: boolean;
  poAnggarans: any[];
  selectedId?: string | number | null;
  noResultsMessage: string;
}>();

const emit = defineEmits<{
  (e: 'update:open', value: boolean): void;
  (e: 'search', query: string): void;
  (e: 'select', po: any): void;
}>();

const searchQuery = ref('');
let searchTimeout: ReturnType<typeof setTimeout>;

function close() {
  emit('update:open', false);
}

function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => emit('search', searchQuery.value), 300);
}

const pageSize = ref<number>(10);
const currentPage = ref<number>(1);
const totalPages = computed(() =>
  Math.max(1, Math.ceil(props.poAnggarans.length / pageSize.value))
);
const pagedOrders = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return props.poAnggarans.slice(start, start + pageSize.value);
});

watch([() => props.poAnggarans, pageSize], () => {
  currentPage.value = 1;
});

function isRowChecked(id: number | string): boolean {
  if (props.selectedId == null) return false;
  return String(props.selectedId) === String(id);
}

function selectSingle(id: number | string) {
  const selected = props.poAnggarans.find((po: any) => String(po.id) === String(id));
  if (selected) {
    emit('select', selected);
    close();
  }
}

function formatDate(value: any): string {
  if (!value) return '-';
  try {
    return new Date(value).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return String(value || '-');
  }
}
</script>
