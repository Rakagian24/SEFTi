<template>
  <div class="bg-white rounded-lg border border-gray-200">
    <div class="p-4">
      <div v-if="loading" class="text-sm text-gray-500">Loading...</div>

      <div v-else>
        <table class="min-w-full divide-y divide-gray-200">
          <thead class="bg-gray-50">
            <tr>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider w-10">
                <input type="checkbox" :checked="allSelectableSelected" @change="toggleSelectAll($event)" />
              </th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">No. BPB</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tanggal</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Department</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Supplier</th>
              <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
              <th class="px-3 py-2 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
            </tr>
          </thead>
          <tbody class="bg-white divide-y divide-gray-200">
            <tr v-for="row in data" :key="row.id" class="hover:bg-gray-50">
              <td class="px-3 py-2">
                <input
                  type="checkbox"
                  :disabled="!isSelectable(row)"
                  :checked="selected.includes(row.id)"
                  @change="toggleRow(row)"
                />
              </td>
              <td class="px-3 py-2 text-sm text-gray-900">{{ row.no_bpb || '-' }}</td>
              <td class="px-3 py-2 text-sm text-gray-700">{{ formatDate(row.tanggal) }}</td>
              <td class="px-3 py-2 text-sm text-gray-700">{{ row.department?.name || '-' }}</td>
              <td class="px-3 py-2 text-sm text-gray-700">{{ row.supplier?.nama_supplier || '-' }}</td>
              <td class="px-3 py-2 text-sm">
                <span :class="statusClass(row.status)" class="px-2 py-1 rounded-full text-xs font-medium">{{ row.status }}</span>
              </td>
              <td class="px-3 py-2 text-sm text-right">
                <div class="inline-flex gap-2">
                  <button
                    class="px-3 py-1 rounded-md text-white"
                    :class="isSelectable(row) ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-300 cursor-not-allowed'"
                    :disabled="!isSelectable(row)"
                    @click="$emit('action', { action: 'approve', row })"
                  >
                    Setujui
                  </button>
                  <button
                    class="px-3 py-1 rounded-md border border-red-300 text-red-600 bg-white hover:bg-red-50"
                    :disabled="row.status !== 'In Progress'"
                    @click="$emit('action', { action: 'reject', row })"
                  >
                    Tolak
                  </button>
                </div>
              </td>
            </tr>
            <tr v-if="!data || data.length === 0">
              <td colspan="7" class="px-3 py-8 text-center text-sm text-gray-500">Tidak ada data</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="pagination" class="px-4 py-3 border-t bg-gray-50 flex items-center justify-between">
      <div class="text-sm text-gray-600">Halaman {{ pagination.current_page }} dari {{ pagination.last_page }}</div>
      <div class="flex items-center gap-2">
        <button class="px-3 py-1 rounded border bg-white disabled:opacity-50" :disabled="!pagination.prev_page_url" @click="$emit('paginate', pagination.prev_page_url)">Sebelumnya</button>
        <button class="px-3 py-1 rounded border bg-white disabled:opacity-50" :disabled="!pagination.next_page_url" @click="$emit('paginate', pagination.next_page_url)">Berikutnya</button>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
  data: any[];
  loading: boolean;
  selected: number[];
  pagination: any | null;
  selectableStatuses: string[];
  isRowSelectable: (row: any) => boolean;
}>();

const emit = defineEmits<{
  (e: 'select', value: number[]): void;
  (e: 'action', payload: { action: 'approve' | 'reject'; row: any }): void;
  (e: 'paginate', url: string): void;
}>();

const allSelectableSelected = computed(() => {
  const selectableIds = (props.data || []).filter(isSelectable).map((r: any) => r.id);
  return selectableIds.length > 0 && selectableIds.every((id: number) => props.selected.includes(id));
});

function toggleSelectAll(e: Event) {
  const checked = (e.target as HTMLInputElement).checked;
  const selectableIds = (props.data || []).filter(isSelectable).map((r: any) => r.id);
  emit('select', checked ? selectableIds : []);
}

function toggleRow(row: any) {
  const id = row.id;
  const next = props.selected.includes(id)
    ? props.selected.filter((x) => x !== id)
    : [...props.selected, id];
  emit('select', next);
}

function isSelectable(row: any) {
  return props.selectableStatuses.includes(row.status) && props.isRowSelectable(row);
}

function statusClass(status: string) {
  switch (status) {
    case 'Approved':
      return 'bg-green-100 text-green-800';
    case 'Rejected':
      return 'bg-red-100 text-red-800';
    case 'In Progress':
      return 'bg-blue-100 text-blue-800';
    default:
      return 'bg-gray-100 text-gray-800';
  }
}

function formatDate(d: string | null | undefined) {
  if (!d) return '-';
  try { return new Date(d).toLocaleDateString('id-ID'); } catch { return d; }
}
</script>
