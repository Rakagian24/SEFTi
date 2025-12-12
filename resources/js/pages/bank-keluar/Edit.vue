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

// interface BankSupplierAccount {
//   bank_id?: number | string;
//   nama_pemilik_rekening?: string;
//   no_rekening?: string;
// }

interface BankKeluarFormData {
  id: number | string;
  no_bk: string;
  tanggal: string;
  payment_voucher_id?: number | string | null;
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
  paymentVouchers?: any[];
  perihals?: SimpleOption[];
  suppliers: SimpleOption[];
  bisnisPartners: SimpleOption[];
  banks: SimpleOption[];
  bankSupplierAccounts: any[];
  creditCards: any[];
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
          </div>
        </div>

        <BankKeluarForm
          mode="edit"
          :bank-keluar="props.bankKeluar"
          :departments="props.departments"
          :payment-vouchers="props.paymentVouchers"
          :perihals="props.perihals"
          :suppliers="props.suppliers"
          :bisnis-partners="props.bisnisPartners"
          :banks="props.banks"
          :bank-supplier-accounts="props.bankSupplierAccounts"
          :credit-cards="props.creditCards"
        />
      </div>
    </div>
  </AppLayout>
</template>

