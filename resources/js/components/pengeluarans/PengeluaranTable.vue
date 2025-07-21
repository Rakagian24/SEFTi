<script setup lang="ts">
import DialogContent from "../ui/dialog/DialogContent.vue";
import { ref } from "vue";
import ConfirmDialog from "../ui/ConfirmDialog.vue";
import { router } from "@inertiajs/vue3";

defineProps({ pengeluarans: Object });
const emit = defineEmits(["edit", "delete", "detail", "paginate", "log"]);

function editRow(row: any) {
  emit("edit", row);
}

function logRow(row: any) {
  emit("log", row);
}

function goToPage(url: string) {
  emit("paginate", url);
  // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
  window.dispatchEvent(new CustomEvent("pagination-changed"));
}

const showDeskripsiDialog = ref(false);
const deskripsiDetail = ref("");

// Tooltip functionality (seperti di ArPartnerTable)
const activeTooltip = ref(null);

// Fungsi untuk toggle deskripsi tooltip
function toggleDeskripsi(rowId: any, event: Event) {
  event.stopPropagation();
  if (activeTooltip.value === rowId) {
    activeTooltip.value = null;
  } else {
    activeTooltip.value = rowId;
  }
}

// Fungsi untuk menutup tooltip
function closeTooltip() {
  activeTooltip.value = null;
}

// Fungsi untuk memotong teks deskripsi
function truncateText(text: string, maxLength: number = 50) {
  if (!text) return "-";
  return text.length > maxLength ? text.substring(0, maxLength) + "..." : text;
}

// Fungsi untuk mengecek apakah ada deskripsi (tidak kosong)
function hasDescription(text: string) {
  return text && text.trim() !== "";
}

const showConfirm = ref(false);
const confirmRow = ref<any>(null);

function askDeleteRow(row: any) {
  confirmRow.value = row;
  showConfirm.value = true;
}
function onConfirmDelete() {
  emit("delete", confirmRow.value);
  showConfirm.value = false;
  confirmRow.value = null;
}
function onCancelDelete() {
  showConfirm.value = false;
  confirmRow.value = null;
}

function toggleStatus(row: any) {
  router.put(
    `/pengeluarans/${row.id}/toggle-status`,
    {},
    {
      onSuccess: () => {
        window.dispatchEvent(new CustomEvent("table-changed"));
      },
    }
  );
}
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Nama Pengeluaran
            </th>
            <th
              class="px-6 py-4 text-left text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Deskripsi
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Status
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap"
            >
              Toggle
            </th>
            <th
              class="px-6 py-4 text-center text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap sticky right-0 bg-[#FFFFFF]"
            >
              Action
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr
            v-for="row in pengeluarans?.data"
            :key="row.id"
            class="alternating-row"
            @click="closeTooltip()"
          >
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 font-medium">
              {{ row.nama }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-[#101010] relative">
              <div class="flex items-center">
                <span class="inline-block max-w-[200px] truncate">
                  {{ truncateText(row.deskripsi) }}
                </span>
                <button
                  v-if="hasDescription(row.deskripsi)"
                  @click="toggleDeskripsi(row.id, $event)"
                  class="ml-2 text-blue-600 hover:text-blue-800 focus:outline-none flex-shrink-0"
                  :title="
                    activeTooltip === row.id
                      ? 'Tutup deskripsi lengkap'
                      : 'Lihat deskripsi lengkap'
                  "
                >
                  <svg
                    class="w-4 h-4 transform transition-transform duration-200"
                    :class="{ 'rotate-180': activeTooltip === row.id }"
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

              <!-- Floating tooltip untuk deskripsi lengkap -->
              <div
                v-if="activeTooltip === row.id && hasDescription(row.deskripsi)"
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
                    <svg
                      class="w-4 h-4"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M6 18L18 6M6 6l12 12"
                      ></path>
                    </svg>
                  </button>
                </div>
                <div class="bg-gray-50 rounded-md p-3 border border-gray-100">
                  <p
                    class="text-sm text-gray-700 leading-relaxed whitespace-pre-line select-text"
                  >
                    {{ row.deskripsi }}
                  </p>
                </div>
                <!-- Arrow pointer -->
                <div
                  class="absolute -top-2 left-6 w-4 h-4 bg-white border-l border-t border-gray-200 transform rotate-45"
                ></div>
              </div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <span
                :class="[
                  'inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium',
                  row.status === 'active'
                    ? 'bg-green-100 text-green-800'
                    : 'bg-red-100 text-red-800',
                ]"
              >
                {{ row.status === "active" ? "Active" : "Inactive" }}
              </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
              <label class="inline-flex items-center cursor-pointer ml-2">
                <div
                  class="w-10 h-6 rounded-full transition-colors duration-200 relative"
                  :class="row.status === 'active' ? 'bg-green-400' : 'bg-gray-200'"
                  @click.stop="toggleStatus(row)"
                  style="cursor: pointer"
                >
                  <div
                    class="absolute top-1 bg-white w-4 h-4 rounded-full transition-transform duration-200"
                    :class="row.status === 'active' ? 'translate-x-4 left-1' : 'left-1'"
                  ></div>
                </div>
              </label>
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
                  @click="askDeleteRow(row)"
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
                  @click.stop="logRow(row)"
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
      <div v-if="!pengeluarans?.data?.length" class="text-center py-12">
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
            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
          />
        </svg>
        <h3 class="mt-2 text-sm font-medium text-gray-900">Tidak ada data pengeluaran</h3>
        <p class="mt-1 text-sm text-[#101010]">
          Mulai dengan menambahkan data pengeluaran baru.
        </p>
      </div>
    </div>

    <!-- Pagination - Simple centered design -->
    <div
      v-if="pengeluarans?.data?.length"
      class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg"
    >
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="goToPage(pengeluarans?.prev_page_url)"
          :disabled="!pengeluarans?.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pengeluarans?.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <!-- Page Numbers -->
        <template v-for="(link, index) in pengeluarans?.links?.slice(1, -1)" :key="index">
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
          @click="goToPage(pengeluarans?.next_page_url)"
          :disabled="!pengeluarans?.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            pengeluarans?.next_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Next
        </button>
      </nav>
    </div>
  </div>

  <DialogContent v-if="showDeskripsiDialog" @close="showDeskripsiDialog = false">
    <div class="text-lg font-bold mb-2">Deskripsi Lengkap</div>
    <textarea
      class="w-full border rounded p-2"
      rows="5"
      readonly
      :value="deskripsiDetail"
    ></textarea>
  </DialogContent>

  <ConfirmDialog
    :show="showConfirm"
    message="Apakah Anda yakin ingin menghapus data pengeluaran ini?"
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
