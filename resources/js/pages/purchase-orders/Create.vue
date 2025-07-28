<template>
  <div class="bg-white rounded-lg shadow-sm p-6">
    <h1 class="text-xl font-bold mb-4">Buat Purchase Order</h1>
    <div
      v-if="notif"
      :class="notif.type === 'error' ? 'text-red-600' : 'text-green-600'"
      class="mb-2"
    >
      {{ notif.message }}
    </div>
    <form @submit.prevent="onSubmit" novalidate class="space-y-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="floating-input">
          <input
            type="text"
            v-model="form.no_po"
            readonly
            id="no_po"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="no_po" class="floating-label">No. Purchase Order</label>
        </div>
        <div class="floating-input">
          <select v-model="form.tipe_po" id="tipe_po" class="floating-input-field">
            <option value="">Pilih Tipe PO</option>
            <option value="Reguler">Reguler</option>
            <option value="Lainnya">Lainnya</option>
          </select>
          <label for="tipe_po" class="floating-label">Tipe PO</label>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="floating-input">
          <input
            type="text"
            v-model="form.tanggal"
            readonly
            id="tanggal"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="tanggal" class="floating-label">Tanggal</label>
        </div>
        <div class="floating-input">
          <select
            v-model="form.department_id"
            id="department_id"
            class="floating-input-field"
            required
          >
            <option value="">Pilih Departemen</option>
            <option v-for="d in departemenList" :key="d.id" :value="d.id">
              {{ d.name }}
            </option>
          </select>
          <label for="department_id" class="floating-label">
            Departemen<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="floating-input">
          <select
            v-model="form.perihal"
            id="perihal"
            class="floating-input-field"
            required
          >
            <option value="">Pilih Perihal</option>
            <option v-for="p in perihalList" :key="p.id" :value="p.nama">
              {{ p.nama }}
            </option>
          </select>
          <label for="perihal" class="floating-label">
            Perihal<span class="text-red-500">*</span>
          </label>
        </div>
        <div class="floating-input">
          <input
            type="text"
            v-model="form.no_invoice"
            id="no_invoice"
            class="floating-input-field"
            placeholder=" "
            required
          />
          <label for="no_invoice" class="floating-label">
            No. Invoice<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="floating-input">
          <input
            type="number"
            v-model.number="form.harga"
            id="harga"
            class="floating-input-field"
            placeholder=" "
            required
          />
          <label for="harga" class="floating-label">
            Harga<span class="text-red-500">*</span>
          </label>
        </div>
        <div class="floating-input">
          <select
            v-model="form.metode_pembayaran"
            id="metode_pembayaran"
            class="floating-input-field"
            required
          >
            <option value="">Pilih Metode</option>
            <option value="Transfer">Transfer</option>
            <option value="Cek/Giro">Cek/Giro</option>
          </select>
          <label for="metode_pembayaran" class="floating-label">
            Metode Pembayaran<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6">
        <div class="floating-input">
          <textarea
            v-model="form.detail_keperluan"
            id="detail_keperluan"
            class="floating-input-field resize-none"
            placeholder=" "
            rows="3"
          ></textarea>
          <label for="detail_keperluan" class="floating-label">Detail Keperluan</label>
        </div>
      </div>

      <div
        v-if="form.metode_pembayaran === 'Cek/Giro'"
        class="grid grid-cols-1 md:grid-cols-3 gap-6"
      >
        <div class="floating-input">
          <input
            type="text"
            v-model="form.no_giro"
            id="no_giro"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="no_giro" class="floating-label">No. Cek/Giro</label>
        </div>
        <div class="floating-input">
          <input
            type="date"
            v-model="form.tanggal_giro"
            id="tanggal_giro"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="tanggal_giro" class="floating-label">Tanggal Giro</label>
        </div>
        <div class="floating-input">
          <input
            type="date"
            v-model="form.tanggal_cair"
            id="tanggal_cair"
            class="floating-input-field"
            placeholder=" "
          />
          <label for="tanggal_cair" class="floating-label">Tanggal Cair</label>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="floating-input">
          <select
            v-model="form.nama_bank"
            id="nama_bank"
            class="floating-input-field"
            required
          >
            <option value="">Pilih Bank</option>
            <option v-for="b in bankList" :key="b" :value="b">{{ b }}</option>
          </select>
          <label for="nama_bank" class="floating-label">
            Nama Bank<span class="text-red-500">*</span>
          </label>
        </div>
        <div class="floating-input">
          <input
            type="text"
            v-model="form.nama_rekening"
            id="nama_rekening"
            class="floating-input-field"
            placeholder=" "
            required
          />
          <label for="nama_rekening" class="floating-label">
            Nama Rekening<span class="text-red-500">*</span>
          </label>
        </div>
        <div class="floating-input">
          <input
            type="text"
            v-model="form.no_rekening"
            id="no_rekening"
            class="floating-input-field"
            placeholder=" "
            required
          />
          <label for="no_rekening" class="floating-label">
            No. Rekening<span class="text-red-500">*</span>
          </label>
        </div>
      </div>

      <div class="grid grid-cols-1 gap-6">
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
      </div>

      <!-- Khusus Staff Toko: Upload Dokumen Draft Invoice -->
      <div v-if="isStaffToko" class="grid grid-cols-1 gap-6">
        <div class="floating-input">
          <input
            type="file"
            @change="onFileChange"
            id="dokumen"
            class="floating-input-field"
          />
          <label for="dokumen" class="floating-label">Upload Dokumen Draft Invoice</label>
        </div>
      </div>

      <hr class="my-6" />

      <!-- Grid/List Barang -->
      <div v-if="isLainnya" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="floating-input">
            <input
              type="number"
              v-model.number="form.cicilan"
              id="cicilan"
              class="floating-input-field"
              placeholder=" "
              min="0"
            />
            <label for="cicilan" class="floating-label">Cicilan</label>
          </div>
          <div class="floating-input">
            <input
              type="number"
              v-model.number="form.termin"
              id="termin"
              class="floating-input-field"
              placeholder=" "
              min="0"
            />
            <label for="termin" class="floating-label">Termin</label>
          </div>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="floating-input">
            <input
              type="number"
              v-model.number="form.nominal"
              id="nominal"
              class="floating-input-field"
              placeholder=" "
              min="0"
              required
            />
            <label for="nominal" class="floating-label">
              Nominal<span class="text-red-500">*</span>
            </label>
          </div>
          <div class="floating-input">
            <select
              v-model="form.keterangan"
              id="keterangan"
              class="floating-input-field"
              required
            >
              <option value="">Pilih Metode Pembayaran</option>
              <option value="Transfer">Transfer</option>
              <option value="Cek/Giro">Cek/Giro</option>
            </select>
            <label for="keterangan" class="floating-label">
              Keterangan<span class="text-red-500">*</span>
            </label>
          </div>
        </div>
      </div>

      <PurchaseOrderBarangGrid
        v-model:items="barangList"
        v-model:diskon="form.diskon"
        v-model:ppn="form.ppn"
        v-model:pph="form.pph"
        :pphList="pphList"
        @add-pph="onAddPph"
        :nominal="isLainnya ? Number(form.nominal) : undefined"
      />

      <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
        <button
          type="submit"
          class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          :disabled="loading"
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
          Kirim
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="onSaveDraft"
          :disabled="loading"
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
              d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z"
            />
          </svg>
          Simpan Draft
        </button>
        <button
          type="button"
          class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
          @click="goBack"
          :disabled="loading"
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
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { useRouter } from "vue-router";
import PurchaseOrderBarangGrid from "../../components/purchase-orders/PurchaseOrderBarangGrid.vue";
import axios from "axios";

// Dummy data master
const departemenList = ref([
  { id: 1, name: "SGT 1" },
  { id: 2, name: "SGT 2" },
  { id: 3, name: "SGT 3" },
  { id: 4, name: "Nirwana Textile Hasanudin" },
  { id: 5, name: "Nirwana Textile Bkr" },
  { id: 6, name: "Nirwana Textile Yogyakarta" },
  { id: 7, name: "Nirwana Textile Bali" },
  { id: 8, name: "Nirwana Textile Surabaya" },
  { id: 9, name: "Human Greatness" },
  { id: 10, name: "Zi&Glo" },
]);
const perihalList = ref<any[]>([]);

onMounted(() => {
  axios.get("/perihals").then((res) => {
    perihalList.value = res.data.filter((p: any) => p.status === "active");
  });
});
const bankList = ref(["BCA", "Mandiri", "BRI", "BNI", "CIMB", "Danamon"]);
const pphList = ref([
  { kode: "21", nama: "PPh 21", tarif: 0.05 },
  { kode: "23", nama: "PPh 23", tarif: 0.02 },
]);

const router = useRouter();
const isStaffToko = false; // TODO: deteksi dari user login

const form = ref({
  no_po: "",
  tipe_po: "Reguler",
  tanggal: "",
  department_id: "",
  perihal: "",
  no_invoice: "",
  harga: 0,
  detail_keperluan: "",
  metode_pembayaran: "",
  nama_bank: "",
  nama_rekening: "",
  no_rekening: "",
  note: "",
  no_giro: "",
  tanggal_giro: "",
  tanggal_cair: "",
  diskon: 0,
  ppn: false,
  pph: [],
  cicilan: 0,
  termin: 0,
  nominal: 0,
  keterangan: "",
});
const barangList = ref<any[]>([]);
const loading = ref(false);
const notif = ref<{ type: string; message: string } | null>(null);
const dokumenFile = ref<File | null>(null);

const isLainnya = computed(() => form.value.tipe_po === "Lainnya");

function onFileChange(e: Event) {
  const files = (e.target as HTMLInputElement).files;
  if (files && files.length > 0) {
    dokumenFile.value = files[0];
  }
}
function onAddPph(pphBaru: any) {
  pphList.value.push(pphBaru);
}
function goBack() {
  router.push({ name: "purchase-orders.index" });
}

function validateForm() {
  if (
    !form.value.department_id ||
    !form.value.perihal ||
    !form.value.no_invoice ||
    !form.value.harga ||
    !form.value.metode_pembayaran ||
    !form.value.nama_bank ||
    !form.value.nama_rekening ||
    !form.value.no_rekening
  ) {
    notif.value = { type: "error", message: "Lengkapi semua field wajib!" };
    return false;
  }
  if (!barangList.value.length) {
    notif.value = { type: "error", message: "Minimal 1 barang harus diisi!" };
    return false;
  }
  return true;
}

async function onSaveDraft() {
  notif.value = null;
  if (!validateForm()) return;
  loading.value = true;
  try {
    const formData = new FormData();
    Object.entries(form.value).forEach(([k, v]) => formData.append(k, v as any));
    formData.append("status", "Draft");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);
    await axios.post("/purchase-orders", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    notif.value = { type: "success", message: "Draft PO berhasil disimpan!" };
    setTimeout(() => router.push({ name: "purchase-orders.index" }), 1000);
  } catch (e: any) {
    notif.value = {
      type: "error",
      message: e?.response?.data?.message || "Gagal simpan draft.",
    };
  } finally {
    loading.value = false;
  }
}

async function onSubmit() {
  notif.value = null;
  if (!validateForm()) return;
  loading.value = true;
  try {
    const formData = new FormData();
    Object.entries(form.value).forEach(([k, v]) => formData.append(k, v as any));
    formData.append("status", "In Progress");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);
    await axios.post("/purchase-orders", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    notif.value = { type: "success", message: "PO berhasil dikirim!" };
    setTimeout(() => router.push({ name: "purchase-orders.index" }), 1000);
  } catch (e: any) {
    notif.value = {
      type: "error",
      message: e?.response?.data?.message || "Gagal kirim PO.",
    };
  } finally {
    loading.value = false;
  }
}
</script>

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
