<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { formatCurrencyWithSymbol } from '@/lib/currencyUtils';

interface BankKeluarDocument {
  id: number | string;
  original_filename: string;
}

interface BankKeluarRelationOption {
  name?: string;
  nama?: string;
}

interface BankKeluar {
  id: number | string;
  no_bk: string;
  tanggal: string | Date;
  department?: BankKeluarRelationOption | null;
  perihal?: BankKeluarRelationOption | null;
  nominal: number;
  metode_bayar: string;
  payment_voucher?: { no_pv?: string } | null;
  supplier?: { nama?: string } | null;
  bank?: { nama?: string } | null;
  nama_pemilik_rekening?: string | null;
  no_rekening?: string | null;
  status: string;
  note?: string | null;
  creator?: { name?: string } | null;
  updater?: { name?: string } | null;
  created_at?: string | Date;
  updated_at?: string | Date;
  documents?: BankKeluarDocument[];
}

const { bankKeluar } = defineProps<{
  bankKeluar: BankKeluar;
}>();

function formatDate(date: string | Date | null | undefined): string {
  if (!date) return '-';
  return new Date(date).toLocaleDateString('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
  });
}
</script>

<template>
  <AppLayout>
    <Head title="Bank Keluar Detail" />

    <div class="py-6">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center">
          <div>
            <h1 class="text-2xl font-semibold text-gray-900">Bank Keluar Detail</h1>
            <p class="mt-1 text-sm text-gray-600">{{ bankKeluar.no_bk }}</p>
          </div>
          <div class="flex space-x-2">
            <Link
              :href="route('bank-keluar.index')"
              class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg
                class="mr-2 -ml-1 h-5 w-5 text-gray-500"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M10 19l-7-7m0 0l7-7m-7 7h18"
                />
              </svg>
              Back to List
            </Link>
            <Link
              :href="route('bank-keluar.edit', bankKeluar.id)"
              class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <svg
                class="mr-2 -ml-1 h-5 w-5"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                />
              </svg>
              Edit
            </Link>
          </div>
        </div>

        <div class="mt-6">
          <div class="bg-white shadow-sm rounded-lg overflow-hidden">
            <div class="px-4 py-5 sm:p-6">
              <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <h3 class="text-lg font-medium text-gray-900">Bank Keluar Information</h3>
                  <div class="mt-4 border-t border-gray-200 pt-4">
                    <dl class="divide-y divide-gray-200">
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">No. BK</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.no_bk }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Tanggal</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ formatDate(bankKeluar.tanggal) }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Department</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.department?.name || '-' }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Perihal</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.perihal?.name || '-' }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Nominal</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ formatCurrencyWithSymbol(bankKeluar.nominal, 'IDR') }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Metode Bayar</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.metode_bayar }}</dd>
                      </div>
                    </dl>
                  </div>
                </div>

                <div>
                  <h3 class="text-lg font-medium text-gray-900">Payment Details</h3>
                  <div class="mt-4 border-t border-gray-200 pt-4">
                    <dl class="divide-y divide-gray-200">
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Payment Voucher</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.payment_voucher?.no_pv || '-' }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Supplier</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.supplier?.nama || '-' }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Bank</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.bank?.nama || '-' }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Nama Pemilik Rekening</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.nama_pemilik_rekening || '-' }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">No. Rekening</dt>
                        <dd class="text-sm font-medium text-gray-900">{{ bankKeluar.no_rekening || '-' }}</dd>
                      </div>
                      <div class="py-3 flex justify-between">
                        <dt class="text-sm font-medium text-gray-500">Status</dt>
                        <dd class="text-sm font-medium">
                          <span
                            :class="{
                              'bg-green-100 text-green-800': bankKeluar.status === 'aktif',
                              'bg-red-100 text-red-800': bankKeluar.status === 'batal',
                            }"
                            class="px-2 py-1 rounded-full text-xs"
                          >
                            {{ bankKeluar.status === 'aktif' ? 'Aktif' : 'Batal' }}
                          </span>
                        </dd>
                      </div>
                    </dl>
                  </div>
                </div>
              </div>

              <div class="mt-6">
                <h3 class="text-lg font-medium text-gray-900">Additional Information</h3>
                <div class="mt-4 border-t border-gray-200 pt-4">
                  <dl class="divide-y divide-gray-200">
                    <div class="py-3">
                      <dt class="text-sm font-medium text-gray-500">Note</dt>
                      <dd class="mt-1 text-sm text-gray-900">{{ bankKeluar.note || '-' }}</dd>
                    </div>
                    <div class="py-3">
                      <dt class="text-sm font-medium text-gray-500">Created By</dt>
                      <dd class="mt-1 text-sm text-gray-900">{{ bankKeluar.creator?.name || '-' }} ({{ formatDate(bankKeluar.created_at) }})</dd>
                    </div>
                    <div class="py-3">
                      <dt class="text-sm font-medium text-gray-500">Last Updated By</dt>
                      <dd class="mt-1 text-sm text-gray-900">{{ bankKeluar.updater?.name || '-' }} ({{ formatDate(bankKeluar.updated_at) }})</dd>
                    </div>
                  </dl>
                </div>
              </div>

              <div v-if="bankKeluar.documents && bankKeluar.documents.length > 0" class="mt-6">
                <h3 class="text-lg font-medium text-gray-900">Documents</h3>
                <div class="mt-4 border-t border-gray-200 pt-4">
                  <ul class="divide-y divide-gray-200">
                    <li v-for="doc in bankKeluar.documents" :key="doc.id" class="py-3 flex justify-between items-center">
                      <div class="flex items-center">
                        <svg class="h-5 w-5 text-gray-400 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z" />
                        </svg>
                        <span class="text-sm text-gray-900">{{ doc.original_filename }}</span>
                      </div>
                      <div class="flex space-x-2">
                        <a
                          :href="route('bank-keluar.documents.download', doc.id)"
                          class="text-blue-600 hover:text-blue-900 text-sm"
                          target="_blank"
                        >
                          Download
                        </a>
                      </div>
                    </li>
                  </ul>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
