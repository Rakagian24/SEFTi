<script setup lang="ts">
import { ref, watch, computed, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import CustomSelect from "../ui/CustomSelect.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import axios from 'axios';
import { useMessagePanel } from '@/composables/useMessagePanel';
import { formatCurrency } from '@/lib/currencyUtils';

const props = defineProps({
  editData: Object,
  bankAccounts: Array,
  departments: Array,
  arPartners: Array,
  no_bm: String,
  default_tipe_po: String,
  isDetailPage: {
    type: Boolean,
    default: false
  },
});
const emit = defineEmits(["close", "refreshTable"]);

const form = ref<Record<string, any>>({
  no_bm: props.no_bm || "",
  tanggal: new Date().toISOString().slice(0, 10), // Set default to today
  tipe_po: props.default_tipe_po || "Reguler",
  department_id: "",
  bank_account_id: "",
  terima_dari: "",
  ar_partner_id: "",
  nilai: "",
  note: "",
  purchase_order_id: "",
  input_lainnya: "",
});
const errors = ref<Record<string, any>>({});
const backendErrors = computed(() => ({}));
const { addSuccess, addError, clearAll } = useMessagePanel();
const isSubmitting = ref(false);

// AR Partners state for lazy loading
const arPartnersOptions = ref<Array<{label: string, value: string}>>([]);
const isLoadingArPartners = ref(false);

// Computed property for department options
const departmentOptions = computed(() => {
  return (props.departments || []).map((dept: any) => ({
    label: dept.name,
    value: dept.id
  }));
});

// Filtered bank accounts based on selected department
const filteredBankAccounts = computed(() => {
  if (!form.value.department_id) return [];
  return (props.bankAccounts || []).filter((account: any) =>
    account.department_id == form.value.department_id
  );
});

// Computed untuk mendapatkan bank account yang dipilih
const selectedBankAccount = computed(() => {
  return (filteredBankAccounts.value || []).find(
    (a: any) => String(a.id) === String(form.value.bank_account_id)
  ) as any;
});

// Computed untuk mendapatkan currency dari bank account yang dipilih
const selectedCurrency = computed(() => {
  if (selectedBankAccount.value && selectedBankAccount.value.bank) {
    return selectedBankAccount.value.bank.currency || 'IDR';
  }
  return 'IDR';
});

// Function to load AR Partners from API
const loadArPartners = async (search = '') => {
  try {
    isLoadingArPartners.value = true;
    const response = await axios.get('/bank-masuk/ar-partners', {
      params: {
        search: search,
        limit: 50,
        department_id: form.value.department_id || undefined,
      }
    });

    if (response.data.success) {
      arPartnersOptions.value = response.data.data.map((partner: any) => ({
        label: partner.nama_ap,
        value: partner.id
      }));
    }
  } catch (error) {
    console.error('Error loading AR Partners:', error);
    addError('Gagal memuat data Customer');
  } finally {
    isLoadingArPartners.value = false;
  }
};

onMounted(() => {
  // Component mounted successfully
})


// Load AR Partners when terima_dari changes to Customer
watch(() => form.value.terima_dari, (newValue) => {
  if (newValue === 'Customer' && arPartnersOptions.value.length === 0) {
    loadArPartners();
  }
});

// Search AR Partners with debounce
let searchTimeout: ReturnType<typeof setTimeout>;
const searchArPartners = (query: string) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadArPartners(query);
  }, 300);
};

function formatOnBlur() {
  if (form.value.nilai) {
    form.value.nilai = formatCurrency(form.value.nilai);
  }
}

function handleNominalInput(e: Event) {
  const target = e.target as HTMLInputElement;
  const value = target.value.replace(/[^\d.]/g, '');

  // Handle multiple decimal points (keep only first)
  const parts = value.split('.');
  let finalValue = value;
  if (parts.length > 2) {
    finalValue = parts[0] + '.' + parts.slice(1).join('');
  }

  form.value.nilai = finalValue;
}

function handleNominalKeydown(e: KeyboardEvent) {
  const allowedKeys = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9', '.', 'Backspace', 'Delete', 'Tab', 'Enter', 'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'];

  if (!allowedKeys.includes(e.key)) {
    e.preventDefault();
  }

  // Prevent multiple decimal points
  if (e.key === '.' && (e.target as HTMLInputElement).value.includes('.')) {
    e.preventDefault();
  }
}

// Watch editData untuk mode edit
watch(
  () => props.editData,
  (editVal) => {
    if (editVal) {
      Object.assign(form.value, {
        ...editVal,
        tanggal: editVal.tanggal || "",
        nilai: editVal.nilai ? String(Number(editVal.nilai)) : "",
        no_bm: editVal.no_bm || "", // Set no_bm dari data edit
        department_id: editVal.bank_account?.department_id || "",
      });
    } else {
      form.value = {
        no_bm: props.no_bm || "",
        tanggal: new Date().toISOString().slice(0, 10), // Set default to today
        tipe_po: props.default_tipe_po || "Reguler",
        department_id: "",
        bank_account_id: "",
        terima_dari: "",
        ar_partner_id: "",
        nilai: "",
        note: "",
        purchase_order_id: "",
        input_lainnya: "",
      };
    }
  },
  { immediate: true }
);

// Watch department_id changes to reset bank_account_id dan reload AR Partners jika perlu
watch(
  () => form.value.department_id,
  (newDepartmentId) => {
    if (newDepartmentId) {
      // Reset bank_account_id when department changes
      form.value.bank_account_id = "";
      // Jika terima_dari Customer, reload AR Partners
      if (form.value.terima_dari === 'Customer') {
        loadArPartners();
      }
    }
  }
);

// Watch terima_dari changes to Customer untuk load AR Partners sesuai departemen
watch(() => form.value.terima_dari, (newValue) => {
  if (newValue === 'Customer') {
    loadArPartners();
  }
});

// Generate no_bm otomatis saat bank_account_id atau tanggal berubah
watch(
  [() => form.value.bank_account_id, () => form.value.tanggal],
  async () => {
    // Jika dalam mode edit, cek apakah ada perubahan yang memerlukan regenerate no_bm
    if (props.editData && props.editData.id) {
      const originalData = props.editData;

      // Parse tanggal untuk perbandingan bulan dan tahun
      const originalDate = new Date(originalData.tanggal);
      const newDate = new Date(form.value.tanggal);

      const originalMonth = originalDate.getMonth() + 1; // getMonth() returns 0-11
      const originalYear = originalDate.getFullYear();
      const newMonth = newDate.getMonth() + 1;
      const newYear = newDate.getFullYear();

      // Cek apakah hanya tanggal yang berubah (bulan dan tahun sama)
      const onlyDateChanged = originalMonth === newMonth && originalYear === newYear &&
                             originalData.tanggal !== form.value.tanggal;

      // Cek apakah hanya departemen yang berubah
      const onlyDepartmentChanged = originalData.bank_account_id != form.value.bank_account_id &&
                                  originalData.tanggal === form.value.tanggal;

      // Jika tidak ada perubahan atau hanya tanggal yang berubah (bulan dan tahun sama), jangan generate
      if (!onlyDepartmentChanged && !(originalData.bank_account_id != form.value.bank_account_id ||
                                    originalData.tanggal != form.value.tanggal)) {
        return;
      }

      // Jika hanya tanggal yang berubah (bulan dan tahun sama), pertahankan nomor BM
      if (onlyDateChanged) {
        return;
      }
    }

    // Generate no_bm jika ada bank_account_id dan tanggal
    if (form.value.bank_account_id && form.value.tanggal) {
      try {
        const params: any = {
          bank_account_id: form.value.bank_account_id,
          tanggal: form.value.tanggal
        };

        // Tambahkan exclude_id dan current_no_bm jika dalam mode edit
        if (props.editData && props.editData.id) {
          params.exclude_id = props.editData.id;
          params.current_no_bm = props.editData.no_bm; // Kirim nomor BM saat ini
        }

        const { data } = await axios.get('/bank-masuk/next-number', { params });
        form.value.no_bm = data.no_bm;
        // console.log('Preview No BM:', data.no_bm);
      } catch {
        form.value.no_bm = '';
      }
    } else {
      form.value.no_bm = '';
    }
  },
  { immediate: true }
);

watch(
  () => backendErrors.value,
  (val) => {
    if (val) {
      errors.value = { ...errors.value, ...val };
    }
  }
);

function validate() {
  errors.value = {};
  if (!form.value.tanggal) errors.value.tanggal = "Tanggal wajib diisi";
  if (!form.value.tipe_po) errors.value.tipe_po = "Tipe PO wajib diisi";
  if (!form.value.department_id) errors.value.department_id = "Department wajib dipilih";
  if (!form.value.bank_account_id) errors.value.bank_account_id = "Rekening wajib dipilih";

  // Clean nilai dari format currency untuk validasi
  let cleanNilai = form.value.nilai;
  if (cleanNilai) {
    // Remove currency symbol and formatting
    const symbol = selectedCurrency.value === 'USD' ? '$' : 'Rp ';
    cleanNilai = cleanNilai.replace(symbol, '').replace(/,/g, '');
  }

  if (!cleanNilai || isNaN(Number(cleanNilai))) errors.value.nilai = "Nominal wajib diisi";
  if (form.value.tipe_po === "Anggaran" && !form.value.purchase_order_id) errors.value.purchase_order_id = "Purchase Order wajib diisi";
  if (!form.value.terima_dari) errors.value.terima_dari = "Terima Dari wajib diisi";
  if (form.value.terima_dari === "Customer" && !form.value.ar_partner_id) errors.value.ar_partner_id = "Customer wajib dipilih";
  if (form.value.terima_dari === "Lainnya" && !form.value.input_lainnya) errors.value.input_lainnya = "Input Lainnya wajib diisi";
  return Object.keys(errors.value).length === 0;
}

function submit(keepForm = false) {
  clearAll();
  if (isSubmitting.value) return;
  // Pastikan tanggal format YYYY-MM-DD
  if (form.value.tanggal) {
    if (!/^[0-9]{4}-[0-9]{2}-[0-9]{2}$/.test(form.value.tanggal)) {
      const d = new Date(form.value.tanggal);
      form.value.tanggal = d.toISOString().slice(0, 10);
    }
  }
  if (!validate()) {
    addError('Periksa kembali input Anda.');
    return;
  }
  isSubmitting.value = true;

        // Clean nilai dari format currency sebelum kirim ke backend
  const data = { ...form.value };
  if (data.nilai) {
    const symbol = selectedCurrency.value === 'USD' ? '$' : 'Rp ';
    data.nilai = data.nilai.replace(symbol, '').replace(/,/g, '');
  }

  // Jangan kirim no_bm saat update untuk menghindari konflik
  if (props.editData) {
    delete data.no_bm;
    // console.log('Deleted no_bm from data:', data);
  }

  if (props.editData) {
    router.put(`/bank-masuk/${props.editData.id}`, data, {
      onSuccess: () => {
        addSuccess('Data bank masuk berhasil diupdate');
        emit('close');
        // Jika dipanggil dari halaman detail, tidak redirect ke index
        if (!props.isDetailPage) {
          router.get('/bank-masuk');
        }
      },
      onError: (serverErrors) => {
        clearAll();
        errors.value = {};
        if (serverErrors && typeof serverErrors === 'object') {
          Object.entries(serverErrors).forEach(([key, val]) => {
            errors.value[key] = Array.isArray(val) ? val[0] : val;
          });
          const messages = Object.values(serverErrors).flat().join(' ');
          addError(messages || 'Gagal memperbarui data bank masuk');
        } else {
          addError('Gagal memperbarui data bank masuk');
        }
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });
  } else {
    router.post('/bank-masuk', data, {
      onSuccess: () => {
        addSuccess('Data bank masuk berhasil disimpan');
        if (keepForm) {
          // Reset hanya field tertentu, field utama tetap
          // Tanggal, Tipe PO, Bank Account tetap, hanya reset nominal, note, purchase_order_id, input_lainnya
          form.value.nilai = '';
          form.value.note = '';
          form.value.purchase_order_id = '';
          form.value.input_lainnya = '';
          form.value.terima_dari = '';
          form.value.ar_partner_id = '';

          // Generate nomor BM baru untuk data berikutnya
          generateNewNoBM();

          // Tidak refresh table dan tidak tutup form
        } else {
          emit('close');
          router.get('/bank-masuk');
        }
      },
      onError: (serverErrors) => {
        clearAll();
        errors.value = {};
        if (serverErrors && typeof serverErrors === 'object') {
          Object.entries(serverErrors).forEach(([key, val]) => {
            errors.value[key] = Array.isArray(val) ? val[0] : val;
          });
          const messages = Object.values(serverErrors).flat().join(' ');
          addError(messages || 'Gagal menyimpan data bank masuk');
        } else {
          addError('Gagal menyimpan data bank masuk');
        }
      },
      onFinish: () => {
        isSubmitting.value = false;
      }
    });
  }
}
async function generateNewNoBM() {
  // Generate no_bm baru jika ada bank_account_id dan tanggal
  if (form.value.bank_account_id && form.value.tanggal) {
    try {
      const params: any = {
        bank_account_id: form.value.bank_account_id,
        tanggal: form.value.tanggal
      };

      const { data } = await axios.get('/bank-masuk/next-number', { params });
      form.value.no_bm = data.no_bm;
    } catch {
      form.value.no_bm = '';
    }
  } else {
    form.value.no_bm = '';
  }
}

function handleBatal() {
  emit("close");
  // Refresh konten sesuai konteks
  if (props.isDetailPage) {
    // Jika di halaman detail, refresh halaman detail
    router.get(`/bank-masuk/${props.editData?.id}`);
  } else {
    // Jika di halaman index, refresh tabel
    emit('refreshTable');
  }
}

// Add paste event listener when component is mounted
onMounted(() => {
  const nominalInput = document.getElementById('nilai') as HTMLInputElement;
  if (nominalInput) {
    nominalInput.addEventListener('paste', (e) => {
      e.preventDefault();
      const pastedText = e.clipboardData?.getData('text') || '';

      // Only allow numbers and decimal point
      const cleanText = pastedText.replace(/[^\d.]/g, '');

      // Handle multiple decimal points (keep only first)
      const parts = cleanText.split('.');
      let finalText = cleanText;
      if (parts.length > 2) {
        finalText = parts[0] + '.' + parts.slice(1).join('');
      }

      // Insert the cleaned text at cursor position
      const start = nominalInput.selectionStart || 0;
      const end = nominalInput.selectionEnd || 0;
      const currentValue = nominalInput.value;
      const newValue = currentValue.substring(0, start) + finalText + currentValue.substring(end);

      // Update the form value
      form.value.nilai = newValue;

      // Set cursor position after the pasted text
      setTimeout(() => {
        nominalInput.setSelectionRange(start + finalText.length, start + finalText.length);
      }, 0);
    });
  }
});
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div
      class="bg-white rounded-lg w-full max-w-2xl max-h-[90vh] overflow-y-auto shadow-xl"
    >
      <div class="p-6">
        <!-- Error global -->
        <div v-if="Object.keys(errors).length" class="mb-4">
          <div v-for="(msg, key) in errors" :key="key" class="text-sm text-red-600 mb-1">
            {{ msg }}
          </div>
        </div>
        <div
          v-if="Object.keys(backendErrors).length"
          class="mb-4 p-3 bg-red-100 text-red-700 rounded"
        >
          <div v-for="(msg, key) in backendErrors" :key="key">{{ msg }}</div>
        </div>
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ props.editData ? "Edit Bank Masuk" : "Create Bank Masuk" }}
          </h2>
          <button
            @click="handleBatal"
            class="text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        <form @submit.prevent="submit(false)" novalidate class="space-y-4">
          <!-- No Bank Masuk -->
          <div class="floating-input">
            <input
              type="text"
              v-model="form.no_bm"
              id="no_bm"
              class="floating-input-field"
              placeholder=""
              readonly
            />
            <label for="no_bm" class="floating-label">No. Bank Masuk</label>
          </div>
          <!-- Tanggal pakai Datepicker -->
          <div class="floating-input">
            <Datepicker
              v-model="form.tanggal"
              :input-class="['floating-input-field', form.tanggal ? 'filled' : '']"
              placeholder=" "
              :format="(date: string | Date) => date ? new Date(date).toLocaleDateString('id-ID') : ''"
              :enable-time-picker="false"
              :auto-apply="true"
              :close-on-auto-apply="true"
              id="tanggal"
            />
            <!-- <label for="tanggal" class="floating-label">
              Tanggal<span class="text-red-500">*</span>
            </label> -->
            <div v-if="errors.tanggal" class="text-red-500 text-xs mt-1">
              {{ errors.tanggal }}
            </div>
          </div>
          <!-- Tipe PO (radio) -->
          <div>
            <div class="flex gap-6">
              <label class="inline-flex items-center">
                <input type="radio" value="Reguler" v-model="form.tipe_po" />
                <span class="ml-2">Reguler</span>
              </label>
              <label class="inline-flex items-center">
                <input type="radio" value="Anggaran" v-model="form.tipe_po" />
                <span class="ml-2">Anggaran</span>
              </label>
              <label class="inline-flex items-center">
                <input type="radio" value="Lainnya" v-model="form.tipe_po" />
                <span class="ml-2">Lainnya</span>
              </label>
            </div>
            <div v-if="errors.tipe_po" class="text-red-500 text-xs mt-1">
              {{ errors.tipe_po }}
            </div>
          </div>
          <!-- Department -->
          <div>
            <CustomSelect
              :model-value="form.department_id"
              @update:modelValue="(val) => (form.department_id = val)"
              :options="departmentOptions"
              placeholder="Pilih Department"
            >
              <template #label>Department<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
              Department wajib dipilih
            </div>
          </div>
          <!-- Bank Account (Rekening) -->
          <div>
            <CustomSelect
              :model-value="form.bank_account_id"
              @update:modelValue="(val) => (form.bank_account_id = val)"
              :options="filteredBankAccounts.map((acc: any) => ({
                label: `${acc.bank?.singkatan || 'Unknown'} - ******${acc.no_rekening.slice(-5)}`,
                value: acc.id
              }))"
              placeholder="Pilih Rekening"
              :disabled="!form.department_id"
            >
              <template #label>Rekening<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.bank_account_id" class="text-red-500 text-xs mt-1">
              Rekening wajib dipilih
            </div>
          </div>
          <!-- Terima Dari -->
          <div>
            <CustomSelect
              :model-value="form.terima_dari"
              @update:modelValue="(val) => (form.terima_dari = val)"
              :options="[
                { label: 'Customer', value: 'Customer' },
                { label: 'Karyawan', value: 'Karyawan' },
                { label: 'Penjualan Toko', value: 'Penjualan Toko' },
                { label: 'Lainnya', value: 'Lainnya' },
              ]"
              placeholder="Pilih"
            >
              <template #label>Terima Dari<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.terima_dari" class="text-red-500 text-xs mt-1">
              {{ errors.terima_dari }}
            </div>
            <!-- AR Partner dropdown (hanya jika terima_dari Customer) -->
            <div v-if="form.terima_dari === 'Customer'" class="mt-4">
              <CustomSelect
                :model-value="form.ar_partner_id"
                @update:modelValue="(val) => (form.ar_partner_id = val)"
                :options="arPartnersOptions"
                :loading="isLoadingArPartners"
                placeholder="Pilih Customer"
                :searchable="true"
                @search="searchArPartners"
              >
                <template #label>Customer<span class="text-red-500">*</span></template>
              </CustomSelect>
              <div v-if="errors.ar_partner_id" class="text-red-500 text-xs mt-1">
                {{ errors.ar_partner_id }}
              </div>
            </div>
            <!-- Input Lainnya (hanya jika terima_dari Lainnya) -->
            <div v-if="form.terima_dari === 'Lainnya'" class="floating-input mt-2">
              <input
                type="text"
                v-model="form.input_lainnya"
                id="input_lainnya"
                class="floating-input-field"
                :class="{ 'border-red-500': errors.input_lainnya }"
                placeholder=" "
              />
              <label for="input_lainnya" class="floating-label">
                Input Lainnya<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.input_lainnya" class="text-red-500 text-xs mt-1">{{ errors.input_lainnya }}</div>
            </div>
          </div>
          <!-- Nominal (format currency dinamis, hanya angka) -->
          <div class="floating-input">
            <input
              type="text"
              v-model="form.nilai"
              id="nilai"
              class="floating-input-field"
              :class="{ 'border-red-500': errors.nilai }"
              placeholder=" "
              autocomplete="off"
              @blur="formatOnBlur"
              @keydown="handleNominalKeydown"
              @input="handleNominalInput"
            />
            <label for="nilai" class="floating-label"
              >Nominal<span class="text-red-500">*</span></label
            >
            <div v-if="errors.nilai" class="text-red-500 text-xs mt-1">
              {{ errors.nilai }}
            </div>
          </div>
          <!-- Purchase Order (hanya jika tipe_po Anggaran) -->
          <div v-if="form.tipe_po === 'Anggaran'" class="floating-input">
            <input
              type="text"
              v-model="form.purchase_order_id"
              id="purchase_order_id"
              class="floating-input-field"
              :class="{ 'border-red-500': errors.purchase_order_id }"
              placeholder=" "
            />
            <label for="purchase_order_id" class="floating-label">
              Purchase Order<span class="text-red-500">*</span>
            </label>
            <div v-if="errors.purchase_order_id" class="text-red-500 text-xs mt-1">{{ errors.purchase_order_id }}</div>
          </div>
          <!-- Note -->
          <div class="floating-input">
            <textarea
              v-model="form.note"
              id="note"
              class="floating-input-field resize-none"
              placeholder=" "
              rows="3"
            ></textarea>
            <label for="note" class="floating-label">Note</label>
          </div>
          <!-- Action Buttons -->
          <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
            <button
              type="submit"
              class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                fill="#E6E6E6"
                height="24"
                viewBox="0 0 24 24"
                width="24"
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
              >
                <path
                  d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
                />
              </svg>
              Simpan
            </button>
            <button
              v-if="!props.editData"
              type="button"
              class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
              @click="submit(true)"
              :disabled="isSubmitting"
            >
              <svg
                fill="#E6E6E6"
                height="24"
                viewBox="0 0 24 24"
                width="24"
                xmlns="http://www.w3.org/2000/svg"
                class="w-5 h-5"
              >
                <path
                  d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
                />
              </svg>
              Simpan & Lanjutkan
            </button>
            <button
              type="button"
              @click="handleBatal"
              class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            >
              <svg
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke-width="1.5"
                stroke="currentColor"
                class="w-5 h-5"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
              Batal
            </button>
          </div>
        </form>
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
.floating-input-field:not(:placeholder-shown) ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
}

/* Special handling for readonly inputs - only show label at top when filled */
.floating-input-field[readonly].filled ~ .floating-label {
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
.floating-input-field[type="date"] ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}

/* Floating label support for vue-datepicker */
.floating-input .floating-input-field:focus ~ .floating-label,
.floating-input .floating-input-field.filled ~ .floating-label {
  top: -0.5rem;
  left: 0.75rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  transform: translateY(0) scale(1);
  background-color: white;
  padding: 0 0.25rem;
}

/* Datepicker specific styling for Quicksand font */
.floating-input .dp__input {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
  color: #374151 !important;
  border: 1px solid #d1d5db !important;
}

.floating-input .dp__input::placeholder {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  color: #6b7280 !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
}

.floating-input .dp__input:focus {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  border-color: #1f9254 !important;
}

/* Ensure datepicker dropdown also uses Quicksand */
.floating-input .dp__main {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
}

.floating-input .dp__calendar_header,
.floating-input .dp__calendar_header_separator,
.floating-input .dp__calendar_header_cell,
.floating-input .dp__calendar_row,
.floating-input .dp__calendar_cell,
.floating-input .dp__month_year_row,
.floating-input .dp__month_year_select,
.floating-input .dp__action_buttons,
.floating-input .dp__action_button {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
}
</style>
