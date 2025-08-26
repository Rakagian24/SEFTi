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

      <!-- Row 2: Purchase Order | Dynamic Right Column (by Metode Bayar) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Purchase Order -->
        <div class="floating-input">
          <div class="flex gap-2">
            <div class="flex-1">
              <CustomSelect
                v-model="form.purchase_order_id"
                :options="purchaseOrderOptions"
                placeholder="Pilih Purchase Order (opsional)"
                :error="errors.purchase_order_id"
                :searchable="true"
                @search="searchPurchaseOrders"
                @change="onPurchaseOrderChange"
              />
            </div>
            <button
              type="button"
              @click="showPurchaseOrderModal = true"
              class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
            >
              +
            </button>
          </div>
          <p class="text-sm text-gray-500 mt-1">
            Bisa memilih lebih dari satu Purchase Order
          </p>
        </div>

        <!-- Dynamic: Right column -->
        <div>
          <!-- Transfer: Supplier dropdown then Bank by supplier accounts -->
          <div v-if="form.metode_pembayaran === 'Transfer'">
            <div class="mb-4">
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
            <div>
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
          </div>

          <!-- Cek/Giro: No. Giro dari PO -->
          <div v-else-if="form.metode_pembayaran === 'Cek/Giro'">
            <CustomSelect
              :model-value="form.no_giro ?? ''"
              @update:modelValue="(val) => (form.no_giro = val as any)"
              :options="giroOptions"
              placeholder="Pilih No. Cek/Giro dari PO"
              :error="errors.no_giro"
            >
              <template #label>No. Cek/Giro<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.no_giro" class="text-red-500 text-xs mt-1">
              {{ errors.no_giro }}
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

            <!-- Nama Bank (auto-filled) -->
            <div class="mt-4 floating-input">
              <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled">
                {{ selectedCreditCardBankName || '-' }}
              </div>
              <label class="floating-label">Nama Bank</label>
            </div>

            <!-- No Kartu Kredit (auto-filled) -->
            <div class="mt-4 floating-input">
              <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled">
                {{ form.no_kartu_kredit || '-' }}
              </div>
              <label class="floating-label">No Kartu Kredit</label>
            </div>
          </div>
        </div>
      </div>

      <!-- Selected Purchase Orders Display (full width) -->
      <div v-if="selectedPurchaseOrders.length > 0" class="space-y-2">
        <label class="block text-sm font-medium text-gray-700">
          Purchase Order yang Dipilih:
        </label>
        <div class="space-y-2">
          <div
            v-for="po in selectedPurchaseOrders"
            :key="po.id"
            class="flex items-center justify-between p-3 bg-gray-50 rounded-md"
          >
            <div>
              <span class="font-medium">{{ po.no_po }}</span>
              <span class="text-gray-500 ml-2">- {{ po.perihal?.nama || "" }}</span>
            </div>
            <button
              type="button"
              @click="removePurchaseOrder(po.id)"
              class="text-red-600 hover:text-red-800"
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

      <!-- Row 3: Tanggal | No Rekening / Tanggal Giro -->
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

        <!-- Right column dynamic -->
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
        </div>
      </div>

      <!-- Row 4: Perihal | Tanggal Cair -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="floating-input">
          <CustomSelect
            v-model="form.perihal_id"
            :options="perihalOptions"
            placeholder="Pilih perihal"
            :error="errors.perihal_id"
          >
            <template #label>Perihal<span class="text-red-500">*</span></template>
          </CustomSelect>
          <div v-if="errors.perihal_id" class="text-red-500 text-xs mt-1">
            {{ errors.perihal_id }}
          </div>
        </div>

        <div v-if="form.metode_pembayaran === 'Cek/Giro'" class="floating-input">
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
            class="md:max-w-md"
          />
          <div v-if="errors.tanggal_cair" class="text-red-500 text-xs mt-1">
            {{ errors.tanggal_cair }}
          </div>
        </div>
      </div>

      <!-- Row 5: Nominal (full width) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
        </div>
      </div>
      <!-- Row 6: Note (full width) -->
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
          <span v-else>Kirim</span>
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

    <!-- Purchase Order Selection Modal -->
    <Dialog :open="showPurchaseOrderModal" @close="showPurchaseOrderModal = false">
      <DialogContent class="max-w-4xl">
        <DialogHeader>
          <DialogTitle>Pilih Purchase Order</DialogTitle>
        </DialogHeader>
        <div class="max-h-96 overflow-y-auto">
          <div class="space-y-2">
            <div class="sticky top-0 bg-white p-2 border-b border-gray-200">
              <input
                v-model="modalSearchQuery"
                @input="onModalSearch"
                type="text"
                placeholder="Cari PO (No atau Perihal)..."
                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
            <div
              v-for="po in availablePurchaseOrders"
              :key="po.id"
              class="flex items-center justify-between p-3 border border-gray-200 rounded-md hover:bg-gray-50"
            >
              <div class="flex-1">
                <div class="font-medium">{{ po.no_po }}</div>
                <div class="text-sm text-gray-500">{{ po.perihal?.nama || "" }}</div>
                <div class="text-sm text-gray-500">
                  Total: {{ formatCurrency(po.total ?? 0) }}
                </div>
              </div>
              <button
                type="button"
                @click="addPurchaseOrder(po)"
                :disabled="isPurchaseOrderSelected(po.id)"
                class="px-3 py-1 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 disabled:bg-gray-300 disabled:cursor-not-allowed"
              >
                {{ isPurchaseOrderSelected(po.id) ? "Dipilih" : "Pilih" }}
              </button>
            </div>
          </div>
        </div>
        <DialogFooter>
          <button
            type="button"
            @click="showPurchaseOrderModal = false"
            class="px-4 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50"
          >
            Tutup
          </button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { router } from "@inertiajs/vue3";
import CustomSelect from "../ui/CustomSelect.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from "../ui/dialog";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";
import axios from "axios";
import { format } from "date-fns";

interface Perihal {
  id: number;
  nama: string;
}

interface Bank {
  id: number;
  nama_bank: string;
}
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

interface PurchaseOrder {
  id: number;
  no_po: string;
  perihal_id?: number | null;
  perihal?: Perihal | null;
  total?: number;
  metode_pembayaran?: string;
  bank_id?: number | null;
  nama_rekening?: string;
  no_rekening?: string;
  no_giro?: string;
}

interface EditData {
  id: number;
  no_mb?: string;
  tanggal?: string;
  purchase_order_id?: number | null;
  perihal_id?: number | null;
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
}

interface FormData {
  no_mb: string;
  tanggal: string;
  purchase_order_id: string;
  perihal_id: string;
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
  perihals?: Perihal[];
  purchaseOrders?: PurchaseOrder[];
  banks?: Bank[];
}>();

const emit = defineEmits(["close", "refreshTable"]);

const form = ref<FormData>({
  no_mb: "",
  tanggal: "",
  purchase_order_id: "",
  perihal_id: "",
  nominal: "",
  metode_pembayaran: "",
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

// When opening modal, load initial list if dynamic cache empty
watch(showPurchaseOrderModal, (open) => {
  if (
    open &&
    (!dynamicPurchaseOrders.value || dynamicPurchaseOrders.value.length === 0)
  ) {
    searchPurchaseOrders("");
  }
});

// Initialize form with edit data
onMounted(() => {
  if (props.editData) {
    form.value = {
      no_mb: props.editData.no_mb || "",
      tanggal: props.editData.tanggal || "",
      purchase_order_id: props.editData.purchase_order_id?.toString() || "",
      perihal_id: props.editData.perihal_id?.toString() || "",
      nominal: formatCurrency(props.editData.total || 0),
      metode_pembayaran: props.editData.metode_pembayaran || "",
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
    }
  }
});

const perihalOptions = computed(() => {
  return (
    props.perihals?.map((perihal) => ({
      label: perihal.nama,
      value: perihal.id.toString(),
    })) || []
  );
});

const purchaseOrderOptions = computed(() => {
  const source =
    dynamicPurchaseOrders.value && dynamicPurchaseOrders.value.length > 0
      ? dynamicPurchaseOrders.value
      : props.purchaseOrders || [];
  return source.map((po: any) => ({
    label: `${po.no_po} - ${po.perihal?.nama || ""}`,
    value: po.id.toString(),
  }));
});

// Debounced search for purchase orders (approved) for dropdown
let poSearchTimeout: ReturnType<typeof setTimeout>;
function searchPurchaseOrders(query: string) {
  clearTimeout(poSearchTimeout);
  poSearchTimeout = setTimeout(async () => {
    try {
      const { data } = await axios.get("/memo-pembayaran/purchase-orders/search", {
        params: { search: query, per_page: 20 },
      });
      if (data && data.success) {
        // Overwrite props-like list by emitting event is not possible; so maintain a local cache for modal
        dynamicPurchaseOrders.value = data.data || [];
      }
    } catch {
      // silent
    }
  }, 300);
}

// Maintain dynamic list for modal (fallbacks to props when empty)
const dynamicPurchaseOrders = ref<PurchaseOrder[]>([]);
const availablePurchaseOrders = computed<PurchaseOrder[]>(() => {
  return dynamicPurchaseOrders.value && dynamicPurchaseOrders.value.length > 0
    ? dynamicPurchaseOrders.value
    : props.purchaseOrders || [];
});

// Modal search handling
const modalSearchQuery = ref("");
let modalSearchTimeout: ReturnType<typeof setTimeout>;
function onModalSearch() {
  clearTimeout(modalSearchTimeout);
  modalSearchTimeout = setTimeout(() => {
    searchPurchaseOrders(modalSearchQuery.value);
  }, 300);
}

// Note: Bank options for Transfer are sourced from supplier bank accounts, not master banks

const metodePembayaranOptions = computed(() => [
  { label: "Transfer", value: "Transfer" },
  { label: "Cek/Giro", value: "Cek/Giro" },
  { label: "Kredit", value: "Kredit" },
]);

// Transfer: supplier and bank accounts
const selectedSupplierId = ref<string | null>(null);
const supplierOptions = ref<Array<{ label: string; value: string }>>([]);
const selectedSupplierBankAccounts = ref<any[]>([]);
async function fetchSuppliers(query?: string) {
  try {
    const { data } = await axios.get("/memo-pembayaran/suppliers/options", {
      params: { search: query || "", per_page: 200 },
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

async function handleSupplierChange(supplierId: string) {
  selectedSupplierId.value = supplierId || null;
  form.value.bank_id = "";
  form.value.nama_rekening = "";
  form.value.no_rekening = "";
  selectedSupplierBankAccounts.value = [];
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
          params: { per_page: 200 },
        });
        giroOptions.value = Array.isArray(data?.data) ? data.data : [];
      } catch {
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
  }
}

// Giro options from API
const giroOptions = ref<Array<{ label: string; value: string }>>([]);

function formatNominal() {
  const value = form.value.nominal.replace(/[^\d]/g, "");
  if (value) {
    form.value.nominal = formatCurrency(parseInt(value));
  }
}

function onPurchaseOrderChange() {
  if (!form.value.purchase_order_id) return;
  const selectedPO = props.purchaseOrders?.find(
    (po) => po.id.toString() === form.value.purchase_order_id
  );
  if (!selectedPO) return;

  // Add to selected list if not already there
  if (!isPurchaseOrderSelected(selectedPO.id)) {
    selectedPurchaseOrders.value.push(selectedPO);
  }

  // Auto-fill fields from PO
  form.value.perihal_id = selectedPO.perihal_id?.toString() || "";
  form.value.nominal = formatCurrency(selectedPO.total || 0);
  form.value.metode_pembayaran = selectedPO.metode_pembayaran || "";
  form.value.bank_id = selectedPO.bank_id?.toString() || "";
  form.value.nama_rekening = selectedPO.nama_rekening || "";
  form.value.no_rekening = selectedPO.no_rekening || "";
  if ((selectedPO as any).no_giro && selectedPO.metode_pembayaran === "Cek/Giro") {
    form.value.no_giro = (selectedPO as any).no_giro;
  }
  // Rebuild supplier options from selected POs
  fetchSuppliers();

  // Clear single-select dropdown after adding
  form.value.purchase_order_id = "";
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
}

function addPurchaseOrder(po: any) {
  if (!isPurchaseOrderSelected(po.id)) {
    selectedPurchaseOrders.value.push(po);
  }
}

function removePurchaseOrder(poId: number) {
  selectedPurchaseOrders.value = selectedPurchaseOrders.value.filter(
    (po) => po.id !== poId
  );
  fetchSuppliers();
}

function isPurchaseOrderSelected(poId: number) {
  return selectedPurchaseOrders.value.some((po) => po.id === poId);
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

  // Validate required fields (conditional by metode)
  const baseRequired: Array<keyof FormData> = [
    "perihal_id",
    "nominal",
    "metode_pembayaran",
  ];
  const transferRequired: Array<keyof FormData> = [
    "bank_id",
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
  if (form.value.metode_pembayaran === "Transfer")
    requiredFields = requiredFields.concat(transferRequired);
  if (form.value.metode_pembayaran === "Cek/Giro")
    requiredFields = requiredFields.concat(cekGiroRequired);
  if (form.value.metode_pembayaran === "Kredit")
    requiredFields = requiredFields.concat(kreditRequired);

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
    perihal_id: form.value.perihal_id,
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
      emit("close");
      emit("refreshTable");
    },
    onError: (errorBag) => {
      errors.value = errorBag as Record<string, any>;
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
