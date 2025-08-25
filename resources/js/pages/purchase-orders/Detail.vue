<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Purchase Order</h1>
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

          <!-- Edit Button -->
          <button
            v-if="purchaseOrder.status === 'Draft'"
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
            Edit
          </button>

          <!-- Download Button -->
          <button
            @click="downloadPO"
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
                Purchase Order Information
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
                    <p class="text-sm font-medium text-gray-900">PO Number</p>
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
                    <p class="text-sm font-medium text-gray-900">PO Type</p>
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
                    <p class="text-sm font-medium text-gray-900">Department</p>
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
                    <p class="text-sm font-medium text-gray-900">Subject</p>
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
                    <p class="text-sm font-medium text-gray-900">Date</p>
                    <p class="text-sm text-gray-600">
                      {{ formatDate(purchaseOrder.tanggal) }}
                    </p>
                  </div>
                </div>

                <!-- Reguler PO specific fields -->
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
                    <p class="text-sm font-medium text-gray-900">Invoice Number</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_invoice || "-" }}
                    </p>
                  </div>
                </div>

                <!-- Lainnya PO specific fields -->
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
                    <p class="text-sm font-medium text-gray-900">Termin Reference</p>
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
                    <p class="text-sm font-medium text-gray-900">Payment Method</p>
                    <p class="text-sm text-gray-600">
                      {{ purchaseOrder.metode_pembayaran || "-" }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Payment Information Card -->
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
              <h3 class="text-lg font-semibold text-gray-900">Payment Information</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Transfer payment method fields -->
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
                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"
                    />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Account Name</p>
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
                    <p class="text-sm font-medium text-gray-900">Account Number</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_rekening || "-" }}
                    </p>
                  </div>
                </div>
              </template>

              <!-- Cek/Giro payment method fields -->
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
                    <p class="text-sm font-medium text-gray-900">Check/Giro Number</p>
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
                    <p class="text-sm font-medium text-gray-900">Giro Date</p>
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
                    <p class="text-sm font-medium text-gray-900">Maturity Date</p>
                    <p class="text-sm text-gray-600">
                      {{ formatDate(purchaseOrder.tanggal_cair) }}
                    </p>
                  </div>
                </div>
              </template>

              <!-- Kredit payment method fields -->
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
                    <p class="text-sm font-medium text-gray-900">Credit Card Number</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ purchaseOrder.no_kartu_kredit || "-" }}
                    </p>
                  </div>
                </div>
              </template>
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
              <h3 class="text-lg font-semibold text-gray-900">Order Items</h3>
              <span
                class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full"
              >
                {{ purchaseOrder.items?.length || 0 }} items
              </span>
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
              <h3 class="text-lg font-semibold text-gray-900">Additional Information</h3>
            </div>

            <div class="space-y-6">
              <div>
                <p class="text-sm font-medium text-gray-900 mb-2">Requirements Detail</p>
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
                <p class="text-sm font-medium text-gray-900 mb-2">Notes</p>
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
                <p class="text-sm font-medium text-gray-900 mb-2">Attached Document</p>
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

        <!-- Right Column - Summary & Metadata -->
        <div class="space-y-6">
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
              <h3 class="text-lg font-semibold text-gray-900">Order Summary</h3>
            </div>

            <div class="space-y-4">
              <!-- Show different summary based on PO type -->
              <template v-if="purchaseOrder.tipe_po === 'Reguler'">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Subtotal</span>
                  <span class="text-sm font-medium text-gray-900">{{
                    formatCurrency(calculateTotal())
                  }}</span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Discount</span>
                  <span class="text-sm font-medium text-red-600"
                    >-{{ formatCurrency(purchaseOrder.diskon || 0) }}</span
                  >
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">PPN (11%)</span>
                  <span class="text-sm font-medium text-gray-900">{{
                    formatCurrency(calculatePPN())
                  }}</span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">PPH</span>
                  <span class="text-sm font-medium text-gray-900">{{
                    formatCurrency(calculatePPH())
                  }}</span>
                </div>

                <div class="border-t border-gray-200 pt-4">
                  <div class="flex items-center justify-between">
                    <span class="text-lg font-semibold text-gray-900">Grand Total</span>
                    <span class="text-lg font-bold text-green-600">{{
                      formatCurrency(calculateGrandTotal())
                    }}</span>
                  </div>
                </div>
              </template>

              <template v-else-if="purchaseOrder.tipe_po === 'Lainnya'">
                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Installment Amount</span>
                  <span class="text-sm font-medium text-gray-900">{{
                    formatCurrency(purchaseOrder.cicilan || 0)
                  }}</span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Termin Total</span>
                  <span class="text-sm font-medium text-gray-900">{{
                    formatCurrency(purchaseOrder.termin?.nominal || 0)
                  }}</span>
                </div>

                <div class="flex items-center justify-between">
                  <span class="text-sm text-gray-600">Termin Progress</span>
                  <span class="text-sm font-medium text-gray-900">
                    {{
                      `${purchaseOrder.termin?.jumlah_termin_dibuat || 0} / ${
                        purchaseOrder.termin?.jumlah_termin || 0
                      }`
                    }}
                  </span>
                </div>

                <div class="border-t border-gray-200 pt-4">
                  <div class="flex items-center justify-between">
                    <span class="text-lg font-semibold text-gray-900"
                      >Current Amount</span
                    >
                    <span class="text-lg font-bold text-green-600">{{
                      formatCurrency(purchaseOrder.cicilan || 0)
                    }}</span>
                  </div>
                </div>
              </template>
            </div>

            <div class="mt-6 pt-6 border-t border-gray-200">
              <div class="text-center">
                <p class="text-xs text-gray-500 mb-2">Total Amount</p>
                <p class="text-2xl font-bold text-indigo-600">
                  {{
                    purchaseOrder.tipe_po === "Reguler"
                      ? formatCurrency(calculateGrandTotal())
                      : formatCurrency(purchaseOrder.cicilan || 0)
                  }}
                </p>
              </div>
            </div>
          </div>

          <!-- Metadata Card -->
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
                  d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2m-6 4h6m-6 0a1 1 0 00-1 1v4a1 1 0 001 1h6a1 1 0 001-1v-4a1 1 0 00-1-1"
                />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Metadata</h3>
            </div>

            <div class="space-y-4">
              <div>
                <p class="text-sm font-medium text-gray-900">Created At</p>
                <p class="text-sm text-gray-600">
                  {{ formatDate(purchaseOrder.created_at) }}
                </p>
              </div>

              <div>
                <p class="text-sm font-medium text-gray-900">Updated At</p>
                <p class="text-sm text-gray-600">
                  {{ formatDate(purchaseOrder.updated_at) }}
                </p>
              </div>

              <div v-if="purchaseOrder.creator">
                <p class="text-sm font-medium text-gray-900">Created By</p>
                <p class="text-sm text-gray-600">
                  {{ purchaseOrder.creator.name || purchaseOrder.creator }}
                </p>
              </div>

              <div v-if="purchaseOrder.updater">
                <p class="text-sm font-medium text-gray-900">Updated By</p>
                <p class="text-sm text-gray-600">
                  {{ purchaseOrder.updater.name || purchaseOrder.updater }}
                </p>
              </div>
            </div>
          </div>

          <!-- Quick Summary Card -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <h3 class="text-lg font-semibold text-gray-900 mb-4">Quick Summary</h3>

            <div class="space-y-3">
              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">PO Type</span>
                <span class="text-sm font-medium text-gray-900">{{
                  purchaseOrder.tipe_po || "-"
                }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Payment Method</span>
                <span class="text-sm font-medium text-gray-900">{{
                  purchaseOrder.metode_pembayaran || "-"
                }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Items Count</span>
                <span class="text-sm font-medium text-gray-900"
                  >{{ purchaseOrder.items?.length || 0 }} items</span
                >
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Department</span>
                <span class="text-sm font-medium text-gray-900">{{
                  purchaseOrder.department?.name || "-"
                }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Has Document</span>
                <span class="text-sm font-medium text-gray-900">{{
                  purchaseOrder.dokumen ? "Yes" : "No"
                }}</span>
              </div>

              <div class="flex items-center justify-between">
                <span class="text-sm text-gray-600">Has Requirements</span>
                <span class="text-sm font-medium text-gray-900">{{
                  purchaseOrder.detail_keperluan ? "Yes" : "No"
                }}</span>
              </div>
            </div>
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
          Back to Purchase Orders
        </button>
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
    Draft: "bg-gray-100 text-gray-800",
    "In Progress": "bg-blue-100 text-blue-800",
    Approved: "bg-green-100 text-green-800",
    Rejected: "bg-red-100 text-red-800",
    Completed: "bg-purple-100 text-purple-800",
  };
  return (
    statusClasses[status as keyof typeof statusClasses] || "bg-gray-100 text-gray-800"
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
  if (window.history.length > 1) {
    window.history.back();
  } else {
    router.visit("/purchase-orders");
  }
}
</script>

<style scoped>
/* Custom styles for consistent look */
.shadow-sm {
  box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
}

/* Hover effects */
.hover\:bg-white\/50:hover {
  background-color: rgba(255, 255, 255, 0.5);
}

/* Transition for smooth interactions */
.transition-colors {
  transition-property: color, background-color, border-color, text-decoration-color, fill,
    stroke;
  transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
  transition-duration: 200ms;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .grid-cols-1.md\:grid-cols-2 {
    grid-template-columns: repeat(1, minmax(0, 1fr));
  }
}

/* Status badge colors */
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
