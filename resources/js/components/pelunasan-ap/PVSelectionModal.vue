<template>
  <div class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>

    <!-- Panel -->
    <div class="relative mx-auto mt-10 max-w-5xl rounded-xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold">Pilih Payment Voucher</h2>
        <button
          type="button"
          @click="$emit('close')"
          class="text-gray-500 hover:text-gray-700"
        >
          <Icon name="x" class="w-5 h-5" />
        </button>
      </div>

      <!-- Toolbar -->
      <div class="px-6 py-3 flex items-center gap-2">
        <input
          v-model="filterDate"
          type="date"
          class="px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          placeholder="Filter tanggal"
        />
        <button
          type="button"
          @click="applyFilter"
          class="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors"
        >
          Filter
        </button>
        <div class="ml-auto flex items-center gap-3">
          <div class="text-sm text-gray-600">
            {{ selectedPVs.length }} dipilih dari {{ pvList.length }} data
          </div>
        </div>
      </div>

      <!-- Table -->
      <div class="px-6 pb-2 max-h-[28rem] overflow-auto">
        <table class="w-full text-sm table-auto">
          <thead>
            <tr class="text-left text-gray-600">
              <th class="w-10 px-3 py-2">
                <input
                  type="checkbox"
                  v-model="selectAll"
                  @change="toggleSelectAll"
                  class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded"
                />
              </th>
              <th class="py-2 px-3 w-28">Tanggal</th>
              <th class="py-2 px-3 w-40">No. PV</th>
              <th class="py-2 px-3 w-32">Tipe</th>
              <th class="py-2 px-3 w-40">No. BK</th>
              <th class="py-2 px-3">Supplier</th>
              <th class="py-2 px-3 w-32 text-right">Nilai</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="pv in pvList"
              :key="pv.id"
              :class="[
                'border-t border-gray-100 transition-colors',
                selectedPVs.includes(pv.id) ? 'bg-blue-50' : 'hover:bg-blue-50/50'
              ]"
            >
              <td class="py-3 px-3">
                <input
                  type="checkbox"
                  :value="pv.id"
                  v-model="selectedPVs"
                  class="w-4 h-4 text-blue-600 focus:ring-blue-500 rounded"
                />
              </td>
              <td class="py-3 px-3 text-gray-700">{{ formatDate(pv.tanggal) }}</td>
              <td class="py-3 px-3 font-medium text-gray-900">{{ pv.no_pv }}</td>
              <td class="py-3 px-3 text-gray-700">{{ pv.tipe_pv }}</td>
              <td class="py-3 px-3 text-gray-700">{{ pv.no_bk || '-' }}</td>
              <td class="py-3 px-3 text-gray-700">{{ pv.supplier?.nama_supplier || pv.supplier?.nama }}</td>
              <td class="py-3 px-3 text-right font-medium text-gray-900">{{ formatCurrency(pv.nominal) }}</td>
            </tr>

            <tr v-if="pvList.length === 0">
              <td colspan="7" class="py-10 text-center text-gray-500">
                <div class="flex flex-col items-center">
                  <svg
                    class="w-12 h-12 mb-3 text-gray-300"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    ></path>
                  </svg>
                  <div class="text-base font-medium mb-1">
                    Tidak ada Payment Voucher yang tersedia
                  </div>
                  <div class="text-sm">Silakan coba filter tanggal yang berbeda</div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex items-center justify-end gap-2">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 transition-colors"
        >
          Batal
        </button>
        <button
          type="button"
          @click="confirmSelection"
          :disabled="selectedPVs.length === 0"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-md transition-colors',
            selectedPVs.length === 0
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
              : 'bg-blue-600 text-white hover:bg-blue-700'
          ]"
        >
          Tambah ({{ selectedPVs.length }})
        </button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import Icon from '@/components/Icon.vue'

interface PaymentVoucher {
  id: number
  tanggal?: string
  no_pv?: string
  tipe_pv?: string
  no_bk?: string | null
  supplier?: { nama?: string; nama_supplier?: string } | null
  nominal?: number
}

const props = defineProps<{
  supplierId?: string | number
  tipePv?: string | null
}>()

const emit = defineEmits(['select', 'close'])

const pvList = ref<PaymentVoucher[]>([])
const selectedPVs = ref<number[]>([])
const selectAll = ref(false)
const filterDate = ref('')

onMounted(() => {
  fetchPaymentVouchers()
})

const fetchPaymentVouchers = async () => {
  try {
    const response = await axios.get(route('pelunasan-ap.payment-vouchers.search'), {
      params: {
        supplier_id: props.supplierId,
        tanggal_start: filterDate.value,
        tipe_pv: props.tipePv || undefined,
      },
    })
    pvList.value = response.data.data || []
  } catch (error) {
    console.error('Error fetching payment vouchers:', error)
  }
}

const applyFilter = () => {
  fetchPaymentVouchers()
}

const toggleSelectAll = () => {
  if (selectAll.value) {
    selectedPVs.value = pvList.value.map((pv: any) => pv.id)
  } else {
    selectedPVs.value = []
  }
}

const confirmSelection = () => {
  const selected = pvList.value.filter((pv: any) => selectedPVs.value.includes(pv.id))
  emit('select', selected)
}

const formatDate = (date: any) => {
  if (!date) return '-'
  try {
    return new Date(date).toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: 'numeric',
    })
  } catch {
    return String(date || '-')
  }
}

const formatCurrency = (value: any) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0)
}
</script>
