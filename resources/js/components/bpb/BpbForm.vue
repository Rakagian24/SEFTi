<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import PurchaseOrderInfo from '@/components/PurchaseOrderInfo.vue';
import CustomSelect from "../ui/CustomSelect.vue";
import FileUpload from "@/components/ui/FileUpload.vue";

const props = defineProps<{
  latestPOs: Array<any>;
  suppliers: Array<any>;
  departmentOptions: Array<{ value: number|string; label: string }>;
  modelValue: any;
  existingSuratJalanFile?: string | null;
}>();

const emit = defineEmits(["update:modelValue", "open-po-modal"]);

function update(field: string, value: any) {
  emit("update:modelValue", { ...props.modelValue, [field]: value });
}

function onSupplierChange(id: any) {
  const s = props.suppliers.find((x: any) => String(x.id) === String(id));
  emit("update:modelValue", {
    ...props.modelValue,
    supplier_id: id,
    alamat: s?.alamat || "",
    department_id: (() => {
      const currentDept = props.modelValue?.department_id;
      if (!s) return currentDept;
      if (!currentDept) return s.department_id ?? currentDept;
      if (s?.is_all) return currentDept;
      return String(currentDept) === String(s.department_id) ? currentDept : s.department_id;
    })(),
    purchase_order_id: "",
    items: [],
  });
}

function onDepartmentChange(id: any) {
  emit("update:modelValue", {
    ...props.modelValue,
    department_id: id,
    metode_pembayaran: props.modelValue?.metode_pembayaran || 'Transfer',
    supplier_id: '',
    credit_card_id: '',
    alamat: '',
    purchase_order_id: '',
    items: [],
  });
}

// Selected PO detailed info for right panel
const selectedPO = ref<any | null>(null);

watch(
  () => props.modelValue?.purchase_order_id,
  async (id) => {
    selectedPO.value = null;
    if (!id) return;
    try {
      const res = await fetch(`/purchase-orders/${id}/json`);
      if (res.ok) {
        const data = await res.json();
        selectedPO.value = data;
        const hasItems = Array.isArray((props.modelValue as any)?.items) && ((props.modelValue as any).items as any[]).length > 0;

        if (!hasItems) {
          // Create mode: prefill items from PO using remaining_qty
          const prefilledItems = Array.isArray(data?.items)
            ? data.items.map((it: any) => ({
                purchase_order_item_id: it.id,
                nama_barang: it.nama_barang,
                // Default qty sama dengan sisa agar langsung merefleksikan remaining
                qty: Number(it.remaining_qty || 0),
                satuan: it.satuan,
                harga: it.harga,
                remaining_qty: it.remaining_qty,
                initial_qty: 0,
              }))
            : [];
          emit("update:modelValue", {
            ...props.modelValue,
            items: prefilledItems,
            // Mirror PO level discount and PPN flag
            diskon: Number(data?.diskon || 0),
            use_ppn: Boolean(data?.ppn || false),
            ppn_rate: 11,
            // Inherit department from PO to satisfy server validation
            department_id: data?.department_id ?? props.modelValue?.department_id ?? null,
          });
        } else {
          // Edit mode: jangan menimpa items; hanya sinkron flag diskon/PPN dan department
          emit("update:modelValue", {
            ...props.modelValue,
            diskon: Number(data?.diskon || (props.modelValue as any)?.diskon || 0),
            use_ppn: Boolean(data?.ppn || (props.modelValue as any)?.use_ppn || false),
            ppn_rate: 11,
            department_id: data?.department_id ?? props.modelValue?.department_id ?? null,
          });
        }
      }
    } catch {}
  },
  { immediate: true }
);

// Filtered suppliers based on selected department
const filteredSuppliers = computed(() => {
  const deptId = props.modelValue?.department_id;
  if (!deptId) return [] as any[];
  const d = (props.departmentOptions || []).find((x:any)=> String(x.value ?? x.id) === String(deptId));
  const name = String(d?.label || '').toLowerCase();
  const suppliers = (props.suppliers || []) as any[];
  if (name === 'all') return suppliers;
  const target = String(deptId);
  return suppliers.filter((s:any) => String(s.department_id) === target || Boolean(s?.is_all));
});

// Credit cards by department for Kredit method
const creditCardOptions = ref<any[]>([]);
watch(
  () => [props.modelValue?.department_id, props.modelValue?.metode_pembayaran] as const,
  async ([deptId, metode]) => {
    creditCardOptions.value = [];
    if (metode === 'Kredit' && deptId) {
      try {
        const params = new URLSearchParams();
        // Only filter by department when not selecting the special 'All' department
        const d = (props.departmentOptions || []).find((x:any)=> String(x.value ?? x.id) === String(deptId));
        const name = String(d?.label || '').toLowerCase();
        if (name !== 'all') {
          params.set('department_id', String(deptId));
        }
        params.set('status', 'active');
        params.set('per_page', '1000');
        const res = await fetch(`/credit-cards?${params.toString()}`, { headers: { Accept: 'application/json' } });
        if (res.ok) {
          const json = await res.json();
          creditCardOptions.value = Array.isArray(json?.data) ? json.data : [];
        }
      } catch {}
    }
  },
  { immediate: true }
);

function onPaymentMethodChange(val: string) {
  const metode = val || 'Transfer';
  // Reset fields depending on method
  if (metode === 'Transfer') {
    emit('update:modelValue', {
      ...props.modelValue,
      metode_pembayaran: metode,
      credit_card_id: '',
      purchase_order_id: '',
      items: [],
    });
  } else if (metode === 'Kredit') {
    emit('update:modelValue', {
      ...props.modelValue,
      metode_pembayaran: metode,
      supplier_id: '',
      alamat: '',
      purchase_order_id: '',
      credit_card_id: '',
      items: [],
    });
  } else {
    emit('update:modelValue', { ...props.modelValue, metode_pembayaran: metode });
  }
}

// Filtered POs based on selected supplier and allowed conditions
const filteredPOs = ref<any[]>([]);
watch(
  () => [props.modelValue?.supplier_id, props.modelValue?.department_id, props.modelValue?.metode_pembayaran, props.modelValue?.credit_card_id],
  async ([supplierId, departmentId, metode, creditCardId]) => {
    filteredPOs.value = [];
    // Prefill instantly with currently selected PO (no network) to avoid UI delay
    const currentId = props.modelValue?.purchase_order_id;
    if (currentId) {
      const found = (props.latestPOs || []).find((po:any) => String(po.id) === String(currentId));
      const label = found?.no_po || `PO #${currentId}`;
      filteredPOs.value = [{ id: currentId, no_po: label }];
    }
    // Determine eligibility params based on method
    const isKredit = String(metode || '').toLowerCase() === 'kredit';
    // If Transfer and no supplier selected, keep current selection only
    if (!isKredit && !supplierId) {
      if (currentId) {
        try {
          const curRes = await fetch(`/purchase-orders/${currentId}/json`);
          if (curRes.ok) {
            const cur = await curRes.json();
            filteredPOs.value = [{ id: cur.id, no_po: cur.no_po }];
          }
        } catch {}
      }
      return;
    }
    const params = new URLSearchParams();
    if (isKredit) {
      if (creditCardId) params.set('credit_card_id', String(creditCardId));
      if (departmentId) params.set('department_id', String(departmentId));
    } else {
      params.set('supplier_id', String(supplierId));
      if (departmentId) params.set('department_id', String(departmentId));
    }
    try {
      const res = await fetch(`/bpb/purchase-orders/eligible?${params.toString()}`);
      if (res.ok) {
        const json = await res.json();
        const arr = Array.isArray(json?.data) ? json.data : [];
        // Ensure currently selected PO stays visible in options when editing (regardless of eligibility/filter)
        if (currentId && !arr.some((po:any) => String(po.id) === String(currentId))) {
          try {
            const curRes = await fetch(`/purchase-orders/${currentId}/json`);
            if (curRes.ok) {
              const cur = await curRes.json();
              // Always append the currently selected PO so it can be displayed in edit mode
              arr.push({ id: cur.id, no_po: cur.no_po });
            }
          } catch {}
        }
        filteredPOs.value = arr;
      }
    } catch {}
  },
  { immediate: true }
);

const noBpbDisplay = computed(() => props.modelValue?.no_bpb || 'Akan di-generate otomatis');
const tanggalDisplay = computed(() => {
  try {
    return new Date().toLocaleDateString("id-ID", {
      day: "2-digit",
      month: "short",
      year: "numeric",
    });
  } catch {
    return "";
  }
});
const noPvDisplay = computed(() => {
  const no = selectedPO.value?.payment_voucher_no || selectedPO.value?.payment_voucher?.no_pv;
  return no ? no : 'Purchase Order ini belum di buatkan Payment Voucher';
});

// Local state for keterangan (note) with update on blur only (PV behavior)
const localNote = ref<string>(props.modelValue?.keterangan ?? '');
watch(
  () => props.modelValue?.keterangan,
  (v) => {
    if ((v ?? '') !== localNote.value) localNote.value = v ?? '';
  }
);
function onNoteBlur(e: Event) {
  const v = (e.target as HTMLTextAreaElement).value;
  if (v !== (props.modelValue?.keterangan ?? '')) update('keterangan', v);
}
</script>

<template>
  <div class="pv-form-container">
    <!-- Left Column: Form -->
    <div class="pv-form-left">
      <div class="space-y-6">
        <!-- No. BPB (readonly) -->
        <div class="floating-input">
          <div class="floating-input-field cursor-not-allowed filled">
            {{ noBpbDisplay }}
          </div>
          <label class="floating-label">No. BPB</label>
        </div>

        <!-- Tanggal (readonly) -->
        <div class="floating-input">
          <div class="floating-input-field cursor-not-allowed filled">
            {{ tanggalDisplay }}
          </div>
          <label class="floating-label">Tanggal</label>
        </div>

        <!-- No. Payment Voucher (readonly) -->
        <div class="floating-input">
          <div class="floating-input-field cursor-not-allowed filled">
            {{ noPvDisplay }}
          </div>
          <label class="floating-label">No. Payment Voucher</label>
        </div>

        <!-- Departemen -->
        <div class="floating-input">
          <CustomSelect
            :model-value="modelValue.department_id ?? ''"
            @update:modelValue="(v:any)=>onDepartmentChange(v)"
            :options="(departmentOptions || []).map((d:any)=>({ label: d.label, value: d.value }))"
            placeholder="Pilih Departemen"
          >
            <template #label>
              Departemen<span class="text-red-500">*</span>
            </template>
          </CustomSelect>
        </div>

        <!-- Metode Pembayaran -->
        <div class="floating-input">
          <CustomSelect
            :model-value="modelValue.metode_pembayaran ?? 'Transfer'"
            @update:modelValue="(v:any)=>onPaymentMethodChange(v)"
            :options="[
              { label: 'Transfer', value: 'Transfer' },
              { label: 'Kredit', value: 'Kredit' },
            ]"
            placeholder="Pilih Metode"
          >
            <template #label>
              Metode Pembayaran<span class="text-red-500">*</span>
            </template>
          </CustomSelect>
        </div>

        <!-- Supplier (Transfer) -->
        <div v-if="(modelValue.metode_pembayaran ?? 'Transfer') === 'Transfer'" class="floating-input">
          <CustomSelect
            :model-value="modelValue.supplier_id ?? ''"
            @update:modelValue="(v:any)=>onSupplierChange(v)"
            :options="(filteredSuppliers || []).map((s:any)=>({ label: s.nama_supplier, value: s.id }))"
            :searchable="true"
            placeholder="Pilih Supplier"
          >
            <template #label>
              Supplier<span class="text-red-500">*</span>
            </template>
          </CustomSelect>
        </div>

        <!-- Nama Rekening (Kredit) -->
        <div v-else class="floating-input">
          <CustomSelect
            :model-value="modelValue.credit_card_id ?? ''"
            @update:modelValue="(v:any)=>update('credit_card_id', v)"
            :options="(creditCardOptions || []).map((cc:any)=>({ label: cc.nama_pemilik, value: cc.id }))"
            :disabled="!modelValue.department_id"
            :searchable="true"
            placeholder="Pilih Nama Rekening (Kredit)"
          >
            <template #label>
              Nama Rekening (Kredit)<span class="text-red-500">*</span>
            </template>
          </CustomSelect>
        </div>

        <!-- Purchase Order -->
        <div class="floating-input">
          <div class="flex gap-2">
            <div class="flex-1">
              <CustomSelect
                :model-value="modelValue.purchase_order_id ?? ''"
                @update:modelValue="(v:any)=>update('purchase_order_id', v)"
                :options="filteredPOs.map((po:any)=>({ label: po.no_po, value: po.id }))"
                placeholder="Pilih Purchase Order"
              >
                <template #label>
                  Purchase Order<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
            </div>
            <button
              type="button"
              class="inline-flex items-center justify-center w-12 h-12 rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition-colors"
              title="Pilih dari daftar PO"
              @click="$emit('open-po-modal')"
            >
              <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                <path fill-rule="evenodd" d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z" clip-rule="evenodd" />
              </svg>
            </button>
          </div>
        </div>

        <!-- No. Surat Jalan (wajib) -->
        <div class="floating-input">
          <input
            type="text"
            class="floating-input-field"
            placeholder=" "
            :value="modelValue.surat_jalan_no ?? ''"
            @input="(e:any)=>update('surat_jalan_no', e.target.value)"
          />
          <label class="floating-label">No. Surat Jalan<span class="text-red-500">*</span></label>
        </div>

        <!-- Dokumen Surat Jalan (opsional) - pakai style FileUpload seperti Draft Invoice PO -->
        <div class="bg-white rounded-lg border border-gray-200 p-6">
          <h3 class="font-medium text-gray-900 mb-1">Dokumen Surat Jalan</h3>
          <p class="text-xs text-gray-500 mb-4">Opsional</p>
          <div class="space-y-4">
            <FileUpload
              :model-value="modelValue.surat_jalan_file ?? null"
              @update:modelValue="(file: File | null) => emit('update:modelValue', { ...modelValue, surat_jalan_file: file })"
              :required="false"
              accept=".pdf,.jpg,.jpeg,.png"
              :max-size="50 * 1024 * 1024"
              drag-text="Bawa berkas ke area ini (maks. 50 MB) - Hanya file JPG, JPEG, PNG, dan PDF"
              @error="() => {}"
            />

            <div class="text-xs text-gray-500">
              <div class="flex items-center gap-1">
                <span class="text-red-500">⚠</span>
                <span>Bawa berkas ke area ini (maks. 50 MB) - Hanya file JPG, JPEG, PNG, dan PDF</span>
              </div>

              <div class="mt-2" v-if="props.existingSuratJalanFile">
                <p>
                  Dokumen saat ini:
                  <a
                    :href="'/storage/' + props.existingSuratJalanFile"
                    target="_blank"
                    class="text-blue-600 hover:underline"
                  >
                    {{ props.existingSuratJalanFile.split('/').pop() }}
                  </a>
                </p>
              </div>
            </div>
          </div>
        </div>

        <!-- Keterangan -->
        <div class="floating-input">
          <textarea
            v-model="localNote"
            id="keterangan"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
            @blur="onNoteBlur"
          ></textarea>
          <label for="keterangan" class="floating-label">Keterangan</label>
        </div>
      </div>
    </div>

    <!-- Right Column: Purchase Order Info -->
    <div class="pv-form-right">
      <PurchaseOrderInfo :purchase-order="selectedPO" :show-financial="true" />
    </div>
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
.floating-input-field.filled ~ .floating-label,
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}

/* Disabled field styling */
.floating-input-field:disabled,
.floating-input-field.cursor-not-allowed {
  background-color: white;
  cursor: not-allowed;
}
</style>
