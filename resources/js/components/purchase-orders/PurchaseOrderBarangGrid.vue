<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <h2 class="font-semibold text-lg mb-4 text-gray-800">Daftar Barang/Jasa</h2>

    <!-- Action buttons -->
    <div class="mb-4 flex gap-2">
      <button
        class="px-3 py-1.5 bg-blue-500 text-white text-sm rounded hover:bg-blue-600 transition-colors"
        @click="showAdd = true"
      >
        Tambah (+)
      </button>
      <button
        class="px-3 py-1.5 bg-red-500 text-white text-sm rounded hover:bg-red-600 transition-colors disabled:bg-gray-300 disabled:cursor-not-allowed"
        @click="clearAll"
        :disabled="!items.length"
      >
        Clear (-)
      </button>
    </div>

    <!-- Table -->
    <div class="overflow-hidden rounded-lg border border-gray-200 mb-4">
      <table class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th
              class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              Nama Barang
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
              class="px-4 py-3 text-center text-xs font-medium text-gray-700 uppercase tracking-wider"
            >
              Aksi
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.nama }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.qty }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.satuan }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">
              {{ formatRupiah(item.harga) }}
            </td>
            <td class="px-4 py-3 text-center">
              <button
                class="px-2 py-1 bg-red-500 text-white text-xs rounded hover:bg-red-600 transition-colors"
                @click="removeItem(idx)"
              >
                Hapus
              </button>
            </td>
          </tr>
          <tr v-if="!items.length">
            <td colspan="5" class="px-4 py-8 text-center text-sm text-gray-500">
              Belum ada barang
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Checkbox options -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
      <!-- Diskon -->
      <div class="space-y-2">
        <label class="flex items-center space-x-2">
          <input
            type="checkbox"
            v-model="diskonAktif"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="text-sm font-medium text-gray-700">Diskon</span>
        </label>
        <input
          v-if="diskonAktif"
          type="number"
          min="0"
          v-model.number="diskon"
          placeholder="Nominal Diskon (Rp)"
          class="w-full px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        />
      </div>

      <!-- PPN -->
      <div>
        <label class="flex items-center space-x-2">
          <input
            type="checkbox"
            v-model="ppnAktif"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="text-sm font-medium text-gray-700">PPN (11%)</span>
        </label>
      </div>

      <!-- PPH -->
      <div class="space-y-2">
        <label class="flex items-center space-x-2">
          <input
            type="checkbox"
            v-model="pphAktif"
            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
          />
          <span class="text-sm font-medium text-gray-700">PPH</span>
        </label>
        <div v-if="pphAktif" class="space-y-2">
          <CustomSelect
            :model-value="pphKode"
            @update:modelValue="(val) => (pphKode = val as string)"
            :options="pphList.map((p: any) => ({
              label: `${p.nama} (${(p.tarif * 100).toFixed(2)}%)`,
              value: p.kode
            }))"
            placeholder="Pilih PPH"
          />
          <button
            class="px-3 py-1.5 bg-blue-500 text-white text-xs rounded hover:bg-blue-600 transition-colors"
            @click="showAddPph = true"
          >
            Tambah PPH
          </button>
        </div>
      </div>
    </div>

    <!-- Summary Card -->
    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
      <div class="space-y-2">
        <div class="flex justify-between items-center text-sm">
          <span class="text-gray-600">Subtotal</span>
          <span class="font-semibold text-gray-900">{{ formatRupiah(subtotal) }}</span>
        </div>
        <div v-if="diskonAktif" class="flex justify-between items-center text-sm">
          <span class="text-gray-600">Diskon</span>
          <span class="font-semibold text-red-600">-{{ formatRupiah(diskon) }}</span>
        </div>
        <div class="flex justify-between items-center text-sm">
          <span class="text-gray-600">DPP</span>
          <span class="font-semibold text-gray-900">{{ formatRupiah(dpp) }}</span>
        </div>
        <div v-if="ppnAktif" class="flex justify-between items-center text-sm">
          <span class="text-gray-600">PPN (11%)</span>
          <span class="font-semibold text-gray-900">{{ formatRupiah(ppnNominal) }}</span>
        </div>
        <div v-if="pphAktif && pph" class="flex justify-between items-center text-sm">
          <span class="text-gray-600">PPH ({{ (pph.tarif * 100).toFixed(2) }}%)</span>
          <span class="font-semibold text-gray-900">{{ formatRupiah(pphNominal) }}</span>
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

    <!-- Modals -->
    <TambahBarangModal :show="showAdd" @submit="addItem" @close="showAdd = false" />
    <TambahPphModal :show="showAddPph" @submit="addPph" @close="showAddPph = false" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from "vue";
import TambahBarangModal from "./TambahBarangModal.vue";
import TambahPphModal from "./TambahPphModal.vue";
import CustomSelect from "@/components/ui/CustomSelect.vue";

const props = defineProps<{
  items: any[];
  diskon: number;
  ppn: boolean;
  pph: any[];
  pphList: any[];
  nominal?: number;
}>();

const emit = defineEmits([
  "update:items",
  "update:diskon",
  "update:ppn",
  "update:pph",
  "add-pph",
]);

const items = ref<any[]>(props.items || []);
const diskon = ref<number>(props.diskon || 0);
const diskonAktif = ref(diskon.value > 0);
const ppnAktif = ref(props.ppn || false);
const pphAktif = ref(props.pph && props.pph.length > 0);
const pphKode = ref((props.pph && props.pph[0]?.kode) || "");
const showAdd = ref(false);
const showAddPph = ref(false);

watch(items, (val) => emit("update:items", val), { deep: true });
watch(diskon, (val) => emit("update:diskon", val));
watch(ppnAktif, (val) => emit("update:ppn", val));
watch(pphKode, (val) => {
  if (val) emit("update:pph", [pph.value]);
  else emit("update:pph", []);
});

const subtotal = computed(() =>
  typeof props.nominal === "number" && !isNaN(props.nominal) && props.nominal > 0
    ? props.nominal
    : items.value.reduce((sum, i) => sum + i.qty * i.harga, 0)
);

const dpp = computed(() =>
  Math.max(subtotal.value - (diskonAktif.value ? diskon.value : 0), 0)
);
const ppnNominal = computed(() => (ppnAktif.value ? dpp.value * 0.11 : 0));
const pph = computed(() => props.pphList.find((p) => p.kode === pphKode.value));
const pphNominal = computed(() =>
  pphAktif.value && pph.value ? dpp.value * pph.value.tarif : 0
);
const grandTotal = computed(() => dpp.value + ppnNominal.value + pphNominal.value);

function addItem(barang: any) {
  items.value.push(barang);
  showAdd.value = false;
}

function removeItem(idx: number) {
  items.value.splice(idx, 1);
}

function clearAll() {
  items.value = [];
}

function addPph(pphBaru: any) {
  emit("add-pph", pphBaru);
  pphKode.value = pphBaru.kode;
  showAddPph.value = false;
}

function formatRupiah(val: number) {
  const formattedNumber = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(val);

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
