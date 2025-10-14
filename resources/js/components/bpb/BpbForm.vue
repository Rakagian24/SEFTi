<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  latestPOs: Array<any>;
  suppliers: Array<any>;
  modelValue: any;
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
  });
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="space-y-4">
      <!-- Row 1: No. BPB | Supplier -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="floating-input">
          <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled">
            Akan di-generate otomatis
          </div>
          <label for="no_bpb" class="floating-label">No. BPB</label>
        </div>
        <div class="floating-input">
          <select
            :value="modelValue.supplier_id"
            @change="onSupplierChange(($event.target as HTMLSelectElement).value)"
            class="floating-input-field"
            id="supplier"
          >
            <option value="">Pilih Supplier</option>
            <option v-for="s in suppliers" :key="s.id" :value="s.id">
              {{ s.nama_supplier }}
            </option>
          </select>
          <label for="supplier" class="floating-label">
            Supplier<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <!-- Row 2: Tanggal | Alamat -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="floating-input">
          <label class="block text-xs font-light text-gray-700 mb-1">Tanggal</label>
          <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled">
            {{ new Date().toLocaleDateString('id-ID') }}
          </div>
        </div>
        <div class="floating-input">
          <input
            type="text"
            :value="modelValue.alamat"
            @input="update('alamat', ($event.target as HTMLInputElement).value)"
            id="alamat"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="alamat" class="floating-label">Alamat</label>
        </div>
      </div>

      <!-- Row 3: No. Payment Voucher | Keterangan -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="floating-input">
          <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled">
            Akan di-generate otomatis
          </div>
          <label for="no_payment" class="floating-label">No. Payment Voucher</label>
        </div>
        <div class="floating-input">
          <textarea
            :value="modelValue.keterangan"
            @input="update('keterangan', ($event.target as HTMLTextAreaElement).value)"
            id="keterangan"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label for="keterangan" class="floating-label">Keterangan</label>
        </div>
      </div>

      <!-- Row 4: Purchase Order (Full Width) -->
      <div class="grid grid-cols-1 gap-6">
        <div>
          <div class="floating-input">
            <div class="flex gap-2">
              <select
                :value="modelValue.purchase_order_id"
                @change="update('purchase_order_id', ($event.target as HTMLSelectElement).value)"
                class="floating-input-field flex-1"
                id="purchase_order"
              >
                <option value="">Pilih Purchase Order</option>
                <option v-for="po in latestPOs" :key="po.id" :value="po.id">
                  {{ po.no_po }}
                </option>
              </select>
              <button
                type="button"
                class="inline-flex items-center justify-center w-10 h-10 rounded-md text-white bg-blue-500 hover:bg-blue-600 focus:outline-none transition-colors"
                title="Pilih dari daftar PO"
                @click="$emit('open-po-modal')"
              >
                <svg
                  xmlns="http://www.w3.org/2000/svg"
                  viewBox="0 0 24 24"
                  fill="currentColor"
                  class="w-5 h-5"
                >
                  <path
                    fill-rule="evenodd"
                    d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z"
                    clip-rule="evenodd"
                  />
                </svg>
              </button>
            </div>
            <label for="purchase_order" class="floating-label">
              Purchase Order<span class="text-red-500">*</span>
            </label>
          </div>
          <small class="text-gray-500 text-xs mt-1 block">Menampilkan 5 PO terbaru</small>
        </div>
      </div>

      <!-- Row 5: Note (Full Width) -->
      <div class="grid grid-cols-1 gap-6">
        <div class="floating-input">
          <textarea
            :value="modelValue.note"
            @input="update('note', ($event.target as HTMLTextAreaElement).value)"
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
  background-color: #f3f4f6;
  color: #6b7280;
  cursor: not-allowed;
  opacity: 0.7;
}

.floating-input-field:disabled ~ .floating-label,
.floating-input-field.cursor-not-allowed ~ .floating-label {
  color: #9ca3af;
}
</style>