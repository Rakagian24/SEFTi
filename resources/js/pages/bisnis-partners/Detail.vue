<script setup lang="ts">
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import { Handshake, ArrowLeft, Edit, Calendar, User, Mail, Phone, MapPin, CreditCard, Clock, Building2 } from "lucide-vue-next";
import BisnisPartnerForm from '@/components/bisnis-partners/BisnisPartnerForm.vue';
import { ref } from 'vue';
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";

interface Bank {
  id: number;
  nama_bank: string;
  singkatan: string;
  status: string;
}

defineOptions({ layout: AppLayout });

const props = defineProps({
  bisnisPartner: {
    type: Object,
    required: true
  },
  banks: {
    type: Array as () => Bank[],
    required: true
  }
});

const showEditForm = ref(false);
const showConfirm = ref(false);
const { addSuccess, addError } = useMessagePanel();

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Bisnis Partner", href: "/bisnis-partners" },
  { label: "Detail" }
];

function goBack() {
  router.visit('/bisnis-partners');
}

function openEdit() {
  showEditForm.value = true;
}
function closeEdit() {
  showEditForm.value = false;
}

function handleDelete() {
  showConfirm.value = true;
}
function confirmDelete() {
  router.delete(`/bisnis-partners/${props.bisnisPartner.id}`, {
    onSuccess: () => {
      addSuccess('Data bisnis partner berhasil dihapus');
      router.visit('/bisnis-partners');
    },
    onError: (errors) => {
      let msg = 'Terjadi kesalahan saat menghapus data';
      if (errors && errors.message) msg = errors.message;
      addError(msg);
      router.visit('/bisnis-partners');
    }
  });
  showConfirm.value = false;
}
function cancelDelete() {
  showConfirm.value = false;
}

function viewLogs() {
  router.visit(`/bisnis-partners/${props.bisnisPartner.id}/logs`);
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

// Get jenis BP color
function getJenisBpColor(jenis: string) {
  switch (jenis) {
    case 'Customer':
      return 'bg-blue-100 text-blue-800';
    case 'Karyawan':
      return 'bg-green-100 text-green-800';
    case 'Cabang':
      return 'bg-purple-100 text-purple-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
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
            <h1 class="text-2xl font-bold text-gray-900">Detail Bisnis Partner</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <Handshake class="w-4 h-4 mr-1" />
              {{ bisnisPartner.nama_bp }}
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
      <ConfirmDialog
        :show="showConfirm"
        :message="`Apakah Anda yakin ingin menghapus data ${props.bisnisPartner.nama_bp}?`"
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
      <!-- Edit Form (inline, not modal) -->
      <BisnisPartnerForm
        v-if="showEditForm"
        :editData="bisnisPartner"
        :banks="banks"
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
              <span :class="'px-3 py-1 text-xs font-medium rounded-full ' + getJenisBpColor(bisnisPartner.jenis_bp)">
                {{ bisnisPartner.jenis_bp }}
              </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <User class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nama Bisnis Partner</p>
                    <p class="text-sm text-gray-600">{{ bisnisPartner.nama_bp }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Mail class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Email</p>
                    <p class="text-sm text-blue-600">{{ bisnisPartner.email }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Phone class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Telepon</p>
                    <p class="text-sm text-gray-600">{{ bisnisPartner.no_telepon }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <MapPin class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Alamat</p>
                    <p class="text-sm text-gray-600">{{ bisnisPartner.alamat }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <Clock class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Terms of Payment</p>
                    <p class="text-sm text-gray-600">{{ bisnisPartner.terms_of_payment }}</p>
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
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <CreditCard class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nama Bank</p>
                    <p class="text-sm text-gray-600">{{ bisnisPartner.bank?.nama_bank || '-' }}</p>
                    <p class="text-xs text-gray-500">{{ bisnisPartner.bank?.singkatan || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <User class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nama Pemilik Rekening</p>
                    <p class="text-sm text-gray-600">{{ bisnisPartner.nama_rekening }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <CreditCard class="w-5 h-5 text-gray-400 mt-0.5" />
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Rekening/VA</p>
                    <p class="text-sm text-gray-600 font-mono">{{ bisnisPartner.no_rekening_va }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <div class="w-5 h-5 mt-0.5 flex items-center justify-center">
                    <div :class="'w-3 h-3 rounded-full ' + (bisnisPartner.bank?.status === 'active' ? 'bg-green-500' : 'bg-red-500')"></div>
                  </div>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Status Bank</p>
                    <p class="text-sm text-gray-600 capitalize">{{ bisnisPartner.bank?.status || '-' }}</p>
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
                <p class="text-sm text-gray-600 font-mono">#{{ bisnisPartner.id }}</p>
              </div>

              <div>
                <p class="text-sm font-medium text-gray-900">Created At</p>
                <p class="text-sm text-gray-600">{{ formatDate(bisnisPartner.created_at) }}</p>
              </div>

              <div>
                <p class="text-sm font-medium text-gray-900">Updated At</p>
                <p class="text-sm text-gray-600">{{ formatDate(bisnisPartner.updated_at) }}</p>
              </div>

              <div v-if="bisnisPartner.created_by">
                <p class="text-sm font-medium text-gray-900">Created By</p>
                <p class="text-sm text-gray-600">{{ bisnisPartner.created_by.name || bisnisPartner.created_by }}</p>
              </div>

              <div v-if="bisnisPartner.updated_by">
                <p class="text-sm font-medium text-gray-900">Updated By</p>
                <p class="text-sm text-gray-600">{{ bisnisPartner.updated_by.name || bisnisPartner.updated_by }}</p>
              </div>
            </div>
          </div>
        </div>
        <button
            @click="goBack"
            class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
          >
            <ArrowLeft class="w-4 h-4" />
            Back
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
}
</style>
