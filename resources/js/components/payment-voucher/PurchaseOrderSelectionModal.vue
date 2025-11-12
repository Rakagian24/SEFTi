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
        <div class="text-xs text-gray-600 mb-2">
          Pilih BPB (opsional), kemudian klik radio di kiri untuk memilih PO.
        </div>
        <table class="w-full text-sm table-auto">
          <thead>
            <tr class="text-left text-gray-600">
              <th class="w-10 px-3"></th>
              <th class="py-2 px-3 w-40">No. PO</th>
              <th class="py-2 px-3 w-48">Departemen</th>
              <th class="py-2 px-3 w-48">Perihal</th>
              <th class="py-2 px-3 w-28">Tanggal</th>
              <th class="py-2 px-3 w-40">No. Invoice</th>
              <th class="py-2 px-3 w-32">Nominal</th>
              <th class="py-2 px-3">Keterangan</th>
            </tr>
          </thead>
          <tbody>
            <template v-for="po in pagedOrders" :key="po.id">
              <tr
                :class="[
                  'border-t border-gray-100 transition-colors hover:bg-blue-50/50',
                  isRowChecked(po.id) ? 'bg-blue-50' : 'bg-white',
                ]"
              >
                <td class="py-3 px-3">
                  <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                    <input
                      type="radio"
                      :name="'po-selection'"
                      :checked="isRowChecked(po.id)"
                      @change.stop="selectRow(po)"
                      class="w-4 h-4 text-blue-600 focus:ring-blue-500"
                      :aria-label="`Pilih PO ${po.no_po}`"
                    />
                    <span class="sr-only">Pilih</span>
                  </label>
                </td>
                <td class="py-3 px-3">
                  <div class="flex items-center gap-2">
                    <svg
                      v-if="bpbList[po.id] === undefined || (bpbList[po.id] || []).length > 0"
                      @click.stop="toggleExpand(po)"
                      :class="[
                        'w-4 h-4 transition-transform duration-200 text-gray-400 cursor-pointer',
                        isExpanded(po.id) ? 'rotate-90 text-blue-600' : '',
                      ]"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 5l7 7-7 7"
                      />
                    </svg>
                    <span class="font-medium whitespace-normal break-words">{{ po.no_po }}</span>
                  </div>
                </td>
                <td class="py-3 px-3">
                  <span class="block whitespace-normal break-words" :title="po.department?.name || '-'">
                    {{ po.department?.name || "-" }}
                  </span>
                </td>
                <td class="py-3 px-3">
                  <span class="block whitespace-normal break-words" :title="po.perihal?.nama || '-'">
                    {{ po.perihal?.nama || "-" }}
                  </span>
                </td>
                <td class="py-3 px-3">{{ formatDate(po.tanggal) }}</td>
                <td class="py-3 px-3">
                  <span class="block whitespace-normal break-words" :title="po.no_invoice || '-'">
                    {{ po.no_invoice || "-" }}
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
                      @click.stop="showKeteranganModal(po)"
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
  
              <!-- Expanded BPB list - Langsung di bawah row yang dipilih -->
              <tr v-if="isExpanded(po.id)" :key="po.id + '-bpb'">
                <td colspan="8" class="bg-gray-50">
                  <div class="px-4 py-3">
                    <div v-if="bpbLoading[po.id]" class="flex items-center justify-center py-6 text-sm text-gray-600">
                      <div class="animate-spin rounded-full h-5 w-5 border-b-2 border-blue-600 mr-2"></div>
                      Memuat BPB...
                    </div>

                    <div v-else-if="(bpbList[po.id] || []).length === 0" class="text-center py-6">
                      <svg class="w-10 h-10 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                      </svg>
                      <p class="text-sm text-gray-500">Tidak ada BPB yang tersedia</p>
                    </div>

                    <div v-else class="bg-white border rounded-md overflow-hidden">
                      <div class="flex items-center justify-between px-3 py-2 text-xs bg-white">
                        <div class="font-medium text-gray-700">BPB untuk PO {{ po.no_po }}</div>
                        <div class="flex items-center gap-2 text-gray-700">
                          <span class="inline-flex items-center px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">
                            {{ (selectedBpbs[po.id] || []).length }} BPB
                          </span>
                          <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-50 text-gray-700 border border-gray-200">
                            Total: {{ formatCurrency(selectedBpbTotal(po.id)) }}
                          </span>
                        </div>
                      </div>
                      <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                          <tr class="text-left text-gray-700">
                            <th class="w-10 px-3 py-2">
                              <input
                                type="checkbox"
                                :checked="isAllBpbSelected(po.id)"
                                @change="toggleSelectAllBpb(po)"
                                class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded"
                              />
                            </th>
                            <th class="py-2 px-3 w-40 font-medium">No. BPB</th>
                            <th class="py-2 px-3 w-28 font-medium">Tanggal</th>
                            <th class="py-2 px-3 w-32 font-medium">Nominal</th>
                            <th class="py-2 px-3 font-medium">Keterangan</th>
                          </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                          <tr
                            v-for="b in bpbList[po.id]"
                            :key="b.id"
                            :class="[
                              'transition-colors',
                              isBpbSelected(po.id, b.id) ? 'bg-blue-50' : 'hover:bg-gray-50'
                            ]"
                          >
                            <td class="py-2 px-3">
                              <input
                                type="checkbox"
                                :checked="isBpbSelected(po.id, b.id)"
                                @change.stop="toggleBpb(po.id, b)"
                                class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded"
                              />
                            </td>
                            <td class="py-2 px-3 font-medium text-gray-900">{{ b.no_bpb }}</td>
                            <td class="py-2 px-3 text-gray-700">{{ formatDate(b.tanggal) }}</td>
                            <td class="py-2 px-3 font-medium text-gray-900">{{ formatCurrency(b.grand_total ?? 0) }}</td>
                            <td class="py-2 px-3 text-gray-600">{{ truncateText(b.keterangan || '-', 100) }}</td>
                          </tr>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </td>
              </tr>
            </template>

            <tr v-if="purchaseOrders.length === 0">
              <td colspan="8" class="py-10 text-center text-gray-500">
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
import axios from "axios";

const props = defineProps<{
  open: boolean;
  purchaseOrders: any[];
  selectedIds: number[];
  noResultsMessage: string;
}>();

const emit = defineEmits(["update:open", "search", "add-selected"]);

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

// Selection (single PO via radio) and multi BPB per PO
const selectedId = ref<number | null>(null);

// Expand & BPB state
const expanded = ref<Record<number, boolean>>({});
const bpbList = reactive<Record<number, any[]>>({});
const bpbLoading = reactive<Record<number, boolean>>({});
const selectedBpbs = reactive<Record<number, number[]>>({}); // per PO: array of BPB ids

function isExpanded(id: number): boolean {
  return !!expanded.value[id];
}

async function toggleExpand(po: any) {
  const id = po?.id;
  if (!id) return;
  // If already expanded, collapse
  if (expanded.value[id]) {
    expanded.value[id] = false;
    return;
  }
  // Opening: ensure BPBs are loaded, and only expand if there are any
  if (!bpbList[id]) {
    await fetchBpbs(po);
  }
  const hasBpbs = (bpbList[id] || []).length > 0;
  expanded.value[id] = hasBpbs;
}

async function fetchBpbs(po: any) {
  const id = po?.id;
  if (!id) return;
  bpbLoading[id] = true;
  try {
    const { data } = await axios.get(`/payment-voucher/purchase-orders/${id}/bpbs`, { withCredentials: true });
    bpbList[id] = (data?.data || []) as any[];
  } catch {
    bpbList[id] = [];
  } finally {
    bpbLoading[id] = false;
  }
}

function isRowChecked(id: number): boolean {
  return selectedId.value === id;
}

function selectRow(po: any) {
  selectedId.value = po?.id ?? null;
  if (!po) return;
  // Build payload with current BPB selections for this PO (if any)
  const poId = po.id;
  const ids = selectedBpbs[poId] || [];
  const bpbs = (bpbList[poId] || []).filter((x: any) => ids.includes(x.id));
  emit('add-selected', { po, bpbs });
  emit('update:open', false);
}

function isBpbSelected(poId: number, bpbId: number): boolean {
  return (selectedBpbs[poId] || []).includes(bpbId);
}

function toggleBpb(poId: number, b: any) {
  const arr = selectedBpbs[poId] || [];
  const idx = arr.indexOf(b.id);
  if (idx >= 0) {
    arr.splice(idx, 1);
  } else {
    arr.push(b.id);
  }
  selectedBpbs[poId] = [...arr];
}

function isAllBpbSelected(poId: number): boolean {
  const list = bpbList[poId] || [];
  const sel = selectedBpbs[poId] || [];
  return list.length > 0 && sel.length === list.length;
}

function toggleSelectAllBpb(po: any) {
  const poId = po?.id;
  if (!poId) return;
  const list = bpbList[poId] || [];
  if (isAllBpbSelected(poId)) {
    selectedBpbs[poId] = [];
  } else {
    selectedBpbs[poId] = list.map((x: any) => x.id);
  }
}


// Compute total of selected BPBs for a given PO
function selectedBpbTotal(poId: number): number {
  const ids = selectedBpbs[poId] || [];
  const list = (bpbList[poId] || []).filter((x: any) => ids.includes(x.id));
  return list.reduce((sum: number, b: any) => sum + (Number(b?.grand_total) || 0), 0);
}

// removed explicit confirm button; selection emits immediately

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

// No auto-emit on modal close; selection happens on radio click
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
