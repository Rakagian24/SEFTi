<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Bukti Penerimaan Barang</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Package class="w-4 h-4 mr-1" />
            Buat dokumen BPB baru
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <BpbForm v-model="form" :latestPOs="latestPOs" :suppliers="suppliers" :departmentOptions="departmentOptions" @open-po-modal="openPoModal" />
      </div>

      <BpbItemsTable
        v-model="form"
        @clear-items="clearItems"
        @add-pph="() => (showPphModal = true)"
      />

      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="openConfirmSend"
        >
          <svg
            fill="#E6E6E6"
            height="24"
            viewBox="0 0 24 24"
            width="24"
            xmlns="http://www.w3.org/2000/svg"
            class="w-5 h-5"
          >
            <path
              d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
            />
          </svg>
          Kirim
        </button>
        <button
          class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="openConfirmSave"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="w-5 h-5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"
            />
          </svg>
          Simpan Draft
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="router.visit('/bpb');"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
            stroke-width="1.5"
            stroke="currentColor"
            class="w-5 h-5"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
          Batal
        </button>
      </div>

      <!-- Modal Tambah Item removed: handled internally by BpbItemsTable -->

      <!-- Modal Picker PO -->
      <PoPickerModal
        v-model:open="showPoModal"
        :purchase-orders="eligiblePOs"
        :selected-ids="form.purchase_order_id ? [form.purchase_order_id] : []"
        no-results-message=""
        @search="(q:string)=>fetchEligiblePOs(q)"
        @add="(po:any)=>{ if(po?.id){ form.purchase_order_id = po.id } }"
      />

      <!-- Modal Tambah PPh -->
      <PphModal :show="showPphModal" @close="showPphModal=false" @save="savePph" />
      <!-- Confirm Dialogs -->
      <ConfirmDialog
        :show="showConfirmSend"
        message="Apakah Anda yakin ingin mengirim BPB ini?"
        @confirm="() => confirmSend()"
        @cancel="() => (showConfirmSend = false)"
      />
      <ConfirmDialog
        :show="showConfirmSave"
        message="Simpan draft BPB ini?"
        @confirm="() => confirmSave()"
        @cancel="() => (showConfirmSave = false)"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import BpbForm from '@/components/bpb/BpbForm.vue';
import BpbItemsTable from '@/components/bpb/BpbItemsTable.vue';
import PoPickerModal from '@/components/bpb/PoPickerModal.vue';
import PphModal from '@/components/bpb/PphModal.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Package } from 'lucide-vue-next';
import { useMessagePanel } from '@/composables/useMessagePanel';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'BPB', href: '/bpb' },
  { label: 'Buat Baru' },
];

defineOptions({ layout: AppLayout });

const page = usePage();
const latestPOs = (page.props as any).latestPOs || [];
const suppliers = (page.props as any).suppliers || [];
const departmentOptions = (page.props as any).departmentOptions || [];

type Item = { nama_barang: string; qty: number; satuan: string; harga: number };

const form = ref({
  department_id: (page.props as any).auth?.user?.department_id || null,
  purchase_order_id: null as number | null,
  supplier_id: '',
  alamat: '',
  note: '',
  keterangan: '',
  items: [] as Item[],
  diskon: 0,
  use_ppn: false,
  ppn_rate: 11,
  use_pph: false,
  pph_rate: 0,
});

const showPoModal = ref(false);
const showPphModal = ref(false);
const showConfirmSend = ref(false);
const showConfirmSave = ref(false);

// Eligible POs for modal (filtered by supplier/department/search)
const eligiblePOs = ref<any[]>([]);

function fetchEligiblePOs(search?: string) {
  const supplierId = form.value.supplier_id;
  if (!supplierId) { eligiblePOs.value = []; return; }
  const params = new URLSearchParams();
  params.set('supplier_id', String(supplierId));
  if (form.value.department_id) params.set('department_id', String(form.value.department_id));
  if (search) params.set('search', search);
  params.set('per_page', '50');
  axios.get(`/bpb/purchase-orders/eligible?${params.toString()}`)
    .then(res => { eligiblePOs.value = Array.isArray(res?.data?.data) ? res.data.data : []; })
    .catch(() => { eligiblePOs.value = []; });
}

function openPoModal() {
  if (!form.value.supplier_id) {
    addError('Pilih supplier terlebih dahulu');
    return;
  }
  fetchEligiblePOs();
  showPoModal.value = true;
}

// Refresh eligible POs when supplier or department changes
watch(() => [form.value.supplier_id, form.value.department_id], () => {
  if (showPoModal.value) fetchEligiblePOs();
});

function openConfirmSend() {
  showConfirmSend.value = true;
}

function openConfirmSave() {
  showConfirmSave.value = true;
}

function confirmSend() {
  showConfirmSend.value = false;
  saveDraft(true);
}

function confirmSave() {
  showConfirmSave.value = false;
  saveDraft(false);
}

function clearItems() {
  form.value.items = [];
}


const { addSuccess, addError, clearAll } = useMessagePanel();

function saveDraft(send = false) {
  if (!form.value.purchase_order_id || !form.value.supplier_id || !form.value.alamat) {
    addError('Purchase Order, Supplier, dan Alamat wajib diisi');
    return;
  }
  // Client-side validation for items
  const items: any[] = Array.isArray(form.value.items) ? form.value.items : [];
  if (!items.length) {
    addError('Minimal satu item harus diisi');
    return;
  }
  for (const it of items) {
    const qty = Number(it?.qty || 0);
    const max = Number(it?.remaining_qty ?? it?.qty ?? Infinity);
    if (!it?.purchase_order_item_id) {
      addError('Data item tidak valid: purchase_order_item_id kosong');
      return;
    }
    if (qty <= 0) {
      addError(`Qty untuk "${it?.nama_barang ?? '-'}" harus lebih dari 0`);
      return;
    }
    if (isFinite(max) && qty - max > 0.000001) {
      addError(`Qty untuk "${it?.nama_barang ?? '-'}" melebihi sisa (${max})`);
      return;
    }
  }
  clearAll();
  axios
    .post('/bpb/store-draft', form.value)
    .then((res) => {
      const id = res?.data?.bpb?.id;
      if (send && id) {
        return axios.post('/bpb/send', { ids: [id] }).then(() => {
          addSuccess('Dokumen berhasil dikirim');
          router.visit('/bpb');
        });
      } else {
        addSuccess('Draft berhasil disimpan');
        router.visit('/bpb');
      }
    })
    .catch((err) => {
      const serverErrors = err?.response?.data?.errors;
      if (serverErrors && typeof serverErrors === 'object') {
        const messages = Object.values(serverErrors).flat().join(' ');
        addError(messages || 'Gagal menyimpan draft');
      } else {
        addError(err?.response?.data?.message || 'Gagal menyimpan draft');
      }
    });
}

function savePph(payload: { kode: string; nama: string; tarif: number; deskripsi?: string }) {
  axios
    .post('/purchase-orders/add-pph', {
      kode_pph: payload.kode,
      nama_pph: payload.nama,
      tarif_pph: payload.tarif,
      deskripsi: payload.deskripsi || '',
      status: 'active',
    })
    .then(() => {
      form.value.use_pph = true;
      form.value.pph_rate = payload.tarif;
      showPphModal.value = false;
    })
    .catch(() => {
      showPphModal.value = false;
    });
}
</script>
