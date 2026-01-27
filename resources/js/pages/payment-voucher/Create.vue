<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Payment Voucher</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <TicketPercent class="w-4 h-4 mr-1" />
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
            :currencyOptions="props.currencyOptions"
            :memoOptions="memoOptions"
            :availableMemos="availableMemos"
            :banks="props.banks"
            :bisnisPartnerOptions="props.bisnisPartnerOptions"
            :poAnggaranOptions="poAnggaranOptions"
            :availablePoAnggarans="availablePoAnggarans"
            @search-purchase-orders="handleSearchPOs"
            @add-purchase-order="handleAddPO"
            @search-memos="handleSearchMemos"
            @add-memo="handleAddMemo"
            @refresh-suppliers="handleRefreshSuppliers"
            @search-po-anggaran="handleSearchPoAnggaran"
            @add-po-anggaran="handleAddPoAnggaran"
          />
        </div>

        <div v-show="activeTab === 'docs'">
          <PaymentVoucherSupportingDocs ref="docsRef" v-model:pvId="draftId" />
        </div>
      </div>
      <!-- Action Buttons - shown on all tabs -->
        <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
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
            @click="() => saveDraft(true, true)"
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
        <ConfirmDialog
          :show="confirmShow"
          :message="confirmMessage"
          @confirm="onConfirm"
          @cancel="onCancel"
        />
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
import { router, usePage } from "@inertiajs/vue3";
import { TicketPercent } from "lucide-vue-next";
import { useMessagePanel } from "@/composables/useMessagePanel";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";

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
  defaultDepartmentId?: string | number | null;
  perihalOptions?: any[];
  creditCardOptions?: any[];
  giroOptions?: any[];
  pphOptions?: any[];
  currencyOptions?: any[];
  bisnisPartnerOptions?: any[];
}>();

const formData = ref({});
const docsRef = ref<any>(null);
const availablePOs = ref<any[]>([]);
const purchaseOrderOptions = ref<any[]>([]);
const availableMemos = ref<any[]>([]);
const memoOptions = ref<any[]>([]);
const availablePoAnggarans = ref<any[]>([]);
const poAnggaranOptions = ref<any[]>([]);
const activeTab = ref<"form" | "docs">("form");
const isSubmitting = ref(false);
const isCreatingDraft = ref(false); // mutex to prevent concurrent store-draft
const draftId = ref<number | null>(null);
const autoSaveTimeout = ref<number | null>(null);
const { addSuccess, addError, clearAll } = useMessagePanel();
// Guard to prevent duplicate flash messages when page props update rapidly
const lastFlash = ref<{ success?: string; error?: string }>({});

// Watch for server flash messages and display via message panel (align with Index.vue)
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

async function handleAddPoAnggaran(poa: any) {
  // Set selected Po Anggaran and clear others
  formData.value = {
    ...formData.value,
    po_anggaran_id: poa.id,
    purchase_order_id: null,
    memo_id: null,
    nominal: poa.nominal || 0,
    department_id: (formData.value as any)?.department_id || poa.department?.id || poa.department_id,
    perihal_id: poa.perihal?.id || poa.perihal_id,
  } as any;

  // Update options list
  const opt = { value: poa.id, label: `${poa.no_po_anggaran}` };
  if (!poAnggaranOptions.value.some((o) => o.value === poa.id)) {
    poAnggaranOptions.value = [opt, ...poAnggaranOptions.value];
  }
  if (!availablePoAnggarans.value.some((x) => x.id === poa.id)) {
    availablePoAnggarans.value = [poa, ...availablePoAnggarans.value];
  }
}

async function handleSearchPoAnggaran(search: string) {
  await fetchPoAnggarans(search);
}

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

// PO Selection handlers
async function handleSearchPOs(search: string) {
  await fetchPOs(search);
}

async function handleSearchMemos(search: string) {
  await fetchMemos(search);
}

async function handleAddMemo(memo: any) {
  // Set the selected memo in formData and clear PO
  formData.value = {
    ...formData.value,
    memo_id: memo.id,
    purchase_order_id: null,
    nominal: memo.total || memo.nominal || 0,
  } as any;

  const memoOption = {
    value: memo.id,
    label: `${memo.no_memo || memo.number || memo.id} - ${(memo.supplier?.nama_supplier || memo.supplier?.name || memo.supplier_name || '-') } - ${(memo.perihal?.nama || memo.perihal_name || '-')}`,
  };
  const exists = memoOptions.value.some((opt) => opt.value === memo.id);
  if (!exists) memoOptions.value = [memoOption, ...memoOptions.value];

  // Ensure full memo object exists for the info panel on the right
  const hasMemo = availableMemos.value.some((m) => m.id === memo.id);
  if (!hasMemo) availableMemos.value = [memo, ...availableMemos.value];
}

async function handleAddPO(payload: any) {
  const po = payload?.po || payload; // backward compatible
  const bpbs = payload?.bpbs || (payload?.bpb ? [payload.bpb] : []);
  const memos = Array.isArray(payload?.memos) ? payload.memos : [];
  const dpPvs = Array.isArray(payload?.dpPvs) ? payload.dpPvs : [];

  // Set the selected PO and optional BPBs (multiple)
  const base: any = {
    ...formData.value,
    purchase_order_id: po.id,
    // mirror helpful context from PO so it persists on draft immediately
    department_id: (formData.value as any)?.department_id || po.department?.id || po.department_id || (formData.value as any)?.departmentId,
    supplier_id: (formData.value as any)?.supplier_id || po.supplier_id || po.supplier?.id,
    metode_bayar: (formData.value as any)?.metode_bayar || po.metode_pembayaran || po.metode_bayar,
  };
  const tipe = String((formData.value as any)?.tipe_pv || "");
  // Helper: FIFO allocation by created order, using 'outstanding' fallbacking to total/grand_total
  function fifoAllocate(items: any[], amount: number, opt: { idKey: string; outKey: string; totalKey: string; allocIdKey: string; }) {
    const result: any[] = [];
    let remaining = Number(amount) || 0;
    for (const it of items) {
      if (remaining <= 0) break;
      const out = Number(it?.[opt.outKey]) || Number(it?.[opt.totalKey]) || 0;
      if (out <= 0) continue;
      const take = Math.min(remaining, out);
      result.push({ [opt.allocIdKey]: it[opt.idKey], amount: take });
      remaining -= take;
    }
    return result;
  }

  // Khusus tipe DP: gunakan DP PO dan lewati alokasi BPB/Memo/PV DP
  if (tipe === 'DP') {
    const dpRemaining = Number((po as any)?.dp_remaining ?? NaN);
    const dpNominal = Number((po as any)?.dp_nominal ?? NaN);
    const fallback = (Number((po as any)?.outstanding ?? 0)
      || Number((po as any)?.grand_total ?? 0)
      || Number((po as any)?.total ?? 0)
      || 0);
    const nominal = Number.isFinite(dpRemaining) && dpRemaining > 0
      ? dpRemaining
      : (Number.isFinite(dpNominal) && dpNominal > 0 ? dpNominal : fallback);
    base.nominal = nominal;
    delete base.bpb_ids;
    base._bpbs = undefined;
    base._memos = undefined;
    delete base.bpb_allocations;
    delete base.memo_allocations;
    delete base.dp_allocations;
  } else if (bpbs.length > 0) {
    // Base nominal hint: use existing nominal if >0 else sum of BPB outstanding
    const nominalHint = Number((formData.value as any)?.nominal) || bpbs.reduce((s:number,b:any)=> s + (Number(b.outstanding ?? b.grand_total) || 0), 0);
    // Oldest-first: assume fetched asc order from backend; if not, sort by tanggal/id
    const sorted = [...bpbs].sort((a:any,b:any)=> new Date(a.tanggal||0).getTime() - new Date(b.tanggal||0).getTime() || (a.id - b.id));
    const allocs = fifoAllocate(sorted, nominalHint, { idKey: 'id', outKey: 'outstanding', totalKey: 'grand_total', allocIdKey: 'bpb_id' });
    const sumAlloc = allocs.reduce((s:number,x:any)=> s + (Number(x.amount)||0), 0);
    base.bpb_allocations = allocs;
    base._bpbAllocations = allocs;
    base._bpbs = bpbs; // keep for UI reference
    base.nominal = sumAlloc;
    delete base.bpb_id;
    delete base.bpb_ids;
    // Clear memo allocations when BPB selected (BPB takes precedence)
    delete base.memo_allocations;
    delete base._memoAllocations;
  } else if (memos.length > 0) {
    const nominalHint = Number((formData.value as any)?.nominal) || memos.reduce((s:number,m:any)=> s + (Number(m.outstanding ?? m.total) || 0), 0);
    const sorted = [...memos].sort((a:any,b:any)=> new Date(a.tanggal||0).getTime() - new Date(b.tanggal||0).getTime() || (a.id - b.id));
    const allocs = fifoAllocate(sorted, nominalHint, { idKey: 'id', outKey: 'outstanding', totalKey: 'total', allocIdKey: 'memo_id' });
    const sumAlloc = allocs.reduce((s:number,x:any)=> s + (Number(x.amount)||0), 0);
    base.memo_allocations = allocs;
    base._memoAllocations = allocs;
    base._bpbs = undefined;
    base.nominal = Math.min(nominalHint, sumAlloc);
  } else if (tipe !== 'DP') {
    // Reguler tanpa BPB/Memo eksplisit: gunakan outstanding PO bila tersedia
    const grandTotal = Number((po as any)?.grand_total ?? (po as any)?.total ?? 0) || 0;
    const out = Number((po as any)?.outstanding ?? NaN);
    base.nominal = Number.isFinite(out) && out > 0 ? out : grandTotal;
    delete base.bpb_ids;
    base._bpbs = undefined;
  }
  // DP allocations (PV Reguler menggunakan PV DP yang dipilih dari PO ini)
  if (tipe === 'Reguler' && dpPvs.length > 0) {
    const allocs = dpPvs
      .map((row: any) => {
        const nominal = Number(row.nominal || 0) || 0;
        const out = Number(row.outstanding || nominal) || 0;
        const amt = Math.min(Number(row.amount || 0) || 0, out);
        return amt > 0
          ? { dp_payment_voucher_id: Number(row.id), amount: amt }
          : null;
      })
      .filter((x: any) => x != null);
    if (allocs.length > 0) {
      base.dp_allocations = allocs as any;
      (base as any)._dpAllocations = allocs as any;
    } else {
      delete base.dp_allocations;
      delete (base as any)._dpAllocations;
    }
  } else {
    delete base.dp_allocations;
    delete (base as any)._dpAllocations;
  }

  // Keep memos UI reference for clamping logic
  if (memos.length > 0) {
    base._memos = memos;
  } else {
    delete base._memos;
  }
  formData.value = base;

  // Update purchase order options to include the selected PO
  const poOption = {
    value: po.id,
    label: `${po.no_po} - ${po.department?.name || "-"} - ${po.perihal?.nama || "-"}`,
  };

  // Check if PO is already in options
  const exists = purchaseOrderOptions.value.some((option) => option.value === po.id);
  if (!exists) {
    purchaseOrderOptions.value = [poOption, ...purchaseOrderOptions.value];
  }

  // Ensure full PO object exists for info panel and form watchers
  const hasPO = availablePOs.value.some((x) => x.id === po.id);
  if (!hasPO) {
    availablePOs.value = [po, ...availablePOs.value];
  }
}

// Action button handlers
async function saveDraft(showMessage = true, redirect = false, skipLog = false) {
  if (isSubmitting.value) return;
  // Cancel any scheduled autosave to avoid back-to-back requests
  if (autoSaveTimeout.value) {
    clearTimeout(autoSaveTimeout.value);
    autoSaveTimeout.value = null;
  }

  isSubmitting.value = true;

  try {
    const payload: any = { ...formData.value };
    // Normalize memo/po fields based on tipe
    const tipe = (formData.value as any)?.tipe_pv;
    if (tipe === 'Lainnya') {
      payload.memo_pembayaran_id = (formData.value as any)?.memo_id || null;
      payload.purchase_order_id = null;
      payload.po_anggaran_id = null;
    } else if (tipe === 'Manual') {
      payload.memo_pembayaran_id = null;
      payload.purchase_order_id = null;
      payload.po_anggaran_id = null;
    } else if (tipe === 'Anggaran') {
      payload.purchase_order_id = null;
      payload.memo_pembayaran_id = null;
      payload.po_anggaran_id = (formData.value as any)?.po_anggaran_id || null;
    } else {
      // Reguler: PO based
      payload.purchase_order_id = (formData.value as any)?.purchase_order_id || null;
      payload.memo_pembayaran_id = null;
      payload.po_anggaran_id = null;
      // Include allocations if present
      if (Array.isArray((formData.value as any)?.bpb_allocations) && (formData.value as any)?.bpb_allocations.length > 0) {
        payload.bpb_allocations = (formData.value as any).bpb_allocations;
      } else if (Array.isArray((formData.value as any)?._bpbAllocations) && (formData.value as any)?._bpbAllocations.length > 0) {
        payload.bpb_allocations = (formData.value as any)._bpbAllocations;
      }
      if (!payload.bpb_allocations || payload.bpb_allocations.length === 0) {
        if (Array.isArray((formData.value as any)?.memo_allocations) && (formData.value as any)?.memo_allocations.length > 0) {
          payload.memo_allocations = (formData.value as any).memo_allocations;
        } else if (Array.isArray((formData.value as any)?._memoAllocations) && (formData.value as any)?._memoAllocations.length > 0) {
          payload.memo_allocations = (formData.value as any)._memoAllocations;
        }
      }
    }

    let response;
    if (draftId.value) {
      // Update existing draft
      response = await axios.patch(`/payment-voucher/${draftId.value}`, payload, { withCredentials: true });
    } else {
      // Create new draft (guard against concurrent creation)
      if (isCreatingDraft.value) return; // another create in-flight
      isCreatingDraft.value = true;
      try {
        // When skipLog is true (e.g. from direct send flow), instruct backend not to log saved_draft
        response = await axios.post("/payment-voucher/store-draft", { ...payload, skip_log: skipLog }, { withCredentials: true });
      } finally {
        isCreatingDraft.value = false;
      }
    }

    if (response.data && (response.data.id || response.data.success)) {
      // Store the draft ID for future updates
      draftId.value = response.data.id || draftId.value;
      (formData.value as any).id = draftId.value;

      if (showMessage) {
        addSuccess("Draft Payment Voucher berhasil disimpan");
      }
      // Sync current checklist active states to server so Edit reflects them
      try { await docsRef.value?.syncActiveStates(draftId.value); } catch {}
      // Flush any queued document uploads before redirect (if any)
      try { await docsRef.value?.flushUploads(draftId.value); } catch {}
      if (redirect) {
        router.visit("/payment-voucher");
      }
    }
  } catch (error) {
    console.error("Error saving draft:", error);
    if (showMessage) {
      addError("Gagal menyimpan draft. Silakan coba lagi.");
    }
  } finally {
    isSubmitting.value = false;
  }
}

// No auto-save helper: draft hanya tersimpan saat menekan tombol Simpan Draft

function handleCancel() {
  router.visit("/payment-voucher");
}

async function handleSend() {
  if (isSubmitting.value) return;
  const doSend = async () => {
    try {
      isSubmitting.value = true;
      // Clear previous messages to avoid stacking validation and success popups
      try { clearAll(); } catch {}

      // Pre-validate kelengkapan dokumen wajib sebelum membuat/menyimpan draft
      try {
        const missingDocs: string[] | undefined = docsRef.value?.getRequiredMissingDocs?.();
        if (Array.isArray(missingDocs) && missingDocs.length > 0) {
          addError(
            `Dokumen wajib belum lengkap: ${missingDocs.join(", ")}. Silakan upload dokumen terlebih dahulu sebelum mengirim.`
          );
          activeTab.value = "docs";
          isSubmitting.value = false;
          return;
        }
      } catch {}

      // Always ensure a draft exists, upload queued files, then send by id
      // 1) Create or update draft (skip draft log when coming from send flow)
      if (!draftId.value) {
        await saveDraft(false, false, true);
      } else {
        await saveDraft(false, false, true);
      }
      // Ensure draftId exists before proceeding; if not, try to create directly
      if (!draftId.value) {
        try {
          const payload: any = { ...formData.value };
          const tipe = (formData.value as any)?.tipe_pv;
          if (tipe === 'Lainnya') {
            payload.memo_pembayaran_id = (formData.value as any)?.memo_id || null;
            payload.purchase_order_id = null;
            payload.po_anggaran_id = null;
          } else if (tipe === 'Manual') {
            payload.memo_pembayaran_id = null;
            payload.purchase_order_id = null;
            payload.po_anggaran_id = null;
          } else if (tipe === 'Anggaran') {
            payload.purchase_order_id = null;
            payload.memo_pembayaran_id = null;
            payload.po_anggaran_id = (formData.value as any)?.po_anggaran_id || null;
          } else {
            payload.purchase_order_id = (formData.value as any)?.purchase_order_id || null;
            payload.memo_pembayaran_id = null;
            payload.po_anggaran_id = null;
          }
          const resp = await axios.post("/payment-voucher/store-draft", { ...payload, skip_log: true }, { withCredentials: true });
          if (resp?.data?.id) {
            draftId.value = resp.data.id;
            (formData.value as any).id = draftId.value;
          }
        } catch {}
      }
      if (!draftId.value) {
        addError("Draft belum berhasil dibuat. Silakan klik Simpan Draft lalu coba Kirim lagi.");
        isSubmitting.value = false;
        return;
      }

      // 2) Ensure checklist active states are persisted server-side
      if (draftId.value) {
        try { await docsRef.value?.syncActiveStates(draftId.value); } catch {}
      }
      // 3) Flush any queued uploads to the draft id
      if (draftId.value) {
        try { await docsRef.value?.flushUploads(draftId.value); } catch {}
      }
      // 4) Send by ids so backend validates against uploaded files
      const numericId = Number(draftId.value);
      if (!Number.isInteger(numericId)) {
        addError("Terjadi kesalahan ID draft. Silakan refresh halaman dan coba lagi.");
        isSubmitting.value = false;
        return;
      }
      let documentsActive: string[] | undefined;
      try {
        const actives = docsRef.value?.getActiveDocKeys?.();
        if (Array.isArray(actives)) documentsActive = actives as any;
      } catch {}
      const sentResponse = await axios.post(
        "/payment-voucher/send",
        { ids: [numericId], documents_active: documentsActive },
        { withCredentials: true }
      );
      const data = sentResponse?.data;
      if (data && data.success) {
        try { clearAll(); } catch {}
        addSuccess("Payment Voucher berhasil dikirim");
        router.visit("/payment-voucher");
      } else {
        const msg = data?.message || "Gagal mengirim Payment Voucher.";
        addError(msg);
      }
    } catch (e: any) {
      console.error("Failed to send Payment Voucher", e);
      const data = e?.response?.data;
      let msg = data?.message || data?.error || e?.message || "Gagal mengirim Payment Voucher.";
      try {
        const missingDocs = data?.missing_documents as any[] | undefined;
        // Hanya tambahkan detail jika pesan server belum memuatnya
        const hasDocHint = typeof msg === 'string' && /Dokumen belum lengkap/i.test(msg);
        if (!hasDocHint && Array.isArray(missingDocs) && missingDocs.length) {
          const first = missingDocs[0];
          const labels = (first?.missing_labels && first.missing_labels.length)
            ? first.missing_labels
            : (first?.missing_types || []);
          if (labels.length) {
            msg = `${msg} (Dokumen: ${labels.join(', ')})`;
          }
        }
      } catch {}
      addError(msg);
    } finally {
      isSubmitting.value = false;
    }
  };
  openConfirm("Apakah Anda yakin ingin mengirim Payment Voucher ini?", doSend);
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
      params,
      withCredentials: true,
    });
    if (data && data.success) {
      availablePOs.value = data.data || [];
      // Update purchase order options for dropdown
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
    if ((formData.value as any)?.department_id) {
      params.department_id = (formData.value as any).department_id;
    }
    if ((formData.value as any)?.supplier_id) {
      params.supplier_id = (formData.value as any).supplier_id;
    }
    if (search) params.search = search;

    const { data } = await axios.get("/payment-voucher/memos/search", {
      params,
      withCredentials: true,
    });
    if (data && data.success) {
      availableMemos.value = data.data || [];
      memoOptions.value = (data.data || []).map((mm: any) => ({
        value: mm.id,
        label: `${mm.no_memo}`,
      }));
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

async function fetchPoAnggarans(search: string = "") {
  try {
    const params: any = { per_page: 20 };
    if ((formData.value as any)?.department_id) {
      params.department_id = (formData.value as any).department_id;
    }
    if ((formData.value as any)?.bisnis_partner_id) {
      params.bisnis_partner_id = (formData.value as any).bisnis_partner_id;
    }
    if (search) params.search = search;

    const { data } = await axios.get("/payment-voucher/po-anggaran/search", {
      params,
      withCredentials: true,
    });
    if (data && data.success) {
      const rows: any[] = Array.isArray(data.data) ? data.data : [];

      // Always keep currently selected Po Anggaran in the local lists so it
      // does not disappear from the select/info panel when refetching.
      const currentId = (formData.value as any)?.po_anggaran_id;
      let mergedRows = [...rows];
      if (currentId) {
        const alreadyInRows = mergedRows.some((r: any) => String(r.id) === String(currentId));
        if (!alreadyInRows) {
          const existing = (availablePoAnggarans.value || []).find(
            (x: any) => String(x.id) === String(currentId)
          );
          if (existing) {
            mergedRows = [existing, ...mergedRows];
          }
        }
      }

      availablePoAnggarans.value = mergedRows;
      poAnggaranOptions.value = mergedRows.map((row: any) => ({
        value: row.id,
        label: `${row.no_po_anggaran}`,
      }));
    } else {
      availablePoAnggarans.value = [];
      poAnggaranOptions.value = [];
    }
  } catch (e) {
    availablePoAnggarans.value = [];
    poAnggaranOptions.value = [];
    console.error("Failed to fetch Po Anggaran for PV:", e);
  }
}

// Auto-fetch when metode/supplier/giro/kartu kredit changes
watch(
  () => [
    (formData.value as any)?.metode_bayar,
    (formData.value as any)?.department_id,
    (formData.value as any)?.supplier_id,
    (formData.value as any)?.giro_id,
    (formData.value as any)?.credit_card_id,
    (formData.value as any)?.tipe_pv,
    (formData.value as any)?.bisnis_partner_id,
  ],
  () => {
    fetchPOs("");
    fetchMemos("");
    fetchPoAnggarans("");
  },
  { deep: false }
);

// No auto-save: draft hanya tersimpan saat menekan tombol Simpan Draft

// no auto-draft on mount; documents component can create PV lazily
// Prefill department when only one non-All department is available for the user
watch(
  () => props.defaultDepartmentId,
  (val) => {
    if (val && !(formData.value as any)?.department_id) {
      formData.value = { ...(formData.value as any), department_id: val } as any;
    }
  },
  { immediate: true }
);

</script>
