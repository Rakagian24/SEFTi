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
          <div v-if="selectedIds.size > 0" class="flex items-center gap-2">
            <button
              @click="sendDrafts"
              :disabled="!canSend"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="w-4 h-4" />
              Kirim ({{ selectedIds.size }})
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

      <PaymentVoucherFilter
        :tanggal="tanggal"
        :no-pv="noPv"
        :department-id="departmentId as any"
        :status="status"
        :supplier-id="supplierId as any"
        :department-options="departmentOptions"
        :supplier-options="supplierOptions"
        :entries-per-page="entriesPerPage"
        :search="search"
        @update:tanggal="(v:any)=> tanggal = v"
        @update:noPv="(v:string)=> noPv = v"
        @update:departmentId="(v:any)=> departmentId = v"
        @update:status="(v:string)=> status = v"
        @update:supplierId="(v:any)=> supplierId = v"
        @update:entriesPerPage="(v:number)=> { entriesPerPage = v; applyFilters(); }"
        @update:search="(v:string)=> { search = v; applyFilters(); }"
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
const rows = ref<PvRow[]>((pvPage.value?.data ?? []) as PvRow[]);

// Column selector
type Column = { key: string; label: string; checked: boolean };
const columnOptions = ref<Column[]>([
  { key: "no_pv", label: "No. PV", checked: true },
  { key: "no_po", label: "No. PO", checked: true },
  { key: "no_bk", label: "No. BK", checked: true },
  { key: "tanggal", label: "Tanggal", checked: true },
  { key: "status", label: "Status", checked: true },
  { key: "supplier", label: "Supplier", checked: true },
  { key: "department", label: "Departemen", checked: true },
  { key: "action", label: "Action", checked: true },
]);
const visibleColumns = ref<Column[]>(columnOptions.value);

const selectedIds = ref<Set<PvRow["id"]>>(new Set());
function onToggleAll(val: boolean) {
  const selectable = rows.value.filter((r) => r.status === "Draft").map((r) => r.id);
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
  if (supplierId.value) params.supplier_id = supplierId.value;
  params.per_page = entriesPerPage.value;
  if (search.value) params.search = search.value;
  router.get("/payment-voucher", params, { preserveState: true, preserveScroll: true });
}

function sendDrafts() {
  if (!canSend.value) return;
  const ids = Array.from(selectedIds.value);
  router.post("/payment-voucher/send", { ids }, { preserveScroll: true });
}

function cancelPv(id: PvRow["id"]) {
  if (!confirm("Batalkan Payment Voucher ini?")) return;
  router.post(`/payment-voucher/${id}/cancel`, {}, { preserveScroll: true });
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
