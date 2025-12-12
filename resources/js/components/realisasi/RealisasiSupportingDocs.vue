<script setup lang="ts">
import { ref, watch, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { useAlertDialog } from "@/composables/useAlertDialog";
import { Eye, Download, Trash2 } from "lucide-vue-next";

type DocKey = "transport" | "konsumsi" | "hotel" | "uang_saku" | "lainnya";

type DocItem = {
  key: DocKey;
  label: string;
  required: boolean;
  active: boolean;
  file?: File | null;
  url?: string | null;
  docId?: number | null;
  uploadedFileName?: string | null;
  uploadStatus?: "uploading" | "success" | "error" | null;
};

const props = defineProps<{ realisasiId?: number | string | null }>();
const localRealisasiId = ref<number | string | null>(
  props.realisasiId ??
    ((usePage().props as any).realisasi?.id || (usePage().props as any).id)
);

watch(
  () => props.realisasiId,
  (v) => {
    if (v) localRealisasiId.value = v;
    hydrateFromServer();
  }
);

const docs = ref<DocItem[]>([
  { key: "transport", label: "Bukti Transport", required: true, active: true },
  { key: "konsumsi", label: "Bukti Konsumsi", required: true, active: true },
  { key: "hotel", label: "Bukti Hotel", required: true, active: true },
  { key: "uang_saku", label: "Bukti Uang Saku", required: true, active: true },
  { key: "lainnya", label: "Lampiran Lainnya", required: false, active: true },
]);

const dragStates = ref<Record<DocKey, boolean>>({
  transport: false,
  konsumsi: false,
  hotel: false,
  uang_saku: false,
  lainnya: false,
});

const { showError, showWarning } = useAlertDialog();

function hydrateFromServer() {
  try {
    const page: any = usePage().props as any;
    const rls: any = page?.realisasi;
    if (!rls || !rls.id) return;
    if (String(localRealisasiId.value || "") !== String(rls.id || "")) return;
    const serverDocs: any[] = Array.isArray(rls.documents) ? rls.documents : [];

    for (const item of docs.value) {
      const match = serverDocs.find((d: any) => String(d.type) === String(item.key));
      if (match) {
        item.active = !!match.active;
        item.docId = match.id ?? null;
        item.uploadedFileName = match.original_name || null;
        item.file = null;
        item.url = match.id ? `/realisasi/documents/${match.id}/download` : null;
        item.uploadStatus = item.uploadedFileName ? "success" : null;
      } else {
        item.active = false;
        item.docId = null;
        item.uploadedFileName = null;
        item.file = null;
        item.url = null;
        item.uploadStatus = null;
      }
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
  const item = docs.value.find((d) => d.key === key)!;
  if (!item.active) {
    showWarning("Form dokumen non-active", "Peringatan");
    input.value = "";
    return;
  }

  item.file = file;
  item.uploadedFileName = file.name;
  item.uploadStatus = null;

  const targetId = localRealisasiId.value;
  if (targetId) {
    await uploadQueuedItem(key, targetId, input);
  }
}

function previewDocument(key: DocKey) {
  const item = docs.value.find((d) => d.key === key);
  if (!item) return;
  if (item.file) {
    const blobUrl = URL.createObjectURL(item.file);
    window.open(blobUrl, "_blank");
    return;
  }
  if (item.docId) {
    const viewUrl = `/realisasi/documents/${item.docId}/view`;
    window.open(viewUrl, "_blank");
    return;
  }
  if (item.url) {
    window.open(item.url, "_blank");
  }
}

function downloadDocument(key: DocKey) {
  const item = docs.value.find((d) => d.key === key);
  if (!item) return;

  if (item.file) {
    try {
      const blobUrl = URL.createObjectURL(item.file);
      const a = document.createElement("a");
      a.href = blobUrl;
      a.download = item.uploadedFileName || "document.pdf";
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      return;
    } catch {}
  }

  if (item.docId) {
    const downloadUrl = `/realisasi/documents/${item.docId}/download`;
    window.open(downloadUrl, "_blank");
    return;
  }

  if (item.url) {
    window.open(item.url, "_blank");
  }
}

function removeDocument(key: DocKey) {
  const item = docs.value.find((d) => d.key === key);
  if (!item) return;

  const serverDocId = item.docId;
  if (serverDocId) {
    router.delete(`/realisasi/documents/${serverDocId}` as any, {
      preserveScroll: true,
      onFinish: () => {
        item.file = null;
        item.uploadedFileName = null;
        item.uploadStatus = null;
        item.url = null;
        item.docId = null;
        const inputEl = document.getElementById(key) as HTMLInputElement | null;
        if (inputEl) inputEl.value = "";
      },
    });
    return;
  }

  item.file = null;
  item.uploadedFileName = null;
  item.uploadStatus = null;
  item.url = null;
  item.docId = null;
  const inputEl = document.getElementById(key) as HTMLInputElement | null;
  if (inputEl) inputEl.value = "";
}

async function setActive(key: DocKey, val: boolean) {
  const item = docs.value.find((d) => d.key === key);
  if (!item) return;
  item.active = val;
  if (!val) {
    item.file = null;
    item.uploadedFileName = null;
    item.uploadStatus = null;
    const inputEl = document.getElementById(key) as HTMLInputElement | null;
    if (inputEl) inputEl.value = "";
  }

  const targetId = localRealisasiId.value;
  if (targetId) {
    router.post(
      `/realisasi/${targetId}/documents/set-active`,
      { type: key, active: val },
      { preserveScroll: true }
    );
  }
}

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

function handleDragEnter(key: DocKey, e: DragEvent) {
  e.preventDefault();
  e.stopPropagation();
  if (canUpload()) {
    dragStates.value[key] = true;
  }
}

function handleDragOver(key: DocKey, e: DragEvent) {
  e.preventDefault();
  e.stopPropagation();
}

function handleDragLeave(key: DocKey, e: DragEvent) {
  e.preventDefault();
  e.stopPropagation();
  dragStates.value[key] = false;
}

function handleDrop(key: DocKey, e: DragEvent) {
  e.preventDefault();
  e.stopPropagation();
  dragStates.value[key] = false;

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

  const mockEvent = ({
    target: {
      files: [file],
    },
  } as unknown) as Event;

  onFileChange(key, mockEvent);
}

async function uploadQueuedItem(key: DocKey, realisasiId: number | string, inputEl?: HTMLInputElement | null) {
  const item = docs.value.find((d) => d.key === key);
  if (!item || !item.file) return;
  item.uploadStatus = "uploading";
  const form = new FormData();
  form.append("type", key);
  form.append("file", item.file);
  await new Promise<void>((resolve) => {
    router.post(`/realisasi/${realisasiId}/documents`, form as any, {
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
        try {
          if (inputEl) inputEl.value = "";
        } catch {}
        resolve();
      },
    });
  });
}

async function flushUploads(explicitId?: number | string | null) {
  const targetId = explicitId ?? localRealisasiId.value;
  if (!targetId) return;
  for (const d of docs.value) {
    if (d.file && d.uploadStatus !== "success") {
      await uploadQueuedItem(d.key, targetId, document.getElementById(d.key) as HTMLInputElement | null);
    }
  }
}

function getActiveDocKeys() {
  return docs.value.filter((d) => !!d.active).map((d) => d.key);
}

async function syncActiveStates(explicitId?: number | string | null) {
  const targetId = explicitId ?? localRealisasiId.value;
  if (!targetId) return;
  for (const item of docs.value) {
    try {
      await new Promise<void>((resolve) => {
        router.post(
          `/realisasi/${targetId}/documents/set-active`,
          { type: item.key, active: !!item.active } as any,
          { preserveScroll: true, onFinish: () => resolve() }
        );
      });
    } catch {}
  }
}

defineExpose({ flushUploads, getActiveDocKeys, syncActiveStates });
</script>

<template>
  <div class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div
        v-for="item in docs"
        :key="item.key"
        class="bg-white rounded-lg border border-gray-200 p-6 relative"
        :class="{ 'opacity-60': !item.active }"
      >
        <div class="absolute top-4 right-4 flex items-center gap-2">
          <input
            type="checkbox"
            class="h-4 w-4"
            :checked="item.active"
            @change="(e: any) => setActive(item.key, e.target.checked)"
          />
        </div>
        <h3 class="font-medium text-gray-900 mb-1">
          {{ item.label }}
          <span v-if="item.required" class="text-red-500">*</span>
        </h3>
        <p class="text-xs text-gray-500 mb-4">
          {{ item.required ? 'Wajib' : 'Opsional' }}
        </p>

        <div class="space-y-4">
          <div
            class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
            :class="{
              'border-blue-400 bg-blue-50': dragStates[item.key],
              'border-gray-300': !dragStates[item.key],
            }"
            @dragenter="(e) => handleDragEnter(item.key, e)"
            @dragover="(e) => handleDragOver(item.key, e)"
            @dragleave="(e) => handleDragLeave(item.key, e)"
            @drop="(e) => handleDrop(item.key, e)"
          >
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              :id="item.key"
              :disabled="!item.active || !canUpload()"
              @change="(e) => onFileChange(item.key, e)"
            />
            <label :for="item.key" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">
                {{
                  dragStates[item.key]
                    ? 'Lepaskan file di sini'
                    : 'Pilih Berkas atau Drag & Drop'
                }}
              </div>
            </label>
          </div>

          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Bawa berkas ke area ini (maks. 10 MB, PDF)</span>
            </div>

            <div
              v-if="item.uploadedFileName"
              class="flex items-center gap-2 mt-1"
            >
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => previewDocument(item.key)"
                title="Lihat"
              >
                <Eye class="w-4 h-4" />
              </button>
              <button
                class="text-gray-600 hover:text-blue-600"
                @click="() => downloadDocument(item.key)"
                title="Download"
              >
                <Download class="w-4 h-4" />
              </button>
              <span class="text-blue-600">{{ item.uploadedFileName }}</span>
              <span
                v-if="item.uploadStatus === 'uploading'"
                class="text-yellow-500"
                >⏳</span
              >
              <span
                v-else-if="item.uploadStatus === 'success'"
                class="text-green-500"
                >✓</span
              >
              <span
                v-else-if="item.uploadStatus === 'error'"
                class="text-red-500"
                >✗</span
              >
              <button
                class="text-red-600 hover:text-red-700 ml-2"
                @click="() => removeDocument(item.key)"
                title="Hapus"
              >
                <Trash2 class="w-4 h-4" />
              </button>
            </div>

            <div v-if="getUploadStatusText(item.key)" class="mt-1">
              <span
                :class="{
                  'text-yellow-600': item.uploadStatus === 'uploading',
                  'text-green-600': item.uploadStatus === 'success',
                  'text-red-600': item.uploadStatus === 'error',
                }"
              >
                {{ getUploadStatusText(item.key) }}
              </span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
