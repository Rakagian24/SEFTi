<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Detail Realisasi (Approval)</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <Grid2x2Check class="w-4 h-4 mr-1" />
              {{ realisasi?.no_realisasi || '-' }}
            </div>
          </div>
        </div>

        <div class="flex items-center gap-3">
          <span :class="`px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(realisasi?.status)}`">
            <div class="w-2 h-2 rounded-full mr-2 inline-block" :class="getStatusDotClass(realisasi?.status)"></div>
            {{ realisasi?.status || '-' }}
          </span>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Realisasi</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. Realisasi</p>
                    <p class="text-sm text-gray-600 font-mono">{{ realisasi?.no_realisasi || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">{{ formatDate(realisasi?.tanggal) }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">{{ realisasi?.department?.name || '-' }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                    <p class="text-sm text-gray-600">{{ realisasi?.metode_pembayaran || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nama Bank</p>
                    <p class="text-sm text-gray-600">{{ realisasi?.bank?.nama_bank || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nama Rekening</p>
                    <p class="text-sm text-gray-600">{{ realisasi?.nama_rekening || '-' }}</p>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="bg-blue-50 rounded-lg p-4 border border-blue-100">
                <p class="text-sm font-medium text-blue-800 mb-1">Total Anggaran</p>
                <p class="text-lg font-semibold text-blue-900">{{ formatCurrency(realisasi?.total_anggaran || 0) }}</p>
              </div>
              <div class="bg-green-50 rounded-lg p-4 border border-green-100">
                <p class="text-sm font-medium text-green-800 mb-1">Total Realisasi</p>
                <p class="text-lg font-semibold text-green-900">{{ formatCurrency(realisasi?.total_realisasi || 0) }}</p>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-sm font-medium text-gray-900 mb-2">Catatan</p>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-sm text-gray-700 leading-relaxed">{{ realisasi?.note || '-' }}</p>
              </div>
            </div>
          </div>

          <div v-if="realisasi?.poAnggaran" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Anggaran</h3>
              <a
                v-if="realisasi?.poAnggaran?.id"
                :href="`/po-anggaran/${realisasi.poAnggaran.id}`"
                class="ml-2 text-xs text-blue-600 hover:text-blue-800 hover:underline"
                target="_blank"
              >
                Lihat Detail
              </a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. PO Anggaran</p>
                    <p class="text-sm text-gray-600 font-mono">{{ realisasi?.poAnggaran?.no_po_anggaran || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">{{ realisasi?.poAnggaran?.department?.name || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">{{ formatDate(realisasi?.poAnggaran?.tanggal) }}</p>
                  </div>
                </div>
              </div>

              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Metode Pembayaran</p>
                    <p class="text-sm text-gray-600">{{ realisasi?.poAnggaran?.metode_pembayaran || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nominal</p>
                    <p class="text-sm text-gray-600 font-medium">{{ formatCurrency(realisasi?.poAnggaran?.nominal || 0) }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Status</p>
                    <p class="text-sm text-gray-600">
                      <span :class="`px-2 py-0.5 text-xs font-medium rounded-full ${getStatusBadgeClass(realisasi?.poAnggaran?.status)}`">
                        {{ realisasi?.poAnggaran?.status || '-' }}
                      </span>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-sm font-medium text-gray-900 mb-2">Catatan PO Anggaran</p>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-sm text-gray-700 leading-relaxed">{{ realisasi?.poAnggaran?.note || '-' }}</p>
              </div>
            </div>
            <div v-if="(realisasi?.poAnggaran?.items || []).length" class="mt-6">
              <div class="flex items-center gap-2 mb-3">
                <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                </svg>
                <h4 class="text-base font-semibold text-gray-900">Items Pengeluaran PO</h4>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                  <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                      <th class="px-4 py-3 text-left font-semibold text-gray-900">Detail</th>
                      <th class="px-4 py-3 text-left font-semibold text-gray-900">Keterangan</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Qty</th>
                      <th class="px-4 py-3 text-left font-semibold text-gray-900">Satuan</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Harga</th>
                      <th class="px-4 py-3 text-right font-semibold text-gray-900">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-gray-200">
                    <tr
                      v-for="(poIt, idx) in realisasi?.poAnggaran?.items || []"
                      :key="idx"
                      class="hover:bg-gray-50"
                    >
                      <td class="px-4 py-3 text-gray-900">{{ poIt.jenis_pengeluaran_text || '-' }}</td>
                      <td class="px-4 py-3 text-gray-700">{{ poIt.keterangan || '-' }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatQty(poIt.qty) }}</td>
                      <td class="px-4 py-3 text-gray-600">{{ poIt.satuan || '-' }}</td>
                      <td class="px-4 py-3 text-right text-gray-900">{{ formatCurrency(poIt.harga || 0) }}</td>
                      <td class="px-4 py-3 text-right font-medium text-gray-900">
                        {{ formatCurrency(poIt.subtotal ?? ((poIt.qty || 1) * (poIt.harga || 0))) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Detail Realisasi</h3>
              <span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">
                {{ realisasi?.items?.length || 0 }} item
              </span>
            </div>

            <div class="overflow-hidden">
              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                  <thead class="bg-gray-50">
                    <tr>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Detail</th>
                      <th class="px-6 py-4 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Keterangan</th>
                      <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Qty</th>
                      <th class="px-6 py-4 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">Satuan</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Harga</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Subtotal</th>
                      <th class="px-6 py-4 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Realisasi</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white divide-y divide-gray-200">
                    <tr
                      v-for="(item, index) in realisasi?.items || []"
                      :key="index"
                      class="hover:bg-gray-50 transition-colors"
                    >
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">{{ index + 1 }}</span>
                      </td>
                      <td class="px-6 py-4 text-sm text-gray-900">{{ item.jenis_pengeluaran_text || '-' }}</td>
                      <td class="px-6 py-4 text-sm text-gray-700">{{ item.keterangan || '-' }}</td>
                      <td class="px-6 py-4 text-center text-sm text-gray-900">{{ formatQty(item.qty) }}</td>
                      <td class="px-6 py-4 text-center text-sm text-gray-600">{{ item.satuan || '-' }}</td>
                      <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatCurrency(item.harga || 0) }}</td>
                      <td class="px-6 py-4 text-right text-sm font-medium text-gray-900">{{ formatCurrency(item.subtotal ?? ((item.qty || 1) * (item.harga || 0))) }}</td>
                      <td class="px-6 py-4 text-right text-sm font-semibold text-gray-900">{{ formatCurrency(item.realisasi || 0) }}</td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="space-y-6">
          <ApprovalProgress
            :progress="progress || []"
            :purchase-order="realisasi"
            :user-role="userRole || ''"
            :can-verify="!!canVerify"
            :can-validate="false"
            :can-approve="!!canApprove"
            :can-reject="false"
            @verify="handleVerify"
            @approve="handleApprove"
          />

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan</h3>
            </div>

            <div class="space-y-4">
              <!-- Financial Summary -->
              <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Total Detail Items</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(itemsTotal) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Total Anggaran</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(realisasi?.total_anggaran || 0) }}</span>
                </div>
                <div class="border-t pt-3 flex items-center justify-between">
                  <span class="text-gray-900 font-semibold">Total Realisasi</span>
                  <span class="text-gray-900 font-semibold">{{ formatCurrency(realisasi?.total_realisasi || 0) }}</span>
                </div>
                <div class="border-t pt-3 flex items-center justify-between">
                  <span class="text-gray-900 font-semibold">Selisih</span>
                  <span :class="{
                    'text-green-600 font-semibold': (realisasi?.total_anggaran || 0) >= (realisasi?.total_realisasi || 0),
                    'text-red-600 font-semibold': (realisasi?.total_anggaran || 0) < (realisasi?.total_realisasi || 0)
                  }">{{ formatCurrency((realisasi?.total_anggaran || 0) - (realisasi?.total_realisasi || 0)) }}</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Back Button -->
      <div class="mt-6">
        <button
          @click="router.visit('/approval/realisasi')"
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
          Kembali ke Daftar Approval Realisasi
        </button>
      </div>
    </div>

    <ApprovalConfirmationDialog
      :is-open="showApprovalDialog"
      @update:open="(v: boolean) => (showApprovalDialog = v)"
      @cancel="() => { showApprovalDialog = false; pendingAction = null; }"
      @confirm="handleApprovalConfirm"
    />

    <PasscodeVerificationDialog
      :is-open="showPasscodeDialog"
      :action="passcodeAction"
      :action-data="pendingAction"
      @update:open="(v: boolean) => (showPasscodeDialog = v)"
      @cancel="() => { showPasscodeDialog = false; pendingAction = null; }"
      @verified="handlePasscodeVerified"
    />

    <SuccessDialog
      :is-open="showSuccessDialog"
      :action="successAction"
      :user-name="user?.name || 'User'"
      document-type="Realisasi"
      @update:open="(v: boolean) => { showSuccessDialog = v; if (!v) { router.visit('/approval/realisasi'); } }"
      @close="() => { showSuccessDialog = false; router.visit('/approval/realisasi'); }"
    />
  </div>
</template>

<script setup lang="ts">
import { computed, ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { router, usePage } from '@inertiajs/vue3';
import { formatCurrency } from '@/lib/currencyUtils';
import ApprovalProgress from '@/components/approval/ApprovalProgress.vue';
import ApprovalConfirmationDialog from '@/components/approval/ApprovalConfirmationDialog.vue';
import PasscodeVerificationDialog from '@/components/approval/PasscodeVerificationDialog.vue';
import SuccessDialog from '@/components/approval/SuccessDialog.vue';
import { useApi } from '@/composables/useApi';
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";

defineOptions({ layout: AppLayout });

const props = defineProps<{
  realisasi: any;
  progress?: any[];
  userRole?: string;
  canVerify?: boolean;
  canApprove?: boolean;
}>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Approval', href: '/approval' },
  { label: 'Realisasi', href: '/approval/realisasi' },
  { label: 'Detail' },
];

const realisasi = props.realisasi;
const progress = props.progress || [];
const userRole = props.userRole || '';
const canVerify = props.canVerify ?? false;
const canApprove = props.canApprove ?? false;

function formatDate(value?: string) {
  if (!value) return '-';
  const d = new Date(value);
  if (isNaN(d.getTime())) return value;
  return d.toLocaleDateString('id-ID', { year: 'numeric', month: 'long', day: 'numeric' });
}

function formatQty(val: any) {
  const n = Number(val);
  if (!isFinite(n) || isNaN(n)) return '1';
  const isInt = Math.floor(n) === n;
  return new Intl.NumberFormat('id-ID', {
    minimumFractionDigits: isInt ? 0 : 0,
    maximumFractionDigits: isInt ? 0 : 2,
  }).format(n);
}

function getStatusBadgeClass(status: string) {
  return getSharedStatusBadgeClass(status);
}

function getStatusDotClass(status: string) {
  return getSharedStatusDotClass(status);
}

const itemsTotal = computed(() => {
  const items = (realisasi?.items || []) as any[];
  return items.reduce((acc, it) => acc + Number(it.subtotal ?? (Number(it.qty || 1) * Number(it.harga || 0))), 0);
});

const showApprovalDialog = ref(false);
const showPasscodeDialog = ref(false);
const showSuccessDialog = ref(false);
const passcodeAction = ref<'verify' | 'approve'>('approve');
const successAction = ref<'verify' | 'approve'>('approve');
const pendingAction = ref<{
  type: 'single';
  action: 'verify' | 'approve';
  ids: number[];
  singleItem?: any;
} | null>(null);

const { post } = useApi();

function handleVerify() {
  if (!realisasi?.id) return;
  pendingAction.value = {
    type: 'single',
    action: 'verify',
    ids: [realisasi.id],
    singleItem: realisasi,
  };
  showApprovalDialog.value = true;
}

function handleApprove() {
  if (!realisasi?.id) return;
  pendingAction.value = {
    type: 'single',
    action: 'approve',
    ids: [realisasi.id],
    singleItem: realisasi,
  };
  showApprovalDialog.value = true;
}

const handleApprovalConfirm = () => {
  if (!pendingAction.value) return;
  showApprovalDialog.value = false;
  passcodeAction.value = pendingAction.value.action;
  showPasscodeDialog.value = true;
};

async function handlePasscodeVerified() {
  try {
    if (!pendingAction.value) return;
    const id = pendingAction.value.ids[0];
    if (pendingAction.value.action === 'verify') {
      await post(`/api/approval/realisasis/${id}/verify`);
      (realisasi as any).status = 'Verified';
    } else if (pendingAction.value.action === 'approve') {
      await post(`/api/approval/realisasis/${id}/approve`);
      (realisasi as any).status = 'Approved';
    }
    successAction.value = pendingAction.value.action;
    showPasscodeDialog.value = false;
    showSuccessDialog.value = true;
  } catch {
    showPasscodeDialog.value = false;
  } finally {
    pendingAction.value = null;
  }
}

const page = usePage();
const user = page.props.auth?.user;
</script>
