<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <form @submit.prevent="onSubmit" novalidate class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Row: No. PO Anggaran & Departemen -->
        <div>
          <CustomSelect
            :model-value="form.po_anggaran_id ?? ''"
            @update:modelValue="(val) => { form.po_anggaran_id = val; onPoChange(); }"
            :options="(Array.isArray(poOptions) ? poOptions : []).map((opt: any) => ({ label: opt.no_po_anggaran, value: String(opt.id) }))"
            placeholder="Pilih PO Anggaran"
          >
            <template #label> No. PO Anggaran<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>
        <div>
          <CustomSelect
            :model-value="form.department_id ?? ''"
            @update:modelValue="(val) => (form.department_id = val as any)"
            :options="(Array.isArray(departments) ? departments : []).map((d: any) => ({ label: d.name ?? d.nama_department, value: String(d.id) }))"
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
            :options="(Array.isArray(banks) ? banks : []).map((b: any) => ({ label: b.nama_bank, value: String(b.id) }))"
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
      <RealisasiPengeluaranGrid
        v-model:items="form.items"
        :total-anggaran="form.total_anggaran"
      />

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

  </div>
</template>

<script setup lang="ts">
import { reactive, ref, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import CustomSelect from '@/components/ui/CustomSelect.vue';
import { parseCurrency, formatCurrency } from '@/lib/currencyUtils';
import RealisasiPengeluaranGrid from '@/components/realisasi/RealisasiPengeluaranGrid.vue';

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

async function loadBanks() {
  try {
    const { data } = await axios.get('/banks');
    banks.value = Array.isArray(data?.data)
      ? data.data
      : (Array.isArray(data) ? data : []);
  } catch {
    banks.value = [];
  }
}

async function loadPoOptions() {
  try {
    const { data } = await axios.get('/realisasi/po-anggaran/options');
    poOptions.value = Array.isArray(data?.data)
      ? data.data
      : (Array.isArray(data) ? data : []);
  } catch {
    poOptions.value = [];
  }
}

loadBanks();
loadPoOptions();

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
