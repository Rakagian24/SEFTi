<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              Detail Purchase Order (Approval)
            </h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <CreditCard class="w-4 h-4 mr-1" />
              {{ purchaseOrder.no_po }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <span
            :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(
              purchaseOrder.status
            )}`"
          >
            <div
              class="w-2 h-2 rounded-full mr-2 inline-block"
              :class="getStatusDotClass(purchaseOrder.status)"
            ></div>
            {{ purchaseOrder.status }}
          </span>
        </div>
      </div>

      <!-- Rejection Reason Alert -->
      <div
        v-if="purchaseOrder.status === 'Rejected' && purchaseOrder.rejection_reason"
        class="mb-6"
      >
        <div class="bg-red-50 border border-red-200 rounded-lg p-4">
          <div class="flex items-start">
            <div class="flex-shrink-0">
              <svg
                class="w-5 h-5 text-red-400"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"
                />
              </svg>
            </div>
            <div class="ml-3">
              <h3 class="text-sm font-medium text-red-800">Alasan Penolakan</h3>
              <div class="mt-2 text-sm text-red-700">
                <p>{{ purchaseOrder.rejection_reason }}</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Main Content -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Main Info -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Basic Information Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg
                class="w-5 h-5 text-gray-600"
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
              <h3 class="text-lg font-semibold text-gray-900">
                Informasi Purchase Order
              </h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Purchase Order</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_po || "-" }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
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
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tipe PO</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.tipe_po || "-" }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.department?.name || "-" }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 4h6m-6 0a2 2 0 00-2 2v8a2 2 0 002 2h8a2 2 0 002-2v-8a2 2 0 00-2-2h-2"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Perihal</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.perihal?.nama || "-" }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
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
                  <div>
                    <p class="text-sm font-medium text-gray-900">Supplier</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.supplier?.nama_supplier || "-" }}
                    </p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-6 4h6m-6 0a1 1 0 00-1 1v4a1 1 0 001 1h6a1 1 0 001-1v-4a1 1 0 00-1-1"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">
                      {{ formatDate(purchaseOrder.tanggal) }}
                    </p>
                  </div>
                </div>

                <div
                  v-if="purchaseOrder.tipe_po === 'Reguler'"
                  class="flex items-start gap-3"
                >
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
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
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Invoice</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_invoice || "-" }}
                    </p>
                  </div>
                </div>

                <div
                  v-if="purchaseOrder.tipe_po === 'Lainnya'"
                  class="flex items-start gap-3"
                >
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
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
                  <div>
                    <p class="text-sm font-medium text-gray-900">No Ref Termin</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.termin?.no_referensi || "-" }}
                    </p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.metode_pembayaran || "-" }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Items Table -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg
                class="w-5 h-5 text-gray-600"
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
              <h3 class="text-lg font-semibold text-gray-900">Item Pesanan</h3>
              <span
                class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full"
                >{{ purchaseOrder.items?.length || 0 }} item</span
              >
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
                        Nama Item
                      </th>
                      <th
                        class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Jumlah
                      </th>
                      <th
                        class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Satuan
                      </th>
                      <th
                        class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider"
                      >
                        Harga Satuan
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
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg
                class="w-5 h-5 text-gray-600"
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
              <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
            </div>

            <div class="space-y-6">
              <div>
                <p class="text-sm font-medium text-gray-900 mb-2">Detail Keperluan</p>
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
                <p class="text-sm font-medium text-gray-900 mb-2">Catatan</p>
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
                <p class="text-sm font-medium text-gray-900 mb-2">Dokumen Terlampir</p>
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
                      >{{ purchaseOrder.dokumen.split("/").pop() }}</a
                    >
                    <p class="text-xs text-gray-500 mt-1">Click to view document</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Summary & Metadata -->
        <div class="space-y-6">
          <!-- Approval Progress -->
          <ApprovalProgress
            :progress="approvalProgress"
            :purchase-order="purchaseOrder"
            :user-role="userRole"
            :can-verify="canVerify"
            :can-validate="canValidate"
            :can-approve="canApprove"
            :can-reject="canReject"
            @verify="handleVerify"
            @validate="handleValidate"
            @approve="handleApprove"
            @reject="handleRejectClick"
          />
          <!-- Payment Information Card (moved to right column) -->
          <div
            v-if="purchaseOrder.metode_pembayaran"
            class="bg-white rounded-lg shadow-sm border border-gray-200 p-6"
          >
            <div class="flex items-center gap-2 mb-4">
              <svg
                class="w-5 h-5 text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Pembayaran</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <template v-if="purchaseOrder.metode_pembayaran === 'Transfer'">
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
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
                  <div>
                    <p class="text-sm font-medium text-gray-900">Supplier</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.supplier?.nama_supplier || "-" }}
                    </p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Bank</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.bank?.nama_bank || "-" }}
                    </p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nama Rekening</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.nama_rekening || "-" }}
                    </p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Rekening/VA</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_rekening || "-" }}
                    </p>
                  </div>
                </div>
              </template>

              <template v-if="purchaseOrder.metode_pembayaran === 'Cek/Giro'">
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
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
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Cek/Giro</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_giro || "-" }}
                    </p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-6 4h6m-6 0a1 1 0 00-1 1v4a1 1 0 001 1h6a1 1 0 001-1v-4a1 1 0 00-1-1"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal Giro</p>
                    <p class="text-sm text-gray-600">
                      {{ formatDate(purchaseOrder.tanggal_giro) }}
                    </p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-6 4h6m-6 0a1 1 0 00-1 1v4a1 1 0 001 1h6a1 1 0 001-1v-4a1 1 0 00-1-1"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal Cair</p>
                    <p class="text-sm text-gray-600">
                      {{ formatDate(purchaseOrder.tanggal_cair) }}
                    </p>
                  </div>
                </div>
              </template>

              <template v-if="purchaseOrder.metode_pembayaran === 'Kredit'">
                <div class="flex items-start gap-3">
                  <svg
                    class="w-5 h-5 text-gray-400 mt-0.5"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Kartu Kredit</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_kartu_kredit || "-" }}
                    </p>
                  </div>
                </div>
              </template>
            </div>
          </div>

          <!-- Termin Summary (for PO Lainnya) -->
          <TerminSummary
            v-if="purchaseOrder.tipe_po === 'Lainnya' && purchaseOrder.termin"
            :termin-data="purchaseOrder.termin"
          />

          <!-- Order Summary Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg
                class="w-5 h-5 text-gray-600"
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
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan Pesanan</h3>
            </div>

            <div class="space-y-4">
              <!-- Subtotal, Diskon, PPN, PPH - ditampilkan untuk semua tipe -->
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Subtotal</span>
                <span class="text-sm font-medium text-gray-900">{{
                  formatCurrency(purchaseOrder.total || 0)
                }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Diskon</span>
                <span class="text-sm font-medium text-red-600"
                  >-{{ formatCurrency(purchaseOrder.diskon || 0) }}</span
                >
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">PPN (11%)</span>
                <span class="text-sm font-medium text-gray-900">{{
                  formatCurrency(purchaseOrder.ppn_nominal || 0)
                }}</span>
              </div>
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">PPH</span>
                <span class="text-sm font-medium text-gray-900">{{
                  formatCurrency(purchaseOrder.pph_nominal || 0)
                }}</span>
              </div>

              <!-- Total Keseluruhan -->
              <div class="border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-semibold text-gray-900"
                    >Total Keseluruhan</span
                  >
                  <span class="text-lg font-bold text-green-600">{{
                    formatCurrency(purchaseOrder.grand_total || 0)
                  }}</span>
                </div>
              </div>
            </div>

            <!-- <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="text-center">
                <p class="text-xs text-gray-500 mb-2">Total Jumlah</p>
                <p class="text-2xl font-bold text-indigo-600">
                  {{
                    purchaseOrder.tipe_po === "Reguler"
                      ? formatCurrency(purchaseOrder.grand_total || 0)
                      : formatCurrency(purchaseOrder.cicilan || 0)
                  }}
                </p>
              </div>
            </div> -->
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="goBack"
          class="flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 hover:text-gray-800 hover:bg-white/50 rounded-md transition-colors duration-200"
        >
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 19l-7-7m0 0l7-7m-7 7h18"
            />
          </svg>
          Kembali ke Approval Purchase Order
        </button>
      </div>
    </div>
  </div>

  <!-- Passcode Verification Dialog -->
  <!-- Approval Confirmation Dialog -->
  <ApprovalConfirmationDialog
    :is-open="showApprovalDialog"
    @update:open="showApprovalDialog = $event"
    @cancel="
      () => {
        showApprovalDialog = false;
        pendingAction = null;
      }
    "
    @confirm="handleApprovalConfirm"
  />

  <!-- Rejection Confirmation Dialog -->
  <RejectionConfirmationDialog
    :is-open="showRejectionDialog"
    :require-reason="true"
    @update:open="showRejectionDialog = $event"
    @cancel="
      () => {
        showRejectionDialog = false;
        pendingAction = null;
      }
    "
    @confirm="handleRejectionConfirm"
  />

  <!-- Passcode Verification Dialog -->
  <PasscodeVerificationDialog
    :is-open="showPasscodeDialog"
    :action="passcodeAction"
    :action-data="pendingAction"
    @update:open="showPasscodeDialog = $event"
    @cancel="
      () => {
        showPasscodeDialog = false;
        pendingAction = null;
      }
    "
    @verified="handlePasscodeVerified"
  />

  <!-- Success Dialog -->
  <SuccessDialog
    :is-open="showSuccessDialog"
    :action="successAction"
    :user-name="(purchaseOrder.creator && (purchaseOrder.creator.name || '')) || 'User'"
    document-type="Purchase Order"
    @update:open="(val: boolean) => {
        showSuccessDialog = val;
        if (!val) {
        router.visit('/approval/purchase-orders');
        }
    }"
    @close="
      () => {
        showSuccessDialog = false;
        router.visit('/approval/purchase-orders');
      }
    "
  />
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { CreditCard } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";
import ApprovalProgress from "@/components/approval/ApprovalProgress.vue";
import TerminSummary from "@/components/ui/TerminSummary.vue";
import { useApi } from "@/composables/useApi";
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "Approval", href: "/approval" },
  { label: "Purchase Order", href: "/approval/purchase-orders" },
  { label: "Detail" },
];

const props = defineProps<{ purchaseOrder: any }>();
const purchaseOrder = ref(props.purchaseOrder);
const { post, get } = useApi();

// Approval progress data
const approvalProgress = ref<any[]>([]);
const loadingProgress = ref(false);
const userRole = ref("");

// Dialog states
const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const successAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const pendingAction = ref<{
  type: "single";
  action: "verify" | "validate" | "approve" | "reject";
  ids: number[];
  singleItem?: any;
  reason?: string;
} | null>(null);

function handleApprove() {
  const dept = purchaseOrder.value?.department?.name;
  const creatorRole = purchaseOrder.value?.creator?.role?.name;
  let mappedAction: "verify" | "validate" | "approve" = "approve";

  // Admin bypass logic - determine the appropriate action based on current status
  if (userRole.value === "Admin") {
    if (purchaseOrder.value.status === "In Progress") {
      mappedAction = "verify";
    } else if (purchaseOrder.value.status === "Verified") {
      // Admin can approve directly for:
      // - Dept Zi&Glo
      // - Staff Akunting & Finance
      if (
        dept === "Zi&Glo" ||
        dept === "Human Greatness" ||
        creatorRole === "Staff Akunting & Finance"
      ) {
        mappedAction = "approve";
      } else {
        mappedAction = "validate"; // Normal flow (Toko / Digital Marketing)
      }
    } else if (purchaseOrder.value.status === "Validated") {
      mappedAction = "approve";
    }
  }

  pendingAction.value = {
    type: "single",
    action: mappedAction,
    ids: [purchaseOrder.value.id],
    singleItem: purchaseOrder.value,
  };
  showApprovalDialog.value = true;
}

function handleRejectClick() {
  pendingAction.value = {
    type: "single",
    action: "reject",
    ids: [purchaseOrder.value.id],
    singleItem: purchaseOrder.value,
  };
  showRejectionDialog.value = true;
}

function handleApprovalConfirm() {
  if (!pendingAction.value) return;
  showApprovalDialog.value = false;
  passcodeAction.value = pendingAction.value.action;
  showPasscodeDialog.value = true;
}

function handleRejectionConfirm(reason: string) {
  if (!pendingAction.value) return;
  pendingAction.value.reason = reason;
  showRejectionDialog.value = false;
  passcodeAction.value = "reject";
  showPasscodeDialog.value = true;
}

async function handlePasscodeVerified() {
  try {
    if (!pendingAction.value) return;

    if (pendingAction.value.action === "verify") {
      await post(`/api/approval/purchase-orders/${pendingAction.value.ids[0]}/verify`);
      purchaseOrder.value.status = "Verified";
    } else if (pendingAction.value.action === "validate") {
      await post(`/api/approval/purchase-orders/${pendingAction.value.ids[0]}/validate`);
      purchaseOrder.value.status = "Validated";
    } else if (pendingAction.value.action === "approve") {
      await post(`/api/approval/purchase-orders/${pendingAction.value.ids[0]}/approve`);
      purchaseOrder.value.status = "Approved";
    } else {
      await post(`/api/approval/purchase-orders/${pendingAction.value.ids[0]}/reject`, {
        reason: pendingAction.value.reason || "",
      });
      purchaseOrder.value.status = "Rejected";
    }

    // ✅ Refresh auth, biar nggak perlu manual refresh
    await router.reload({ only: ["auth"] });

    // ✅ Refresh approval progress di detail
    await fetchApprovalProgress();

    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;
  } catch {
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
}

function formatDate(date: string | null) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status);
}

function goBack() {
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit("/approval/purchase-orders");
  }
}

// Fetch approval progress
async function fetchApprovalProgress() {
  loadingProgress.value = true;
  try {
    const response = await get(
      `/api/approval/purchase-orders/${purchaseOrder.value.id}/progress`
    );
    approvalProgress.value = response.progress || [];
  } catch (error) {
    console.error("Error fetching approval progress:", error);
  } finally {
    loadingProgress.value = false;
  }
}

// Helper to read creator role and department
const creatorRole = computed(
  () => (purchaseOrder.value.creator as any)?.role?.name || ""
);
const departmentName = computed(() => purchaseOrder.value.department?.name || "");

// Derive current workflow step from API progress for robust gating
const currentStep = computed(() => {
  return (
    (approvalProgress.value || []).find((s: any) => s && s.status === "current") || null
  );
});

// Check user permissions for approval actions based on workflow progress first,
// then fall back to creator-role/department rules
const canVerify = computed(() => {
  // Admin bypass: can verify if status is "In Progress"
  if (userRole.value === "Admin" && purchaseOrder.value.status === "In Progress") {
    return true;
  }

  // Progress-driven rule
  if (currentStep.value?.step === "verified") {
    // Verified step assigned to Kepala Toko (Staff Toko flow) or Kabag (Akunting flow)
    return ["Kepala Toko", "Kabag", "Admin"].includes(userRole.value);
  }

  // Legacy fallback
  if (purchaseOrder.value.status !== "In Progress") return false;
  if (departmentName.value === "Zi&Glo" || departmentName.value === "Human Greatness")
    return false; // Zi&Glo & Human Greatness have no verify step
  if (creatorRole.value === "Staff Toko")
    return ["Kepala Toko", "Admin"].includes(userRole.value);
  if (creatorRole.value === "Staff Akunting & Finance")
    return ["Kabag", "Admin"].includes(userRole.value);
  return false;
});

const canValidate = computed(() => {
  // Admin bypass: can validate if status is "Verified" (following workflow)
  if (userRole.value === "Admin") {
    return purchaseOrder.value.status === "Verified";
  }

  // Progress-driven rule: Kadiv validates when current step is 'validated'
  if (currentStep.value?.step === "validated") {
    return ["Kadiv", "Admin"].includes(userRole.value);
  }

  // Legacy fallback
  if (creatorRole.value === "Staff Toko") {
    return (
      purchaseOrder.value.status === "Verified" &&
      ["Kadiv", "Admin"].includes(userRole.value)
    );
  }
  if (
    creatorRole.value === "Staff Digital Marketing" ||
    departmentName.value === "Zi&Glo" ||
    departmentName.value === "Human Greatness"
  ) {
    return (
      purchaseOrder.value.status === "In Progress" &&
      ["Kadiv", "Admin"].includes(userRole.value)
    );
  }
  return false;
});

const canApprove = computed(() => {
  // Admin bypass: can approve based on workflow (Verified for some, Validated for others)
  if (userRole.value === "Admin") {
    const dept = purchaseOrder.value?.department?.name;
    const creatorRole = purchaseOrder.value?.creator?.role?.name;

    // Direct approval for Zi&Glo, Human Greatness, Staff Akunting & Finance
    if (
      dept === "Zi&Glo" ||
      dept === "Human Greatness" ||
      creatorRole === "Staff Akunting & Finance"
    ) {
      return purchaseOrder.value.status === "Verified";
    }

    // Normal workflow: need validation first
    return purchaseOrder.value.status === "Validated";
  }

  // Progress-driven rule: Direksi approves when current step is 'approved'
  if (currentStep.value?.step === "approved") {
    return ["Direksi", "Admin"].includes(userRole.value);
  }

  // Legacy fallback
  if (creatorRole.value === "Staff Toko") {
    return (
      ["Direksi", "Admin"].includes(userRole.value) &&
      purchaseOrder.value.status === "Validated"
    );
  }
  if (creatorRole.value === "Staff Akunting & Finance") {
    return (
      ["Direksi", "Admin"].includes(userRole.value) &&
      purchaseOrder.value.status === "Verified"
    );
  }
  if (creatorRole.value === "Staff Digital Marketing") {
    return (
      ["Direksi", "Admin"].includes(userRole.value) &&
      purchaseOrder.value.status === "Validated"
    );
  }
  if (departmentName.value === "Zi&Glo" || departmentName.value === "Human Greatness") {
    return (
      ["Direksi", "Admin"].includes(userRole.value) &&
      purchaseOrder.value.status === "Validated"
    );
  }
  return false;
});

const canReject = computed(() => {
  // Admin bypass: can reject any document in progress
  if (userRole.value === "Admin") {
    return ["In Progress", "Verified", "Validated"].includes(purchaseOrder.value.status);
  }

  // Check if user has already performed any action
  const currentUser = page.props.auth?.user;
  if (!currentUser) return false;

  // Check if current user is the verifier, validator, or approver
  const hasPerformedAction =
    purchaseOrder.value.verifier_id === currentUser.id ||
    purchaseOrder.value.validator_id === currentUser.id ||
    purchaseOrder.value.approver_id === currentUser.id;

  if (hasPerformedAction) {
    return false; // User who already performed action cannot reject
  }

  return ["In Progress", "Verified", "Validated"].includes(purchaseOrder.value.status);
});

// Handle approval actions
function handleVerify() {
  pendingAction.value = {
    type: "single",
    action: "verify",
    ids: [purchaseOrder.value.id],
    singleItem: purchaseOrder.value,
  };
  showApprovalDialog.value = true;
}

function handleValidate() {
  pendingAction.value = {
    type: "single",
    action: "validate",
    ids: [purchaseOrder.value.id],
    singleItem: purchaseOrder.value,
  };
  showApprovalDialog.value = true;
}

// Initialize user role and fetch progress
// Initialize user role ASAP (before mount) to ensure action buttons render correctly
const page = usePage();
const user = page.props.auth?.user;
if (user && (user as any).role) {
  userRole.value = (user as any).role.name || "";
}

onMounted(async () => {
  await fetchApprovalProgress();

  // Check for auto passcode dialog after redirect from passcode creation
  const urlParams = new URLSearchParams(window.location.search);
  if (urlParams.get("auto_passcode_dialog") === "1") {
    const actionDataParam = urlParams.get("action_data");
    if (actionDataParam) {
      try {
        const actionData = JSON.parse(decodeURIComponent(actionDataParam));
        pendingAction.value = actionData;
        passcodeAction.value = actionData.action;
        showPasscodeDialog.value = true;

        // Clean up URL parameters
        const newUrl = new URL(window.location.href);
        newUrl.searchParams.delete("auto_passcode_dialog");
        newUrl.searchParams.delete("action_data");
        window.history.replaceState({}, "", newUrl.toString());
      } catch (error) {
        console.error("Error parsing action data:", error);
      }
    }
  }
});
</script>

<style scoped>
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}
.hover\:bg-white\/50:hover {
  background-color: rgba(255, 255, 255, 0.5);
}
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill,
    stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}
@media (max-width: 768px) {
  .grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}
.bg-yellow-100 {
  background-color: #fef3c7;
}
.text-yellow-800 {
  color: #92400e;
}
.bg-green-100 {
  background-color: #dcfce7;
}
.text-green-800 {
  color: #166534;
}
.bg-red-100 {
  background-color: #fee2e2;
}
.text-red-800 {
  color: #991b1b;
}
.bg-gray-100 {
  background-color: #f3f4f6;
}
.text-gray-800 {
  color: #1f2937;
}
.text-green-600 {
  color: #059669;
}
.text-blue-600 {
  color: #2563eb;
}
.text-red-600 {
  color: #dc2626;
}
.bg-blue-100 {
  background-color: #dbeafe;
}
.text-blue-800 {
  color: #1e40af;
}
.bg-purple-100 {
  background-color: #e9d5ff;
}
.text-purple-800 {
  color: #6b21a8;
}
</style>
