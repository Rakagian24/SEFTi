<template>
  <div>
    <h1 class="text-2xl font-bold mb-4">Log Aktivitas Purchase Order</h1>
    <div class="mb-4 flex flex-wrap gap-2">
      <input v-model="filters.search" placeholder="Cari user/deskripsi..." class="input input-bordered" />
      <select v-model="filters.action" class="select select-bordered">
        <option value="">Semua Aksi</option>
        <option v-for="action in actionOptions" :key="action" :value="action">{{ action }}</option>
      </select>
      <select v-model="filters.role" class="select select-bordered">
        <option value="">Semua Role</option>
        <option v-for="role in roleOptions" :key="role.id" :value="role.name">{{ role.name }}</option>
      </select>
      <select v-model="filters.department" class="select select-bordered">
        <option value="">Semua Departemen</option>
        <option v-for="dept in departmentOptions" :key="dept.id" :value="dept.name">{{ dept.name }}</option>
      </select>
      <input v-model="filters.date" type="date" class="input input-bordered" />
      <button @click="() => fetchLogs()" class="btn btn-primary">Filter</button>
    </div>
    <div class="overflow-x-auto">
      <table class="table w-full">
        <thead>
          <tr>
            <th>Tanggal</th>
            <th>User</th>
            <th>Role</th>
            <th>Departemen</th>
            <th>Aksi</th>
            <th>Deskripsi</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="log in logs.data" :key="log.id">
            <td>{{ formatDate(log.created_at) }}</td>
            <td>{{ log.user?.name }}</td>
            <td>{{ log.user?.role?.name }}</td>
            <td>{{ log.user?.department?.name }}</td>
            <td>{{ log.action }}</td>
            <td>{{ log.description }}</td>
            <td>{{ log.ip_address }}</td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="mt-4 flex justify-end">
      <button @click="prevPage" :disabled="!logs.prev_page_url" class="btn btn-sm mr-2">Prev</button>
      <button @click="nextPage" :disabled="!logs.next_page_url" class="btn btn-sm">Next</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import axios from 'axios';

interface Role { id: number; name: string; }
interface Department { id: number; name: string; }
interface User { name: string; role?: Role; department?: Department; }
interface Log {
  id: number;
  created_at: string;
  user?: User;
  action: string;
  description: string;
  ip_address: string;
}
interface LogsResponse {
  data: Log[];
  prev_page_url?: string;
  next_page_url?: string;
}

const route = useRoute();
const purchaseOrderId = route.params.id;
const logs = ref<LogsResponse>({ data: [] });
const filters = ref({ search: '', action: '', role: '', department: '', date: '', per_page: 10 });
const actionOptions = ref<string[]>([]);
const roleOptions = ref<Role[]>([]);
const departmentOptions = ref<Department[]>([]);

function formatDate(date: string) {
  return new Date(date).toLocaleString();
}

async function fetchLogs(pageUrl: string | null = null) {
  const url = pageUrl || `/purchase-orders/${purchaseOrderId}/log`;
  const { search, action, role, department, date, per_page } = filters.value;
  const params = { search, action, role, department, date, per_page };
  const { data } = await axios.get(url, { params });
  logs.value = data.logs;
  // Optionally set options if backend provides
  if (data.roleOptions) roleOptions.value = data.roleOptions;
  if (data.departmentOptions) departmentOptions.value = data.departmentOptions;
  if (data.actionOptions) actionOptions.value = data.actionOptions;
}

function prevPage() {
  if (logs.value.prev_page_url) fetchLogs(logs.value.prev_page_url);
}
function nextPage() {
  if (logs.value.next_page_url) fetchLogs(logs.value.next_page_url);
}

onMounted(fetchLogs);
</script>

<style scoped>
.table th, .table td {
  white-space: nowrap;
}
</style>
