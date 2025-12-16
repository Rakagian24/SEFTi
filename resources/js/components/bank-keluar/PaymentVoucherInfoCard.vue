<template>
  <div class="po-info-card">
    <div class="po-info-header">
      <h3 class="po-info-title">Informasi Payment Voucher</h3>
    </div>

    <div v-if="!selectedPaymentVoucher" class="po-info-empty">
      <svg class="po-info-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="po-info-empty-text">Pilih Payment Voucher untuk melihat informasi</p>
    </div>

    <div v-else class="po-info-content">
      <!-- Basic Information -->
      <div class="po-info-section">
        <h4 class="po-info-section-title">Informasi Dasar</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">No. PV</span>
            <span class="po-info-value">{{ selectedPaymentVoucher.no_pv || '-' }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Tanggal</span>
            <span class="po-info-value">{{ formatDate(selectedPaymentVoucher.tanggal) }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Tipe PV</span>
            <span class="po-info-value">{{ selectedPaymentVoucher.tipe_pv || '-' }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Departemen</span>
            <span class="po-info-value">{{ departmentName }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Metode Bayar</span>
            <span class="po-info-value">{{ metodeBayar }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Perihal</span>
            <span class="po-info-value">{{ perihalName }}</span>
          </div>
        </div>
      </div>

      <!-- Financial Information -->
      <div class="po-info-section">
        <h4 class="po-info-section-title">Keuangan</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">Nominal</span>
            <span class="po-info-value">{{ formatCurrency(selectedPaymentVoucher.nominal || 0) }}</span>
          </div>
          <div
            v-if="
              (selectedPaymentVoucher as any).remaining_nominal != null &&
              Number((selectedPaymentVoucher as any).remaining_nominal) < Number(selectedPaymentVoucher.nominal || 0)
            "
            class="po-info-item"
          >
            <div v-if="hasRemainingNominal" class="po-info-item">
              <span class="po-info-label">Sisa Nominal</span>
              <span class="po-info-value">{{ formatCurrency((selectedPaymentVoucher as any).remaining_nominal || 0) }}</span>
            </div>
          </div>
          <div v-if="selectedPaymentVoucher.currency" class="po-info-item">
            <span class="po-info-label">Mata Uang</span>
            <span class="po-info-value">{{ selectedPaymentVoucher.currency }}</span>
          </div>
          <div v-if="selectedPaymentVoucher.ppn_nominal" class="po-info-item">
            <span class="po-info-label">PPN</span>
            <span class="po-info-value">{{ formatCurrency(selectedPaymentVoucher.ppn_nominal) }}</span>
          </div>
          <div v-if="selectedPaymentVoucher.pph_nominal" class="po-info-item">
            <span class="po-info-label">PPh</span>
            <span class="po-info-value">{{ formatCurrency(selectedPaymentVoucher.pph_nominal) }}</span>
          </div>
        </div>
      </div>

      <!-- Supplier Information -->
      <div v-if="hasBisnisPartnerInfo" class="po-info-section">
        <h4 class="po-info-section-title">Informasi Bisnis Partner</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">Nama</span>
            <span class="po-info-value">{{ bisnisPartnerName }}</span>
          </div>
          <div v-if="bisnisPartnerType" class="po-info-item">
            <span class="po-info-label">Jenis</span>
            <span class="po-info-value">{{ bisnisPartnerType }}</span>
          </div>
          <div v-if="bisnisPartnerContact" class="po-info-item">
            <span class="po-info-label">Kontak</span>
            <span class="po-info-value">{{ bisnisPartnerContact }}</span>
          </div>
          <div v-if="bisnisPartnerEmail" class="po-info-item">
            <span class="po-info-label">Email</span>
            <span class="po-info-value">{{ bisnisPartnerEmail }}</span>
          </div>
          <div v-if="bisnisPartnerAddress" class="po-info-item po-info-item-full">
            <span class="po-info-label">Alamat</span>
            <span class="po-info-value">{{ bisnisPartnerAddress }}</span>
          </div>
          <div v-if="bisnisPartnerBankName !== '-'" class="po-info-item">
            <span class="po-info-label">Nama Bank</span>
            <span class="po-info-value">{{ bisnisPartnerBankName }}</span>
          </div>
          <div v-if="bisnisPartnerAccountName && bisnisPartnerAccountName !== '-'" class="po-info-item">
            <span class="po-info-label">Nama Rekening</span>
            <span class="po-info-value">{{ bisnisPartnerAccountName }}</span>
          </div>
          <div v-if="bisnisPartnerAccountNumber && bisnisPartnerAccountNumber !== '-'" class="po-info-item">
            <span class="po-info-label">No. Rekening / VA</span>
            <span class="po-info-value">{{ bisnisPartnerAccountNumber }}</span>
          </div>
        </div>
      </div>

      <div v-if="hasSupplierInfo" class="po-info-section">
        <h4 class="po-info-section-title">Informasi Supplier</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">Nama Supplier</span>
            <span class="po-info-value">{{ supplierDisplayName }}</span>
          </div>
          <div v-if="supplierPhone" class="po-info-item">
            <span class="po-info-label">Telepon</span>
            <span class="po-info-value">{{ supplierPhone }}</span>
          </div>
          <div v-if="supplierAddress" class="po-info-item po-info-item-full">
            <span class="po-info-label">Alamat</span>
            <span class="po-info-value">{{ supplierAddress }}</span>
          </div>
        </div>
      </div>

      <!-- Payment Information -->
      <div v-if="hasPaymentRecipient" class="po-info-section">
        <h4 class="po-info-section-title">Informasi Pembayaran</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">{{ isCreditCard ? 'Nama Pemilik Kartu' : 'Nama Pemilik Rekening' }}</span>
            <span class="po-info-value">{{ paymentOwnerName }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Bank</span>
            <span class="po-info-value">{{ bankName }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">{{ isCreditCard ? 'No. Kartu' : 'No. Rekening' }}</span>
            <span class="po-info-value">{{ accountNumber }}</span>
          </div>
        </div>
      </div>

      <!-- Related Documents -->
      <div v-if="hasRelatedDocuments" class="po-info-section">
        <h4 class="po-info-section-title">Dokumen Terkait</h4>

        <!-- Purchase Order -->
        <div v-if="selectedPaymentVoucher.purchase_order" class="po-info-subcard">
          <div class="po-info-subcard-header">
            <div>
              <p class="po-info-subcard-title">
                {{ selectedPaymentVoucher.purchase_order.no_po || '-' }}
              </p>
              <p class="po-info-subcard-subtitle">
                {{ selectedPaymentVoucher.purchase_order.perihal?.nama || '-' }}
              </p>
            </div>
            <div class="po-info-subcard-meta">
              <p class="po-info-subcard-amount">
                {{ formatCurrency(selectedPaymentVoucher.purchase_order.grand_total || selectedPaymentVoucher.purchase_order.total || 0) }}
              </p>
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                  getStatusClass(selectedPaymentVoucher.purchase_order.status)
                ]"
              >
                {{ selectedPaymentVoucher.purchase_order.status || '-' }}
              </span>
            </div>
          </div>
        </div>

        <!-- Memo Pembayaran -->
        <div v-if="selectedPaymentVoucher.memo_pembayaran" class="po-info-subcard">
          <div class="po-info-subcard-header">
            <div>
              <p class="po-info-subcard-title">
                {{ selectedPaymentVoucher.memo_pembayaran.no_mb || selectedPaymentVoucher.memo_pembayaran.no_memo || '-' }}
              </p>
              <p class="po-info-subcard-subtitle">
                {{ selectedPaymentVoucher.memo_pembayaran.perihal?.nama || '-' }}
              </p>
            </div>
            <div class="po-info-subcard-meta">
              <p class="po-info-subcard-amount">
                {{ formatCurrency(selectedPaymentVoucher.memo_pembayaran.total || selectedPaymentVoucher.memo_pembayaran.nominal || 0) }}
              </p>
              <span
                :class="[
                  'inline-flex items-center px-2 py-0.5 rounded text-xs font-medium',
                  getStatusClass(selectedPaymentVoucher.memo_pembayaran.status)
                ]"
              >
                {{ selectedPaymentVoucher.memo_pembayaran.status || '-' }}
              </span>
            </div>
          </div>
        </div>

        <!-- BPB Allocations -->
        <div v-if="selectedPaymentVoucher.bpb_allocations && selectedPaymentVoucher.bpb_allocations.length > 0" class="po-info-sublist">
          <p class="po-info-sublist-title">BPB Terkait ({{ selectedPaymentVoucher.bpb_allocations.length }})</p>
          <div class="po-info-sublist-body">
            <div
              v-for="allocation in selectedPaymentVoucher.bpb_allocations"
              :key="`bpb-${allocation.bpb_id}`"
              class="po-info-sublist-item"
            >
              <span class="po-info-sublist-label">{{ allocation.bpb?.no_bpb || `#${allocation.bpb_id}` }}</span>
              <span class="po-info-sublist-value">{{ formatCurrency(allocation.amount || 0) }}</span>
            </div>
          </div>
        </div>

        <!-- Memo Allocations -->
        <div v-if="selectedPaymentVoucher.memo_allocations && selectedPaymentVoucher.memo_allocations.length > 0" class="po-info-sublist">
          <p class="po-info-sublist-title">Memo Terkait ({{ selectedPaymentVoucher.memo_allocations.length }})</p>
          <div class="po-info-sublist-body">
            <div
              v-for="allocation in selectedPaymentVoucher.memo_allocations"
              :key="`memo-${allocation.memo_id}`"
              class="po-info-sublist-item"
            >
              <span class="po-info-sublist-label">{{ allocation.memo?.no_memo || `#${allocation.memo_id}` }}</span>
              <span class="po-info-sublist-value">{{ formatCurrency(allocation.amount || 0) }}</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes -->
      <div v-if="selectedPaymentVoucher.note" class="po-info-section">
        <h4 class="po-info-section-title">Catatan</h4>
        <div class="po-info-grid">
          <div class="po-info-item po-info-item-full">
            <!-- <span class="po-info-label">Catatan</span> -->
            <span class="po-info-value">{{ selectedPaymentVoucher.note }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import { formatCurrency } from '@/lib/currencyUtils';
import { getStatusBadgeClass } from '@/lib/status';

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
  supplier?: { nama_supplier?: string };
  supplier_name?: string;
  supplier_phone?: string;
  supplier_address?: string;
  bank_supplier_account?:
    | {
        bank_id?: number | string;
        nama_pemilik_rekening?: string;
        no_rekening?: string;
        nama_bank?: string;
        bank?: { nama_bank?: string };
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
        bank?: { nama_bank?: string };
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
  po_anggaran?: {
    id?: number | string;
    no_po_anggaran?: string;
    bisnis_partner_id?: number | string;
    perihal?: { nama?: string };
    grand_total?: number;
    status?: string;
    bisnis_partner?: {
      id?: number | string;
      nama_bp?: string;
      jenis_bp?: string;
      alamat?: string;
      email?: string;
      no_telepon?: string;
      nama_rekening?: string;
      no_rekening_va?: string;
      bank?: {
        nama_bank?: string;
        singkatan?: string;
      };
    };
    bank?: {
      nama_bank?: string;
      singkatan?: string;
    };
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

const props = defineProps<{
  selectedPaymentVoucher: PaymentVoucher | null;
  departments?: Array<{ id: number | string; name?: string }>;
  perihals?: Array<{ id: number | string; nama?: string }>;
}>();

// Computed properties
const departmentName = computed(() => {
  if (!props.selectedPaymentVoucher) return '-';

  // Try direct department name first
  if (props.selectedPaymentVoucher.department?.name) {
    return props.selectedPaymentVoucher.department.name;
  }

  // Try to find in departments array
  if (props.departments && props.selectedPaymentVoucher.department_id) {
    const dept = props.departments.find(d => String(d.id) === String(props.selectedPaymentVoucher?.department_id));
    return dept?.name || '-';
  }

  return '-';
});

const metodeBayar = computed(() => {
  if (!props.selectedPaymentVoucher) return '-';
  const pv: any = props.selectedPaymentVoucher as any;

  return (
    // Urutan prioritas:
    // 1. Field langsung di PV
    // 2. Field ter-normalisasi (metode_pembayaran) bila ada
    // 3. Dari Purchase Order / Memo Pembayaran
    // 4. Dari PO Anggaran (untuk tipe Anggaran)
    // 5. Field manual
    pv.metode_bayar ||
    pv.metode_pembayaran ||
    pv.purchase_order?.metode_pembayaran ||
    pv.memo_pembayaran?.metode_pembayaran ||
    pv.po_anggaran?.metode_pembayaran ||
    pv.manual_metode_bayar ||
    '-'
  );
});

const perihalName = computed(() => {
  if (!props.selectedPaymentVoucher) return '-';

  // Try direct perihal name first
  if (props.selectedPaymentVoucher.perihal?.nama) {
    return props.selectedPaymentVoucher.perihal.nama;
  }

  // Try to find in perihals array
  if (props.perihals && props.selectedPaymentVoucher.perihal_id) {
    const perihal = props.perihals.find(p => String(p.id) === String(props.selectedPaymentVoucher?.perihal_id));
    return perihal?.nama || '-';
  }

  return '-';
});

const isCreditCard = computed(() => {
  return props.selectedPaymentVoucher?.metode_bayar === 'Kartu Kredit';
});

const hasRemainingNominal = computed(() => {
  const pv: any = props.selectedPaymentVoucher;
  if (!pv) return false;

  const nominal = Number(pv.nominal || 0);
  const remaining = Number(pv.remaining_nominal ?? null);

  // Muncul hanya jika:
  // 1. remaining_nominal ada
  // 2. nominal > 0
  // 3. remaining < nominal
  return remaining !== null && nominal > 0 && remaining < nominal;
});


const hasSupplierInfo = computed(() => {
  if (!props.selectedPaymentVoucher) return false;
  const pv: any = props.selectedPaymentVoucher as any;
  return !!(
    pv.supplier_name ||
    pv.supplier?.nama_supplier ||
    pv.supplier_phone ||
    pv.supplier_address
  );
});

const hasPaymentRecipient = computed(() => {
  if (!props.selectedPaymentVoucher) return false;
  const pv: any = props.selectedPaymentVoucher as any;

  if (isCreditCard.value) {
    // Tampilkan untuk Kartu Kredit kalau ada minimal nama pemilik / no kartu / nama bank
    return !!(
      pv.credit_card?.nama_pemilik ||
      pv.credit_card?.owner_name ||
      pv.credit_card?.no_kartu_kredit ||
      pv.credit_card?.card_number ||
      pv.credit_card?.bank_name ||
      pv.credit_card?.bank?.nama_bank
    );
  }

  // Untuk Transfer / selain Kartu Kredit
  const acc: any =
    pv.bank_supplier_account ||
    pv.purchase_order?.bank_supplier_account ||
    pv.memo_pembayaran?.bank_supplier_account ||
    null;

  return !!(
    pv.supplier_name ||
    pv.supplier?.nama_supplier ||
    acc?.nama_pemilik_rekening ||
    acc?.nama_rekening ||
    acc?.nama_bank ||
    acc?.bank?.nama_bank ||
    pv.manual_nama_pemilik_rekening ||
    pv.manual_nama_bank ||
    pv.manual_no_rekening
  );
});

const bisnisPartner = computed(() => {
  return props.selectedPaymentVoucher?.po_anggaran?.bisnis_partner ?? null;
});

const hasBisnisPartnerInfo = computed(() => {
  return !!bisnisPartner.value;
});

const bisnisPartnerName = computed(() => {
  return bisnisPartner.value?.nama_bp || '-';
});

const bisnisPartnerType = computed(() => {
  return bisnisPartner.value?.jenis_bp || '';
});

const bisnisPartnerContact = computed(() => {
  if (!bisnisPartner.value?.no_telepon) return '';
  return bisnisPartner.value.no_telepon;
});

const bisnisPartnerEmail = computed(() => {
  if (!bisnisPartner.value?.email) return '';
  return bisnisPartner.value.email;
});

const bisnisPartnerAddress = computed(() => {
  if (!bisnisPartner.value?.alamat) return '';
  return bisnisPartner.value.alamat;
});

const bisnisPartnerBankName = computed(() => {
  return (
    bisnisPartner.value?.bank?.nama_bank ||
    props.selectedPaymentVoucher?.po_anggaran?.bank?.nama_bank ||
    '-'
  );
});

const bisnisPartnerAccountName = computed(() => {
  return bisnisPartner.value?.nama_rekening || '-';
});

const bisnisPartnerAccountNumber = computed(() => {
  return bisnisPartner.value?.no_rekening_va || '-';
});

const supplierDisplayName = computed(() => {
  if (!props.selectedPaymentVoucher) return '-';
  const pv: any = props.selectedPaymentVoucher as any;
  return (
    pv.supplier_name ||
    pv.supplier?.nama_supplier ||
    '-'
  );
});

const supplierPhone = computed(() => {
  if (!props.selectedPaymentVoucher) return '';
  const pv: any = props.selectedPaymentVoucher as any;
  return (
    pv.supplier_phone ||
    pv.supplier?.no_telepon ||
    ''
  );
});

const supplierAddress = computed(() => {
  if (!props.selectedPaymentVoucher) return '';
  const pv: any = props.selectedPaymentVoucher as any;
  return (
    pv.supplier_address ||
    pv.supplier?.alamat ||
    ''
  );
});

const bankName = computed(() => {
  if (!props.selectedPaymentVoucher) return '-';

  if (isCreditCard.value) {
    const cc: any = (props.selectedPaymentVoucher as any).credit_card || {};
    return (
      cc.bank_name ||
      cc.bank?.nama_bank ||
      '-'
    );
  }

  const pv: any = props.selectedPaymentVoucher as any;
  const acc: any =
    pv.bank_supplier_account ||
    pv.purchase_order?.bank_supplier_account ||
    pv.memo_pembayaran?.bank_supplier_account ||
    null;

  return (
    acc?.nama_bank ||
    acc?.bank?.nama_bank ||
    pv.manual_nama_bank ||
    '-'
  );
});

const accountNumber = computed(() => {
  if (!props.selectedPaymentVoucher) return '-';

  if (isCreditCard.value) {
    const cc: any = (props.selectedPaymentVoucher as any).credit_card || {};
    return (
      cc.no_kartu_kredit ||
      cc.card_number ||
      '-'
    );
  }

  const pv: any = props.selectedPaymentVoucher as any;
  const acc: any =
    pv.bank_supplier_account ||
    pv.purchase_order?.bank_supplier_account ||
    pv.memo_pembayaran?.bank_supplier_account ||
    null;

  return (
    acc?.no_rekening ||
    pv.manual_no_rekening ||
    '-'
  );
});

const paymentOwnerName = computed(() => {
  if (!props.selectedPaymentVoucher) return '-';

  if (isCreditCard.value) {
    const cc: any = (props.selectedPaymentVoucher as any).credit_card || {};
    return (
      cc.nama_pemilik ||
      cc.owner_name ||
      cc.nama_kartu ||
      cc.card_name ||
      '-'
    );
  }

  const pv: any = props.selectedPaymentVoucher as any;
  const acc: any =
    pv.bank_supplier_account ||
    pv.purchase_order?.bank_supplier_account ||
    pv.memo_pembayaran?.bank_supplier_account ||
    null;

  return (
    acc?.nama_pemilik_rekening ||
    acc?.nama_rekening ||
    pv.manual_nama_pemilik_rekening ||
    '-'
  );
});

const hasRelatedDocuments = computed(() => {
  if (!props.selectedPaymentVoucher) return false;
  return !!(props.selectedPaymentVoucher.purchase_order ||
           props.selectedPaymentVoucher.memo_pembayaran ||
           (props.selectedPaymentVoucher.bpb_allocations && props.selectedPaymentVoucher.bpb_allocations.length > 0) ||
           (props.selectedPaymentVoucher.memo_allocations && props.selectedPaymentVoucher.memo_allocations.length > 0));
});

// Helper functions
function formatDate(date: string | undefined) {
  if (!date) return '-';
  try {
    return new Date(date).toLocaleDateString('id-ID', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
  } catch {
    return date;
  }
}

function getStatusClass(status: string | undefined) {
  return getStatusBadgeClass(status || '');
}
</script>

<style scoped>
.po-info-card {
  background-color: white;
  border-radius: 0.5rem;
  box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
  overflow: hidden;
  height: fit-content;
  position: sticky;
  top: 1rem;
  max-height: calc(100vh - 2rem);
  overflow-y: auto;
}
.po-info-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 0.75rem 1rem;
}
.po-info-title {
  font-size: 1rem;
  font-weight: 600;
  color: white;
  margin: 0;
}
.po-info-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 2rem 1rem;
  text-align: center;
}
.po-info-empty-icon {
  width: 3rem;
  height: 3rem;
  color: #d1d5db;
  margin-bottom: 0.75rem;
}
.po-info-empty-text {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}
.po-info-content {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.po-info-section {
  border-bottom: 1px solid #e5e7eb;
  padding-bottom: 0.75rem;
}
.po-info-section:last-child {
  border-bottom: none;
  padding-bottom: 0;
}
.po-info-section-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #374151;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.5rem 0;
}
.po-info-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.5rem;
}
.po-info-item {
  display: flex;
  flex-direction: column;
  gap: 0.125rem;
}
.po-info-item-full {
  grid-column: 1 / -1;
}
.po-info-item-highlight {
  background-color: #f0f9ff;
  padding: 0.5rem;
  border-radius: 0.25rem;
  border: 1px solid #bfdbfe;
  margin: 0.25rem 0;
}
.po-info-label {
  font-size: 0.625rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  line-height: 1.2;
}
.po-info-value {
  font-size: 0.75rem;
  color: #111827;
  font-weight: 500;
  line-height: 1.2;
  word-break: break-word;
}
.po-info-item-highlight .po-info-label {
  color: #1e40af;
  font-weight: 600;
}
.po-info-item-highlight .po-info-value {
  font-size: 0.875rem;
  font-weight: 700;
  color: #1e40af;
}
@media (max-width: 768px) {
  .po-info-card {
    position: static;
    max-height: none;
  }
  .po-info-grid {
    grid-template-columns: 1fr;
  }
}
.po-info-card::-webkit-scrollbar {
  width: 4px;
}
.po-info-card::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 2px;
}
.po-info-card::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 2px;
}
.po-info-card::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Sub-card styles for related documents */
.po-info-subcard {
  border-radius: 0.375rem;
  border: 1px solid #e5e7eb;
  padding: 0.75rem;
  background-color: #f9fafb;
  margin-bottom: 0.5rem;
}
.po-info-subcard-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}
.po-info-subcard-title {
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
  margin: 0;
}
.po-info-subcard-subtitle {
  font-size: 0.75rem;
  color: #6b7280;
  margin: 0.15rem 0 0 0;
}
.po-info-subcard-meta {
  text-align: right;
}
.po-info-subcard-amount {
  font-size: 0.875rem;
  font-weight: 600;
  color: #111827;
  margin: 0 0 0.25rem 0;
}

.po-info-sublist {
  margin-top: 0.5rem;
}
.po-info-sublist-title {
  font-size: 0.75rem;
  font-weight: 600;
  color: #374151;
  margin-bottom: 0.25rem;
}
.po-info-sublist-body {
  border-radius: 0.375rem;
  border: 1px solid #e5e7eb;
  background-color: #f9fafb;
  padding: 0.5rem;
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}
.po-info-sublist-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  font-size: 0.75rem;
}
.po-info-sublist-label {
  font-weight: 500;
  color: #111827;
}
.po-info-sublist-value {
  color: #111827;
}
</style>
