<script setup lang="ts">
const props = defineProps<{
  latestPOs: Array<any>;
  suppliers: Array<any>;
  modelValue: any;
}>();

const emit = defineEmits(["update:modelValue"]);

function update(field: string, value: any) {
  emit("update:modelValue", { ...props.modelValue, [field]: value });
}

function onSupplierChange(id: any) {
  const s = props.suppliers.find((x: any) => String(x.id) === String(id));
  emit("update:modelValue", {
    ...props.modelValue,
    supplier_id: id,
    alamat: s?.alamat || "",
  });
}
</script>

<template>
  <div class="grid grid-cols-2 gap-6 bg-white p-4 rounded-lg border">
    <div class="space-y-2">
      <label class="text-xs text-gray-500">No. BPB</label>
      <input disabled class="border rounded px-2 py-1 w-full bg-gray-100" />
    </div>
    <div class="space-y-2">
      <label class="text-xs text-gray-500">Supplier</label>
      <select
        :value="modelValue.supplier_id"
        @change="onSupplierChange(($event.target as HTMLSelectElement).value)"
        class="border rounded px-2 py-1 w-full"
      >
        <option value="">Pilih Supplier</option>
        <option v-for="s in suppliers" :key="s.id" :value="s.id">
          {{ s.nama_supplier }}
        </option>
      </select>
    </div>
    <div class="space-y-2">
      <label class="text-xs text-gray-500">Tanggal</label>
      <input disabled class="border rounded px-2 py-1 w-full bg-gray-100" />
    </div>
    <div class="space-y-2">
      <label class="text-xs text-gray-500">Alamat</label>
      <input
        :value="modelValue.alamat"
        @input="update('alamat', ($event.target as HTMLInputElement).value)"
        class="border rounded px-2 py-1 w-full"
      />
    </div>
    <div class="space-y-2">
      <label class="text-xs text-gray-500">No. Payment Voucher</label>
      <input disabled class="border rounded px-2 py-1 w-full bg-gray-100" />
    </div>
    <div class="space-y-2">
      <label class="text-xs text-gray-500">Keterangan</label>
      <textarea
        :value="modelValue.keterangan"
        @input="update('keterangan', ($event.target as HTMLTextAreaElement).value)"
        class="border rounded px-2 py-1 w-full"
      ></textarea>
    </div>
    <div class="col-span-2 space-y-2">
      <label class="text-xs text-gray-500">Purchase Order</label>
      <div class="flex gap-2">
        <select
          :value="modelValue.purchase_order_id"
          @change="update('purchase_order_id', ($event.target as HTMLSelectElement).value)"
          class="border rounded px-2 py-1 w-full"
        >
          <option value="">Pilih Purchase Order</option>
          <option v-for="po in latestPOs" :key="po.id" :value="po.id">
            {{ po.no_po }}
          </option>
        </select>
        <button
          class="px-3 py-1 rounded bg-[#5856D6] text-white"
          title="Pilih dari daftar PO"
        >
          +
        </button>
      </div>
      <small class="text-gray-500">Menampilkan 5 PO terbaru</small>
    </div>
    <div class="col-span-2 space-y-2">
      <label class="text-xs text-gray-500">Note</label>
      <textarea
        :value="modelValue.note"
        @input="update('note', ($event.target as HTMLTextAreaElement).value)"
        class="border rounded px-2 py-1 w-full"
      ></textarea>
    </div>
  </div>
</template>
