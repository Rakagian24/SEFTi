<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import BpbForm from '@/components/bpb/BpbForm.vue';
import BpbItemsTable from '@/components/bpb/BpbItemsTable.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';

defineOptions({ layout: AppLayout });

const props = defineProps<{ bpb: any; latestPOs: any[]; suppliers: any[] }>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'BPB', href: '/bpb' },
  { label: props.bpb?.no_bpb || `Edit #${props.bpb?.id}` },
];

const form = ref({
  purchase_order_id: String(props.bpb?.purchase_order_id || ''),
  supplier_id: String(props.bpb?.supplier_id || ''),
  alamat: props.bpb?.supplier?.alamat || '',
  keterangan: props.bpb?.keterangan || '',
  items: (props.bpb?.items || []).map((it: any) => ({
    nama_barang: it.nama_barang,
    qty: Number(it.qty),
    satuan: it.satuan,
    harga: Number(it.harga),
  })),
  diskon: Number(props.bpb?.diskon || 0),
  use_ppn: Number(props.bpb?.ppn || 0) > 0,
  ppn_rate: 11,
  use_pph: Number(props.bpb?.pph || 0) > 0,
  pph_rate: Number(props.bpb?.pph || 0) > 0 ? 2 : 0,
});

const { addSuccess, addError, clearAll } = useMessagePanel();

const showConfirmSave = ref(false);
const showConfirmSend = ref(false);
const showConfirmCancel = ref(false);

const canSend = computed(() => ['Draft','Rejected'].includes(String(props.bpb?.status || '')));

function openConfirmSave() {
  showConfirmSave.value = true;
}

function openConfirmSend() {
  if (!canSend.value) return;
  showConfirmSend.value = true;
}

function openConfirmCancel() {
  showConfirmCancel.value = true;
}

function submit() {
  // Only update minimal fields via PUT JSON endpoint
  clearAll();
  axios
    .put(`/bpb/${props.bpb.id}`, {
      purchase_order_id: form.value.purchase_order_id || null,
      supplier_id: form.value.supplier_id || null,
      keterangan: form.value.keterangan || null,
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
    })
    .finally(() => {
      showConfirmSave.value = false;
    });
}

function confirmSend() {
  if (!canSend.value) { showConfirmSend.value = false; return; }
  clearAll();
  axios
    .post('/bpb/send', { ids: [props.bpb.id] })
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
  <div class="space-y-6">
    <Breadcrumbs :items="breadcrumbs" />

    <div class="bg-white rounded-lg border p-4 space-y-4">
      <h1 class="text-xl font-semibold">Edit BPB</h1>
      <div v-if="props.bpb?.rejection_reason" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-700">
        <div class="font-semibold mb-1">Alasan Penolakan</div>
        <div>{{ props.bpb.rejection_reason }}</div>
      </div>
      <div class="bg-white rounded-lg shadow-sm p-6">
        <BpbForm v-model="form" :latestPOs="props.latestPOs" :suppliers="props.suppliers" />
      </div>
      <BpbItemsTable v-model="form" />

      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="openConfirmSend"
          :disabled="!canSend"
        >
          <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          Kirim
        </button>
        <button
          class="px-6 py-2 text-sm font-medium text-white bg-blue-500 border border-transparent rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="openConfirmSave"
        >
          <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="w-6 h-6"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
          Simpan Perubahan
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="openConfirmCancel"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
          Batal
        </button>
      </div>
      <ConfirmDialog
        :show="showConfirmSave"
        message="Simpan perubahan BPB ini?"
        @confirm="submit"
        @cancel="() => (showConfirmSave = false)"
      />
      <ConfirmDialog
        :show="showConfirmSend"
        message="Kirim dokumen BPB ini?"
        @confirm="confirmSend"
        @cancel="() => (showConfirmSend = false)"
      />
      <ConfirmDialog
        :show="showConfirmCancel"
        message="Batalkan perubahan dan kembali?"
        @confirm="() => { showConfirmCancel = false; clearAll(); router.visit('/bpb'); }"
        @cancel="() => (showConfirmCancel = false)"
      />
    </div>
  </div>
</template>
