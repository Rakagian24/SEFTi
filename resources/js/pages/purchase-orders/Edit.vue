<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">
            {{
              purchaseOrder.status === "Rejected"
                ? "Perbaiki Purchase Order"
                : "Edit Purchase Order"
            }}
          </h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            {{ purchaseOrder.status === "Rejected" ? "Perbaiki" : "Edit" }} Purchase Order
            #{{ purchaseOrder.no_po }}
          </div>
        </div>
      </div>

      <!-- Rejection Reason Alert -->
      <div
        v-if="purchaseOrder.status === 'Rejected' && purchaseOrder.rejection_reason"
        class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4"
      >
        <div class="flex items-start">
          <div class="flex-shrink-0">
            <svg
              class="w-5 h-5 text-red-400"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
              />
            </svg>
          </div>
          <div class="ml-3">
            <h3 class="text-sm font-medium text-red-800">Alasan Penolakan</h3>
            <div class="mt-2 text-sm text-red-700">
              <p>{{ purchaseOrder.rejection_reason }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Purchase Order Form Component -->
      <PurchaseOrderForm
        v-model:form="form"
        v-model:dokumenFile="dokumenFile"
        v-model:selectedCreditCardId="selectedCreditCardId"
        v-model:selectedCreditCardBankName="selectedCreditCardBankName"
        :errors="errors"
        :purchaseOrder="purchaseOrder"
        :departemenList="departemenList"
        :perihalList="perihalList"
        :supplierList="supplierList"
        :bankList="bankList"
        :customerOptions="customerOptions"
        :creditCardOptions="creditCardOptions"
        :terminList="terminList"
        :selectedSupplierBankAccounts="selectedSupplierBankAccounts"
        :isStaffToko="isStaffToko"
        :isRefundKonsumenPerihal="isRefundKonsumenPerihal"
        :isSpecialPerihal="isSpecialPerihal"
        :selectedPerihalName="selectedPerihalName"
        :datePickerKey="datePickerKey"
        :displayTanggal="displayTanggal"
        :validTanggalGiro="validTanggalGiro"
        :validTanggalCair="validTanggalCair"
        :displayHarga="displayHarga"
        :jenisBarangList="jenisBarangList"
        :useBarangDropdown="useBarangDropdown"
        @showAddPerihalModal="showAddPerihalModal = true"
        @showAddTerminModal="showAddTerminModal = true"
        @addError="addError"
        @handleCustomerChange="handleCustomerChange"
        @handleCustomerBankChange="handleCustomerBankChange"
        @handleSupplierChange="handleSupplierChange"
        @handleBankSupplierAccountChange="handleBankSupplierAccountChange"
        @handleBankChange="handleBankChange"
        @handleSelectCreditCard="handleSelectCreditCard"
        @handleTerminChange="handleTerminChange"
        @searchCustomers="searchCustomers"
        @searchSuppliers="searchSuppliers"
        @searchCreditCards="searchCreditCards"
        @searchTermins="searchTermins"
        @allowNumericKeydown="allowNumericKeydown"
        @searchJenisBarangs="searchJenisBarangs"
      />

      <!-- Grid/List Barang - Outside the form to prevent submission conflicts -->
      <div class="bg-white rounded-lg shadow-sm p-6">
        <PurchaseOrderBarangGrid
          ref="barangGridRef"
          v-model:items="barangList"
          v-model:diskon="form.diskon"
          v-model:ppn="form.ppn"
          v-model:pph="form.pph_id"
          :pphList="pphList"
          :form="form"
          :nominal="undefined"
          :selected-perihal-name="selectedPerihalName"
          :use-barang-dropdown="useBarangDropdown"
          :selected-jenis-barang-id="form.jenis_barang_id as any"
          :barang-options="barangOptions"
          @search-barangs="searchBarangs"
          @add-pph="onAddPph"
          v-model:dpActive="form.dp_active"
          v-model:dpType="form.dp_type"
          v-model:dpPercent="form.dp_percent"
          v-model:dpNominal="form.dp_nominal"
        />
        <div v-if="errors.barang" class="text-red-500 text-xs mt-1">
          {{ errors.barang }}
        </div>

        <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            @click="showSubmitConfirmation"
            :disabled="loading"
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
            {{ purchaseOrder.status === "Rejected" ? "Kirim Ulang" : "Kirim" }}
          </button>
          <button
            type="button"
            class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            @click="onSaveDraft"
            :disabled="loading"
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
            Simpan Draft
          </button>
          <button
            type="button"
            class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            @click="goBack"
            :disabled="loading"
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

        <PerihalQuickAddModal
          v-if="showAddPerihalModal"
          @close="showAddPerihalModal = false"
          @created="handlePerihalCreated"
        />

        <TerminQuickAddModal
          v-if="showAddTerminModal"
          @close="showAddTerminModal = false"
          @created="handleTerminCreated"
          :department-options="departemenList"
          :department-id="form.department_id as any"
        />

        <!-- Confirm Dialog -->
        <ConfirmDialog
          :show="showConfirmDialog"
          :message="
            confirmAction === 'submit'
              ? 'Apakah Anda yakin ingin mengirim Purchase Order ini?'
              : ''
          "
          @confirm="onSubmit"
          @cancel="showConfirmDialog = false"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrderBarangGrid from "../../components/purchase-orders/PurchaseOrderBarangGrid.vue";
import PurchaseOrderForm from "../../components/purchase-orders/PurchaseOrderForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import PerihalQuickAddModal from "@/components/perihals/PerihalQuickAddModal.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import TerminQuickAddModal from "@/components/termins/TerminQuickAddModal.vue";
import { CreditCard } from "lucide-vue-next";
import axios from "axios";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { usePermissions } from "@/composables/usePermissions";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Purchase Order", href: "/purchase-orders" },
  { label: "Edit" },
];

// Master data from props (provided by Inertia controller)
const props = defineProps<{
  purchaseOrder: any;
  departments: any[];
  perihals: any[];
  suppliers: any[];
  banks: any[];
  pphs: any[];
  termins: any[];
}>();

const departemenList = ref(Array.isArray(props.departments) ? props.departments : []);
const perihalList = ref<any[]>(Array.isArray(props.perihals) ? props.perihals : []);
const supplierList = ref<any[]>([]);
let supplierSearchTimeout: ReturnType<typeof setTimeout>;
const terminList = ref<any[]>(Array.isArray(props.termins) ? props.termins : []);
// Ensure currently selected termin from existing PO is present right away (before any async loads)
terminList.value = ensureSelectedTerminPresent(terminList.value);
let terminSearchTimeout: ReturnType<typeof setTimeout>;
let latestTerminRequestId = 0;
// Kredit: state untuk dropdown kartu kredit
const creditCardOptions = ref<any[]>([]);
const selectedCreditCardId = ref<string | null>(
  props.purchaseOrder.credit_card_id ? String(props.purchaseOrder.credit_card_id) : null
);
const selectedCreditCardBankName = ref<string>("");
let creditCardSearchTimeout: ReturnType<typeof setTimeout>;

// Supplier bank accounts data
const selectedSupplierBankAccounts = ref<any[]>([]);
const selectedSupplier = ref<any>(null);

// Customer data for Refund Konsumen
const customerOptions = ref<any[]>([]);
const bankList = ref<any[]>(props.banks || []);
let customerSearchTimeout: ReturnType<typeof setTimeout>;

// Transform PPH data for grid (tarif in decimal)
const pphList = ref(
  (Array.isArray(props.pphs) ? props.pphs : []).map((pph: any) => ({
    id: pph.id,
    kode: pph.kode_pph,
    nama: pph.nama_pph,
    tarif: pph.tarif_pph ? pph.tarif_pph / 100 : 0,
  }))
);

// Use permissions composable to detect user role
const { hasRole } = usePermissions();
const isStaffToko = computed(
  () =>
    hasRole("Staff Toko") ||
    hasRole("Staff Digital Marketing") ||
    hasRole("Kepala Toko") ||
    hasRole("Admin")
);

// Initialize form with existing PO data
const form = ref({
  tipe_po: props.purchaseOrder.tipe_po || "Reguler",
  tanggal: props.purchaseOrder.tanggal
    ? new Date(props.purchaseOrder.tanggal)
    : new Date(),
  department_id: props.purchaseOrder.department_id
    ? String(props.purchaseOrder.department_id)
    : "",
  perihal_id: props.purchaseOrder.perihal_id
    ? String(props.purchaseOrder.perihal_id)
    : "",
  supplier_id: props.purchaseOrder.supplier_id
    ? String(props.purchaseOrder.supplier_id)
    : "",
  bank_supplier_account_id: props.purchaseOrder.bank_supplier_account_id
    ? String(props.purchaseOrder.bank_supplier_account_id)
    : "",
  no_po: props.purchaseOrder.no_po || "",
  no_invoice: props.purchaseOrder.no_invoice || "",
  harga: props.purchaseOrder.harga || (null as any),
  detail_keperluan: props.purchaseOrder.detail_keperluan || "",
  metode_pembayaran: props.purchaseOrder.metode_pembayaran || "",
  note: props.purchaseOrder.note || props.purchaseOrder.keterangan || "",
  no_giro: props.purchaseOrder.no_giro || "",
  tanggal_giro: props.purchaseOrder.tanggal_giro || "",
  tanggal_cair: props.purchaseOrder.tanggal_cair || "",
  diskon: props.purchaseOrder.diskon || (null as any),
  ppn: props.purchaseOrder.ppn || false,
  pph_id: props.purchaseOrder.pph_id ? [props.purchaseOrder.pph_id] : ([] as any[]),
  termin_id: props.purchaseOrder.termin_id
    ? String(props.purchaseOrder.termin_id)
    : (null as any),
  nominal: props.purchaseOrder.nominal || (null as any),
  keterangan: props.purchaseOrder.keterangan || "",
  // Customer fields for Refund Konsumen
  customer_id: props.purchaseOrder.customer_id
    ? String(props.purchaseOrder.customer_id)
    : "",
  customer_bank_id: props.purchaseOrder.customer_bank_id
    ? String(props.purchaseOrder.customer_bank_id)
    : "",
  credit_card_id: props.purchaseOrder.credit_card_id
    ? String(props.purchaseOrder.credit_card_id)
    : "",
  // Jenis Barang (for Perihal: Permintaan Pembayaran Barang)
  jenis_barang_id: (props.purchaseOrder as any)?.jenis_barang_id
    ? String((props.purchaseOrder as any).jenis_barang_id)
    : "",
  // DP fields
  dp_active: !!(props.purchaseOrder as any)?.dp_active,
  dp_type: ((props.purchaseOrder as any)?.dp_type || 'percent') as 'percent' | 'nominal',
  dp_percent: (props.purchaseOrder as any)?.dp_percent ?? (null as any),
  dp_nominal: (props.purchaseOrder as any)?.dp_nominal ?? (null as any),
});

// Initialize barang list with existing items
const barangList = ref<any[]>(
  Array.isArray(props.purchaseOrder.items)
    ? props.purchaseOrder.items.map((item: any) => ({
        nama: (item && (item.nama ?? item.nama_barang)) ?? "",
        qty: Number(item?.qty ?? 1),
        satuan: item?.satuan ?? "",
        harga: Number(item?.harga ?? 0),
        // Preserve item type to ensure correct PPh base calculation (Jasa vs Barang)
        tipe: (item?.tipe ? String(item.tipe) : undefined) as any,
      }))
    : []
);

const loading = ref(false);
const dokumenFile = ref<File | null>(null);
const errors = ref<{ [key: string]: string }>({});
const barangGridRef = ref();
const showAddPerihalModal = ref(false);
const showAddTerminModal = ref(false);
// Detect selected Perihal name and whether it is a special case
const selectedPerihalName = computed(() => {
  const id = form.value.perihal_id;
  const found = perihalList.value.find((p: any) => String(p.id) === String(id));
  return found ? String(found.nama || "") : "";
});

// Compute selected department name (for HG/Zi&Glo logic)
const selectedDepartmentName = computed(() => {
  const id = form.value.department_id;
  const found = departemenList.value.find((d: any) => String(d.id) === String(id));
  return found ? String(found.name || found.nama || "") : "";
});

// Helper: ensure currently selected termin (from form or PO) stays in options
function ensureSelectedTerminPresent(list: any[]): any[] {
  try {
    const currentId = form.value?.termin_id ? String(form.value.termin_id) : null;
    const fromPO = props.purchaseOrder?.termin
      ? {
          id: props.purchaseOrder.termin.id,
          no_referensi: props.purchaseOrder.termin.no_referensi,
          status: props.purchaseOrder.termin.status,
          department_id: props.purchaseOrder.termin.department_id,
        }
      : null;
    const idToEnsure = currentId || (fromPO ? String(fromPO.id) : null);
    if (!idToEnsure) return list;
    const exists = (list || []).some((t: any) => String(t.id) === String(idToEnsure));
    if (exists) return list;
    if (fromPO && String(fromPO.id) === String(idToEnsure)) {
      const label = fromPO.no_referensi && String(fromPO.no_referensi).trim() !== ''
        ? fromPO.no_referensi
        : `#${fromPO.id}`;
      return [{ ...fromPO, no_referensi: label }, ...(list || [])];
    }
    // Fallback minimal item if we only have the id (ensure non-empty label)
    return [
      {
        id: idToEnsure,
        no_referensi: props.purchaseOrder?.termin?.no_referensi && String(props.purchaseOrder.termin.no_referensi).trim() !== ''
          ? props.purchaseOrder.termin.no_referensi
          : `#${idToEnsure}`,
      },
      ...(list || []),
    ];
  } catch {
    return list;
  }
}

const isSpecialPerihal = computed(() => {
  const nama = selectedPerihalName.value?.toLowerCase();
  return (
    nama === "permintaan pembayaran ongkir" ||
    nama === "permintaan pembayaran refund konsumen"
  );
});

const isRefundKonsumenPerihal = computed(() => {
  const nama = selectedPerihalName.value?.toLowerCase();
  return nama === "permintaan pembayaran refund konsumen";
});

const specialBarangNama = computed(() => {
  const nama = selectedPerihalName.value?.toLowerCase();
  if (nama === "permintaan pembayaran refund konsumen")
    return "Pembayaran Refund Konsumen";
  if (nama === "permintaan pembayaran ongkir") return "Pembayaran Ongkir";
  return "";
});

// When special perihal selected, auto-fill barang list and allow manual harga
watch(
  () => form.value.perihal_id,
  () => {
    if (form.value.tipe_po === "Reguler" && isSpecialPerihal.value) {
      barangList.value = [
        {
          nama: specialBarangNama.value,
          qty: 1,
          satuan: "–",
          harga: Number(form.value.harga || 0),
          // Special perihal items are services for PPh base calculation
          tipe: 'Jasa' as any,
        },
      ];
    } else if (form.value.tipe_po === "Reguler" && !isSpecialPerihal.value) {
      // Only clear barang list if it contains special perihal items
      const hasSpecialItem = barangList.value.some(
        (item) =>
          item.nama === "Pembayaran Refund Konsumen" || item.nama === "Pembayaran Ongkir"
      );
      if (hasSpecialItem) {
        barangList.value = [];
      }
    }
  }
);

// ================= Jenis Barang & Barang options (Edit) =================
const jenisBarangList = ref<any[]>([]);
const barangOptions = ref<any[]>([]);
let jenisBarangSearchTimeout: ReturnType<typeof setTimeout>;
let barangSearchTimeout: ReturnType<typeof setTimeout>;

// Dropdown only for HG/Zi&Glo + Perihal Barang, except when Jenis = Lainnya
const useBarangDropdown = computed(() => {
  const perihalOk = selectedPerihalName.value?.toLowerCase() === 'permintaan pembayaran barang';
  const deptRaw = selectedDepartmentName.value?.toString().toLowerCase() || '';
  const deptNormalized = deptRaw
    .replace(/\u0026/g, '&')
    .replace(/&amp;/g, '&')
    .replace(/[^a-z0-9]/g, '');
  const deptOk = deptNormalized === 'humangreatness' || deptNormalized === 'ziglo';
  if (!(perihalOk && deptOk)) return false;
  const selectedJenis = (jenisBarangList.value || []).find(
    (j: any) => String(j.id) === String(form.value.jenis_barang_id)
  );
  const isJenisLainnya = (selectedJenis?.nama_jenis_barang || '').toLowerCase() === 'lainnya';
  return !isJenisLainnya;
});

// When perihal changes, reset or load jenis/barang options accordingly
watch(
  () => form.value.perihal_id,
  () => {
    if (!useBarangDropdown.value) {
      barangOptions.value = [];
    } else {
      // Load jenis list initially
      searchJenisBarangs('');
      if (form.value.jenis_barang_id) {
        searchBarangs('');
      }
    }
  }
);

// Watch selected jenis to fetch barang options when relevant
watch(
  () => [form.value.jenis_barang_id, selectedPerihalName.value] as const,
  () => {
    if (selectedPerihalName.value?.toLowerCase() === 'permintaan pembayaran barang' && form.value.jenis_barang_id) {
      if (useBarangDropdown.value) {
        searchBarangs('');
      } else {
        barangOptions.value = [];
      }
    } else {
      barangOptions.value = [];
    }
  }
);

// Clear barang grid when Jenis Barang changes (skip initial run)
const jenisBarangInitialized = ref(false);
watch(
  () => form.value.jenis_barang_id,
  () => {
    if (!jenisBarangInitialized.value) {
      jenisBarangInitialized.value = true;
      return;
    }
    barangList.value = [];
  }
);

function searchJenisBarangs(query: string) {
  clearTimeout(jenisBarangSearchTimeout);
  jenisBarangSearchTimeout = setTimeout(async () => {
    try {
      const { data } = await axios.get('/purchase-orders/jenis-barangs', {
        headers: { Accept: 'application/json' },
        params: { search: query, per_page: 100 },
      });
      const list = Array.isArray(data?.data) ? data.data : [];
      // Ensure currently selected jenis (from draft) exists in options
      const selectedId = (form.value?.jenis_barang_id ?? '').toString();
      const exists = list.some((j: any) => j && j.id !== undefined && j.id !== null && j.id.toString() === selectedId);
      let merged = list.slice();
      if (selectedId && !exists) {
        const fromPO = (props.purchaseOrder as any)?.jenis_barang;
        const label = fromPO?.nama_jenis_barang && String(fromPO.nama_jenis_barang).trim() !== ''
          ? fromPO.nama_jenis_barang
          : `#${selectedId}`;
        merged = [{ id: selectedId, nama_jenis_barang: label, singkatan: fromPO?.singkatan }, ...merged];
      }
      jenisBarangList.value = merged;
    } catch {
      jenisBarangList.value = [];
    }
  }, 300);
}

function searchBarangs(query: string) {
  clearTimeout(barangSearchTimeout);
  barangSearchTimeout = setTimeout(async () => {
    if (!form.value.jenis_barang_id) return;
    try {
      const { data } = await axios.get('/purchase-orders/barangs', {
        headers: { Accept: 'application/json' },
        params: { jenis_barang_id: form.value.jenis_barang_id, search: query, per_page: 100 },
      });
      barangOptions.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      barangOptions.value = [];
    }
  }, 300);
}

// Initial load for drafts: ensure Jenis/Barang options are available so the selected Jenis shows up
searchJenisBarangs('');
if (form.value.jenis_barang_id && useBarangDropdown.value) {
  searchBarangs('');
}

// Keep item.harga synced with manual Harga field for special perihal
watch(
  () => form.value.harga,
  (newHarga) => {
    if (
      form.value.tipe_po === "Reguler" &&
      isSpecialPerihal.value &&
      Array.isArray(barangList.value) &&
      barangList.value.length > 0
    ) {
      barangList.value = [
        {
          ...barangList.value[0],
          harga: Number(newHarga || 0),
        },
      ];
    }
  }
);

// Numeric keydown helper for Harga (prevent letters)
function allowNumericKeydown(event: KeyboardEvent) {
  const allowedKeys = [
    "Backspace",
    "Delete",
    "Tab",
    "Enter",
    "Escape",
    "ArrowLeft",
    "ArrowRight",
    "Home",
    "End",
    ",",
    ".",
    "0",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
  ];
  const isCtrlCombo = event.ctrlKey || event.metaKey;
  if (isCtrlCombo) return; // allow copy/paste/select all
  if (!allowedKeys.includes(event.key)) {
    event.preventDefault();
  }
}

// Force re-render of date pickers to prevent display issues
const datePickerKey = ref(0);

// Message panel
const { addSuccess, addError, clearAll } = useMessagePanel();

// Confirmation dialog
const showConfirmDialog = ref(false);
const confirmAction = ref<string>("");

// Watch department/metode untuk load kartu kredit aktif per departemen
watch(
  () => [form.value.department_id, form.value.metode_pembayaran] as const,
  async ([deptId, metode]) => {
    if (metode === "Kredit") {
      // Pertahankan pilihan kartu kredit yang sudah ada saat edit
      const existingId = props.purchaseOrder.credit_card_id
        ? String(props.purchaseOrder.credit_card_id)
        : null;

      // Jangan reset ke null jika sudah ada nilai (dari PO lama)
      if (!selectedCreditCardId.value && existingId) {
        selectedCreditCardId.value = existingId;
      }

      selectedCreditCardBankName.value = selectedCreditCardBankName.value || "";
      creditCardOptions.value = [];

      if (deptId) {
        try {
          const { data } = await axios.get("/credit-cards", {
            headers: { Accept: "application/json" },
            params: { department_id: deptId, status: "active", per_page: 1000 },
          });
          creditCardOptions.value = Array.isArray(data?.data) ? data.data : [];

          // Setelah options ter-load, auto-set pilihan dari PO jika ada
          const targetId = selectedCreditCardId.value || existingId;
          if (targetId) {
            const cc = creditCardOptions.value.find(
              (c: any) => String(c.id) === String(targetId)
            );
            if (cc) {
              selectedCreditCardId.value = String(cc.id);
              // Set nama bank dan no kartu kredit untuk ditampilkan di form
              selectedCreditCardBankName.value = cc.bank?.nama_bank
                ? cc.bank.singkatan
                  ? `${cc.bank.nama_bank} (${cc.bank.singkatan})`
                  : cc.bank.nama_bank
                : "";
              (form.value as any).no_kartu_kredit = cc.no_kartu_kredit || "";
            } else {
              // Fallback pakai data dari PO apabila kartu tidak ada di daftar aktif
              if (props.purchaseOrder.credit_card) {
                // Sisipkan opsi sementara agar dropdown menampilkan pilihan lama
                creditCardOptions.value = [
                  {
                    id: props.purchaseOrder.credit_card.id,
                    nama_pemilik: props.purchaseOrder.credit_card.nama_pemilik,
                    no_kartu_kredit: props.purchaseOrder.credit_card.no_kartu_kredit,
                    bank_id: props.purchaseOrder.credit_card.bank_id,
                    bank: props.purchaseOrder.credit_card.bank,
                  },
                  ...creditCardOptions.value,
                ];
                selectedCreditCardId.value = String(
                  props.purchaseOrder.credit_card.id
                );
                selectedCreditCardBankName.value = props.purchaseOrder.credit_card?.bank?.nama_bank
                  ? props.purchaseOrder.credit_card?.bank?.singkatan
                    ? `${props.purchaseOrder.credit_card.bank.nama_bank} (${props.purchaseOrder.credit_card.bank.singkatan})`
                    : props.purchaseOrder.credit_card.bank.nama_bank
                  : "";
                (form.value as any).no_kartu_kredit =
                  props.purchaseOrder.credit_card?.no_kartu_kredit || "";
              }
            }
          }
        } catch {
          creditCardOptions.value = [];
        }
      } else if (existingId && props.purchaseOrder.credit_card) {
        // Jika belum ada deptId (kasus edge), set tampilan dari data PO
        selectedCreditCardId.value = existingId;
        selectedCreditCardBankName.value = props.purchaseOrder.credit_card?.bank?.nama_bank
          ? props.purchaseOrder.credit_card?.bank?.singkatan
            ? `${props.purchaseOrder.credit_card.bank.nama_bank} (${props.purchaseOrder.credit_card.bank.singkatan})`
            : props.purchaseOrder.credit_card.bank.nama_bank
          : "";
        (form.value as any).no_kartu_kredit =
          props.purchaseOrder.credit_card?.no_kartu_kredit || "";
      }
    }
  },
  { immediate: true }
);

// Watch metode pembayaran untuk set tanggal default saat memilih Cek/Giro
watch(
  () => form.value.metode_pembayaran,
  (newMetode) => {
    if (newMetode === "Cek/Giro") {
      // Set tanggal default ke hari ini jika belum ada nilai
      if (!form.value.tanggal_giro) {
        form.value.tanggal_giro = new Date() as any;
      }
      if (!form.value.tanggal_cair) {
        form.value.tanggal_cair = new Date() as any;
      }
    }
  }
);

// Force re-render of date pickers to prevent display issues
watch(
  () => form.value.tanggal_cair,
  () => {
    // Force re-render of tanggal giro picker when tanggal cair changes
    nextTick(() => {
      datePickerKey.value++;
    });
  }
);

watch(
  () => form.value.tanggal_giro,
  () => {
    // Force re-render of tanggal cair picker when tanggal giro changes
    nextTick(() => {
      datePickerKey.value++;
    });
  }
);

// Watch for PO type changes to load termins when switching to Lainnya
watch(
  () => form.value.tipe_po,
  async (newTipe) => {
    if (newTipe === "Lainnya" && form.value.department_id) {
      try {
        const response = await axios.get("/purchase-orders/termins/by-department", {
          params: {
            department_id: form.value.department_id,
            purchase_order_id: props.purchaseOrder?.id,
          },
        });
        const payload = response?.data;
        const list = Array.isArray(payload)
          ? payload
          : Array.isArray(payload?.data)
          ? payload.data
          : [];
        terminList.value = ensureSelectedTerminPresent(list);
      } catch (error) {
        console.error("Error fetching termins by department:", error);
      }
    }
  }
);

// Load suppliers and termins by department on change
watch(
  () => form.value.department_id,
  async (deptId) => {
    // Clear selection and dependent fields
    form.value.supplier_id = "";
    selectedSupplierBankAccounts.value = [];
    selectedSupplier.value = null;

    // Clear barang list when department changes
    barangList.value = [];

    if (!deptId) {
      supplierList.value = [];
      // Clear termin list if no department selected
      if (form.value.tipe_po === "Lainnya") {
        terminList.value = [];
      }
      return;
    }

    // Load suppliers for the department
    try {
      const { data } = await axios.get("/purchase-orders/suppliers/by-department", {
        params: { department_id: deptId },
      });
      supplierList.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      supplierList.value = [];
    }

    // Load termins for the department if PO type is Lainnya
    if (form.value.tipe_po === "Lainnya") {
      // Preserve existing termin if it belongs to the same department
      const currentTerminDeptId = props.purchaseOrder?.termin?.department_id
        ? String(props.purchaseOrder.termin.department_id)
        : null;
      const newDeptId = String(deptId);
      const shouldKeepCurrentTermin =
        currentTerminDeptId && currentTerminDeptId === newDeptId && form.value.termin_id;
      if (!shouldKeepCurrentTermin) {
        form.value.termin_id = null as any;
      }

      try {
        const response = await axios.get("/purchase-orders/termins/by-department", {
          params: { department_id: deptId, purchase_order_id: props.purchaseOrder?.id },
        });
        const payload = response?.data;
        const list = Array.isArray(payload)
          ? payload
          : Array.isArray(payload?.data)
          ? payload.data
          : [];
        terminList.value = ensureSelectedTerminPresent(list);
      } catch (error) {
        console.error("Error fetching termins by department:", error);
      }
    }
  }
);

function searchSuppliers(query: string) {
  clearTimeout(supplierSearchTimeout);
  supplierSearchTimeout = setTimeout(async () => {
    if (
      !form.value.department_id ||
      (form.value.metode_pembayaran !== "Transfer" && form.value.metode_pembayaran)
    )
      return;
    try {
      const { data } = await axios.get("/purchase-orders/suppliers/by-department", {
        params: { department_id: form.value.department_id, search: query, per_page: 50 },
      });
      supplierList.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      // ignore
    }
  }, 300);
}

// Customer functions for Refund Konsumen
function handleCustomerChange(customerId: string) {
  form.value.customer_id = customerId;
  // Clear customer bank and account fields when customer changes
  form.value.customer_bank_id = "";
}

function handleCustomerBankChange(bankId: string) {
  form.value.customer_bank_id = bankId;
}

function searchCustomers(query: string) {
  clearTimeout(customerSearchTimeout);
  customerSearchTimeout = setTimeout(async () => {
    if (!form.value.department_id) return;
    try {
      const { data } = await axios.get("/purchase-orders/ar-partners", {
        headers: { Accept: "application/json" },
        params: {
          department_id: form.value.department_id,
          search: query,
          limit: 50,
        },
      });
      customerOptions.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      // ignore
    }
  }, 300);
}

// Watch for department changes to load customers for Refund Konsumen
watch(
  () => form.value.department_id,
  () => {
    if (form.value.department_id && isRefundKonsumenPerihal.value) {
      searchCustomers("");
    }
  }
);

// Watch for perihal changes to load customers for Refund Konsumen
watch(
  () => form.value.perihal_id,
  () => {
    if (form.value.department_id && isRefundKonsumenPerihal.value) {
      searchCustomers("");
    }
  }
);

// Auto-select department when only one available
if (!form.value.department_id && (departemenList.value || []).length === 1) {
  form.value.department_id = String(departemenList.value[0].id);
}

// Keep tanggal as Date internally; display uses displayTanggal

// Display read-only tanggal in dd-MM-yyyy
function formatDate(date: string) {
  if (!date) return "";
  return new Date(date).toLocaleDateString("id-ID", {
    day: "2-digit",
    month: "short",
    year: "2-digit",
  });
}

const displayTanggal = computed(() => {
  try {
    return formatDate(form.value.tanggal as any);
  } catch {
    return "";
  }
});

const validTanggalGiro = computed({
  get: () => {
    if (!form.value.tanggal_giro) return null as any;
    try {
      const date = new Date(form.value.tanggal_giro as any);
      return isNaN(date.getTime()) ? null : (date as any);
    } catch {
      return null as any;
    }
  },
  set: (value) => {
    (form.value as any).tanggal_giro = value as any;
  },
});

const validTanggalCair = computed({
  get: () => {
    if (!form.value.tanggal_cair) return null as any;
    try {
      const date = new Date(form.value.tanggal_cair as any);
      return isNaN(date.getTime()) ? null : (date as any);
    } catch {
      return null as any;
    }
  },
  set: (value) => {
    (form.value as any).tanggal_cair = value as any;
  },
});

// Formatted numeric inputs (thousand separators + decimals, no currency symbol)
const displayHarga = computed<string>({
  get: () => {
    // Always render from form.harga to avoid circular dependency with child grandTotal
    return formatCurrency(form.value.harga ?? "");
  },
  set: (val: string) => {
    const parsed = parseCurrency(val);
    form.value.harga = parsed === "" ? null : Number(parsed);
  },
});

// Handler functions for supplier and bank selection
async function handleSupplierChange(supplierId: string) {
  form.value.supplier_id = supplierId;
  form.value.bank_supplier_account_id = "";
  (form.value as any).bank_id = "";
  (form.value as any).nama_rekening = "";
  (form.value as any).no_rekening = "";
  selectedSupplierBankAccounts.value = [];
  selectedSupplier.value = null;

  if (!supplierId) return;

  try {
    const response = await axios.post("/purchase-orders/supplier-bank-accounts", {
      supplier_id: supplierId,
    });

    const { supplier, bank_accounts } = response.data;
    selectedSupplier.value = supplier;
    selectedSupplierBankAccounts.value = bank_accounts;

    // Auto-select bank account if only one bank account
    if (bank_accounts.length === 1) {
      const account = bank_accounts[0];
      form.value.bank_supplier_account_id = String(account.id);
      (form.value as any).bank_id = String(account.bank_id ?? "");
      (form.value as any).nama_rekening = account.nama_rekening || "";
      const bankAbbreviation = account.bank_singkatan || "";
      (form.value as any).no_rekening = account.no_rekening
        ? `${account.no_rekening}${bankAbbreviation ? ` (${bankAbbreviation})` : ""}`
        : "";
    }
  } catch (error) {
    console.error("Error fetching supplier bank accounts:", error);
    addError("Gagal mengambil data rekening supplier");
  }
}

function handleBankSupplierAccountChange(bankSupplierAccountId: string) {
  form.value.bank_supplier_account_id = bankSupplierAccountId;
  (form.value as any).bank_id = "";
  (form.value as any).nama_rekening = "";
  (form.value as any).no_rekening = "";

  if (!bankSupplierAccountId) return;

  const account = selectedSupplierBankAccounts.value.find(
    (acc: any) => String(acc.id) === bankSupplierAccountId
  );

  if (account) {
    (form.value as any).bank_id = String(account.bank_id ?? "");
    (form.value as any).nama_rekening = account.nama_rekening || "";
    const bankAbbreviation = account.bank_singkatan || "";
    (form.value as any).no_rekening = account.no_rekening
      ? `${account.no_rekening}${bankAbbreviation ? ` (${bankAbbreviation})` : ""}`
      : "";
  }
}

function handleBankChange(bankId: string) {
  (form.value as any).bank_id = bankId;
  (form.value as any).nama_rekening = "";
  (form.value as any).no_rekening = "";

  if (!bankId) return;

  const selectedAccount = selectedSupplierBankAccounts.value.find(
    (account: any) => String(account.bank_id) === bankId
  );

  if (selectedAccount) {
    (form.value as any).nama_rekening = selectedAccount.nama_rekening || "";
    const bankAbbreviation = selectedAccount.bank_singkatan || "";
    (form.value as any).no_rekening = selectedAccount.no_rekening
      ? `${selectedAccount.no_rekening}${bankAbbreviation ? ` (${bankAbbreviation})` : ""}`
      : "";
  }
}

function handleSelectCreditCard(creditCardId: string) {
  selectedCreditCardId.value = creditCardId;
  selectedCreditCardBankName.value = "";
  (form.value as any).no_kartu_kredit = "";
  if (!creditCardId) return;
  const cc = creditCardOptions.value.find(
    (c: any) => String(c.id) === String(creditCardId)
  );
  if (cc) {
    selectedCreditCardBankName.value = cc.bank?.nama_bank
      ? cc.bank.singkatan
        ? `${cc.bank.nama_bank} (${cc.bank.singkatan})`
        : cc.bank.nama_bank
      : "";
    (form.value as any).no_kartu_kredit = cc.no_kartu_kredit || "";
  }
}

function searchCreditCards(query: string) {
  clearTimeout(creditCardSearchTimeout);
  creditCardSearchTimeout = setTimeout(async () => {
    if (!form.value.department_id || form.value.metode_pembayaran !== "Kredit") return;
    try {
      const { data } = await axios.get("/credit-cards", {
        headers: { Accept: "application/json" },
        params: {
          department_id: form.value.department_id,
          status: "active",
          search: query,
          per_page: 50,
        },
      });
      creditCardOptions.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      // ignore
    }
  }, 300);
}

// Auto-update harga field when grand total changes in barang grid (for Reguler and Lainnya PO)
watch(
  () => barangGridRef.value?.grandTotal,
  (newGrandTotal) => {
    if (
      (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") &&
      typeof newGrandTotal === "number" &&
      !isNaN(newGrandTotal)
    ) {
      form.value.harga = newGrandTotal;
    }
  },
  { immediate: false }
);

// Prefill supplier bank accounts on initial load for Transfer
onMounted(async () => {
  try {
    if (
      form.value.metode_pembayaran === "Transfer" &&
      form.value.supplier_id
    ) {
      const response = await axios.post("/purchase-orders/supplier-bank-accounts", {
        supplier_id: form.value.supplier_id,
      });
      const { supplier, bank_accounts } = response.data || {};
      selectedSupplier.value = supplier || null;
      selectedSupplierBankAccounts.value = Array.isArray(bank_accounts)
        ? bank_accounts
        : [];

      // Ensure existing selection is reflected and display fields are filled
      const selectedId = form.value.bank_supplier_account_id;
      if (selectedId) {
        const acc = selectedSupplierBankAccounts.value.find(
          (a: any) => String(a.id) === String(selectedId)
        );
        if (acc) {
          // keep model id; set display helpers
          (form.value as any).bank_id = String(acc.bank_id ?? "");
          (form.value as any).nama_rekening = acc.nama_rekening || "";
          const bankAbbreviation = acc.bank_singkatan || "";
          (form.value as any).no_rekening = acc.no_rekening
            ? `${acc.no_rekening}${bankAbbreviation ? ` (${bankAbbreviation})` : ""}`
            : "";
        } else if (props.purchaseOrder?.bank_supplier_account) {
          // Fallback from PO relation when account not found in fetched list
          const bsa = props.purchaseOrder.bank_supplier_account;
          (form.value as any).bank_id = String(bsa.bank_id ?? "");
          (form.value as any).nama_rekening = bsa.nama_rekening || "";
          const bankAbbreviation = bsa.bank_singkatan || bsa.bank?.singkatan || "";
          (form.value as any).no_rekening = bsa.no_rekening
            ? `${bsa.no_rekening}${bankAbbreviation ? ` (${bankAbbreviation})` : ""}`
            : "";
          // Also inject the existing account into options so the select can display the label
          const existsInOptions = selectedSupplierBankAccounts.value.some(
            (a: any) => String(a.id) === String(bsa.id)
          );
          if (!existsInOptions) {
            selectedSupplierBankAccounts.value = [
              {
                id: bsa.id,
                bank_id: bsa.bank_id,
                nama_rekening: bsa.nama_rekening,
                no_rekening: bsa.no_rekening,
                bank_singkatan: bsa.bank_singkatan || bsa.bank?.singkatan || "",
              },
              ...selectedSupplierBankAccounts.value,
            ];
          }
        }
      }
    }

    // Prefetch termins for Lainnya and preserve selection
    if (form.value.tipe_po === "Lainnya" && form.value.department_id) {
      try {
        const { data } = await axios.get("/purchase-orders/termins/by-department", {
          params: { department_id: form.value.department_id },
        });
        const list = Array.isArray(data)
          ? data
          : Array.isArray(data?.data)
          ? data.data
          : [];
        terminList.value = ensureSelectedTerminPresent(list);
        // Ensure selected termin exists in the options so the label shows
        if (form.value.termin_id) {
          const exists = terminList.value.some(
            (t: any) => String(t.id) === String(form.value.termin_id)
          );
          if (!exists && props.purchaseOrder?.termin) {
            terminList.value = [
              {
                id: props.purchaseOrder.termin.id,
                no_referensi: props.purchaseOrder.termin.no_referensi,
                status: props.purchaseOrder.termin.status,
                department_id: props.purchaseOrder.termin.department_id,
              },
              ...terminList.value,
            ];
          }
        }
      } catch {
        // ignore termin prefetch errors
      }
    }
  } catch {
    // ignore prefill errors
  }
});

// Also watch for changes in barang list, diskon, ppn, and pph that affect grand total
watch(
  [
    () => barangList.value,
    () => form.value.diskon,
    () => form.value.ppn,
    () => form.value.pph_id,
  ],
  () => {
    if (
      (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") &&
      barangGridRef.value?.grandTotal
    ) {
      // Small delay to ensure the grid has recalculated the grand total
      setTimeout(() => {
        if (barangGridRef.value?.grandTotal) {
          form.value.harga = barangGridRef.value.grandTotal;
        }
      }, 100);
    }
  },
  { deep: true }
);

// Immediate update when barang list changes (for better responsiveness)
watch(
  () => barangList.value.length,
  () => {
    if (
      (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") &&
      barangGridRef.value?.grandTotal
    ) {
      // Update immediately when items are added/removed
      form.value.harga = barangGridRef.value.grandTotal;
    }
  }
);

// Handle case when barang list is empty
watch(
  () => barangList.value.length === 0,
  (isEmpty) => {
    if (isEmpty && form.value.tipe_po === "Reguler") {
      // When barang list is empty, set harga to 0 or base amount
      form.value.harga = 0;
    }
  }
);

// Watch for PO type changes to update harga field accordingly
watch(
  () => form.value.tipe_po,
  (newTipe) => {
    if (newTipe === "Reguler") {
      // Update harga when switching to Reguler PO
      // Use a longer delay to ensure the barang grid is fully rendered
      setTimeout(() => {
        if (
          barangGridRef.value?.grandTotal &&
          typeof barangGridRef.value.grandTotal === "number"
        ) {
          form.value.harga = barangGridRef.value.grandTotal;
        } else {
          // If no grand total available yet, set to 0
          form.value.harga = 0;
        }
      }, 300);
    } else if (newTipe === "Lainnya") {
      // Update harga from grand total when switching to Lainnya PO
      setTimeout(() => {
        if (
          barangGridRef.value?.grandTotal &&
          typeof barangGridRef.value.grandTotal === "number"
        ) {
          form.value.harga = barangGridRef.value.grandTotal;
        } else {
          form.value.harga = 0;
        }
      }, 300);
      // Clear special perihal items when switching away from Reguler
      const hasSpecialItem = barangList.value.some(
        (item) =>
          item.nama === "Pembayaran Refund Konsumen" || item.nama === "Pembayaran Ongkir"
      );
      if (hasSpecialItem) {
        barangList.value = [];
      }
    } else if (newTipe === "Anggaran") {
      // Clear harga when switching to Anggaran PO
      form.value.harga = null as any;
      // Clear special perihal items when switching away from Reguler
      const hasSpecialItem = barangList.value.some(
        (item) =>
          item.nama === "Pembayaran Refund Konsumen" || item.nama === "Pembayaran Ongkir"
      );
      if (hasSpecialItem) {
        barangList.value = [];
      }
    }
  }
);

// Watch for barangGridRef to become available and initialize harga
watch(
  () => barangGridRef.value,
  (newRef) => {
    if (newRef && form.value.tipe_po === "Reguler") {
      // When the grid component becomes available, initialize harga
      setTimeout(() => {
        if (newRef.grandTotal && typeof newRef.grandTotal === "number") {
          form.value.harga = newRef.grandTotal;
        }
      }, 100);
    }
  },
  { immediate: false }
);



// Add missing functions
async function searchTermins(query: string) {
  clearTimeout(terminSearchTimeout);
  terminSearchTimeout = setTimeout(async () => {
    const requestId = ++latestTerminRequestId;
    const isEmptyQuery = !query || String(query).trim() === "";
    try {
      // If department is selected, search within that department
      if (form.value.department_id && form.value.tipe_po === "Lainnya") {
        const { data } = await axios.get("/purchase-orders/termins/by-department", {
          params: {
            department_id: form.value.department_id,
            search: query,
            purchase_order_id: props.purchaseOrder?.id,
          },
        });
        if (requestId !== latestTerminRequestId) return;
        const list = Array.isArray(data)
          ? data
          : Array.isArray((data as any)?.data)
          ? (data as any).data
          : null;
        if (Array.isArray(list)) {
          // Only overwrite with empty results if user typed a non-empty query
          if (!isEmptyQuery || list.length > 0) {
            terminList.value = ensureSelectedTerminPresent(list);
          }
        }
      } else {
        // Fallback to general search if no department selected
        const { data } = await axios.get("/purchase-orders/termins/search", {
          params: { search: query, per_page: 20 },
        });
        if (requestId !== latestTerminRequestId) return;
        const list = Array.isArray(data)
          ? data
          : Array.isArray((data as any)?.data)
          ? (data as any).data
          : null;
        if (Array.isArray(list)) {
          if (!isEmptyQuery || list.length > 0) {
            terminList.value = ensureSelectedTerminPresent(list);
          }
        }
      }
    } catch (error) {
      if (requestId !== latestTerminRequestId) return;
      console.error("Failed to search termins:", error);
    }
  }, 300);
}

function handleTerminChange(terminId: string) {
  form.value.termin_id = terminId;
  // Clear barang list when termin changes
  barangList.value = [];
}

function handleTerminCreated(newItem: any) {
  if (newItem && newItem.id) {
    terminList.value.push({
      id: newItem.id,
      no_referensi: newItem.no_referensi,
      jumlah_termin: newItem.jumlah_termin,
    });
    form.value.termin_id = String(newItem.id);
    handleTerminChange(String(newItem.id));
  }
}

function handlePerihalCreated(perihal: any) {
  // Add the newly created perihal to the list
  perihalList.value.push({
    id: perihal.id,
    nama: perihal.nama,
  });
  // Set the selected perihal to the newly created one
  form.value.perihal_id = String(perihal.id);
  showAddPerihalModal.value = false;
}

function onAddPph(pphBaru: any) {
  try {
    const exists = (pphList.value || []).some((p: any) => String(p.id) === String(pphBaru.id));
    if (!exists) {
      pphList.value = [...(pphList.value || []), pphBaru];
    }
    form.value.pph_id = [pphBaru.id];
  } catch {
    // no-op
  }
}

function goBack() {
  router.visit("/purchase-orders");
}

function validateForm() {
  errors.value = {};
  let isValid = true;

  // PPH validation removed

  if (form.value.tipe_po === "Reguler") {
    // Validasi field wajib untuk tipe Reguler
    if (!form.value.department_id) {
      errors.value.department_id = "Departemen wajib dipilih";
      isValid = false;
    }
    if (!form.value.perihal_id) {
      errors.value.perihal_id = "Perihal wajib dipilih";
      isValid = false;
    }

    // Check if it's Refund Konsumen perihal
    const isRefundKonsumen =
      selectedPerihalName.value?.toLowerCase() ===
      "permintaan pembayaran refund konsumen";

    if (form.value.metode_pembayaran === "Transfer") {
      if (isRefundKonsumen) {
        // For Refund Konsumen, validate customer fields
        if (!form.value.customer_id) {
          errors.value.customer_id = "Customer wajib dipilih untuk metode Transfer";
          isValid = false;
        }
        if (!form.value.customer_bank_id) {
          errors.value.customer_bank_id = "Nama Bank wajib dipilih";
          isValid = false;
        }
        // Customer bank fields validation removed - handled by backend
      } else {
        // For other perihals, validate supplier fields
        if (!form.value.supplier_id) {
          errors.value.supplier_id = "Supplier wajib dipilih untuk metode Transfer";
          isValid = false;
        }
      }
    }
    // No Invoice is optional
    if (!form.value.harga) {
      // Auto-populate harga from grand total if available
      if (
        barangGridRef.value?.grandTotal &&
        typeof barangGridRef.value.grandTotal === "number"
      ) {
        form.value.harga = barangGridRef.value.grandTotal;
      } else {
        errors.value.harga = "Harga wajib diisi";
        isValid = false;
      }
    }
    if (!form.value.metode_pembayaran) {
      errors.value.metode_pembayaran = "Metode pembayaran wajib dipilih";
      isValid = false;
    }

    // Validasi field berdasarkan metode pembayaran
    if (form.value.metode_pembayaran === "Cek/Giro") {
      if (!form.value.no_giro) {
        errors.value.no_giro = "No. Cek/Giro wajib diisi";
        isValid = false;
      }
      if (!form.value.tanggal_giro) {
        errors.value.tanggal_giro = "Tanggal Giro wajib diisi";
        isValid = false;
      }
      if (!form.value.tanggal_cair) {
        errors.value.tanggal_cair = "Tanggal Cair wajib diisi";
        isValid = false;
      }
    } else if (
      form.value.metode_pembayaran === "Transfer" ||
      !form.value.metode_pembayaran
    ) {
      // Validasi field bank untuk Transfer atau ketika belum memilih metode pembayaran
      if (isRefundKonsumen) {
        // For Refund Konsumen, validate customer bank fields (already validated above)
        // No additional validation needed here
      } else {
        // Supplier bank fields validation removed - handled by bank_supplier_account_id
      }
    } else if (form.value.metode_pembayaran === "Kredit") {
      if (!selectedCreditCardId.value) {
        errors.value.credit_card_id = "Kartu Kredit wajib dipilih";
        isValid = false;
      }
    }
  } else if (form.value.tipe_po === "Lainnya") {
    if (!form.value.department_id) {
      errors.value.department_id = "Departemen wajib dipilih";
      isValid = false;
    }
    if (!form.value.perihal_id) {
      errors.value.perihal_id = "Perihal wajib dipilih";
      isValid = false;
    }
    if (!form.value.termin_id) {
      errors.value.termin_id = "No Ref Termin wajib dipilih";
      isValid = false;
    }
  }

  if (!barangList.value.length) {
    errors.value.barang = "Minimal 1 barang harus diisi";
    isValid = false;
  }

  // Validate file upload for staff toko & kepala toko (hanya untuk tipe Reguler)
  if (isStaffToko.value && form.value.tipe_po === "Reguler") {
    // Check if there's either an existing document or a new file being uploaded
    if (!dokumenFile.value && !props.purchaseOrder.dokumen) {
      errors.value.dokumen = "Draft Invoice harus diupload";
      isValid = false;
    } else if (dokumenFile.value) {
      // Validate file type
      const allowedTypes = ["image/jpeg", "image/jpg", "image/png", "application/pdf"];
      const fileType = dokumenFile.value.type;
      if (!allowedTypes.includes(fileType)) {
        errors.value.dokumen =
          "Format file tidak didukung. Hanya file JPG, JPEG, PNG, dan PDF yang diperbolehkan";
        isValid = false;
      }

      // Validate file size (50MB)
      const maxSize = 50 * 1024 * 1024; // 50MB in bytes
      if (dokumenFile.value.size > maxSize) {
        errors.value.dokumen = "Ukuran file terlalu besar. Maksimal 50 MB";
        isValid = false;
      }
    }
  }

  return isValid;
}

// Validasi khusus untuk draft - hanya Departemen, Tanggal, dan Tipe
function validateDraftForm() {
  errors.value = {};
  let isValid = true;

  // Validasi Departemen
  if (!form.value.department_id) {
    errors.value.department_id = "Departemen wajib dipilih";
    isValid = false;
  }

  // Validasi Tipe PO
  if (!form.value.tipe_po) {
    errors.value.tipe_po = "Tipe PO wajib dipilih";
    isValid = false;
  }

  // Semua field lain tidak wajib untuk draft
  return isValid;
}

async function onSaveDraft() {
  clearAll();

  if (!validateDraftForm()) {
    return;
  }

  loading.value = true;

  // Reset diskon jika tidak aktif
  if (
    !form.value.diskon ||
    form.value.diskon === null ||
    form.value.diskon === undefined
  ) {
    form.value.diskon = 0;
  }

  try {
    const formData = new FormData();
    const fieldsToFormat = ["tanggal_giro", "tanggal_cair"]; // Jangan format/kirim tanggal saat draft

    const fieldsToSubmit: any = {
      tipe_po: form.value.tipe_po,
      department_id: form.value.department_id,
      perihal_id: form.value.perihal_id,
      // Jenis Barang (optional)
      jenis_barang_id: form.value.jenis_barang_id || null,
      supplier_id: form.value.supplier_id,
      no_invoice: form.value.no_invoice,
      harga: form.value.harga,
      detail_keperluan: form.value.detail_keperluan,
      metode_pembayaran: form.value.metode_pembayaran,
      note: form.value.note,
      keterangan: form.value.keterangan,
      diskon: form.value.diskon,
      ppn: form.value.ppn,
      termin_id: form.value.termin_id,
      // Include optional fields for draft persistence
      nominal: form.value.nominal,
      termin: (form.value as any).termin,
    };

    // Normalize and include pph_id (use first element if array)
    if (form.value.pph_id) {
      const pphId = Array.isArray(form.value.pph_id) && form.value.pph_id.length > 0
        ? form.value.pph_id[0]
        : form.value.pph_id;
      if (pphId) {
        fieldsToSubmit.pph_id = pphId;
      }
    }

    // Add conditional fields
    if (form.value.metode_pembayaran === "Transfer" || !form.value.metode_pembayaran) {
      const isRefundKonsumen =
        selectedPerihalName.value?.toLowerCase() ===
        "permintaan pembayaran refund konsumen";

      if (isRefundKonsumen) {
        fieldsToSubmit.customer_id = form.value.customer_id;
        fieldsToSubmit.customer_bank_id = form.value.customer_bank_id;
        // Customer bank fields removed - handled by backend
      } else {
        fieldsToSubmit.bank_supplier_account_id = form.value.bank_supplier_account_id;
        // Bank fields removed - handled by bank_supplier_account_id
      }
    }

    if (form.value.metode_pembayaran === "Cek/Giro") {
      fieldsToSubmit.no_giro = form.value.no_giro;
      fieldsToSubmit.tanggal_giro = form.value.tanggal_giro;
      fieldsToSubmit.tanggal_cair = form.value.tanggal_cair;
    }

    if (form.value.metode_pembayaran === "Kredit") {
      fieldsToSubmit.credit_card_id = selectedCreditCardId.value;
    }

    const nullableKeysDraft = [
      "note",
      "detail_keperluan",
      "keterangan",
      "no_invoice",
      "no_po",
    ];

    Object.entries(fieldsToSubmit).forEach(([k, v]) => {
      let value: any = v as any;
      if (fieldsToFormat.includes(k)) {
        value = formatDateForSubmit(value);
      }

      if (k === "ppn") {
        value = value ? 1 : 0;
      }

      if (
        value !== null &&
        value !== undefined &&
        (value !== "" || nullableKeysDraft.includes(k))
      ) {
        formData.append(k, value);
      }
    });

    formData.append("status", "Draft");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);
    formData.append("_method", "PUT");

    // DP fields (persist only)
    formData.append('dp_active', form.value.dp_active ? '1' : '0');
    formData.append('dp_type', form.value.dp_type || '');
    if (form.value.dp_type === 'percent' && form.value.dp_percent != null) {
      formData.append('dp_percent', String(form.value.dp_percent));
    }
    if (form.value.dp_type === 'nominal' && form.value.dp_nominal != null) {
      formData.append('dp_nominal', String(form.value.dp_nominal));
    }

    await axios.post(`/purchase-orders/${props.purchaseOrder.id}`, formData, {
      headers: { "Content-Type": "multipart/form-data", Accept: "application/json" },
      timeout: 30000,
    });

    // CRITICAL: Set loading to false BEFORE navigation
    loading.value = false;

    // Show success message
    addSuccess("Draft PO berhasil disimpan!");

    // Use nextTick to ensure DOM updates are processed
    await nextTick();

    // Navigate after a small delay
    setTimeout(() => {
      router.visit("/purchase-orders", {
        preserveState: false,
        preserveScroll: false,
      });
    }, 100);
  } catch (e: any) {
    console.error("Edit draft error:", e);

    // CRITICAL: Set loading to false on error too
    loading.value = false;

    if (e?.response?.data?.errors) {
      errors.value = e.response.data.errors;

      if (e?.response?.data?.message) {
        addError(e.response.data.message);
      }

      if (e?.response?.data?.error_messages) {
        Object.values(e.response.data.error_messages).forEach((message: any) => {
          addError(message);
        });
      }
    } else if (e?.response?.status === 419 || e?.response?.status === 401) {
      addError("Session expired. Halaman akan di-refresh.");
      setTimeout(() => {
        window.location.reload();
      }, 2000);
      return;
    } else if (e?.code === "ECONNABORTED") {
      addError("Request timeout. Silakan coba lagi.");
      return;
    } else {
      addError(e?.response?.data?.message || "Gagal simpan draft.");
    }
  }
}

function showSubmitConfirmation() {
  confirmAction.value = "submit";
  showConfirmDialog.value = true;
}

async function onSubmit() {
  clearAll();

  if (!validateForm()) {
    showConfirmDialog.value = false;
    addError("Validasi form gagal. Silakan periksa kembali data yang diisi.");
    return;
  }

  loading.value = true;

  // Reset diskon jika tidak aktif
  if (
    !form.value.diskon ||
    form.value.diskon === null ||
    form.value.diskon === undefined
  ) {
    form.value.diskon = 0;
  }

  try {
    const formData = new FormData();
    const fieldsToFormat = ["tanggal", "tanggal_giro", "tanggal_cair"];

    const fieldsToSubmit: any = {
      tipe_po: form.value.tipe_po,
      tanggal: form.value.tanggal,
      department_id: form.value.department_id,
      perihal_id: form.value.perihal_id,
      // Jenis Barang (optional)
      jenis_barang_id: form.value.jenis_barang_id || null,
      supplier_id: form.value.supplier_id,
      no_invoice: form.value.no_invoice,
      harga: form.value.harga,
      detail_keperluan: form.value.detail_keperluan,
      metode_pembayaran: form.value.metode_pembayaran,
      note: form.value.note,
      keterangan: form.value.keterangan,
      diskon: form.value.diskon,
      ppn: form.value.ppn,
      termin_id: form.value.termin_id,
    };

    // Add conditional fields
    if (form.value.metode_pembayaran === "Transfer" || !form.value.metode_pembayaran) {
      const isRefundKonsumen =
        selectedPerihalName.value?.toLowerCase() ===
        "permintaan pembayaran refund konsumen";

      if (isRefundKonsumen) {
        fieldsToSubmit.customer_id = form.value.customer_id;
        fieldsToSubmit.customer_bank_id = form.value.customer_bank_id;
        // Customer bank fields removed - handled by backend
      } else {
        fieldsToSubmit.bank_supplier_account_id = form.value.bank_supplier_account_id;
        // Bank fields removed - handled by bank_supplier_account_id
      }
    }

    if (form.value.metode_pembayaran === "Cek/Giro") {
      fieldsToSubmit.no_giro = form.value.no_giro;
      fieldsToSubmit.tanggal_giro = form.value.tanggal_giro;
      fieldsToSubmit.tanggal_cair = form.value.tanggal_cair;
    }

    if (form.value.metode_pembayaran === "Kredit") {
      fieldsToSubmit.credit_card_id = selectedCreditCardId.value;
    }

    const nullableKeysSubmit = [
      "note",
      "detail_keperluan",
      "keterangan",
      "no_invoice",
      "no_po",
    ];

    Object.entries(fieldsToSubmit).forEach(([k, v]) => {
      let value: any = v as any;
      if (fieldsToFormat.includes(k)) {
        value = formatDateForSubmit(value);
      }

      if (k === "ppn") {
        value = value ? 1 : 0;
      }

      if (
        value !== null &&
        value !== undefined &&
        (value !== "" || nullableKeysSubmit.includes(k))
      ) {
        formData.append(k, value);
      }
    });

    const isKredit = form.value.metode_pembayaran === "Kredit";
    formData.append("status", isKredit ? "Approved" : "In Progress");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);
    formData.append("_method", "PUT");
    await axios.post(`/purchase-orders/${props.purchaseOrder.id}`, formData, {
      headers: { "Content-Type": "multipart/form-data", Accept: "application/json" },
      timeout: 30000,
    });

    // CRITICAL: Set loading to false BEFORE navigation
    loading.value = false;

    // Show success message
    if (isKredit) {
      addSuccess("PO Kredit berhasil disetujui!");
    } else if (props.purchaseOrder.status === "Rejected") {
      addSuccess("PO berhasil diperbaiki dan dikirim ulang!");
    } else {
      addSuccess("PO berhasil dikirim!");
    }

    // Use nextTick to ensure DOM updates are processed
    await nextTick();

    // Navigate after a small delay
    setTimeout(() => {
      router.visit("/purchase-orders", {
        preserveState: false,
        preserveScroll: false,
      });
    }, 100);
  } catch (e: any) {
    console.error("Edit submit error:", e);

    // CRITICAL: Set loading to false on error too
    loading.value = false;

    if (e?.response?.data?.errors) {
      errors.value = e.response.data.errors;

      if (e?.response?.data?.message) {
        addError(e.response.data.message);
      }

      if (e?.response?.data?.error_messages) {
        Object.values(e.response.data.error_messages).forEach((message: any) => {
          addError(message);
        });
      }
    } else if (e?.response?.status === 419 || e?.response?.status === 401) {
      addError("Session expired. Halaman akan di-refresh.");
      setTimeout(() => {
        window.location.reload();
      }, 2000);
      return;
    } else if (e?.code === "ECONNABORTED") {
      addError("Request timeout. Silakan coba lagi.");
      return;
    } else {
      addError(e?.response?.data?.message || "Gagal update PO.");
    }

    // Close confirmation dialog on error
    showConfirmDialog.value = false;
  }
}

function formatDateForSubmit(value: any) {
  if (!value) return "";
  const date = value instanceof Date ? value : new Date(value);
  if (isNaN(date.getTime())) return "";
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

onMounted(async () => {
  // Ensure supplier list is filtered by initial department
  if (form.value.department_id) {
    try {
      const { data } = await axios.get("/purchase-orders/suppliers/by-department", {
        params: { department_id: form.value.department_id },
      });
      supplierList.value = Array.isArray(data?.data) ? data.data : [];
    } catch {
      supplierList.value = [];
    }
  }

  // Initialize calculated harga based on existing items
  if (barangList.value.length > 0) {
    // Trigger recalculation
    const subtotal = barangList.value.reduce((sum, item) => {
      return sum + Number(item.qty || 0) * Number(item.harga || 0);
    }, 0);

    // Set initial harga if not already set
    if (!form.value.harga && subtotal > 0) {
      form.value.harga = subtotal;
    }
  }

  // Initialize harga field with grand total if it's a Reguler or Lainnya PO
  if (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") {
    // Small delay to ensure the barang grid component is fully mounted
    setTimeout(() => {
      if (
        barangGridRef.value?.grandTotal &&
        typeof barangGridRef.value.grandTotal === "number"
      ) {
        form.value.harga = barangGridRef.value.grandTotal;
      }
    }, 200);
  }

  // Load supplier bank accounts if supplier is already selected
  if (form.value.supplier_id) {
    try {
      const response = await axios.post("/purchase-orders/supplier-bank-accounts", {
        supplier_id: form.value.supplier_id,
      });

      const { supplier, bank_accounts } = response.data;
      selectedSupplier.value = supplier;
      selectedSupplierBankAccounts.value = bank_accounts;
    } catch (error) {
      console.error("Error loading supplier bank accounts:", error);
    }
  }

  // Initialize termin list if PO type is Lainnya and department is selected
  if (form.value.tipe_po === "Lainnya" && form.value.department_id) {
    try {
      const response = await axios.get("/purchase-orders/termins/by-department", {
        params: { department_id: form.value.department_id },
      });
      const payload = response?.data;
      terminList.value = Array.isArray(payload)
        ? payload
        : Array.isArray(payload?.data)
        ? payload.data
        : [];
    } catch (error) {
      console.error("Error fetching termins by department:", error);
    }
  }
});

// Cleanup on component unmount to prevent memory leaks
onUnmounted(() => {
  // Simple cleanup without conditions for testing
});
</script>

<style scoped>
.floating-input {
  position: relative;
}

.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.3s ease-in-out;
}

.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #9ca3af;
  transition: all 0.3s ease-in-out;
  pointer-events: none;
  transform-origin: left top;
  background-color: white;
  padding: 0 0.25rem;
  z-index: 1;
}

/* When input is focused or has value - label goes to border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Special handling for select - check if it has selected value */
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Textarea specific styles */
.floating-input-field:is(textarea) {
  resize: vertical;
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.floating-input-field:is(textarea):focus ~ .floating-label,
.floating-input-field:is(textarea):not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
}

/* Hover effects */
.floating-input:hover .floating-input-field {
  border-color: #9ca3af;
}

.floating-input:hover .floating-input-field:focus {
  border-color: #1f9254;
}

/* Make sure the label background covers the border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}

/* Disabled field styling */
.floating-input-field:disabled {
  background-color: #f3f4f6;
  color: #6b7280;
  cursor: not-allowed;
  opacity: 0.7;
}

.floating-input-field:disabled ~ .floating-label {
  color: #9ca3af;
}
</style>
