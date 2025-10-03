<template>
  <div class="mt-4">
    <div
      class="bg-gradient-to-br from-slate-50 to-blue-50/30 border border-slate-200/60 rounded-2xl p-6 shadow-sm"
    >
      <!-- Compact Header -->
      <div class="flex items-center justify-between mb-2.5">
        <div class="flex items-center gap-2">
          <svg
            class="w-3.5 h-3.5 text-blue-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          <h3 class="text-sm font-semibold text-gray-800">
            Panduan Status {{ entityLabel }}
          </h3>
        </div>
        <span
          class="text-xs font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"
        >
          {{ legendItems.length }} status
        </span>
      </div>

      <!-- Status Grid - Super Compact 2 Columns -->
      <div class="grid grid-cols-2 gap-x-6 gap-y-2">
        <div
          v-for="item in legendItems"
          :key="item.status"
          class="flex items-start gap-2 group"
        >
          <!-- Dot + Badge with fixed width -->
          <div class="flex items-center gap-1.5 flex-shrink-0 w-[90px]">
            <span
              :class="getDotClass(item.status)"
              class="w-1.5 h-1.5 rounded-full flex-shrink-0"
            ></span>
            <span
              :class="getBadgeClass(item.status)"
              class="text-xs font-medium whitespace-nowrap"
            >
              {{ item.status }}
            </span>
          </div>

          <!-- Separator -->
          <span class="text-gray-300 text-xs flex-shrink-0">·</span>

          <!-- Description -->
          <p
            class="text-xs text-gray-600 leading-relaxed group-hover:text-gray-800 transition-colors flex-1"
            :title="item.description"
          >
            {{ item.description }}
          </p>
        </div>
      </div>

      <!-- Workflow Info - Ultra Compact -->
      <div
        class="mt-2.5 flex items-center gap-1.5 text-xs text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded"
      >
        <svg class="w-3 h-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
            clip-rule="evenodd"
          />
        </svg>
        <span>{{ workflowNote }}</span>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { getStatusBadgeClass, getStatusDotClass } from "@/lib/status";

interface Props {
  entity?: "Memo Pembayaran" | "Purchase Order" | string;
}

const props = defineProps<Props>();

function getBadgeClass(status: string) {
  return getStatusBadgeClass(status);
}

function getDotClass(status: string) {
  return getStatusDotClass(status);
}

const entityLabel = computed(() => props.entity || "Dokumen");

const workflowNote = computed(() => {
  if (props.entity === "Purchase Order") {
    return "PO melalui tahap verifikasi, validasi, dan approval sesuai role dan departemen.";
  } else if (props.entity === "Memo Pembayaran") {
    return "Memo Pembayaran melalui tahap verifikasi dan approval sesuai role dan departemen.";
  }
  return "Dokumen akan melalui alur approval sesuai role pembuat.";
});

const legendItems = computed(() => [
  {
    status: "Draft",
    description: "Dokumen dalam tahap penyusunan, belum dikirim ke approval.",
  },
  {
    status: "In Progress",
    description: "Dokumen sedang menunggu tindakan dari approver.",
  },
  {
    status: "Verified",
    description: "Dokumen telah diverifikasi dan siap tahap berikutnya.",
  },
  {
    status: "Validated",
    description: "Dokumen telah divalidasi, menunggu persetujuan Direksi.",
  },
  {
    status: "Approved",
    description: "Dokumen disetujui dan siap diproses lebih lanjut.",
  },
  {
    status: "Rejected",
    description: "Dokumen ditolak, perlu perbaikan sebelum dikirim ulang.",
  },
  {
    status: "Canceled",
    description: "Dokumen dibatalkan oleh pembuat.",
  },
]);
</script>
