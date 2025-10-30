<script setup lang="ts">
import { computed, watch, ref } from "vue";
import CustomSelect from "../ui/CustomSelect.vue";
// import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import PurchaseOrderSelectionModal from "./PurchaseOrderSelectionModal.vue";
import PurchaseOrderInfo from "../PurchaseOrderInfo.vue";
import MemoPembayaranSelectionModal from "./MemoPembayaranSelectionModal.vue";
import MemoPembayaranInfo from "../MemoPembayaranInfo.vue";
import SupplierForm from "../suppliers/SupplierForm.vue";

const model = defineModel<any>({ required: true });

const props = defineProps<{
  supplierOptions?: any[];
  departmentOptions?: any[];
  perihalOptions?: any[];
  giroOptions?: any[];
  creditCardOptions?: any[];
  purchaseOrderOptions?: any[];
  availablePOs?: any[];
  currencyOptions?: any[];
  memoOptions?: any[];
  availableMemos?: any[];
  banks?: any[];
}>();

const emit = defineEmits<{
  "search-purchase-orders": [search: string];
  "add-purchase-order": [po: any];
  "search-memos": [search: string];
  "add-memo": [memo: any];
  "refresh-suppliers": [];
}>();

// Modal state
const showPOSelection = ref(false);
const showMemoSelection = ref(false);
const showCreateSupplier = ref(false);
const pendingNewSupplierEmail = ref<string | null>(null);

// Get selected PO for info display
const selectedPO = computed(() => {
  if (!model.value?.purchase_order_id || !props.availablePOs) return null;
  return props.availablePOs.find((po) => po.id === model.value.purchase_order_id);
});

// Get selected Memo for info display
const selectedMemo = computed(() => {
  if (!model.value?.memo_id || !props.availableMemos) return null;
  return (props.availableMemos || []).find((m: any) => m.id === model.value.memo_id);
});

// PO Selection functions
function openPurchaseOrderModal() {
  showPOSelection.value = true;
}

function resolveBankNameById(bankId: string | number | undefined | null): string {
  if (!bankId) return "";
  const b = (props.banks || []).find((x: any) => String(x.id) === String(bankId));
  return b?.nama_bank || b?.name || "";
}

function handleSupplierCreated(data: any) {
  try {
    pendingNewSupplierEmail.value = data?.email || null;
    // Prefill visible fields immediately for good UX
    model.value = {
      ...(model.value || {}),
      supplier_name: data?.nama_supplier || "",
      supplier_phone: data?.no_telepon || "",
      supplier_address: data?.alamat || "",
    };
    const accs = Array.isArray(data?.bank_accounts) ? data.bank_accounts : [];
    if (accs.length === 1) {
      const acc = accs[0];
      model.value = {
        ...(model.value || {}),
        supplier_bank_name: resolveBankNameById(acc?.bank_id),
        supplier_account_name: acc?.nama_rekening || "",
        supplier_account_number: acc?.no_rekening || "",
      };
    }
  } catch {}
  // Ask parent to refresh supplierOptions and close modal
  showCreateSupplier.value = false;
  emit('refresh-suppliers');
}

// After supplierOptions reload, auto-select the newly created supplier by email
watch(
  () => props.supplierOptions,
  (list) => {
    if (!pendingNewSupplierEmail.value || !Array.isArray(list)) return;
    const found = (list || []).find((s: any) => String(s.email || "") === String(pendingNewSupplierEmail.value));
    if (found) {
      model.value = { ...(model.value || {}), supplier_id: found.value ?? found.id };
      pendingNewSupplierEmail.value = null;
    }
  },
  { deep: false }
);

function handlePOSearch(search: string) {
  emit("search-purchase-orders", search);
}

function handleAddPO(po: any) {
  emit("add-purchase-order", po);
  showPOSelection.value = false;
}

function openMemoSelectionModal() {
  showMemoSelection.value = true;
}

function handleMemoSearch(search: string) {
  emit("search-memos", search);
}

function handleAddMemo(memo: any) {
  emit("add-memo", memo);
  showMemoSelection.value = false;
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

// Determine if current tipe behaves like Manual (Manual or Pajak)
const isManualLike = computed(() => {
  return String(model.value?.tipe_pv || "") === "Manual" || String(model.value?.tipe_pv || "") === "Pajak";
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

const selectedSupplier = computed(() => {
  if (!model.value?.supplier_id) return null;
  return (props.supplierOptions || []).find(
    (x: any) => String(x.value || x.id) === String(model.value.supplier_id)
  );
});

const supplierBankAccountOptions = computed(() => {
  const s: any = selectedSupplier.value;
  if (!s || !Array.isArray(s.bank_accounts)) return [];
  return s.bank_accounts.map((ba: any) => ({
    label: ba?.nama_rekening || ba?.account_name || ba?.atas_nama || ba?.owner_name || `Rekening`,
    value: String(ba?.id ?? ""),
  }));
});

function applySelectedSupplierInfo() {
  const s: any = selectedSupplier.value;
  if (!s) return;
  const phone = s.no_telepon || s.phone || s.no_hp || s.phone_number || s?.supplier?.no_telepon || "";
  const address = s.alamat || s.address || s?.supplier?.alamat || "";
  const name = s.nama_supplier || s.name || s.label || s?.supplier?.nama_supplier || "";
  model.value = {
    ...(model.value || {}),
    supplier_name: name,
    supplier_phone: phone,
    supplier_address: address,
  };
}

// Ensure contact fields populate when the selected supplier object becomes available
watch(
  () => selectedSupplier.value,
  (s) => {
    if (!s) return;
    applySelectedSupplierInfo();
  },
  { deep: false, immediate: true }
);

// Re-apply when supplierOptions refresh while a supplier is already selected
watch(
  () => props.supplierOptions,
  () => {
    if (!model.value?.supplier_id) return;
    applySelectedSupplierInfo();
  },
  { deep: false }
);

function applySelectedBankAccount() {
  const s: any = selectedSupplier.value;
  if (!s || !Array.isArray(s.bank_accounts)) return;
  const accounts: any[] = s.bank_accounts || [];
  if (!accounts.length) return;
  const selId = model.value?.bank_supplier_account_id;
  const acc = accounts.find((a:any)=> String(a?.id) === String(selId)) || accounts[0] || {};
  const bankName = acc?.bank?.nama_bank || acc?.bank_name || resolveBankNameById(acc?.bank_id) || "";
  const accountName = acc?.nama_rekening || acc?.account_name || acc?.atas_nama || acc?.owner_name || "";
  const accountNumber = acc?.no_rekening || acc?.account_number || "";
  model.value = {
    ...(model.value || {}),
    supplier_account_name: accountName,
    supplier_bank_name: bankName,
    supplier_account_number: accountNumber,
  };
}

// const selectedSupplierBank = computed(() => {
//   const s: any = selectedSupplier.value;
//   if (!s) return null;
//   const idx = model.value?.supplier_bank_account_index
//     ? parseInt(String(model.value.supplier_bank_account_index))
//     : 0;
//   const accounts: any[] = s.bank_accounts || [];
//   if (!accounts.length) return null;
//   return accounts[Math.min(Math.max(idx, 0), accounts.length - 1)];
// });

// const selectedDepartment = computed(() => {
//   if (!model.value?.department_id) return null;
//   return (props.departmentOptions || []).find(
//     (d: any) => String(d.value ?? d.id) === String(model.value.department_id)
//   );
// });

// const selectedPerihal = computed(() => {
//   if (!model.value?.perihal_id) return null;
//   return (props.perihalOptions || []).find(
//     (p: any) => String(p.value ?? p.id) === String(model.value.perihal_id)
//   );
// });

// const selectedCurrency = computed(() => {
//   if (!model.value?.currency) return null;
//   return (props.currencyOptions || []).find(
//     (c: any) => String(c.value ?? c.id ?? c.code) === String(model.value.currency)
//   );
// });

// Manual nominal input handling: allow decimals while typing; format on blur (1,234.56)
const nominalInput = ref<string>("");
const isTypingNominal = ref<boolean>(false);

// function formatNominal(val: number | string | null | undefined): string {
//   if (val === null || val === undefined || val === "") return "";
//   const num = typeof val === "number" ? val : Number(String(val).replaceAll(",", ""));
//   if (Number.isNaN(num)) return "";
//   // Use en-US for comma thousands, dot decimal
//   return num.toLocaleString("en-US", { maximumFractionDigits: 20 });
// }

function parseNominalInput(input: string): number | null {
  if (!input) return null;
  const cleaned = input.replace(/[^0-9.]/g, "");
  // If multiple dots, keep first and remove the rest
  const firstDot = cleaned.indexOf(".");
  const normalized =
    firstDot === -1 ? cleaned : cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, "");
  const num = Number(normalized);
  return Number.isNaN(num) ? null : num;
}

// Insert thousand separators but preserve decimals (and trailing dot) exactly as entered
function formatNominalTextPreserve(raw: string): string {
  const s = String(raw || "");
  const noCommas = s.replaceAll(",", "");
  const hasDot = noCommas.includes(".");
  const keepTrailingDot = hasDot && noCommas.endsWith(".");
  const [intPartRaw, decPartRaw = ""] = noCommas.split(".");
  const intPart = intPartRaw;
  const decPart = decPartRaw;
  const intPartFormatted = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ",");
  if (keepTrailingDot) return intPartFormatted + ".";
  if (hasDot) return intPartFormatted + "." + decPart;
  return intPartFormatted;
}

// Initialize and keep nominalInput in sync with model.nominal_text when not typing
watch(
  () => model.value?.nominal_text,
  (txt) => {
    if (isTypingNominal.value) return;
    const raw = txt ?? (model.value?.nominal != null ? String(model.value?.nominal) : "");
    nominalInput.value = formatNominalTextPreserve(raw);
  },
  { immediate: true }
);

function handleNominalFocus() {
  isTypingNominal.value = true;
  // show raw number without commas while editing
  nominalInput.value = String(nominalInput.value || "").replaceAll(",", "");
}

function handleNominalInput(e: any) {
  isTypingNominal.value = true;
  const raw = String(e?.target?.value ?? "");
  // Allow only digits and a single dot while typing
  const cleaned = raw.replace(/[^0-9.]/g, "");
  const firstDot = cleaned.indexOf(".");
  const normalized = firstDot === -1
    ? cleaned
    : cleaned.slice(0, firstDot + 1) + cleaned.slice(firstDot + 1).replace(/\./g, "");
  nominalInput.value = normalized;
  const v = parseNominalInput(normalized);
  model.value = {
    ...(model.value || {}),
    nominal_text: normalized,
    nominal: v === null ? 0 : v,
  };
}

function handleNominalBlur() {
  isTypingNominal.value = false;
  const raw = (model.value?.nominal_text || "").replaceAll(",", "").trim();
  if (!raw) {
    model.value = { ...(model.value || {}), nominal_text: "0", nominal: 0 };
    nominalInput.value = "0";
    return;
  }
  nominalInput.value = formatNominalTextPreserve(model.value?.nominal_text || "");
}

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

watch(
  () => model.value?.department_id,
  (newDept, oldDept) => {
    if (oldDept === undefined) return;
    model.value = {
      ...(model.value || {}),
      purchase_order_id: undefined,
      memo_id: undefined,
      nominal: 0,
      supplier_id:
        model.value?.supplier_id && selectedSupplier.value?.department_id !== newDept
          ? undefined
          : model.value?.supplier_id,
      credit_card_id:
        model.value?.credit_card_id && selectedCreditCard.value?.department_id !== newDept
          ? undefined
          : model.value?.credit_card_id,
    };
  }
);

// Watch supplier changes
watch(
  () => model.value?.supplier_id,
  (newVal) => {
    if (!newVal) {
      model.value = {
        ...(model.value || {}),
        supplier_bank_account_index: undefined,
        bank_supplier_account_id: undefined,
        purchase_order_id: undefined,
        memo_id: undefined,
        nominal: 0,
        supplier_name: undefined,
        supplier_phone: undefined,
        supplier_address: undefined,
        supplier_account_name: undefined,
        supplier_bank_name: undefined,
        supplier_account_number: undefined,
      };
      return;
    }
    const s = (props.supplierOptions || []).find(
      (x: any) => String(x.value || x.id) === String(newVal)
    );
    if (!s) return;

    const accounts = (s.bank_accounts || []) as any[];

    // Apply supplier basic info directly from s to avoid any computed timing edge cases
    try {
      const phone = s.no_telepon || s.phone || s.no_hp || s.phone_number || s?.supplier?.no_telepon || "";
      const address = s.alamat || s.address || s?.supplier?.alamat || "";
      const name = s.nama_supplier || s.name || s.label || s?.supplier?.nama_supplier || "";
      model.value = {
        ...(model.value || {}),
        supplier_name: name,
        supplier_phone: phone,
        supplier_address: address,
      };
    } catch {}

    if (accounts.length === 1) {
      model.value = {
        ...model.value,
        bank_supplier_account_id: accounts[0]?.id ? String(accounts[0].id) : undefined,
        department_id: s.department_id,
        purchase_order_id: undefined,
        memo_id: undefined,
        nominal: 0,
      };
      applySelectedBankAccount();
    } else {
      model.value = {
        ...model.value,
        bank_supplier_account_id: undefined,
        department_id: s.department_id,
        purchase_order_id: undefined,
        memo_id: undefined,
        nominal: 0,
        supplier_account_name: undefined,
        supplier_bank_name: undefined,
        supplier_account_number: undefined,
      };
    }
  }
);

// Watch bank account id changes (Manual flow)
watch(
  () => model.value?.bank_supplier_account_id,
  (val) => {
    if (val === undefined || val === null || val === "") return;
    applySelectedBankAccount();
  }
);

// When tipe changes to Manual, clear PO selection
watch(
  () => model.value?.tipe_pv,
  (val, oldVal) => {
    if (oldVal === undefined) return;
    if (val === 'Manual' || val === 'Pajak') {
      model.value = {
        ...(model.value || {}),
        purchase_order_id: undefined,
        memo_id: undefined,
      };
      if (val === 'Pajak' && !model.value?.pajak_channel) {
        model.value = { ...(model.value || {}), pajak_channel: 'Bank' };
      }
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
      keep.supplier_bank_account_index = undefined;
      keep.bank_supplier_account_id = undefined;
    } else if (val === "Kartu Kredit") {
      keep.supplier_id = undefined;
      keep.supplier_bank_account_index = undefined;
      keep.bank_supplier_account_id = undefined;
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
        // Reflect PO-driven values we still keep in PV form
        const updates: any = {
          nominal: selectedPO.total || 0,
          perihal_id: selectedPO.perihal_id || selectedPO.perihal?.id,
        };

        // No need to copy supplier/cc presentational fields; shown via relations/info components

        model.value = {
          ...(model.value || {}),
          ...updates,
        };
      }
    }
  }
);

// Watch Memo selection
watch(
  () => model.value?.memo_id,
  (memoId) => {
    if (memoId && props.availableMemos) {
      const m = (props.availableMemos || []).find((x: any) => x.id === memoId);
      if (m) {
        const updates: any = {
          nominal: m.total || m.nominal || 0,
          perihal_id: m.perihal_id || m.perihal?.id,
        };

        // Try to mirror key contextual fields from the selected memo so that
        // the left-side selects display the chosen supplier/kredit correctly
        try {
          const metode = m.metode_pembayaran || m.metode_bayar;
          if (metode) updates.metode_bayar = metode;
          // Department (if memo scoped to a department)
          if (m.department_id || m.department?.id) {
            updates.department_id = m.department_id || m.department?.id;
          }
          // Supplier vs Credit Card based on metode
          if (metode === 'Kartu Kredit') {
            updates.credit_card_id = m.credit_card_id || m.credit_card?.id;
            updates.supplier_id = undefined;
            updates.bank_supplier_account_id = undefined;
          } else {
            // Default to transfer/supplier flow
            if (m.supplier_id || m.supplier?.id) {
              updates.supplier_id = m.supplier_id || m.supplier?.id;
            }
            // If backend provides bank account reference, reflect it
            if (m.bank_supplier_account_id || m.bankSupplierAccount?.id) {
              updates.bank_supplier_account_id = String(
                m.bank_supplier_account_id || m.bankSupplierAccount?.id
              );
            }
            updates.credit_card_id = undefined;
          }
        } catch {}

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
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.tipe_pv"
                value="Pajak"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">Pajak</span>
            </label>
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.tipe_pv"
                value="Manual"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">Manual</span>
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
        <div class="floating-input" v-if="!isManualLike">
          <template v-if="model.metode_bayar === 'Kartu Kredit'">
            <CustomSelect
              v-model="model.credit_card_id"
              :options="filteredCreditCardOptions.map((c:any)=>({ label: c.label || c.card_number, value: c.value ?? c.id }))"
              placeholder="Pilih Kartu Kredit"
              :searchable="true"
              :disabled="model.tipe_pv !== 'Manual' && !model.department_id"
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
              :disabled="model.tipe_pv !== 'Manual' && !model.department_id"
            >
              <template #label>
                Nama Supplier<span class="text-red-500">*</span>
              </template>
            </CustomSelect>
          </template>
        </div>

        <!-- Purchase Order / Memo Pembayaran Selection -->
        <div v-if="!isManualLike" class="floating-input">
          <div class="flex gap-2">
            <div class="flex-1">
              <template v-if="model.tipe_pv === 'Lainnya'">
                <CustomSelect
                  v-model="model.memo_id"
                  :options="memoOptions || []"
                  placeholder="Pilih Memo Pembayaran"
                  :searchable="true"
                  :disabled="!model.department_id"
                  @search="handleMemoSearch"
                >
                  <template #label>
                    Memo Pembayaran<span class="text-red-500">*</span>
                  </template>
                </CustomSelect>
              </template>
              <template v-else>
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
              </template>
            </div>
            <button
              type="button"
              v-if="model.tipe_pv === 'Lainnya'"
              @click="openMemoSelectionModal"
              :disabled="!model.department_id"
              class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              +
            </button>
            <button
              type="button"
              v-else
              @click="openPurchaseOrderModal"
              :disabled="!model.department_id"
              class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              +
            </button>
          </div>
        </div>

        <div v-else class="space-y-6">
          <div class="floating-input">
            <CustomSelect
              v-model="model.perihal_id"
              :options="(props.perihalOptions || [])"
              placeholder="Pilih Perihal"
            >
              <template #label> Perihal<span class="text-red-500">*</span> </template>
            </CustomSelect>
          </div>
          <div class="floating-input">
            <input
              v-model="nominalInput"
              type="text"
              inputmode="decimal"
              class="floating-input-field"
              placeholder=" "
              @focus="handleNominalFocus"
              @input="handleNominalInput"
              @blur="handleNominalBlur"
            />
            <label class="floating-label">Nominal</label>
          </div>
          <div class="floating-input">
            <CustomSelect
              v-model="model.currency"
              :options="(props.currencyOptions || [])"
              placeholder="Pilih Currency"
            >
              <template #label> Currency<span class="text-red-500">*</span> </template>
            </CustomSelect>
          </div>
        </div>

        <!-- Note -->
        <div class="floating-input">
          <div class="mb-2 text-sm font-medium text-gray-700">Kelengkapan Dokumen?</div>
          <div class="flex gap-6 items-center">
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.kelengkapan_dokumen"
                :value="true"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">Lengkap</span>
            </label>
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.kelengkapan_dokumen"
                :value="false"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">Tidak Lengkap</span>
            </label>
          </div>
        </div>

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

    <!-- Right Column: Info -->
    <div class="pv-form-right" v-if="!isManualLike">
      <template v-if="model.tipe_pv === 'Lainnya'">
        <MemoPembayaranInfo :memo="selectedMemo" />
      </template>
      <template v-else>
        <PurchaseOrderInfo :purchase-order="selectedPO" />
      </template>
    </div>

    <div class="pv-form-right" v-else>
      <div class="space-y-6">
        <div class="floating-input">
          <div class="flex gap-2 items-start">
            <div class="flex-1">
              <CustomSelect
                v-model="model.supplier_id"
                :options="(props.supplierOptions || []).map((s:any)=>({ label: s.label || s.nama_supplier || s.name, value: s.value ?? s.id }))"
                placeholder="Pilih Supplier"
                :searchable="true"
              >
                <template #label>
                  Supplier<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
            </div>
            <button
              type="button"
              @click="showCreateSupplier = true"
              :disabled="!(props.banks && props.banks.length)"
              class="px-4 py-2 w-12 h-12 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
              title="Tambah Supplier"
            >
              +
            </button>
          </div>
        </div>

        <div class="floating-input">
          <input
            v-model="model.supplier_phone"
            type="text"
            class="floating-input-field"
            placeholder=" "
          />
          <label class="floating-label">No Telepon</label>
        </div>

        <div class="floating-input">
          <input
            v-model="model.supplier_address"
            type="text"
            class="floating-input-field"
            placeholder=" "
          />
          <label class="floating-label">Alamat</label>
        </div>

        <div class="floating-input">
          <CustomSelect
            v-model="model.bank_supplier_account_id"
            :options="supplierBankAccountOptions"
            placeholder="Pilih Nama Rekening"
            :disabled="!selectedSupplier || !selectedSupplier.bank_accounts || !selectedSupplier.bank_accounts.length"
          >
            <template #label> Nama Pemilik Rekening </template>
          </CustomSelect>
        </div>
          <div class="floating-input">
            <input
              v-model="model.supplier_bank_name"
              type="text"
              class="floating-input-field"
              placeholder=" "
            />
            <label class="floating-label">Nama Bank</label>
          </div>
          <div class="floating-input">
            <input
              v-model="model.supplier_account_number"
              type="text"
              class="floating-input-field"
              placeholder=" "
            />
            <label class="floating-label">No Rekening</label>
          </div>
      </div>
    </div>

    <!-- Selection Modal -->
    <MemoPembayaranSelectionModal
      v-if="model.tipe_pv === 'Lainnya'"
      v-model:open="showMemoSelection"
      :memos="availableMemos || []"
      :selected-ids="model.memo_id ? [model.memo_id] : []"
      :no-results-message="'Tidak ada Memo Pembayaran yang tersedia'"
      @search="handleMemoSearch"
      @add-selected="handleAddMemo"
    />
    <PurchaseOrderSelectionModal
      v-else-if="!isManualLike"
      v-model:open="showPOSelection"
      :purchase-orders="availablePOs || []"
      :selected-ids="model.purchase_order_id ? [model.purchase_order_id] : []"
      :no-results-message="'Tidak ada Purchase Order yang tersedia'"
      @search="handlePOSearch"
      @add-selected="handleAddPO"
    />

    <!-- Create Supplier Modal -->
    <SupplierForm
      v-if="showCreateSupplier"
      :asModal="true"
      :editData="undefined"
      :banks="props.banks || []"
      :departmentOptions="props.departmentOptions || []"
      @created="handleSupplierCreated"
      @close="() => { showCreateSupplier = false; emit('refresh-suppliers'); }"
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
