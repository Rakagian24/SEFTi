<template>
  <div class="bg-white rounded-lg shadow p-6">
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
                :key="selectedPurchaseOrder?.id || 'none'"
                v-model="form.purchase_order_id"
                :options="purchaseOrderOptions"
                :placeholder="getPurchaseOrderPlaceholder()"
                :error="errors.purchase_order_id"
                :searchable="true"
                :disabled="isFieldLocked() || !canSelectPurchaseOrder()"
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
              :disabled="isFieldLocked() || !canSelectPurchaseOrder()"
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
            >
              <template #label
                >Nomor Cek/Giro<span class="text-red-500">*</span></template
              >
            </CustomSelect>
            <div v-if="errors.no_giro" class="text-red-500 text-xs mt-1">
              {{ errors.no_giro }}
            </div>
            <div
              v-if="
                form.metode_pembayaran === 'Cek/Giro' && props.giroNumbers.length === 0
              "
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
            v-if="selectedPurchaseOrder?.tipe_po !== 'Lainnya'"
            v-model="form.nominal"
            type="text"
            id="nominal"
            class="floating-input-field"
            :class="{ 'border-red-500': errors.nominal }"
            placeholder=" "
            @input="formatNominal"
          />
          <div
            v-else
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ form.nominal || "0" }}
          </div>
          <label for="nominal" class="floating-label">
            Nominal<span class="text-red-500">*</span>
          </label>
          <div v-if="errors.nominal" class="text-red-500 text-xs mt-1">
            {{ errors.nominal }}
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

      <!-- Cicilan (for PO Lainnya with Termin) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div v-if="selectedPurchaseOrder?.tipe_po === 'Lainnya'" class="floating-input">
          <input
            v-model="form.cicilan"
            type="text"
            id="cicilan"
            class="floating-input-field"
            :class="{ 'border-red-500': errors.cicilan }"
            placeholder=" "
            @input="formatCicilan"
          />
          <label for="cicilan" class="floating-label">
            Cicilan<span class="text-red-500">*</span>
          </label>
          <div v-if="errors.cicilan" class="text-red-500 text-xs mt-1">
            {{ errors.cicilan }}
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

      <!-- Selected Purchase Order Display (full width) -->
      <div v-if="selectedPurchaseOrder" class="space-y-2">
        <div class="flex items-center justify-between">
          <label class="block text-sm font-medium text-gray-700">
            Purchase Order yang Dipilih:
          </label>
          <div class="text-sm text-gray-600">
            Total: {{ formatCurrency(selectedPurchaseOrder.total || 0) }}
          </div>
        </div>
        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-md">
          <div class="flex-1">
            <div class="flex items-center gap-2">
              <span class="font-medium">{{ selectedPurchaseOrder.no_po }}</span>
              <span
                v-if="(selectedPurchaseOrder as any).status === 'Approved'"
                class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full"
              >
                Approved
              </span>
            </div>
            <div class="text-sm text-gray-500">
              {{ selectedPurchaseOrder.perihal?.nama || "" }}
            </div>
            <div class="text-xs text-gray-400 mt-1">
              Metode: {{ selectedPurchaseOrder.metode_pembayaran }} • Nominal:
              {{ formatCurrency(selectedPurchaseOrder.total || 0) }}
            </div>
          </div>
          <button
            type="button"
            @click="removePurchaseOrder"
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

      <!-- Termin Summary Display for PO Lainnya -->
      <div
        v-if="
          selectedPurchaseOrder?.tipe_po === 'Lainnya' && selectedPurchaseOrder?.termin
        "
        class="col-span-2"
      >
        <TerminSummaryDisplay
          :termin-info="selectedPurchaseOrder.termin"
          :is-lainnya="true"
        />
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
      :selected-ids="selectedPurchaseOrder ? [selectedPurchaseOrder.id] : []"
      :no-results-message="getNoResultsMessage()"
      @search="onPurchaseOrderSearch"
      @add="addPurchaseOrder"
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
import TerminSummaryDisplay from "../purchase-orders/TerminSummaryDisplay.vue";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";
import axios from "axios";
import { format } from "date-fns";
// import { useMessagePanel } from "@/composables/useMessagePanel";
// const { addSuccess } = useMessagePanel();

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
  supplier_id?: number | null;
  no_kartu_kredit?: string;
  tanggal_giro?: string | null;
  tanggal_cair?: string | null;
  tipe_po?: string; // tambahkan tipe_po untuk PO tipe Lainnya
  termin_id?: number | null; // tambahkan termin_id untuk PO tipe Lainnya
  termin?: any; // tambahkan termin untuk PO tipe Lainnya
}

interface EditData {
  id: number;
  no_mb?: string;
  tanggal?: string;
  purchase_order_id?: number | null;
  total?: number;
  cicilan?: number;
  metode_pembayaran?: string;
  bank_id?: number | null;
  supplier_id?: number | null;
  credit_card_id?: number | null;
  nama_rekening?: string;
  no_rekening?: string;
  no_giro?: string;
  tanggal_giro?: string | null;
  tanggal_cair?: string | null;
  keterangan?: string;
  purchase_order?: PurchaseOrder;
  purchase_orders?: PurchaseOrder[]; // Fallback for old data
  status?: string;
  rejection_reason?: string;
}

interface FormData {
  no_mb: string;
  tanggal: string;
  purchase_order_id: string;
  nominal: string;
  cicilan: string;
  metode_pembayaran: string;
  bank_id: string;
  supplier_id?: string;
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
  giroNumbers: any[];
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
  cicilan: "",
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
const selectedPurchaseOrder = ref<PurchaseOrder | null>(null);

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

// Keep nominal in sync with the selected PO
watch(
  selectedPurchaseOrder,
  () => {
    form.value.nominal = formatCurrency(selectedPurchaseOrder.value?.total || 0);
  },
  { deep: true }
);

onMounted(async () => {
  const edit = props.editData;
  if (!edit) return;

  form.value = {
    no_mb: edit.no_mb || "",
    tanggal: edit.tanggal || "",
    purchase_order_id:
      edit.purchase_order_id?.toString() || edit.purchase_order?.id?.toString() || "",
    nominal: formatCurrency(edit.total || edit.purchase_order?.total || 0),
    cicilan: formatCurrency(edit.cicilan || 0),
    metode_pembayaran:
      edit.metode_pembayaran || edit.purchase_order?.metode_pembayaran || "Transfer",
    bank_id: edit.bank_id?.toString() || edit.purchase_order?.bank_id?.toString() || "",
    supplier_id:
      edit.supplier_id?.toString() || edit.purchase_order?.supplier_id?.toString() || "",
    nama_rekening: edit.nama_rekening || edit.purchase_order?.nama_rekening || "",
    no_rekening: edit.no_rekening || edit.purchase_order?.no_rekening || "",
    no_giro: edit.no_giro || edit.purchase_order?.no_giro || "",
    no_kartu_kredit:
      edit.credit_card_id?.toString() || edit.purchase_order?.no_kartu_kredit || "",
    tanggal_giro: edit.tanggal_giro
      ? new Date(edit.tanggal_giro)
      : edit.purchase_order?.tanggal_giro
      ? new Date(edit.purchase_order.tanggal_giro)
      : null,
    tanggal_cair: edit.tanggal_cair
      ? new Date(edit.tanggal_cair)
      : edit.purchase_order?.tanggal_cair
      ? new Date(edit.purchase_order.tanggal_cair)
      : null,
    note: edit.keterangan || "",
  };

  // Handle single purchase order from backend
  if (edit.purchase_order) {
    selectedPurchaseOrder.value = edit.purchase_order;
    form.value.purchase_order_id = edit.purchase_order.id.toString();
    form.value.nominal = formatCurrency(selectedPurchaseOrder.value.total || 0);
    // Set supplierId dari PO jika belum dipilih
    if (!selectedSupplierId.value && edit.purchase_order.supplier_id) {
      selectedSupplierId.value = edit.purchase_order.supplier_id.toString();
    }
  } else if (
    edit.purchase_orders &&
    Array.isArray(edit.purchase_orders) &&
    edit.purchase_orders.length > 0
  ) {
    // Fallback for old data structure
    selectedPurchaseOrder.value = edit.purchase_orders[0];
    form.value.purchase_order_id = edit.purchase_orders[0].id.toString();
    form.value.nominal = formatCurrency(selectedPurchaseOrder.value.total || 0);
    if (!selectedSupplierId.value && selectedPurchaseOrder.value.supplier_id) {
      selectedSupplierId.value = selectedPurchaseOrder.value.supplier_id.toString();
    }
  }
  // Pastikan purchaseOrderOptions berisi semua PO yang eligible
  dynamicPurchaseOrders.value = props.purchaseOrders || [];

  switch (form.value.metode_pembayaran) {
    case "Transfer":
      if (edit.supplier_id) {
        await handleSupplierChange(String(edit.supplier_id));
        form.value.supplier_id = String(edit.supplier_id);
        form.value.bank_id = edit.bank_id?.toString() || "";
        form.value.no_rekening = edit.no_rekening || "";
        // Ensure supplier appears in dropdown options
        if (!supplierOptions.value.some((o) => o.value === String(edit.supplier_id))) {
          supplierOptions.value.push({
            label: `Supplier ${edit.supplier_id}`,
            value: String(edit.supplier_id),
          });
        }
      }
      break;

    case "Kredit":
      if (edit.credit_card_id) {
        handleSelectCreditCard(String(edit.credit_card_id));
        selectedCreditCardId.value = String(edit.credit_card_id);
        form.value.no_kartu_kredit = ""; // ✅ isi default, user bisa edit
      }
      break;

    case "Cek/Giro":
      if (edit.no_giro) {
        handleGiroChange(edit.no_giro);
      }
      form.value.tanggal_giro = edit.tanggal_giro ? new Date(edit.tanggal_giro) : null;
      form.value.tanggal_cair = edit.tanggal_cair ? new Date(edit.tanggal_cair) : null;
      break;
  }

  if (form.value.metode_pembayaran === "Cek/Giro") {
    searchGiroNumbers("");
  }
});

// no displayPerihalName; we use disabled CustomSelect bound to perihal_id

const purchaseOrderOptions = computed(() => {
  // Combine dynamic list with selected PO (for edit mode)
  const source = Array.isArray(dynamicPurchaseOrders.value)
    ? dynamicPurchaseOrders.value
    : [];
  // Add selectedPurchaseOrder if not present in dynamic list
  if (
    selectedPurchaseOrder.value &&
    !source.some((dpo: any) => dpo.id === selectedPurchaseOrder.value!.id)
  ) {
    source.push(selectedPurchaseOrder.value);
  }
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
        // Filter hanya PO dengan no_giro yang sama persis
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
  form.value.supplier_id = supplierId || "";
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
interface GiroOption {
  label: string;
  value: string;
  tanggal_giro?: string;
  tanggal_cair?: string;
}

const giroOptions = ref<GiroOption[]>([]);

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
function handleGiroChange(giroNumber?: string) {
  form.value.no_giro = giroNumber ?? "";

  // Clear PO options when giro changes
  dynamicPurchaseOrders.value = [];
  purchaseOrderSearchInfo.value = {};

  // Cari giro yang dipilih
  const selectedGiro = giroOptions.value.find(
    (option) => option.value?.toString() === giroNumber?.toString()
  );

  if (selectedGiro) {
    form.value.tanggal_giro = selectedGiro.tanggal_giro
      ? new Date(selectedGiro.tanggal_giro)
      : null;

    form.value.tanggal_cair = selectedGiro.tanggal_cair
      ? new Date(selectedGiro.tanggal_cair)
      : null;

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

function formatCicilan() {
  const value = form.value.cicilan.replace(/[^\d]/g, "");
  if (value) {
    form.value.cicilan = formatCurrency(parseInt(value));
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

  // First check if PO metode pembayaran matches selected metode
  if (selectedPO.metode_pembayaran !== form.value.metode_pembayaran) {
    isValid = false;
    // } else if ((selectedPO as any).status !== "Approved") {
    //     isValid = false;
    //     errorMessage = "Hanya Purchase Order yang disetujui (Approved) yang dapat dipilih";
  } else if (form.value.metode_pembayaran === "Transfer" && selectedSupplierId.value) {
    // Auto-set supplier from PO if not set (dropdown sudah ter-filter, jadi tidak perlu validasi)
    if (!selectedSupplierId.value && (selectedPO as any).supplier_id) {
      selectedSupplierId.value = (selectedPO as any).supplier_id.toString();
    }
  } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
    // Pastikan perbandingan no_giro selalu string
    if (
      (selectedPO.no_giro?.toString() ?? "") !== (form.value.no_giro?.toString() ?? "")
    ) {
      isValid = false;
    }
  } else if (
    form.value.metode_pembayaran === "Kredit" &&
    (form.value as any).no_kartu_kredit
  ) {
    if ((selectedPO as any).no_kartu_kredit !== (form.value as any).no_kartu_kredit) {
      isValid = false;
    }
  }

  if (!isValid) {
    form.value.purchase_order_id = "";
    return;
  }

  // Replace current selection with this single PO
  selectedPurchaseOrder.value = selectedPO;
  // Auto-update nominal from selected PO
  form.value.nominal = formatCurrency(selectedPO.total || 0);

  // Reset cicilan untuk PO tipe Lainnya
  if (selectedPO.tipe_po === "Lainnya") {
    form.value.cicilan = "";
  }

  // Auto-fill fields from PO
  applyPurchaseOrderToForm(selectedPO as any);
  // Rebuild supplier options from selected PO
  fetchSuppliers();
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

  // First check if PO metode pembayaran matches selected metode
  if (po.metode_pembayaran !== form.value.metode_pembayaran) {
    isValid = false;
  } else if (form.value.metode_pembayaran === "Transfer" && selectedSupplierId.value) {
    // Auto-set supplier from PO if not set (dropdown sudah ter-filter, jadi tidak perlu validasi)
    if (!selectedSupplierId.value && (po as any).supplier_id) {
      selectedSupplierId.value = (po as any).supplier_id.toString();
    }
  } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
    // Pastikan perbandingan no_giro selalu string
    if ((po.no_giro?.toString() ?? "") !== (form.value.no_giro?.toString() ?? "")) {
      isValid = false;
    }
  } else if (
    form.value.metode_pembayaran === "Kredit" &&
    (form.value as any).no_kartu_kredit
  ) {
    if ((po as any).no_kartu_kredit !== (form.value as any).no_kartu_kredit) {
      isValid = false;
    }
  }

  if (!isValid) {
    return;
  }

  // Replace current selection with this single PO
  selectedPurchaseOrder.value = po;
  // Set the form value to show the selected PO
  form.value.purchase_order_id = po.id.toString();
  // Auto-update nominal from selected PO
  form.value.nominal = formatCurrency(po.total || 0);

  // Auto-fill fields from PO (handles perihal_id from nested object too)
  applyPurchaseOrderToForm(po);
}

function removePurchaseOrder() {
  selectedPurchaseOrder.value = null;
  form.value.purchase_order_id = "";
  fetchSuppliers();
  // Clear nominal after removal
  form.value.nominal = "";
}

// Check if purchase order selection is allowed based on metode pembayaran and related fields
function canSelectPurchaseOrder(): boolean {
  switch (form.value.metode_pembayaran) {
    case "Transfer":
      // Allow selection if supplier is selected OR if we're in edit mode with existing PO
      return !!selectedSupplierId.value || !!form.value.purchase_order_id;
    case "Cek/Giro":
      return !!form.value.no_giro;
    case "Kredit":
      return !!selectedCreditCardId.value;
    default:
      return false;
  }
}

function isFieldLocked(): boolean {
  // Field tidak locked jika:
  // 1. Mode create (editData tidak ada)
  // 2. Mode edit dengan status Draft
  // 3. Mode edit dengan status Rejected
  if (!props.editData) return false; // Create mode, tidak locked

  const status = props.editData.status;
  return status !== "Draft" && status !== "Rejected";
}

// Get placeholder text for purchase order dropdown
function getPurchaseOrderPlaceholder(): string {
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
    // You can add a toast notification here if you have a toast system
    return;
  }
  showPurchaseOrderModal.value = true;
}

// Get total of selected purchase order
function getSelectedPurchaseOrdersTotal(): number {
  if (selectedPurchaseOrder.value) {
    const val = Number((selectedPurchaseOrder.value as any).total);
    return isNaN(val) ? 0 : val;
  }
  return 0;
}

function saveDraft() {
  errors.value = {};
  handleSubmit("draft");
}

const onSubmit = () => {
  handleSubmit("send");
};

function handleSubmit(action: "send" | "draft" = "send") {
  isSubmitting.value = true;
  errors.value = {};

  if (action === "draft") {
    // 👉 Draft tidak perlu validasi ketat
    // cukup langsung submit payload seadanya
  } else {
    // 👉 Validasi untuk kirim (send)
    const selectedTotal = getSelectedPurchaseOrdersTotal();
    const formTotal = Number(parseCurrency(form.value.nominal)) || 0;
    if (selectedTotal > 0 && formTotal > 0 && selectedTotal !== formTotal) {
      errors.value.nominal = `Total Purchase Order (${formatCurrency(
        selectedTotal
      )}) tidak sama dengan nominal yang diinput (${formatCurrency(formTotal)})`;
      isSubmitting.value = false;
      return;
    }

    // Validasi kesesuaian metode bayar dengan PO
    if (selectedPurchaseOrder.value) {
      if (
        selectedPurchaseOrder.value.metode_pembayaran !== form.value.metode_pembayaran
      ) {
        errors.value.purchase_order_id = `Purchase Order ${selectedPurchaseOrder.value.no_po} tidak sesuai dengan metode pembayaran yang dipilih`;
        isSubmitting.value = false;
        return;
      }

      // Auto-set supplier from PO if not set (dropdown sudah ter-filter, jadi tidak perlu validasi)
      if (
        form.value.metode_pembayaran === "Transfer" &&
        !selectedSupplierId.value &&
        (selectedPurchaseOrder.value as any).supplier_id
      ) {
        selectedSupplierId.value = (selectedPurchaseOrder.value as any).supplier_id.toString();
      } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
        if (
          (selectedPurchaseOrder.value.no_giro?.toString() ?? "") !==
          (form.value.no_giro?.toString() ?? "")
        ) {
          errors.value.purchase_order_id = `Purchase Order ${selectedPurchaseOrder.value.no_po} tidak sesuai dengan No. Cek/Giro yang dipilih`;
          isSubmitting.value = false;
          return;
        }
      } else if (
        form.value.metode_pembayaran === "Kredit" &&
        (form.value as any).no_kartu_kredit
      ) {
        if (
          (selectedPurchaseOrder.value as any).no_kartu_kredit !==
          (form.value as any).no_kartu_kredit
        ) {
          errors.value.purchase_order_id = `Purchase Order ${selectedPurchaseOrder.value.no_po} tidak sesuai dengan Kartu Kredit yang dipilih`;
          isSubmitting.value = false;
          return;
        }
      }
    }

    // Field wajib hanya berlaku kalau send
    const baseRequired: Array<keyof FormData> = ["nominal", "metode_pembayaran"];
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

    let requiredFields: Array<keyof FormData> = [...baseRequired];
    if (form.value.metode_pembayaran === "Transfer")
      requiredFields = requiredFields.concat(transferSendRequired);
    if (form.value.metode_pembayaran === "Cek/Giro")
      requiredFields = requiredFields.concat(cekGiroSendRequired);
    if (form.value.metode_pembayaran === "Kredit")
      requiredFields = requiredFields.concat(kreditSendRequired);

    // Tambahkan cicilan sebagai field wajib untuk PO tipe Lainnya
    if (selectedPurchaseOrder.value?.tipe_po === "Lainnya") {
      requiredFields.push("cicilan");
    }

    const missingFields = requiredFields.filter((field) => {
      const value = (form.value as any)[field];

      // Handle currency fields (nominal, cicilan) - check if parsed value is empty or zero
      if (field === 'nominal' || field === 'cicilan') {
        const parsedValue = parseCurrency(value || '');
        return !parsedValue || parsedValue === '0';
      }

      // Handle date fields - check if null or undefined
      if (field === 'tanggal_giro' || field === 'tanggal_cair') {
        return !value;
      }

      // Handle other fields - check if empty string, null, or undefined
      return !value || (typeof value === 'string' && value.trim() === '');
    });

    if (missingFields.length > 0) {
      errors.value = missingFields.reduce((acc, field) => {
        acc[field as string] = "Field ini wajib diisi";
        return acc;
      }, {} as Record<string, string>);
      isSubmitting.value = false;
      return;
    }
  }

  // Payload sama untuk draft & send
  const payload = {
    purchase_order_id: selectedPurchaseOrder.value?.id || null,
    total: parseCurrency(form.value.nominal),
    cicilan: form.value.cicilan ? parseCurrency(form.value.cicilan) : null,
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
      } else {
      }
      emit("close");
      emit("refreshTable");
    },
    onError: (errorBag) => {
      errors.value = errorBag as Record<string, any>;
      if ((errors.value as any).purchase_order_id) {
        errors.value.purchase_order_id = (errors.value as any).purchase_order_id;
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
