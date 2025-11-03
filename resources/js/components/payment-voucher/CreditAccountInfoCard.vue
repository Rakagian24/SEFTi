<template>
  <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
    <div class="flex items-center gap-2 mb-4">
      <CreditCardIcon class="w-5 h-5 text-gray-600" />
      <h3 class="text-lg font-semibold text-gray-900">Informasi Rekening Kredit</h3>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Credit Card Info -->
      <div class="space-y-4">
        <div class="pb-2 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Kartu Kredit</p>
        </div>
        <InfoItem icon="user" label="Nama Kartu" :value="creditInfo.cardName" />
        <InfoItem icon="credit-card" label="No. Kartu" :value="creditInfo.cardNumber" mono />
        <!-- <InfoItem v-if="creditInfo.holder" icon="user" label="Pemegang Kartu" :value="creditInfo.holder" /> -->
      </div>

      <!-- Issuer / Bank Info -->
      <div class="space-y-4">
        <div class="pb-2 border-b border-gray-200">
          <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Penerbit</p>
        </div>
        <InfoItem icon="building" label="Bank Penerbit" :value="creditInfo.bankName" />
        <InfoItem v-if="creditInfo.brand" icon="badge" label="Brand" :value="creditInfo.brand" />
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted } from "vue";
import { CreditCard as CreditCardIcon } from "lucide-vue-next";
import InfoItem from "@/components/ui/InfoItem.vue";

const props = defineProps<{
  paymentVoucher: any;
}>();

function getCreditCard(pv: any) {
  const po = pv?.purchaseOrder || pv?.purchase_order || pv?.purchaseorder;
  const memo = pv?.memoPembayaran || pv?.memo_pembayaran || pv?.memopembayaran;
  return (
    pv?.creditCard ||
    pv?.credit_card ||
    po?.creditCard ||
    po?.credit_card ||
    memo?.creditCard ||
    memo?.credit_card ||
    null
  );
}

const creditInfo = computed(() => {
  const pv: any = props.paymentVoucher || {};
  const cc: any = getCreditCard(pv);

  // Common field fallbacks to be defensive against different API shapes
  const cardNumber =
    pv?.no_kartu_kredit ||
    cc?.no_kartu_kredit ||
    cc?.card_number ||
    cc?.nomor_kartu ||
    "-";

  const cardName =
    cc?.nama_kartu ||
    cc?.card_name ||
    cc?.name ||
    pv?.nama_kartu ||
    pv?.card_name ||
    cc?.nama_pemilik ||
    "-";

  const holder = cc?.pemegang_kartu || cc?.holder || cc?.holder_name || cc?.nama_pemilik || null;

  const bankName =
    cc?.bank?.nama_bank ||
    cc?.bank?.name ||
    cc?.issuer_bank ||
    "-";

  const brand = cc?.brand || cc?.tipe || null;

  return {
    cardNumber,
    cardName,
    holder,
    bankName,
    brand,
  };
});

onMounted(() => {
  try {
    const pv: any = props.paymentVoucher || {};
    const cc: any = getCreditCard(pv);
    // Debug snapshot to help verify backend payload shape
    console.group("[CreditAccountInfoCard] Snapshot");
    console.log({ pv, creditCard: cc });
    console.groupEnd();
  } catch {}
});
</script>
