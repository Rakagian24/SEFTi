<script setup lang="ts">
import { computed, watch } from "vue";

const model = defineModel<any>({ required: true });

const props = defineProps<{
  supplierOptions?: any[];
  departmentOptions?: any[];
  perihalOptions?: any[];
}>();

const tipeOptions = [
  { value: "Reguler", label: "Reguler" },
  { value: "Anggaran", label: "Anggaran" },
  { value: "Lainnya", label: "Lainnya" },
];

const metodeBayarOptions = [
  { value: "Transfer", label: "Transfer" },
  { value: "Cek", label: "Cek" },
  { value: "Giro", label: "Giro" },
];

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

watch(
  () => model.value?.supplier_id,
  (newVal) => {
    if (!newVal) return;
    const s = (props.supplierOptions || []).find(
      (x: any) => String(x.value || x.id) === String(newVal)
    );
    if (!s) return;
    model.value = {
      ...(model.value || {}),
      supplier_phone: s.phone || "",
      supplier_address: s.address || "",
      department_id:
        model.value?.department_id || s.department_id || model.value?.department_id,
    };
  }
);
</script>

<template>
  <div class="bg-white rounded-lg shadow p-6">
    <div class="space-y-6">
      <!-- Row 1: No. Payment Voucher | Tanggal -->
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

        <!-- Tanggal -->
        <div class="floating-input">
          <div
            class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
          >
            {{ displayTanggal || "-" }}
          </div>
          <label class="floating-label">Tanggal</label>
        </div>
      </div>

      <!-- Row 2: Tipe PV | Nominal -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tipe PV -->
        <div class="floating-input">
          <select
            v-model="model.tipe_pv"
            class="floating-input-field"
            :class="{ filled: model.tipe_pv }"
          >
            <option value="">Pilih Tipe</option>
            <option v-for="opt in tipeOptions" :key="opt.value" :value="opt.value">
              {{ opt.label }}
            </option>
          </select>
          <label class="floating-label">
            Tipe PV<span class="text-red-500">*</span>
          </label>
        </div>

        <!-- Nominal -->
        <div class="floating-input">
          <input
            v-model.number="model.nominal"
            type="number"
            id="nominal"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="nominal" class="floating-label">
            Nominal<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <!-- Row 3: Supplier | Metode Bayar -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Supplier -->
        <div class="floating-input">
          <select
            v-model="model.supplier_id"
            class="floating-input-field"
            :class="{ filled: model.supplier_id }"
          >
            <option value="">Pilih Supplier</option>
            <option
              v-for="s in props.supplierOptions || []"
              :key="s.value || s.id"
              :value="s.value || s.id"
            >
              {{ s.label || s.name }}
            </option>
          </select>
          <label class="floating-label">
            Supplier<span class="text-red-500">*</span>
          </label>
        </div>

        <!-- Metode Bayar -->
        <div class="floating-input">
          <select
            v-model="model.metode_bayar"
            class="floating-input-field"
            :class="{ filled: model.metode_bayar }"
          >
            <option value="">Pilih Metode</option>
            <option v-for="m in metodeBayarOptions" :key="m.value" :value="m.value">
              {{ m.label }}
            </option>
          </select>
          <label class="floating-label">
            Metode Bayar<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <!-- Row 4: No. Telpon | Alamat -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- No. Telpon -->
        <div class="floating-input">
          <input
            v-model="model.supplier_phone"
            type="text"
            id="supplier_phone"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="supplier_phone" class="floating-label">No. Telpon</label>
        </div>

        <!-- Alamat -->
        <div class="floating-input">
          <input
            v-model="model.supplier_address"
            type="text"
            id="supplier_address"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="supplier_address" class="floating-label">Alamat</label>
        </div>
      </div>

      <!-- Row 5: Informasi Rekening Supplier (full width) -->
      <div>
        <label class="block text-sm font-medium text-gray-700 mb-2">
          Informasi Rekening Supplier
        </label>
        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50">
          <template v-if="selectedSupplier">
            <template v-if="selectedSupplier.bank_accounts?.length">
              <div class="space-y-2">
                <div
                  v-for="(ba, i) in selectedSupplier.bank_accounts"
                  :key="i"
                  class="flex items-center justify-between p-2 bg-white rounded border"
                >
                  <div class="flex flex-col">
                    <span class="font-medium text-sm">{{ ba.bank_name }}</span>
                    <span class="text-xs text-gray-500">{{ ba.account_name }}</span>
                  </div>
                  <span class="text-sm text-gray-600 font-mono">
                    •••••{{ String(ba.account_number || "").slice(-5) }}
                  </span>
                </div>
              </div>
            </template>
            <div v-else class="text-gray-500 text-sm">Tidak ada data rekening</div>
          </template>
          <div v-else class="text-gray-500 text-sm">
            Pilih supplier untuk melihat informasi rekening
          </div>
        </div>
      </div>

      <!-- Row 6: Departemen | Perihal -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Departemen -->
        <div class="floating-input">
          <select
            v-model="model.department_id"
            class="floating-input-field"
            :class="{ filled: model.department_id }"
          >
            <option value="">Pilih Departemen</option>
            <option
              v-for="d in props.departmentOptions || []"
              :key="d.value || d.id"
              :value="d.value || d.id"
            >
              {{ d.label || d.name }}
            </option>
          </select>
          <label class="floating-label">
            Departemen<span class="text-red-500">*</span>
          </label>
          <p class="text-xs text-gray-500 mt-1">Format: SGT1 – BCA – 68775</p>
        </div>

        <!-- Perihal -->
        <div class="floating-input">
          <select
            v-model="model.perihal_id"
            class="floating-input-field"
            :class="{ filled: model.perihal_id }"
          >
            <option value="">Pilih Perihal</option>
            <option
              v-for="p in props.perihalOptions || []"
              :key="p.value || p.id"
              :value="p.value || p.id"
            >
              {{ p.label || p.nama }}
            </option>
          </select>
          <label class="floating-label">
            Perihal<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <!-- Row 7: No. Cek/Giro | Tanggal Giro -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- No. Cek/Giro -->
        <div class="floating-input">
          <input
            v-model="model.no_giro"
            type="text"
            id="no_giro"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="no_giro" class="floating-label">No. Cek/Giro</label>
        </div>

        <!-- Tanggal Giro -->
        <div class="floating-input">
          <input
            v-model="model.tanggal_giro"
            type="date"
            id="tanggal_giro"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="tanggal_giro" class="floating-label">Tanggal Giro</label>
        </div>
      </div>

      <!-- Row 8: Tanggal Cair | (empty) -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Tanggal Cair -->
        <div class="floating-input">
          <input
            v-model="model.tanggal_cair"
            type="date"
            id="tanggal_cair"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="tanggal_cair" class="floating-label">Tanggal Cair</label>
        </div>

        <!-- Empty column for layout consistency -->
        <div></div>
      </div>

      <!-- Row 9: Note | Keterangan -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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

        <!-- Keterangan -->
        <div class="floating-input">
          <textarea
            v-model="model.keterangan"
            id="keterangan"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label for="keterangan" class="floating-label">Keterangan</label>
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
