<template>
  <div
    class="bg-white rounded-lg shadow-sm border border-gray-200 p-4"
    @click.stop
    @submit.stop
  >
    <!-- Prevent form submission from this component -->
    <div @click.stop @submit.stop @keydown.stop>
      <!-- <h2 class="font-semibold text-lg mb-4 text-gray-800">Daftar Barang/Jasa</h2> -->

      <!-- Action buttons - styled like in the image -->
      <div class="mb-4 flex gap-1" @click.stop @submit.stop>
        <!-- Tombol Tambah -->
        <button
          type="button"
          class="w-8 h-8 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
          @click="openAddModal"
          @click.stop.prevent
          :disabled="terminInfo?.status_termin === 'in_progress'"
          title="Tambah"
        >
          <CirclePlus class="w-4 h-4" />
        </button>

        <!-- Tombol Clear -->
        <button
          type="button"
          class="w-8 h-8 bg-yellow-500 text-white rounded flex items-center justify-center hover:bg-yellow-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
          @click="clearAll"
          @click.stop.prevent
          :disabled="terminInfo?.status_termin === 'in_progress' || !items.length"
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
                {{ headerText }}
              </th>
              <th
                v-if="isBarangJasaPerihal"
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Tipe
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Qty
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Satuan
              </th>
              <th
                class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
              >
                Harga
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
            <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50">
              <td class="px-4 py-3 w-10">
                <input
                  type="checkbox"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ item.nama }}</td>
              <td v-if="isBarangJasaPerihal" class="px-4 py-3 text-sm text-gray-900">
                {{ item.tipe }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ item.qty }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ item.satuan }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">
                {{ formatRupiah(item.harga) }}
              </td>
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                {{ formatRupiah(item.qty * item.harga) }}
              </td>
              <td class="px-4 py-3 w-16">
                <button
                  type="button"
                  class="w-6 h-6 bg-red-500 text-white rounded flex items-center justify-center hover:bg-red-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                  @click="removeItem(idx)"
                  @click.stop.prevent
                  :disabled="terminInfo?.status_termin === 'in_progress'"
                  title="Hapus"
                >
                  <Trash2 class="w-3 h-3" />
                </button>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                Belum ada barang
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
                  v-model="diskonAktif"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">Diskon</span>
              </label>
              <input
                v-if="diskonAktif"
                type="text"
                v-model="displayDiskon"
                placeholder="10,000"
                @keydown="allowNumericKeydown"
                :class="[
                  'w-40 px-3 py-2 border rounded-md text-sm focus:outline-none focus:ring-2',
                  Number(diskon || 0) > subtotal
                    ? 'border-red-500 focus:ring-red-500 focus:border-red-500'
                    : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500',
                ]"
              />
              <div
                v-if="diskonAktif && Number(diskon || 0) > subtotal"
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
                  v-model="ppnAktif"
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
                  v-model="pphAktif"
                  class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-sm font-medium text-gray-700">PPH</span>
              </label>
              <div v-if="pphAktif" class="flex-1 flex items-center space-x-2">
                <div class="w-40">
                  <CustomSelect
                    :model-value="pphKode"
                    @update:modelValue="(val) => (pphKode = val as string)"
                    :options="(pphList || []).map((p: any) => ({
                      label: `${p.nama} (${(p.tarif * 100).toFixed(2)}%)`,
                      value: p.kode
                    }))"
                    placeholder="Pilih PPH"
                    class="w-full compact-select"
                  />
                </div>
                <button
                  class="w-6 h-6 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors text-xs disabled:bg-gray-300 disabled:cursor-not-allowed"
                  @click="showAddPph = true"
                  :disabled="terminInfo?.status_termin === 'in_progress'"
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
                  formatRupiah(subtotal)
                }}</span>
              </div>
              <div v-if="diskonAktif" class="flex justify-between items-center text-sm">
                <span class="text-gray-600">Diskon</span>
                <span class="font-semibold text-gray-900">{{
                  formatRupiah(diskon)
                }}</span>
              </div>
              <div class="flex justify-between items-center text-sm">
                <span class="text-gray-600">DPP</span>
                <span class="font-semibold text-gray-900">{{ formatRupiah(dpp) }}</span>
              </div>
              <div v-if="ppnAktif" class="flex justify-between items-center text-sm">
                <span class="text-gray-600">PPN</span>
                <span class="font-semibold text-gray-900">{{
                  formatRupiah(ppnNominal)
                }}</span>
              </div>
              <div
                v-if="pphAktif && pph"
                class="flex justify-between items-center text-sm"
              >
                <span class="text-gray-600">PPH {{ (pph.tarif * 100).toFixed(0) }}%</span>
                <span class="font-semibold text-gray-900">{{
                  formatRupiah(pphNominal)
                }}</span>
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

      <!-- Modals -->
      <TambahBarangModal
        :show="showAdd"
        @submit="addItem"
        @submit-keep="addItemKeep"
        @close="showAdd = false"
        :selectedPerihalName="props.selectedPerihalName"
      />
      <TambahPphModal :show="showAddPph" @submit="addPph" @close="showAddPph = false" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { CirclePlus, CircleMinus, Trash2 } from "lucide-vue-next";
import TambahBarangModal from "./TambahBarangModal.vue";
import TambahPphModal from "./TambahPphModal.vue";
import CustomSelect from "@/components/ui/CustomSelect.vue";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";

const props = defineProps<{
  items: any[];
  diskon?: number | null;
  ppn: boolean;
  pph: any[];
  pphList: any[];
  nominal?: number;
  form?: { tipe_po?: string; status_termin?: string };
  terminInfo?: any;
  selectedPerihalName?: string;
}>();

const emit = defineEmits([
  "update:items",
  "update:diskon",
  "update:ppn",
  "update:pph",
  "add-pph",
]);

const items = ref<any[]>(props.items || []);
const isSyncingFromProps = ref(false);
const diskon = ref<number | null>(props.diskon ?? null);
const diskonAktif = ref(!!(diskon.value && diskon.value > 0));
const ppnAktif = ref(props.ppn || false);
const pphAktif = ref(props.pph && props.pph.length > 0);
const pphKode = ref("");
// const selectedPerihalName = ref(props.selectedPerihalName ?? "");

function syncPphKodeFromProps() {
  const first = props.pph && props.pph.length > 0 ? props.pph[0] : (null as any);
  if (!first) {
    pphKode.value = "";
    return;
  }
  // If parent passed an object with kode
  if (typeof first === "object" && first && "kode" in first) {
    pphKode.value = (first as any).kode || "";
    return;
  }
  // If parent passed an id or kode value, resolve to kode via pphList
  const matched = props.pphList?.find((p: any) => p.id === first || p.kode === first);
  pphKode.value = matched ? matched.kode : "";
}

// Initialize and keep in sync when parent pph changes
syncPphKodeFromProps();
watch(
  () => props.pph,
  () => {
    pphAktif.value = !!(props.pph && props.pph.length > 0);
    syncPphKodeFromProps();
  },
  { deep: true }
);

// Watch diskonAktif and pphAktif, emit reset to parent if unchecked
watch(
  () => diskonAktif.value,
  (val) => {
    if (!val) {
      emit("update:diskon", 0);
    }
  }
);
watch(
  () => pphAktif.value,
  (val) => {
    if (!val) {
      emit("update:pph", []);
    }
  }
);
const showAdd = ref(false);
const showAddPph = ref(false);

// Computed property to determine if it's "Permintaan Pembayaran Jasa"
const isJasaPerihal = computed(() => {
  return props.selectedPerihalName?.toLowerCase() === "permintaan pembayaran jasa";
});

// Computed property for the header text
const headerText = computed(() => {
  return isJasaPerihal.value ? "Nama Jasa" : "Nama Barang";
});

// Debug function to track modal state
function openAddModal(event?: Event) {
  // Prevent any default behavior and stop propagation
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  showAdd.value = true;
}

// const isLainnyaTerminBerjalan = computed(() => {
//   return (
//     props.form?.tipe_po === "Lainnya" && props.terminInfo?.status_termin === "in_progress"
//   );
// });

// Watch for changes in props.items to sync with local items
watch(
  () => props.items,
  (newItems) => {
    if (newItems && Array.isArray(newItems)) {
      isSyncingFromProps.value = true;
      items.value = [...newItems];
      // reset flag in next microtask so the items watcher won't emit for this sync
      Promise.resolve().then(() => {
        isSyncingFromProps.value = false;
      });
    }
  },
  { deep: true }
);

// Save items to localStorage whenever they change
watch(
  items,
  (val) => {
    if (isSyncingFromProps.value) return;
    // Emit changes to parent so v-model stays in sync
    emit("update:items", [...val]);
    // If all items are removed/cleared, reset discount, PPN, and PPH states
    if (val.length === 0) {
      diskonAktif.value = false;
      diskon.value = null;
      ppnAktif.value = false;
      pphAktif.value = false;
      pphKode.value = "";
    }
  },
  { deep: true }
);

// Disable auto-loading from localStorage to prevent stale data when creating new PO
onMounted(() => {
  // Intentionally left blank to avoid restoring previous items implicitly
});
watch(diskon, (val) => emit("update:diskon", val));
watch(ppnAktif, (val) => emit("update:ppn", val));
watch(pphKode, (val) => {
  if (val && pph.value) emit("update:pph", [pph.value.id || pph.value.kode]);
  else emit("update:pph", []);
});

const subtotal = computed(() => {
  // Fallback nominal hanya untuk tipe Lainnya
  const isLainnya = props.form?.tipe_po === "Lainnya";
  const hasItems = Array.isArray(items.value) && items.value.length > 0;
  if (isLainnya && !hasItems && typeof props.nominal === "number" && !isNaN(props.nominal) && props.nominal > 0) {
    return props.nominal;
  }
  return items.value.reduce((sum, i) => sum + i.qty * i.harga, 0);
});

const dpp = computed(() =>
  Math.max(subtotal.value - (diskonAktif.value ? diskon.value || 0 : 0), 0)
);
const ppnNominal = computed(() => (ppnAktif.value ? dpp.value * 0.11 : 0));
const pph = computed(() => (Array.isArray(props.pphList) ? props.pphList : []).find((p) => p.kode === pphKode.value));
const isBarangJasaPerihal = computed(() => {
  return props.selectedPerihalName?.toLowerCase() === "permintaan pembayaran barang/jasa";
});

const pphNominal = computed(() => {
  if (!pphAktif.value || !pph.value) return 0;
  // Jika perihal adalah Permintaan Pembayaran Barang/Jasa, hanya item Jasa yang dihitung
  if (isBarangJasaPerihal.value) {
    // Fallback untuk tipe Lainnya tanpa item: gunakan nominal sebagai DPP Jasa
    const isLainnya = props.form?.tipe_po === "Lainnya";
    const hasItems = Array.isArray(items.value) && items.value.length > 0;
    const jasaDpp = hasItems
      ? items.value.filter((i) => i.tipe === "Jasa").reduce((sum, i) => sum + i.qty * i.harga, 0)
      : (isLainnya ? (Number(props.nominal || 0)) : 0);
    const jasaDppAfterDiskon = Math.max(
      jasaDpp - (diskonAktif.value ? diskon.value || 0 : 0),
      0
    );
    return jasaDppAfterDiskon * pph.value.tarif;
  }
  // Default: semua item
  return dpp.value * pph.value.tarif;
});
const grandTotal = computed(() => dpp.value + ppnNominal.value + pphNominal.value);

// Formatted discount input
const displayDiskon = computed<string>({
  get: () => formatCurrency(diskon.value ?? ""),
  set: (val: string) => {
    const parsed = parseCurrency(val);
    diskon.value = parsed === "" ? null : Number(parsed);
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

function addItem(barang: any) {
  if (!barang.tipe) {
    barang.tipe = isJasaPerihal.value ? "Jasa" : "Barang";
    // fallback aja kalau kosong
  }
  items.value.push(barang);
  showAdd.value = false;
}

function addItemKeep(barang: any) {
  items.value.push(barang);
  // keep modal open
}

function removeItem(idx: number) {
  items.value.splice(idx, 1);
}

function clearAll() {
  items.value = [];
}

// Function to clear localStorage (can be called from parent when draft is saved)
function clearDraftStorage() {
  localStorage.removeItem("po_draft_items");
}

// Expose the function to parent component
defineExpose({
  clearDraftStorage,
  grandTotal,
});

function addPph(pphBaru: any) {
  emit("add-pph", pphBaru);
  pphKode.value = pphBaru.kode;
  showAddPph.value = false;
}

function formatRupiah(val: number | string | null | undefined) {
  const num = Number(val) || 0;
  const formattedNumber = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(num);

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

/* Compact select styling for PPH dropdown */
.compact-select :deep(.custom-select) {
  height: 36px;
  min-height: 36px;
  padding-top: 6px;
  padding-bottom: 6px;
}

/* Table row hover effect */
tbody tr:hover {
  background-color: #f9fafb;
}
</style>
