<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";
import CustomSelect from "@/components/ui/CustomSelect.vue";
const props = defineProps<{ show: boolean }>();
const emit = defineEmits(["submit", "submit-keep", "close"]);
const form = ref<{ nama: string; qty: number | null; satuan: string; harga: number | null }>({ nama: "", qty: null, satuan: "", harga: null });
const errors = ref<{ [key: string]: string }>({});
const successVisible = ref(false);
let hideTimer: number | undefined;

// Display models with thousand/decimal formatting
const displayQty = computed<string>({
  get: () => formatCurrency(form.value.qty ?? ""),
  set: (val: string) => {
    const parsed = parseCurrency(val);
    form.value.qty = parsed === "" ? null : Number(parsed);
  },
});

const displayHarga = computed<string>({
  get: () => formatCurrency(form.value.harga ?? ""),
  set: (val: string) => {
    const parsed = parseCurrency(val);
    form.value.harga = parsed === "" ? null : Number(parsed);
  },
});

function validate() {
  errors.value = {};
  if (!form.value.nama) errors.value.nama = "Nama barang wajib diisi";
  if (!form.value.qty) errors.value.qty = "Qty wajib diisi";
  if (!form.value.satuan) errors.value.satuan = "Satuan wajib diisi";
  if (!form.value.harga) errors.value.harga = "Harga wajib diisi";
  return Object.keys(errors.value).length === 0;
}
function addItem(event?: Event) {

  // Prevent event from bubbling up to parent form
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  if (!validate()) return;
  emit("submit", { ...form.value });
  form.value = { nama: "", qty: null, satuan: "", harga: null };
}
function addItemAndContinue(event?: Event) {

  // Prevent event from bubbling up to parent form
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }

  if (!validate()) return;
  emit("submit-keep", { ...form.value });
  form.value = { nama: "", qty: null, satuan: "", harga: null };
  successVisible.value = true;
  if (hideTimer) clearTimeout(hideTimer);
  hideTimer = window.setTimeout(() => {
    successVisible.value = false;
  }, 2000);
}
function close() {
  emit("close");
  form.value = { nama: "", qty: null, satuan: "", harga: null };
  successVisible.value = false;
}
watch(
  () => props.show,
  (val) => {
    if (!val) {
      form.value = { nama: "", qty: null, satuan: "", harga: null };
      successVisible.value = false;
      if (hideTimer) clearTimeout(hideTimer);
    }
  }
);
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    @click.stop
  >
    <div class="bg-white rounded-xl w-full max-w-xl md:max-w-3xl shadow-2xl overflow-hidden" @click.stop>
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">Detail Barang</h2>
        <button
          @click="close"
          class="w-6 h-6 flex items-center justify-center rounded-md hover:bg-gray-100 text-gray-400 hover:text-gray-600"
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

      <!-- Form Content -->
      <div class="px-6 py-6">
        <div v-if="successVisible" class="mb-4 px-3 py-2 bg-green-50 text-green-700 border border-green-200 rounded-md text-sm">
          Berhasil menambahkan barang.
        </div>
        <div class="space-y-4">
          <div class="floating-input">
            <input
              v-model="form.nama"
              id="tb_nama"
              class="floating-input-field"
              placeholder=" "
              type="text"
            />
            <label for="tb_nama" class="floating-label">Nama Barang<span class="text-red-500">*</span></label>
            <div v-if="errors.nama" class="text-red-500 text-xs mt-1">{{ errors.nama }}</div>
          </div>

          <div class="floating-input">
            <input
              v-model="displayQty"
              id="tb_qty"
              type="text"
              class="floating-input-field"
              placeholder=" "
              @keydown="(e:any)=>{ const k=e as KeyboardEvent; const allowed=['Backspace','Delete','Tab','Enter','Escape','ArrowLeft','ArrowRight','Home','End',',','.','0','1','2','3','4','5','6','7','8','9']; if(!(k.ctrlKey||k.metaKey) && !allowed.includes(k.key)) k.preventDefault(); }"
            />
            <label for="tb_qty" class="floating-label">Qty<span class="text-red-500">*</span></label>
            <div v-if="errors.qty" class="text-red-500 text-xs mt-1">{{ errors.qty }}</div>
          </div>

          <div class="floating-input">
            <CustomSelect
              v-model="form.satuan"
              :options="[
                { label: 'Pcs', value: 'Pcs' },
                { label: 'Kg', value: 'Kg' },
                { label: 'Lembar', value: 'Lembar' }
              ]"
              placeholder="Pilih Satuan"
            >
              <template #label>
                Satuan<span class="text-red-500">*</span>
              </template>
            </CustomSelect>
            <div v-if="errors.satuan" class="text-red-500 text-xs mt-1">{{ errors.satuan }}</div>
          </div>

          <div class="floating-input">
            <input
              v-model="displayHarga"
              id="tb_harga"
              type="text"
              class="floating-input-field"
              placeholder=" "
              @keydown="(e:any)=>{ const k=e as KeyboardEvent; const allowed=['Backspace','Delete','Tab','Enter','Escape','ArrowLeft','ArrowRight','Home','End',',','.','0','1','2','3','4','5','6','7','8','9']; if(!(k.ctrlKey||k.metaKey) && !allowed.includes(k.key)) k.preventDefault(); }"
            />
            <label for="tb_harga" class="floating-label">Harga<span class="text-red-500">*</span></label>
            <div v-if="errors.harga" class="text-red-500 text-xs mt-1">{{ errors.harga }}</div>
          </div>

          <!-- Buttons -->
          <div class="flex gap-3 pt-4">
            <button
              type="button"
              @click="addItem"
              class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 4v16m8-8H4"
                ></path>
              </svg>
              Simpan
            </button>
            <button
              type="button"
              @click="addItemAndContinue"
              class="flex-1 bg-blue-300 hover:bg-blue-400 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 3H5a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V7l-4-4zM9 15h6M9 11h6"
                ></path>
              </svg>
              Simpan & Lanjutkan
            </button>
            <button
              type="button"
              @click="close"
              class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2"
            >
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                ></path>
              </svg>
              Batal
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.floating-input { position: relative; }
.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.75rem;
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
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
}
.floating-input-field:is(textarea) {
  padding-top: 1rem;
  padding-bottom: 1rem;
}
/* Ensure floating label appears properly for select fields */
.floating-input-field:is(select) ~ .floating-label {
  top: -0.5rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
}
</style>
