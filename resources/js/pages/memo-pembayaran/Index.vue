<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Memo Pembayaran</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Manage Memo Pembayaran data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <!-- pakai openConfirmSend agar ada konfirmasi -->
            <button
              @click="openConfirmSend"
              :disabled="!canSend"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="w-4 h-4" />
              Kirim ({{ selected.length }})
            </button>
          </div>

          <button
            @click="goToAdd"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
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

      <!-- Confirm Dialog untuk Kirim -->
      <ConfirmDialog
        :show="showConfirmSend"
        :message="`Apakah Anda yakin ingin mengirim ${confirmCount} Memo Pembayaran?`"
        @confirm="confirmSend"
        @cancel="cancelSend"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { router } from "@inertiajs/vue3";
import MemoPembayaranTable from "../../components/memo-pembayaran/MemoPembayaranTable.vue";
import MemoPembayaranFilter from "../../components/memo-pembayaran/MemoPembayaranFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { WalletCards, Send } from "lucide-vue-next";

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

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
const canSend = computed(() => selected.value.length > 0);
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
  { key: "nama_rekening", label: "Nama Rekening", checked: false, sortable: false },
  { key: "no_rekening", label: "No. Rekening", checked: false, sortable: false },
  { key: "no_kartu_kredit", label: "No. Kartu Kredit", checked: false, sortable: false },
  { key: "no_giro", label: "No. Giro", checked: false, sortable: false },
  { key: "tanggal_giro", label: "Tanggal Giro", checked: false, sortable: true },
  { key: "tanggal_cair", label: "Tanggal Cair", checked: false, sortable: true },
  { key: "keterangan", label: "Keterangan", checked: false, sortable: false },
  { key: "total", label: "Total", checked: false, sortable: true },
  { key: "diskon", label: "Diskon", checked: false, sortable: true },
  { key: "ppn", label: "PPN", checked: false, sortable: false },
  { key: "ppn_nominal", label: "PPN Nominal", checked: false, sortable: true },
  { key: "pph_nominal", label: "PPH Nominal", checked: false, sortable: true },
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
  if (payload.entriesPerPage) params.per_page = payload.entriesPerPage;
  if (payload.search_columns) params.search_columns = payload.search_columns;

  // simpan filters lokal supaya pagination/refresh konsisten
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
  if (action === "delete")
    router.delete(`/memo-pembayaran/${row.id}`, {
      onSuccess: (page) => {
        addSuccess("Memo Pembayaran berhasil dibatalkan");
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
    });
  if (action === "detail") router.visit(`/memo-pembayaran/${row.id}`);
  if (action === "log") router.visit(`/memo-pembayaran/${row.id}/log`);
  if (action === "download") window.open(`/memo-pembayaran/${row.id}/download`, "_blank");
}

// Confirm send flow (ganti sendSelected lama)
function openConfirmSend() {
  if (!canSend.value) return;
  // client-side precheck: ensure all selected drafts have mandatory fields
  const rows = (memoPembayarans.value?.data || []) as any[];
  const selectedRows = rows.filter((r) => selected.value.includes(r.id));
  const problems: string[] = [];
  const validIds: number[] = [];
  for (const row of selectedRows) {
    const missing: string[] = [];
    if (!row.total || Number(row.total) <= 0) missing.push("Total");
    if (!["Transfer", "Cek/Giro", "Kredit"].includes(row.metode_pembayaran))
      missing.push("Metode Pembayaran");
    else if (row.metode_pembayaran === "Transfer") {
      if (!row.bank_id) missing.push("Bank");
      if (!row.nama_rekening) missing.push("Nama Rekening");
      if (!row.no_rekening) missing.push("No. Rekening");
    } else if (row.metode_pembayaran === "Cek/Giro") {
      if (!row.no_giro) missing.push("No. Giro");
      if (!row.tanggal_giro) missing.push("Tanggal Giro");
      if (!row.tanggal_cair) missing.push("Tanggal Cair");
    } else if (row.metode_pembayaran === "Kredit") {
      if (!row.no_kartu_kredit) missing.push("No. Kartu Kredit");
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

// Listen for table changes to refresh data
onMounted(() => {
  window.addEventListener("table-changed", () => {
    router.reload({ only: ["memoPembayarans"] });
  });
});
</script>
