<template>
  <div class="bg-white rounded-lg shadow p-6">
    <!-- Rejection Reason Alert -->
    <div
      v-if="editData?.status === 'Rejected' && editData?.rejection_reason"
      class="mb-6"
    >
      <div class="bg-red-50 border border-red-200 rounded-lg p-4">
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
              <p>{{ editData.rejection_reason }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <form @submit.prevent="onSubmit" novalidate class="space-y-6">
      <!-- Row 1: No. Memo Pembayaran | Metode Bayar -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- No. Memo Pembayaran -->
        <div class="floating-input">
          <div
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ form.no_mb || "Akan di-generate otomatis" }}
          </div>
          <label class="floating-label">No. Memo Pembayaran</label>
        </div>

        <!-- Metode Bayar -->
        <div class="floating-input">
          <CustomSelect
            v-model="form.metode_pembayaran"
            :options="metodePembayaranOptions"
            placeholder="Pilih metode pembayaran"
            :error="errors.metode_pembayaran"
            @change="onMetodePembayaranChange"
          >
            <template #label>Metode Bayar<span class="text-red-500">*</span></template>
          </CustomSelect>
          <div v-if="errors.metode_pembayaran" class="text-red-500 text-xs mt-1">
            {{ errors.metode_pembayaran }}
          </div>
        </div>
      </div>

      <!-- Row 2: Purchase Order | Dynamic Right Column -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Purchase Order -->
        <div class="floating-input">
          <div class="flex gap-2">
            <div class="flex-1">
              <CustomSelect
                :key="selectedPurchaseOrders.map((po) => po.id).join(',')"
                v-model="form.purchase_order_id"
                :options="purchaseOrderOptions"
                :placeholder="getPurchaseOrderPlaceholder()"
                :error="errors.purchase_order_id"
                :searchable="true"
                :disabled="!canSelectPurchaseOrder()"
                @search="searchPurchaseOrders"
                @update:modelValue="() => onPurchaseOrderChange()"
              >
                <template #label
                  >Purchase Order<span class="text-red-500">*</span></template
                >
              </CustomSelect>
            </div>
            <button
              type="button"
              @click="openPurchaseOrderModal"
              :disabled="!canSelectPurchaseOrder()"
              class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              +
            </button>
          </div>
          <p class="text-sm text-gray-500 mt-1">
            {{ getPurchaseOrderHelperText() }}
          </p>
          <div v-if="errors.purchase_order_id" class="text-red-500 text-xs mt-1">
            {{ errors.purchase_order_id }}
          </div>
        </div>

        <!-- Dynamic Right Column based on Metode Bayar -->
        <div>
          <!-- Transfer: Nama Rekening (Supplier) -->
          <div v-if="form.metode_pembayaran === 'Transfer'">
            <CustomSelect
              :model-value="selectedSupplierId ?? ''"
              @update:modelValue="(val) => handleSupplierChange(val as any)"
              :options="supplierOptions"
              :searchable="true"
              @search="searchSuppliers"
              placeholder="Pilih Supplier"
              :error="errors.supplier_id"
            >
              <template #label
                >Nama Rekening (Supplier)<span class="text-red-500">*</span></template
              >
            </CustomSelect>
            <div v-if="errors.supplier_id" class="text-red-500 text-xs mt-1">
              {{ errors.supplier_id }}
            </div>
          </div>

          <!-- Cek/Giro: No. Cek/Giro -->
          <div v-else-if="form.metode_pembayaran === 'Cek/Giro'">
            <CustomSelect
              :model-value="form.no_giro ?? ''"
              @update:modelValue="(val) => handleGiroChange(val as any)"
              :options="giroOptions"
              placeholder="Pilih No. Cek/Giro dari PO"
              :error="errors.no_giro"
              :searchable="true"
              @search="searchGiroNumbers"
            >
              <template #label>No. Cek/Giro<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.no_giro" class="text-red-500 text-xs mt-1">
              {{ errors.no_giro }}
            </div>
            <div
              v-if="form.metode_pembayaran === 'Cek/Giro' && giroOptions.length === 0"
              class="text-blue-600 text-xs mt-1"
            >
              Tidak ada data Cek/Giro dari Purchase Order yang disetujui
            </div>
          </div>

          <!-- Kredit: Nama Rekening (Kredit) -->
          <div v-else-if="form.metode_pembayaran === 'Kredit'">
            <CustomSelect
              :model-value="selectedCreditCardId ?? ''"
              @update:modelValue="(val) => handleSelectCreditCard(val as any)"
              :options="creditCardOptions.map((cc: any) => ({ label: cc.nama_pemilik, value: String(cc.id) }))"
              :searchable="true"
              @search="searchCreditCards"
              placeholder="Pilih Nama Rekening (Kredit)"
            >
              <template #label
                >Nama Rekening (Kredit)<span class="text-red-500">*</span></template
              >
            </CustomSelect>
            <div v-if="errors.no_kartu_kredit" class="text-red-500 text-xs mt-1">
              {{ errors.no_kartu_kredit }}
            </div>
          </div>
        </div>
      </div>

      <!-- Row 3: Tanggal | Dynamic Right Column -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tanggal -->
        <div class="floating-input">
          <div
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ displayTanggal || "-" }}
          </div>
          <label class="floating-label">Tanggal</label>
        </div>

        <!-- Dynamic Right Column -->
        <div>
          <!-- Transfer: Nama Bank -->
          <div v-if="form.metode_pembayaran === 'Transfer'" class="floating-input">
            <CustomSelect
              :model-value="form.bank_id"
              @update:modelValue="(val) => handleBankChange(val as any)"
              :options="selectedSupplierBankAccounts.map((a: any) => ({ label: `${a.bank_name} (${a.bank_singkatan})`, value: String(a.bank_id) }))"
              :disabled="selectedSupplierBankAccounts.length === 0"
              placeholder="Pilih Bank"
              :error="errors.bank_id"
            >
              <template #label>Nama Bank<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.bank_id" class="text-red-500 text-xs mt-1">
              {{ errors.bank_id }}
            </div>
          </div>

          <!-- Cek/Giro: Tanggal Giro -->
          <div v-else-if="form.metode_pembayaran === 'Cek/Giro'" class="floating-input">
            <label class="block text-xs font-light text-gray-700 mb-1">
              Tanggal Giro<span class="text-red-500">*</span>
            </label>
            <Datepicker
              v-model="form.tanggal_giro"
              :input-class="['floating-input-field', form.tanggal_giro ? 'filled' : '']"
              placeholder=" "
              :format="'dd MMMM yyyy'"
              :enable-time-picker="false"
              :auto-apply="true"
              :clearable="true"
              id="tanggal_giro"
            />
            <div v-if="errors.tanggal_giro" class="text-red-500 text-xs mt-1">
              {{ errors.tanggal_giro }}
            </div>
          </div>

          <!-- Kredit: Nama Bank -->
          <div v-else-if="form.metode_pembayaran === 'Kredit'" class="floating-input">
            <div
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
            >
              {{ selectedCreditCardBankName || "-" }}
            </div>
            <label class="floating-label">Nama Bank</label>
          </div>
        </div>
      </div>

      <!-- Row 4: Perihal | Dynamic Right Column -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Row 5: Nominal -->
        <div class="floating-input">
          <input
            v-model="form.nominal"
            type="text"
            id="nominal"
            class="floating-input-field"
            :class="{ 'border-red-500': errors.nominal }"
            placeholder=" "
            @input="formatNominal"
          />
          <label for="nominal" class="floating-label">
            Nominal<span class="text-red-500">*</span>
          </label>
          <div v-if="errors.nominal" class="text-red-500 text-xs mt-1">
            {{ errors.nominal }}
          </div>
          <div
            v-if="selectedPurchaseOrders.length > 0 && !errors.nominal"
            class="text-blue-600 text-xs mt-1"
          >
            Total Purchase Order: {{ formatCurrency(getSelectedPurchaseOrdersTotal()) }}
          </div>
        </div>

        <!-- Dynamic Right Column -->
        <div>
          <!-- Transfer: No. Rekening -->
          <div v-if="form.metode_pembayaran === 'Transfer'" class="floating-input">
            <input
              v-model="form.no_rekening"
              type="text"
              id="no_rekening"
              class="floating-input-field"
              :class="{ 'border-red-500': errors.no_rekening }"
              placeholder=" "
            />
            <label for="no_rekening" class="floating-label">
              No. Rekening<span class="text-red-500">*</span>
            </label>
            <div v-if="errors.no_rekening" class="text-red-500 text-xs mt-1">
              {{ errors.no_rekening }}
            </div>
          </div>

          <!-- Cek/Giro: Tanggal Cair -->
          <div v-else-if="form.metode_pembayaran === 'Cek/Giro'" class="floating-input">
            <label class="block text-xs font-light text-gray-700 mb-1">
              Tanggal Cair<span class="text-red-500">*</span>
            </label>
            <Datepicker
              v-model="form.tanggal_cair"
              :input-class="['floating-input-field', form.tanggal_cair ? 'filled' : '']"
              placeholder=" "
              :format="'dd MMMM yyyy'"
              :enable-time-picker="false"
              :auto-apply="true"
              :clearable="true"
              id="tanggal_cair"
            />
            <div v-if="errors.tanggal_cair" class="text-red-500 text-xs mt-1">
              {{ errors.tanggal_cair }}
            </div>
          </div>

          <!-- Kredit: No Kartu Kredit -->
          <div v-else-if="form.metode_pembayaran === 'Kredit'" class="floating-input">
            <div
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
            >
              {{ form.no_kartu_kredit || "-" }}
            </div>
            <label class="floating-label">No Kartu Kredit</label>
          </div>
        </div>
      </div>

      <!-- Row 6: Note -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="floating-input">
          <textarea
            v-model="form.note"
            id="note"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label for="note" class="floating-label">Note</label>
        </div>
      </div>

      <!-- Selected Purchase Orders Display (full width) -->
      <div v-if="selectedPurchaseOrders.length > 0" class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700">
            Purchase Order yang Dipilih:
          </label>
          <div class="text-sm text-gray-600">
            Total: {{ formatCurrency(getSelectedPurchaseOrdersTotal()) }}
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
          <div
            v-for="po in selectedPurchaseOrders"
            :key="po.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
          >
            <div class="flex-1">
              <div class="flex items-center gap-2">
                <span class="font-medium">{{ po.no_po }}</span>
                <span
                  v-if="(po as any).status === 'Approved'"
                  class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full"
                >
                  Approved
                </span>
              </div>
              <div class="text-sm text-gray-500">{{ po.perihal?.nama || "" }}</div>
              <div class="text-xs text-gray-400 mt-1">
                Metode: {{ po.metode_pembayaran }} • Nominal:
                {{ formatCurrency(po.total || 0) }}
              </div>
            </div>
            <button
              type="button"
              @click="removePurchaseOrder(po.id)"
              class="text-red-600 hover:text-red-800 ml-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          type="submit"
          :disabled="isSubmitting"
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
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
          {{ editData?.status === "Rejected" ? "Kirim Ulang" : "Kirim" }}
        </button>

        <button
          type="button"
          @click="saveDraft"
          :disabled="isSubmitting"
          class="px-6 py-2 text-sm font-medium text-white bg-gray-600 border border-transparent rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
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
          <span v-if="isSubmitting">Menyimpan...</span>
          <span v-else>Simpan Draft</span>
        </button>

        <button
          type="button"
          @click="$emit('close')"
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
    </form>

    <!-- Purchase Order Selection (Custom Overlay Component) -->
    <PurchaseOrderSelection
      v-model:open="showPurchaseOrderModal"
      :purchase-orders="availablePurchaseOrders"
      :selected-ids="selectedPurchaseOrders.map((po) => po.id)"
      :no-results-message="getNoResultsMessage()"
      @search="onPurchaseOrderSearch"
      @add="addPurchaseOrder"
      @add-many="addManyPurchaseOrders"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { router } from "@inertiajs/vue3";
import CustomSelect from "../ui/CustomSelect.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import PurchaseOrderSelection from "./PurchaseOrderSelection.vue";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";
import axios from "axios";
import { format } from "date-fns";
import { useMessagePanel } from "@/composables/useMessagePanel";
const { addSuccess } = useMessagePanel();

interface Perihal {
  id: number;
  nama: string;
}

interface Bank {
  id: number;
  nama_bank: string;
}
// moved below defineProps to avoid TDZ when accessing props

interface PurchaseOrder {
  id: number;
  no_po: string;
  // perihal is kept only for display from PO, not used in form data
  perihal?: Perihal | null;
  perihal_id?: number | null;
  total?: number;
  metode_pembayaran?: string;
  bank_id?: number | null;
  nama_rekening?: string;
  no_rekening?: string;
  no_giro?: string;
  status?: string; // tambahkan properti status agar filter berjalan
}

interface EditData {
  id: number;
  no_mb?: string;
  tanggal?: string;
  purchase_order_id?: number | null;
  // perihal_id removed from edit data usage in form
  total?: number;
  metode_pembayaran?: string;
  bank_id?: number | null;
  nama_rekening?: string;
  no_rekening?: string;
  no_giro?: string;
  tanggal_giro?: string | null;
  tanggal_cair?: string | null;
  keterangan?: string;
  purchase_orders?: PurchaseOrder[];
  status?: string;
  rejection_reason?: string;
}

interface FormData {
  no_mb: string;
  tanggal: string;
  purchase_order_id: string;
  // perihal_id removed from form
  nominal: string;
  metode_pembayaran: string;
  bank_id: string;
  nama_rekening: string;
  no_rekening: string;
  no_giro: string;
  no_kartu_kredit?: string;
  tanggal_giro: Date | null;
  tanggal_cair: Date | null;
  note: string;
}

const props = defineProps<{
  editData?: EditData;
  departments?: any[];
  // perihals removed from props
  purchaseOrders?: PurchaseOrder[];
  banks?: Bank[];
}>();

const displayTanggal = computed(() => {
  // If editing and tanggal exists, show formatted date
  if (props.editData?.tanggal) {
    try {
      return format(new Date(props.editData.tanggal), "dd-MM-yyyy");
    } catch {
      return props.editData.tanggal;
    }
  }
  // For create (draft state), preview today's date as read-only preview
  try {
    return format(new Date(), "dd-MM-yyyy");
  } catch {
    return "";
  }
});

const emit = defineEmits(["close", "refreshTable"]);

const form = ref<FormData>({
  no_mb: "",
  tanggal: "",
  purchase_order_id: "",
  nominal: "",
  metode_pembayaran: "Transfer",
  bank_id: "",
  nama_rekening: "",
  no_rekening: "",
  no_giro: "",
  no_kartu_kredit: "",
  tanggal_giro: null as Date | null,
  tanggal_cair: null as Date | null,
  note: "",
});

const errors = ref<Record<string, any>>({});
const isSubmitting = ref(false);
const showPurchaseOrderModal = ref(false);
const selectedPurchaseOrders = ref<PurchaseOrder[]>([]);

// Transfer: supplier and bank accounts (declare early to avoid TDZ in watchers)
const selectedSupplierId = ref<string | null>(null);
const supplierOptions = ref<Array<{ label: string; value: string }>>([]);
const selectedSupplierBankAccounts = ref<any[]>([]);
async function fetchSuppliers(query?: string) {
  try {
    const { data } = await axios.get("/memo-pembayaran/suppliers/options", {
      params: { search: query || "", per_page: 200 },
      withCredentials: true,
    });
    const list = Array.isArray(data?.data) ? data.data : [];
    supplierOptions.value = list.map((s: any) => ({
      label: s.nama_supplier,
      value: String(s.id),
    }));
  } catch {
    supplierOptions.value = [];
  }
}

let supplierSearchTimeout: ReturnType<typeof setTimeout>;
function searchSuppliers(query: string) {
  clearTimeout(supplierSearchTimeout);
  supplierSearchTimeout = setTimeout(() => fetchSuppliers(query), 300);
}

// initial load
fetchSuppliers();

// When opening modal, load initial list if dynamic cache empty
watch(showPurchaseOrderModal, (open) => {
  if (
    open &&
    (!dynamicPurchaseOrders.value || dynamicPurchaseOrders.value.length === 0)
  ) {
    searchPurchaseOrders("");
  }
});

// Watch for changes in metode pembayaran and related fields to refresh PO options
watch(
  [
    () => form.value.metode_pembayaran,
    () => selectedSupplierId.value,
    () => form.value.no_giro,
    () => (form.value as any).no_kartu_kredit,
  ],
  () => {
    // Clear PO options when any of these fields change
    dynamicPurchaseOrders.value = [];
    purchaseOrderSearchInfo.value = {};
    // Immediately fetch filtered POs if criteria are ready
    if (canSelectPurchaseOrder()) {
      searchPurchaseOrders("");
    }
  }
);

// Keep nominal in sync with the sum of selected POs
watch(
  selectedPurchaseOrders,
  () => {
    form.value.nominal = formatCurrency(getSelectedPurchaseOrdersTotal());
  },
  { deep: true }
);

// Initialize form with edit data
onMounted(() => {
  if (props.editData) {
    form.value = {
      no_mb: props.editData.no_mb || "",
      tanggal: props.editData.tanggal || "",
      purchase_order_id: props.editData.purchase_order_id?.toString() || "",
      nominal: formatCurrency(props.editData.total || 0),
      metode_pembayaran: props.editData.metode_pembayaran || "Transfer",
      bank_id: props.editData.bank_id?.toString() || "",
      nama_rekening: props.editData.nama_rekening || "",
      no_rekening: props.editData.no_rekening || "",
      no_giro: props.editData.no_giro || "",
      no_kartu_kredit: (props as any).editData?.no_kartu_kredit || "",
      tanggal_giro: props.editData.tanggal_giro
        ? new Date(props.editData.tanggal_giro)
        : null,
      tanggal_cair: props.editData.tanggal_cair
        ? new Date(props.editData.tanggal_cair)
        : null,
      note: props.editData.keterangan || "",
    };

    // Load existing purchase orders for edit mode
    if (props.editData.purchase_orders && Array.isArray(props.editData.purchase_orders)) {
      selectedPurchaseOrders.value = props.editData.purchase_orders;
      // Ensure nominal reflects the sum of existing selected POs in edit mode
      form.value.nominal = formatCurrency(getSelectedPurchaseOrdersTotal());
    }
  }

  // Load initial giro options if metode pembayaran is Cek/Giro
  if (form.value.metode_pembayaran === "Cek/Giro") {
    searchGiroNumbers("");
  }
});

// Removed isPerihalReadonly usage; always display-only

// no displayPerihalName; we use disabled CustomSelect bound to perihal_id

const purchaseOrderOptions = computed(() => {
  // Tampilkan semua PO yang belum dipakai, termasuk yang status-nya 'Canceled'
  const source = dynamicPurchaseOrders.value || [];
  return source.map((po: any) => ({
    label: `${po.no_po}`,
    value: po.id.toString(),
  }));
});

// Debounced search for purchase orders (approved) for dropdown
let poSearchTimeout: ReturnType<typeof setTimeout>;
function searchPurchaseOrders(query: string) {
  clearTimeout(poSearchTimeout);
  poSearchTimeout = setTimeout(async () => {
    try {
      // Build params based on selected metode pembayaran and related fields
      const params: any = { search: query, per_page: 20 };

      // Add metode pembayaran filter
      if (form.value.metode_pembayaran) {
        params.metode_pembayaran = form.value.metode_pembayaran;
      }

      // Add specific filters based on metode pembayaran
      if (form.value.metode_pembayaran === "Transfer" && selectedSupplierId.value) {
        params.supplier_id = selectedSupplierId.value;
      } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
        params.no_giro = form.value.no_giro;
      } else if (
        form.value.metode_pembayaran === "Kredit" &&
        (form.value as any).no_kartu_kredit
      ) {
        params.no_kartu_kredit = (form.value as any).no_kartu_kredit;
      }

      const { data } = await axios.get("/memo-pembayaran/purchase-orders/search", {
        params,
        withCredentials: true,
      });
      if (data && data.success) {
        // Overwrite props-like list by emitting event is not possible; so maintain a local cache for modal
        dynamicPurchaseOrders.value = data.data || [];
        purchaseOrderSearchInfo.value = {
          total_count: data.total_count,
          filter_info: data.filter_info,
        };
      }
    } catch (error) {
      console.error("Error searching PO:", error); // Debug log
    }
  }, 300);
}

// Maintain dynamic list for modal (fallbacks to props when empty)
const dynamicPurchaseOrders = ref<PurchaseOrder[]>([]);
const purchaseOrderSearchInfo = ref<{
  total_count?: number;
  filter_info?: any;
}>({});

const availablePurchaseOrders = computed<PurchaseOrder[]>(() => {
  // return dynamicPurchaseOrders.value && dynamicPurchaseOrders.value.length > 0
  // ? dynamicPurchaseOrders.value
  // : props.purchaseOrders || [];
  // Always use dynamically fetched, filtered list for modal
  return dynamicPurchaseOrders.value || [];
});

// Modal search handling (delegated to child component input)
function onPurchaseOrderSearch(query: string) {
  searchPurchaseOrders(query);
}

// Note: Bank options for Transfer are sourced from supplier bank accounts, not master banks

const metodePembayaranOptions = computed(() => [
  { label: "Transfer", value: "Transfer" },
  { label: "Cek/Giro", value: "Cek/Giro" },
  { label: "Kredit", value: "Kredit" },
]);

// (moved above watchers)

async function handleSupplierChange(supplierId: string) {
  selectedSupplierId.value = supplierId || null;
  form.value.bank_id = "";
  form.value.nama_rekening = "";
  form.value.no_rekening = "";
  selectedSupplierBankAccounts.value = [];

  // Clear PO options when supplier changes
  dynamicPurchaseOrders.value = [];
  purchaseOrderSearchInfo.value = {};

  if (!supplierId) return;
  try {
    const response = await axios.post("/purchase-orders/supplier-bank-accounts", {
      supplier_id: supplierId,
    });
    const { bank_accounts } = response.data || {};
    selectedSupplierBankAccounts.value = Array.isArray(bank_accounts)
      ? bank_accounts
      : [];
    if (selectedSupplierBankAccounts.value.length === 1) {
      const account = selectedSupplierBankAccounts.value[0];
      form.value.bank_id = String(account.bank_id);
      form.value.nama_rekening = account.nama_rekening || "";
      form.value.no_rekening = account.no_rekening || "";
    }

    // Refresh purchase order options based on selected supplier
    if (form.value.metode_pembayaran === "Transfer") {
      searchPurchaseOrders("");
    }
  } catch {
    selectedSupplierBankAccounts.value = [];
  }
}

function handleBankChange(bankId: string) {
  form.value.bank_id = bankId;
  form.value.nama_rekening = "";
  form.value.no_rekening = "";
  if (!bankId) return;
  const account = selectedSupplierBankAccounts.value.find(
    (a: any) => String(a.bank_id) === String(bankId)
  );
  if (account) {
    form.value.nama_rekening = account.nama_rekening || "";
    form.value.no_rekening = account.no_rekening || "";
  }
}

// Kredit: credit cards
const creditCardOptions = ref<any[]>([]);
const selectedCreditCardId = ref<string | null>(null);
const selectedCreditCardBankName = ref<string>("");
let creditCardSearchTimeout: ReturnType<typeof setTimeout>;

watch(
  () => form.value.metode_pembayaran,
  async (metode) => {
    if (metode === "Kredit") {
      selectedCreditCardId.value = null;
      (form.value as any).no_kartu_kredit = "";
      selectedCreditCardBankName.value = "";
      try {
        const { data } = await axios.get("/credit-cards", {
          headers: { Accept: "application/json" },
          params: { per_page: 1000 },
          withCredentials: true,
        });
        creditCardOptions.value = Array.isArray(data?.data)
          ? data.data
          : Array.isArray(data)
          ? data
          : [];
      } catch {
        creditCardOptions.value = [];
      }
    } else if (metode === "Cek/Giro") {
      // load giro numbers list
      try {
        const { data } = await axios.get("/memo-pembayaran/giro-numbers", {
          headers: { Accept: "application/json" },
          params: { per_page: 200 },
          withCredentials: true,
        });
        giroOptions.value = Array.isArray(data?.data) ? data.data : [];
      } catch (error) {
        console.error("Error loading giro numbers:", error);
        giroOptions.value = [];
      }
    }
  },
  { immediate: true }
);

function searchCreditCards(query: string) {
  clearTimeout(creditCardSearchTimeout);
  creditCardSearchTimeout = setTimeout(async () => {
    try {
      const { data } = await axios.get("/credit-cards", {
        headers: { Accept: "application/json" },
        params: { search: query, per_page: 100 },
        withCredentials: true,
      });
      creditCardOptions.value = Array.isArray(data?.data)
        ? data.data
        : Array.isArray(data)
        ? data
        : [];
    } catch {
      creditCardOptions.value = [];
    }
  }, 300);
}

function handleSelectCreditCard(creditCardId: string) {
  selectedCreditCardId.value = creditCardId || null;
  (form.value as any).no_kartu_kredit = "";
  form.value.bank_id = "";
  selectedCreditCardBankName.value = "";

  // Clear PO options when credit card changes
  dynamicPurchaseOrders.value = [];
  purchaseOrderSearchInfo.value = {};

  if (!creditCardId) return;
  const cc = creditCardOptions.value.find(
    (c: any) => String(c.id) === String(creditCardId)
  );
  if (cc) {
    (form.value as any).no_kartu_kredit = cc.no_kartu_kredit || "";
    form.value.bank_id = cc.bank_id ? String(cc.bank_id) : "";
    selectedCreditCardBankName.value = cc.bank?.nama_bank
      ? cc.bank?.singkatan
        ? `${cc.bank.nama_bank} (${cc.bank.singkatan})`
        : cc.bank.nama_bank
      : "";

    // Refresh purchase order options based on selected credit card
    if (form.value.metode_pembayaran === "Kredit") {
      searchPurchaseOrders("");
    }
  }
}

// Giro options from API
const giroOptions = ref<Array<{ label: string; value: string }>>([]);

// Search for giro numbers
let giroSearchTimeout: ReturnType<typeof setTimeout>;
function searchGiroNumbers(query: string) {
  clearTimeout(giroSearchTimeout);
  giroSearchTimeout = setTimeout(async () => {
    try {
      const { data } = await axios.get("/memo-pembayaran/giro-numbers", {
        headers: { Accept: "application/json" },
        params: { search: query, per_page: 100 },
        withCredentials: true,
      });
      giroOptions.value = Array.isArray(data?.data) ? data.data : [];
    } catch (error) {
      console.error("Error searching giro numbers:", error);
      giroOptions.value = [];
    }
  }, 300);
}

// Handle giro number selection
function handleGiroChange(giroNumber: string) {
  form.value.no_giro = giroNumber;

  // Clear PO options when giro changes
  dynamicPurchaseOrders.value = [];
  purchaseOrderSearchInfo.value = {};

  // Auto-fill tanggal_giro and tanggal_cair from selected giro
  if (giroNumber) {
    const selectedGiro = giroOptions.value.find((option) => option.value === giroNumber);
    if (selectedGiro && (selectedGiro as any).tanggal_giro) {
      form.value.tanggal_giro = new Date((selectedGiro as any).tanggal_giro);
    }
    if (selectedGiro && (selectedGiro as any).tanggal_cair) {
      form.value.tanggal_cair = new Date((selectedGiro as any).tanggal_cair);
    }

    // Refresh purchase order options based on selected giro
    if (form.value.metode_pembayaran === "Cek/Giro") {
      searchPurchaseOrders("");
    }
  } else {
    // Clear dates if no giro selected
    form.value.tanggal_giro = null;
    form.value.tanggal_cair = null;
  }
}

function formatNominal() {
  const value = form.value.nominal.replace(/[^\d]/g, "");
  if (value) {
    form.value.nominal = formatCurrency(parseInt(value));
  }
}

function onPurchaseOrderChange() {
  if (!form.value.purchase_order_id) return;

  // Check if purchase order selection is allowed
  if (!canSelectPurchaseOrder()) {
    form.value.purchase_order_id = "";
    return;
  }

  const selectedPO = availablePurchaseOrders.value.find(
    (po) => po.id.toString() === form.value.purchase_order_id
  );
  if (!selectedPO) return;

  // Validate that the PO matches the current filter criteria
  let isValid = true;
  let errorMessage = "";

  // First check if PO metode pembayaran matches selected metode
  if (selectedPO.metode_pembayaran !== form.value.metode_pembayaran) {
    isValid = false;
    errorMessage = `Purchase Order menggunakan metode pembayaran ${selectedPO.metode_pembayaran}, tidak sesuai dengan metode yang dipilih (${form.value.metode_pembayaran})`;
    // } else if ((selectedPO as any).status !== "Approved") {
    //     isValid = false;
    //     errorMessage = "Hanya Purchase Order yang disetujui (Approved) yang dapat dipilih";
  } else if (form.value.metode_pembayaran === "Transfer" && selectedSupplierId.value) {
    if ((selectedPO as any).supplier?.id?.toString() !== selectedSupplierId.value) {
      isValid = false;
      errorMessage = "Purchase Order tidak sesuai dengan Supplier yang dipilih";
    }
  } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
    if (selectedPO.no_giro !== form.value.no_giro) {
      isValid = false;
      errorMessage = "Purchase Order tidak sesuai dengan No. Cek/Giro yang dipilih";
    }
  } else if (
    form.value.metode_pembayaran === "Kredit" &&
    (form.value as any).no_kartu_kredit
  ) {
    if ((selectedPO as any).no_kartu_kredit !== (form.value as any).no_kartu_kredit) {
      isValid = false;
      errorMessage = "Purchase Order tidak sesuai dengan Kartu Kredit yang dipilih";
    }
  }

  if (!isValid) {
    console.warn(errorMessage);
    form.value.purchase_order_id = "";
    return;
  }

  // Add to selected list if not already there
  // For dropdown path: replace selection with this single PO (not additive)
  selectedPurchaseOrders.value = [selectedPO];
  // Auto-update nominal as sum of selected POs
  form.value.nominal = formatCurrency(getSelectedPurchaseOrdersTotal());

  // Auto-fill fields from PO
  applyPurchaseOrderToForm(selectedPO as any);
  // Rebuild supplier options from selected POs
  fetchSuppliers();

  // Clear single-select dropdown after adding
  form.value.purchase_order_id = "";
}

function applyPurchaseOrderToForm(po: any) {
  // perihal_id no longer set on form
  // Nominal will be controlled by the sum of selected POs
  form.value.metode_pembayaran = po.metode_pembayaran || "";
  form.value.bank_id = po.bank_id ? String(po.bank_id) : "";
  form.value.nama_rekening = po.nama_rekening || "";
  form.value.no_rekening = po.no_rekening || "";
  if (po.no_giro && po.metode_pembayaran === "Cek/Giro") {
    form.value.no_giro = po.no_giro;
  }
}

function onMetodePembayaranChange() {
  // Reset Cek/Giro specific fields when method changes
  if (form.value.metode_pembayaran !== "Cek/Giro") {
    form.value.no_giro = "";
    form.value.tanggal_giro = null;
    form.value.tanggal_cair = null;
  }
  if (form.value.metode_pembayaran !== "Transfer") {
    selectedSupplierId.value = null as any;
    selectedSupplierBankAccounts.value = [];
    form.value.bank_id = "" as any;
    form.value.nama_rekening = "" as any;
    form.value.no_rekening = "" as any;
  }
  if (form.value.metode_pembayaran !== "Kredit") {
    selectedCreditCardId.value = null as any;
    (form.value as any).no_kartu_kredit = "";
  }

  // Clear purchase order options when metode pembayaran changes
  dynamicPurchaseOrders.value = [];
  purchaseOrderSearchInfo.value = {};
  // Immediately fetch filtered POs if criteria are ready
  if (canSelectPurchaseOrder()) {
    searchPurchaseOrders("");
  }
}

function addPurchaseOrder(po: any) {
  // Check if purchase order selection is allowed
  if (!canSelectPurchaseOrder()) {
    return;
  }

  // Validate that the PO matches the current filter criteria
  let isValid = true;
  let errorMessage = "";

  // First check if PO metode pembayaran matches selected metode
  if (po.metode_pembayaran !== form.value.metode_pembayaran) {
    isValid = false;
    errorMessage = `Purchase Order menggunakan metode pembayaran ${po.metode_pembayaran}, tidak sesuai dengan metode yang dipilih (${form.value.metode_pembayaran})`;
  } else if (form.value.metode_pembayaran === "Transfer" && selectedSupplierId.value) {
    if ((po as any).supplier?.id?.toString() !== selectedSupplierId.value) {
      isValid = false;
      errorMessage = "Purchase Order tidak sesuai dengan Supplier yang dipilih";
    }
  } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
    if (po.no_giro !== form.value.no_giro) {
      isValid = false;
      errorMessage = "Purchase Order tidak sesuai dengan No. Cek/Giro yang dipilih";
    }
  } else if (
    form.value.metode_pembayaran === "Kredit" &&
    (form.value as any).no_kartu_kredit
  ) {
    if ((po as any).no_kartu_kredit !== (form.value as any).no_kartu_kredit) {
      isValid = false;
      errorMessage = "Purchase Order tidak sesuai dengan Kartu Kredit yang dipilih";
    }
  }

  if (!isValid) {
    console.warn(errorMessage);
    return;
  }

  if (!isPurchaseOrderSelected(po.id)) {
    // Check if adding this PO would exceed the total nominal
    const currentTotal = selectedPurchaseOrders.value.reduce(
      (sum, selectedPo) => sum + (selectedPo.total || 0),
      0
    );
    const newTotal = currentTotal + (po.total || 0);
    const formTotal = Number(parseCurrency(form.value.nominal)) || 0;

    if (formTotal > 0 && newTotal > formTotal) {
      console.warn(
        `Total Purchase Order (${formatCurrency(
          newTotal
        )}) melebihi nominal yang diinput (${formatCurrency(formTotal)})`
      );
      return;
    }

    selectedPurchaseOrders.value.push(po);
    // Auto-update nominal as sum of selected POs
    form.value.nominal = formatCurrency(getSelectedPurchaseOrdersTotal());
  } else {
    console.warn("Purchase Order ini sudah dipilih");
  }

  // Auto-fill fields from PO (handles perihal_id from nested object too)
  applyPurchaseOrderToForm(po);
}

// Bulk add POs from modal selection
function addManyPurchaseOrders(list: any[]) {
  if (!Array.isArray(list) || list.length === 0) return;
  for (const po of list) {
    addPurchaseOrder(po);
  }
}

function removePurchaseOrder(poId: number) {
  selectedPurchaseOrders.value = selectedPurchaseOrders.value.filter(
    (po) => po.id !== poId
  );
  fetchSuppliers();
  // Recalculate nominal after removal
  form.value.nominal = formatCurrency(getSelectedPurchaseOrdersTotal());
}

function isPurchaseOrderSelected(poId: number) {
  return selectedPurchaseOrders.value.some((po) => po.id === poId);
}

// Check if purchase order selection is allowed based on metode pembayaran and related fields
function canSelectPurchaseOrder(): boolean {
  if (!form.value.metode_pembayaran) return false;

  let result: boolean;
  switch (form.value.metode_pembayaran) {
    case "Transfer":
      result = !!selectedSupplierId.value;
      break;
    case "Cek/Giro":
      result = !!form.value.no_giro;
      break;
    case "Kredit":
      result = !!(form.value as any).no_kartu_kredit;
      break;
    default:
      result = false;
  }
  return result;
}

// Get placeholder text for purchase order dropdown
function getPurchaseOrderPlaceholder(): string {
  // Reflect current selections first
  const count = selectedPurchaseOrders.value.length;
  if (count === 1) {
    const one = selectedPurchaseOrders.value[0];
    return one?.no_po ? String(one.no_po) : "1 PO dipilih";
  }
  if (count > 1) {
    return `${count} PO dipilih`;
  }

  if (!form.value.metode_pembayaran) {
    return "Pilih metode pembayaran terlebih dahulu";
  }

  switch (form.value.metode_pembayaran) {
    case "Transfer":
      return selectedSupplierId.value
        ? "Pilih Purchase Order dari Supplier"
        : "Pilih Supplier terlebih dahulu";
    case "Cek/Giro":
      return form.value.no_giro
        ? "Pilih Purchase Order dengan Giro ini"
        : "Pilih No. Cek/Giro terlebih dahulu";
    case "Kredit":
      return (form.value as any).no_kartu_kredit
        ? "Pilih Purchase Order dengan Kartu Kredit ini"
        : "Pilih Kartu Kredit terlebih dahulu";
    default:
      return "Pilih Purchase Order (opsional)";
  }
}

// Get helper text for purchase order selection
function getPurchaseOrderHelperText(): string {
  if (!form.value.metode_pembayaran) {
    return "Pilih metode pembayaran terlebih dahulu untuk memilih Purchase Order";
  }

  switch (form.value.metode_pembayaran) {
    case "Transfer":
      return selectedSupplierId.value
        ? "Pilih Purchase Order dari Supplier"
        : "Pilih Supplier terlebih dahulu";
    case "Cek/Giro":
      return form.value.no_giro
        ? "Pilih Purchase Order dengan Giro ini"
        : "Pilih No. Cek/Giro terlebih dahulu";
    case "Kredit":
      return (form.value as any).no_kartu_kredit
        ? "Pilih Purchase Order dengan Kartu Kredit ini"
        : "Pilih Kartu Kredit terlebih dahulu";
    default:
      return "Tidak ada Purchase Order yang tersedia";
  }
}

// Get message when no purchase orders are available
function getNoResultsMessage(): string {
  if (!form.value.metode_pembayaran) {
    return "Pilih metode pembayaran terlebih dahulu";
  }

  switch (form.value.metode_pembayaran) {
    case "Transfer":
      if (!selectedSupplierId.value) {
        return "Pilih Supplier terlebih dahulu untuk melihat Purchase Order yang tersedia";
      }
      return "Tidak ada Purchase Order yang disetujui untuk Supplier yang dipilih";
    case "Cek/Giro":
      if (!form.value.no_giro) {
        return "Pilih No. Cek/Giro terlebih dahulu untuk melihat Purchase Order yang tersedia";
      }
      return "Tidak ada Purchase Order yang disetujui dengan No. Cek/Giro yang dipilih";
    case "Kredit":
      if (!(form.value as any).no_kartu_kredit) {
        return "Pilih Kartu Kredit terlebih dahulu untuk melihat Purchase Order yang tersedia";
      }
      return "Tidak ada Purchase Order yang disetujui dengan Kartu Kredit yang dipilih";
    default:
      return "Tidak ada Purchase Order yang tersedia";
  }
}

// Open purchase order modal with validation
function openPurchaseOrderModal() {
  if (!canSelectPurchaseOrder()) {
    // Show error message based on what's missing
    let errorMessage = "Pilih metode pembayaran terlebih dahulu";

    if (form.value.metode_pembayaran) {
      switch (form.value.metode_pembayaran) {
        case "Transfer":
          if (!selectedSupplierId.value) {
            errorMessage =
              "Pilih Supplier terlebih dahulu untuk membuka daftar Purchase Order";
          }
          break;
        case "Cek/Giro":
          if (!form.value.no_giro) {
            errorMessage =
              "Pilih No. Cek/Giro terlebih dahulu untuk membuka daftar Purchase Order";
          }
          break;
        case "Kredit":
          if (!(form.value as any).no_kartu_kredit) {
            errorMessage =
              "Pilih Kartu Kredit terlebih dahulu untuk membuka daftar Purchase Order";
          }
          break;
      }
    }

    // You can add a toast notification here if you have a toast system
    console.warn(errorMessage);
    return;
  }
  showPurchaseOrderModal.value = true;
}

// Get total of selected purchase orders
function getSelectedPurchaseOrdersTotal(): number {
  return selectedPurchaseOrders.value.reduce((sum, po) => {
    const val = Number((po as any).total);
    return sum + (isNaN(val) ? 0 : val);
  }, 0);
}

function saveDraft() {
  handleSubmit("draft");
}

const onSubmit = () => {
  handleSubmit("send");
};

function handleSubmit(action: "send" | "draft" = "send") {
  isSubmitting.value = true;
  errors.value = {};

  // Validate total purchase orders vs nominal (only when sending)
  const isSend = action === "send";
  if (isSend) {
    const selectedTotal = getSelectedPurchaseOrdersTotal();
    const formTotal = Number(parseCurrency(form.value.nominal)) || 0;
    if (selectedTotal > 0 && formTotal > 0 && selectedTotal !== formTotal) {
      errors.value.nominal = `Total Purchase Order (${formatCurrency(
        selectedTotal
      )}) tidak sama dengan nominal yang diinput (${formatCurrency(formTotal)})`;
      isSubmitting.value = false;
      return;
    }
  }

  // Validate that all selected purchase orders match the selected metode pembayaran
  if (selectedPurchaseOrders.value.length > 0) {
    const invalidPOs = selectedPurchaseOrders.value.filter(
      (po) => po.metode_pembayaran !== form.value.metode_pembayaran
    );
    if (invalidPOs.length > 0) {
      errors.value.purchase_order_id = `Purchase Order ${invalidPOs
        .map((po) => po.no_po)
        .join(", ")} tidak sesuai dengan metode pembayaran yang dipilih`;
      isSubmitting.value = false;
      return;
    }

    // Validate that all selected purchase orders match the selected supplier/giro/kredit
    let invalidCriteriaPOs: any[] = [];

    if (form.value.metode_pembayaran === "Transfer" && selectedSupplierId.value) {
      invalidCriteriaPOs = selectedPurchaseOrders.value.filter(
        (po) => (po as any).supplier?.id?.toString() !== selectedSupplierId.value
      );
    } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
      invalidCriteriaPOs = selectedPurchaseOrders.value.filter(
        (po) => po.no_giro !== form.value.no_giro
      );
    } else if (
      form.value.metode_pembayaran === "Kredit" &&
      (form.value as any).no_kartu_kredit
    ) {
      invalidCriteriaPOs = selectedPurchaseOrders.value.filter(
        (po) => (po as any).no_kartu_kredit !== (form.value as any).no_kartu_kredit
      );
    }

    if (invalidCriteriaPOs.length > 0) {
      errors.value.purchase_order_id = `Purchase Order ${invalidCriteriaPOs
        .map((po) => po.no_po)
        .join(", ")} tidak sesuai dengan kriteria yang dipilih`;
      isSubmitting.value = false;
      return;
    }

    // Validate that all selected purchase orders have Approved status
    // (Relaxed temporarily: allow any status)
    // const nonApprovedPOs = selectedPurchaseOrders.value.filter(
    //   (po) => (po as any).status !== "Approved"
    // );
    // if (nonApprovedPOs.length > 0) {
    //   errors.value.purchase_order_id = `Purchase Order ${nonApprovedPOs
    //     .map((po) => po.no_po)
    //     .join(", ")} belum disetujui`;
    //   isSubmitting.value = false;
    //   return;
    // }

    // Validate that there are no duplicate purchase orders
    const poIds = selectedPurchaseOrders.value.map((po) => po.id);
    const duplicateIds = poIds.filter((id, index) => poIds.indexOf(id) !== index);
    if (duplicateIds.length > 0) {
      const duplicatePOs = selectedPurchaseOrders.value.filter((po) =>
        duplicateIds.includes(po.id)
      );
      errors.value.purchase_order_id = `Purchase Order ${duplicatePOs
        .map((po) => po.no_po)
        .join(", ")} dipilih lebih dari sekali`;
      isSubmitting.value = false;
      return;
    }

    // Note: Additional validation for PO already used in other Memo Pembayaran
    // would require backend validation since we don't have that data in frontend
  }

  // Validate required fields (depends on action and metode)
  const baseRequired: Array<keyof FormData> = ["nominal", "metode_pembayaran"];
  // Strict requirements when sending
  const transferSendRequired: Array<keyof FormData> = [
    "bank_id",
    "nama_rekening",
    "no_rekening",
  ];
  const cekGiroSendRequired: Array<keyof FormData> = [
    "no_giro",
    "tanggal_giro",
    "tanggal_cair",
  ];
  const kreditSendRequired: Array<keyof FormData> = ["no_kartu_kredit"];
  // Minimal requirements for draft
  const transferDraftRequired: Array<keyof FormData> = ["bank_id"];
  const cekGiroDraftRequired: Array<keyof FormData> = ["no_giro"];
  const kreditDraftRequired: Array<keyof FormData> = ["no_kartu_kredit"];

  let requiredFields: Array<keyof FormData> = [...baseRequired];
  const isSending = action === "send";
  if (form.value.metode_pembayaran === "Transfer")
    requiredFields = requiredFields.concat(
      isSending ? transferSendRequired : transferDraftRequired
    );
  if (form.value.metode_pembayaran === "Cek/Giro")
    requiredFields = requiredFields.concat(
      isSending ? cekGiroSendRequired : cekGiroDraftRequired
    );
  if (form.value.metode_pembayaran === "Kredit")
    requiredFields = requiredFields.concat(
      isSending ? kreditSendRequired : kreditDraftRequired
    );

  const missingFields = requiredFields.filter((field) => !(form.value as any)[field]);

  if (missingFields.length > 0) {
    errors.value = missingFields.reduce((acc, field) => {
      acc[field as string] = "Field ini wajib diisi";
      return acc;
    }, {} as Record<string, string>);
    isSubmitting.value = false;
    return;
  }

  const payload = {
    // perihal_id removed from payload
    purchase_order_ids: selectedPurchaseOrders.value.map((po) => po.id),
    total: parseCurrency(form.value.nominal),
    metode_pembayaran: form.value.metode_pembayaran,
    bank_id: form.value.bank_id || null,
    nama_rekening: form.value.nama_rekening || null,
    no_rekening: form.value.no_rekening || null,
    no_giro: form.value.no_giro || null,
    no_kartu_kredit: (form.value as any).no_kartu_kredit || null,
    tanggal_giro: form.value.tanggal_giro || null,
    tanggal_cair: form.value.tanggal_cair || null,
    keterangan: form.value.note,
    action: action, // 'draft' or 'send'
  };

  const url = props.editData
    ? `/memo-pembayaran/${props.editData.id}`
    : "/memo-pembayaran";

  const method = props.editData ? "put" : "post";

  router[method](url, payload, {
    onSuccess: () => {
      if (action === "send") {
        addSuccess("Memo Pembayaran berhasil dikirim!");
      } else {
        addSuccess("Memo Pembayaran berhasil disimpan sebagai draft!");
      }
      emit("close");
      emit("refreshTable");
    },
    onError: (errorBag) => {
      errors.value = errorBag as Record<string, any>;
      // Map backend error keys to frontend field names
      if ((errors.value as any).purchase_order_ids && !errors.value.purchase_order_id) {
        errors.value.purchase_order_id = (errors.value as any).purchase_order_ids;
      }
      isSubmitting.value = false;
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
}
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

/* Special handling for readonly inputs - only show label at top when filled */
.floating-input-field[readonly].filled ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Date input special handling */
.floating-input-field[type="date"] ~ .floating-label {
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
.floating-input-field[readonly].filled ~ .floating-label,
.floating-input-field[type="date"] ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}

/* Floating label support for vue-datepicker */
.floating-input .floating-input-field:focus ~ .floating-label,
.floating-input .floating-input-field.filled ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
  background-color: white;
  padding: 0 0.25rem;
}

/* Datepicker specific styling for Quicksand font */
.floating-input .dp__input {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
  color: #374151 !important;
  border: 1px solid #d1d5db !important;
}

.floating-input .dp__input::placeholder {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  color: #6b7280 !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
}

.floating-input .dp__input:focus {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  border-color: #1f9254 !important;
}

/* Ensure datepicker dropdown also uses Quicksand */
.floating-input .dp__main {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
}

.floating-input .dp__calendar_header,
.floating-input .dp__calendar_header_separator,
.floating-input .dp__calendar_header_cell,
.floating-input .dp__calendar_row,
.floating-input .dp__calendar_cell,
.floating-input .dp__month_year_row,
.floating-input .dp__month_year_select,
.floating-input .dp__action_buttons,
.floating-input .dp__action_button {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
}
</style>
