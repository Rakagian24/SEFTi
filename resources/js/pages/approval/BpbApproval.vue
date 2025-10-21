<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">BPB Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <PackageIcon class="w-4 h-4 mr-1" />
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
              selectedIds.length > 0 ? getApprovalButtonClassForTemplate('approve') : 'bg-gray-300 text-gray-500 cursor-not-allowed',
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
              selectedIds.length > 0 ? 'bg-white text-red-600 border border-red-600 hover:bg-red-50' : 'bg-gray-300 text-gray-500 cursor-not-allowed',
            ]"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Tolak
          </button>
        </div>
      </div>

      <BpbApprovalFilter
        :filters="filters"
        :departments="departments"
        :entries-per-page="filters.per_page || 10"
        @filter="handleFilter"
        @reset="resetFilters"
        @update:entries-per-page="updateEntriesPerPage"
      />

      <BpbApprovalTable
        :data="rows"
        :loading="loading"
        :selected="selectedIds"
        :pagination="pagination"
        :selectable-statuses="selectableStatuses"
        :is-row-selectable="isRowSelectableForRole"
        @select="handleSelect"
        @action="handleAction"
        @paginate="handlePaginate"
      />
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
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import RejectionConfirmationDialog from '@/components/approval/RejectionConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import BpbApprovalFilter from '@/components/approval/BpbApprovalFilter.vue';
import BpbApprovalTable from '@/components/approval/BpbApprovalTable.vue';
import { useApi } from '@/composables/useApi';
import { getApprovalButtonClass } from '@/lib/status';
import { Package as PackageIcon } from 'lucide-vue-next';

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
        await post('/api/approval/bpbs/bulk-approve', { bpb_ids: pendingAction.value.ids });
      } else {
        await post(`/api/approval/bpbs/${pendingAction.value.ids[0]}/approve`);
      }
    } else if (pendingAction.value.action === 'reject') {
      if (pendingAction.value.type === 'bulk') {
        await post('/api/approval/bpbs/bulk-reject', { bpb_ids: pendingAction.value.ids, reason: pendingAction.value.reason || '' });
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
