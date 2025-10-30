<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useAlertDialog } from "@/composables/useAlertDialog";
import { Eye, Download, Trash2 } from "lucide-vue-next";

type DocKey = "bukti_transfer_bca" | "bukti_input_bca" | "invoice" | "surat_jalan" | "efaktur" | "lainnya";

type DocItem = {
  key: DocKey;
  label: string;
  required: boolean;
  active: boolean;
  file?: File | null;
  url?: string | null;
  uploadedFileName?: string | null;
  uploadStatus?: "uploading" | "success" | "error" | null;
};

const props = defineProps<{ pvId?: number | string | null }>();
const localPvId = ref<number | string | null>(
  props.pvId ??
    ((usePage().props as any).id || (usePage().props as any).paymentVoucher?.id)
);

watch(
  () => props.pvId,
  (v) => {
    if (v) localPvId.value = v;
    hydrateFromServer();
  }
);

// Hydrate docs state from server-provided paymentVoucher.documents on Edit page
function hydrateFromServer() {
  try {
    const page: any = usePage().props as any;
    const pv: any = page?.paymentVoucher;
    if (!pv || !pv.id) return;
    if (String(localPvId.value || "") !== String(pv.id || "")) return;
    const serverDocs: any[] = Array.isArray(pv.documents) ? pv.documents : [];
    // Reset items first
    for (const item of docs.value) {
      const match = serverDocs.find((d: any) => String(d.type) === String(item.key));
      if (match) {
        item.active = !!match.active;
        item.uploadedFileName = match.original_name || null;
        item.file = null; // prefer server file info
        item.url = match.id ? `/payment-voucher/documents/${match.id}/download` : null;
        item.uploadStatus = item.uploadedFileName ? "success" : null;
      } else {
        // No server doc -> clear
        item.uploadedFileName = null;
        item.file = null;
        item.url = null;
        item.uploadStatus = null;
      }
    }
    // If PV is Approved, default 'Bukti Transfer BCA' to active (checked)
    if ((pv.status || '').toLowerCase() === 'approved') {
      const btb = docs.value.find((d) => d.key === 'bukti_transfer_bca');
      if (btb) btb.active = true;
    }
  } catch {}
}

onMounted(() => {
  hydrateFromServer();
});

watch(
  () => usePage().props,
  () => {
    hydrateFromServer();
  }
);

async function ensurePvId(): Promise<number | string | null> {
  // Do not auto-create draft here. Only return existing pvId if any.
  return localPvId.value || null;
}
const { showError, showWarning } = useAlertDialog();

const docs = ref<DocItem[]>([
  {
    key: "bukti_transfer_bca",
    label: "Bukti Transfer BCA",
    required: true,
    active: false,
  },
  {
    key: "bukti_input_bca",
    label: "Bukti Input BCA",
    required: true,
    active: true,
  },
  { key: "invoice", label: "Invoice/Nota/Faktur", required: true, active: true },
  { key: "surat_jalan", label: "Surat Jalan", required: true, active: true },
  { key: "efaktur", label: "E-Faktur Pajak", required: true, active: true },
  { key: "lainnya", label: "Lainnya", required: false, active: true },
]);

// State untuk tracking drag status
const dragStates = ref<Record<DocKey, boolean>>({
  bukti_transfer_bca: false,
  bukti_input_bca: false,
  invoice: false,
  surat_jalan: false,
  efaktur: false,
  lainnya: false,
});


// Progress value currently unused by the UI; remove to satisfy linter

function validateFile(file: File): string | null {
  if (file.type !== "application/pdf") return "File harus PDF";
  if (file.size > 10 * 1024 * 1024) return "Maksimal 10MB";
  return null;
}

function canUpload(): boolean {
  return true;
}

async function onFileChange(key: DocKey, e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  const err = validateFile(file);
  if (err) {
    showError(err, "Format File Salah");
    input.value = "";
    return;
  }
  if (!canUpload()) {
    showWarning("Upload harus berurutan", "Peringatan Upload");
    input.value = "";
    return;
  }
  const item = docs.value.find((d) => d.key === key)!;
  if (!item.active) {
    showWarning("Form dokumen non-active", "Peringatan");
    input.value = "";
    return;
  }

  // Queue the file locally first
  item.file = file;
  item.uploadedFileName = file.name;
  item.uploadStatus = null; // not uploaded yet if no pvId

  const targetId = await ensurePvId();
  if (targetId) {
    // If pvId already exists, upload immediately
    await uploadQueuedItem(key, targetId, input);
  }
}

function previewDocument(key: DocKey) {
  const item = docs.value.find((d) => d.key === key);
  if (!item) return;
  // Prioritaskan file lokal yang baru diupload
  if (item.file) {
    const blobUrl = URL.createObjectURL(item.file);
    window.open(blobUrl, "_blank");
    // Tidak memanggil URL.revokeObjectURL agar tab baru tetap bisa membaca blob
    return;
  }
  if (item.url) {
    window.open(item.url, "_blank");
  }
}

function removeDocument(key: DocKey) {
  const item = docs.value.find((d) => d.key === key);
  if (!item) return;
  item.file = null;
  item.uploadedFileName = null;
  item.uploadStatus = null;
  // reset input file agar bisa memilih file yang sama lagi jika diperlukan
  const inputEl = document.getElementById(key) as HTMLInputElement | null;
  if (inputEl) inputEl.value = "";
}

// Progress indicator removed for now

function isActive(key: DocKey) {
  return !!docs.value.find((d) => d.key === key)?.active;
}

async function setActive(key: DocKey, val: boolean) {
  const item = docs.value.find((d) => d.key === key);
  if (!item) return;
  item.active = val;
  if (!val) {
    // Clear any existing file/status when deactivated
    item.file = null;
    item.uploadedFileName = null;
    item.uploadStatus = null;
    const inputEl = document.getElementById(key) as HTMLInputElement | null;
    if (inputEl) inputEl.value = "";
  }

  // Persist to backend if PV exists
  const targetId = localPvId.value;
  if (targetId) {
    router.post(
      `/payment-voucher/${targetId}/documents/set-active`,
      { type: key, active: val },
      { preserveScroll: true }
    );
  }
}

// Progress helper functions removed for now

function getDocItem(key: DocKey) {
  return docs.value.find((d) => d.key === key);
}

function getUploadStatusText(key: DocKey) {
  const item = getDocItem(key);
  if (!item) return null;

  switch (item.uploadStatus) {
    case "uploading":
      return "Mengupload...";
    case "success":
      return "Berhasil diupload";
    case "error":
      return "Upload gagal";
    default:
      return null;
  }
}

// Drag and Drop handlers
function handleDragEnter(key: DocKey, e: DragEvent) {
  e.preventDefault();
  e.stopPropagation();
  if (canUpload()) {
    dragStates.value[key] = true;
  }
}

function handleDragOver(key: DocKey, _e: DragEvent) {
  _e.preventDefault();
  _e.stopPropagation();
}

function handleDragLeave(key: DocKey, _e: DragEvent) {
  _e.preventDefault();
  _e.stopPropagation();
  dragStates.value[key] = false;
}

function handleDrop(key: DocKey, e: DragEvent) {
  e.preventDefault();
  e.stopPropagation();
  dragStates.value[key] = false;

  if (!canUpload()) {
    showWarning("Upload harus berurutan", "Peringatan Upload");
    return;
  }

  const item = docs.value.find((d) => d.key === key);
  if (!item?.active) {
    showWarning("Form dokumen non-active", "Peringatan");
    return;
  }

  const files = e.dataTransfer?.files;
  if (!files || files.length === 0) return;

  const file = files[0];
  const err = validateFile(file);
  if (err) {
    showError(err, "Format File Salah");
    return;
  }

  // Simulasi event untuk menggunakan fungsi onFileChange yang sudah ada
  const mockEvent = ({
    target: {
      files: [file],
    },
  } as unknown) as Event;

  onFileChange(key, mockEvent);
}

// Helper to upload a single queued item
async function uploadQueuedItem(key: DocKey, pvId: number | string, inputEl?: HTMLInputElement | null) {
  const item = docs.value.find((d) => d.key === key);
  if (!item || !item.file) return;
  item.uploadStatus = "uploading";
  const form = new FormData();
  form.append("type", key);
  form.append("file", item.file);
  await new Promise<void>((resolve) => {
    router.post(`/payment-voucher/${pvId}/documents`, form as any, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        item.uploadStatus = "success";
        resolve();
      },
      onError: () => {
        item.uploadStatus = "error";
        item.uploadedFileName = null;
        item.file = null;
        try { if (inputEl) inputEl.value = ""; } catch {}
        resolve();
      },
    });
  });
}

// Expose a method so parent can flush all queued uploads after pvId exists
async function flushUploads(explicitPvId?: number | string | null) {
  const targetId = explicitPvId ?? localPvId.value;
  if (!targetId) return;
  for (const d of docs.value) {
    if (d.file && d.uploadStatus !== "success") {
      await uploadQueuedItem(d.key, targetId, document.getElementById(d.key) as HTMLInputElement | null);
    }
  }
}

defineExpose({ flushUploads });
</script>

<template>
  <div class="min-h-screen p-6">
    <!-- Progress Stepper (temporarily disabled)
    <div class="mb-8 overflow-x-auto">
      <div class="flex items-start min-w-max gap-0">
        <div
          v-for="(step, index) in getVisibleSteps()"
          :key="step.key"
          class="flex flex-col items-center relative"
          :style="{ marginRight: index < getVisibleSteps().length - 1 ? '80px' : '0' }"
        >
          <div
            class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium relative z-10"
            :class="{
              'bg-blue-600 text-white': getStepStatus(step.key as DocKey) === 'completed',
              'bg-white border-2 border-blue-600 text-blue-600': getStepStatus(step.key as DocKey) === 'current',
              'bg-white border-2 border-gray-300 text-gray-400': getStepStatus(step.key as DocKey) === 'pending'
            }"
          >
            <span v-if="getStepStatus(step.key as DocKey) === 'completed'">✓</span>
            <span
              v-else-if="getStepStatus(step.key as DocKey) === 'current'"
              class="w-2.5 h-2.5 bg-blue-600 rounded-full"
            ></span>
            <span v-else class="w-2.5 h-2.5 bg-gray-300 rounded-full"></span>
          </div>

          <div
            class="mt-2 text-xs text-center text-gray-600 max-w-[100px] whitespace-normal leading-tight"
          >
            {{ step.label }}
          </div>

          <div
            v-if="index < getVisibleSteps().length - 1"
            class="absolute top-4 h-0.5"
            :class="{
              'bg-blue-600': getStepStatus(step.key as DocKey) === 'completed',
              'bg-gray-300': getStepStatus(step.key as DocKey) !== 'completed'
            }"
            style="left: calc(50% + 16px); width: 150px"
          ></div>
        </div>
      </div>
    </div>
    -->

    <!-- Document Forms -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Bukti Transfer BCA -->
      <div
        class="bg-white rounded-lg border border-gray-200 p-6 relative order-5"
        :class="{ 'opacity-60': !isActive('bukti_transfer_bca') }"
      >
        <div class="absolute top-4 right-4 flex items-center gap-2">
          <input
            type="checkbox"
            class="h-4 w-4"
            :checked="isActive('bukti_transfer_bca')"
            @change="(e:any) => setActive('bukti_transfer_bca', e.target.checked)"
          />
        </div>
        <h3 class="font-medium text-gray-900 mb-1">
          Bukti Transfer BCA <span class="text-red-500">*</span>
        </h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div
            class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
            :class="{
              'border-blue-400 bg-blue-50': dragStates.bukti_transfer_bca,
              'border-gray-300': !dragStates.bukti_transfer_bca,
            }"
            @dragenter="(e) => handleDragEnter('bukti_transfer_bca', e)"
            @dragover="(e) => handleDragOver('bukti_transfer_bca', e)"
            @dragleave="(e) => handleDragLeave('bukti_transfer_bca', e)"
            @drop="(e) => handleDrop('bukti_transfer_bca', e)"
          >
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="bukti_transfer_bca"
              :disabled="
                !docs.find((d) => d.key === 'bukti_transfer_bca')?.active ||
                !canUpload()
              "
              @change="(e) => onFileChange('bukti_transfer_bca', e)"
            />
            <label for="bukti_transfer_bca" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">
                {{
                  dragStates.bukti_transfer_bca
                    ? "Lepaskan file di sini"
                    : "Pilih Berkas atau Drag & Drop"
                }}
              </div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Bawa berkas ke area ini (maks. 10 MB)</span>
            </div>
            <!-- Tampilkan file yang sudah di-upload -->
            <div
              v-if="getDocItem('bukti_transfer_bca')?.uploadedFileName"
              class="flex items-center gap-2 mt-1"
            >
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => previewDocument('bukti_transfer_bca')"
                title="Lihat"
              >
                <Eye class="w-4 h-4" />
              </button>
              <a
                v-if="getDocItem('bukti_transfer_bca')?.url"
                :href="getDocItem('bukti_transfer_bca')?.url || '#'"
                target="_blank"
                download
                class="text-gray-600 hover:text-blue-600"
                title="Download"
              >
                <Download class="w-4 h-4" />
              </a>
              <span class="text-blue-600">{{ getDocItem("bukti_transfer_bca")?.uploadedFileName }}</span>
              <span
                v-if="getDocItem('bukti_transfer_bca')?.uploadStatus === 'uploading'"
                class="text-yellow-500"
                >⏳</span
              >
              <span
                v-else-if="getDocItem('bukti_transfer_bca')?.uploadStatus === 'success'"
                class="text-green-500"
                >✓</span
              >
              <span
                v-else-if="getDocItem('bukti_transfer_bca')?.uploadStatus === 'error'"
                class="text-red-500"
                >✗</span
              >
              <button
                class="text-red-600 hover:text-red-700 ml-2"
                @click="() => removeDocument('bukti_transfer_bca')"
                title="Hapus"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <!-- Status upload -->
            <div v-if="getUploadStatusText('bukti_transfer_bca')" class="mt-1">
              <span
                :class="{
                  'text-yellow-600':
                    getDocItem('bukti_transfer_bca')?.uploadStatus === 'uploading',
                  'text-green-600':
                    getDocItem('bukti_transfer_bca')?.uploadStatus === 'success',
                  'text-red-600':
                    getDocItem('bukti_transfer_bca')?.uploadStatus === 'error',
                }"
              >
                {{ getUploadStatusText("bukti_transfer_bca") }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Bukti Input BCA -->
      <div
        class="bg-white rounded-lg border border-gray-200 p-6 relative order-1"
        :class="{ 'opacity-60': !isActive('bukti_input_bca') }"
      >
        <div class="absolute top-4 right-4 flex items-center gap-2">
          <input
            type="checkbox"
            class="h-4 w-4"
            :checked="isActive('bukti_input_bca')"
            @change="(e:any) => setActive('bukti_input_bca', e.target.checked)"
          />
        </div>
        <h3 class="font-medium text-gray-900 mb-1">
          Bukti Input BCA <span class="text-red-500">*</span>
        </h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div
            class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
            :class="{
              'border-blue-400 bg-blue-50': dragStates.bukti_input_bca,
              'border-gray-300': !dragStates.bukti_input_bca,
            }"
            @dragenter="(e) => handleDragEnter('bukti_input_bca', e)"
            @dragover="(e) => handleDragOver('bukti_input_bca', e)"
            @dragleave="(e) => handleDragLeave('bukti_input_bca', e)"
            @drop="(e) => handleDrop('bukti_input_bca', e)"
          >
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="bukti_input_bca"
              :disabled="
                !docs.find((d) => d.key === 'bukti_input_bca')?.active ||
                !canUpload()
              "
              @change="(e) => onFileChange('bukti_input_bca', e)"
            />
            <label for="bukti_input_bca" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">
                {{
                  dragStates.bukti_input_bca
                    ? "Lepaskan file di sini"
                    : "Pilih Berkas atau Drag & Drop"
                }}
              </div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Bawa berkas ke area ini (maks. 10 MB)</span>
            </div>
            <!-- Tampilkan file yang sudah di-upload -->
            <div
              v-if="getDocItem('bukti_input_bca')?.uploadedFileName"
              class="flex items-center gap-2 mt-1"
            >
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => previewDocument('bukti_input_bca')"
                title="Lihat"
              >
                <Eye class="w-4 h-4" />
              </button>
              <a
                v-if="getDocItem('bukti_input_bca')?.url"
                :href="getDocItem('bukti_input_bca')?.url || '#'"
                target="_blank"
                download
                class="text-gray-600 hover:text-blue-600"
                title="Download"
              >
                <Download class="w-4 h-4" />
              </a>
              <span class="text-blue-600">{{ getDocItem('bukti_input_bca')?.uploadedFileName }}</span>
              <span
                v-if="getDocItem('bukti_input_bca')?.uploadStatus === 'uploading'"
                class="text-yellow-500"
                >⏳</span
              >
              <span
                v-else-if="getDocItem('bukti_input_bca')?.uploadStatus === 'success'"
                class="text-green-500"
                >✓</span
              >
              <span
                v-else-if="getDocItem('bukti_input_bca')?.uploadStatus === 'error'"
                class="text-red-500"
                >✗</span
              >
              <button
                class="text-red-600 hover:text-red-700 ml-2"
                @click="() => removeDocument('bukti_input_bca')"
                title="Hapus"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <!-- Status upload -->
            <div v-if="getUploadStatusText('bukti_input_bca')" class="mt-1">
              <span
                :class="{
                  'text-yellow-600':
                    getDocItem('bukti_input_bca')?.uploadStatus === 'uploading',
                  'text-green-600':
                    getDocItem('bukti_input_bca')?.uploadStatus === 'success',
                  'text-red-600':
                    getDocItem('bukti_input_bca')?.uploadStatus === 'error',
                }"
              >
                {{ getUploadStatusText('bukti_input_bca') }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Invoice/Nota/Faktur -->
      <div
        class="bg-white rounded-lg border border-gray-200 p-6 relative order-2"
        :class="{ 'opacity-60': !isActive('invoice') }"
      >
        <div class="absolute top-4 right-4 flex items-center gap-2">
          <input
            type="checkbox"
            class="h-4 w-4"
            :checked="isActive('invoice')"
            @change="(e:any) => setActive('invoice', e.target.checked)"
          />
        </div>
        <h3 class="font-medium text-gray-900 mb-1">
          Invoice/Nota/Faktur <span class="text-red-500">*</span>
        </h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div
            class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
            :class="{
              'border-blue-400 bg-blue-50': dragStates.invoice,
              'border-gray-300': !dragStates.invoice,
            }"
            @dragenter="(e) => handleDragEnter('invoice', e)"
            @dragover="(e) => handleDragOver('invoice', e)"
            @dragleave="(e) => handleDragLeave('invoice', e)"
            @drop="(e) => handleDrop('invoice', e)"
          >
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="invoice"
              :disabled="
                !docs.find((d) => d.key === 'invoice')?.active || !canUpload()
              "
              @change="(e) => onFileChange('invoice', e)"
            />
            <label for="invoice" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">
                {{
                  dragStates.invoice
                    ? "Lepaskan file di sini"
                    : "Pilih Berkas atau Drag & Drop"
                }}
              </div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Bawa berkas ke area ini (maks. 10 MB)</span>
            </div>
            <!-- Tampilkan file yang sudah di-upload -->
            <div
              v-if="getDocItem('invoice')?.uploadedFileName"
              class="flex items-center gap-2 mt-1"
            >
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => previewDocument('invoice')"
                title="Lihat"
              >
                <Eye class="w-4 h-4" />
              </button>
              <a
                v-if="getDocItem('invoice')?.url"
                :href="getDocItem('invoice')?.url || '#'"
                target="_blank"
                download
                class="text-gray-600 hover:text-blue-600"
                title="Download"
              >
                <Download class="w-4 h-4" />
              </a>
              <span class="text-blue-600">{{ getDocItem("invoice")?.uploadedFileName }}</span>
              <span
                v-if="getDocItem('invoice')?.uploadStatus === 'uploading'"
                class="text-yellow-500"
                >⏳</span
              >
              <span
                v-else-if="getDocItem('invoice')?.uploadStatus === 'success'"
                class="text-green-500"
                >✓</span
              >
              <span
                v-else-if="getDocItem('invoice')?.uploadStatus === 'error'"
                class="text-red-500"
                >✗</span
              >
              <button
                class="text-red-600 hover:text-red-700 ml-2"
                @click="() => removeDocument('invoice')"
                title="Hapus"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <!-- Status upload -->
            <div v-if="getUploadStatusText('invoice')" class="mt-1">
              <span
                :class="{
                  'text-yellow-600': getDocItem('invoice')?.uploadStatus === 'uploading',
                  'text-green-600': getDocItem('invoice')?.uploadStatus === 'success',
                  'text-red-600': getDocItem('invoice')?.uploadStatus === 'error',
                }"
              >
                {{ getUploadStatusText("invoice") }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <!-- Surat Jalan -->
      <div
        class="bg-white rounded-lg border border-gray-200 p-6 md:col-start-1 relative order-3"
        :class="{ 'opacity-60': !isActive('surat_jalan') }"
      >
        <div class="absolute top-4 right-4 flex items-center gap-2">
          <input
            type="checkbox"
            class="h-4 w-4"
            :checked="isActive('surat_jalan')"
            @change="(e:any) => setActive('surat_jalan', e.target.checked)"
          />
        </div>
        <h3 class="font-medium text-gray-900 mb-1">
          Surat Jalan <span class="text-red-500">*</span>
        </h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div
            class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
            :class="{
              'border-blue-400 bg-blue-50': dragStates.surat_jalan,
              'border-gray-300': !dragStates.surat_jalan,
            }"
            @dragenter="(e) => handleDragEnter('surat_jalan', e)"
            @dragover="(e) => handleDragOver('surat_jalan', e)"
            @dragleave="(e) => handleDragLeave('surat_jalan', e)"
            @drop="(e) => handleDrop('surat_jalan', e)"
          >
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="surat_jalan"
              :disabled="
                !docs.find((d) => d.key === 'surat_jalan')?.active ||
                !canUpload()
              "
              @change="(e) => onFileChange('surat_jalan', e)"
            />
            <label for="surat_jalan" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">
                {{
                  dragStates.surat_jalan
                    ? "Lepaskan file di sini"
                    : "Pilih Berkas atau Drag & Drop"
                }}
              </div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Bawa berkas ke area ini (maks. 10 MB)</span>
            </div>
            <!-- Tampilkan file yang sudah di-upload -->
            <div
              v-if="getDocItem('surat_jalan')?.uploadedFileName"
              class="flex items-center gap-2 mt-1"
            >
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => previewDocument('surat_jalan')"
                title="Lihat"
              >
                <Eye class="w-4 h-4" />
              </button>
              <a
                v-if="getDocItem('surat_jalan')?.url"
                :href="getDocItem('surat_jalan')?.url || '#'"
                target="_blank"
                download
                class="text-gray-600 hover:text-blue-600"
                title="Download"
              >
                <Download class="w-4 h-4" />
              </a>
              <span class="text-blue-600">{{ getDocItem("surat_jalan")?.uploadedFileName }}</span>
              <span
                v-if="getDocItem('surat_jalan')?.uploadStatus === 'uploading'"
                class="text-yellow-500"
                >⏳</span
              >
              <span
                v-else-if="getDocItem('surat_jalan')?.uploadStatus === 'success'"
                class="text-green-500"
                >✓</span
              >
              <span
                v-else-if="getDocItem('surat_jalan')?.uploadStatus === 'error'"
                class="text-red-500"
                >✗</span
              >
              <button
                class="text-red-600 hover:text-red-700 ml-2"
                @click="() => removeDocument('surat_jalan')"
                title="Hapus"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <!-- Status upload -->
            <div v-if="getUploadStatusText('surat_jalan')" class="mt-1">
              <span
                :class="{
                  'text-yellow-600':
                    getDocItem('surat_jalan')?.uploadStatus === 'uploading',
                  'text-green-600': getDocItem('surat_jalan')?.uploadStatus === 'success',
                  'text-red-600': getDocItem('surat_jalan')?.uploadStatus === 'error',
                }"
              >
                {{ getUploadStatusText("surat_jalan") }}
              </span>
            </div>
          </div>
        </div>
      </div>


      <!-- E-Faktur Pajak -->
      <div
        class="bg-white rounded-lg border border-gray-200 p-6 relative order-4"
        :class="{ 'opacity-60': !isActive('efaktur') }"
      >
        <div class="absolute top-4 right-4 flex items-center gap-2">
          <input
            type="checkbox"
            class="h-4 w-4"
            :checked="isActive('efaktur')"
            @change="(e:any) => setActive('efaktur', e.target.checked)"
          />
        </div>
        <h3 class="font-medium text-gray-900 mb-1">
          E-Faktur Pajak <span class="text-red-500">*</span>
        </h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div
            class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
            :class="{
              'border-blue-400 bg-blue-50': dragStates.efaktur,
              'border-gray-300': !dragStates.efaktur,
            }"
            @dragenter="(e) => handleDragEnter('efaktur', e)"
            @dragover="(e) => handleDragOver('efaktur', e)"
            @dragleave="(e) => handleDragLeave('efaktur', e)"
            @drop="(e) => handleDrop('efaktur', e)"
          >
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="efaktur"
              :disabled="
                !docs.find((d) => d.key === 'efaktur')?.active ||
                !canUpload()
              "
              @change="(e) => onFileChange('efaktur', e)"
            />
            <label for="efaktur" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">
                {{
                  dragStates.efaktur
                    ? "Lepaskan file di sini"
                    : "Pilih Berkas atau Drag & Drop"
                }}
              </div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Bawa berkas ke area ini (maks. 10 MB)</span>
            </div>
            <!-- Tampilkan file yang sudah di-upload -->
            <div
              v-if="getDocItem('efaktur')?.uploadedFileName"
              class="flex items-center gap-2 mt-1"
            >
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => previewDocument('efaktur')"
                title="Lihat"
              >
                <Eye class="w-4 h-4" />
              </button>
              <a
                v-if="getDocItem('efaktur')?.url"
                :href="getDocItem('efaktur')?.url || '#'"
                target="_blank"
                download
                class="text-gray-600 hover:text-blue-600"
                title="Download"
              >
                <Download class="w-4 h-4" />
              </a>
              <span class="text-blue-600">{{ getDocItem("efaktur")?.uploadedFileName }}</span>
              <span
                v-if="getDocItem('efaktur')?.uploadStatus === 'uploading'"
                class="text-yellow-500"
                >⏳</span
              >
              <span
                v-else-if="getDocItem('efaktur')?.uploadStatus === 'success'"
                class="text-green-500"
                >✓</span
              >
              <span
                v-else-if="getDocItem('efaktur')?.uploadStatus === 'error'"
                class="text-red-500"
                >✗</span
              >
              <button
                class="text-red-600 hover:text-red-700 ml-2"
                @click="() => removeDocument('efaktur')"
                title="Hapus"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <!-- Status upload -->
            <div v-if="getUploadStatusText('efaktur')" class="mt-1">
              <span
                :class="{
                  'text-yellow-600': getDocItem('efaktur')?.uploadStatus === 'uploading',
                  'text-green-600': getDocItem('efaktur')?.uploadStatus === 'success',
                  'text-red-600': getDocItem('efaktur')?.uploadStatus === 'error',
                }"
              >
                {{ getUploadStatusText("efaktur") }}
              </span>
            </div>
          </div>
        </div>
      </div>


    <!-- Lainnya -->
      <div
        class="bg-white rounded-lg border border-gray-200 p-6 relative order-6"
        :class="{ 'opacity-60': !isActive('lainnya') }"
      >
        <div class="absolute top-4 right-4 flex items-center gap-2">
          <input
            type="checkbox"
            class="h-4 w-4"
            :checked="isActive('lainnya')"
            @change="(e:any) => setActive('lainnya', e.target.checked)"
          />
        </div>
        <h3 class="font-medium text-gray-900 mb-1">Lainnya</h3>
        <p class="text-xs text-gray-500 mb-4">Opsional</p>

        <div class="space-y-4">
          <div
            class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
            :class="{
              'border-blue-400 bg-blue-50': dragStates.lainnya,
              'border-gray-300': !dragStates.lainnya,
            }"
            @dragenter="(e) => handleDragEnter('lainnya', e)"
            @dragover="(e) => handleDragOver('lainnya', e)"
            @dragleave="(e) => handleDragLeave('lainnya', e)"
            @drop="(e) => handleDrop('lainnya', e)"
          >
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="lainnya"
              :disabled="
                !docs.find((d) => d.key === 'lainnya')?.active || !canUpload()
              "
              @change="(e) => onFileChange('lainnya', e)"
            />
            <label for="lainnya" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">
                {{
                  dragStates.lainnya
                    ? "Lepaskan file di sini"
                    : "Pilih Berkas atau Drag & Drop"
                }}
              </div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Bawa berkas ke area ini (maks. 10 MB)</span>
            </div>
            <!-- Tampilkan file yang sudah di-upload -->
            <div
              v-if="getDocItem('lainnya')?.uploadedFileName"
              class="flex items-center gap-2 mt-1"
            >
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => previewDocument('lainnya')"
                title="Lihat"
              >
                <Eye class="w-4 h-4" />
              </button>
              <a
                v-if="getDocItem('lainnya')?.url"
                :href="getDocItem('lainnya')?.url || '#'"
                target="_blank"
                download
                class="text-gray-600 hover:text-blue-600"
                title="Download"
              >
                <Download class="w-4 h-4" />
              </a>
              <span class="text-blue-600">{{ getDocItem("lainnya")?.uploadedFileName }}</span>
              <span
                v-if="getDocItem('lainnya')?.uploadStatus === 'uploading'"
                class="text-yellow-500"
                >⏳</span
              >
              <span
                v-else-if="getDocItem('lainnya')?.uploadStatus === 'success'"
                class="text-green-500"
                >✓</span
              >
              <span
                v-else-if="getDocItem('lainnya')?.uploadStatus === 'error'"
                class="text-red-500"
                >✗</span
              >
              <button
                class="text-red-600 hover:text-red-700 ml-2"
                @click="() => removeDocument('lainnya')"
                title="Hapus"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>
            <!-- Status upload -->
            <div v-if="getUploadStatusText('lainnya')" class="mt-1">
              <span
                :class="{
                  'text-yellow-600': getDocItem('lainnya')?.uploadStatus === 'uploading',
                  'text-green-600': getDocItem('lainnya')?.uploadStatus === 'success',
                  'text-red-600': getDocItem('lainnya')?.uploadStatus === 'error',
                }"
              >
                {{ getUploadStatusText("lainnya") }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
input[type="file"]:disabled + label {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
