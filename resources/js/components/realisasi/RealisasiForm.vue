<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <form @submit.prevent="onSubmit" novalidate class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Row: No. PO Anggaran & Departemen -->
        <div>
          <CustomSelect
            :model-value="form.po_anggaran_id ?? ''"
            @update:modelValue="(val) => { form.po_anggaran_id = val; onPoChange(); }"
            :options="poOptions.map((opt: any) => ({ label: opt.no_po_anggaran, value: String(opt.id) }))"
            placeholder="Pilih PO Anggaran"
          >
            <template #label> No. PO Anggaran<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>
        <div>
          <CustomSelect
            :model-value="form.department_id ?? ''"
            @update:modelValue="(val) => (form.department_id = val as any)"
            :options="departments.map((d: any) => ({ label: d.name ?? d.nama_department, value: String(d.id) }))"
            placeholder="Pilih Departemen"
          >
            <template #label> Departemen<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Row: Metode Pembayaran & Nama Bank -->
        <div>
          <CustomSelect
            :model-value="form.metode_pembayaran ?? ''"
            @update:modelValue="(val) => (form.metode_pembayaran = val as string)"
            :options="[{ label: 'Transfer', value: 'Transfer' }]"
            placeholder="Pilih Metode"
          >
            <template #label> Metode Pembayaran<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>
        <div>
          <CustomSelect
            :model-value="form.bank_id ? String(form.bank_id) : ''"
            @update:modelValue="(val) => (form.bank_id = val ? Number(val) : null)"
            :options="banks.map((b: any) => ({ label: b.nama_bank, value: String(b.id) }))"
            placeholder="Pilih Bank"
          >
            <template #label> Nama Bank<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Row: Nama Rekening & No. Rekening -->
        <div class="floating-input">
          <input
            type="text"
            v-model="form.nama_rekening"
            id="nama_rekening"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="nama_rekening" class="floating-label">Nama Rekening<span class="text-red-500">*</span></label>
        </div>
        <div class="floating-input">
          <input
            type="text"
            v-model="form.no_rekening"
            id="no_rekening"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="no_rekening" class="floating-label">No. Rekening/VA<span class="text-red-500">*</span></label>
        </div>

        <!-- Row: Total Anggaran & Note -->
        <div class="floating-input">
          <input
            type="text"
            :value="displayTotalAnggaran"
            id="total_anggaran"
            class="floating-input-field"
            placeholder=" "
            @keydown="allowNumericKeydown"
            @input="onTotalAnggaranInput"
          />
          <label for="total_anggaran" class="floating-label">Total Anggaran<span class="text-red-500">*</span></label>
        </div>
        <div class="floating-input">
          <textarea
            v-model="form.note"
            id="note"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label for="note" class="floating-label">Note</label>
        </div>
      </div>

      <!-- Detail Pengeluaran Section -->
      <div class="mt-6 pt-6 border-t border-gray-200">
        <div class="flex items-center justify-between mb-4">
          <h3 class="text-lg font-semibold text-gray-900">Detail Pengeluaran</h3>
          <div class="flex gap-2">
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              @click="clearItems"
            >
              Clear (-)
            </button>
            <button
              type="button"
              class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              @click="showAdd = true"
            >
              Tambah (+)
            </button>
          </div>
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
              <tr v-for="(it, idx) in form.items" :key="idx" class="hover:bg-gray-50">
                <td class="px-4 py-3 text-sm text-gray-900">{{ it.jenis_pengeluaran_text }}</td>
                <td class="px-4 py-3 text-sm text-gray-900">{{ it.keterangan }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(it.harga) }}</td>
                <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ it.qty }}</td>
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
              <tr v-if="!form.items || form.items.length === 0">
                <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
                  Belum ada data detail pengeluaran
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex flex-wrap gap-6 justify-end bg-gray-50 p-4 rounded-lg">
          <div class="flex flex-col">
            <span class="text-xs text-gray-500 mb-1">Total Realisasi</span>
            <span class="text-lg font-semibold text-gray-900">{{ formatCurrency(totalRealisasi) }}</span>
          </div>
          <div class="flex flex-col">
            <span class="text-xs text-gray-500 mb-1">Sisa</span>
            <span class="text-lg font-semibold" :class="sisa >= 0 ? 'text-green-600' : 'text-red-600'">{{ formatCurrency(sisa) }}</span>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="goBack"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Batal
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="saveDraft"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
          </svg>
          Simpan Draft
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="send"
        >
          <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
          </svg>
          Kirim
        </button>
      </div>
    </form>

    <!-- Modal for adding new item -->
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
  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import CustomSelect from '@/components/ui/CustomSelect.vue';
import { parseCurrency, formatCurrency } from '@/lib/currencyUtils';

const props = defineProps<{ mode: 'create'|'edit'; realisasi?: any; departments?: any[] }>();

const departments = ref<any[]>(props.departments || []);
const form = reactive<any>({
  po_anggaran_id: props.realisasi?.po_anggaran_id ?? '',
  department_id: props.realisasi?.department_id ?? '',
  metode_pembayaran: props.realisasi?.metode_pembayaran ?? 'Transfer',
  bank_id: props.realisasi?.bank_id ?? null,
  nama_rekening: props.realisasi?.nama_rekening ?? '',
  no_rekening: props.realisasi?.no_rekening ?? '',
  total_anggaran: props.realisasi?.total_anggaran ?? 0,
  note: props.realisasi?.note ?? '',
  items: props.realisasi?.items ?? [],
});

const banks = ref<any[]>([]);
const poOptions = ref<any[]>([]);
const showAdd = ref(false);
const newItem = reactive<any>({
  jenis_pengeluaran_id: null,
  jenis_pengeluaran_text: '',
  keterangan: '',
  harga: 0,
  qty: 0,
  satuan: '',
  realisasi: 0
});

async function loadBanks() {
  try {
    const { data } = await axios.get('/banks');
    banks.value = data?.data ?? data ?? [];
  } catch {
    banks.value = [];
  }
}

async function loadPoOptions() {
  try {
    const { data } = await axios.get('/realisasi/po-anggaran/options');
    poOptions.value = data ?? [];
  } catch {
    poOptions.value = [];
  }
}

loadBanks();
loadPoOptions();

const totalRealisasi = computed(() => (form.items || []).reduce((a: number, it: any) => a + (Number(it.realisasi)||0), 0));
const sisa = computed(() => Number(form.total_anggaran || 0) - Number(totalRealisasi.value || 0));

function goBack() { history.back(); }

function saveDraft() {
  if (props.mode === 'create') router.post('/realisasi', { ...form });
  else router.put(`/realisasi/${props.realisasi.id}`, { ...form });
}

function send() {
  if (props.mode === 'create') {
    router.post('/realisasi', { ...form });
  } else {
    router.put(`/realisasi/${props.realisasi.id}`, { ...form }, {
      onSuccess: () => router.post('/realisasi/send', { ids: [ props.realisasi.id ] })
    });
  }
}

function clearItems() { form.items = []; }

function addItem() {
  form.items.push({ ...newItem });
  showAdd.value = false;
  // Reset newItem
  Object.assign(newItem, {
    jenis_pengeluaran_id: null,
    jenis_pengeluaran_text: '',
    keterangan: '',
    harga: 0,
    qty: 0,
    satuan: '',
    realisasi: 0
  });
}

async function onPoChange() {
  if (!form.po_anggaran_id) return;
  try {
    const { data } = await axios.get(`/realisasi/po-anggaran/${form.po_anggaran_id}`);
    // Prefill fields from PO Anggaran
    form.department_id = data?.department_id ?? form.department_id;
    form.bank_id = data?.bank_id ?? form.bank_id;
    form.nama_rekening = data?.nama_rekening ?? form.nama_rekening;
    form.no_rekening = data?.no_rekening ?? form.no_rekening;
    form.total_anggaran = data?.nominal ?? form.total_anggaran;
    // Prefill items
    const items = (data?.items || []).map((it: any) => ({
      jenis_pengeluaran_id: it.jenis_pengeluaran_id ?? null,
      jenis_pengeluaran_text: it.jenis_pengeluaran_text ?? '',
      keterangan: it.keterangan ?? '',
      harga: it.harga ?? 0,
      qty: it.qty ?? 0,
      satuan: it.satuan ?? '',
      subtotal: (Number(it.harga)||0) * (Number(it.qty)||0),
      realisasi: 0,
    }));
    if (items.length) form.items = items;
  } catch (error) {
    console.error('Error loading PO Anggaran:', error);
  }
}

function onSubmit() { saveDraft(); }

// Numeric formatting for total_anggaran
const displayTotalAnggaran = computed(() => formatCurrency(form.total_anggaran ?? ''));
function onTotalAnggaranInput(e: Event) {
  const input = e.target as HTMLInputElement;
  const parsed = parseCurrency(input.value);
  form.total_anggaran = parsed === '' ? null : Number(parsed);
}

// Numeric formatting for realisasi in table
function onRealisasiInput(e: Event, idx: number) {
  const input = e.target as HTMLInputElement;
  const parsed = parseCurrency(input.value);
  form.items[idx].realisasi = parsed === '' ? 0 : Number(parsed);
}

// Numeric formatting for new item
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
