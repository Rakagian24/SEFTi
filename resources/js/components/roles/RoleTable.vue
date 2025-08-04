<script setup lang="ts">
import { ref } from 'vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'
import EmptyState from '../ui/EmptyState.vue'

defineProps({ roles: { type: Object, default: () => ({ data: [] }) } });
const emit = defineEmits(["edit", "delete", "log", "paginate", "toggleStatus", "add"]);

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

function handleAdd() {
  emit('add');
}
</script>

<template>
  <!-- Empty State -->
  <EmptyState
    v-if="!roles || (Array.isArray(roles) && roles.length === 0) || (roles?.data && roles.data.length === 0)"
    title="No Roles found"
    description="There are no roles to display. Start by adding your first role."
    icon="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"
    action-text="Add Role"
    :show-action="true"
    @action="handleAdd"
  />

  <!-- Table with data -->
  <div v-else class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nama Role</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Deskripsi</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Toggle</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="role in (roles?.data || roles)" :key="role.id" class="alternating-row" @click="closeTooltip()">
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">{{ role.name }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010] relative">
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
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  role.status === 'active'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800',
                ]"
              >
                {{ role.status === 'active' ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <label class="inline-flex items-center cursor-pointer ml-2">
                <div
                  class="w-10 h-6 rounded-full transition-colors duration-200 relative"
                  :class="role.status === 'active' ? 'bg-green-400' : 'bg-gray-200'"
                  @click.stop="toggleStatus(role)"
                  style="cursor: pointer"
                >
                  <div
                    class="absolute top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200"
                    :class="role.status === 'active' ? 'translate-x-4 left-1' : 'left-1'"
                  ></div>
                </div>
              </label>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit Button -->
                <button
                  @click="editRow(role)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200"
                  title="Edit"
                >
                  <svg
                    class="w-4 h-4 text-blue-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    />
                  </svg>
                </button>

                <!-- Delete Button -->
                <button
                  @click="askDeleteRow(role)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200"
                  title="Hapus"
                >
                  <svg
                    class="w-4 h-4 text-red-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                    />
                  </svg>
                </button>

                <!-- Log Activity Button -->
                <button
                  @click.stop="logRow(role)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-gray-50 hover:bg-gray-100 transition-colors duration-200"
                  title="Log Activity"
                >
                  <svg
                    class="w-4 h-4 text-gray-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                    />
                  </svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Empty State -->
      <div v-if="!roles || (Array.isArray(roles) && roles.length === 0) || (roles?.data && roles.data.length === 0)" class="text-center py-12">
        <svg
          class="mx-auto h-12 w-12 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
          />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data role</h3>
        <p class="mt-1 text-sm text-[#101010]">
          Mulai dengan menambahkan data role baru.
        </p>
      </div>
    </div>
  </div>

  <ConfirmDialog
    v-if="showConfirm"
    :show="showConfirm"
    title="Konfirmasi Hapus"
    message="Yakin ingin menghapus role ini?"
    @confirm="onConfirmDelete"
    @cancel="onCancelDelete"
  />
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

/* Alternating row colors */
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

/* Disabled button styling */
nav button:disabled {
  opacity: 0.5;
}

/* Hover effects for pagination buttons */
nav button:not(:disabled):hover {
  transform: translateY(-1px);
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

/* Truncate text styling */
.truncate {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

/* Tooltip animations */
.tooltip-enter-active,
.tooltip-leave-active {
  transition: all 0.2s ease;
}

.tooltip-enter-from,
.tooltip-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}
</style>
