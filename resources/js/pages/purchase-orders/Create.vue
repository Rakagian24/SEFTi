<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            Create new Purchase Order
          </div>
        </div>
      </div>

      <PurchaseOrderForm
        v-model:form="form"
        v-model:dokumenFile="dokumenFile"
        v-model:selectedCreditCardId="selectedCreditCardId"
        v-model:selectedCreditCardBankName="selectedCreditCardBankName"
        :errors="errors"
        :purchaseOrder="{ dokumen: null }"
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
        @searchJenisBarangs="searchJenisBarangs"
        @allowNumericKeydown="allowNumericKeydown"
      />

      <!-- Grid/List Barang - Outside the form to prevent submission conflicts -->
      <PurchaseOrderBarangGrid
        ref="barangGridRef"
        v-model:items="barangList"
        v-model:diskon="form.diskon"
        v-model:ppn="form.ppn"
        v-model:pph="form.pph_id"
        :pphList="pphList"
        :nominal="isSpecialPerihal ? form.harga : undefined"
        :form="form"
        :selected-perihal-name="selectedPerihalName"
        :use-barang-dropdown="useBarangDropdown"
        :selected-jenis-barang-id="form.jenis_barang_id as any"
        :barang-options="barangOptions"
        @add-pph="onAddPph"
        @search-barangs="searchBarangs"
      />
      <div v-if="errors.barang" class="text-red-500 text-xs mt-1">
        Form ini wajib di isi
      </div>

      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="showSubmitConfirmation"
          :disabled="loading || showConfirmDialog"
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
          Kirim
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="onSaveDraft"
          :disabled="loading || showConfirmDialog"
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
          :disabled="loading || showConfirmDialog"
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
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch, watchEffect, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrderBarangGrid from "../../components/purchase-orders/PurchaseOrderBarangGrid.vue";
import PurchaseOrderForm from "../../components/purchase-orders/PurchaseOrderForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
// Removed: CustomSelect & FileUpload were used inside the old inline form
import PerihalQuickAddModal from "@/components/perihals/PerihalQuickAddModal.vue";
import TerminQuickAddModal from "@/components/termins/TerminQuickAddModal.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { CreditCard } from "lucide-vue-next";
import axios from "axios";
import AppLayout from "@/layouts/AppLayout.vue";
// Removed: Datepicker was used inside the old inline form
import { format } from "date-fns";

import { useMessagePanel } from "@/composables/useMessagePanel";
import { usePermissions } from "@/composables/usePermissions";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Purchase Order", href: "/purchase-orders" },
  { label: "Create" },
];

// Master data from props (provided by Inertia controller)
const props = defineProps<{
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

// Message panel
const { addSuccess, addError, clearAll } = useMessagePanel();
const terminList = ref<any[]>(Array.isArray(props.termins) ? props.termins : []);

// Supplier bank accounts data
const selectedSupplierBankAccounts = ref<any[]>([]);
const selectedSupplier = ref<any>(null);
// Credit card list by department
const creditCardOptions = ref<any[]>([]);
const selectedCreditCardId = ref<string | null>(null);
const selectedCreditCardBankName = ref<string>("");
let creditCardSearchTimeout: ReturnType<typeof setTimeout>;

// Customer data for Refund Konsumen
const customerOptions = ref<any[]>([]);
const bankList = ref<any[]>(Array.isArray(props.banks) ? props.banks : []);
// Transform PPH data for grid (tarif in decimal)
const pphList = ref(
  (Array.isArray(props.pphs) ? props.pphs : []).map((pph: any) => ({
    id: pph.id,
    kode: pph.kode_pph,
    nama: pph.nama_pph,
    tarif: pph.tarif_pph ? pph.tarif_pph / 100 : 0,
  }))
);
// Defensive: normalize lists to arrays to avoid runtime .map errors when props become non-arrays
watchEffect(() => {
  if (!Array.isArray(departemenList.value)) departemenList.value = [] as any[];
  if (!Array.isArray(perihalList.value)) perihalList.value = [] as any[];
  if (!Array.isArray(supplierList.value)) supplierList.value = [] as any[];
  if (!Array.isArray(terminList.value)) terminList.value = [] as any[];
  if (!Array.isArray(selectedSupplierBankAccounts.value))
    selectedSupplierBankAccounts.value = [] as any[];
  if (!Array.isArray(creditCardOptions.value)) creditCardOptions.value = [] as any[];
  if (!Array.isArray(customerOptions.value)) customerOptions.value = [] as any[];
  if (!Array.isArray(bankList.value)) bankList.value = [] as any[];
});
let customerSearchTimeout: ReturnType<typeof setTimeout>;

// Use permissions composable to detect user role
const { hasRole } = usePermissions();
const isStaffToko = computed(
  () =>
    hasRole("Staff Toko") ||
    hasRole("Staff Digital Marketing") ||
    hasRole("Kepala Toko") ||
    hasRole("Admin")
);

const form = ref({
  tipe_po: "Reguler",
  tanggal: new Date() as any, // Set ke tanggal saat ini
  department_id: "",
  perihal_id: "",
  supplier_id: "",
  bank_supplier_account_id: "",
  no_invoice: "",
  harga: null as any,
  detail_keperluan: "",
  metode_pembayaran: "",
  note: "",
  no_giro: "",
  tanggal_giro: new Date() as any, // Set ke tanggal saat ini
  tanggal_cair: new Date() as any, // Set ke tanggal saat ini
  diskon: null as any,
  ppn: false,
  pph_id: [] as any[],
  termin: null as any,
  termin_id: null as any,
  nominal: null as any,
  keterangan: "",
  // Customer fields for Refund Konsumen
  customer_id: "",
  customer_bank_id: "",
  // Additional fields for form submission
  dokumen: null as any,
  // Jenis Barang (for Perihal: Permintaan Pembayaran Barang)
  jenis_barang_id: "",
} as any);

// Watch diskon and pph_id from child grid, force reset to 0/[] if uncheck
watch(
  () => form.value.diskon,
  (val) => {
    if (!val || val === null || val === undefined) {
      form.value.diskon = 0;
    }
  }
);
watch(
  () => form.value.diskon,
  () => {}
);

// Core reactive state used across template/watchers should be declared early
const barangList = ref<any[]>([]);
const loading = ref(false);
const dokumenFile = ref<File | null>(null);
const errors = ref<{ [key: string]: string }>({});
const barangGridRef = ref();
const showAddPerihalModal = ref(false);
const showAddTerminModal = ref(false);
// Confirmation dialog
const showConfirmDialog = ref(false);
const confirmAction = ref<string>("");
// Detect selected Perihal name and whether it is a special case
const selectedPerihalName = computed(() => {
  const id = form.value.perihal_id;
  const found = perihalList.value.find((p: any) => String(p.id) === String(id));
  return found ? String(found.nama || "") : "";
});

const selectedDepartmentName = computed(() => {
  const id = form.value.department_id;
  const found = departemenList.value.find((d: any) => String(d.id) === String(id));
  return found ? String(found.name || found.nama || "") : "";
});

// Jenis Barang & Barang options for Reguler with Perihal 'Permintaan Pembayaran Barang'
const jenisBarangList = ref<any[]>([]);
const barangOptions = ref<any[]>([]);
let jenisBarangSearchTimeout: ReturnType<typeof setTimeout>;
let barangSearchTimeout: ReturnType<typeof setTimeout>;

const useBarangDropdown = computed(() => {
  const perihalOk = selectedPerihalName.value?.toLowerCase() === 'permintaan pembayaran barang';
  const dept = selectedDepartmentName.value?.toLowerCase();
  const deptOk = dept === 'human greatness' || dept === 'zi&glo' || dept === 'zi\u0026glo';
  if (!(perihalOk && deptOk)) return false;
  const selectedJenis = (jenisBarangList.value || []).find(
    (j: any) => String(j.id) === String(form.value.jenis_barang_id)
  );
  const isJenisLainnya = (selectedJenis?.nama_jenis_barang || '').toLowerCase() === 'lainnya';
  return !isJenisLainnya;
});

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
      // Always set default single item as specified
      barangList.value = [
        {
          nama: specialBarangNama.value,
          qty: 1,
          satuan: "–",
          harga: Number(form.value.harga || 0),
        },
      ];
    } else if (form.value.tipe_po === "Reguler" && !isSpecialPerihal.value) {
      // Clear barang list when switching away from special perihal
      barangList.value = [];
    }
    // Reset Jenis/Barang options when perihal changes
    if (!useBarangDropdown.value) {
      form.value.jenis_barang_id = '' as any;
      barangOptions.value = [];
    } else {
      // Load jenis list initially
      searchJenisBarangs('');
      // If already selected jenis, refresh barang options
      if (form.value.jenis_barang_id) {
        searchBarangs('');
      }
    }
  }
);

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

watch(
  () => barangGridRef.value?.grandTotal,
  (newGrandTotal) => {
    // Cegah loop: hanya update jika tipe Reguler atau Lainnya, grandTotal valid, dan berbeda dengan harga form
    if (
      (form.value.tipe_po === "Reguler" || form.value.tipe_po === "Lainnya") &&
      typeof newGrandTotal === "number" &&
      !isNaN(newGrandTotal) &&
      newGrandTotal > 0 &&
      form.value.harga !== newGrandTotal
    ) {
      form.value.harga = newGrandTotal;
    }
  },
  { immediate: false }
);

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

// Watch for PO type changes to update harga field accordingly
watch(
  () => form.value.tipe_po,
  async (newTipe) => {
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

      // Load termins for the selected department if available
      if (form.value.department_id) {
        try {
          const response = await axios.get("/purchase-orders/termins/by-department", {
            params: { department_id: form.value.department_id },
          });
          if (response.data && response.data.success) {
            terminList.value = response.data.data || [];
          }
        } catch (error) {
          console.error("Error fetching termins by department:", error);
          // Fallback to filtering from props
          const filteredTermins = props.termins.filter(
            (termin: any) => termin.department_id == form.value.department_id
          );
          terminList.value = filteredTermins;
        }
      } else {
        terminList.value = [];
      }
    } else if (newTipe === "Anggaran") {
      // Clear harga when switching to Anggaran PO
      form.value.harga = null;
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

// Load suppliers and termins by department on change
watch(
  () => form.value.department_id,
  async (deptId) => {
    // Clear selection and dependent fields
    form.value.supplier_id = "";
    form.value.bank_id = "";
    form.value.nama_rekening = "";
    form.value.no_rekening = "";
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
      // Clear selected termin when department changes
      form.value.termin_id = null;

      try {
        const response = await axios.get("/purchase-orders/termins/by-department", {
          params: { department_id: deptId },
        });
        if (response.data && response.data.success) {
          terminList.value = response.data.data || [];
        }
      } catch (error) {
        console.error("Error fetching termins by department:", error);
        addError("Gagal mengambil data termin untuk departemen yang dipilih");
        // Fallback to filtering from props
        const filteredTermins = props.termins.filter(
          (termin: any) => termin.department_id == deptId
        );
        terminList.value = filteredTermins;
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

// Watch department for Kredit method to load credit cards
watch(
  () => [form.value.department_id, form.value.metode_pembayaran] as const,
  async ([deptId, metode]) => {
    if (metode === "Kredit") {
      selectedCreditCardId.value = null;
      form.value.no_kartu_kredit = "";
      creditCardOptions.value = [];
      if (deptId) {
        try {
          const { data } = await axios.get("/credit-cards", {
            headers: { Accept: "application/json" },
            params: { department_id: deptId, status: "active", per_page: 1000 },
          });
          creditCardOptions.value = Array.isArray(data?.data) ? data.data : [];
        } catch {
          creditCardOptions.value = [];
        }
      }
    }
  },
  { immediate: true }
);

// Auto-select department when only one available
if (!form.value.department_id && (departemenList.value || []).length === 1) {
  form.value.department_id = String(departemenList.value[0].id);
}

// Initialize termin list based on selected department and PO type
if (form.value.tipe_po === "Lainnya") {
  if (form.value.department_id) {
    // Filter termin list by department on initial load
    const filteredTermins = props.termins.filter(
      (termin: any) => termin.department_id == form.value.department_id
    );
    terminList.value = filteredTermins;
  } else {
    // If no department selected but PO type is Lainnya, show empty list
    terminList.value = [];
  }
}

// Initialize harga field with grand total if it's a Reguler PO
onMounted(async () => {
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

  // Initialize termin list if PO type is Lainnya and department is selected
  if (form.value.tipe_po === "Lainnya" && form.value.department_id) {
    try {
      const response = await axios.get("/purchase-orders/termins/by-department", {
        params: { department_id: form.value.department_id },
      });
      if (response.data && response.data.success) {
        terminList.value = response.data.data || [];
      }
    } catch (error) {
      console.error("Error fetching termins by department:", error);
      // Fallback to filtering from props
      const filteredTermins = props.termins.filter(
        (termin: any) => termin.department_id == form.value.department_id
      );
      terminList.value = filteredTermins;
    }
  }
});

// Watch selected jenis to fetch barang options when relevant
watch(
  () => [form.value.jenis_barang_id, selectedPerihalName.value] as const,
  () => {
    if (selectedPerihalName.value?.toLowerCase() === 'permintaan pembayaran barang' && form.value.jenis_barang_id) {
      if (useBarangDropdown.value) {
        searchBarangs('');
      } else {
        // When dropdown is disabled (e.g., Jenis Barang = Lainnya), clear options
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
    // Clear items on change
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
      jenisBarangList.value = Array.isArray(data?.data) ? data.data : [];
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

// Keep tanggal as Date internally; display uses displayTanggal

// Display read-only tanggal in dd-MM-yyyy
const displayTanggal = computed(() => {
  try {
    return format(new Date(form.value.tanggal as any), "dd-MM-yyyy");
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
    form.value.tanggal_giro = value as any;
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
    form.value.tanggal_cair = value as any;
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
  form.value.bank_id = "";
  form.value.nama_rekening = "";
  form.value.no_rekening = "";
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
      form.value.bank_id = String(account.bank_id);
      form.value.nama_rekening = account.nama_rekening;
      // Format: no_rekening (singkatan)
      const bankAbbreviation = account.bank_singkatan || "";
      form.value.no_rekening = account.no_rekening
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
  form.value.bank_id = "";
  form.value.nama_rekening = "";
  form.value.no_rekening = "";

  if (!bankSupplierAccountId) return;

  const account = selectedSupplierBankAccounts.value.find(
    (acc: any) => String(acc.id) === bankSupplierAccountId
  );

  if (account) {
    form.value.bank_id = String(account.bank_id);
    form.value.nama_rekening = account.nama_rekening;
    // Format: no_rekening (singkatan)
    const bankAbbreviation = account.bank_singkatan || "";
    form.value.no_rekening = account.no_rekening
      ? `${account.no_rekening}${bankAbbreviation ? ` (${bankAbbreviation})` : ""}`
      : "";
  }
}

function handleBankChange(bankId: string) {
  form.value.bank_id = bankId;
  form.value.nama_rekening = "";
  form.value.no_rekening = "";

  if (!bankId) return;

  const selectedAccount = selectedSupplierBankAccounts.value.find(
    (account: any) => String(account.bank_id) === bankId
  );

  if (selectedAccount) {
    form.value.nama_rekening = selectedAccount.nama_rekening;
    // Format: no_rekening (singkatan)
    const bankAbbreviation = selectedAccount.bank_singkatan || "";
    form.value.no_rekening = selectedAccount.no_rekening
      ? `${selectedAccount.no_rekening}${
          bankAbbreviation ? ` (${bankAbbreviation})` : ""
        }`
      : "";
  }
}

function handleSelectCreditCard(creditCardId: string) {
  selectedCreditCardId.value = creditCardId;
  form.value.no_kartu_kredit = "";
  form.value.bank_id = "";
  selectedCreditCardBankName.value = "";
  if (!creditCardId) return;
  const cc = creditCardOptions.value.find(
    (c: any) => String(c.id) === String(creditCardId)
  );
  if (cc) {
    form.value.no_kartu_kredit = cc.no_kartu_kredit || "";
    form.value.bank_id = cc.bank_id ? String(cc.bank_id) : "";
    selectedCreditCardBankName.value = cc.bank?.nama_bank
      ? cc.bank.singkatan
        ? `${cc.bank.nama_bank} (${cc.bank.singkatan})`
        : cc.bank.nama_bank
      : "";
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

// Customer functions for Refund Konsumen
function handleCustomerChange(customerId: string) {
  form.value.customer_id = customerId;
  // Clear customer bank and account fields when customer changes
  form.value.customer_bank_id = "";
  form.value.customer_nama_rekening = "";
  form.value.customer_no_rekening = "";
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

// Removed auto-sync from cicilan to nominal; cicilan is a standalone manual input

function onAddPph(pphBaru: any) {
  try {
    const exists = (pphList.value || []).some((p: any) => String(p.id) === String(pphBaru.id));
    if (!exists) {
      pphList.value = [...(pphList.value || []), pphBaru];
    }
    // Select the newly added PPh
    form.value.pph_id = [pphBaru.id];
  } catch {
    // no-op
  }
}
function goBack() {
  router.visit("/purchase-orders");
}

function handlePerihalCreated(newItem: any) {
  if (newItem && newItem.id) {
    perihalList.value.push({
      id: newItem.id,
      nama: newItem.nama,
      status: newItem.status,
    });
    form.value.perihal_id = String(newItem.id);
  }
}

function handleTerminCreated(newItem: any) {
  if (newItem && newItem.id) {
    terminList.value.push({
      id: newItem.id,
      no_referensi: newItem.no_referensi,
      jumlah_termin: newItem.jumlah_termin,
    });
    // Set selected termin to the newly created one
    form.value.termin_id = String(newItem.id);
    // Immediately fetch and refresh termin info and barang list
    handleTerminChange(String(newItem.id));
  }
}

function handleTerminChange(terminId: string) {
  form.value.termin_id = terminId;
  // Clear barang list when termin changes
  barangList.value = [];
}

// Search termins (debounced) similar to BankMasuk customer search
let terminSearchTimeout: ReturnType<typeof setTimeout>;
function searchTermins(query: string) {
  clearTimeout(terminSearchTimeout);
  terminSearchTimeout = setTimeout(async () => {
    try {
      // If department is selected, search within that department
      if (form.value.department_id && form.value.tipe_po === "Lainnya") {
        const { data } = await axios.get("/purchase-orders/termins/by-department", {
          params: {
            department_id: form.value.department_id,
            search: query,
          },
        });
        if (data && data.success) {
          terminList.value = data.data || [];
        }
      } else {
        // Fallback to general search if no department selected
        const { data } = await axios.get("/purchase-orders/termins/search", {
          params: { search: query, per_page: 20 },
        });
        if (data && data.success) {
          terminList.value = data.data || [];
        }
      }
    } catch (e) {
      console.error("Error searching termins:", e);
    }
  }, 300);
}

function validateForm() {
  errors.value = {};
  let isValid = true;

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
        if (!form.value.customer_nama_rekening) {
          errors.value.customer_nama_rekening = "Nama Rekening wajib diisi";
          isValid = false;
        }
        if (!form.value.customer_no_rekening) {
          errors.value.customer_no_rekening = "No. Rekening wajib diisi";
          isValid = false;
        }
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
        // For other perihals, validate supplier bank fields
        if (!form.value.bank_id) {
          errors.value.bank_id = "Nama Rekening wajib dipilih";
          isValid = false;
        }
        if (!form.value.nama_rekening) {
          errors.value.nama_rekening = "Nama Rekening wajib diisi";
          isValid = false;
        }
        if (!form.value.no_rekening) {
          errors.value.no_rekening = "No. Rekening/VA wajib diisi";
          isValid = false;
        }
      }
    } else if (form.value.metode_pembayaran === "Kredit") {
      if (!form.value.no_kartu_kredit) {
        errors.value.no_kartu_kredit = "No. Kartu Kredit wajib diisi";
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
    if (!dokumenFile.value) {
      errors.value.dokumen = "Draft Invoice harus diupload";
      isValid = false;
    } else {
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

  try {
    // Prepare minimal payload for draft
    const formData = new FormData();

    // Required fields only
    formData.append("tipe_po", form.value.tipe_po);
    formData.append("department_id", form.value.department_id);
    formData.append("status", "Draft");

    // Optional fields - only send if filled
    // Jangan kirim tanggal saat draft

    if (form.value.perihal_id) {
      formData.append("perihal_id", form.value.perihal_id);
    }

    // Jenis Barang (optional)
    if (form.value.jenis_barang_id) {
      formData.append("jenis_barang_id", String(form.value.jenis_barang_id));
    }

    if (form.value.supplier_id) {
      formData.append("supplier_id", form.value.supplier_id);
    }

    if (form.value.bank_supplier_account_id) {
      formData.append("bank_supplier_account_id", form.value.bank_supplier_account_id);
    }

    if (form.value.metode_pembayaran) {
      formData.append("metode_pembayaran", form.value.metode_pembayaran);
    }

    if (form.value.no_invoice) {
      formData.append("no_invoice", form.value.no_invoice);
    }

    if (form.value.harga) {
      formData.append("harga", String(form.value.harga));
    }

    if (form.value.keterangan || form.value.note) {
      formData.append("keterangan", form.value.note || form.value.keterangan);
    }

    // Optional pricing and tax fields for draft
    if (form.value.diskon !== null && form.value.diskon !== undefined) {
      formData.append("diskon", String(form.value.diskon || 0));
    }
    if (form.value.ppn !== null && form.value.ppn !== undefined) {
      formData.append("ppn", form.value.ppn ? "1" : "0");
    }
    if (form.value.pph_id) {
      const pphId = Array.isArray(form.value.pph_id) && form.value.pph_id.length > 0
        ? form.value.pph_id[0]
        : form.value.pph_id;
      if (pphId) {
        formData.append("pph_id", String(pphId));
      }
    }

    // Termin-related fields for tipe "Lainnya"
    if (form.value.tipe_po === "Lainnya") {
      if (form.value.termin_id) {
        formData.append("termin_id", String(form.value.termin_id));
      }
      if (form.value.nominal !== null && form.value.nominal !== undefined) {
        formData.append("nominal", String(form.value.nominal));
      }
      if (form.value.termin !== null && form.value.termin !== undefined) {
        formData.append("termin", String(form.value.termin));
      }
    }

    // Send barang as JSON string (backend will decode it)
    if (barangList.value && barangList.value.length > 0) {
      formData.append("barang", JSON.stringify(barangList.value));
    } else {
      formData.append("barang", JSON.stringify([]));
    }

    // Add dokumen if exists
    if (dokumenFile.value) {
      formData.append("dokumen", dokumenFile.value);
    }

    // Use axios for better error handling
    const response = await axios.post("/purchase-orders", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
      timeout: 30000, // 30 second timeout
    });

    if (response.data) {
      // Clear draft storage
      if (barangGridRef.value?.clearDraftStorage) {
        barangGridRef.value.clearDraftStorage();
      }

      addSuccess("Draft berhasil disimpan");

      // Navigate to index
      router.visit("/purchase-orders");
    }
  } catch (error: any) {
    console.error("Error saving draft:", error);

    if (error.response?.data?.errors) {
      errors.value = error.response.data.errors;
      addError("Validasi gagal. Silakan periksa kembali data yang diisi.");
    } else if (error.code === "ECONNABORTED") {
      addError("Request timeout. Silakan coba lagi.");
    } else {
      addError("Terjadi kesalahan saat menyimpan draft.");
    }
  } finally {
    loading.value = false;
  }
}

function showSubmitConfirmation() {
  confirmAction.value = "submit";
  showConfirmDialog.value = true;
  try { console.log('[PO] showSubmitConfirmation -> dialog open'); } catch {}
}

function onSubmit() {
  try { console.log('[PO] onSubmit -> start'); } catch {}
  clearAll();

  if (!validateForm()) {
    showConfirmDialog.value = false;
    addError("Validasi form gagal. Silakan periksa kembali data yang diisi.");
    return;
  }
  // Close the confirm dialog immediately to avoid being stuck under overlay
  showConfirmDialog.value = false;
  loading.value = true;

  // Reset diskon dan pph_id jika tidak aktif
  if (
    !form.value.diskon ||
    form.value.diskon === null ||
    form.value.diskon === undefined
  ) {
    form.value.diskon = 0;
  }
  if (
    !form.value.pph_id ||
    (Array.isArray(form.value.pph_id) && form.value.pph_id.length === 0)
  ) {
    form.value.pph_id = [];
  }

  // Prepare payload (same pattern as memo pembayaran)
  const isKredit = form.value.metode_pembayaran === "Kredit";

  const payload: any = {
    tipe_po: form.value.tipe_po,
    tanggal: formatDateForSubmit(form.value.tanggal),
    department_id: form.value.department_id,
    perihal_id: form.value.perihal_id,
    // Jenis Barang (optional)
    jenis_barang_id: form.value.jenis_barang_id || null,
    supplier_id: form.value.supplier_id,
    no_invoice: form.value.no_invoice,
    harga: form.value.harga,
    detail_keperluan: form.value.detail_keperluan,
    metode_pembayaran: form.value.metode_pembayaran,
    keterangan: form.value.note || form.value.keterangan,
    diskon: form.value.diskon,
    ppn: form.value.ppn ? 1 : 0,
    pph_id:
      Array.isArray(form.value.pph_id) && form.value.pph_id.length > 0
        ? (() => {
            const pphId = form.value.pph_id[0];
            // Pastikan kita mengirim ID yang valid (number), bukan kode (string)
            return typeof pphId === "number" ? pphId : null;
          })()
        : null,
    termin: form.value.termin,
    status: isKredit ? "Approved" : "In Progress",
    barang: JSON.stringify(barangList.value),
  };

  // Add conditional fields
  if (form.value.metode_pembayaran === "Transfer" || !form.value.metode_pembayaran) {
    const isRefundKonsumen =
      selectedPerihalName.value?.toLowerCase() ===
      "permintaan pembayaran refund konsumen";

    if (isRefundKonsumen) {
      payload.customer_id = form.value.customer_id;
      payload.customer_bank_id = form.value.customer_bank_id;
      payload.customer_nama_rekening = form.value.customer_nama_rekening;
      payload.customer_no_rekening = form.value.customer_no_rekening;
    } else {
      payload.bank_supplier_account_id = form.value.bank_supplier_account_id;
    }
  }

  if (form.value.metode_pembayaran === "Cek/Giro") {
    payload.no_giro = form.value.no_giro;
    payload.tanggal_giro = formatDateForSubmit(form.value.tanggal_giro);
    payload.tanggal_cair = formatDateForSubmit(form.value.tanggal_cair);
  }

  if (form.value.metode_pembayaran === "Kredit") {
    payload.credit_card_id = selectedCreditCardId.value;
  }

  if (form.value.tipe_po === "Lainnya") {
    payload.termin_id = form.value.termin_id;
  }

  // Add dokumen if exists
  if (dokumenFile.value) {
    payload.dokumen = dokumenFile.value;
  }

  // Use Inertia router like memo pembayaran
  try {
    router.post("/purchase-orders", payload, {
      onStart: () => {
        // Request started
      },
      onProgress: () => {
        // Request in progress
      },
      onSuccess: () => {
        if (barangGridRef.value?.clearDraftStorage) {
          barangGridRef.value.clearDraftStorage();
        }
        showConfirmDialog.value = false;
        loading.value = false;
        // Force navigate to index to avoid being stuck on modal when backend returns JSON
        router.visit("/purchase-orders");
      },
      onError: (err) => {
        loading.value = false;
        showConfirmDialog.value = false;

        // Handle validation errors
        if (err && typeof err === "object") {
          errors.value = err as any;
          addError("Validasi gagal. Silakan periksa kembali data yang diisi.");
        } else {
          addError("Terjadi kesalahan saat menyimpan data.");
        }
      },
      onFinish: () => {
        loading.value = false;
        showConfirmDialog.value = false;
      },
    });
  } catch {
    loading.value = false;
    showConfirmDialog.value = false;
    addError("Terjadi kesalahan saat mengirim request.");
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

// Cleanup on component unmount to prevent memory leaks
onUnmounted(() => {
  // Clear all timeouts to prevent memory leaks
  if (supplierSearchTimeout) clearTimeout(supplierSearchTimeout);
  if (creditCardSearchTimeout) clearTimeout(creditCardSearchTimeout);
  if (customerSearchTimeout) clearTimeout(customerSearchTimeout);
  if (terminSearchTimeout) clearTimeout(terminSearchTimeout);

  // Reset loading state
  loading.value = false;
});

// Force re-render of date pickers to prevent display issues
const datePickerKey = ref(0);

watch(
  () => form.value.tanggal_cair,
  () => {
    // Force re-render of both date pickers when any date changes
    nextTick(() => {
      datePickerKey.value++;
    });
  }
);

watch(
  () => form.value.tanggal_giro,
  () => {
    // Force re-render of both date pickers when any date changes
    nextTick(() => {
      datePickerKey.value++;
    });
  }
);
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
