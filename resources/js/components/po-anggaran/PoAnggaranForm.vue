<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <form @submit.prevent="$emit('submit')" novalidate class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Row 1: No Anggaran & Nominal -->
        <div class="floating-input">
          <input
            type="text"
            v-model="form.no_anggaran"
            id="no_anggaran"
            class="floating-input-field"
            placeholder=" "
            readonly
          />
          <label for="no_anggaran" class="floating-label">No. Anggaran</label>
        </div>
        <div class="floating-input">
          <input
            type="text"
            :value="displayNominal"
            id="nominal"
            class="floating-input-field"
            placeholder=" "
            readonly
          />
          <label for="nominal" class="floating-label">Nominal<span class="text-red-500">*</span></label>
        </div>

        <!-- Row 2: Tanggal & Note -->
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
        <div class="floating-input row-span-3">
          <textarea v-model="form.note" id="note" class="floating-input-field resize-none h-full" placeholder=" " rows="8"></textarea>
          <label for="note" class="floating-label">Note</label>
        </div>

        <!-- Row 3: Departemen (left column only, note spans right) -->
        <div>
          <CustomSelect
            :model-value="form.department_id ?? ''"
            @update:modelValue="(val) => (form.department_id = val as any)"
            :options="(departments || []).map((d: any) => ({ label: d.name ?? d.nama_department, value: String(d.id) }))"
            :disabled="(departments || []).length === 1"
            placeholder="Pilih Departemen"
          >
            <template #label> Departemen<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Row 3.1: Perihal (under Departemen) -->
        <div>
          <CustomSelect
            :model-value="form.perihal_id ?? ''"
            @update:modelValue="(val) => (form.perihal_id = val as any)"
            :options="perihalOptions"
            placeholder="Pilih Perihal"
          >
            <template #label> Perihal<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Row 4: Metode Pembayaran (left column only) -->
        <div>
          <CustomSelect
            :model-value="form.metode_pembayaran ?? ''"
            @update:modelValue="(val) => (form.metode_pembayaran = val as string)"
            :options="[
              { label: 'Transfer', value: 'Transfer' },
              { label: 'Kredit', value: 'Kredit' },
            ]"
            placeholder="Pilih Metode"
          >
            <template #label> Metode Pembayaran<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Rekening Section: stacked vertically in left column (half width) -->
        <div class="space-y-6 md:col-start-1">
          <CustomSelect
            :model-value="selectedRekeningId"
            @update:modelValue="onRekeningChange"
            :options="rekeningOptions"
            :disabled="!form.department_id"
            placeholder="Pilih Nama Rekening"
          >
            <template #label> Nama Rekening<span class="text-red-500">*</span> </template>
          </CustomSelect>

          <div class="floating-input">
            <input
              type="text"
              :value="form.nama_bank || ''"
              id="nama_bank"
              class="floating-input-field"
              placeholder=" "
              readonly
            />
            <label for="nama_bank" class="floating-label">Nama Bank</label>
          </div>

          <div class="floating-input">
            <input
              type="text"
              v-model="form.no_rekening"
              id="no_rekening"
              class="floating-input-field"
              placeholder=" "
              readonly
            />
            <label for="no_rekening" class="floating-label">No. Rekening</label>
          </div>
        </div>
      </div>
    </form>
  </div>
</template>
<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import axios from 'axios';
import CustomSelect from '@/components/ui/CustomSelect.vue';
import { formatCurrency } from '@/lib/currencyUtils';

function getLocalDateString() {
  const d = new Date();
  const year = d.getFullYear();
  const month = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const props = defineProps<{ mode: 'create'|'edit'; form: any; poAnggaran?: any; departments?: any[] }>();
const emit = defineEmits<{ 'update:form': [value: any]; 'submit': [] }>();
const departments = ref<any[]>(Array.isArray(props?.departments) ? props.departments : (Array.isArray(props?.poAnggaran?.departments) ? props.poAnggaran!.departments : []));

const form = ref<any>(props.form ?? {
  department_id: props.poAnggaran?.department_id ?? '',
  metode_pembayaran: props.poAnggaran?.metode_pembayaran ?? 'Transfer',
  bank_id: props.poAnggaran?.bank_id ?? null,
  bisnis_partner_id: props.poAnggaran?.bisnis_partner_id ?? null,
  nama_rekening: props.poAnggaran?.nama_rekening ?? '',
  no_rekening: props.poAnggaran?.no_rekening ?? '',
  nama_bank: props.poAnggaran?.nama_bank ?? '',
  no_giro: props.poAnggaran?.no_giro ?? '',
  tanggal_giro: props.poAnggaran?.tanggal_giro ?? '',
  tanggal_cair: props.poAnggaran?.tanggal_cair ?? '',
  detail_keperluan: props.poAnggaran?.detail_keperluan ?? '',
  nominal: props.poAnggaran?.nominal ?? 0,
  note: props.poAnggaran?.note ?? '',
  no_anggaran: props.poAnggaran?.no_anggaran ?? '',
  tanggal: props.poAnggaran?.tanggal ?? '',
  items: props.poAnggaran?.items ?? [],
  // Common fitur for grid
  diskon: props.poAnggaran?.diskon ?? null,
  ppn: props.poAnggaran?.ppn ?? false,
  perihal_id: props.poAnggaran?.perihal_id ?? '',
});

// Sync incoming form prop and emit changes back to parent
watch(() => props.form, (v) => { if (v) form.value = v; }, { deep: true });
watch(form, (v) => emit('update:form', v), { deep: true });

// Auto-select department if only one is available and none selected yet
watch(departments, (list) => {
  if (!form.value.department_id && Array.isArray(list) && list.length === 1) {
    const only = list[0];
    if (only && only.id) {
      form.value.department_id = String(only.id);
    }
  }
}, { immediate: true });

// Set today's date by default if empty
if (!form.value.tanggal) {
  form.value.tanggal = getLocalDateString();
}

const tanggalDisplay = computed(() => {
  try {
    return new Date(form.value.tanggal || new Date().toISOString().slice(0, 10)).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    });
  } catch {
    return '';
  }
});

const banks = ref<any[]>([]);
const perihals = ref<any[]>([]);

// Data sources for rekening selection
const bisnisPartners = ref<any[]>([]);
const creditCards = ref<any[]>([]);
const selectedRekeningId = ref<string | number | undefined>(undefined);
const rekeningOptions = computed(() => {
  if (form.value.metode_pembayaran === 'Transfer') {
    return bisnisPartners.value.map((it: any) => ({
      label: `${it?.nama_rekening || it?.nama_bp || '-'}`,
      value: String(it.id),
    }));
  }
  return creditCards.value.map((it: any) => ({
    label: `${it?.nama_pemilik ?? '-'} - ${it?.no_kartu_kredit ?? ''}`,
    value: String(it.id),
  }));
});

async function loadBanks() { try { const { data } = await axios.get('/banks'); banks.value = data?.data ?? data ?? []; } catch { banks.value = []; } }
loadBanks();

const perihalOptions = computed(() => {
  const list = Array.isArray(perihals.value) ? perihals.value : [];
  const excludedNames = [
    'permintaan pembayaran ongkir',
    'permintaan pembayaran refund konsumen',
  ];
  const filtered = list.filter((p: any) => {
    const name = (p.nama || '').toLowerCase();
    return !excludedNames.includes(name);
  });
  return filtered.map((p: any) => ({ label: p.nama, value: String(p.id) }));
});

async function loadPerihals() {
  try {
    const { data } = await axios.get('/api/perihals');
    perihals.value = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
  } catch {
    perihals.value = [];
  }
}
loadPerihals();

async function loadBisnisPartners() {
  if (!form.value.department_id) { bisnisPartners.value = []; return; }
  try {
    const { data } = await axios.get('/bisnis-partners/options', { params: { department_id: form.value.department_id } });
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    bisnisPartners.value = list;
  } catch { bisnisPartners.value = []; }
}

async function loadCreditCards() {
  if (!form.value.department_id) { creditCards.value = []; return; }
  try {
    const { data } = await axios.get('/credit-cards', { params: { department_id: form.value.department_id } });
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    creditCards.value = list;
  } catch { creditCards.value = []; }
}

function clearRekeningFields() {
  selectedRekeningId.value = undefined;
  form.value.no_rekening = '';
  form.value.nama_rekening = '';
  form.value.nama_bank = '';
  form.value.bank_id = null;
  form.value.bisnis_partner_id = null;
}

function onRekeningChange(val: any) {
  selectedRekeningId.value = val as any;
  const list = form.value.metode_pembayaran === 'Transfer' ? bisnisPartners.value : creditCards.value;
  const found = list.find((x: any) => String(x.id) === String(val));
  if (!found) { clearRekeningFields(); return; }
  if (form.value.metode_pembayaran === 'Transfer') {
    form.value.no_rekening = found?.no_rekening_va ?? '';
    form.value.nama_bank = found?.bank?.nama_bank ?? '';
    form.value.nama_rekening = found?.nama_rekening || found?.nama_bp || '';
    form.value.bank_id = found?.bank_id ?? null;
    form.value.bisnis_partner_id = found?.id ?? null;
  } else {
    form.value.no_rekening = found?.no_kartu_kredit ?? '';
    form.value.nama_bank = found?.bank?.nama_bank ?? '';
    form.value.nama_rekening = `${found?.nama_pemilik ?? ''} - ${form.value.no_rekening}`;
    form.value.bank_id = found?.bank_id ?? null;
  }
}

watch(() => form.value.department_id, async () => {
  clearRekeningFields();
  if (form.value.metode_pembayaran === 'Transfer') await loadBisnisPartners();
  else await loadCreditCards();
});

watch(() => form.value.metode_pembayaran, async () => {
  clearRekeningFields();
  if (form.value.metode_pembayaran === 'Transfer') await loadBisnisPartners();
  else await loadCreditCards();
});

// Numeric formatting for nominal (thousand separators)
const displayNominal = computed(() => formatCurrency(form.value.nominal ?? ''));

// Initialize rekening options and selection for edit mode
onMounted(async () => {
  if (!form.value?.department_id && Array.isArray(departments.value) && departments.value.length === 1) {
    const only = departments.value[0];
    if (only && only.id) {
      form.value.department_id = String(only.id);
    }
  }

  if (!form.value?.department_id) return;
  if (form.value.metode_pembayaran === 'Transfer') {
    await loadBisnisPartners();
    if (form.value.bisnis_partner_id) {
      selectedRekeningId.value = String(form.value.bisnis_partner_id);
      onRekeningChange(form.value.bisnis_partner_id);
    }
  } else {
    await loadCreditCards();
    // For credit card path, we don't have a specific relation id stored; keep existing fields
  }
});
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
.floating-input-field:focus { outline: none; border-color: #1f9254; box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2); }
.floating-label {
  position: absolute; left: 0.75rem; top: 1rem; font-size: 0.875rem; line-height: 1.25rem; color: #9ca3af; transition: all 0.3s ease-in-out; pointer-events: none; transform-origin: left top; background-color: white; padding: 0 0.25rem; z-index: 1;
}
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem; font-size: 0.75rem; line-height: 1rem; color: #333333;
}
.floating-input-field:is(textarea) { padding-top: 1rem; padding-bottom: 1rem; }
</style>
