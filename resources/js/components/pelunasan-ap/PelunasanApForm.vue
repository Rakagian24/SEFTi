<template>
  <!-- Card utama: form + info referensi -->
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="pelunasan-form-container">
      <form @submit.prevent="handleSubmit" novalidate class="space-y-4 pelunasan-form-left">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
          <div class="lg:col-span-2 space-y-4">
            <!-- No. PL -->
            <div class="floating-input">
              <input
                type="text"
                v-model="form.no_pl"
                id="no_pl"
                class="floating-input-field"
                placeholder=" "
                readonly
              />
              <label for="no_pl" class="floating-label">No. PL</label>
            </div>

            <!-- Tanggal -->
            <div class="floating-input">
              <input
                type="text"
                :value="tanggalDisplay"
                id="tanggal"
                class="floating-input-field"
                placeholder=" "
                readonly
              />
              <label for="tanggal" class="floating-label">Tanggal</label>
            </div>

            <!-- Tipe Pelunasan -->
            <div>
              <CustomSelect
                :model-value="form.tipe_pelunasan ?? 'Bank Keluar'"
                @update:modelValue="(val) => (form.tipe_pelunasan = val)"
                :options="[
                  { label: 'Bank Keluar', value: 'Bank Keluar' },
                  { label: 'Mutasi', value: 'Mutasi' },
                  { label: 'Retur', value: 'Retur' },
                ]"
                placeholder="Pilih Tipe"
              >
                <template #label> Tipe Pelunasan<span class="text-red-500">*</span> </template>
              </CustomSelect>
              <div v-if="errors.tipe_pelunasan" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
            </div>

            <!-- Referensi Dokumen -->
            <div>
              <div class="mb-1 text-sm font-medium text-gray-700">
                Referensi Dokumen<span class="text-red-500">*</span>
              </div>
              <div class="flex gap-2">
                <input
                  v-model="selectedRefDoc"
                  type="text"
                  placeholder="Pilih dokumen referensi"
                  class="flex-1 px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-sm"
                  readonly
                />
                <button
                  type="button"
                  @click="openRefDocModal"
                  class="px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
                >
                  <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                  </svg>
                </button>
              </div>
              <div v-if="errors.bank_keluar_id" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
            </div>

            <!-- Supplier -->
            <div class="floating-input">
              <input
                type="text"
                v-model="selectedSupplier"
                id="supplier"
                class="floating-input-field"
                placeholder=" "
                readonly
              />
              <label for="supplier" class="floating-label">Supplier</label>
            </div>

            <!-- Nilai Dokumen Referensi -->
            <div class="floating-input">
              <input
                type="text"
                :value="formatCurrency(form.nilai_dokumen_referensi)"
                id="nilai_dokumen"
                class="floating-input-field"
                placeholder=" "
                readonly
              />
              <label for="nilai_dokumen" class="floating-label">Nilai Dokumen Referensi</label>
            </div>

            <!-- Keterangan -->
            <div class="floating-input">
              <textarea
                v-model="form.keterangan"
                id="keterangan"
                class="floating-input-field"
                placeholder=" "
                rows="3"
              ></textarea>
              <label for="keterangan" class="floating-label">Keterangan</label>
            </div>
          </div>
        </div>
      </form>

      <!-- Right column: Reference Document Info -->
      <div class="pelunasan-form-right">
        <ReferenceDocumentInfo :reference-doc="selectedRefDocData" />
      </div>
    </div>
  </div>

  <!-- Card terpisah: Payment Vouchers (grid) -->
  <div class="bg-white rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Daftar Payment Voucher</h3>
      <button
        type="button"
        @click="openPVModal"
        class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah
      </button>
    </div>

    <PelunasanApDokumenGrid
      :items="form.items as any"
      :total-p-v="totalPV"
      :total-alokasi="totalAlokasi"
      :total-sisa="totalSisa"
      :grand-total="grandTotal"
      :pembulatan-minus="form.pembulatan_minus"
      :pembulatan-plus="form.pembulatan_plus"
      @change-nilai-pelunasan="onChangeNilaiPelunasan"
      @remove="removeItem"
      @update:pembulatanMinus="(v: number) => (form.pembulatan_minus = v)"
      @update:pembulatanPlus="(v: number) => (form.pembulatan_plus = v)"
    />
  </div>

  <!-- Action Buttons -->
  <div class="flex justify-start gap-3 pt-6 border-t border-gray-200">
    <button
      type="button"
      class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] border border-transparent rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
      @click="send"
    >
      <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="w-5 h-5">
        <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z" />
      </svg>
      Kirim
    </button>
    <button
      type="button"
      class="px-6 py-2 text-sm font-medium text-white bg-blue-300 border border-transparent rounded-md hover:bg-blue-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
      @click="saveDraft"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
      </svg>
      Simpan Draft
    </button>
    <button
      type="button"
      class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors flex items-center gap-2"
      @click="goBack"
    >
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
      </svg>
      Batal
    </button>
  </div>

  <!-- Reference Document Modal -->
  <RefDocModal
    v-if="showRefDocModal"
    :tipe-pelunasan="form.tipe_pelunasan"
    @select="selectRefDoc"
    @close="showRefDocModal = false"
  />

  <!-- Payment Voucher Modal -->
  <PVSelectionModal
    v-if="showPVModal"
    :supplier-id="form.supplier_id"
    @select="addPaymentVouchers"
    @close="showPVModal = false"
  />
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { router } from '@inertiajs/vue3'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import RefDocModal from '@/components/pelunasan-ap/RefDocModal.vue'
import PVSelectionModal from '@/components/pelunasan-ap/PVSelectionModal.vue'
import PelunasanApDokumenGrid from '@/components/pelunasan-ap/PelunasanApDokumenGrid.vue'
// import ReferenceDocumentInfo from '@/components/pelunasan-ap/ReferenceDocumentInfo.vue'

function getLocalDateString() {
  const d = new Date()
  const year = d.getFullYear()
  const month = String(d.getMonth() + 1).padStart(2, '0')
  const day = String(d.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const props = defineProps<{
  mode: 'create' | 'edit'
  pelunasanAp?: any
}>()

const form = ref({
  no_pl: props.pelunasanAp?.no_pl || '',
  tanggal: props.pelunasanAp?.tanggal || getLocalDateString(),
  tipe_pelunasan: props.pelunasanAp?.tipe_pelunasan || 'Bank Keluar',
  bank_keluar_id: props.pelunasanAp?.bank_keluar_id || null,
  bank_mutasi_id: props.pelunasanAp?.bank_mutasi_id || null,
  supplier_id: props.pelunasanAp?.supplier_id || null,
  nilai_dokumen_referensi: props.pelunasanAp?.nilai_dokumen_referensi || 0,
  keterangan: props.pelunasanAp?.keterangan || '',
  items: props.pelunasanAp?.items || [],
  pembulatan_minus: props.pelunasanAp?.pembulatan_minus || 0,
  pembulatan_plus: props.pelunasanAp?.pembulatan_plus || 0,
})

const errors = ref<Record<string, string>>({})

const tanggalDisplay = computed(() => {
  try {
    return new Date(form.value.tanggal || new Date().toISOString().slice(0, 10)).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return ''
  }
})

const showRefDocModal = ref(false)
const showPVModal = ref(false)
const selectedRefDoc = ref(props.pelunasanAp?.bank_keluar?.no_bk || '')
const selectedSupplier = ref(props.pelunasanAp?.supplier?.nama || '')
const selectedRefDocData = ref(props.pelunasanAp?.bank_keluar || null)

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0)
}

const openRefDocModal = () => {
  showRefDocModal.value = true
}

const selectRefDoc = (doc: any) => {
  form.value.bank_keluar_id = doc.id
  selectedRefDoc.value = doc.no_bk
  selectedSupplier.value = doc.supplier?.nama || ''
  form.value.supplier_id = doc.supplier_id
  form.value.nilai_dokumen_referensi = doc.nilai
  selectedRefDocData.value = doc
  showRefDocModal.value = false
}

const openPVModal = () => {
  if (!form.value.supplier_id) {
    alert('Pilih dokumen referensi terlebih dahulu')
    return
  }
  showPVModal.value = true
}

const addPaymentVouchers = (pvs: any[]) => {
  pvs.forEach((pv: any) => {
    if (!form.value.items.find((item: any) => item.payment_voucher_id === pv.id)) {
      form.value.items.push({
        payment_voucher_id: pv.id,
        payment_voucher: pv,
        pv: pv,
        nilai_pv: pv.nominal,
        outstanding: pv.nominal,
        nilai_pelunasan: 0,
        sisa: pv.nominal,
      })
    }
  })
  showPVModal.value = false
}

const removeItem = (idx: number) => {
  form.value.items.splice(idx, 1)
}

const onChangeNilaiPelunasan = (idx: number, value: number) => {
  const item = form.value.items[idx]
  item.nilai_pelunasan = value
  item.sisa = (item.outstanding || 0) - (item.nilai_pelunasan || 0)
}

const totalPV = computed(() => {
  return form.value.items.reduce((sum: number, item: any) => sum + (item.nilai_pv || 0), 0)
})

const totalAlokasi = computed(() => {
  return form.value.items.reduce((sum: number, item: any) => sum + (item.nilai_pelunasan || 0), 0)
})

const totalSisa = computed(() => {
  return form.value.items.reduce((sum: number, item: any) => sum + (item.sisa || 0), 0)
})

const grandTotal = computed(() => {
  return totalAlokasi.value - (form.value.pembulatan_minus || 0) + (form.value.pembulatan_plus || 0)
})

const goBack = () => {
  history.back()
}

const saveDraft = () => {
  errors.value = {}

  if (props.mode === 'create') {
    router.post('/pelunasan-ap', { ...form.value, submit_type: 'draft' })
  } else {
    router.put(`/pelunasan-ap/${props.pelunasanAp?.id}`, { ...form.value })
  }
}

const send = () => {
  errors.value = {}

  const hasRefDoc = !!form.value.bank_keluar_id
  const hasSupplier = !!form.value.supplier_id
  const hasTipe = !!form.value.tipe_pelunasan

  if (!hasTipe) errors.value.tipe_pelunasan = 'required'
  if (!hasRefDoc) errors.value.bank_keluar_id = 'required'
  if (!hasSupplier) errors.value.supplier_id = 'required'

  if (Object.keys(errors.value).length > 0) {
    return
  }

  const ok = window.confirm('Apakah Anda yakin ingin mengirim Pelunasan AP ini?')
  if (!ok) return

  if (props.mode === 'create') {
    router.post('/pelunasan-ap', { ...form.value, submit_type: 'send' })
  } else {
    router.put(`/pelunasan-ap/${props.pelunasanAp?.id}`, { ...form.value }, {
      onSuccess: () => router.post('/pelunasan-ap/send', { ids: [props.pelunasanAp?.id] })
    })
  }
}

const handleSubmit = () => {
  saveDraft()
}
</script>

<style scoped>
.pelunasan-form-container {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 2rem;
}

.pelunasan-form-left {
  width: 100%;
}

.pelunasan-form-right {
  width: 100%;
}

@media (max-width: 1024px) {
  .pelunasan-form-container {
    grid-template-columns: 1fr;
  }
}

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
</style>
