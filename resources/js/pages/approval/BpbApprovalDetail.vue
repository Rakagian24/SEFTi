<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail BPB (Approval)</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              {{ bpb?.no_bpb || `BPB #${bpb?.id}` }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(bpb?.status)}`">
            <span class="w-2 h-2 rounded-full mr-2 inline-block" :class="getStatusDotClass(bpb?.status)"></span>
            {{ bpb?.status }}
          </span>
          <button v-if="canApprove" @click="openApprove" class="px-4 py-2 rounded-lg text-white bg-blue-600 hover:bg-blue-700">Setujui</button>
          <button v-if="canReject" @click="openReject" class="px-4 py-2 rounded-lg border border-red-600 text-red-600 bg-white hover:bg-red-50">Tolak</button>
          <Link :href="`/approval/bpbs/${bpb?.id}/log`" class="px-4 py-2 rounded-lg border bg-white hover:bg-gray-50 text-gray-700">Lihat Log</Link>
        </div>
      </div>

      <div v-if="bpb?.rejection_reason" class="bg-white rounded-lg shadow-sm border border-red-200 p-6 mb-6">
        <div class="text-sm font-semibold text-red-700 mb-1">Alasan Penolakan</div>
        <div class="text-sm text-red-700">{{ bpb.rejection_reason }}</div>
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
                    <td class="px-4 py-3 text-right text-gray-900">{{ it.qty }}</td>
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
              <div class="flex items-center justify-between py-2">
                <span class="text-sm text-gray-600">PPH</span>
                <span class="text-sm font-medium text-gray-900">{{ formatCurrency(Number(bpb?.pph || 0)) }}</span>
              </div>
              <div class="border-t border-gray-200 pt-4 mt-4">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-semibold text-gray-900">Grand Total</span>
                  <span class="text-lg font-bold text-green-600">{{ formatCurrency(Number(bpb?.grand_total || 0)) }}</span>
                </div>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Status</h3>
            </div>
            <div class="flex items-center justify-center">
              <span :class="`px-4 py-2 text-sm font-medium rounded-full ${getStatusBadgeClass(bpb?.status)}`">
                <span class="w-2 h-2 rounded-full mr-2 inline-block" :class="getStatusDotClass(bpb?.status)"></span>
                {{ bpb?.status }}
              </span>
            </div>
          </div>
        </div>
      </div>

      <ApprovalConfirmationDialog :is-open="showApprove" @update:open="showApprove = $event" @cancel="closeApprove" @confirm="confirmApprove" />
      <RejectionConfirmationDialog :is-open="showReject" :require-reason="true" @update:open="showReject = $event" @cancel="closeReject" @confirm="confirmReject" />
      <PasscodeVerificationDialog :is-open="showPasscode" :action="passcodeAction" @update:open="showPasscode = $event" @cancel="() => showPasscode = false" @verified="onVerified" />
      <SuccessDialog :is-open="showSuccess" :action="successAction" :user-name="userName" document-type="BPB" @update:open="showSuccess = $event" />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import RejectionConfirmationDialog from '@/components/approval/RejectionConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import { useApi } from '@/composables/useApi';

defineOptions({ layout: AppLayout });

const props = defineProps<{ bpb: any }>();
const { post, get } = useApi();

const bpb = ref<any>(props.bpb || null);
const user = usePage().props.auth?.user as any;
const userName = ref(user?.name || 'User');

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

function getApprovalButtonClass(action: string) { return getApprovalButtonClass(action); }

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
function getStatusBadgeClass(status?: string) {
  const classes: Record<string, string> = {
    Draft: 'bg-gray-100 text-gray-800',
    'In Progress': 'bg-yellow-100 text-yellow-800',
    Approved: 'bg-green-100 text-green-800',
    Canceled: 'bg-red-100 text-red-800',
    Rejected: 'bg-red-100 text-red-800',
  };
  return status ? (classes[status] || 'bg-gray-100 text-gray-800') : 'bg-gray-100 text-gray-800';
}
function getStatusDotClass(status?: string) {
  const classes: Record<string, string> = {
    Draft: 'bg-gray-400',
    'In Progress': 'bg-yellow-500',
    Approved: 'bg-green-600',
    Canceled: 'bg-red-600',
    Rejected: 'bg-red-600',
  };
  return status ? (classes[status] || 'bg-gray-400') : 'bg-gray-400';
}

onMounted(() => evaluatePermissions());
</script>
