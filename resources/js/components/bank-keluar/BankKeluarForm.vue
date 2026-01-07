<script setup lang="ts">
import ConfirmDialog from '@/components/ui/ConfirmDialog.vue';
import CustomSelect from '@/components/ui/CustomSelect.vue';
import MessagePanel from '@/components/ui/MessagePanel.vue';
import PaymentVoucherInfoCard from '@/components/bank-keluar/PaymentVoucherInfoCard.vue';
import { useMessagePanel } from '@/composables/useMessagePanel';
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';
import { formatCurrency, parseCurrency } from '@/lib/currencyUtils';
import { useRouter } from 'vue-router'

const router = useRouter()

interface SimpleOption {
    id: number | string;
    name?: string;
    nama?: string;
    nama_bp?: string;
    nama_supplier?: string;
    departments?: Array<{ id: number | string }>;
}

interface BankKeluarFormData {
    id?: number | string;
    no_bk?: string;
    tanggal?: string;
    payment_voucher_id?: number | string | null;
    tipe_bk?: string | null;
    department_id?: number | string | '';
    perihal_id?: number | string | null;
    nominal?: number | string;
    metode_bayar?: string;
    supplier_id?: number | string | null;
    bisnis_partner_id?: number | string | null;
    bank_id?: number | string | null;
    bank_supplier_account_id?: number | string | null;
    credit_card_id?: number | string | null;
    nama_pemilik_rekening?: string;
    no_rekening?: string;
    note?: string | null;
    // Sumber dokumen: PV / Non PV / Realisasi (frontend only for now)
    source_type?: string | null;
}

const props = defineProps<{
    departments: SimpleOption[];
    paymentVouchers?: any[];
    perihals?: Array<{ id: number | string; nama?: string }>;
    suppliers: SimpleOption[];
    bisnisPartners: SimpleOption[];
    banks: SimpleOption[];
    bankSupplierAccounts: any[];
    creditCards: any[];
    mode?: 'create' | 'edit';
    bankKeluar?: BankKeluarFormData;
}>();

const isEditMode = props.mode === 'edit';

const { messages, addSuccess, addError, removeMessage, clearAll } = useMessagePanel();

const form = useForm<any>({
    no_bk: props.bankKeluar?.no_bk ?? '',
    tanggal: props.bankKeluar?.tanggal ?? new Date().toISOString().split('T')[0],
    payment_voucher_id: (props.bankKeluar as any)?.payment_voucher_id ?? null,
    tipe_bk: (props.bankKeluar as any)?.tipe_bk ?? 'Reguler',
    department_id: props.bankKeluar?.department_id ?? '',
    perihal_id: props.bankKeluar?.perihal_id ?? null,
    nominal: props.bankKeluar?.nominal ?? '',
    metode_bayar: props.bankKeluar?.metode_bayar ?? 'Transfer',
    supplier_id: props.bankKeluar?.supplier_id ?? null,
    bisnis_partner_id: props.bankKeluar?.bisnis_partner_id ?? null,
    bank_id: props.bankKeluar?.bank_id ?? null,
    bank_supplier_account_id: props.bankKeluar?.bank_supplier_account_id ?? null,
    credit_card_id: props.bankKeluar?.credit_card_id ?? null,
    nama_pemilik_rekening: props.bankKeluar?.nama_pemilik_rekening ?? '',
    no_rekening: props.bankKeluar?.no_rekening ?? '',
    note: props.bankKeluar?.note ?? '',
    source_type: (props.bankKeluar as any)?.source_type ?? 'PV',
});
const saveAndContinue = ref(false);

// Local display value for nominal with thousand separators
const displayNominal = ref<string>(form.nominal ? formatCurrency(form.nominal) : '');

watch(
    () => form.nominal,
    (newVal) => {
        if (newVal === null || newVal === undefined || newVal === '') {
            displayNominal.value = '';
            return;
        }
        displayNominal.value = formatCurrency(newVal);
    },
);

function handleNominalInput(e: Event) {
    const target = e.target as HTMLInputElement | null;
    const raw = target?.value ?? '';

    if (!raw) {
        displayNominal.value = '';
        form.nominal = '';
        return;
    }

    const cleaned = parseCurrency(raw);
    const numeric = Number(cleaned || 0);

    const pv = selectedPaymentVoucher.value as any | null;
    const maxNominal = pv ? Number((pv as any).remaining_nominal ?? pv.nominal ?? 0) || 0 : 0;

    if (pv && maxNominal > 0 && numeric > maxNominal) {
        const clamped = maxNominal;
        form.nominal = String(clamped);
        displayNominal.value = formatCurrency(clamped);
        nominalError.value = `Nominal Bank Keluar tidak boleh melebihi nominal PV (maksimal ${formatCurrency(maxNominal)}).`;
    } else {
        form.nominal = cleaned || '';
        displayNominal.value = raw;
        nominalError.value = null;
    }
}

function handleNominalKeydown(e: KeyboardEvent) {
    const allowedControlKeys = [
        'Backspace',
        'Tab',
        'ArrowLeft',
        'ArrowRight',
        'ArrowUp',
        'ArrowDown',
        'Delete',
        'Home',
        'End',
        'Enter',
    ];

    // Izinkan tombol kontrol dasar dan kombinasi Ctrl/Cmd (copy, paste, select all, dll.)
    if (allowedControlKeys.includes(e.key) || e.ctrlKey || e.metaKey) {
        return;
    }

    const isNumber = /[0-9]/.test(e.key);
    const isDecimalSeparator = e.key === '.' || e.key === ',';

    if (!isNumber && !isDecimalSeparator) {
        e.preventDefault();
    }
}

function handleNominalBlur(e: FocusEvent) {
    const target = e.target as HTMLInputElement | null;
    const raw = target?.value ?? '';

    if (!raw) {
        displayNominal.value = '';
        return;
    }

    const cleaned = parseCurrency(raw);
    displayNominal.value = cleaned ? formatCurrency(cleaned) : raw;
}

// Filter Bisnis Partner berdasarkan department
const filteredBisnisPartners = computed(() => {
    const deptId = (form as any).department_id as string | number | '';
    if (!deptId) {
        return props.bisnisPartners;
    }

    return props.bisnisPartners.filter((bp) => {
        if (!bp || !bp.departments || bp.departments.length === 0) {
            return true;
        }
        return bp.departments.some((dept) => String(dept.id) === String(deptId));
    });
});

const isSourcePV = computed(() => (form as any).source_type === 'PV');
const isSourceNonPV = computed(() => (form as any).source_type === 'Non PV');
const isSourceRealisasi = computed(() => (form as any).source_type === 'Realisasi');

// const filteredBankAccounts = computed(() => {
//     const supplierId = (form as any).supplier_id;
//     if (!supplierId) return [];
//     return (props.bankSupplierAccounts || []).filter((a: any) => String(a.supplier_id) === String(supplierId));
// });

// const bankAccountOptions = computed(() => {
//     return filteredBankAccounts.value.map((a: any) => ({
//         label: a.nama_rekening || String(a.no_rekening || ''),
//         value: a.id,
//     }));
// });

// const creditCardOptions = computed(() => {
//     return (props.creditCards || []).map((c: any) => ({
//         label: c.nama_pemilik || c.no_kartu_kredit,
//         value: c.id,
//     }));
// });

// Filter Payment Voucher berdasarkan tipe BK, department, dan supplier/bisnis partner
const filteredPaymentVouchers = computed(() => {
    const tipeBk = (form as any).tipe_bk as string | null;
    const deptId = (form as any).department_id as string | number | '';
    const supplierId = (form as any).supplier_id as string | number | null;
    const bisnisPartnerId = (form as any).bisnis_partner_id as string | number | null;

    const vouchers = (props.paymentVouchers || []) as any[];

    return vouchers.filter((pv: any) => {
        // Filter by tipe PV jika tersedia
        if (tipeBk && pv.tipe_pv && pv.tipe_pv !== tipeBk) {
            return false;
        }

        // Filter by department jika tersedia
        if (deptId && pv.department_id && String(pv.department_id) !== String(deptId)) {
            return false;
        }

        if (tipeBk === 'Anggaran') {
            // Untuk Anggaran, match ke Bisnis Partner jika sudah dipilih
            if (!bisnisPartnerId) {
                return true;
            }

            const pvBisnisPartnerId = pv.po_anggaran?.bisnis_partner_id ?? pv.bisnis_partner_id ?? null;
            if (!pvBisnisPartnerId) {
                return false;
            }

            return String(pvBisnisPartnerId) === String(bisnisPartnerId);
        }

        // Untuk non-Anggaran, match ke Supplier jika sudah dipilih
        if (!supplierId) {
            return true;
        }

        if (!pv.supplier_id) {
            return false;
        }

        return String(pv.supplier_id) === String(supplierId);
    });
});

const selectedPaymentVoucher = computed(() => {
    const id = (form as any).payment_voucher_id;
    if (!id || !filteredPaymentVouchers.value.length) return null;
    return filteredPaymentVouchers.value.find((pv: any) => String(pv.id) === String(id)) || null;
});

const filteredSuppliers = computed(() => {
    const deptId = (form as any).department_id as string | number | '';
    const suppliers = (props.suppliers as any[]) || [];
    if (!deptId) {
        return suppliers;
    }
    const target = String(deptId);
    return suppliers.filter((s: any) => {
        if (!s) return false;
        if (s?.is_all) return true;
        if (s.department_id == null) return false;
        return String(s.department_id) === target;
    });
});

// const filteredCreditCards = computed(() => {
//     const deptId = (form as any).department_id as string | number | '';
//     const cards = (creditCardOptions.value || []) as any[];
//     if (!deptId) return cards;
//     const target = String(deptId);
//     return cards.filter((c: any) => {
//         if (c?.is_all) return true;
//         return String(c.department_id ?? '') === target;
//     });
// });

const showConfirmSubmit = ref(false);
const confirmSubmitMessage = ref('Apakah Anda yakin ingin menyimpan data Bank Keluar ini?');

const nominalError = ref<string | null>(null);

const validTanggal = computed({
    get: () => {
        const raw = form.tanggal as string | undefined;
        if (!raw) {
            return new Date();
        }
        try {
            const d = new Date(raw);
            return isNaN(d.getTime()) ? new Date() : d;
        } catch {
            return new Date();
        }
    },
    set: (value: Date | null) => {
        if (!value) {
            (form as any).tanggal = '';
            return;
        }
        const year = value.getFullYear();
        const month = String(value.getMonth() + 1).padStart(2, '0');
        const day = String(value.getDate()).padStart(2, '0');
        (form as any).tanggal = `${year}-${month}-${day}`;
    },
});

function performSubmit() {
    clearAll();

    // Frontend validation: nominal BK tidak boleh melebihi nominal PV yang dipilih
    nominalError.value = null;
    const pv = selectedPaymentVoucher.value as any | null;
    const bkNominal = Number(form.nominal || 0);
    if (pv && bkNominal > 0) {
        const maxNominal = Number((pv as any).remaining_nominal ?? pv.nominal ?? 0);
        if (maxNominal > 0 && bkNominal > maxNominal) {
            nominalError.value = `Nominal Bank Keluar tidak boleh melebihi nominal PV (maksimal ${formatCurrency(maxNominal)}).`;
            addError(nominalError.value);
            return;
        }
    }

    if (isEditMode && props.bankKeluar?.id) {
        form.put(route('bank-keluar.update', props.bankKeluar.id), {
            preserveScroll: true,
            onSuccess: () => {
                addSuccess('Data bank keluar berhasil diperbarui');
            },
            onError: (errors: Record<string, string | string[]>) => {
                const messagesArray = Object.values(errors || {})
                    .flat()
                    .filter((v) => !!v) as string[];
                const messageText = messagesArray.join(' ') || 'Gagal memperbarui data bank keluar';
                addError(messageText);
            },
        });
    } else {
        const extraParams = saveAndContinue.value ? { stay: 1 } : {};
        form.post(route('bank-keluar.store', extraParams), {
            preserveScroll: true,
            onSuccess: () => {
                if (saveAndContinue.value) {
                    const currentTanggal = form.tanggal;
                    const currentDepartment = form.department_id;
                    const currentTipeBk = form.tipe_bk;
                    const currentMetodeBayar = form.metode_bayar;

                    form.reset();

                    (form as any).tanggal = currentTanggal;
                    (form as any).department_id = currentDepartment;
                    (form as any).tipe_bk = currentTipeBk;
                    (form as any).metode_bayar = currentMetodeBayar;
                } else {
                    form.reset();
                }
                addSuccess('Data bank keluar berhasil disimpan');
            },
            onError: (errors: Record<string, string | string[]>) => {
                const messagesArray = Object.values(errors || {})
                    .flat()
                    .filter((v) => !!v) as string[];
                const messageText = messagesArray.join(' ') || 'Gagal menyimpan data bank keluar';
                addError(messageText);
            },
        });
    }
}

function handleSubmit(mode: 'index' | 'stay' = 'index') {
    if (form.processing) return;
    saveAndContinue.value = mode === 'stay';
    showConfirmSubmit.value = true;
}

function handleFormSubmit() {
    // Default behaviour when user presses Enter in form: Simpan (redirect index)
    handleSubmit('index');
}

function handleCancel() {
    router.back()
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

        if ((form as any).bisnis_partner_id) {
            const bpStillValid = filteredBisnisPartners.value.some((bp) => String(bp.id) === String((form as any).bisnis_partner_id));
            if (!bpStillValid) {
                (form as any).bisnis_partner_id = null;
            }
        }
    },
);

// Do not auto-change department based on supplier selection; department must follow explicit user input.

// function handleBisnisPartnerChange(value: string | number | null) {
//     form.bisnis_partner_id = value;
// }

// Watch for tipe_bk changes to reset dependent fields
watch(
    () => form.tipe_bk,
    (newVal, oldVal) => {
        if (newVal !== oldVal) {
            // Reset dependent fields when tipe_bk changes
            // Selalu kosongkan Payment Voucher ketika tipe BK berubah
            form.payment_voucher_id = null;

            if (newVal === 'Anggaran') {
                form.supplier_id = null;
                form.bank_supplier_account_id = null;
                form.nama_pemilik_rekening = '';
                form.no_rekening = '';
            } else {
                form.bisnis_partner_id = null;
            }
        }
    },
);

// Watch for perubahan sumber BK (PV / Non PV / Realisasi)
watch(
    () => (form as any).source_type,
    (newVal, oldVal) => {
        if (newVal === oldVal) return;

        // Selalu kosongkan Payment Voucher ketika mode berubah
        form.payment_voucher_id = null;

        if (newVal === 'PV') {
            // Kembali ke perilaku lama: reset Bisnis Partner hanya jika tipe_bk bukan Anggaran
            if (form.tipe_bk !== 'Anggaran') {
                form.bisnis_partner_id = null;
            }
        } else if (newVal === 'Non PV' || newVal === 'Realisasi') {
            // Mode Non PV / Realisasi tidak menggunakan Supplier
            form.supplier_id = null;
            // Untuk mempermudah validasi backend dan logika bisnis,
            // set tipe_bk otomatis ke 'Anggaran' ketika bukan PV
            form.tipe_bk = 'Anggaran';
        }
    },
);
</script>

<template>
    <div class="rounded-lg bg-white p-6 shadow-sm">
        <MessagePanel :messages="messages" position="top-right" @close="removeMessage" @clear="clearAll" />
        <div class="pv-form-container">
            <!-- Left Column: Form -->
            <div class="pv-form-left">
                <form @submit.prevent="handleFormSubmit" class="space-y-6">
                    <!-- Row 1: No. Bank Keluar | Tanggal -->
                    <div class="space-y-6">
                        <div class="floating-input">
                            <input id="no_bk" v-model="form.no_bk" type="text" class="floating-input-field" placeholder=" " readonly />
                            <label for="no_bk" class="floating-label"> No. Bank Keluar <span class="text-red-500">*</span> </label>
                        </div>

                        <!-- Sumber BK: PV / Non PV / Realisasi -->
                        <div>
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="radio"
                                        class="h-4 w-4 text-blue-600"
                                        value="PV"
                                        v-model="form.source_type"
                                    />
                                    <span>PV</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="radio"
                                        class="h-4 w-4 text-blue-600"
                                        value="Non PV"
                                        v-model="form.source_type"
                                    />
                                    <span>Non PV</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input
                                        type="radio"
                                        class="h-4 w-4 text-blue-600"
                                        value="Realisasi"
                                        v-model="form.source_type"
                                    />
                                    <span>Realisasi</span>
                                </label>
                            </div>
                        </div>

                        <div class="floating-input">
                            <Datepicker
                                v-model="validTanggal"
                                :enable-time-picker="false"
                                :auto-apply="true"
                                :clearable="false"
                                locale="id"
                                :format="'dd MMM yyyy'"
                                :input-class="['floating-input-field', validTanggal ? 'filled' : '']"
                                :class="{ 'border-red-500': form.errors.tanggal }"
                            />
                            <!-- <label for="tanggal" class="floating-label"> Tanggal <span class="text-red-500">*</span> </label> -->
                            <input id="tanggal" v-model="form.tanggal" type="hidden" />
                            <div v-if="form.errors.tanggal" class="mt-1 text-xs text-red-500">{{ form.errors.tanggal }}</div>
                        </div>
                    </div>

                    <!-- Row 2: Tipe Bank Keluar | Department -->
                    <div class="space-y-6">
                        <!-- Tipe BK hanya relevan untuk mode PV; untuk Non PV & Realisasi diset otomatis di backend/frontend -->
                        <div v-if="isSourcePV">
                            <div class="flex flex-wrap gap-4">
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" class="h-4 w-4 text-blue-600" value="Reguler" v-model="form.tipe_bk" />
                                    <span>Reguler</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" class="h-4 w-4 text-blue-600" value="Anggaran" v-model="form.tipe_bk" />
                                    <span>Anggaran</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" class="h-4 w-4 text-blue-600" value="Lainnya" v-model="form.tipe_bk" />
                                    <span>Lainnya</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" class="h-4 w-4 text-blue-600" value="Pajak" v-model="form.tipe_bk" />
                                    <span>Pajak</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" class="h-4 w-4 text-blue-600" value="Manual" v-model="form.tipe_bk" />
                                    <span>Manual</span>
                                </label>
                                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                    <input type="radio" class="h-4 w-4 text-blue-600" value="DP" v-model="form.tipe_bk" />
                                    <span>DP</span>
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
                            <div v-if="form.errors.department_id" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                        </div>
                    </div>

                    <!-- Row 3: Supplier / Bisnis Partner + PV / Realisasi -->
                    <div class="space-y-6">
                        <!-- Mode PV: behaviour lama (Anggaran pakai Bisnis Partner, lainnya pakai Supplier) -->
                        <div v-if="isSourcePV && form.tipe_bk === 'Anggaran'">
                            <CustomSelect
                                v-model="form.bisnis_partner_id"
                                :options="
                                    filteredBisnisPartners.map((bp) => ({
                                        label: bp.nama_bp ?? bp.name ?? bp.nama ?? String(bp.id),
                                        value: bp.id,
                                    }))
                                "
                                placeholder="-- Select Bisnis Partner --"
                                :class="{ 'border-red-500': form.errors.bisnis_partner_id }"
                            >
                                <template #label> Bisnis Partner <span class="text-red-500">*</span> </template>
                            </CustomSelect>
                            <div v-if="form.errors.bisnis_partner_id" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                        </div>

                        <!-- Mode PV + non-Anggaran: Supplier -->
                        <div v-else-if="isSourcePV">
                            <CustomSelect
                                v-model="form.supplier_id"
                                :options="
                                    filteredSuppliers.map((supplier) => ({
                                        label: supplier.nama_supplier ?? supplier.name ?? supplier.nama ?? String(supplier.id),
                                        value: supplier.id,
                                    }))
                                "
                                placeholder="-- Select Supplier --"
                                :class="{ 'border-red-500': form.errors.supplier_id }"
                            >
                                <template #label> Supplier <span class="text-red-500">*</span> </template>
                            </CustomSelect>
                            <div v-if="form.errors.supplier_id" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                        </div>

                        <!-- Mode Non PV: selalu Bisnis Partner menggantikan Supplier -->
                        <div v-else-if="isSourceNonPV">
                            <CustomSelect
                                v-model="form.bisnis_partner_id"
                                :options="
                                    filteredBisnisPartners.map((bp) => ({
                                        label: bp.nama_bp ?? bp.name ?? bp.nama ?? String(bp.id),
                                        value: bp.id,
                                    }))
                                "
                                placeholder="-- Select Bisnis Partner --"
                                :class="{ 'border-red-500': form.errors.bisnis_partner_id }"
                            >
                                <template #label> Bisnis Partner <span class="text-red-500">*</span> </template>
                            </CustomSelect>
                            <div v-if="form.errors.bisnis_partner_id" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                        </div>

                        <!-- Mode Realisasi: Bisnis Partner juga menggantikan Supplier -->
                        <div v-else-if="isSourceRealisasi">
                            <CustomSelect
                                v-model="form.bisnis_partner_id"
                                :options="
                                    filteredBisnisPartners.map((bp) => ({
                                        label: bp.nama_bp ?? bp.name ?? bp.nama ?? String(bp.id),
                                        value: bp.id,
                                    }))
                                "
                                placeholder="-- Select Bisnis Partner --"
                                :class="{ 'border-red-500': form.errors.bisnis_partner_id }"
                            >
                                <template #label> Bisnis Partner <span class="text-red-500">*</span> </template>
                            </CustomSelect>
                            <div v-if="form.errors.bisnis_partner_id" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                        </div>

                        <!-- Row 4: Payment Voucher / Realisasi -->
                        <div v-if="isSourcePV">
                            <CustomSelect
                                v-model="form.payment_voucher_id"
                                :options="
                                    filteredPaymentVouchers.map((pv: any) => ({
                                        label: pv.no_pv,
                                        value: pv.id,
                                    }))
                                "
                                placeholder="-- Select Payment Voucher --"
                                :class="{ 'border-red-500': form.errors.payment_voucher_id }"
                            >
                                <template #label> Payment Voucher </template>
                            </CustomSelect>
                            <div v-if="form.errors.payment_voucher_id" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                        </div>

                        <!-- Untuk Non PV tidak ada field Payment Voucher -->

                        <!-- Mode Realisasi: ganti label menjadi Realisasi.
                             Untuk saat ini masih menggunakan binding payment_voucher_id sebagai placeholder
                             sampai integrasi sumber data Realisasi ditambahkan di backend. -->
                        <div v-else-if="isSourceRealisasi">
                            <CustomSelect
                                v-model="form.payment_voucher_id"
                                :options="
                                    filteredPaymentVouchers.map((pv: any) => ({
                                        label: pv.no_pv,
                                        value: pv.id,
                                    }))
                                "
                                placeholder="-- Select Realisasi --"
                                :class="{ 'border-red-500': form.errors.payment_voucher_id }"
                            >
                                <template #label> Realisasi </template>
                            </CustomSelect>
                            <div v-if="form.errors.payment_voucher_id" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                        </div>

                        <!-- Row 5: Nominal -->
                        <div class="floating-input">
                            <input
                                id="nominal"
                                type="text"
                                inputmode="decimal"
                                :value="displayNominal"
                                @input="handleNominalInput"
                                @keydown="handleNominalKeydown"
                                @blur="handleNominalBlur"
                                class="floating-input-field"
                                placeholder=" "
                            />
                            <label for="nominal" class="floating-label"> Nominal <span class="text-red-500">*</span> </label>
                            <div v-if="form.errors.nominal" class="mt-1 text-xs text-red-500">{{ form.errors.nominal }}</div>
                            <div v-else-if="nominalError" class="mt-1 text-xs text-red-500">{{ nominalError }}</div>
                        </div>

                                            <!-- Row 5: Note -->
                    <div class="floating-input">
                        <textarea id="note" v-model="form.note" rows="3" class="floating-input-field resize-none" placeholder=" "></textarea>
                        <label for="note" class="floating-label">Note</label>
                        <div v-if="form.errors.note" class="mt-1 text-xs text-red-500">Form ini wajib di isi</div>
                    </div>
                    </div>
                </form>
            </div>

            <div class="pv-form-right">
                <div class="space-y-6">
                    <!-- Payment Voucher Info Card -->
                    <PaymentVoucherInfoCard :selected-payment-voucher="selectedPaymentVoucher" :departments="departments" :perihals="perihals" />
                </div>
            </div>
        </div>
    </div>
    <!-- Action Buttons -->
    <div class="flex justify-start gap-3 border-t border-gray-200 pt-6">
        <button
            type="submit"
            :disabled="form.processing"
            @click.prevent="handleSubmit('index')"
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
            v-if="!isEditMode"
            type="submit"
            :disabled="form.processing"
            @click.prevent="handleSubmit('stay')"
            class="flex items-center gap-2 rounded-md border border-gray-300 bg-white px-6 py-2 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 focus:outline-none disabled:cursor-not-allowed disabled:opacity-50"
        >
            <svg fill="#4B5563" height="24" viewBox="0 0 24 24" width="24" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5">
                <path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h7v-2H5V5h10v4h4v3h2V7l-4-4zm-1 10v3h-3v2h3v3h2v-3h3v-2h-3v-3h-2z" />
            </svg>
            <span v-if="form.processing">Menyimpan...</span>
            <span v-else>Simpan &amp; Lanjutkan</span>
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

    <ConfirmDialog
        :show="showConfirmSubmit"
        :message="confirmSubmitMessage"
        @confirm="
            showConfirmSubmit = false;
            performSubmit();
        "
        @cancel="showConfirmSubmit = false"
    />
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
