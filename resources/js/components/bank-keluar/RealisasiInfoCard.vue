<template>
  <div class="po-info-card">
    <div class="po-info-header">
      <h3 class="po-info-title">Informasi Realisasi</h3>
    </div>

    <div v-if="!selectedRealisasi" class="po-info-empty">
      <svg class="po-info-empty-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
      </svg>
      <p class="po-info-empty-text">Pilih Realisasi untuk melihat informasi</p>
    </div>

    <div v-else class="po-info-content">
      <!-- Basic Information -->
      <div class="po-info-section">
        <h4 class="po-info-section-title">Informasi Dasar</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">No. Realisasi</span>
            <span class="po-info-value">{{ selectedRealisasi.no_realisasi || '-' }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Tanggal</span>
            <span class="po-info-value">{{ formatDate(selectedRealisasi.tanggal) }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Departemen</span>
            <span class="po-info-value">{{ departmentName }}</span>
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
            <span class="po-info-label">Nominal PO</span>
            <span class="po-info-value">{{ formatCurrency(selectedRealisasi.po_anggaran?.grand_total || 0) }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Nominal Realisasi</span>
            <span class="po-info-value">{{ formatCurrency(selectedRealisasi.nominal || 0) }}</span>
          </div>
          <div v-if="hasRemaining" class="po-info-item po-info-item-highlight">
            <span class="po-info-label">Sisa</span>
            <span class="po-info-value">{{ formatCurrency(remainingAmount) }}</span>
          </div>
        </div>
      </div>

      <!-- PO Anggaran Information -->
      <div v-if="selectedRealisasi.po_anggaran" class="po-info-section">
        <h4 class="po-info-section-title">Informasi PO Anggaran</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">No. PO Anggaran</span>
            <span class="po-info-value">{{ selectedRealisasi.po_anggaran.no_po_anggaran || '-' }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Status</span>
            <span :class="['inline-flex items-center px-2 py-0.5 rounded text-xs font-medium', getStatusClass(selectedRealisasi.po_anggaran.status)]">
              {{ selectedRealisasi.po_anggaran.status || '-' }}
            </span>
          </div>
        </div>
      </div>

      <!-- Bisnis Partner Information -->
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

      <!-- Notes -->
      <div v-if="selectedRealisasi.note" class="po-info-section">
        <h4 class="po-info-section-title">Catatan</h4>
        <div class="po-info-grid">
          <div class="po-info-item po-info-item-full">
            <span class="po-info-value">{{ selectedRealisasi.note }}</span>
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

interface Realisasi {
  id: number | string;
  no_realisasi: string;
  tanggal?: string;
  nominal?: number;
  note?: string;
  department_id?: number | string;
  department?: { name?: string };
  perihal_id?: number | string;
  perihal?: { nama?: string };
  po_anggaran?: {
    id?: number | string;
    no_po_anggaran?: string;
    grand_total?: number;
    status?: string;
    bisnis_partner_id?: number | string;
    perihal?: { nama?: string };
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
}

const props = defineProps<{
  selectedRealisasi: Realisasi | null;
  departments?: Array<{ id: number | string; name?: string }>;
  perihals?: Array<{ id: number | string; nama?: string }>;
}>();

// Computed properties
const departmentName = computed(() => {
  if (!props.selectedRealisasi) return '-';

  // Try direct department name first
  if (props.selectedRealisasi.department?.name) {
    return props.selectedRealisasi.department.name;
  }

  // Try to find in departments array
  if (props.departments && props.selectedRealisasi.department_id) {
    const dept = props.departments.find(d => String(d.id) === String(props.selectedRealisasi?.department_id));
    return dept?.name || '-';
  }

  return '-';
});

const perihalName = computed(() => {
  if (!props.selectedRealisasi) return '-';

  // Try direct perihal name first
  if (props.selectedRealisasi.perihal?.nama) {
    return props.selectedRealisasi.perihal.nama;
  }

  // Try to find in perihals array
  if (props.perihals && props.selectedRealisasi.perihal_id) {
    const perihal = props.perihals.find(p => String(p.id) === String(props.selectedRealisasi?.perihal_id));
    return perihal?.nama || '-';
  }

  // Try from PO Anggaran
  if (props.selectedRealisasi.po_anggaran?.perihal?.nama) {
    return props.selectedRealisasi.po_anggaran.perihal.nama;
  }

  return '-';
});

const bisnisPartner = computed(() => {
  return props.selectedRealisasi?.po_anggaran?.bisnis_partner ?? null;
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
    props.selectedRealisasi?.po_anggaran?.bank?.nama_bank ||
    '-'
  );
});

const bisnisPartnerAccountName = computed(() => {
  return bisnisPartner.value?.nama_rekening || '-';
});

const bisnisPartnerAccountNumber = computed(() => {
  return bisnisPartner.value?.no_rekening_va || '-';
});

const remainingAmount = computed(() => {
  if (!props.selectedRealisasi?.po_anggaran) return 0;
  
  const poTotal = Number(props.selectedRealisasi.po_anggaran.grand_total || 0);
  const realisasiNominal = Number(props.selectedRealisasi.nominal || 0);
  
  return Math.max(0, poTotal - realisasiNominal);
});

const hasRemaining = computed(() => {
  return remainingAmount.value > 0;
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
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
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
  background-color: #f0fdf4;
  padding: 0.5rem;
  border-radius: 0.25rem;
  border: 1px solid #86efac;
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
  color: #166534;
  font-weight: 600;
}
.po-info-item-highlight .po-info-value {
  font-size: 0.875rem;
  font-weight: 700;
  color: #166534;
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
</style>
