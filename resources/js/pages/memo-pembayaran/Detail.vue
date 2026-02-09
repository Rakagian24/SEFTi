<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Desktop / Tablet header + actions -->
      <div class="mb-4 hidden items-start justify-between gap-3 md:mb-6 md:flex">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Memo Pembayaran</h1>
            <div class="mt-2 flex items-center text-sm text-gray-500">
              <WalletCards class="w-4 h-4 mr-1" />
              {{ memoPembayaran.no_mb }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <!-- Status Badge -->
          <span
            :class="`inline-flex items-center px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(
              memoPembayaran.status
            )}`"
          >
            <div
              class="w-2 h-2 rounded-full mr-2 inline-block"
              :class="getStatusDotClass(memoPembayaran.status)"
            ></div>
            {{ memoPembayaran.status }}
          </span>

          <!-- Edit Button (creator can edit Draft, creator or Admin can edit Rejected) -->
          <button
            v-if="canEdit"
            @click="goToEdit"
            class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            {{ memoPembayaran.status === "Rejected" ? "Perbaiki" : "Edit" }}
          </button>

          <!-- Send Button with Confirmation (not shown for Rejected) -->
          <button
            v-if="memoPembayaran.status === 'Draft' && isCreator"
            @click="openConfirmSend"
            class="flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7"
              />
            </svg>
            Kirim
          </button>

          <!-- Download Button: only for specific statuses -->
          <button
            v-if="
              ['In Progress', 'Verified', 'Validated', 'Approved'].includes(
                memoPembayaran.status
              )
            "
            @click="downloadMemo"
            class="flex items-center gap-2 px-4 py-2 bg-purple-600 text-white text-sm font-medium rounded-md hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            Download PDF
          </button>

          <!-- Log Button -->
          <button
            @click="goToLog"
            class="flex items-center gap-2 px-4 py-2 bg-gray-100 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 transition-colors duration-200"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 17v-6h13M9 7h13M4 7h.01M4 17h.01"
              />
            </svg>
            Log
          </button>
        </div>
      </div>

      <!-- Mobile header -->
      <div class="mb-4 md:hidden">
        <h1 class="text-xl font-bold text-gray-900">Detail Memo Pembayaran</h1>
        <div class="mt-1 flex items-center text-xs text-gray-500">
          <WalletCards class="mr-1 h-3 w-3" />
          {{ memoPembayaran.no_mb }}
        </div>

        <div class="mt-2">
          <span
            :class="`inline-flex items-center px-3 py-1 text-[11px] font-medium rounded-full ${getStatusBadgeClass(
              memoPembayaran.status
            )}`"
          >
            <div
              class="mr-2 inline-block h-2 w-2 rounded-full"
              :class="getStatusDotClass(memoPembayaran.status)"
            ></div>
            {{ memoPembayaran.status }}
          </span>
        </div>
      </div>

      <!-- Mobile actions: Kirim / Download / Log -->
      <div class="mb-4 flex items-center justify-between gap-2 md:hidden">
        <div class="flex flex-col text-[11px] text-gray-600">
          <span class="font-medium">Aksi</span>
          <span class="text-gray-500">Memo Pembayaran ini</span>
        </div>

        <div class="flex items-center gap-2">
          <button
            v-if="memoPembayaran.status === 'Draft' && isCreator"
            type="button"
            @click="openConfirmSend"
            class="inline-flex items-center gap-1 rounded-lg bg-green-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-green-700"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7"
              />
            </svg>
            <span>Kirim</span>
          </button>

          <button
            v-if="['In Progress', 'Verified', 'Validated', 'Approved'].includes(memoPembayaran.status)"
            type="button"
            @click="downloadMemo"
            class="inline-flex items-center gap-1 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-purple-700"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            <span>Download</span>
          </button>

          <button
            type="button"
            @click="goToLog"
            class="inline-flex items-center gap-1 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm active:bg-gray-50"
          >
            <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2V9a2 2 0 00-2-2h-5m-3-4h3m-3 4h3"
              />
            </svg>
            <span>Log</span>
          </button>
        </div>
      </div>

      <!-- Rejection Reason Card -->
      <div
        v-if="memoPembayaran.status === 'Rejected' && memoPembayaran.rejection_reason"
        class="bg-white rounded-lg shadow-sm border border-red-200 p-6"
      >
        <div class="flex items-start gap-2">
          <svg
            class="w-5 h-5 text-red-500 mt-0.5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 9v2m0 4h.01M5.07 19h13.86a2 2 0 001.73-3L13.73 4a2 2 0 00-3.46 0L3.34 16a2 2 0 001.73 3z"
            />
          </svg>
          <div>
            <h3 class="text-sm font-semibold text-red-700">Alasan Penolakan</h3>
            <p class="text-sm text-red-700 mt-1 whitespace-pre-wrap">
              {{ memoPembayaran.rejection_reason }}
            </p>
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
              <div v-if="paymentInfo.bank" class="flex items-start gap-3 break-inside-avoid mb-4">
                <Banknote class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Bank</p>
                  <p class="text-sm text-gray-600">
                    {{ paymentInfo.bank?.nama_bank || "-" }}
                  </p>
                </div>
              </div>

              <div v-if="paymentInfo.bankSupplierAccount" class="flex items-start gap-3 break-inside-avoid mb-4">
                <User class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Nama Rekening</p>
                  <p class="text-sm text-gray-600">
                    {{ paymentInfo.bankSupplierAccount?.nama_rekening || "-" }}
                  </p>
                </div>
              </div>

              <div v-if="paymentInfo.bankSupplierAccount" class="flex items-start gap-3 break-inside-avoid mb-4">
                <Hash class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">No Rekening</p>
                  <p class="text-sm text-gray-600 font-mono">
                    {{ paymentInfo.bankSupplierAccount?.no_rekening || "-" }}
                  </p>
                </div>
              </div>

              <div v-if="paymentInfo.no_giro" class="flex items-start gap-3 break-inside-avoid mb-4">
                <ReceiptText class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">No. Giro</p>
                  <p class="text-sm text-gray-600 font-mono">
                    {{ paymentInfo.no_giro }}
                  </p>
                </div>
              </div>

              <div v-if="paymentInfo.tanggal_giro" class="flex items-start gap-3 break-inside-avoid mb-4">
                <CalendarDays class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Tanggal Giro</p>
                  <p class="text-sm text-gray-600">
                    {{ formatDate(paymentInfo.tanggal_giro) }}
                  </p>
                </div>
              </div>

              <div v-if="paymentInfo.tanggal_cair" class="flex items-start gap-3 break-inside-avoid mb-4">
                <CalendarDays class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Tanggal Cair</p>
                  <p class="text-sm text-gray-600">
                    {{ formatDate(paymentInfo.tanggal_cair) }}
                  </p>
                </div>
              </div>

              <div v-if="paymentInfo.creditCard" class="flex items-start gap-3 break-inside-avoid mb-4">
                <CreditCard class="w-5 h-5 text-gray-400 mt-0.5" />
                <div>
                  <p class="text-sm font-medium text-gray-900">Info Kartu Kredit</p>
                  <p class="text-sm text-gray-600 font-mono">
                    {{ paymentInfo.creditCard?.no_kartu_kredit }} - {{ paymentInfo.creditCard?.nama_pemilik }}
                  </p>
                  <p class="text-xs text-gray-500">
                    {{ paymentInfo.creditCard?.bank?.nama_bank }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Purchase Order & Items (mirroring BPB detail layout) -->
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
            </div>

            <div v-if="memoPembayaran.purchaseOrder">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                  <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 20h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                    </svg>
                    <div>
                      <p class="text-sm font-medium text-gray-900">No. PO</p>
                      <p class="text-sm text-gray-600 font-mono">
                        {{ memoPembayaran.purchaseOrder.no_po || "-" }}
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
                        {{ formatDate(memoPembayaran.purchaseOrder.tanggal || null) }}
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
                        {{ memoPembayaran.purchaseOrder.metode_pembayaran || "-" }}
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
                        {{ memoPembayaran.purchaseOrder.perihal?.nama || "-" }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div
                v-if="(memoPembayaran.purchaseOrder.items || []).length"
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
                        v-for="(poIt, idx) in memoPembayaran.purchaseOrder.items || []"
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

        <!-- Right Column - Approval Progress, Summary & Creator -->
        <div class="space-y-6">
          <!-- Approval Progress -->
          <ApprovalProgress
            :progress="approvalProgress"
            :purchase-order="memoPembayaran"
            :user-role="userRole"
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
          Kembali ke Daftar Memo Pembayaran
        </button>
      </div>

      <!-- Confirm Dialog untuk Kirim dari halaman detail -->
      <ConfirmDialog
        :show="showConfirmSend"
        :message="`Apakah Anda yakin ingin mengirim Memo Pembayaran ini?`"
        @confirm="confirmSend"
        @cancel="cancelSend"
      />
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, computed, onMounted } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import AppLayout from "@/layouts/AppLayout.vue";
import { CreditCard, WalletCards } from "lucide-vue-next";
import { User, Hash, Banknote, CalendarDays, ReceiptText } from "lucide-vue-next";
import { formatCurrency } from "@/lib/currencyUtils";
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";
import ApprovalProgress from "@/components/approval/ApprovalProgress.vue";
import TerminSummary from "@/components/ui/TerminSummary.vue";
import ConfirmDialog from "@/components/ui/ConfirmDialog.vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
import { useApi } from "@/composables/useApi";

const props = defineProps<{ memoPembayaran: any }>();
const memoPembayaran = ref(props.memoPembayaran);
const { addSuccess, addError } = useMessagePanel();
const { get } = useApi();

const paymentInfo = computed<any>(() => {
  const row: any = memoPembayaran.value || {};
  const po: any = row.purchaseOrder || row.purchase_order || {};
  const bankSupplierAccount =
    row.bankSupplierAccount ||
    row.bank_supplier_account ||
    po.bankSupplierAccount ||
    po.bank_supplier_account ||
    null;
  const bank =
    row.bank ||
    bankSupplierAccount?.bank ||
    po.bank ||
    po.bankSupplierAccount?.bank ||
    null;
  const creditCard =
    row.creditCard || row.credit_card || po.creditCard || po.credit_card || null;
  return {
    bank,
    bankSupplierAccount,
    creditCard,
    no_giro: row.no_giro || po.no_giro || null,
    tanggal_giro: row.tanggal_giro || po.tanggal_giro || null,
    tanggal_cair: row.tanggal_cair || po.tanggal_cair || null,
  };
});

const approvalProgress = ref<any[]>([]);
const loadingProgress = ref(false);
const userRole = ref("");
const page = usePage();
const user = page.props.auth?.user;
if (user && (user as any).role) {
  userRole.value = (user as any).role.name || "";
}

// Only the creator can edit when status is Rejected (and Draft in our rule below), or Admin can edit any Rejected document
const isCreator = computed<boolean>(() => {
  const currentUserId = (page.props.auth?.user as any)?.id;
  const creatorId = (memoPembayaran.value as any)?.creator?.id;
  return Boolean(
    currentUserId && creatorId && String(currentUserId) === String(creatorId)
  );
});

// Check if current user is Admin
const isAdmin = computed<boolean>(() => {
  const userRole = (page.props.auth?.user as any)?.role?.name;
  return userRole === "Admin";
});

// Check if user can edit (creator for draft, creator or admin for rejected documents)
const canEdit = computed<boolean>(() => {
  if (memoPembayaran.value.status === "Draft") {
    return isCreator.value;
  }
  if (memoPembayaran.value.status === "Rejected") {
    return isCreator.value || isAdmin.value;
  }
  return false;
});

async function fetchApprovalProgress() {
  loadingProgress.value = true;
  try {
    const response = await get(
      `/api/approval/memo-pembayarans/${memoPembayaran.value.id}/progress`
    );
    approvalProgress.value = response.progress || [];
  } catch (error) {
    console.error("Error fetching approval progress:", error);
  } finally {
    loadingProgress.value = false;
  }
}

onMounted(async () => {
  await fetchApprovalProgress();
  try {
    const row: any = memoPembayaran.value || {};
    const po: any = row.purchaseOrder || row.purchase_order || null;
    const bankSupplierAccount =
      row.bankSupplierAccount ||
      row.bank_supplier_account ||
      po?.bankSupplierAccount ||
      po?.bank_supplier_account ||
      null;
    const creditCard = row.creditCard || row.credit_card || po?.creditCard || po?.credit_card || null;
    console.group("[MemoPembayaran Detail] Data Snapshot");
    console.log({ row, purchaseOrder: po, bankSupplierAccount, creditCard });
    console.log("paymentInfo", paymentInfo.value);
    console.groupEnd();
  } catch (e) {
    console.error("[MemoPembayaran Detail] Logging error", e);
  }
});

const breadcrumbs = computed(() => [
  { label: "Dashboard", href: "/dashboard" },
  { label: "Memo Pembayaran", href: "/memo-pembayaran" },
  { label: "Detail", href: "#" },
]);

defineOptions({ layout: AppLayout });

function formatDate(date: string | null) {
  if (!date) return "-";
  return new Date(date).toLocaleDateString("id-ID", {
    year: "numeric",
    month: "long",
    day: "numeric",
  });
}

function formatInteger(value?: number | string) {
  return new Intl.NumberFormat("id-ID", {
    minimumFractionDigits: 0,
    maximumFractionDigits: 0,
  }).format(Number(value ?? 0));
}

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status);
}


function goBack() {
  router.visit("/memo-pembayaran");
}

function goToEdit() {
  router.visit(`/memo-pembayaran/${memoPembayaran.value.id}/edit`);
}

function goToLog() {
  router.visit(`/memo-pembayaran/${memoPembayaran.value.id}/log`);
}

function downloadMemo() {
  window.open(`/memo-pembayaran/${memoPembayaran.value.id}/download`, "_blank");
}

// Confirm send flow for Detail page
const showConfirmSend = ref(false);

function openConfirmSend() {
  // Only allow when Draft or Rejected
  const status = memoPembayaran.value?.status;
  if (status !== "Draft" && status !== "Rejected") return;
  // client-side precheck based on metode pembayaran
  const row = memoPembayaran.value as any;
  const missing: string[] = [];
  if (!row.total || Number(row.total) <= 0) missing.push("Total");
  if (!["Transfer", "Cek/Giro", "Kredit"].includes(row.metode_pembayaran))
    missing.push("Metode Pembayaran");
  else if (row.metode_pembayaran === "Transfer") {
    if (!row.bank_supplier_account_id) missing.push("Bank Account");
  } else if (row.metode_pembayaran === "Cek/Giro") {
    if (!row.no_giro) missing.push("No. Giro");
    if (!row.tanggal_giro) missing.push("Tanggal Giro");
    if (!row.tanggal_cair) missing.push("Tanggal Cair");
  } else if (row.metode_pembayaran === "Kredit") {
    if (!row.credit_card_id) missing.push("Credit Card");
  }
  if (missing.length) {
    addError(
      `Tidak bisa mengirim Memo Pembayaran karena data belum lengkap: ${missing.join(
        ", "
      )}`
    );
    return;
  }
  showConfirmSend.value = true;
}

function confirmSend() {
  router.post(
    "/memo-pembayaran/send",
    { ids: [memoPembayaran.value.id] },
    {
      onSuccess: () => {
        addSuccess("Memo Pembayaran berhasil dikirim");
        // Reload current detail to reflect new status
        router.visit(`/memo-pembayaran/${memoPembayaran.value.id}`);
      },
      onError: () => addError("Terjadi kesalahan saat mengirim Memo Pembayaran"),
      preserveScroll: true,
    }
  );
  showConfirmSend.value = false;
}

function cancelSend() {
  showConfirmSend.value = false;
}
</script>
