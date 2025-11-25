<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import BankKeluarForm from '@/components/bank-keluar/BankKeluarForm.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { getIconForPage } from '@/lib/iconMapping';

interface SimpleOption {
  id: number | string;
  name?: string;
  nama?: string;
}

interface BankSupplierAccount {
  bank_id?: number | string;
  nama_pemilik_rekening?: string;
  no_rekening?: string;
}

interface PaymentVoucher {
  id: number | string;
  no_pv: string;
  nominal: number;
  supplier?: { nama?: string };
  tipe_pv?: string;
  department_id?: number | string;
  perihal_id?: number | string;
  metode_bayar?: string;
  supplier_id?: number | string;
  bank_supplier_account?: BankSupplierAccount | null;
}

interface BankKeluarFormData {
  id: number | string;
  no_bk: string;
  tanggal: string;
  payment_voucher_id?: number | string | null;
  tipe_pv?: string | null;
  department_id: number | string;
  perihal_id?: number | string | null;
  nominal: number | string;
  metode_bayar: string;
  supplier_id?: number | string | null;
  bank_id?: number | string | null;
  nama_pemilik_rekening?: string;
  no_rekening?: string;
  note?: string | null;
}

const props = defineProps<{
  bankKeluar: BankKeluarFormData;
  departments: SimpleOption[];
  paymentVouchers: PaymentVoucher[];
  perihals: SimpleOption[];
  suppliers: SimpleOption[];
  banks: SimpleOption[];
}>();

const breadcrumbs = [
  { label: 'Home', href: '/dashboard' },
  { label: 'Bank Keluar', href: '/bank-keluar' },
  { label: 'Edit' },
];
</script>

<template>
  <AppLayout>
    <Head title="Edit Bank Keluar" />

    <div class="bg-[#DFECF2] min-h-screen">
      <div class="pl-2 pt-6 pr-6 pb-6">
        <Breadcrumbs :items="breadcrumbs" />

        <div class="flex items-center justify-between mb-6 mt-4">
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Edit Bank Keluar</h1>
            <div class="flex items-center mt-2 text-sm text-gray-500">
              <component :is="getIconForPage('Bank Keluar')" class="w-4 h-4 mr-1" />
              <span>Edit dokumen Bank Keluar</span>
            </div>
            <p class="mt-1 text-sm text-gray-600">No. BK: {{ props.bankKeluar.no_bk }}</p>
          </div>
        </div>

        <BankKeluarForm
          mode="edit"
          :bank-keluar="props.bankKeluar"
          :departments="props.departments"
          :payment-vouchers="props.paymentVouchers"
          :perihals="props.perihals"
          :suppliers="props.suppliers"
          :banks="props.banks"
        />
      </div>
    </div>
  </AppLayout>
</template>

