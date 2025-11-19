<template>
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Detail Pengeluaran</h3>
    </div>

    <div class="overflow-x-auto border border-gray-200 rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Detail</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Keterangan</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Harga</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Qty</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Satuan</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Subtotal</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Realisasi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(it, idx) in localItems" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.jenis_pengeluaran_text }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.keterangan }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(it.harga) }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatQty(it.qty) }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ it.satuan }}</td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ formatCurrency((Number(it.harga)||0) * (Number(it.qty)||0)) }}</td>
            <td class="px-4 py-3">
              <input
                type="text"
                class="w-32 px-3 py-1.5 text-sm text-right border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                :value="formatCurrency(it.realisasi)"
                @keydown="allowNumericKeydown"
                @input="(e) => onRealisasiInput(e, idx)"
              />
            </td>
          </tr>
          <tr v-if="!localItems || localItems.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
              Belum ada data detail pengeluaran
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="mt-4 flex justify-end">
      <div class="w-full sm:w-80">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Total Realisasi</span>
              <span class="font-semibold text-gray-900">{{ formatCurrency(totalRealisasi) }}</span>
            </div>
            <div class="border-t border-gray-300 pt-2 mt-2">
              <div class="flex justify-between items-center">
                <span class="text-base font-semibold text-gray-900">Sisa</span>
                <span
                  class="text-lg font-bold"
                  :class="sisa >= 0 ? 'text-green-600' : 'text-red-600'"
                >
                  {{ formatCurrency(sisa) }}
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="showAdd" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50" @click.self="showAdd = false">
      <div class="bg-white rounded-lg shadow-xl p-6 max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">Tambah Detail Pengeluaran</h3>
        <form @submit.prevent="addItem">
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="floating-input">
              <input
                type="text"
                v-model="newItem.jenis_pengeluaran_text"
                id="jenis_pengeluaran_text"
                class="floating-input-field"
                placeholder=" "
              />
              <label for="jenis_pengeluaran_text" class="floating-label">Jenis Pengeluaran</label>
            </div>
            <div class="floating-input">
              <input
                type="text"
                v-model="newItem.keterangan"
                id="keterangan"
                class="floating-input-field"
                placeholder=" "
              />
              <label for="keterangan" class="floating-label">Keterangan</label>
            </div>
            <div class="floating-input">
              <input
                type="text"
                :value="formatCurrency(newItem.harga)"
                id="harga"
                class="floating-input-field"
                placeholder=" "
                @keydown="allowNumericKeydown"
                @input="onNewItemHargaInput"
              />
              <label for="harga" class="floating-label">Harga</label>
            </div>
            <div class="floating-input">
              <input
                type="number"
                step="0.01"
                v-model.number="newItem.qty"
                id="qty"
                class="floating-input-field"
                placeholder=" "
              />
              <label for="qty" class="floating-label">Qty</label>
            </div>
            <div class="floating-input">
              <input
                type="text"
                v-model="newItem.satuan"
                id="satuan"
                class="floating-input-field"
                placeholder=" "
              />
              <label for="satuan" class="floating-label">Satuan</label>
            </div>
            <div class="floating-input">
              <input
                type="text"
                :value="formatCurrency(newItem.realisasi)"
                id="realisasi"
                class="floating-input-field"
                placeholder=" "
                @keydown="allowNumericKeydown"
                @input="onNewItemRealisasiInput"
              />
              <label for="realisasi" class="floating-label">Realisasi</label>
            </div>
          </div>
          <div class="mt-6 flex justify-end gap-3">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              @click="showAdd=false"
            >
              Tutup
            </button>
            <button
              type="submit"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
            >
              Tambah
            </button>
          </div>
        </form>
      </div>
    </div>

</template>

<script setup lang="ts">
import { ref, reactive, computed, watch } from 'vue';
import { parseCurrency, formatCurrency } from '@/lib/currencyUtils';

const props = defineProps<{
  items: any[];
  totalAnggaran?: number | null;
}>();

const emit = defineEmits(['update:items']);

const localItems = ref<any[]>(props.items || []);
const showAdd = ref(false);
const newItem = reactive<any>({
  jenis_pengeluaran_id: null,
  jenis_pengeluaran_text: '',
  keterangan: '',
  harga: 0,
  qty: 0,
  satuan: '',
  realisasi: 0,
});

watch(
  () => props.items,
  (val) => {
    // Sync sekali arah dari parent ke lokal saat props berubah (misal saat pilih PO Anggaran)
    localItems.value = Array.isArray(val) ? [...val] : [];
  }
);

const totalRealisasi = computed(() => (localItems.value || []).reduce((a: number, it: any) => a + (Number(it.realisasi)||0), 0));
function formatQty(value: any) {
  const num = Number(value || 0);
  const fixed = num.toFixed(2); // 2 decimal places
  return fixed
    .replace(/\.00$/, '')   // 1) 2.00 -> 2
    .replace(/(\.\d)0$/, '$1'); // 2) 2.50 -> 2.5
}
const sisa = computed(() => Number(props.totalAnggaran || 0) - Number(totalRealisasi.value || 0));

function addItem() {
  localItems.value.push({ ...newItem });
  showAdd.value = false;
  Object.assign(newItem, {
    jenis_pengeluaran_id: null,
    jenis_pengeluaran_text: '',
    keterangan: '',
    harga: 0,
    qty: 0,
    satuan: '',
    realisasi: 0,
  });
  // Emit perubahan items ke parent setelah user menambah baris baru
  emit('update:items', [...localItems.value]);
}

function onRealisasiInput(e: Event, idx: number) {
  const input = e.target as HTMLInputElement;
  const parsed = parseCurrency(input.value);
  localItems.value[idx].realisasi = parsed === '' ? 0 : Number(parsed);
  // Emit perubahan items ke parent hanya saat user mengubah nilai realisasi
  emit('update:items', [...localItems.value]);
}

function onNewItemHargaInput(e: Event) {
  const input = e.target as HTMLInputElement;
  const parsed = parseCurrency(input.value);
  newItem.harga = parsed === '' ? 0 : Number(parsed);
}

function onNewItemRealisasiInput(e: Event) {
  const input = e.target as HTMLInputElement;
  const parsed = parseCurrency(input.value);
  newItem.realisasi = parsed === '' ? 0 : Number(parsed);
}

function allowNumericKeydown(event: KeyboardEvent) {
  const allowedKeys = [
    'Backspace','Delete','Tab','Enter','Escape','ArrowLeft','ArrowRight','Home','End',',','.',
    '0','1','2','3','4','5','6','7','8','9'
  ];
  if (event.ctrlKey || event.metaKey) return;
  if (!allowedKeys.includes(event.key)) event.preventDefault();
}
</script>

<style scoped>
.floating-input { position: relative; }
.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.3s ease-in-out;
}
.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #9ca3af;
  transition: all 0.3s ease-in-out;
  pointer-events: none;
  transform-origin: left top;
  background-color: white;
  padding: 0 0.25rem;
  z-index: 1;
}
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
}
.floating-input-field:is(textarea) {
  padding-top: 1rem;
  padding-bottom: 1rem;
}
</style>
