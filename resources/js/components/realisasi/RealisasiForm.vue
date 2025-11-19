<template>
    <!-- Card utama: form + info PO Anggaran -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <div class="realisasi-form-container">
        <form @submit.prevent="onSubmit" novalidate class="space-y-4 realisasi-form-left">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="lg:col-span-2 space-y-4">
            <!-- No Realisasi -->
            <div class="floating-input">
              <input
                type="text"
                v-model="form.no_realisasi"
                id="no_realisasi"
                class="floating-input-field"
                placeholder=" "
                readonly
              />
              <label for="no_realisasi" class="floating-label">No. Realisasi</label>
            </div>

            <!-- Tanggal -->
            <div class="floating-input">
              <input
                type="text"
                :value="tanggalDisplay"
                id="tanggal"
                class="floating-input-field"
                placeholder=" "
                readonly
              />
              <label for="tanggal" class="floating-label">Tanggal</label>
            </div>

            <!-- Departemen -->
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

            <!-- Metode Pembayaran -->
            <div>
              <CustomSelect
                :model-value="form.metode_pembayaran ?? ''"
                @update:modelValue="onMetodeChange"
                :options="[
                  { label: 'Transfer', value: 'Transfer' },
                  { label: 'Kredit', value: 'Kredit' },
                ]"
                placeholder="Pilih Metode"
              >
                <template #label> Metode Pembayaran<span class="text-red-500">*</span> </template>
              </CustomSelect>
            </div>

            <!-- Bisnis Partner / Nama Kredit -->
            <div>
              <CustomSelect
                :model-value="selectedRekeningId ?? ''"
                @update:modelValue="onRekeningChange"
                :options="rekeningOptions"
                :disabled="!form.department_id"
                placeholder="Pilih Rekening"
              >
                <template #label>
                  {{ form.metode_pembayaran === 'Kredit' ? 'Akun Kredit' : 'Bisnis Partner' }}<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
            </div>

            <!-- PO Anggaran -->
            <div>
              <CustomSelect
                :model-value="form.po_anggaran_id ?? ''"
                @update:modelValue="(val) => { form.po_anggaran_id = val; onPoChange(); }"
                :options="(Array.isArray(poOptions) ? poOptions : []).map((opt: any) => ({ label: opt.no_po_anggaran, value: String(opt.id) }))"
                placeholder="Pilih PO Anggaran"
              >
                <template #label> PO Anggaran<span class="text-red-500">*</span> </template>
              </CustomSelect>
            </div>
          </div>
        </div>
        </form>

        <!-- Right column: PO Anggaran info -->
        <div class="realisasi-form-right">
          <PurchaseOrderAnggaranInfo :po-anggaran="selectedPoAnggaran" />
        </div>
      </div>
    </div>

    <!-- Card terpisah: Detail Pengeluaran (grid) -->
    <div class="bg-white rounded-lg shadow-sm p-6">
      <RealisasiPengeluaranGrid
        v-model:items="form.items"
        :total-anggaran="form.total_anggaran"
      />
    </div>
    <!-- Action Buttons -->
      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
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
          class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="goBack"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Batal
        </button>
      </div>
</template>

<script setup lang="ts">
import { reactive, ref, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import CustomSelect from '@/components/ui/CustomSelect.vue';
import RealisasiPengeluaranGrid from '@/components/realisasi/RealisasiPengeluaranGrid.vue';
import PurchaseOrderAnggaranInfo from '@/components/PurchaseOrderAnggaranInfo.vue';

function getLocalDateString() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const props = defineProps<{ mode: 'create'|'edit'; realisasi?: any; departments?: any[] }>();

const departments = ref<any[]>(props.departments || []);
const form = reactive<any>({
  no_realisasi: props.realisasi?.no_realisasi ?? '',
  tanggal: props.realisasi?.tanggal ?? '',
  po_anggaran_id: props.realisasi?.po_anggaran_id ?? '',
  department_id: props.realisasi?.department_id ?? '',
  metode_pembayaran: props.realisasi?.metode_pembayaran ?? 'Transfer',
  bisnis_partner_id: props.realisasi?.bisnis_partner_id ?? null,
  credit_card_id: props.realisasi?.credit_card_id ?? null,
  bank_id: props.realisasi?.bank_id ?? null,
  nama_rekening: props.realisasi?.nama_rekening ?? '',
  no_rekening: props.realisasi?.no_rekening ?? '',
  total_anggaran: props.realisasi?.total_anggaran ?? 0,
  note: props.realisasi?.note ?? '',
  items: props.realisasi?.items ?? [],
});

if (!form.tanggal) {
  form.tanggal = getLocalDateString();
}

const tanggalDisplay = computed(() => {
  try {
    return new Date(form.tanggal || new Date().toISOString().slice(0, 10)).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return '';
  }
});

const banks = ref<any[]>([]);
const poOptions = ref<any[]>([]);
const bisnisPartners = ref<any[]>([]);
const creditCards = ref<any[]>([]);
// Preselect rekening (bisnis partner / kredit) saat edit
const initialRekeningId = props.realisasi?.bisnis_partner_id
  ?? props.realisasi?.credit_card_id
  ?? undefined;
const selectedRekeningId = ref<string | number | undefined>(
  initialRekeningId ? String(initialRekeningId) : undefined
);
const rekeningOptions = computed(() => {
  if (form.metode_pembayaran === 'Transfer') {
    return (bisnisPartners.value || []).map((bp: any) => ({
      label: bp?.nama_rekening || bp?.nama_bp || '-',
      value: String(bp.id),
    }));
  }
  return (creditCards.value || []).map((cc: any) => ({
    label: `${cc?.nama_pemilik ?? '-'} - ${cc?.no_kartu_kredit ?? ''}`,
    value: String(cc.id),
  }));
});
const selectedPoAnggaran = ref<any | null>(
  props.realisasi?.po_anggaran
  ?? props.realisasi?.poAnggaran
  ?? null
);

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
    const params: any = {};
    if (form.department_id) params.department_id = form.department_id;
    if (form.metode_pembayaran) params.metode_pembayaran = form.metode_pembayaran;
    if (form.metode_pembayaran === 'Transfer' && selectedRekeningId.value) {
      params.bisnis_partner_id = selectedRekeningId.value;
    }
    if (form.metode_pembayaran === 'Kredit' && form.bank_id) {
      params.bank_id = form.bank_id;
    }

    const { data } = await axios.get('/realisasi/po-anggaran/options', { params });
    const list = Array.isArray(data?.data)
      ? data.data
      : (Array.isArray(data) ? data : []);

    // Saat edit, pastikan PO yang sedang dipakai tetap ada di options
    if (form.po_anggaran_id) {
      const currentId = String(form.po_anggaran_id);
      const alreadyInList = (list || []).some((x: any) => String(x.id) === currentId);
      if (!alreadyInList && props.realisasi?.poAnggaran) {
        list.push(props.realisasi.poAnggaran);
      }
    }

    poOptions.value = list;
  } catch {
    poOptions.value = [];
  }
}

loadBanks();
loadPoOptions();

watch(
  () => [form.department_id, form.metode_pembayaran, selectedRekeningId.value],
  () => {
    loadPoOptions();
  }
);

async function loadBisnisPartners() {
  if (!form.department_id) { bisnisPartners.value = []; return; }
  try {
    const { data } = await axios.get('/bisnis-partners/options', { params: { department_id: form.department_id } });
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    bisnisPartners.value = list;
  } catch {
    bisnisPartners.value = [];
  }
}

async function loadCreditCards() {
  if (!form.department_id) { creditCards.value = []; return; }
  try {
    const { data } = await axios.get('/credit-cards', { params: { department_id: form.department_id } });
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    creditCards.value = list;
  } catch {
    creditCards.value = [];
  }
}

function clearRekeningFields() {
  selectedRekeningId.value = undefined;
  form.bisnis_partner_id = null;
  form.credit_card_id = null;
  form.no_rekening = '';
  form.nama_rekening = '';
  form.bank_id = null;
}

function onMetodeChange(val: any) {
  form.metode_pembayaran = val as string;
  clearRekeningFields();
  if (form.metode_pembayaran === 'Transfer') {
    loadBisnisPartners();
  } else {
    loadCreditCards();
  }
}

function onRekeningChange(val: any) {
  selectedRekeningId.value = val as any;
  const list = form.metode_pembayaran === 'Transfer' ? bisnisPartners.value : creditCards.value;
  const found = (list || []).find((x: any) => String(x.id) === String(val));
  if (!found) {
    clearRekeningFields();
    return;
  }
  if (form.metode_pembayaran === 'Transfer') {
    form.bisnis_partner_id = found.id ?? null;
    form.credit_card_id = null;
    form.no_rekening = found?.no_rekening_va ?? '';
    form.nama_rekening = found?.nama_rekening || found?.nama_bp || '';
    form.bank_id = found?.bank_id ?? null;
  } else {
    form.credit_card_id = found.id ?? null;
    form.bisnis_partner_id = null;
    form.no_rekening = found?.no_kartu_kredit ?? '';
    form.nama_rekening = `${found?.nama_pemilik ?? ''} - ${form.no_rekening}`;
    form.bank_id = found?.bank_id ?? null;
  }
}

watch(
  () => form.department_id,
  () => {
    clearRekeningFields();
    if (form.metode_pembayaran === 'Transfer') {
      loadBisnisPartners();
    } else {
      loadCreditCards();
    }
  }
);

function goBack() { history.back(); }

function saveDraft() {
  if (props.mode === 'create') router.post('/realisasi', { ...form, submit_type: 'draft' });
  else router.put(`/realisasi/${props.realisasi.id}`, { ...form });
}

function send() {
  if (props.mode === 'create') {
    router.post('/realisasi', { ...form, submit_type: 'send' });
  } else {
    router.put(`/realisasi/${props.realisasi.id}`, { ...form }, {
      onSuccess: () => router.post('/realisasi/send', { ids: [ props.realisasi.id ] })
    });
  }
}

async function onPoChange() {
  if (!form.po_anggaran_id) return;
  try {
    const { data } = await axios.get(`/realisasi/po-anggaran/${form.po_anggaran_id}`, {
      params: { only_outstanding: 1 },
    });
    // Prefill items
    const items = (data?.items || []).map((it: any) => ({
      po_anggaran_item_id: it.id ?? null,
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
    // Set total_anggaran based on outstanding budget (fallback to nominal)
    form.total_anggaran = Number((data as any)?.outstanding ?? (data as any)?.nominal ?? 0);
    selectedPoAnggaran.value = data;
  } catch (error) {
    console.error('Error loading PO Anggaran:', error);
  }
}

function onSubmit() { saveDraft(); }

// (Total anggaran manual input telah dihapus dari form; bila diperlukan lagi, gunakan util parseCurrency/formatCurrency di sini.)
</script>

<style scoped>
.realisasi-form-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.realisasi-form-left {
  width: 100%;
}

.realisasi-form-right {
  width: 100%;
}

@media (max-width: 1024px) {
  .realisasi-form-container {
    grid-template-columns: 1fr;
  }
}

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
