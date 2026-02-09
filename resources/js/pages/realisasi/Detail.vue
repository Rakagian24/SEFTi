<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="px-4 pt-4 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />

      <!-- Header -->
      <div class="mb-4 flex items-start justify-between gap-3 md:mb-6">
        <div class="flex items-center gap-4">
          <div>
            <h1 class="text-xl font-bold text-gray-900 md:text-2xl">Detail Realisasi</h1>
            <div class="mt-2 flex items-center text-xs text-gray-500 md:text-sm">
              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4" />
              </svg>
              {{ realisasi.no_realisasi || '-' }}
            </div>
          </div>
        </div>

        <div class="flex flex-col items-end gap-2">
          <span :class="`inline-flex items-center px-3 py-1 text-xs font-medium rounded-full ${getStatusBadgeClass(realisasi.status)}`">
            <div class="w-2 h-2 rounded-full mr-2 inline-block" :class="getStatusDotClass(realisasi.status)"></div>
            {{ realisasi.status || '-' }}
          </span>

          <!-- Download Button (desktop) -->
          <a
            v-if="realisasi?.status !== 'Canceled' && realisasi?.no_realisasi"
            :href="`/realisasi/${realisasi.id}/download`"
            target="_blank"
            class="hidden items-center gap-1.5 rounded-md bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 transition-colors duration-200 hover:bg-blue-100 md:flex"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
            </svg>
            Download PDF
          </a>
        </div>
      </div>

      <!-- Mobile actions: Download & Log -->
      <div class="mb-4 flex items-center gap-2 md:hidden">
        <button
          type="button"
          class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm active:bg-gray-50"
          @click="downloadRealisasiMobile"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 4v12m0 0l-4-4m4 4l4-4M4 20h16"
            />
          </svg>
          <span>Download</span>
        </button>

        <button
          type="button"
          class="inline-flex flex-1 items-center justify-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs font-medium text-gray-700 shadow-sm active:bg-gray-50"
          @click="goToLogMobile"
        >
          <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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

      <div
        v-if="realisasi?.status === 'Rejected' && realisasi?.rejection_reason"
        class="bg-white rounded-lg shadow-sm border border-red-200 p-6 mb-6"
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
            <div class="text-sm font-semibold text-red-700">Alasan Penolakan</div>
            <p class="text-sm text-red-700 mt-1 whitespace-pre-wrap">
              {{ realisasi.rejection_reason }}
            </p>
          </div>
        </div>
      </div>

      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column: 5 Cards -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Card 1: Informasi Realisasi -->
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
                    <p class="text-sm text-gray-600 font-mono">{{ realisasi.no_realisasi || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">{{ formatDate(realisasi.tanggal) }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">{{ realisasi.department?.name || '-' }}</p>
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
                    <p class="text-sm text-gray-600">{{ realisasi.metode_pembayaran || '-' }}</p>
                  </div>
                </div>
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7h18M3 12h18M3 17h18" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Perihal</p>
                    <p class="text-sm text-gray-600">
                      {{ (realisasi.poAnggaran || realisasi.po_anggaran)?.perihal?.nama || (realisasi.poAnggaran || realisasi.po_anggaran)?.perihal_name || '-' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 2: Informasi PO Anggaran Terkait -->
          <div v-if="realisasi.poAnggaran" class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Anggaran</h3>
              <a
                v-if="realisasi.poAnggaran?.id"
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
                    <p class="text-sm text-gray-600 font-mono">{{ realisasi.poAnggaran?.no_po_anggaran || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">{{ realisasi.poAnggaran?.department?.name || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5a2 2 0 012-2h4a2 2 0 012 2v2" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Tanggal</p>
                    <p class="text-sm text-gray-600">{{ formatDate(realisasi.poAnggaran?.tanggal) }}</p>
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
                    <p class="text-sm text-gray-600">{{ realisasi.poAnggaran?.metode_pembayaran || '-' }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Nominal</p>
                    <p class="text-sm text-gray-600 font-medium">{{ formatCurrency(realisasi.poAnggaran?.nominal || 0) }}</p>
                  </div>
                </div>

                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">Status</p>
                    <p class="text-sm text-gray-600">
                      <span :class="`px-2 py-0.5 text-xs font-medium rounded-full ${getStatusBadgeClass(realisasi.poAnggaran?.status)}`">
                        {{ realisasi.poAnggaran?.status || '-' }}
                      </span>
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-6">
              <p class="text-sm font-medium text-gray-900 mb-2">Catatan PO Anggaran</p>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-sm text-gray-700 leading-relaxed">{{ realisasi.poAnggaran?.note || '-' }}</p>
              </div>
            </div>

            <div v-if="(realisasi.poAnggaran?.items || []).length" class="mt-6">
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
                      v-for="(poIt, idx) in realisasi.poAnggaran?.items || []"
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

          <!-- Card 3: Informasi Bisnis Partner -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.121 17.804A13.937 13.937 0 0112 15c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Bisnis Partner</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <!-- Kolom kiri: Bisnis Partner -->
              <div class="space-y-4">
                <div>
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Bisnis Partner</p>
                  <p class="text-sm font-medium text-gray-900">Nama Bisnis Partner</p>
                  <p class="text-sm text-gray-700">
                    {{ (realisasi.bisnisPartner || realisasi.bisnis_partner)?.nama_bp || '-' }}
                  </p>
                </div>

                <div>
                  <p class="text-sm font-medium text-gray-900">Alamat</p>
                  <p class="text-sm text-gray-700 whitespace-pre-line">
                    {{ (realisasi.bisnisPartner || realisasi.bisnis_partner)?.alamat || '-' }}
                  </p>
                </div>

                <div>
                  <p class="text-sm font-medium text-gray-900">No. Telepon</p>
                  <p class="text-sm text-gray-700">
                    {{ (realisasi.bisnisPartner || realisasi.bisnis_partner)?.no_telepon || '-' }}
                  </p>
                </div>
              </div>

              <!-- Kolom kanan: Rekening Bank -->
              <div class="space-y-4">
                <div>
                  <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Rekening Bank</p>
                  <p class="text-sm font-medium text-gray-900">Nama Bank</p>
                  <p class="text-sm text-gray-700">{{ realisasi.bank?.nama_bank || '-' }}</p>
                </div>

                <div>
                  <p class="text-sm font-medium text-gray-900">Nama Rekening</p>
                  <p class="text-sm text-gray-700">{{ realisasi.nama_rekening || '-' }}</p>
                </div>

                <div>
                  <p class="text-sm font-medium text-gray-900">No. Rekening/VA</p>
                  <p class="text-sm text-gray-700 font-mono">{{ realisasi.no_rekening || '-' }}</p>
                </div>
              </div>
            </div>
          </div>

          <!-- Card 4: Detail Realisasi -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-6a2 2 0 00-2-2h-2" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Detail Realisasi</h3>
              <span class="ml-2 px-2 py-1 text-xs font-medium bg-gray-100 text-gray-700 rounded-full">
                {{ (realisasi.items || []).length }} item
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
                    <tr v-for="(it, idx) in realisasi.items || []" :key="idx" class="hover:bg-gray-50 transition-colors">
                      <td class="px-6 py-4 whitespace-nowrap">
                        <span class="text-sm font-medium text-gray-900">{{ Number(idx) + 1 }}</span>
                      </td>
                      <td class="px-6 py-4"><span class="text-sm font-medium text-gray-900">{{ it.jenis_pengeluaran_text || '-' }}</span></td>
                      <td class="px-6 py-4"><span class="text-sm text-gray-700">{{ it.keterangan || '-' }}</span></td>
                      <td class="px-6 py-4 text-center"><span class="text-sm text-gray-900">{{ formatQty(it.qty) }}</span></td>
                      <td class="px-6 py-4 text-center"><span class="text-sm text-gray-600">{{ it.satuan || '-' }}</span></td>
                      <td class="px-6 py-4 text-right"><span class="text-sm font-medium text-gray-900">{{ formatCurrency(it.harga || 0) }}</span></td>
                      <td class="px-6 py-4 text-right"><span class="text-sm font-medium text-gray-900">{{ formatCurrency(it.subtotal ?? ((it.qty || 1) * (it.harga || 0))) }}</span></td>
                      <td class="px-6 py-4 text-right"><span class="text-sm font-semibold text-gray-900">{{ formatCurrency(it.realisasi || 0) }}</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          <!-- Card 5: Informasi Tambahan -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Informasi Tambahan</h3>
            </div>

            <div>
              <p class="text-sm font-medium text-gray-900 mb-2">Catatan</p>
              <div class="bg-gray-50 rounded-lg p-4 border border-gray-100">
                <p class="text-sm text-gray-700 leading-relaxed">{{ realisasi.note || '-' }}</p>
              </div>
            </div>
          </div>

          <!-- PO Anggaran Terkait (mirip Informasi PO Terkait di Memo Pembayaran) -->
          <div
            v-if="realisasi.poAnggaran || realisasi.po_anggaran"
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
              <h3 class="text-lg font-semibold text-gray-900">Informasi PO Anggaran Terkait</h3>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="space-y-4">
                <div class="flex items-start gap-3">
                  <svg class="w-5 h-5 text-gray-400 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6M7 20h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v11a2 2 0 002 2z" />
                  </svg>
                  <div>
                    <p class="text-sm font-medium text-gray-900">No. PO Anggaran</p>
                    <p class="text-sm text-gray-600 font-mono">
                      {{ (realisasi.poAnggaran || realisasi.po_anggaran)?.no_po_anggaran || '-' }}
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
                      {{ formatDate((realisasi.poAnggaran || realisasi.po_anggaran)?.tanggal) }}
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
                    <p class="text-sm font-medium text-gray-900">Departemen</p>
                    <p class="text-sm text-gray-600">
                      {{ (realisasi.poAnggaran || realisasi.po_anggaran)?.department?.name || '-' }}
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
                      {{ (realisasi.poAnggaran || realisasi.po_anggaran)?.perihal?.nama || (realisasi.poAnggaran || realisasi.po_anggaran)?.perihal_name || '-' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Dokumen Pendukung (hanya list dokumen) -->
          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Dokumen Pendukung</h3>
            </div>

            <div v-if="uploadedDocs.length" class="border-t border-gray-200 pt-4">
              <div class="space-y-2">
                <div
                  v-for="d in uploadedDocs"
                  :key="d.id"
                  class="flex items-center justify-between bg-gray-50 rounded-md px-3 py-2"
                >
                  <div class="flex flex-col">
                    <span class="text-sm font-medium text-blue-700">
                      {{ getDocLabel(d.type) }}
                    </span>
                    <span class="text-xs text-gray-600 break-all">
                      {{ d.original_name || d.path || '-' }}
                    </span>
                  </div>
                  <a
                    :href="`/realisasi/documents/${d.id}/download`"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800 flex items-center gap-1"
                    target="_blank"
                  >
                    <span>Download</span>
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Right Column: Approval Progress + Ringkasan Pembayaran -->
        <div class="space-y-6">
          <ApprovalProgress
            :progress="progress || []"
            :purchase-order="realisasi"
            :user-role="userRole || ''"
            :can-verify="false"
            :can-validate="false"
            :can-approve="false"
            :can-reject="false"
          />

          <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center gap-2 mb-4">
              <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
              </svg>
              <h3 class="text-lg font-semibold text-gray-900">Ringkasan Pembayaran</h3>
            </div>

            <div class="space-y-4">
              <div class="space-y-3">
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Total Anggaran</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(realisasi.total_anggaran || 0) }}</span>
                </div>
                <div class="flex items-center justify-between text-sm">
                  <span class="text-gray-600">Total Realisasi</span>
                  <span class="font-medium text-gray-900">{{ formatCurrency(realisasi.total_realisasi || 0) }}</span>
                </div>
                <div class="border-t pt-3 flex items-center justify-between">
                  <span class="text-gray-900 font-semibold">Selisih</span>
                  <span
                    :class="{
                      'text-green-600 font-semibold': (realisasi.total_anggaran || 0) >= (realisasi.total_realisasi || 0),
                      'text-red-600 font-semibold': (realisasi.total_anggaran || 0) < (realisasi.total_realisasi || 0),
                    }"
                  >
                    {{ formatCurrency((realisasi.total_anggaran || 0) - (realisasi.total_realisasi || 0)) }}
                  </span>
                </div>
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
          Kembali ke Daftar Realisasi
        </button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { formatCurrency } from '@/lib/currencyUtils';
import {
  getStatusBadgeClass as getSharedStatusBadgeClass,
  getStatusDotClass as getSharedStatusDotClass,
} from "@/lib/status";
import ApprovalProgress from '@/components/approval/ApprovalProgress.vue';

defineOptions({ layout: AppLayout });
const props = defineProps<{ realisasi: any; progress?: any[]; userRole?: string }>();
const realisasi = props.realisasi as any;
const progress = props.progress || [];
const page = usePage();
const userRole = props.userRole || (((page.props as any)?.auth?.user?.role?.name) || '');
const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Realisasi', href: '/realisasi' }, { label: 'Detail' }];

const uploadedDocs = (realisasi.documents || []).filter(
  (d: any) => d && d.active && d.path
);

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

function getDocLabel(type: string): string {
  const map: Record<string, string> = {
    transport: 'Bukti Transport',
    konsumsi: 'Bukti Konsumsi',
    hotel: 'Bukti Hotel',
    uang_saku: 'Bukti Uang Saku',
    lainnya: 'Lampiran Lainnya',
  };

  return map[type] || type;
}

function goBack() { history.back(); }

function downloadRealisasiMobile() {
  const realisasi = props.realisasi as any;
  if (!realisasi?.id) return;

  const link = document.createElement('a');
  link.href = `/realisasi/${realisasi.id}/download`;
  link.target = '_blank';
  link.download = `Realisasi_${realisasi.no_realisasi || 'Dokumen'}.pdf`;

  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}

function goToLogMobile() {
  const realisasi = props.realisasi as any;
  if (!realisasi?.id) return;
  router.visit(`/realisasi/${realisasi.id}/log`);
}
</script>

