<script setup lang="ts">
import EmptyState from "../ui/EmptyState.vue";

defineProps({ items: Object });
const emit = defineEmits(["edit", "delete", "paginate", "toggleStatus", "add"]);

function editRow(row: any) { emit("edit", row); }
function deleteRow(row: any) { emit("delete", row); }
function toggleStatus(row: any) { emit("toggleStatus", row); }
function goToPage(url: string) { emit("paginate", url); window.dispatchEvent(new CustomEvent("pagination-changed")); }
function handleAdd() { emit("add"); }
</script>

<template>
  <EmptyState
    v-if="!items?.data || items.data.length === 0"
    title="No Barang found"
    description="Belum ada data. Tambah Barang pertama Anda."
    icon="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
    action-text="Add Barang"
    :show-action="true"
    @action="handleAdd"
  />

  <div v-else class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Nama Barang</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Jenis</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Satuan</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Department</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Status</th>
            <th class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">Toggle</th>
            <th class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in items?.data" :key="row.id" class="alternating-row">
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">{{ row.nama_barang }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">{{ row.jenis_barang?.nama_jenis_barang || row.jenisBarang?.nama_jenis_barang || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">{{ row.satuan || '-' }}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
              {{ Array.isArray(row.departments) && row.departments.length
                ? row.departments.map((d:any) => d?.name || d?.label || '').filter((n:string) => n).join(', ')
                : '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <span :class="['inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium', row.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800']">
                {{ row.status === 'active' ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
              <label class="inline-flex items-center cursor-pointer">
                <div class="w-10 h-6 rounded-full transition-colors duration-200 relative" :class="row.status === 'active' ? 'bg-green-400' : 'bg-gray-200'" @click="toggleStatus(row)" style="cursor:pointer">
                  <div class="absolute top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200" :class="row.status === 'active' ? 'translate-x-4 left-1' : 'left-1'"></div>
                </div>
              </label>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell">
              <div class="flex items-center justify-center space-x-2">
                <button @click="editRow(row)" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-blue-50 hover:bg-blue-100 transition-colors duration-200" title="Edit">
                  <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button @click="deleteRow(row)" class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-red-50 hover:bg-red-100 transition-colors duration-200" title="Hapus">
                  <svg class="w-4 h-4 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <button @click="goToPage(items?.prev_page_url)" :disabled="!items?.prev_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', items?.prev_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">Previous</button>
        <template v-for="(link, index) in items?.links?.slice(1, -1)" :key="index">
          <button @click="goToPage(link.url)" :disabled="!link.url" :class="['w-10 h-10 text-sm font-medium rounded-lg transition-colors duration-200', link.active ? 'bg-black text-white' : link.url ? 'bg-gray-200 text-gray-600 hover:bg-gray-300' : 'bg-gray-200 text-gray-400 cursor-not-allowed']" v-html="link.label"></button>
        </template>
        <button @click="goToPage(items?.next_page_url)" :disabled="!items?.next_page_url" :class="['px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200', items?.next_page_url ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50' : 'text-gray-400 cursor-not-allowed']">Next</button>
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
