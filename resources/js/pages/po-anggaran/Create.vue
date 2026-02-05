<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="PO Anggaran" :show-add-button="false" />

      <PoAnggaranForm
        mode="create"
        v-model:form="form"
        :departments="departments"
        :errors="formErrors"
        @submit="onSubmit"
        @clear-error="handleClearError"
      />

      <PoAnggaranPengeluaranGrid
        v-model:items="form.items"
        v-model:diskon="form.diskon"
        v-model:ppn="form.ppn"
        :nominal="form.nominal"
        :form="{ tipe_po: 'Anggaran', perihal_id: form.perihal_id } as any"
      />

      <div class="mt-6 flex flex-col gap-3 border-t border-gray-200 pb-4 md:flex-row md:justify-start">
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
          @click="onSaveDraft"
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
        @confirm="onSubmit"
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
const props = defineProps<{ departments?: any[] }>();
const departments = props.departments || [];
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'PO Anggaran', href: '/po-anggaran' }, { label: 'Create' }];

const form = ref<any>({
  department_id: '',
  metode_pembayaran: 'Transfer',
  bank_id: null,
  bisnis_partner_id: null,
  credit_card_id: null,
  nama_rekening: '',
  no_rekening: '',
  nama_bank: '',
  no_giro: '',
  tanggal_giro: '',
  tanggal_cair: '',
  detail_keperluan: '',
  nominal: 0,
  note: '',
  no_anggaran: '',
  tanggal: '',
  items: [] as any[],
  diskon: null as any,
  ppn: false,
  perihal_id: '',
});

const loading = ref(false);
const showConfirmDialog = ref(false);
const page = usePage();
const inertiaErrors = computed<Record<string, any>>(() => ((page.props as any)?.errors ?? {}));
const formErrors = ref<Record<string, any>>({});
const { addError, addSuccess, clearAll } = useMessagePanel();

watch(
  inertiaErrors,
  (newErrors) => {
    // Debug: log raw inertia errors for PO Anggaran create
    if (newErrors && Object.keys(newErrors).length) {
      console.log('[PO Anggaran][Create] Inertia errors:', newErrors);
    }
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

async function onSaveDraft() {
  try {
    loading.value = true;
    await router.post('/po-anggaran', { ...form.value, action: 'draft' }, {
      onStart: () => {
        clearAll();
      },
      onError: (errors) => {
        // Debug: log validation errors when saving draft
        if (errors && Object.keys(errors).length) {
          console.log('[PO Anggaran][Create] Draft validation errors:', errors);
        }
        formErrors.value = { ...errors };
        if (errors && Object.keys(errors).length) {
          addError('Form ini wajib diisi. Mohon lengkapi data wajib.');
        }
        showConfirmDialog.value = false;
      },
      onSuccess: () => {
        formErrors.value = {};
        clearAll();
        const flash = ((page.props as any)?.flash ?? {}) as Record<string, any>;
        const message = typeof flash.success === 'string' && flash.success
          ? flash.success
          : 'PO Anggaran berhasil dikirim.';
        addSuccess(message);
        showConfirmDialog.value = false;
      }
    });
  } finally {
    loading.value = false;
  }
}

async function onSubmit() {
  try {
    loading.value = true;
    await router.post('/po-anggaran', { ...form.value, action: 'send' }, {
      onStart: () => {
        clearAll();
      },
      onError: (errors) => {
        // Debug: log validation errors when sending PO Anggaran
        if (errors && Object.keys(errors).length) {
          console.log('[PO Anggaran][Create] Send validation errors:', errors);
        }
        formErrors.value = { ...errors };
        if (errors && Object.keys(errors).length) {
          addError('Form ini wajib diisi. Mohon lengkapi data wajib.');
        }
        showConfirmDialog.value = false;
      },
      onSuccess: () => {
        formErrors.value = {};
        clearAll();
        const flash = ((page.props as any)?.flash ?? {}) as Record<string, any>;
        const message = typeof flash.success === 'string' && flash.success
          ? flash.success
          : 'PO Anggaran berhasil di simpan sebagai Draft';
        addSuccess(message);
        showConfirmDialog.value = false;
      }
    });
  } finally {
    loading.value = false;
  }
}

// Auto-calculate nominal based on grid totals (subtotal - diskon + PPN)
function recomputeNominal() {
  const items = Array.isArray(form.value.items) ? form.value.items : [];
  const subtotal = items.reduce((sum: number, i: any) => sum + (Number(i.qty) || 0) * (Number(i.harga) || 0), 0);
  const diskonVal = Number(form.value.diskon) || 0;
  const dpp = Math.max(subtotal - (diskonVal > 0 ? diskonVal : 0), 0);
  const ppnNominal = form.value.ppn ? dpp * 0.11 : 0;
  const total = dpp + ppnNominal;
  form.value.nominal = total;
}

// Recompute when items, diskon, or ppn change
watch(
  () => [form.value.items, form.value.diskon, form.value.ppn],
  () => {
    recomputeNominal();
  },
  { deep: true }
);

// Initial compute
recomputeNominal();
</script>
