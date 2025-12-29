<template>
  <div
    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
    @click.stop
    @submit.stop
  >
    <!-- Prevent form submission from this component -->
    <div @click.stop @submit.stop @keydown.stop>
      <!-- Action buttons - styled like in PurchaseOrderBarangGrid -->
      <div class="mb-4 flex gap-1" @click.stop @submit.stop>
        <!-- Tombol Tambah PO -->
        <button
          type="button"
          class="w-8 h-8 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
          @click="openPOSelection"
          @click.stop.prevent
          title="Tambah PO"
        >
          <CirclePlus class="w-4 h-4" />
        </button>

        <!-- Tombol Clear -->
        <button
          type="button"
          class="w-8 h-8 bg-yellow-500 text-white rounded flex items-center justify-center hover:bg-yellow-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
          @click="clearAll"
          @click.stop.prevent
          :disabled="!items.length"
          title="Clear"
        >
          <CircleMinus class="w-4 h-4" />
        </button>
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
                  class="w-6 h-6 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
                  @click.stop.prevent
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
                :class="[
                  'w-40 px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2',
                  Number(diskonNominal || 0) > total
                    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                    : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500',
                ]"
              />
              <div
                v-if="withDiskon && Number(diskonNominal || 0) > total"
                class="text-xs text-red-600 mt-1"
              >
                Nominal diskon melebihi total.
              </div>
            </div>

            <!-- PPN -->
            <div class="flex items-center space-x-4">
              <label class="flex items-center space-x-2 min-w-[80px]">
                <input
                  type="checkbox"
                  v-model="withPpn"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">PPN</span>
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
              <div v-if="withPph" class="flex-1 flex items-center space-x-2">
                <div class="w-40">
                  <CustomSelect
                    v-model="selectedPphId"
                    :options="(props.pphOptions || []).map((pph: any) => ({
                      label: pph.label || `${pph.nama_pph} (${pph.tarif_pph}%)`,
                      value: pph.value || pph.id
                    }))"
                    placeholder="Pilih PPH"
                    class="w-full compact-select"
                  />
                </div>
                <button
                  type="button"
                  class="w-6 h-6 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors text-xs"
                  @click.stop.prevent="showAddPph = true"
                  title="Tambah PPH"
                >
                  +
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
                <span class="text-gray-600">Total</span>
                <span class="font-semibold text-gray-900">{{
                  formatRupiah(total)
                }}</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">Diskon</span>
                <span class="font-semibold text-gray-900">{{
                  formatRupiah(diskon)
                }}</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">DPP</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(total - diskon) }}</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">PPN</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(ppn) }}</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">PPH</span>
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

    <!-- PO Selection Modal -->
    <PurchaseOrderSelectionModal
      v-model:open="showPOSelection"
      :purchase-orders="filteredPOs"
      :selected-ids="selectedPOIds"
      :no-results-message="noResultsMessage"
      @search="handlePOSearch"
      @add-selected="handleAddSelectedPOs"
    />

  <!-- Tambah PPH Modal -->
  <TambahPphModal :show="showAddPph" @submit="addPph" @close="showAddPph = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { CirclePlus, CircleMinus, Eye } from "lucide-vue-next";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";
import PurchaseOrderSelectionModal from "./PurchaseOrderSelectionModal.vue";
import CustomSelect from "../ui/CustomSelect.vue";
import TambahPphModal from "@/components/purchase-orders/TambahPphModal.vue";
import { useAlertDialog } from "@/composables/useAlertDialog";

const props = defineProps<{
  items: any[];
  availablePOs?: any[];
  formData?: any;
  pphOptions?: any[];
  // Summary values datang dari backend/parent (PO/PV), tidak dihitung ulang di frontend
  summary?: {
    total?: number;
    diskon?: number;
    ppn_nominal?: number;
    pph_nominal?: number;
    grand_total?: number;
  };
}>();
const emit = defineEmits(["add-po", "clear", "add-selected-pos", "add-pph"]);
const { showWarning } = useAlertDialog();

const withDiskon = ref(false);
const withPpn = ref(false);
const withPph = ref(false);

const diskonNominal = ref<number>(0);
// const ppnRate = ref<number>(11);
const selectedPphId = ref<number | undefined>(undefined);

// PO Selection Modal state
const showPOSelection = ref(false);
const showAddPph = ref(false);

// Dynamic message based on form data
const noResultsMessage = computed(() => {
  if (!props.formData) {
    return "Silakan pilih metode pembayaran dan supplier/giro/kartu kredit terlebih dahulu";
  }

  const { metode_bayar, supplier_id, giro_id, credit_card_id } = props.formData;

  if (metode_bayar === "Transfer" && !supplier_id) {
    return "Silakan pilih supplier terlebih dahulu";
  } else if (metode_bayar === "Cek/Giro" && !giro_id) {
    return "Silakan pilih giro terlebih dahulu";
  } else if (metode_bayar === "Kartu Kredit" && !credit_card_id) {
    return "Silakan pilih kartu kredit terlebih dahulu";
  }

  return (
    "Tidak ada Purchase Order yang tersedia untuk " +
    (metode_bayar === "Transfer"
      ? "supplier yang dipilih"
      : metode_bayar === "Cek/Giro"
      ? "giro yang dipilih"
      : metode_bayar === "Kartu Kredit"
      ? "kartu kredit yang dipilih"
      : "pilihan yang dipilih")
  );
});

// Get selected PO IDs from current items
const selectedPOIds = computed(() =>
  props.items.map((item) => item.id || item.po_id).filter(Boolean)
);

// Get selected PPH rate
// const selectedPphRate = computed(() => {
//   if (!selectedPphId.value || !props.pphOptions) return 0;
//   const selectedPph = props.pphOptions.find((pph) => pph.value === selectedPphId.value);
//   return selectedPph?.tarif_pph || 0;
// });

// Filter POs based on form data (supplier/giro/kartu kredit)
const filteredPOs = computed(() => {
  if (!props.availablePOs || !props.formData) {
    return props.availablePOs || [];
  }

  const { metode_bayar, supplier_id, giro_id, credit_card_id } = props.formData;

  return props.availablePOs.filter((po) => {
    // Filter berdasarkan metode pembayaran
    if (metode_bayar === "Transfer" && supplier_id) {
      // Untuk transfer, filter berdasarkan supplier
      return po.supplier_id === supplier_id;
    } else if (metode_bayar === "Cek/Giro" && giro_id) {
      // Untuk cek/giro, filter berdasarkan giro
      return po.giro_id === giro_id;
    } else if (metode_bayar === "Kartu Kredit" && credit_card_id) {
      // Untuk kartu kredit, filter berdasarkan kartu kredit
      return po.credit_card_id === credit_card_id;
    }

    // Jika tidak ada filter yang sesuai, return semua PO
    return true;
  });
});

// Nilai summary diambil langsung dari props.summary (backend/parent), tanpa perhitungan ulang di frontend
const total = computed(() => Number(props.summary?.total ?? 0));
const diskon = computed(() => Number(props.summary?.diskon ?? 0));
const ppn = computed(() => Number(props.summary?.ppn_nominal ?? 0));
const pph = computed(() => Number(props.summary?.pph_nominal ?? 0));
const grandTotal = computed(() => Number(props.summary?.grand_total ?? 0));

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
  selectedPphId.value = undefined;
}

// PO Selection Modal handlers
function openPOSelection() {
  // Validasi apakah sudah memilih supplier/giro/kartu kredit
  if (!props.formData) {
    showWarning(
      "Silakan pilih metode pembayaran dan supplier/giro/kartu kredit terlebih dahulu",
      "Peringatan"
    );
    return;
  }

  const { metode_bayar, supplier_id, giro_id, credit_card_id } = props.formData;

  if (metode_bayar === "Transfer" && !supplier_id) {
    showWarning("Silakan pilih supplier terlebih dahulu", "Peringatan");
    return;
  } else if (metode_bayar === "Cek/Giro" && !giro_id) {
    showWarning("Silakan pilih giro terlebih dahulu", "Peringatan");
    return;
  } else if (metode_bayar === "Kartu Kredit" && !credit_card_id) {
    showWarning("Silakan pilih kartu kredit terlebih dahulu", "Peringatan");
    return;
  }

  showPOSelection.value = true;
}

function handlePOSearch(query: string) {
  // Emit search event to parent component
  emit("add-po", { search: query });
}

function handleAddSelectedPOs(selectedPOs: any[]) {
  // Emit selected POs to parent component
  emit("add-selected-pos", selectedPOs);
}

function addPph(pphBaru: any) {
  emit("add-pph", pphBaru);
  // after adding, if new option exists in list, select it by id/kode
  if (pphBaru?.id) {
    selectedPphId.value = pphBaru.id;
  }
  showAddPph.value = false;
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
