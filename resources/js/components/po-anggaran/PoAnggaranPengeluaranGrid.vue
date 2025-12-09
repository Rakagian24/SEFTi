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
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Detail</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Keterangan</th>
              <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Harga</th>
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
                Subtotal
              </th>
              <th class="px-4 py-3 w-16">
                <!-- Action column header -->
              </th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50">
              <td class="px-4 py-3 text-sm text-gray-900">{{ item.jenis_pengeluaran_text || item.detail || item.nama }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ item.keterangan || '' }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ formatRupiah(item.harga) }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ formatQty(item.qty) }}</td>
              <td class="px-4 py-3 text-sm text-gray-900">{{ item.satuan }}</td>
              <td class="px-4 py-3 text-sm text-gray-900 font-medium">
                {{ formatRupiah(item.qty * item.harga) }}
              </td>
              <td class="px-4 py-3 w-24">
                <div class="flex items-center justify-end gap-1">
                  <button
                    type="button"
                    class="w-6 h-6 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
                    @click="openEditModal(idx)"
                    @click.stop.prevent
                    :disabled="terminInfo?.status_termin === 'in_progress'"
                    title="Edit"
                  >
                    <Pencil class="w-3 h-3" />
                  </button>
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
                </div>
              </td>
            </tr>
            <tr v-if="!items.length">
              <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500">
                Belum ada detail anggaran
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-end mb-4 pr-44">
          <div class="flex items-center gap-4">
            <span class="text-sm font-semibold text-gray-700">Grand Total:</span>
            <span class="text-sm font-bold text-gray-900">{{ formatRupiah(grandTotal) }}</span>
          </div>
      </div>

      <!-- Modals -->
      <TambahPengeluaranModal
        :show="showAdd"
        :mode="modalMode"
        :initial-item="editingItem"
        @submit="handleModalSubmit"
        @submit-keep="handleModalSubmitKeep"
        @close="handleModalClose"
        :selectedPerihalName="props.selectedPerihalName"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { CirclePlus, CircleMinus, Trash2, Pencil } from "lucide-vue-next";
import TambahPengeluaranModal from "./TambahPengeluaranModal.vue";

const props = defineProps<{
  items: any[];
  diskon?: number | null;
  ppn: boolean;
  nominal?: number;
  form?: { tipe_po?: string; status_termin?: string };
  terminInfo?: any;
  selectedPerihalName?: string;
  pphList?: Array<{ id: number; kode: string; nama: string; tarif: number }>;
  pph?: any[];
}>();

const emit = defineEmits(["update:items", "update:diskon", "update:ppn", "update:pph", "add-pph"]);

const items = ref<any[]>(props.items || []);
const isSyncingFromProps = ref(false);
const diskon = ref<number | null>(props.diskon ?? null);
const diskonAktif = ref(!!(diskon.value && diskon.value > 0));
const ppnAktif = ref(props.ppn || false);
const pphAktif = ref(Array.isArray(props.pph) ? props.pph.length > 0 : false);
const selectedPphId = computed<number | ''>({
  get: () => {
    const arr = Array.isArray(props.pph) ? props.pph : [];
    const v = arr && arr.length ? arr[0] : null;
    if (v === null || v === undefined || v === '') return '';
    const n = Number(v as any);
    return isNaN(n) ? '' : n;
  },
  set: (val) => {
    if (!val) emit('update:pph', []);
    else emit('update:pph', [Number(val)]);
  }
});
watch(pphAktif, (val) => {
  if (!val) emit('update:pph', []);
});

// Watch diskonAktif and emit reset to parent if unchecked
watch(
  () => diskonAktif.value,
  (val) => {
    if (!val) {
      emit("update:diskon", 0);
    }
  }
);
const showAdd = ref(false);
const modalMode = ref<'add' | 'edit'>('add');
const editingIndex = ref<number | null>(null);
const editingItem = ref<any | null>(null);

// Computed property to determine if it's "Permintaan Pembayaran Jasa"
const isJasaPerihal = computed(() => {
  return props.selectedPerihalName?.toLowerCase() === "permintaan pembayaran jasa";
});

// header text no longer needed after refactor

// Debug function to track modal state
function openAddModal(event?: Event) {
  // Prevent any default behavior and stop propagation
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  modalMode.value = 'add';
  editingIndex.value = null;
  editingItem.value = null;
  showAdd.value = true;
}

function openEditModal(idx: number, event?: Event) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  if (props.terminInfo?.status_termin === 'in_progress') return;
  modalMode.value = 'edit';
  editingIndex.value = idx;
  editingItem.value = { ...items.value[idx] };
  showAdd.value = true;
}

function resetModalState() {
  showAdd.value = false;
  modalMode.value = 'add';
  editingIndex.value = null;
  editingItem.value = null;
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
    // If all items are removed/cleared, reset discount and PPN states
    if (val.length === 0) {
      diskonAktif.value = false;
      diskon.value = null;
      ppnAktif.value = false;
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
// function onChangePph(e: Event) {}

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
// moved above to ensure availability in watchers

const jasaBase = computed(() => {
  const isLainnya = props.form?.tipe_po === 'Lainnya';
  const hasItems = Array.isArray(items.value) && items.value.length > 0;
  if (isLainnya && !hasItems && typeof props.nominal === 'number' && !isNaN(props.nominal) && props.nominal > 0) {
    return props.nominal;
  }
  // Jika perihal adalah "Permintaan Pembayaran Jasa", gunakan subtotal penuh
  if (isJasaPerihal.value) {
    return items.value.reduce((sum, i) => sum + i.qty * i.harga, 0);
  }
  // Selain itu, hanya ambil item bertipe 'jasa'
  return items.value
    .filter(i => String(i.tipe || '').toLowerCase() === 'jasa')
    .reduce((sum, i) => sum + i.qty * i.harga, 0);
});
const selectedPph = computed(() => (props.pphList || []).find(p => Number(p.id) === Number(selectedPphId.value)));
const pphNominal = computed(() => {
  const tarif = selectedPph.value?.tarif || 0; // tarif in decimal (e.g., 0.02)
  return jasaBase.value * tarif;
});
const grandTotal = computed(() => dpp.value + ppnNominal.value - pphNominal.value);

function addItem(barang: any) {
  if (!barang.tipe) {
    barang.tipe = isJasaPerihal.value ? "Jasa" : "Barang";
    // fallback aja kalau kosong
  }
  items.value.push(barang);
  resetModalState();
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

function formatRupiah(val: number | string | null | undefined) {
  const num = Number(val) || 0;
  const formattedNumber = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(num);

  return `Rp ${formattedNumber}`;
}

function formatQty(val: number | string | null | undefined) {
  const num = Number(val);
  if (isNaN(num)) return "";
  return new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num);
}

function updateItem(updated: any) {
  if (editingIndex.value === null) return;
  const targetIdx = editingIndex.value;
  const existing = items.value[targetIdx] || {};
  if (!updated.tipe) {
    updated.tipe = existing.tipe || (isJasaPerihal.value ? 'Jasa' : 'Barang');
  }
  items.value.splice(targetIdx, 1, { ...existing, ...updated });
  resetModalState();
}

function handleModalClose() {
  resetModalState();
}

function handleModalSubmit(barang: any) {
  if (modalMode.value === 'edit') {
    updateItem(barang);
  } else {
    addItem(barang);
  }
}

function handleModalSubmitKeep(barang: any) {
  if (modalMode.value === 'edit') {
    updateItem(barang);
  } else {
    addItemKeep(barang);
  }
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
.compact-select {
  min-width: 280px;
}
.compact-select :deep(.custom-select) {
  height: 36px;
  min-height: 36px;
  padding-top: 6px;
  padding-bottom: 6px;
  min-width: 280px;
}

/* Table row hover effect */
tbody tr:hover {
  background-color: #f9fafb;
}
</style>
