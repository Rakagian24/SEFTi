<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{ show: boolean; data: Array<any> }>();
const emit = defineEmits<{
  close: [];
  pick: [ids: number[]];
}>();

const selected = ref<number[]>([]);

function toggleAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  selected.value = checked ? props.data.map((x: any) => x.id) : [];
}

function toggleOne(id: number, e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  if (checked) {
    if (!selected.value.includes(id)) selected.value.push(id);
  } else {
    selected.value = selected.value.filter(x => x !== id);
  }
}

function pick() {
  if (selected.value.length === 0) return;
  emit('pick', selected.value);
}
</script>

<template>
  <div v-if="show" class="fixed inset-0 bg-black/30 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-4 w-[720px] max-h-[80vh] overflow-auto space-y-3">
      <div class="flex items-center justify-between">
        <h3 class="font-semibold">Pilih Purchase Order</h3>
        <button class="text-gray-500" @click="$emit('close')">✕</button>
      </div>

      <div class="overflow-x-auto border rounded">
        <table class="min-w-full">
          <thead>
            <tr class="bg-gray-50">
              <th class="px-4 py-2 text-center"><input type="checkbox" @change="toggleAll" /></th>
              <th class="px-4 py-2 text-left">No. PO</th>
              <th class="px-4 py-2 text-left">Department</th>
              <th class="px-4 py-2 text-left">Tanggal</th>
              <th class="px-4 py-2 text-left">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="po in data" :key="po.id" class="border-t">
              <td class="px-4 py-2 text-center">
                <input type="checkbox" :checked="selected.includes(po.id)" @change="(e)=>toggleOne(po.id, e)" />
              </td>
              <td class="px-4 py-2 whitespace-nowrap">{{ po.no_po }}</td>
              <td class="px-4 py-2">{{ po.department?.name || '-' }}</td>
              <td class="px-4 py-2">{{ po.tanggal || po.created_at }}</td>
              <td class="px-4 py-2">{{ po.status }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="flex justify-end gap-2">
        <button class="px-3 py-2 rounded border" @click="$emit('close')">Tutup</button>
        <button class="px-3 py-2 rounded bg-[#5856D6] text-white" :disabled="selected.length===0" @click="pick">Pilih</button>
      </div>
    </div>
  </div>
</template>
