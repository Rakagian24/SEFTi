<script setup lang="ts">
import { computed, watch } from "vue";
import CustomSelect from "../ui/CustomSelect.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";

const model = defineModel<any>({ required: true });

const props = defineProps<{
  supplierOptions?: any[];
  departmentOptions?: any[];
  perihalOptions?: any[];
  giroOptions?: any[]; // expects objects with id/name/bank_name/tanggal_giro/tanggal_cair
  creditCardOptions?: any[]; // expects objects with id/card_number/bank_name/owner_name
  totalFromBarangGrid?: number; // Total from PaymentVoucherBarangGrid
}>();

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
  () => model.value?.supplier_bank_account_index,
  (idx) => {
    if (idx === undefined || idx === null || idx === "") {
      // Only reset if supplier has multiple accounts (not auto-filled single account)
      const accounts = (selectedSupplier.value?.bank_accounts || []) as any[];
      if (accounts.length > 1) {
        model.value = {
          ...model.value,
          bank_name: "",
          account_owner_name: "",
          account_number: "",
        };
      }
      return;
    }
    const i = Number(idx);
    const accounts = (selectedSupplier.value?.bank_accounts || []) as any[];
    const ba = accounts[i];
    if (!ba) return;

    model.value = {
      ...model.value,
      bank_name: ba.bank_name || "",
      account_owner_name: ba.account_name || "",
      account_number: ba.account_number || "",
    };
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
  (val) => {
    // Reset dependent fields when metode bayar changes
    const keep = { ...(model.value || {}) };
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
    }
    model.value = keep;
  }
);

// Watch for changes in total from BarangGrid and update nominal
watch(
  () => props.totalFromBarangGrid,
  (newTotal) => {
    if (newTotal !== undefined && newTotal !== null) {
      model.value = {
        ...(model.value || {}),
        nominal: newTotal,
      };
    }
  },
  { immediate: true }
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
              :input-class="'floating-input-field'"
              :state="true"
              placeholder=" "
            />
          </template>
          <template v-else-if="model.metode_bayar === 'Kartu Kredit'">
            <div class="floating-input">
              <input
                v-model="model.bank_name"
                type="text"
                id="cc_bank_name"
                class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                placeholder=" "
                readonly
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
                class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                placeholder=" "
                readonly
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
              :input-class="'floating-input-field'"
              :state="true"
              placeholder=" "
            />
          </template>
          <template v-else-if="model.metode_bayar === 'Kartu Kredit'">
            <div class="floating-input">
              <input
                v-model="model.no_kartu_kredit"
                type="text"
                id="no_kartu_kredit"
                class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                placeholder=" "
                readonly
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
                class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                placeholder=" "
                readonly
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
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              placeholder=" "
              readonly
            />
            <label for="account_number" class="floating-label"
              >No Rekening (Supplier)</label
            >
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
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              placeholder=" "
              readonly
            />
            <label for="supplier_phone" class="floating-label">No Telp</label>
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
              props.totalFromBarangGrid !== undefined && props.totalFromBarangGrid !== null
                ? 'bg-gray-50 text-gray-600 cursor-not-allowed'
                : ''
            ]"
            :readonly="props.totalFromBarangGrid !== undefined && props.totalFromBarangGrid !== null"
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
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              placeholder=" "
              readonly
            />
            <label for="supplier_address" class="floating-label">Alamat</label>
          </div>
        </div>
        <div v-else></div>
      </div>

      <!-- Row 7: Metode Bayar -->
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
        <div></div>
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
