<script setup lang="ts">
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";

type DocKey = "bukti_transfer_bca" | "invoice" | "surat_jalan" | "efaktur" | "lainnya";

type DocItem = {
  key: DocKey;
  label: string;
  required: boolean;
  active: boolean;
  file?: File | null;
  url?: string | null;
};

const pvId = (usePage().props as any).id || (usePage().props as any).paymentVoucher?.id;

const docs = ref<DocItem[]>([
  {
    key: "bukti_transfer_bca",
    label: "Bukti Transfer BCA",
    required: true,
    active: true,
  },
  { key: "invoice", label: "Invoice/Nota/Faktur", required: true, active: true },
  { key: "surat_jalan", label: "Surat Jalan", required: true, active: true },
  { key: "efaktur", label: "E-Faktur Pajak", required: true, active: true },
  { key: "lainnya", label: "Lainnya", required: false, active: true },
]);

const uploadOrder: DocKey[] = [
  "bukti_transfer_bca",
  "invoice",
  "surat_jalan",
  "efaktur",
  "lainnya",
];

// Progress value currently unused by the UI; remove to satisfy linter

function validateFile(file: File): string | null {
  if (file.type !== "application/pdf") return "File harus PDF";
  if (file.size > 10 * 1024 * 1024) return "Maksimal 10MB";
  return null;
}

function canUpload(key: DocKey): boolean {
  const index = uploadOrder.indexOf(key);
  for (let i = 0; i < index; i++) {
    const k = uploadOrder[i];
    const item = docs.value.find((d) => d.key === k)!;
    if (item.required && !item.file) return false;
  }
  return true;
}

function onFileChange(key: DocKey, e: Event) {
  const input = e.target as HTMLInputElement;
  const file = input.files?.[0];
  if (!file) return;
  const err = validateFile(file);
  if (err) {
    alert(err);
    input.value = "";
    return;
  }
  if (!canUpload(key)) {
    alert("Upload harus berurutan");
    input.value = "";
    return;
  }
  const item = docs.value.find((d) => d.key === key)!;
  if (!item.active) {
    alert("Form dokumen non-active");
    input.value = "";
    return;
  }
  item.file = file;
  if (pvId) {
    const form = new FormData();
    form.append("type", key);
    form.append("file", file);
    router.post(`/payment-voucher/${pvId}/documents`, form as any, {
      forceFormData: true,
      preserveScroll: true,
    });
  }
}

// Actions (toggle/view/download/delete) are not used in this UI version; removed to satisfy linter

// Steps for progress indicator
const steps = [
  { key: "bukti_transfer_bca", label: "Bukti Transfer BCA" },
  { key: "invoice", label: "Invoice/Nota/Faktur" },
  { key: "surat_jalan", label: "Surat Jalan" },
  { key: "efaktur", label: "E-Faktur Pajak" },
];

function getStepStatus(key: DocKey) {
  const item = docs.value.find((d) => d.key === key);
  if (item?.file) return "completed";
  if (canUpload(key)) return "current";
  return "pending";
}
</script>

<template>
  <div class="bg-gray-50 min-h-screen p-6">
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-2xl font-medium text-gray-900 mb-2">Dokumen Persiapan</h1>
    </div>

    <!-- Progress Stepper -->
    <div class="mb-8">
      <div class="flex items-center justify-between mb-6">
        <div v-for="(step, index) in steps" :key="step.key" class="flex items-center">
          <!-- Step Circle -->
          <div class="flex items-center">
            <div
              class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium"
              :class="{
                'bg-blue-600 text-white': ['completed', 'current'].includes(getStepStatus(step.key as DocKey)),
                'bg-gray-300 text-gray-600': getStepStatus(step.key as DocKey) === 'pending'
              }"
            >
              <span v-if="getStepStatus(step.key as DocKey) === 'completed'">✓</span>
              <span v-else>{{ index + 1 }}</span>
            </div>
            <div class="ml-2 text-sm text-gray-700">{{ step.label }}</div>
          </div>
          <!-- Connector Line -->
          <div
            v-if="index < steps.length - 1"
            class="flex-1 h-0.5 mx-4"
            :class="{
              'bg-blue-600': getStepStatus(step.key as DocKey) === 'completed',
              'bg-gray-300': getStepStatus(step.key as DocKey) !== 'completed'
            }"
          ></div>
        </div>
      </div>
    </div>

    <!-- Document Forms -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Bukti Transfer BCA -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-medium text-gray-900 mb-1">Bukti Transfer BCA *</h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="bukti_transfer_bca"
              :disabled="
                !docs.find((d) => d.key === 'bukti_transfer_bca')?.active ||
                !canUpload('bukti_transfer_bca')
              "
              @change="(e) => onFileChange('bukti_transfer_bca', e)"
            />
            <label for="bukti_transfer_bca" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">Pilih Berkas</div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Batas berkas file upload (maks 20 MB)</span>
            </div>
            <div class="flex items-center gap-1 mt-1">
              <span class="text-blue-500">📁</span>
              <span class="text-blue-600">Dokumen.pdf</span>
            </div>
          </div>
        </div>
      </div>

      <!-- E-Faktur Pajak -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-medium text-gray-900 mb-1">E-Faktur Pajak *</h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="efaktur"
              :disabled="
                !docs.find((d) => d.key === 'efaktur')?.active || !canUpload('efaktur')
              "
              @change="(e) => onFileChange('efaktur', e)"
            />
            <label for="efaktur" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">Pilih Berkas</div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Batas berkas file upload (maks 20 MB)</span>
            </div>
            <div class="flex items-center gap-1 mt-1">
              <span class="text-blue-500">📁</span>
              <span class="text-blue-600">Dokumen.pdf</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Invoice/Nota/Faktur -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-medium text-gray-900 mb-1">Invoice/Nota/Faktur *</h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="invoice"
              :disabled="
                !docs.find((d) => d.key === 'invoice')?.active || !canUpload('invoice')
              "
              @change="(e) => onFileChange('invoice', e)"
            />
            <label for="invoice" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">Pilih Berkas</div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Batas berkas file upload (maks 20 MB)</span>
            </div>
            <div class="flex items-center gap-1 mt-1">
              <span class="text-blue-500">📁</span>
              <span class="text-blue-600">Dokumen.pdf</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Lainnya -->
      <div class="bg-white rounded-lg border border-gray-200 p-6">
        <h3 class="font-medium text-gray-900 mb-1">Lainnya</h3>
        <p class="text-xs text-gray-500 mb-4">Opsional</p>

        <div class="space-y-4">
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="lainnya"
              :disabled="
                !docs.find((d) => d.key === 'lainnya')?.active || !canUpload('lainnya')
              "
              @change="(e) => onFileChange('lainnya', e)"
            />
            <label for="lainnya" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">Pilih Berkas</div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Batas berkas file upload (maks 20 MB)</span>
            </div>
            <div class="flex items-center gap-1 mt-1">
              <span class="text-blue-500">📁</span>
              <span class="text-blue-600">Dokumen.pdf</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Surat Jalan -->
      <div class="bg-white rounded-lg border border-gray-200 p-6 md:col-start-1">
        <h3 class="font-medium text-gray-900 mb-1">Surat Jalan *</h3>
        <p class="text-xs text-gray-500 mb-4">Wajib</p>

        <div class="space-y-4">
          <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center">
            <input
              type="file"
              accept="application/pdf"
              class="hidden"
              id="surat_jalan"
              :disabled="
                !docs.find((d) => d.key === 'surat_jalan')?.active ||
                !canUpload('surat_jalan')
              "
              @change="(e) => onFileChange('surat_jalan', e)"
            />
            <label for="surat_jalan" class="cursor-pointer">
              <div class="text-gray-500 mb-2">📄</div>
              <div class="text-blue-600 text-sm">Pilih Berkas</div>
            </label>
          </div>
          <div class="text-xs text-gray-500">
            <div class="flex items-center gap-1">
              <span class="text-red-500">⚠</span>
              <span>Batas berkas file upload (maks 20 MB)</span>
            </div>
            <div class="flex items-center gap-1 mt-1">
              <span class="text-blue-500">📁</span>
              <span class="text-blue-600">Dokumen.pdf</span>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Action Buttons -->
    <div class="mt-8 flex gap-3">
      <button
        type="button"
        class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
      >
        KIRIM
      </button>
      <button
        type="button"
        class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors"
      >
        Simpan Draft
      </button>
      <button
        type="button"
        class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
      >
        Batal
      </button>
    </div>
  </div>
</template>

<style scoped>
input[type="file"]:disabled + label {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
