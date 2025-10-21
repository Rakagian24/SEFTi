<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">PO Anggaran Approval</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <FileText class="w-4 h-4 mr-1" />
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
      <div class="bg-white rounded-lg shadow p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
          <input v-model="filters.search" class="form-input" placeholder="Cari no / keperluan..." />
          <select v-model="filters.status" class="form-select">
            <option value="">Semua Status</option>
            <option value="In Progress">In Progress</option>
            <option value="Verified">Verified</option>
            <option value="Validated">Validated</option>
          </select>
          <select v-model="filters.department_id" class="form-select">
            <option value="">Semua Departemen</option>
            <option v-for="d in departments" :key="d.id" :value="d.id">{{ d.name }}</option>
          </select>
          <button class="btn-primary" @click="fetchData">Terapkan</button>
        </div>
      </div>

      <!-- Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-4 py-2 w-10"><input type="checkbox" :checked="allSelected" @change="toggleSelectAll" /></th>
              <th class="px-4 py-2 text-left">No. PO Anggaran</th>
              <th class="px-4 py-2 text-left">Departemen</th>
              <th class="px-4 py-2 text-left">Tanggal</th>
              <th class="px-4 py-2 text-left">Nominal</th>
              <th class="px-4 py-2 text-left">Status</th>
              <th class="px-4 py-2 text-right">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.id" class="border-t">
              <td class="px-4 py-2">
                <input type="checkbox" :value="row.id" v-model="selectedIds" :disabled="!isRowSelectable(row)" />
              </td>
              <td class="px-4 py-2">{{ row.no_po_anggaran || '-' }}</td>
              <td class="px-4 py-2">{{ row.department?.name || '-' }}</td>
              <td class="px-4 py-2">{{ row.tanggal || '-' }}</td>
              <td class="px-4 py-2">{{ formatCurrency(row.nominal) }}</td>
              <td class="px-4 py-2">{{ row.status }}</td>
              <td class="px-4 py-2 text-right space-x-2">
                <button v-if="canVerify(row)" class="btn-secondary" @click="openSingle('verify', row)">Verifikasi</button>
                <button v-if="canValidate(row)" class="btn-secondary" @click="openSingle('validate', row)">Validasi</button>
                <button v-if="canApprove(row)" class="btn-primary" @click="openSingle('approve', row)">Setujui</button>
                <button v-if="canReject(row)" class="btn-danger" @click="openSingle('reject', row)">Tolak</button>
              </td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="7" class="px-4 py-6 text-center text-gray-500">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <ApprovalConfirmationDialog :is-open="showApprovalDialog" @update:open="showApprovalDialog = $event" @cancel="resetDialog" @confirm="confirmApproval" />
    <RejectionConfirmationDialog :is-open="showRejectionDialog" :require-reason="true" @update:open="showRejectionDialog = $event" @cancel="resetDialog" @confirm="confirmRejection" />
    <PasscodeVerificationDialog :is-open="showPasscodeDialog" :action="passcodeAction" :action-data="pendingAction" @update:open="showPasscodeDialog = $event" @cancel="resetDialog" @verified="doAction" />
    <SuccessDialog :is-open="showSuccessDialog" :action="successAction" :user-name="userName" document-type="PO Anggaran" @update:open="showSuccessDialog = $event" @close="() => (showSuccessDialog = false)" />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { FileText } from 'lucide-vue-next';
import { useApi } from '@/composables/useApi';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import RejectionConfirmationDialog from '@/components/approval/RejectionConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
// import { getApprovalButtonClass } from '@/lib/status';

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Approval', href: '/approval' },
  { label: 'PO Anggaran' },
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
const passcodeAction = ref<'verify' | 'validate' | 'approve' | 'reject'>('approve');
const successAction = ref<'verify' | 'validate' | 'approve' | 'reject'>('approve');
const pendingAction = ref<any | null>(null);

const userName = ref('');
const userRole = ref<string>('');

const filters = ref<any>({
  search: '',
  status: '',
  department_id: '',
});

const allSelected = computed(() => rows.value.length > 0 && selectedIds.value.length === rows.value.filter(isRowSelectable).length);

function toggleSelectAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  if (!checked) {
    selectedIds.value = [];
  } else {
    selectedIds.value = rows.value.filter(isRowSelectable).map((r: any) => r.id);
  }
}

function formatCurrency(n: any) {
  const num = Number(n || 0);
  return num.toLocaleString('id-ID');
}

const primaryActionType = computed<'verify' | 'validate' | 'approve'>(() => {
  const role = userRole.value;
  if (role === 'Kepala Toko' || role === 'Kabag') return 'verify';
  if (role === 'Kadiv') return 'validate';
  return 'approve';
});

const primaryActionLabel = computed(() => ({ verify: 'Verifikasi', validate: 'Validasi', approve: 'Setujui' } as any)[primaryActionType.value]);

function isRowSelectable(row: any): boolean {
  const role = userRole.value;
  const creatorRole = row?.creator?.role?.name;
  if (role === 'Kepala Toko') return row.status === 'In Progress' && creatorRole === 'Staff Toko';
  if (role === 'Kabag') return row.status === 'In Progress' && creatorRole === 'Staff Akunting & Finance';
  if (role === 'Kadiv') return row.status === 'Verified' || (row.status === 'In Progress' && creatorRole === 'Staff Digital Marketing');
  if (role === 'Direksi') return row.status === 'Validated' || row.status === 'Verified';
  return ['Admin'].includes(role) ? ['In Progress', 'Verified', 'Validated'].includes(row.status) : false;
}

function canVerify(row: any) { return isRowSelectable(row) && ['In Progress'].includes(row.status) && ['Kepala Toko','Kabag','Admin'].includes(userRole.value); }
function canValidate(row: any) { return ['Kadiv','Admin'].includes(userRole.value) && row.status === 'Verified'; }
function canApprove(row: any) {
  const role = userRole.value;
  if (role === 'Direksi') return ['Validated','Verified'].includes(row.status);
  if (role === 'Admin') return ['Validated','Verified'].includes(row.status);
  return false;
}
function canReject(row: any) { return ['In Progress','Verified','Validated'].includes(row.status); }

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

async function fetchData() {
  loading.value = true;
  try {
    const params = new URLSearchParams();
    if (filters.value.search) params.append('search', filters.value.search);
    if (filters.value.status) params.append('status', filters.value.status);
    if (filters.value.department_id) params.append('department_id', String(filters.value.department_id));

    const data = await get(`/api/approval/po-anggarans?${params.toString()}`);
    rows.value = data.data || [];
    // Try normalize created_by_role from backend if available in relation; otherwise leave undefined
  } catch (e) {
    console.error('fetchData error', e);
  } finally {
    loading.value = false;
  }
}

const page = usePage();
const user = page.props.auth?.user;
if (user) { userName.value = user.name || 'User'; userRole.value = (user as any).role?.name || ''; }

onMounted(async () => {
  await Promise.all([fetchDepartments(), fetchData()]);
});

function getApprovalButtonClass(action: string) { return getApprovalButtonClass(action); }
</script>

<style lang="postcss" scoped>
  .form-input { @apply border rounded px-3 py-2 w-full; }
  .form-select { @apply border rounded px-3 py-2 w-full; }
  .btn-primary { @apply bg-blue-600 text-white px-3 py-2 rounded hover:bg-blue-700; }
  .btn-secondary { @apply bg-white text-gray-700 border border-gray-300 px-3 py-2 rounded hover:bg-gray-50; }
  .btn-danger { @apply bg-white text-red-600 border border-red-600 px-3 py-2 rounded hover:bg-red-50; }
</style>
