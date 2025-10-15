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
            :availablePOs="availablePOs"
            :purchaseOrderOptions="purchaseOrderOptions"
            :availableMemos="availableMemos"
            :memoOptions="memoOptions"
            :currencyOptions="props.currencyOptions"
            @search-purchase-orders="handleSearchPOs"
            @add-purchase-order="handleAddPO"
            @search-memos="handleSearchMemos"
            @add-memo="handleAddMemo"
          />

          <hr class="my-6" />

  <!-- Barang Grid removed: now single Purchase Order via select in form -->
        </div>

        <div v-show="activeTab === 'docs'">
          <PaymentVoucherSupportingDocs :pvId="props.id" />
        </div>

        <!-- Action Buttons - shown on all tabs -->
        <div class="flex justify-start gap-3 pt-6 border-t border-gray-200 mt-6">
          <button
            type="button"
            @click="handleSend"
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
            @click="() => submitUpdate()"
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
            Batal
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch, computed } from "vue";
import axios from "axios";
import PaymentVoucherForm from "../../components/payment-voucher/PaymentVoucherForm.vue";
import PaymentVoucherSupportingDocs from "../../components/payment-voucher/PaymentVoucherSupportingDocs.vue";
// import PaymentVoucherBarangGrid from "../../components/payment-voucher/PaymentVoucherBarangGrid.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";

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
  currencyOptions?: any[];
}>();

const formData = ref<any>({ ...(props.paymentVoucher || {}) });
const availablePOs = ref<any[]>([]);
const purchaseOrderOptions = ref<any[]>([]);
const availableMemos = ref<any[]>([]);
const memoOptions = ref<any[]>([]);
const activeTab = ref<"form" | "docs">("form");
// Single PO now handled within PaymentVoucherForm via purchase_order_id
const selectedPoItems = ref<any[]>([]);
const isSubmitting = ref(false);
// const totalFromBarangGrid = ref<number | undefined>(undefined);
// const localPphOptions = ref<any[]>(props.pphOptions || []);
const autoSaveTimeout = ref<number | null>(null);
const isDraft = computed(() => formData.value?.status === "Draft");
const { addSuccess, addError } = useMessagePanel();

// Initialize selectedPoItems from PV purchaseOrders
selectedPoItems.value = [];

// PO Selection handlers (align with Create)
async function handleSearchPOs(search: string) {
  await fetchPOs(search);
}

async function handleSearchMemos(search: string) {
  await fetchMemos(search);
}

async function handleAddPO(po: any) {
  formData.value = {
    ...formData.value,
    purchase_order_id: po.id,
    nominal: po.total || 0,
  };
  const poOption = {
    value: po.id,
    label: `${po.no_po} - ${po.department?.name || "-"} - ${po.perihal?.nama || "-"}`,
  };
  const exists = purchaseOrderOptions.value.some((option) => option.value === po.id);
  if (!exists) purchaseOrderOptions.value = [poOption, ...purchaseOrderOptions.value];
}

async function handleAddMemo(memo: any) {
  formData.value = {
    ...formData.value,
    memo_id: memo.id,
    purchase_order_id: null,
    nominal: memo.total || memo.nominal || 0,
  };
  const opt = { value: memo.id, label: `${memo.no_memo || memo.number || memo.id}` };
  const exists = memoOptions.value.some((o) => o.value === memo.id);
  if (!exists) memoOptions.value = [opt, ...memoOptions.value];
}

// removed leftover functions from multi-PO grid

async function submitUpdate(showMessage = true) {
  try {
    isSubmitting.value = true;
    const payload: any = { ...formData.value };
    // Single purchase_order_id already in payload
    await axios.patch(`/payment-voucher/${props.id}`, payload, { withCredentials: true });

    if (showMessage) {
      addSuccess("Payment Voucher berhasil diperbarui");
    }
    isSubmitting.value = false;
  } catch (e) {
    isSubmitting.value = false;
    console.error("Failed to update Payment Voucher", e);
    if (showMessage) {
      addError("Gagal memperbarui Payment Voucher. Silakan coba lagi.");
    }
  }
}

// Auto-save functionality for drafts
function scheduleAutoSave() {
  if (!isDraft.value) return; // Only auto-save drafts

  if (autoSaveTimeout.value) {
    clearTimeout(autoSaveTimeout.value);
  }

  autoSaveTimeout.value = setTimeout(() => {
    if (hasFormData()) {
      submitUpdate(false); // Auto-save without showing message
    }
  }, 3000); // Auto-save after 3 seconds of inactivity
}

function hasFormData() {
  const data = formData.value as any;
  return (
    data &&
    (data.supplier_id ||
      data.department_id ||
      data.perihal_id ||
      data.nominal ||
      data.metode_bayar ||
      data.note ||
      data.keterangan)
  );
}

function handleCancel() {
  window.history.back();
}

async function cancelDraft() {
  if (!isDraft.value) return;
  try {
    isSubmitting.value = true;
    await axios.post(
      `/payment-voucher/${props.id}/cancel`,
      {},
      { withCredentials: true }
    );
    window.history.back();
  } catch (e) {
    console.error("Failed to cancel Payment Voucher draft", e);
    addError("Gagal membatalkan draft Payment Voucher.");
  } finally {
    isSubmitting.value = false;
  }
}

async function handleSend() {
  if (isSubmitting.value) return;
  try {
    isSubmitting.value = true;
    // Simpan perubahan terlebih dahulu
    await submitUpdate(false);
    // Kirim PV
    const { data } = await axios.post(
      "/payment-voucher/send",
      { ids: [props.id] },
      { withCredentials: true }
    );
    if (data && data.success) {
      addSuccess("Payment Voucher berhasil dikirim");
      window.location.href = "/payment-voucher";
    } else {
      const msg = data?.message || "Gagal mengirim Payment Voucher.";
      addError(msg);
    }
  } catch (e) {
    console.error("Failed to send Payment Voucher", e);
    const anyErr: any = e as any;
    const data = anyErr?.response?.data;
    let msg =
      data?.message || data?.error || anyErr?.message || "Gagal mengirim Payment Voucher.";
    try {
      const invalid = data?.invalid_fields as any[] | undefined;
      const missingDocs = data?.missing_documents as any[] | undefined;
      const parts: string[] = [];
      if (Array.isArray(invalid) && invalid.length) {
        const first = invalid[0];
        if (first?.missing?.length) parts.push(`Field: ${first.missing.join(", ")}`);
      }
      if (Array.isArray(missingDocs) && missingDocs.length) {
        const first = missingDocs[0];
        if (first?.missing_types?.length) parts.push(`Dokumen: ${first.missing_types.join(", ")}`);
      }
      if (parts.length) msg = `${msg} (${parts.join("; ")})`;
    } catch {}
    addError(msg);
  } finally {
    isSubmitting.value = false;
  }
}

async function fetchPOs(search: string = "") {
  try {
    const params: any = { per_page: 20 };
    const m = (formData.value as any)?.metode_bayar;
    if (m) params.metode_bayar = m;
    const tipe = (formData.value as any)?.tipe_pv;
    if (tipe) params.tipe_pv = tipe;
    if (m === "Transfer" && (formData.value as any)?.supplier_id) {
      params.supplier_id = (formData.value as any).supplier_id;
    } else if (m === "Cek/Giro" && (formData.value as any)?.giro_id) {
      params.giro_id = (formData.value as any).giro_id;
    } else if (m === "Kartu Kredit" && (formData.value as any)?.credit_card_id) {
      params.credit_card_id = (formData.value as any).credit_card_id;
    }
    if (search) params.search = search;

    const { data } = await axios.get("/payment-voucher/purchase-orders/search", {
      params,
      withCredentials: true,
    });
    if (data && data.success) {
      availablePOs.value = data.data || [];
      purchaseOrderOptions.value = (data.data || []).map((po: any) => ({
        value: po.id,
        label: `${po.no_po}`,
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

async function fetchMemos(search: string = "") {
  try {
    const params: any = { per_page: 20 };
    const m = (formData.value as any)?.metode_bayar;
    if (m) params.metode_bayar = m;
    const tipe = (formData.value as any)?.tipe_pv;
    if (tipe) params.tipe_pv = tipe;
    if ((formData.value as any)?.department_id) params.department_id = (formData.value as any).department_id;
    if ((formData.value as any)?.supplier_id) params.supplier_id = (formData.value as any).supplier_id;
    if (search) params.search = search;

    const { data } = await axios.get("/payment-voucher/memos/search", {
      params,
      withCredentials: true,
    });
    if (data && data.success) {
      availableMemos.value = data.data || [];
      memoOptions.value = (data.data || []).map((mm: any) => ({ value: mm.id, label: `${mm.no_memo || mm.number || mm.id}` }));
    } else {
      availableMemos.value = [];
      memoOptions.value = [];
    }
  } catch (e) {
    availableMemos.value = [];
    memoOptions.value = [];
    console.error("Failed to fetch memos for PV:", e);
  }
}

watch(
  () => [
    (formData.value as any)?.metode_bayar,
    (formData.value as any)?.supplier_id,
    (formData.value as any)?.giro_id,
    (formData.value as any)?.credit_card_id,
    (formData.value as any)?.tipe_pv,
  ],
  () => {
    fetchPOs("");
    fetchMemos("");
  },
  { deep: false }
);

// Auto-save when form data changes (only for drafts)
watch(
  formData,
  () => {
    scheduleAutoSave();
  },
  { deep: true }
);
</script>
