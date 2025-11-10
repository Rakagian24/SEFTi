<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Edit PO Anggaran" />

      <PoAnggaranForm
        mode="edit"
        v-model:form="form"
        :poAnggaran="poAnggaran"
        :departments="departments"
        @submit="onSave"
      />

      <PoAnggaranPengeluaranGrid
        v-model:items="form.items"
        v-model:diskon="form.diskon"
        v-model:ppn="form.ppn"
        :nominal="form.nominal"
        :form="{ tipe_po: 'Anggaran' } as any"
      />

      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="onSend"
          :disabled="loading"
        >
          <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
            <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
          </svg>
          Kirim
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="onSave"
          :disabled="loading"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
          </svg>
          Simpan
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="goBack"
          :disabled="loading"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Batal
        </button>
      </div>
    </div>
  </div>
  </template>
<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';
import PoAnggaranForm from '@/components/po-anggaran/PoAnggaranForm.vue';
import PoAnggaranPengeluaranGrid from '@/components/po-anggaran/PoAnggaranPengeluaranGrid.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ poAnggaran: any; departments?: any[] }>();
const departments = props.departments || [];
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'PO Anggaran', href: '/po-anggaran' }, { label: 'Edit' }];

const form = ref<any>({
  department_id: props.poAnggaran?.department_id ?? '',
  metode_pembayaran: props.poAnggaran?.metode_pembayaran ?? 'Transfer',
  bank_id: props.poAnggaran?.bank_id ?? null,
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
  diskon: props.poAnggaran?.diskon ?? null,
  ppn: props.poAnggaran?.ppn ?? false,
  perihal_id: props.poAnggaran?.perihal_id ?? '',
});

const loading = ref(false);
function goBack() { history.back(); }

async function onSave() {
  try {
    loading.value = true;
    await router.put(`/po-anggaran/${props.poAnggaran.id}`, { ...form.value });
  } finally {
    loading.value = false;
  }
}

async function onSend() {
  try {
    loading.value = true;
    await router.put(`/po-anggaran/${props.poAnggaran.id}`, { ...form.value });
    await router.post('/po-anggaran/send', { ids: [props.poAnggaran.id] });
  } finally {
    loading.value = false;
  }
}
</script>
