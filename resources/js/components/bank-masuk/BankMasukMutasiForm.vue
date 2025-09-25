<script setup lang="ts">
import { ref, computed, watch } from "vue";
import axios from "axios";
import CustomSelect from "../ui/CustomSelect.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { Send, X } from "lucide-vue-next";

const props = defineProps<{
  source: any;
  departments: any[];
  arPartners: any[];
}>();

const emit = defineEmits(["close", "submitted"]);
const { addSuccess, addError } = useMessagePanel();

// Form state
const form = ref<{
  nomor: string;
  tanggal: string;
  tujuan_department_id: string | number | null;
  ar_partner_id: string | number | null;
  nominal: string | number;
  note: string | null;
}>({
  nomor: "",
  tanggal: new Date().toISOString().slice(0, 10),
  tujuan_department_id: null,
  ar_partner_id: null,
  nominal: "",
  note: "",
});

const errors = ref<Record<string, string>>({});

const departmentOptions = computed(() =>
  (props.departments || []).map((d: any) => ({ label: d.name, value: d.id }))
);

// Dynamic AR Partner options loaded per department
const arPartnersOptions = ref<Array<{ label: string; value: string }>>([]);
const isLoadingArPartners = ref(false);
let searchTimeout: ReturnType<typeof setTimeout>;

async function loadArPartners(search = "") {
  try {
    if (!form.value.tujuan_department_id) {
      arPartnersOptions.value = [];
      return;
    }
    isLoadingArPartners.value = true;
    const response = await axios.get("/bank-masuk/ar-partners", {
      params: {
        search,
        limit: 50,
        department_id: form.value.tujuan_department_id,
      },
    });
    if (response.data && Array.isArray(response.data.data)) {
      arPartnersOptions.value = response.data.data.map((p: any) => ({
        label: p.nama_ap,
        value: String(p.id),
      }));
    } else if (Array.isArray(response.data)) {
      // fallback if endpoint returns array directly
      arPartnersOptions.value = response.data.map((p: any) => ({
        label: p.nama_ap,
        value: String(p.id),
      }));
    } else {
      arPartnersOptions.value = [];
    }
  } catch {
    arPartnersOptions.value = [];
  } finally {
    isLoadingArPartners.value = false;
  }
}

function searchArPartners(query: string) {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => loadArPartners(query), 300);
}

const customerOptions = computed(() => {
  return [{ label: "Unrealized", value: "unrealized" }, ...arPartnersOptions.value];
});

// Auto-generate nomor when tujuan selected and load AR Partners
watch(
  () => form.value.tujuan_department_id,
  async (val, oldVal) => {
    errors.value.nomor = "";
    // Reset customer when department changes
    if (val !== oldVal) {
      form.value.ar_partner_id = null;
      arPartnersOptions.value = [];
    }
    if (val) {
      try {
        const { data } = await axios.get("/bank-masuk/mutasi/next-number", {
          params: { department_id: val },
        });
        form.value.nomor = data?.no_mutasi || "";
      } catch {
        form.value.nomor = "";
      }
      // Load AR partners for selected department
      loadArPartners();
    }
  }
);

function selectFull() {
  form.value.nominal = props.source?.nominal_akhir ?? props.source?.nilai ?? 0;
}

function validate() {
  errors.value = {};
  if (!form.value.tujuan_department_id)
    errors.value.tujuan_department_id = "Tujuan wajib dipilih";
  if (!form.value.ar_partner_id && form.value.ar_partner_id !== "unrealized")
    errors.value.ar_partner_id = "Customer wajib dipilih";
  if (form.value.nominal === "" || Number(form.value.nominal) <= 0)
    errors.value.nominal = "Nominal wajib diisi";
  return Object.keys(errors.value).length === 0;
}

async function submit() {
  if (!validate()) return;
  try {
    await axios.post(`/bank-masuk/${props.source.id}/mutasi`, {
      no_mutasi: form.value.nomor,
      tanggal: form.value.tanggal,
      tujuan_department_id: form.value.tujuan_department_id,
      ar_partner_id:
        form.value.ar_partner_id === "unrealized" ? null : form.value.ar_partner_id,
      unrealized: form.value.ar_partner_id === "unrealized",
      nominal: form.value.nominal,
      note: form.value.note,
    });
    addSuccess("Dokumen mutasi berhasil dikirim");
    emit("submitted");
    emit("close");
  } catch (e: any) {
    addError(e?.response?.data?.message || "Gagal menyimpan mutasi");
  }
}

function close() {
  emit("close");
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white rounded-lg w-full max-w-3xl shadow-lg">
      <!-- Header -->
      <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Add Mutasi</h3>
        <button
          @click="close"
          class="text-gray-400 hover:text-gray-500 transition-colors"
        >
          <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>
      </div>

      <!-- Content -->
      <div class="px-6 py-4 space-y-4">
        <!-- Single column layout -->
        <div class="grid grid-cols-1 gap-4">
          <!-- Nomor Mutasi (readonly) -->
          <div class="floating-input">
            <input
              type="text"
              :value="form.nomor"
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
              :class="{ filled: !!form.nomor }"
              placeholder=" "
              readonly
            />
            <label class="floating-label">No. Mutasi</label>
          </div>

          <!-- Tanggal (readonly) -->
          <div class="floating-input">
            <input
              type="date"
              :value="form.tanggal"
              class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed filled"
              placeholder=" "
              readonly
            />
            <label class="floating-label">Tanggal</label>
          </div>

          <!-- Tujuan -->
          <div class="floating-input">
            <CustomSelect
              :model-value="form.tujuan_department_id ?? undefined"
              @update:modelValue="(v:any)=>form.tujuan_department_id=v"
              :options="departmentOptions"
              placeholder="Pilih Tujuan"
            >
              <template #label>Tujuan<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.tujuan_department_id" class="text-xs text-red-500 mt-1">
              {{ errors.tujuan_department_id }}
            </div>
          </div>

          <!-- Customer -->
          <div class="floating-input">
            <CustomSelect
              :model-value="form.ar_partner_id ?? undefined"
              @update:modelValue="(v:any)=>form.ar_partner_id=v"
              :options="customerOptions"
              placeholder="Pilih Customer"
              :searchable="true"
              :loading="isLoadingArPartners"
              :disabled="!form.tujuan_department_id"
              @search="searchArPartners"
            >
              <template #label>Customer<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.ar_partner_id" class="text-xs text-red-500 mt-1">
              {{ errors.ar_partner_id }}
            </div>
          </div>

          <!-- Nominal -->
          <div class="floating-input">
            <div class="relative">
              <input
                type="number"
                min="0"
                step="0.01"
                class="floating-input-field pr-16"
                v-model="form.nominal"
                placeholder=" "
              />
              <button
                type="button"
                class="absolute right-2 top-1/2 -translate-y-1/2 px-2 py-1 bg-green-600 text-white text-xs font-medium rounded hover:bg-green-700 transition-colors"
                @click="selectFull"
              >
                Full
              </button>
              <label class="floating-label"
                >Rp. Nominal<span class="text-red-500">*</span></label
              >
            </div>
            <div v-if="errors.nominal" class="text-xs text-red-500 mt-1">
              {{ errors.nominal }}
            </div>
          </div>

          <!-- Keterangan -->
          <div class="floating-input">
            <textarea
              class="floating-input-field resize-none"
              rows="3"
              v-model="form.note"
              placeholder=" "
            ></textarea>
            <label class="floating-label">Keterangan</label>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div
        class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex items-center gap-3 justify-end"
      >
        <button
          class="px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 transition-colors flex items-center gap-2"
          @click="submit"
        >
          <Send class="w-4 h-4" />
          Kirim
        </button>
        <button
          class="px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50 transition-colors flex items-center gap-2"
          @click="close"
        >
          <X class="w-4 h-4" />
          Batal
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Floating input pattern (copied from other modules for consistency) */
.floating-input {
  position: relative;
}

.floating-input-field {
  width: 100%;
  padding: 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  background-color: white;
  color: #111827;
}

.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 1px rgba(31, 146, 84, 0.2);
}

.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 0.75rem;
  font-size: 0.75rem;
  color: #6b7280;
  pointer-events: none;
  transition: all 0.15s ease-in-out;
}

/* When input is focused or has value - label goes to border */
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  background-color: white;
  padding: 0 0.25rem;
}

/* Special handling for readonly inputs - only show label at top when filled */
.floating-input-field[readonly].filled ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  background-color: white;
  padding: 0 0.25rem;
}

/* CustomSelect provides its own floating behavior via #label slot */

/* Textarea specific styles */
.floating-input-field:is(textarea) {
  resize: vertical;
  padding-top: 1rem;
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
</style>
