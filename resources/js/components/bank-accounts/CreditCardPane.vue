<script setup lang="ts">
import { ref, watch } from 'vue'
import { router } from '@inertiajs/vue3'
import { useMessagePanel } from '@/composables/useMessagePanel'
//
import CreditCardForm from './CreditCardForm.vue'
import CreditCardFilter from './CreditCardFilter.vue'
import CreditCardTable from './CreditCardTable.vue'
import axios from 'axios'

const { addSuccess, addError, clearAll } = useMessagePanel()

const showForm = ref(false)
const editData = ref<Record<string, any> | null>(null)

const searchQuery = ref('')
const status = ref('')
const departmentId = ref('')

const departmentsState = ref<any[]>([])

const creditCards = ref<any>({ data: [], links: [], from: 0, to: 0, total: 0 })
const entriesPerPage = ref(10)

const props = defineProps<{ departments?: any[] }>()

watch(() => props.departments, (val) => { if (val) departmentsState.value = val as any[] }, { immediate: true })

async function loadCreditCards(params: Record<string, any> = {}) {
  const query = new URLSearchParams()
  if (searchQuery.value) query.set('search', searchQuery.value)
  if (status.value) query.set('status', status.value)
  if (departmentId.value) query.set('department_id', String(departmentId.value))
  query.set('per_page', String(entriesPerPage.value))
  Object.entries(params).forEach(([k, v]) => { if (v !== undefined && v !== null) query.set(k, String(v)) })
  const { data } = await axios.get(`/credit-cards?${query.toString()}`, { headers: { 'Accept': 'application/json' } })
  creditCards.value = data
}

watch([searchQuery, status, departmentId, entriesPerPage], () => { loadCreditCards().catch(() => {}) })

loadCreditCards().catch(() => {})

function openAdd() { editData.value = null; showForm.value = true }
function closeForm() { showForm.value = false; editData.value = null }

function handleSubmit(payload: any) {
  if (editData.value) {
    router.put(`/credit-cards/${editData.value.id}`, payload, {
      onSuccess: () => { clearAll(); addSuccess('Kartu Kredit berhasil diperbarui'); showForm.value = false },
      onError: () => { clearAll(); addError('Gagal memperbarui Kartu Kredit') }
    })
  } else {
    router.post('/credit-cards', payload, {
      onSuccess: () => { clearAll(); addSuccess('Kartu Kredit berhasil ditambahkan'); showForm.value = false },
      onError: () => { clearAll(); addError('Gagal menambahkan Kartu Kredit') }
    })
  }
}

function handlePaginate(url: string) {
  try {
    const u = new URL(url, (globalThis as any).location?.origin ?? 'http://localhost')
    const page = u.searchParams.get('page') || undefined
    loadCreditCards({ page })
  } catch {
    // Fallback: try loading without page
    loadCreditCards().catch(() => {})
  }
}

</script>

<template>
    <CreditCardFilter
      :departments="departmentsState"
      v-model:search="searchQuery"
      v-model:status="status"
      v-model:entries-per-page="entriesPerPage"
      v-model:department-id="departmentId"
      @reset="() => { searchQuery = ''; status = ''; departmentId = ''; entriesPerPage = 10 }"
    />

    <CreditCardTable
      :credit-cards="creditCards"
      @add="openAdd"
      @edit="(row:any)=>{ editData = row; showForm = true }"
      @delete="(row:any)=>{ axios.delete(`/credit-cards/${row.id}`).then(()=>{ addSuccess('Berhasil dihapus'); loadCreditCards() }).catch(()=> addError('Gagal menghapus')) }"
      @toggle-status="(row:any)=>{ axios.patch(`/credit-cards/${row.id}/toggle-status`).then(()=>{ addSuccess('Status diperbarui'); loadCreditCards() }).catch(()=> addError('Gagal memperbarui status')) }"
      @paginate="handlePaginate"
    />

    <CreditCardForm v-if="showForm" :departments="departmentsState" :edit-data="editData" @close="closeForm" @submit="handleSubmit" />
</template>

