<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40" @click="close"></div>

    <!-- Panel -->
    <div
      class="relative mx-auto mt-10 max-w-7xl rounded-xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
    >
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold">Purchase Order</h2>
        <button type="button" @click="close" class="text-gray-500 hover:text-gray-700">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="w-5 h-5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Toolbar -->
      <div class="px-6 py-3 flex items-center gap-2">
        <button
          type="button"
          class="px-3 py-1.5 text-xs rounded-md border border-gray-300"
        >
          + Filter
        </button>
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
              <th class="w-10 px-3">
                <!-- radio header placeholder -->
              </th>
              <th class="py-2 px-3 w-40">No. PO</th>
              <th class="py-2 px-3 w-44">Perihal</th>
              <th class="py-2 px-3 w-28">Tanggal</th>
              <th class="py-2 px-3 w-40">No. Invoice</th>
              <th class="py-2 px-3 w-32">Nominal</th>
              <th class="py-2 px-3">Keterangan</th>
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
                  name="poSelection"
                  :checked="isRowChecked(po.id)"
                  @change="selectSingle(po.id)"
                  :disabled="selectedIds.includes(po.id)"
                />
              </td>
              <td class="py-3 px-3">
                <div class="flex items-center gap-2">
                  <span class="font-medium whitespace-normal break-words">{{ po.no_po }}</span>
                </div>
              </td>
              <td class="py-3 px-3">
                <span class="block whitespace-normal break-words" :title="po.perihal?.nama || '-'">
                  {{ po.perihal?.nama || "-" }}
                </span>
              </td>
              <td class="py-3 px-3">{{ formatDate((po as any).tanggal) }}</td>
              <td class="py-3 px-3">
                <span class="block whitespace-normal break-words" :title="(po as any).no_invoice || '-'">
                  {{ (po as any).no_invoice || '-' }}
                </span>
              </td>
              <td class="py-3 px-3">{{ formatCurrency(po.total ?? 0) }}</td>
              <td class="py-3 px-3 relative group">
                <div class="flex items-center gap-2">
                  <span
                    class="truncate block max-w-[24rem]"
                    :title="getKeterangan(po) || '-'"
                  >
                    {{ truncateText(getKeterangan(po) || "-", 80) }}
                  </span>
                  <button
                    v-if="(getKeterangan(po) || '').length > 80"
                    @click="showKeteranganModal(po)"
                    class="ml-1 p-1 rounded-full hover:bg-gray-200 text-gray-500 hover:text-gray-700 flex-shrink-0"
                    title="Lihat selengkapnya"
                  >
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                      ></path>
                    </svg>
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="purchaseOrders.length === 0">
              <td colspan="7" class="py-10 text-center text-gray-500">
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
                    ></path>
                  </svg>
                  <div class="text-base font-medium mb-1">
                    Tidak ada Purchase Order yang tersedia
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

    <!-- Keterangan Detail Modal -->
    <div
      v-if="keteranganModal.show"
      class="fixed inset-0 z-60 flex items-center justify-center p-4"
    >
      <div class="absolute inset-0 bg-black/50" @click="closeKeteranganModal"></div>
      <div
        class="relative bg-white rounded-xl shadow-xl max-w-2xl w-full max-h-[80vh] overflow-hidden"
      >
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
          <h3 class="text-lg font-semibold">Detail Keterangan</h3>
          <button
            type="button"
            @click="closeKeteranganModal"
            class="text-gray-500 hover:text-gray-700"
          >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              ></path>
            </svg>
          </button>
        </div>

        <div class="px-6 py-4 border-b border-gray-100">
          <div class="text-sm text-gray-600 mb-1">No. PO:</div>
          <div class="font-medium">{{ keteranganModal.po?.no_po }}</div>
        </div>

        <div class="px-6 py-4 max-h-96 overflow-y-auto">
          <div class="text-sm text-gray-600 mb-2">Keterangan:</div>
          <div class="text-gray-800 leading-relaxed whitespace-pre-wrap">
            {{ keteranganModal.po?.keterangan || "-" }}
          </div>
        </div>

        <div class="px-6 py-4 border-t border-gray-200 flex justify-end">
          <button
            type="button"
            @click="closeKeteranganModal"
            class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
          >
            Tutup
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, reactive } from "vue";
import { formatCurrency } from "@/lib/currencyUtils";
import { format } from "date-fns";

const props = defineProps<{
  open: boolean;
  purchaseOrders: any[];
  selectedIds: number[];
  noResultsMessage: string;
}>();

const emit = defineEmits(["update:open", "search", "add", "add-many"]);

const searchQuery = ref("");
let searchTimeout: ReturnType<typeof setTimeout>;

// Keterangan modal state
const keteranganModal = reactive({
  show: false,
  po: null as any,
});

function close() {
  emit("update:open", false);
}

function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => emit("search", searchQuery.value), 300);
}

// Pagination
const pageSize = ref<number>(10);
const currentPage = ref<number>(1);
const totalPages = computed(() =>
  Math.max(1, Math.ceil(props.purchaseOrders.length / pageSize.value))
);
const pagedOrders = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return props.purchaseOrders.slice(start, start + pageSize.value);
});
watch([() => props.purchaseOrders, pageSize], () => {
  currentPage.value = 1;
});

// Selection
const checkedIds = ref<Set<number>>(new Set());
watch(
  () => props.selectedIds,
  (ids) => {
    ids.forEach((id) => checkedIds.value.delete(id));
  },
  { immediate: true }
);

function isRowChecked(id: number): boolean {
  return checkedIds.value.has(id) || props.selectedIds.includes(id);
}
function selectSingle(id: number) {
  if (props.selectedIds.includes(id)) return;
  checkedIds.value.clear();
  checkedIds.value.add(id);

  // Automatically select and close modal
  const selectedPO = props.purchaseOrders.find((po) => po.id === id);
  if (selectedPO) {
    emit("add", selectedPO);
    close();
  }
}

function getKeterangan(po: any): string {
  return po.keterangan || "";
}

function formatDate(value: any): string {
  try {
    if (!value) return "-";
    return format(new Date(value), "dd/MM/yyyy");
  } catch {
    return String(value || "-");
  }
}

function truncateText(text: string, maxLength: number): string {
  if (!text || text.length <= maxLength) return text;
  return text.substring(0, maxLength) + "...";
}

function showKeteranganModal(po: any) {
  keteranganModal.po = po;
  keteranganModal.show = true;
}

function closeKeteranganModal() {
  keteranganModal.show = false;
  keteranganModal.po = null;
}
</script>

<style scoped>
/* Custom scrollbar untuk modal keterangan */
.max-h-96::-webkit-scrollbar {
  width: 6px;
}

.max-h-96::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.max-h-96::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}

.max-h-96::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}
</style>
