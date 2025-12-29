<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">
              Detail Memo Pembayaran (Approval)
            </h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <WalletCards class="w-4 h-4 mr-1" />
              {{ memoPembayaran.no_mb }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <span
            :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(
              memoPembayaran.status
            )}`"
          >
            <div
              class="w-2 h-2 rounded-full mr-2 inline-block"
              :class="getStatusDotClass(memoPembayaran.status)"
            ></div>
            {{ memoPembayaran.status }}
          </span>
        </div>
      </div>

      <!-- Rejection Reason Alert -->
      <div
        v-if="memoPembayaran.status === 'Rejected' && memoPembayaran.rejection_reason"
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
                <p>{{ memoPembayaran.rejection_reason }}</p>
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
                Informasi Memo Pembayaran
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
                    <p class="text-sm font-medium text-gray-900">No. Memo Pembayaran</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ memoPembayaran.no_mb || "-" }}
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
                    <p class="text-sm font-medium text-gray-900">Department</p>
                    <p class="text-sm text-gray-600">
                      {{ memoPembayaran.department?.name || "-" }}
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
                      d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                    <p class="text-sm text-gray-600">
                      {{ memoPembayaran.metode_pembayaran || "-" }}
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
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">
                      {{ formatDate(memoPembayaran.tanggal) }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Information Card -->
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
                  d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Pembayaran</h3>
            </div>

            <div class="space-y-4 md:columns-2 md:gap-6">
              <div class="flex items-start gap-3 break-inside-avoid mb-4">
                <Banknote class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Bank</p>
                  <p class="text-sm text-gray-600">
                    {{
                      memoPembayaran.bankSupplierAccount?.bank?.nama_bank ||
                        memoPembayaran.bank_supplier_account?.bank?.nama_bank ||
                        memoPembayaran.purchase_order?.bankSupplierAccount?.bank?.nama_bank ||
                        memoPembayaran.purchase_order?.bank_supplier_account?.bank?.nama_bank ||
                        memoPembayaran.purchase_order?.bank?.nama_bank ||
                        (purchaseOrders.length ? (purchaseOrders[0].bankSupplierAccount?.bank?.nama_bank || purchaseOrders[0].bank_supplier_account?.bank?.nama_bank || purchaseOrders[0].bank?.nama_bank) : null) ||
                        memoPembayaran.bank?.nama_bank ||
                        "-"
                    }}
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3 break-inside-avoid mb-4">
                <User class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Nama Rekening</p>
                  <p class="text-sm text-gray-600">
                    {{
                      memoPembayaran.bankSupplierAccount?.nama_rekening ||
                        memoPembayaran.bank_supplier_account?.nama_rekening ||
                        memoPembayaran.purchase_order?.bankSupplierAccount?.nama_rekening ||
                        memoPembayaran.purchase_order?.bank_supplier_account?.nama_rekening ||
                        memoPembayaran.purchase_order?.nama_rekening ||
                        (purchaseOrders.length ? (purchaseOrders[0].bankSupplierAccount?.nama_rekening || purchaseOrders[0].bank_supplier_account?.nama_rekening || purchaseOrders[0].nama_rekening) : null) ||
                        memoPembayaran.nama_rekening
                    }}
                  </p>
                </div>
              </div>

              <div class="flex items-start gap-3 break-inside-avoid mb-4">
                <Hash class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">No. Rekening</p>
                  <p class="text-sm text-gray-600 font-mono">
                    {{
                      memoPembayaran.bankSupplierAccount?.no_rekening ||
                        memoPembayaran.bank_supplier_account?.no_rekening ||
                        memoPembayaran.purchase_order?.bankSupplierAccount?.no_rekening ||
                        memoPembayaran.purchase_order?.bank_supplier_account?.no_rekening ||
                        memoPembayaran.purchase_order?.no_rekening ||
                        (purchaseOrders.length ? (purchaseOrders[0].bankSupplierAccount?.no_rekening || purchaseOrders[0].bank_supplier_account?.no_rekening || purchaseOrders[0].no_rekening) : null) ||
                        memoPembayaran.no_rekening
                    }}
                  </p>
                </div>
              </div>

              <div v-if="memoPembayaran.no_giro" class="flex items-start gap-3 break-inside-avoid mb-4">
                <ReceiptText class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">No. Giro</p>
                  <p class="text-sm text-gray-600 font-mono">
                    {{ memoPembayaran.no_giro }}
                  </p>
                </div>
              </div>

              <div v-if="memoPembayaran.tanggal_giro" class="flex items-start gap-3 break-inside-avoid mb-4">
                <CalendarDays class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Tanggal Giro</p>
                  <p class="text-sm text-gray-600">
                    {{ formatDate(memoPembayaran.tanggal_giro) }}
                  </p>
                </div>
              </div>

              <div v-if="memoPembayaran.tanggal_cair" class="flex items-start gap-3 break-inside-avoid mb-4">
                <CalendarDays class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Tanggal Cair</p>
                  <p class="text-sm text-gray-600">
                    {{ formatDate(memoPembayaran.tanggal_cair) }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Purchase Orders Info & Items -->
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
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Terkait</h3>
              <span
                class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full"
                >{{ purchaseOrders.length }} PO</span
              >
            </div>

            <div v-if="purchaseOrders.length > 0" class="space-y-6">
              <div
                v-for="po in purchaseOrders"
                :key="po.id"
                class="border border-gray-200 rounded-lg p-4"
              >
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                  <div class="space-y-4">
                    <div class="flex items-start gap-3">
                      <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 20h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-gray-900">No. PO</p>
                        <p class="text-sm text-gray-600 font-mono">
                          {{ po.no_po || "-" }}
                        </p>
                      </div>
                    </div>

                    <div class="flex items-start gap-3">
                      <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-gray-900">Tanggal PO</p>
                        <p class="text-sm text-gray-600">
                          {{ formatDate(po.tanggal || null) }}
                        </p>
                      </div>
                    </div>
                  </div>

                  <div class="space-y-4">
                    <div class="flex items-start gap-3">
                      <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-2.21 0-4 1.79-4 4m8 0a4 4 0 11-8 0 4 4 0 018 0zm-8 6h8" />
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                        <p class="text-sm text-gray-600">
                          {{ po.metode_pembayaran || "-" }}
                        </p>
                      </div>
                    </div>

                    <div class="flex items-start gap-3">
                      <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                      </svg>
                      <div>
                        <p class="text-sm font-medium text-gray-900">Perihal</p>
                        <p class="text-sm text-gray-600">
                          {{ po.perihal?.nama || "-" }}
                        </p>
                      </div>
                    </div>
                  </div>
                </div>

                <div
                  v-if="(po.items || []).length"
                  class="mt-6"
                >
                  <div class="flex items-center gap-2 mb-3">
                    <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                    </svg>
                    <h4 class="text-base font-semibold text-gray-900">Items PO</h4>
                  </div>

                  <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                      <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                          <th class="px-4 py-3 text-left font-semibold text-gray-900">Nama Barang</th>
                          <th class="px-4 py-3 text-right font-semibold text-gray-900">Qty</th>
                          <th class="px-4 py-3 text-left font-semibold text-gray-900">Satuan</th>
                          <th class="px-4 py-3 text-right font-semibold text-gray-900">Harga</th>
                          <th class="px-4 py-3 text-right font-semibold text-gray-900">Subtotal</th>
                        </tr>
                      </thead>
                      <tbody class="divide-y divide-gray-200">
                        <tr
                          v-for="(poIt, idx) in po.items || []"
                          :key="idx"
                          class="hover:bg-gray-50"
                        >
                          <td class="px-4 py-3 text-gray-900">{{ poIt.nama_barang }}</td>
                          <td class="px-4 py-3 text-right text-gray-900">{{ formatInteger(poIt.qty) }}</td>
                          <td class="px-4 py-3 text-gray-600">{{ poIt.satuan }}</td>
                          <td class="px-4 py-3 text-right text-gray-900">
                            {{ formatCurrency(Number(poIt.harga || 0)) }}
                          </td>
                          <td class="px-4 py-3 text-right font-medium text-gray-900">
                            {{ formatCurrency(Number(poIt.qty || 0) * Number(poIt.harga || 0)) }}
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>

            <div v-else class="text-center py-8 text-gray-500">
              <svg
                class="w-12 h-12 mx-auto text-gray-400 mb-2"
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
              <p>Tidak ada Purchase Order terkait</p>
            </div>
          </div>

          <!-- Termin Summary (for PO Lainnya) -->
          <TerminSummary
            v-if="
              memoPembayaran.purchase_order?.tipe_po === 'Lainnya' &&
              memoPembayaran.purchase_order?.termin
            "
            :termin-data="memoPembayaran.purchase_order.termin"
          />

          <!-- Additional Information -->
          <div
            v-if="memoPembayaran.keterangan"
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
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
            </div>

            <div class="space-y-6">
              <div>
                <p class="text-sm font-medium text-gray-900 mb-2">Keterangan</p>
                <div class="bg-gray-50 rounded-lg p-4">
                  <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">
                    {{ memoPembayaran.keterangan || "No additional notes." }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column - Approval Progress & Actions -->
        <div class="space-y-6">
          <!-- Approval Progress -->
          <ApprovalProgress
            :progress="approvalProgress"
            :purchase-order="memoPembayaran"
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

          <!-- Approval Notes -->
          <div
            v-if="memoPembayaran.approval_notes"
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
                  d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Catatan Approval</h3>
            </div>
            <div class="bg-gray-50 rounded-lg p-4">
              <p class="text-sm text-gray-900 leading-relaxed whitespace-pre-wrap">
                {{ memoPembayaran.approval_notes }}
              </p>
            </div>
          </div>

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
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan Pembayaran</h3>
            </div>

            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Jumlah Total</span>
                <span class="text-sm font-medium text-gray-900">{{
                  formatCurrency(memoPembayaran.total || 0)
                }}</span>
              </div>

              <div class="border-t border-gray-200 pt-4">
                <div class="flex items-center justify-between">
                  <span class="text-lg font-semibold text-gray-900"
                    >Total Keseluruhan</span
                  >
                  <span class="text-lg font-bold text-green-600">{{
                    formatCurrency(memoPembayaran.total || 0)
                  }}</span>
                </div>
              </div>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="text-center">
                <p class="text-xs text-gray-500 mb-2">Total Pembayaran</p>
                <p class="text-2xl font-bold text-indigo-600">
                  {{ formatCurrency(memoPembayaran.total || 0) }}
                </p>
              </div>
            </div>
          </div>

          <!-- Creator Information -->
          <!-- <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
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
                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Pembuat</h3>
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
                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                  />
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-900">Dibuat oleh</p>
                  <p class="text-sm text-gray-600">
                    {{ memoPembayaran.creator?.name || "-" }}
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
                    d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"
                  />
                </svg>
                <div>
                  <p class="text-sm font-medium text-gray-900">Tanggal dibuat</p>
                  <p class="text-sm text-gray-600">
                    {{ formatDate(memoPembayaran.created_at) }}
                  </p>
                </div>
              </div>
            </div>
          </div> -->
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
          Kembali ke Approval Memo Pembayaran
        </button>
      </div>
    </div>

    <!-- Approval Confirmation Dialog -->
    <ApprovalConfirmationDialog
      :is-open="showApprovalDialog"
      @update:open="(v: boolean) => (showApprovalDialog = v)"
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
      @update:open="(v: boolean) => (showRejectionDialog = v)"
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
      @update:open="(v: boolean) => (showPasscodeDialog = v)"
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
      :user-name="
        (memoPembayaran.creator && (memoPembayaran.creator.name || '')) || 'User'
      "
      document-type="Memo Pembayaran"
      @update:open="(v: boolean) => (showSuccessDialog = v)"
      @close="
        () => {
          showSuccessDialog = false;
          // Redirect to memo pembayaran approval index page after any approval action
          router.visit('/approval/memo-pembayarans');
        }
      "
    />
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { WalletCards } from "lucide-vue-next";
import { User, Hash, Banknote, CalendarDays, ReceiptText } from "lucide-vue-next";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import ApprovalProgress from "@/components/approval/ApprovalProgress.vue";
import TerminSummary from "@/components/ui/TerminSummary.vue";
import ApprovalConfirmationDialog from "@/components/approval/ApprovalConfirmationDialog.vue";
import RejectionConfirmationDialog from "@/components/approval/RejectionConfirmationDialog.vue";
import PasscodeVerificationDialog from "@/components/approval/PasscodeVerificationDialog.vue";
import SuccessDialog from "@/components/approval/SuccessDialog.vue";
import { formatCurrency } from "@/lib/currencyUtils";
import { useApi } from "@/composables/useApi";
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";
import AppLayout from "@/layouts/AppLayout.vue";

// Props
const props = defineProps<{
  memoPembayaran: any;
}>();

// Create a reactive reference to the memo
const memoPembayaran = ref(props.memoPembayaran);

// Reactive data
const approvalProgress = ref<any[]>([]);
const loadingProgress = ref(false);
const userRole = ref("");
const showApprovalDialog = ref(false);
const showRejectionDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const successAction = ref<"verify" | "validate" | "approve" | "reject">("approve");
const userName = ref("");
const pendingAction = ref<{
  type: "single";
  action: "verify" | "validate" | "approve" | "reject";
  ids: number[];
  singleItem?: any;
  reason?: string;
} | null>(null);
const { post, get } = useApi();

// Computed
const breadcrumbs = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Approval", href: "/approval" },
  { label: "Memo Pembayaran", href: "/approval/memo-pembayarans" },
  { label: "Detail", href: "#" },
]);

function formatInteger(value?: number | string) {
  return new Intl.NumberFormat("id-ID", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0));
}

// Computed property to handle purchase orders data
const purchaseOrders = computed(() => {
  // First check for many-to-many relationship
  const manyToMany =
    memoPembayaran.value.purchase_orders || memoPembayaran.value.purchaseOrders;
  if (manyToMany && Array.isArray(manyToMany) && manyToMany.length > 0) {
    return manyToMany;
  }

  // Fallback to single relationship if available
  const singlePO =
    memoPembayaran.value.purchase_order || memoPembayaran.value.purchaseOrder;
  if (singlePO) {
    return [singlePO]; // Wrap single PO in array for consistent handling
  }

  return [];
});

defineOptions({ layout: AppLayout });

// Computed properties for approval permissions based on new workflow
const canVerify = computed(() => {
  const role = userRole.value;
  const creatorRole = memoPembayaran.value?.creator?.role?.name;
  const dept = memoPembayaran.value?.department?.name;
  const status = memoPembayaran.value.status;

  // Admin bypass: can verify if status is "In Progress" and memo needs verification
  if (role === "Admin" && status === "In Progress") {
    // Admin can verify any memo that has a verify step in the workflow
    // This includes Staff Toko memos and any other memos that require verification
    return true;
  }

  // Kepala Toko bisa verify memo Staff Toko dan Admin (bukan Zi&Glo/HG)
  if (role === "Kepala Toko") {
    return (
      status === "In Progress" &&
      (creatorRole === "Staff Toko" || creatorRole === "Admin") &&
      dept !== "Zi&Glo" &&
      dept !== "Human Greatness"
    );
  }
  return false;
});

const canValidate = computed(() => {
  // Tidak ada validate step dalam workflow baru
  return false;
});

const canApprove = computed(() => {
  const role = userRole.value;
  const creatorRole = memoPembayaran.value?.creator?.role?.name;
  const dept = memoPembayaran.value?.department?.name;
  const status = memoPembayaran.value.status;

  // Admin bypass: can approve based on workflow
  if (role === "Admin") {
    // Direct approval for DM, Zi&Glo, Human Greatness, Staff Akunting
    if (
      creatorRole === "Staff Digital Marketing" ||
      dept === "Zi&Glo" ||
      dept === "Human Greatness" ||
      creatorRole === "Staff Akunting & Finance"
    ) {
      return status === "In Progress";
    }

    // Staff Toko needs verification first
    if (creatorRole === "Staff Toko") {
      return status === "Verified";
    }

    // Kepala Toko memo yang langsung Verified
    if (creatorRole === "Kepala Toko") {
      return status === "Verified";
    }

    return false;
  }

  if (role === "Kadiv") {
    if (
      status === "Verified" &&
      (creatorRole === "Staff Toko" || creatorRole === "Kepala Toko")
    ) {
      return true; // Staff Toko flow: setelah Kepala Toko verify, atau Kepala Toko langsung
    }
    if (status === "In Progress" && creatorRole === "Staff Digital Marketing") {
      return true; // Digital Marketing flow: langsung approve
    }
    return false;
  }

  if (role === "Kabag") {
    // Kabag hanya bisa approve memo Staff Akunting & Finance
    return status === "In Progress" && creatorRole === "Staff Akunting & Finance";
  }

  if (role === "Kepala Toko") {
    // Kepala Toko menyetujui langsung memo Staff Toko untuk departemen Zi&Glo/Human Greatness
    return (
      status === "In Progress" &&
      creatorRole === "Staff Toko" &&
      (dept === "Zi&Glo" || dept === "Human Greatness")
    );
  }

  return false;
});

const canReject = computed(() => {
  const status = memoPembayaran.value.status;
  const role = userRole.value;

  // Admin bypass: can reject any memo in progress
  if (role === "Admin") {
    return ["In Progress", "Verified", "Validated"].includes(status);
  }

  // Check if user has already performed any action
  const currentUser = page.props.auth?.user;
  if (!currentUser) return false;

  // Check if current user is the verifier, validator, or approver
  const hasPerformedAction =
    memoPembayaran.value.verifier_id === currentUser.id ||
    memoPembayaran.value.validator_id === currentUser.id ||
    memoPembayaran.value.approver_id === currentUser.id;

  if (hasPerformedAction) {
    return false; // User who already performed action cannot reject
  }

  // Semua role yang bisa approve juga bisa reject
  return (
    ["In Progress", "Verified", "Validated"].includes(status) &&
    (canVerify.value || canApprove.value)
  );
});

// Methods
async function fetchApprovalProgress() {
  loadingProgress.value = true;
  try {
    const data = await get(
      `/api/approval/memo-pembayarans/${props.memoPembayaran.id}/progress`
    );
    approvalProgress.value = data.progress || [];
  } catch (error) {
    console.error("Error fetching approval progress:", error);
  } finally {
    loadingProgress.value = false;
  }
}

function handleApprove() {
  const role = userRole.value;
  const creatorRole = memoPembayaran.value?.creator?.role?.name;
  const dept = memoPembayaran.value?.department?.name;
  const status = memoPembayaran.value.status;
  let mappedAction: "verify" | "validate" | "approve" = "approve";

  // Admin bypass logic - determine appropriate action based on current status and workflow
  if (role === "Admin") {
    if (status === "In Progress") {
      // Check if this should be verified first or approved directly
      if (
        creatorRole === "Staff Toko" &&
        dept !== "Zi&Glo" &&
        dept !== "Human Greatness"
      ) {
        mappedAction = "verify"; // Staff Toko needs verification first
      } else {
        mappedAction = "approve"; // Direct approval for others (DM, Akunting, Zi&Glo)
      }
    } else if (status === "Verified") {
      mappedAction = "approve"; // Always approve verified memos
    } else {
      mappedAction = "approve"; // fallback
    }
  }

  pendingAction.value = {
    type: "single",
    action: mappedAction,
    ids: [props.memoPembayaran.id],
    singleItem: props.memoPembayaran,
  };
  showApprovalDialog.value = true;
}

function handleRejectClick() {
  pendingAction.value = {
    type: "single",
    action: "reject",
    ids: [props.memoPembayaran.id],
    singleItem: props.memoPembayaran,
  };
  showRejectionDialog.value = true;
}

function handleVerify() {
  pendingAction.value = {
    type: "single",
    action: "verify",
    ids: [props.memoPembayaran.id],
    singleItem: props.memoPembayaran,
  };
  showApprovalDialog.value = true;
}

function handleValidate() {
  // Tidak ada validate step dalam workflow baru
  // Function ini tetap ada untuk kompatibilitas dengan ApprovalProgress component
  console.warn("Validate action is not available in the new workflow");
}

const handleApprovalConfirm = () => {
  if (!pendingAction.value) return;
  showApprovalDialog.value = false;
  passcodeAction.value = pendingAction.value.action;
  showPasscodeDialog.value = true;
};

const handleRejectionConfirm = (data: any) => {
  if (!pendingAction.value) return;
  const reason = typeof data === "string" ? data : data?.reason;
  pendingAction.value.reason = reason || "";
  showRejectionDialog.value = false;
  passcodeAction.value = "reject";
  showPasscodeDialog.value = true;
};

async function handlePasscodeVerified() {
  try {
    if (!pendingAction.value) return;

    const id = pendingAction.value.ids[0];
    if (pendingAction.value.action === "verify") {
      await post(`/api/approval/memo-pembayarans/${id}/verify`);
      (props.memoPembayaran as any).status = "Verified";
    } else if (pendingAction.value.action === "validate") {
      await post(`/api/approval/memo-pembayarans/${id}/validate`);
      (props.memoPembayaran as any).status = "Validated";
    } else if (pendingAction.value.action === "approve") {
      await post(`/api/approval/memo-pembayarans/${id}/approve`);
      (props.memoPembayaran as any).status = "Approved";
    } else {
      await post(`/api/approval/memo-pembayarans/${id}/reject`, {
        reason: pendingAction.value.reason || "",
      });
      (props.memoPembayaran as any).status = "Rejected";
    }

    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;

    await fetchApprovalProgress();
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
  router.visit("/approval/memo-pembayarans");
}

// Initialize user role and fetch progress
const page = usePage();
const user = page.props.auth?.user;
if (user && (user as any).role) {
  userRole.value = (user as any).role.name || "";
}
if (user) {
  userName.value = (user as any).name || "User";
}

// Lifecycle
onMounted(() => {
  fetchApprovalProgress();

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
