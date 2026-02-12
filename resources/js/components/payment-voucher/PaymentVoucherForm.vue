<script setup lang="ts">
import { computed, watch, ref, onMounted, nextTick } from "vue";
import CustomSelect from "../ui/CustomSelect.vue";
// import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import PurchaseOrderSelectionModal from "./PurchaseOrderSelectionModal.vue";
import PurchaseOrderInfo from "../PurchaseOrderInfo.vue";
import PurchaseOrderAnggaranInfo from "../PurchaseOrderAnggaranInfo.vue";
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
  // New for Anggaran
  bisnisPartnerOptions?: any[];
  poAnggaranOptions?: any[];
  availablePoAnggarans?: any[];
  readOnly?: boolean;
}>();

const emit = defineEmits<{
  "search-purchase-orders": [search: string];
  "add-purchase-order": [po: any];
  "search-memos": [search: string];
  "add-memo": [memo: any];
  "refresh-suppliers": [];
  // New for Anggaran
  "search-po-anggaran": [search: string];
}>();

// Modal state
const showPOSelection = ref(false);
const showMemoSelection = ref(false);
const showCreateSupplier = ref(false);
const pendingNewSupplierEmail = ref<string | null>(null);

// Selected PO detail: fetch full JSON when an ID is chosen so financial info is complete
const selectedPODetail = ref<any | null>(null);

// Get selected PO for info display (prefer detailed JSON if loaded)
const selectedPO = computed(() => {
  const id = model.value?.purchase_order_id;
  if (!id) return null;
  return selectedPODetail.value
    ? selectedPODetail.value
    : (props.availablePOs || []).find((po) => String(po.id) === String(id)) || null;
});


// Fetch PO detail whenever selection changes
watch(
  () => model.value?.purchase_order_id,
  async (id) => {
    selectedPODetail.value = null;
    if (!id) return;
    try {
      const res = await fetch(`/purchase-orders/${id}/json`, { headers: { Accept: 'application/json' } });
      if (res.ok) {
        const data = await res.json();
        // Ensure essential numeric fields are numbers
        data.total = Number(data.total || 0);
        data.diskon = Number(data.diskon || 0);
        data.ppn_nominal = Number(data.ppn_nominal || 0);
        data.pph_nominal = Number(data.pph_nominal || 0);
        data.grand_total = Number(data.grand_total ?? data.total ?? 0);
        selectedPODetail.value = data;
      }
    } catch (e) {
      // swallow; UI will fallback to lightweight PO in availablePOs
      console.error('Failed to load PO detail for PV panel', e);
    }
  },
  { immediate: true }
);

watch(
  () => (model.value as any)?.bisnis_partner_id,
  (newVal, oldVal) => {
    if (oldVal === undefined) return;
    if (String(model.value?.tipe_pv || "") !== "Anggaran") return;
    model.value = {
      ...(model.value || {}),
      po_anggaran_id: undefined,
      nominal: isManualLike.value ? model.value?.nominal : 0,
      nominal_text: isManualLike.value ? model.value?.nominal_text : undefined,
      perihal_id: undefined,
    } as any;
  }
);

// Get selected Memo for info display
const selectedMemo = computed(() => {
  if (!model.value?.memo_id || !props.availableMemos) return null;
  return (props.availableMemos || []).find((m: any) => m.id === model.value.memo_id);
});

// Get selected Po Anggaran for info display (tipe PV Anggaran)
const selectedPoAnggaran = computed(() => {
  const id = (model.value as any)?.po_anggaran_id;
  if (!id || !props.availablePoAnggarans) return null;
  return (props.availablePoAnggarans || []).find((p: any) => String(p.id) === String(id)) || null;
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

onMounted(() => {
  try {
    if (model.value?.supplier_id) {
      // If phone/address empty on initial load but supplier is selected, fill them
      if (!model.value?.supplier_phone || !model.value?.supplier_address) {
        applySelectedSupplierInfo();
      }
      // If exactly one account and no selection yet, set it and apply
      const s = (props.supplierOptions || []).find(
        (x: any) => String(x.value || x.id) === String(model.value?.supplier_id)
      );
      const accounts = (s?.bank_accounts || []) as any[];
      if (Array.isArray(accounts) && accounts.length === 1 && !model.value?.bank_supplier_account_id) {
        model.value = {
          ...(model.value || {}),
          bank_supplier_account_id: accounts[0]?.id != null ? String(accounts[0].id) : undefined,
        };
        nextTick(() => applySelectedBankAccount());
      }
    }
    // When editing an existing PV that already has a selected PO but nominal is 0 or empty,
    // initialize nominal from the selected PO using the same logic as when the user picks a PO.
    try {
      const poId = model.value?.purchase_order_id;
      const tipe = String(model.value?.tipe_pv || "");
      const isPoBased = tipe !== "Manual" && tipe !== "Pajak" && tipe !== "Anggaran";
      const existingNominal = Number((model.value as any)?.nominal ?? 0);
      if (poId && isPoBased && (!Number.isFinite(existingNominal) || existingNominal === 0)) {
        // Best-effort: this will only have effect once availablePOs is populated
        // and will be a no-op otherwise.
        applyPurchaseOrderSelection(poId as any, { force: true }).catch(() => {});
      }
    } catch {}
  } catch {}
});

function handlePOSearch(search: string) {
  emit("search-purchase-orders", search);
}

function handleAddPO(payload: any) {
  // payload may be a PO object or { po, bpb }
  emit("add-purchase-order", payload);
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

function handleSearchPoAnggaran(search: string) {
  emit("search-po-anggaran", search);
}


// const metodeBayarOptions = [
//   { value: "Transfer", label: "Transfer" },
//   { value: "Kartu Kredit", label: "Kartu Kredit" },
// ];

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
  const tgl = model.value?.tanggal;

  const safeFormat = (date: string | Date) => {
    try {
      return new Date(date).toLocaleDateString("id-ID", {
        day: "2-digit",
        month: "short",
        year: "numeric",
      });
    } catch {
      return typeof date === "string" ? date : "";
    }
  };

  return tgl ? safeFormat(tgl) : safeFormat(new Date());
});

// Determine if current tipe behaves like Manual (Manual or Pajak)
const isManualLike = computed(() => {
  return String(model.value?.tipe_pv || "") === "Manual" || String(model.value?.tipe_pv || "") === "Pajak";
});

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

const hasSupplierBankAccounts = computed(() => {
  const s: any = selectedSupplier.value;
  const accounts = (s?.bank_accounts || []) as any[];
  return Array.isArray(accounts) && accounts.length > 0;
});

const supplierBankAccountOptions = computed(() => {
  const s: any = selectedSupplier.value;
  if (!s || !Array.isArray(s.bank_accounts)) return [];
  return s.bank_accounts.map((ba: any) => ({
    label: ba?.nama_rekening || ba?.account_name || ba?.atas_nama || ba?.owner_name || `Rekening`,
    value: ba?.id != null ? String(ba.id) : "",
  }));
});

// Nominal should always be read-only for Reguler, Anggaran, Lainnya, and DP
const isNominalReadOnly = computed(() => {
  const tipe = String(model.value?.tipe_pv || "");
  return tipe === "Reguler" || tipe === "Anggaran" || tipe === "Lainnya" || tipe === "DP";
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
    try {
      const s = (props.supplierOptions || []).find(
        (x: any) => String(x.value || x.id) === String(model.value?.supplier_id)
      );
      const accounts = (s?.bank_accounts || []) as any[];
      if (
        Array.isArray(accounts) && accounts.length === 1 &&
        (model.value?.bank_supplier_account_id === undefined || model.value?.bank_supplier_account_id === null || model.value?.bank_supplier_account_id === "")
      ) {
        model.value = {
          ...(model.value || {}),
          bank_supplier_account_id: accounts[0]?.id != null ? String(accounts[0].id) : undefined,
        };
        applySelectedBankAccount();
      }
    } catch {}
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
  // If supplier has exactly one account and v-model is empty, set the select value too
  if (accounts.length === 1 && (model.value?.bank_supplier_account_id === undefined || model.value?.bank_supplier_account_id === null || model.value?.bank_supplier_account_id === "")) {
    model.value = {
      ...(model.value || {}),
      bank_supplier_account_id: acc?.id != null ? String(acc.id) : model.value?.bank_supplier_account_id,
    };
  }
}


// Manual nominal input handling: allow decimals while typing; format on blur (1,234.56)
const nominalInput = ref<string>("");
const isTypingNominal = ref<boolean>(false);

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
  // Clamp against PO outstanding for tipe Reguler (when available)
  try {
    const tipe = String(model.value?.tipe_pv || "");
    // DP: clamp ke maksimum DP PO (dp_remaining/dp_nominal) bila ada
    if (tipe === 'DP' && model.value?.purchase_order_id && Array.isArray(props.availablePOs)) {
      const po = (props.availablePOs || []).find((p:any)=> String(p.id) === String(model.value?.purchase_order_id));
      const dpRemaining = Number((po as any)?.dp_remaining ?? NaN);
      const dpNominal = Number((po as any)?.dp_nominal ?? NaN);
      const capBase = Number.isFinite(dpRemaining) && dpRemaining > 0
        ? dpRemaining
        : (Number.isFinite(dpNominal) && dpNominal > 0 ? dpNominal : 0);
      const current = parseNominalInput(model.value?.nominal_text || '') ?? 0;
      const clamped = Math.min(current, Math.max(capBase, 0));
      model.value = { ...(model.value || {}), nominal: clamped, nominal_text: String(clamped) } as any;
    } else if (tipe === 'Reguler' && model.value?.purchase_order_id && Array.isArray(props.availablePOs)) {
      const po = (props.availablePOs || []).find((p:any)=> String(p.id) === String(model.value?.purchase_order_id));
      const outPO = Number((po as any)?.outstanding ?? NaN);
      // Prefer allocations jika ada
      const bpbAllocs = (model.value as any)?.bpb_allocations || (model.value as any)?._bpbAllocations || [];
      const memoAllocs = (model.value as any)?.memo_allocations || (model.value as any)?._memoAllocations || [];
      const sumBpbAlloc = Array.isArray(bpbAllocs) ? bpbAllocs.reduce((s:number,a:any)=> s + (Number(a?.amount)||0), 0) : 0;
      const sumMemoAlloc = Array.isArray(memoAllocs) ? memoAllocs.reduce((s:number,a:any)=> s + (Number(a?.amount)||0), 0) : 0;
      // Fallback to selected docs totals when allocations absent
      const sumBpb = sumBpbAlloc > 0 ? sumBpbAlloc : (Array.isArray((model.value as any)?._bpbs) ? ((model.value as any)?._bpbs || []).reduce((s:number,b:any)=> s + (Number(b?.grand_total)||0), 0) : 0);
      const sumMemo = sumMemoAlloc > 0 ? sumMemoAlloc : (Array.isArray((model.value as any)?._memos) ? ((model.value as any)?._memos || []).reduce((s:number,m:any)=> s + (Number(m?.total)||0), 0) : 0);
      const cap = sumBpb > 0 ? sumBpb : (sumMemo > 0 ? sumMemo : (Number.isFinite(outPO) ? Math.max(outPO,0) : 0));
      const current = parseNominalInput(model.value?.nominal_text || '') ?? 0;
      const clamped = Math.min(current, Math.max(cap, 0));
      model.value = { ...(model.value || {}), nominal: clamped, nominal_text: String(clamped) } as any;
    } else if (tipe === 'Anggaran' && (model.value as any)?.po_anggaran_id && Array.isArray(props.availablePoAnggarans)) {
      const poa = (props.availablePoAnggarans || []).find((x:any)=> String(x.id) === String((model.value as any)?.po_anggaran_id));
      const out = Number((poa as any)?.outstanding ?? NaN);
      if (!Number.isNaN(out) && Number.isFinite(out)) {
        const current = parseNominalInput(model.value?.nominal_text || '') ?? 0;
        const clamped = Math.min(current, Math.max(out, 0));
        model.value = { ...(model.value || {}), nominal: clamped, nominal_text: String(clamped) } as any;
      }
    }
  } catch {}
  nominalInput.value = formatNominalTextPreserve(model.value?.nominal_text || "");
}

// const supplierBankAccountOptions = computed(() => {
//   if (!selectedSupplier.value?.bank_accounts) return [];
//   return selectedSupplier.value.bank_accounts.map((ba: any, idx: number) => ({
//     label: `${ba.account_name || "-"}`,
//     value: String(idx),
//   }));
// });

// Determine if selected department is the special "All" department
const isAllDepartment = computed(() => {
  const deptId = model.value?.department_id;
  if (!deptId) return false;
  const d = (props.departmentOptions || []).find(
    (x: any) => String(x.value ?? x.id) === String(deptId)
  );
  const name = String(d?.name || d?.label || "").toLowerCase();
  return name === "all";
});

const filteredSupplierOptions = computed(() => {
  if (!model.value?.department_id) return props.supplierOptions || [];
  if (isAllDepartment.value) return props.supplierOptions || [];
  const target = String(model.value.department_id);
  return (props.supplierOptions || []).filter(
    (s: any) => String(s.department_id) === target || Boolean(s?.is_all)
  );
});

// Filter Bisnis Partner (simple list; if needed can filter by dept later)
const filteredBisnisPartnerOptions = computed(() => {
  return props.bisnisPartnerOptions || [];
});

// Cache the perihal names that require Bisnis Partner for faster lookup
const bisnisPartnerPerihals = new Set([
  'top up flazz bca',
  'top up kartu kredit',
  'pembayaran dividen',
  'biaya admin',
  'biaya admin business debit card',
  'biaya pajak',
  'tarikan tunai kas kecil'
]);

// Check if selected perihal requires Bisnis Partner instead of Supplier (for Manual type)
const shouldUseBisnisPartner = computed(() => {
  // Quick exit for non-Manual types
  if (String(model.value?.tipe_pv || "") !== "Manual") return false;
  if (!model.value?.perihal_id) return false;

  // Cache the perihal options lookup to avoid repeated array searches
  const perihalOptions = props.perihalOptions || [];
  if (!perihalOptions.length) return false;

  const selectedPerihal = perihalOptions.find(
    (p: any) => String(p.value ?? p.id) === String(model.value?.perihal_id)
  );

  if (!selectedPerihal) return false;

  const perihalName = String(selectedPerihal.label || selectedPerihal.nama || "").toLowerCase();

  return bisnisPartnerPerihals.has(perihalName);
});

const filteredCreditCardOptions = computed(() => {
  if (!model.value?.department_id) return props.creditCardOptions || [];
  if (isAllDepartment.value) return props.creditCardOptions || [];
  const target = String(model.value.department_id);
  return (props.creditCardOptions || []).filter(
    (c: any) => String(c.department_id) === target || Boolean(c?.is_all)
  );
});

watch(
  () => model.value?.department_id,
  (newDept, oldDept) => {
    // Ignore initial mount and no-op changes where department does not actually change
    if (oldDept === undefined) return;
    if (String(newDept ?? '') === String(oldDept ?? '')) return;
    const supplierMatchesDept = selectedSupplier.value
      ? String(selectedSupplier.value.department_id) === String(newDept ?? '') || Boolean(selectedSupplier.value?.is_all)
      : false;
    const creditCardMatchesDept = selectedCreditCard.value
      ? String(selectedCreditCard.value.department_id) === String(newDept ?? '') || Boolean(selectedCreditCard.value?.is_all)
      : false;
    model.value = {
      ...(model.value || {}),
      purchase_order_id: undefined,
      po_anggaran_id: undefined,
      memo_id: undefined,
      nominal: isManualLike.value ? model.value?.nominal : 0,
      supplier_id: supplierMatchesDept ? model.value?.supplier_id : undefined,
      credit_card_id: creditCardMatchesDept ? model.value?.credit_card_id : undefined,
      bisnis_partner_id: undefined,
    };
  }
);

// Watch supplier changes
watch(
  () => model.value?.supplier_id,
  (newVal, oldVal) => {
    if (!newVal) {
      model.value = {
        ...(model.value || {}),
        supplier_bank_account_index: undefined,
        bank_supplier_account_id: undefined,
        purchase_order_id: undefined,
        memo_id: undefined,
        nominal: isManualLike.value ? model.value?.nominal : 0,
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
    const currentDept = model.value?.department_id;
    const shouldAdoptSupplierDept = !currentDept && s.department_id != null;
    const nextDepartmentId = shouldAdoptSupplierDept
      ? s.department_id
      : s?.is_all
        ? currentDept
        : (currentDept && String(currentDept) !== String(s.department_id) ? s.department_id : currentDept);
    const normalizedDept = nextDepartmentId != null ? String(nextDepartmentId) : nextDepartmentId;

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
      // Jika ini load awal (oldVal === undefined) dan sudah ada bank_supplier_account_id dari backend,
      // jangan override pilihan rekening yang sudah tersimpan.
      const nextBankAccountId =
        oldVal === undefined && model.value?.bank_supplier_account_id
          ? model.value.bank_supplier_account_id
          : accounts[0]?.id != null
          ? String(accounts[0].id)
          : undefined;

      model.value = {
        ...model.value,
        bank_supplier_account_id: nextBankAccountId,
        department_id: normalizedDept ?? model.value?.department_id,
        // Only clear selections when supplier actually changes after mount
        purchase_order_id: oldVal !== undefined ? undefined : model.value?.purchase_order_id,
        memo_id: oldVal !== undefined ? undefined : model.value?.memo_id,
        nominal: isManualLike.value ? model.value?.nominal : 0,
      };

      // Hanya applySelectedBankAccount jika kita benar-benar mengubah/menetapkan rekening dari kode ini
      if (nextBankAccountId && nextBankAccountId !== model.value?.bank_supplier_account_id) {
        applySelectedBankAccount();
      } else if (oldVal === undefined && model.value?.bank_supplier_account_id) {
        // Untuk kasus edit awal dengan rekening sudah ada, tetap sinkronkan field tampilan
        applySelectedBankAccount();
      }
    } else {
      // Untuk supplier dengan banyak rekening, pada load awal jangan menghapus
      // pilihan rekening yang sudah ada dari backend. Hanya reset ketika user
      // benar-benar mengganti supplier (oldVal !== undefined).
      if (oldVal === undefined) {
        model.value = {
          ...model.value,
          department_id: normalizedDept ?? model.value?.department_id,
          nominal: isManualLike.value ? model.value?.nominal : 0,
        };
      } else {
        model.value = {
          ...model.value,
          bank_supplier_account_id: undefined,
          department_id: normalizedDept ?? model.value?.department_id,
          // Only clear selections when supplier actually changes after mount
          purchase_order_id: oldVal !== undefined ? undefined : model.value?.purchase_order_id,
          memo_id: oldVal !== undefined ? undefined : model.value?.memo_id,
          nominal: isManualLike.value ? model.value?.nominal : 0,
          supplier_account_name: undefined,
          supplier_bank_name: undefined,
          supplier_account_number: undefined,
        };
      }
    }
  },
  { immediate: true }
);

// Watch bank account id changes (Manual flow)
watch(
  () => model.value?.bank_supplier_account_id,
  (val) => {
    if (val === undefined || val === null || val === "") return;
    applySelectedBankAccount();
  },
  { immediate: true }
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
        nominal: model.value?.nominal,
      };
      if (val === 'Pajak' && !model.value?.pajak_channel) {
        model.value = { ...(model.value || {}), pajak_channel: 'Bank' };
      }
    }
  }
);

// Watch perihal changes for Manual type to handle Supplier/Bisnis Partner switching
watch(
  () => [model.value?.perihal_id, model.value?.tipe_pv],
  ([newPerihalId, newTipePv], [oldPerihalId, oldTipePv]) => {
    if (String(newTipePv || "") !== "Manual") return;

    // Only proceed if perihal actually changed
    if (newPerihalId === oldPerihalId && newTipePv === oldTipePv) return;

    if (shouldUseBisnisPartner.value) {
      // Switch to Bisnis Partner mode - clear supplier fields only if they exist
      if (model.value?.supplier_id) {
        model.value = {
          ...(model.value || {}),
          supplier_id: undefined,
          supplier_name: undefined,
          supplier_phone: undefined,
          supplier_address: undefined,
          supplier_account_name: undefined,
          supplier_bank_name: undefined,
          supplier_account_number: undefined,
          bank_supplier_account_id: undefined,
        };
      }
    } else {
      // Switch to Supplier mode - clear bisnis partner field only if it exists
      if (model.value?.bisnis_partner_id) {
        model.value = {
          ...(model.value || {}),
          bisnis_partner_id: undefined,
        };
      }
    }
  }
);

// Watch Bisnis Partner changes for Manual type
watch(
  () => (model.value as any)?.bisnis_partner_id,
  (newVal) => {
    if (!newVal) return;

    const selectedBP = (props.bisnisPartnerOptions || []).find(
      (bp: any) => String(bp.value ?? bp.id) === String(newVal)
    );

    console.log('Selected BP data:', selectedBP);

    if (!selectedBP) return;

    // Auto-fill contact and bank details from Bisnis Partner
    // Get bank name from bank relationship if available
    let bankName = "";
    if (selectedBP.bank && selectedBP.bank.nama_bank) {
      bankName = selectedBP.bank.nama_bank;
    } else if (selectedBP.bank_id && props.banks) {
      const bank = props.banks.find((b: any) => String(b.id) === String(selectedBP.bank_id));
      bankName = bank?.nama_bank || bank?.name || "";
    }

    const updates = {
      // Contact details from Bisnis Partner
      supplier_name: selectedBP.nama_bp || selectedBP.nama || selectedBP.label || "",
      supplier_phone: selectedBP.no_telepon || selectedBP.phone || "",
      supplier_address: selectedBP.alamat || selectedBP.address || "",
      // Bank details from Bisnis Partner
      supplier_bank_name: bankName,
      supplier_account_name: selectedBP.nama_rekening || "",
      supplier_account_number: selectedBP.no_rekening_va || "",
    };

    model.value = {
      ...(model.value || {}),
      ...updates,
    };
  },
  { immediate: true }
);

// Watch Po Anggaran selection (Anggaran type)
watch(
  () => (model.value as any)?.po_anggaran_id,
  (id) => {
    if (id && props.availablePoAnggarans) {
      const poa = (props.availablePoAnggarans || []).find((x: any) => String(x.id) === String(id));
      if (poa) {
        const nominalFromPoa = Number((poa as any)?.outstanding ?? poa.nominal ?? 0) || 0;
        const updates: any = {
          nominal: nominalFromPoa,
          nominal_text: String(nominalFromPoa),
          perihal_id: poa.perihal?.id,
          bisnis_partner_id: poa.bisnis_partner?.id ?? poa.bisnis_partner_id ?? (model.value as any)?.bisnis_partner_id,
        };
        model.value = {
          ...(model.value || {}),
          ...updates,
        } as any;
      }
    }
    if (!id) {
      model.value = { ...(model.value || {}), bisnis_partner_id: undefined } as any;
    }
  }
);

watch(
  () => model.value?.credit_card_id,
  () => {
    const c = selectedCreditCard.value;
    if (!c) return;
    try {
      // When a credit card is selected, mirror its bank/name/number into the manual fields
      const bankName = c?.bank?.nama_bank || resolveBankNameById(c?.bank_id) || c?.bank_name || "";
      const accountNumber = c?.no_kartu_kredit || c?.card_number || "";
      const accountName = c?.nama_pemilik || c?.owner_name || "";
      model.value = {
        ...(model.value || {}),
        supplier_bank_name: bankName,
        supplier_account_number: accountNumber,
        supplier_account_name: accountName,
      };
    } catch {}
  },
  { immediate: true }
);

watch(
  () => model.value?.metode_bayar,
  (val, oldVal) => {
    if (oldVal === undefined) return;

    const keep = { ...(model.value || {}) };
    keep.purchase_order_id = undefined;
    keep.nominal = isManualLike.value ? model.value?.nominal : 0;

    if (val === "Transfer") {
      // Transfer flow keeps supplier and selected bank account
      keep.credit_card_id = undefined;
    } else if (val === "Kartu Kredit") {
      keep.supplier_id = undefined;
      keep.supplier_bank_account_index = undefined;
      keep.bank_supplier_account_id = undefined;
    }
    model.value = keep;
  }
);

// Watch PO selection
async function applyPurchaseOrderSelection(poId: any, options: { force?: boolean } = {}) {
  const force = options.force ?? false;
  if (!poId || !props.availablePOs) return;
  const selectedPO = props.availablePOs.find((po) => String(po.id) === String(poId));
  if (!selectedPO) return;

  // When not forced, do not override an existing positive nominal (e.g. user-edited value)
  if (!force) {
    const existingNominal = Number((model.value as any)?.nominal ?? 0);
    if (Number.isFinite(existingNominal) && existingNominal > 0) {
      return;
    }
  }

  // Clear previous children and allocations immediately to avoid stale UI
  model.value = {
    ...(model.value || {}),
    _bpbs: undefined,
    _memos: undefined,
    _bpbAllocations: undefined,
    _memoAllocations: undefined,
    bpb_allocations: undefined,
    memo_allocations: undefined,
  } as any;

  const tipe = String(model.value?.tipe_pv || "");
  const updates: any = { perihal_id: selectedPO.perihal_id || selectedPO.perihal?.id };

  // DP PV: tidak ada BPB/Memo allocations, nominal diambil dari DP PO
  if (tipe === 'DP') {
    const dpRemaining = Number((selectedPO as any)?.dp_remaining ?? NaN);
    const dpNominal = Number((selectedPO as any)?.dp_nominal ?? NaN);
    const fallback = (Number((selectedPO as any)?.outstanding ?? 0)
      || Number((selectedPO as any)?.grand_total ?? 0)
      || Number((selectedPO as any)?.total ?? 0)
      || 0);
    const nominal = Number.isFinite(dpRemaining) && dpRemaining > 0
      ? dpRemaining
      : (Number.isFinite(dpNominal) && dpNominal > 0 ? dpNominal : fallback);
    updates.nominal = nominal;
    // Keep nominal_text in sync so input menampilkan nilai DP dengan benar
    updates.nominal_text = String(nominal);
    // DP tidak menggunakan BPB/Memo allocations
    updates._bpbs = undefined;
    updates._memos = undefined;
    updates.bpb_allocations = undefined;
    updates.memo_allocations = undefined;
    updates._bpbAllocations = undefined;
    updates._memoAllocations = undefined;
  } else {
    try {
      const [bpbsRes, memosRes] = await Promise.all([
        fetch(`/payment-voucher/purchase-orders/${poId}/bpbs`, { credentials: 'include' }),
        fetch(`/payment-voucher/purchase-orders/${poId}/memos`, { credentials: 'include' }),
      ]);
      const bpbsJson = bpbsRes.ok ? await bpbsRes.json() : { data: [] };
      const memosJson = memosRes.ok ? await memosRes.json() : { data: [] };
      const bpbs = Array.isArray(bpbsJson?.data) ? bpbsJson.data : [];
      const memos = Array.isArray(memosJson?.data) ? memosJson.data : [];

      if (bpbs.length > 0) {
        const allocs = bpbs.map((b:any)=> ({ bpb_id: b.id, amount: Number(b.outstanding ?? b.grand_total ?? 0) || 0 }));
        const sumAlloc = allocs.reduce((s:number,a:any)=> s + (Number(a.amount)||0), 0);
        updates.bpb_allocations = allocs;
        updates._bpbAllocations = allocs;
        updates._bpbs = bpbs;
        updates.nominal = sumAlloc;
        updates.memo_allocations = undefined;
        updates._memoAllocations = undefined;
      } else if (memos.length > 0) {
        const allocs = memos.map((m:any)=> ({ memo_id: m.id, amount: Number(m.outstanding ?? m.total ?? 0) || 0 }));
        const sumAlloc = allocs.reduce((s:number,a:any)=> s + (Number(a.amount)||0), 0);
        updates.memo_allocations = allocs;
        updates._memoAllocations = allocs;
        updates._memos = memos;
        updates._bpbs = undefined;
        const hint = Number((model.value as any)?.nominal) || sumAlloc;
        updates.nominal = Math.min(hint, sumAlloc);
        updates.bpb_allocations = undefined;
        updates._bpbAllocations = undefined;
      } else {
        const fallback = (Number((selectedPO as any)?.outstanding ?? 0)
          || Number((selectedPO as any)?.grand_total ?? 0)
          || Number((selectedPO as any)?.total ?? 0)
          || 0);
        updates.nominal = fallback;
        updates._bpbs = undefined;
        updates._memos = undefined;
        updates.bpb_allocations = undefined;
        updates.memo_allocations = undefined;
        updates._bpbAllocations = undefined;
        updates._memoAllocations = undefined;
      }
    } catch {}
  }

  // Sinkronkan nominal_text jika nominal sudah di-set agar input langsung terisi
  if (typeof updates.nominal === 'number' && !Number.isNaN(updates.nominal)) {
    updates.nominal_text = String(updates.nominal);
  }

  model.value = { ...(model.value || {}), ...updates };
}

watch(
  () => model.value?.purchase_order_id,
  async (poId) => {
    await applyPurchaseOrderSelection(poId, { force: true });
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
  <div class="grid grid-cols-1 gap-4 md:grid-cols-2 md:gap-6">
    <!-- Left Column: Form -->
    <div class="pv-form-left">
      <div class="space-y-4">
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
          <div class="flex flex-wrap gap-3 md:gap-6">
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
            <label class="flex items-center">
              <input
                type="radio"
                v-model="model.tipe_pv"
                value="DP"
                class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
              />
              <span class="ml-2 text-sm text-gray-700">DP</span>
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
        <!-- <div class="floating-input">
          <CustomSelect
            v-model="model.metode_bayar"
            :options="metodeBayarOptions"
            placeholder="Pilih Metode"
          >
            <template #label> Metode Bayar<span class="text-red-500">*</span> </template>
          </CustomSelect>
        </div> -->

        <!-- Nama Supplier / Nama Kredit / Bisnis Partner (Anggaran) -->
        <div class="floating-input" v-if="!isManualLike">
          <!-- Anggaran: Bisnis Partner -->
          <template v-if="model.tipe_pv === 'Anggaran'">
            <CustomSelect
              v-model="(model as any).bisnis_partner_id"
              :options="(filteredBisnisPartnerOptions || []).map((bp:any)=>({ label: bp.label, value: bp.value }))"
              placeholder="Pilih Bisnis Partner"
              :searchable="true"
              :disabled="!model.department_id"
            >
              <template #label>
                Bisnis Partner<span class="text-red-500">*</span>
              </template>
            </CustomSelect>
          </template>
          <!-- Kredit: Nama Pemilik Kredit -->
          <template v-else-if="model.metode_bayar === 'Kartu Kredit'">
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

        <!-- Purchase Order / Memo Pembayaran / Po Anggaran Selection -->
        <div v-if="!isManualLike" class="floating-input space-y-2">
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
              <template v-else-if="model.tipe_pv === 'Anggaran'">
                <CustomSelect
                  v-model="(model as any).po_anggaran_id"
                  :options="(poAnggaranOptions || []).map((p:any)=>({ value: p.value ?? p.id, label: p.label ?? p.no_po_anggaran ?? '' }))"
                  placeholder="Pilih PO Anggaran"
                  :searchable="true"
                  :disabled="!model.department_id"
                  @search="handleSearchPoAnggaran"
                >
                  <template #label>
                    PO Anggaran<span class="text-red-500">*</span>
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
              v-else-if="model.tipe_pv !== 'Anggaran'"
              @click="openPurchaseOrderModal"
              :disabled="!model.department_id"
              class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              +
            </button>
          </div>
        </div>

        <!-- Manual-like (Pajak/Manual) fields -->
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

        <!-- Nominal for non-manual types (Reguler, Anggaran, Lainnya) -->
        <div v-if="!isManualLike" class="floating-input">
          <input
            v-model="nominalInput"
            type="text"
            inputmode="decimal"
            class="floating-input-field"
            placeholder=" "
            :readonly="isNominalReadOnly"
            :class="{ 'bg-gray-50 text-gray-600 cursor-not-allowed': isNominalReadOnly }"
            @focus="handleNominalFocus"
            @input="handleNominalInput"
            @blur="handleNominalBlur"
          />
          <label class="floating-label">Nominal</label>
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
      <template v-else-if="model.tipe_pv === 'Anggaran'">
        <PurchaseOrderAnggaranInfo :po-anggaran="selectedPoAnggaran" />
      </template>
      <template v-else>
        <PurchaseOrderInfo
          :purchase-order="selectedPO"
          :bpbs="model._bpbs"
          :memos="model._memos"
          :bpb-allocations="model._bpbAllocations || model.bpb_allocations"
          :memo-allocations="model._memoAllocations || model.memo_allocations"
          :show-financial="true"
        />
      </template>
    </div>

    <div class="pv-form-right" v-else>
      <div class="space-y-4">
        <template v-if="model.metode_bayar === 'Kartu Kredit'">
          <!-- Kredit mode: choose credit card account instead of supplier -->
          <div class="floating-input">
            <CustomSelect
              v-model="model.credit_card_id"
              :options="filteredCreditCardOptions.map((c:any)=>({ label: c.label || c.card_number || c.no_kartu_kredit, value: c.value ?? c.id }))"
              placeholder="Pilih Nama Rekening Kredit"
              :searchable="true"
            >
              <template #label> Nama Rekening Kredit<span class="text-red-500">*</span> </template>
            </CustomSelect>
          </div>
        </template>
        <template v-else>
          <!-- Bisnis Partner field for specific perihals in Manual type -->
          <div class="floating-input" v-if="shouldUseBisnisPartner">
            <CustomSelect
              v-model="(model as any).bisnis_partner_id"
              :options="(filteredBisnisPartnerOptions || []).map((bp:any)=>({ label: bp.label, value: bp.value }))"
              placeholder="Pilih Bisnis Partner"
              :searchable="true"
            >
              <template #label>
                Bisnis Partner<span class="text-red-500">*</span>
              </template>
            </CustomSelect>
          </div>
          <!-- Regular Supplier field for other perihals -->
          <div class="floating-input" v-else>
            <div class="flex gap-2 items-start">
              <div class="flex-1">
                <CustomSelect
                  v-model="model.supplier_id"
                  :options="(filteredSupplierOptions || []).map((s:any)=>({ label: s.label || s.nama_supplier || s.name, value: s.value ?? s.id }))"
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

          <!-- Bisnis Partner contact fields - readonly and auto-filled -->
          <template v-if="shouldUseBisnisPartner">
            <div class="floating-input">
              <input
                v-model="model.supplier_phone"
                type="text"
                class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                readonly
                placeholder=" "
              />
              <label class="floating-label">No Telepon</label>
            </div>

            <div class="floating-input">
              <input
                v-model="model.supplier_address"
                type="text"
                class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                readonly
                placeholder=" "
              />
              <label class="floating-label">Alamat</label>
            </div>
          </template>

          <!-- Supplier contact fields - only show when using regular supplier -->
          <template v-else>
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
          </template>
        </template>

        <div class="floating-input">
          <template v-if="model.metode_bayar === 'Kartu Kredit'">
            <!-- In Kredit mode, bank/name/number are auto from card; no supplier account select -->
          </template>
          <template v-else-if="shouldUseBisnisPartner">
            <!-- In Bisnis Partner mode, show readonly bank fields -->
            <div class="floating-input">
              <input
                v-model="model.supplier_account_name"
                type="text"
                class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                readonly
                placeholder=" "
              />
              <label class="floating-label">Nama Pemilik Rekening</label>
            </div>
          </template>
          <template v-else>
            <CustomSelect
              v-model="model.bank_supplier_account_id"
              :options="supplierBankAccountOptions"
              placeholder="Pilih Nama Rekening"
              :disabled="!selectedSupplier || !selectedSupplier.bank_accounts || !selectedSupplier.bank_accounts.length"
            >
              <template #label> Nama Pemilik Rekening </template>
            </CustomSelect>
          </template>
        </div>

        <!-- Bank fields for Bisnis Partner - readonly and auto-filled -->
        <template v-if="shouldUseBisnisPartner && model.metode_bayar !== 'Kartu Kredit'">
          <div class="floating-input">
            <input
              v-model="model.supplier_bank_name"
              type="text"
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              readonly
              placeholder=" "
            />
            <label class="floating-label">Nama Bank</label>
          </div>
          <div class="floating-input">
            <input
              v-model="model.supplier_account_number"
              type="text"
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              readonly
              placeholder=" "
            />
            <label class="floating-label">No Rekening</label>
          </div>
        </template>

        <!-- Regular bank fields for Supplier -->
        <template v-else-if="!shouldUseBisnisPartner && model.metode_bayar !== 'Kartu Kredit'">
          <div class="floating-input">
            <input
              v-model="model.supplier_bank_name"
              type="text"
              class="floating-input-field"
              :class="{ 'bg-gray-50 text-gray-600 cursor-not-allowed': (model.metode_bayar === 'Kartu Kredit') || hasSupplierBankAccounts }"
              :readonly="(model.metode_bayar === 'Kartu Kredit') || hasSupplierBankAccounts"
              placeholder=" "
            />
            <label class="floating-label">Nama Bank</label>
          </div>
          <div class="floating-input">
            <input
              v-model="model.supplier_account_number"
              type="text"
              class="floating-input-field"
              :class="{ 'bg-gray-50 text-gray-600 cursor-not-allowed': (model.metode_bayar === 'Kartu Kredit') || hasSupplierBankAccounts }"
              :readonly="(model.metode_bayar === 'Kartu Kredit') || hasSupplierBankAccounts"
              placeholder=" "
            />
            <label class="floating-label">No Rekening</label>
          </div>
        </template>
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
      :selected-bpb-ids="Array.isArray(model._bpbs) ? model._bpbs.map((b:any)=> b.id) : []"
      :selected-memo-ids="Array.isArray(model._memos) ? model._memos.map((m:any)=> m.id) : []"
      :no-results-message="'Tidak ada Purchase Order yang tersedia'"
      @search="handlePOSearch"
      @add-selected="handleAddPO"
    />

    <!-- DP PV selection modal removed in refactor -->
    <!-- Create Supplier Modal -->
    <SupplierForm
      v-if="showCreateSupplier"
      :asModal="true"
      :suppressSuccessMessage="true"
      :editData="undefined"
      :banks="props.banks || []"
      :departmentOptions="props.departmentOptions || []"
      @created="handleSupplierCreated"
      @close="() => { showCreateSupplier = false; }"
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
