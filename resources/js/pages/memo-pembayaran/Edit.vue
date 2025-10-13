<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Memo Pembayaran</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Edit dokumen Memo Pembayaran
          </div>
        </div>
      </div>

      <!-- Rejection Reason Alert -->
      <div
        v-if="memoPembayaran?.status === 'Rejected' && memoPembayaran?.rejection_reason"
        class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Alasan Penolakan</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ memoPembayaran.rejection_reason }}</p>
            </div>
          </div>
        </div>
      </div>

      <MemoPembayaranForm
        :editData="memoPembayaran"
        :purchaseOrders="purchaseOrders"
        :banks="banks"
        :creditCards="creditCards"
        :giroNumbers="giroNumbers"
        @close="goBack"
        @refreshTable="goBack"
        @edited="handleEdited"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import MemoPembayaranForm from "../../components/memo-pembayaran/MemoPembayaranForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Memo Pembayaran", href: "/memo-pembayaran" },
  { label: "Edit" },
];

const { addSuccess } = useMessagePanel();

defineOptions({ layout: AppLayout });

const props = defineProps<{
  memoPembayaran: any;
  purchaseOrders: any[];
  banks: any[];
  creditCards: any[];
}>();

const memoPembayaran = ref(props.memoPembayaran);
const purchaseOrders = ref(props.purchaseOrders || []);
const banks = ref(props.banks || []);
const creditCards = ref(props.creditCards || []);
const giroNumbers = ref<any[]>([]);

function goBack() {
  router.visit("/memo-pembayaran");
}

function handleEdited() {
  addSuccess("Memo Pembayaran berhasil diedit!");
  goBack();
}
</script>
