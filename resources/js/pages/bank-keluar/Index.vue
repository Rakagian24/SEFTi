<script setup lang="ts">
import BankKeluarFilter from '@/components/bank-keluar/BankKeluarFilter.vue';
import BankKeluarTable from '@/components/bank-keluar/BankKeluarTable.vue';
import Breadcrumbs from '@/components/ui/Breadcrumbs.vue';
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import MessagePanel from '@/components/ui/MessagePanel.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { getIconForPage } from '@/lib/iconMapping';
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { useMessagePanel } from '@/composables/useMessagePanel';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Pagination<T> {
    data: T[];
    current_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url?: string | null;
    next_page_url?: string | null;
    links: PaginationLink[];
}

interface SimpleOption {
    id: number | string;
    name?: string;
    nama?: string;
}

interface Filters {
    no_bk: string;
    no_pv: string;
    department_id: string | number | null;
    supplier_id: string | number | null;
    start: string | null;
    end: string | null;
    search: string;
}

interface BankKeluarRow {
    id: number | string;
    no_bk: string;
    tanggal: string | Date;
    tipe_bk?: string | null;
    department?: { name?: string } | null;
    metode_bayar: string;
    supplier?: { nama_supplier?: string; nama?: string } | null;
    bisnis_partner?: { nama_bp?: string; nama?: string } | null;
    nominal: number;
    note?: string | null;
}

const breadcrumbs = [{ label: 'Home', href: '/dashboard' }, { label: 'Bank Keluar' }];

const props = defineProps<{
    bankKeluars: Pagination<BankKeluarRow>;
    filters: Partial<Filters>;
    departments: SimpleOption[];
    suppliers: SimpleOption[];
    sortBy?: string | null;
    sortDirection?: 'asc' | 'desc' | null;
    per_page: number;
}>();

const showConfirmDelete = ref(false);
const bankKeluarToDelete = ref<BankKeluarRow | null>(null);

// Message panel state
const page = usePage();
const { messages, addSuccess, addError, removeMessage, clearAll } = useMessagePanel();

watch(
    () => page.props,
    (newProps: any) => {
        const flash = newProps?.flash || {};
        if (typeof flash.success === 'string' && flash.success) addSuccess(flash.success);
        if (typeof flash.error === 'string' && flash.error) addError(flash.error);
    },
    { immediate: true },
);

const normalizedFilters = computed<Filters>(() => ({
    no_bk: props.filters.no_bk || '',
    no_pv: props.filters.no_pv || '',
    department_id: props.filters.department_id ?? '',
    supplier_id: props.filters.supplier_id ?? '',
    start: (props.filters.start as string | null) ?? '',
    end: (props.filters.end as string | null) ?? '',
    search: props.filters.search || '',
}));

const title = computed(() => {
    return 'Bank Keluar';
});

function handleFilter(filters: Filters) {
    router.get(route('bank-keluar.index'), filters as unknown as Record<string, any>, {
        preserveState: true,
        replace: true,
    });
}

function handleSort({ sortBy, sortDirection }: { sortBy: string; sortDirection: 'asc' | 'desc' }) {
    router.get(route('bank-keluar.index'), { ...(props.filters as Record<string, any>), sortBy, sortDirection } as Record<string, any>, {
        preserveState: true,
        replace: true,
    });
}

function handlePaginate(url: string | null | undefined) {
    if (!url) return;
    router.get(url);
}

function handleEdit(bankKeluar: BankKeluarRow) {
    router.get(route('bank-keluar.edit', bankKeluar.id));
}

function handleDetail(bankKeluar: BankKeluarRow) {
    router.get(route('bank-keluar.show', bankKeluar.id));
}

function handleLog(bankKeluar: BankKeluarRow) {
    router.get(route('bank-keluar.log', bankKeluar.id));
}

function confirmDelete(bankKeluar: BankKeluarRow) {
    // Cast to any to satisfy Ref<BankKeluarRow | null> in some TS plugins
    bankKeluarToDelete.value = bankKeluar as any;
    showConfirmDelete.value = true;
}

function handleDelete() {
    if (bankKeluarToDelete.value) {
        router.delete(route('bank-keluar.destroy', bankKeluarToDelete.value.id), {
            onSuccess: () => {
                showConfirmDelete.value = false;
                bankKeluarToDelete.value = null;
                addSuccess('Bank Keluar berhasil dibatalkan.');
            },
            onError: () => {
                addError('Terjadi kesalahan saat membatalkan Bank Keluar.');
            },
        });
    }
}

function handleExportExcel() {
    router.post(route('bank-keluar.export-excel'), props.filters as unknown as Record<string, any>, { preserveState: true });
}

function handleCreate() {
    router.get(route('bank-keluar.create'));
}
</script>

<template>
    <AppLayout>
        <Head :title="title" />

        <div class="min-h-screen bg-[#DFECF2]">
            <div class="pt-6 pr-6 pb-6 pl-2">
                <Breadcrumbs :items="breadcrumbs" />

                <MessagePanel
                    :messages="messages"
                    position="top-right"
                    @close="removeMessage"
                    @clear="clearAll"
                />

                <div class="mt-4 mb-6 flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">{{ title }}</h1>
                        <div class="mt-2 flex items-center text-sm text-gray-500">
                            <component :is="getIconForPage('Bank Keluar')" class="mr-1 h-4 w-4" />
                            <span>Manage Bank Keluar data</span>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <button
                            @click="handleExportExcel"
                            class="flex items-center gap-2 rounded-md border border-green-300 bg-green-100 px-4 py-2 text-sm font-medium text-green-700 hover:bg-green-200"
                        >
                            <svg class="h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor">
                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
                                />
                            </svg>
                            <span>Export to Excel</span>
                        </button>

                        <button
                            @click="handleCreate"
                            class="flex items-center gap-2 rounded-md bg-[#101010] px-4 py-2 text-sm font-medium text-white transition-colors duration-200 hover:bg-white hover:text-[#101010] focus:ring-2 focus:ring-[#5856D6] focus:ring-offset-2 focus:outline-none"
                        >
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Add New</span>
                        </button>
                    </div>
                </div>

                <BankKeluarFilter :filters="normalizedFilters" :departments="departments" :suppliers="suppliers" @filter="handleFilter" />
                <BankKeluarTable
                    :bankKeluars="bankKeluars"
                    :sortBy="sortBy || undefined"
                    :sortDirection="sortDirection || undefined"
                    @edit="handleEdit"
                    @delete="confirmDelete"
                    @detail="handleDetail"
                    @log="handleLog"
                    @paginate="handlePaginate"
                    @sort="handleSort"
                />

                <ConfirmDialog
                    :show="showConfirmDelete"
                    title="Konfirmasi Pembatalan"
                    message="Apakah Anda yakin ingin membatalkan Bank Keluar ini?"
                    @confirm="handleDelete"
                    @cancel="showConfirmDelete = false"
                />
            </div>
        </div>
    </AppLayout>
</template>
