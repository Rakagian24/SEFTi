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
  // Get supplier from PO or Memo
  const po = props.paymentVoucher.purchaseOrder;
  const memo = props.paymentVoucher.memoPembayaran;
  
  const supplier = po?.supplier || memo?.supplier;
  
  return {
    name: supplier?.nama_supplier || "-",
    phone: supplier?.no_telepon || "-",
    address: supplier?.alamat || "-",
  };
});

const bankInfo = computed(() => {
  // Get bank account from PO or Memo
  const po = props.paymentVoucher.purchaseOrder;
  const memo = props.paymentVoucher.memoPembayaran;
  
  const bankAccount = po?.bankSupplierAccount || memo?.bankSupplierAccount;
  
  return {
    bankName: bankAccount?.bank?.nama_bank || "-",
    accountName: bankAccount?.nama_rekening || "-",
    accountNumber: bankAccount?.no_rekening || "-",
  };
});
</script>
