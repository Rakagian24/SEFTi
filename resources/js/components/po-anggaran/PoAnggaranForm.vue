<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <form @submit.prevent="$emit('submit')" novalidate class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- LEFT COLUMN -->
        <div class="space-y-6">
          <!-- No Anggaran -->
          <div class="floating-input">
            <input
              type="text"
              v-model="form.no_po_anggaran"
              id="no_anggaran"
              class="floating-input-field"
              placeholder=" "
              readonly
            />
            <label for="no_anggaran" class="floating-label">No. Anggaran</label>
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
              @update:modelValue="(val) => {
                form.department_id = val as any;
                if (val) emitClearError('department_id');
              }"
              :options="(departments || []).map((d: any) => ({ label: d.name ?? d.nama_department, value: String(d.id) }))"
              :disabled="(departments || []).length === 1"
              placeholder="Pilih Departemen"
              :class="{ 'border-red-500': !!fieldError('department_id') }"
            >
              <template #label> Departemen<span class="text-red-500">*</span> </template>
            </CustomSelect>
            <div v-if="fieldError('department_id')" class="text-red-500 text-xs mt-1">{{ fieldError('department_id') }}</div>
          </div>

          <!-- Perihal -->
          <div>
            <CustomSelect
              :model-value="form.perihal_id ?? ''"
              @update:modelValue="onPerihalChange"
              :options="perihalOptions"
              placeholder="Pilih Perihal"
              :class="{ 'border-red-500': !!fieldError('perihal_id') }"
            >
              <template #label> Perihal<span class="text-red-500">*</span> </template>
            </CustomSelect>
            <div v-if="fieldError('perihal_id')" class="text-red-500 text-xs mt-1">{{ fieldError('perihal_id') }}</div>
          </div>

          <!-- Nominal -->
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
            <div v-if="fieldError('nominal')" class="text-red-500 text-xs mt-1">{{ fieldError('nominal') }}</div>
          </div>
        </div>

        <!-- RIGHT COLUMN -->
        <div class="space-y-6">
          <!-- Metode Pembayaran -->
          <div>
            <CustomSelect
              :model-value="form.metode_pembayaran ?? ''"
              @update:modelValue="(val) => {
                form.metode_pembayaran = val as string;
                if (val) {
                  emitClearError('metode_pembayaran');
                  emitClearError('bisnis_partner_id');
                  emitClearError('credit_card_id');
                  emitClearError('nama_rekening');
                  emitClearError('no_rekening');
                  emitClearError('bank_id');
                }
              }"
              :options="[
                { label: 'Transfer', value: 'Transfer' },
                { label: 'Kredit', value: 'Kredit' },
              ]"
              placeholder="Pilih Metode"
              :class="{ 'border-red-500': !!fieldError('metode_pembayaran') }"
            >
              <template #label> Metode Pembayaran<span class="text-red-500">*</span> </template>
            </CustomSelect>
            <div v-if="fieldError('metode_pembayaran')" class="text-red-500 text-xs mt-1">{{ fieldError('metode_pembayaran') }}</div>
          </div>

          <!-- Nama Rekening / Penerima -->
          <div>
            <!-- Transfer dengan perihal Supplier: pilih Supplier + Rekening Supplier -->
            <template v-if="form.metode_pembayaran === 'Transfer' && isSupplierPerihal">
              <!-- Supplier -->
              <CustomSelect
                :model-value="form.supplier_id ?? ''"
                @update:modelValue="(val) => handleSupplierChange(val as string)"
                :options="(Array.isArray(suppliers) ? suppliers : []).map((s: any) => ({ label: s.nama_supplier, value: String(s.id) }))"
                :searchable="true"
                :disabled="!form.department_id"
                @search="searchSuppliers"
                placeholder="Pilih Supplier"
                :class="{ 'border-red-500': !!fieldError('supplier_id') }"
              >
                <template #label>
                  Nama Supplier<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
              <div v-if="fieldError('supplier_id')" class="text-red-500 text-xs mt-1">{{ fieldError('supplier_id') }}</div>

              <!-- Rekening Supplier -->
              <CustomSelect
                class="mt-3"
                :model-value="form.bank_supplier_account_id ?? ''"
                @update:modelValue="(val) => handleBankSupplierAccountChange(val as string)"
                :options="(Array.isArray(supplierBankAccounts) ? supplierBankAccounts : []).map((acc: any) => ({ label: acc.nama_rekening, value: String(acc.id) }))"
                :disabled="!form.supplier_id || !supplierBankAccounts.length"
                placeholder="Pilih Rekening Supplier"
                :class="{ 'border-red-500': !!fieldError('bank_supplier_account_id') }"
              >
                <template #label>
                  Nama Rekening Supplier<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
              <div v-if="fieldError('bank_supplier_account_id')" class="text-red-500 text-xs mt-1">
                {{ fieldError('bank_supplier_account_id') }}
              </div>
            </template>

            <!-- Perihal lain: gunakan Bisnis Partner / Kartu Kredit seperti sebelumnya -->
            <template v-else>
              <CustomSelect
                :model-value="selectedRekeningId"
                @update:modelValue="onRekeningChange"
                :options="rekeningOptions"
                :disabled="!form.department_id"
                placeholder="Pilih Bisnis Partner / Kartu Kredit"
                :class="{
                  'border-red-500': !!fieldError('bisnis_partner_id') || !!fieldError('credit_card_id')
                }"
              >
                <template #label>
                  <span v-if="form.metode_pembayaran === 'Kredit'">Kartu Kredit<span class="text-red-500">*</span></span>
                  <span v-else>Bisnis Partner<span class="text-red-500">*</span></span>
                </template>
              </CustomSelect>
              <div v-if="fieldError('bisnis_partner_id')" class="text-red-500 text-xs mt-1">{{ fieldError('bisnis_partner_id') }}</div>
              <div v-else-if="fieldError('credit_card_id')" class="text-red-500 text-xs mt-1">{{ fieldError('credit_card_id') }}</div>
            </template>
          </div>

          <!-- Nama Bank -->
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
            <div v-if="fieldError('bank_id')" class="text-red-500 text-xs mt-1">{{ fieldError('bank_id') }}</div>
          </div>

          <!-- No. Rekening -->
          <div class="floating-input">
            <input
              type="text"
              :value="noRekeningDisplay"
              id="no_rekening"
              class="floating-input-field"
              placeholder=" "
              readonly
            />
            <label for="no_rekening" class="floating-label">No. Rekening</label>
            <div v-if="fieldError('no_rekening')" class="text-red-500 text-xs mt-1">{{ fieldError('no_rekening') }}</div>
          </div>

          <!-- Note -->
          <div class="floating-input">
            <textarea v-model="form.note" id="note" class="floating-input-field" placeholder=" " rows="3"></textarea>
            <label for="note" class="floating-label">Note</label>
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

const props = defineProps<{ mode: 'create'|'edit'; form: any; poAnggaran?: any; departments?: any[]; errors?: Record<string, string | string[]> }>();
const emit = defineEmits<{ 'update:form': [value: any]; 'submit': []; 'clear-error': [field: string] }>();
const departments = ref<any[]>(Array.isArray(props?.departments) ? props.departments : (Array.isArray(props?.poAnggaran?.departments) ? props.poAnggaran!.departments : []));

const form = ref<any>(props.form ?? {
  department_id: props.poAnggaran?.department_id ?? '',
  metode_pembayaran: props.poAnggaran?.metode_pembayaran ?? 'Transfer',
  bank_id: props.poAnggaran?.bank_id ?? null,
  supplier_id: props.poAnggaran?.supplier_id ?? null,
  bank_supplier_account_id: props.poAnggaran?.bank_supplier_account_id ?? null,
  bisnis_partner_id: props.poAnggaran?.bisnis_partner_id ?? null,
  credit_card_id: props.poAnggaran?.credit_card_id ?? null,
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
  no_po_anggaran: props.poAnggaran?.no_po_anggaran ?? '',
  tanggal: props.poAnggaran?.tanggal ?? '',
  items: props.poAnggaran?.items ?? [],
  diskon: props.poAnggaran?.diskon ?? null,
  ppn: props.poAnggaran?.ppn ?? false,
  perihal_id: props.poAnggaran?.perihal_id ?? '',
});

watch(() => props.form, (v) => { if (v) form.value = v; }, { deep: true });
watch(form, (v) => emit('update:form', v), { deep: true });

const incomingErrors = computed(() => props.errors ?? {});

function emitClearError(field: string) {
  emit('clear-error', field);
}

function fieldError(field: string): string {
  const err = (incomingErrors.value as Record<string, string | string[] | undefined>)[field];
  if (Array.isArray(err)) return err.length ? String(err[0]) : '';
  return typeof err === 'string' ? err : '';
}

watch(departments, (list) => {
  if (!form.value.department_id && Array.isArray(list) && list.length === 1) {
    const only = list[0];
    if (only && only.id) {
      form.value.department_id = String(only.id);
    }
  }
}, { immediate: true });

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
const bisnisPartners = ref<any[]>([]);
const creditCards = ref<any[]>([]);
const selectedRekeningId = ref<string | number | undefined>(undefined);
const suppliers = ref<any[]>([]);
const supplierBankAccounts = ref<any[]>([]);

const rekeningOptions = computed(() => {
  if (form.value.metode_pembayaran === 'Transfer') {
    // Untuk perihal Supplier, rekening diambil dari supplierBankAccounts melalui select khusus
    if (isSupplierPerihal.value) {
      return [];
    }
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


async function loadSuppliers(search?: string) {
  try {
    const departmentId = form.value.department_id ? String(form.value.department_id) : undefined;
    const { data } = await axios.get('/memo-pembayaran/suppliers/options', {
      params: {
        search: search || '',
        per_page: 200,
        department_id: departmentId,
      },
      withCredentials: true,
    });
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    suppliers.value = list;
  } catch {
    suppliers.value = [];
  }
}

async function loadSupplierBankAccounts(supplierId: string) {
  if (!supplierId) {
    supplierBankAccounts.value = [];
    return;
  }
  try {
    const response = await axios.post('/purchase-orders/supplier-bank-accounts', {
      supplier_id: supplierId,
    });
    const { bank_accounts } = response.data || {};
    supplierBankAccounts.value = Array.isArray(bank_accounts) ? bank_accounts : [];

    // Jika hanya ada satu rekening dan belum ada yang dipilih, auto-pilih
    if (supplierBankAccounts.value.length === 1 && !form.value.bank_supplier_account_id) {
      const account = supplierBankAccounts.value[0];
      form.value.bank_supplier_account_id = String(account.id);
      applySelectedBankAccount();
    }
  } catch {
    supplierBankAccounts.value = [];
  }
}

function applySelectedBankAccount() {
  const selId = form.value.bank_supplier_account_id;
  if (!selId) return;
  const acc = supplierBankAccounts.value.find((a: any) => String(a.id) === String(selId));
  if (!acc) return;

  const bankName = acc?.bank?.nama_bank || acc?.bank_name || '';
  const accountName = acc?.nama_rekening || acc?.atas_nama || acc?.owner_name || '';
  const accountNumber = acc?.no_rekening || acc?.account_number || '';

  form.value.nama_bank = bankName;
  form.value.nama_rekening = accountName;
  form.value.no_rekening = accountNumber;
  form.value.bank_id = acc?.bank_id ?? null;
}

function clearRekeningFields() {
  selectedRekeningId.value = undefined;
  form.value.no_rekening = '';
  form.value.nama_rekening = '';
  form.value.nama_bank = '';
  form.value.bank_id = null;
  form.value.bisnis_partner_id = null;
  form.value.credit_card_id = null;
}

function resetPengeluaranGrid() {
  if (Array.isArray(form.value.items) && form.value.items.length) {
    form.value.items = [];
  }
  form.value.diskon = null;
  form.value.ppn = false;
  if (Object.prototype.hasOwnProperty.call(form.value, 'pph')) {
    form.value.pph = [];
  }
}

function onPerihalChange(val: any) {
  const newValue = val === null || val === undefined ? '' : String(val);
  const currentValue = form.value.perihal_id === null || form.value.perihal_id === undefined ? '' : String(form.value.perihal_id);
  if (newValue === currentValue) return;
  form.value.perihal_id = newValue;
  resetPengeluaranGrid();
  if (form.value.perihal_id) emitClearError('perihal_id');
}

function searchSuppliers(query: string) {
  loadSuppliers(query);
}

function handleSupplierChange(id: string) {
  form.value.supplier_id = id || null;
  form.value.bank_supplier_account_id = '';
  form.value.nama_bank = '';
  form.value.nama_rekening = '';
  form.value.no_rekening = '';
  if (!id) {
    supplierBankAccounts.value = [];
    return;
  }
  loadSupplierBankAccounts(id);
}

function handleBankSupplierAccountChange(id: string) {
  form.value.bank_supplier_account_id = id || '';
  applySelectedBankAccount();
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
    if (form.value.bisnis_partner_id) emitClearError('bisnis_partner_id');
  } else {
    form.value.no_rekening = found?.no_kartu_kredit ?? '';
    form.value.nama_bank = found?.bank?.nama_bank ?? '';
    form.value.nama_rekening = `${found?.nama_pemilik ?? ''} - ${form.value.no_rekening}`;
    form.value.bank_id = found?.bank_id ?? null;
    form.value.credit_card_id = found?.id ?? null;
    emitClearError('credit_card_id');
  }
  if (form.value.nama_rekening) emitClearError('nama_rekening');
  if (form.value.no_rekening) emitClearError('no_rekening');
  if (form.value.bank_id) emitClearError('bank_id');
}

watch(() => form.value.department_id, async () => {
  clearRekeningFields();
  if (form.value.metode_pembayaran === 'Transfer') {
    if (isSupplierPerihal.value) {
      // Perihal Supplier: gunakan Supplier, bukan Bisnis Partner
      await loadSuppliers();
      if (form.value.supplier_id) {
        await loadSupplierBankAccounts(String(form.value.supplier_id));
      }
    } else {
      await loadBisnisPartners();
    }
  } else {
    clearRekeningFields();
    await loadCreditCards();
  }
});

watch(() => form.value.metode_pembayaran, async () => {
  clearRekeningFields();
  if (form.value.metode_pembayaran === 'Transfer') {
    if (isSupplierPerihal.value) {
      // Perihal Supplier: gunakan Supplier, bukan Bisnis Partner
      await loadSuppliers();
      if (form.value.supplier_id) {
        await loadSupplierBankAccounts(String(form.value.supplier_id));
      }
    } else {
      await loadBisnisPartners();
    }
  } else {
    clearRekeningFields();
    await loadCreditCards();
  }
});

const displayNominal = computed(() => formatCurrency(form.value.nominal ?? ''));

const noRekeningDisplay = computed(() => {
  const nomor = form.value.no_rekening || '';
  const nama = form.value.nama_rekening || '';
  if (nomor && nama) return `${nomor} a/n ${nama}`;
  return nomor || nama || '';
});

const normalizedPerihalName = computed(() => {
  try {
    const id = (form.value?.perihal_id ?? '').toString();
    const p = (Array.isArray(perihals.value) ? perihals.value : []).find((x: any) => x && String(x.id) === id);
    return (p?.nama || '').toString().toLowerCase().trim();
  } catch {
    return '';
  }
});

const isSupplierPerihal = computed(() => {
  const nama = normalizedPerihalName.value;
  return (
    nama === 'permintaan pembayaran barang' ||
    nama === 'permintaan pembayaran jasa' ||
    nama === 'permintaan pembayaran barang/jasa'
  );
});

onMounted(async () => {
  if (!form.value?.department_id && Array.isArray(departments.value) && departments.value.length === 1) {
    const only = departments.value[0];
    if (only && only.id) {
      form.value.department_id = String(only.id);
    }
  }

  if (!form.value?.department_id) return;
  if (form.value.metode_pembayaran === 'Transfer') {
    if (isSupplierPerihal.value) {
      await loadSuppliers();
      if (form.value.supplier_id) {
        await loadSupplierBankAccounts(String(form.value.supplier_id));
        if (form.value.bank_supplier_account_id) {
          applySelectedBankAccount();
        }
      }
    } else {
      await loadBisnisPartners();
      if (form.value.bisnis_partner_id) {
        selectedRekeningId.value = String(form.value.bisnis_partner_id);
        onRekeningChange(form.value.bisnis_partner_id);
      }
    }
  } else {
    await loadCreditCards();
    if (form.value.credit_card_id) {
      selectedRekeningId.value = String(form.value.credit_card_id);
      onRekeningChange(form.value.credit_card_id);
    }
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
