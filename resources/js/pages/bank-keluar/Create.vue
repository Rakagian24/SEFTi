<script setup lang="ts">
import BankKeluarForm from '@/components/bank-keluar/BankKeluarForm.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { getIconForPage } from '@/lib/iconMapping';
import { Head } from '@inertiajs/vue3';

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
    remaining_nominal?: number;
    supplier?: { nama?: string };
    tipe_pv?: string;
    department_id?: number | string;
    perihal_id?: number | string;
    metode_bayar?: string;
    supplier_id?: number | string;
    bank_supplier_account?: BankSupplierAccount | null;
}

const props = defineProps<{
    departments: SimpleOption[];
    paymentVouchers: PaymentVoucher[];
    perihals: SimpleOption[];
    suppliers: SimpleOption[];
    bisnisPartners: SimpleOption[];
    banks: SimpleOption[];
    bankSupplierAccounts: any[];
    creditCards: any[];
}>();

const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Bank Keluar', href: '/bank-keluar' }, { label: 'Buat Baru' }];
</script>

<template>
    <AppLayout>
        <Head title="Buat Bank Keluar" />

        <div class="min-h-screen bg-[#DFECF2]">
            <div class="pt-6 pr-6 pb-6 pl-2">
                <Breadcrumbs :items="breadcrumbs" />

                <div class="mt-4 mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Buat Bank Keluar</h1>
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <component :is="getIconForPage('Bank Keluar')" class="mr-1 h-4 w-4" />
                            <span>Buat dokumen Bank Keluar baru</span>
                        </div>
                    </div>
                </div>
                <BankKeluarForm
                    class="mt-6"
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
