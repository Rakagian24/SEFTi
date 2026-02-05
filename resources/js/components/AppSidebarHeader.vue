<script setup lang="ts">
// import Breadcrumbs from "@/components/Breadcrumbs.vue";
import GreetingText from "./GreetingText.vue";
import NotificationPanel from "./NotificationPanel.vue";
import NavUser from "./NavUser.vue";
// import DepartmentDropdown from "./DepartmentDropdown.vue";
import type { BreadcrumbItemType, User } from "@/types";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted, onBeforeUnmount } from "vue";
import { useSidebar } from "@/components/ui/sidebar/utils";
import { Menu } from "lucide-vue-next";
import { useApi } from "@/composables/useApi";

withDefaults(
  defineProps<{
    breadcrumbs?: BreadcrumbItemType[];
  }>(),
  {
    breadcrumbs: () => [],
  }
);

// Get user from Inertia page props
const page = usePage();
const user = computed(() => page.props.auth.user as User);

// Sidebar control for mobile menu button
const { toggleSidebar } = useSidebar();

const { get } = useApi();

const notifications = ref({
  calendar: 0,
  messages: 0,
  alerts: 0,
});

const isAlertDropdownOpen = ref(false);
const isLoadingAlerts = ref(false);
const notificationAreaRef = ref<HTMLElement | null>(null);

interface AlertItem {
  id: number;
  type: string;
  number: string;
  status: string;
  department?: string | null;
  url: string;
  origin?: 'task' | 'creator';
  creatorName?: string | null;
  creatorRole?: string | null;
  stageLabel?: string | null;
  actorName?: string | null;
  actorRole?: string | null;
}

const alertItems = ref<AlertItem[]>([]);

const handleCalendarClick = () => {
  // reserved for future calendar notifications
};

const handleMessageClick = () => {
  // reserved for future in-app messages
};

async function refreshApprovalCounts() {
  try {
    const [po, memo, pv, poAnggaran, realisasi, bpb] = await Promise.all([
      get("/api/approval/purchase-orders/count").catch(() => ({ count: 0 })),
      get("/api/approval/memo-pembayaran/count").catch(() => ({ count: 0 })),
      get("/api/approval/payment-vouchers/count").catch(() => ({ count: 0 })),
      get("/api/approval/po-anggaran/count").catch(() => ({ count: 0 })),
      get("/api/approval/realisasi/count").catch(() => ({ count: 0 })),
      get("/api/approval/bpbs/count").catch(() => ({ count: 0 })),
    ]);

    const alertsTotal =
      Number(po.count || 0) +
      Number(memo.count || 0) +
      Number(pv.count || 0) +
      Number(poAnggaran.count || 0) +
      Number(realisasi.count || 0) +
      Number(bpb.count || 0);

    notifications.value.alerts = alertsTotal;
  } catch {
    notifications.value.alerts = 0;
  }
}

function mapPoItem(item: any): AlertItem {
  const status = (item.status || "") as string;
  // Prefer backend-provided current_action (verify|validate|approve) to determine stage
  const action = (item.current_action || "") as string;
  let stageLabel: string | null = null;
  if (action === "verify") stageLabel = "Verifikasi";
  else if (action === "validate") stageLabel = "Validasi";
  else if (action === "approve") stageLabel = "Approval";
  // Fallback lama jika current_action belum tersedia
  if (!stageLabel) {
    const normalized = status.toLowerCase();
    if (normalized === "in progress") stageLabel = "Verifikasi";
    else if (normalized === "verified") stageLabel = "Validasi";
    else if (normalized === "validated") stageLabel = "Approval";
  }

  return {
    id: Number(item.id),
    type: "Purchase Order",
    number: item.no_po || "-",
    status: status,
    department: item.department?.name ?? null,
    url: `/approval/purchase-orders/${item.id}`,
    origin: "task",
    creatorName: item.creator?.name ?? null,
    creatorRole: item.creator?.role?.name ?? null,
    stageLabel,
  };
}

function mapPoAnggaranItem(item: any): AlertItem {
  const status = (item.status || "") as string;
  const action = (item.current_action || "") as string;
  const actionNorm = action.toLowerCase();
  const normalized = status.toLowerCase();
  let stageLabel: string | null = null;

  // Utamakan current_action dari backend (verify/validate/approve)
  if (actionNorm === "verify") stageLabel = "Verifikasi";
  else if (actionNorm === "validate") stageLabel = "Validasi";
  else if (actionNorm === "approve") stageLabel = "Approval";
  else {
    // Fallback ke mapping status jika current_action belum tersedia
    if (normalized === "in progress") stageLabel = "Verifikasi";
    else if (normalized === "verified") stageLabel = "Validasi";
    else if (normalized === "validated") stageLabel = "Approval";
  }

  return {
    id: Number(item.id),
    type: "PO Anggaran",
    number: item.no_po_anggaran || "-",
    status,
    department: item.department?.name ?? null,
    url: `/approval/po-anggarans/${item.id}`,
    origin: "task",
    creatorName: item.creator?.name ?? null,
    creatorRole: item.creator?.role?.name ?? null,
    stageLabel,
  };
}

function mapMemoItem(item: any): AlertItem {
  const status = (item.status || "") as string;
  const action = (item.current_action || "") as string;
  const actionNorm = action.toLowerCase();
  const normalized = status.toLowerCase();
  let stageLabel: string | null = null;

  if (actionNorm === "verify") stageLabel = "Verifikasi";
  else if (actionNorm === "validate") stageLabel = "Validasi";
  else if (actionNorm === "approve") stageLabel = "Approval";
  else {
    if (normalized === "in progress") stageLabel = "Verifikasi";
    else if (normalized === "verified") stageLabel = "Validasi";
    else if (normalized === "validated") stageLabel = "Approval";
  }

  return {
    id: Number(item.id),
    type: "Memo Pembayaran",
    number: item.no_mb || "-",
    status,
    department: item.department?.name ?? null,
    url: `/approval/memo-pembayarans/${item.id}`,
    origin: "task",
    creatorName: item.creator?.name ?? null,
    creatorRole: item.creator?.role?.name ?? null,
    stageLabel,
  };
}

function mapPaymentVoucherItem(item: any): AlertItem {
  const status = (item.status || "") as string;
  const action = (item.current_action || "") as string;
  const actionNorm = action.toLowerCase();
  const normalized = status.toLowerCase();
  let stageLabel: string | null = null;

  if (actionNorm === "verify") stageLabel = "Verifikasi";
  else if (actionNorm === "validate") stageLabel = "Validasi";
  else if (actionNorm === "approve") stageLabel = "Approval";
  else {
    if (normalized === "in progress") stageLabel = "Verifikasi";
    else if (normalized === "verified") stageLabel = "Validasi";
    else if (normalized === "validated") stageLabel = "Approval";
  }

  return {
    id: Number(item.id),
    type: "Payment Voucher",
    number: item.no_pv || "-",
    status,
    department: item.department?.name ?? null,
    url: `/approval/payment-vouchers/${item.id}`,
    origin: "task",
    creatorName: item.creator?.name ?? null,
    creatorRole: item.creator?.role?.name ?? null,
    stageLabel,
  };
}

function mapRealisasiItem(item: any): AlertItem {
  const status = (item.status || "") as string;
  const action = (item.current_action || "") as string;
  const actionNorm = action.toLowerCase();
  const normalized = status.toLowerCase();
  let stageLabel: string | null = null;

  if (actionNorm === "verify") stageLabel = "Verifikasi";
  else if (actionNorm === "validate") stageLabel = "Validasi";
  else if (actionNorm === "approve") stageLabel = "Approval";
  else {
    if (normalized === "in progress") stageLabel = "Verifikasi";
    else if (normalized === "verified") stageLabel = "Validasi";
    else if (normalized === "validated") stageLabel = "Approval";
  }

  return {
    id: Number(item.id),
    type: "Realisasi",
    number: item.no_realisasi || "-",
    status,
    department: item.department?.name ?? null,
    url: `/approval/realisasis/${item.id}`,
    origin: "task",
    creatorName: item.creator?.name ?? null,
    creatorRole: item.creator?.role?.name ?? null,
    stageLabel,
  };
}

function mapBpbItem(item: any): AlertItem {
  const status = (item.status || "") as string;
  const action = (item.current_action || "") as string;
  const actionNorm = action.toLowerCase();
  const normalized = status.toLowerCase();
  let stageLabel: string | null = null;

  if (actionNorm === "verify") stageLabel = "Verifikasi";
  else if (actionNorm === "validate") stageLabel = "Validasi";
  else if (actionNorm === "approve") stageLabel = "Approval";
  else {
    if (normalized === "in progress") stageLabel = "Verifikasi";
    else if (normalized === "verified") stageLabel = "Validasi";
    else if (normalized === "validated") stageLabel = "Approval";
  }

  return {
    id: Number(item.id),
    type: "BPB",
    number: item.no_bpb || "-",
    status,
    department: item.department?.name ?? null,
    url: `/approval/bpbs/${item.id}`,
    origin: "task",
    creatorName: item.creator?.name ?? null,
    creatorRole: item.creator?.role?.name ?? null,
    stageLabel,
  };
}

async function loadAlertItems() {
  isLoadingAlerts.value = true;
  try {
    const [po, memo, pv, poAnggaran, realisasi, bpb, myNotifications] = await Promise.all([
      get("/api/approval/purchase-orders?per_page=5").catch(() => ({ data: [] })),
      get("/api/approval/memo-pembayarans?per_page=5").catch(() => ({ data: [] })),
      get("/api/approval/payment-vouchers?per_page=5").catch(() => ({ data: [] })),
      get("/api/approval/po-anggarans?per_page=5").catch(() => ({ data: [] })),
      get("/api/approval/realisasis?per_page=5").catch(() => ({ data: [] })),
      get("/api/approval/bpbs?per_page=5").catch(() => ({ data: [] })),
      get("/api/approval/my-notifications").catch(() => ({ notifications: [] })),
    ]);

    const items: AlertItem[] = [];

    (po.data || []).forEach((it: any) => items.push(mapPoItem(it)));
    (poAnggaran.data || []).forEach((it: any) => items.push(mapPoAnggaranItem(it)));
    (memo.data || []).forEach((it: any) => items.push(mapMemoItem(it)));
    (pv.data || []).forEach((it: any) => items.push(mapPaymentVoucherItem(it)));
    (realisasi.data || []).forEach((it: any) => items.push(mapRealisasiItem(it)));
    (bpb.data || []).forEach((it: any) => items.push(mapBpbItem(it)));

    // Creator notifications (final status updates for documents created by the user)
    (myNotifications.notifications || []).forEach((n: any) => {
      items.push({
        id: Number(n.id),
        type: n.document_type || "Dokumen",
        number: n.document_number || "-",
        status: n.status || "",
        department: n.department ?? null,
        url: n.url || "#",
        origin: "creator",
        creatorName: n.creator_name ?? null,
        creatorRole: n.creator_role ?? null,
        actorName: n.actor_name ?? null,
        actorRole: n.actor_role ?? null,
      });
    });

    alertItems.value = items;
  } finally {
    isLoadingAlerts.value = false;
  }
}

const handleAlertClick = async () => {
  isAlertDropdownOpen.value = !isAlertDropdownOpen.value;
  if (isAlertDropdownOpen.value) {
    await loadAlertItems();
  }
};

function handleDocumentClick(event: MouseEvent) {
  const root = notificationAreaRef.value;
  if (!root) return;
  const target = event.target as Node | null;
  if (!target) return;
  if (!root.contains(target)) {
    isAlertDropdownOpen.value = false;
  }
}

function getAlertStatusLabel(item: AlertItem): string {
  const raw = (item.status || '').toLowerCase();

  // ========== Creator notifications (final status: Approved / Rejected) ==========
  if (item.origin === 'creator') {
    if (raw === 'approved') {
      return `✓ Pengajuan Anda telah disetujui`;
    }
    if (raw === 'rejected') {
      const actorName = item.actorName || 'Approver';
      const actorRole = item.actorRole || '';
      const rolePart = actorRole ? ` (${actorRole})` : '';
      return `✗ Ditolak oleh ${actorName}${rolePart}`;
    }

    return `Status: ${item.status}`;
  }

  // ========== Approval tasks (verify / validate / approve) ==========
  if (item.origin === 'task') {
    const creatorName = item.creatorName || 'User';
    const stage = item.stageLabel || item.status || '';

    // Format: Perlu [Tahap] dari [Nama Pembuat]
    return `Perlu ${stage} dari ${creatorName}`;
  }

  // ========== Fallback generic (jika origin belum ter-set) ==========
  if (raw === 'in progress') return 'Menunggu verifikasi';
  if (raw === 'verified') return 'Menunggu validasi';
  if (raw === 'validated') return 'Menunggu approval';

  return `Status: ${item.status}`;
}

// Extract first name for greeting
const firstName = computed(() => {
  return user.value?.name?.split(" ")[0] || "User";
});

const activeDepartment = ref("");
// const isRefreshing = ref(false);
const lastSelectedDept = ref("");

onMounted(() => {
  // Load department aktif dari URL saat mount
  const urlParams = new URLSearchParams(window.location.search);
  const urlDept = urlParams.get("activeDepartment") || "";
  lastSelectedDept.value = urlDept;
  activeDepartment.value = urlDept;

  refreshApprovalCounts();

  document.addEventListener("click", handleDocumentClick);
});

onBeforeUnmount(() => {
  document.removeEventListener("click", handleDocumentClick);
});

// function handleDepartmentChange(val: string) {
//   // Jika sama dengan yang sudah dipilih, jangan refresh
//   if (val === lastSelectedDept.value) return;

//   activeDepartment.value = val;
//   lastSelectedDept.value = val;

//   // Update URL tanpa reload
//   const url = new URL(window.location.href);
//   if (val) {
//     url.searchParams.set("activeDepartment", val);
//   } else {
//     url.searchParams.delete("activeDepartment");
//   }

//   // Update URL tanpa reload
//   window.history.pushState({}, "", url.toString());

//   // Refresh halaman
//   window.location.reload();
// }
</script>

<template>
  <header
    class="flex h-16 shrink-0 items-center justify-between gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:p-4"
  >
    <!-- Left side - Greeting / Mobile Menu -->
    <div class="flex items-center gap-4">
      <!-- Mobile: Sidebar toggle button -->
      <button
        type="button"
        class="inline-flex items-center justify-center rounded-full p-2 text-gray-700 hover:bg-gray-100 md:hidden"
        @click="toggleSidebar"
      >
        <Menu class="w-5 h-5" />
        <span class="sr-only">Toggle sidebar navigation</span>
      </button>

      <!-- Desktop: Greeting text -->
      <div class="hidden md:flex items-center gap-3">
        <GreetingText :user-name="firstName" />
      </div>
    </div>

    <!-- Middle - Department Dropdown -->
    <!-- <DepartmentDropdown @update:activeDepartment="handleDepartmentChange" /> -->

    <!-- Right side - Notifications and Profile -->
    <div ref="notificationAreaRef" class="flex items-center gap-3 relative">
      <NotificationPanel
        :notifications="notifications"
        @calendar-click="handleCalendarClick"
        @message-click="handleMessageClick"
        @alert-click="handleAlertClick"
      />

      <!-- Alert Dropdown dengan Style yang Disesuaikan -->
      <div
        v-if="isAlertDropdownOpen"
        class="absolute z-[9999] rounded-xl border border-gray-200 bg-white shadow-2xl overflow-hidden w-[calc(100vw-1.5rem)] max-w-md right-2 top-14 md:w-96 md:right-14 md:top-10"
      >
        <!-- Header Dropdown -->
        <div class="bg-gradient-to-r from-emerald-500 to-teal-500 px-5 py-3.5 flex items-center justify-between">
          <h3 class="text-white font-semibold text-base flex items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
              <path d="M10 2a6 6 0 00-6 6v3.586l-.707.707A1 1 0 004 14h12a1 1 0 00.707-1.707L16 11.586V8a6 6 0 00-6-6zM10 18a3 3 0 01-3-3h6a3 3 0 01-3 3z" />
            </svg>
            Notifikasi
          </h3>
          <button
            type="button"
            class="text-white/90 hover:text-white transition-colors text-sm font-medium px-2 py-1 rounded hover:bg-white/20"
            @click="isAlertDropdownOpen = false"
          >
            Tutup
          </button>
        </div>

        <!-- Loading State -->
        <div v-if="isLoadingAlerts" class="px-5 py-8 text-center">
          <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-emerald-200 border-t-emerald-500"></div>
          <p class="mt-3 text-sm text-gray-500">Memuat notifikasi...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="!alertItems.length" class="px-5 py-12 text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
          </svg>
          <p class="text-sm text-gray-500 font-medium">Tidak ada notifikasi</p>
          <p class="text-xs text-gray-400 mt-1">Semua dokumen sudah ditindak</p>
        </div>

        <!-- Alert List -->
        <ul v-else class="max-h-[28rem] overflow-y-auto">
          <li
            v-for="item in alertItems"
            :key="`${item.type}-${item.id}`"
            class="border-b border-gray-100 last:border-b-0 transition-all duration-200 hover:bg-emerald-50/50 cursor-pointer group"
            @click="router.visit(item.url); isAlertDropdownOpen = false;"
          >
            <div class="px-5 py-4">
              <!-- Document Type Badge -->
              <div class="flex items-start justify-between gap-3 mb-2">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700 group-hover:bg-emerald-200 transition-colors">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4z" clip-rule="evenodd" />
                  </svg>
                  {{ item.type }}
                </span>
                <span class="text-xs text-gray-500 font-medium whitespace-nowrap">
                  {{ item.number }}
                </span>
              </div>

              <!-- Alert Message -->
              <div class="text-sm text-gray-700 leading-relaxed">
                {{ getAlertStatusLabel(item) }}
              </div>

              <!-- Department (if available) -->
              <div v-if="item.department" class="mt-2 flex items-center gap-1.5 text-xs text-gray-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                  <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 110 2h-3a1 1 0 01-1-1v-2a1 1 0 00-1-1H9a1 1 0 00-1 1v2a1 1 0 01-1 1H4a1 1 0 110-2V4zm3 1h2v2H7V5zm2 4H7v2h2V9zm2-4h2v2h-2V5zm2 4h-2v2h2V9z" clip-rule="evenodd" />
                </svg>
                {{ item.department }}
              </div>
            </div>
          </li>
        </ul>

        <!-- Footer (Optional) -->
        <!-- <div v-if="alertItems.length > 0" class="border-t border-gray-200 bg-gray-50 px-5 py-3 text-center">
          <button
            type="button"
            class="text-sm text-emerald-600 hover:text-emerald-700 font-medium hover:underline"
            @click="router.visit('/approvals'); isAlertDropdownOpen = false;"
          >
            Lihat Semua Notifikasi →
          </button>
        </div> -->
      </div>

      <NavUser :user="user" />
    </div>
  </header>
</template>
