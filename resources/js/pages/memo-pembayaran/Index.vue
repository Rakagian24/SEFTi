<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Desktop / Tablet header + actions -->
      <div class="mb-6 hidden items-center justify-between md:flex">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Memo Pembayaran</h1>
          <div class="mt-2 flex items-center text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Manage Memo Pembayaran data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <!-- pakai openConfirmSend agar ada konfirmasi -->
            <button
              @click="openConfirmSend"
              :disabled="!canSendSelected"
              class="flex items-center gap-2 rounded-md bg-green-600 px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50"
            >
              <Send class="w-4 h-4" />
              Kirim ({{ selected.length }})
            </button>
          </div>

          <button
            @click="goToAdd"
            class="flex items-center gap-2 rounded-md bg-[#101010] px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Add New
          </button>
        </div>
      </div>

      <!-- Mobile header -->
      <div class="mb-4 md:hidden">
        <h1 class="text-xl font-bold text-gray-900">Memo Pembayaran</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <WalletCards class="mr-1 h-3 w-3" />
          Manage Memo Pembayaran data
        </div>
      </div>

      <!-- Mobile actions: Pilih semua + Kirim + Add New -->
      <div class="mb-4 flex items-center justify-between gap-2 md:hidden">
        <div class="flex flex-col text-xs text-gray-600">
          <button
            type="button"
            class="mb-1 self-start rounded-full border border-blue-500 px-2 py-0.5 text-[11px] font-medium text-blue-600"
            @click="toggleMobileSelectAll"
          >
            {{ areAllMobileRowsSelected() ? 'Batal pilih semua' : 'Pilih semua' }}
          </button>
          <div>
            <span v-if="selected.length > 0" class="font-semibold text-blue-600">
              {{ selected.length }}
            </span>
            <span v-else class="text-gray-400">0</span>
            dokumen dipilih
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="openConfirmSend"
            :disabled="!canSendSelected"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              canSendSelected
                ? 'bg-green-600 text-white hover:bg-green-700'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M5 13l4 4L19 7"
              />
            </svg>
            <span>Kirim</span>
          </button>

          <button
            type="button"
            @click="goToAdd"
            class="inline-flex items-center gap-1 rounded-lg bg-[#101010] px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-white hover:text-[#101010]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            <span>Add New</span>
          </button>
        </div>
      </div>

      <MemoPembayaranFilter
        :filters="filters"
        :departments="departments"
        :statusOptions="statusOptions"
        :metodePembayaranOptions="metodePembayaranOptions"
        :columns="columns"
        @filter="applyFilters"
        @reset="resetFilters"
        @update:columns="updateColumns"
      />

      <MemoPembayaranTable
        :data="memoPembayarans.data || []"
        :pagination="memoPembayarans"
        :selected="selected"
        :columns="columns"
        @select="onSelect"
        @action="handleAction"
        @paginate="handlePagination"
        @add="goToAdd"
        @update:columns="updateColumns"
      />

      <StatusLegend entity="Memo Pembayaran" />

      <!-- Confirm Dialog untuk Kirim -->
      <ConfirmDialog
        :show="showConfirmSend"
        :message="`Apakah Anda yakin ingin mengirim ${confirmCount} Memo Pembayaran?`"
        @confirm="confirmSend"
        @cancel="cancelSend"
      />

      <!-- Confirm Dialog untuk Hapus/Cancel -->
      <ConfirmDialog
        :show="showConfirmDelete"
        message="Batalkan Memo Pembayaran ini?"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import MemoPembayaranTable from "../../components/memo-pembayaran/MemoPembayaranTable.vue";
import MemoPembayaranFilter from "../../components/memo-pembayaran/MemoPembayaranFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import StatusLegend from "@/components/ui/StatusLegend.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { WalletCards, Send } from "lucide-vue-next";

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

// Ambil page props untuk flash message
const page = usePage();

const props = defineProps<{
  memoPembayarans: any;
  filters: Record<string, any>;
  departments: any[];
  statusOptions: string[];
  metodePembayaranOptions: string[];
}>();

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Memo Pembayaran" }];

// reactive copies / defaults (hindari undefined di template)
const departments = ref(props.departments || []);
const statusOptions = ref(props.statusOptions || []);
const metodePembayaranOptions = ref(props.metodePembayaranOptions || []);
const filters = ref(props.filters || {}); // <-- penting: sebelumnya undefined
const memoPembayarans = ref(
  props.memoPembayarans || { data: [], total: 0, current_page: 1, last_page: 1 }
);

const selected = ref<number[]>([]);
const showConfirmDelete = ref(false);
const deleteTargetId = ref<number | null>(null);

// Get current user info
const currentUserId = computed<string | number | null>(() => {
  const id = (page.props.auth as any)?.user?.id;
  return id ?? null;
});

const isAdmin = computed<boolean>(() => {
  const userRole = (page.props.auth as any)?.user?.role?.name;
  return userRole === "Admin";
});

// Check if user can send selected items
const canSendSelected = computed(() => {
  if (selected.value.length === 0) return false;

  const rows = (memoPembayarans.value?.data || []) as any[];
  const selectedRows = rows.filter((r) => selected.value.includes(r.id));

  // Check if user can send all selected items
  return selectedRows.every((row) => {
    if (row.status === "Draft") {
      // Only creator can send draft memos (admin can send any)
      const isCreator = isCreatorRow(row);
      return isCreator || isAdmin.value;
    }
    if (row.status === "Rejected") {
      // Only creator can send rejected memos (admin can send any)
      const isCreator = isCreatorRow(row);
      return isCreator || isAdmin.value;
    }
    return false;
  });
});

function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

function getMobileSelectableIds(): number[] {
  const rows = (memoPembayarans.value?.data || []) as any[];
  return rows
    .filter((row: any) => {
      if (!(row.status === "Draft" || row.status === "Rejected")) return false;
      const isCreator = isCreatorRow(row);
      return isCreator || isAdmin.value;
    })
    .map((row: any) => row.id)
    .filter((id: any) => typeof id === "number");
}

function areAllMobileRowsSelected(): boolean {
  const selectableIds = getMobileSelectableIds();
  if (selectableIds.length === 0) return false;
  return selectableIds.every((id) => selected.value.includes(id));
}

function toggleMobileSelectAll() {
  const selectableIds = getMobileSelectableIds();
  if (selectableIds.length === 0) {
    selected.value = [];
    return;
  }
  if (selectableIds.every((id) => selected.value.includes(id))) {
    selected.value = selected.value.filter((id) => !selectableIds.includes(id));
  } else {
    selected.value = Array.from(new Set([...selected.value, ...selectableIds]));
  }
}

// number of valid items to confirm-send (after precheck)
const confirmCount = ref<number>(0);

// Confirm dialog state
const showConfirmSend = ref(false);

const columns = ref([
  { key: "no_mb", label: "No. MB", checked: true, sortable: false },
  { key: "no_po", label: "No. PO", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: true, sortable: false },
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
  { key: "perihal", label: "Perihal", checked: false, sortable: false },
  { key: "department", label: "Department", checked: false, sortable: false },
  {
    key: "metode_pembayaran",
    label: "Metode Pembayaran",
    checked: false,
    sortable: false,
  },
  { key: "grand_total", label: "Grand Total", checked: false, sortable: true },
  { key: "bank_account_info", label: "Info Rekening", checked: false, sortable: false },
  {
    key: "credit_card_info",
    label: "Info Kartu Kredit",
    checked: false,
    sortable: false,
  },
//   { key: "no_giro", label: "No. Giro", checked: false, sortable: false },
//   { key: "tanggal_giro", label: "Tanggal Giro", checked: false, sortable: true },
//   { key: "tanggal_cair", label: "Tanggal Cair", checked: false, sortable: true },
//   { key: "keterangan", label: "Keterangan", checked: false, sortable: false },
  { key: "total", label: "Total", checked: false, sortable: true },
//   { key: "diskon", label: "Diskon", checked: false, sortable: true },
//   { key: "ppn", label: "PPN", checked: false, sortable: false },
//   { key: "ppn_nominal", label: "PPN Nominal", checked: false, sortable: true },
//   { key: "pph_nominal", label: "PPH Nominal", checked: false, sortable: true },
  { key: "created_by", label: "Dibuat Oleh", checked: false, sortable: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false, sortable: true },
]);

function applyFilters(payload: Record<string, any>) {
  const params: Record<string, any> = {};
  if (payload.tanggal_start) params.tanggal_start = payload.tanggal_start;
  if (payload.tanggal_end) params.tanggal_end = payload.tanggal_end;
  if (payload.no_mb) params.no_mb = payload.no_mb;
  if (payload.department_id) params.department_id = payload.department_id;
  if (payload.status) params.status = payload.status;
  if (payload.metode_pembayaran) params.metode_pembayaran = payload.metode_pembayaran;
  if (payload.supplier_id) params.supplier_id = payload.supplier_id;
  if (payload.search) params.search = payload.search;
  if (payload.per_page) params.per_page = payload.per_page;
  if (payload.search_columns) params.search_columns = payload.search_columns;

  // simpan filters lokal supaya pagination/refresh konsisten
  // Hapus parameter search jika tidak ada di payload (untuk mengatasi masalah search tidak bisa dihapus)
  if (!payload.hasOwnProperty("search") || !payload.search) {
    delete filters.value.search;
  }
  filters.value = { ...filters.value, ...params };
  // reset selection saat filter berubah
  selected.value = [];
  router.get("/memo-pembayaran", filters.value, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      // Update local data with new data from server
      if (page.props.memoPembayarans) {
        memoPembayarans.value = page.props.memoPembayarans;
      }
      window.dispatchEvent(new CustomEvent("table-changed"));
    },
  });
}

function updateColumns(newColumns: any[]) {
  columns.value = newColumns;
  // opsional: masukkan columns ke filters jika backend support
  filters.value.columns = JSON.stringify(newColumns);
  // reset selection saat kolom berubah (data mungkin berubah)
  selected.value = [];
  router.get("/memo-pembayaran", filters.value, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page) => {
      // Update local data with new data from server
      if (page.props.memoPembayarans) {
        memoPembayarans.value = page.props.memoPembayarans;
      }
      window.dispatchEvent(new CustomEvent("table-changed"));
    },
  });
}

function resetFilters() {
  filters.value = { per_page: 10 };
  // reset selection saat reset filter
  selected.value = [];
  router.get("/memo-pembayaran", filters.value, {
    preserveState: true,
    onSuccess: (page) => {
      // Update local data with new data from server
      if (page.props.memoPembayarans) {
        memoPembayarans.value = page.props.memoPembayarans;
      }
      window.dispatchEvent(new CustomEvent("table-changed"));
    },
  });
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split("?")[1] || "");
  const page = urlParams.get("page");
  // reset selection saat ganti halaman
  selected.value = [];
  router.get(
    "/memo-pembayaran",
    { ...filters.value, page },
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page) => {
        // Update local data with new data from server
        if (page.props.memoPembayarans) {
          memoPembayarans.value = page.props.memoPembayarans;
        }
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
    }
  );
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected;
}

function handleAction(payload: { action: string; row: any }) {
  const { action, row } = payload;
  if (action === "edit") router.visit(`/memo-pembayaran/${row.id}/edit`);
  if (action === "delete") {
    deleteTargetId.value = row.id;
    showConfirmDelete.value = true;
  }
  if (action === "detail") router.visit(`/memo-pembayaran/${row.id}`);
  if (action === "log") router.visit(`/memo-pembayaran/${row.id}/log`);
  if (action === "preview") window.open(`/memo-pembayaran/${row.id}/preview`, "_blank");
  if (action === "download") window.open(`/memo-pembayaran/${row.id}/download`, "_blank");
}

// Confirm send flow (ganti sendSelected lama)
function openConfirmSend() {
  if (!canSendSelected.value) return;
  // client-side precheck: ensure all selected drafts have mandatory fields
  const rows = (memoPembayarans.value?.data || []) as any[];
  const selectedRows = rows.filter((r) => selected.value.includes(r.id));
  const problems: string[] = [];
  const validIds: number[] = [];
  for (const row of selectedRows) {
    const missing: string[] = [];
    // Metode pembayaran minimal
    if (!row.metode_pembayaran || !["Transfer", "Kredit"].includes(row.metode_pembayaran)) {
      missing.push("Metode Pembayaran");
    }
    // Purchase Order wajib
    if (!row.purchase_order_id) {
      missing.push("Purchase Order");
    }
    // Supplier/Kredit kondisional
    if (row.metode_pembayaran === "Transfer" && !row.supplier_id) {
      missing.push("Supplier");
    }
    if (row.metode_pembayaran === "Kredit" && !row.credit_card_id) {
      missing.push("Credit Card");
    }
    if (missing.length) {
      problems.push(
        `${row.no_mb || "Draft#" + row.id} belum lengkap: ${missing.join(", ")}`
      );
    } else {
      validIds.push(row.id);
    }
  }
  if (problems.length) {
    addError(`Sebagian Memo Pembayaran tidak lengkap:\n- ${problems.join("\n- ")}`);
  }
  // only proceed if there is at least one valid item
  if (validIds.length === 0) return;
  // narrow selection to valid ids for this send
  selected.value = validIds;
  confirmCount.value = validIds.length;
  showConfirmSend.value = true;
}

function confirmSend() {
  // kirim via inertia, lalu kosongkan selected & berikan flash melalui useMessagePanel
  router.post(
    "/memo-pembayaran/send",
    { ids: selected.value },
    {
      onSuccess: (page) => {
        const failed = (page.props as any)?.failed_memos || [];
        const updated = (page.props as any)?.updated_memos || [];
        if (updated?.length) {
          addSuccess(`${updated.length} Memo Pembayaran berhasil dikirim`);
        }
        if (failed?.length) {
          const problems = failed
            .map(
              (m: any) =>
                `${m.no_mb || "Draft#" + m.id}: ${
                  Array.isArray(m.errors) ? m.errors.join(", ") : ""
                }`
            )
            .join("\n- ");
          addError(
            `Sebagian Memo Pembayaran gagal dikirim karena belum lengkap:\n- ${problems}`
          );
        }
        selected.value = [];
        confirmCount.value = 0;
        // Update local data with new data from server
        if (page.props.memoPembayarans) {
          memoPembayarans.value = page.props.memoPembayarans;
        }
        // refresh list agar status terbaru muncul
        router.get("/memo-pembayaran", filters.value, {
          preserveState: true,
          preserveScroll: true,
          onSuccess: (page) => {
            if (page.props.memoPembayarans) {
              memoPembayarans.value = page.props.memoPembayarans;
            }
            window.dispatchEvent(new CustomEvent("table-changed"));
          },
        });
      },
      onError: () => addError("Terjadi kesalahan saat mengirim Memo Pembayaran"),
      preserveScroll: true,
    }
  );
  showConfirmSend.value = false;
}

function cancelSend() {
  showConfirmSend.value = false;
}

function goToAdd() {
  router.visit("/memo-pembayaran/create");
}

function confirmDelete() {
  if (!deleteTargetId.value) return;
  router.delete(`/memo-pembayaran/${deleteTargetId.value}`, {
    onSuccess: (page) => {
      addSuccess("Memo Pembayaran berhasil dibatalkan");
      if (page.props.memoPembayarans) {
        memoPembayarans.value = page.props.memoPembayarans;
      }
      router.get("/memo-pembayaran", filters.value, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          if (page.props.memoPembayarans) {
            memoPembayarans.value = page.props.memoPembayarans;
          }
          window.dispatchEvent(new CustomEvent("table-changed"));
        },
      });
    },
    onFinish: () => {
      showConfirmDelete.value = false;
      deleteTargetId.value = null;
    },
  });
}

function cancelDelete() {
  showConfirmDelete.value = false;
  deleteTargetId.value = null;
}

// Watch for props changes to update local data
watch(
  () => props.memoPembayarans,
  (newData) => {
    if (newData) {
      memoPembayarans.value = newData;
    }
  },
  { immediate: true }
);

watch(
  () => props.filters,
  (newFilters) => {
    if (newFilters) {
      filters.value = newFilters;
    }
  },
  { immediate: true }
);

// Watch for flash message dari server (success)
watch(
  () => page.props,
  (newProps) => {
    const flash =
      newProps && typeof newProps.flash === "object"
        ? (newProps.flash as Record<string, any>)
        : {};
    if (typeof flash.success === "string" && flash.success.length > 0) {
      addSuccess(flash.success);
    }
    if (typeof flash.error === "string" && flash.error.length > 0) {
      addError(flash.error);
    }
  },
  { immediate: true }
);

// Listen for table changes to refresh data
onMounted(() => {
  window.addEventListener("table-changed", () => {
    router.reload({ only: ["memoPembayarans"] });
  });
});
</script>
