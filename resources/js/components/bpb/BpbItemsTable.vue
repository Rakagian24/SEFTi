<script setup lang="ts">
import { computed } from "vue";

type Item = { nama_barang: string; qty: number; satuan: string; harga: number };

const props = defineProps<{
  modelValue: {
    items: Item[];
    diskon: number;
    use_ppn: boolean;
    ppn_rate: number;
    use_pph: boolean;
    pph_rate: number;
  };
}>();

const emit = defineEmits(["update:modelValue", "add-item", "clear-items", "add-pph"]);

const subtotal = computed(() =>
  props.modelValue.items.reduce(
    (c, it) => c + Number(it.qty || 0) * Number(it.harga || 0),
    0
  )
);
const dpp = computed(() =>
  Math.max(0, subtotal.value - Number(props.modelValue.diskon || 0))
);
const ppn = computed(() =>
  props.modelValue.use_ppn
    ? dpp.value * (Number(props.modelValue.ppn_rate || 0) / 100)
    : 0
);
const pph = computed(() =>
  props.modelValue.use_pph
    ? dpp.value * (Number(props.modelValue.pph_rate || 0) / 100)
    : 0
);
const grandTotal = computed(() => dpp.value + ppn.value + pph.value);

function update(partial: any) {
  emit("update:modelValue", { ...props.modelValue, ...partial });
}
</script>

<template>
  <div class="bg-white p-4 rounded-lg border space-y-3">
    <div class="flex items-center gap-2">
      <button
        class="px-2 py-1 rounded bg-[#5856D6] text-white"
        @click="$emit('add-item')"
      >
        +
      </button>
      <button
        class="px-2 py-1 rounded bg-red-600 text-white"
        @click="$emit('clear-items')"
      >
        -
      </button>
    </div>
    <div class="overflow-x-auto">
      <table class="min-w-full">
        <thead>
          <tr class="bg-gray-50">
            <th class="px-4 py-2 text-left">Nama Barang</th>
            <th class="px-4 py-2 text-right">Qty</th>
            <th class="px-4 py-2">Satuan</th>
            <th class="px-4 py-2 text-right">Harga</th>
            <th class="px-4 py-2 text-right">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="(it, idx) in modelValue.items" :key="idx" class="border-t">
            <td class="px-4 py-2">{{ it.nama_barang }}</td>
            <td class="px-4 py-2 text-right">{{ it.qty }}</td>
            <td class="px-4 py-2">{{ it.satuan }}</td>
            <td class="px-4 py-2 text-right">{{ Number(it.harga).toLocaleString() }}</td>
            <td class="px-4 py-2 text-right">
              {{ (Number(it.qty) * Number(it.harga)).toLocaleString() }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="grid grid-cols-2 gap-6">
      <div class="space-y-2">
        <label class="flex items-center gap-2"
          ><input
            type="checkbox"
            :checked="modelValue.use_ppn"
            @change="update({use_ppn: ($event.target as HTMLInputElement).checked})"
          />
          PPN</label
        >
        <label class="flex items-center gap-2"
          ><input
            type="checkbox"
            :checked="modelValue.use_pph"
            @change="update({use_pph: ($event.target as HTMLInputElement).checked})"
          />
          PPH</label
        >
        <div class="flex items-center gap-2" v-if="modelValue.use_ppn">
          <span class="text-xs text-gray-500">Tarif PPN (%)</span>
          <input
            type="number"
            :value="modelValue.ppn_rate"
            @input="update({ppn_rate: Number(($event.target as HTMLInputElement).value)})"
            class="border rounded px-2 py-1 w-24"
          />
        </div>
        <div class="flex items-center gap-2" v-if="modelValue.use_pph">
          <span class="text-xs text-gray-500">Tarif PPH (%)</span>
          <input
            type="number"
            :value="modelValue.pph_rate"
            @input="update({pph_rate: Number(($event.target as HTMLInputElement).value)})"
            class="border rounded px-2 py-1 w-24"
          />
          <button
            class="px-2 py-1 rounded border"
            title="Tambah PPh"
            @click="$emit('add-pph')"
          >
            +
          </button>
        </div>
        <div class="space-y-1">
          <label class="flex items-center gap-2"><span>Diskon</span></label>
          <input
            type="number"
            :value="modelValue.diskon"
            @input="update({diskon: Number(($event.target as HTMLInputElement).value)})"
            class="border rounded px-2 py-1 w-40"
          />
        </div>
      </div>
      <div class="ml-auto w-64 space-y-2">
        <div class="flex justify-between">
          <span>Total</span><span>{{ subtotal.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between">
          <span>Diskon</span><span>{{ Number(modelValue.diskon).toLocaleString() }}</span>
        </div>
        <div class="flex justify-between">
          <span>DPP</span><span>{{ dpp.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between">
          <span>PPN</span><span>{{ ppn.toLocaleString() }}</span>
        </div>
        <div class="flex justify-between">
          <span>PPH</span><span>{{ pph.toLocaleString() }}</span>
        </div>
        <div class="border-t pt-2 flex justify-between font-semibold">
          <span>Grand Total</span><span>{{ grandTotal.toLocaleString() }}</span>
        </div>
      </div>
    </div>
  </div>
</template>
