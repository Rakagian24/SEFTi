<script setup lang="ts">
import { computed, watch, ref } from "vue";
import CustomSelect from "../ui/CustomSelect.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import PurchaseOrderSelectionModal from "./PurchaseOrderSelectionModal.vue";

const model = defineModel<any>({ required: true });

const props = defineProps<{
  supplierOptions?: any[];
  departmentOptions?: any[];
  perihalOptions?: any[];
  giroOptions?: any[]; // expects objects with id/name/bank_name/tanggal_giro/tanggal_cair
  creditCardOptions?: any[]; // expects objects with id/card_number/bank_name/owner_name
  purchaseOrderOptions?: any[];
  availablePOs?: any[];
}>();

const emit = defineEmits<{
  "search-purchase-orders": [search: string];
  "add-purchase-order": [po: any];
}>();

// Modal state
const showPOSelection = ref(false);

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
  //   { value: "Cek/Giro", label: "Cek/Giro" },
  { value: "Kartu Kredit", label: "Kartu Kredit" },
];

// Set default metode bayar to Transfer if not set
if (!model.value?.metode_bayar) {
  model.value = {
    ...(model.value || {}),
    metode_bayar: "Transfer",
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

const selectedSupplier = computed(() => {
  if (!model.value?.supplier_id) return null;
  return (props.supplierOptions || []).find(
    (x: any) => String(x.value || x.id) === String(model.value.supplier_id)
  );
});

const selectedGiro = computed(() => {
  if (!model.value?.giro_id) return null;
  return (props.giroOptions || []).find(
    (x: any) => String(x.value || x.id) === String(model.value.giro_id)
  );
});

const selectedCreditCard = computed(() => {
  if (!model.value?.credit_card_id) return null;
  return (props.creditCardOptions || []).find(
    (x: any) => String(x.value || x.id) === String(model.value.credit_card_id)
  );
});

const supplierBankAccountOptions = computed(() => {
  if (!selectedSupplier.value?.bank_accounts) return [];
  return selectedSupplier.value.bank_accounts.map((ba: any, idx: number) => ({
    label: `${ba.account_name || "-"}`,
    value: String(idx),
  }));
});

// Filter options based on selected department
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

const filteredGiroOptions = computed(() => {
  if (!model.value?.department_id) return props.giroOptions || [];
  return (props.giroOptions || []).filter(
    (g: any) => g.department_id === model.value.department_id
  );
});

function stringToDate(value?: string) {
  if (!value) return (null as unknown) as Date | null;
  try {
    const d = new Date(value);
    return isNaN(d.getTime()) ? null : d;
  } catch {
    return null;
  }
}

function dateToString(d?: Date | null) {
  if (!d) return "";
  try {
    const iso = d.toISOString();
    return iso.slice(0, 10);
  } catch {
    return "";
  }
}

const tanggalGiroDate = computed<Date | null>({
  get() {
    return stringToDate(model.value?.tanggal_giro || "");
  },
  set(val: Date | null) {
    model.value = {
      ...(model.value || {}),
      tanggal_giro: dateToString(val || (undefined as any)),
    };
  },
});

const tanggalCairDate = computed<Date | null>({
  get() {
    return stringToDate(model.value?.tanggal_cair || "");
  },
  set(val: Date | null) {
    model.value = {
      ...(model.value || {}),
      tanggal_cair: dateToString(val || (undefined as any)),
    };
  },
});

watch(
  () => model.value?.supplier_id,
  (newVal) => {
    if (!newVal) {
      // Reset supplier-related fields when no supplier selected
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

    // Update supplier basic info
    model.value = {
      ...(model.value || {}),
      supplier_phone: s.phone || "",
      supplier_address: s.address || "",
      department_id:
        model.value?.department_id || s.department_id || model.value?.department_id,
    };

    // Handle bank account selection when supplier changes
    const accounts = (s.bank_accounts || []) as any[];

    if (accounts.length === 1) {
      // Auto-fill when only 1 bank account
      const ba = accounts[0];

      model.value = {
        ...model.value,
        bank_name: ba.bank_name || "",
        account_owner_name: ba.account_name || "",
        account_number: ba.account_number || "",
        supplier_bank_account_index: "0",
      };
    } else if (accounts.length > 1) {
      // Reset bank account selection when multiple accounts
      model.value = {
        ...model.value,
        supplier_bank_account_index: undefined,
        bank_name: "",
        account_owner_name: "",
        account_number: "",
      };
    } else {
      // No bank accounts
      model.value = {
        ...model.value,
        supplier_bank_account_index: undefined,
        bank_name: "",
        account_owner_name: "",
        account_number: "",
      };
    }
  }
);

watch(
  () => model.value?.supplier_id,
  (newVal) => {
    if (!newVal) {
      // Reset supplier-related fields when no supplier selected
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

    // Handle bank account selection when supplier changes
    const accounts = (s.bank_accounts || []) as any[];

    if (accounts.length === 1) {
      // Auto-fill when only 1 bank account
      const ba = accounts[0];

      model.value = {
        ...model.value,
        supplier_phone: s.phone || "",
        supplier_address: s.address || "",
        bank_name: ba.bank_name || "",
        account_owner_name: ba.account_name || "",
        account_number: ba.account_number || "",
        supplier_bank_account_index: "0",
        department_id:
          model.value?.department_id || s.department_id || model.value?.department_id,
      };
    } else if (accounts.length > 1) {
      // Reset bank account selection when multiple accounts
      model.value = {
        ...model.value,
        supplier_phone: s.phone || "",
        supplier_address: s.address || "",
        supplier_bank_account_index: undefined,
        bank_name: "",
        account_owner_name: "",
        account_number: "",
        department_id:
          model.value?.department_id || s.department_id || model.value?.department_id,
      };
    } else {
      // No bank accounts
      model.value = {
        ...model.value,
        supplier_phone: s.phone || "",
        supplier_address: s.address || "",
        supplier_bank_account_index: undefined,
        bank_name: "",
        account_owner_name: "",
        account_number: "",
        department_id:
          model.value?.department_id || s.department_id || model.value?.department_id,
      };
    }
  }
);

watch(
  () => model.value?.giro_id,
  () => {
    const g = selectedGiro.value;
    if (!g) return;
    model.value = {
      ...(model.value || {}),
      no_giro: g.no_giro || g.name || "",
      tanggal_giro: g.tanggal_giro || "",
      tanggal_cair: g.tanggal_cair || "",
    };
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
    // Only process if actually changing metode bayar (not initial load)
    if (oldVal === undefined) return;

    // Reset dependent fields when metode bayar changes
    const keep = { ...(model.value || {}) };

    // Always reset PO selection when payment method changes
    keep.purchase_order_id = undefined;
    keep.nominal = 0;

    if (val === "Transfer") {
      keep.giro_id = undefined;
      keep.credit_card_id = undefined;
      keep.no_giro = "";
      keep.tanggal_giro = "";
      keep.tanggal_cair = "";
      keep.no_kartu_kredit = "";

      // Only reset bank account fields if no supplier is selected
      // If supplier is selected, let the supplier watcher handle the bank account data
      if (!keep.supplier_id) {
        keep.bank_name = "";
        keep.account_owner_name = "";
        keep.account_number = "";
        keep.supplier_bank_account_index = undefined;
        keep.supplier_phone = "";
        keep.supplier_address = "";
      }
    } else if (val === "Cek/Giro") {
      keep.supplier_id = undefined;
      keep.credit_card_id = undefined;
      keep.supplier_phone = "";
      keep.supplier_address = "";
      keep.bank_name = "";
      keep.account_owner_name = "";
      keep.account_number = "";
      keep.supplier_bank_account_index = undefined;
      keep.no_kartu_kredit = "";
      keep.tanggal_giro = "";
      keep.tanggal_cair = "";
    } else if (val === "Kartu Kredit") {
      keep.supplier_id = undefined;
      keep.giro_id = undefined;
      keep.supplier_phone = "";
      keep.supplier_address = "";
      keep.account_number = "";
      keep.supplier_bank_account_index = undefined;
      keep.no_giro = "";
      keep.tanggal_giro = "";
      keep.tanggal_cair = "";
      keep.bank_name = "";
      keep.no_kartu_kredit = "";
    }
    model.value = keep;
  }
);

// Watch for changes in purchase_order_id and auto-fill fields based on payment method
watch(
  () => model.value?.purchase_order_id,
  (poId) => {
    if (poId && props.availablePOs) {
      const selectedPO = props.availablePOs.find((po) => po.id === poId);
      if (selectedPO) {
        const currentMethod = model.value?.metode_bayar;

        // Always update nominal from PO
        let updates: any = {
          nominal: selectedPO.total || 0,
        };

        // Auto-fill fields based on payment method
        if (currentMethod === "Transfer") {
          // For Transfer: only fill if not already filled by supplier selection
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
        } else if (currentMethod === "Cek/Giro") {
          // For Cek/Giro: fill giro dates from PO
          updates = {
            ...updates,
            tanggal_giro: selectedPO.tanggal_giro || "",
            tanggal_cair: selectedPO.tanggal_cair || "",
          };
        } else if (currentMethod === "Kartu Kredit") {
          // For Kartu Kredit: fill credit card details from PO
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
  <div>
    <div class="space-y-6">
      <!-- Row 1: No. Payment Voucher | Nama Supplier / Nama Giro / Nama Kredit -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- No. Payment Voucher -->
        <div class="floating-input">
          <div
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ noPv }}
          </div>
          <label class="floating-label">No. Payment Voucher</label>
        </div>

        <!-- Right column depends on metode bayar -->
        <div class="floating-input">
          <template v-if="model.metode_bayar === 'Cek/Giro'">
            <CustomSelect
              v-model="model.giro_id"
              :options="filteredGiroOptions.map((g:any)=>({ label: g.label || g.name, value: g.value ?? g.id }))"
              placeholder="Pilih Giro"
              :searchable="true"
              :disabled="!model.department_id"
            >
              <template #label> Nama Giro </template>
            </CustomSelect>
          </template>
          <template v-else-if="model.metode_bayar === 'Kartu Kredit'">
            <CustomSelect
              v-model="model.credit_card_id"
              :options="filteredCreditCardOptions.map((c:any)=>({ label: c.label || c.card_number, value: c.value ?? c.id }))"
              placeholder="Pilih Kartu Kredit"
              :searchable="true"
              :disabled="!model.department_id"
            >
              <template #label> Nama Kredit </template>
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
      </div>

      <!-- Row 2: Tanggal | No Telp / Tanggal Giro / Nama Bank -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tanggal (display only) -->
        <div class="floating-input">
          <div
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ displayTanggal || "-" }}
          </div>
          <label class="floating-label">Tanggal</label>
        </div>

        <!-- Right column depends on metode bayar -->
        <div>
          <template v-if="model.metode_bayar === 'Cek/Giro'">
            <label class="block text-xs font-light text-gray-700 mb-1"
              >Tanggal Giro</label
            >
            <Datepicker
              v-model="tanggalGiroDate"
              :enable-time-picker="false"
              :input-class="[
                'floating-input-field',
                model.purchase_order_id
                  ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                  : '',
              ]"
              :state="true"
              :disabled="!!model.purchase_order_id"
              placeholder=" "
            />
          </template>
          <template v-else-if="model.metode_bayar === 'Kartu Kredit'">
            <div class="floating-input">
              <input
                v-model="model.bank_name"
                type="text"
                id="cc_bank_name"
                :class="[
                  'floating-input-field',
                  model.purchase_order_id
                    ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                    : '',
                ]"
                :readonly="!!model.purchase_order_id"
                placeholder=" "
              />
              <label for="cc_bank_name" class="floating-label">Nama Bank (Kredit)</label>
            </div>
          </template>
          <template v-else>
            <div v-if="supplierBankAccountOptions.length > 1" class="floating-input">
              <CustomSelect
                v-model="model.supplier_bank_account_index"
                :options="supplierBankAccountOptions"
                placeholder="Pilih Rekening"
                :disabled="!model.department_id || !model.supplier_id"
              >
                <template #label>
                  Nama Pemilik Rekening (Supplier)<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
            </div>
            <div v-else class="floating-input">
              <input
                v-model="model.account_owner_name"
                type="text"
                id="account_owner_name"
                :class="[
                  'floating-input-field',
                  model.purchase_order_id
                    ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                    : '',
                ]"
                :readonly="!!model.purchase_order_id"
                placeholder=" "
              />
              <label for="account_owner_name" class="floating-label"
                >Nama Pemilik Rekening (Supplier)</label
              >
            </div>
          </template>
        </div>
      </div>

      <!-- Row 3: Tipe | Alamat / Tanggal Cair / No Kartu Kredit -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tipe PV -->
        <div>
          <div class="flex gap-6 items-center">
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

        <!-- Right column depends on metode bayar -->
        <div>
          <template v-if="model.metode_bayar === 'Cek/Giro'">
            <label class="block text-xs font-light text-gray-700 mb-1"
              >Tanggal Cair</label
            >
            <Datepicker
              v-model="tanggalCairDate"
              :enable-time-picker="false"
              :input-class="[
                'floating-input-field',
                model.purchase_order_id
                  ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                  : '',
              ]"
              :state="true"
              :disabled="!!model.purchase_order_id"
              placeholder=" "
            />
          </template>
          <template v-else-if="model.metode_bayar === 'Kartu Kredit'">
            <div class="floating-input">
              <input
                v-model="model.no_kartu_kredit"
                type="text"
                id="no_kartu_kredit"
                :class="[
                  'floating-input-field',
                  model.purchase_order_id
                    ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                    : '',
                ]"
                :readonly="!!model.purchase_order_id"
                placeholder=" "
              />
              <label for="no_kartu_kredit" class="floating-label"
                >No Kartu Kredit (Kredit)</label
              >
            </div>
          </template>
          <template v-else>
            <div class="floating-input">
              <input
                v-model="model.bank_name"
                type="text"
                id="bank_name"
                :class="[
                  'floating-input-field',
                  model.purchase_order_id
                    ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                    : '',
                ]"
                :readonly="!!model.purchase_order_id"
                placeholder=" "
              />
              <label for="bank_name" class="floating-label">Nama Bank (Supplier)</label>
            </div>
          </template>
        </div>
      </div>

      <!-- Row 4: Departemen | Nama Pemilik Rekening (Supplier) / Nama Bank (Kredit) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

        <!-- Right column depends on metode bayar -->
        <div v-if="model.metode_bayar === 'Transfer'">
          <div class="floating-input">
            <input
              v-model="model.account_number"
              type="text"
              id="account_number"
              :class="[
                'floating-input-field',
                model.purchase_order_id
                  ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                  : '',
              ]"
              :readonly="!!model.purchase_order_id"
              placeholder=" "
            />
            <label for="account_number" class="floating-label"
              >No Rekening (Supplier)</label
            >
          </div>
        </div>
        <div v-else-if="model.metode_bayar === 'Cek/Giro'">
          <!-- Purchase Order for Cek/Giro -->
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
        </div>
        <div v-else></div>
      </div>

      <!-- Row 5: Perihal | Nama Bank (Supplier) / Nama Pemilik Rekening (Kredit) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Perihal -->
        <div class="floating-input">
          <CustomSelect
            v-model="model.perihal_id"
            :options="(props.perihalOptions || []).map((p:any)=>({ label: p.label || p.nama, value: p.value ?? p.id }))"
            placeholder="Pilih Perihal"
          >
            <template #label> Perihal<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div>

        <!-- Right column depends on metode bayar -->
        <div v-if="model.metode_bayar === 'Transfer'">
          <div class="floating-input">
            <input
              v-model="model.supplier_phone"
              type="text"
              id="supplier_phone"
              :class="[
                'floating-input-field',
                model.purchase_order_id
                  ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                  : '',
              ]"
              :readonly="!!model.purchase_order_id"
              placeholder=" "
            />
            <label for="supplier_phone" class="floating-label">No Telp</label>
          </div>
        </div>
        <div v-else-if="model.metode_bayar === 'Kartu Kredit'">
          <!-- Purchase Order for Kartu Kredit -->
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
        </div>
        <div v-else></div>
      </div>

      <!-- Row 6: Nominal | Alamat -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

        <!-- Right column depends on metode bayar -->
        <div v-if="model.metode_bayar === 'Transfer'">
          <div class="floating-input">
            <input
              v-model="model.supplier_address"
              type="text"
              id="supplier_address"
              :class="[
                'floating-input-field',
                model.purchase_order_id
                  ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                  : '',
              ]"
              :readonly="!!model.purchase_order_id"
              placeholder=" "
            />
            <label for="supplier_address" class="floating-label">Alamat</label>
          </div>
        </div>
        <div v-else></div>
      </div>

      <!-- Row 7: Metode Bayar | Purchase Order (for Transfer) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

        <!-- Purchase Order for Transfer -->
        <div v-if="model.metode_bayar === 'Transfer'">
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
        </div>
        <div v-else></div>
      </div>

      <!-- Note -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
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

/* Select special handling */
.floating-input-field:is(select) ~ .floating-label {
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
.floating-input-field[type="date"] ~ .floating-label,
.floating-input-field:is(select) ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}
</style>
