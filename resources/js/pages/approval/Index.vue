<template>
    <div class="min-h-screen bg-[#DFECF2]">
        <div class="pt-6 pr-6 pb-6 pl-2">
            <Breadcrumbs :items="breadcrumbs" />

            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Approval</h1>
                    <div class="mt-2 flex items-center text-sm text-gray-500">
                        <SquareCheck class="mr-1 h-4 w-4" />
                        Dokumen yang menunggu persetujuan
                    </div>
                </div>
            </div>

            <!-- Statistics Cards Grid -->
            <div class="mb-8 grid grid-cols-2 gap-6 lg:grid-cols-4">
                <!-- Purchase Order Card -->
                <ApprovalCard
                    v-if="canAccess('purchase_order')"
                    title="Purchase Order"
                    :count="purchaseOrderCount"
                    :icon="''"
                    color="blue"
                    href="/approval/purchase-orders"
                    :loading="loading.purchaseOrder"
                />


                <!-- BPB Card -->
                <ApprovalCard
                    v-if="canAccess('bpb')"
                    title="BPB"
                    :count="bpbCount"
                    :icon="''"
                    color="indigo"
                    href="/approval/bpbs"
                    :loading="loading.bpb"
                />

                <!-- Memo Pembayaran Card -->
                <ApprovalCard
                    v-if="canAccess('memo_pembayaran')"
                    title="Memo Pembayaran"
                    :count="memoPembayaranCount"
                    :icon="''"
                    color="teal"
                    href="/approval/memo-pembayaran"
                    :loading="loading.memoPembayaran"
                />

                <!-- Payment Voucher Card -->
                <ApprovalCard
                    v-if="canAccess('payment_voucher')"
                    title="Payment Voucher"
                    :count="paymentVoucherCount"
                    :icon="''"
                    color="green"
                    href="/approval/payment-vouchers"
                    :loading="loading.paymentVoucher"
                />

                <!-- Anggaran (PO Anggaran) Card -->
                <ApprovalCard
                    v-if="canAccess('anggaran')"
                    title="Anggaran"
                    :count="anggaranCount"
                    :icon="''"
                    color="purple"
                    href="/approval/po-anggaran"
                    :loading="loading.anggaran"
                />

                <!-- Realisasi Card -->
                <ApprovalCard
                    v-if="canAccess('realisasi')"
                    title="Realisasi"
                    :count="realisasiCount"
                    :icon="''"
                    color="orange"
                    href="/approval/realisasi"
                    :loading="loading.realisasi"
                />

                <!-- Pelunasan Card -->
                <!-- <ApprovalCard
                    v-if="canAccess('pelunasan')"
                    title="Pelunasan"
                    :count="pelunasanCount"
                    icon="file-text"
                    color="red"
                    href="/approval/pelunasan"
                    :loading="loading.pelunasan"
                /> -->
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import ApprovalCard from '@/components/approval/ApprovalCard.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import { useApi } from '@/composables/useApi';
import AppLayout from '@/layouts/AppLayout.vue';
import { SquareCheck } from 'lucide-vue-next';
import { onMounted, ref } from 'vue';

defineOptions({ layout: AppLayout });

// Get user role and permissions from Inertia props
const props = defineProps<{
    userRole: string;
    userPermissions: string[];
}>();

const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Approval' }];

// Initialize API composable
const { get } = useApi();

// Loading states
const loading = ref({
    purchaseOrder: false,
    paymentVoucher: false,
    anggaran: false,
    realisasi: false,
    bpb: false,
    memoPembayaran: false,
    pelunasan: false,
});

// Document counts
const purchaseOrderCount = ref(0);
const paymentVoucherCount = ref(0);
const anggaranCount = ref(0);
const realisasiCount = ref(0);
const bpbCount = ref(0);
const memoPembayaranCount = ref(0);
// const pelunasanCount = ref(5);

// Check if user can access specific document type based on role
const canAccess = (documentType: string): boolean => {
    const role = props.userRole;

    switch (role) {
        case 'Kepala Toko':
            return ['purchase_order', 'memo_pembayaran', 'bpb', 'anggaran', 'realisasi'].includes(documentType);

        case 'Kabag':
            return ['purchase_order', 'memo_pembayaran', 'bpb', 'payment_voucher', 'anggaran', 'realisasi', 'pelunasan'].includes(documentType);

        case 'Staff Akunting & Finance':
            return ['purchase_order', 'memo_pembayaran', 'bpb', 'payment_voucher', 'anggaran', 'realisasi'].includes(documentType);

        case 'Kadiv':
            return ['purchase_order', 'memo_pembayaran', 'payment_voucher', 'anggaran', 'realisasi'].includes(documentType);

        case 'Direksi':
            return ['purchase_order', 'memo_pembayaran', 'payment_voucher', 'anggaran', 'realisasi'].includes(documentType);

        default:
            return true;
    }
};

// Fetch document counts
const fetchDocumentCounts = async () => {
    try {
        // === Purchase Order ===
        if (canAccess('purchase_order')) {
            loading.value.purchaseOrder = true;
            try {
                const data = await get('/api/approval/purchase-orders/count');

                // isi count untuk card
                purchaseOrderCount.value = data.count || 0;
            } catch (error) {
                console.error('Error fetching purchase order count:', error);
                purchaseOrderCount.value = 0;
            } finally {
                loading.value.purchaseOrder = false;
            }
        } else {
            purchaseOrderCount.value = 0;
        }

        // === PO Anggaran ===
        if (canAccess('anggaran')) {
            loading.value.anggaran = true;
            try {
                const data = await get('/api/approval/po-anggaran/count');
                anggaranCount.value = data.count || 0;
            } catch (error) {
                console.error('Error fetching anggaran count:', error);
                anggaranCount.value = 0;
            } finally {
                loading.value.anggaran = false;
            }
        } else {
            anggaranCount.value = 0;
        }

        // === Realisasi ===
        if (canAccess('realisasi')) {
            loading.value.realisasi = true;
            try {
                const data = await get('/api/approval/realisasi/count');
                realisasiCount.value = data.count || 0;
            } catch (error) {
                console.error('Error fetching realisasi count:', error);
                realisasiCount.value = 0;
            } finally {
                loading.value.realisasi = false;
            }
        } else {
            realisasiCount.value = 0;
        }

        // === Memo Pembayaran ===
        if (canAccess('memo_pembayaran')) {
            loading.value.memoPembayaran = true;
            try {
                const data = await get('/api/approval/memo-pembayaran/count');

                // isi count untuk card
                memoPembayaranCount.value = data.count || 0;
            } catch (error) {
                console.error('Error fetching memo pembayaran count:', error);
                memoPembayaranCount.value = 0;
            } finally {
                loading.value.memoPembayaran = false;
            }
        } else {
            memoPembayaranCount.value = 0;
        }

        // === Payment Voucher ===
        if (canAccess('payment_voucher')) {
            loading.value.paymentVoucher = true;
            try {
                const data = await get('/api/approval/payment-vouchers/count');

                // isi count untuk card
                paymentVoucherCount.value = data.count || 0;
            } catch (error) {
                console.error('Error fetching payment voucher count:', error);
                paymentVoucherCount.value = 0;
            } finally {
                loading.value.paymentVoucher = false;
            }
        } else {
            paymentVoucherCount.value = 0;
        }

        // === BPB ===
        if (canAccess('bpb')) {
            loading.value.bpb = true;
            try {
                const data = await get('/api/approval/bpbs/count');
                bpbCount.value = data.count || 0;
            } catch (error) {
                console.error('Error fetching BPB count:', error);
                bpbCount.value = 0;
            } finally {
                loading.value.bpb = false;
            }
        } else {
            bpbCount.value = 0;
        }

        // Note: kalau nanti document type lain aktif, tinggal tambah dengan pola sama
    } catch (error) {
        console.error('Error fetching document counts:', error);
        // fallback default
        purchaseOrderCount.value = 8;
        memoPembayaranCount.value = 5;
    }
};

// Initialize and fetch counts
onMounted(async () => {
    // User role and permissions are now passed as props from the backend
    // No need to make API calls for authentication data

    // Try to fetch data, but don't fail if authentication is not working
    try {
        await fetchDocumentCounts();
    } catch (error) {
        console.error('Failed to fetch some data, using fallback values:', error);
        // Set fallback values
        purchaseOrderCount.value = 8;
        memoPembayaranCount.value = 5;
        paymentVoucherCount.value = 3;
    }
});
</script>
