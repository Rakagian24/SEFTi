<script setup lang="ts">
import { ref } from 'vue'
import ConfirmDialog from '../ui/ConfirmDialog.vue'

defineProps({ departments: { type: Object, default: () => ({ data: [] }) } });
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
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nama Department</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Aksi</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Toggle</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="department in departments?.data || departments" :key="department.id" class="hover:bg-gray-50">
            <td class="px-6 py-4">{{ department.name }}</td>
            <td class="px-6 py-4">
              <span :class="department.status === 'active' ? 'text-green-600' : 'text-gray-400'">
                {{ department.status === 'active' ? 'Aktif' : 'Nonaktif' }}
              </span>
            </td>
            <td class="px-6 py-4 text-center">
              <button class="btn btn-xs btn-primary mr-1" @click="editRow(department)">Edit</button>
              <button class="btn btn-xs btn-warning mr-1" @click="logRow(department)">Log</button>
              <button class="btn btn-xs btn-danger" @click="askDeleteRow(department)">Hapus</button>
            </td>
            <td class="px-6 py-4 text-center">
              <label class="inline-flex items-center cursor-pointer">
                <div
                  class="w-10 h-6 rounded-full transition-colors duration-200 relative"
                  :class="department.status === 'active' ? 'bg-green-400' : 'bg-gray-200'"
                  @click="toggleStatus(department)"
                  style="cursor:pointer"
                >
                  <div
                    class="absolute top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200"
                    :class="department.status === 'active' ? 'translate-x-4 left-1' : 'left-1'"
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
      message="Yakin ingin menghapus department ini?"
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
