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
          <h3 class="text-base font-semibold text-gray-800">
            Panduan Status {{ entityLabel }}
          </h3>
        </div>
        <span
          class="text-sm font-medium text-gray-500 bg-gray-100 px-2 py-0.5 rounded-full"
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
              class="text-sm font-medium whitespace-nowrap"
            >
              {{ item.status }}
            </span>
          </div>

          <!-- Description -->
          <p
            class="text-sm text-gray-600 leading-relaxed group-hover:text-gray-800 transition-colors flex-1"
            :title="item.description"
          >
            {{ item.description }}
          </p>
        </div>
      </div>

      <!-- Workflow Info - Ultra Compact -->
      <div
        class="mt-2.5 flex items-center gap-1.5 text-sm text-blue-700 bg-blue-50 px-2.5 py-1.5 rounded"
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
import { usePage } from "@inertiajs/vue3";
import { getStatusBadgeClass, getStatusDotClass } from "@/lib/status";

interface Props {
  entity?: "Memo Pembayaran" | "Purchase Order" | "Payment Voucher" | "BPB" | string;
}

const props = defineProps<Props>();

function getBadgeClass(status: string) {
  return getStatusBadgeClass(status);
}

function getDotClass(status: string) {
  return getStatusDotClass(status);
}

const entityLabel = computed(() => props.entity || "Dokumen");

// Current user role and department (if available)
const page = usePage();
const userRole = computed<string>(() => ((page.props as any)?.auth?.user?.role?.name) || "");
const userDept = computed<string>(() => ((page.props as any)?.auth?.user?.department?.name) || "");

function isSpecialDept(name: string | undefined) {
  return name === "Zi&Glo" || name === "Human Greatness";
}

// Build concise, entity-specific workflow note
const workflowNote = computed(() => {
  const role = userRole.value || "User";
  const dept = userDept.value || "Semua Departemen";
  switch (props.entity) {
    case "Purchase Order":
      return isSpecialDept(dept)
        ? `PO: Alur khusus ${dept}. DM bisa langsung ke Direksi; lainnya mengikuti verifikasi/validasi/approval.`
        : "PO: Verifikasi → Validasi → Approval, disesuaikan peran pembuat (Staff Toko/Akunting/DM/Kepala Toko/Kabag).";
    case "Memo Pembayaran":
      return isSpecialDept(dept)
        ? `Memo: Alur ringkas untuk ${dept}. Sebagian peran langsung approve sesuai pembuat.`
        : "Memo: Verifikasi → Approval (atau langsung Approval) sesuai peran pembuat.";
    case "Payment Voucher":
      return "Payment Voucher: Verifikasi oleh Kabag/Kadiv → Approval oleh Direksi.";
    case "BPB":
      return "BPB: Approval tunggal oleh Kepala Toko (Staff Toko) atau Kabag (Akunting).";
    default:
      return `Alur persetujuan mengikuti workflow dinamis. Anda login sebagai ${role}.`;
  }
});

// Dynamic descriptions per status and entity
function describe(status: string): string {
  const e = props.entity;
  if (status === "Draft") return "Disusun oleh pembuat. Kirim untuk memulai alur approval.";

  if (e === "Purchase Order") {
    switch (status) {
      case "In Progress":
        return isSpecialDept(userDept.value)
          ? "Menunggu: Kepala Toko/Kabag/Kadiv atau langsung Direksi (DM di Zi&Glo/HG)."
          : "Menunggu verifikasi: Kepala Toko (Staff Toko) atau Kabag (Akunting).";
      case "Verified":
        return isSpecialDept(userDept.value)
          ? "Sudah diverifikasi. Berikutnya: Direksi (kecuali alur yang butuh validasi)."
          : "Sudah diverifikasi. Berikutnya: Validasi oleh Kadiv (staff toko/KT).";
      case "Validated":
        return "Sudah divalidasi. Berikutnya: Approval oleh Direksi.";
      case "Approved":
        return "Disetujui Direksi. Selesai.";
      case "Rejected":
        return "Ditolak oleh approver. Perlu diperbaiki dan dikirim ulang.";
      case "Canceled":
        return "Dibatalkan oleh pembuat/admin.";
    }
  }

  if (e === "Memo Pembayaran") {
    switch (status) {
      case "In Progress":
        return isSpecialDept(userDept.value)
          ? "Menunggu approval sesuai pembuat (mis. langsung Kepala Toko/Kadiv)."
          : "Menunggu verifikasi Kepala Toko atau langsung approval (sesuai pembuat).";
      case "Verified":
        return "Sudah diverifikasi. Berikutnya: Approval oleh Kadiv.";
      case "Approved":
        return "Disetujui. Selesai.";
      case "Rejected":
        return "Ditolak. Perbaiki lalu kirim ulang.";
      case "Canceled":
        return "Dibatalkan oleh pembuat/admin.";
    }
  }

  if (e === "Payment Voucher") {
    switch (status) {
      case "In Progress":
        return "Menunggu verifikasi oleh Kabag/Kadiv.";
      case "Verified":
        return "Sudah diverifikasi. Berikutnya: Approval oleh Direksi.";
      case "Approved":
        return "Disetujui Direksi. Selesai.";
      case "Rejected":
        return "Ditolak. Perbaiki lalu kirim ulang.";
      case "Canceled":
        return "Dibatalkan oleh pembuat/admin.";
    }
  }

  if (e === "BPB") {
    switch (status) {
      case "In Progress":
        return "Menunggu approval: Kepala Toko (jika dibuat Staff Toko) atau Kabag (Akunting).";
      case "Approved":
        return "Disetujui. Selesai.";
      case "Rejected":
        return "Ditolak. Perbaiki lalu kirim ulang.";
      case "Canceled":
        return "Dibatalkan oleh pembuat/admin.";
    }
  }

  // Default fallback
  switch (status) {
    case "In Progress":
      return "Menunggu tindakan sesuai workflow.";
    case "Verified":
      return "Sudah diverifikasi. Menunggu tahap berikutnya.";
    case "Validated":
      return "Sudah divalidasi. Menunggu approval akhir.";
    case "Approved":
      return "Disetujui. Selesai.";
    case "Rejected":
      return "Ditolak. Perbaiki lalu kirim ulang.";
    case "Canceled":
      return "Dibatalkan oleh pembuat/admin.";
  }
  return "";
}

const legendItems = computed(() => {
  const base = [
    "Draft",
    "In Progress",
    "Verified",
    "Validated",
    "Approved",
    "Rejected",
    "Canceled",
  ];

  // Trim statuses for entities that don't use all steps
  let statuses = base;
  if (props.entity === "Memo Pembayaran") {
    statuses = ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"];
  } else if (props.entity === "Payment Voucher") {
    statuses = ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"];
  } else if (props.entity === "BPB") {
    statuses = ["Draft", "In Progress", "Approved", "Rejected", "Canceled"];
  }

  return statuses.map((s) => ({ status: s, description: describe(s) }));
});
</script>
