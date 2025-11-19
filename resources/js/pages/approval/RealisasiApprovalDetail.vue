<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <button
            type="button"
            class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            @click="router.visit('/approval/realisasi')"
          >
            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
            Kembali
          </button>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Realisasi (Approval)</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4" />
              </svg>
              {{ realisasi?.no_realisasi || '-' }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span class="px-3 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
            <span class="inline-block w-2 h-2 mr-2 rounded-full" :class="statusDotClass"></span>
            {{ realisasi?.status || '-' }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Realisasi</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">No. Realisasi</p>
                  <p class="text-sm font-mono text-gray-900">{{ realisasi?.no_realisasi || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Tanggal</p>
                  <p class="text-sm text-gray-900">{{ formatDate(realisasi?.tanggal) }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Departemen</p>
                  <p class="text-sm text-gray-900">{{ realisasi?.department?.name || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Metode Pembayaran</p>
                  <p class="text-sm text-gray-900">{{ realisasi?.metode_pembayaran || '-' }}</p>
                </div>
              </div>

              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">Nama Bank</p>
                  <p class="text-sm text-gray-900">{{ realisasi?.bank?.nama_bank || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Nama Rekening</p>
                  <p class="text-sm text-gray-900">{{ realisasi?.nama_rekening || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">No. Rekening/VA</p>
                  <p class="text-sm font-mono text-gray-900">{{ realisasi?.no_rekening || '-' }}</p>
                </div>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div>
                <p class="text-xs font-medium text-gray-500 mb-1">Total Anggaran</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(realisasi?.total_anggaran || 0) }}</p>
              </div>
              <div>
                <p class="text-xs font-medium text-gray-500 mb-1">Total Realisasi</p>
                <p class="text-lg font-semibold text-gray-900">{{ formatCurrency(realisasi?.total_realisasi || 0) }}</p>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-xs font-medium text-gray-500 mb-1">Catatan</p>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-900 leading-relaxed">{{ realisasi?.note || '-' }}</p>
              </div>
            </div>
          </div>

          <div v-if="realisasi?.poAnggaran" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Anggaran</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">No. PO Anggaran</p>
                  <p class="text-sm font-mono text-gray-900">{{ realisasi?.poAnggaran?.no_po_anggaran || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Departemen</p>
                  <p class="text-sm text-gray-900">{{ realisasi?.poAnggaran?.department?.name || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Tanggal</p>
                  <p class="text-sm text-gray-900">{{ formatDate(realisasi?.poAnggaran?.tanggal) }}</p>
                </div>
              </div>
              <div class="space-y-4">
                <div>
                  <p class="text-xs font-medium text-gray-500">Metode Pembayaran</p>
                  <p class="text-sm text-gray-900">{{ realisasi?.poAnggaran?.metode_pembayaran || '-' }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Nominal</p>
                  <p class="text-sm font-semibold text-gray-900">{{ formatCurrency(realisasi?.poAnggaran?.nominal || 0) }}</p>
                </div>
                <div>
                  <p class="text-xs font-medium text-gray-500">Status</p>
                  <p class="text-sm text-gray-900">{{ realisasi?.poAnggaran?.status || '-' }}</p>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-xs font-medium text-gray-500 mb-1">Catatan PO Anggaran</p>
              <div class="bg-gray-50 rounded-lg p-4">
                <p class="text-sm text-gray-900 leading-relaxed">{{ realisasi?.poAnggaran?.note || '-' }}</p>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Detail Realisasi</h3>
              <span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">
                {{ realisasi?.items?.length || 0 }} item
              </span>
            </div>

            <div class="overflow-hidden">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Keterangan</th>
                      <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
                      <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Satuan</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Harga</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtotal</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Realisasi</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="(item, index) in realisasi?.items || []"
                      :key="index"
                      class="hover:bg-gray-50 transition-colors"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">{{ index + 1 }}</span>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-900">{{ item.jenis_pengeluaran_text || '-' }}</td>
                      <td class="px-6 py-4 text-sm text-gray-700">{{ item.keterangan || '-' }}</td>
                      <td class="px-6 py-4 text-center text-sm text-gray-900">{{ formatQty(item.qty) }}</td>
                      <td class="px-6 py-4 text-center text-sm text-gray-600">{{ item.satuan || '-' }}</td>
                      <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatCurrency(item.harga || 0) }}</td>
                      <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatCurrency(item.subtotal ?? ((item.qty || 1) * (item.harga || 0))) }}</td>
                      <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ formatCurrency(item.realisasi || 0) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <ApprovalProgress
            :progress="progress || []"
            :purchase-order="realisasi"
            :user-role="userRole || ''"
            :can-verify="!!canVerify"
            :can-validate="false"
            :can-approve="!!canApprove"
            :can-reject="false"
            @verify="handleVerify"
            @approve="handleApprove"
          />

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan</h3>
            </div>

            <div class="space-y-3">
              <div class="flex items-center justify-between text-sm">
                <span class="text-gray-600">Total Detail Items</span>
                <span class="font-medium text-gray-900">{{ formatCurrency(itemsTotal) }}</span>
              </div>
              <div class="border-t pt-3 flex items-center justify-between text-sm">
                <span class="text-gray-900 font-semibold">Total Realisasi</span>
                <span class="text-gray-900 font-semibold">{{ formatCurrency(realisasi?.total_realisasi || 0) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <ApprovalConfirmationDialog
      :is-open="showApprovalDialog"
      @update:open="(v: boolean) => (showApprovalDialog = v)"
      @cancel="() => { showApprovalDialog = false; pendingAction = null; }"
      @confirm="handleApprovalConfirm"
    />

    <PasscodeVerificationDialog
      :is-open="showPasscodeDialog"
      :action="passcodeAction"
      :action-data="pendingAction"
      @update:open="(v: boolean) => (showPasscodeDialog = v)"
      @cancel="() => { showPasscodeDialog = false; pendingAction = null; }"
      @verified="handlePasscodeVerified"
    />

    <SuccessDialog
      :is-open="showSuccessDialog"
      :action="successAction"
      :user-name="user?.name || 'User'"
      document-type="Realisasi"
      @update:open="(v: boolean) => { showSuccessDialog = v; if (!v) { router.visit('/approval/realisasi'); } }"
      @close="() => { showSuccessDialog = false; router.visit('/approval/realisasi'); }"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { router, usePage } from '@inertiajs/vue3';
import { formatCurrency } from '@/lib/currencyUtils';
import ApprovalProgress from '@/components/approval/ApprovalProgress.vue';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import { useApi } from '@/composables/useApi';

defineOptions({ layout: AppLayout });

const props = defineProps<{
  realisasi: any;
  progress?: any[];
  userRole?: string;
  canVerify?: boolean;
  canApprove?: boolean;
}>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Approval', href: '/approval' },
  { label: 'Realisasi', href: '/approval/realisasi' },
  { label: 'Detail' },
];

const realisasi = props.realisasi;
const progress = props.progress || [];
const userRole = props.userRole || '';
const canVerify = props.canVerify ?? false;
const canApprove = props.canApprove ?? false;

function formatDate(value?: string) {
  if (!value) return '-';
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatQty(val: any) {
  const n = Number(val);
  if (!isFinite(n) || isNaN(n)) return '1';
  const isInt = Math.floor(n) === n;
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: isInt ? 0 : 0,
    maximumFractionDigits: isInt ? 0 : 2,
  }).format(n);
}

const statusDotClass = computed(() => {
  const status = (realisasi?.status || '').toLowerCase();
  if (status === 'approved') return 'bg-green-500';
  if (status === 'verified') return 'bg-blue-500';
  if (status === 'in progress') return 'bg-yellow-400';
  if (status === 'rejected' || status === 'canceled') return 'bg-red-500';
  return 'bg-gray-400';
});

const itemsTotal = computed(() => {
  const items = (realisasi?.items || []) as any[];
  return items.reduce((acc, it) => acc + Number(it.subtotal ?? (Number(it.qty || 1) * Number(it.harga || 0))), 0);
});

const showApprovalDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<'verify' | 'approve'>('approve');
const successAction = ref<'verify' | 'approve'>('approve');
const pendingAction = ref<{
  type: 'single';
  action: 'verify' | 'approve';
  ids: number[];
  singleItem?: any;
} | null>(null);

const { post } = useApi();

function handleVerify() {
  if (!realisasi?.id) return;
  pendingAction.value = {
    type: 'single',
    action: 'verify',
    ids: [realisasi.id],
    singleItem: realisasi,
  };
  showApprovalDialog.value = true;
}

function handleApprove() {
  if (!realisasi?.id) return;
  pendingAction.value = {
    type: 'single',
    action: 'approve',
    ids: [realisasi.id],
    singleItem: realisasi,
  };
  showApprovalDialog.value = true;
}

const handleApprovalConfirm = () => {
  if (!pendingAction.value) return;
  showApprovalDialog.value = false;
  passcodeAction.value = pendingAction.value.action;
  showPasscodeDialog.value = true;
};

async function handlePasscodeVerified() {
  try {
    if (!pendingAction.value) return;
    const id = pendingAction.value.ids[0];
    if (pendingAction.value.action === 'verify') {
      await post(`/api/approval/realisasis/${id}/verify`);
      (realisasi as any).status = 'Verified';
    } else if (pendingAction.value.action === 'approve') {
      await post(`/api/approval/realisasis/${id}/approve`);
      (realisasi as any).status = 'Approved';
    }
    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;
  } catch {
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
}

const page = usePage();
const user = page.props.auth?.user;
</script>
