<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Edit Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            Edit Purchase Order #{{ purchaseOrder.no_po }}
          </div>
        </div>
      </div>

      <div class="bg-white rounded-lg shadow-sm p-6">
        <form @submit.prevent="onSubmit" novalidate class="space-y-4">
          <!-- Form Layout for Reguler -->
          <div class="space-y-4">
            <!-- Row 1: No. PO | Metode Bayar -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <div
                  class="floating-input-field bg-gray-50 text-gray-600 cursor-not-allowed"
                >
                  {{ form.no_po || previewNumber || "Akan di-generate otomatis" }}
                </div>
                <label for="no_po" class="floating-label">No. Purchase Order</label>
              </div>
              <div>
                <CustomSelect
                  :model-value="form.metode_pembayaran ?? ''"
                  @update:modelValue="(val) => (form.metode_pembayaran = val as string)"
                  :options="[
                    { label: 'Transfer', value: 'Transfer' },
                    { label: 'Cek/Giro', value: 'Cek/Giro' },
                    { label: 'Kredit', value: 'Kredit' },
                  ]"
                  placeholder="Pilih Metode"
                  :class="{ 'border-red-500': errors.metode_pembayaran }"
                >
                  <template #label>
                    Metode Pembayaran<span class="text-red-500">*</span>
                  </template>
                </CustomSelect>
                <div v-if="errors.metode_pembayaran" class="text-red-500 text-xs mt-1">
                  {{ errors.metode_pembayaran }}
                </div>
              </div>
            </div>

            <!-- Row 2: Tipe PO | Payment Method Fields -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="flex space-x-12 items-center">
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
              <!-- Dynamic field based on payment method -->
              <div
                v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran"
              >
                <CustomSelect
                  :model-value="form.bank_id ?? ''"
                  @update:modelValue="(val) => (form.bank_id = val as any)"
                  :options="bankList.map((b: any) => ({ label: b.nama_bank, value: String(b.id) }))"
                  placeholder="Pilih Bank"
                  :class="{ 'border-red-500': errors.bank_id }"
                >
                  <template #label>
                    Nama Bank<span class="text-red-500">*</span>
                  </template>
                </CustomSelect>
                <div v-if="errors.bank_id" class="text-red-500 text-xs mt-1">
                  {{ errors.bank_id }}
                </div>
              </div>
              <div
                v-else-if="form.metode_pembayaran === 'Cek/Giro'"
                class="floating-input"
              >
                <input
                  type="text"
                  v-model="form.no_giro"
                  id="no_giro"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.no_giro }"
                  placeholder=" "
                  required
                />
                <label for="no_giro" class="floating-label"
                  >No. Cek/Giro<span class="text-red-500">*</span></label
                >
                <div v-if="errors.no_giro" class="text-red-500 text-xs mt-1">
                  {{ errors.no_giro }}
                </div>
              </div>
              <div
                v-else-if="form.metode_pembayaran === 'Kredit'"
                class="floating-input"
              >
                <input
                  type="text"
                  v-model="form.no_kartu_kredit"
                  id="no_kartu_kredit"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.no_kartu_kredit }"
                  placeholder=" "
                  required
                />
                <label for="no_kartu_kredit" class="floating-label"
                  >No. Kartu Kredit<span class="text-red-500">*</span></label
                >
                <div v-if="errors.no_kartu_kredit" class="text-red-500 text-xs mt-1">
                  {{ errors.no_kartu_kredit }}
                </div>
              </div>
            </div>

            <!-- Row 3: Tanggal | Dynamic Field 2 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <label class="block text-xs font-light text-gray-700 mb-1">Tanggal</label>
                <Datepicker
                  v-model="validTanggal"
                  :input-class="['floating-input-field', validTanggal ? 'filled' : '']"
                  placeholder="Tanggal"
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
              </div>
              <!-- Dynamic field based on payment method -->
              <div
                v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran"
                class="floating-input"
              >
                <input
                  type="text"
                  v-model="form.nama_rekening"
                  id="nama_rekening"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.nama_rekening }"
                  placeholder=" "
                  required
                />
                <label for="nama_rekening" class="floating-label">
                  Nama Rekening<span class="text-red-500">*</span>
                </label>
                <div v-if="errors.nama_rekening" class="text-red-500 text-xs mt-1">
                  {{ errors.nama_rekening }}
                </div>
              </div>
              <div
                v-else-if="form.metode_pembayaran === 'Cek/Giro'"
                class="floating-input"
              >
                <label class="block text-xs font-light text-gray-700 mb-1"
                  >Tanggal Giro<span class="text-red-500">*</span></label
                >
                <Datepicker
                  v-model="validTanggalGiro"
                  :input-class="[
                    'floating-input-field',
                    validTanggalGiro ? 'filled' : '',
                    errors.tanggal_giro ? 'border-red-500' : '',
                  ]"
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
                  id="tanggal_giro"
                />
                <div v-if="errors.tanggal_giro" class="text-red-500 text-xs mt-1">
                  {{ errors.tanggal_giro }}
                </div>
              </div>
            </div>

            <!-- Row 4: Departemen | Dynamic Field 3 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <CustomSelect
                  :model-value="form.department_id ?? ''"
                  @update:modelValue="(val) => (form.department_id = val as any)"
                  :options="departemenList.map((d: any) => ({ label: d.name, value: String(d.id) }))"
                  :disabled="(departemenList || []).length === 1"
                  placeholder="Pilih Departemen"
                  :class="{ 'border-red-500': errors.department_id }"
                >
                  <template #label>
                    Departemen<span class="text-red-500">*</span>
                  </template>
                </CustomSelect>
                <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
                  {{ errors.department_id }}
                </div>
              </div>
              <!-- Dynamic field based on payment method -->
              <div
                v-if="form.metode_pembayaran === 'Transfer' || !form.metode_pembayaran"
                class="floating-input"
              >
                <input
                  type="text"
                  v-model="form.no_rekening"
                  id="no_rekening"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.no_rekening }"
                  placeholder=" "
                  required
                  @keydown="allowDigitsOnlyKeydown"
                />
                <label for="no_rekening" class="floating-label">
                  No. Rekening/VA<span class="text-red-500">*</span>
                </label>
                <div v-if="errors.no_rekening" class="text-red-500 text-xs mt-1">
                  {{ errors.no_rekening }}
                </div>
              </div>
              <div
                v-else-if="form.metode_pembayaran === 'Cek/Giro'"
                class="floating-input"
              >
                <label class="block text-xs font-light text-gray-700 mb-1"
                  >Tanggal Cair<span class="text-red-500">*</span></label
                >
                <Datepicker
                  v-model="validTanggalCair"
                  :input-class="[
                    'floating-input-field',
                    validTanggalCair ? 'filled' : '',
                    errors.tanggal_cair ? 'border-red-500' : '',
                  ]"
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
                  id="tanggal_cair"
                />
                <div v-if="errors.tanggal_cair" class="text-red-500 text-xs mt-1">
                  {{ errors.tanggal_cair }}
                </div>
              </div>
            </div>

            <!-- Row 6: Perihal | Note -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <CustomSelect
                  :model-value="form.perihal_id ?? ''"
                  @update:modelValue="(val) => (form.perihal_id = val as any)"
                  :options="perihalList.map((p: any) => ({ label: p.nama, value: String(p.id) }))"
                  placeholder="Pilih Perihal"
                  :class="{ 'border-red-500': errors.perihal_id }"
                >
                  <template #label> Perihal<span class="text-red-500">*</span> </template>
                  <template #suffix>
                    <span
                      class="inline-flex items-center justify-center w-6 h-6 rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none"
                      @click.stop="showAddPerihalModal = true"
                      title="Tambah Perihal"
                      role="button"
                      tabindex="0"
                    >
                      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-4 h-4">
                        <path fill-rule="evenodd" d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z" clip-rule="evenodd" />
                      </svg>
                    </span>
                  </template>
                </CustomSelect>
                <div v-if="errors.perihal_id" class="text-red-500 text-xs mt-1">
                  {{ errors.perihal_id }}
                </div>
              </div>
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

            <!-- Row 7: No Invoice (single column) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <input
                  type="text"
                  v-model="form.no_invoice"
                  id="no_invoice"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.no_invoice }"
                  placeholder=" "
                />
                <label for="no_invoice" class="floating-label"> No. Invoice </label>
                <div v-if="errors.no_invoice" class="text-red-500 text-xs mt-1">
                  {{ errors.no_invoice }}
                </div>
              </div>
            </div>

            <!-- Row 8: Harga (single column) -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="floating-input">
                <input
                  type="text"
                  v-model="displayHarga"
                  id="harga"
                  class="floating-input-field"
                  :class="{ 'border-red-500': errors.harga }"
                  placeholder=" "
                  required
                  readonly
                />
                <label for="harga" class="floating-label">
                  Harga<span class="text-red-500">*</span>
                </label>
                <div v-if="errors.harga" class="text-red-500 text-xs mt-1">
                  {{ errors.harga }}
                </div>
              </div>
            </div>

            <!-- Row 9: Detail Keperluan (single column) -->
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
          </div>

          <!-- Khusus Staff Toko: Upload Dokumen Draft Invoice (Hanya untuk Tipe Reguler) -->
          <div
            v-if="isStaffToko && form.tipe_po === 'Reguler'"
            class="grid grid-cols-1 gap-6"
          >
            <FileUpload
              v-model="dokumenFile"
              label="Draft Invoice"
              :required="false"
              accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png"
              :max-size="50 * 1024 * 1024"
              drag-text="Bawa berkas ke area ini (maks. 50 MB)"
              @error="(message) => addError(message)"
            />
            <div class="text-sm text-gray-600">
              <p v-if="purchaseOrder.dokumen">
                Dokumen saat ini:
                <a
                  :href="'/storage/' + purchaseOrder.dokumen"
                  target="_blank"
                  class="text-blue-600 hover:underline"
                  >{{ purchaseOrder.dokumen.split("/").pop() }}</a
                >
              </p>
              <p v-else>Belum ada dokumen yang diupload</p>
            </div>
            <div v-if="errors.dokumen" class="text-red-500 text-xs mt-1">
              {{ errors.dokumen }}
            </div>
          </div>

          <hr class="my-6" />
        </form>

        <!-- Grid/List Barang - Outside the form to prevent submission conflicts -->
        <PurchaseOrderBarangGrid
          ref="barangGridRef"
          v-model:items="barangList"
          v-model:diskon="form.diskon"
          v-model:ppn="form.ppn"
          v-model:pph="form.pph_id"
          :pphList="pphList"
          @add-pph="onAddPph"
          :nominal="isLainnya && form.nominal ? Number(form.nominal) : undefined"
        />
        <div v-if="errors.barang" class="text-red-500 text-xs mt-1">
          {{ errors.barang }}
        </div>

        <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
          <button
            type="button"
            class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
            @click="onSubmit"
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

        <PerihalQuickAddModal
          v-if="showAddPerihalModal"
          @close="showAddPerihalModal = false"
          @created="handlePerihalCreated"
        />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, watch, onMounted } from "vue";
import { router } from "@inertiajs/vue3";
import PurchaseOrderBarangGrid from "../../components/purchase-orders/PurchaseOrderBarangGrid.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import CustomSelect from "@/components/ui/CustomSelect.vue";
import FileUpload from "@/components/ui/FileUpload.vue";
import PerihalQuickAddModal from "@/components/perihals/PerihalQuickAddModal.vue";
import { CreditCard } from "lucide-vue-next";
import axios from "axios";
import AppLayout from "@/layouts/AppLayout.vue";
import Datepicker from "@vuepic/vue-datepicker";
import "@vuepic/vue-datepicker/dist/main.css";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { usePermissions } from "@/composables/usePermissions";
import { formatCurrency, parseCurrency } from "@/lib/currencyUtils";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Purchase Order", href: "/purchase-orders" },
  { label: "Edit" },
];

// Master data from props (provided by Inertia controller)
const props = defineProps<{
  purchaseOrder: any;
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
    id: pph.id, // Keep the ID for backend submission
    kode: pph.kode_pph,
    nama: pph.nama_pph,
    tarif: pph.tarif_pph ? pph.tarif_pph / 100 : 0, // Convert percentage to decimal
  }))
);

// Use permissions composable to detect user role
const { hasRole } = usePermissions();
const isStaffToko = computed(() => hasRole("Staff Toko") || hasRole("Admin"));

// Initialize form with existing PO data
const form = ref({
  tipe_po: props.purchaseOrder.tipe_po || "Reguler",
  tanggal: props.purchaseOrder.tanggal
    ? new Date(props.purchaseOrder.tanggal)
    : new Date(),
  department_id: props.purchaseOrder.department_id
    ? String(props.purchaseOrder.department_id)
    : "",
  perihal_id: props.purchaseOrder.perihal_id
    ? String(props.purchaseOrder.perihal_id)
    : "",
  no_po: props.purchaseOrder.no_po || "",
  no_invoice: props.purchaseOrder.no_invoice || "",
  harga: props.purchaseOrder.harga || (null as any),
  detail_keperluan: props.purchaseOrder.detail_keperluan || "",
  metode_pembayaran: props.purchaseOrder.metode_pembayaran || "",
  bank_id: props.purchaseOrder.bank_id ? String(props.purchaseOrder.bank_id) : "",
  nama_rekening: props.purchaseOrder.nama_rekening || "",
  no_rekening: props.purchaseOrder.no_rekening || "",
  no_kartu_kredit: props.purchaseOrder.no_kartu_kredit || "",
  note: props.purchaseOrder.note || props.purchaseOrder.keterangan || "",
  no_giro: props.purchaseOrder.no_giro || "",
  tanggal_giro: props.purchaseOrder.tanggal_giro || "",
  tanggal_cair: props.purchaseOrder.tanggal_cair || "",
  diskon: props.purchaseOrder.diskon || (null as any),
  ppn: props.purchaseOrder.ppn || false,
  pph_id: props.purchaseOrder.pph_id ? [props.purchaseOrder.pph_id] : ([] as any[]),
  cicilan: props.purchaseOrder.cicilan || (null as any),
  termin: props.purchaseOrder.termin || (null as any),
  nominal: props.purchaseOrder.nominal || (null as any),
  keterangan: props.purchaseOrder.keterangan || "",
});

// Reactive preview number for edit (when no_po not assigned yet)
const previewNumber = ref<string | null>(null);

async function getPreviewNumberFromBackend() {
  if (!form.value.department_id || !form.value.tipe_po) return null as any;
  try {
    const response = await axios.post('/purchase-orders/preview-number', {
      tipe_po: form.value.tipe_po,
      department_id: form.value.department_id,
    });
    return response.data.preview_number as string;
  } catch (e) {
    console.error('Error getting preview number:', e);
    return null as any;
  }
}

// Initialize barang list with existing items
const barangList = ref<any[]>(Array.isArray(props.purchaseOrder.items)
  ? props.purchaseOrder.items.map((item: any) => ({
      nama: (item && (item.nama ?? item.nama_barang)) ?? "",
      qty: Number(item?.qty ?? 1),
      satuan: item?.satuan ?? "",
      harga: Number(item?.harga ?? 0),
    }))
  : []);

const loading = ref(false);
const dokumenFile = ref<File | null>(null);
const errors = ref<{ [key: string]: string }>({});
const barangGridRef = ref();
const showAddPerihalModal = ref(false);

// Message panel
const { addSuccess, addError, clearAll } = useMessagePanel();

const isLainnya = computed(() => form.value.tipe_po === "Lainnya");

// Keep preview number updated when changing department/tipe (only when no_po not set)
watch([
  () => form.value.department_id,
  () => form.value.tipe_po,
], async () => {
  if (form.value.no_po) {
    previewNumber.value = null as any;
    return;
  }
  if (form.value.department_id && form.value.tipe_po) {
    previewNumber.value = await getPreviewNumberFromBackend();
  } else {
    previewNumber.value = null as any;
  }
});

// Keep department selection when switching tipe_po, including to 'Lainnya'
watch(() => form.value.tipe_po, () => {
  // Intentionally do not clear department_id so Department can be set on 'Lainnya'
});

// Auto-select department when only one available
if (!form.value.department_id && (departemenList.value || []).length === 1) {
  form.value.department_id = String(departemenList.value[0].id);
}

// Date wrappers to emulate Bank Masuk behavior
const validTanggal = computed({
  get: () => {
    if (!form.value.tanggal) return null as any;
    try {
      const date = new Date(form.value.tanggal as any);
      return isNaN(date.getTime()) ? null : (date as any);
    } catch {
      return null as any;
    }
  },
  set: (value) => {
    (form.value as any).tanggal = value as any;
  },
});

const validTanggalGiro = computed({
  get: () => {
    if (!form.value.tanggal_giro) return null as any;
    try {
      const date = new Date(form.value.tanggal_giro as any);
      return isNaN(date.getTime()) ? null : (date as any);
    } catch {
      return null as any;
    }
  },
  set: (value) => {
    (form.value as any).tanggal_giro = value as any;
  },
});

const validTanggalCair = computed({
  get: () => {
    if (!form.value.tanggal_cair) return null as any;
    try {
      const date = new Date(form.value.tanggal_cair as any);
      return isNaN(date.getTime()) ? null : (date as any);
    } catch {
      return null as any;
    }
  },
  set: (value) => {
    (form.value as any).tanggal_cair = value as any;
  },
});


// Formatted numeric inputs (thousand separators + decimals, no currency symbol)
const displayHarga = computed<string>({
  get: () => formatCurrency(form.value.harga ?? ""),
  set: (val: string) => {
    const parsed = parseCurrency(val);
    form.value.harga = parsed === "" ? null : Number(parsed);
  },
});

function allowDigitsOnlyKeydown(event: KeyboardEvent) {
  const allowedKeys = [
    "Backspace",
    "Delete",
    "Tab",
    "Enter",
    "Escape",
    "ArrowLeft",
    "ArrowRight",
    "Home",
    "End",
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
  ];
  const isCtrlCombo = event.ctrlKey || event.metaKey;
  if (isCtrlCombo) return;
  if (!allowedKeys.includes(event.key)) {
    event.preventDefault();
  }
}

// Auto-calculate Harga from grid items summary
const calculatedHarga = computed<number>(() => {
  const subtotal = (barangList.value || []).reduce((sum, i) => {
    const qty = Number(i?.qty || 0);
    const harga = Number(i?.harga || 0);
    return sum + qty * harga;
  }, 0);
  const diskonVal = Number(form.value.diskon || 0);
  const dpp = Math.max(subtotal - diskonVal, 0);
  const ppnNominal = form.value.ppn ? dpp * 0.11 : 0;
  let pphNominal = 0;
  const selected = (form.value.pph_id || [])[0];
  if (selected != null) {
    const found = pphList.value.find(
      (p: any) => p.id === selected || p.kode === selected
    );
    if (found && typeof found.tarif === "number") {
      pphNominal = dpp * found.tarif;
    }
  }
  return dpp + ppnNominal + pphNominal;
});

// Keep form.harga in sync with calculatedHarga
watch(
  calculatedHarga,
  (val) => {
    form.value.harga = isNaN(Number(val)) ? (null as any) : Number(val);
  },
  { immediate: true }
);

function onAddPph(pphBaru: any) {
  // Transform the new PPH data to match the expected format
  const transformedPph = {
    id: pphBaru.id || pphBaru.kode, // Use kode as fallback if id is not available
    kode: pphBaru.kode,
    nama: pphBaru.nama,
    tarif: pphBaru.tarif,
  };
  pphList.value.push(transformedPph);
}

function goBack() {
  router.visit("/purchase-orders");
}

function validateForm() {
  errors.value = {};
  let isValid = true;


  if (form.value.tipe_po === "Reguler") {
    // Validasi field wajib untuk tipe Reguler
    if (!form.value.department_id) {
      errors.value.department_id = "Departemen wajib dipilih";
      isValid = false;
    }
    if (!form.value.perihal_id) {
      errors.value.perihal_id = "Perihal wajib dipilih";
      isValid = false;
    }
    // No Invoice is optional
    if (!form.value.harga) {
      errors.value.harga = "Harga wajib diisi";
      isValid = false;
    }
    if (!form.value.metode_pembayaran) {
      errors.value.metode_pembayaran = "Metode pembayaran wajib dipilih";
      isValid = false;
    }

    // Validasi field berdasarkan metode pembayaran
    if (form.value.metode_pembayaran === "Cek/Giro") {
      if (!form.value.no_giro) {
        errors.value.no_giro = "No. Cek/Giro wajib diisi";
        isValid = false;
      }
      if (!form.value.tanggal_giro) {
        errors.value.tanggal_giro = "Tanggal Giro wajib diisi";
        isValid = false;
      }
      if (!form.value.tanggal_cair) {
        errors.value.tanggal_cair = "Tanggal Cair wajib diisi";
        isValid = false;
      }
    } else if (
      form.value.metode_pembayaran === "Transfer" ||
      !form.value.metode_pembayaran
    ) {
      // Validasi field bank untuk Transfer atau ketika belum memilih metode pembayaran
      if (!form.value.bank_id) {
        errors.value.bank_id = "Nama Bank wajib dipilih";
        isValid = false;
      }
      if (!form.value.nama_rekening) {
        errors.value.nama_rekening = "Nama Rekening wajib diisi";
        isValid = false;
      }
      if (!form.value.no_rekening) {
        errors.value.no_rekening = "No. Rekening/VA wajib diisi";
        isValid = false;
      }
    } else if (form.value.metode_pembayaran === "Kredit") {
      if (!form.value.no_kartu_kredit) {
        errors.value.no_kartu_kredit = "No. Kartu Kredit wajib diisi";
        isValid = false;
      }
    }
  } else if (form.value.tipe_po === "Lainnya") {
    if (!form.value.department_id) {
      errors.value.department_id = "Departemen wajib dipilih";
      isValid = false;
    }
    if (!form.value.perihal_id) {
      errors.value.perihal_id = "Perihal wajib dipilih";
      isValid = false;
    }
    if (!form.value.nominal) {
      errors.value.nominal = "Nominal wajib diisi";
      isValid = false;
    }
  }

  if (!barangList.value.length) {
    errors.value.barang = "Minimal 1 barang harus diisi";
    isValid = false;
  }
  return isValid;
}

async function onSaveDraft() {
  clearAll();
  if (!validateForm()) return;
  loading.value = true;
  try {
    const formData = new FormData();
    const fieldsToFormat = ["tanggal_giro", "tanggal_cair"];

    // Only submit fields that have values or are required
    const fieldsToSubmit: any = {
      tipe_po: form.value.tipe_po,
      department_id: form.value.department_id,
      perihal_id: form.value.perihal_id,
      no_invoice: form.value.no_invoice,
      harga: form.value.harga,
      detail_keperluan: form.value.detail_keperluan,
      metode_pembayaran: form.value.metode_pembayaran,
      keterangan: form.value.note || form.value.keterangan, // Map note to keterangan
      diskon: form.value.diskon,
      ppn: form.value.ppn,
      pph_id: form.value.pph_id,
      cicilan: form.value.cicilan,
      termin: form.value.termin,
      nominal: form.value.nominal,
    };

    // Add bank-related fields if Transfer or no method selected
    if (form.value.metode_pembayaran === "Transfer" || !form.value.metode_pembayaran) {
      fieldsToSubmit.bank_id = form.value.bank_id;
      fieldsToSubmit.nama_rekening = form.value.nama_rekening;
      fieldsToSubmit.no_rekening = form.value.no_rekening;
    }

    // Add Cek/Giro fields if Cek/Giro method selected
    if (form.value.metode_pembayaran === "Cek/Giro") {
      fieldsToSubmit.no_giro = form.value.no_giro;
      fieldsToSubmit.tanggal_giro = form.value.tanggal_giro;
      fieldsToSubmit.tanggal_cair = form.value.tanggal_cair;
    }

    // Add Kredit fields if Kredit method selected
    if (form.value.metode_pembayaran === "Kredit") {
      fieldsToSubmit.no_kartu_kredit = form.value.no_kartu_kredit;
    }

    Object.entries(fieldsToSubmit).forEach(([k, v]) => {
      let value: any = v as any;
      if (fieldsToFormat.includes(k)) {
        value = formatDateForSubmit(value);
      }

      // Handle special field types
      if (k === "ppn") {
        // Convert boolean to 1 or 0 for server
        value = value ? 1 : 0;
      } else if (k === "pph_id") {
        // Handle PPH ID - extract the ID from the array or use the value directly
        if (Array.isArray(value) && value.length > 0) {
          // Extract just the ID from the array
          const pphId = value[0];
          if (pphId) {
            value = pphId; // Send just the ID value
          } else {
            value = null;
          }
        } else {
          value = null; // Don't send empty array
        }
      }

      if (value !== null && value !== undefined && value !== "") {
        formData.append(k, value);
      }
    });

    formData.append("status", "Draft");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);

    formData.append("_method", "PUT");
    await axios.post(`/purchase-orders/${props.purchaseOrder.id}`, formData, {
      headers: { "Content-Type": "multipart/form-data" },
    });
    addSuccess("Draft PO berhasil disimpan!");
    setTimeout(() => router.visit("/purchase-orders"), 1000);
  } catch (e: any) {
    if (e?.response?.data?.errors) {
      errors.value = e.response.data.errors;
    } else {
      addError(e?.response?.data?.message || "Gagal simpan draft.");
    }
  } finally {
    loading.value = false;
  }
}

async function onSubmit() {
  clearAll();
  if (!validateForm()) return;
  loading.value = true;
  try {
    const formData = new FormData();
    const fieldsToFormat = ["tanggal_giro", "tanggal_cair"];

    // Only submit fields that have values or are required
    const fieldsToSubmit: any = {
      tipe_po: form.value.tipe_po,
      department_id: form.value.department_id,
      perihal_id: form.value.perihal_id,
      no_invoice: form.value.no_invoice,
      harga: form.value.harga,
      detail_keperluan: form.value.detail_keperluan,
      metode_pembayaran: form.value.metode_pembayaran,
      keterangan: form.value.note || form.value.keterangan, // Map note to keterangan
      diskon: form.value.diskon,
      ppn: form.value.ppn,
      pph_id: form.value.pph_id,
      cicilan: form.value.cicilan,
      termin: form.value.termin,
      nominal: form.value.nominal,
    };

    // Add bank-related fields if Transfer or no method selected
    if (form.value.metode_pembayaran === "Transfer" || !form.value.metode_pembayaran) {
      fieldsToSubmit.bank_id = form.value.bank_id;
      fieldsToSubmit.nama_rekening = form.value.nama_rekening;
      fieldsToSubmit.no_rekening = form.value.no_rekening;
    }

    // Add Cek/Giro fields if Cek/Giro method selected
    if (form.value.metode_pembayaran === "Cek/Giro") {
      fieldsToSubmit.no_giro = form.value.no_giro;
      fieldsToSubmit.tanggal_giro = form.value.tanggal_giro;
      fieldsToSubmit.tanggal_cair = form.value.tanggal_cair;
    }

    // Add Kredit fields if Kredit method selected
    if (form.value.metode_pembayaran === "Kredit") {
      fieldsToSubmit.no_kartu_kredit = form.value.no_kartu_kredit;
    }

    Object.entries(fieldsToSubmit).forEach(([k, v]) => {
      let value: any = v as any;
      if (fieldsToFormat.includes(k)) {
        value = formatDateForSubmit(value);
      }

      // Handle special field types
      if (k === "ppn") {
        // Convert boolean to 1 or 0 for server
        value = value ? 1 : 0;
      } else if (k === "pph_id") {
        // Handle PPH ID - extract the ID from the array or use the value directly
        if (Array.isArray(value) && value.length > 0) {
          // Extract just the ID from the array
          const pphId = value[0];
          if (pphId) {
            value = pphId; // Send just the ID value
          } else {
            value = null;
          }
        } else {
          value = null; // Don't send empty array
        }
      }

      if (value !== null && value !== undefined && value !== "") {
        formData.append(k, value);
      }
    });

    // If Kredit, set status to Approved immediately so backend will approve
    const isKredit = form.value.metode_pembayaran === "Kredit";
    formData.append("status", isKredit ? "Approved" : "Draft");
    formData.append("barang", JSON.stringify(barangList.value));
    if (dokumenFile.value) formData.append("dokumen", dokumenFile.value);

    formData.append("_method", "PUT");
    await axios.post(
      `/purchase-orders/${props.purchaseOrder.id}`,
      formData,
      {
        headers: { "Content-Type": "multipart/form-data" },
      }
    );

    addSuccess("PO berhasil dikirim!");
    setTimeout(() => router.visit("/purchase-orders"), 800);
  } catch (e: any) {
    if (e?.response?.data?.errors) {
      errors.value = e.response.data.errors;
    } else {
      addError(e?.response?.data?.message || "Gagal update PO.");
    }
  } finally {
    loading.value = false;
  }
}

function formatDateForSubmit(value: any) {
  if (!value) return "";
  const date = value instanceof Date ? value : new Date(value);
  if (isNaN(date.getTime())) return "";
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, "0");
  const day = String(date.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

onMounted(() => {
  // Initialize calculated harga based on existing items
  if (barangList.value.length > 0) {
    // Trigger recalculation
    const subtotal = barangList.value.reduce((sum, item) => {
      return sum + Number(item.qty || 0) * Number(item.harga || 0);
    }, 0);

    // Set initial harga if not already set
    if (!form.value.harga && subtotal > 0) {
      form.value.harga = subtotal;
    }
  }

  // Initialize preview number if the PO doesn't have a number yet
  if (!form.value.no_po && form.value.department_id && form.value.tipe_po) {
    getPreviewNumberFromBackend().then((num) => (previewNumber.value = num as any));
  }
});

function handlePerihalCreated(newItem: any) {
  if (newItem && newItem.id) {
    perihalList.value.push({ id: newItem.id, nama: newItem.nama, status: newItem.status });
    form.value.perihal_id = String(newItem.id);
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

/* Disabled field styling */
.floating-input-field:disabled {
  background-color: #f3f4f6;
  color: #6b7280;
  cursor: not-allowed;
  opacity: 0.7;
}

.floating-input-field:disabled ~ .floating-label {
  color: #9ca3af;
}
</style>

