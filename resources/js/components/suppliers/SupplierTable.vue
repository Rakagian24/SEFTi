<script setup lang="ts">
defineProps({ suppliers: Object });
const emit = defineEmits(["edit", "delete", "detail", "log", "paginate"]);

function editRow(row: any) {
  emit("edit", row);
}

function deleteRow(row: any) {
  emit("delete", row);
}

function detailRow(row: any) {
  emit("detail", row);
}

function logRow(row: any) {
  emit("log", row);
}

function goToPage(url: string) {
  emit("paginate", url);
  window.dispatchEvent(new CustomEvent("pagination-changed"));
  window.dispatchEvent(new CustomEvent("table-changed"));
}

// Helper function to get primary bank account
function getPrimaryBankAccount(row: any) {
  if (row.bank_1 && row.nama_rekening_1 && row.no_rekening_1) {
    return {
      bank: row.bank_1,
      nama_rekening: row.nama_rekening_1,
      no_rekening: row.no_rekening_1,
    };
  }
  return null;
}
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Nama Supplier
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Alamat
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Email
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              No. Telepon
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Nama Bank
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Nama Rekening
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              No. Rekening/VA
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Terms of Payment
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold [#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]"
            >
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-for="row in suppliers?.data" :key="row.id" class="alternating-row">
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
              {{ row.nama_supplier }}
            </td>
            <td class="px-6 py-4 text-sm [#101010] max-w-xs">
              <div class="truncate" :title="row.alamat">
                {{ row.alamat }}
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-blue-600">
              {{ row.email || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
              {{ row.no_telepon || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
              {{ getPrimaryBankAccount(row)?.bank || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
              {{ getPrimaryBankAccount(row)?.nama_rekening || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
              {{ getPrimaryBankAccount(row)?.no_rekening || '-' }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm [#101010]">
              {{ row.terms_of_payment || '-' }}
            </td>
            <td
              class="px-6 py-4 whitespace-nowrap text-center sticky right-0 action-cell"
            >
              <div class="flex items-center justify-center space-x-2">
                <!-- Edit Button -->
                <button
                  @click="editRow(row)"
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
                  @click="deleteRow(row)"
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

                <!-- Detail Button -->
                <button
                  @click="detailRow(row)"
                  class="inline-flex items-center justify-center w-8 h-8 rounded-md bg-green-50 hover:bg-green-100 transition-colors duration-200"
                  title="Detail"
                >
                  <svg
                    class="w-4 h-4 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>

                <!-- Log Activity Button -->
                <button
                  @click="logRow(row)"
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
    </div>

    <!-- Pagination -->
    <div
      class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
    >
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="goToPage(suppliers?.prev_page_url)"
          :disabled="!suppliers?.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            suppliers?.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <!-- Page Numbers -->
        <template
          v-for="(link, index) in suppliers?.links?.slice(1, -1)"
          :key="index"
        >
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

        <!-- Next Button -->
        <button
          @click="goToPage(suppliers?.next_page_url)"
          :disabled="!suppliers?.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            suppliers?.next_page_url
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
