<template>
  <div class="space-y-4">
    <!-- Header -->
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-gray-900">Detail Payment Voucher</h3>
    </div>

    <!-- PV Table -->
    <div class="overflow-x-auto border border-gray-200 rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. PV</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai PV</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Outstanding</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Nilai Pelunasan</th>
            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Sisa</th>
            <th class="px-4 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(item, idx) in items" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ formatDate(item.pv?.tanggal || item.payment_voucher?.tanggal) }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ item.pv?.no_pv || item.payment_voucher?.no_pv }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(item.nilai_pv) }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 text-right">{{ formatCurrency(item.outstanding) }}</td>
            <td class="px-4 py-3">
              <input
                v-model.number="localItems[idx].nilai_pelunasan"
                type="number"
                step="0.01"
                @input="onChangeNilaiPelunasan(idx)"
                class="w-32 px-3 py-1.5 text-sm text-right border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </td>
            <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">{{ formatCurrency(item.sisa) }}</td>
            <td class="px-4 py-3 text-center">
              <button
                type="button"
                @click="$emit('remove', idx)"
                class="text-red-600 hover:text-red-900 inline-flex items-center justify-center"
              >
                <Icon name="trash-2" class="w-4 h-4" />
              </button>
            </td>
          </tr>
          <tr v-if="!items || items.length === 0">
            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
              Belum ada data payment voucher
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Summary -->
    <div class="mt-4">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Summary Kiri -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Total PV</span>
              <span class="font-semibold text-gray-900">{{ formatCurrency(totalPV) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Total Alokasi</span>
              <span class="font-semibold text-gray-900">{{ formatCurrency(totalAlokasi) }}</span>
            </div>
            <div class="border-t border-gray-300 pt-2 mt-2">
              <div class="flex justify-between items-center">
                <span class="text-base font-semibold text-gray-900">Total Sisa PV</span>
                <span class="text-lg font-bold text-gray-900">{{ formatCurrency(totalSisa) }}</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Summary Kanan -->
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="space-y-3">
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Pembulatan (-)</label>
              <input
                v-model.number="localPembulatanMinus"
                type="number"
                step="0.01"
                class="w-full px-3 py-1.5 text-sm text-right border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Pembulatan (+)</label>
              <input
                v-model.number="localPembulatanPlus"
                type="number"
                step="0.01"
                class="w-full px-3 py-1.5 text-sm text-right border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
              />
            </div>
            <div class="border-t border-gray-300 pt-2 mt-2">
              <div class="flex justify-between items-center">
                <span class="text-base font-semibold text-gray-900">Grand Total</span>
                <span class="text-lg font-bold text-green-600">{{ formatCurrency(grandTotal) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, watch } from 'vue'
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
  const value = Number(localItems.value[idx]?.nilai_pelunasan || 0)
  emit('change-nilai-pelunasan', idx, value)
}

const formatDate = (date: any) => {
  if (!date) return '-'
  return new Date(date).toLocaleDateString('id-ID')
}

const formatCurrency = (value: any) => {
  return new Intl.NumberFormat('id-ID', {
    style: 'currency',
    currency: 'IDR',
    minimumFractionDigits: 0,
  }).format(value || 0)
}
</script>
