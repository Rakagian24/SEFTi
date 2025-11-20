<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail BPB (Approval)</h1>
            <FileText class="w-4 h-4 mr-1" />
            {{ bpb?.no_bpb || `BPB #${bpb?.id}` }}
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(bpb?.status)}`">
            <span class="w-2 h-2 rounded-full mr-2 inline-block" :class="getStatusDotClass(bpb?.status)"></span>
            {{ bpb?.status }}
          </span>
        </div>
      </div>

      <div
        v-if="bpb?.status === 'Rejected' && bpb?.rejection_reason"
        class="bg-white rounded-lg shadow-sm border border-red-200 p-6 mb-6"
      >
        <div class="flex items-start gap-2">
          <svg
            class="w-5 h-5 text-red-500 mt-0.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"
            />
          </svg>
          <div>
            <div class="text-sm font-semibold text-red-700">Alasan Penolakan</div>
            <p class="text-sm text-red-700 mt-1 whitespace-pre-wrap">
              {{ bpb.rejection_reason }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Informasi BPB</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div>
                  <p class="text-sm font-medium text-gray-900">No. BPB</p>
                  <p class="text-sm text-gray-600 font-mono">{{ bpb?.no_bpb || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Tanggal</p>
                  <p class="text-sm text-gray-600">{{ formatDate(bpb?.tanggal || null) }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Departemen</p>
                  <p class="text-sm text-gray-600">{{ bpb?.department?.name || '-' }}</p>
                </div>
              </div>
              <div class="space-y-4">
                <div>
                  <p class="text-sm font-medium text-gray-900">Supplier</p>
                  <p class="text-sm text-gray-600">{{ bpb?.supplier?.nama_supplier || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">No. PO</p>
                  <p class="text-sm text-gray-600 font-mono">{{ bpb?.purchase_order?.no_po || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">No. PV</p>
                  <p class="text-sm text-gray-600 font-mono">{{ bpb?.payment_voucher?.no_pv || '-' }}</p>
                </div>
              </div>
            </div>
          </div>


          <div v-if="bpb?.purchase_order" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Terkait</h3>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div>
                  <p class="text-sm font-medium text-gray-900">No. PO</p>
                  <p class="text-sm text-gray-600 font-mono">{{ bpb?.purchase_order?.no_po || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Tanggal PO</p>
                  <p class="text-sm text-gray-600">{{ formatDate(bpb?.purchase_order?.tanggal || null) }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">Perihal</p>
                  <p class="text-sm text-gray-600">{{ bpb?.purchase_order?.perihal?.nama || '-' }}</p>
                </div>
              </div>
              <div class="space-y-4">
                <div>
                  <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                  <p class="text-sm text-gray-600">{{ bpb?.purchase_order?.metode_pembayaran || '-' }}</p>
                </div>
                <div>
                  <p class="text-sm font-medium text-gray-900">No. Invoice</p>
                  <p class="text-sm text-gray-600 font-mono">{{ bpb?.purchase_order?.no_invoice || '-' }}</p>
                </div>
              </div>
            </div>

            <div v-if="(bpb?.purchase_order?.items || []).length" class="mt-6">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h4 class="text-base font-semibold text-gray-900">Items PO</h4>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                      <th class="px-4 py-3 text-left font-semibold text-gray-900">Nama Barang</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Qty</th>
                      <th class="px-4 py-3 text-left font-semibold text-gray-900">Satuan</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Harga</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr v-for="(poIt, idx) in bpb?.purchase_order?.items || []" :key="idx" class="hover:bg-gray-50">
                      <td class="px-4 py-3 text-gray-900">{{ poIt.nama_barang }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatInteger(poIt.qty) }}</td>
                      <td class="px-4 py-3 text-gray-600">{{ poIt.satuan }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(Number(poIt.harga)) }}</td>
                      <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(Number(poIt.qty) * Number(poIt.harga)) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Daftar Barang</h3>
            </div>
            <div class="overflow-x-auto">
              <table class="min-w-full text-sm">
                <thead>
                  <tr class="bg-gray-50 border-b border-gray-200">
                    <th class="px-4 py-3 text-left font-semibold text-gray-900">Nama Barang</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-900">Qty</th>
                    <th class="px-4 py-3 text-left font-semibold text-gray-900">Satuan</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-900">Harga</th>
                    <th class="px-4 py-3 text-right font-semibold text-gray-900">Subtotal</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                  <tr v-for="(it, idx) in bpb?.items || []" :key="idx" class="hover:bg-gray-50">
                    <td class="px-4 py-3 text-gray-900">{{ it.nama_barang }}</td>
                    <td class="px-4 py-3 text-right text-gray-900">{{ formatInteger(it.qty) }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ it.satuan }}</td>
                    <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(Number(it.harga)) }}</td>
                    <td class="px-4 py-3 text-right font-medium text-gray-900">{{ formatCurrency(Number(it.qty) * Number(it.harga)) }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div v-if="bpb?.keterangan" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
            </div>
            <div class="space-y-6">
              <div>
                <p class="text-sm font-medium text-gray-900 mb-2">Keterangan</p>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">{{ bpb?.keterangan }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <ApprovalProgress
            :progress="approvalProgress"
            :purchase-order="bpb"
            :user-role="userRole"
            :can-approve="canApprove"
            :can-reject="canReject"
            @approve="handleApproveClick"
            @reject="handleRejectClick"
          />

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan Keuangan</h3>
            </div>
            <div class="space-y-3">
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">Subtotal</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(Number(bpb?.subtotal || 0)) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">Diskon</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(Number(bpb?.diskon || 0)) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">DPP</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(Number(bpb?.dpp || 0)) }}</span>
              </div>
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">PPN</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(Number(bpb?.ppn || 0)) }}</span>
              </div>
              <!-- <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">PPH</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(Number(bpb?.pph || 0)) }}</span>
              </div> -->
              <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-semibold text-gray-900">Grand Total</span>
                  <span class="text-lg font-bold text-green-600">{{ formatCurrency(Number(bpb?.grand_total || 0)) }}</span>
                </div>
              </div>
            </div>
            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="text-center">
                <p class="text-xs text-gray-500 mb-2">Total Pembayaran</p>
                <p class="text-2xl font-bold text-indigo-600">
                  {{ formatCurrency(Number(bpb?.grand_total || 0)) }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Kembali ke Approval BPB
        </button>
      </div>

      <ApprovalConfirmationDialog :is-open="showApprove" @update:open="showApprove = $event" @cancel="closeApprove" @confirm="confirmApprove" />
      <RejectionConfirmationDialog :is-open="showReject" :require-reason="true" @update:open="showReject = $event" @cancel="closeReject" @confirm="confirmReject" />
      <PasscodeVerificationDialog :is-open="showPasscode" :action="passcodeAction" @update:open="showPasscode = $event" @cancel="() => showPasscode = false" @verified="onVerified" />
      <SuccessDialog :is-open="showSuccess" :action="successAction" :user-name="userName" document-type="BPB" @update:open="showSuccess = $event" @close="handleSuccessClose" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ApprovalProgress from '@/components/approval/ApprovalProgress.vue';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import RejectionConfirmationDialog from '@/components/approval/RejectionConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import { useApi } from '@/composables/useApi';
import { getStatusBadgeClass as getSharedStatusBadgeClass, getStatusDotClass as getSharedStatusDotClass } from '@/lib/status';
import { FileText } from 'lucide-vue-next';

defineOptions({ layout: AppLayout });

const props = defineProps<{ bpb: any }>();
const { post, get } = useApi();

const bpb = ref<any>(props.bpb || null);
const page = usePage();
const user = page.props.auth?.user as any;
const userName = ref(user?.name || 'User');
const userRole = ref<string>((user?.role?.name as string) || '');

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Approval', href: '/approval' },
  { label: 'BPB', href: '/approval/bpbs' },
  { label: bpb.value?.no_bpb || `BPB #${bpb.value?.id}` },
];

const showApprove = ref(false);
const showReject = ref(false);
const showPasscode = ref(false);
const showSuccess = ref(false);
const passcodeAction = ref<'approve' | 'reject'>('approve');
const successAction = ref<'approve' | 'reject'>('approve');

const rejectionReason = ref<string>('');

const canApprove = ref(false);
const canReject = ref(false);

// Approval progress state
const approvalProgress = ref<any[]>([]);
const loadingProgress = ref(false);

const handleSuccessClose = () => { showSuccess.value = false;
    router.visit('/approval/bpbs');
 };

function buildFallbackProgress() {
  const creatorRole = bpb.value?.creator?.role?.name;
  const roleLabel = creatorRole === 'Staff Akunting & Finance' ? 'Kabag' : 'Kabag';
  let stepStatus: 'pending' | 'current' | 'completed' | 'rejected' = 'pending';
  switch (bpb.value?.status) {
    case 'Approved':
      stepStatus = 'completed';
      break;
    case 'In Progress':
      stepStatus = 'current';
      break;
    case 'Rejected':
      stepStatus = 'rejected';
      break;
    default:
      stepStatus = 'pending';
  }
  return [
    {
      step: 'approved',
      role: roleLabel,
      status: stepStatus,
    },
  ];
}

function evaluatePermissions() {
  const role = user?.role?.name;
  const creatorRole = bpb.value?.creator?.role?.name;
  const status = bpb.value?.status;

  canApprove.value = false;
  canReject.value = false;

  if (status === 'In Progress') {
    if (role === 'Admin') { canApprove.value = canReject.value = true; return; }
    if (role === 'Kepala Toko' && creatorRole === 'Staff Toko') canApprove.value = canReject.value = true;
    if (role === 'Kabag' && creatorRole === 'Staff Akunting & Finance') canApprove.value = canReject.value = true;
  }
}

function openApprove() { showApprove.value = true; }
function closeApprove() { showApprove.value = false; }
function openReject() { showReject.value = true; }
function closeReject() { showReject.value = false; }

// Bridge handlers for ApprovalProgress actions
function handleApproveClick() { openApprove(); }
function handleRejectClick() { openReject(); }

function goBack() {
  router.visit("/approval/bpbs");
}

async function confirmApprove() {
  closeApprove();
  passcodeAction.value = 'approve';
  showPasscode.value = true;
}

async function confirmReject(reason: string) {
  rejectionReason.value = reason || '';
  closeReject();
  passcodeAction.value = 'reject';
  showPasscode.value = true;
}

async function onVerified() {
  try {
    if (passcodeAction.value === 'approve') {
      await post(`/api/approval/bpbs/${bpb.value.id}/approve`);
      successAction.value = 'approve';
    } else {
      await post(`/api/approval/bpbs/${bpb.value.id}/reject`, { reason: rejectionReason.value || '' });
      successAction.value = 'reject';
    }
    // refresh object
    const data = await get(`/api/approval/bpbs/${bpb.value.id}/progress`);
    bpb.value.status = data.current_status;
    approvalProgress.value = data.progress || [];
    showPasscode.value = false;
    showSuccess.value = true;
    evaluatePermissions();
  } catch (e) {
    console.error('Action failed', e);
    showPasscode.value = false;
  }
}

function formatDate(date: string | null) {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
}
function formatCurrency(value?: number) {
  return new Intl.NumberFormat('id-ID', { style: 'currency', currency: 'IDR', minimumFractionDigits: 0 }).format(Number(value ?? 0));
}
function formatInteger(value?: number | string) {
  return new Intl.NumberFormat('id-ID', { minimumFractionDigits: 0, maximumFractionDigits: 0 }).format(Number(value ?? 0));
}
function getStatusBadgeClass(status?: string) {
  return getSharedStatusBadgeClass(status || 'Draft');
}
function getStatusDotClass(status?: string) {
  return getSharedStatusDotClass(status || 'Draft');
}

onMounted(() => evaluatePermissions());

// Fetch approval progress on mount
onMounted(async () => {
  try {
    loadingProgress.value = true;
    const data = await get(`/api/approval/bpbs/${bpb.value.id}/progress`);
    const steps = data?.progress || [];
    approvalProgress.value = steps.length ? steps : buildFallbackProgress();
  } catch (e) {
    console.error('Error fetching approval progress:', e);
  } finally {
    loadingProgress.value = false;
  }
});
</script>
