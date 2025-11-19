<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import BpbForm from '@/components/bpb/BpbForm.vue';
import BpbItemsTable from '@/components/bpb/BpbItemsTable.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { FileText } from 'lucide-vue-next';

defineOptions({ layout: AppLayout });

// Ensure we always display the latest server state when opening this page
onMounted(() => {
  router.reload({ only: ['bpb'] });
});

const props = defineProps<{ bpb: any; latestPOs: any[]; suppliers: any[]; departmentOptions: Array<{ value: number|string; label: string }>; }>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'BPB', href: '/bpb' },
  { label: props.bpb?.no_bpb || `Edit #${props.bpb?.id}` },
];

const form = ref({
  department_id: String(props.bpb?.department_id || ''),
  purchase_order_id: String(props.bpb?.purchase_order_id || ''),
  supplier_id: String(props.bpb?.supplier_id || ''),
  alamat: props.bpb?.supplier?.alamat || '',
  keterangan: props.bpb?.keterangan || '',
  items: (props.bpb?.items || []).map((it: any) => ({
    nama_barang: it.nama_barang,
    qty: Number(it.qty),
    // Simpan qty awal untuk perhitungan sisa saat edit
    initial_qty: Number(it.qty),
    satuan: it.satuan,
    harga: Number(it.harga),
    purchase_order_item_id: it.purchase_order_item_id,
  })),
  diskon: Number(props.bpb?.diskon || 0),
  use_ppn: Number(props.bpb?.ppn || 0) > 0,
  ppn_rate: 11,
  use_pph: Number(props.bpb?.pph || 0) > 0,
  pph_rate: Number(props.bpb?.pph || 0) > 0 ? 2 : 0,
});

const { addSuccess, addError, clearAll } = useMessagePanel();

// Keep form in sync when navigating back to this page or reopening after save
watch(
  () => props.bpb,
  (bpb: any) => {
    if (!bpb) return;
    form.value = {
      department_id: String(bpb?.department_id || ''),
      purchase_order_id: String(bpb?.purchase_order_id || ''),
      supplier_id: String(bpb?.supplier_id || ''),
      alamat: bpb?.supplier?.alamat || '',
      keterangan: bpb?.keterangan || '',
      items: (bpb?.items || []).map((it: any) => ({
        nama_barang: it.nama_barang,
        qty: Number(it.qty),
        // Pastikan initial_qty tetap ada saat sinkronisasi
        initial_qty: Number(it.qty),
        satuan: it.satuan,
        harga: Number(it.harga),
        purchase_order_item_id: it.purchase_order_item_id,
      })),
      diskon: Number(bpb?.diskon || 0),
      use_ppn: Number(bpb?.ppn || 0) > 0,
      ppn_rate: 11,
      use_pph: Number(bpb?.pph || 0) > 0,
      pph_rate: Number(bpb?.pph || 0) > 0 ? 2 : 0,
    } as any;
  }
);

const showConfirmSend = ref(false);

const canSend = computed(() => ['Draft','Rejected'].includes(String(props.bpb?.status || '')));

function openConfirmSend() {
  if (!canSend.value) return;
  showConfirmSend.value = true;
}

function submit() {
  // Only update minimal fields via PUT JSON endpoint
  clearAll();
  axios
    .put(`/bpb/${props.bpb.id}`, {
      department_id: form.value.department_id || null,
      purchase_order_id: form.value.purchase_order_id || null,
      supplier_id: form.value.supplier_id || null,
      keterangan: form.value.keterangan || null,
      diskon: form.value.diskon,
      use_ppn: !!form.value.use_ppn,
      ppn_rate: form.value.ppn_rate,
      use_pph: !!form.value.use_pph,
      pph_rate: form.value.pph_rate,
      items: (form.value.items || []).map((it:any)=>({
        nama_barang: it.nama_barang,
        qty: Number(it.qty || 0),
        satuan: it.satuan,
        harga: Number(it.harga || 0),
        purchase_order_item_id: it.purchase_order_item_id,
      })),
    })
    .then(() => {
      addSuccess('BPB berhasil diperbarui');
      router.visit('/bpb');
    })
    .catch((err) => {
      const serverErrors = err?.response?.data?.errors;
      if (serverErrors && typeof serverErrors === 'object') {
        const messages = Object.values(serverErrors).flat().join(' ');
        addError(messages || 'Gagal memperbarui BPB');
      } else {
        addError(err?.response?.data?.message || 'Gagal memperbarui BPB');
      }
    });
}

function confirmSend() {
  if (!canSend.value) { showConfirmSend.value = false; return; }
  clearAll();
  // Persist latest item changes before sending
  axios
    .put(`/bpb/${props.bpb.id}`, {
      department_id: form.value.department_id || null,
      purchase_order_id: form.value.purchase_order_id || null,
      supplier_id: form.value.supplier_id || null,
      keterangan: form.value.keterangan || null,
      diskon: form.value.diskon,
      use_ppn: !!form.value.use_ppn,
      ppn_rate: form.value.ppn_rate,
      use_pph: !!form.value.use_pph,
      pph_rate: form.value.pph_rate,
      items: (form.value.items || []).map((it:any)=>({
        nama_barang: it.nama_barang,
        qty: Number(it.qty || 0),
        satuan: it.satuan,
        harga: Number(it.harga || 0),
        purchase_order_item_id: it.purchase_order_item_id,
      })),
    })
    .then(() => axios.post('/bpb/send', { ids: [props.bpb.id] }))
    .then(() => {
      addSuccess('Dokumen berhasil dikirim');
      router.visit('/bpb');
    })
    .catch((err) => {
      const serverErrors = err?.response?.data?.errors;
      if (serverErrors && typeof serverErrors === 'object') {
        const messages = Object.values(serverErrors).flat().join(' ');
        addError(messages || 'Gagal mengirim dokumen');
      } else {
        addError(err?.response?.data?.message || 'Gagal mengirim dokumen');
      }
    })
    .finally(() => {
      showConfirmSend.value = false;
    });
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Bukti Penerimaan Barang</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <FileText class="w-4 h-4 mr-1" />
            Edit dokumen BPB
          </div>
        </div>
      </div>

      <div v-if="props.bpb?.rejection_reason" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700 mb-4">
        <div class="font-semibold mb-1">Alasan Penolakan</div>
        <div>{{ props.bpb.rejection_reason }}</div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <BpbForm
          :model-value="form"
          @update:modelValue="(v:any)=>Object.assign(form, v)"
          :latestPOs="props.latestPOs"
          :suppliers="props.suppliers"
          :departmentOptions="props.departmentOptions"
        />
      </div>

      <BpbItemsTable
        :model-value="form"
        @update:modelValue="(v:any)=>Object.assign(form, v)"
      />

      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="openConfirmSend"
          :disabled="!canSend"
        >
          <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          Kirim
        </button>
        <button
          class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="submit"
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
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          Batal
        </button>
      </div>

      <ConfirmDialog
        :show="showConfirmSend"
        message="Apakah Anda yakin ingin mengirim BPB ini?"
        @confirm="confirmSend"
        @cancel="() => (showConfirmSend = false)"
      />
    </div>
  </div>
</template>
