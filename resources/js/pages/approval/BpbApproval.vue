<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Desktop / Tablet header + actions -->
      <div class="mb-6 hidden items-center justify-between md:flex">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">BPB Approval</h1>
          <div class="mt-2 flex items-center text-sm text-gray-500">
            <FileText class="w-4 h-4 mr-1" />
            Dokumen BPB yang menunggu persetujuan
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="text-sm text-gray-600">
            <span v-if="selectedIds.length > 0" class="font-medium text-blue-600">
              {{ selectedIds.length }}
            </span>
            <span v-else class="text-gray-400">0</span>
            dokumen dipilih
          </div>

          <button
            @click="handleBulkApprove"
            :disabled="selectedIds.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedIds.length > 0
                ? getApprovalButtonClassForTemplate('approve')
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            Setujui
          </button>

          <button
            @click="handleBulkReject"
            :disabled="selectedIds.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedIds.length > 0
                ? 'bg-white text-red-600 border border-red-600 hover:bg-red-50'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Tolak
          </button>
        </div>
      </div>

      <!-- Mobile header -->
      <div class="mb-4 md:hidden">
        <h1 class="text-xl font-bold text-gray-900">BPB Approval</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <FileText class="mr-1 h-3 w-3" />
          Dokumen BPB yang menunggu persetujuan
        </div>
      </div>

      <!-- Mobile actions: select all + selected count + approve/reject -->
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
            <span v-if="selectedIds.length > 0" class="font-semibold text-blue-600">
              {{ selectedIds.length }}
            </span>
            <span v-else class="text-gray-400">0</span>
            dokumen dipilih
          </div>
        </div>

        <div class="flex items-center gap-2">
          <button
            type="button"
            @click="handleBulkApprove"
            :disabled="selectedIds.length === 0"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              selectedIds.length > 0
                ? getApprovalButtonClassForTemplate('approve')
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
            <span>Setujui</span>
          </button>

          <button
            type="button"
            @click="handleBulkReject"
            :disabled="selectedIds.length === 0"
            :class="[
              'inline-flex items-center gap-1 rounded-lg px-3 py-1.5 text-xs font-medium transition-colors',
              selectedIds.length > 0
                ? 'bg-white text-red-600 border border-red-600 hover:bg-red-50'
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            <span>Tolak</span>
          </button>
        </div>
      </div>

      <!-- Desktop / Tablet: Filters + Table -->
      <div class="hidden md:block">
        <BpbApprovalFilter
          :filters="filters"
          :departments="departments"
          :entries-per-page="filters.per_page || 10"
          :columns="columns"
          @filter="handleFilter"
          @reset="resetFilters"
          @update:entries-per-page="updateEntriesPerPage"
          @update:columns="updateColumns"
        />

        <BpbApprovalTable
          :data="rows"
          :loading="loading"
          :selected="selectedIds"
          :pagination="pagination"
          :selectable-statuses="selectableStatuses"
          :is-row-selectable="isRowSelectableForRole"
          :columns="columns"
          @select="handleSelect"
          @action="handleAction"
          @paginate="handlePaginate"
        />

        <StatusLegend entity="BPB" />
      </div>

      <!-- Mobile: Card list -->
      <div class="mt-4 md:hidden">
        <!-- Simple search -->
        <div class="mb-4">
          <input
            v-model="filters.search"
            type="text"
            class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-700 placeholder-gray-400 focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
            placeholder="Cari No. BPB / No. PO / Supplier"
            @keyup.enter="fetchRows()"
            @blur="fetchRows()"
          />
        </div>

        <div v-if="loading" class="space-y-3">
          <div v-for="i in 3" :key="i" class="w-full rounded-xl bg-white p-3 text-left shadow-sm animate-pulse">
            <div class="mb-2 flex justify-between">
              <div class="h-4 w-28 rounded bg-slate-200" />
              <div class="h-4 w-16 rounded bg-slate-200" />
            </div>
            <div class="mb-2 h-3 w-40 rounded bg-slate-200" />
            <div class="flex items-end justify-between">
              <div class="h-4 w-24 rounded bg-slate-200" />
              <div class="h-3 w-20 rounded bg-slate-200" />
            </div>
          </div>
        </div>

        <div v-else>
          <div
            v-if="!rows || rows.length === 0"
            class="py-8 text-center text-sm text-gray-500"
          >
            Tidak ada dokumen BPB yang menunggu persetujuan.
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
                    v-if="isRowSelectableForRole(row)"
                    type="checkbox"
                    :value="row.id"
                    v-model="selectedIds"
                    class="h-4 w-4 self-center rounded border-gray-300 text-blue-600 focus:ring-1 focus:ring-blue-500"
                    @click.stop
                  />
                  <div>
                    <div class="text-xs font-semibold text-gray-500">No. BPB</div>
                    <div class="text-xs font-semibold text-gray-900">
                      {{ row.no_bpb || '-' }}
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
                {{ row.perihal || '-' }}
              </div>

              <div class="mt-2 flex items-end justify-between gap-2">
                <div class="min-w-0 flex-1">
                  <div class="text-[11px] text-gray-500">Supplier</div>
                  <div class="truncate text-xs font-medium text-gray-900">
                    {{ getSupplierLabel(row) }}
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
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Mobile Pagination -->
          <div
            v-if="pagination && rows && rows.length > 0"
            class="mt-4 flex items-center justify-center border-t border-gray-200 pt-4"
          >
            <nav
              class="flex w-full max-w-xs items-center justify-between text-xs text-gray-600"
              aria-label="Pagination"
            >
              <button
                type="button"
                @click="handlePaginate(pagination.prev_page_url)"
                :disabled="!pagination.prev_page_url"
                :class="[
                  'rounded-full border px-3 py-1.5 font-medium transition-colors',
                  pagination.prev_page_url
                    ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                    : 'border-transparent text-gray-400 cursor-not-allowed',
                ]"
              >
                Prev
              </button>

              <div class="px-3 py-1 text-[11px] text-gray-500">
                Halaman
                <span class="font-semibold text-gray-800">{{ filters.page }}</span>
              </div>

              <button
                type="button"
                @click="handlePaginate(pagination.next_page_url)"
                :disabled="!pagination.next_page_url"
                :class="[
                  'rounded-full border px-3 py-1.5 font-medium transition-colors',
                  pagination.next_page_url
                    ? 'border-gray-300 bg-white hover:bg-gray-50 hover:text-gray-800'
                    : 'border-transparent text-gray-400 cursor-not-allowed',
                ]"
              >
                Next
              </button>
            </nav>
          </div>
        </div>
      </div>
    </div>

    <ApprovalConfirmationDialog
      :is-open="showApprovalDialog"
      @update:open="showApprovalDialog = $event"
      @cancel="handleApprovalCancel"
      @confirm="handleApprovalConfirm"
    />

    <RejectionConfirmationDialog
      :is-open="showRejectionDialog"
      :require-reason="true"
      @update:open="showRejectionDialog = $event"
      @cancel="handleRejectionCancel"
      @confirm="handleRejectionConfirm"
    />

    <PasscodeVerificationDialog
      :is-open="showPasscodeDialog"
      :action="passcodeAction"
      :action-data="pendingAction"
      @update:open="showPasscodeDialog = $event"
      @cancel="handlePasscodeCancel"
      @verified="handlePasscodeVerified"
    />

    <SuccessDialog
      :is-open="showSuccessDialog"
      :action="successAction"
      :user-name="userName"
      document-type="BPB"
      @update:open="showSuccessDialog = $event"
      @close="handleSuccessClose"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import RejectionConfirmationDialog from '@/components/approval/RejectionConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import StatusLegend from '@/components/ui/StatusLegend.vue';
import BpbApprovalFilter from '@/components/approval/BpbApprovalFilter.vue';
import BpbApprovalTable from '@/components/approval/BpbApprovalTable.vue';
import { useApi } from '@/composables/useApi';
import { getApprovalButtonClass, getStatusBadgeClass as getSharedStatusBadgeClass } from '@/lib/status';
import { FileText } from 'lucide-vue-next';

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Approval', href: '/approval' },
  { label: 'BPB' },
];

function getApprovalButtonClassForTemplate(action: string) {
  return getApprovalButtonClass(action);
}

const { get, post } = useApi();

const rows = ref<any[]>([]);
const departments = ref<any[]>([]);
const loading = ref(false);
const selectedIds = ref<number[]>([]);
const pagination = ref<any>(null);

const filters = ref({
  search: '',
  department_id: '',
  status: '',
  per_page: 10,
  page: 1,
});

// Shared columns configuration (standardized like BPB list)
type Column = { key: string; label: string; checked: boolean; sortable?: boolean };

function updateColumns(c: any) {
  columns.value = Array.isArray(c) ? c : [];
}
const columns = ref<Column[]>([
  { key: 'no_bpb', label: 'No. BPB', checked: true, sortable: true },
  { key: 'no_po', label: 'No. PO', checked: true, sortable: true },
  { key: 'no_pv', label: 'No. PV', checked: false, sortable: true },
  { key: 'tanggal', label: 'Tanggal', checked: true, sortable: true },
  { key: 'status', label: 'Status', checked: true, sortable: true },
  { key: 'supplier', label: 'Supplier', checked: true },
  { key: 'department', label: 'Departemen', checked: false },
  { key: 'perihal', label: 'Perihal (PO)', checked: false },
  { key: 'subtotal', label: 'Subtotal', checked: false },
//  { key: 'diskon', label: 'Diskon', checked: false },
//  { key: 'dpp', label: 'DPP', checked: false },
//  { key: 'ppn', label: 'PPN', checked: false },
//  { key: 'pph', label: 'PPH', checked: false },
  { key: 'grand_total', label: 'Grand Total', checked: true },
  { key: 'keterangan', label: 'Keterangan', checked: false },
]);

const selectableStatuses = ref<string[]>(['In Progress']);

const page = usePage();
const userName = ref('');
const userRole = ref<string>('');

const user = page.props.auth?.user;
if (user) {
  userName.value = user.name || 'User';
  userRole.value = (user as any).role?.name || '';
}

function refreshSelectableStatuses() {
  const role = userRole.value;
  if (role === 'Admin') {
    selectableStatuses.value = ['In Progress'];
  } else if (role === 'Kepala Toko' || role === 'Kabag') {
    selectableStatuses.value = ['In Progress'];
  } else {
    selectableStatuses.value = ['In Progress'];
  }
}

function isRowSelectableForRole(row: any): boolean {
  const role = userRole.value;
  const status = row?.status;
  const creatorRole = row?.creator?.role?.name;

  if (role === 'Admin') return status === 'In Progress';

  if (role === 'Kepala Toko') {
    return status === 'In Progress' && creatorRole === 'Staff Toko';
  }
  if (role === 'Kabag') {
    return status === 'In Progress' && creatorRole === 'Staff Akunting & Finance';
  }
  return false;
}

const handleSelect = (ids: number[]) => { selectedIds.value = ids; };

// Mobile card helpers
const mobileMenuBpbId = ref<number | null>(null);

function toggleMobileMenu(bpbId: number) {
  mobileMenuBpbId.value = mobileMenuBpbId.value === bpbId ? null : bpbId;
}

function handleMobileAction(action: 'detail' | 'download' | 'log', row: any) {
  mobileMenuBpbId.value = null;
  if (!row?.id) return;

  if (action === 'detail') {
    router.visit(`/approval/bpbs/${row.id}/detail`);
    return;
  }
  if (action === 'download') {
    window.open(`/bpb/${row.id}/download`, '_blank');
    return;
  }
  if (action === 'log') {
    router.visit(`/approval/bpbs/${row.id}/log`);
  }
}

function getStatusBadgeClassMobile(status: string) {
  return getSharedStatusBadgeClass(status || 'Draft');
}

function getSupplierLabel(row: any) {
  if (!row) return '-';
  if (row.supplier && typeof row.supplier === 'object') {
    // Typical shape from table: supplier?.nama_supplier
    return row.supplier.nama_supplier || row.supplier.name || row.supplier.nama || '-';
  }
  if (typeof row.supplier === 'string') {
    return row.supplier;
  }
  if (row.supplier_name) return row.supplier_name;
  return '-';
}

function getMobileSelectableIds(): number[] {
  const currentRows = rows.value || [];
  const statuses = selectableStatuses.value || [];
  return currentRows
    .filter((row: any) => {
      const status = row?.status;
      return status && statuses.includes(status) && isRowSelectableForRole(row);
    })
    .map((row: any) => row.id)
    .filter((id: any) => typeof id === 'number');
}

function areAllMobileRowsSelected(): boolean {
  const selectableIds = getMobileSelectableIds();
  if (selectableIds.length === 0) return false;
  return selectableIds.every((id) => selectedIds.value.includes(id));
}

function toggleMobileSelectAll() {
  const selectableIds = getMobileSelectableIds();
  if (selectableIds.length === 0) {
    selectedIds.value = [];
    return;
  }
  if (selectableIds.every((id) => selectedIds.value.includes(id))) {
    selectedIds.value = selectedIds.value.filter((id) => !selectableIds.includes(id));
  } else {
    selectedIds.value = [...new Set([...selectedIds.value, ...selectableIds])];
  }
}

const handleFilter = (newFilters: any) => {
  const updated: any = { ...filters.value, ...newFilters, page: 1 };
  if (Object.prototype.hasOwnProperty.call(newFilters, 'entriesPerPage')) {
    updated.per_page = newFilters.entriesPerPage;
    delete updated.entriesPerPage;
  }
  if (Object.prototype.hasOwnProperty.call(newFilters, 'search')) {
    updated.search = newFilters.search || '';
  }
  Object.keys(updated).forEach((key) => {
    if (key !== 'search' && (updated[key] === '' || updated[key] === null || updated[key] === undefined)) {
      delete updated[key];
    }
  });
  filters.value = updated;
  fetchRows();
};

const resetFilters = () => {
  filters.value = { search: '', department_id: '', status: '', per_page: 10, page: 1 };
  fetchRows();
};

const updateEntriesPerPage = (perPage: number) => {
  filters.value.per_page = perPage;
  filters.value.page = 1;
  fetchRows();
};

const handlePaginate = (url: string) => {
  if (!url) return;
  try {
    const urlObj = new URL(url, window.location.origin);
    const page = urlObj.searchParams.get('page');
    if (page) {
      filters.value.page = parseInt(page);
      fetchRows();
    }
  } catch (e) {
    console.error('Error parsing pagination URL:', e);
  }
};

const handleAction = (actionData: any) => {
  const { action, row } = actionData;
  switch (action) {
    case 'approve':
      pendingAction.value = { type: 'single', action: 'approve', ids: [row.id], singleItem: row };
      showApprovalDialog.value = true;
      break;
    case 'reject':
      pendingAction.value = { type: 'single', action: 'reject', ids: [row.id], singleItem: row };
      showRejectionDialog.value = true;
      break;
    case 'detail':
      router.visit(`/approval/bpbs/${row.id}/detail`);
      break;
    case 'log':
      router.visit(`/approval/bpbs/${row.id}/log`);
      break;
    case 'download':
      window.open(`/bpb/${row.id}/download`, '_blank');
      break;
  }
};

const handleBulkApprove = () => {
  if (selectedIds.value.length === 0) return;
  pendingAction.value = { type: 'bulk', action: 'approve', ids: [...selectedIds.value] };
  showApprovalDialog.value = true;
};

const handleBulkReject = () => {
  if (selectedIds.value.length === 0) return;
  pendingAction.value = { type: 'bulk', action: 'reject', ids: [...selectedIds.value] };
  showRejectionDialog.value = true;
};

// Dialog state
const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<'approve' | 'reject'>('approve');
const successAction = ref<'approve' | 'reject'>('approve');
const pendingAction = ref<{
  type: 'bulk' | 'single';
  action: 'approve' | 'reject';
  ids: number[];
  singleItem?: any;
  reason?: string;
} | null>(null);

const handleApprovalCancel = () => { showApprovalDialog.value = false; pendingAction.value = null; };
const handleRejectionCancel = () => { showRejectionDialog.value = false; pendingAction.value = null; };
const handleApprovalConfirm = () => { if (!pendingAction.value) return; showApprovalDialog.value = false; passcodeAction.value = pendingAction.value.action; showPasscodeDialog.value = true; };
const handleRejectionConfirm = (reason: string) => { if (!pendingAction.value) return; pendingAction.value.reason = reason; showRejectionDialog.value = false; passcodeAction.value = 'reject'; showPasscodeDialog.value = true; };
const handlePasscodeCancel = () => { showPasscodeDialog.value = false; pendingAction.value = null; };

const handlePasscodeVerified = async () => {
  if (!pendingAction.value) return;
  try {
    if (pendingAction.value.action === 'approve') {
      if (pendingAction.value.type === 'bulk') {
        for (const id of pendingAction.value.ids) {
          await post(`/api/approval/bpbs/${id}/approve`, {}, {
            headers: { 'X-Bulk-Operation': 'true' }
          });
        }
        await post('/api/approval/bpbs/bulk-summary', {
          ids: pendingAction.value.ids,
          action: 'approve'
        });
      } else {
        await post(`/api/approval/bpbs/${pendingAction.value.ids[0]}/approve`);
      }
    } else if (pendingAction.value.action === 'reject') {
      if (pendingAction.value.type === 'bulk') {
        for (const id of pendingAction.value.ids) {
          await post(`/api/approval/bpbs/${id}/reject`, { reason: pendingAction.value.reason || '' }, {
            headers: { 'X-Bulk-Operation': 'true' }
          });
        }
        // Reject不需要bulk-summary，因为只通知creator
      } else {
        await post(`/api/approval/bpbs/${pendingAction.value.ids[0]}/reject`, { reason: pendingAction.value.reason || '' });
      }
    }
    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;
    await fetchRows();
    selectedIds.value = selectedIds.value.filter((id) => !pendingAction.value!.ids.includes(id));
  } catch (e) {
    console.error('BPB approval action error:', e);
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
};

const handleSuccessClose = () => { showSuccessDialog.value = false; };

function formatDate(date: string | null | undefined) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  });
}

function formatCurrency(value: number) {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0);
}

const fetchDepartments = async () => {
  try {
    const data = await get('/api/departments');
    departments.value = data.data || [];
  } catch (e) { console.error('Error fetching departments:', e); }
};

const fetchRows = async () => {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    Object.entries(filters.value).forEach(([k, v]) => { if (v) params.append(k, String(v)); });
    const data = await get(`/api/approval/bpbs?${params.toString()}`);
    rows.value = data.data || [];
    pagination.value = data.pagination || null;
  } catch (e) {
    console.error('Error fetching bpbs:', e);
  } finally {
    loading.value = false;
  }
};

onMounted(async () => {
  refreshSelectableStatuses();
  await Promise.all([fetchDepartments(), fetchRows()]);
});
</script>
