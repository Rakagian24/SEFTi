<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <Building2 class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Informasi Supplier & Rekening</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Supplier Info -->
      <div class="space-y-4">
        <div class="pb-2 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Supplier</p>
        </div>
        <InfoItem icon="user" label="Nama Supplier" :value="supplierInfo.name" />
        <InfoItem v-if="supplierInfo.phone" icon="phone" label="No. Telepon" :value="supplierInfo.phone" />
        <InfoItem v-if="supplierInfo.address" icon="map" label="Alamat" :value="supplierInfo.address" />
      </div>

      <!-- Bank Account Info -->
      <div class="space-y-4">
        <div class="pb-2 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Rekening Bank</p>
        </div>
        <InfoItem icon="building" label="Bank" :value="bankInfo.bankName" />
        <InfoItem icon="user" label="Nama Rekening" :value="bankInfo.accountName" />
        <InfoItem icon="credit-card" label="No. Rekening" :value="bankInfo.accountNumber" mono />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed } from "vue";
import { Building2 } from "lucide-vue-next";
import InfoItem from "@/components/ui/InfoItem.vue";

const props = defineProps<{
  paymentVoucher: any;
}>();

const supplierInfo = computed(() => {
  // Get supplier from PO or Memo - try all possible field names
  const pv = props.paymentVoucher;
  const po = pv.purchaseOrder || pv.purchase_order || pv.purchaseorder;
  const memo = pv.memoPembayaran || pv.memo_pembayaran || pv.memopembayaran;
  
  console.log('=== SupplierBankInfoCard Debug ===');
  console.log('Full PV Data:', pv);
  console.log('PO Data:', po);
  console.log('Memo Data:', memo);
  console.log('PV Keys:', Object.keys(pv));
  
  // Try to get supplier from various sources
  let supplier = null;
  if (po) {
    supplier = po.supplier;
    console.log('Supplier from PO:', supplier);
  } else if (memo) {
    supplier = memo.supplier;
    console.log('Supplier from Memo:', supplier);
  } else if (pv.supplier) {
    supplier = pv.supplier;
    console.log('Supplier from PV directly:', supplier);
  }
  
  console.log('Final Supplier:', supplier);
  
  return {
    name: supplier?.nama_supplier || supplier?.name || "-",
    phone: supplier?.no_telepon || supplier?.phone || "-",
    address: supplier?.alamat || supplier?.address || "-",
  };
});

const bankInfo = computed(() => {
  // Get bank account from PO or Memo - try all possible field names
  const pv = props.paymentVoucher;
  const po = pv.purchaseOrder || pv.purchase_order || pv.purchaseorder;
  const memo = pv.memoPembayaran || pv.memo_pembayaran || pv.memopembayaran;
  
  let bankAccount = null;
  if (po) {
    bankAccount = po.bankSupplierAccount || po.bank_supplier_account || po.banksupplieraccount;
    console.log('Bank Account from PO:', bankAccount);
  } else if (memo) {
    bankAccount = memo.bankSupplierAccount || memo.bank_supplier_account || memo.banksupplieraccount;
    console.log('Bank Account from Memo:', bankAccount);
  }
  
  console.log('Final Bank Account:', bankAccount);
  console.log('Bank:', bankAccount?.bank);
  
  return {
    bankName: bankAccount?.bank?.nama_bank || bankAccount?.bank?.name || "-",
    accountName: bankAccount?.nama_rekening || bankAccount?.account_name || "-",
    accountNumber: bankAccount?.no_rekening || bankAccount?.account_number || "-",
  };
});
</script>
