<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">PO Anggaran Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Wallet2 class="w-4 h-4 mr-1" />
            Dokumen PO Anggaran yang menunggu persetujuan
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
            @click="handleBulkPrimary"
            :disabled="selectedIds.length === 0"
            :class="[
              'inline-flex items-center gap-2 px-4 py-2 rounded-lg font-medium transition-all duration-200',
              selectedIds.length > 0
                ? getApprovalButtonClass(primaryActionType)
                : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
            </svg>
            {{ primaryActionLabel }}
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

      <!-- Filters -->
      <PoAnggaranApprovalFilter
        :filters="filters"
        :departments="departments"
        :perihals="perihals"
        :columns="columns"
        :entries-per-page="perPage"
        @filter="onFilter"
        @reset="onReset"
        @update:entriesPerPage="(n:number) => { perPage = n; }"
        @update:columns="(cols:any) => { columns = cols; }"
      />

      <!-- Table -->
      <PoAnggaranApprovalTable
        :data="rows"
        :pagination="pagination"
        :selected="selectedIds"
        :columns="columns"
        :current-role="userRole"
        @select="(ids:number[]) => { selectedIds = ids; }"
        @action="onRowAction"
        @paginate="goToPage"
      />
    </div>

    <ApprovalConfirmationDialog :is-open="showApprovalDialog" @update:open="showApprovalDialog = $event" @cancel="resetDialog" @confirm="confirmApproval" />
    <RejectionConfirmationDialog :is-open="showRejectionDialog" :require-reason="true" @update:open="showRejectionDialog = $event" @cancel="resetDialog" @confirm="confirmRejection" />
    <PasscodeVerificationDialog :is-open="showPasscodeDialog" :action="passcodeAction" :action-data="pendingAction" @update:open="showPasscodeDialog = $event" @cancel="resetDialog" @verified="doAction" />
    <SuccessDialog :is-open="showSuccessDialog" :action="successAction" :user-name="userName" document-type="PO Anggaran" @update:open="showSuccessDialog = $event" @close="() => (showSuccessDialog = false)" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Wallet2 } from 'lucide-vue-next';
import { useApi } from '@/composables/useApi';
import PoAnggaranApprovalFilter from '@/components/approval/PoAnggaranApprovalFilter.vue';
import PoAnggaranApprovalTable from '@/components/approval/PoAnggaranApprovalTable.vue';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import RejectionConfirmationDialog from '@/components/approval/RejectionConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import { getApprovalButtonClass as approvalBtnClass } from '@/lib/status';

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Approval', href: '/approval' },
  { label: 'PO Anggaran' },
];

const { get, post } = useApi();

const rows = ref<any[]>([]);
const pagination = ref<any | null>(null);
const departments = ref<any[]>([]);
const perihals = ref<any[]>([]);
const loading = ref(false);
const selectedIds = ref<number[]>([]);
const perPage = ref<number>(10);
const columns = ref<any[]>([
  { key: 'no_po_anggaran', label: 'No. PO Anggaran', checked: true, sortable: true },
  { key: 'tanggal', label: 'Tanggal', checked: true, sortable: true },
  { key: 'department', label: 'Departemen', checked: true },
  // Optional columns (unchecked by default)
  { key: 'perihal', label: 'Perihal', checked: false },
  { key: 'metode_pembayaran', label: 'Metode Pembayaran', checked: false },
  { key: 'bank', label: 'Bank', checked: false },
  { key: 'bisnis_partner', label: 'Bisnis Partner', checked: false },
  { key: 'nama_rekening', label: 'Nama Rekening', checked: false },
  { key: 'no_rekening', label: 'No. Rekening', checked: false },
  { key: 'no_giro', label: 'No. Giro', checked: false },
  { key: 'tanggal_giro', label: 'Tanggal Giro', checked: false },
  { key: 'tanggal_cair', label: 'Tanggal Cair', checked: false },
  { key: 'nominal', label: 'Nominal', checked: true, sortable: true },
  { key: 'detail_keperluan', label: 'Detail Keperluan', checked: false },
  { key: 'note', label: 'Catatan', checked: false },
  { key: 'created_by', label: 'Dibuat Oleh', checked: false },
  { key: 'created_at', label: 'Dibuat Tanggal', checked: false },
  { key: 'status', label: 'Status', checked: true, sortable: true },
]);

const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<'verify' | 'validate' | 'approve' | 'reject'>('approve');
const successAction = ref<'verify' | 'validate' | 'approve' | 'reject'>('approve');
const pendingAction = ref<any | null>(null);

const userName = ref('');
const userRole = ref<string>('');

const filters = ref<any>({
  search: '',
  status: '',
  department_id: '',
  perihal_id: '',
  metode_pembayaran: '',
});

// Selection managed by table component

// Primary bulk action depends on current selection + role, mirip Purchase Order
const primaryActionType = computed<'verify' | 'validate' | 'approve'>(() => {
  const role = userRole.value;
  const ids = selectedIds.value || [];

  if (ids.length === 0) {
    // Default label ketika belum ada yang dipilih
    if (role === 'Kepala Toko' || role === 'Kabag') return 'verify';
    if (role === 'Kadiv') return 'validate';
    return 'approve';
  }

  const firstRow = (rows.value || []).find((r: any) => r.id === ids[0]);
  const status: string | undefined = firstRow?.status;
  const creatorRole: string | undefined = firstRow?.creator?.role?.name;

  if (role === 'Kepala Toko' || role === 'Kabag') {
    return 'verify';
  }

  if (role === 'Kadiv') {
    return 'validate';
  }

  if (role === 'Direksi' || role === 'Direksi Finance') {
    return 'approve';
  }

  if (role === 'Admin') {
    if (status === 'In Progress') {
      return creatorRole === 'Staff Digital Marketing' ? 'validate' : 'verify';
    }
    if (status === 'Verified') {
      if (creatorRole === 'Staff Akunting & Finance' || creatorRole === 'Kabag') return 'approve';
      return 'validate';
    }
    if (status === 'Validated') {
      return 'approve';
    }
    return 'approve';
  }

  // Fallback konservatif
  return 'approve';
});

const primaryActionLabel = computed(() => ({ verify: 'Verifikasi', validate: 'Validasi', approve: 'Setujui' } as any)[primaryActionType.value]);

// Row action visibility is handled by PoAnggaranApprovalTable

function openSingle(action: 'verify'|'validate'|'approve'|'reject', row: any) {
  pendingAction.value = { type: 'single', action, ids: [row.id], singleItem: row };
  if (action === 'reject') showRejectionDialog.value = true; else showApprovalDialog.value = true;
}

function handleBulkPrimary() {
  if (selectedIds.value.length === 0) return;
  pendingAction.value = { type: 'bulk', action: primaryActionType.value, ids: [...selectedIds.value] };
  showApprovalDialog.value = true;
}

function handleBulkReject() {
  if (selectedIds.value.length === 0) return;
  pendingAction.value = { type: 'bulk', action: 'reject', ids: [...selectedIds.value] };
  showRejectionDialog.value = true;
}

function resetDialog() {
  showApprovalDialog.value = false;
  showRejectionDialog.value = false;
  showPasscodeDialog.value = false;
  pendingAction.value = null;
}

async function confirmApproval() {
  passcodeAction.value = pendingAction.value.action;
  showApprovalDialog.value = false;
  showPasscodeDialog.value = true;
}

async function confirmRejection(reason: string) {
  if (pendingAction.value) pendingAction.value.reason = reason;
  passcodeAction.value = 'reject';
  showRejectionDialog.value = false;
  showPasscodeDialog.value = true;
}

async function doAction() {
  if (!pendingAction.value) return;
  try {
    const act = pendingAction.value.action;
    const ids: number[] = pendingAction.value.ids || [];
    if (act === 'verify') {
      for (const id of ids) await post(`/api/approval/po-anggarans/${id}/verify`);
    } else if (act === 'validate') {
      for (const id of ids) await post(`/api/approval/po-anggarans/${id}/validate`);
    } else if (act === 'approve') {
      for (const id of ids) await post(`/api/approval/po-anggarans/${id}/approve`);
    } else if (act === 'reject') {
      for (const id of ids) await post(`/api/approval/po-anggarans/${id}/reject`, { reason: pendingAction.value.reason || '' });
    }

    await fetchData();
    selectedIds.value = [];
    successAction.value = act;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;
  } catch (e) {
    console.error(e);
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
}

async function fetchDepartments() {
  try { const data = await get('/api/departments'); departments.value = data.data || []; } catch (error) { console.error(error); }
}

async function fetchPerihals() {
  try {
    const data = await get('/api/perihals');
    perihals.value = data.data || [];
  } catch (error) {
    console.error('fetchPerihals error', error);
  }
}

async function fetchData() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.department_id) params.append('department_id', String(filters.value.department_id));
    if (filters.value.perihal_id) params.append('perihal_id', String(filters.value.perihal_id));
    if (filters.value.metode_pembayaran) params.append('metode_pembayaran', String(filters.value.metode_pembayaran));
    if (perPage.value) params.append('per_page', String(perPage.value));

    const data = await get(`/api/approval/po-anggarans?${params.toString()}`);
    rows.value = data.data || [];
    pagination.value = data.pagination || null;
    // Try normalize created_by_role from backend if available in relation; otherwise leave undefined
  } catch (e) {
    console.error('fetchData error', e);
  } finally {
    loading.value = false;
  }
}

function onFilter(payload: any) {
  filters.value.search = payload.search || '';
  filters.value.status = payload.status || '';
  filters.value.department_id = payload.department_id || '';
  filters.value.perihal_id = payload.perihal_id || '';
  filters.value.metode_pembayaran = payload.metode_pembayaran || '';
  perPage.value = payload.entriesPerPage || perPage.value;
  fetchData();
}

function onReset() {
  filters.value = { search: '', status: '', department_id: '', perihal_id: '', metode_pembayaran: '' };
  perPage.value = 10;
  fetchData();
}

function onRowAction(evt: { action: 'verify'|'validate'|'approve'|'reject'|'detail'|'download'|'log'; row: any }) {
  const { action, row } = evt;
  if (action === 'detail') { router.visit(`/approval/po-anggaran/${row.id}/detail`); return; }
  if (action === 'download') { window.open(`/po-anggaran/${row.id}/download`, '_blank'); return; }
  if (action === 'log') { router.visit(`/approval/po-anggaran/${row.id}/log`); return; }
  if (action === 'reject') { openSingle('reject', row); return; }

  // Map generic 'approve' to specific step per role & status
  let mapped: 'verify'|'validate'|'approve' = 'approve';
  const role = userRole.value;
  const status = row?.status;
  const creatorRole = row?.creator?.role?.name;

  if (role === 'Kepala Toko' || role === 'Kabag') {
    mapped = 'verify';
  } else if (role === 'Kadiv') {
    if (status === 'In Progress' && creatorRole === 'Staff Digital Marketing') mapped = 'validate';
    else mapped = 'validate';
  } else if (role === 'Direksi' || role === 'Direksi Finance') {
    mapped = 'approve';
  } else if (role === 'Admin') {
    if (status === 'In Progress') {
      mapped = (creatorRole === 'Staff Digital Marketing') ? 'validate' : 'verify';
    } else if (status === 'Verified') {
      // If chain typically has Kadiv (Staff Toko/Kepala Toko), validate; Finance path approves
      if (creatorRole === 'Staff Akunting & Finance' || creatorRole === 'Kabag') mapped = 'approve';
      else mapped = 'validate';
    } else if (status === 'Validated') {
      mapped = 'approve';
    } else {
      mapped = 'approve';
    }
  }

  openSingle(mapped, row);
}

async function goToPage(url: string) {
  if (!url) return;
  loading.value = true;
  try {
    const data = await get(url);
    rows.value = data.data || [];
    pagination.value = data.pagination || null;
  } catch (e) {
    console.error('pagination error', e);
  } finally {
    loading.value = false;
  }
}

const page = usePage();
const user = page.props.auth?.user;
if (user) { userName.value = user.name || 'User'; userRole.value = (user as any).role?.name || ''; }

onMounted(async () => {
  await Promise.all([fetchDepartments(), fetchPerihals(), fetchData()]);
});

function getApprovalButtonClass(action: string) { return approvalBtnClass(action); }
</script>

<style lang="postcss" scoped>
  .form-input { @apply border rounded px-3 py-2 w-full; }
  .form-select { @apply border rounded px-3 py-2 w-full; }
  .btn-primary { @apply bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700; }
  .btn-secondary { @apply bg-white text-gray-700 border border-gray-300 px-3 py-2 rounded hover:bg-gray-50; }
  .btn-danger { @apply bg-white text-red-600 border border-red-600 px-3 py-2 rounded hover:bg-red-50; }
</style>
