<template>
    <!-- Card utama: form + info PO Anggaran -->
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
              <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
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
              <div v-if="errors.metode_pembayaran" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
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
              <div v-if="errors.rekening" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
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
              <div v-if="errors.po_anggaran_id" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
            </div>

            <!-- Catatan / Note Realisasi -->
            <div class="floating-input">
              <textarea
                id="note"
                v-model="form.note"
                class="floating-input-field resize-y min-h-[96px]"
                placeholder=" "
              />
              <label for="note" class="floating-label">Catatan</label>
            </div>
          </div>
        </div>
        </form>

        <!-- Right column: PO Anggaran info -->
        <div class="realisasi-form-right">
          <PurchaseOrderAnggaranInfo :po-anggaran="selectedPoAnggaran" />
        </div>
      </div>

    <!-- Card terpisah: Detail Pengeluaran (grid) -->
    <div class="bg-white rounded-lg shadow-sm p-6 mt-6">
      <RealisasiPengeluaranGrid
        v-model:items="form.items"
        :total-anggaran="form.total_anggaran"
      />
    </div>
</template>

<script setup lang="ts">
import { reactive, ref, watch, computed, onMounted } from 'vue';
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
const emit = defineEmits<{
  (e: 'save-draft', payload: { form: any }): void;
  (e: 'send', payload: { form: any }): void;
}>();

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

const errors = ref<Record<string, string>>({});

// Auto-select department if only one is available and none selected yet
if (!form.department_id && Array.isArray(departments.value) && departments.value.length === 1) {
  const only = departments.value[0];
  if (only && only.id) {
    form.department_id = String(only.id);
  }
}

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
    return (bisnisPartners.value || []).map((bp: any) => {
      const namaBp = bp?.nama_bp || '-';

      return {
        label: namaBp,
        value: String(bp.id),
      };
    });
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
      if (!alreadyInList) {
        const currentPo = props.realisasi?.po_anggaran ?? props.realisasi?.poAnggaran;
        if (currentPo) {
          list.push(currentPo);
        }
      }
    }

    poOptions.value = list;
  } catch {
    poOptions.value = [];
  }
}

function ensureInitialRekeningOptions() {
  if (!form.department_id) {
    bisnisPartners.value = [];
    creditCards.value = [];
    return;
  }
  if (form.metode_pembayaran === 'Transfer') {
    loadBisnisPartners();
  } else {
    loadCreditCards();
  }
}

loadBanks();
loadPoOptions();
ensureInitialRekeningOptions();

onMounted(() => {
  // Di mode edit, pastikan info PO Anggaran yang ditampilkan selalu lengkap
  // dengan mengambil ulang detail PO melalui endpoint yang sama seperti di create.
  if (props.mode === 'edit' && form.po_anggaran_id) {
    onPoChange();
  }
});

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

function saveDraft() {
  emit('save-draft', { form: { ...form } });
}

async function onPoChange() {
  if (!form.po_anggaran_id) return;
  try {
    const params: any = { only_outstanding: 1 };
    if (props.mode === 'edit' && props.realisasi?.id) {
      params.exclude_realisasi_id = props.realisasi.id;
    }

    const { data } = await axios.get(`/realisasi/po-anggaran/${form.po_anggaran_id}`, {
      params,
    });
    // Prefill items
    const items = (data?.items || []).map((it: any) => {
      const harga = Number(it.harga) || 0;
      const qty = Number(it.qty) || 0;
      const subtotal = harga * qty;
      // Gunakan nilai outstanding/sisa per item bila tersedia, jika tidak fallback ke subtotal
      const nominalPerItem =
        it.outstanding_per_item ??
        it.outstanding ??
        it.sisa ??
        subtotal;
      return {
        po_anggaran_item_id: it.id ?? null,
        jenis_pengeluaran_id: it.jenis_pengeluaran_id ?? null,
        jenis_pengeluaran_text: it.jenis_pengeluaran_text ?? '',
        keterangan: it.keterangan ?? '',
        harga,
        qty,
        satuan: it.satuan ?? '',
        subtotal,
        realisasi: Number(nominalPerItem) || 0,
      };
    });
    // Di mode edit, saat pertama kali load dan PO Anggaran belum diganti,
    // biarkan items dari Realisasi (yang sudah menyimpan keterangan realisasi) tetap dipakai.
    const isInitialEditWithSamePo =
      props.mode === 'edit' &&
      !!props.realisasi?.id &&
      String(props.realisasi?.po_anggaran_id ?? '') === String(form.po_anggaran_id ?? '') &&
      Array.isArray(props.realisasi?.items) &&
      (props.realisasi.items as any[]).length > 0;

    if (!isInitialEditWithSamePo && items.length) {
      form.items = items;
    }
    // Set total_anggaran based on outstanding budget (fallback to nominal)
    form.total_anggaran = Number((data as any)?.outstanding ?? (data as any)?.nominal ?? 0);
    selectedPoAnggaran.value = data;
  } catch (error) {
    console.error('Error loading PO Anggaran:', error);
  }
}

function onSubmit() { saveDraft(); }

// Berikan akses ke snapshot form saat ini untuk parent (Create/Edit)
function getFormSnapshot() {
  return { ...form };
}

defineExpose({ getFormSnapshot });

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
