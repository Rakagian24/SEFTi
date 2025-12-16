<script setup lang="ts">
import { ref, computed, onMounted, onUnmounted } from 'vue';

const props = defineProps<{
  list: any;
  selectedIds?: number[];
}>();

const emit = defineEmits(['paginate', 'update:selectedIds']);

function goToPage(url: any) {
  emit('paginate', url);
  window.dispatchEvent(new CustomEvent('pagination-changed'));
  window.dispatchEvent(new CustomEvent('table-changed'));
}

function formatDate(dateString: string | null | undefined) {
  if (!dateString) return '-';
  try {
    const date = new Date(dateString);
    return date.toLocaleDateString('id-ID', {
      day: '2-digit',
      month: 'short',
      year: '2-digit'
    });
  } catch {
    return dateString;
  }
}

function formatCurrency(value: any) {
  const num = Number(value || 0);
  return num.toLocaleString('id-ID', {
    style: 'currency',
    currency: 'IDR',
    maximumFractionDigits: 0
  });
}

const rows = computed(() => props.list?.data || []);

const selectedIds = computed<number[]>(() => props.selectedIds || []);

function isRowSelected(id: number) {
  return selectedIds.value.includes(id);
}

function toggleRowSelection(id: number, checked: boolean) {
  const current = new Set(selectedIds.value);
  if (checked) {
    current.add(id);
  } else {
    current.delete(id);
  }
  emit('update:selectedIds', Array.from(current));
}

function areAllRowsSelected() {
  if (rows.value.length === 0) return false;
  return rows.value.every((row: any) => selectedIds.value.includes(row.id));
}

function toggleSelectAll(checked: boolean) {
  if (!checked) {
    // Unselect all rows on current page
    const remaining = selectedIds.value.filter(id => !rows.value.some((row: any) => row.id === id));
    emit('update:selectedIds', remaining);
  } else {
    // Select all rows on current page (keep other selections)
    const current = new Set(selectedIds.value);
    rows.value.forEach((row: any) => {
      current.add(row.id);
    });
    emit('update:selectedIds', Array.from(current));
  }
}

// Tooltip functionality untuk keterangan
const activeTooltip = ref<number | null>(null);

function toggleKeterangan(rowId: number, event: Event) {
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

function truncateText(text: string | null | undefined, maxLength: number = 50) {
  if (!text) return '-';
  return text.length > maxLength ? text.substring(0, maxLength) + '...' : text;
}

function hasKeterangan(text: string | null | undefined) {
  return text && text.trim() !== '';
}

// Handle table change event
function handleTableChange() {
  activeTooltip.value = null;
}

onMounted(() => {
  window.addEventListener('table-changed', handleTableChange);
});

onUnmounted(() => {
  window.removeEventListener('table-changed', handleTableChange);
});
</script>

<template>
  <div class="bg-white rounded-b-lg shadow-b-sm border-b border-gray-200">
    <div class="overflow-x-auto rounded-lg">
      <table class="min-w-full">
        <thead class="bg-[#FFFFFF] border-b border-gray-200">
          <tr>
            <th class="px-4 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              <input
                type="checkbox"
                :checked="areAllRowsSelected()"
                @change="toggleSelectAll(($event.target as HTMLInputElement).checked)"
              />
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Supplier
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Departemen
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Tanggal PV
            </th>
            <th class="px-6 py-4 text-center align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              No. PV
            </th>
            <th class="px-6 py-4 text-right align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Nominal
            </th>
            <th class="px-6 py-4 text-left align-middle text-xs font-bold text-[#101010] uppercase tracking-wider whitespace-nowrap">
              Keterangan
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <tr v-if="rows.length === 0">
            <td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">
              Tidak ada data. Silakan pilih filter dan data akan ditampilkan.
            </td>
          </tr>
          <tr
            v-for="row in rows"
            :key="row.id"
            class="alternating-row"
            @click="closeTooltip()"
          >
            <!-- Checkbox -->
            <td class="px-4 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              <input
                type="checkbox"
                :checked="isRowSelected(row.id)"
                @change.stop="toggleRowSelection(row.id, ($event.target as HTMLInputElement).checked)"
              />
            </td>

            <!-- Supplier -->
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.supplier || '-' }}
            </td>

            <!-- Departemen -->
            <td class="px-6 py-4 text-left align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ row.department || '-' }}
            </td>

            <!-- Tanggal PV -->
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm text-[#101010]">
              {{ formatDate(row.tanggal) }}
            </td>

            <!-- No. PV -->
            <td class="px-6 py-4 text-center align-middle whitespace-nowrap text-sm font-medium">
              <a
                :href="`/payment-voucher/${row.id}`"
                target="_blank"
                rel="noopener noreferrer"
                class="text-blue-600 hover:text-blue-800 hover:underline transition-colors duration-150"
              >
                {{ row.no_pv || '-' }}
              </a>
            </td>

            <!-- Nominal -->
            <td class="px-6 py-4 text-right align-middle whitespace-nowrap text-sm text-[#101010] font-medium tabular-nums">
              {{ formatCurrency(row.nominal) }}
            </td>

            <!-- Keterangan with Tooltip -->
            <td class="px-6 py-4 text-left align-middle text-sm text-[#101010] relative">
              <div class="flex items-center">
                <span class="inline-block max-w-[300px] truncate">
                  {{ truncateText(row.keterangan) }}
                </span>
                <button
                  v-if="hasKeterangan(row.keterangan)"
                  @click="toggleKeterangan(row.id, $event)"
                  class="ml-2 text-blue-600 hover:text-blue-800 focus:outline-none flex-shrink-0"
                  :title="activeTooltip === row.id ? 'Tutup keterangan lengkap' : 'Lihat keterangan lengkap'"
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
                    />
                  </svg>
                </button>
              </div>

              <!-- Floating tooltip untuk keterangan lengkap -->
              <div
                v-if="activeTooltip === row.id && hasKeterangan(row.keterangan)"
                class="absolute top-full left-0 mt-2 z-50 bg-white border border-gray-200 rounded-lg shadow-lg p-4 max-w-sm w-80"
                style="min-width: 300px"
              >
                <div class="flex items-start justify-between mb-2">
                  <h4 class="text-sm font-semibold text-gray-900">Keterangan Lengkap:</h4>
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
                      />
                    </svg>
                  </button>
                </div>
                <div class="bg-gray-50 rounded-md p-3 border border-gray-100">
                  <p
                    class="text-sm text-gray-700 leading-relaxed whitespace-pre-line select-text"
                  >
                    {{ row.keterangan }}
                  </p>
                </div>
                <!-- Arrow pointer -->
                <div
                  class="absolute -top-2 left-6 w-4 h-4 bg-white border-l border-t border-gray-200 transform rotate-45"
                ></div>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div class="bg-white px-6 py-4 flex items-center justify-center border-t border-gray-200 rounded-b-lg">
      <nav class="flex items-center space-x-2" aria-label="Pagination">
        <!-- Previous Button -->
        <button
          @click="goToPage(list?.prev_page_url)"
          :disabled="!list?.prev_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            list?.prev_page_url
              ? 'text-gray-600 hover:text-gray-800 hover:bg-gray-50'
              : 'text-gray-400 cursor-not-allowed',
          ]"
        >
          Previous
        </button>

        <!-- Page Numbers -->
        <template v-for="(link, index) in list?.links?.slice(1, -1)" :key="index">
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
          @click="goToPage(list?.next_page_url)"
          :disabled="!list?.next_page_url"
          :class="[
            'px-4 py-2 text-sm font-medium rounded-lg transition-colors duration-200',
            list?.next_page_url
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
