<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Payment Voucher</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Buat dokumen Payment Voucher baru
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <!-- Tabs -->
        <div class="border-b border-gray-200 mb-4">
          <nav class="flex -mb-px" aria-label="Tabs">
            <button
              type="button"
              class="mr-6 whitespace-nowrap py-2 px-1 border-b-2 text-sm font-medium"
              :class="
                activeTab === 'form'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              "
              @click="activeTab = 'form'"
            >
              Form
            </button>
            <button
              type="button"
              class="whitespace-nowrap py-2 px-1 border-b-2 text-sm font-medium"
              :class="
                activeTab === 'docs'
                  ? 'border-blue-600 text-blue-600'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
              "
              @click="activeTab = 'docs'"
            >
              Dokumen Pendukung
            </button>
          </nav>
        </div>

        <!-- Tab Panels -->
        <div v-show="activeTab === 'form'">
          <PaymentVoucherForm
            v-model="formData"
            :supplierOptions="props.supplierOptions"
            :departmentOptions="props.departmentOptions"
            :perihalOptions="props.perihalOptions"
            :creditCardOptions="props.creditCardOptions"
            :giroOptions="props.giroOptions"
            :purchaseOrderOptions="purchaseOrderOptions"
            :availablePOs="availablePOs"
            @search-purchase-orders="handleSearchPOs"
            @add-purchase-order="handleAddPO"
          />
        </div>

        <div v-show="activeTab === 'docs'">
          <PaymentVoucherSupportingDocs />
        </div>

        <!-- Action Buttons - shown on all tabs -->
        <div class="flex justify-start gap-3 pt-6 border-t border-gray-200 mt-6">
          <button
            type="submit"
            :disabled="isSubmitting"
            class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          >
            <svg
              fill="#E6E6E6"
              height="24"
              viewBox="0 0 24 24"
              width="24"
              xmlns="http://www.w3.org/2000/svg"
              class="w-5 h-5"
            >
              <path
                d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
              />
            </svg>
            <span v-if="isSubmitting">Mengirim...</span>
            <span v-else>Kirim</span>
          </button>

          <button
            type="button"
            @click="saveDraft"
            :disabled="isSubmitting"
            class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-5 h-5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"
              />
            </svg>
            <span v-if="isSubmitting">Menyimpan...</span>
            <span v-else>Simpan Draft</span>
          </button>

          <button
            type="button"
            @click="handleCancel"
            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          >
            <svg
              xmlns="http://www.w3.org/2000/svg"
              fill="none"
              viewBox="0 0 24 24"
              stroke-width="1.5"
              stroke="currentColor"
              class="w-5 h-5"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
            Batal
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import axios from "axios";
import PaymentVoucherForm from "../../components/payment-voucher/PaymentVoucherForm.vue";
import PaymentVoucherSupportingDocs from "../../components/payment-voucher/PaymentVoucherSupportingDocs.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards } from "lucide-vue-next";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher", href: "/payment-voucher" },
  { label: "Buat Baru" },
];

defineOptions({ layout: AppLayout });

const props = defineProps<{
  purchaseOrders: any[];
  banks: any[];
  supplierOptions?: any[];
  departmentOptions?: any[];
  perihalOptions?: any[];
  creditCardOptions?: any[];
  giroOptions?: any[];
  pphOptions?: any[];
}>();

const formData = ref({});
const availablePOs = ref<any[]>([]);
const purchaseOrderOptions = ref<any[]>([]);
const activeTab = ref<"form" | "docs">("form");
const isSubmitting = ref(false);

// PO Selection handlers
async function handleSearchPOs(search: string) {
  await fetchPOs(search);
}

async function handleAddPO(po: any) {
  // Set the selected PO in formData
  formData.value = {
    ...formData.value,
    purchase_order_id: po.id,
    nominal: po.total || 0,
  };

  // Update purchase order options to include the selected PO
  const poOption = {
    value: po.id,
    label: `${po.no_po} - ${po.department?.name || "-"} - ${po.perihal?.nama || "-"}`,
  };

  // Check if PO is already in options
  const exists = purchaseOrderOptions.value.some(option => option.value === po.id);
  if (!exists) {
    purchaseOrderOptions.value = [poOption, ...purchaseOrderOptions.value];
  }
}

// Action button handlers
function saveDraft() {
  isSubmitting.value = true;
  // TODO: Implement save draft functionality
  console.log("Saving draft...", formData.value);
  setTimeout(() => {
    isSubmitting.value = false;
  }, 1000);
}

function handleCancel() {
  // TODO: Implement cancel functionality
  console.log("Cancelling...");
  // Could navigate back or show confirmation dialog
}

async function fetchPOs(search: string = "") {
  try {
    const params: any = { per_page: 20 };
    const m = (formData.value as any)?.metode_bayar;
    if (m) params.metode_bayar = m;
    if (m === "Transfer" && (formData.value as any)?.supplier_id) {
      params.supplier_id = (formData.value as any).supplier_id;
    } else if (m === "Cek/Giro" && (formData.value as any)?.giro_id) {
      params.giro_id = (formData.value as any).giro_id;
    } else if (m === "Kartu Kredit" && (formData.value as any)?.credit_card_id) {
      params.credit_card_id = (formData.value as any).credit_card_id;
    }
    if (search) params.search = search;

    const { data } = await axios.get("/payment-voucher/purchase-orders/search", { params, withCredentials: true });
    if (data && data.success) {
      availablePOs.value = data.data || [];
      // Update purchase order options for dropdown
      purchaseOrderOptions.value = (data.data || []).map((po: any) => ({
        value: po.id,
        label: `${po.no_po} - ${po.department?.name || "-"} - ${po.perihal?.nama || "-"}`,
      }));
    } else {
      availablePOs.value = [];
      purchaseOrderOptions.value = [];
    }
  } catch (e) {
    availablePOs.value = [];
    purchaseOrderOptions.value = [];
    console.error("Failed to fetch POs for PV:", e);
  }
}

// Auto-fetch when metode/supplier/giro/kartu kredit changes
watch(
  () => [
    (formData.value as any)?.metode_bayar,
    (formData.value as any)?.supplier_id,
    (formData.value as any)?.giro_id,
    (formData.value as any)?.credit_card_id,
  ],
  () => fetchPOs(""),
  { deep: false }
);
</script>
