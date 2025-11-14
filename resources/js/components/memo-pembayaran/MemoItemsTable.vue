<script setup lang="ts">
import { computed } from "vue";

// Reused UI from BPB Items table; used in Memo Pembayaran
// Expected item fields: nama_barang, qty, satuan, harga, remaining_qty (cap)

type Item = { nama_barang: string; qty: number; satuan: string; harga: number };

const props = defineProps<{
  modelValue: {
    items: Item[];
    diskon?: number;
    use_ppn?: boolean;
    ppn_rate?: number;
    use_pph?: boolean;
    pph_rate?: number;
  };
}>();

const emit = defineEmits(["update:modelValue"]);

const subtotal = computed(() =>
  (props.modelValue.items || []).reduce(
    (c, it: any) => c + Number(it.qty || 0) * Number(it.harga || 0),
    0
  )
);
const dpp = computed(() => Math.max(0, subtotal.value - Number(props.modelValue?.diskon || 0)));
const ppn = computed(() => (props.modelValue?.use_ppn ? dpp.value * 0.11 : 0));
const pph = computed(() =>
  props.modelValue?.use_pph
    ? dpp.value * (Number(props.modelValue?.pph_rate || 0) / 100)
    : 0
);
const grandTotal = computed(() => dpp.value + ppn.value + pph.value);

function formatRupiah(val: number | string | null | undefined) {
  const num = Number(val) || 0;
  const formattedNumber = new Intl.NumberFormat("en-US", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 2,
  }).format(num);
  return `Rp ${formattedNumber}`;
}

function setQty(index: number, value: number) {
  const items: any[] = (props.modelValue.items || []) as any[];
  const it: any = items[index];
  if (!it) return;
  const max = Number(it?.remaining_qty ?? 0);
  const v = Math.max(0, Math.min(Number(value || 0), isFinite(max) ? max : Number(value || 0)));
  const next = items.map((x, i) => (i === index ? { ...x, qty: v } : x));
  emit("update:modelValue", { ...props.modelValue, items: next as any });
}
</script>

<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
    <div class="overflow-hidden rounded-lg border border-gray-200 mb-4">
      <table class="min-w-full">
        <thead class="bg-gray-50">
          <tr>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Nama Barang
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Qty
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Satuan
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Harga
            </th>
            <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">
              Subtotal
            </th>
          </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
          <tr v-for="(it, idx) in modelValue.items" :key="idx" class="hover:bg-gray-50">
            <td class="px-4 py-3 text-sm text-gray-900">{{ (it as any).nama_barang || (it as any).nama_item || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">
              <div class="flex items-center gap-2">
                <input
                  type="number"
                  :value="(it as any).qty ?? (it as any).remaining_qty ?? 0"
                  @input="setQty(idx, Number(($event.target as HTMLInputElement).value))"
                  :min="0"
                  :max="Number((it as any).remaining_qty ?? 0)"
                  class="w-24 px-2 py-1 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                />
                <span class="text-xs text-gray-500">
                  Sisa: {{ Number((it as any).remaining_qty ?? 0) }}
                </span>
              </div>
            </td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ (it as any).satuan?.nama || (it as any).satuan || '-' }}</td>
            <td class="px-4 py-3 text-sm text-gray-900">{{ formatRupiah((it as any).harga) }}</td>
            <td class="px-4 py-3 text-sm text-gray-900 font-medium">
              {{ formatRupiah(Number((it as any).qty) * Number((it as any).harga)) }}
            </td>
          </tr>
          <tr v-if="!modelValue.items || !modelValue.items.length">
            <td colspan="7" class="px-4 py-8 text-center text-sm text-gray-500">
              Belum ada barang
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
      <div class="lg:w-80 lg:ml-auto">
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
          <div class="space-y-2">
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Total</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(subtotal) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">Diskon</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(modelValue?.diskon || 0) }}</span>
            </div>
            <div class="flex justify-between items-center text-sm">
              <span class="text-gray-600">DPP</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(dpp) }}</span>
            </div>
            <div v-if="modelValue?.use_ppn" class="flex justify-between items-center text-sm">
              <span class="text-gray-600">PPN</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(ppn) }}</span>
            </div>
            <div v-if="modelValue?.use_pph" class="flex justify-between items-center text-sm">
              <span class="text-gray-600">PPH</span>
              <span class="font-semibold text-gray-900">{{ formatRupiah(pph) }}</span>
            </div>
            <div class="border-t border-gray-300 pt-2 mt-2">
              <div class="flex justify-between items-center">
                <span class="text-base font-semibold text-gray-900">Grand Total</span>
                <span class="text-lg font-bold text-gray-900">{{ formatRupiah(grandTotal) }}</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Focus states */
input:focus {
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Table row hover effect */
tbody tr:hover {
  background-color: #f9fafb;
}

/* Disabled button styling */
button:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}
</style>
