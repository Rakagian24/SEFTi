<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Head title="Payment Voucher" />

      <Breadcrumbs :items="breadcrumbs" />

      <!-- Desktop / Tablet Layout -->
      <div class="hidden md:block">
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

            <button
              @click="exportExcel"
              class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-green-700 bg-green-100 border border-green-300 rounded-md hover:bg-green-200"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0,0,256,256" fill="currentColor">
                <g fill="currentColor" fill-rule="nonzero" stroke="none" stroke-width="1" stroke-linecap="butt" stroke-linejoin="miter" stroke-miterlimit="10" stroke-dasharray="" stroke-dashoffset="0" font-family="none" font-weight="none" font-size="none" text-anchor="none" style="mix-blend-mode: normal">
                  <g transform="scale(5.12,5.12)">
                    <path d="M28.875,0c-0.01953,0.00781 -0.04297,0.01953 -0.0625,0.03125l-28,5.3125c-0.47656,0.08984 -0.82031,0.51172 -0.8125,1v37.3125c-0.00781,0.48828 0.33594,0.91016 0.8125,1l28,5.3125c0.28906,0.05469 0.58984,-0.01953 0.82031,-0.20703c0.22656,-0.1875 0.36328,-0.46484 0.36719,-0.76172v-5h17c1.09375,0 2,-0.90625 2,-2v-34c0,-1.09375 -0.90625,-2 -2,-2h-17v-5c0.00391,-0.28906 -0.12109,-0.5625 -0.33594,-0.75391c-0.21484,-0.19141 -0.50391,-0.28125 -0.78906,-0.24609zM28,2.1875v4.34375c-0.13281,0.27734 -0.13281,0.59766 0,0.875v35.40625c-0.02734,0.13281 -0.02734,0.27344 0,0.40625v4.59375l-26,-4.96875v-35.6875zM30,8h17v34h-17v-5h4v-2h-4v-6h4v-2h-4v-5h4v-2h-4v-5h4v-2h-4zM36,13v2h8v-2zM6.6875,15.6875l5.46875,9.34375l-5.96875,9.34375h5l3.25,-6.03125c0.22656,-0.58203 0.375,-1.02734 0.4375,-1.3125h0.03125c0.12891,0.60938 0.25391,1.02344 0.375,1.25l3.25,6.09375h4.96875l-5.75,-9.4375l5.59375,-9.25h-4.6875l-2.96875,5.53125c-0.28516,0.72266 -0.48828,1.29297 -0.59375,1.65625h-0.03125c-0.16406,-0.60937 -0.35156,-1.15234 -0.5625,-1.59375l-2.6875,-5.59375zM36,20v2h8v-2zM36,27v2h8v-2zM36,35v2h8v-2z"></path>
                  </g>
                </g>
              </svg>
              Export to Excel
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
          :kelengkapan-dokumen="kelengkapanDokumen"
          :supplier-id="supplierId as any"
          :bisnis-partner-id="bisnisPartnerId as any"
          :department-options="departmentOptions"
          :supplier-options="supplierOptions"
          :bisnis-partner-options="bisnisPartnerOptions"
          :entries-per-page="entriesPerPage"
          :search="search"
          :columns="visibleColumns"
          @update:tanggal="updateTanggal"
          @update:noPv="(v:string)=> noPv = v"
          @update:departmentId="(v:any)=> departmentId = v"
          @update:status="(v:string)=> status = v"
          @update:tipe-pv="(v:string)=> { tipePv = v; applyFilters(); }"
          @update:metodeBayar="(v:string)=> { metodeBayar = v; applyFilters(); }"
          @update:kelengkapan-dokumen="(v:string)=> { kelengkapanDokumen = v; applyFilters(); }"
          @update:supplierId="(v:any)=> supplierId = v"
          @update:bisnisPartnerId="(v:any)=> { bisnisPartnerId = v; applyFilters(); }"
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
      </div>

      <!-- Mobile Layout -->
      <div class="md:hidden mt-4 space-y-5">
        <!-- Mobile header -->
        <div>
          <h1 class="text-xl font-bold text-gray-900">Payment Voucher</h1>
          <div class="mt-1 flex items-center text-xs text-gray-500">
            <TicketPercent class="mr-1 h-3 w-3" />
            Manage Payment Voucher data
          </div>
        </div>

        <!-- Mobile actions: Pilih semua + Kirim + Add New -->
        <div class="flex items-center justify-between gap-2">
          <div class="flex flex-col text-xs text-gray-600">
            <button
              type="button"
              class="mb-1 self-start rounded-full border border-blue-500 px-2 py-0.5 text-[11px] font-medium text-blue-600"
              @click="toggleMobileSelectAll"
            >
              {{ areAllMobileRowsSelected() ? 'Batal pilih semua' : 'Pilih semua' }}
            </button>
            <div>
              <span v-if="selectedIds.size > 0" class="font-semibold text-blue-600">
                {{ selectedIds.size }}
              </span>
              <span v-else class="text-gray-400">0</span>
              dokumen dipilih
            </div>
          </div>

          <div class="flex items-center gap-2">
            <button
              type="button"
              @click="sendDrafts"
              :disabled="!canSend"
              :class="[
                'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
                canSend
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

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="sendDrafts"
            :disabled="!canSend"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              canSend
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

      <div class="mt-4 md:hidden">
        <!-- Simple search -->
        <div class="mb-4">
          <input
            v-model="search"
            type="text"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="Cari No. PV / Supplier / Bisnis Partner / Perihal"
            @keyup.enter="applyFilters"
            @blur="applyFilters"
          />
        </div>

        <!-- Mobile card list -->
        <div
          v-if="(rows || []).length === 0"
          class="py-8 text-center text-sm text-gray-500"
        >
          Belum ada Payment Voucher yang terdaftar.
        </div>

        <div v-else class="space-y-3">
          <div
            v-for="pv in rows || []"
            :key="pv.id"
            class="w-full rounded-xl bg-white p-3 text-left shadow-sm active:bg-slate-50"
          >
            <div class="mb-1 flex items-start justify-between">
              <div class="flex items-center gap-2">
                <input
                  v-if="canSelectRowForBulk(pv)"
                  type="checkbox"
                  :checked="selectedIds.has(pv.id)"
                  class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-1 focus:ring-blue-500 self-center"
                  @click.stop="toggleRowMobile(pv, !selectedIds.has(pv.id))"
                />
                <div>
                  <div class="text-xs font-semibold text-gray-500">No. PV</div>
                  <div class="text-xs font-semibold text-gray-900">
                    {{ pv.no_pv || '-' }}
                  </div>
                </div>
              </div>

              <span
                class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium"
                :class="getStatusBadgeClassMobile(pv.status)"
              >
                {{ pv.status || '-' }}
              </span>
            </div>

            <div class="mt-1 text-xs text-gray-500 truncate">
              {{ pv.perihal?.nama || '-' }}
            </div>

            <div class="mt-2 flex items-end justify-between gap-2">
              <div class="min-w-0 flex-1">
                <div class="text-[11px] text-gray-500">
                  {{
                    pv.bisnis_partner?.nama_bp
                      ? 'Bisnis Partner'
                      : pv.supplier?.nama_supplier
                      ? 'Supplier'
                      : 'Supplier / Bisnis Partner'
                  }}
                </div>
                <div class="truncate text-xs font-medium text-gray-900">
                  {{ pv.bisnis_partner?.nama_bp || pv.supplier?.nama_supplier || '-' }}
                </div>
              </div>

              <div class="text-right">
                <div class="text-[11px] text-gray-500">Grand Total</div>
                <div class="text-sm font-semibold text-emerald-700">
                  {{ formatCurrencyMobile(pv.grand_total || 0) }}
                </div>
                <div class="mt-1 text-[11px] text-gray-400">
                  {{ pv.tanggal ? formatDateMobile(pv.tanggal) : '-' }}
                </div>
              </div>
            </div>

            <!-- Mobile card actions menu -->
            <div class="mt-2 flex justify-end">
              <div class="relative inline-block text-left">
                <button
                  type="button"
                  class="inline-flex items-center justify-center rounded-full p-1.5 text-gray-400 hover:bg-gray-100 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-1"
                  @click.stop="toggleMobileMenu(pv.id)"
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
                  v-if="mobileMenuPvId === pv.id"
                  class="absolute right-0 z-20 mt-1 w-40 origin-top-right rounded-lg bg-white py-1 text-xs shadow-lg ring-1 ring-black/5"
                >
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canShowDetailMobile(pv)"
                    @click.stop="handleMobileAction('detail', pv)"
                  >
                    Detail
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('download', pv)"
                  >
                    Download
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    @click.stop="handleMobileAction('log', pv)"
                  >
                    Log
                  </button>
                  <button
                    type="button"
                    class="block w-full px-3 py-2 text-left text-gray-700 hover:bg-gray-50"
                    v-if="canEditRowMobile(pv)"
                    @click.stop="handleMobileAction('edit', pv)"
                  >
                    Edit
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Mobile Pagination -->
        <div
          v-if="pvPage && (pvPage.data || []).length > 0"
          class="mt-4 flex items-center justify-center border-t border-gray-200 pt-4"
        >
          <nav
            class="flex w-full max-w-xs items-center justify-between text-xs text-gray-600"
            aria-label="Pagination"
          >
            <button
              type="button"
              @click="handleMobilePaginate(pvPage.prev_page_url as any)"
              :disabled="!pvPage.prev_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                pvPage.prev_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Prev
            </button>

            <div class="px-3 py-1 text-[11px] text-gray-500">
              Halaman
              <span class="font-semibold text-gray-800">{{ (pvPage.current_page as any) || 1 }}</span>
            </div>

            <button
              type="button"
              @click="handleMobilePaginate(pvPage.next_page_url as any)"
              :disabled="!pvPage.next_page_url"
              :class="[
                'rounded-full border px-3 py-1.5 font-medium transition-colors',
                pvPage.next_page_url
                  ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                  : 'border-transparent text-gray-400 cursor-not-allowed',
              ]"
            >
              Next
            </button>
          </nav>
        </div>
      </div>
      <StatusLegend entity="Payment Voucher" />

      <ConfirmDialog
        :show="confirmShow"
        :message="confirmMessage"
        @confirm="onConfirm"
        @cancel="onCancel"
      />

      <form
        ref="exportForm"
        action="/payment-voucher/export-excel"
        method="POST"
        target="_blank"
        class="hidden"
      >
        <input type="hidden" name="_token" :value="csrfToken" />
        <input type="hidden" name="tanggal_start" :value="tanggal?.start || ''" />
        <input type="hidden" name="tanggal_end" :value="tanggal?.end || ''" />
        <input type="hidden" name="no_pv" :value="noPv" />
        <input type="hidden" name="department_id" :value="departmentId as any" />
        <input type="hidden" name="status" :value="status" />
        <input type="hidden" name="tipe_pv" :value="tipePv" />
        <input type="hidden" name="metode_bayar" :value="metodeBayar" />
        <input type="hidden" name="supplier_id" :value="supplierId as any" />
        <input type="hidden" name="export_columns" :value="exportColumns" />
      </form>
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
import { getStatusBadgeClass as getSharedStatusBadgeClass } from "@/lib/status";

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
  // Additional optional fields used in mobile card view
  grand_total?: number | null;
  perihal?: { nama?: string | null } | null;
  bisnis_partner?: { nama_bp?: string | null } | null;
  supplier?: { nama_supplier?: string | null } | null;
};

const page = usePage();
const { addSuccess, addError, clearAll } = useMessagePanel();
const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '';
const exportForm = ref<HTMLFormElement | null>(null);

const breadcrumbs = computed(() => [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher" },
]);

// Filters
const tanggal = ref<{ start?: string | null; end?: string | null }>({
  start: (page.props as any).filters?.tanggal_start
    ? ((page.props as any).filters.tanggal_start as string)
    : undefined,
  end: (page.props as any).filters?.tanggal_end
    ? ((page.props as any).filters.tanggal_end as string)
    : undefined,
});
const noPv = ref(((page.props as any).filters?.no_pv ?? "") as string);
const departmentId = ref<string | number | undefined>(
  ((page.props as any).filters?.department_id ?? undefined) as any
);
const status = ref<string>(((page.props as any).filters?.status ?? "") as string);
const tipePv = ref<string>(((page.props as any).filters?.tipe_pv ?? "") as string);
const metodeBayar = ref<string>(((page.props as any).filters?.metode_bayar ?? "") as string);
const kelengkapanDokumen = ref<string>(((page.props as any).filters?.kelengkapan_dokumen ?? "") as string);
const supplierId = ref<string | number | undefined>(
  ((page.props as any).filters?.supplier_id ?? undefined) as any
);
const bisnisPartnerId = ref<string | number | undefined>(
  ((page.props as any).filters?.bisnis_partner_id ?? undefined) as any
);

// Options from server props with client-side filtering
const departmentOptionsAll = (page.props as any).departmentOptions || [];
const supplierOptionsAll = (page.props as any).supplierOptions || [];
const bisnisPartnerOptionsAll = (page.props as any).bisnisPartnerOptions || [];
const departmentOptions = ref<Array<{ value: string | number; label: string }>>(
  departmentOptionsAll
);
const supplierOptions = ref<Array<{ value: string | number; label: string }>>(
  supplierOptionsAll
);
const bisnisPartnerOptions = ref<Array<{ value: string | number; label: string }>>(
  bisnisPartnerOptionsAll
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
  { key: "bisnis_partner", label: "Bisnis Partner", checked: true },
  { key: "department", label: "Departemen", checked: true },
  // Extended columns (unchecked by default)
  { key: "perihal", label: "Perihal", checked: false },
  { key: "metode_pembayaran", label: "Metode Pembayaran", checked: false },
  { key: "kelengkapan_dokumen", label: "Kelengkapan Dokumen", checked: false },
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
const exportColumns = computed(() => (visibleColumns.value || []).filter(c => c.checked).map(c => c.key).join(','));

const selectedIds = ref<Set<PvRow["id"]>>(new Set());

// Mobile card three-dots menu state
const mobileMenuPvId = ref<number | string | null>(null);

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

function isCreatorRowLocal(row: any) {
  const creatorId = row?.creator?.id ?? row?.created_by_id ?? row?.user_id;
  if (!creatorId || !currentUserId.value) return false;
  return String(creatorId) === String(currentUserId.value);
}

function canEditRowMobile(row: any) {
  if (!row) return false;
  if (row.status === "Draft" || row.status === "Rejected" || row.status === "Approved") {
    return isCreatorRowLocal(row) || isAdmin.value;
  }
  return false;
}

function canShowDetailMobile(row: any) {
  if (!row) return false;
  const isCreator = isCreatorRowLocal(row);
  if (row.status === "Draft") {
    return !isCreator;
  }
  if (row.status === "Rejected") {
    return !isCreator;
  }
  return true;
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

// Helper for mobile checkbox toggle using full row
function toggleRowMobile(row: PvRow, val: boolean) {
  if (!canSelectRowForBulk(row)) return;
  onToggleRow({ id: row.id, val });
}

function getMobileSelectableCount(): number {
  const currentRows = rows.value || [];
  return currentRows.filter((r: any) => canSelectRowForBulk(r)).length;
}

function areAllMobileRowsSelected(): boolean {
  const selectableCount = getMobileSelectableCount();
  if (selectableCount === 0) return false;
  return selectedIds.value.size === selectableCount;
}

function toggleMobileSelectAll() {
  const selectableCount = getMobileSelectableCount();
  if (selectableCount === 0) {
    selectedIds.value = new Set();
    return;
  }
  const shouldUnselect = areAllMobileRowsSelected();
  onToggleAll(!shouldUnselect);
}

const canSend = computed(() => selectedIds.value.size > 0);

function resetFilters() {
  tanggal.value = {};
  noPv.value = "";
  departmentId.value = undefined;
  status.value = "";
  tipePv.value = "";
  metodeBayar.value = "";
  kelengkapanDokumen.value = "";
  supplierId.value = undefined;
  bisnisPartnerId.value = undefined;
  const params = { per_page: 10 };
  router.get("/payment-voucher", params, { preserveState: true });
}

function applyFilters() {
  const params: Record<string, any> = {};
  if (tanggal.value.start && tanggal.value.end) {
    params.tanggal_start = tanggal.value.start;
    params.tanggal_end = tanggal.value.end;
  }
  if (noPv.value) params.no_pv = noPv.value;
  if (departmentId.value) params.department_id = departmentId.value;
  if (status.value) params.status = status.value;
  if (tipePv.value) params.tipe_pv = tipePv.value;
  if (metodeBayar.value) params.metode_bayar = metodeBayar.value;
  if (kelengkapanDokumen.value !== "") params.kelengkapan_dokumen = kelengkapanDokumen.value;
  if (supplierId.value) params.supplier_id = supplierId.value;
  if (bisnisPartnerId.value) params.bisnis_partner_id = bisnisPartnerId.value;
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

// Simple helpers for mobile formatting/pagination/actions
function formatDateMobile(value?: string | null) {
  if (!value) return "-";
  const d = new Date(value);
  return d.toLocaleDateString("id-ID", {
    day: "numeric",
    month: "short",
    year: "numeric",
  });
}

function formatCurrencyMobile(base: number | null | undefined) {
  const num = Number(base ?? 0);
  if (!Number.isFinite(num)) return "-";
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(num);
}

function getStatusBadgeClassMobile(status: string) {
  return getSharedStatusBadgeClass(status || "Draft");
}

// Debounce auto-apply when filters change to avoid excessive requests
let _filterTimer: number | undefined;
function scheduleApplyFilters() {
  if (_filterTimer) window.clearTimeout(_filterTimer);
  _filterTimer = window.setTimeout(() => applyFilters(), 300);
}

function updateTanggal(v: any) {
  const merged = { ...(tanggal.value || {}), ...(v || {}) } as any;
  tanggal.value = merged;
  const hasStart = !!merged.start;
  const hasEnd = !!merged.end;
  if ((hasStart && hasEnd) || (!hasStart && !hasEnd)) {
    scheduleApplyFilters();
  }
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
          const missingDocs = res?.missing_documents as any[] | undefined;
          const hasDocHint = typeof msg === 'string' && /Dokumen belum lengkap/i.test(msg);
          if (!hasDocHint && Array.isArray(missingDocs) && missingDocs.length) {
            const first = missingDocs[0];
            const labels = (first?.missing_labels && first.missing_labels.length)
              ? first.missing_labels
              : (first?.missing_types || []);
            if (labels.length) {
              msg = `${msg} (Dokumen: ${labels.join(', ')})`;
            }
          }
        } catch {}
        addError(msg);
      });
  });
}

function toggleMobileMenu(id: PvRow["id"]) {
  mobileMenuPvId.value = mobileMenuPvId.value === id ? null : id;
}

function handleMobileAction(action: "detail" | "download" | "log" | "edit", row: PvRow | any) {
  mobileMenuPvId.value = null;
  const id = row?.id;
  if (!id) return;

  if (action === "detail") {
    router.visit(`/payment-voucher/${id}`);
  } else if (action === "edit") {
    router.visit(`/payment-voucher/${id}/edit`);
  } else if (action === "log") {
    router.visit(`/payment-voucher/${id}/log`);
  } else if (action === "download") {
    window.open(`/payment-voucher/${id}/download`, "_blank");
  }
}

function handleMobilePaginate(url: string | null | undefined) {
  if (!url) return;
  router.visit(url, { preserveState: true, preserveScroll: true });
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

function exportExcel() {
  try {
    exportForm.value?.submit();
  } catch {}
}

function filterSuppliersByDepartment(deptId: string | number | undefined | null) {
  if (!deptId) {
    supplierOptions.value = supplierOptionsAll;
    return;
  }
  const target = String(deptId || "");
  supplierOptions.value = (supplierOptionsAll || []).filter(
    (s: any) => String(s.department_id || "") === target || Boolean(s?.is_all)
  );
}

onMounted(() => {
  // initialize supplier options filtered by selected department if any
  if (departmentId.value) {
    filterSuppliersByDepartment(departmentId.value as any);
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
    tanggal.value?.start || "",
    tanggal.value?.end || "",
    noPv.value,
    departmentId.value,
    status.value,
    tipePv.value,
    metodeBayar.value,
    supplierId.value,
  ],
  () => {
    const start = tanggal.value?.start || "";
    const end = tanggal.value?.end || "";
    const dateReady = (!!start && !!end) || (!start && !end);
    if (dateReady) scheduleApplyFilters();
  },
  { deep: false }
);

watch(
  () => departmentId.value,
  (newVal) => {
    // filter suppliers by department when department changes
    filterSuppliersByDepartment(newVal as any);
  }
);
</script>
