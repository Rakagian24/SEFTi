<script setup lang="ts">
import { ref, watch, computed } from "vue";
import { router } from "@inertiajs/vue3";
import { useMessagePanel } from "@/composables/useMessagePanel";
import CustomSelect from "../ui/CustomSelect.vue";

interface Bank {
  id: number;
  nama_bank: string;
  singkatan?: string;
  status?: string;
}

interface BankAccount {
  bank_id: string;
  nama_rekening: string;
  no_rekening: string;
}

const props = defineProps({
  editData: Object,
  banks: {
    type: Array as () => Bank[],
    default: () => [],
  },
  departmentOptions: {
    type: Array as () => Array<{ id: number | string; name: string }>,
    default: () => [],
  },
  asModal: {
    type: Boolean,
    default: true,
  },
  suppressSuccessMessage: {
    type: Boolean,
    default: false,
  },
});

const emit = defineEmits(["close", "created"]);

const { addSuccess, addError } = useMessagePanel();

const form = ref({
  nama_supplier: "",
  alamat: "",
  email: "",
  no_telepon: "",
  department_id: "",
  bank_accounts: [] as BankAccount[],
  terms_of_payment: "",
});

const errors = ref<{ [key: string]: string }>({});

// Initialize with one bank account
const initializeBankAccounts = () => {
  if (form.value.bank_accounts.length === 0) {
    form.value.bank_accounts = [
      {
        bank_id: "",
        nama_rekening: "",
        no_rekening: "",
      },
    ];
  }
};

watch(
  () => props.editData,
  (val) => {
    if (val) {
      Object.assign(form.value, {
        nama_supplier: val.nama_supplier || "",
        alamat: val.alamat || "",
        email: val.email || "",
        no_telepon: val.no_telepon || "",
        department_id: val.department_id ? String(val.department_id) : "",
        terms_of_payment: val.terms_of_payment || "",
      });
      // Ambil data bank dari relasi pivot (val.banks)
      if (val.banks && Array.isArray(val.banks) && val.banks.length > 0) {
        form.value.bank_accounts = val.banks.map((bank: any) => ({
          bank_id: bank.id.toString(),
          nama_rekening: bank.pivot?.nama_rekening || "",
          no_rekening: bank.pivot?.no_rekening || "",
        }));
      } else {
        form.value.bank_accounts = [{ bank_id: "", nama_rekening: "", no_rekening: "" }];
      }
    } else {
      form.value = {
        nama_supplier: "",
        alamat: "",
        email: "",
        no_telepon: "",
        department_id: "",
        bank_accounts: [{ bank_id: "", nama_rekening: "", no_rekening: "" }],
        terms_of_payment: "",
      };
    }
  },
  { immediate: true }
);

// Auto-select department when only one available and disable select
const isSingleDepartment = computed(() => Array.isArray(props.departmentOptions) && props.departmentOptions.length === 1);
if (isSingleDepartment.value && !form.value.department_id) {
  const first = (props.departmentOptions as any[])[0] || {};
  form.value.department_id = String(first.value ?? first.id ?? "");
}

function addBankAccount() {
  if (form.value.bank_accounts.length < 3) {
    form.value.bank_accounts.push({
      bank_id: "",
      nama_rekening: "",
      no_rekening: "",
    });
  }
}

function removeBankAccount(index: number) {
  if (form.value.bank_accounts.length > 1) {
    form.value.bank_accounts.splice(index, 1);
  }
}

function validate() {
  errors.value = {};
  if (!form.value.nama_supplier) errors.value.nama_supplier = "Nama supplier wajib diisi";
  if (!form.value.alamat) errors.value.alamat = "Alamat wajib diisi";
  if (!form.value.terms_of_payment)
    errors.value.terms_of_payment = "Terms of payment wajib diisi";
  if (!form.value.email) {
    errors.value.email = "Email wajib diisi";
  } else if (!/^\S+@\S+\.\S+$/.test(form.value.email)) {
    errors.value.email = "Format email tidak valid";
  }
  if (!form.value.no_telepon) errors.value.no_telepon = "No telepon wajib diisi";
  if (form.value.no_telepon && /\D/.test(form.value.no_telepon))
    errors.value.no_telepon = "No telepon hanya boleh angka";
  // department_id tidak wajib, tapi jika diisi harus valid
  if (
    form.value.department_id &&
    !props.departmentOptions.some(
      (d:any) => String(d.id ?? d.value) === String(form.value.department_id)
    )
  ) {
    errors.value.department_id = "Departemen tidak valid";
  }
  form.value.bank_accounts.forEach((acc, idx) => {
    if (!acc.bank_id) errors.value[`bank_id_${idx}`] = "Bank wajib dipilih";
    if (!acc.nama_rekening)
      errors.value[`nama_rekening_${idx}`] = "Nama rekening wajib diisi";
    if (!acc.no_rekening) errors.value[`no_rekening_${idx}`] = "No rekening wajib diisi";
    if (acc.no_rekening && /\D/.test(acc.no_rekening))
      errors.value[`no_rekening_${idx}`] = "No rekening hanya boleh angka";
  });
  return Object.keys(errors.value).length === 0;
}

function submit() {
  if (!validate()) return;
  // Kirim data bank_accounts sesuai pivot (bank_id, nama_rekening, no_rekening)
  const submitData = {
    ...form.value,
    department_id: form.value.department_id || null,
    bank_accounts: form.value.bank_accounts,
  };
  if (props.editData) {
    router.put(`/suppliers/${props.editData.id}`, submitData, {
      onSuccess: () => {
        if (!props.suppressSuccessMessage) {
          addSuccess("Data supplier berhasil diperbarui");
        }
        emit("close");
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        addError("Gagal memperbarui data supplier");
      },
    });
  } else {
    router.post("/suppliers", submitData, {
      onSuccess: () => {
        if (!props.suppressSuccessMessage) {
          addSuccess("Data supplier berhasil ditambahkan");
        }
        try { emit("created", submitData); } catch {}
        emit("close");
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
      onError: () => {
        addError("Gagal menambahkan data supplier");
      },
    });
  }
}

function handleReset() {
  form.value = {
    nama_supplier: "",
    alamat: "",
    email: "",
    no_telepon: "",
    department_id: "",
    bank_accounts: [{ bank_id: "", nama_rekening: "", no_rekening: "" }],
    terms_of_payment: "",
  };
}

// Initialize bank accounts on mount
initializeBankAccounts();
</script>

<template>
  <div
    v-if="asModal"
    class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4"
  >
    <div
      class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl"
    >
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ props.editData ? "Edit Supplier" : "Create Supplier" }}
          </h2>
          <button
            @click="emit('close')"
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

        <form @submit.prevent="submit" novalidate class="space-y-4">
          <!-- Row 1: Nama Supplier and Email -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.nama_supplier"
                :class="{ 'border-red-500': errors.nama_supplier }"
                type="text"
                id="nama_supplier"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="nama_supplier" class="floating-label">
                Nama Supplier<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.nama_supplier" class="text-red-500 text-xs mt-1">
                {{ errors.nama_supplier }}
              </div>
            </div>
            <div class="floating-input">
              <CustomSelect
                :model-value="form.department_id"
                @update:modelValue="(val) => (form.department_id = val)"
                :options="
                  props.departmentOptions.map((d:any) => ({
                    label: d.name ?? d.label,
                    value: String(d.value ?? d.id ?? ''),
                  }))
                "
                placeholder="Pilih Departemen"
                :disabled="isSingleDepartment"
              >
                <template #label> Departemen<span class="text-red-500">*</span> </template>
              </CustomSelect>
              <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
                {{ errors.department_id }}
              </div>
            </div>
          </div>
          <!-- Row 2: Alamat and No Telepon -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.email"
                :class="{ 'border-red-500': errors.email }"
                type="email"
                id="email"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="email" class="floating-label">
                Email<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.email" class="text-red-500 text-xs mt-1">
                {{ errors.email }}
              </div>
            </div>
            <div class="floating-input">
              <input
                v-model="form.no_telepon"
                :class="{ 'border-red-500': errors.no_telepon }"
                type="text"
                id="no_telepon"
                class="floating-input-field"
                placeholder=" "
                @input="form.no_telepon = form.no_telepon.replace(/\D/g, '')"
              />
              <label for="no_telepon" class="floating-label">
                No Telepon<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.no_telepon" class="text-red-500 text-xs mt-1">
                {{ errors.no_telepon }}
              </div>
            </div>
          </div>
          <div class="grid grid-cols-1 gap-6">
            <div class="floating-input">
              <textarea
                v-model="form.alamat"
                :class="{ 'border-red-500': errors.alamat }"
                id="alamat"
                class="floating-input-field resize-none"
                placeholder=" "
                rows="3"
                required
              ></textarea>
              <label for="alamat" class="floating-label">
                Alamat<span class="text-red-500">*</span>
              </label>
              <div v-if="errors.alamat" class="text-red-500 text-xs mt-1">
                {{ errors.alamat }}
              </div>
            </div>
          </div>
          <!-- Row 3: Terms of Payment -->
          <div class="grid grid-cols-1 gap-6">
            <div>
              <CustomSelect
                :model-value="form.terms_of_payment ?? ''"
                @update:modelValue="(val) => (form.terms_of_payment = val)"
                :options="[
                  { label: '0 Hari', value: '0 Hari' },
                  { label: '7 Hari', value: '7 Hari' },
                  { label: '15 Hari', value: '15 Hari' },
                  { label: '30 Hari', value: '30 Hari' },
                  { label: '45 Hari', value: '45 Hari' },
                  { label: '60 Hari', value: '60 Hari' },
                  { label: '90 Hari', value: '90 Hari' },
                ]"
              >
                <template #label>
                  Terms of Payment<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
              <div v-if="errors.terms_of_payment" class="text-red-500 text-xs mt-1">
                {{ errors.terms_of_payment }}
              </div>
            </div>
          </div>
          <!-- Bank Accounts Section -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-800">Informasi Rekening Bank</h3>
              <button
                type="button"
                @click="addBankAccount"
                v-if="form.bank_accounts.length < 3"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
                Tambah Rekening
              </button>
            </div>
            <div
              v-for="(account, index) in form.bank_accounts"
              :key="index"
              class="border border-gray-200 rounded-lg p-4 space-y-4"
            >
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-medium text-gray-700">
                  Rekening {{ index + 1 }}
                </h4>
                <button
                  type="button"
                  @click="removeBankAccount(index)"
                  v-if="form.bank_accounts.length > 1"
                  class="text-red-500 hover:text-red-700 transition-colors"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Bank Selection -->
                <div>
                  <CustomSelect
                    :model-value="account.bank_id ?? ''"
                    @update:modelValue="(val) => (account.bank_id = val)"
                    :options="
                      banks.map((bank) => ({
                        label: bank.singkatan
                          ? `${bank.nama_bank} (${bank.singkatan})`
                          : bank.nama_bank,
                        value: bank.id.toString(),
                      }))
                    "
                    placeholder="Pilih Bank"
                  >
                    <template #label>
                      Nama Bank<span class="text-red-500">*</span>
                    </template>
                  </CustomSelect>
                  <div
                    v-if="errors[`bank_id_${index}`]"
                    class="text-red-500 text-xs mt-1"
                  >
                    {{ errors[`bank_id_${index}`] }}
                  </div>
                </div>
                <!-- Account Owner Name -->
                <div class="floating-input">
                  <input
                    v-model="account.nama_rekening"
                    :class="{ 'border-red-500': errors[`nama_rekening_${index}`] }"
                    type="text"
                    :id="`nama_rekening_${index}`"
                    class="floating-input-field"
                    placeholder=" "
                    required
                  />
                  <label :for="`nama_rekening_${index}`" class="floating-label">
                    Nama Rekening<span class="text-red-500">*</span>
                  </label>
                  <div
                    v-if="errors[`nama_rekening_${index}`]"
                    class="text-red-500 text-xs mt-1"
                  >
                    {{ errors[`nama_rekening_${index}`] }}
                  </div>
                </div>
                <!-- Account Number -->
                <div class="floating-input">
                  <input
                    v-model="account.no_rekening"
                    :class="{ 'border-red-500': errors[`no_rekening_${index}`] }"
                    type="text"
                    :id="`no_rekening_${index}`"
                    class="floating-input-field"
                    placeholder=" "
                    required
                    @input="account.no_rekening = account.no_rekening.replace(/\D/g, '')"
                  />
                  <label :for="`no_rekening_${index}`" class="floating-label">
                    No. Rekening/VA<span class="text-red-500">*</span>
                  </label>
                  <div
                    v-if="errors[`no_rekening_${index}`]"
                    class="text-red-500 text-xs mt-1"
                  >
                    {{ errors[`no_rekening_${index}`] }}
                  </div>
                </div>
              </div>
            </div>
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
              type="button"
              @click="handleReset"
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
                  d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"
                />
              </svg>
              Reset
            </button>
            <button
              type="button"
              @click="emit('close')"
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
  <div v-else>
    <div
      class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl mx-auto"
    >
      <div class="p-6">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
          <h2 class="text-xl font-semibold text-gray-800">
            {{ props.editData ? "Edit Supplier" : "Create Supplier" }}
          </h2>
          <button
            @click="emit('close')"
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

        <form @submit.prevent="submit" novalidate class="space-y-4">
          <!-- Row 1: Nama Supplier and Email -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <input
                v-model="form.nama_supplier"
                type="text"
                id="nama_supplier"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="nama_supplier" class="floating-label">
                Nama Supplier<span class="text-red-500">*</span>
              </label>
            </div>
            <div class="floating-input">
              <input
                v-model="form.email"
                :class="{ 'border-red-500': errors.email }"
                type="email"
                id="email"
                class="floating-input-field"
                placeholder=" "
                required
              />
              <label for="email" class="floating-label"> Email </label>
              <div v-if="errors.email" class="text-red-500 text-xs mt-1">
                {{ errors.email }}
              </div>
            </div>
          </div>
          <!-- Row 2: Alamat and No Telepon -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="floating-input">
              <textarea
                v-model="form.alamat"
                id="alamat"
                class="floating-input-field resize-none"
                placeholder=" "
                rows="3"
                required
              ></textarea>
              <label for="alamat" class="floating-label">
                Alamat<span class="text-red-500">*</span>
              </label>
            </div>
            <div class="floating-input">
              <input
                v-model="form.no_telepon"
                type="tel"
                id="no_telepon"
                class="floating-input-field"
                placeholder=" "
              />
              <label for="no_telepon" class="floating-label"> No Telepon </label>
            </div>
          </div>

          <!-- Row 3: Terms of Payment -->
          <div class="grid grid-cols-1 gap-6">
            <div>
              <CustomSelect
                :model-value="form.terms_of_payment ?? ''"
                @update:modelValue="(val) => (form.terms_of_payment = val)"
                :options="[
                  { label: '0 Hari', value: '0 Hari' },
                  { label: '7 Hari', value: '7 Hari' },
                  { label: '15 Hari', value: '15 Hari' },
                  { label: '30 Hari', value: '30 Hari' },
                  { label: '45 Hari', value: '45 Hari' },
                  { label: '60 Hari', value: '60 Hari' },
                  { label: '90 Hari', value: '90 Hari' },
                ]"
              >
                <template #label> Terms of Payment </template>
              </CustomSelect>
            </div>
          </div>
          <!-- Bank Accounts Section -->
          <div class="space-y-4">
            <div class="flex items-center justify-between">
              <h3 class="text-lg font-medium text-gray-800">Informasi Rekening Bank</h3>
              <button
                type="button"
                @click="addBankAccount"
                v-if="form.bank_accounts.length < 3"
                class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors"
              >
                <svg
                  class="w-4 h-4 mr-2"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
                Tambah Rekening
              </button>
            </div>
            <div
              v-for="(account, index) in form.bank_accounts"
              :key="index"
              class="border border-gray-200 rounded-lg p-4 space-y-4"
            >
              <div class="flex items-center justify-between">
                <h4 class="text-sm font-medium text-gray-700">
                  Rekening {{ index + 1 }}
                </h4>
                <button
                  type="button"
                  @click="removeBankAccount(index)"
                  v-if="form.bank_accounts.length > 1"
                  class="text-red-500 hover:text-red-700 transition-colors"
                >
                  <svg
                    class="w-5 h-5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>
              </div>
              <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <!-- Bank Selection -->
                <div>
                  <CustomSelect
                    :model-value="account.bank_id ?? ''"
                    @update:modelValue="(val) => (account.bank_id = val)"
                    :options="
                      banks.map((bank) => ({
                        label: bank.singkatan
                          ? `${bank.nama_bank} (${bank.singkatan})`
                          : bank.nama_bank,
                        value: bank.id.toString(),
                      }))
                    "
                    placeholder="Pilih Bank"
                  >
                    <template #label>
                      Nama Bank<span class="text-red-500">*</span>
                    </template>
                  </CustomSelect>
                </div>
                <!-- Account Owner Name -->
                <div class="floating-input">
                  <input
                    v-model="account.nama_rekening"
                    type="text"
                    :id="`nama_rekening_${index}`"
                    class="floating-input-field"
                    placeholder=" "
                    required
                  />
                  <label :for="`nama_rekening_${index}`" class="floating-label">
                    Nama Rekening<span class="text-red-500">*</span>
                  </label>
                </div>
                <!-- Account Number -->
                <div class="floating-input">
                  <input
                    v-model="account.no_rekening"
                    type="text"
                    :id="`no_rekening_${index}`"
                    class="floating-input-field"
                    placeholder=" "
                    required
                  />
                  <label :for="`no_rekening_${index}`" class="floating-label">
                    No. Rekening/VA<span class="text-red-500">*</span>
                  </label>
                </div>
              </div>
            </div>
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
              type="button"
              @click="handleReset"
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
                  d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99"
                />
              </svg>
              Reset
            </button>
            <button
              type="button"
              @click="emit('close')"
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
  /* margin-top: 1rem; */
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
.floating-input select.floating-input-field:not([value=""]) ~ .floating-label,
.floating-input select.floating-input-field:focus ~ .floating-label {
  background-color: white;
  padding: 0 0.25rem;
}
</style>
