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

      <!-- Rejection Reason Banner -->
      <div
        v-if="(props.paymentVoucher?.status || formData.value?.status) === 'Rejected' && (props.paymentVoucher?.rejection_reason || formData.value?.rejection_reason)"
        class="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-red-800"
      >
        <div class="flex items-start gap-3">
          <svg class="w-5 h-5 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M4.93 4.93l14.14 14.14M12 7a5 5 0 015 5m0 0a5 5 0 11-10 0 5 5 0 0110 0z" />
          </svg>
          <div class="flex-1">
            <p class="text-sm font-semibold">Alasan Ditolak</p>
            <p class="text-sm whitespace-pre-wrap">{{ (props.paymentVoucher?.rejection_reason || formData.value?.rejection_reason) }}</p>
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
            :creditCardOptions="creditCardOptionsLocal"
            :giroOptions="props.giroOptions"
            :availablePOs="availablePOs"
            :purchaseOrderOptions="purchaseOrderOptions"
            :availableMemos="availableMemos"
            :memoOptions="memoOptions"
            :currencyOptions="props.currencyOptions"
            :banks="props.banks"
            @search-purchase-orders="handleSearchPOs"
            @add-purchase-order="handleAddPO"
            @search-memos="handleSearchMemos"
            @add-memo="handleAddMemo"
            @refresh-suppliers="handleRefreshSuppliers"
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
            @click="() => submitUpdate(true, true, true)"
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
        <ConfirmDialog
          :show="confirmShow"
          :message="confirmMessage"
          @confirm="onConfirm"
          @cancel="onCancel"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from "vue";
import axios from "axios";
import PaymentVoucherForm from "../../components/payment-voucher/PaymentVoucherForm.vue";
import PaymentVoucherSupportingDocs from "../../components/payment-voucher/PaymentVoucherSupportingDocs.vue";
// import PaymentVoucherBarangGrid from "../../components/payment-voucher/PaymentVoucherBarangGrid.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { WalletCards } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { router, usePage } from "@inertiajs/vue3";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";

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
  banks?: any[];
}>();

const formData = ref<any>({ ...(props.paymentVoucher || {}) });
const creditCardOptionsLocal = ref<any[]>(props.creditCardOptions || []);
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
const submittingLock = ref(false);
const { addSuccess, addError, clearAll } = useMessagePanel();
// Guard to prevent duplicate flash messages when page props update rapidly
const lastFlash = ref<{ success?: string; error?: string }>({});

// Watch for server flash messages and display via message panel (align with Index/Create)
const page = usePage();
watch(
  () => page.props,
  (newProps) => {
    const flash = (newProps as any)?.flash || {};
    if (typeof flash.success === "string" && flash.success) {
      if (flash.success !== lastFlash.value.success) {
        addSuccess(flash.success);
        lastFlash.value.success = flash.success;
      }
    }
    if (typeof flash.error === "string" && flash.error) {
      if (flash.error !== lastFlash.value.error) {
        addError(flash.error);
        lastFlash.value.error = flash.error;
      }
    }
  },
  { immediate: false }
);

// Confirm dialog state
const confirmShow = ref(false);
const confirmMessage = ref("");
let confirmAction: (() => void) | null = null;
function openConfirm(message: string, action: () => void) {
  confirmMessage.value = message;
  confirmAction = action;
  confirmShow.value = true;
}
function onConfirm() {
  confirmShow.value = false;
  const action = confirmAction;
  confirmAction = null;
  if (action) action();
}
function onCancel() {
  confirmShow.value = false;
  confirmAction = null;
}

function handleRefreshSuppliers() {
  try {
    router.reload({ only: ["supplierOptions"] });
  } catch {}
}

// Initialize selectedPoItems from PV purchaseOrders
selectedPoItems.value = [];

// Seed current PO into options and list so it displays on mount
try {
  const pvRaw: any = (props.paymentVoucher as any);
  const pvPO: any = pvRaw?.purchaseOrder || pvRaw?.purchase_order;
  if (pvPO && pvPO.id) {
    // Ensure model has the selected id
    if (!formData.value?.purchase_order_id) {
      formData.value = { ...(formData.value || {}), purchase_order_id: pvPO.id };
    }
    // Add to dropdown options if missing
    const option = { value: pvPO.id, label: `${pvPO.no_po}` };
    const exists = purchaseOrderOptions.value.some((o) => o.value === pvPO.id);
    if (!exists) purchaseOrderOptions.value = [option, ...purchaseOrderOptions.value];
    // Add to availablePOs for info panel resolution
    const inList = availablePOs.value.some((po) => po.id === pvPO.id);
    if (!inList) availablePOs.value = [pvPO, ...availablePOs.value];
  }
} catch {}

// Seed current Memo Pembayaran into options and list so it displays on mount
try {
  const pvRaw: any = (props.paymentVoucher as any);
  const pvMemo: any = pvRaw?.memoPembayaran || pvRaw?.memo_pembayaran;
  if (pvMemo && pvMemo.id) {
    // Ensure model has the selected memo id
    if (!formData.value?.memo_id) {
      formData.value = { ...(formData.value || {}), memo_id: pvMemo.id };
    }
    // Add to memoOptions for dropdown if missing
    const memoOpt = { value: pvMemo.id, label: `${pvMemo.no_memo || pvMemo.number || pvMemo.id}` };
    const memoExists = memoOptions.value.some((o) => o.value === pvMemo.id);
    if (!memoExists) memoOptions.value = [memoOpt, ...memoOptions.value];
    // Add to availableMemos for info panel
    const memoInList = availableMemos.value.some((m) => m.id === pvMemo.id);
    if (!memoInList) availableMemos.value = [pvMemo, ...availableMemos.value];
  }
} catch {}

// Ensure selected credit card appears in options on edit
try {
  const pvRaw: any = (props.paymentVoucher as any);
  const selectedCcId = (formData.value as any)?.credit_card_id;
  if (selectedCcId) {
    const exists = (creditCardOptionsLocal.value || []).some(
      (c: any) => String(c.value ?? c.id) === String(selectedCcId)
    );
    if (!exists) {
      const cc = pvRaw?.credit_card || pvRaw?.creditCard || null;
      const label = cc?.label || cc?.card_number || cc?.no_kartu_kredit || String(selectedCcId);
      const deptId = (formData.value as any)?.department_id || pvRaw?.department_id;
      const bankId = cc?.bank_id || cc?.bank?.id;
      const bankName = cc?.bank_name || cc?.bank?.nama_bank;
      const ownerName = cc?.owner_name || cc?.nama_pemilik;
      creditCardOptionsLocal.value = [
        {
          id: selectedCcId,
          value: selectedCcId,
          label,
          card_number: cc?.card_number || cc?.no_kartu_kredit || label,
          department_id: deptId,
          bank_id: bankId,
          bank_name: bankName,
          owner_name: ownerName,
        },
        ...(creditCardOptionsLocal.value || []),
      ];
    }
  }
} catch {}

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
    // mirror helpful context from PO so it persists on update immediately
    department_id: (formData.value as any)?.department_id || po.department?.id || po.department_id || (formData.value as any)?.departmentId,
    supplier_id: (formData.value as any)?.supplier_id || po.supplier_id || po.supplier?.id,
    metode_bayar: (formData.value as any)?.metode_bayar || po.metode_pembayaran || po.metode_bayar,
  };
  const poOption = {
    value: po.id,
    label: `${po.no_po} - ${po.department?.name || "-"} - ${po.perihal?.nama || "-"}`,
  };
  const exists = purchaseOrderOptions.value.some((option) => option.value === po.id);
  if (!exists) purchaseOrderOptions.value = [poOption, ...purchaseOrderOptions.value];
  // keep the full PO for info panel and form watchers (perihal fill)
  const inList = availablePOs.value.some((x:any) => x.id === po.id);
  if (!inList) availablePOs.value = [po, ...availablePOs.value];
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

async function submitUpdate(showMessage = true, redirect = false, saveAsDraft = false) {
  try {
    if (submittingLock.value) return;
    submittingLock.value = true;
    isSubmitting.value = true;
    const payload: any = { ...formData.value };
    const tipe = (formData.value as any)?.tipe_pv;
    if (tipe === 'Lainnya') {
      payload.memo_pembayaran_id = (formData.value as any)?.memo_id || (formData.value as any)?.memo_pembayaran_id || null;
      payload.purchase_order_id = null;
    } else if (tipe === 'Manual') {
      payload.memo_pembayaran_id = null;
      payload.purchase_order_id = null;
    } else {
      payload.purchase_order_id = (formData.value as any)?.purchase_order_id || (formData.value as any)?.purchaseOrder?.id || null;
      payload.memo_pembayaran_id = null;
    }
    if (saveAsDraft) payload.save_as_draft = true;
    // Single purchase_order_id already in payload
    await axios.patch(`/payment-voucher/${props.id}`, payload, { withCredentials: true });

    if (showMessage) {
      addSuccess("Payment Voucher berhasil diperbarui");
    }
    if (redirect) {
      window.location.href = "/payment-voucher";
    }
    isSubmitting.value = false;
  } catch (e) {
    isSubmitting.value = false;
    console.error("Failed to update Payment Voucher", e);
    if (showMessage) {
      addError("Gagal memperbarui Payment Voucher. Silakan coba lagi.");
    }
  }
  finally {
    submittingLock.value = false;
  }
}

function handleCancel() {
  router.visit("/payment-voucher");
}

async function handleSend() {
  if (isSubmitting.value) return;
  const doSend = async () => {
    try {
      isSubmitting.value = true;
      // Save latest form changes (without redirect, not as draft)
      try {
        await submitUpdate(false, false, false);
      } catch {}
      const { data } = await axios.post(
        "/payment-voucher/send",
        { ids: [props.id] },
        { withCredentials: true }
      );
      if (data && data.success) {
        try { clearAll(); } catch {}
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
      let msg = data?.message || data?.error || anyErr?.message || "Gagal mengirim Payment Voucher.";
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
  };
  openConfirm("Kirim draft Payment Voucher ini? Setelah dikirim, dokumen tidak dapat diedit.", doSend);
}

async function fetchPOs(search: string = "") {
  try {
    const params: any = { per_page: 20 };
    const m = (formData.value as any)?.metode_bayar;
    if (m) params.metode_bayar = m;
    const tipe = (formData.value as any)?.tipe_pv;
    if (tipe) params.tipe_pv = tipe;
    // Always include department and supplier filters if present
    if ((formData.value as any)?.department_id) {
      params.department_id = (formData.value as any).department_id;
    }
    if ((formData.value as any)?.supplier_id) {
      params.supplier_id = (formData.value as any).supplier_id;
    }
    if (m === "Cek/Giro" && (formData.value as any)?.giro_id) {
      params.giro_id = (formData.value as any).giro_id;
    } else if (m === "Kartu Kredit" && (formData.value as any)?.credit_card_id) {
      params.credit_card_id = (formData.value as any).credit_card_id;
    }
    if (search) params.search = search;

    const { data } = await axios.get("/payment-voucher/purchase-orders/search", {
      params: { ...params, current_pv_id: props.id },
      withCredentials: true,
    });
    if (data && data.success) {
      availablePOs.value = data.data || [];
      purchaseOrderOptions.value = (data.data || []).map((po: any) => ({
        value: po.id,
        label: `${po.no_po}`,
      }));
      // Ensure currently selected PO remains in options even if not in the fetched list
      const selectedId = (formData.value as any)?.purchase_order_id;
      if (selectedId && !purchaseOrderOptions.value.some((o) => o.value === selectedId)) {
        // Try to find from existing availablePOs or props
        const fromAvail = (availablePOs.value || []).find((po: any) => po.id === selectedId);
        const pvRaw: any = (props.paymentVoucher as any);
        const pvPO: any = pvRaw?.purchaseOrder || pvRaw?.purchase_order;
        const label = fromAvail?.no_po || pvPO?.no_po || String(selectedId);
        purchaseOrderOptions.value = [{ value: selectedId, label }, ...purchaseOrderOptions.value];
      }
      // Also keep availablePOs containing the selected PO so info panel works
      const selectedId2 = (formData.value as any)?.purchase_order_id;
      if (selectedId2 && !(availablePOs.value || []).some((po: any) => po.id === selectedId2)) {
        const pvRaw2: any = (props.paymentVoucher as any);
        const pvPO2: any = pvRaw2?.purchaseOrder || pvRaw2?.purchase_order;
        if (pvPO2 && pvPO2.id === selectedId2) {
          availablePOs.value = [pvPO2, ...availablePOs.value];
        }
      }
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
      memoOptions.value = (data.data || []).map((mm: any) => ({ value: mm.id, label: `${mm.no_memo}` }));
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
    (formData.value as any)?.department_id,
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

// No auto-save: draft hanya tersimpan saat menekan tombol Simpan Draft
// (tidak ada watcher tambahan)
</script>
