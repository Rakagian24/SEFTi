<template>
  <MessagePanel
    :messages="messages"
    position="top-right"
    @close="removeMessage"
    @clear="clearAll"
  />

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
              <div class="flex flex-wrap gap-4 text-sm">
                <label class="inline-flex items-center gap-2">
                  <input
                    type="radio"
                    value="Bank Keluar"
                    v-model="form.tipe_pelunasan"
                    class="text-[#101010] focus:ring-[#101010] border-gray-300"
                  />
                  <span>Bank Keluar</span>
                </label>
                <label class="inline-flex items-center gap-2">
                  <input
                    type="radio"
                    value="Retur"
                    v-model="form.tipe_pelunasan"
                    class="text-[#101010] focus:ring-[#101010] border-gray-300"
                  />
                  <span>Retur</span>
                </label>
              </div>
              <div v-if="errors.tipe_pelunasan" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
            </div>

            <div class="floating-input" v-if="departments && departments.length">
              <CustomSelect
                :model-value="form.department_id ?? ''"
                @update:modelValue="(val: any) => (form.department_id = val)"
                :options="(departments || []).map((d: any) => ({ label: d.nama || d.name, value: d.id }))"
                placeholder="Pilih Department"
              >
                <template #label>
                  Department<span class="text-red-500">*</span>
                </template>
              </CustomSelect>
              <div v-if="errors.department_id" class="text-red-500 text-xs mt-1">
                Field ini wajib di isi
              </div>
            </div>

            <!-- Referensi Dokumen -->
            <div class="floating-input">
              <div class="flex gap-2">
                <div class="flex-1">
                  <CustomSelect
                    :model-value="form.bank_keluar_id ?? ''"
                    @update:modelValue="onChangeBankKeluar"
                    :options="bankKeluarOptions.map((bk: any) => ({ label: bk.no_bk, value: bk.id }))"
                    placeholder="Pilih Referensi Dokumen"
                  >
                    <template #label>
                      Referensi Dokumen<span class="text-red-500">*</span>
                    </template>
                  </CustomSelect>
                </div>
                <button
                  type="button"
                  @click="openRefDocModal"
                  class="inline-flex items-center justify-center w-12 h-12 rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none transition-colors"
                  title="Pilih dari daftar dokumen referensi"
                >
                  <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="w-5 h-5">
                    <path fill-rule="evenodd" d="M12 4.5a.75.75 0 01.75.75v6h6a.75.75 0 010 1.5h-6v6a.75.75 0 01-1.5 0v-6h-6a.75.75 0 010-1.5h6v-6A.75.75 0 0112 4.5z" clip-rule="evenodd" />
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
    <PelunasanApDokumenGrid
      :items="form.items"
      :total-p-v="totalPV"
      :total-alokasi="totalAlokasi"
      :total-sisa="totalSisa"
      :grand-total="grandTotal"
      :pembulatan-minus="form.pembulatan_minus || 0"
      :pembulatan-plus="form.pembulatan_plus || 0"
      @remove="removeItem"
      @update:pembulatanMinus="(val: number) => (form.pembulatan_minus = val)"
      @update:pembulatanPlus="(val: number) => (form.pembulatan_plus = val)"
      @add="openPVModal"
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
    :department-id="form.department_id"
    @select="selectRefDoc"
    @close="showRefDocModal = false"
  />

  <!-- Payment Voucher Modal -->
  <PVSelectionModal
    v-if="showPVModal"
    :supplier-id="form.supplier_id"
    :tipe-pv="selectedBankKeluarTipe"
    @select="addPaymentVouchers"
    @close="showPVModal = false"
  />

  <ConfirmDialog
    :show="showConfirmDialog"
    message="Apakah Anda yakin ingin mengirim Pelunasan AP ini?"
    @confirm="confirmSend"
    @cancel="showConfirmDialog = false"
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import MessagePanel from '@/components/ui/MessagePanel.vue'
import { useMessagePanel } from '@/composables/useMessagePanel'
import axios from 'axios'
import CustomSelect from '@/components/ui/CustomSelect.vue'
import RefDocModal from '@/components/pelunasan-ap/RefDocModal.vue'
import PVSelectionModal from '@/components/pelunasan-ap/PVSelectionModal.vue'
import PelunasanApDokumenGrid from '@/components/pelunasan-ap/PelunasanApDokumenGrid.vue'
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue'
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
  departments?: any[]
}>()

const { messages, addSuccess, addError, removeMessage, clearAll } = useMessagePanel()

const form = ref({
  no_pl: props.pelunasanAp?.no_pl || '',
  tanggal: props.pelunasanAp?.tanggal || getLocalDateString(),
  tipe_pelunasan: props.pelunasanAp?.tipe_pelunasan || 'Bank Keluar',
  bank_keluar_id: props.pelunasanAp?.bank_keluar_id || null,
  bank_mutasi_id: props.pelunasanAp?.bank_mutasi_id || null,
  supplier_id: props.pelunasanAp?.supplier_id || null,
  department_id: props.pelunasanAp?.department_id || null,
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
const showConfirmDialog = ref(false)
const selectedRefDoc = ref(props.pelunasanAp?.bank_keluar?.no_bk || '')
const selectedSupplier = ref(props.pelunasanAp?.supplier?.nama_supplier || '')
const selectedRefDocData = ref(props.pelunasanAp?.bank_keluar || null)
const bankKeluarOptions = ref<any[]>([])
const selectedBankKeluarTipe = ref<string | null>((props.pelunasanAp as any)?.bank_keluar?.tipe_bk || null)

const formatCurrency = (value: number) => {
  const numeric = Number(value ?? 0)
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(numeric)
}

const fetchBankKeluar = async () => {
  try {
    const response = await axios.get(route('pelunasan-ap.bank-keluars.search'), {
      params: {
        tanggal_start: '',
        page: 1,
        department_id: form.value.department_id || undefined,
      },
    })
    bankKeluarOptions.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching bank keluar options:', error)
  }
}

onMounted(() => {
  fetchBankKeluar()
})

watch(
  () => form.value.department_id,
  async (newVal, oldVal) => {
    if (newVal === oldVal) return

    form.value.bank_keluar_id = null
    selectedRefDoc.value = ''
    selectedSupplier.value = ''
    form.value.supplier_id = null
    form.value.nilai_dokumen_referensi = 0
    form.value.items = []
    selectedBankKeluarTipe.value = null

    await fetchBankKeluar()
  }
)

const applySelectedBankKeluar = (doc: any) => {
  form.value.bank_keluar_id = doc.id
  selectedRefDoc.value = doc.no_bk
  selectedSupplier.value = doc.supplier?.nama_supplier || doc.supplier?.nama || ''
  form.value.supplier_id = doc.supplier_id
  form.value.nilai_dokumen_referensi = doc.nominal ?? doc.nilai ?? 0
  selectedBankKeluarTipe.value = doc.tipe_bk || null
  if (!form.value.department_id) {
    form.value.department_id = doc.department_id ?? doc.department?.id ?? null
  }
  selectedRefDocData.value = doc
}

const openRefDocModal = () => {
  showRefDocModal.value = true
}

const selectRefDoc = (doc: any) => {
  applySelectedBankKeluar(doc)
  showRefDocModal.value = false
}

const onChangeBankKeluar = (id: any) => {
  form.value.bank_keluar_id = id
  const doc = bankKeluarOptions.value.find((bk: any) => bk.id === id)
  if (doc) {
    applySelectedBankKeluar(doc)
  }
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

// const onChangeNilaiPelunasan = (idx: number, value: number) => {
//   const item = form.value.items[idx]
//   item.nilai_pelunasan = value
//   item.sisa = (item.outstanding || 0) - (item.nilai_pelunasan || 0)
// }

const totalPV = computed(() => {
  return form.value.items.reduce((sum: number, item: any) => {
    const nilai = Number(item.nilai_pv ?? 0)
    return sum + (isNaN(nilai) ? 0 : nilai)
  }, 0)
})

const totalAlokasi = computed(() => {
  return form.value.items.reduce((sum: number, item: any) => {
    const nilai = Number(item.nilai_pelunasan ?? 0)
    return sum + (isNaN(nilai) ? 0 : nilai)
  }, 0)
})

const totalSisa = computed(() => {
  return form.value.items.reduce((sum: number, item: any) => {
    const nilai = Number(item.sisa ?? 0)
    return sum + (isNaN(nilai) ? 0 : nilai)
  }, 0)
})

const grandTotal = computed(() => {
  return totalAlokasi.value - (form.value.pembulatan_minus || 0) + (form.value.pembulatan_plus || 0)
})

const goBack = () => {
  history.back()
}

const saveDraft = () => {
  errors.value = {}

  // Validasi: Grand Total tidak boleh melebihi Nilai Dokumen Referensi
  const maxRef = Number(form.value.nilai_dokumen_referensi || 0)
  const currentGrandTotal = Number(grandTotal.value || 0)
  if (maxRef > 0 && currentGrandTotal > maxRef) {
    addError('Grand Total tidak boleh melebihi Nilai Dokumen Referensi')
    return
  }

  if (props.mode === 'create') {
    router.post('/pelunasan-ap', { ...form.value, submit_type: 'draft' }, {
      onSuccess: () => {
        addSuccess('Draft Pelunasan AP berhasil disimpan')
      },
      onError: () => {
        addError('Gagal menyimpan draft Pelunasan AP')
      },
      preserveScroll: true,
    })
  } else {
    router.put(`/pelunasan-ap/${props.pelunasanAp?.id}`, { ...form.value }, {
      onSuccess: () => {
        addSuccess('Pelunasan AP draft berhasil diperbarui')
      },
      onError: () => {
        addError('Gagal memperbarui draft Pelunasan AP')
      },
      preserveScroll: true,
    })
  }
}

const send = () => {
  errors.value = {}

  const hasRefDoc = !!form.value.bank_keluar_id
  const hasSupplier = !!form.value.supplier_id
  const hasTipe = !!form.value.tipe_pelunasan
  const hasDepartment = !!form.value.department_id

  if (!hasTipe) errors.value.tipe_pelunasan = 'required'
  if (!hasRefDoc) errors.value.bank_keluar_id = 'required'
  if (!hasSupplier) errors.value.supplier_id = 'required'
  if (!hasDepartment) errors.value.department_id = 'required'

  if (Object.keys(errors.value).length > 0) {
    return
  }

  // Validasi: Grand Total tidak boleh melebihi Nilai Dokumen Referensi
  const maxRef = Number(form.value.nilai_dokumen_referensi || 0)
  const currentGrandTotal = Number(grandTotal.value || 0)
  if (maxRef > 0 && currentGrandTotal > maxRef) {
    addError('Grand Total tidak boleh melebihi Nilai Dokumen Referensi')
    return
  }

  // Tampilkan dialog konfirmasi kustom
  showConfirmDialog.value = true
}

const confirmSend = () => {
  showConfirmDialog.value = false

  if (props.mode === 'create') {
    router.post('/pelunasan-ap', { ...form.value, submit_type: 'send' }, {
      onSuccess: () => {
        addSuccess('Pelunasan AP berhasil dikirim')
      },
      onError: () => {
        addError('Gagal mengirim Pelunasan AP')
      },
      preserveScroll: true,
    })
  } else {
    router.put(`/pelunasan-ap/${props.pelunasanAp?.id}`, { ...form.value }, {
      onSuccess: () => {
        router.post('/pelunasan-ap/send', { ids: [props.pelunasanAp?.id] }, {
          onSuccess: () => {
            addSuccess('Pelunasan AP berhasil dikirim')
          },
          onError: () => {
            addError('Gagal mengirim Pelunasan AP')
          },
          preserveScroll: true,
        })
      },
      onError: () => {
        addError('Gagal memperbarui Pelunasan AP sebelum dikirim')
      },
      preserveScroll: true,
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
