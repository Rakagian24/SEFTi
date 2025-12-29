<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />
      <PageHeader title="Edit Realisasi" :show-add-button="false" />

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div class="border-b border-gray-200 mb-4">
          <nav class="flex -mb-px" aria-label="Tabs">
            <button
              type="button"
              class="mr-6 whitespace-nowrap py-2 px-1 border-b-2 text-sm font-medium"
              :class="
                activeTab === 'form'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              "
              @click="activeTab = 'form'"
            >
              Form
            </button>
            <button
              type="button"
              class="whitespace-nowrap py-2 px-1 border-b-2 text-sm font-medium"
              :class="
                activeTab === 'docs'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              "
              @click="activeTab = 'docs'"
            >
              Dokumen Pendukung
            </button>
          </nav>
        </div>

        <div v-show="activeTab === 'form'">
          <RealisasiForm
            ref="formRef"
            mode="edit"
            :realisasi="realisasi"
            :departments="departments"
            @save-draft="handleSaveDraft"
            @send="handleSend"
          />
        </div>

        <div v-show="activeTab === 'docs'">
          <RealisasiSupportingDocs ref="docsRef" :realisasi-id="realisasi.id" />
        </div>
      </div>

      <!-- Action Buttons - shown on all tabs -->
      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          type="button"
          @click="triggerSend"
          :disabled="isSubmitting"
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
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
          <span v-if="isSubmitting">Mengirim...</span>
          <span v-else>Kirim</span>
        </button>

        <button
          type="button"
          @click="triggerSaveDraft"
          :disabled="isSubmitting"
          class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
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
          <span v-if="isSubmitting">Menyimpan...</span>
          <span v-else>Simpan Draft</span>
        </button>

        <button
          type="button"
          @click="openCancelConfirm"
          class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
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

      <!-- Confirm Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="
          confirmAction === 'send'
            ? 'Apakah Anda yakin ingin mengirim Realisasi ini?'
            : confirmAction === 'cancel'
              ? 'Apakah Anda yakin ingin membatalkan perubahan Realisasi ini? Perubahan yang belum disimpan akan hilang.'
              : ''
        "
        @confirm="handleConfirm"
        @cancel="showConfirmDialog = false"
      />
    </div>
  </div>
</template>
<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import PageHeader from '@/components/PageHeader.vue';
import RealisasiForm from '@/components/realisasi/RealisasiForm.vue';
import RealisasiSupportingDocs from '@/components/realisasi/RealisasiSupportingDocs.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';

defineOptions({ layout: AppLayout });
const props = defineProps<{ realisasi: any; departments: any[] }>();
const departments = props.departments || [];
const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Realisasi', href: '/realisasi' },
  { label: 'Edit' },
];

const activeTab = ref<'form' | 'docs'>('form');
const isSubmitting = ref(false);
const isSavingDraft = ref(false);
const lastFormPayload = ref<any | null>(null);
const formRef = ref<any | null>(null);
const docsRef = ref<any | null>(null);
const showConfirmDialog = ref(false);
const confirmAction = ref<'send' | 'cancel' | ''>('');

const { addSuccess, addError, clearAll } = useMessagePanel();

function openCancelConfirm() {
  confirmAction.value = 'cancel';
  showConfirmDialog.value = true;
}

function rememberForm(payload: { form: any }) {
  lastFormPayload.value = payload?.form ? { ...payload.form } : null;
}

async function handleSaveDraft(payload: { form: any }) {
  rememberForm(payload);
  const ok = await doSaveDraft();
  if (ok) {
    router.visit('/realisasi');
  }
}

async function handleSend(payload: { form: any }) {
  rememberForm(payload);
  await doSend();
}

async function doSaveDraft(showMessage = true): Promise<boolean> {
  if (isSavingDraft.value) return false;
  const formPayload = lastFormPayload.value;
  if (!formPayload) return false;

  isSavingDraft.value = true;
  try {
    await axios.patch(`/realisasi/${props.realisasi.id}/update-draft`, formPayload, {
      withCredentials: true,
    });

    try { await docsRef.value?.syncActiveStates(props.realisasi.id); } catch {}
    try { await docsRef.value?.flushUploads(props.realisasi.id); } catch {}
    if (showMessage) {
      addSuccess('Draft Realisasi berhasil disimpan');
    }
    return true;
  } catch (e: any) {
    const data = e?.response?.data;
    const msg = data?.message || data?.error || e?.message || 'Terjadi kesalahan saat menyimpan draft Realisasi';
    addError(msg);
    return false;
  } finally {
    isSavingDraft.value = false;
  }
}

async function doSend() {
  if (isSubmitting.value) return;
  const formPayload = lastFormPayload.value;
  if (!formPayload) {
    activeTab.value = 'form';
    return;
  }

  // Pre-validate minimal kelengkapan form sebelum menyimpan draft & mengirim
  const preErrors: string[] = [];
  if (!formPayload.department_id) preErrors.push('Departemen belum dipilih.');
  if (!formPayload.po_anggaran_id) preErrors.push('PO Anggaran belum dipilih.');
  if (!formPayload.metode_pembayaran) preErrors.push('Metode Pembayaran belum dipilih.');
  if (formPayload.metode_pembayaran === 'Transfer') {
    if (!formPayload.bisnis_partner_id) preErrors.push('Bisnis Partner belum dipilih.');
    if (!formPayload.nama_rekening || !formPayload.no_rekening) preErrors.push('Informasi rekening belum lengkap.');
  }
  const items = Array.isArray(formPayload.items) ? formPayload.items : [];
  const hasPositiveRealisasi = items.some((it: any) => Number(it?.realisasi || 0) > 0);
  if (!hasPositiveRealisasi) preErrors.push('Detail pengeluaran belum diisi (realisasi masih kosong).');

  if (preErrors.length) {
    preErrors.forEach((msg) => addError(msg));
    return;
  }

  // Pre-validate kelengkapan dokumen wajib sebelum menyimpan draft & mengirim
  try {
    const missingDocs: string[] | undefined = docsRef.value?.getRequiredMissingDocs?.();
    if (Array.isArray(missingDocs) && missingDocs.length > 0) {
      addError(
        `Dokumen wajib belum lengkap: ${missingDocs.join(", ")}. Silakan upload dokumen terlebih dahulu sebelum mengirim.`
      );
      activeTab.value = 'docs';
      return;
    }
  } catch {}

  isSubmitting.value = true;
  try {
    // Bersihkan pesan sebelumnya agar validasi & sukses tidak numpuk
    try { clearAll(); } catch {}
    // Pastikan selalu ada draft terbaru sebelum kirim (silent agar pesan draft tidak tampil)
    await doSaveDraft(false);

    const id = props.realisasi.id;
    if (!id) {
      isSubmitting.value = false;
      return;
    }

    // Sinkronkan lagi untuk memastikan state terakhir
    try { await docsRef.value?.syncActiveStates(id); } catch {}
    try { await docsRef.value?.flushUploads(id); } catch {}

    let documentsActive: string[] | undefined;
    try {
      const actives = docsRef.value?.getActiveDocKeys?.();
      if (Array.isArray(actives)) documentsActive = actives as any;
    } catch {}

    const response = await axios.post(
      '/realisasi/send',
      { ids: [id], documents_active: documentsActive },
      { withCredentials: true }
    );

    const data = response?.data;
    if (data && data.success) {
      // Pesan sukses tunggal saat Realisasi dikirim
      addSuccess('Realisasi berhasil dikirim');
      router.visit('/realisasi');
    } else {
      let msg: string = data?.message || 'Gagal mengirim Realisasi.';
      try {
        const failed = Array.isArray(data?.failed) ? data.failed : [];
        const first = failed.length ? failed[0] : null;
        if (first && Array.isArray(first.errors) && first.errors.length) {
          msg = `${msg} (${first.errors.join('; ')})`;
        }
      } catch {}
      addError(msg);
    }
  } catch (e: any) {
    const data = e?.response?.data;
    const msg = data?.message || data?.error || e?.message || 'Terjadi kesalahan saat mengirim Realisasi';
    addError(msg);
  } finally {
    isSubmitting.value = false;
  }
}

async function triggerSaveDraft() {
  if (!lastFormPayload.value) {
    const snapshot = formRef.value?.getFormSnapshot?.();
    if (!snapshot) {
      activeTab.value = 'form';
      return;
    }
    rememberForm({ form: snapshot });
  }
  const ok = await doSaveDraft();
  if (ok) {
    router.visit('/realisasi');
  }
}

function triggerSend() {
  if (isSubmitting.value) return;
  if (!lastFormPayload.value) {
    const snapshot = formRef.value?.getFormSnapshot?.();
    if (!snapshot) {
      activeTab.value = 'form';
      return;
    }
    rememberForm({ form: snapshot });
  }
  confirmAction.value = 'send';
  showConfirmDialog.value = true;
}

function handleConfirm() {
  if (confirmAction.value === 'send') {
    showConfirmDialog.value = false;
    doSend();
  } else if (confirmAction.value === 'cancel') {
    showConfirmDialog.value = false;
    router.visit('/realisasi');
  } else {
    showConfirmDialog.value = false;
  }
}
</script>
