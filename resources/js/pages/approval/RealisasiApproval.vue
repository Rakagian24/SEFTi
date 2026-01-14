<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Realisasi Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <Grid2x2Check class="w-4 h-4 mr-1" />
            Dokumen Realisasi yang menunggu persetujuan
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
      <RealisasiApprovalFilter
        :filters="filters"
        :departments="departments"
        @filter="onFilter"
        @reset="onReset"
      />

      <!-- Table -->
      <RealisasiApprovalTable
        :data="rows"
        :selected="selectedIds"
        :current-role="userRole"
        @select="(ids:number[]) => { selectedIds = ids; }"
        @action="onRowAction"
      />
    </div>

    <ApprovalConfirmationDialog
      :is-open="showApprovalDialog"
      @update:open="showApprovalDialog = $event"
      @cancel="resetDialog"
      @confirm="confirmApproval"
    />
    <RejectionConfirmationDialog
      :is-open="showRejectionDialog"
      :require-reason="true"
      @update:open="showRejectionDialog = $event"
      @cancel="resetDialog"
      @confirm="confirmRejection"
    />
    <PasscodeVerificationDialog
      :is-open="showPasscodeDialog"
      :action="passcodeAction"
      :action-data="pendingAction"
      @update:open="showPasscodeDialog = $event"
      @cancel="resetDialog"
      @verified="doAction"
    />
    <SuccessDialog
      :is-open="showSuccessDialog"
      :action="successAction"
      :user-name="userName"
      document-type="Realisasi"
      @update:open="showSuccessDialog = $event"
      @close="() => (showSuccessDialog = false)"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Grid2x2Check } from 'lucide-vue-next';
import { useApi } from '@/composables/useApi';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import RejectionConfirmationDialog from '@/components/approval/RejectionConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import RealisasiApprovalFilter from '@/components/approval/RealisasiApprovalFilter.vue';
import RealisasiApprovalTable from '@/components/approval/RealisasiApprovalTable.vue';
import { getApprovalButtonClass as approvalBtnClass } from '@/lib/status';

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Approval', href: '/approval' },
  { label: 'Realisasi' },
];

const { get, post } = useApi();

const rows = ref<any[]>([]);
const departments = ref<any[]>([]);
const loading = ref(false);
const selectedIds = ref<number[]>([]);

const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<'verify' | 'approve' | 'reject'>('approve');
const successAction = ref<'verify' | 'approve' | 'reject'>('approve');
const pendingAction = ref<any | null>(null);

const userName = ref('');
const userRole = ref<string>('');

const filters = ref<any>({ search: '', status: '', department_id: '' });

const primaryActionType = computed<'verify' | 'approve'>(() => {
  const role = userRole.value;
  if (role === 'Kepala Toko') return 'verify';
  if (role === 'Kabag') return 'approve';
  if (role === 'Kadiv') return 'approve';
  return 'approve';
});

const primaryActionLabel = computed(() => ({ verify: 'Verifikasi', approve: 'Setujui' } as any)[primaryActionType.value]);

function openSingle(action: 'verify'|'approve', row: any) {
  pendingAction.value = { type: 'single', action, ids: [row.id], singleItem: row };
  showApprovalDialog.value = true;
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
      for (const id of ids) await post(`/api/approval/realisasis/${id}/verify`);
    } else if (act === 'approve') {
      for (const id of ids) await post(`/api/approval/realisasis/${id}/approve`);
    } else if (act === 'reject') {
      for (const id of ids) {
        await post(`/api/approval/realisasis/${id}/reject`, { reason: pendingAction.value.reason || '' });
      }
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

async function fetchDepartments() { try { const data = await get('/api/departments'); departments.value = data.data || []; } catch (error) { console.error(error); } }

async function fetchData() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.department_id) params.append('department_id', String(filters.value.department_id));

    const data = await get(`/api/approval/realisasis?${params.toString()}`);
    rows.value = data.data || [];
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
  fetchData();
}

function onReset() {
  filters.value = { search: '', status: '', department_id: '' };
  fetchData();
}

function onRowAction(evt: { action: 'verify'|'approve'|'detail'|'log'|'download'; row: any }) {
  const { action, row } = evt;
  if (action === 'detail') { router.visit(`/approval/realisasi/${row.id}/detail`); return; }
  if (action === 'log') { router.visit(`/approval/realisasi/${row.id}/log`); return; }
  if (action === 'download') { window.open(`/realisasi/${row.id}/download`, '_blank'); return; }

  if (action === 'verify' || action === 'approve') {
    openSingle(action, row);
  }
}

const page = usePage();
const user = page.props.auth?.user;
if (user) { userName.value = user.name || 'User'; userRole.value = (user as any).role?.name || ''; }

onMounted(async () => { await Promise.all([fetchDepartments(), fetchData()]); });

function getApprovalButtonClass(action: string) { return approvalBtnClass(action); }
</script>

<style lang="postcss" scoped>
  .form-input { @apply border rounded px-3 py-2 w-full; }
  .form-select { @apply border rounded px-3 py-2 w-full; }
  .btn-primary { @apply bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700; }
  .btn-secondary { @apply bg-white text-gray-700 border border-gray-300 px-3 py-2 rounded hover:bg-gray-50; }
  .btn-danger { @apply bg-white text-red-600 border border-red-600 px-3 py-2 rounded hover:bg-red-50; }
</style>
