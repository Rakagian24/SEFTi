<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Failed PO Message Panel -->
      <div v-if="showFailedMessage" class="mb-6">
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 shadow-lg">
          <!-- Progress Bar for Auto-hide -->
          <div
            class="absolute top-0 left-0 h-1 bg-amber-300 rounded-t-lg transition-all duration-1000"
            :style="{ width: progressWidth + '%' }"
          ></div>

          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg class="h-6 w-6 text-amber-500" viewBox="0 0 20 20" fill="currentColor">
                <path
                  fill-rule="evenodd"
                  d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
            <div class="ml-3 flex-1">
              <div class="flex items-center justify-between mb-2">
                <h3 class="text-base font-semibold text-amber-800">
                  {{ failedMessageTitle }}
                </h3>
                <div class="flex items-center gap-2">
                  <span
                    class="text-xs text-amber-600 bg-amber-100 px-2 py-1 rounded-full"
                  >
                    Auto-hide dalam {{ countdownSeconds }}s
                  </span>
                  <button
                    @click="closeFailedMessage"
                    class="inline-flex text-amber-400 hover:text-amber-500 focus:outline-none focus:text-amber-500 transition-colors"
                  >
                    <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                      <path
                        fill-rule="evenodd"
                        d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                        clip-rule="evenodd"
                      />
                    </svg>
                  </button>
                </div>
              </div>

              <div class="text-sm text-amber-700 mb-4">
                <p>{{ failedMessageSummary }}</p>
              </div>

              <!-- Failed PO Details -->
              <div class="space-y-3">
                <div
                  v-for="po in failedPOs"
                  :key="po.id"
                  class="bg-white rounded-lg p-4 border border-amber-200 shadow-sm hover:shadow-md transition-shadow"
                >
                  <div class="flex items-start justify-between">
                    <div class="flex-1">
                      <div class="flex items-center gap-2 mb-2">
                        <div class="w-2 h-2 bg-amber-500 rounded-full"></div>
                        <div class="font-semibold text-amber-900 text-base">
                          {{ po.no_po || `PO #${po.id}` }}
                        </div>
                        <span
                          class="text-xs bg-amber-100 text-amber-800 px-2 py-1 rounded-full font-medium"
                        >
                          Belum Lengkap
                        </span>
                      </div>
                      <div class="text-amber-800 text-sm space-y-1.5 ml-4">
                        <div
                          v-for="(error, index) in po.errors"
                          :key="index"
                          class="flex items-start"
                        >
                          <span
                            class="w-1.5 h-1.5 bg-amber-400 rounded-full mr-2 mt-1.5 flex-shrink-0"
                          ></span>
                          <span>{{ error }}</span>
                        </div>
                      </div>
                    </div>
                    <button
                      @click="editFailedPO(po.id)"
                      class="ml-4 text-sm bg-amber-100 text-amber-800 px-3 py-2 rounded-md hover:bg-amber-200 transition-colors font-medium flex items-center gap-1"
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
                          d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                        />
                      </svg>
                      Edit
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="flex items-center justify-between mb-6">
        <div>
          <h1 class="text-2xl font-bold text-gray-900">Purchase Order</h1>
          <div class="flex items-center mt-2 text-sm text-gray-500">
            <CreditCard class="w-4 h-4 mr-1" />
            Manage Purchase Order data
          </div>
        </div>

        <div class="flex items-center gap-3">
          <div class="flex items-center gap-2">
            <button
              @click="sendSelected"
              :disabled="!canSend"
              class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              <Send class="w-4 h-4" />
              Kirim ({{ selected.length }})
            </button>
          </div>

          <button
            @click="goToAdd"
            class="flex items-center gap-2 px-4 py-2 bg-[#101010] text-white text-sm font-medium rounded-md hover:bg-white hover:text-[#101010] focus:outline-none focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v16m8-8H4"
              />
            </svg>
            Add New
          </button>
        </div>
      </div>

      <PurchaseOrderFilter
        :filters="filters"
        :departments="departments"
        :perihals="perihals"
        :columns="columns"
        :entries-per-page="filters.per_page || 10"
        @filter="applyFilters"
        @reset="resetFilters"
        @update:columns="updateColumns"
        @update:entries-per-page="updateEntriesPerPage"
      />

      <PurchaseOrderTable
        :data="purchaseOrders?.data || []"
        :pagination="purchaseOrders"
        :selected="selected"
        :columns="columns"
        @select="onSelect"
        @action="handleAction"
        @paginate="handlePagination"
        @add="goToAdd"
      />

      <!-- Debug info removed -->

      <!-- Confirm Delete Dialog -->
      <ConfirmDialog
        :show="showConfirmDialog"
        :message="
          confirmRow ? `Apakah Anda yakin ingin menghapus Purchase Order ini?` : ''
        "
        @confirm="confirmDelete"
        @cancel="cancelDelete"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import axios from "axios";
import PurchaseOrderTable from "../../components/purchase-orders/PurchaseOrderTable.vue";
import PurchaseOrderFilter from "../../components/purchase-orders/PurchaseOrderFilter.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { CreditCard, Send } from "lucide-vue-next";

interface Column {
  key: string;
  label: string;
  checked: boolean;
  sortable?: boolean;
}

const breadcrumbs = [{ label: "Home", href: "/dashboard" }, { label: "Purchase Order" }];

defineOptions({ layout: AppLayout });

const { addSuccess, addError } = useMessagePanel();

const props = defineProps<{
  purchaseOrders: any;
  filters: Record<string, any>;
  departments: any[];
  perihals: any[];
  columns: Column[];
}>();

// Watch for props changes (debug removed)

// Get page props for flash messages and session data
const page = usePage();

// Watch for flash messages and session data changes
watch(
  () => page.props,
  (newProps: any) => {
    // Handle success flash message
    if (newProps.flash?.success) {
      addSuccess(newProps.flash.success);
    }

    // Handle failed POs from session data
    if (
      newProps.failed_pos &&
      Array.isArray(newProps.failed_pos) &&
      newProps.failed_pos.length > 0
    ) {
      failedPOs.value = newProps.failed_pos;
      failedMessageTitle.value = "Beberapa Purchase Order Gagal Dikirim";

      const updatedCount = newProps.updated_pos?.length || 0;
      const failedCount = newProps.failed_pos.length;

      failedMessageSummary.value = `${updatedCount} PO berhasil dikirim, ${failedCount} PO gagal karena data belum lengkap. Silakan lengkapi data yang diperlukan terlebih dahulu sebelum mengirim ulang.`;
      showFailedMessageWithTimer();
    }
  },
  { immediate: false, deep: false }
);

// State untuk message panel failed PO
const showFailedMessage = ref(false);
const failedPOs = ref<any[]>([]);
const failedMessageTitle = ref("");
const failedMessageSummary = ref("");

// Contoh message yang bisa digunakan:
// - "Beberapa Purchase Order Gagal Dikirim"
// - "Data Purchase Order Belum Lengkap"
// - "Validasi Purchase Order Gagal"
// - "PO Draft Belum Memenuhi Syarat"

// Contoh summary yang bisa digunakan:
// - "X PO berhasil dikirim, Y PO gagal karena data belum lengkap. Silakan lengkapi data yang diperlukan terlebih dahulu sebelum mengirim ulang."
// - "X PO berhasil dikirim, Y PO gagal validasi. Pastikan semua field wajib telah diisi dengan benar."
// - "X PO berhasil dikirim, Y PO masih dalam status Draft. Lengkapi data yang diperlukan untuk melanjutkan proses."

const selected = ref<number[]>([]);
const canSend = computed(() => selected.value.length > 0);
const showConfirmDialog = ref(false);
const confirmRow = ref<any>(null);

const departments = ref(props.departments || []);
const perihals = ref(props.perihals || []);

// Local state for purchase orders data
const purchaseOrders = ref(
  props.purchaseOrders || { data: [], total: 0, current_page: 1, last_page: 1 }
);
const currentFilters = ref(props.filters || {});

// Default columns configuration
const defaultColumns: Column[] = [
  { key: "no_po", label: "No. PO", checked: true, sortable: true },
  { key: "no_invoice", label: "No. Invoice", checked: false, sortable: true },
  { key: "tipe_po", label: "Tipe PO", checked: false, sortable: false },
  { key: "tanggal", label: "Tanggal", checked: true, sortable: true },
  { key: "department", label: "Departemen", checked: true, sortable: false },
  { key: "perihal", label: "Perihal", checked: true, sortable: false },
  { key: "supplier", label: "Supplier", checked: false, sortable: false },
  {
    key: "metode_pembayaran",
    label: "Metode Pembayaran",
    checked: false,
    sortable: false,
  },
  { key: "total", label: "Total", checked: false, sortable: true },
  { key: "diskon", label: "Diskon", checked: false, sortable: true },
  { key: "ppn", label: "PPN", checked: false, sortable: true },
  { key: "pph", label: "PPH", checked: false, sortable: true },
  { key: "grand_total", label: "Grand Total", checked: true, sortable: true },
  { key: "status", label: "Status", checked: true, sortable: true },
  { key: "created_by", label: "Dibuat Oleh", checked: false, sortable: false },
  { key: "created_at", label: "Tanggal Dibuat", checked: false, sortable: true },
];

const columns = ref<Column[]>(props.columns || defaultColumns);

// Function to load purchase orders data
async function loadPurchaseOrders(params: Record<string, any> = {}) {
  try {
    const query = new URLSearchParams();

    // Add current filters
    Object.entries(currentFilters.value).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        query.set(key, String(value));
      }
    });

    // Add new params
    Object.entries(params).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== "") {
        query.set(key, String(value));
      }
    });

    const response = await axios.get(`/purchase-orders?${query.toString()}`, {
      headers: {
        Accept: "application/json",
        "Content-Type": "application/json",
      },
    });

    // Check if response is HTML (Inertia response)
    if (typeof response.data === "string" && response.data.includes("<!DOCTYPE html>")) {
      console.error(
        "Received HTML response instead of JSON. This might be an authentication issue."
      );
      return;
    }

    purchaseOrders.value = response.data;
  } catch (err: any) {
    console.error("Error loading purchase orders:", err);
    if (err && err.response) {
      console.error("Response status:", err.response.status);
      console.error("Response data:", err.response.data);
    }
  }
}

onMounted(() => {
  // Initialize columns from props or use defaults
  if (props.columns && props.columns.length > 0) {
    columns.value = props.columns;
  } else {
    columns.value = defaultColumns;
  }

  // Check for flash messages and session data on mount
  const pageProps = page.props as any;

  if (pageProps.flash?.success) {
    addSuccess(pageProps.flash.success);
  }

  if (
    pageProps.failed_pos &&
    Array.isArray(pageProps.failed_pos) &&
    pageProps.failed_pos.length > 0
  ) {
    failedPOs.value = pageProps.failed_pos;
    failedMessageTitle.value = "Beberapa Purchase Order Gagal Dikirim";

    const updatedCount = pageProps.updated_pos?.length || 0;
    const failedCount = pageProps.failed_pos.length;

    failedMessageSummary.value = `${updatedCount} PO berhasil dikirim, ${failedCount} PO gagal karena data belum lengkap. Silakan lengkapi data yang diperlukan terlebih dahulu sebelum mengirim ulang.`;
    showFailedMessageWithTimer();
  }
});

function applyFilters(payload: Record<string, any>) {
  // Update current filters
  currentFilters.value = {
    ...currentFilters.value,
    ...payload,
    per_page: payload.entriesPerPage || currentFilters.value.per_page || 10,
  };

  // Load data with new filters
  loadPurchaseOrders();
}

function resetFilters() {
  // Reset columns to defaults when resetting filters
  columns.value = [...defaultColumns];
  currentFilters.value = { per_page: 10, columns: JSON.stringify(columns.value) };
  loadPurchaseOrders();
}

function updateColumns(newColumns: Column[]) {
  columns.value = newColumns;
  currentFilters.value.columns = JSON.stringify(newColumns);
  loadPurchaseOrders();
}

function updateEntriesPerPage(newPerPage: number) {
  currentFilters.value.per_page = newPerPage;
  loadPurchaseOrders();
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split("?")[1]);
  const page = urlParams.get("page");
  if (page) {
    currentFilters.value.page = page;
    loadPurchaseOrders();
  }
}

function onSelect(newSelected: number[]) {
  selected.value = newSelected;
}

function handleAction(payload: { action: string; row: any }) {
  const { action, row } = payload;
  if (action === "edit") router.visit(`/purchase-orders/${row.id}/edit`);
  if (action === "delete") {
    confirmRow.value = row;
    showConfirmDialog.value = true;
  }
  if (action === "detail") router.visit(`/purchase-orders/${row.id}`);
  if (action === "log") router.visit(`/purchase-orders/${row.id}/log`);
  if (action === "download") window.open(`/purchase-orders/${row.id}/download`, "_blank");
}

function confirmDelete() {
  if (confirmRow.value) {
    router.delete(`/purchase-orders/${confirmRow.value.id}`, {
      onSuccess: () => addSuccess("Purchase Order berhasil dibatalkan"),
    });
  }
  cancelDelete();
}

function cancelDelete() {
  showConfirmDialog.value = false;
  confirmRow.value = null;
}

function sendSelected() {
  if (!canSend.value) return;
  router.post(
    "/purchase-orders/send",
    { ids: selected.value },
    {
      onSuccess: () => {
        // Success message will be handled by flash messages
        // Failed POs will be handled by session data
        selected.value = [];
        // Reload data so the table reflects latest statuses without manual refresh
        loadPurchaseOrders();
      },
      onError: () => addError("Terjadi kesalahan saat mengirim Purchase Order"),
      preserveScroll: true,
    }
  );
}

function goToAdd() {
  router.visit("/purchase-orders/create");
}

// Function untuk edit PO yang gagal
function editFailedPO(poId: number) {
  router.visit(`/purchase-orders/${poId}/edit`);
}

// Function untuk menutup message panel failed PO
function closeFailedMessage() {
  showFailedMessage.value = false;
  failedPOs.value = [];
  failedMessageTitle.value = "";
  failedMessageSummary.value = "";
}

// Auto-hide message panel after 10 seconds
function showFailedMessageWithTimer() {
  showFailedMessage.value = true;
  progressWidth.value = 0;
  countdownSeconds.value = 10;

  const totalSeconds = 10;
  const interval = setInterval(() => {
    countdownSeconds.value--;
    progressWidth.value = ((totalSeconds - countdownSeconds.value) / totalSeconds) * 100;

    if (countdownSeconds.value <= 0) {
      clearInterval(interval);
      closeFailedMessage();
    }
  }, 1000);
}

// State untuk countdown dan progress bar
const progressWidth = ref(0);
const countdownSeconds = ref(10);
</script>
