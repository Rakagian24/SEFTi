<script setup lang="ts">
import { ref } from 'vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'

defineProps({ roles: { type: Object, default: () => ({ data: [] }) } });
const emit = defineEmits(["edit", "delete", "log", "paginate", "toggleStatus"]);

function editRow(row: any) {
  emit("edit", row);
}
function logRow(row: any) {
  emit("log", row);
}
function askDeleteRow(row: any) {
  confirmRow.value = row;
  showConfirm.value = true;
}
function toggleStatus(row: any) {
  emit('toggleStatus', row);
}
const showConfirm = ref(false)
const confirmRow = ref<any>(null)
function onConfirmDelete() {
  emit('delete', confirmRow.value);
  showConfirm.value = false;
  confirmRow.value = null;
}
function onCancelDelete() {
  showConfirm.value = false;
  confirmRow.value = null;
}

const activeTooltip = ref<number|null>(null);

function toggleDescription(rowId: number, event: MouseEvent) {
  event.stopPropagation();
  if (activeTooltip.value === rowId) {
    activeTooltip.value = null;
  } else {
    activeTooltip.value = rowId;
  }
}
function closeTooltip() {
  activeTooltip.value = null;
}
function truncateText(text: string, maxLength = 50): string {
  if (!text) return '-';
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}
function hasDescription(text: string): boolean {
  return !!(text && text.trim() !== '');
}
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nama Role</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Deskripsi</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Aksi</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Toggle</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="role in roles?.data || roles" :key="role.id" class="hover:bg-gray-50" @click="closeTooltip()">
            <td class="px-6 py-4">{{ role.name }}</td>
            <td class="px-6 py-4 relative">
              <div class="flex items-center">
                <span class="inline-block max-w-[200px] truncate">
                  {{ truncateText(role.description) }}
                </span>
                <button
                  v-if="!!hasDescription(role.description) && (role.description?.length ?? 0) > 50"
                  @click="toggleDescription(role.id, $event)"
                  class="ml-2 text-blue-600 hover:text-blue-800 focus:outline-none flex-shrink-0"
                  :title="activeTooltip === role.id ? 'Tutup deskripsi lengkap' : 'Lihat deskripsi lengkap'"
                >
                  <svg
                    class="w-4 h-4 transform transition-transform duration-200"
                    :class="{ 'rotate-180': activeTooltip === role.id }"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                  </svg>
                </button>
              </div>
              <div
                v-if="activeTooltip === role.id && !!hasDescription(role.description)"
                class="absolute top-full left-0 mt-2 z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm w-80"
                style="min-width: 300px"
              >
                <div class="flex items-start justify-between mb-2">
                  <h4 class="text-sm font-semibold text-gray-900">Deskripsi Lengkap:</h4>
                  <button
                    @click="closeTooltip()"
                    class="text-gray-400 hover:text-gray-600 transition-colors ml-2"
                    title="Tutup"
                  >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                  </button>
                </div>
                <div class="bg-gray-50 rounded-md p-3 border border-gray-100">
                  <p class="text-sm text-gray-700 leading-relaxed whitespace-pre-line select-text">
                    {{ role.description }}
                  </p>
                </div>
                <div class="absolute -top-2 left-6 w-4 h-4 bg-white border-l border-t border-gray-200 transform rotate-45"></div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span :class="role.status === 'active' ? 'text-green-600' : 'text-gray-400'">
                {{ role.status === 'active' ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <button class="btn btn-xs btn-primary mr-1" @click="editRow(role)">Edit</button>
              <button class="btn btn-xs btn-warning mr-1" @click="logRow(role)">Log</button>
              <button class="btn btn-xs btn-danger" @click="askDeleteRow(role)">Hapus</button>
            </td>
            <td class="px-6 py-4 text-center">
              <label class="inline-flex items-center cursor-pointer">
                <div
                  class="w-10 h-6 rounded-full transition-colors duration-200 relative"
                  :class="role.status === 'active' ? 'bg-green-400' : 'bg-gray-200'"
                  @click="toggleStatus(role)"
                  style="cursor:pointer"
                >
                  <div
                    class="absolute top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200"
                    :class="role.status === 'active' ? 'translate-x-4 left-1' : 'left-1'"
                  ></div>
                </div>
              </label>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <ConfirmDialog
      v-if="showConfirm"
      :show="showConfirm"
      title="Konfirmasi Hapus"
      message="Yakin ingin menghapus role ini?"
      @confirm="onConfirmDelete"
      @cancel="onCancelDelete"
    />
  </div>
</template>

<style scoped>
/* Custom scrollbar for horizontal scroll */
.overflow-x-auto::-webkit-scrollbar {
  height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
  background: #f1f5f9;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
  background: #94a3b8;
}

/* Ensure action column stays visible during horizontal scroll */
.sticky {
  position: sticky;
  z-index: 10;
}

/* Alternating row colors - FIXED: Removed transparency */
.alternating-row:nth-child(even) {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) {
  background-color: #ffffff;
}

/* Hover effect for alternating rows */
.alternating-row:nth-child(even):hover {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover {
  background-color: #f8fafc;
}

/* Action cell background matching parent row */
.alternating-row:nth-child(even) .action-cell {
  background-color: #eff6f9;
}

.alternating-row:nth-child(odd) .action-cell {
  background-color: #ffffff;
}

.alternating-row:nth-child(even):hover .action-cell {
  background-color: #e0f2fe;
}

.alternating-row:nth-child(odd):hover .action-cell {
  background-color: #f8fafc;
}

/* Pagination styling enhancements */
nav button:focus {
  outline: none;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* Active page button styling */
nav button.z-10 {
  background-color: #2563eb !important;
  border-color: #2563eb !important;
  color: white !important;
  font-weight: 600;
}

/* Disabled button styling */
nav button:disabled {
  opacity: 0.5;
}

/* Hover effects for pagination buttons */
nav button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}
</style>
