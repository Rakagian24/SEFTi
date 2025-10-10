<script setup lang="ts">
import { computed, watch, ref } from "vue";
import CustomSelect from "../ui/CustomSelect.vue";
// import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import PurchaseOrderSelectionModal from "./PurchaseOrderSelectionModal.vue";
import PurchaseOrderInfo from "./PurchaseOrderInfo.vue";

const model = defineModel<any>({ required: true });

const props = defineProps<{
  supplierOptions?: any[];
  departmentOptions?: any[];
  perihalOptions?: any[];
  giroOptions?: any[];
  creditCardOptions?: any[];
  purchaseOrderOptions?: any[];
  availablePOs?: any[];
}>();

const emit = defineEmits<{
  "search-purchase-orders": [search: string];
  "add-purchase-order": [po: any];
}>();

// Modal state
const showPOSelection = ref(false);

// Get selected PO for info display
const selectedPO = computed(() => {
  if (!model.value?.purchase_order_id || !props.availablePOs) return null;
  return props.availablePOs.find((po) => po.id === model.value.purchase_order_id);
});

// PO Selection functions
function openPurchaseOrderModal() {
  showPOSelection.value = true;
}

function handlePOSearch(search: string) {
  emit("search-purchase-orders", search);
}

function handleAddPO(po: any) {
  emit("add-purchase-order", po);
  showPOSelection.value = false;
}

const metodeBayarOptions = [
  { value: "Transfer", label: "Transfer" },
  { value: "Kartu Kredit", label: "Kartu Kredit" },
];

// Set defaults
if (!model.value?.metode_bayar) {
  model.value = {
    ...(model.value || {}),
    metode_bayar: "Transfer",
  };
}

if (!model.value?.tipe_pv) {
  model.value = {
    ...(model.value || {}),
    tipe_pv: "Reguler",
  };
}

const noPv = computed(() => model.value?.no_pv || "Akan di-generate otomatis");
const displayTanggal = computed(() => {
  if (model.value?.tanggal) {
    try {
      return new Date(model.value.tanggal).toLocaleDateString("id-ID");
    } catch {
      return model.value.tanggal;
    }
  }
  try {
    return new Date().toLocaleDateString("id-ID");
  } catch {
    return "";
  }
});

// const selectedSupplier = computed(() => {
//   if (!model.value?.supplier_id) return null;
//   return (props.supplierOptions || []).find(
//     (x: any) => String(x.value || x.id) === String(model.value.supplier_id)
//   );
// });

const selectedCreditCard = computed(() => {
  if (!model.value?.credit_card_id) return null;
  return (props.creditCardOptions || []).find(
    (x: any) => String(x.value || x.id) === String(model.value.credit_card_id)
  );
});

// const supplierBankAccountOptions = computed(() => {
//   if (!selectedSupplier.value?.bank_accounts) return [];
//   return selectedSupplier.value.bank_accounts.map((ba: any, idx: number) => ({
//     label: `${ba.account_name || "-"}`,
//     value: String(idx),
//   }));
// });

const filteredSupplierOptions = computed(() => {
  if (!model.value?.department_id) return props.supplierOptions || [];
  return (props.supplierOptions || []).filter(
    (s: any) => s.department_id === model.value.department_id
  );
});

const filteredCreditCardOptions = computed(() => {
  if (!model.value?.department_id) return props.creditCardOptions || [];
  return (props.creditCardOptions || []).filter(
    (c: any) => c.department_id === model.value.department_id
  );
});

// Watch supplier changes
watch(
  () => model.value?.supplier_id,
  (newVal) => {
    if (!newVal) {
      model.value = {
        ...(model.value || {}),
        supplier_phone: "",
        supplier_address: "",
        bank_name: "",
        account_owner_name: "",
        account_number: "",
        supplier_bank_account_index: undefined,
      };
      return;
    }
    const s = (props.supplierOptions || []).find(
      (x: any) => String(x.value || x.id) === String(newVal)
    );
    if (!s) return;

    const accounts = (s.bank_accounts || []) as any[];

    if (accounts.length === 1) {
      const ba = accounts[0];
      model.value = {
        ...model.value,
        supplier_phone: s.phone || "",
        supplier_address: s.address || "",
        bank_name: ba.bank_name || "",
        account_owner_name: ba.account_name || "",
        account_number: ba.account_number || "",
        supplier_bank_account_index: "0",
        department_id: model.value?.department_id || s.department_id,
      };
    } else {
      model.value = {
        ...model.value,
        supplier_phone: s.phone || "",
        supplier_address: s.address || "",
        supplier_bank_account_index: accounts.length > 1 ? undefined : undefined,
        bank_name: "",
        account_owner_name: "",
        account_number: "",
        department_id: model.value?.department_id || s.department_id,
      };
    }
  }
);

watch(
  () => model.value?.credit_card_id,
  () => {
    const c = selectedCreditCard.value;
    if (!c) return;
    model.value = {
      ...(model.value || {}),
      no_kartu_kredit: c.card_number || "",
      bank_name: c.bank_name || "",
      account_owner_name: c.owner_name || "",
    };
  }
);

watch(
  () => model.value?.metode_bayar,
  (val, oldVal) => {
    if (oldVal === undefined) return;

    const keep = { ...(model.value || {}) };
    keep.purchase_order_id = undefined;
    keep.nominal = 0;

    if (val === "Transfer") {
      keep.credit_card_id = undefined;
      keep.no_kartu_kredit = "";

      if (!keep.supplier_id) {
        keep.bank_name = "";
        keep.account_owner_name = "";
        keep.account_number = "";
        keep.supplier_bank_account_index = undefined;
        keep.supplier_phone = "";
        keep.supplier_address = "";
      }
    } else if (val === "Kartu Kredit") {
      keep.supplier_id = undefined;
      keep.supplier_phone = "";
      keep.supplier_address = "";
      keep.account_number = "";
      keep.supplier_bank_account_index = undefined;
      keep.bank_name = "";
      keep.no_kartu_kredit = "";
    }
    model.value = keep;
  }
);

// Watch PO selection
watch(
  () => model.value?.purchase_order_id,
  (poId) => {
    if (poId && props.availablePOs) {
      const selectedPO = props.availablePOs.find((po) => po.id === poId);
      if (selectedPO) {
        const currentMethod = model.value?.metode_bayar;

        let updates: any = {
          nominal: selectedPO.total || 0,
          perihal_id: selectedPO.perihal_id || selectedPO.perihal?.id,
        };

        if (currentMethod === "Transfer") {
          if (!model.value?.supplier_phone) {
            updates.supplier_phone = selectedPO.supplier?.phone || "";
          }
          if (!model.value?.supplier_address) {
            updates.supplier_address = selectedPO.supplier?.address || "";
          }
          if (!model.value?.bank_name) {
            updates.bank_name = selectedPO.supplier?.bank_name || "";
          }
          if (!model.value?.account_owner_name) {
            updates.account_owner_name = selectedPO.supplier?.account_owner_name || "";
          }
          if (!model.value?.account_number) {
            updates.account_number = selectedPO.supplier?.account_number || "";
          }
        } else if (currentMethod === "Kartu Kredit") {
          updates = {
            ...updates,
            bank_name: selectedPO.credit_card?.bank_name || "",
            no_kartu_kredit: selectedPO.credit_card?.card_number || "",
          };
        }

        model.value = {
          ...(model.value || {}),
          ...updates,
        };
      }
    }
  }
);
</script>

<template>
  <div class="pv-form-container">
    <!-- Left Column: Form -->
    <div class="pv-form-left">
      <div class="space-y-6">
        <!-- No. Payment Voucher -->
        <div class="floating-input">
          <div
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ noPv }}
          </div>
          <label class="floating-label">No. Payment Voucher</label>
        </div>

        <!-- Tanggal -->
        <div class="floating-input">
          <div
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ displayTanggal || "-" }}
          </div>
          <label class="floating-label">Tanggal</label>
        </div>

        <!-- Tipe PV -->
        <div>
          <label class="block text-sm font-medium text-gray-700 mb-2">Tipe PV</label>
          <div class="flex gap-6">
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.tipe_pv"
                value="Reguler"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">Reguler</span>
            </label>
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.tipe_pv"
                value="Anggaran"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">Anggaran</span>
            </label>
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.tipe_pv"
                value="Lainnya"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">Lainnya</span>
            </label>
          </div>
        </div>

        <!-- Departemen -->
        <div class="floating-input">
          <CustomSelect
            v-model="model.department_id"
            :options="(props.departmentOptions || []).map((d:any)=>({ label: d.name || d.label, value: d.value ?? d.id }))"
            placeholder="Pilih Departemen"
          >
            <template #label> Departemen<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Metode Bayar -->
        <div class="floating-input">
          <CustomSelect
            v-model="model.metode_bayar"
            :options="metodeBayarOptions"
            placeholder="Pilih Metode"
          >
            <template #label> Metode Bayar<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Nama Supplier / Nama Kredit -->
        <div class="floating-input">
          <template v-if="model.metode_bayar === 'Kartu Kredit'">
            <CustomSelect
              v-model="model.credit_card_id"
              :options="filteredCreditCardOptions.map((c:any)=>({ label: c.label || c.card_number, value: c.value ?? c.id }))"
              placeholder="Pilih Kartu Kredit"
              :searchable="true"
              :disabled="!model.department_id"
            >
              <template #label> Nama Kredit<span class="text-red-500">*</span> </template>
            </CustomSelect>
          </template>
          <template v-else>
            <CustomSelect
              v-model="model.supplier_id"
              :options="filteredSupplierOptions.map((s:any)=>({ label: s.label || s.name, value: s.value ?? s.id }))"
              placeholder="Pilih Supplier"
              :searchable="true"
              :disabled="!model.department_id"
            >
              <template #label>
                Nama Supplier<span class="text-red-500">*</span>
              </template>
            </CustomSelect>
          </template>
        </div>

        <!-- Purchase Order -->
        <div class="floating-input">
          <div class="flex gap-2">
            <div class="flex-1">
              <CustomSelect
                v-model="model.purchase_order_id"
                :options="purchaseOrderOptions || []"
                placeholder="Pilih Purchase Order"
                :searchable="true"
                :disabled="!model.department_id"
                @search="handlePOSearch"
              >
                <template #label>
                  Purchase Order<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
            </div>
            <button
              type="button"
              @click="openPurchaseOrderModal"
              :disabled="!model.department_id"
              class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              +
            </button>
          </div>
        </div>

        <!-- Perihal -->
        <div class="floating-input">
          <CustomSelect
            v-model="model.perihal_id"
            :options="(props.perihalOptions || []).map((p:any)=>({ label: p.label || p.nama, value: p.value ?? p.id }))"
            placeholder="Pilih Perihal"
            :disabled="!!model.purchase_order_id"
          >
            <template #label> Perihal<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Nominal -->
        <div class="floating-input">
          <input
            v-model.number="model.nominal"
            type="number"
            id="nominal"
            :class="[
              'floating-input-field',
              model.purchase_order_id
                ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                : '',
            ]"
            :readonly="!!model.purchase_order_id"
            placeholder=" "
          />
          <label for="nominal" class="floating-label">
            Nominal<span class="text-red-500">*</span>
          </label>
        </div>

        <!-- Note -->
        <div class="floating-input">
          <textarea
            v-model="model.note"
            id="note"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label for="note" class="floating-label">Note</label>
        </div>
      </div>
    </div>

    <!-- Right Column: Purchase Order Info -->
    <div class="pv-form-right">
      <PurchaseOrderInfo :purchase-order="selectedPO" />
    </div>

    <!-- Purchase Order Selection Modal -->
    <PurchaseOrderSelectionModal
      v-model:open="showPOSelection"
      :purchase-orders="availablePOs || []"
      :selected-ids="model.purchase_order_id ? [model.purchase_order_id] : []"
      :no-results-message="'Tidak ada Purchase Order yang tersedia'"
      @search="handlePOSearch"
      @add-selected="handleAddPO"
    />
  </div>
</template>

<style scoped>
.pv-form-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.pv-form-left {
  width: 100%;
}

.pv-form-right {
  width: 100%;
}

@media (max-width: 1024px) {
  .pv-form-container {
    grid-template-columns: 1fr;
  }
}

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

.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

.floating-input-field[readonly].filled ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

.floating-input-field[type="date"] ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

.floating-input-field:is(select) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

.floating-input-field:is(textarea) {
  resize: vertical;
  padding-top: 1rem;
  padding-bottom: 1rem;
}

.floating-input-field:is(textarea):focus ~ .floating-label,
.floating-input-field:is(textarea):not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
}

.floating-input:hover .floating-input-field {
  border-color: #9ca3af;
}

.floating-input:hover .floating-input-field:focus {
  border-color: #1f9254;
}

.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input-field[readonly].filled ~ .floating-label,
.floating-input-field[type="date"] ~ .floating-label,
.floating-input-field:is(select) ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}
</style>
