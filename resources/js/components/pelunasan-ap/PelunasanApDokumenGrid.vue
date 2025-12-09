<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h3 class="text-lg font-semibold text-gray-900">Daftar Payment Voucher</h3>
      <button
        type="button"
        @click="$emit('add')"
        class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
      >
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
        </svg>
        Tambah
      </button>
    </div>

    <!-- PV Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200">
      <div class="overflow-x-auto">
        <table class="min-w-full">
          <thead>
            <tr class="bg-gray-50 border-b border-gray-200">
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Tanggal</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">No. PV</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Nilai PV</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Oustanding</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Alokasi</th>
              <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Sisa</th>
              <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50 transition-colors" :class="idx % 2 === 0 ? 'bg-white' : 'bg-gray-50'">
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatDate(item.pv?.tanggal || item.payment_voucher?.tanggal) }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ item.pv?.no_pv || item.payment_voucher?.no_pv }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatCurrency(item.nilai_pv) }}</td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatCurrency(item.outstanding) }}</td>
              <td class="px-6 py-4">
                <input
                  :value="formatAlokasiInput(localItems[idx]?.nilai_pelunasan)"
                  type="text"
                  @input="onAlokasiInput(idx, $event)"
                  @keydown="restrictToNumeric($event)"
                  class="w-full px-4 py-2 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                  placeholder="Rp."
                />
              </td>
              <td class="px-6 py-4 text-sm text-gray-600">{{ formatCurrency(item.sisa) }}</td>
              <td class="px-6 py-4 text-center">
                <button
                  type="button"
                  @click="$emit('remove', idx)"
                  class="text-red-500 hover:text-red-700 transition-colors"
                >
                  <Icon name="trash-2" class="w-5 h-5" />
                </button>
              </td>
            </tr>
            <tr v-if="!items || items.length === 0">
              <td colspan="7" class="px-6 py-12 text-center text-sm text-gray-500">
                Belum ada data payment voucher
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Summary Section -->
    <div class="flex flex-col lg:flex-row gap-6 items-stretch">
      <!-- Left Cards -->
      <div class="space-y-4 w-full lg:w-1/3">
        <div class="bg-gradient-to-br from-blue-600 to-blue-700 rounded-2xl p-6 text-white shadow-lg">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
              <TicketPercent name="file-text" class="w-5 h-5" />
            </div>
            <div>
              <p class="text-sm text-blue-100 mb-1">Total PV yang dipilih</p>
              <p class="text-3xl font-bold">{{ items?.length || 0 }}</p>
            </div>
          </div>
        </div>

        <div class="bg-gradient-to-br from-yellow-400 to-yellow-500 rounded-2xl p-6 text-gray-900 shadow-lg">
          <div class="flex items-start gap-3">
            <div class="w-10 h-10 bg-white/30 rounded-lg flex items-center justify-center">
              <CreditCard name="shopping-cart" class="w-5 h-5" />
            </div>
            <div>
              <p class="text-sm text-gray-700 mb-1">Total PO yang dipilih</p>
              <p class="text-3xl font-bold">{{ items?.length || 0 }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Summary Card -->
      <div class="w-full lg:flex-1 bg-white rounded-2xl shadow-sm border border-gray-200 p-4 lg:p-6 max-w-xl lg:ml-auto">
        <h3 class="text-lg font-semibold text-gray-900 mb-6">Ringkasan Alokasi</h3>

        <div class="space-y-4">
          <div class="flex justify-between items-center border-b border-gray-100">
            <span class="text-sm text-gray-600">Total Alokasi</span>
            <span class="text-base font-semibold text-gray-900">{{ formatCurrency(totalAlokasi) }}</span>
          </div>

          <div class="flex justify-between items-center border-b border-gray-100">
            <span class="text-sm text-gray-600">Total PV</span>
            <span class="text-base font-semibold text-gray-900">{{ formatCurrency(totalPV) }}</span>
          </div>

          <div class="flex justify-between items-center border-b border-gray-100">
            <span class="text-sm text-gray-600">Total Sisa PV</span>
            <span class="text-base font-semibold text-gray-900">{{ formatCurrency(totalSisa) }}</span>
          </div>

          <div class="flex justify-between items-center">
            <span class="text-sm text-gray-600">Pembulatan (-)</span>
            <input
              v-model.number="localPembulatanMinus"
              type="text"
              @keydown="restrictToNumeric($event)"
              class="w-40 px-4 py-2 text-sm text-right border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Rp."
            />
          </div>

          <div class="flex justify-between items-center border-b border-gray-200">
            <span class="text-sm text-gray-600">Pembulatan (+)</span>
            <input
              v-model.number="localPembulatanPlus"
              type="text"
              @keydown="restrictToNumeric($event)"
              class="w-40 px-4 py-2 text-sm text-right border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
              placeholder="Rp. xxxxx"
            />
          </div>

          <div class="flex justify-between items-center py-4 bg-gray-50 -mx-6 px-6 -mb-6 rounded-b-2xl">
            <span class="text-lg font-bold text-gray-900">Grand Total</span>
            <span class="text-xl font-bold text-gray-900">{{ formatCurrency(grandTotal) }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
import { formatCurrency as formatInputCurrency, parseCurrency } from '@/lib/currencyUtils'
import { CreditCard, TicketPercent } from 'lucide-vue-next'
import Icon from '@/components/Icon.vue'

interface Item {
  nilai_pv?: number
  outstanding?: number
  nilai_pelunasan?: number
  sisa?: number
  pv?: any
  payment_voucher?: any
}

const props = defineProps<{
  items: Item[]
  totalPV: number
  totalAlokasi: number
  totalSisa: number
  grandTotal: number
  pembulatanMinus: number
  pembulatanPlus: number
}>()

const emit = defineEmits<{
  (e: 'change-nilai-pelunasan', idx: number, value: number): void
  (e: 'remove', idx: number): void
  (e: 'update:pembulatanMinus', value: number): void
  (e: 'update:pembulatanPlus', value: number): void
  (e: 'add'): void
}>()

const localItems = ref<Item[]>([])
const localPembulatanMinus = ref(props.pembulatanMinus)
const localPembulatanPlus = ref(props.pembulatanPlus)

watch(
  () => props.items,
  (val) => {
    localItems.value = val ? [...val] : []
  },
  { immediate: true, deep: true },
)

watch(localPembulatanMinus, (val) => {
  emit('update:pembulatanMinus', val || 0)
})

watch(localPembulatanPlus, (val) => {
  emit('update:pembulatanPlus', val || 0)
})

const onChangeNilaiPelunasan = (idx: number) => {
  const rawValue = Number(localItems.value[idx]?.nilai_pelunasan || 0)

  // Ambil batas maksimum dari sisa terlebih dahulu, fallback ke outstanding
  const item = props.items[idx]
  const maxFromItem = Number(item?.sisa ?? item?.outstanding ?? 0)

  // Tidak boleh negatif dan tidak boleh melebihi sisa/outstanding
  let safeValue = isNaN(rawValue) ? 0 : rawValue
  if (safeValue < 0) safeValue = 0
  if (safeValue > maxFromItem) safeValue = maxFromItem

  // Sinkronkan kembali ke input lokal agar tampilan ikut terkoreksi (tanpa formatting tambahan)
  if (localItems.value[idx]) {
    localItems.value[idx].nilai_pelunasan = safeValue
  }

  emit('change-nilai-pelunasan', idx, safeValue)
}

const formatAlokasiInput = (value: number | undefined) => {
  if (value === undefined || value === null) return ''
  const numeric = Number(value)
  if (!isFinite(numeric)) return ''

  // Gunakan util global: ribuan koma, desimal titik, dan hanya tampilkan desimal jika ada
  return formatInputCurrency(numeric)
}

const onAlokasiInput = (idx: number, event: Event) => {
  const input = event.target as HTMLInputElement
  const raw = input.value

  if (!raw) {
    if (localItems.value[idx]) {
      localItems.value[idx].nilai_pelunasan = 0
    }
    onChangeNilaiPelunasan(idx)
    input.value = ''
    return
  }

  // Gunakan parseCurrency agar konsisten dengan field lain ("1,234.56" -> "1234.56")
  const cleaned = parseCurrency(raw)
  let numeric = Number(cleaned)
  if (isNaN(numeric)) {
    numeric = 0
  }

  if (localItems.value[idx]) {
    localItems.value[idx].nilai_pelunasan = numeric
  }

  // Terapkan clamp terhadap sisa/outstanding dan emit ke parent
  onChangeNilaiPelunasan(idx)

  // Sesuaikan tampilan input dengan format yang sudah dikoreksi
  const current = localItems.value[idx]?.nilai_pelunasan
  input.value = formatAlokasiInput(typeof current === 'number' ? current : numeric)
}

const restrictToNumeric = (event: KeyboardEvent) => {
  const allowedControlKeys = [
    'Backspace',
    'Tab',
    'ArrowLeft',
    'ArrowRight',
    'ArrowUp',
    'ArrowDown',
    'Delete',
    'Home',
    'End',
    'Enter',
  ]

  if (allowedControlKeys.includes(event.key)) {
    return
  }

  const isNumber = /[0-9]/.test(event.key)
  const isDecimalSeparator = event.key === '.' || event.key === ','

  if (!isNumber && !isDecimalSeparator) {
    event.preventDefault()
  }
}

const formatDate = (date: any) => {
  if (!date) return 'xxxxxxx'
  return new Date(date).toLocaleDateString('id-ID')
}

const formatCurrency = (value: any) => {
  const numeric = Number(value ?? 0)
  if (!isFinite(numeric)) {
    return 'Rp. xxxxxxxx'
  }
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(numeric)
}
</script>
