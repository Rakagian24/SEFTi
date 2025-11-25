<script setup lang="ts">
import CustomSelect from '@/components/ui/CustomSelect.vue';
import { formatCurrencyWithSymbol } from '@/lib/currencyUtils';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import PaymentVoucherInfoCard from './PaymentVoucherInfoCard.vue';
import { Eye } from 'lucide-vue-next';

interface SimpleOption {
    id: number | string;
    name?: string;
    nama?: string;
}

interface PaymentVoucher {
    id: number | string;
    no_pv: string;
    tipe_pv?: string;
    tanggal?: string;
    metode_bayar?: string;
    department_id?: number | string;
    department?: { name?: string };
    perihal_id?: number | string;
    perihal?: { nama?: string };
    nominal?: number;
    currency?: string;
    ppn_nominal?: number;
    pph_nominal?: number;
    note?: string;

    // Supplier info
    supplier_id?: number | string;
    supplier?: { nama_supplier?: string; nama?: string };
    supplier_name?: string;
    supplier_phone?: string;
    supplier_address?: string;
    bank_supplier_account?:
        | {
              bank_id?: number | string;
              nama_pemilik_rekening?: string;
              no_rekening?: string;
              nama_bank?: string;
          }
        | null
        | undefined;

    // Credit card info
    credit_card?:
        | {
              nama_pemilik?: string;
              owner_name?: string;
              no_kartu_kredit?: string;
              card_number?: string;
              bank_name?: string;
          }
        | null
        | undefined;

    // Related documents
    purchase_order?: {
        no_po?: string;
        perihal?: { nama?: string };
        grand_total?: number;
        total?: number;
        status?: string;
    };
    memo_pembayaran?: {
        no_mb?: string;
        no_memo?: string;
        perihal?: { nama?: string };
        total?: number;
        nominal?: number;
        status?: string;
    };
    bpb_allocations?: Array<{
        bpb_id: number | string;
        bpb?: { no_bpb?: string };
        amount?: number;
    }>;
    memo_allocations?: Array<{
        memo_id: number | string;
        memo?: { no_memo?: string };
        amount?: number;
    }>;
    remaining_nominal?: number;
}

interface BankKeluarFormData {
    id?: number | string;
    no_bk?: string;
    tanggal?: string;
    payment_voucher_id?: number | string | null;
    tipe_pv?: string | null;
    department_id?: number | string | '';
    perihal_id?: number | string | null;
    nominal?: number | string;
    metode_bayar?: string;
    supplier_id?: number | string | null;
    bank_id?: number | string | null;
    nama_pemilik_rekening?: string;
    no_rekening?: string;
    note?: string | null;
}

const props = defineProps<{
    departments: SimpleOption[];
    paymentVouchers: PaymentVoucher[];
    perihals: SimpleOption[];
    suppliers: SimpleOption[];
    banks: SimpleOption[];
    mode?: 'create' | 'edit';
    bankKeluar?: BankKeluarFormData;
}>();

const isEditMode = props.mode === 'edit';

const form = useForm<any>({
    no_bk: props.bankKeluar?.no_bk ?? '',
    tanggal: props.bankKeluar?.tanggal ?? new Date().toISOString().split('T')[0],
    payment_voucher_id: props.bankKeluar?.payment_voucher_id ?? null,
    tipe_pv: props.bankKeluar?.tipe_pv ?? null,
    department_id: props.bankKeluar?.department_id ?? '',
    perihal_id: props.bankKeluar?.perihal_id ?? null,
    nominal: props.bankKeluar?.nominal ?? '',
    metode_bayar: props.bankKeluar?.metode_bayar ?? 'Transfer',
    supplier_id: props.bankKeluar?.supplier_id ?? null,
    bank_id: props.bankKeluar?.bank_id ?? null,
    nama_pemilik_rekening: props.bankKeluar?.nama_pemilik_rekening ?? '',
    no_rekening: props.bankKeluar?.no_rekening ?? '',
    note: props.bankKeluar?.note ?? '',
    document: null,
});

const selectedPaymentVoucher = ref<PaymentVoucher | null>(
    props.bankKeluar?.payment_voucher_id
        ? props.paymentVouchers.find((pv) => String(pv.id) === String(props.bankKeluar?.payment_voucher_id)) || null
        : null,
);

// Watch for payment voucher selection changes
watch(
    () => form.payment_voucher_id,
    (newValue) => {
        selectedPaymentVoucher.value = newValue ? props.paymentVouchers.find((pv) => String(pv.id) === String(newValue)) || null : null;
    },
    { immediate: true },
);

const filteredPaymentVouchers = computed(() => {
    return props.paymentVouchers.filter((pv) => {
        const tipeForm = (form as any).tipe_pv as string | null;
        const deptForm = (form as any).department_id as string | number | '';
        const metodeFormRaw = (form as any).metode_bayar as string | undefined;
        const supplierForm = (form as any).supplier_id as string | number | null;
        const ownerNameForm = ((form as any).nama_pemilik_rekening as string | undefined) || '';

        if (tipeForm && pv.tipe_pv && String(pv.tipe_pv) !== String(tipeForm)) {
            return false;
        }

        if (deptForm && pv.department_id && String(pv.department_id) !== String(deptForm)) {
            return false;
        }

        if (metodeFormRaw) {
            let metodeForm = metodeFormRaw;
            if (metodeForm === 'Kredit') {
                metodeForm = 'Kartu Kredit';
            }
            const metodePv = pv.metode_bayar || '';
            if (metodePv && metodePv !== metodeForm) {
                return false;
            }
        }

        if (supplierForm && pv.supplier_id && String(pv.supplier_id) !== String(supplierForm)) {
            return false;
        }

        if (ownerNameForm) {
            const metodePvNorm = (pv.metode_bayar || '').toLowerCase();
            let sourceName: string | undefined;

            if (metodePvNorm === 'kartu kredit') {
                sourceName = (pv as any).credit_card?.nama_pemilik || (pv as any).credit_card?.owner_name || undefined;
            } else {
                sourceName = pv.bank_supplier_account?.nama_pemilik_rekening;
            }

            if (sourceName) {
                const needle = ownerNameForm.toLowerCase();
                const haystack = String(sourceName || '').toLowerCase();
                if (!haystack.includes(needle)) {
                    return false;
                }
            }
        }

        return true;
    });
});
const filteredSuppliers = computed(() => {
    const deptId = (form as any).department_id as string | number | '';
    if (!deptId) {
        return props.suppliers as any[];
    }
    return (props.suppliers as any[]).filter((s: any) => {
        if (s == null) return false;
        if (s.department_id == null) return false;
        return String(s.department_id) === String(deptId);
    });
});

const documentPreview = ref<string | null>(null);

const dragActive = ref(false);

const currentPvMaxNominal = ref<number | null>(null);

const displayTanggal = computed(() => {
    const tgl = form.tanggal as string | undefined;

    const safeFormat = (date: string | Date) => {
        try {
            return new Date(date).toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
            });
        } catch {
            return typeof date === 'string' ? date : '';
        }
    };

    return tgl ? safeFormat(tgl) : safeFormat(new Date());
});

function handleSubmit() {
    const confirmed = window.confirm(isEditMode ? 'Apakah Anda yakin ingin menyimpan perubahan Bank Keluar ini?' : 'Apakah Anda yakin ingin menyimpan Bank Keluar ini?');
    if (!confirmed) {
        return;
    }

    if (isEditMode && props.bankKeluar?.id) {
        form.put(route('bank-keluar.update', props.bankKeluar.id), {
            preserveScroll: true,
            onSuccess: () => {
                documentPreview.value = null;
            },
        });
    } else {
        form.post(route('bank-keluar.store'), {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                documentPreview.value = null;
            },
        });
    }
}

function handleCancel() {
    console.log('Cancel clicked');
    window.history.back();
}

watch(
    () => (form as any).department_id,
    (newVal: string | number | '' | undefined, oldVal: string | number | '' | undefined) => {
        if (oldVal === undefined) return;
        const currentSupplierId = (form as any).supplier_id;
        if (!currentSupplierId) return;
        const stillValid = (filteredSuppliers.value || []).some((s: any) => String(s.id) === String(currentSupplierId));
        if (!stillValid) {
            (form as any).supplier_id = null;
        }
    },
);

function handlePaymentVoucherChange(e: Event) {
    const target = e.target as HTMLSelectElement | null;
    const pvId = target?.value;
    if (pvId) {
        const pv = props.paymentVouchers.find((pv) => String(pv.id) === pvId);
        if (pv) {
            selectedPaymentVoucher.value = pv;
            (form as any).tipe_pv = pv.tipe_pv;
            (form as any).department_id = pv.department_id;
            (form as any).perihal_id = pv.perihal_id;
            const remaining = (pv as any).remaining_nominal ?? pv.nominal ?? 0;
            (form as any).nominal = remaining;
            currentPvMaxNominal.value = Number(remaining) || 0;
            (form as any).metode_bayar = pv.metode_bayar || 'Transfer';
            (form as any).supplier_id = pv.supplier_id;

            if (pv.bank_supplier_account) {
                (form as any).bank_id = pv.bank_supplier_account.bank_id;
                (form as any).nama_pemilik_rekening = pv.bank_supplier_account.nama_pemilik_rekening;
                (form as any).no_rekening = pv.bank_supplier_account.no_rekening;
            }
        }
    } else {
        selectedPaymentVoucher.value = null;
        currentPvMaxNominal.value = null;
    }
}

watch(
    () => (form as any).nominal,
    (val) => {
        if (currentPvMaxNominal.value == null) return;
        const num = Number(val) || 0;
        if (num > currentPvMaxNominal.value) {
            (form as any).nominal = currentPvMaxNominal.value;
        }
    },
);

function handleFileChange(e: Event) {
    const target = e.target as HTMLInputElement | null;
    const file = target?.files?.[0];
    if (file) {
        (form as any).document = file as unknown as File;

        const reader = new FileReader();
        reader.onload = (ev: ProgressEvent<FileReader>) => {
            documentPreview.value = (ev.target?.result as string) ?? null;
        };
        reader.readAsDataURL(file);
    }
}

function removeFile() {
    (form as any).document = null;
    documentPreview.value = null;
}

function previewUploadedDocument() {
    if (!documentPreview.value) return;
    try {
        window.open(documentPreview.value, '_blank');
    } catch {
        // ignore
    }
}

function handleDragEnterUpload(e: DragEvent) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = true;
}

function handleDragOverUpload(e: DragEvent) {
    e.preventDefault();
    e.stopPropagation();
}

function handleDragLeaveUpload(e: DragEvent) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = false;
}

function handleDropUpload(e: DragEvent) {
    e.preventDefault();
    e.stopPropagation();
    dragActive.value = false;

    const files = e.dataTransfer?.files;
    if (!files || files.length === 0) return;

    const file = files[0];
    const mockEvent = {
        target: {
            files: [file],
        },
    } as unknown as Event;

    handleFileChange(mockEvent);
}
</script>

<template>
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <div class="pv-form-container">
            <!-- Left Column: Form -->
            <div class="pv-form-left">
                <form @submit.prevent="handleSubmit" class="space-y-6">
                    <!-- Row 1: No. Bank Keluar | Tanggal -->
                    <div class="space-y-6">
                        <div class="floating-input">
                            <input id="no_bk" v-model="form.no_bk" type="text" class="floating-input-field" placeholder=" " readonly />
                            <label for="no_bk" class="floating-label"> No. Bank Keluar </label>
                        </div>

                        <div class="floating-input">
                            <div
                                class="floating-input-field filled cursor-not-allowed bg-gray-50 text-gray-600"
                                :class="{ 'border-red-500': form.errors.tanggal }"
                            >
                                {{ displayTanggal || '-' }}
                            </div>
                            <label for="tanggal" class="floating-label"> Tanggal <span class="text-red-500">*</span> </label>
                            <input id="tanggal" v-model="form.tanggal" type="hidden" />
                            <div v-if="form.errors.tanggal" class="mt-1 text-xs text-red-500">{{ form.errors.tanggal }}</div>
                        </div>
                    </div>

                    <!-- Row 2: Tipe Bank Keluar | Department -->
                    <div class="space-y-6">
                        <div>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="radio"
                                        class="h-4 w-4 text-blue-600"
                                        value="Reguler"
                                        v-model="form.tipe_pv"
                                    />
                                    <span>Reguler</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="radio"
                                        class="h-4 w-4 text-blue-600"
                                        value="Anggaran"
                                        v-model="form.tipe_pv"
                                    />
                                    <span>Anggaran</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="radio"
                                        class="h-4 w-4 text-blue-600"
                                        value="Lainnya"
                                        v-model="form.tipe_pv"
                                    />
                                    <span>Lainnya</span>
                                </label>
                            </div>
                            <div v-if="form.errors.tipe_pv" class="mt-1 text-xs text-red-500">{{ form.errors.tipe_pv }}</div>
                        </div>

                        <div>
                            <CustomSelect
                                v-model="form.department_id"
                                :options="
                                    departments.map((department) => ({
                                        label: department.name ?? department.nama ?? String(department.id),
                                        value: department.id,
                                    }))
                                "
                                placeholder="-- Select Department --"
                                :class="{ 'border-red-500': form.errors.department_id }"
                            >
                                <template #label> Department <span class="text-red-500">*</span> </template>
                            </CustomSelect>
                            <div v-if="form.errors.department_id" class="mt-1 text-xs text-red-500">{{ form.errors.department_id }}</div>
                        </div>
                    </div>

                    <!-- Row 3: Metode Bayar | Nama Pemilik Rekening/Kredit -->
                    <div class="space-y-6">
                        <div>
                            <CustomSelect
                                v-model="form.metode_bayar"
                                :options="[
                                    { label: 'Transfer', value: 'Transfer' },
                                    { label: 'Kartu Kredit', value: 'Kartu Kredit' },
                                ]"
                                placeholder="-- Select Metode Bayar --"
                                :class="{ 'border-red-500': form.errors.metode_bayar }"
                            >
                                <template #label> Metode Bayar <span class="text-red-500">*</span> </template>
                            </CustomSelect>
                            <div v-if="form.errors.metode_bayar" class="mt-1 text-xs text-red-500">{{ form.errors.metode_bayar }}</div>
                        </div>

                        <div>
                            <CustomSelect
                                v-model="form.supplier_id"
                                :options="
                                    filteredSuppliers.map((supplier) => ({
                                        label: supplier.name ?? supplier.nama ?? String(supplier.id),
                                        value: supplier.id,
                                    }))
                                "
                                placeholder="-- Select Supplier --"
                                :class="{ 'border-red-500': form.errors.supplier_id }"
                            >
                                <template #label>
                                    {{ form.metode_bayar === 'Kartu Kredit' ? 'Nama Pemilik Rekening Kredit' : 'Nama Pemilik Rekening' }}
                                </template>
                            </CustomSelect>
                            <div v-if="form.errors.supplier_id" class="mt-1 text-xs text-red-500">{{ form.errors.supplier_id }}</div>
                        </div>
                    </div>

                    <!-- Row 4: Payment Voucher | Upload Bukti Pembayaran -->
                    <div class="space-y-6">
                        <div>
                            <CustomSelect
                                v-model="form.payment_voucher_id"
                                :options="[
                                    { label: '-- Select Payment Voucher --', value: '' },
                                    ...filteredPaymentVouchers.map((pv) => ({
                                        label: `${pv.no_pv} - ${pv.supplier?.nama ?? 'No Supplier'} - ${formatCurrencyWithSymbol(pv.nominal ?? 0, 'IDR')}`,
                                        value: pv.id,
                                    })),
                                ]"
                                placeholder="-- Select Payment Voucher --"
                                :class="{ 'border-red-500': form.errors.payment_voucher_id }"
                                @update:modelValue="
                                    (value) => {
                                        form.payment_voucher_id = value;
                                        const fakeEvent = { target: { value: String(value ?? '') } } as unknown as Event;
                                        handlePaymentVoucherChange(fakeEvent);
                                    }
                                "
                            >
                                <template #label> Payment Voucher </template>
                            </CustomSelect>
                            <div v-if="form.errors.payment_voucher_id" class="mt-1 text-xs text-red-500">
                                {{ form.errors.payment_voucher_id }}
                            </div>
                        </div>

                        <div class="floating-input">
                            <input
                                id="nominal"
                                v-model.number="form.nominal"
                                type="number"
                                min="0"
                                step="0.01"
                                class="floating-input-field"
                                placeholder=" "
                                :class="{ 'border-red-500': form.errors.nominal }"
                            />
                            <label for="nominal" class="floating-label"> Nominal </label>
                            <div v-if="form.errors.nominal" class="mt-1 text-xs text-red-500">{{ form.errors.nominal }}</div>
                        </div>

                        <div
                            class="bg-white rounded-lg border border-gray-200 p-4 relative"
                        >
                            <h3 class="font-medium text-gray-900 mb-1">
                                Upload Bukti Pembayaran
                            </h3>
                            <p class="text-xs text-gray-500 mb-4">Wajib</p>

                            <div class="space-y-4">
                                <div
                                    class="border-2 border-dotted rounded-lg p-4 text-center transition-colors duration-200"
                                    :class="{
                                        'border-blue-400 bg-blue-50': dragActive,
                                        'border-gray-300': !dragActive,
                                    }"
                                    @dragenter="handleDragEnterUpload"
                                    @dragover="handleDragOverUpload"
                                    @dragleave="handleDragLeaveUpload"
                                    @drop="handleDropUpload"
                                >
                                    <input
                                        id="document"
                                        type="file"
                                        class="hidden"
                                        :class="{ 'border-red-500': form.errors.document }"
                                        @change="handleFileChange"
                                        accept="image/*,.pdf"
                                    />
                                    <label for="document" class="cursor-pointer">
                                        <div class="text-gray-500 mb-2">📄</div>
                                        <div class="text-blue-600 text-sm">
                                            {{
                                                dragActive
                                                    ? 'Lepaskan file di sini'
                                                    : 'Pilih Berkas atau Drag & Drop'
                                            }}
                                        </div>
                                    </label>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <div class="flex items-center gap-1">
                                        <span class="text-red-500">⚠</span>
                                        <span>Bawa berkas ke area ini (maks. 10 MB)</span>
                                    </div>

                                    <!-- Document Preview -->
                                    <div v-if="documentPreview" class="mt-3">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                class="text-gray-600 hover:text-blue-600"
                                                @click="previewUploadedDocument"
                                                title="Lihat"
                                            >
                                                <Eye class="h-4 w-4" />
                                            </button>
                                            <span class="text-blue-600 text-sm">
                                                {{ (form as any).document?.name }}
                                            </span>
                                            <button
                                                type="button"
                                                @click="removeFile"
                                                class="ml-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-xs text-white hover:bg-red-600"
                                            >
                                                ×
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <div v-if="form.errors.document" class="mt-1 text-xs text-red-500">
                                    {{ form.errors.document }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 5: Note -->
                    <div class="floating-input">
                        <textarea id="note" v-model="form.note" rows="3" class="floating-input-field resize-none" placeholder=" "></textarea>
                        <label for="note" class="floating-label">Note</label>
                        <div v-if="form.errors.note" class="mt-1 text-xs text-red-500">{{ form.errors.note }}</div>
                    </div>
                </form>
            </div>

            <!-- Right Column: Payment Voucher Info Card -->
            <div class="pv-form-right">
                <PaymentVoucherInfoCard :selected-payment-voucher="selectedPaymentVoucher" :departments="departments" :perihals="perihals" />
            </div>
        </div>
    </div>
    <!-- Action Buttons -->
    <div class="flex justify-start gap-3 border-t border-gray-200 pt-6">
        <button
            type="submit"
            :disabled="form.processing"
            @click="handleSubmit"
            class="flex items-center gap-2 rounded-md border border-transparent bg-[#7F9BE6] px-6 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-700 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        >
            <svg fill="#E6E6E6" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5">
                <path
                    d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"
                />
            </svg>
            <span v-if="form.processing">Menyimpan...</span>
            <span v-else>Simpan</span>
        </button>

        <button
            type="button"
            @click="handleCancel"
            class="flex items-center gap-2 rounded-md border border-gray-300 bg-white px-6 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none"
        >
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-5 w-5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
            Batal
        </button>
    </div>
</template>

<style scoped>
.pv-form-container {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2rem;
}

.pv-form-left {
    width: 100%;
}

.pv-form-right {
    width: 100%;
}

@media (max-width: 1024px) {
    .pv-form-container {
        grid-template-columns: 1fr;
    }
}

.floating-input {
    position: relative;
}

.floating-input-field {
    width: 100%;
    padding: 1rem 0.75rem;
    border: 1px solid #d1d5db;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    background-color: white;
    transition: all 0.3s ease-in-out;
}

.floating-input-field:focus {
    outline: none;
    border-color: #1f9254;
    box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.floating-label {
    position: absolute;
    left: 0.75rem;
    top: 1rem;
    font-size: 0.875rem;
    line-height: 1.25rem;
    color: #9ca3af;
    transition: all 0.3s ease-in-out;
    pointer-events: none;
    transform-origin: left top;
    background-color: white;
    padding: 0 0.25rem;
    z-index: 1;
}

.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
    top: -0.5rem;
    left: 0.75rem;
    font-size: 0.75rem;
    line-height: 1rem;
    color: #333333;
    transform: translateY(0) scale(1);
}

.floating-input-field[readonly].filled ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
    top: -0.5rem;
    left: 0.75rem;
    font-size: 0.75rem;
    line-height: 1rem;
    color: #333333;
    transform: translateY(0) scale(1);
}

.floating-input-field[type='date'] ~ .floating-label {
    top: -0.5rem;
    left: 0.75rem;
    font-size: 0.75rem;
    line-height: 1rem;
    color: #333333;
    transform: translateY(0) scale(1);
}

.floating-input-field:is(select) ~ .floating-label {
    top: -0.5rem;
    left: 0.75rem;
    font-size: 0.75rem;
    line-height: 1rem;
    color: #333333;
    transform: translateY(0) scale(1);
}

.floating-input-field:is(textarea) {
    resize: vertical;
    padding-top: 1rem;
    padding-bottom: 1rem;
}

.floating-input-field:is(textarea):focus ~ .floating-label,
.floating-input-field:is(textarea):not(:placeholder-shown) ~ .floating-label {
    top: -0.5rem;
}

.floating-input:hover .floating-input-field {
    border-color: #9ca3af;
}

.floating-input:hover .floating-input-field:focus {
    border-color: #1f9254;
}

.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label,
.floating-input-field[readonly].filled ~ .floating-label,
.floating-input-field[type='date'] ~ .floating-label,
.floating-input-field:is(select) ~ .floating-label,
.floating-input-field.filled ~ .floating-label {
    background-color: white;
    padding: 0 0.25rem;
}
</style>
