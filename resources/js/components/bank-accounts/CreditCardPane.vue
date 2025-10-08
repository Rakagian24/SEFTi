<script setup lang="ts">
import { ref, watch } from "vue";
import { useMessagePanel } from "@/composables/useMessagePanel";
//
import CreditCardForm from "./CreditCardForm.vue";
import CreditCardFilter from "./CreditCardFilter.vue";
import CreditCardTable from "./CreditCardTable.vue";
import axios from "axios";
import { router } from "@inertiajs/vue3";

const { addSuccess, addError, clearAll } = useMessagePanel();

const showForm = ref(false);
const editData = ref<Record<string, any> | null>(null);

const searchQuery = ref("");
const status = ref("");
const departmentId = ref<string>("");
const bankId = ref<string>("");

const departmentsState = ref<any[]>([]);
const banksState = ref<any[]>([]);

const creditCards = ref<any>({ data: [], links: [], from: 0, to: 0, total: 0 });
const entriesPerPage = ref(10);

const props = defineProps<{ departments?: any[]; banks?: any[] }>();

watch(
  () => props.departments,
  (val) => {
    if (val) departmentsState.value = val as any[];
  },
  { immediate: true }
);
watch(
  () => props.banks,
  (val) => {
    if (val) banksState.value = val as any[];
  },
  { immediate: true }
);

async function loadCreditCards(params: Record<string, any> = {}) {
  const query = new URLSearchParams();
  if (searchQuery.value) query.set("search", searchQuery.value);
  if (status.value) query.set("status", status.value);
  if (departmentId.value) query.set("department_id", String(departmentId.value));
  if (bankId.value) query.set("bank_id", String(bankId.value));
  query.set("per_page", String(entriesPerPage.value));
  Object.entries(params).forEach(([k, v]) => {
    if (v !== undefined && v !== null) query.set(k, String(v));
  });
  const { data } = await axios.get(`/credit-cards?${query.toString()}`, {
    headers: { Accept: "application/json" },
  });
  creditCards.value = data;
}

watch([searchQuery, status, departmentId, bankId, entriesPerPage], () => {
  loadCreditCards().catch(() => {});
});

loadCreditCards().catch(() => {});

function openAdd() {
  editData.value = null;
  showForm.value = true;
}
function closeForm() {
  showForm.value = false;
  editData.value = null;
}

// Expose to parent so the page-level Add New button can open this form
defineExpose({ openAdd });

async function handleSubmit(payload: any) {
  try {
    if (editData.value) {
      await axios.post(
        `/credit-cards/${editData.value.id}`,
        { ...payload, _method: "PUT" },
        { headers: { Accept: "application/json" } }
      );
      clearAll();
      addSuccess("Kartu Kredit berhasil diperbarui");
    } else {
      await axios.post("/credit-cards", payload, {
        headers: { Accept: "application/json" },
      });
      clearAll();
      addSuccess("Kartu Kredit berhasil ditambahkan");
    }
    showForm.value = false;
    await loadCreditCards();
  } catch {
    clearAll();
    addError(
      editData.value ? "Gagal memperbarui Kartu Kredit" : "Gagal menambahkan Kartu Kredit"
    );
  }
}

function handlePaginate(url: string) {
  try {
    const u = new URL(url, (globalThis as any).location?.origin ?? "http://localhost");
    const page = u.searchParams.get("page") || undefined;
    loadCreditCards({ page });
  } catch {
    // Fallback: try loading without page
    loadCreditCards().catch(() => {});
  }
}

function handleGoToLog(row: any) {
  router.visit(`/credit-cards/${row.id}/logs`);
}
</script>

<template>
  <CreditCardFilter
    :departments="departmentsState"
    :banks="banksState"
    v-model:search="searchQuery"
    v-model:status="status"
    v-model:entriesPerPage="entriesPerPage"
    v-model:departmentId="departmentId"
    v-model:bankId="bankId"
    @reset="
      () => {
        searchQuery = '';
        status = '';
        departmentId = '';
        bankId = '';
        entriesPerPage = 10;
      }
    "
  />

  <CreditCardTable
    :credit-cards="creditCards"
    @add="openAdd"
    @edit="(row:any)=>{ editData = row; showForm = true }"
    @delete="(row:any)=>{ axios.post(`/credit-cards/${row.id}`, { _method: 'DELETE' }, { headers: { 'Accept': 'application/json' } }).then(()=>{ addSuccess('Berhasil dihapus'); loadCreditCards() }).catch(()=> addError('Gagal menghapus')) }"
    @toggle-status="
      async (row: any) => {
        try {
          const response = await axios.post(
            `/credit-cards/${row.id}/toggle-status`,
            { _method: 'PATCH' },
            { headers: { Accept: 'application/json' } }
          );
          if (response.data.success) {
            addSuccess(response.data.message || 'Status diperbarui');
            await loadCreditCards();
          } else {
            addError(response.data.message || 'Gagal memperbarui status');
          }
        } catch (error) {
          addError('Gagal memperbarui status');
        }
      }
    "
    @paginate="handlePaginate"
    @log="handleGoToLog"
  />

  <CreditCardForm
    v-if="showForm"
    :departments="departmentsState"
    :banks="props.banks || []"
    :edit-data="editData"
    @close="closeForm"
    @submit="handleSubmit"
  />
</template>
