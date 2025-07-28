<template>
  <div class="bg-white rounded-lg shadow-b-sm border-b border-gray-200">
    <h2 class="font-semibold mb-2">Daftar Barang/Jasa</h2>
    <div class="mb-2 flex gap-2">
      <button class="btn btn-primary" @click="showAdd = true">Tambah (+)</button>
      <button class="btn btn-danger" @click="clearAll" :disabled="!items.length">Clear (-)</button>
    </div>
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full border divide-y divide-gray-200 rounded-lg">
        <thead class="bg-gray-50 border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nama Barang</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Qty</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Satuan</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Harga</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50">
            <td class="px-6 py-4">{{ item.nama }}</td>
            <td class="px-6 py-4">{{ item.qty }}</td>
            <td class="px-6 py-4">{{ item.satuan }}</td>
            <td class="px-6 py-4">{{ formatRupiah(item.harga) }}</td>
            <td class="px-6 py-4 text-center">
              <button class="btn btn-xs btn-danger" @click="removeItem(idx)">Hapus</button>
            </td>
          </tr>
          <tr v-if="!items.length">
            <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-500">Belum ada barang</td>
          </tr>
        </tbody>
      </table>
    </div>
    <TambahBarangModal :show="showAdd" @submit="addItem" @close="showAdd = false" />
    <div class="flex gap-4 items-center mb-2 mt-4">
      <label><input type="checkbox" v-model="diskonAktif" /> Diskon</label>
      <input v-if="diskonAktif" type="number" min="0" v-model.number="diskon" placeholder="Nominal Diskon (Rp)" class="input w-32" />
      <label><input type="checkbox" v-model="ppnAktif" /> PPN (11%)</label>
      <label><input type="checkbox" v-model="pphAktif" /> PPH</label>
      <div v-if="pphAktif">
        <select v-model="pphKode" class="input">
          <option value="">Pilih PPH</option>
          <option v-for="p in pphList" :key="p.kode" :value="p.kode">{{ p.nama }} ({{ (p.tarif*100).toFixed(2) }}%)</option>
        </select>
        <button class="btn btn-xs btn-primary" @click="showAddPph = true">Tambah PPH</button>
      </div>
    </div>
    <TambahPphModal :show="showAddPph" @submit="addPph" @close="showAddPph = false" />
    <!-- Summary -->
    <div class="mt-4 p-2 border rounded bg-gray-50">
      <div>Subtotal: <b>{{ formatRupiah(subtotal) }}</b></div>
      <div v-if="diskonAktif">Diskon: <b>-{{ formatRupiah(diskon) }}</b></div>
      <div>DPP: <b>{{ formatRupiah(dpp) }}</b></div>
      <div v-if="ppnAktif">PPN (11%): <b>{{ formatRupiah(ppnNominal) }}</b></div>
      <div v-if="pphAktif && pph">PPH ({{ (pph.tarif*100).toFixed(2) }}%): <b>{{ formatRupiah(pphNominal) }}</b></div>
      <div class="text-lg mt-2">Grand Total: <b>{{ formatRupiah(grandTotal) }}</b></div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch } from 'vue';
import TambahBarangModal from './TambahBarangModal.vue';
import TambahPphModal from './TambahPphModal.vue';
const props = defineProps<{ items: any[]; diskon: number; ppn: boolean; pph: any[]; pphList: any[]; nominal?: number }>();
const emit = defineEmits(['update:items', 'update:diskon', 'update:ppn', 'update:pph', 'add-pph']);

const items = ref<any[]>(props.items || []);
const diskon = ref<number>(props.diskon || 0);
const diskonAktif = ref(diskon.value > 0);
const ppnAktif = ref(props.ppn || false);
const pphAktif = ref(props.pph && props.pph.length > 0);
const pphKode = ref(props.pph && props.pph[0]?.kode || '');
const showAdd = ref(false);
const showAddPph = ref(false);

watch(items, val => emit('update:items', val));
watch(diskon, val => emit('update:diskon', val));
watch(ppnAktif, val => emit('update:ppn', val));
watch(pphKode, val => {
  if (val) emit('update:pph', [pph.value]);
  else emit('update:pph', []);
});

const subtotal = computed(() => typeof props.nominal === 'number' && !isNaN(props.nominal) && props.nominal > 0 ? props.nominal : items.value.reduce((sum, i) => sum + (i.qty * i.harga), 0));
const dpp = computed(() => Math.max(subtotal.value - (diskonAktif.value ? diskon.value : 0), 0));
const ppnNominal = computed(() => ppnAktif.value ? dpp.value * 0.11 : 0);
const pph = computed(() => props.pphList.find(p => p.kode === pphKode.value));
const pphNominal = computed(() => (pphAktif.value && pph.value) ? dpp.value * pph.value.tarif : 0);
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
  emit('add-pph', pphBaru);
  pphKode.value = pphBaru.kode;
  showAddPph.value = false;
}
function formatRupiah(val: number) {
  return val.toLocaleString('id-ID', { style: 'currency', currency: 'IDR' });
}
</script>

<style scoped>
.floating-input {
  position: relative;
}

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

/* When input is focused or has value - label goes to border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Special handling for select - check if it has selected value */
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Make sure the label background covers the border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}
</style>
