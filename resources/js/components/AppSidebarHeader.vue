<script setup lang="ts">
// import Breadcrumbs from "@/components/Breadcrumbs.vue";
import GreetingText from "./GreetingText.vue";
import NotificationPanel from "./NotificationPanel.vue";
import NavUser from "./NavUser.vue";
// import DepartmentDropdown from "./DepartmentDropdown.vue";
import type { BreadcrumbItemType, User } from "@/types";
import { router, usePage } from "@inertiajs/vue3";
import { ref, computed, onMounted } from "vue";
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

interface AlertItem {
  id: number;
  type: string;
  number: string;
  status: string;
  department?: string | null;
  url: string;
  origin?: 'task' | 'creator';
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
  return {
    id: Number(item.id),
    type: "Purchase Order",
    number: item.no_po || "-",
    status: item.status || "",
    department: item.department?.name ?? null,
    url: `/approval/purchase-orders/${item.id}`,
    origin: "task",
  };
}

function mapPoAnggaranItem(item: any): AlertItem {
  return {
    id: Number(item.id),
    type: "PO Anggaran",
    number: item.no_po_anggaran || "-",
    status: item.status || "",
    department: item.department?.name ?? null,
    url: `/approval/po-anggarans/${item.id}`,
    origin: "task",
  };
}

function mapMemoItem(item: any): AlertItem {
  return {
    id: Number(item.id),
    type: "Memo Pembayaran",
    number: item.no_mb || "-",
    status: item.status || "",
    department: item.department?.name ?? null,
    url: `/approval/memo-pembayarans/${item.id}`,
    origin: "task",
  };
}

function mapPaymentVoucherItem(item: any): AlertItem {
  return {
    id: Number(item.id),
    type: "Payment Voucher",
    number: item.no_pv || "-",
    status: item.status || "",
    department: item.department?.name ?? null,
    url: `/approval/payment-vouchers/${item.id}`,
    origin: "task",
  };
}

function mapRealisasiItem(item: any): AlertItem {
  return {
    id: Number(item.id),
    type: "Realisasi",
    number: item.no_realisasi || "-",
    status: item.status || "",
    department: item.department?.name ?? null,
    url: `/approval/realisasis/${item.id}`,
    origin: "task",
  };
}

function mapBpbItem(item: any): AlertItem {
  return {
    id: Number(item.id),
    type: "BPB",
    number: item.no_bpb || "-",
    status: item.status || "",
    department: item.department?.name ?? null,
    url: `/approval/bpbs/${item.id}`,
    origin: "task",
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

function getAlertStatusLabel(item: AlertItem): string {
  const raw = (item.status || '').toLowerCase();

  if (item.origin === 'creator') {
    if (raw === 'approved') return 'Dokumen kamu sudah disetujui';
    if (raw === 'rejected') return 'Dokumen kamu ditolak';
    return `Status dokumen: ${item.status}`;
  }

  if (raw === 'in progress') return 'Perlu tindakan awal';
  if (raw === 'verified') return 'Menunggu validasi atau approval berikutnya';
  if (raw === 'validated') return 'Menunggu approval akhir';

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
    <div class="flex items-center gap-3 relative">
      <NotificationPanel
        :notifications="notifications"
        @calendar-click="handleCalendarClick"
        @message-click="handleMessageClick"
        @alert-click="handleAlertClick"
      />

      <div
        v-if="isAlertDropdownOpen"
        class="absolute right-14 top-10 z-50 w-80 rounded-lg border border-gray-200 bg-white shadow-lg"
      >
        <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
          <span class="text-sm font-semibold text-gray-800">Dokumen yang perlu ditindak</span>
          <button
            type="button"
            class="text-xs text-gray-400 hover:text-gray-600"
            @click="isAlertDropdownOpen = false"
          >
            Tutup
          </button>
        </div>

        <div v-if="isLoadingAlerts" class="px-4 py-3 text-sm text-gray-500">
          Memuat notifikasi...
        </div>

        <div v-else-if="!alertItems.length" class="px-4 py-3 text-sm text-gray-500">
          Tidak ada dokumen yang perlu ditindak.
        </div>

        <ul v-else class="max-h-96 overflow-y-auto divide-y divide-gray-100">
          <li
            v-for="item in alertItems"
            :key="`${item.type}-${item.id}`"
            class="px-4 py-3 hover:bg-gray-50 cursor-pointer"
            @click="router.visit(item.url); isAlertDropdownOpen = false;"
          >
            <div class="flex items-center justify-between">
              <span class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                {{ item.type }}
              </span>
              <span class="text-xs text-gray-400 text-right max-w-[11rem] line-clamp-2">
                {{ getAlertStatusLabel(item) }}
              </span>
            </div>
            <div class="mt-1 text-sm font-medium text-gray-900">
              {{ item.number }}
            </div>
            <div v-if="item.department" class="mt-0.5 text-xs text-gray-500">
              {{ item.department }}
            </div>
          </li>
        </ul>
      </div>

      <NavUser :user="user" />
    </div>
  </header>
</template>
