/**
 * Shared action description utilities for log activities
 */

export function getActionDescription(action: string, entityType: string = ''): string {
  const actionLower = action?.toLowerCase() || '';

  switch (actionLower) {
    // CRUD Operations
    case 'created':
    case 'create':
      return `Membuat data ${entityType}`;
    case 'updated':
    case 'update':
      return `Mengubah data ${entityType}`;
    case 'deleted':
    case 'delete':
      return `Menghapus data ${entityType}`;

    // Workflow Status Changes
    case 'draft':
      return `Menyimpan ${entityType} sebagai Draft`;
    case 'in progress':
    case 'submitted':
    case 'submit':
      return `Memproses ${entityType}`;
    case 'verified':
    case 'verify':
      return `Memverifikasi ${entityType}`;
    case 'validated':
    case 'validate':
      return `Memvalidasi ${entityType}`;
    case 'approved':
    case 'approve':
      return `Menyetujui ${entityType}`;
    case 'canceled':
    case 'cancel':
      return `Membatalkan ${entityType}`;
    case 'rejected':
    case 'reject':
      return `Menolak ${entityType}`;

    // Bank specific actions
    case 'out':
      return `Mengeluarkan ${entityType}`;
    case 'received':
      return `Menerima ${entityType}`;
    case 'returned':
      return `Mengembalikan ${entityType}`;

    // Default fallback
    default:
      return action || '';
  }
}

// Specific entity type helpers
export function getMemoActionDescription(action: string): string {
  return getActionDescription(action, 'Memo Pembayaran');
}

export function getPurchaseOrderActionDescription(action: string): string {
  return getActionDescription(action, 'Purchase Order');
}

export function getBankActionDescription(action: string): string {
  return getActionDescription(action, 'Bank Masuk');
}

export function getPaymentVoucherActionDescription(action: string): string {
  return getActionDescription(action, 'Payment Voucher');
}

export function getSupplierActionDescription(action: string): string {
  return getActionDescription(action, 'Supplier');
}

export function getUserActionDescription(action: string): string {
  return getActionDescription(action, 'User');
}

export function getBankAccountActionDescription(action: string): string {
  return getActionDescription(action, 'Bank Account');
}

export function getCreditCardActionDescription(action: string): string {
  return getActionDescription(action, 'Credit Card');
}

export function getPengeluaranActionDescription(action: string): string {
  return getActionDescription(action, 'Pengeluaran');
}

export function getArPartnerActionDescription(action: string): string {
  return getActionDescription(action, 'AR Partner');
}

export function getPphActionDescription(action: string): string {
  return getActionDescription(action, 'PPh');
}
