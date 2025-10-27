<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Head title="Payment Voucher" />

      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Payment Voucher</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <TicketPercent class="w-4 h-4 mr-1" />
            Manage Payment Voucher data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <button
            @click="sendDrafts"
            :disabled="!canSend"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <Send class="w-4 h-4" />
            Kirim ({{ selectedIds.size }})
          </button>

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

      <PaymentVoucherFilter
        :tanggal="tanggal"
        :no-pv="noPv"
        :department-id="departmentId as any"
        :status="status"
        :tipe-pv="tipePv"
        :metode-bayar="metodeBayar"
        :supplier-id="supplierId as any"
        :department-options="departmentOptions"
        :supplier-options="supplierOptions"
        :entries-per-page="entriesPerPage"
        :search="search"
        :columns="visibleColumns"
        @update:tanggal="(v:any)=> tanggal = v"
        @update:noPv="(v:string)=> noPv = v"
        @update:departmentId="(v:any)=> departmentId = v"
        @update:status="(v:string)=> status = v"
        @update:tipe-pv="(v:string)=> { tipePv = v; applyFilters(); }"
        @update:metodeBayar="(v:string)=> { metodeBayar = v; applyFilters(); }"
        @update:supplierId="(v:any)=> supplierId = v"
        @update:entriesPerPage="(v:number)=> { entriesPerPage = v; applyFilters(); }"
        @update:search="(v:string)=> { search = v; applyFilters(); }"
        @update:columns="handleUpdateColumns"
        @reset="resetFilters"
        @apply="applyFilters"
      />

      <PaymentVoucherTable
        :rows="rows"
        :selected-ids="selectedIds"
        :pagination="pvPage"
        :visible-columns="visibleColumns"
        @toggleAll="onToggleAll"
        @toggleRow="onToggleRow"
        @cancel="cancelPv"
        @paginate="(url:string)=> router.visit(url, { preserveState: true, preserveScroll: true })"
      />
      <StatusLegend entity="Payment Voucher" />
      <ConfirmDialog
        :show="confirmShow"
        :message="confirmMessage"
        @confirm="onConfirm"
        @cancel="onCancel"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import AppLayout from "@/layouts/AppLayout.vue";
import { ref, computed, onMounted, watch } from "vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import PaymentVoucherFilter from "@/components/payment-voucher/PaymentVoucherFilter.vue";
import PaymentVoucherTable from "@/components/payment-voucher/PaymentVoucherTable.vue";
import { Send, TicketPercent } from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import StatusLegend from "@/components/ui/StatusLegend.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import axios from "axios";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";

defineOptions({ layout: AppLayout });

type PvRow = {
  id: number | string;
  no_pv?: string | null;
  no_po?: string | null;
  no_bk?: string | null;
  tanggal?: string | null;
  status: "Draft" | "In Progress" | "Rejected" | "Approved" | "Canceled";
  supplier_name?: string | null;
  department_name?: string | null;
};

const page = usePage();
const { addSuccess, addError, clearAll } = useMessagePanel();

const breadcrumbs = computed(() => [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher" },
]);

// Filters
const tanggal = ref<{ start?: Date | null; end?: Date | null }>({
  start: (page.props as any).filters?.tanggal_start
    ? new Date((page.props as any).filters.tanggal_start)
    : undefined,
  end: (page.props as any).filters?.tanggal_end
    ? new Date((page.props as any).filters.tanggal_end)
    : undefined,
});
const noPv = ref(((page.props as any).filters?.no_pv ?? "") as string);
const departmentId = ref<string | number | undefined>(
  ((page.props as any).filters?.department_id ?? undefined) as any
);
const status = ref<string>(((page.props as any).filters?.status ?? "") as string);
const tipePv = ref<string>(((page.props as any).filters?.tipe_pv ?? "") as string);
const metodeBayar = ref<string>(((page.props as any).filters?.metode_bayar ?? "") as string);
const supplierId = ref<string | number | undefined>(
  ((page.props as any).filters?.supplier_id ?? undefined) as any
);

// Options from server props with client-side filtering
const departmentOptionsAll = (page.props as any).departmentOptions || [];
const supplierOptionsAll = (page.props as any).supplierOptions || [];
const departmentOptions = ref<Array<{ value: string | number; label: string }>>(
  departmentOptionsAll
);
const supplierOptions = ref<Array<{ value: string | number; label: string }>>(
  supplierOptionsAll
);
const entriesPerPage = ref<number>(
  ((page.props as any).filters?.per_page ?? 10) as number
);
const search = ref<string>(((page.props as any).filters?.search ?? "") as string);

// Table data from server
const pvPage = computed(() => (usePage().props as any).paymentVouchers);
const rows = computed<PvRow[]>(() => (pvPage.value?.data ?? []) as PvRow[]);

// Column selector
type Column = { key: string; label: string; checked: boolean };
const columnOptions = ref<Column[]>([
  { key: "no_pv", label: "No. PV", checked: true },
  { key: "reference_number", label: "Nomor Referensi Dokumen", checked: true },
  { key: "no_bk", label: "No. BK", checked: true },
  { key: "tanggal", label: "Tanggal", checked: true },
  { key: "status", label: "Status", checked: true },
  { key: "supplier", label: "Supplier", checked: true },
  { key: "department", label: "Departemen", checked: true },
  // Extended columns (unchecked by default)
  { key: "perihal", label: "Perihal", checked: false },
  { key: "metode_pembayaran", label: "Metode Pembayaran", checked: false },
  { key: "nama_rekening", label: "Nama Rekening", checked: false },
  { key: "no_rekening", label: "No. Rekening", checked: false },
  { key: "no_kartu_kredit", label: "No. Kartu Kredit", checked: false },
//   { key: "no_giro", label: "No. Giro", checked: false },
//   { key: "tanggal_giro", label: "Tanggal Giro", checked: false },
//   { key: "tanggal_cair", label: "Tanggal Cair", checked: false },
  { key: "keterangan", label: "Keterangan", checked: false },
  { key: "total", label: "Total", checked: false },
//   { key: "diskon", label: "Diskon", checked: false },
//   { key: "ppn", label: "PPN", checked: false },
//   { key: "ppn_nominal", label: "PPN Nominal", checked: false },
//   { key: "pph_nominal", label: "PPH Nominal", checked: false },
  { key: "grand_total", label: "Grand Total", checked: false },
  { key: "created_by", label: "Dibuat Oleh", checked: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false },
]);
const visibleColumns = ref<Column[]>(columnOptions.value);

const selectedIds = ref<Set<PvRow["id"]>>(new Set());

const confirmShow = ref(false);
const confirmMessage = ref("");
let confirmAction: (() => void) | null = null;
function openConfirm(message: string, action: () => void) {
  confirmMessage.value = message;
  confirmAction = action;
  confirmShow.value = true;
}
function onConfirm() {
  confirmShow.value = false;
  const action = confirmAction;
  confirmAction = null;
  if (action) action();
}
function onCancel() {
  confirmShow.value = false;
  confirmAction = null;
}

const currentUserId = computed(() => (usePage().props as any)?.auth?.user?.id);
const isAdmin = computed(() => ((usePage().props as any)?.userRole || (usePage().props as any)?.auth?.user?.role?.name) === "Admin");

function canSelectRowForBulk(r: any) {
  const statusOk = r.status === "Draft" || r.status === "Rejected";
  if (!statusOk) return false;
  const creatorId = r?.creator?.id ?? r?.created_by_id ?? r?.user_id;
  if (isAdmin.value) return true;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

function handleUpdateColumns(cols: any[]) {
  visibleColumns.value = cols as any;
}

function onToggleAll(val: boolean) {
  const selectable = (rows.value || [])
    .filter((r) => canSelectRowForBulk(r))
    .map((r) => r.id);
  selectedIds.value = val ? new Set(selectable) : new Set();
}
function onToggleRow({ id, val }: { id: PvRow["id"]; val: boolean }) {
  const next = new Set(selectedIds.value);
  if (val) next.add(id);
  else next.delete(id);
  selectedIds.value = next;
}

const canSend = computed(() => selectedIds.value.size > 0);

function resetFilters() {
  tanggal.value = {};
  noPv.value = "";
  departmentId.value = undefined;
  status.value = "";
  tipePv.value = "";
  metodeBayar.value = "";
  supplierId.value = undefined;
  const params = { per_page: 10 };
  router.get("/payment-voucher", params, { preserveState: true });
}

function applyFilters() {
  const params: Record<string, any> = {};
  if (tanggal.value.start)
    params.tanggal_start = tanggal.value.start.toISOString().slice(0, 10);
  if (tanggal.value.end)
    params.tanggal_end = tanggal.value.end.toISOString().slice(0, 10);
  if (noPv.value) params.no_pv = noPv.value;
  if (departmentId.value) params.department_id = departmentId.value;
  if (status.value) params.status = status.value;
  if (tipePv.value) params.tipe_pv = tipePv.value;
  if (metodeBayar.value) params.metode_bayar = metodeBayar.value;
  if (supplierId.value) params.supplier_id = supplierId.value;
  params.per_page = entriesPerPage.value;
  if (search.value) {
    params.search = search.value;
    const selectedKeys = (visibleColumns.value || [])
      .filter((c) => c.checked)
      .map((c) => c.key);
    if (selectedKeys.length) {
      params.search_columns = selectedKeys.join(",");
    }
  }
  router.get("/payment-voucher", params, { preserveState: true, preserveScroll: true });
}

// Debounce auto-apply when filters change to avoid excessive requests
let _filterTimer: number | undefined;
function scheduleApplyFilters() {
  if (_filterTimer) window.clearTimeout(_filterTimer);
  _filterTimer = window.setTimeout(() => applyFilters(), 300);
}

function sendDrafts() {
  if (!canSend.value) return;
  const count = selectedIds.value.size;
  openConfirm(`Apakah Anda yakin ingin mengirim ${count} Payment Voucher?`, () => {
    const ids = Array.from(selectedIds.value);
    // Clear previous messages to avoid stacking validation and success popups
    try { clearAll(); } catch {}
    axios
      .post(
        "/payment-voucher/send",
        { ids },
        { withCredentials: true }
      )
      .then(({ data }) => {
        if (data && data.success) {
          try { clearAll(); } catch {}
          addSuccess("Payment Voucher berhasil dikirim");
          selectedIds.value = new Set();
          router.reload({ only: ["paymentVouchers"] });
        } else {
          const msg = (data && (data.message || data.error)) || "Gagal mengirim Payment Voucher.";
          addError(msg);
        }
      })
      .catch((e: any) => {
        const res = e?.response?.data;
        let msg = res?.message || res?.error || e?.message || "Gagal mengirim Payment Voucher.";
        try {
          const invalid = res?.invalid_fields as any[] | undefined;
          const missingDocs = res?.missing_documents as any[] | undefined;
          const parts: string[] = [];
          if (Array.isArray(invalid) && invalid.length) {
            const first = invalid[0];
            if (first?.missing?.length) parts.push(`Field: ${first.missing.join(", ")}`);
          }
          if (Array.isArray(missingDocs) && missingDocs.length) {
            const first = missingDocs[0];
            if (first?.missing_types?.length)
              parts.push(`Dokumen: ${first.missing_types.join(", ")}`);
          }
          if (parts.length) msg = `${msg} (${parts.join("; ")})`;
        } catch {}
        addError(msg);
      });
  });
}

function cancelPv(id: PvRow["id"]) {
  openConfirm("Apakah Anda yakin ingin membatalkan Payment Voucher ini?", () => {
    router.post(`/payment-voucher/${id}/cancel`, {}, {
      preserveScroll: true,
      onSuccess: () => {
        const next = new Set(selectedIds.value);
        next.delete(id);
        selectedIds.value = next;
      },
    });
  });
}

function goToAdd() {
  router.visit("/payment-voucher/create");
}

onMounted(() => {
  // initialize supplier options filtered by selected department if any
  if (departmentId.value) {
    supplierOptions.value = (supplierOptionsAll || []).filter(
      (s: any) => String(s.department_id || "") === String(departmentId.value || "")
    );
  }
});

// Watch for server flash messages and display via message panel
watch(
  () => page.props,
  (newProps) => {
    const flash = (newProps as any)?.flash || {};
    if (typeof flash.success === "string" && flash.success) {
      addSuccess(flash.success);
    }
    if (typeof flash.error === "string" && flash.error) {
      addError(flash.error);
    }
  },
  { immediate: true }
);

// Auto-apply when filter values change (debounced via scheduleApplyFilters)
watch(
  () => [
    tanggal.value?.start ? tanggal.value.start.toString() : "",
    tanggal.value?.end ? tanggal.value.end.toString() : "",
    noPv.value,
    departmentId.value,
    status.value,
    tipePv.value,
    metodeBayar.value,
    supplierId.value,
  ],
  () => scheduleApplyFilters(),
  { deep: false }
);

watch(
  () => departmentId.value,
  (newVal) => {
    // filter suppliers by department when department changes
    if (!newVal) {
      supplierOptions.value = supplierOptionsAll;
    } else {
      supplierOptions.value = (supplierOptionsAll || []).filter(
        (s: any) => String(s.department_id || "") === String(newVal || "")
      );
    }
  }
);
</script>
