<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  purchaseOrder?: any;
}>();

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
      month: "long",
      year: "numeric",
    });
  } catch {
    return dateString;
  }
};

const infoItems = computed(() => {
  const po = props.purchaseOrder;
  if (!po) return [];

  return [
    { label: "No. PO", value: po.no_po || po.po_number || "-" },
    { label: "Tanggal", value: formatDate(po.tanggal || po.date) },
    { label: "Departemen", value: po.department?.name || po.department_name || "-" },
    { label: "Perihal", value: po.perihal?.nama || po.perihal_name || "-" },
    {
      label: "Nominal",
      value: formatCurrency(po.total || po.nominal || 0),
      highlight: true,
    },
    { label: "Nama Supplier", value: po.supplier?.name || po.supplier_name || "-" },
    {
      label: "Nama Pemilik Rekening",
      value: po.supplier?.account_owner_name || po.account_owner_name || "-",
    },
    { label: "Nama Bank", value: po.supplier?.bank_name || po.bank_name || "-" },
    {
      label: "No. Rekening",
      value: po.supplier?.account_number || po.account_number || "-",
    },
    { label: "No. Telepon", value: po.supplier?.phone || po.phone || "-" },
    { label: "Alamat", value: po.supplier?.address || po.address || "-" },
  ].filter(
    (item) =>
      item.value !== "-" || ["No. PO", "Nominal", "Nama Supplier"].includes(item.label)
  );
});
</script>

<template>
  <div class="po-info-card">
    <div class="po-info-header">
      <h3 class="po-info-title">Informasi Purchase Order</h3>
    </div>

    <div v-if="!purchaseOrder" class="po-info-empty">
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
      <p class="po-info-empty-text">Pilih Purchase Order untuk melihat informasi</p>
    </div>

    <div v-else class="po-info-content">
      <div
        v-for="(item, index) in infoItems"
        :key="index"
        class="po-info-item"
        :class="{ 'po-info-item-highlight': item.highlight }"
      >
        <dt class="po-info-label">{{ item.label }}</dt>
        <dd class="po-info-value">{{ item.value }}</dd>
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
}

.po-info-header {
  background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
  padding: 1rem 1.25rem;
}

.po-info-title {
  font-size: 1.125rem;
  font-weight: 600;
  color: white;
  margin: 0;
}

.po-info-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 3rem 1.5rem;
  text-align: center;
}

.po-info-empty-icon {
  width: 4rem;
  height: 4rem;
  color: #d1d5db;
  margin-bottom: 1rem;
}

.po-info-empty-text {
  color: #6b7280;
  font-size: 0.875rem;
  margin: 0;
}

.po-info-content {
  padding: 1.25rem;
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.po-info-item {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.25rem;
  padding-bottom: 1rem;
  border-bottom: 1px solid #e5e7eb;
}

.po-info-item:last-child {
  border-bottom: none;
  padding-bottom: 0;
}

.po-info-item-highlight {
  background-color: #f0f9ff;
  padding: 0.75rem;
  border-radius: 0.375rem;
  border: 1px solid #bfdbfe;
  margin: 0.5rem 0;
}

.po-info-label {
  font-size: 0.75rem;
  font-weight: 500;
  color: #6b7280;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  margin: 0;
}

.po-info-value {
  font-size: 0.875rem;
  color: #111827;
  margin: 0;
  word-break: break-word;
}

.po-info-item-highlight .po-info-label {
  color: #1e40af;
}

.po-info-item-highlight .po-info-value {
  font-size: 1.125rem;
  font-weight: 600;
  color: #1e40af;
}

/* Responsive adjustments */
@media (max-width: 768px) {
  .po-info-card {
    position: static;
  }
}
</style>
