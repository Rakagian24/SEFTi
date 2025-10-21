<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <form @submit.prevent="onSubmit" novalidate class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
        <div>
          <CustomSelect
            :model-value="form.metode_pembayaran ?? ''"
            @update:modelValue="(val) => (form.metode_pembayaran = val as string)"
            :options="[
              { label: 'Transfer', value: 'Transfer' },
              { label: 'Cek/Giro', value: 'Cek/Giro' },
            ]"
            placeholder="Pilih Metode"
          >
            <template #label> Metode Pembayaran<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <div class="floating-input">
          <input
            type="text"
            v-model="form.nama_rekening"
            id="nama_rekening"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="nama_rekening" class="floating-label">Nama Rekening</label>
        </div>
        <div class="floating-input">
          <input
            type="text"
            v-model="form.no_rekening"
            id="no_rekening"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="no_rekening" class="floating-label">No. Rekening</label>
        </div>

        <div v-if="form.metode_pembayaran==='Cek/Giro'" class="floating-input">
          <input
            type="text"
            v-model="form.no_giro"
            id="no_giro"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="no_giro" class="floating-label">No. Cek/Giro</label>
        </div>
        <div v-if="form.metode_pembayaran==='Cek/Giro'" class="floating-input">
          <input
            type="date"
            v-model="form.tanggal_giro"
            id="tanggal_giro"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="tanggal_giro" class="floating-label">Tanggal Giro</label>
        </div>
        <div v-if="form.metode_pembayaran==='Cek/Giro'" class="floating-input">
          <input
            type="date"
            v-model="form.tanggal_cair"
            id="tanggal_cair"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="tanggal_cair" class="floating-label">Tanggal Cair</label>
        </div>

        <div class="md:col-span-2 floating-input">
          <textarea
            v-model="form.detail_keperluan"
            id="detail_keperluan"
            rows="3"
            class="floating-input-field resize-none"
            placeholder=" "
          ></textarea>
          <label for="detail_keperluan" class="floating-label">Detail Keperluan</label>
        </div>

        <div class="floating-input">
          <input
            type="text"
            :value="displayNominal"
            id="nominal"
            class="floating-input-field"
            placeholder=" "
            @keydown="allowNumericKeydown"
            @input="onNominalInput"
          />
          <label for="nominal" class="floating-label">Nominal<span class="text-red-500">*</span></label>
        </div>
        <div class="md:col-span-2 floating-input">
          <textarea v-model="form.note" id="note" class="floating-input-field resize-none" placeholder=" " rows="3"></textarea>
          <label for="note" class="floating-label">Note</label>
        </div>
      </div>

      <!-- Barang Grid (reusable, consistent styling/features) -->
      <PoAnggaranBarangGrid
        v-model:items="form.items"
        v-model:diskon="form.diskon"
        v-model:ppn="form.ppn"
        :pphList="pphList"
        :nominal="form.nominal"
        :form="{ tipe_po: 'Anggaran' } as any"
      />

      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button type="button" class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2" @click="goBack">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Batal
        </button>
        <button type="button" class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2" @click="saveDraft">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
          </svg>
          Simpan Draft
        </button>
        <button type="button" class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2" @click="send">
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
import PoAnggaranBarangGrid from '@/components/po-anggaran/PoAnggaranBarangGrid.vue';
import { parseCurrency, formatCurrency } from '@/lib/currencyUtils';

const props = defineProps<{ mode: 'create'|'edit'; poAnggaran?: any; departments?: any[] }>();
const departments = ref<any[]>(Array.isArray(props?.poAnggaran?.departments) ? props.poAnggaran.departments : (props.departments || []));

const form = reactive<any>({
  department_id: props.poAnggaran?.department_id ?? '',
  metode_pembayaran: props.poAnggaran?.metode_pembayaran ?? 'Transfer',
  bank_id: props.poAnggaran?.bank_id ?? null,
  nama_rekening: props.poAnggaran?.nama_rekening ?? '',
  no_rekening: props.poAnggaran?.no_rekening ?? '',
  no_giro: props.poAnggaran?.no_giro ?? '',
  tanggal_giro: props.poAnggaran?.tanggal_giro ?? '',
  tanggal_cair: props.poAnggaran?.tanggal_cair ?? '',
  detail_keperluan: props.poAnggaran?.detail_keperluan ?? '',
  nominal: props.poAnggaran?.nominal ?? 0,
  note: props.poAnggaran?.note ?? '',
  items: props.poAnggaran?.items ?? [],
  // Common fitur for grid
  diskon: props.poAnggaran?.diskon ?? null,
  ppn: props.poAnggaran?.ppn ?? false,
});

const banks = ref<any[]>([]);
const pphList = ref<any[]>([]);

async function loadBanks() { try { const { data } = await axios.get('/banks'); banks.value = data?.data ?? data ?? []; } catch { banks.value = []; } }
loadBanks();

// Load PPH list (optional). If endpoint differs for PO Anggaran, adjust accordingly.
async function loadPph() {
  try {
    const { data } = await axios.get('/purchase-orders/pph');
    const list = Array.isArray(data?.data) ? data.data : (Array.isArray(data) ? data : []);
    pphList.value = list.map((pph: any) => ({ id: pph.id, kode: pph.kode_pph ?? pph.kode, nama: pph.nama_pph ?? pph.nama, tarif: pph.tarif_pph ? pph.tarif_pph/100 : (pph.tarif ?? 0) }));
  } catch {
    pphList.value = [];
  }
}
loadPph();

function goBack() { history.back(); }

function saveDraft() {
  if (props.mode === 'create') router.post('/po-anggaran', { ...form });
  else router.put(`/po-anggaran/${props.poAnggaran.id}`, { ...form });
}

function send() {
  if (props.mode === 'create') {
    router.post('/po-anggaran', { ...form });
  } else {
    router.put(`/po-anggaran/${props.poAnggaran.id}`, { ...form }, { onSuccess: () => router.post('/po-anggaran/send', { ids: [ props.poAnggaran.id ] }) });
  }
}

function onSubmit() { saveDraft(); }

// Numeric formatting for nominal (thousand separators)
const displayNominal = computed(() => formatCurrency(form.nominal ?? ''));
function onNominalInput(e: Event) {
  const input = e.target as HTMLInputElement;
  const parsed = parseCurrency(input.value);
  form.nominal = parsed === '' ? null : Number(parsed);
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
