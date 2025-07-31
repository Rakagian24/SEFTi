<script setup lang="ts">
import { usePage, router } from '@inertiajs/vue3';
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import BankMasukForm from "@/components/bank-masuk/BankMasukForm.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import {
  ArrowLeft,
  Calendar,
  User,
  Building2,
  CreditCard,
  FileText,
  Hash,
  DollarSign,
  Banknote
} from "lucide-vue-next";
import { ref } from 'vue';

defineOptions({ layout: AppLayout });

const page = usePage();
const bankMasuk = page.props.bankMasuk as any;
const bankAccounts = page.props.bankAccounts || [];
const showConfirmDialog = ref(false);
const showEditForm = ref(false);
const { addSuccess, addError } = useMessagePanel();

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bank Masuk", href: "/bank-masuk" },
  { label: "Detail" }
];

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.get('/bank-masuk');
  }
}

// Format date helper
function formatDate(dateString: string) {
  if (!dateString) return '-';
  const date = new Date(dateString);
  return date.toLocaleDateString('id-ID', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit'
  });
}

// Format currency helper
function formatCurrency(amount: number, currency: string = 'IDR') {
  if (!amount) return '-';

  const number_string = String(amount).replace(/[^\d]/g, "");
  if (!number_string) return "-";

  const sisa = number_string.length % 3;
  let formatted = number_string.substr(0, sisa);
  const ribuan = number_string.substr(sisa).match(/\d{3}/g);
  if (ribuan) {
    formatted += (sisa ? "." : "") + ribuan.join(".");
  }

  // Tentukan symbol berdasarkan currency
  let symbol = 'Rp ';
  if (currency === 'USD') {
    symbol = '$';
  }

  return symbol + formatted;
}

// Mask account number
function maskAccountNumber(accountNumber: string) {
  if (!accountNumber) return '-';
  return '*****' + accountNumber.slice(-5);
}

// Status badge: selalu hijau, label 'Aktif' kapital di awal
function getStatusLabel() {
  return 'Aktif';
}
function getStatusColor() {
  return 'bg-green-100 text-green-800';
}

function handleDelete() {
  showConfirmDialog.value = true;
}

function confirmDelete() {
  router.delete(`/bank-masuk/${bankMasuk.id}`, {
    onSuccess: () => {
      router.get('/bank-masuk', {}, {
        onSuccess: () => {
          window.dispatchEvent(new CustomEvent('table-changed'));
          addSuccess('Bank Masuk berhasil dihapus.');
        }
      });
    },
    onError: () => {
      addError('Gagal menghapus Bank Masuk.');
    }
  });
  showConfirmDialog.value = false;
}

function cancelDelete() {
  showConfirmDialog.value = false;
}

function openEditForm() {
  showEditForm.value = true;
}

function closeEditForm() {
  showEditForm.value = false;
  // Refresh halaman detail setelah edit
  router.get(`/bank-masuk/${bankMasuk.id}`);
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Bank Masuk</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <Banknote class="w-4 h-4 mr-1" />
              {{ bankMasuk.no_bm }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <span :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusColor()}`">
            {{ getStatusLabel() }}
          </span>
          <!-- Edit Button -->
          <button
            @click="openEditForm"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Edit
          </button>
          <!-- Delete Button -->
          <button
            @click="handleDelete"
            class="flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
            </svg>
            Delete
          </button>
          <!-- Logs Button -->
          <button
            @click="router.get(`/bank-masuk/${bankMasuk.id}/log`)"
            class="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Logs
          </button>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Transaction Information Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Transaction Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <Hash class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Bank Masuk</p>
                    <p class="text-sm text-gray-600 font-mono">{{ bankMasuk.no_bm }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Calendar class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">{{ bankMasuk.tanggal }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <FileText class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tipe PO</p>
                    <p class="text-sm text-gray-600">{{ bankMasuk.tipe_po }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Building2 class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">{{ bankMasuk.bank_account?.nama_pemilik || '-' }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <User class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Terima Dari</p>
                    <p class="text-sm text-gray-600">{{ bankMasuk.terima_dari }}</p>
                  </div>
                </div>

                <div v-if="bankMasuk.input_lainnya" class="flex items-start gap-3">
                  <FileText class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Input Lainnya</p>
                    <p class="text-sm text-gray-600">{{ bankMasuk.input_lainnya }}</p>
                  </div>
                </div>

                <div v-if="bankMasuk.purchase_order_id" class="flex items-start gap-3">
                  <Hash class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Purchase Order</p>
                    <p class="text-sm text-gray-600 font-mono">{{ bankMasuk.purchase_order_id }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Financial Information Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <DollarSign class="w-5 h-5 text-gray-600" />
              <h3 class="text-lg font-semibold text-gray-900">Financial Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="flex items-start gap-3">
                <DollarSign class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Nominal</p>
                  <p class="text-lg font-semibold text-green-600">{{ formatCurrency(bankMasuk.nilai, bankMasuk.bank_account?.bank?.currency) }}</p>
                </div>
              </div>

              <div class="flex items-start gap-3">
                <CreditCard class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">No. Rekening</p>
                  <p class="text-sm text-gray-600 font-mono">{{ maskAccountNumber(bankMasuk.bank_account?.no_rekening) }}</p>
                </div>
              </div>
            </div>

            <div v-if="bankMasuk.note" class="mt-6 pt-6 border-t border-gray-200">
              <div class="flex items-start gap-3">
                <FileText class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Note</p>
                  <p class="text-sm text-gray-600 leading-relaxed">{{ bankMasuk.note }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Metadata -->
        <div class="space-y-6">
          <!-- Metadata Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <Calendar class="w-5 h-5 text-gray-600" />
              <h3 class="text-lg font-semibold text-gray-900">Metadata</h3>
            </div>

            <div class="space-y-4">
              <div>
                <p class="text-sm font-medium text-gray-900">Created At</p>
                <p class="text-sm text-gray-600">{{ formatDate(bankMasuk.created_at) }}</p>
              </div>

              <div>
                <p class="text-sm font-medium text-gray-900">Updated At</p>
                <p class="text-sm text-gray-600">{{ formatDate(bankMasuk.updated_at) }}</p>
              </div>

              <div v-if="bankMasuk.creator">
                <p class="text-sm font-medium text-gray-900">Created By</p>
                <p class="text-sm text-gray-600">{{ bankMasuk.creator.name || bankMasuk.creator }}</p>
              </div>

              <div v-if="bankMasuk.updater">
                <p class="text-sm font-medium text-gray-900">Updated By</p>
                <p class="text-sm text-gray-600">{{ bankMasuk.updater.name || bankMasuk.updater }}</p>
              </div>
            </div>
          </div>

          <!-- Quick Summary Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Summary</h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Transaction Type</span>
                <span class="text-sm font-medium text-gray-900">{{ bankMasuk.tipe_po }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Amount</span>
                <span class="text-sm font-medium text-green-600">{{ formatCurrency(bankMasuk.nilai, bankMasuk.bank_account?.bank?.currency) }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Has Notes</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ bankMasuk.note ? 'Yes' : 'No' }}
                </span>
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
          <ArrowLeft class="w-4 h-4" />
          Back to Bank Masuk
        </button>
      </div>
    </div>
  </div>

  <ConfirmDialog
    :show="showConfirmDialog"
    message="Apakah Anda yakin ingin menghapus data bank masuk ini secara permanen?"
    @confirm="confirmDelete"
    @cancel="cancelDelete"
  />

  <!-- Edit Form Modal -->
  <BankMasukForm
    v-if="showEditForm"
    :editData="bankMasuk"
    :bankAccounts="bankAccounts as any[]"
    :isDetailPage="true"
    @close="closeEditForm"
    @refreshTable="closeEditForm"
  />
</template>

<style scoped>
/* Custom styles for consistent look */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Hover effects */
.hover\:bg-white\/50:hover {
  background-color: rgba(255, 255, 255, 0.5);
}

/* Transition for smooth interactions */
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill, stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

/* Status badge colors */
.bg-yellow-100 {
  background-color: #fef3c7;
}

.text-yellow-800 {
  color: #92400e;
}

.bg-green-100 {
  background-color: #dcfce7;
}

.text-green-800 {
  color: #166534;
}

.bg-red-100 {
  background-color: #fee2e2;
}

.text-red-800 {
  color: #991b1b;
}

.bg-gray-100 {
  background-color: #f3f4f6;
}

.text-gray-800 {
  color: #1f2937;
}

.text-green-600 {
  color: #059669;
}

.text-yellow-600 {
  color: #d97706;
}

.text-red-600 {
  color: #dc2626;
}
</style>
