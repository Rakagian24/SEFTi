<script setup lang="ts">
import { computed, ref } from "vue";
// import { CirclePlus, CircleMinus } from "lucide-vue-next";
import AddItemModal from "@/components/bpb/AddItemModal.vue";

type Item = { nama_barang: string; qty: number; satuan: string; harga: number };

const props = defineProps<{
  modelValue: {
    items: Item[];
    diskon: number;
    use_ppn: boolean;
    ppn_rate: number;
    use_pph: boolean;
    pph_rate: number;
  };
}>();

const emit = defineEmits(["update:modelValue", "clear-items", "add-pph"]);

const subtotal = computed(() =>
  props.modelValue.items.reduce(
    (c, it) => c + Number(it.qty || 0) * Number(it.harga || 0),
    0
  )
);
const dpp = computed(() =>
  Math.max(0, subtotal.value - Number(props.modelValue.diskon || 0))
);
// PPN fixed 11% when checked
const ppn = computed(() => (props.modelValue.use_ppn ? dpp.value * 0.11 : 0));
const pph = computed(() =>
  props.modelValue.use_pph
    ? dpp.value * (Number(props.modelValue.pph_rate || 0) / 100)
    : 0
);
const grandTotal = computed(() => dpp.value + ppn.value + pph.value);

// function update(partial: any) {
//   emit("update:modelValue", { ...props.modelValue, ...partial });
// }

function formatRupiah(val: number | string | null | undefined) {
  const num = Number(val) || 0;
  const formattedNumber = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(num);
  return `Rp ${formattedNumber}`;
}

// Add item modal state and handlers
const showAdd = ref(false);
// function openAdd() {
//   showAdd.value = true;
// }
function closeAdd() {
  showAdd.value = false;
}
function saveAdd(item: { nama_barang: string; qty: number; satuan: string; harga: number }) {
  const next = [...props.modelValue.items, item];
  emit("update:modelValue", { ...props.modelValue, items: next });
  showAdd.value = false;
}
function saveContinue(item: { nama_barang: string; qty: number; satuan: string; harga: number }) {
  const next = [...props.modelValue.items, item];
  emit("update:modelValue", { ...props.modelValue, items: next });
  // keep modal open
}

function setQty(index: number, value: number) {
  const items: any[] = props.modelValue.items as any[];
  const it: any = items[index];
  if (!it) return;
  // Batas maksimal: gunakan remaining_qty jika ada, kalau tidak fallback ke qty saat ini
  const rawMax = it?.remaining_qty ?? it?.qty ?? Infinity;
  const max = Number(rawMax);
  const v = Math.max(0, Math.min(Number(value || 0), isFinite(max) ? max : Number(value || 0)));
  const next = items.map((x, i) => (i === index ? { ...x, qty: v } : x));
  emit("update:modelValue", { ...props.modelValue, items: next as any });
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <!-- Action buttons -->
    <!-- <div class="mb-4 flex gap-1">
      <button
        type="button"
        class="w-8 h-8 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors"
        @click="openAdd"
        title="Tambah"
      >
        <CirclePlus class="w-4 h-4" />
      </button>
      <button
        type="button"
        class="w-8 h-8 bg-yellow-500 text-white rounded flex items-center justify-center hover:bg-yellow-600 transition-colors"
        @click="$emit('clear-items')"
        :disabled="!modelValue.items.length"
        :class="{ 'bg-gray-300 cursor-not-allowed': !modelValue.items.length }"
        title="Clear"
      >
        <CircleMinus class="w-4 h-4" />
      </button>
    </div> -->

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 mb-4">
      <table class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Nama Barang
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Qty
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Satuan
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Harga
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Subtotal
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(it, idx) in modelValue.items" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.nama_barang }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">
              <div class="flex items-center gap-2">
                <input
                  type="number"
                  :value="(it as any).qty ?? (it as any).remaining_qty ?? 0"
                  @input="setQty(idx, Number(($event.target as HTMLInputElement).value))"
                  :min="0"
                  :max="Number((it as any).remaining_qty ?? (it as any).qty ?? Infinity)"
                  class="w-24 px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <span class="text-xs text-gray-500">
                  Sisa: {{ Number((it as any).remaining_qty ?? (it as any).qty ?? 0) }}
                </span>
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.satuan }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ formatRupiah(it.harga) }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">
              {{ formatRupiah(Number(it.qty) * Number(it.harga)) }}
            </td>
          </tr>
          <tr v-if="!modelValue.items.length">
            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
              Belum ada barang
            </td>
          </tr>
          <tr v-if="!modelValue.items.length">
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
        <!-- <div class="space-y-4">
          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 min-w-[80px]">
              <span class="text-sm font-medium text-gray-700">Diskon</span>
            </label>
            <input
              type="number"
              :value="modelValue.diskon"
              @input="update({diskon: Number(($event.target as HTMLInputElement).value)})"
              class="w-40 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              placeholder="0"
            />
          </div>
          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 min-w-[80px]">
              <input
                type="checkbox"
                :checked="modelValue.use_ppn"
                @change="update({use_ppn: ($event.target as HTMLInputElement).checked, ppn_rate: 11})"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">PPN (11%)</span>
            </label>
          </div>
          <div class="flex items-center space-x-4">
            <label class="flex items-center space-x-2 min-w-[80px]">
              <input
                type="checkbox"
                :checked="modelValue.use_pph"
                @change="update({use_pph: ($event.target as HTMLInputElement).checked})"
                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="text-sm font-medium text-gray-700">PPH</span>
            </label>
            <div v-if="modelValue.use_pph" class="flex items-center space-x-3">
              <span class="text-sm text-gray-600">Tarif: <strong>{{ modelValue.pph_rate || 0 }}%</strong></span>
              <button
                type="button"
                class="px-2 h-8 bg-blue-500 text-white rounded flex items-center justify-center hover:bg-blue-600 transition-colors text-xs"
                @click="$emit('add-pph')"
                title="Pilih PPH dari modul"
              >
                Pilih PPH
              </button>
            </div>
          </div>
        </div> -->
      </div>

      <!-- Right side - Summary -->
      <div class="lg:w-80">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Total</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(subtotal) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Diskon</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(modelValue.diskon) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">DPP</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(dpp) }}</span>
            </div>
            <div v-if="modelValue.use_ppn" class="flex justify-between items-center text-sm">
              <span class="text-gray-600">PPN</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(ppn) }}</span>
            </div>
            <div v-if="modelValue.use_pph" class="flex justify-between items-center text-sm">
              <span class="text-gray-600">PPH</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(pph) }}</span>
            </div>
            <div class="border-t border-gray-300 pt-2 mt-2">
              <div class="flex justify-between items-center">
                <span class="text-base font-semibold text-gray-900">Grand Total</span>
                <span class="text-lg font-bold text-gray-900">{{ formatRupiah(grandTotal) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Add Item Modal -->
    <AddItemModal :show="showAdd" @close="closeAdd" @save="saveAdd" @save-continue="saveContinue" />
  </div>
</template>

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
input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Table row hover effect */
tbody tr:hover {
  background-color: #f9fafb;
}

/* Disabled button styling */
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
