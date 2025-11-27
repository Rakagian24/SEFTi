<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrencyWithSymbol } from '@/lib/currencyUtils';
import { computed } from 'vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';

interface BankKeluarDocument {
  id: number | string;
  original_filename: string;
}

interface BankKeluarRelationOption {
  id?: number | string;
  name?: string;
  nama?: string;
  nama_bp?: string;
  jenis_bp?: string;
}

interface BankRelationOption {
  nama?: string;
  nama_bank?: string;
}

interface BankSupplierAccountRelation {
  nama_pemilik_rekening?: string;
  nama_rekening?: string;
  no_rekening?: string;
  bank?: BankRelationOption | null;
}

interface CreditCardRelation {
  nama_pemilik?: string;
  owner_name?: string;
  no_kartu_kredit?: string;
  bank?: BankRelationOption | null;
}

interface SupplierRelation {
  nama?: string;
  nama_supplier?: string;
  contact?: string;
  no_telepon?: string;
}

interface PaymentVoucherRelation {
  no_pv?: string;
  perihal?: BankKeluarRelationOption | null;
  supplier?: SupplierRelation | null;
  po_anggaran?: {
    bisnis_partner?: {
      nama_bp?: string;
      bank?: BankRelationOption | null;
    } | null;
  } | null;
}

interface BankKeluar {
  id: number | string;
  no_bk: string;
  tanggal: string | Date;
  department?: BankKeluarRelationOption | null;
  perihal?: BankKeluarRelationOption | null;
  tipe_bk?: string | null;
  nominal: number;
  metode_bayar: string;
  payment_voucher?: PaymentVoucherRelation | null;
  supplier?: SupplierRelation | null;
  bisnis_partner?: {
    nama_bp?: string;
    nama?: string;
    jenis_bp?: string;
    bank?: BankRelationOption | null;
  } | null;
  bank?: BankRelationOption | null;
  bank_supplier_account?: BankSupplierAccountRelation | null;
  credit_card?: CreditCardRelation | null;
  nama_pemilik_rekening?: string | null;
  no_rekening?: string | null;
  status: string;
  note?: string | null;
  creator?: { name?: string } | null;
  updater?: { name?: string } | null;
  created_at?: string | Date;
  updated_at?: string | Date;
  documents?: BankKeluarDocument[];
}

const { bankKeluar } = defineProps<{
  bankKeluar: BankKeluar;
}>();

const breadcrumbs = computed(() => [
  { label: 'Dashboard', href: '/dashboard' },
  { label: 'Bank Keluar', href: '/bank-keluar' },
  { label: 'Detail', href: '#' },
]);

const displayPerihal = computed(() => {
  return (
    bankKeluar.perihal?.name ||
    bankKeluar.perihal?.nama ||
    bankKeluar.payment_voucher?.perihal?.name ||
    bankKeluar.payment_voucher?.perihal?.nama ||
    '-'
  );
});

const supplierName = computed(() => {
  return (
    bankKeluar.supplier?.nama_supplier ??
    bankKeluar.supplier?.nama ??
    bankKeluar.payment_voucher?.supplier?.nama_supplier ??
    bankKeluar.payment_voucher?.supplier?.nama ??
    '-'
  );
});

const bisnisPartnerName = computed(() => {
  return (
    bankKeluar.bisnis_partner?.nama_bp ||
    bankKeluar.bisnis_partner?.nama ||
    bankKeluar.payment_voucher?.po_anggaran?.bisnis_partner?.nama_bp ||
    '-'
  );
});

const showBisnisPartner = computed(() => bisnisPartnerName.value !== '-');

const bankName = computed(() => {
  return (
    bankKeluar.bank?.nama ||
    bankKeluar.bank?.nama_bank ||
    bankKeluar.bisnis_partner?.bank?.nama ||
    bankKeluar.bisnis_partner?.bank?.nama_bank ||
    bankKeluar.bank_supplier_account?.bank?.nama ||
    bankKeluar.bank_supplier_account?.bank?.nama_bank ||
    bankKeluar.credit_card?.bank?.nama ||
    bankKeluar.credit_card?.bank?.nama_bank ||
    '-'
  );
});

const paymentOwnerName = computed(() => {
  if (bankKeluar.metode_bayar === 'Kartu Kredit') {
    return (
      bankKeluar.credit_card?.nama_pemilik ||
      bankKeluar.credit_card?.owner_name ||
      '-'
    );
  }

  return (
    bankKeluar.nama_pemilik_rekening ||
    bankKeluar.bank_supplier_account?.nama_pemilik_rekening ||
    bankKeluar.bank_supplier_account?.nama_rekening ||
    '-'
  );
});

const accountNumber = computed(() => {
  if (bankKeluar.metode_bayar === 'Kartu Kredit') {
    return bankKeluar.credit_card?.no_kartu_kredit || '-';
  }

  return (
    bankKeluar.no_rekening ||
    bankKeluar.bank_supplier_account?.no_rekening ||
    '-'
  );
});

const ownerLabel = computed(() =>
  bankKeluar.metode_bayar === 'Kartu Kredit' ? 'Nama Pemilik Kartu' : 'Nama Pemilik Rekening',
);

const accountLabel = computed(() =>
  bankKeluar.metode_bayar === 'Kartu Kredit' ? 'No. Kartu Kredit' : 'No. Rekening',
);

function formatDate(date: string | Date | null | undefined): string {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}

function getStatusBadgeClass(status: string) {
  if (status === 'aktif') return 'bg-green-50 text-green-700 border border-green-200';
  if (status === 'batal') return 'bg-red-50 text-red-700 border border-red-200';
  return 'bg-gray-50 text-gray-700 border border-gray-200';
}

function getStatusDotClass(status: string) {
  if (status === 'aktif') return 'bg-green-500';
  if (status === 'batal') return 'bg-red-500';
  return 'bg-gray-500';
}

function goBack() {
  router.visit('/bank-keluar');
}

function goToEdit() {
  router.visit(`/bank-keluar/${bankKeluar.id}/edit`);
}
</script>

<template>
  <AppLayout>
    <Head title="Bank Keluar Detail" />

    <div class="bg-[#DFECF2] min-h-screen">
      <div class="pl-2 pt-6 pr-6 pb-6">
        <Breadcrumbs :items="breadcrumbs" />

        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <div class="flex items-center gap-4">
            <div>
              <h1 class="text-2xl font-bold text-gray-900">Detail Bank Keluar</h1>
              <div class="flex items-center mt-2 text-sm text-gray-500">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                </svg>
                {{ bankKeluar.no_bk }}
              </div>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <span
              :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(
                bankKeluar.status
              )}`"
            >
              <div
                class="w-2 h-2 rounded-full mr-2 inline-block"
                :class="getStatusDotClass(bankKeluar.status)"
              ></div>
              {{ bankKeluar.status === 'aktif' ? 'Aktif' : 'Batal' }}
            </span>

            <button
              @click="goToEdit"
              class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              Edit
            </button>
          </div>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
          <div class="lg:col-span-2 space-y-6">
            <!-- Bank Keluar Information Card -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Informasi Bank Keluar</h3>
              </div>
              <div class="px-6 py-4">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">No. BK</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ bankKeluar.no_bk }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ formatDate(bankKeluar.tanggal) }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Department</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ bankKeluar.department?.name || '-' }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Perihal</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ displayPerihal }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Nominal</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-semibold">{{ formatCurrencyWithSymbol(bankKeluar.nominal, 'IDR') }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Metode Bayar</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ bankKeluar.metode_bayar }}</dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Payment Details Card -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Detail Pembayaran</h3>
              </div>
              <div class="px-6 py-4">
                <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Payment Voucher</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ bankKeluar.payment_voucher?.no_pv || '-' }}</dd>
                  </div>
                  <div v-if="bankKeluar.tipe_bk !== 'Anggaran'">
                    <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ supplierName }}</dd>
                  </div>
                  <div v-if="showBisnisPartner">
                    <dt class="text-sm font-medium text-gray-500">Bisnis Partner</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ bisnisPartnerName }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Nama Bank</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ bankName }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">{{ ownerLabel }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ paymentOwnerName }}</dd>
                  </div>
                  <div>
                    <dt class="text-sm font-medium text-gray-500">{{ accountLabel }}</dt>
                    <dd class="mt-1 text-sm text-gray-900 font-medium">{{ accountNumber }}</dd>
                  </div>
                </dl>
              </div>
            </div>

            <!-- Documents Card -->
            <div v-if="bankKeluar.documents && bankKeluar.documents.length > 0" class="bg-white shadow-sm rounded-lg overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Dokumen</h3>
              </div>
              <div class="px-6 py-4">
                <ul class="divide-y divide-gray-200">
                  <li v-for="doc in bankKeluar.documents" :key="doc.id" class="py-3 flex justify-between items-center">
                    <div class="flex items-center">
                      <svg class="h-5 w-5 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                      </svg>
                      <span class="text-sm text-gray-900">{{ doc.original_filename }}</span>
                    </div>
                    <a
                      :href="route('bank-keluar.documents.download', doc.id)"
                      class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                      target="_blank"
                    >
                      Download
                    </a>
                  </li>
                </ul>
              </div>
            </div>

            <!-- Additional Information Card -->
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
              </div>
              <div class="px-6 py-4">
                <dl class="space-y-4">
                  <div>
                    <dt class="text-sm font-medium text-gray-500">Catatan</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ bankKeluar.note || '-' }}</dd>
                  </div>
                </dl>
              </div>
            </div>
          </div>

          <!-- Sidebar - Summary Card -->
          <div class="space-y-6">
            <div class="bg-white shadow-sm rounded-lg overflow-hidden">
              <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Ringkasan</h3>
              </div>
              <div class="px-6 py-4 space-y-4">
                <div class="flex justify-between items-center pb-4 border-b border-gray-200">
                  <span class="text-sm font-medium text-gray-500">Total Nominal</span>
                  <span class="text-lg font-bold text-gray-900">{{ formatCurrencyWithSymbol(bankKeluar.nominal, 'IDR') }}</span>
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
            Kembali ke Daftar Bank Keluar
          </button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
