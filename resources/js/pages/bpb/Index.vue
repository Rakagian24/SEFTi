<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import BpbFilter from "@/components/bpb/BpbFilter.vue";
import BpbTable from "@/components/bpb/BpbTable.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import StatusLegend from "@/components/ui/StatusLegend.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { FileText, Send } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bukti Penerimaan Barang" },
];

defineOptions({ layout: AppLayout });

const rows = ref<any[]>([]);
const meta = ref<any>({});
const selected = ref<number[]>([]);
const loading = ref(false);
const { addSuccess, addError, clearAll } = useMessagePanel();

const page = usePage();
const departmentOptions = computed<any[]>(() => (page.props as any).departmentOptions || []);
const supplierOptions = computed<any[]>(() => (page.props as any).supplierOptions || []);

// Current user & permissions (mirror behavior from table component for mobile)
const currentUserId = computed<string | number | null>(() => {
  const id = (page.props as any)?.auth?.user?.id;
  return id ?? null;
});

const isAdmin = computed<boolean>(() => {
  const role = (page.props as any)?.auth?.user?.role?.name;
  return String(role || "").toLowerCase() === "admin";
});

function isCreatorRow(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.created_by ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

// Column config shared between Filter and Table
type Column = { key: string; label: string; checked: boolean; sortable?: boolean };
const columns = ref<Column[]>([
  // Primary identifiers
  { key: "no_bpb", label: "No. BPB", checked: true, sortable: true },
  { key: "no_po", label: "No. PO", checked: true, sortable: true },
  { key: "no_pv", label: "No. PV", checked: false, sortable: true },
  // Meta
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
  // Relational
  { key: "supplier", label: "Supplier", checked: true },
  { key: "department", label: "Departemen", checked: false },
  { key: "perihal", label: "Perihal (PO)", checked: false },
  // Amounts
  { key: "subtotal", label: "Subtotal", checked: false },
//   { key: "diskon", label: "Diskon", checked: false },
//   { key: "dpp", label: "DPP", checked: false },
//   { key: "ppn", label: "PPN", checked: false },
//   { key: "pph", label: "PPH", checked: false },
  { key: "grand_total", label: "Grand Total", checked: true },
  // Notes
  { key: "keterangan", label: "Keterangan", checked: false },
]);

function updateColumns(c: Column[]) {
  columns.value = Array.isArray(c) ? c : [];
}

// Confirm dialog state
const showConfirmSend = ref(false);
const showConfirmCancel = ref(false);
const cancelTargetId = ref<number | null>(null);

// Mobile state
const mobileSearch = ref("");
const mobileMenuBpbId = ref<number | null>(null);

function fetchData(params: any = {}) {
  loading.value = true;
  router.get(
    "/bpb",
    params,
    {
      preserveState: true,
      preserveScroll: true,
      onSuccess: (page: any) => {
        const props = (page as any).props as any;
        rows.value = props.bpbs?.data || [];
        meta.value = props.bpbs || {};
      },
      onFinish: () => (loading.value = false),
    }
  );
}

function onFilter(payload: any) {
  fetchData(payload);
}

function onReset() {
  fetchData({});
}

function onPaginate(url: string) {
  if (!url) return;
  loading.value = true;
  router.visit(url, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: (page: any) => {
      const props = (page as any).props as any;
      rows.value = props.bpbs?.data || [];
      meta.value = props.bpbs || {};
    },
    onFinish: () => (loading.value = false),
  });
}

function onSend() {
  if (selected.value.length === 0) return;
  showConfirmSend.value = true;
}

function confirmSend() {
  clearAll();
  router.post(
    "/bpb/send",
    { ids: selected.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        addSuccess("Dokumen berhasil dikirim");
        fetchData({});
        selected.value = [];
      },
      onError: (err: any) => {
        addError((err && (Object.values(err).flat() as any).join(" ")) || "Gagal mengirim dokumen");
      },
      onFinish: () => (showConfirmSend.value = false),
    }
  );
}

function cancelSend() {
  showConfirmSend.value = false;
}

function onAction(e: { action: string; row: any }) {
  const { action, row } = e;
  if (action === "edit") {
    router.visit(`/bpb/${row.id}/edit`);
  } else if (action === "cancel") {
    cancelTargetId.value = row.id;
    showConfirmCancel.value = true;
  } else if (action === "detail") {
    router.visit(`/bpb/${row.id}/detail`);
  } else if (action === "download") {
    window.open(`/bpb/${row.id}/download`, "_blank");
  } else if (action === "preview") {
    window.open(`/bpb/${row.id}/preview`, "_blank");
  } else if (action === "log") {
    router.visit(`/bpb/${row.id}/log`);
  }
}

function confirmCancel() {
  if (!cancelTargetId.value) return;
  clearAll();
  axios
    .post(`/bpb/${cancelTargetId.value}/cancel`)
    .then(() => {
      addSuccess("BPB berhasil dibatalkan");
      fetchData({});
    })
    .catch((err) => {
      const serverErrors = err?.response?.data?.errors;
      if (serverErrors && typeof serverErrors === 'object') {
        const messages = (Object.values(serverErrors) as any).flat().join(' ');
        addError(messages || 'Gagal membatalkan dokumen');
      } else {
        addError(err?.response?.data?.message || 'Gagal membatalkan dokumen');
      }
    })
    .finally(() => {
      showConfirmCancel.value = false;
      cancelTargetId.value = null;
    });
}

function cancelCancel() {
  showConfirmCancel.value = false;
  cancelTargetId.value = null;
}

onMounted(() => fetchData({}));

// Mobile helpers (status badge & formatting)
function getStatusBadgeClassMobile(status: string) {
  return getSharedStatusBadgeClass(status || "Draft");
}

function formatDate(value?: string) {
  if (!value) return "-";
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString("id-ID", {
    year: "numeric",
    month: "short",
    day: "numeric",
  });
}

function formatCurrency(amount: number | null | undefined) {
  if (amount === null || amount === undefined) return "-";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(amount);
}

// Mobile selection & actions mirror table permissions
function canSendRow(row: any) {
  if (!row) return false;
  if (!(row.status === "Draft" || row.status === "Rejected")) return false;
  return isCreatorRow(row) || isAdmin.value;
}

function getMobileSelectableIds(): number[] {
  const currentRows = rows.value || [];
  return currentRows
    .filter((row: any) => canSendRow(row))
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

function toggleMobileMenu(bpbId: number) {
  mobileMenuBpbId.value = mobileMenuBpbId.value === bpbId ? null : bpbId;
}

function handleMobileAction(action: "detail" | "download" | "log" | "edit" | "cancel", row: any) {
  mobileMenuBpbId.value = null;
  if (!row?.id) return;

  if (action === "detail") {
    router.visit(`/bpb/${row.id}/detail`);
    return;
  }
  if (action === "download") {
    window.open(`/bpb/${row.id}/download`, "_blank");
    return;
  }
  if (action === "log") {
    router.visit(`/bpb/${row.id}/log`);
    return;
  }
  if (action === "edit") {
    router.visit(`/bpb/${row.id}/edit`);
    return;
  }
  if (action === "cancel") {
    cancelTargetId.value = row.id;
    showConfirmCancel.value = true;
  }
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Desktop / Tablet header + actions -->
      <div class="mb-6 hidden items-center justify-between md:flex">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Bukti Penerimaan Barang</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <FileText class="w-4 h-4 mr-1" />
            Manage Bukti Penerimaan Barang data
          </div>
        </div>
        <div class="flex items-center gap-3">
          <button
            @click="onSend"
            :disabled="selected.length === 0"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4" />
            Kirim ({{ selected.length }})
          </button>

          <button
            @click="router.visit('/bpb/create')"
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

      <!-- Mobile header -->
      <div class="mb-4 md:hidden">
        <h1 class="text-xl font-bold text-gray-900">Bukti Penerimaan Barang</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <FileText class="mr-1 h-3 w-3" />
          Manage Bukti Penerimaan Barang data
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
            @click="onSend"
            :disabled="selected.length === 0"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              selected.length > 0
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
            @click="router.visit('/bpb/create')"
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

      <!-- Desktop / Tablet: Filters + Table -->
      <div class="hidden md:block">
        <BpbFilter
          :department-options="departmentOptions"
          :supplier-options="supplierOptions"
          :columns="columns"
          @filter="onFilter"
          @reset="onReset"
          @update:columns="updateColumns"
        />

        <BpbTable
          :data="rows"
          :pagination="meta"
          :columns="columns"
          @select="(ids: number[]) => (selected = ids)"
          @action="onAction"
          @paginate="onPaginate"
        />
      </div>

      <!-- Mobile: Card list -->
      <div class="mt-4 md:hidden">
        <!-- Simple search -->
        <div class="mb-4">
          <input
            v-model="mobileSearch"
            type="text"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="Cari No. BPB / No. PO / Supplier"
            @keyup.enter="fetchData({ search: mobileSearch })"
            @blur="fetchData({ search: mobileSearch })"
          />
        </div>

        <div
          v-if="rows.length === 0"
          class="py-8 text-center text-sm text-gray-500"
        >
          Belum ada Bukti Penerimaan Barang yang terdaftar.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="row in rows"
            :key="row.id"
            class="w-full rounded-xl bg-white p-3 text-left shadow-sm active:bg-slate-50"
          >
            <div class="mb-1 flex items-start justify-between">
              <div class="flex items-center gap-2">
                <input
                  v-if="canSendRow(row)"
                  type="checkbox"
                  :value="row.id"
                  v-model="selected"
                  class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-1 focus:ring-blue-500 self-center"
                  @click.stop
                />
                <div>
                  <div class="text-xs font-semibold text-gray-500">No. BPB</div>
                  <div class="text-xs font-semibold text-gray-900">
                    {{ row.no_bpb || '-' }}
                  </div>
                  <div class="mt-1 text-[11px] text-gray-500">
                    No. PO: {{ row.purchase_order?.no_po || '-' }}
                  </div>
                </div>
              </div>

              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="getStatusBadgeClassMobile(row.status)"
              >
                {{ row.status || '-' }}
              </span>
            </div>

            <div class="mt-1 text-xs text-gray-500 truncate">
              {{ row.purchase_order?.perihal?.nama || '-' }}
            </div>

            <div class="mt-2 flex items-end justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="text-[11px] text-gray-500">Supplier</div>
                <div class="truncate text-xs font-medium text-gray-900">
                  {{ row.supplier?.nama_supplier || '-' }}
                </div>
              </div>

              <div class="text-right">
                <div class="text-[11px] text-gray-500">Grand Total</div>
                <div class="text-sm font-semibold text-emerald-700">
                  {{ formatCurrency(row.grand_total || 0) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-400">
                  {{ row.tanggal ? formatDate(row.tanggal) : '-' }}
                </div>
              </div>
            </div>

            <!-- Mobile card actions menu -->
            <div class="mt-2 flex justify-end">
              <div class="relative inline-block text-left">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                  @click.stop="toggleMobileMenu(row.id)"
                >
                  <span class="sr-only">Buka menu</span>
                  <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3zm0 5.25a1.5 1.5 0 110 3 1.5 1.5 0 010-3z"
                    />
                  </svg>
                </button>

                <div
                  v-if="mobileMenuBpbId === row.id"
                  class="absolute right-0 z-20 mt-1 w-40 origin-top-right rounded-lg bg-white py-1 text-xs shadow-lg ring-1 ring-black/5"
                >
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('detail', row)"
                  >
                    Detail
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('download', row)"
                  >
                    Download
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('log', row)"
                  >
                    Log
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canSendRow(row)"
                    @click.stop="handleMobileAction('edit', row)"
                  >
                    Edit
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canSendRow(row)"
                    @click.stop="handleMobileAction('cancel', row)"
                  >
                    Batalkan
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div
          v-if="meta && (meta.data || []).length > 0"
          class="mt-4 flex items-center justify-center border-t border-gray-200 pt-4"
        >
          <nav
            class="flex w-full max-w-xs items-center justify-between text-xs text-gray-600"
            aria-label="Pagination"
          >
            <button
              type="button"
              @click="onPaginate(meta.prev_page_url)"
              :disabled="!meta.prev_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                meta.prev_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Prev
            </button>

            <div class="px-3 py-1 text-[11px] text-gray-500">
              Halaman
              <span class="font-semibold text-gray-800">{{ meta.current_page || 1 }}</span>
            </div>

            <button
              type="button"
              @click="onPaginate(meta.next_page_url)"
              :disabled="!meta.next_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                meta.next_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Next
            </button>
          </nav>
        </div>
      </div>

      <StatusLegend entity="BPB" />

    <!-- Confirm Dialogs -->
    <ConfirmDialog
      :show="showConfirmSend"
      :message="`Apakah Anda yakin ingin mengirim ${selected.length} dokumen BPB?`"
      @confirm="confirmSend"
      @cancel="cancelSend"
    />
    <ConfirmDialog
      :show="showConfirmCancel"
      message="Apakah Anda yakin ingin membatalkan dokumen BPB ini?"
      @confirm="confirmCancel"
      @cancel="cancelCancel"
    />
    </div>
  </div>
</template>
