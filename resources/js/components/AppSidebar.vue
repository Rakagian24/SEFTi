<script setup lang="ts">
import NavMain from "@/components/NavMain.vue";
import { Sidebar, SidebarContent, SidebarMenuButton } from "@/components/ui/sidebar";
import { useSidebar } from "@/components/ui/sidebar/utils";
const { state } = useSidebar();
import { iconMapping } from "@/lib/iconMapping";
import { Link } from "@inertiajs/vue3";
import AppLogo from "@/components/AppLogo.vue";
import { onMounted, onUnmounted, computed } from "vue";
import SidebarTrigger from "@/components/ui/sidebar/SidebarTrigger.vue";
import { usePermissions } from "@/composables/usePermissions";

const mainNavGroups = [
  {
    label: "Master",
    items: [
      { title: "Bank", href: "/banks", icon: iconMapping["banks"] },
      {
        title: "Bank Account",
        href: "/bank-accounts",
        icon: iconMapping["bank-accounts"],
      },
      { title: "Jenis Barang", href: "/jenis-barangs", icon: iconMapping["jenis-barangs"] },
      { title: "Barang", href: "/barangs", icon: iconMapping["barangs"] },
      { title: "Supplier", href: "/suppliers", icon: iconMapping["suppliers"] },
      {
        title: "Bisnis Partner",
        href: "/bisnis-partners",
        icon: iconMapping["bisnis-partners"],
      },
      { title: "PPh", href: "/pphs", icon: iconMapping["pphs"] },
      { title: "Pengeluaran", href: "/pengeluarans", icon: iconMapping["pengeluarans"] },
      { title: "Customer", href: "/ar-partners", icon: iconMapping["ar-partners"] },
    ],
  },
  {
    label: "Daily Use",
    items: [
      {
        title: "Purchase Order",
        href: "/purchase-orders",
        icon: iconMapping["purchase-orders"],
      },
      {
        title: "Memo Pembayaran",
        href: "/memo-pembayaran",
        icon: iconMapping["memo-pembayaran"],
      },
      {
        title: "Payment Voucher",
        href: "/payment-voucher",
        icon: iconMapping["payment-voucher"],
      },
    //   { title: "PO Anggaran", href: "/po-anggaran", icon: iconMapping["po-anggaran"] },
      { title: "BPB", href: "/bpb", icon: iconMapping["bpb"] },
      { title: "Anggaran", href: "/po-anggaran", icon: iconMapping["po-anggaran"] },
      { title: "Realisasi", href: "/realisasi", icon: iconMapping["realisasi"] },
      { title: "Pengeluaran Barang", href: "/pengeluaran-barang", icon: iconMapping["pengeluaran-barang"] },
      { title: "Approval", href: "/approval", icon: iconMapping["approval"] },
      {
        title: "Daftar List Bayar",
        href: "/list-bayar",
        icon: iconMapping["list-bayar"],
      },
    ],
  },
  {
    label: "Bank",
    items: [
      {
        title: "Bank Matching",
        href: "/bank-matching",
        icon: iconMapping["bank-matching"],
      },
      { title: "Bank Masuk", href: "/bank-masuk", icon: iconMapping["bank-masuk"] },
      { title: "Bank Keluar", href: "/bank-keluar", icon: iconMapping["bank-keluar"] },
    ],
  },
  {
    label: "Report",
    items: [
      {
        title: "PO Outstanding",
        href: "/po-outstanding",
        icon: iconMapping["po-outstanding"],
      },
      {
        title: "Mutasi Stock",
        href: "/mutasi-stock",
        icon: iconMapping["mutasi-stock"],
      },
      {
        title: "Kartu Stock",
        href: "/kartu-stock",
        icon: iconMapping["kartu-stock"],
      },
      {
        title: "Laporan Stock",
        href: "/stock",
        icon: iconMapping["stock"],
      },
    ],
  },
  {
    label: "Setting",
    items: [
      { title: "Role", href: "/roles", icon: iconMapping["roles"] },
      { title: "Department", href: "/departments", icon: iconMapping["departments"] },
      { title: "Termin", href: "/termins", icon: iconMapping["termins"] },
      { title: "Perihal", href: "/perihals", icon: iconMapping["perihals"] },
      { title: "Users", href: "/users", icon: iconMapping["users"] },
    ],
  },
];

// Map URL path to permission key defined in backend/seeders
const menuPermissionMap: Record<string, string> = {
  // Master
  "/banks": "bank",
  "/bank-accounts": "bank",
  "/jenis-barangs": "jenis_barang",
  "/barangs": "barang",
  "/suppliers": "supplier",
  "/bisnis-partners": "bisnis_partner",
  "/pphs": "payment_voucher",
  "/pengeluarans": "payment_voucher",
  "/ar-partners": "bisnis_partner",

  // Daily Use
  "/purchase-orders": "purchase_order",
  "/memo-pembayaran": "memo_pembayaran",
  "/payment-voucher": "payment_voucher",
  "/bpb": "bpb",
  "/po-anggaran": "purchase_order",
  "/anggaran": "anggaran",
  "/realisasi": "anggaran", // sementara selaraskan dengan anggaran
  "/approval": "approval",
  "/list-bayar": "daftar_list_bayar",
  "/pengeluaran-barang": "pengeluaran_barang", // Accessible to admin, staff toko, and branch manager

  // Bank
  "/bank-matching": "bank_masuk", // gunakan izin yang sama seperti bank_masuk
  "/bank-masuk": "bank_masuk",
  "/bank-keluar": "bank_keluar",

  // Report
  "/po-outstanding": "po_outstanding",
  "/mutasi-stock": "mutasi_stock",
  "/kartu-stock": "kartu_stock",
  "/stock": "stock",

  // Setting
  "/roles": "*",
  "/departments": "*",
  "/termins": "termin",
  "/perihals": "*",
  "/users": "*",
};

const { hasPermission, isInStockDepartment } = usePermissions();

const filteredNavGroups = computed(() => {
  return mainNavGroups
    .map((group) => {
      const filteredItems = group.items.filter((item) => {
        const permission = menuPermissionMap[item.href as keyof typeof menuPermissionMap];

        // Additional department-based restriction for stock-related modules
        const stockRestrictedPaths = [
          "/stock",
          "/kartu-stock",
          "/mutasi-stock",
          "/pengeluaran-barang",
        ];

        if (stockRestrictedPaths.includes(item.href)) {
          if (!isInStockDepartment()) {
            return false;
          }
        }

        if (!permission) {
          // Jika belum dipetakan, default: hanya tampil untuk Admin (memiliki '*')
          return hasPermission("*");
        }
        return hasPermission(permission) || hasPermission("*");
      });
      return { ...group, items: filteredItems };
    })
    .filter((group) => group.items.length > 0);
});

// Function to update sidebar height based on content changes
function updateSidebarHeight() {
  const sidebar = document.querySelector('[data-sidebar="sidebar"]') as HTMLElement;
  if (sidebar) {
    // Force reflow to recalculate height
    sidebar.style.height = "auto";
    // Use requestAnimationFrame to trigger reflow
    requestAnimationFrame(() => {
      sidebar.style.height = "";
    });
  }
}

// Event listeners for content changes
function handleContentChange() {
  // Use setTimeout to ensure DOM has updated
  setTimeout(updateSidebarHeight, 100);
}

function handlePaginationChange() {
  // Use setTimeout to ensure DOM has updated
  setTimeout(updateSidebarHeight, 100);
}

function handleTableChange() {
  // Use setTimeout to ensure DOM has updated
  setTimeout(updateSidebarHeight, 100);
}

// ResizeObserver to automatically detect content size changes
let resizeObserver: ResizeObserver | null = null;

onMounted(() => {
  // Add event listeners
  window.addEventListener("content-changed", handleContentChange);
  window.addEventListener("pagination-changed", handlePaginationChange);
  window.addEventListener("table-changed", handleTableChange);

  // Setup ResizeObserver for main content
  const mainContent = document.querySelector(".main-content") as HTMLElement;
  if (mainContent && typeof ResizeObserver !== "undefined") {
    resizeObserver = new ResizeObserver(() => {
      updateSidebarHeight();
    });
    resizeObserver.observe(mainContent);
  }
});

onUnmounted(() => {
  // Remove event listeners
  window.removeEventListener("content-changed", handleContentChange);
  window.removeEventListener("pagination-changed", handlePaginationChange);
  window.removeEventListener("table-changed", handleTableChange);

  // Disconnect ResizeObserver
  if (resizeObserver) {
    resizeObserver.disconnect();
  }
});
</script>

<template>
  <div class="sidebar-layout">
    <Sidebar
      collapsible="icon"
      variant="floating"
      class="custom-scrollbar floating-sidebar"
    >
      <div
        data-slot="sidebar-header"
        data-sidebar="header"
        :class="['flex flex-col gap-2', state === 'collapsed' ? 'py-10 px-2' : 'p-10']"
      >
        <ul
          data-slot="sidebar-menu"
          data-sidebar="menu"
          class="flex w-full min-w-0 flex-col gap-1"
        >
          <li
            data-slot="sidebar-menu-item"
            data-sidebar="menu-item"
            class="group/menu-item relative"
          >
            <SidebarMenuButton
              size="lg"
              as-child
              :class="[
                'sidebar-logo',
                'sidebar-logo-btn',
                'justify-center',
                state === 'collapsed' ? 'text-center' : 'text-left',
              ]"
            >
              <Link :href="route('dashboard')">
                <AppLogo :state="state" />
              </Link>
            </SidebarMenuButton>
          </li>
        </ul>
      </div>
      <SidebarContent class="custom-scrollbar sidebar-content">
        <NavMain :groups="filteredNavGroups" />
      </SidebarContent>
    </Sidebar>

    <!-- SidebarTrigger di luar Sidebar, absolute -->
    <SidebarTrigger
      class="sidebar-trigger-absolute"
      :style="{ left: state === 'collapsed' ? '99px' : '275px' }"
    />

    <div class="main-content">
      <slot />
    </div>
  </div>
</template>

<style scoped>
/* Custom Scrollbar */
.custom-scrollbar {
  scrollbar-width: thin;
  scrollbar-color: rgba(156, 163, 175, 0.5) transparent;
}

.custom-scrollbar::-webkit-scrollbar {
  width: 6px;
}

.custom-scrollbar::-webkit-scrollbar-track {
  background: transparent;
}

.custom-scrollbar::-webkit-scrollbar-thumb {
  background-color: rgba(156, 163, 175, 0.5);
  border-radius: 6px;
  transition: background-color 0.2s ease;
}

.custom-scrollbar::-webkit-scrollbar-thumb:hover {
  background-color: rgba(156, 163, 175, 0.8);
}

/* Sidebar Layout */
.sidebar-layout {
  display: flex;
  min-height: 100vh;
  position: relative; /* Penting agar absolute child relatif ke sini */
}

/* Floating Sidebar - sticky dan mengikuti tinggi konten */
.floating-sidebar {
  position: sticky;
  top: 16px;
  left: 0;
  width: 256px;
  min-width: 256px;
  border-radius: 40px;
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.08);
  background: white;
  z-index: 10;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  height: auto;
  align-self: flex-start;
}

/* Main Content Area */
.main-content {
  flex: 1;
  margin-left: 12px;
  transition: margin-left 0.2s ease-linear;
  padding: 20px;
  min-height: 100vh;
}

/* Floating Sidebar - fixed dan selalu menempel di kiri layar */
.floating-sidebar::after {
  content: "";
  position: absolute;
  left: 0;
  right: 0;
  bottom: 0;
  height: 40px; /* tinggi efek shadow/gradient */
  pointer-events: none;
  border-bottom-left-radius: 40px;
  border-bottom-right-radius: 40px;
  box-shadow: 0 16px 32px 8px rgba(0, 0, 0, 0.1);
  /* Jika ingin gradient, bisa ganti dengan:
  background: linear-gradient(to bottom, rgba(255,255,255,0), rgba(0,0,0,0.08));
  */
}

/* Sidebar Content - Hapus scroll internal dan max-height */
.sidebar-content {
  flex: 1;
  padding: 0 20px 32px 20px; /* padding-bottom 32px agar ada jarak dari konten terakhir ke bawah sidebar */
}

/* Logo styling */
.sidebar-logo {
  margin: 12px 0; /* dari 32px 0 */
  display: flex;
  justify-content: center !important;
  align-items: center !important;
  padding: 4px 0; /* dari 16px 0 */
  background: transparent !important;
  box-shadow: none !important;
  border: none !important;
  height: 48px !important; /* dari 56px */
  min-height: 48px !important;
  max-height: 100% !important;
  flex-shrink: 0;
}

.sidebar-logo:focus,
.sidebar-logo:hover {
  background: transparent !important;
  box-shadow: none !important;
  border: none !important;
}

.sidebar-logo {
  overflow: visible !important;
  padding: 0 !important;
  height: 56px !important;
  min-height: 56px !important;
  max-height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
}

.logo-text {
  font-size: 1.1rem !important;
  line-height: 1.5 !important;
  margin: 0 !important;
  display: block !important;
  white-space: nowrap !important;
  overflow: visible !important;
}

.sidebar-logo,
.sidebar-logo * {
  overflow: visible !important;
}

/* Collapsed state adjustments */
:global(.group[data-state="collapsed"]) .floating-sidebar {
  width: 100px;
  min-width: 100px;
  border-top-left-radius: 12px !important;
  border-top-right-radius: 12px !important;
  border-bottom-left-radius: 40px !important;
  border-bottom-right-radius: 40px !important;
}

:global(.group[data-state="collapsed"]) .main-content {
  margin-left: calc(80px + 48px);
}

:global(.group[data-state="collapsed"]) .sidebar-logo {
  margin: 20px 0;
  padding: 12px 0;
  justify-content: center !important;
  align-items: center !important;
}

/* Ensure logo remains visible when collapsed */
:global(.group[data-state="collapsed"]) .sidebar-logo span {
  display: block !important;
  opacity: 1 !important;
  transform: none !important;
  font-size: 1rem !important;
  text-align: center;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .main-content {
    margin-left: 0;
  }

  .floating-sidebar {
    display: none;
  }
}

/* Pastikan sidebar mengikuti tinggi dokumen */
html,
body {
  height: auto;
  min-height: 100vh;
}

/* Pastikan sidebar bisa extend sesuai tinggi halaman */
.sidebar-layout {
  min-height: 100vh;
  height: auto;
}

/* Override global style agar sidebar sticky */
:global(.group[data-variant="floating"] [data-sidebar="sidebar"]) {
  position: sticky !important;
  top: 16px !important;
  min-height: 100vh !important;
  height: auto !important;
  max-height: none !important;
}

/* Pastikan wrapper sidebar juga mengikuti */
:global(.group[data-variant="floating"]) {
  min-height: 100vh;
  height: auto;
  align-self: stretch;
}

.sidebar-trigger-absolute {
  position: absolute; /* dari fixed */
  top: 30%;
  transform: translateY(-30%);
  z-index: 1;
}

@media (max-width: 1024px) {
  .sidebar-trigger-absolute {
    left: 80px !important; /* Atur sesuai lebar sidebar collapsed di tablet */
    top: 60%; /* Sedikit lebih ke bawah jika sidebar lebih pendek */
    /* Bisa juga kecilkan ukuran tombol jika perlu */
  }
}

@media (max-width: 768px) {
  .sidebar-trigger-absolute {
    display: none; /* Sembunyikan di mobile jika sidebar hidden */
  }
}
</style>

<style>
:global(.group[data-state="collapsed"]) .sidebar-logo-btn,
:global(.group[data-state="collapsed"]) .sidebar-logo,
:global(.group[data-state="collapsed"]) .logo-container {
  width: 80px !important;
  min-width: 80px !important;
  max-width: 80px !important;
  height: 80px !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin: 0 !important;
  padding: 0 !important;
}

:global(.group[data-state="collapsed"]) .logo-text {
  font-size: 1.1rem !important;
  width: 100% !important;
  text-align: center !important;
  margin: 0 auto !important;
  line-height: 1.2 !important;
  display: block !important;
  white-space: nowrap !important;
  overflow: visible !important;
}

:global(.group[data-state="collapsed"]) .sidebar-logo-btn {
  text-align: center !important;
  align-items: center !important;
  justify-content: center !important;
  width: 80px !important;
  min-width: 80px !important;
  max-width: 80px !important;
  height: 80px !important;
  padding: 0 !important;
  margin: 0 !important;
  display: flex !important;
}

:global(.group[data-state="collapsed"]) .sidebar-logo-btn .logo-container {
  width: 100% !important;
  height: 100% !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  margin: 0 !important;
  padding: 0 !important;
}

:global(.group[data-state="collapsed"]) .sidebar-logo-btn .logo-text {
  font-size: 1.1rem !important;
  width: 100% !important;
  text-align: center !important;
  margin: 0 auto !important;
  line-height: 1.2 !important;
  display: block !important;
  white-space: nowrap !important;
  overflow: visible !important;
  color: white !important;
}

:global(.group[data-state="collapsed"]) .sidebar-logo-btn {
  text-align: center !important;
}

:global(.group[data-state="collapsed"]) .sidebar-logo-btn.text-left {
  text-align: center !important;
}

:global(.group[data-state="collapsed"]) .sidebar-logo-btn * {
  text-align: center !important;
  justify-content: center !important;
  align-items: center !important;
}
</style>
