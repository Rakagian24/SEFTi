<template>
  <div class="bg-white rounded-lg shadow p-6">
    <form @submit.prevent="onSubmit" novalidate class="space-y-6">
      <!-- Row 1: No. Memo Pembayaran | Purchase Order -->
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
      </div>

      <!-- Row 2: Tanggal | Nominal -->
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

        <!-- Nominal -->
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
      </div>

      <!-- Row 3: Metode Pembayaran | Cicilan/Note -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

        <!-- Cicilan (for PO Lainnya) or Note -->
        <div class="floating-input">
          <input
            v-if="selectedPurchaseOrder?.tipe_po === 'Lainnya'"
            v-model="form.cicilan"
            type="text"
            id="cicilan"
            class="floating-input-field"
            :class="{ 'border-red-500': errors.cicilan }"
            placeholder=" "
            @input="formatCicilan"
          />
          <textarea
            v-else
            v-model="form.note"
            id="note"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label
            :for="selectedPurchaseOrder?.tipe_po === 'Lainnya' ? 'cicilan' : 'note'"
            class="floating-label"
          >
            <template v-if="selectedPurchaseOrder?.tipe_po === 'Lainnya'">
              Cicilan<span class="text-red-500">*</span>
            </template>
            <template v-else>Note</template>
          </label>
          <div
            v-if="selectedPurchaseOrder?.tipe_po === 'Lainnya' && errors.cicilan"
            class="text-red-500 text-xs mt-1"
          >
            {{ errors.cicilan }}
          </div>
        </div>
      </div>

      <!-- Row 4: Nama Supplier/No Cek Giro/Nama Pemilik Kredit | Note (for Lainnya) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Dynamic Left Column based on Metode Bayar -->
        <div>
          <!-- Transfer: Nama Supplier -->
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
              <template #label>Nama Supplier<span class="text-red-500">*</span></template>
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

          <!-- Kredit: Nama Pemilik Kredit -->
          <div v-else-if="form.metode_pembayaran === 'Kredit'">
            <CustomSelect
              :model-value="selectedCreditCardId ?? ''"
              @update:modelValue="(val) => handleSelectCreditCard(val as any)"
              :options="creditCardOptions.map((cc: any) => ({ label: cc.nama_pemilik, value: String(cc.id) }))"
              :searchable="true"
              @search="searchCreditCards"
              placeholder="Pilih Nama Pemilik Kredit"
            >
              <template #label
                >Nama Pemilik Kredit<span class="text-red-500">*</span></template
              >
            </CustomSelect>
            <div v-if="errors.no_kartu_kredit" class="text-red-500 text-xs mt-1">
              {{ errors.no_kartu_kredit }}
            </div>
          </div>
        </div>

        <!-- Note (only for PO Lainnya) -->
        <div v-if="selectedPurchaseOrder?.tipe_po === 'Lainnya'" class="floating-input">
          <textarea
            v-model="form.note"
            id="note_lainnya"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label for="note_lainnya" class="floating-label">Note</label>
        </div>
      </div>

      <!-- Row 5: Nama Rekening/Tanggal Giro/Nama Bank Kredit -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Dynamic Column based on Metode Bayar -->
        <div>
          <!-- Transfer: Nama Rekening -->
          <div v-if="form.metode_pembayaran === 'Transfer'" class="floating-input">
            <CustomSelect
              :model-value="form.bank_supplier_account_id"
              @update:modelValue="(val) => handleBankAccountChange(val as any)"
              :options="selectedSupplierBankAccounts.map((a: any) => ({ label: a.nama_rekening, value: String(a.id) }))"
              :disabled="selectedSupplierBankAccounts.length === 0"
              placeholder="Pilih Rekening"
              :error="errors.bank_supplier_account_id"
            >
              <template #label>Nama Rekening<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.bank_supplier_account_id" class="text-red-500 text-xs mt-1">
              {{ errors.bank_supplier_account_id }}
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

      <!-- Row 6: No Rekening/Tanggal Cair/No Kredit -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Dynamic Column based on Metode Bayar -->
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
          {{ editData?.status === "Rejected" ? "Kirim Ulang" : "Kirim" }}
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

    <!-- Confirmation Dialog -->
    <ConfirmDialog
      :show="showConfirmDialog"
      message="Apakah Anda yakin ingin mengirim Memo Pembayaran ini?"
      @confirm="onConfirmSubmit"
      @cancel="onCancelSubmit"
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted, watch } from "vue";
import { router } from "@inertiajs/vue3";
import CustomSelect from "../ui/CustomSelect.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import PurchaseOrderSelection from "./PurchaseOrderSelection.vue";
import TerminSummaryDisplay from "../purchase-orders/TerminSummaryDisplay.vue";
import ConfirmDialog from "../ui/ConfirmDialog.vue";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";
import axios from "axios";
import { format } from "date-fns";

// ============================================
// INTERFACES
// ============================================
interface Perihal {
  id: number;
  nama: string;
}

interface Bank {
  id: number;
  nama_bank: string;
}

interface PurchaseOrder {
  id: number;
  no_po: string;
  perihal?: Perihal | null;
  perihal_id?: number | null;
  total?: number;
  metode_pembayaran?: string;
  bank_id?: number | null;
  nama_rekening?: string;
  no_rekening?: string;
  no_giro?: string;
  status?: string;
  supplier_id?: number | null;
  no_kartu_kredit?: string;
  tanggal_giro?: string | null;
  tanggal_cair?: string | null;
  tipe_po?: string;
  termin_id?: number | null;
  termin?: any;
  credit_card_id?: number | null;
  bank_supplier_account_id?: number | null;
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
  bank_supplier_account_id?: number | null;
  supplier_id?: number | null;
  credit_card_id?: number | null;
  nama_rekening?: string;
  no_rekening?: string;
  no_giro?: string;
  no_kartu_kredit?: string;
  tanggal_giro?: string | null;
  tanggal_cair?: string | null;
  keterangan?: string;
  purchase_order?: PurchaseOrder;
  purchase_orders?: PurchaseOrder[];
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
  bank_supplier_account_id: string;
  supplier_id?: string;
  nama_rekening: string;
  no_rekening: string;
  no_giro: string;
  no_kartu_kredit?: string;
  tanggal_giro: Date | null;
  tanggal_cair: Date | null;
  note: string;
}

interface GiroOption {
  label: string;
  value: string;
  tanggal_giro?: string;
  tanggal_cair?: string;
}

// ============================================
// PROPS & EMITS
// ============================================
const props = defineProps<{
  editData?: EditData;
  departments?: any[];
  purchaseOrders?: PurchaseOrder[];
  banks?: Bank[];
  giroNumbers: any[];
}>();

const emit = defineEmits(["close", "refreshTable"]);

// ============================================
// STATE MANAGEMENT
// ============================================
const form = ref<FormData>({
  no_mb: "",
  tanggal: "",
  purchase_order_id: "",
  nominal: "",
  cicilan: "",
  metode_pembayaran: "Transfer",
  bank_id: "",
  bank_supplier_account_id: "",
  nama_rekening: "",
  no_rekening: "",
  no_giro: "",
  no_kartu_kredit: "",
  tanggal_giro: null,
  tanggal_cair: null,
  note: "",
});

// Control flags
const isInitializing = ref(false);
const isUpdatingFromPO = ref(false);
const isSubmitting = ref(false);
const isLoadingDependencies = ref(false);

// UI State
const errors = ref<Record<string, any>>({});
const loadingErrors = ref<Record<string, string>>({});
const showPurchaseOrderModal = ref(false);
const showConfirmDialog = ref(false);

// Purchase Order State
const selectedPurchaseOrder = ref<PurchaseOrder | null>(null);
const dynamicPurchaseOrders = ref<PurchaseOrder[]>([]);
const purchaseOrderSearchInfo = ref<{ total_count?: number; filter_info?: any }>({});

// Transfer State
const selectedSupplierId = ref<string | null>(null);
const supplierOptions = ref<Array<{ label: string; value: string }>>([]);
const selectedSupplierBankAccounts = ref<any[]>([]);

// Credit Card State
const selectedCreditCardId = ref<string | null>(null);
const creditCardOptions = ref<any[]>([]);
const selectedCreditCardBankName = ref<string>("");

// Giro State
const giroOptions = ref<GiroOption[]>([]);

// Timeouts
let supplierSearchTimeout: ReturnType<typeof setTimeout>;
let poSearchTimeout: ReturnType<typeof setTimeout>;
let creditCardSearchTimeout: ReturnType<typeof setTimeout>;
// let giroSearchTimeout: ReturnType<typeof setTimeout>;

// ============================================
// COMPUTED PROPERTIES
// ============================================
const isEditing = computed(
  () => !!props.editData && ["Draft", "Rejected"].includes(props.editData?.status || "")
);

const displayTanggal = computed(() => {
  if (props.editData?.tanggal) {
    try {
      return format(new Date(props.editData.tanggal), "dd-MM-yyyy");
    } catch {
      return props.editData.tanggal;
    }
  }
  try {
    return format(new Date(), "dd-MM-yyyy");
  } catch {
    return "";
  }
});

const purchaseOrderOptions = computed(() => {
  const source = Array.isArray(dynamicPurchaseOrders.value)
    ? dynamicPurchaseOrders.value
    : [];

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

const availablePurchaseOrders = computed<PurchaseOrder[]>(() => {
  return dynamicPurchaseOrders.value || [];
});

const metodePembayaranOptions = computed(() => [
  { label: "Transfer", value: "Transfer" },
  { label: "Kredit", value: "Kredit" },
]);

// ============================================
// HELPER FUNCTIONS
// ============================================
function isFieldLocked(): boolean {
  if (!props.editData) return false;
  const status = props.editData.status;
  return status !== "Draft" && status !== "Rejected";
}

function canSelectPurchaseOrder(): boolean {
  switch (form.value.metode_pembayaran) {
    case "Transfer":
      return !!selectedSupplierId.value || !!form.value.purchase_order_id;
    case "Cek/Giro":
      return !!form.value.no_giro;
    case "Kredit":
      return !!selectedCreditCardId.value;
    default:
      return false;
  }
}

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
      return form.value.no_kartu_kredit
        ? "Pilih Purchase Order dengan Kartu Kredit ini"
        : "Pilih Kartu Kredit terlebih dahulu";
    default:
      return "Pilih Purchase Order (opsional)";
  }
}

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
      return form.value.no_kartu_kredit
        ? "Pilih Purchase Order dengan Kartu Kredit ini"
        : "Pilih Kartu Kredit terlebih dahulu";
    default:
      return "Tidak ada Purchase Order yang tersedia";
  }
}

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
      if (!form.value.no_kartu_kredit) {
        return "Pilih Kartu Kredit terlebih dahulu untuk melihat Purchase Order yang tersedia";
      }
      return "Tidak ada Purchase Order yang disetujui dengan Kartu Kredit yang dipilih";
    default:
      return "Tidak ada Purchase Order yang tersedia";
  }
}

function getSelectedPurchaseOrdersTotal(): number {
  if (selectedPurchaseOrder.value) {
    const val = Number(selectedPurchaseOrder.value.total);
    return isNaN(val) ? 0 : val;
  }
  return 0;
}

// function calculateNominal(): number {
//   // Priority 1: Edit data total (untuk preserve user input)
//   if (props.editData?.total) return props.editData.total;

//   // Priority 2: Selected PO total
//   if (selectedPurchaseOrder.value?.total) return selectedPurchaseOrder.value.total;

//   // Priority 3: Zero
//   return 0;
// }

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

// ============================================
// API CALLS - SUPPLIERS
// ============================================
async function fetchSuppliers(query?: string) {
  try {
    loadingErrors.value.suppliers = "";
    const { data } = await axios.get("/memo-pembayaran/suppliers/options", {
      params: { search: query || "", per_page: 200 },
      withCredentials: true,
    });
    const list = Array.isArray(data?.data) ? data.data : [];
    supplierOptions.value = list.map((s: any) => ({
      label: s.nama_supplier,
      value: String(s.id),
    }));
  } catch (error) {
    loadingErrors.value.suppliers = "Gagal memuat data supplier";
    console.error("Error fetching suppliers:", error);
    supplierOptions.value = [];
  }
}

function searchSuppliers(query: string) {
  clearTimeout(supplierSearchTimeout);
  supplierSearchTimeout = setTimeout(() => fetchSuppliers(query), 300);
}

// ============================================
// API CALLS - PURCHASE ORDERS
// ============================================
async function searchPurchaseOrders(query: string) {
  clearTimeout(poSearchTimeout);
  poSearchTimeout = setTimeout(async () => {
    try {
      loadingErrors.value.purchaseOrders = "";
      const params: any = { search: query, per_page: 20 };

      if (form.value.metode_pembayaran) {
        params.metode_pembayaran = form.value.metode_pembayaran;
      }

      if (form.value.metode_pembayaran === "Transfer" && selectedSupplierId.value) {
        params.supplier_id = selectedSupplierId.value;
      } else if (form.value.metode_pembayaran === "Cek/Giro" && form.value.no_giro) {
        params.no_giro = form.value.no_giro;
      } else if (form.value.metode_pembayaran === "Kredit" && form.value.no_kartu_kredit) {
        params.no_kartu_kredit = form.value.no_kartu_kredit;
      }

      const { data } = await axios.get("/memo-pembayaran/purchase-orders/search", {
        params,
        withCredentials: true,
      });

      if (data && data.success) {
        dynamicPurchaseOrders.value = data.data || [];
        purchaseOrderSearchInfo.value = {
          total_count: data.total_count,
          filter_info: data.filter_info,
        };
      }
    } catch (error) {
      loadingErrors.value.purchaseOrders = "Gagal memuat Purchase Orders";
      console.error("Error searching PO:", error);
      dynamicPurchaseOrders.value = [];
    }
  }, 300);
}

function onPurchaseOrderSearch(query: string) {
  searchPurchaseOrders(query);
}

// ============================================
// API CALLS - CREDIT CARDS
// ============================================
async function loadCreditCards() {
  try {
    loadingErrors.value.creditCards = "";
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
  } catch (error) {
    loadingErrors.value.creditCards = "Gagal memuat data kartu kredit";
    console.error("Error loading credit cards:", error);
    creditCardOptions.value = [];
  }
}

function searchCreditCards(query: string) {
  clearTimeout(creditCardSearchTimeout);
  creditCardSearchTimeout = setTimeout(async () => {
    try {
      loadingErrors.value.creditCards = "";
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
    } catch (error) {
      loadingErrors.value.creditCards = "Gagal mencari kartu kredit";
      console.error("Error searching credit cards:", error);
      creditCardOptions.value = [];
    }
  }, 300);
}

// ============================================
// API CALLS - GIRO
// ============================================
async function loadGiroNumbers() {
  try {
    loadingErrors.value.giro = "";
    const { data } = await axios.get("/memo-pembayaran/giro-numbers", {
      headers: { Accept: "application/json" },
      params: { per_page: 200 },
      withCredentials: true,
    });
    giroOptions.value = Array.isArray(data?.data) ? data.data : [];
  } catch (error) {
    loadingErrors.value.giro = "Gagal memuat nomor giro";
    console.error("Error loading giro numbers:", error);
    giroOptions.value = [];
  }
}

// function searchGiroNumbers(query: string) {
//   clearTimeout(giroSearchTimeout);
//   giroSearchTimeout = setTimeout(async () => {
//     try {
//       loadingErrors.value.giro = "";
//       const { data } = await axios.get("/memo-pembayaran/giro-numbers", {
//         headers: { Accept: "application/json" },
//         params: { search: query, per_page: 100 },
//         withCredentials: true,
//       });
//       giroOptions.value = Array.isArray(data?.data) ? data.data : [];
//     } catch (error) {
//       loadingErrors.value.giro = "Gagal mencari nomor giro";
//       console.error("Error searching giro numbers:", error);
//       giroOptions.value = [];
//     }
//   }, 300);
// }

// ============================================
// CENTRALIZED PO SELECTION
// ============================================
function selectPurchaseOrder(po: PurchaseOrder, skipValidation = false) {
  if (!skipValidation && !canSelectPurchaseOrder()) {
    console.warn("Cannot select PO: validation failed");
    return;
  }

  // Start updating flag
  isUpdatingFromPO.value = true;

  try {
    // Set PO selection
    selectedPurchaseOrder.value = po;
    form.value.purchase_order_id = po.id.toString();

    // Calculate and set nominal with priority
    const nominalValue = props.editData?.total || po.total || 0;
    form.value.nominal = formatCurrency(nominalValue);

    // Set metode pembayaran
    form.value.metode_pembayaran = po.metode_pembayaran || "Transfer";

    // Apply PO-specific fields
    applyPurchaseOrderFields(po);

    // Load dependent data based on metode pembayaran
    loadDependentData(po);
  } finally {
    // Always reset flag
    isUpdatingFromPO.value = false;
  }
}

function applyPurchaseOrderFields(po: PurchaseOrder) {
  switch (po.metode_pembayaran) {
    case "Transfer":
      // Set bank_supplier_account_id first (dropdown value)
      if (po.bank_supplier_account_id) {
        form.value.bank_supplier_account_id = String(po.bank_supplier_account_id);
      }
      // Set related fields
      if (po.bank_id) form.value.bank_id = String(po.bank_id);
      if (po.nama_rekening) form.value.nama_rekening = po.nama_rekening;
      if (po.no_rekening) form.value.no_rekening = po.no_rekening;
      break;

    case "Cek/Giro":
      if (po.no_giro) form.value.no_giro = po.no_giro;
      if (po.tanggal_giro) form.value.tanggal_giro = new Date(po.tanggal_giro);
      if (po.tanggal_cair) form.value.tanggal_cair = new Date(po.tanggal_cair);
      break;

    case "Kredit":
      if (po.no_kartu_kredit) form.value.no_kartu_kredit = po.no_kartu_kredit;
      if (po.bank_id) form.value.bank_id = String(po.bank_id);
      break;
  }
}

async function loadDependentData(po: PurchaseOrder) {
  isLoadingDependencies.value = true;

  try {
    switch (po.metode_pembayaran) {
      case "Transfer":
        if (po.supplier_id && !selectedSupplierId.value) {
          selectedSupplierId.value = String(po.supplier_id);
          form.value.supplier_id = String(po.supplier_id);
          // Load bank accounts first
          await loadSupplierBankAccounts(String(po.supplier_id));

          // After loading accounts, ensure the PO's bank account is selected
          if (po.bank_supplier_account_id) {
            form.value.bank_supplier_account_id = String(po.bank_supplier_account_id);
            // Trigger the bank account change to populate related fields
            handleBankAccountChange(String(po.bank_supplier_account_id));
          }
        } else if (selectedSupplierId.value && po.bank_supplier_account_id) {
          // If supplier already selected, just set the bank account
          form.value.bank_supplier_account_id = String(po.bank_supplier_account_id);
          handleBankAccountChange(String(po.bank_supplier_account_id));
        }
        break;

      case "Cek/Giro":
        if (po.no_giro && !form.value.no_giro) {
          form.value.no_giro = po.no_giro;
          await loadGiroDetails(po.no_giro);
        }
        break;

      case "Kredit":
        if (po.credit_card_id && !selectedCreditCardId.value) {
          selectedCreditCardId.value = String(po.credit_card_id);
          await loadCreditCardDetails(String(po.credit_card_id));
        }
        break;
    }
  } finally {
    isLoadingDependencies.value = false;
  }
}

// ============================================
// TRANSFER - SUPPLIER HANDLING
// ============================================
async function handleSupplierChange(supplierId: string) {
  form.value.supplier_id = supplierId || "";
  selectedSupplierId.value = supplierId || null;

  // Clear bank account fields
  form.value.bank_id = "";
  form.value.bank_supplier_account_id = "";
  form.value.nama_rekening = "";
  form.value.no_rekening = "";
  selectedSupplierBankAccounts.value = [];

  // Clear PO only if not initializing or updating from PO
  if (!isInitializing.value && !isUpdatingFromPO.value) {
    clearPurchaseOrderSelection();
  }

  if (!supplierId) return;

  await loadSupplierBankAccounts(supplierId);

  // Refresh PO list if Transfer method
  if (form.value.metode_pembayaran === "Transfer" && !isUpdatingFromPO.value) {
    searchPurchaseOrders("");
  }
}

async function loadSupplierBankAccounts(supplierId: string) {
  try {
    const response = await axios.post("/purchase-orders/supplier-bank-accounts", {
      supplier_id: supplierId,
    });
    const { bank_accounts } = response.data || {};
    selectedSupplierBankAccounts.value = Array.isArray(bank_accounts)
      ? bank_accounts
      : [];

    // Auto-fill only if:
    // 1. Only one account available
    // 2. No PO selected (to avoid overwriting PO data)
    // 3. Not in edit mode with existing data
    if (
      selectedSupplierBankAccounts.value.length === 1 &&
      !selectedPurchaseOrder.value &&
      !props.editData
    ) {
      const account = selectedSupplierBankAccounts.value[0];
      form.value.bank_supplier_account_id = String(account.id);
      // Trigger handleBankAccountChange to populate all related fields
      handleBankAccountChange(String(account.id));
    }
  } catch (error) {
    console.error("Error loading supplier bank accounts:", error);
    selectedSupplierBankAccounts.value = [];
  }
}

function handleBankAccountChange(accountId: string) {
  form.value.bank_supplier_account_id = accountId;
  form.value.bank_id = "";
  form.value.nama_rekening = "";
  form.value.no_rekening = "";

  if (!accountId) return;

  const account = selectedSupplierBankAccounts.value.find(
    (a: any) => String(a.id) === String(accountId)
  );

  if (account) {
    form.value.bank_id = String(account.bank_id);
    form.value.nama_rekening = account.nama_rekening || "";
    const bankAbbreviation = account.bank_singkatan || "";
    form.value.no_rekening = account.no_rekening
      ? `${account.no_rekening}${bankAbbreviation ? ` (${bankAbbreviation})` : ""}`
      : "";
  }
}

// ============================================
// KREDIT - CREDIT CARD HANDLING
// ============================================
async function handleSelectCreditCard(creditCardId: string) {
  selectedCreditCardId.value = creditCardId || null;
  form.value.no_kartu_kredit = "";
  form.value.bank_id = "";
  selectedCreditCardBankName.value = "";

  // Clear PO only if not initializing or updating from PO
  if (!isInitializing.value && !isUpdatingFromPO.value) {
    clearPurchaseOrderSelection();
  }

  if (!creditCardId) return;

  await loadCreditCardDetails(creditCardId);

  // Refresh PO list if Kredit method
  if (form.value.metode_pembayaran === "Kredit" && !isUpdatingFromPO.value) {
    searchPurchaseOrders("");
  }
}

async function loadCreditCardDetails(creditCardId: string) {
  const cc = creditCardOptions.value.find(
    (c: any) => String(c.id) === String(creditCardId)
  );

  if (cc) {
    form.value.no_kartu_kredit = cc.no_kartu_kredit || "";
    form.value.bank_id = cc.bank_id ? String(cc.bank_id) : "";
    selectedCreditCardBankName.value = cc.bank?.nama_bank
      ? cc.bank?.singkatan
        ? `${cc.bank.nama_bank} (${cc.bank.singkatan})`
        : cc.bank.nama_bank
      : "";
  } else if (selectedPurchaseOrder.value?.metode_pembayaran === "Kredit") {
    // Fallback to PO data if credit card not found
    const selectedPO = selectedPurchaseOrder.value;
    if (selectedPO.no_kartu_kredit) {
      form.value.no_kartu_kredit = selectedPO.no_kartu_kredit;
    }
    if (selectedPO.bank_id) {
      form.value.bank_id = String(selectedPO.bank_id);
    }
  }
}

// ============================================
// CEK/GIRO HANDLING
// ============================================
async function handleGiroChange(giroNumber?: string) {
  form.value.no_giro = giroNumber ?? "";

  // Clear PO only if not initializing or updating from PO
  if (!isInitializing.value && !isUpdatingFromPO.value) {
    clearPurchaseOrderSelection();
  }

  if (!giroNumber) {
    form.value.tanggal_giro = null;
    form.value.tanggal_cair = null;
    return;
  }

  await loadGiroDetails(giroNumber);

  // Refresh PO list if Cek/Giro method
  if (form.value.metode_pembayaran === "Cek/Giro" && !isUpdatingFromPO.value) {
    searchPurchaseOrders("");
  }
}

async function loadGiroDetails(giroNumber: string) {
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
  } else if (selectedPurchaseOrder.value?.metode_pembayaran === "Cek/Giro") {
    // Fallback to PO data
    const selectedPO = selectedPurchaseOrder.value;
    if (selectedPO.tanggal_giro) {
      form.value.tanggal_giro = new Date(selectedPO.tanggal_giro);
    }
    if (selectedPO.tanggal_cair) {
      form.value.tanggal_cair = new Date(selectedPO.tanggal_cair);
    }
  }
}

// ============================================
// METODE PEMBAYARAN CHANGE
// ============================================
function onMetodePembayaranChange() {
  // Clear method-specific fields
  if (form.value.metode_pembayaran !== "Cek/Giro") {
    form.value.no_giro = "";
    form.value.tanggal_giro = null;
    form.value.tanggal_cair = null;
  }

  if (form.value.metode_pembayaran !== "Transfer") {
    selectedSupplierId.value = null;
    selectedSupplierBankAccounts.value = [];
    form.value.bank_id = "";
    form.value.bank_supplier_account_id = "";
    form.value.nama_rekening = "";
    form.value.no_rekening = "";
  }

  if (form.value.metode_pembayaran !== "Kredit") {
    selectedCreditCardId.value = null;
    form.value.no_kartu_kredit = "";
  }

  // Clear PO selection
  clearPurchaseOrderSelection();

  // Load method-specific data
  if (form.value.metode_pembayaran === "Kredit") {
    loadCreditCards();
  } else if (form.value.metode_pembayaran === "Cek/Giro") {
    loadGiroNumbers();
  }

  // Fetch filtered POs if criteria ready
  if (canSelectPurchaseOrder()) {
    searchPurchaseOrders("");
  }
}

// ============================================
// PO SELECTION HANDLERS
// ============================================
function onPurchaseOrderChange() {
  if (!form.value.purchase_order_id) {
    clearPurchaseOrderSelection();
    return;
  }

  if (!canSelectPurchaseOrder()) {
    form.value.purchase_order_id = "";
    return;
  }

  const selectedPO = availablePurchaseOrders.value.find(
    (po) => po.id.toString() === form.value.purchase_order_id
  );

  if (!selectedPO) return;

  // Skip validation if editing and this is the original PO
  const isOriginalPO = isEditing.value &&
    form.value.purchase_order_id === String(props.editData?.purchase_order_id || "");

  if (isOriginalPO) {
    selectPurchaseOrder(selectedPO, true);
    return;
  }

  // Validate PO matches filter criteria
  if (!validatePurchaseOrder(selectedPO)) {
    form.value.purchase_order_id = "";
    return;
  }

  selectPurchaseOrder(selectedPO);
}

function addPurchaseOrder(po: PurchaseOrder) {
  if (!canSelectPurchaseOrder()) {
    return;
  }

  // Validate PO matches filter criteria
  if (!validatePurchaseOrder(po)) {
    return;
  }

  selectPurchaseOrder(po);
  showPurchaseOrderModal.value = false;
}

function removePurchaseOrder() {
  clearPurchaseOrderSelection();
  fetchSuppliers();
}

function clearPurchaseOrderSelection() {
  selectedPurchaseOrder.value = null;
  form.value.purchase_order_id = "";
  dynamicPurchaseOrders.value = [];
  purchaseOrderSearchInfo.value = {};
  form.value.nominal = "";
}

function validatePurchaseOrder(po: PurchaseOrder): boolean {
  // Check if metode pembayaran matches
  if (po.metode_pembayaran !== form.value.metode_pembayaran) {
    return false;
  }

  // Validate based on metode pembayaran
  switch (form.value.metode_pembayaran) {
    case "Transfer":
      // Auto-set supplier if not set
      if (!selectedSupplierId.value && po.supplier_id) {
        selectedSupplierId.value = String(po.supplier_id);
      }
      return true;

    case "Cek/Giro":
      return (po.no_giro?.toString() ?? "") === (form.value.no_giro?.toString() ?? "");

    case "Kredit":
      return po.no_kartu_kredit === form.value.no_kartu_kredit;

    default:
      return false;
  }
}

function openPurchaseOrderModal() {
  if (!canSelectPurchaseOrder()) {
    return;
  }
  showPurchaseOrderModal.value = true;
}

// ============================================
// FORM SUBMISSION
// ============================================
function saveDraft() {
  errors.value = {};
  handleSubmit("draft");
}

function onSubmit() {
  showConfirmDialog.value = true;
}

function onConfirmSubmit() {
  showConfirmDialog.value = false;
  handleSubmit("send");
}

function onCancelSubmit() {
  showConfirmDialog.value = false;
}

function handleSubmit(action: "send" | "draft" = "send") {
  if (isLoadingDependencies.value) {
    errors.value.general = "Mohon tunggu, data sedang dimuat...";
    return;
  }

  isSubmitting.value = true;
  errors.value = {};

  // Validation for "send" action
  if (action === "send") {
    // Validate total
    const selectedTotal = getSelectedPurchaseOrdersTotal();
    const formTotal = Number(parseCurrency(form.value.nominal)) || 0;

    if (selectedTotal > 0 && formTotal > 0 && selectedTotal !== formTotal) {
      errors.value.nominal = `Total Purchase Order (${formatCurrency(
        selectedTotal
      )}) tidak sama dengan nominal yang diinput (${formatCurrency(formTotal)})`;
      isSubmitting.value = false;
      return;
    }

    // Validate PO consistency
    if (selectedPurchaseOrder.value && !validatePurchaseOrder(selectedPurchaseOrder.value)) {
      errors.value.purchase_order_id = `Purchase Order tidak sesuai dengan kriteria yang dipilih`;
      isSubmitting.value = false;
      return;
    }

    // Required fields validation
    const validationResult = validateRequiredFields();
    if (!validationResult.valid) {
      errors.value = validationResult.errors;
      isSubmitting.value = false;
      return;
    }
  }

  // Build payload
  const payload = {
    purchase_order_id: selectedPurchaseOrder.value?.id ||
      (form.value.purchase_order_id ? parseInt(form.value.purchase_order_id) : null),
    total: parseCurrency(form.value.nominal) || 0,
    cicilan: form.value.cicilan ? parseCurrency(form.value.cicilan) : null,
    metode_pembayaran: form.value.metode_pembayaran,
    bank_id: form.value.bank_id || null,
    bank_supplier_account_id: form.value.bank_supplier_account_id || null,
    nama_rekening: form.value.nama_rekening || null,
    no_rekening: form.value.no_rekening || null,
    no_giro: form.value.no_giro || null,
    no_kartu_kredit: form.value.no_kartu_kredit || null,
    tanggal_giro: form.value.tanggal_giro || null,
    tanggal_cair: form.value.tanggal_cair || null,
    keterangan: form.value.note,
    action: action,
  };

  const url = props.editData
    ? `/memo-pembayaran/${props.editData.id}`
    : "/memo-pembayaran";
  const method = props.editData ? "put" : "post";

  router[method](url, payload, {
    onSuccess: (response) => {
      console.log("Submit success response:", response);
      emit("close");
      emit("refreshTable");
    },
    onError: (errorBag) => {
      errors.value = errorBag as Record<string, any>;
      console.error("Submit error:", errors.value);
      isSubmitting.value = false;
    },
    onFinish: () => {
      isSubmitting.value = false;
    },
  });
}

function validateRequiredFields(): { valid: boolean; errors: Record<string, string> } {
  const baseRequired: Array<keyof FormData> = ["nominal", "metode_pembayaran"];
  const transferRequired: Array<keyof FormData> = [
    "bank_supplier_account_id",
    "nama_rekening",
    "no_rekening",
  ];
  const cekGiroRequired: Array<keyof FormData> = [
    "no_giro",
    "tanggal_giro",
    "tanggal_cair",
  ];
  const kreditRequired: Array<keyof FormData> = ["no_kartu_kredit"];

  let requiredFields: Array<keyof FormData> = [...baseRequired];

  if (form.value.metode_pembayaran === "Transfer") {
    requiredFields = requiredFields.concat(transferRequired);
  }
  if (form.value.metode_pembayaran === "Cek/Giro") {
    requiredFields = requiredFields.concat(cekGiroRequired);
  }
  if (form.value.metode_pembayaran === "Kredit") {
    requiredFields = requiredFields.concat(kreditRequired);
  }

  // Add cicilan for PO tipe Lainnya
  if (selectedPurchaseOrder.value?.tipe_po === "Lainnya") {
    requiredFields.push("cicilan");
  }

  const missingFields = requiredFields.filter((field) => {
    const value = form.value[field];

    // Handle currency fields
    if (field === "nominal" || field === "cicilan") {
      const parsedValue = parseCurrency(value as string || "");
      return !parsedValue || parsedValue === "0";
    }

    // Handle date fields
    if (field === "tanggal_giro" || field === "tanggal_cair") {
      return !value;
    }

    // Handle other fields
    return !value || (typeof value === "string" && value.trim() === "");
  });

  if (missingFields.length > 0) {
    const fieldErrors = missingFields.reduce((acc, field) => {
      acc[field as string] = "Field ini wajib diisi";
      return acc;
    }, {} as Record<string, string>);

    return { valid: false, errors: fieldErrors };
  }

  return { valid: true, errors: {} };
}

// ============================================
// WATCHERS
// ============================================
watch(showPurchaseOrderModal, (open) => {
  if (open && (!dynamicPurchaseOrders.value || dynamicPurchaseOrders.value.length === 0)) {
    searchPurchaseOrders("");
  }
});

// Watch for changes in filter criteria
watch(
  [
    () => form.value.metode_pembayaran,
    () => selectedSupplierId.value,
    () => form.value.no_giro,
    () => form.value.no_kartu_kredit,
  ],
  () => {
    // Skip if initializing, updating from PO, or in edit mode with selected PO
    if (isInitializing.value || isUpdatingFromPO.value) {
      return;
    }

    if (props.editData && selectedPurchaseOrder.value) {
      return;
    }

    // Clear PO options when filter criteria change
    clearPurchaseOrderSelection();

    // Fetch filtered POs if criteria are ready
    if (canSelectPurchaseOrder()) {
      searchPurchaseOrders("");
    }
  }
);

// Keep nominal in sync with selected PO (but respect edit data priority)
watch(
  selectedPurchaseOrder,
  () => {
    if (!isInitializing.value && !props.editData?.total) {
      form.value.nominal = formatCurrency(selectedPurchaseOrder.value?.total || 0);
    }

    if (selectedPurchaseOrder.value?.bank_supplier_account_id) {
      form.value.bank_supplier_account_id = String(
        selectedPurchaseOrder.value.bank_supplier_account_id
      );
    }
  },
  { deep: true }
);

// Watch metode pembayaran for loading dependent data
watch(
  () => form.value.metode_pembayaran,
  async (metode) => {
    if (metode === "Kredit") {
      await loadCreditCards();
    } else if (metode === "Cek/Giro") {
      await loadGiroNumbers();
    }
  },
  { immediate: true }
);

// ============================================
// INITIALIZATION
// ============================================
async function initializeForm() {
  isInitializing.value = true;

  try {
    const edit = props.editData;
    if (!edit) return;

    console.log("🔍 DEBUG initializeForm - editData:", edit);

    // Step 1: Find Purchase Order
    const po = await findPurchaseOrder(edit);

    // Step 2: Initialize form data
    initializeFormData(edit, po);

    // Step 3: Load dependencies
    await loadInitialDependencies(edit, po);

    // Step 4: Apply PO data if available
    if (po) {
      selectPurchaseOrder(po, true);
    }
  } catch (error) {
    console.error("Error initializing form:", error);
  } finally {
    isInitializing.value = false;
  }
}

async function findPurchaseOrder(edit: EditData): Promise<PurchaseOrder | undefined> {
  let po: PurchaseOrder | undefined;

  // Priority 1: Direct purchase_order object
  if (edit.purchase_order) {
    console.log("✅ Found purchase_order object:", edit.purchase_order);
    po = edit.purchase_order;
  }
  // Priority 2: Array fallback
  else if (edit.purchase_orders?.length) {
    console.log("✅ Found purchase_orders array:", edit.purchase_orders);
    po = edit.purchase_orders[0];
  }
  // Priority 3: ID-based lookup
  else if (edit.purchase_order_id && props.purchaseOrders) {
    console.log("🔄 Looking up PO by ID:", edit.purchase_order_id);
    po = props.purchaseOrders.find((p) => p.id === Number(edit.purchase_order_id));
    if (po) console.log("✅ Found PO in props:", po);
  }

  // Priority 4: Create placeholder
  if (!po && edit.purchase_order_id) {
    console.warn("⚠️ Creating placeholder PO from editData");
    po = {
      id: Number(edit.purchase_order_id),
      no_po: `PO-${edit.purchase_order_id}`,
      perihal: edit.purchase_orders?.[0]?.perihal || edit.purchase_order?.perihal,
      total: edit.total || 0,
      metode_pembayaran: edit.metode_pembayaran,
      supplier_id: edit.supplier_id,
      bank_id: edit.bank_id,
      nama_rekening: edit.nama_rekening,
      no_rekening: edit.no_rekening,
      no_giro: edit.no_giro,
      no_kartu_kredit: edit.no_kartu_kredit,
      tanggal_giro: edit.tanggal_giro,
      tanggal_cair: edit.tanggal_cair,
      bank_supplier_account_id: edit.bank_supplier_account_id,
      credit_card_id: edit.credit_card_id,
      status: edit.status,
      tipe_po: edit.purchase_order?.tipe_po,
      termin_id: edit.purchase_order?.termin_id,
      termin: edit.purchase_order?.termin,
    };
  }

  return po;
}

function initializeFormData(edit: EditData, po?: PurchaseOrder) {
  form.value = {
    no_mb: edit.no_mb || "",
    tanggal: edit.tanggal || "",
    purchase_order_id: String(edit.purchase_order_id || po?.id || ""),
    nominal: edit.total ? formatCurrency(edit.total) : po?.total ? formatCurrency(po.total) : "0",
    cicilan: edit.cicilan ? formatCurrency(edit.cicilan) : "",
    metode_pembayaran: edit.metode_pembayaran || po?.metode_pembayaran || "Transfer",
    bank_id: String(edit.bank_id || po?.bank_id || ""),
    bank_supplier_account_id: String(edit.bank_supplier_account_id || po?.bank_supplier_account_id || ""),
    supplier_id: String(edit.supplier_id || po?.supplier_id || ""),
    nama_rekening: edit.nama_rekening || po?.nama_rekening || "",
    no_rekening: edit.no_rekening || po?.no_rekening || "",
    no_giro: edit.no_giro || po?.no_giro || "",
    no_kartu_kredit: edit.no_kartu_kredit || po?.no_kartu_kredit || "",
    tanggal_giro: edit.tanggal_giro ? new Date(edit.tanggal_giro) : po?.tanggal_giro ? new Date(po.tanggal_giro) : null,
    tanggal_cair: edit.tanggal_cair ? new Date(edit.tanggal_cair) : po?.tanggal_cair ? new Date(po.tanggal_cair) : null,
    note: edit.keterangan || "",
  };
}

async function loadInitialDependencies(edit: EditData, po?: PurchaseOrder) {
  // Always load suppliers first
  await fetchSuppliers();

  // Load dependencies based on metode pembayaran
  switch (form.value.metode_pembayaran) {
    case "Transfer":
      if (edit.supplier_id || po?.supplier_id) {
        const supplierId = String(edit.supplier_id || po?.supplier_id);
        selectedSupplierId.value = supplierId;
        form.value.supplier_id = supplierId;

        // Ensure supplier in options
        if (!supplierOptions.value.some((o) => o.value === supplierId)) {
          supplierOptions.value.push({
            label: `Supplier ${supplierId}`,
            value: supplierId,
          });
        }

        await loadSupplierBankAccounts(supplierId);
      }
      break;

    case "Kredit":
      await loadCreditCards();
      if (edit.credit_card_id || po?.credit_card_id) {
        const creditCardId = String(edit.credit_card_id || po?.credit_card_id);
        selectedCreditCardId.value = creditCardId;
        await loadCreditCardDetails(creditCardId);
      }
      break;

    case "Cek/Giro":
      await loadGiroNumbers();
      if (edit.no_giro || po?.no_giro) {
        const noGiro = edit.no_giro || po?.no_giro || "";
        form.value.no_giro = noGiro;
        await loadGiroDetails(noGiro);
      }
      break;
  }
}

onMounted(async () => {
  await initializeForm();
  // Initial load of suppliers for create mode
  if (!props.editData) {
    await fetchSuppliers();
  }
});

// ============================================
// CLEANUP
// ============================================
onUnmounted(() => {
  clearTimeout(supplierSearchTimeout);
  clearTimeout(poSearchTimeout);
  clearTimeout(creditCardSearchTimeout);
//   clearTimeout(giroSearchTimeout);
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
