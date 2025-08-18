<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header Section -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-8">
        <div class="px-6 py-6 border-b border-gray-200">
          <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <div class="flex-1">
              <div class="flex items-center gap-3 mb-2">
                <div class="p-2 bg-blue-100 rounded-lg">
                  <CreditCard class="w-6 h-6 text-blue-600" />
                </div>
                <div>
                  <h1 class="text-3xl font-bold text-gray-900">Purchase Order Detail</h1>
                  <p class="text-lg text-gray-600 mt-1">{{ purchaseOrder.no_po }}</p>
                </div>
              </div>
              <div class="mt-4">
                <span
                  :class="getStatusBadgeClass(purchaseOrder.status)"
                  class="inline-flex items-center px-4 py-2 rounded-full text-sm font-semibold shadow-sm"
                >
                  <div
                    class="w-2 h-2 rounded-full mr-2"
                    :class="getStatusDotClass(purchaseOrder.status)"
                  ></div>
                  {{ purchaseOrder.status }}
                </span>
              </div>
            </div>

            <div class="flex items-center gap-3 flex-shrink-0">
              <button
                v-if="purchaseOrder.status === 'Draft'"
                @click="goToEdit"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow"
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
                Edit PO
              </button>

              <button
                @click="downloadPO"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-purple-600 text-white text-sm font-medium rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow"
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
                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                  />
                </svg>
                Download PDF
              </button>

              <button
                @click="goBack"
                class="inline-flex items-center gap-2 px-4 py-2.5 bg-gray-600 text-white text-sm font-medium rounded-lg hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200 shadow-sm hover:shadow"
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
                    d="M10 19l-7-7m0 0l7-7m-7 7h18"
                  />
                </svg>
                Back to List
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
        <!-- Main Content -->
        <div class="xl:col-span-2 space-y-8">
          <!-- Basic & Payment Information -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
              <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <div class="p-1.5 bg-indigo-100 rounded-lg">
                  <svg
                    class="w-5 h-5 text-indigo-600"
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
                </div>
                Purchase Order Information
              </h2>
            </div>
            <div class="p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                <!-- Basic Information -->
                <div class="space-y-4">
                  <h3
                    class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-100"
                  >
                    Basic Details
                  </h3>
                  <div class="space-y-3">
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500">PO Number</span>
                      <span class="text-sm font-semibold text-gray-900">{{
                        purchaseOrder.no_po || "-"
                      }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500">PO Type</span>
                      <span class="text-sm text-gray-900">{{
                        purchaseOrder.tipe_po || "-"
                      }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500">Department</span>
                      <span class="text-sm text-gray-900">{{
                        purchaseOrder.department?.name || "-"
                      }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500">Subject</span>
                      <span class="text-sm text-gray-900">{{
                        purchaseOrder.perihal?.nama || "-"
                      }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500">Date</span>
                      <span class="text-sm text-gray-900">{{
                        formatDate(purchaseOrder.tanggal)
                      }}</span>
                    </div>
                  </div>
                </div>

                <!-- Payment Information -->
                <div class="space-y-4">
                  <h3
                    class="text-lg font-medium text-gray-900 mb-4 pb-2 border-b border-gray-100"
                  >
                    Payment Details
                  </h3>
                  <div class="space-y-3">
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500"
                        >Payment Method</span
                      >
                      <span class="text-sm text-gray-900">{{
                        purchaseOrder.metode_pembayaran || "-"
                      }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500">Bank</span>
                      <span class="text-sm text-gray-900">{{
                        purchaseOrder.bank?.nama_bank || "-"
                      }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500">Account Name</span>
                      <span class="text-sm text-gray-900">{{
                        purchaseOrder.nama_rekening || "-"
                      }}</span>
                    </div>
                    <div class="flex items-center justify-between py-2">
                      <span class="text-sm font-medium text-gray-500"
                        >Account Number</span
                      >
                      <span class="text-sm font-mono text-gray-900">{{
                        purchaseOrder.no_rekening || "-"
                      }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
              <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <div class="p-1.5 bg-green-100 rounded-lg">
                  <svg
                    class="w-5 h-5 text-green-600"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                </div>
                Order Items
                <span
                  class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full"
                >
                  {{ purchaseOrder.items?.length || 0 }} items
                </span>
              </h2>
            </div>
            <div class="overflow-hidden">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        #
                      </th>
                      <th
                        class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Item Name
                      </th>
                      <th
                        class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Quantity
                      </th>
                      <th
                        class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Unit
                      </th>
                      <th
                        class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Unit Price
                      </th>
                      <th
                        class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Subtotal
                      </th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="(item, index) in purchaseOrder.items"
                      :key="index"
                      class="hover:bg-gray-50 transition-colors"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">{{
                          index + 1
                        }}</span>
                      </td>
                      <td class="px-6 py-4">
                        <span class="text-sm font-medium text-gray-900">{{
                          item.nama || item.nama_barang || "-"
                        }}</span>
                      </td>
                      <td class="px-6 py-4 text-center">
                        <span class="text-sm text-gray-900">{{ item.qty || 1 }}</span>
                      </td>
                      <td class="px-6 py-4 text-center">
                        <span class="text-sm text-gray-600">{{
                          item.satuan || "-"
                        }}</span>
                      </td>
                      <td class="px-6 py-4 text-right">
                        <span class="text-sm font-medium text-gray-900">{{
                          formatCurrency(item.harga || 0)
                        }}</span>
                      </td>
                      <td class="px-6 py-4 text-right">
                        <span class="text-sm font-semibold text-gray-900">{{
                          formatCurrency((item.qty || 1) * (item.harga || 0))
                        }}</span>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Additional Information -->
          <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="px-6 py-5 border-b border-gray-200">
              <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                <div class="p-1.5 bg-orange-100 rounded-lg">
                  <svg
                    class="w-5 h-5 text-orange-600"
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
                </div>
                Additional Information
              </h2>
            </div>
            <div class="p-6 space-y-6">
              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2"
                  >Requirements Detail</label
                >
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-900 leading-relaxed">
                    {{
                      purchaseOrder.detail_keperluan ||
                      "No additional requirements specified."
                    }}
                  </p>
                </div>
              </div>

              <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2"
                  >Notes</label
                >
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-900 leading-relaxed">
                    {{
                      purchaseOrder.keterangan ||
                      purchaseOrder.note ||
                      "No additional notes."
                    }}
                  </p>
                </div>
              </div>

              <div v-if="purchaseOrder.dokumen">
                <label class="block text-sm font-semibold text-gray-700 mb-2"
                  >Attached Document</label
                >
                <div
                  class="flex items-center gap-3 p-4 bg-blue-50 rounded-lg border border-blue-200"
                >
                  <svg
                    class="w-8 h-8 text-blue-600"
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
                  <div class="flex-1">
                    <a
                      :href="'/storage/' + purchaseOrder.dokumen"
                      target="_blank"
                      class="text-sm font-medium text-blue-600 hover:text-blue-800 underline"
                    >
                      {{ purchaseOrder.dokumen.split("/").pop() }}
                    </a>
                    <p class="text-xs text-gray-500 mt-1">Click to view document</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Sidebar with Summary -->
        <div class="xl:col-span-1">
          <div class="sticky top-8">
            <div class="bg-white rounded-xl shadow-sm border border-gray-200">
              <div class="px-6 py-5 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 flex items-center gap-2">
                  <div class="p-1.5 bg-purple-100 rounded-lg">
                    <svg
                      class="w-5 h-5 text-purple-600"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"
                      />
                    </svg>
                  </div>
                  Order Summary
                </h2>
              </div>
              <div class="p-6">
                <div class="space-y-4">
                  <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-600">Subtotal</span>
                    <span class="text-sm font-medium text-gray-900">{{
                      formatCurrency(calculateTotal())
                    }}</span>
                  </div>

                  <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-600">Discount</span>
                    <span class="text-sm font-medium text-red-600"
                      >-{{ formatCurrency(purchaseOrder.diskon || 0) }}</span
                    >
                  </div>

                  <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-600">PPN (11%)</span>
                    <span class="text-sm font-medium text-gray-900">{{
                      formatCurrency(calculatePPN())
                    }}</span>
                  </div>

                  <div class="flex justify-between items-center py-2">
                    <span class="text-sm text-gray-600">PPH</span>
                    <span class="text-sm font-medium text-gray-900">{{
                      formatCurrency(calculatePPH())
                    }}</span>
                  </div>

                  <div class="border-t border-gray-200 pt-4">
                    <div class="flex justify-between items-center">
                      <span class="text-lg font-semibold text-gray-900">Grand Total</span>
                      <span class="text-lg font-bold text-gray-900">{{
                        formatCurrency(calculateGrandTotal())
                      }}</span>
                    </div>
                  </div>
                </div>

                <div class="mt-6 pt-6 border-t border-gray-200">
                  <div class="text-center">
                    <p class="text-xs text-gray-500 mb-2">Total Amount</p>
                    <p class="text-2xl font-bold text-indigo-600">
                      {{ formatCurrency(calculateGrandTotal()) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref } from "vue";
import { router } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { CreditCard } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Purchase Order", href: "/purchase-orders" },
  { label: "Detail" },
];

const props = defineProps<{
  purchaseOrder: any;
}>();

const purchaseOrder = ref(props.purchaseOrder);

function formatDate(date: string | null) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function getStatusBadgeClass(status: string) {
  const statusClasses = {
    Draft: "bg-gray-100 text-gray-800 border border-gray-300",
    "In Progress": "bg-blue-100 text-blue-800 border border-blue-300",
    Approved: "bg-green-100 text-green-800 border border-green-300",
    Rejected: "bg-red-100 text-red-800 border border-red-300",
    Completed: "bg-purple-100 text-purple-800 border border-purple-300",
  };
  return (
    statusClasses[status as keyof typeof statusClasses] ||
    "bg-gray-100 text-gray-800 border border-gray-300"
  );
}

function getStatusDotClass(status: string) {
  const dotClasses = {
    Draft: "bg-gray-500",
    "In Progress": "bg-blue-500",
    Approved: "bg-green-500",
    Rejected: "bg-red-500",
    Completed: "bg-purple-500",
  };
  return dotClasses[status as keyof typeof dotClasses] || "bg-gray-500";
}

function calculateTotal() {
  if (!purchaseOrder.value.items || purchaseOrder.value.items.length === 0) {
    return purchaseOrder.value.harga || 0;
  }
  return purchaseOrder.value.items.reduce((sum: number, item: any) => {
    return sum + (item.qty || 1) * (item.harga || 0);
  }, 0);
}

function calculatePPN() {
  const total = calculateTotal();
  const diskon = purchaseOrder.value.diskon || 0;
  const dpp = Math.max(total - diskon, 0);
  return purchaseOrder.value.ppn ? dpp * 0.11 : 0;
}

function calculatePPH() {
  const total = calculateTotal();
  const diskon = purchaseOrder.value.diskon || 0;
  const dpp = Math.max(total - diskon, 0);

  // Prefer using stored nominal from backend if available
  const storedNominal = Number((purchaseOrder.value as any).pph_nominal);
  if (!isNaN(storedNominal)) {
    return storedNominal;
  }

  // Fallback: compute from related PPH tarif if relation is present
  if (purchaseOrder.value.pph_id) {
    const relatedPph = (purchaseOrder.value as any).pph;
    if (relatedPph && typeof relatedPph.tarif_pph === "number") {
      return dpp * (relatedPph.tarif_pph / 100);
    }
  }

  return 0;
}

function calculateGrandTotal() {
  const total = calculateTotal();
  const diskon = purchaseOrder.value.diskon || 0;
  const ppn = calculatePPN();
  const pph = calculatePPH();
  return total - diskon + ppn + pph;
}

function downloadPO() {
  window.open(`/purchase-orders/${purchaseOrder.value.id}/download`, "_blank");
}

function goToEdit() {
  router.visit(`/purchase-orders/${purchaseOrder.value.id}/edit`);
}

function goBack() {
  router.visit("/purchase-orders");
}
</script>
