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

      <!-- Status Grid - stacked list on mobile, 2-column grid on md+ -->
      <div class="space-y-2 md:space-y-0 md:grid md:grid-cols-2 md:gap-x-6 md:gap-y-2">
        <div
          v-for="item in legendItems"
          :key="item.status"
          class="flex flex-col md:flex-row md:items-start gap-1.5 md:gap-2 group"
        >
          <!-- Dot + Badge with fixed width -->
          <div class="flex items-center gap-1.5 flex-shrink-0 w-full md:w-[90px]">
            <span
              :class="getDotClass(item.status)"
              class="w-1.5 h-1.5 rounded-full flex-shrink-0"
            ></span>
            <span
              :class="getBadgeClass(item.status)"
              class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap"
            >
              {{ item.status }}
            </span>
          </div>

          <!-- Description -->
          <p
            class="text-xs text-gray-600 leading-relaxed group-hover:text-gray-800 transition-colors flex-1 md:mt-0"
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

// Dynamic descriptions per status based on current user's role and entity (formal tone)
function describe(status: string): string {
  const e = props.entity;
  const role = (userRole.value || "").toLowerCase();
  const dept = userDept.value;
  const special = isSpecialDept(dept);

  if (status === "Draft") return "Dokumen dalam penyusunan. Kirim untuk memulai alur persetujuan.";

  // Purchase Order
  if (e === "Purchase Order") {
    if (status === "In Progress") {
      if (role === "kepala toko") return "Dokumen menunggu verifikasi. Tindakan Anda: Verifikasi Purchase Order dari Staff Toko.";
      if (role === "kabag") return "Dokumen menunggu verifikasi. Tindakan Anda: Verifikasi Purchase Order dari Staff Akunting & Finance.";
      if (role === "kadiv") return special
        ? "Dokumen menunggu validasi/approval sesuai alur khusus departemen. Tindakan Anda: Validasi jika diperlukan."
        : "Dokumen menunggu verifikasi oleh Kepala Toko/Kabag. Tahap Anda setelah verifikasi: Validasi.";
      if (role === "direksi") return special
        ? "Dokumen In Progress. Untuk DM (Zi&Glo/HG), Anda dapat langsung melakukan Approval."
        : "Dokumen menunggu validasi oleh Kadiv sebelum Approval.";
      return special ? "Dokumen menunggu tindakan sesuai alur khusus departemen." : "Dokumen menunggu verifikasi oleh Kepala Toko/Kabag.";
    }
    if (status === "Verified") {
      if (role === "kadiv") return "Dokumen ini sudah diverifikasi. Tindakan Anda selanjutnya: Validasi oleh Kadiv.";
      if (role === "direksi") return special
        ? "Dokumen ini sudah diverifikasi. Tindakan Anda selanjutnya: Approval oleh Direksi."
        : "Dokumen ini sudah diverifikasi. Menunggu validasi oleh Kadiv sebelum Approval.";
      return "Dokumen ini sudah diverifikasi. Selanjutnya: Validasi oleh Kadiv.";
    }
    if (status === "Validated") {
      if (role === "direksi") return "Dokumen ini telah divalidasi. Tindakan Anda selanjutnya: Approval oleh Direksi.";
      return "Dokumen ini telah divalidasi. Selanjutnya: Approval oleh Direksi.";
    }
    if (status === "Approved") return "Dokumen telah disetujui. Proses selesai.";
    if (status === "Rejected") return "Dokumen ditolak. Lakukan perbaikan sesuai catatan dan kirim ulang.";
    if (status === "Canceled") return "Dokumen dibatalkan oleh pembuat/administrator.";
  }

  // Memo Pembayaran
  if (e === "Memo Pembayaran") {
    if (status === "In Progress") {
      if (role === "kepala toko") return "Dokumen menunggu verifikasi. Tindakan Anda: Verifikasi Memo dari Staff Toko.";
      if (role === "kadiv") return special
        ? "Dokumen menunggu approval. Tindakan Anda: Approval oleh Kadiv (alur ringkas)."
        : "Dokumen menunggu verifikasi oleh Kepala Toko. Tindakan Anda setelah verifikasi: Approval oleh Kadiv.";
      if (role === "kabag") return "Dokumen menunggu approval. Tindakan Anda: Approval oleh Kabag (Memo dari Staff Akunting & Finance).";
      return special ? "Dokumen menunggu approval sesuai alur ringkas departemen." : "Dokumen menunggu verifikasi Kepala Toko atau approval (Kadiv/Kabag).";
    }
    if (status === "Verified") {
      if (role === "kadiv") return "Dokumen ini sudah diverifikasi. Tindakan Anda selanjutnya: Approval oleh Kadiv.";
      return "Dokumen ini sudah diverifikasi. Selanjutnya: Approval oleh Kadiv.";
    }
    if (status === "Approved") return "Dokumen telah disetujui. Proses selesai.";
    if (status === "Rejected") return "Dokumen ditolak. Lakukan perbaikan sesuai catatan dan kirim ulang.";
    if (status === "Canceled") return "Dokumen dibatalkan oleh pembuat/administrator.";
  }

  // Payment Voucher
  if (e === "Payment Voucher") {
    if (status === "In Progress") {
      if (role === "kabag" || role === "kadiv") return "Dokumen menunggu verifikasi. Tindakan Anda: Verifikasi Payment Voucher.";
      if (role === "direksi") return "Dokumen menunggu verifikasi oleh Kabag/Kadiv sebelum Approval.";
      return "Dokumen menunggu verifikasi oleh Kabag/Kadiv.";
    }
    if (status === "Verified") {
      if (role === "direksi") return "Dokumen ini sudah diverifikasi. Tindakan Anda selanjutnya: Approval oleh Direksi.";
      return "Dokumen ini sudah diverifikasi. Selanjutnya: Approval oleh Direksi.";
    }
    if (status === "Approved") return "Dokumen telah disetujui. Proses selesai.";
    if (status === "Rejected") return "Dokumen ditolak. Lakukan perbaikan sesuai catatan dan kirim ulang.";
    if (status === "Canceled") return "Dokumen dibatalkan oleh pembuat/administrator.";
  }

  // BPB
  if (e === "BPB") {
    if (status === "In Progress") {
      if (role === "kepala toko") return "Dokumen menunggu approval. Tindakan Anda: Approval BPB (dibuat oleh Staff Toko).";
      if (role === "kabag") return "Dokumen menunggu approval. Tindakan Anda: Approval BPB (dibuat oleh Staff Akunting & Finance).";
      return "Dokumen menunggu approval oleh Kepala Toko atau Kabag.";
    }
    if (status === "Approved") return "Dokumen telah disetujui. Proses selesai.";
    if (status === "Rejected") return "Dokumen ditolak. Lakukan perbaikan sesuai catatan dan kirim ulang.";
    if (status === "Canceled") return "Dokumen dibatalkan oleh pembuat/administrator.";
  }

  // Default fallback
  if (status === "In Progress") return "Dokumen menunggu tindakan sesuai workflow yang berlaku.";
  if (status === "Verified") return "Dokumen ini sudah diverifikasi. Menunggu tahap berikutnya.";
  if (status === "Validated") return "Dokumen ini telah divalidasi. Menunggu approval akhir.";
  if (status === "Approved") return "Dokumen telah disetujui. Proses selesai.";
  if (status === "Rejected") return "Dokumen ditolak. Lakukan perbaikan sesuai catatan dan kirim ulang.";
  if (status === "Canceled") return "Dokumen dibatalkan oleh pembuat/administrator.";
  return "";
}

function getWorkflowStatuses(): string[] {
  const role = (userRole.value || "").toLowerCase();
  const dept = userDept.value;
  const special = isSpecialDept(dept);

  // Defaults (includes all)
  let statuses: string[] = [
    "Draft",
    "In Progress",
    "Verified",
    "Validated",
    "Approved",
    "Rejected",
    "Canceled",
  ];

  if (props.entity === "BPB") {
    // Single-step approval
    return ["Draft", "In Progress", "Approved", "Rejected", "Canceled"];
  }

  if (props.entity === "Payment Voucher") {
    // Verify -> Approve (no validate)
    return ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"];
  }

  if (props.entity === "Memo Pembayaran") {
    // Verify -> Approve or direct approve (no validate)
    return ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"];
  }

  if (props.entity === "Purchase Order") {
    // Dynamic: include Validated only when applicable
    // - Kabag flow: Verified -> Approved (no Validated)
    // - Kepala Toko / Kadiv flow (non-special): includes Validated
    // - Direksi: special depts may skip Validated; non-special includes it
    // - Special departments (Zi&Glo/HG): often skip Validated
    if (role === "kabag") {
      return ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"];
    }
    if (role === "kepala toko") {
      return special
        ? ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"]
        : ["Draft", "In Progress", "Verified", "Validated", "Approved", "Rejected", "Canceled"];
    }
    if (role === "kadiv") {
      return special
        ? ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"]
        : ["Draft", "In Progress", "Verified", "Validated", "Approved", "Rejected", "Canceled"];
    }
    if (role === "direksi") {
      return special
        ? ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"]
        : ["Draft", "In Progress", "Verified", "Validated", "Approved", "Rejected", "Canceled"];
    }
    // Fallback for other roles: keep conservative (non-special shows Validated)
    return special
      ? ["Draft", "In Progress", "Verified", "Approved", "Rejected", "Canceled"]
      : ["Draft", "In Progress", "Verified", "Validated", "Approved", "Rejected", "Canceled"];
  }

  return statuses;
}

const legendItems = computed(() => {
  const statuses = getWorkflowStatuses();
  return statuses.map((s) => ({ status: s, description: describe(s) }));
});
</script>
