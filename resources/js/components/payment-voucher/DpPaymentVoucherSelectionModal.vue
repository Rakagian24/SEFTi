<template>
  <div v-if="open" class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40" @click="close"></div>

    <!-- Panel -->
    <div
      class="relative mx-auto mt-10 max-w-4xl rounded-xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.25)]"
    >
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold">Pilih PV DP</h2>
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
        <div class="text-sm text-gray-600">
          PV DP yang tampil hanya untuk PO &amp; supplier yang sama dan masih punya sisa DP.
        </div>
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
              placeholder="Search No. PV / No. PO / Supplier"
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
      <div class="px-6 pb-2 max-h-[26rem] overflow-auto">
        <table class="w-full text-sm table-auto">
          <thead>
            <tr class="text-left text-gray-600 border-b border-gray-100">
              <th class="w-10 px-3 py-2"></th>
              <th class="py-2 px-3 w-32">No. PV DP</th>
              <th class="py-2 px-3 w-28">Tanggal</th>
              <th class="py-2 px-3 w-32">No. PO</th>
              <th class="py-2 px-3 w-40">Supplier</th>
              <th class="py-2 px-3 w-32 text-right">Nominal</th>
              <th class="py-2 px-3 w-32 text-right">Terpakai</th>
              <th class="py-2 px-3 w-32 text-right">Sisa DP</th>
              <th class="py-2 px-3 w-40 text-right">Dipakai</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="row in pagedRows"
              :key="row.id"
              :class="[
                'border-t border-gray-100 transition-colors',
                isSelected(row.id) ? 'bg-blue-50' : 'bg-white hover:bg-blue-50/50',
              ]"
            >
              <td class="py-2 px-3">
                <input
                  type="checkbox"
                  class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded"
                  :checked="isSelected(row.id)"
                  @change="toggleSelect(row)"
                />
              </td>
              <td class="py-2 px-3 font-medium text-gray-900">{{ row.no_pv }}</td>
              <td class="py-2 px-3 text-gray-700">{{ formatDate(row.tanggal) }}</td>
              <td class="py-2 px-3 text-gray-700">{{ row.no_po || '-' }}</td>
              <td class="py-2 px-3 text-gray-700 truncate" :title="row.supplier_name || '-'">
                {{ row.supplier_name || '-' }}
              </td>
              <td class="py-2 px-3 text-right font-medium text-gray-900">{{ formatCurrency(row.nominal || 0) }}</td>
              <td class="py-2 px-3 text-right text-gray-700">{{ formatCurrency(row.used_amount || 0) }}</td>
              <td class="py-2 px-3 text-right font-medium" :class="row.outstanding > 0 ? 'text-emerald-700' : 'text-gray-500'">
                {{ formatCurrency(row.outstanding || 0) }}
              </td>
              <td class="py-2 px-3 text-right">
                <input
                  type="number"
                  min="0"
                  :max="row.outstanding || 0"
                  step="0.01"
                  v-model.number="row.amount"
                  class="w-28 px-2 py-1 text-right border border-gray-300 rounded-md text-sm"
                  :disabled="!isSelected(row.id) || row.outstanding <= 0"
                />
              </td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="9" class="py-10 text-center text-gray-500">
                Tidak ada PV DP yang tersedia untuk PO dan supplier ini.
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-between">
        <div class="text-xs text-gray-700">
          Total Dipakai: <span class="font-semibold">{{ formatCurrency(totalSelectedAmount) }}</span>
        </div>
        <div class="flex items-center gap-3">
          <button
            type="button"
            @click="close"
            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50"
          >
            Batal
          </button>
          <button
            type="button"
            @click="applySelection"
            :disabled="selectedIds.length === 0"
            class="px-5 py-2 text-sm font-medium text-white rounded-md border border-transparent"
            :class="selectedIds.length === 0 ? 'bg-blue-300 cursor-not-allowed' : 'bg-[#7F9BE6] hover:bg-blue-700'"
          >
            Pakai PV DP
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, ref, watch } from "vue";
import axios from "axios";
import { formatCurrency } from "@/lib/currencyUtils";

const props = defineProps<{
  open: boolean;
  purchaseOrderId: number | null | undefined;
  supplierId: number | null | undefined;
  preselectedAllocations?: { dp_payment_voucher_id: number; amount: number }[];
}>();

const emit = defineEmits<{
  "update:open": [value: boolean];
  apply: [rows: any[]];
}>();

const rows = ref<any[]>([]);
const pageSize = ref<number>(10);
const currentPage = ref<number>(1);
const searchQuery = ref<string>("");
let searchTimeout: ReturnType<typeof setTimeout>;

const selectedIds = ref<number[]>([]);

const pagedRows = computed(() => {
  const start = (currentPage.value - 1) * pageSize.value;
  return rows.value.slice(start, start + pageSize.value);
});

const totalSelectedAmount = computed(() => {
  return rows.value
    .filter((r) => selectedIds.value.includes(r.id) && (r.amount || 0) > 0)
    .reduce((sum, r) => sum + (Number(r.amount) || 0), 0);
});

function close() {
  emit("update:open", false);
}

function isSelected(id: number): boolean {
  return selectedIds.value.includes(id);
}

function toggleSelect(row: any) {
  const id = row.id;
  if (!id) return;
  const idx = selectedIds.value.indexOf(id);
  if (idx >= 0) {
    selectedIds.value.splice(idx, 1);
  } else {
    selectedIds.value.push(id);
    // Default amount = outstanding when first selected
    if (!row.amount || row.amount <= 0) {
      row.amount = Number(row.outstanding || 0) || 0;
    }
  }
}

function syncPreselected() {
  try {
    const allocs = props.preselectedAllocations || [];
    if (!allocs.length || !rows.value.length) return;
    const map = new Map<number, number>();
    allocs.forEach((a) => {
      if (a.dp_payment_voucher_id) {
        map.set(Number(a.dp_payment_voucher_id), Number(a.amount || 0));
      }
    });
    const sel: number[] = [];
    rows.value.forEach((r) => {
      const id = Number(r.id);
      if (!id) return;
      if (map.has(id)) {
        const amt = map.get(id) || 0;
        r.amount = amt > 0 ? Math.min(amt, Number(r.outstanding || amt)) : 0;
        if (r.amount > 0) sel.push(id);
      }
    });
    selectedIds.value = sel;
  } catch {}
}

function onSearchInput() {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    fetchDpPvs();
  }, 300);
}

function formatDate(value: any): string {
  if (!value) return "-";
  try {
    return new Date(value).toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  } catch {
    return String(value || "-");
  }
}

async function fetchDpPvs() {
  if (!props.purchaseOrderId || !props.supplierId) {
    rows.value = [];
    selectedIds.value = [];
    return;
  }
  try {
    const params: any = {
      per_page: 100,
      purchase_order_id: props.purchaseOrderId,
      supplier_id: props.supplierId,
    };
    if (searchQuery.value) params.search = searchQuery.value;

    const { data } = await axios.get("/payment-voucher/dp/search", {
      params,
      withCredentials: true,
    });
    if (data && data.success) {
      rows.value = (data.data || []).map((r: any) => ({
        ...r,
        amount: Number(r.outstanding || 0) || 0,
      }));
      currentPage.value = 1;
      // Apply preselected allocations onto freshly loaded rows
      syncPreselected();
    } else {
      rows.value = [];
      selectedIds.value = [];
    }
  } catch (e) {
    console.error("Failed to fetch DP PVs:", e);
    rows.value = [];
    selectedIds.value = [];
  }
}

function applySelection() {
  const selected = rows.value
    .filter((r) => selectedIds.value.includes(r.id) && (Number(r.amount) || 0) > 0)
    .map((r) => ({
      id: r.id,
      no_pv: r.no_pv,
      tanggal: r.tanggal,
      purchase_order_id: r.purchase_order_id,
      no_po: r.no_po,
      supplier_id: r.supplier_id,
      supplier_name: r.supplier_name,
      nominal: Number(r.nominal || 0) || 0,
      used_amount: Number(r.used_amount || 0) || 0,
      outstanding: Number(r.outstanding || 0) || 0,
      amount: Math.min(Number(r.amount || 0) || 0, Number(r.outstanding || 0) || 0),
    }));
  emit("apply", selected);
  emit("update:open", false);
}

watch(
  () => props.open,
  (isOpen) => {
    if (isOpen) {
      fetchDpPvs();
    }
  }
);

onMounted(() => {
  if (props.open) {
    fetchDpPvs();
  }
});
</script>
