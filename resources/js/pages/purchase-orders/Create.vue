<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Buat Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <ShoppingCart class="w-4 h-4 mr-1" />
            Create new Purchase Order
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <div
          v-if="notif"
          :class="notif.type === 'error' ? 'text-red-600' : 'text-green-600'"
          class="mb-2"
        >
          {{ notif.message }}
        </div>
        <form @submit.prevent="onSubmit" novalidate class="space-y-4">
          <!-- Form Layout for Reguler -->
          <div v-if="form.tipe_po === 'Reguler'" class="space-y-4">
            <!-- Row 1: No. PO | Metode Bayar -->
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
              <CustomSelect
                :model-value="form.metode_pembayaran ?? ''"
                @update:modelValue="(val) => (form.metode_pembayaran = val as string)"
                :options="[
                  { label: 'Transfer', value: 'Transfer' },
                  { label: 'Cek/Giro', value: 'Cek/Giro' },
                ]"
                placeholder="Pilih Metode"
              >
                <template #label>
                  Metode Pembayaran<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
            </div>

            <!-- Row 2: Tipe PO | Nama Bank -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="mb-6">
                <div class="flex space-x-6">
                  <label class="flex items-center">
                    <input
                      type="radio"
                      v-model="form.tipe_po"
                      value="Reguler"
                      class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Reguler</span>
                  </label>
                  <label class="flex items-center">
                    <input
                      type="radio"
                      v-model="form.tipe_po"
                      value="Lainnya"
                      class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Lainnya</span>
                  </label>
                </div>
              </div>
              <CustomSelect
                :model-value="form.nama_bank ?? ''"
                @update:modelValue="(val) => (form.nama_bank = val as string)"
                :options="bankList.map((b: any) => ({ label: b.nama_bank, value: b.nama_bank }))"
                placeholder="Pilih Bank"
              >
                <template #label> Nama Bank<span class="text-red-500">*</span> </template>
              </CustomSelect>
            </div>

            <!-- Row 3: Tanggal | Nama Rekening -->
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
            </div>

            <!-- Row 4: Departemen | No Rekening/VA -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <CustomSelect
                :model-value="form.department_id ?? ''"
                @update:modelValue="(val) => (form.department_id = val as any)"
                :options="departemenList.map((d: any) => ({ label: d.name, value: String(d.id) }))"
                :disabled="(departemenList || []).length === 1"
              >
                <template #label> Departemen<span class="text-red-500">*</span> </template>
              </CustomSelect>
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
                  No. Rekening/VA<span class="text-red-500">*</span>
                </label>
              </div>
            </div>

            <!-- Row 5: Perihal | Note -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <CustomSelect
                :model-value="form.perihal ?? ''"
                @update:modelValue="(val) => (form.perihal = val as string)"
                :options="perihalList.map((p: any) => ({ label: p.nama, value: p.nama }))"
                >
                <template #label> Perihal<span class="text-red-500">*</span> </template>
              </CustomSelect>
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

            <!-- Row 6: No Invoice (single column) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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

            <!-- Row 7: Harga (single column) -->
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
            </div>

            <!-- Row 8: Detail Keperluan (single column) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <textarea
                  v-model="form.detail_keperluan"
                  id="detail_keperluan"
                  class="floating-input-field resize-none"
                  placeholder=" "
                  rows="3"
                ></textarea>
                <label for="detail_keperluan" class="floating-label"
                  >Detail Keperluan</label
                >
              </div>
            </div>

            <!-- Conditional fields for Cek/Giro -->
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
          </div>

          <!-- Form Layout for Lainnya -->
          <div v-else-if="form.tipe_po === 'Lainnya'" class="space-y-4">
            <!-- Row 1: No. PO | Nominal -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <input
                  type="text"
                  v-model="form.no_po"
                  readonly
                  id="no_po_lainnya"
                  class="floating-input-field"
                  placeholder=" "
                />
                <label for="no_po_lainnya" class="floating-label"
                  >No. Purchase Order</label
                >
              </div>
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
            </div>

            <!-- Row 2: Tipe PO | Note -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="mb-6">
                <div class="flex space-x-6">
                  <label class="flex items-center">
                    <input
                      type="radio"
                      v-model="form.tipe_po"
                      value="Reguler"
                      class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Reguler</span>
                  </label>
                  <label class="flex items-center">
                    <input
                      type="radio"
                      v-model="form.tipe_po"
                      value="Lainnya"
                      class="h-4 w-4 text-[#7F9BE6] focus:ring-[#7F9BE6] border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Lainnya</span>
                  </label>
                </div>
              </div>
              <div class="floating-input">
                <textarea
                  v-model="form.note"
                  id="note_lainnya"
                  class="floating-input-field resize-none"
                  placeholder=" "
                  rows="3"
                ></textarea>
                <label for="note_lainnya" class="floating-label">Note</label>
              </div>
            </div>

            <!-- Row 3: Tanggal (single column) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <input
                  type="text"
                  v-model="form.tanggal"
                  readonly
                  id="tanggal_lainnya"
                  class="floating-input-field"
                  placeholder=" "
                />
                <label for="tanggal_lainnya" class="floating-label">Tanggal</label>
              </div>
            </div>

            <!-- Row 4: Perihal (single column) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <CustomSelect
                :model-value="form.perihal ?? ''"
                @update:modelValue="(val) => (form.perihal = val as string)"
                :options="perihalList.map((p: any) => ({ label: p.nama, value: p.nama }))"
                placeholder="Pilih Perihal"
              >
                <template #label> Perihal<span class="text-red-500">*</span> </template>
              </CustomSelect>
            </div>

            <!-- Row 5: Cicilan (single column) -->
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
            </div>

            <!-- Row 6: Termin (single column) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
          </div>

          <!-- Khusus Staff Toko: Upload Dokumen Draft Invoice -->
          <div v-if="isStaffToko" class="grid grid-cols-1 gap-6">
            <FileUpload
              v-model="dokumenFile"
              label="Draft Invoice"
              :required="true"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
              :max-size="50 * 1024 * 1024"
              drag-text="Bawa berkas ke area ini (maks. 50 MB)"
              @error="(message) => notif = { type: 'error', message }"
            />
          </div>

          <hr class="my-6" />

          <!-- Grid/List Barang -->
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
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed } from "vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrderBarangGrid from "../../components/purchase-orders/PurchaseOrderBarangGrid.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import CustomSelect from "@/components/ui/CustomSelect.vue";
import FileUpload from "@/components/ui/FileUpload.vue";
import { ShoppingCart } from "lucide-vue-next";
import axios from "axios";
import AppLayout from "@/layouts/AppLayout.vue";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Purchase Order", href: "/purchase-orders" },
  { label: "Create" },
];

// Master data from props (provided by Inertia controller)
const props = defineProps<{
  departments: any[];
  perihals: any[];
  banks: any[];
  pphs: any[];
}>();
const departemenList = ref(props.departments || []);
const perihalList = ref<any[]>(props.perihals || []);
const bankList = ref(props.banks || []);
// Transform PPH data to match the expected format in PurchaseOrderBarangGrid
const pphList = ref(
  (props.pphs || []).map((pph: any) => ({
    kode: pph.kode_pph,
    nama: pph.nama_pph,
    tarif: pph.tarif_pph ? pph.tarif_pph / 100 : 0, // Convert percentage to decimal
  }))
);

const isStaffToko = true; // TODO: deteksi dari user login

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
// Auto-select department when only one available
if (!form.value.department_id && (departemenList.value || []).length === 1) {
  form.value.department_id = String(departemenList.value[0].id);
}
const barangList = ref<any[]>([]);
const loading = ref(false);
const notif = ref<{ type: string; message: string } | null>(null);
const dokumenFile = ref<File | null>(null);

const isLainnya = computed(() => form.value.tipe_po === "Lainnya");
function onAddPph(pphBaru: any) {
  // Transform the new PPH data to match the expected format
  const transformedPph = {
    kode: pphBaru.kode_pph || pphBaru.kode,
    nama: pphBaru.nama_pph || pphBaru.nama,
    tarif: pphBaru.tarif_pph ? pphBaru.tarif_pph / 100 : (pphBaru.tarif || 0),
  };
  pphList.value.push(transformedPph);
}
function goBack() {
  router.visit("/purchase-orders");
}

function validateForm() {
  if (form.value.tipe_po === "Reguler") {
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
  } else if (form.value.tipe_po === "Lainnya") {
    if (!form.value.perihal || !form.value.nominal) {
      notif.value = { type: "error", message: "Lengkapi semua field wajib!" };
      return false;
    }
  }

  if (!barangList.value.length) {
    notif.value = { type: "error", message: "Minimal 1 barang harus diisi!" };
    return false;
  }

  // Validate file upload for staff toko
  if (isStaffToko && !dokumenFile.value) {
    notif.value = { type: "error", message: "Draft Invoice harus diupload!" };
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
    setTimeout(() => router.visit("/purchase-orders"), 1000);
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
    // Always create as Draft first
    formData.append("status", "Draft");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);
    const createRes = await axios.post("/purchase-orders", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    const created = createRes?.data;
    if (created?.id) {
      // Immediately send the created PO
      await axios.post("/purchase-orders/send", { ids: [created.id] });
    }
    notif.value = { type: "success", message: "PO berhasil dikirim!" };
    setTimeout(() => router.visit("/purchase-orders"), 800);
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
