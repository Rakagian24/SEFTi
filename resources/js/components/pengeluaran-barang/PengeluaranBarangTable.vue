<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import EmptyState from "../ui/EmptyState.vue";
import { formatDate } from '@/lib/formatters';

const props = defineProps<{
  items: any;
  modelValue?: number[];
}>();

const emit = defineEmits(["detail", "delete", "paginate", "update:modelValue"]);

const internalSelectedIds = ref<number[]>(props.modelValue ? [...props.modelValue] : []);

watch(
  () => props.modelValue,
  (newVal) => {
    if (!newVal) {
      internalSelectedIds.value = [];
      return;
    }
    internalSelectedIds.value = [...newVal];
  }
);

const allIdsOnPage = computed<number[]>(() => {
  if (!props.items?.data) return [];
  return props.items.data.map((row: any) => row.id as number);
});

const isAllSelected = computed<boolean>(() => {
  if (allIdsOnPage.value.length === 0) return false;
  return allIdsOnPage.value.every((id) => internalSelectedIds.value.includes(id));
});

function toggleSelectAll(event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  if (!checked) {
    const remaining = internalSelectedIds.value.filter((id) => !allIdsOnPage.value.includes(id));
    internalSelectedIds.value = remaining;
  } else {
    const merged = new Set<number>([...internalSelectedIds.value, ...allIdsOnPage.value]);
    internalSelectedIds.value = Array.from(merged);
  }
  emit("update:modelValue", internalSelectedIds.value);
}

function toggleRowSelection(row: any, event: Event) {
  const checked = (event.target as HTMLInputElement).checked;
  const id = row.id as number;

  if (checked) {
    if (!internalSelectedIds.value.includes(id)) {
      internalSelectedIds.value = [...internalSelectedIds.value, id];
    }
  } else {
    internalSelectedIds.value = internalSelectedIds.value.filter((v) => v !== id);
  }

  emit("update:modelValue", internalSelectedIds.value);
}

function isRowSelected(row: any) {
  return internalSelectedIds.value.includes(row.id as number);
}

function goToPage(url: string | null) {
  if (!url) return;
  emit("paginate", url);
  window.dispatchEvent(new CustomEvent("pagination-changed"));
  window.dispatchEvent(new CustomEvent("table-changed"));
}
</script>

<template>
  <!-- Empty State -->
  <EmptyState
    v-if="!items?.data || items.data.length === 0"
    title="No Pengeluaran Barang found"
    description="Belum ada data pengeluaran barang."
    icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
    :show-action="false"
  />

  <!-- Table with data -->
  <div v-else class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              <input
                type="checkbox"
                :checked="isAllSelected"
                @click.stop
                @change="toggleSelectAll"
              />
            </th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">No. Pengeluaran</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Tanggal</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Departemen</th>
            <!-- <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Jenis Pengeluaran</th> -->
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Dikeluarkan Oleh</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="(row, index) in items?.data"
            :key="row.id"
            class="alternating-row cursor-pointer"
            @click="emit('detail', row)"
          >
            <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-[#101010]" @click.stop>
              <input
                type="checkbox"
                :checked="isRowSelected(row)"
                @change="(e: Event) => toggleRowSelection(row, e)"
                @click.stop
              />
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              {{ (items.current_page - 1) * items.per_page + index + 1 }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              {{ row.no_pengeluaran }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              {{ formatDate(row.tanggal) }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              {{ row.department?.name || '-' }}
            </td>
            <!-- <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              {{ row.jenis_pengeluaran || '-' }}
            </td> -->
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010]">
              {{ row.created_by?.name || '-' }}
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <button
          @click="goToPage(items?.prev_page_url)"
          :disabled="!items?.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            items?.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <template v-for="(link, index) in items?.links?.slice(1, -1)" :key="index">
          <button
            @click="goToPage(link.url)"
            :disabled="!link.url"
            :class="[
              'w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200',
              link.active
                ? 'bg-black text-white'
                : link.url
                ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                : 'bg-gray-200 text-gray-400 cursor-not-allowed',
            ]"
            v-html="link.label"
          ></button>
        </template>

        <button
          @click="goToPage(items?.next_page_url)"
          :disabled="!items?.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            items?.next_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Next
        </button>
      </nav>
    </div>
  </div>
</template>

<style scoped>
.overflow-x-auto::-webkit-scrollbar { height: 8px; }
.overflow-x-auto::-webkit-scrollbar-track { background: #f1f5f9; }
.overflow-x-auto::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
.overflow-x-auto::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
.sticky { position: sticky; z-index: 10; }
.alternating-row:nth-child(even) { background-color: #eff6f9; }
.alternating-row:nth-child(odd) { background-color: #ffffff; }
.alternating-row:nth-child(even):hover { background-color: #e0f2fe; }
.alternating-row:nth-child(odd):hover { background-color: #f8fafc; }
.alternating-row:nth-child(even) .action-cell { background-color: #eff6f9; }
.alternating-row:nth-child(odd) .action-cell { background-color: #ffffff; }
.alternating-row:nth-child(even):hover .action-cell { background-color: #e0f2fe; }
.alternating-row:nth-child(odd):hover .action-cell { background-color: #f8fafc; }
nav button:focus { outline: none; box-shadow: 0 0 0 3px rgba(59,130,246,0.1); }
nav button:disabled { opacity: 0.5; }
</style>
