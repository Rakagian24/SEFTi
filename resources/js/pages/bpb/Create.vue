<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Bukti Penerimaan Barang</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <FileText class="w-4 h-4 mr-1" />
            Buat dokumen BPB baru
          </div>
        </div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <BpbForm v-model="form" :latestPOs="latestPOs" :suppliers="suppliers" :departmentOptions="departmentOptions" @open-po-modal="openPoModal" />
      </div>

      <BpbItemsTable
        v-model="form"
        :summary="bpbSummary"
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
          @click="() => saveDraft()"
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import BpbForm from '@/components/bpb/BpbForm.vue';
import BpbItemsTable from '@/components/bpb/BpbItemsTable.vue';
import PoPickerModal from '@/components/bpb/PoPickerModal.vue';
import PphModal from '@/components/bpb/PphModal.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { FileText } from 'lucide-vue-next';
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
const defaultDepartmentId = (page.props as any).defaultDepartmentId || null;

type Item = { nama_barang: string; qty: number; satuan: string; harga: number };

const form = ref({
  department_id: defaultDepartmentId ?? ((page.props as any).auth?.user?.department_id || null),
  purchase_order_id: null as number | null,
  supplier_id: '',
  alamat: '',
  note: '',
  keterangan: '',
  surat_jalan_no: '',
  surat_jalan_file: null as File | null,
  items: [] as Item[],
  diskon: 0,
  use_ppn: false,
  ppn_rate: 11,
  use_pph: false,
  pph_rate: 0,
  metode_pembayaran: 'Transfer',
  credit_card_id: '',
});

// Summary angka BPB (total/diskon/ppn_nominal/pph_nominal/grand_total) untuk ditampilkan di BpbItemsTable
const bpbSummary = computed(() => {
  const items: any[] = Array.isArray(form.value.items) ? (form.value.items as any[]) : [];
  const total = items.reduce(
    (sum, it: any) => sum + Number(it.qty || 0) * Number(it.harga || 0),
    0
  );
  const diskon = Number(form.value.diskon ?? 0);
  const dpp = Math.max(total - diskon, 0);
  const usePpn = !!form.value.use_ppn;
  const usePph = !!form.value.use_pph;
  const ppnRate = Number(form.value.ppn_rate ?? 0);
  const pphRate = Number(form.value.pph_rate ?? 0);
  const ppn = usePpn ? dpp * (ppnRate / 100) : 0;
  const pph = usePph ? dpp * (pphRate / 100) : 0;
  const grand_total = dpp + ppn - pph;

  return {
    total,
    diskon,
    ppn_nominal: ppn,
    pph_nominal: pph,
    grand_total,
  };
});

const showPoModal = ref(false);
const showPphModal = ref(false);
const showConfirmSend = ref(false);

// Eligible POs for modal (filtered by supplier/department/search)
const eligiblePOs = ref<any[]>([]);

function fetchEligiblePOs(search?: string) {
  const metode = form.value.metode_pembayaran || 'Transfer';
  const isKredit = String(metode).toLowerCase() === 'kredit';
  const supplierId = form.value.supplier_id;
  const creditCardId = form.value.credit_card_id;
  if ((!isKredit && !supplierId) || (isKredit && !creditCardId)) { eligiblePOs.value = []; return; }
  const params = new URLSearchParams();
  if (isKredit) {
    params.set('credit_card_id', String(creditCardId));
    if (form.value.department_id) params.set('department_id', String(form.value.department_id));
  } else {
    params.set('supplier_id', String(supplierId));
    if (form.value.department_id) params.set('department_id', String(form.value.department_id));
  }
  if (search) params.set('search', search);
  params.set('per_page', '50');
  axios.get(`/bpb/purchase-orders/eligible?${params.toString()}`)
    .then(res => { eligiblePOs.value = Array.isArray(res?.data?.data) ? res.data.data : []; })
    .catch(() => { eligiblePOs.value = []; });
}

function openPoModal() {
  const metode = form.value.metode_pembayaran || 'Transfer';
  const isKredit = String(metode).toLowerCase() === 'kredit';
  if (!isKredit && !form.value.supplier_id) {
    addError('Pilih supplier terlebih dahulu');
    return;
  }
  if (isKredit && !form.value.credit_card_id) {
    addError('Pilih Nama Rekening (Kredit) terlebih dahulu');
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

function confirmSend() {
  showConfirmSend.value = false;
  sendNow();
}

function clearItems() {
  form.value.items = [];
}

const { addSuccess, addError, clearAll } = useMessagePanel();

function validateBeforeSubmit(requireSuratJalanNo: boolean = true, isDraft: boolean = false): boolean {
  const metode = form.value.metode_pembayaran || 'Transfer';
  const isKredit = String(metode).toLowerCase() === 'kredit';

  // Untuk draft: hanya pastikan department terisi (metode pembayaran sudah ada default di form)
  if (isDraft) {
    if (!form.value.department_id) {
      addError('Department wajib dipilih');
      return false;
    }
    // Tidak ada kewajiban PO, supplier, alamat, credit card, items, ataupun No. Surat Jalan
  } else {
    // Validasi penuh untuk Kirim
    if (!form.value.purchase_order_id) {
      addError('Purchase Order wajib dipilih');
      return false;
    }
    if (!isKredit) {
      if (!form.value.supplier_id || !form.value.alamat) {
        addError('Supplier dan Alamat wajib diisi untuk metode Transfer');
        return false;
      }
    } else {
      if (!form.value.credit_card_id) {
        addError('Nama Rekening (Kredit) wajib dipilih untuk metode Kredit');
        return false;
      }
    }

    // Client-side validation untuk items hanya saat Kirim
    const items: any[] = Array.isArray(form.value.items) ? form.value.items : [];
    if (!items.length) {
      addError('Minimal satu item harus diisi');
      return false;
    }
    for (const it of items) {
      const qty = Number(it?.qty || 0);
      const max = Number(it?.remaining_qty ?? it?.qty ?? Infinity);
      if (!it?.purchase_order_item_id) {
        addError('Data item tidak valid: purchase_order_item_id kosong');
        return false;
      }
      if (qty <= 0) {
        addError(`Qty untuk "${it?.nama_barang ?? '-'}" harus lebih dari 0`);
        return false;
      }
      if (isFinite(max) && qty - max > 0.000001) {
        addError(`Qty untuk "${it?.nama_barang ?? '-'}" melebihi sisa (${max})`);
        return false;
      }
    }
  }

  if (requireSuratJalanNo) {
    if (!form.value.surat_jalan_no || String(form.value.surat_jalan_no).trim() === '') {
      addError('No. Surat Jalan wajib diisi');
      return false;
    }
  }

  clearAll();
  return true;
}

function buildFormData(): FormData {
  const fd = new FormData();
  const payload: any = form.value;

  // Scalar fields
  fd.append('department_id', String(payload.department_id ?? ''));
  fd.append('purchase_order_id', String(payload.purchase_order_id ?? ''));
  if (payload.supplier_id) fd.append('supplier_id', String(payload.supplier_id));
  if (payload.keterangan) fd.append('keterangan', String(payload.keterangan));
  if (payload.surat_jalan_no) fd.append('surat_jalan_no', String(payload.surat_jalan_no));
  if (typeof payload.diskon !== 'undefined') fd.append('diskon', String(payload.diskon ?? 0));
  fd.append('use_ppn', payload.use_ppn ? '1' : '0');
  fd.append('ppn_rate', String(payload.ppn_rate ?? 11));
  fd.append('use_pph', payload.use_pph ? '1' : '0');
  fd.append('pph_rate', String(payload.pph_rate ?? 0));

  if (payload.surat_jalan_file instanceof File) {
    fd.append('surat_jalan_file', payload.surat_jalan_file);
  }

  // Items: Laravel expects items[index][field]
  const items: any[] = Array.isArray(payload.items) ? payload.items : [];
  items.forEach((it, idx) => {
    fd.append(`items[${idx}][nama_barang]`, String(it.nama_barang ?? ''));
    fd.append(`items[${idx}][qty]`, String(it.qty ?? 0));
    fd.append(`items[${idx}][satuan]`, String(it.satuan ?? ''));
    fd.append(`items[${idx}][harga]`, String(it.harga ?? 0));
    if (it.purchase_order_item_id) {
      fd.append(`items[${idx}][purchase_order_item_id]`, String(it.purchase_order_item_id));
    }
  });

  return fd;
}

function saveDraft(send = false) {
  // Saat simpan draft, No. Surat Jalan tidak wajib diisi
  if (!validateBeforeSubmit(false, true)) return;
  const fd = buildFormData();
  axios
    .post('/bpb/store-draft', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
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

function sendNow() {
  // Saat kirim BPB, No. Surat Jalan wajib diisi
  if (!validateBeforeSubmit(true, false)) return;
  const fd = buildFormData();
  axios
    .post('/bpb/store-and-send', fd, { headers: { 'Content-Type': 'multipart/form-data' } })
    .then(() => {
      addSuccess('Dokumen berhasil dikirim');
      router.visit('/bpb');
    })
    .catch((err) => {
      const serverErrors = err?.response?.data?.errors;
      if (serverErrors && typeof serverErrors === 'object') {
        const messages = Object.values(serverErrors).flat().join(' ');
        addError(messages || 'Gagal mengirim BPB');
      } else {
        addError(err?.response?.data?.message || 'Gagal mengirim BPB');
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
