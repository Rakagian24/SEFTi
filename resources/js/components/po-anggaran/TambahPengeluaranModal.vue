<script setup lang="ts">
import { ref, watch, computed } from "vue";
import axios from "axios";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";
import CustomSelect from "@/components/ui/CustomSelect.vue";
const props = withDefaults(defineProps<{
  show: boolean;
  selectedPerihalName?: string;
  perihal?: { id?: number | string; nama?: string };
  perihalId?: number | string | null;
  mode?: 'add' | 'edit';
  initialItem?: {
    jenis_pengeluaran_id?: number | string | null;
    jenis_pengeluaran_text?: string;
    detail?: string;
    keterangan?: string;
    harga?: number | null;
    qty?: number | null;
    satuan?: string;
  } | null;
}>(), {
  mode: 'add',
  initialItem: null,
});

const emit = defineEmits(["submit", "submit-keep", "close"]);
const form = ref<{
  pengeluaran_id: number | '';
  detail: string;
  keterangan: string;
  harga: number | null;
  qty: number | null;
  satuan: string;
}>({ pengeluaran_id: '', detail: "", keterangan: "", harga: null, qty: null, satuan: "" });
const errors = ref<{ [key: string]: string }>({});
const successVisible = ref(false);
let hideTimer: number | undefined;

const headerTitle = computed(() => {
  if (props.mode === 'edit') return "Ubah Pengeluaran";
  return "Tambah Pengeluaran";
});
const isEditMode = computed(() => props.mode === 'edit');

const pengeluaranOptions = ref<Array<{label:string; value:number; deskripsi?: string; perihal_id?: number | string | null; satuan?: string}>>([]);
async function loadPengeluaranOptions() {
  try {
    // Try lightweight JSON endpoint first, optionally filtered by perihal
    const { data } = await axios.get('/pengeluaran-options', {
      params: {
        per_page: 100,
        active_only: true,
        perihal_id: props.perihalId || props.perihal?.id || undefined,
      },
    });
    let list = (Array.isArray(data?.data) ? data.data : []);
    if (!list || list.length === 0) {
      // Fallback to Inertia index payload
      const res2 = await axios.get('/pengeluarans', { params: { per_page: 100 } });
      const d2 = res2.data;
      list = (d2?.pengeluarans?.data) || (d2?.props?.pengeluarans?.data) || (Array.isArray(d2?.data) ? d2.data : []);
    }
    pengeluaranOptions.value = (list || []).map((p: any) => ({
      label: p.nama,
      value: Number(p.id),
      deskripsi: p.deskripsi,
      perihal_id: p.perihal_id ?? null,
      satuan: p.satuan ?? '',
    }));
  } catch {
    pengeluaranOptions.value = [];
  }
}
watch(() => props.show, (v) => { if (v) loadPengeluaranOptions(); });

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

const displaySubtotal = computed<string>(() => {
  const qty = Number(form.value.qty || 0);
  const harga = Number(form.value.harga || 0);
  return formatCurrency(qty * harga);
});

function validate() {
  errors.value = {};
  if (!form.value.pengeluaran_id) errors.value.pengeluaran_id = "Jenis Pengeluaran wajib dipilih";
  if (!form.value.satuan) errors.value.satuan = "Satuan wajib diisi";
  if (!form.value.harga) errors.value.harga = "Harga wajib diisi";
  if (!form.value.qty) errors.value.qty = "Qty wajib diisi";
  return Object.keys(errors.value).length === 0;
}
function addItem(event?: Event) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  if (!validate()) return;
  const selected = pengeluaranOptions.value.find(o => Number(o.value) === Number(form.value.pengeluaran_id));
  emit("submit", {
    jenis_pengeluaran_id: form.value.pengeluaran_id ? Number(form.value.pengeluaran_id) : null,
    jenis_pengeluaran_text: selected?.label || form.value.detail,
    keterangan: form.value.keterangan,
    harga: form.value.harga,
    qty: form.value.qty,
    satuan: form.value.satuan,
  });
  resetFormState();
}
function addItemAndContinue(event?: Event) {
  if (event) {
    event.preventDefault();
    event.stopPropagation();
  }
  if (!validate()) return;
  const selected = pengeluaranOptions.value.find(o => Number(o.value) === Number(form.value.pengeluaran_id));
  emit("submit-keep", {
    jenis_pengeluaran_id: form.value.pengeluaran_id ? Number(form.value.pengeluaran_id) : null,
    jenis_pengeluaran_text: selected?.label || form.value.detail,
    keterangan: form.value.keterangan,
    harga: form.value.harga,
    qty: form.value.qty,
    satuan: form.value.satuan,
  });
  resetFormState();
  successVisible.value = true;
  if (hideTimer) clearTimeout(hideTimer);
  hideTimer = window.setTimeout(() => {
    successVisible.value = false;
  }, 2000);
}

function close() {
  emit("close");
  resetFormState();
  successVisible.value = false;
}

watch(
  () => props.show,
  (val) => {
    if (val) {
      if (isEditMode.value) {
        applyInitialItem(props.initialItem);
      }
    } else {
      resetFormState();
      successVisible.value = false;
      if (hideTimer) clearTimeout(hideTimer);
    }
  }
);

watch(
  () => props.initialItem,
  (item) => {
    if (isEditMode.value && props.show) {
      applyInitialItem(item);
    }
  },
  { deep: true }
);

function resetFormState() {
  form.value = { pengeluaran_id: '', detail: "", keterangan: "", harga: null, qty: null, satuan: "" };
}

function applyInitialItem(item?: typeof props.initialItem | null) {
  if (!item) {
    resetFormState();
    return;
  }
  form.value = {
    pengeluaran_id: item.jenis_pengeluaran_id ? Number(item.jenis_pengeluaran_id) : '',
    detail: item.jenis_pengeluaran_text || item.detail || '',
    keterangan: item.keterangan || '',
    harga: typeof item.harga === 'number' ? item.harga : Number(item.harga || 0) || null,
    qty: typeof item.qty === 'number' ? item.qty : Number(item.qty || 0) || null,
    satuan: item.satuan || '',
  };
}

function handlePengeluaranChange(v: any) {
  form.value.pengeluaran_id = v ? Number(v) : '';
  const selected = pengeluaranOptions.value.find(
    (o) => Number(o.value) === (v ? Number(v) : NaN)
  );
  form.value.satuan = selected?.satuan || '';
}
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
    @click.stop
  >
    <div
      class="bg-white rounded-xl w-full max-w-xl md:max-w-3xl shadow-2xl overflow-hidden"
      @click.stop
    >
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
        <h2 class="text-lg font-semibold text-gray-800">{{ headerTitle }}</h2>
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
        <div v-if="successVisible && !isEditMode" class="mb-4 px-3 py-2 bg-green-50 text-green-700 border border-green-200 rounded-md text-sm">
          Berhasil menambahkan detail anggaran.
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div class="floating-input">
            <CustomSelect
              :model-value="form.pengeluaran_id || ''"
              @update:modelValue="handlePengeluaranChange"
              :options="pengeluaranOptions"
              placeholder="Jenis Pengeluaran"
            >
              <template #label> Jenis Pengeluaran<span class="text-red-500">*</span> </template>
            </CustomSelect>
            <div v-if="errors.pengeluaran_id" class="text-red-500 text-xs mt-1">{{ errors.pengeluaran_id }}</div>
          </div>
          <div class="floating-input">
            <input
              v-model="form.satuan"
              id="tb_satuan"
              type="text"
              class="floating-input-field"
              placeholder=" "
              readonly
            />
            <label for="tb_satuan" class="floating-label">Satuan<span class="text-red-500">*</span></label>
            <div v-if="errors.satuan" class="text-red-500 text-xs mt-1">{{ errors.satuan }}</div>
          </div>

          <div class="floating-input md:col-span-1">
            <textarea v-model="form.keterangan" id="tb_keterangan" class="floating-input-field resize-none" placeholder=" " rows="3"></textarea>
            <label for="tb_keterangan" class="floating-label">Keterangan</label>
          </div>
          <div class="floating-input">
            <input
              :value="displaySubtotal"
              id="tb_subtotal"
              type="text"
              class="floating-input-field"
              placeholder=" "
              readonly
            />
            <label for="tb_subtotal" class="floating-label">Rp. Subtotal<span class="text-red-500">*</span></label>
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
            <label for="tb_harga" class="floating-label">Rp. Harga<span class="text-red-500">*</span></label>
            <div v-if="errors.harga" class="text-red-500 text-xs mt-1">{{ errors.harga }}</div>
          </div>
          <div class="floating-input md:col-start-1">
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

          <div class="md:col-span-2 flex gap-3 pt-2">
            <button type="button" @click="addItem" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
              Simpan
            </button>
            <button v-if="!isEditMode" type="button" @click="addItemAndContinue" class="flex-1 bg-blue-300 hover:bg-blue-400 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 3H5a2 2 0 00-2 2v14a2 2 0 002 2h12a2 2 0 002-2V7l-4-4zM9 15h6M9 11h6"></path></svg>
              Simpan & Lanjutkan
            </button>
            <button type="button" @click="close" class="flex-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center gap-2">
              <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
              Batal
            </button>
          </div>
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
