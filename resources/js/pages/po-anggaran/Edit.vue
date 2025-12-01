<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="PO Anggaran" :show-add-button="false"/>

      <!-- Rejection Reason Alert -->
      <div
        v-if="poAnggaran?.status === 'Rejected' && poAnggaran?.rejection_reason"
        class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg
              class="w-5 h-5 text-red-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Alasan Penolakan</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ poAnggaran.rejection_reason }}</p>
            </div>
          </div>
        </div>
      </div>

      <PoAnggaranForm
        mode="edit"
        v-model:form="form"
        :poAnggaran="poAnggaran"
        :departments="departments"
        :errors="formErrors"
        @submit="onSave"
        @clear-error="handleClearError"
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
          @click="openSendConfirm"
          :disabled="loading || showConfirmDialog"
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
          :disabled="loading || showConfirmDialog"
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
          :disabled="loading || showConfirmDialog"
        >
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
          </svg>
          Batal
        </button>
      </div>
      <ConfirmDialog
        :show="showConfirmDialog"
        message="Apakah Anda yakin ingin mengirim PO Anggaran ini?"
        @confirm="onSend"
        @cancel="closeSendConfirm"
      />
    </div>
  </div>
  </template>
<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';
import PoAnggaranForm from '@/components/po-anggaran/PoAnggaranForm.vue';
import PoAnggaranPengeluaranGrid from '@/components/po-anggaran/PoAnggaranPengeluaranGrid.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';

defineOptions({ layout: AppLayout });
const props = defineProps<{ poAnggaran: any; departments?: any[] }>();
const departments = props.departments || [];
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'PO Anggaran', href: '/po-anggaran' }, { label: 'Edit' }];

const form = ref<any>({
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
  no_po_anggaran: props.poAnggaran?.no_po_anggaran ?? '',
  tanggal: props.poAnggaran?.tanggal ?? '',
  items: props.poAnggaran?.items ?? [],
  diskon: props.poAnggaran?.diskon ?? null,
  ppn: props.poAnggaran?.ppn ?? false,
  perihal_id: props.poAnggaran?.perihal_id ?? '',
});

const loading = ref(false);
const showConfirmDialog = ref(false);
const page = usePage();
const inertiaErrors = computed<Record<string, any>>(() => ((page.props as any)?.errors ?? {}));
const formErrors = ref<Record<string, any>>({});
const { addError, clearAll } = useMessagePanel();

watch(
  inertiaErrors,
  (newErrors) => {
    formErrors.value = { ...newErrors };
    if (newErrors && Object.keys(newErrors).length) {
      addError('Form ini wajib diisi. Mohon lengkapi data wajib.');
    }
  },
  { immediate: true, deep: true }
);

function handleClearError(field: string) {
  if (!field) return;
  const next = { ...formErrors.value };
  if (Object.prototype.hasOwnProperty.call(next, field)) {
    delete next[field];
    formErrors.value = next;
  }
}

function goBack() { history.back(); }

function openSendConfirm() {
  showConfirmDialog.value = true;
}

function closeSendConfirm() {
  showConfirmDialog.value = false;
}

async function onSave() {
  try {
    loading.value = true;
    await router.put(`/po-anggaran/${props.poAnggaran.id}`, { ...form.value, action: 'draft' }, {
      onStart: () => {
        clearAll();
      },
      onError: (errors) => {
        formErrors.value = { ...errors };
        if (errors && Object.keys(errors).length) {
          addError('Form ini wajib diisi. Mohon lengkapi data wajib.');
        }
        showConfirmDialog.value = false;
      },
      onSuccess: () => {
        formErrors.value = {};
        clearAll();
        showConfirmDialog.value = false;
      }
    });
  } finally {
    loading.value = false;
  }
}

async function onSend() {
  try {
    loading.value = true;
    await router.put(`/po-anggaran/${props.poAnggaran.id}`, { ...form.value, action: 'send' }, {
      onStart: () => {
        clearAll();
      },
      onError: (errors) => {
        formErrors.value = { ...errors };
        if (errors && Object.keys(errors).length) {
          addError('Form ini wajib diisi. Mohon lengkapi data wajib.');
        }
        showConfirmDialog.value = false;
      },
      onSuccess: () => {
        formErrors.value = {};
        clearAll();
        showConfirmDialog.value = false;
      }
    });
  } finally {
    loading.value = false;
  }
}

function recomputeNominal() {
  const items = Array.isArray(form.value.items) ? form.value.items : [];
  const subtotal = items.reduce((sum: number, i: any) => sum + (Number(i.qty) || 0) * (Number(i.harga) || 0), 0);
  const diskonVal = Number(form.value.diskon) || 0;
  const dpp = Math.max(subtotal - (diskonVal > 0 ? diskonVal : 0), 0);
  const ppnNominal = form.value.ppn ? dpp * 0.11 : 0;
  const total = dpp + ppnNominal;
  form.value.nominal = total;
}

watch(
  () => [form.value.items, form.value.diskon, form.value.ppn],
  () => {
    recomputeNominal();
  },
  { deep: true }
);

recomputeNominal();
</script>
