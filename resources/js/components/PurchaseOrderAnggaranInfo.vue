<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(defineProps<{
  poAnggaran?: any;
  showFinancial?: boolean;
}>(), {
  showFinancial: true,
});

const formatCurrency = (value: number) => {
  return new Intl.NumberFormat("id-ID", {
    style: "currency",
    currency: "IDR",
    minimumFractionDigits: 0,
  }).format(value || 0);
};

const formatDate = (dateString: string) => {
  if (!dateString) return "-";
  try {
    return new Date(dateString).toLocaleDateString("id-ID", {
      day: "numeric",
      month: "short",
      year: "numeric",
    });
  } catch {
    return dateString;
  }
};

const basicInfo = computed(
  (): Array<{ label: string; value: string }> => {
    const poa = props.poAnggaran;
    if (!poa) return [];

    return [
      { label: "No. PO Anggaran", value: poa.no_po_anggaran || poa.number || poa.id || "-" },
      { label: "Tanggal", value: formatDate(poa.tanggal || poa.created_at) },
      { label: "Department", value: poa.department?.name || poa.department_name || "-" },
      { label: "Perihal", value: poa.perihal?.nama || poa.perihal_name || "-" },
      { label: "Status", value: poa.status || "-" },
    ];
  }
);

const financialInfo = computed(() => {
  const poa = props.poAnggaran;
  if (!poa) return [];

  const items: Array<{ label: string; value: string; highlight?: boolean }> = [];

  items.push({
    label: "Nominal PO",
    value: formatCurrency(Number(poa.nominal) || 0),
  });

  if (poa.outstanding !== undefined) {
    items.push({
      label: "Outstanding",
      value: formatCurrency(Number(poa.outstanding) || 0),
    });
  }

  return items;
});

const partnerInfo = computed(
  (): Array<{ label: string; value: string }> => {
    const poa = props.poAnggaran;
    if (!poa) return [];

    const bp = poa.bisnis_partner || poa.bisnisPartner || {};
    const items: Array<{ label: string; value: string }> = [];

    items.push({ label: "Bisnis Partner", value: bp.nama_bp || bp.name || poa.bp_name || "-" });

    if (bp.no_telepon || poa.bp_phone) {
      items.push({ label: "Telepon", value: bp.no_telepon || poa.bp_phone });
    }
    if (bp.alamat || poa.bp_address) {
      items.push({ label: "Alamat", value: bp.alamat || poa.bp_address });
    }

    return items;
  }
);

const paymentInfo = computed(
  (): Array<{ label: string; value: string }> => {
    const poa = props.poAnggaran;
    if (!poa) return [];

    const metode = poa.metode_pembayaran || poa.metode_bayar || (poa.no_giro ? "Cek/Giro" : "Transfer");

    const items: Array<{ label: string; value: string }> = [
      { label: "Metode", value: metode || "Transfer" },
    ];

    if (metode === "Transfer") {
      const bank = poa.bank || {};
      const bankName = bank.nama_bank || poa.bank_name;
      const accountOwner = poa.nama_rekening;
      const accountNumber = poa.no_rekening;

      if (accountOwner) items.push({ label: "Pemilik Rekening", value: accountOwner });
      if (bankName) items.push({ label: "Bank", value: bankName });
      if (accountNumber) items.push({ label: "No. Rekening", value: accountNumber });
    } else if (metode === "Cek/Giro") {
      if (poa.no_giro) items.push({ label: "No. Giro", value: poa.no_giro });
      if (poa.tanggal_giro) items.push({ label: "Tgl. Giro", value: formatDate(poa.tanggal_giro) });
      if (poa.tanggal_cair) items.push({ label: "Tgl. Cair", value: formatDate(poa.tanggal_cair) });
    }

    return items;
  }
);

const additionalInfo = computed(
  (): Array<{ label: string; value: string }> => {
    const poa = props.poAnggaran;
    if (!poa) return [];

    const items: Array<{ label: string; value: string }> = [];

    if (poa.detail_keperluan) {
      items.push({ label: "Detail Keperluan", value: poa.detail_keperluan });
    }
    if (poa.note) {
      items.push({ label: "Catatan", value: poa.note });
    }

    return items;
  }
);
</script>

<template>
  <div class="po-info-card">
    <div class="po-info-header">
      <h3 class="po-info-title">Informasi PO Anggaran</h3>
    </div>

    <div v-if="!poAnggaran" class="po-info-empty">
      <svg
        class="po-info-empty-icon"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
        />
      </svg>
      <p class="po-info-empty-text">Pilih PO Anggaran untuk melihat informasi</p>
    </div>

    <div v-else class="po-info-content">
      <div class="po-info-section">
        <h4 class="po-info-section-title">Informasi Dasar</h4>
        <div class="po-info-grid">
          <div v-for="(item, index) in basicInfo" :key="index" class="po-info-item">
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <div v-if="props.showFinancial && financialInfo.length > 0" class="po-info-section">
        <h4 class="po-info-section-title">Keuangan</h4>
        <div class="po-info-grid">
          <div
            v-for="(item, index) in financialInfo"
            :key="index"
            class="po-info-item"
          >
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <div v-if="partnerInfo.length > 0" class="po-info-section">
        <h4 class="po-info-section-title">Bisnis Partner</h4>
        <div class="po-info-grid">
          <div v-for="(item, index) in partnerInfo" :key="index" class="po-info-item">
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <div v-if="paymentInfo.length > 1" class="po-info-section">
        <h4 class="po-info-section-title">Pembayaran</h4>
        <div class="po-info-grid">
          <div v-for="(item, index) in paymentInfo" :key="index" class="po-info-item">
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <div v-if="additionalInfo.length > 0" class="po-info-section">
        <h4 class="po-info-section-title">Catatan</h4>
        <div class="po-info-grid">
          <div
            v-for="(item, index) in additionalInfo"
            :key="index"
            class="po-info-item po-info-item-full"
          >
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

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
