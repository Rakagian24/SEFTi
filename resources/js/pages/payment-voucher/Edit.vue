<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Payment Voucher</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <WalletCards class="w-4 h-4 mr-1" />
            Edit dokumen Payment Voucher
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
            :totalFromBarangGrid="totalFromBarangGrid"
          />

          <hr class="my-6" />

          <!-- Barang Grid (Purchase Orders) -->
          <PaymentVoucherBarangGrid
            :items="selectedPoItems"
            :availablePOs="availablePOs"
            :formData="formData"
            :pphOptions="localPphOptions"
            @add-po="handleAddPO"
            @add-selected-pos="handleAddSelectedPOs"
            @clear="() => (selectedPoItems = [])"
            @update-total="handleUpdateTotal"
            @add-pph="handleAddPph"
          />
        </div>

        <div v-show="activeTab === 'docs'">
          <PaymentVoucherSupportingDocs />
        </div>

        <!-- Action Buttons - shown on all tabs -->
        <div class="flex justify-start gap-3 pt-6 border-t border-gray-200 mt-6">
          <button
            type="button"
            @click="submitUpdate"
            :disabled="isSubmitting"
            class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
          >
            <span v-if="isSubmitting">Menyimpan...</span>
            <span v-else>Simpan Perubahan</span>
          </button>

          <button
            type="button"
            @click="handleCancel"
            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          >
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
import PaymentVoucherBarangGrid from "../../components/payment-voucher/PaymentVoucherBarangGrid.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards } from "lucide-vue-next";

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Payment Voucher", href: "/payment-voucher" },
  { label: "Edit" },
];

defineOptions({ layout: AppLayout });

const props = defineProps<{
  id: number | string;
  paymentVoucher: any;
  departmentOptions?: any[];
  supplierOptions?: any[];
  perihalOptions?: any[];
  creditCardOptions?: any[];
  giroOptions?: any[];
  pphOptions?: any[];
}>();

const formData = ref<any>({ ...(props.paymentVoucher || {}) });
const availablePOs = ref<any[]>([]);
const activeTab = ref<"form" | "docs">("form");
const selectedPoItems = ref<any[]>([]);
const isSubmitting = ref(false);
const totalFromBarangGrid = ref<number | undefined>(undefined);
const localPphOptions = ref<any[]>(props.pphOptions || []);

// Initialize selectedPoItems from PV purchaseOrders
selectedPoItems.value = (props.paymentVoucher?.purchase_orders || props.paymentVoucher?.purchaseOrders || []).map((po: any) => ({
  id: po.id,
  po_id: po.id,
  no_po: po.no_po,
  department_name: po.department?.name || "-",
  perihal_name: po.perihal?.nama || "-",
  tanggal: po.tanggal,
  subtotal: po.total || po.pivot?.subtotal || 0,
  keterangan: po.keterangan || "",
}));

async function handleAddPO(data: any) {
  await fetchPOs(data?.search || "");
}

function handleAddSelectedPOs(selectedPOs: any[]) {
  selectedPOs.forEach((po) => {
    const exists = selectedPoItems.value.some((item) => (item.id || item.po_id) === po.id);
    if (!exists) {
      const poItem = {
        id: po.id,
        po_id: po.id,
        no_po: po.no_po,
        department_name: po.department?.name || "-",
        perihal_name: po.perihal?.nama || "-",
        tanggal: po.tanggal,
        subtotal: po.total || 0,
        keterangan: po.keterangan || "",
      };
      selectedPoItems.value.push(poItem);
    }
  });
}

function handleUpdateTotal(total: number) {
  totalFromBarangGrid.value = total;
}

function handleAddPph(pphBaru: any) {
  const normalized = {
    value: pphBaru.id ?? pphBaru.value,
    label:
      pphBaru.label || `${pphBaru.nama_pph || pphBaru.nama} (${pphBaru.tarif_pph ?? (pphBaru.tarif ? pphBaru.tarif * 100 : 0)}%)`,
    nama_pph: pphBaru.nama_pph || pphBaru.nama,
    kode_pph: pphBaru.kode_pph || pphBaru.kode,
    tarif_pph: pphBaru.tarif_pph ?? (pphBaru.tarif ? pphBaru.tarif * 100 : 0),
    id: pphBaru.id ?? pphBaru.value,
  };
  const exists = localPphOptions.value.some((x) => String(x.value) === String(normalized.value));
  if (!exists) localPphOptions.value = [...localPphOptions.value, normalized];
}

async function submitUpdate() {
  try {
    isSubmitting.value = true;
    const payload: any = { ...formData.value };
    payload.purchase_order_ids = selectedPoItems.value.map((x: any) => x.id || x.po_id);
    await axios.patch(`/payment-voucher/${props.id}`, payload, { withCredentials: true });
    isSubmitting.value = false;
  } catch (e) {
    isSubmitting.value = false;
    console.error("Failed to update Payment Voucher", e);
  }
}

function handleCancel() {
  window.history.back();
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
    } else {
      availablePOs.value = [];
    }
  } catch (e) {
    availablePOs.value = [];
    console.error("Failed to fetch POs for PV:", e);
  }
}

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
