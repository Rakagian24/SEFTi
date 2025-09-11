<template>
  <div class="p-0">
    <div class="flex items-center justify-between mb-4">
      <h3 class="font-semibold text-lg text-gray-800">Purchase Orders</h3>
      <div class="flex gap-1">
        <button
          type="button"
          class="w-8 h-8 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
          @click="$emit('add-po')"
          title="Tambah PO"
        >
          <CirclePlus class="w-4 h-4" />
        </button>
        <button
          type="button"
          class="w-8 h-8 bg-yellow-500 text-white rounded flex items-center justify-center hover:bg-yellow-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
          @click="clearAll"
          :disabled="!items.length"
          title="Clear"
        >
          <CircleMinus class="w-4 h-4" />
        </button>
      </div>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 mb-4">
      <table class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 w-10">
              <input
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              No. PO
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              Departemen
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              Perihal
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              Tanggal
            </th>
            <th
              class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              Subtotal
            </th>
            <th class="px-4 py-3 w-16">
              <!-- Action column header -->
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(it, idx) in items" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-3 w-10">
              <input
                type="checkbox"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
            </td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.no_po }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.department_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.perihal_name }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.tanggal }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">
              {{ formatRupiah(it.subtotal) }}
            </td>
            <td class="px-4 py-3 w-16">
              <button
                type="button"
                class="w-6 h-6 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors text-xs"
                title="Detail"
              >
                <Eye class="w-3 h-3" />
              </button>
            </td>
          </tr>
          <tr v-if="items.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
              Belum ada PO
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Bottom section with checkboxes and summary -->
    <div class="flex flex-col lg:flex-row gap-6">
      <!-- Left side - Checkbox options -->
      <div class="flex-1">
        <div class="space-y-4">
          <!-- Diskon -->
          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 min-w-[80px]">
              <input
                type="checkbox"
                v-model="withDiskon"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">Diskon</span>
            </label>
            <input
              v-if="withDiskon"
              type="text"
              v-model="displayDiskonNominal"
              placeholder="10,000"
              @keydown="allowNumericKeydown"
              class="w-40 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
            />
          </div>

          <!-- PPN -->
          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 min-w-[80px]">
              <input
                type="checkbox"
                v-model="withPpn"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">PPN ({{ ppnRate }}%)</span>
            </label>
          </div>

          <!-- PPH -->
          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 min-w-[80px]">
              <input
                type="checkbox"
                v-model="withPph"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">PPH</span>
            </label>
            <div v-if="withPph" class="flex items-center space-x-2">
              <input
                type="number"
                v-model.number="pphRate"
                placeholder="Tarif PPH %"
                class="w-40 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
              <button
                type="button"
                class="w-6 h-6 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors text-xs"
                title="Tambah PPH"
              >
                <Plus class="w-3 h-3" />
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- Right side - Summary -->
      <div class="lg:w-80">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Subtotal</span>
              <span class="font-semibold text-gray-900">{{
                formatRupiah(subtotal)
              }}</span>
            </div>
            <div v-if="withDiskon" class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Diskon</span>
              <span class="font-semibold text-gray-900">{{
                formatRupiah(diskonNominal)
              }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">DPP</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(dpp) }}</span>
            </div>
            <div v-if="withPpn" class="flex justify-between items-center text-sm">
              <span class="text-gray-600">PPN</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(ppn) }}</span>
            </div>
            <div v-if="withPph" class="flex justify-between items-center text-sm">
              <span class="text-gray-600">PPH {{ pphRate }}%</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(pph) }}</span>
            </div>
            <div class="border-t border-gray-300 pt-2 mt-2">
              <div class="flex justify-between items-center">
                <span class="text-base font-semibold text-gray-900">Grand Total</span>
                <span class="text-lg font-bold text-gray-900">{{
                  formatRupiah(grandTotal)
                }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { CirclePlus, CircleMinus, Eye, Plus } from "lucide-vue-next";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";

const props = defineProps<{ items: any[] }>();
const emit = defineEmits(["add-po", "clear"]);

const withDiskon = ref(false);
const withPpn = ref(false);
const withPph = ref(false);

const diskonNominal = ref<number>(0);
const ppnRate = ref<number>(11);
const pphRate = ref<number>(0);

const subtotal = computed(() =>
  props.items.reduce((sum, it: any) => sum + (it.subtotal || 0), 0)
);
const dpp = computed(() =>
  Math.max(0, subtotal.value - (withDiskon.value ? diskonNominal.value || 0 : 0))
);
const ppn = computed(() =>
  withPpn.value ? Math.round(dpp.value * (ppnRate.value / 100)) : 0
);
const pph = computed(() =>
  withPph.value ? Math.round(dpp.value * (pphRate.value / 100)) : 0
);
const grandTotal = computed(() => dpp.value + ppn.value + pph.value);

// Formatted discount input
const displayDiskonNominal = computed<string>({
  get: () => formatCurrency(diskonNominal.value ?? ""),
  set: (val: string) => {
    const parsed = parseCurrency(val);
    diskonNominal.value = parsed === "" ? 0 : Number(parsed);
  },
});

// Numeric keydown helper
function allowNumericKeydown(event: KeyboardEvent) {
  const allowedKeys = [
    "Backspace",
    "Delete",
    "Tab",
    "Enter",
    "Escape",
    "ArrowLeft",
    "ArrowRight",
    "Home",
    "End",
    ",",
    ".",
    "0",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
  ];
  const isCtrlCombo = event.ctrlKey || event.metaKey;
  if (isCtrlCombo) return; // allow copy/paste/select all
  if (!allowedKeys.includes(event.key)) {
    event.preventDefault();
  }
}

function clearAll() {
  emit("clear");
  // Reset all states when clearing
  withDiskon.value = false;
  withPpn.value = false;
  withPph.value = false;
  diskonNominal.value = 0;
  pphRate.value = 0;
}

function formatRupiah(val: number | null | undefined) {
  const safe = typeof val === "number" && !isNaN(val) ? val : 0;
  const formattedNumber = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(safe);

  return `Rp ${formattedNumber}`;
}
</script>

<style scoped>
/* Custom checkbox styling */
input[type="checkbox"]:checked {
  background-color: #3b82f6;
  border-color: #3b82f6;
}

/* Hover effects for interactive elements */
button:hover:not(:disabled) {
  transform: translateY(-1px);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

/* Focus states */
input:focus,
select:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Table row hover effect */
tbody tr:hover {
  background-color: #f9fafb;
}
</style>
