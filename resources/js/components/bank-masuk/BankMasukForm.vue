<script setup lang="ts">
import { ref, watch, computed, onMounted, nextTick } from "vue";
import { router } from "@inertiajs/vue3";
import CustomSelect from "../ui/CustomSelect.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import axios from "axios";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { formatCurrency, formatCurrencyWithSymbol } from "@/lib/currencyUtils";

const props = defineProps({
  editData: Object,
  bankAccounts: Array,
  departments: Array,
  arPartners: Array,
  no_bm: String,
  default_tipe_po: String,
  isDetailPage: {
    type: Boolean,
    default: false,
  },
});
const emit = defineEmits(["close", "refreshTable"]);

const form = ref<Record<string, any>>({
  no_bm: props.no_bm || "",
  tanggal: new Date(), // Set default to today as Date object
  match_date: null,
  tipe_po: props.default_tipe_po || "Reguler",
  department_id: "",
  bank_account_id: "",
  terima_dari: "",
  ar_partner_id: "",
  nilai: "",
  selisih_penambahan: "",
  selisih_pengurangan: "",
  nominal_akhir: "",
  note: "",
  purchase_order_id: "",
  input_lainnya: "",
});

// Preview nomor Bank Masuk real-time
const previewBankMasukNumber = ref('BM/TYP/DPT/I/2025/XXXX');
// const isSimpanLanjutkan = ref(false); // Flag untuk simpan & lanjutkan
const errors = ref<Record<string, any>>({});
const backendErrors = computed(() => ({}));
const { addSuccess, addError, clearAll } = useMessagePanel();
const isSubmitting = ref(false);

// Flag untuk mencegah reset selama inisialisasi edit
const isEditInitializing = ref(false);

// Computed untuk mengontrol apakah ar_partner_id boleh di-reset
const canResetArPartnerId = computed(() => {
  return !isEditInitializing.value && !(props.editData && props.editData.id);
});

// AR Partners state for lazy loading
const arPartnersOptions = ref<Array<{ label: string; value: string }>>([]);
const isLoadingArPartners = ref(false);

// Computed property for department options
const departmentOptions = computed(() => {
  return (props.departments || []).map((dept: any) => ({
    label: dept.name,
    value: dept.id,
  }));
});

// Single-department handling: auto-select and disable when only 1 department
const isSingleDepartment = computed(() => Array.isArray(props.departments) && props.departments.length === 1);
const singleDepartmentId = computed(() => (isSingleDepartment.value ? String((props.departments as any[])[0].id) : ""));

onMounted(() => {
  if (!form.value.department_id && isSingleDepartment.value) {
    form.value.department_id = singleDepartmentId.value;
  }
});

// Filtered bank accounts based on selected department
const filteredBankAccounts = computed(() => {
  if (!form.value.department_id) {
    return [];
  }

  const filtered = (props.bankAccounts || []).filter((account: any) => {
    // Convert both to numbers for comparison
    const accountDeptId = Number(account.department_id);
    const formDeptId = Number(form.value.department_id);
    const matches = accountDeptId === formDeptId;

    // Additional debug info
    if (accountDeptId === 5 || formDeptId === 5) {
    }

    return matches;
  });

  return filtered;
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
    return selectedBankAccount.value.bank.currency || "IDR";
  }
  return "IDR";
});

// Computed untuk memastikan tanggal selalu valid
const validTanggal = computed({
  get: () => {
    if (!form.value.tanggal) return new Date();
    try {
      const date = new Date(form.value.tanggal);
      return isNaN(date.getTime()) ? new Date() : date;
    } catch {
      return new Date();
    }
  },
  set: (value) => {
    form.value.tanggal = value;
  },
});

// Computed untuk memastikan match_date selalu valid
const validMatchDate = computed({
  get: () => {
    if (!form.value.match_date) return null;
    try {
      const date = new Date(form.value.match_date);
      return isNaN(date.getTime()) ? null : date;
    } catch {
      return null;
    }
  },
  set: (value) => {
    form.value.match_date = value;
  },
});

// Computed untuk menghitung nominal akhir
const calculatedNominalAkhir = computed(() => {
  // Clean nilai dari format currency sebelum parsing
  let cleanNilai = form.value.nilai;
  if (cleanNilai) {
    const symbol = selectedCurrency.value === 'USD' ? '$' : 'Rp ';
    cleanNilai = cleanNilai.replace(symbol, '').replace(/,/g, '');
  }

  let cleanSelisihPenambahan = form.value.selisih_penambahan;
  if (cleanSelisihPenambahan) {
    const symbol = selectedCurrency.value === 'USD' ? '$' : 'Rp ';
    cleanSelisihPenambahan = cleanSelisihPenambahan.replace(symbol, '').replace(/,/g, '');
  }

  let cleanSelisihPengurangan = form.value.selisih_pengurangan;
  if (cleanSelisihPengurangan) {
    const symbol = selectedCurrency.value === 'USD' ? '$' : 'Rp ';
    cleanSelisihPengurangan = cleanSelisihPengurangan.replace(symbol, '').replace(/,/g, '');
  }

  const nilai = parseFloat(cleanNilai) || 0;
  const selisihPenambahan = parseFloat(cleanSelisihPenambahan) || 0;
  const selisihPengurangan = parseFloat(cleanSelisihPengurangan) || 0;

  return nilai + selisihPenambahan - selisihPengurangan;
});

// Watch untuk update nominal akhir secara otomatis
watch(
  [
    () => form.value.nilai,
    () => form.value.selisih_penambahan,
    () => form.value.selisih_pengurangan,
  ],
  () => {
    // Hanya update nominal akhir jika terima_dari adalah "Penjualan Toko"
    if (form.value.terima_dari === "Penjualan Toko") {
      const calculatedValue = calculatedNominalAkhir.value;
      form.value.nominal_akhir = formatCurrencyWithSymbol(calculatedValue, selectedCurrency.value);
    }
  }
);

// Watch untuk terima_dari - reset field selisih jika bukan Penjualan Toko
watch(
  () => form.value.terima_dari,
  (newValue) => {
    if (newValue !== "Penjualan Toko") {
      // Reset field selisih dan nominal akhir jika bukan Penjualan Toko
      form.value.selisih_penambahan = "";
      form.value.selisih_pengurangan = "";
      form.value.nominal_akhir = "";
    } else {
      // Jika dipilih Penjualan Toko, hitung ulang nominal akhir
      const calculatedValue = calculatedNominalAkhir.value;
      form.value.nominal_akhir = formatCurrencyWithSymbol(calculatedValue, selectedCurrency.value);
    }
  }
);

// Function to load AR Partners from API
const loadArPartners = async (search = "") => {
  try {
    if (!form.value.department_id) {
      arPartnersOptions.value = [];
      return;
    }
    isLoadingArPartners.value = true;
    const response = await axios.get("/bank-masuk/ar-partners", {
      params: {
        search: search,
        limit: 50,
        department_id: form.value.department_id,
      },
    });

    if (response.data.success) {
      arPartnersOptions.value = response.data.data.map((partner: any) => ({
        label: partner.nama_ap,
        value: String(partner.id),
      }));

      // Tambahkan customer yang terpilih jika tidak ada di options
      if (
        form.value.ar_partner_id &&
        !arPartnersOptions.value.some(
          (opt) => String(opt.value) === String(form.value.ar_partner_id)
        )
      ) {
        if (
          props.editData &&
          props.editData.ar_partner &&
          String(props.editData.ar_partner.id) === String(form.value.ar_partner_id)
        ) {
          arPartnersOptions.value.push({
            label: props.editData.ar_partner.nama_ap,
            value: String(props.editData.ar_partner.id),
          });
        }
      }
    }
  } catch {
    addError("Gagal memuat data Customer");
    arPartnersOptions.value = [];
  } finally {
    isLoadingArPartners.value = false;
  }
};

onMounted(() => {
  // Component mounted successfully

  // Debug data structure
  if (props.bankAccounts && props.bankAccounts.length > 0) {
  }
});

// Load AR Partners when terima_dari changes to Customer
watch(
  () => form.value.terima_dari,
  (newValue) => {

    if (newValue === "Customer" && arPartnersOptions.value.length === 0) {
      loadArPartners();
    }
    // Default match_date = tanggal jika Penjualan Toko, else null
    // Tapi jangan override jika sedang dalam mode edit dan match_date sudah ada
    if (newValue === "Penjualan Toko") {
      // Hanya set default jika tidak dalam mode edit atau jika match_date belum ada
      if (!props.editData || !form.value.match_date) {
        form.value.match_date = form.value.tanggal
          ? new Date(form.value.tanggal)
          : new Date();
      }
    } else {
      // Hanya reset ke null jika tidak dalam mode edit atau jika match_date memang kosong
      if (!props.editData || !form.value.match_date) {
        form.value.match_date = null;
      }
    }
  }
);

// Search AR Partners with debounce
let searchTimeout: ReturnType<typeof setTimeout>;
const searchArPartners = (query: string) => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadArPartners(query);
  }, 300);
};

// Function untuk update preview nomor Bank Masuk real-time
const updatePreviewNumber = async () => {
  if (!form.value.bank_account_id || !form.value.tanggal) {
    previewBankMasukNumber.value = 'BM/TYP/DPT/I/2025/XXXX';
    return;
  }

  try {
    const response = await axios.get('/bank-masuk/next-number', {
      params: {
        bank_account_id: form.value.bank_account_id,
        tanggal: form.value.tanggal,
        exclude_id: props.editData?.id || null,
        current_no_bm: form.value.no_bm || null,
        tipe_po: form.value.tipe_po || null
      }
    });

    if (response.data.no_bm) {
      previewBankMasukNumber.value = response.data.no_bm;
    }
  } catch (error) {
    console.error('Error getting preview number:', error);
    previewBankMasukNumber.value = 'BM/TYP/DPT/I/2025/XXXX';
  }
};

// Watch untuk auto-update preview saat bank_account_id atau tanggal berubah
watch([() => form.value.bank_account_id, () => form.value.tanggal, () => form.value.tipe_po], () => {
  updatePreviewNumber();
}, { deep: true });

function formatOnBlur() {
  if (form.value.nilai) {
    form.value.nilai = formatCurrency(form.value.nilai);
  }
}

// Debounce timer untuk input nominal
let nominalInputTimeout: ReturnType<typeof setTimeout>;

function handleNominalInput(e: Event) {
  const target = e.target as HTMLInputElement;

  // Check if this is a paste operation
  if (target.dataset.pasteOperation === "true") {
    // For paste operations, clean the value immediately
    const value = target.value.replace(/[^\d.]/g, "");

    // Handle multiple decimal points (keep only first)
    const parts = value.split(".");
    let finalValue = value;
    if (parts.length > 2) {
      finalValue = parts[0] + "." + parts.slice(1).join("");
    }

    // Update form value immediately for paste
    form.value.nilai = finalValue;
    return;
  }

  // For regular input, use debounced approach
  const value = target.value.replace(/[^\d.]/g, "");

  // Handle multiple decimal points (keep only first)
  const parts = value.split(".");
  let finalValue = value;
  if (parts.length > 2) {
    finalValue = parts[0] + "." + parts.slice(1).join("");
  }

  // Clear previous timeout
  clearTimeout(nominalInputTimeout);

  // Set timeout untuk update nilai dengan debounce
  nominalInputTimeout = setTimeout(() => {
    form.value.nilai = finalValue;
  }, 10); // 10ms debounce untuk mencegah terlalu banyak re-render
}

function handleNominalKeydown(e: KeyboardEvent) {
  // Allow paste operations (Ctrl+V, Cmd+V)
  if ((e.ctrlKey || e.metaKey) && e.key === "v") {
    return; // Allow paste
  }

  // Allow copy operations (Ctrl+C, Cmd+C)
  if ((e.ctrlKey || e.metaKey) && e.key === "c") {
    return; // Allow copy
  }

  // Allow cut operations (Ctrl+X, Cmd+X)
  if ((e.ctrlKey || e.metaKey) && e.key === "x") {
    return; // Allow cut
  }

  // Allow select all (Ctrl+A, Cmd+A)
  if ((e.ctrlKey || e.metaKey) && e.key === "a") {
    return; // Allow select all
  }

  const allowedKeys = [
    "0",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    ".",
    "Backspace",
    "Delete",
    "Tab",
    "Enter",
    "ArrowLeft",
    "ArrowRight",
    "ArrowUp",
    "ArrowDown",
  ];

  if (!allowedKeys.includes(e.key)) {
    e.preventDefault();
  }

  // Prevent multiple decimal points
  if (e.key === "." && (e.target as HTMLInputElement).value.includes(".")) {
    e.preventDefault();
  }
}

// Functions for Selisih Penambahan
function formatSelisihPenambahanOnBlur() {
  if (form.value.selisih_penambahan) {
    form.value.selisih_penambahan = formatCurrency(form.value.selisih_penambahan);
  }
}

function handleSelisihPenambahanInput(e: Event) {
  const target = e.target as HTMLInputElement;
  const value = target.value.replace(/[^\d.]/g, "");

  // Handle multiple decimal points (keep only first)
  const parts = value.split(".");
  let finalValue = value;
  if (parts.length > 2) {
    finalValue = parts[0] + "." + parts.slice(1).join("");
  }

  form.value.selisih_penambahan = finalValue;
}

function handleSelisihPenambahanKeydown(e: KeyboardEvent) {
  // Allow paste operations (Ctrl+V, Cmd+V)
  if ((e.ctrlKey || e.metaKey) && e.key === "v") {
    return; // Allow paste
  }

  // Allow copy operations (Ctrl+C, Cmd+C)
  if ((e.ctrlKey || e.metaKey) && e.key === "c") {
    return; // Allow copy
  }

  // Allow cut operations (Ctrl+X, Cmd+X)
  if ((e.ctrlKey || e.metaKey) && e.key === "x") {
    return; // Allow cut
  }

  // Allow select all (Ctrl+A, Cmd+A)
  if ((e.ctrlKey || e.metaKey) && e.key === "a") {
    return; // Allow select all
  }

  const allowedKeys = [
    "0",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    ".",
    "Backspace",
    "Delete",
    "Tab",
    "Enter",
    "ArrowLeft",
    "ArrowRight",
    "ArrowUp",
    "ArrowDown",
  ];

  if (!allowedKeys.includes(e.key)) {
    e.preventDefault();
  }

  // Prevent multiple decimal points
  if (e.key === "." && (e.target as HTMLInputElement).value.includes(".")) {
    e.preventDefault();
  }
}

// Functions for Selisih Pengurangan
function formatSelisihPenguranganOnBlur() {
  if (form.value.selisih_pengurangan) {
    form.value.selisih_pengurangan = formatCurrency(form.value.selisih_pengurangan);
  }
}

function handleSelisihPenguranganInput(e: Event) {
  const target = e.target as HTMLInputElement;
  const value = target.value.replace(/[^\d.]/g, "");

  // Handle multiple decimal points (keep only first)
  const parts = value.split(".");
  let finalValue = value;
  if (parts.length > 2) {
    finalValue = parts[0] + "." + parts.slice(1).join("");
  }

  form.value.selisih_pengurangan = finalValue;
}

function handleSelisihPenguranganKeydown(e: KeyboardEvent) {
  // Allow paste operations (Ctrl+V, Cmd+V)
  if ((e.ctrlKey || e.metaKey) && e.key === "v") {
    return; // Allow paste
  }

  // Allow copy operations (Ctrl+C, Cmd+C)
  if ((e.ctrlKey || e.metaKey) && e.key === "c") {
    return; // Allow copy
  }

  // Allow cut operations (Ctrl+X, Cmd+X)
  if ((e.ctrlKey || e.metaKey) && e.key === "x") {
    return; // Allow cut
  }

  // Allow select all (Ctrl+A, Cmd+A)
  if ((e.ctrlKey || e.metaKey) && e.key === "a") {
    return; // Allow select all
  }

  const allowedKeys = [
    "0",
    "1",
    "2",
    "3",
    "4",
    "5",
    "6",
    "7",
    "8",
    "9",
    ".",
    "Backspace",
    "Delete",
    "Tab",
    "Enter",
    "ArrowLeft",
    "ArrowRight",
    "ArrowUp",
    "ArrowDown",
  ];

  if (!allowedKeys.includes(e.key)) {
    e.preventDefault();
  }

  // Prevent multiple decimal points
  if (e.key === "." && (e.target as HTMLInputElement).value.includes(".")) {
    e.preventDefault();
  }
}

// Watch editData untuk mode edit
watch(
  () => props.editData,
  async (editVal) => {
    if (editVal) {
      // Set flag untuk mencegah reset selama inisialisasi
      isEditInitializing.value = true;

      // Store ar_partner_id temporarily
      const tempArPartnerId = editVal.ar_partner_id;

      Object.assign(form.value, {
        ...editVal,
        tanggal: editVal.tanggal ? new Date(editVal.tanggal) : new Date(), // always Date object
        match_date: editVal.match_date ? new Date(editVal.match_date) : null, // Gunakan match_date dari database jika ada
        nilai: editVal.nilai ? String(Number(editVal.nilai)) : "",
        selisih_penambahan: editVal.selisih_penambahan
          ? String(Number(editVal.selisih_penambahan))
          : "",
        selisih_pengurangan: editVal.selisih_pengurangan
          ? String(Number(editVal.selisih_pengurangan))
          : "",
        nominal_akhir: editVal.nominal_akhir ? String(Number(editVal.nominal_akhir)) : "",
        no_bm: editVal.no_bm || "", // Set no_bm dari data edit
        department_id: editVal.bank_account?.department_id || "",
        // Don't set ar_partner_id yet, we'll set it after loading options
        ar_partner_id: "",
      });

      // Load AR Partners if editing and terima_dari is Customer
      if (editVal.terima_dari === "Customer" && editVal.bank_account?.department_id) {
        try {
          // Load AR Partners and then set the selected value
          await loadArPartners();
          // Pastikan customer yang sedang diedit ada di options
          const selectedId = String(tempArPartnerId);
          const exists = arPartnersOptions.value.some(
            (opt) => String(opt.value) === selectedId
          );
          if (tempArPartnerId && !exists && editVal.ar_partner) {
            arPartnersOptions.value.push({
              label: editVal.ar_partner.nama_ap,
              value: tempArPartnerId,
            });
          }

          // Set value dengan nextTick untuk memastikan DOM sudah update
          await nextTick();
          form.value.ar_partner_id = tempArPartnerId;

          // Tunggu sebentar sebelum reset flag untuk memastikan value benar-benar terset
          setTimeout(() => {
            isEditInitializing.value = false;
          }, 200); // Increased delay to prevent match_date override
        } catch (error) {
          console.error("Error loading AR Partners for edit:", error);
          isEditInitializing.value = false;
        }
      } else {
        // Reset flag setelah inisialisasi selesai
        isEditInitializing.value = false;
      }
    } else {
      form.value = {
        no_bm: props.no_bm || "",
        tanggal: new Date(), // Set default to today as Date object
        match_date: null, // Reset match_date ke null
        tipe_po: props.default_tipe_po || "Reguler",
        department_id: "",
        bank_account_id: "",
        terima_dari: "",
        ar_partner_id: "",
        nilai: "",
        selisih_penambahan: "",
        selisih_pengurangan: "",
        nominal_akhir: "",
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
  (newDepartmentId, oldDepartmentId) => {
    // Don't reset if we're in edit mode and the department hasn't actually changed
    if (props.editData && props.editData.id && newDepartmentId === oldDepartmentId) {
      return;
    }

    // Don't reset during edit initialization
    if (isEditInitializing.value) {
      return;
    }

    if (newDepartmentId) {
      // Reset bank_account_id when department changes
      form.value.bank_account_id = "";
      // Reset ar_partner_id when department changes
      if (canResetArPartnerId.value) {
        form.value.ar_partner_id = "";
      }
      // Clear AR Partners options when department changes
      arPartnersOptions.value = [];
      // Jika terima_dari Customer, reload AR Partners
      if (form.value.terima_dari === "Customer") {
        loadArPartners();
      }
    } else {
      // If no department selected, clear related fields
      form.value.bank_account_id = "";
      if (canResetArPartnerId.value) {
        form.value.ar_partner_id = "";
      }
      arPartnersOptions.value = [];
    }
  }
);

// Watch terima_dari changes to Customer untuk load AR Partners sesuai departemen
watch(
  () => form.value.terima_dari,
  (newValue, oldValue) => {
    // Don't reset if we're in edit mode and the value hasn't actually changed
    if (props.editData && props.editData.id && newValue === oldValue) {
      return;
    }

    // Don't reset during edit initialization
    if (isEditInitializing.value) {
      return;
    }

    if (newValue === "Customer") {
      // Clear previous AR Partners when switching to Customer
      arPartnersOptions.value = [];
      if (canResetArPartnerId.value) {
        form.value.ar_partner_id = "";
      }
      // Load AR Partners if department is selected
      if (form.value.department_id) {
        loadArPartners();
      }
    } else {
      // Clear AR Partner when switching away from Customer
      if (canResetArPartnerId.value) {
        form.value.ar_partner_id = "";
      }
      arPartnersOptions.value = [];
    }
  }
);

// Watch untuk mencegah reset ar_partner_id yang tidak diinginkan
watch(
  () => form.value.ar_partner_id,
  (newValue, oldValue) => {
    // Jika dalam mode edit dan value berubah menjadi kosong, cek apakah ini reset yang diinginkan
    if (
      props.editData &&
      props.editData.id &&
      newValue === "" &&
      oldValue &&
      isEditInitializing.value
    ) {
      // Restore the old value
      setTimeout(() => {
        if (isEditInitializing.value) {
          form.value.ar_partner_id = oldValue;
        }
      }, 0);
    }
  }
);

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
      const onlyDateChanged =
        originalMonth === newMonth &&
        originalYear === newYear &&
        originalData.tanggal !== form.value.tanggal;

      // Cek apakah hanya departemen yang berubah
      const onlyDepartmentChanged =
        originalData.bank_account_id != form.value.bank_account_id &&
        originalData.tanggal === form.value.tanggal;

      // Jika tidak ada perubahan atau hanya tanggal yang berubah (bulan dan tahun sama), jangan generate
      if (
        !onlyDepartmentChanged &&
        !(
          originalData.bank_account_id != form.value.bank_account_id ||
          originalData.tanggal != form.value.tanggal
        )
      ) {
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
          tanggal: form.value.tanggal,
        };

        // Tambahkan exclude_id dan current_no_bm jika dalam mode edit
        if (props.editData && props.editData.id) {
          params.exclude_id = props.editData.id;
          params.current_no_bm = props.editData.no_bm; // Kirim nomor BM saat ini
        }

        const { data } = await axios.get("/bank-masuk/next-number", { params });
        form.value.no_bm = data.no_bm;
      } catch {
        form.value.no_bm = "";
      }
    } else {
      form.value.no_bm = "";
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
  if (!form.value.bank_account_id)
    errors.value.bank_account_id = "Rekening wajib dipilih";

  // Clean nilai dari format currency untuk validasi
  let cleanNilai = form.value.nilai;
  if (cleanNilai) {
    // Remove currency symbol and formatting
    const symbol = selectedCurrency.value === "USD" ? "$" : "Rp ";
    cleanNilai = cleanNilai.replace(symbol, "").replace(/,/g, "");
  }

  if (!cleanNilai || isNaN(Number(cleanNilai)))
    errors.value.nilai = "Nominal wajib diisi";
  if (form.value.tipe_po === "Anggaran" && !form.value.purchase_order_id)
    errors.value.purchase_order_id = "Purchase Order wajib diisi";
  if (!form.value.terima_dari) errors.value.terima_dari = "Terima Dari wajib diisi";
  if (form.value.terima_dari === "Customer" && !form.value.ar_partner_id)
    errors.value.ar_partner_id = "Customer wajib dipilih";
  if (form.value.terima_dari === "Lainnya" && !form.value.input_lainnya)
    errors.value.input_lainnya = "Input Lainnya wajib diisi";
  return Object.keys(errors.value).length === 0;
}

function submit(keepForm = false) {
  clearAll();
  if (isSubmitting.value) return;
  // Pastikan tanggal format YYYY-MM-DD
  if (form.value.tanggal) {
    if (form.value.tanggal instanceof Date) {
      // Ambil tanggal lokal, bukan UTC
      const d = form.value.tanggal;
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, "0");
      const day = String(d.getDate()).padStart(2, "0");
      form.value.tanggal = `${year}-${month}-${day}`;
    }
  }
  if (form.value.match_date) {
    if (form.value.match_date instanceof Date) {
      const d = form.value.match_date;
      const year = d.getFullYear();
      const month = String(d.getMonth() + 1).padStart(2, "0");
      const day = String(d.getDate()).padStart(2, "0");
      form.value.match_date = `${year}-${month}-${day}`;
    }
  }
  if (!validate()) {
    addError("Periksa kembali input Anda.");
    return;
  }
  isSubmitting.value = true;

  // Clean nilai dari format currency sebelum kirim ke backend
  const data = { ...form.value };
  if (data.nilai) {
    const symbol = selectedCurrency.value === "USD" ? "$" : "Rp ";
    data.nilai = data.nilai.replace(symbol, "").replace(/,/g, "");
  }

  // Clean selisih fields dari format currency
  if (data.selisih_penambahan) {
    const symbol = selectedCurrency.value === "USD" ? "$" : "Rp ";
    data.selisih_penambahan = data.selisih_penambahan
      .replace(symbol, "")
      .replace(/,/g, "");
  }

  if (data.selisih_pengurangan) {
    const symbol = selectedCurrency.value === "USD" ? "$" : "Rp ";
    data.selisih_pengurangan = data.selisih_pengurangan
      .replace(symbol, "")
      .replace(/,/g, "");
  }

  if (data.nominal_akhir) {
    const symbol = selectedCurrency.value === "USD" ? "$" : "Rp ";
    data.nominal_akhir = data.nominal_akhir.replace(symbol, "").replace(/,/g, "");
  }
  // Jangan kirim no_bm saat update untuk menghindari konflik
  if (props.editData) {
    delete data.no_bm;
  }

  if (props.editData) {
    router.put(`/bank-masuk/${props.editData.id}`, data, {
      onSuccess: () => {
        addSuccess("Data bank masuk berhasil diupdate");
        emit("close");
        // Jika dipanggil dari halaman detail, tidak redirect ke index
        if (!props.isDetailPage) {
          router.get("/bank-masuk");
        }
      },
      onError: (serverErrors) => {
        clearAll();
        errors.value = {};
        if (serverErrors && typeof serverErrors === "object") {
          Object.entries(serverErrors).forEach(([key, val]) => {
            errors.value[key] = Array.isArray(val) ? val[0] : val;
          });
          const messages = Object.values(serverErrors).flat().join(" ");
          addError(messages || "Gagal memperbarui data bank masuk");
        } else {
          addError("Gagal memperbarui data bank masuk");
        }
      },
      onFinish: () => {
        isSubmitting.value = false;
      },
    });
  } else {
    router.post("/bank-masuk", data, {
      onSuccess: () => {
        addSuccess("Data bank masuk berhasil disimpan");
        if (keepForm) {
          // Simpan & Lanjutkan: Reset hanya field tertentu, field utama tetap
          form.value.nilai = "";
          form.value.selisih_penambahan = "";
          form.value.selisih_pengurangan = "";
          form.value.nominal_akhir = "";
          form.value.note = "";
          form.value.purchase_order_id = "";
          form.value.input_lainnya = "";

          // Don't reset ar_partner_id during edit initialization
          if (canResetArPartnerId.value) {
            form.value.ar_partner_id = "";
          }

          // Jangan reset match_date jika terima_dari adalah "Penjualan Toko"
          if (form.value.terima_dari === "Penjualan Toko") {
            // Match date tetap sama untuk Penjualan Toko
          } else {
            // Reset match_date untuk tipe lain
            form.value.match_date = null;
          }

          // Update preview nomor untuk data berikutnya
          updatePreviewNumber();

          // Tampilkan success message untuk simpan & lanjutkan
          addSuccess("Data berhasil disimpan! Form siap untuk input data berikutnya.");

          // Tidak refresh table dan tidak tutup form
        } else {
          // Simpan & Tutup: Tutup form dan redirect
          emit("close");
          router.get("/bank-masuk");
        }
      },
      onError: (serverErrors) => {
        clearAll();
        errors.value = {};
        if (serverErrors && typeof serverErrors === "object") {
          Object.entries(serverErrors).forEach(([key, val]) => {
            errors.value[key] = Array.isArray(val) ? val[0] : val;
          });
          const messages = Object.values(serverErrors).flat().join(" ");
          addError(messages || "Gagal menyimpan data bank masuk");
        } else {
          addError("Gagal menyimpan data bank masuk");
        }
      },
      onFinish: () => {
        isSubmitting.value = false;
      },
    });
  }
}
// async function generateNewNoBM() {
//   // Generate no_bm baru jika ada bank_account_id dan tanggal
//   if (form.value.bank_account_id && form.value.tanggal) {
//     try {
//       const params: any = {
//         bank_account_id: form.value.bank_account_id,
//         tanggal: form.value.tanggal,
//       };

//       const { data } = await axios.get("/bank-masuk/next-number", { params });
//       form.value.no_bm = data.no_bm;
//     } catch {
//       form.value.no_bm = "";
//     }
//   } else {
//     form.value.no_bm = "";
//   }
// }

function handleBatal() {
  emit("close");
//   // Refresh konten sesuai konteks
//   if (props.isDetailPage) {
//     // Jika di halaman detail, refresh halaman detail
//     router.get(`/bank-masuk/${props.editData?.id}`);
//   } else {
//     // Jika di halaman index, refresh tabel
//     emit("refreshTable");
//   }
}

// Add paste event listener when component is mounted
onMounted(() => {
  // Use nextTick to ensure DOM is fully rendered

  // Update preview nomor saat component mounted
  if (form.value.bank_account_id && form.value.tanggal) {
    updatePreviewNumber();
  }
  nextTick(() => {
    const nominalInput = document.getElementById("nilai") as HTMLInputElement;
    if (nominalInput) {
      // Remove any existing paste listeners first
      nominalInput.removeEventListener("paste", handlePaste);

      // Add new paste listener
      nominalInput.addEventListener("paste", handlePaste);

    } else {
      console.warn("Nominal input not found for paste event listener");
    }
  });
});

// Separate function for paste handling
function handlePaste(e: ClipboardEvent) {
  // Allow default paste behavior
  // The input event handler will clean the value automatically

  // Set a flag to indicate this is a paste operation
  const target = e.target as HTMLInputElement;
  if (target) {
    target.dataset.pasteOperation = "true";

    // Clear the flag after a short delay
    setTimeout(() => {
      delete target.dataset.pasteOperation;
    }, 100);
  }
}
</script>

<template>
  <div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div
      class="bg-white rounded-lg w-full max-w-4xl max-h-[90vh] overflow-y-auto shadow-xl"
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
        <form @submit.prevent="submit(false)" novalidate class="space-y-6">
          <!-- Row 1: No Bank Masuk & Tanggal Bank Masuk -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- No Bank Masuk -->
            <div class="floating-input">
              <div class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed">
                {{ previewBankMasukNumber }}
              </div>
              <label for="no_bm" class="floating-label">No. Bank Masuk</label>
            </div>
            <!-- Tanggal Bank Masuk -->
            <div class="floating-input">
              <label class="block text-xs font-light text-gray-700 mb-1"
                >Tanggal Bank Masuk<span class="text-red-500">*</span></label
              >
              <Datepicker
                v-model="validTanggal"
                :input-class="['floating-input-field', validTanggal ? 'filled' : '']"
                placeholder=" "
                :format="(date: string | Date) => {
                  if (!date) return '';
                  try {
                    const dateObj = new Date(date);
                    if (isNaN(dateObj.getTime())) return '';
                    return dateObj.toLocaleDateString('id-ID');
                  } catch {
                    return '';
                  }
                }"
                :enable-time-picker="false"
                :auto-apply="true"
                :close-on-auto-apply="true"
                id="tanggal"
              />
              <!-- <label for="tanggal" class="floating-label">
                Tanggal Bank Masuk<span class="text-red-500">*</span>
              </label> -->
              <div v-if="errors.tanggal" class="text-red-500 text-xs mt-1">
                {{ errors.tanggal }}
              </div>
            </div>
          </div>

          <!-- Row 2: Tipe (full width) -->
          <div>
            <!-- <label class="block text-sm font-medium text-gray-700 mb-3">Tipe<span class="text-red-500">*</span></label> -->
            <div class="flex gap-6">
              <label class="inline-flex items-center">
                <input
                  type="radio"
                  value="Reguler"
                  v-model="form.tipe_po"
                  class="form-radio text-blue-600"
                />
                <span class="ml-2">Reguler</span>
              </label>
              <label class="inline-flex items-center">
                <input
                  type="radio"
                  value="Anggaran"
                  v-model="form.tipe_po"
                  class="form-radio text-blue-600"
                />
                <span class="ml-2">Anggaran</span>
              </label>
              <label class="inline-flex items-center">
                <input
                  type="radio"
                  value="Lainnya"
                  v-model="form.tipe_po"
                  class="form-radio text-blue-600"
                />
                <span class="ml-2">Lainnya</span>
              </label>
            </div>
            <div v-if="errors.tipe_po" class="text-red-500 text-xs mt-1">
              {{ errors.tipe_po }}
            </div>
          </div>

          <!-- Row 3: Department & Rekening -->
          <!-- Department -->
          <div class="floating-input">
            <CustomSelect
              :model-value="form.department_id"
              @update:modelValue="(val) => (form.department_id = val)"
              :options="departmentOptions"
              placeholder="Pilih Department"
              :disabled="isSingleDepartment"
            >
              <template #label>Department<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
              Department wajib dipilih
            </div>
          </div>
          <!-- Rekening -->
          <div class="floating-input">
            <CustomSelect
              :model-value="form.bank_account_id"
              @update:modelValue="(val) => (form.bank_account_id = val)"
              :options="filteredBankAccounts.map((acc: any) => ({
                  label: `${acc.bank?.singkatan || 'Unknown'} - ******${acc.no_rekening.slice(-5)}`,
                  value: acc.id
                }))"
              placeholder="Pilih Rekening"
              :disabled="!form.department_id || filteredBankAccounts.length === 1"
            >
              <template #label>Rekening<span class="text-red-500">*</span></template>
            </CustomSelect>
            <div v-if="errors.bank_account_id" class="text-red-500 text-xs mt-1">
              Rekening wajib dipilih
            </div>
          </div>

          <!-- Row 4: Terima Dari & Customer/Tanggal Match -->
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
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
            </div>
            <!-- Customer/Tanggal Match Column -->
            <div>
              <!-- Customer dropdown (jika terima_dari Customer) -->
              <div v-if="form.terima_dari === 'Customer'">
                <CustomSelect
                  :model-value="form.ar_partner_id"
                  @update:modelValue="(val) => (form.ar_partner_id = val)"
                  :options="arPartnersOptions"
                  :loading="isLoadingArPartners"
                  placeholder="Pilih Customer"
                  :searchable="true"
                  :disabled="!form.department_id"
                  @search="searchArPartners"
                >
                  <template #label>Customer<span class="text-red-500">*</span></template>
                </CustomSelect>
                <div v-if="errors.ar_partner_id" class="text-red-500 text-xs mt-1">
                  {{ errors.ar_partner_id }}
                </div>
              </div>
              <!-- Tanggal Match (jika terima_dari Penjualan Toko) -->
              <div v-if="form.terima_dari === 'Penjualan Toko'" class="floating-input">
                <label class="block text-xs font-light text-gray-700 mb-1"
                  >Tanggal Match<span class="text-red-500">*</span></label
                >
                <Datepicker
                  v-model="validMatchDate"
                  :input-class="['floating-input-field', validMatchDate ? 'filled' : '']"
                  placeholder=" "
                  :format="(date: string | Date) => {
                    if (!date) return '';
                    try {
                      const dateObj = new Date(date);
                      if (isNaN(dateObj.getTime())) return '';
                      return dateObj.toLocaleDateString('id-ID');
                    } catch {
                      return '';
                    }
                  }"
                  :enable-time-picker="false"
                  :auto-apply="true"
                  :close-on-auto-apply="true"
                  id="match_date"
                />
                <!-- <label for="match_date" class="floating-label">Tanggal Match</label> -->
                <div v-if="errors.match_date" class="text-red-500 text-xs mt-1">
                  {{ errors.match_date }}
                </div>
              </div>
              <!-- Input Lainnya (jika terima_dari Lainnya) -->
              <div v-if="form.terima_dari === 'Lainnya'" class="floating-input">
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
                <div v-if="errors.input_lainnya" class="text-red-500 text-xs mt-1">
                  {{ errors.input_lainnya }}
                </div>
              </div>
            </div>
          </div>

          <!-- Row 5: Nominal (full width) -->
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
              >Nominal Awal<span class="text-red-500">*</span></label
            >
            <div v-if="errors.nilai" class="text-red-500 text-xs mt-1">
              {{ errors.nilai }}
            </div>
          </div>

          <!-- Row 5.1: Selisih Penambahan & Selisih Pengurangan (hanya untuk Penjualan Toko) -->
          <div
            v-if="form.terima_dari === 'Penjualan Toko'"
            class="grid grid-cols-1 md:grid-cols-2 gap-4"
          >
            <!-- Selisih Penambahan -->
            <div class="floating-input">
              <input
                type="text"
                v-model="form.selisih_penambahan"
                id="selisih_penambahan"
                class="floating-input-field"
                :class="{ 'border-red-500': errors.selisih_penambahan }"
                placeholder=" "
                autocomplete="off"
                @blur="formatSelisihPenambahanOnBlur"
                @keydown="handleSelisihPenambahanKeydown"
                @input="handleSelisihPenambahanInput"
              />
              <label for="selisih_penambahan" class="floating-label">
                Selisih (Penambahan)
              </label>
              <div v-if="errors.selisih_penambahan" class="text-red-500 text-xs mt-1">
                {{ errors.selisih_penambahan }}
              </div>
            </div>
            <!-- Selisih Pengurangan -->
            <div class="floating-input">
              <input
                type="text"
                v-model="form.selisih_pengurangan"
                id="selisih_pengurangan"
                class="floating-input-field"
                :class="{ 'border-red-500': errors.selisih_pengurangan }"
                placeholder=" "
                autocomplete="off"
                @blur="formatSelisihPenguranganOnBlur"
                @keydown="handleSelisihPenguranganKeydown"
                @input="handleSelisihPenguranganInput"
              />
              <label for="selisih_pengurangan" class="floating-label">
                Selisih (Pengurangan)
              </label>
              <div v-if="errors.selisih_pengurangan" class="text-red-500 text-xs mt-1">
                {{ errors.selisih_pengurangan }}
              </div>
            </div>
          </div>

          <!-- Row 5.2: Nominal Akhir (readonly, hanya untuk Penjualan Toko) -->
          <div v-if="form.terima_dari === 'Penjualan Toko'" class="floating-input">
            <input
              type="text"
              v-model="form.nominal_akhir"
              id="nominal_akhir"
              class="floating-input-field bg-gray-50"
              :class="{ 'border-red-500': errors.nominal_akhir }"
              placeholder=" "
              readonly
            />
            <label for="nominal_akhir" class="floating-label"> Nominal Akhir </label>
            <div v-if="errors.nominal_akhir" class="text-red-500 text-xs mt-1">
              {{ errors.nominal_akhir }}
            </div>
          </div>

          <!-- Row 6: Purchase Order (full width, jika tipe_po Anggaran) -->
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
            <div v-if="errors.purchase_order_id" class="text-red-500 text-xs mt-1">
              {{ errors.purchase_order_id }}
            </div>
          </div>

          <!-- Row 7: Note (full width) -->
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
              class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
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
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
  color: #374151 !important;
  border: 1px solid #d1d5db !important;
}

.floating-input .dp__input::placeholder {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  color: #6b7280 !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
}

.floating-input .dp__input:focus {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  border-color: #1f9254 !important;
}

/* Ensure datepicker dropdown also uses Quicksand */
.floating-input .dp__main {
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
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
  font-family: "Quicksand", Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
}

/* Radio button styling */
.form-radio {
  width: 1rem;
  height: 1rem;
  color: #3b82f6;
  border: 1px solid #d1d5db;
}

.form-radio:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}
</style>
