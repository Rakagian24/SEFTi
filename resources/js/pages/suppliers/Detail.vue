<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import {
  Truck,
  ArrowLeft,
  Edit,
  Calendar,
  User,
  Mail,
  Phone,
  MapPin,
  CreditCard,
  Clock,
  Building2,
  Hash
} from "lucide-vue-next";
import SupplierForm from '@/components/suppliers/SupplierForm.vue';
import { ref } from 'vue';

interface Bank {
  id: number;
  nama_bank: string;
  singkatan?: string;
}

defineOptions({ layout: AppLayout });

const props = defineProps({
  supplier: {
    type: Object,
    required: true
  },
  banks: {
    type: Array as () => Bank[],
    required: true
  }
});

const showEditForm = ref(false);

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Supplier", href: "/suppliers" },
  { label: "Detail" }
];

function goBack() {
  router.visit('/suppliers');
}

function openEdit() {
  showEditForm.value = true;
}
function closeEdit() {
  showEditForm.value = false;
}

function handleDelete() {
  if (confirm(`Apakah Anda yakin ingin menghapus data supplier ${props.supplier.nama_supplier}?`)) {
    router.delete(`/suppliers/${props.supplier.id}`, {
      onSuccess: () => {
        alert('Data supplier berhasil dihapus');
        router.visit('/suppliers');
      },
      onError: (errors) => {
        console.error('Error deleting supplier:', errors);
        alert('Terjadi kesalahan saat menghapus data supplier');
      }
    });
  }
}

function viewLogs() {
  router.visit(`/suppliers/${props.supplier.id}/logs`);
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

// Get bank accounts helper
function getBankAccounts() {
  const accounts = [];

  for (let i = 1; i <= 3; i++) {
    const bank = props.supplier[`bank_${i}`];
    const namaRekening = props.supplier[`nama_rekening_${i}`];
    const noRekening = props.supplier[`no_rekening_${i}`];

    if (bank && namaRekening && noRekening) {
      accounts.push({
        bank,
        nama_rekening: namaRekening,
        no_rekening: noRekening,
        isPrimary: i === 1
      });
    }
  }

  return accounts;
}

const bankAccounts = getBankAccounts();
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
            <h1 class="text-2xl font-bold text-gray-900">Detail Supplier</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <Truck class="w-4 h-4 mr-1" />
              {{ supplier.nama_supplier }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Edit Button -->
          <button
            @click="openEdit"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <Edit class="w-4 h-4" />
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

          <!-- View Logs Button -->
          <button
            @click="viewLogs"
            class="flex items-center gap-2 px-4 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            View Logs
          </button>
        </div>
      </div>

      <!-- Edit Form (inline, not modal) -->
      <SupplierForm
        v-if="showEditForm"
        :editData="supplier"
        :banks="banks"
        :asModal="false"
        @close="closeEdit"
      />

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6" v-if="!showEditForm">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
              <h3 class="text-lg font-semibold text-gray-900">Basic Information</h3>
              <div class="flex items-center gap-2">
              </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <User class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nama Supplier</p>
                    <p class="text-sm text-gray-600">{{ supplier.nama_supplier }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Mail class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Email</p>
                    <p class="text-sm text-blue-600">{{ supplier.email || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Phone class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Telepon</p>
                    <p class="text-sm text-gray-600">{{ supplier.no_telepon || '-' }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <MapPin class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Alamat</p>
                    <p class="text-sm text-gray-600">{{ supplier.alamat }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Clock class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Terms of Payment</p>
                    <p class="text-sm text-gray-600">{{ supplier.terms_of_payment || '-' }}</p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Bank Information Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <Building2 class="w-5 h-5 text-gray-600" />
              <h3 class="text-lg font-semibold text-gray-900">Bank Information</h3>
              <span class="text-sm text-gray-500">({{ bankAccounts.length }} account{{ bankAccounts.length > 1 ? 's' : '' }})</span>
            </div>

            <div v-if="bankAccounts.length === 0" class="text-center py-8">
              <CreditCard class="w-12 h-12 text-gray-300 mx-auto mb-3" />
              <p class="text-sm text-gray-500">No bank accounts configured</p>
            </div>

            <div v-else class="space-y-6">
              <div
                v-for="(account, index) in bankAccounts"
                :key="index"
                class="border border-gray-200 rounded-lg p-4 relative"
              >

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                  <div class="flex items-start gap-3">
                    <CreditCard class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">Bank</p>
                      <p class="text-sm text-gray-600">{{ account.bank }}</p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <User class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">Nama Rekening</p>
                      <p class="text-sm text-gray-600">{{ account.nama_rekening }}</p>
                    </div>
                  </div>

                  <div class="flex items-start gap-3">
                    <Hash class="w-5 h-5 text-gray-400 mt-0.5" />
                    <div>
                      <p class="text-sm font-medium text-gray-900">No. Rekening</p>
                      <p class="text-sm text-gray-600 font-mono">{{ account.no_rekening }}</p>
                    </div>
                  </div>
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
                <p class="text-sm font-medium text-gray-900">ID</p>
                <p class="text-sm text-gray-600 font-mono">#{{ supplier.id }}</p>
              </div>

              <div>
                <p class="text-sm font-medium text-gray-900">Created At</p>
                <p class="text-sm text-gray-600">{{ formatDate(supplier.created_at) }}</p>
              </div>

              <div>
                <p class="text-sm font-medium text-gray-900">Updated At</p>
                <p class="text-sm text-gray-600">{{ formatDate(supplier.updated_at) }}</p>
              </div>

              <div v-if="supplier.created_by">
                <p class="text-sm font-medium text-gray-900">Created By</p>
                <p class="text-sm text-gray-600">{{ supplier.created_by.name || supplier.created_by }}</p>
              </div>

              <div v-if="supplier.updated_by">
                <p class="text-sm font-medium text-gray-900">Updated By</p>
                <p class="text-sm text-gray-600">{{ supplier.updated_by.name || supplier.updated_by }}</p>
              </div>
            </div>
          </div>

          <!-- Quick Stats Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Stats</h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Bank Accounts</span>
                <span class="text-sm font-medium text-gray-900">{{ bankAccounts.length }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Contact Info</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ (supplier.email ? 1 : 0) + (supplier.no_telepon ? 1 : 0) }}/2
                </span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Payment Terms</span>
                <span class="text-sm font-medium text-gray-900">
                  {{ supplier.terms_of_payment ? 'Set' : 'Not Set' }}
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
          Back to Suppliers
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Custom styles for consistent look */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Hover effects */
.hover\:bg-blue-50:hover {
  background-color: #eff6ff;
}

.hover\:bg-gray-50:hover {
  background-color: #f9fafb;
}

.hover\:bg-red-50:hover {
  background-color: #fef2f2;
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

  .grid-cols-1.md\:grid-cols-3 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

/* Bank account card styling */
.border-gray-200 {
  border-color: #e5e7eb;
}

/* Primary account badge */
.bg-green-100 {
  background-color: #dcfce7;
}

.text-green-800 {
  color: #166534;
}

/* Empty state styling */
.text-gray-300 {
  color: #d1d5db;
}

.text-gray-500 {
  color: #6b7280;
}
</style>
