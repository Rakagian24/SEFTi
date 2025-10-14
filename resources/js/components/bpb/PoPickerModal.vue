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
  <div v-if="show" class="fixed inset-0 z-50">
    <!-- Backdrop -->
    <div class="absolute inset-0 bg-black/40" @click="$emit('close')"></div>

    <!-- Panel -->
    <div class="relative mx-auto mt-10 max-w-7xl rounded-xl bg-white shadow-[0_10px_40px_rgba(0,0,0,0.25)]">
      <!-- Header -->
      <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
        <h2 class="text-base font-semibold">Pilih Purchase Order</h2>
        <button type="button" @click="$emit('close')" class="text-gray-500 hover:text-gray-700">
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
        </button>
      </div>

      <!-- Table -->
      <div class="px-6 pb-2 max-h-[28rem] overflow-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-gray-600">
              <th class="w-10 py-2">
                <input type="checkbox" @change="toggleAll" />
              </th>
              <th class="py-2">No. PO</th>
              <th class="py-2">Department</th>
              <th class="py-2">Tanggal</th>
              <th class="py-2">Status</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="po in data"
              :key="po.id"
              :class="[
                'border-t border-gray-100',
                selected.includes(po.id) ? 'bg-gray-50' : 'bg-white',
              ]"
            >
              <td class="py-3">
                <input
                  type="checkbox"
                  :checked="selected.includes(po.id)"
                  @change="(e) => toggleOne(po.id, e)"
                />
              </td>
              <td class="py-3">
                <span class="font-medium">{{ po.no_po }}</span>
              </td>
              <td class="py-3">{{ po.department?.name || '-' }}</td>
              <td class="py-3">{{ po.tanggal || po.created_at }}</td>
              <td class="py-3">{{ po.status }}</td>
            </tr>
            <tr v-if="data.length === 0">
              <td colspan="5" class="py-10 text-center text-gray-500">
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
                    Tidak ada Purchase Order yang tersedia
                  </div>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Footer -->
      <div class="px-6 py-4 border-t border-gray-200 flex justify-end gap-2">
        <button
          type="button"
          @click="$emit('close')"
          class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors"
        >
          Tutup
        </button>
        <button
          type="button"
          :disabled="selected.length === 0"
          @click="pick"
          :class="[
            'px-4 py-2 rounded-lg transition-colors',
            selected.length === 0
              ? 'bg-gray-300 text-gray-500 cursor-not-allowed'
              : 'bg-black text-white hover:bg-gray-800',
          ]"
        >
          Pilih ({{ selected.length }})
        </button>
      </div>
    </div>
  </div>
</template>