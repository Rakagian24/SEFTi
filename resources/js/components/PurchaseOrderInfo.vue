<script setup lang="ts">
import { computed } from "vue";

const props = withDefaults(defineProps<{
  purchaseOrder?: any;
  showFinancial?: boolean;
  bpbs?: any[];
  memos?: any[];
  bpbAllocations?: Array<{ bpb_id: number; amount: number }>;
  memoAllocations?: Array<{ memo_id: number; amount: number }>;
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
    const po = props.purchaseOrder;
    if (!po) return [];

    return [
      { label: "No. PO", value: po.no_po || po.po_number || po.number || "-" },
      { label: "No. Invoice", value: po.no_invoice || po.invoice_number || "-" },
      { label: "Tanggal", value: formatDate(po.tanggal || po.date || po.created_at) },
      { label: "Perihal", value: po.perihal?.nama || po.perihal_name || "-" },
      { label: "Status", value: po.status || "-" },
    ];
  }
);

const financialInfo = computed(() => {
  const po = props.purchaseOrder;
  if (!po) return [];

  const items: Array<{ label: string; value: string; highlight?: boolean }> = [];

  items.push({
    label: "Total",
    value: formatCurrency(po.total ?? po.nominal ?? 0),
  });

  // Add additional financial info based on PO type
  if (po.tipe_po === "Lainnya") {
    if (po.cicilan && po.cicilan > 0) {
      items.push({
        label: "Cicilan",
        value: formatCurrency(po.cicilan),
      });
    }
    if (po.termin) {
      const t = po.termin || {};
      if (typeof t.jumlah_termin_dibuat !== "undefined" && typeof t.jumlah_termin !== "undefined") {
        items.push({
          label: "Termin Dibuat",
          value: `${t.jumlah_termin_dibuat} / ${t.jumlah_termin}`,
        });
      }
      if (typeof t.total_cicilan !== "undefined") {
        items.push({
          label: "Total Cicilan",
          value: formatCurrency(Number(t.total_cicilan) || 0),
        });
      }
      if (typeof t.sisa_pembayaran !== "undefined") {
        items.push({
          label: "Sisa Pembayaran",
          value: formatCurrency(Number(t.sisa_pembayaran) || 0),
        });
      }
    }
  }

  if (po.diskon && po.diskon > 0) {
    items.push({
      label: "Diskon",
      value: formatCurrency(po.diskon),
    });
  }

  if (po.pph_nominal && Number(po.pph_nominal) > 0) {
    items.push({
      label: "PPh",
      value: formatCurrency(Number(po.pph_nominal)),
    });
  }

  if (po.ppn_nominal && po.ppn_nominal > 0) {
    items.push({
      label: "PPN",
      value: formatCurrency(po.ppn_nominal),
    });
  }

  items.push({
    label: "Grand Total",
    value: formatCurrency(po.grand_total ?? po.total ?? po.nominal ?? 0),
    highlight: true,
  });

  // Finance-friendly memo/outstanding section (if data available)
  const grand = Number(po.grand_total ?? po.total ?? po.nominal ?? 0) || 0;
  const hasOutstanding = typeof po.outstanding !== "undefined" && po.outstanding !== null;
  if (hasOutstanding) {
    const out = Math.max(0, Number(po.outstanding) || 0);
    const paid = Math.max(0, grand - out);
    items.push({
      label: "Total Dibayar (Memo)",
      value: formatCurrency(paid),
    });
    items.push({
      label: "Outstanding",
      value: formatCurrency(out),
      highlight: true,
    });
  }

  // Down Payment (DP) summary if available from backend
  const dp = po.dp_info || po.dpInfo || null;
  if (dp && dp.dp_active) {
    // Configured DP on PO
    const dpNominal = Number(dp.dp_nominal ?? 0) || 0;
    const dpPercent = Number(dp.dp_percent ?? 0) || 0;
    if (dpNominal > 0 || dpPercent > 0) {
      items.push({
        label: "DP",
        value:
          (dpPercent ? `${dpPercent}%` : "") +
          (dpNominal ? (dpPercent ? " | " : "") + formatCurrency(dpNominal) : ""),
        highlight: true,
      });
    }

    // Total PV DP yang sudah dibuat
    // const pvDpTotal = Number(dp.pv_dp_total ?? 0) || 0;
    // if (pvDpTotal > 0) {
    //   items.push({
    //     label: "Total PV DP",
    //     value: formatCurrency(pvDpTotal),
    //   });
    // }

    // DP yang sudah dipakai sebagai alokasi ke PV Reguler
    // const dpAllocated = Number(dp.dp_allocated_total ?? 0) || 0;
    // if (dpAllocated > 0) {
    //   items.push({
    //     label: "DP Terpakai",
    //     value: formatCurrency(dpAllocated),
    //   });
    // }

    // Hanya tampilkan informasi "Sisa DP" kalau DP sudah pernah dipakai
    // const hasDpActivity = pvDpTotal > 0 || dpAllocated > 0;

    // Sisa DP yang belum dibuat PV DP dibanding konfigurasi
    // const dpVsConfig = Number(dp.pv_dp_outstanding_vs_config ?? 0) || 0;
    // if (dpVsConfig > 0 && hasDpActivity) {
    //   items.push({
    //     label: "Sisa DP",
    //     value: formatCurrency(dpVsConfig),
    //   });
    // }

    // Sisa DP yang masih bisa dialokasikan
    // const dpAllocOutstanding = Number(dp.dp_allocated_outstanding ?? 0) || 0;
    // if (dpAllocOutstanding > 0 && hasDpActivity) {
    //   items.push({
    //     label: "Sisa DP",
    //     value: formatCurrency(dpAllocOutstanding),
    //   });
    // }
  }

  // Current PV allocations summary (from selections on the form)
  const allocBpb = (props.bpbAllocations || []).reduce((s, a: any) => s + (Number(a?.amount) || 0), 0);
  const allocMemo = (props.memoAllocations || []).reduce((s, a: any) => s + (Number(a?.amount) || 0), 0);
  const allocTotal = allocBpb + allocMemo;
  if (allocTotal > 0) {
    items.push({
      label: "Dialokasikan (PV ini)",
      value: formatCurrency(allocTotal),
      highlight: true,
    });
  }

  return items;
});

const bpbTotal = computed(() => {
  const list = Array.isArray(props.bpbs) ? props.bpbs : [];
  return list.reduce((sum: number, b: any) => sum + (Number(b?.grand_total) || 0), 0);
});

const bpbOutstandingTotal = computed(() => {
  const list = Array.isArray(props.bpbs) ? props.bpbs : [];
  return list.reduce((sum: number, b: any) => sum + (Number(b?.outstanding) || 0), 0);
});

const memoTotal = computed(() => {
  const list = Array.isArray(props.memos) ? props.memos : [];
  return list.reduce((sum: number, m: any) => sum + (Number(m?.grand_total || m?.nominal || 0) || 0), 0);
});

const memoOutstandingTotal = computed(() => {
  const list = Array.isArray(props.memos) ? props.memos : [];
  return list.reduce((sum: number, m: any) => sum + (Number(m?.outstanding) || 0), 0);
});

const supplierInfo = computed(
  (): Array<{ label: string; value: string }> => {
    const po = props.purchaseOrder;
    if (!po) return [];

    // Hide supplier section when payment method is credit
    if (po.metode_pembayaran === "Kartu Kredit" || po.metode_pembayaran === "Kredit") {
      return [];
    }

    const supplier = po.supplier || {};
    const items: Array<{ label: string; value: string }> = [
      {
        label: "Supplier",
        value: supplier.name || supplier.nama_supplier || po.supplier_name || "-",
      },
    ];

    // Add contact info if available
    if (supplier.phone || supplier.no_telepon || po.supplier_phone || po.phone) {
      items.push({
        label: "Telepon",
        value: supplier.phone || supplier.no_telepon || po.supplier_phone || po.phone,
      });
    }

    // Add address if available
    if (supplier.address || supplier.alamat || po.supplier_address || po.alamat) {
      items.push({
        label: "Alamat",
        value: supplier.address || supplier.alamat || po.supplier_address || po.alamat,
      });
    }

    return items;
  }
);

const paymentInfo = computed(
  (): Array<{ label: string; value: string }> => {
    const po = props.purchaseOrder;
    if (!po) return [];

    // Derive payment method with sensible fallbacks
    const metode =
      po.metode_pembayaran ||
      po.metode_bayar ||
      (po.credit_card_id || po.credit_card ? "Kartu Kredit" : "Transfer");

    const items: Array<{ label: string; value: string }> = [
      { label: "Metode", value: metode || "Transfer" },
    ];

    // Dynamic payment info based on payment method
    if (metode === "Transfer") {
      // Bank account info from supplier or PO
      const bankAccount = po.bankSupplierAccount || {};
      const bank = po.bank || {};
      const supplier = po.supplier || {};

      // Try multiple sources for bank account info
      const accountOwner =
        bankAccount.account_owner_name ||
        bankAccount.nama_rekening ||
        po.nama_rekening ||
        supplier.nama_rekening ||
        "-";

      const bankName =
        bankAccount.bank_name ||
        bankAccount.bank?.nama_bank ||
        bank.nama_bank ||
        supplier.bank_name ||
        "-";

      const accountNumber =
        bankAccount.account_number ||
        bankAccount.no_rekening ||
        po.no_rekening ||
        supplier.no_rekening ||
        "-";

      if (accountOwner && accountOwner !== "-") {
        items.push({
          label: "Pemilik Rekening",
          value: accountOwner,
        });
      }

      if (bankName && bankName !== "-") {
        items.push({
          label: "Bank",
          value: bankName,
        });
      }

      if (accountNumber && accountNumber !== "-") {
        items.push({
          label: "No. Rekening",
          value: accountNumber,
        });
      }
    } else if (metode === "Cek/Giro") {
      if (po.no_giro) {
        items.push({
          label: "No. Giro",
          value: po.no_giro,
        });
      }
      if (po.tanggal_giro) {
        items.push({
          label: "Tgl. Giro",
          value: formatDate(po.tanggal_giro),
        });
      }
      if (po.tanggal_cair) {
        items.push({
          label: "Tgl. Cair",
          value: formatDate(po.tanggal_cair),
        });
      }
    } else if (metode === "Kartu Kredit" || metode === "Kredit") {
      const cc = po.credit_card || {};
      const cardNumber = cc.no_kartu_kredit || cc.card_number || po.no_kartu_kredit;
      const ownerName = cc.nama_pemilik || cc.owner_name;
      const bankName = cc.bank_name;

      if (cardNumber) {
        items.push({ label: "No. Kartu", value: cardNumber });
      }
      if (ownerName) {
        items.push({ label: "Nama Pemilik", value: ownerName });
      }
      if (bankName) {
        items.push({ label: "Bank", value: bankName });
      }
    }

    // Additional info for "Lainnya" type
    if (po.tipe_po === "Lainnya") {
      if (po.termin_id && po.termin) {
        items.push({
          label: "Ref. Termin",
          value: po.termin.no_referensi || po.termin_id,
        });
      }
    }

    return items;
  }
);

const additionalInfo = computed(
  (): Array<{ label: string; value: string }> => {
    const po = props.purchaseOrder;
    if (!po) return [];

    const items: Array<{ label: string; value: string }> = [];

    // if (po.keterangan || po.note) {
    //   items.push({
    //     label: "Catatan",
    //     value: po.keterangan || po.note,
    //   });
    // }

    if (po.detail_keperluan) {
      items.push({
        label: "Detail Keperluan",
        value: po.detail_keperluan,
      });
    }

    return items;
  }
);
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
      <p class="po-info-empty-text">Pilih PO untuk melihat informasi</p>
    </div>

    <div v-else class="po-info-content">
      <!-- Basic Information -->
      <div class="po-info-section">
        <h4 class="po-info-section-title">Informasi Dasar</h4>
        <div class="po-info-grid">
          <div v-for="(item, index) in basicInfo" :key="index" class="po-info-item">
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <!-- Financial Information -->
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

      <!-- BPB Information (optional) -->
      <div v-if="Array.isArray(bpbs) && bpbs.length > 0" class="po-info-section">
        <h4 class="po-info-section-title">BPB Terpilih</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">Jumlah BPB</span>
            <span class="po-info-value">{{ bpbs.length }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Total BPB</span>
            <span class="po-info-value">{{ formatCurrency(bpbTotal) }}</span>
          </div>
          <div class="po-info-item" v-if="bpbOutstandingTotal > 0">
            <span class="po-info-label">Outstanding BPB</span>
            <span class="po-info-value">{{ formatCurrency(bpbOutstandingTotal) }}</span>
          </div>
        </div>
        <div class="mt-2">
          <table class="w-full text-xs">
            <thead>
              <tr class="text-left text-gray-600">
                <th class="py-1 pr-2">No. BPB</th>
                <th class="py-1 pr-2">Tanggal</th>
                <th class="py-1 pr-2">Nominal</th>
                <th class="py-1 pr-2">Outstanding</th>
                <th class="py-1 pr-2">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="b in bpbs" :key="b.id" class="border-t border-gray-100">
                <td class="py-1 pr-2 font-medium">{{ b.no_bpb }}</td>
                <td class="py-1 pr-2">{{ formatDate(b.tanggal) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(b.grand_total) || 0) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(b.outstanding) || 0) }}</td>
                <td class="py-1 pr-2">{{ b.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Memo Information (optional) -->
      <div v-if="Array.isArray(memos) && memos.length > 0" class="po-info-section">
        <h4 class="po-info-section-title">Memo Pembayaran Terpilih</h4>
        <div class="po-info-grid">
          <div class="po-info-item">
            <span class="po-info-label">Jumlah Memo</span>
            <span class="po-info-value">{{ memos.length }}</span>
          </div>
          <div class="po-info-item">
            <span class="po-info-label">Total Memo</span>
            <span class="po-info-value">{{ formatCurrency(memoTotal) }}</span>
          </div>
          <div class="po-info-item" v-if="memoOutstandingTotal > 0">
            <span class="po-info-label">Outstanding Memo</span>
            <span class="po-info-value">{{ formatCurrency(memoOutstandingTotal) }}</span>
          </div>
        </div>
        <div class="mt-2">
          <table class="w-full text-xs">
            <thead>
              <tr class="text-left text-gray-600">
                <th class="py-1 pr-2">No. Memo</th>
                <th class="py-1 pr-2">Tanggal</th>
                <th class="py-1 pr-2">Nominal</th>
                <th class="py-1 pr-2">Outstanding</th>
                <th class="py-1 pr-2">Keterangan</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="m in memos" :key="m.id" class="border-t border-gray-100">
                <td class="py-1 pr-2 font-medium">{{ m.no_memo || m.no_memo_pembayaran || m.nomor || m.number }}</td>
                <td class="py-1 pr-2">{{ formatDate(m.tanggal) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(m.grand_total || m.nominal || 0) || 0) }}</td>
                <td class="py-1 pr-2">{{ formatCurrency(Number(m.outstanding) || 0) }}</td>
                <td class="py-1 pr-2">{{ m.keterangan || '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Supplier Information -->
      <div v-if="supplierInfo.length > 0" class="po-info-section">
        <h4 class="po-info-section-title">Supplier</h4>
        <div class="po-info-grid">
          <div v-for="(item, index) in supplierInfo" :key="index" class="po-info-item">
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <!-- Payment Information -->
      <div v-if="paymentInfo.length > 1" class="po-info-section">
        <h4 class="po-info-section-title">Pembayaran</h4>
        <div class="po-info-grid">
          <div v-for="(item, index) in paymentInfo" :key="index" class="po-info-item">
            <span class="po-info-label">{{ item.label }}</span>
            <span class="po-info-value">{{ item.value }}</span>
          </div>
        </div>
      </div>

      <!-- Additional Information -->
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

/* Responsive adjustments */
@media (max-width: 768px) {
  .po-info-card {
    position: static;
    max-height: none;
  }

  .po-info-grid {
    grid-template-columns: 1fr;
  }
}

/* Custom scrollbar for webkit browsers */
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
