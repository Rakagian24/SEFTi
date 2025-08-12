<script setup lang="ts">
import { ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
import AppLayout from "@/layouts/AppLayout.vue";
import UserTable from "../../components/users/UserTable.vue";
import UserFilter from "../../components/users/UserFilter.vue";
import UserForm from "../../components/users/UserForm.vue";
import Breadcrumbs from "@/components/ui/Breadcrumbs.vue";
import PageHeader from "@/components/PageHeader.vue";

defineOptions({ layout: AppLayout });

const breadcrumbs = [
  { label: "Home", href: "/dashboard" },
  { label: "User" }
];

const showForm = ref(false);
const editData = ref<Record<string, any> | undefined>(undefined);

const props = defineProps({
  users: Object,
  filters: Object,
  roles: {
    type: Array as () => Array<{id:number,name:string}>,
    default: () => [],
  },
  departments: {
    type: Array as () => Array<{id:number,name:string}>,
    default: () => [],
  },
});

// Filter state
const entriesPerPage = ref(props.filters?.per_page || 10);
const searchQuery = ref(props.filters?.search || "");
const roleId = ref(props.filters?.role_id || "");
const departmentId = ref(props.filters?.department_id || "");

// Watch for changes and apply filters automatically
watch([entriesPerPage, roleId, departmentId], () => {
  applyFilters();
}, { immediate: false });

// Watch search query with debouncing
let searchTimeout: ReturnType<typeof setTimeout>;
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    applyFilters();
  }, 500);
}, { immediate: false });

function applyFilters() {
  const params: Record<string, any> = {};
  if (searchQuery.value) params.search = searchQuery.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  if (roleId.value) params.role_id = roleId.value;
  if (departmentId.value) params.department_id = departmentId.value;

  // Tambahkan activeDepartment dari URL
  const urlParams = new URLSearchParams(window.location.search);
  const activeDept = urlParams.get('activeDepartment');
  if (activeDept) params.activeDepartment = activeDept;

  router.get('/users', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function resetFilters() {
  searchQuery.value = '';
  entriesPerPage.value = 10;
  roleId.value = '';
  departmentId.value = '';
  router.get('/users', { per_page: 10 }, {
    preserveState: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function handlePagination(url: string) {
  if (!url) return;
  const urlParams = new URLSearchParams(url.split('?')[1]);
  const page = urlParams.get('page');
  const params: Record<string, any> = { page };
  if (searchQuery.value) params.search = searchQuery.value;
  if (entriesPerPage.value) params.per_page = entriesPerPage.value;
  router.get('/users', params, {
    preserveState: true,
    preserveScroll: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}

function openEdit(row: any) {
  editData.value = row;
  showForm.value = true;
}

function closeForm() {
  showForm.value = false;
  editData.value = undefined;
}

function handleDetail(row: any) {
  router.visit(`/users/${row.id}/edit`);
}

function handleToggleStatus(row: any) {
  router.patch(`/users/${row.id}/toggle-status`, {}, {
    preserveState: true,
    onSuccess: () => {
      window.dispatchEvent(new CustomEvent('table-changed'));
    }
  });
}
</script>

<template>
  <div class="bg-[#DFECF2] min-h-screen">
    <div class="pl-2 pt-6 pr-6 pb-6">
      <!-- Breadcrumbs -->
      <Breadcrumbs :items="breadcrumbs" />
      <!-- Header -->
      <PageHeader
        title="User"
        description="Manajemen data user"
        :show-add-button="false"
      />
      <!-- Filter Section -->
      <UserFilter
        :filters="props.filters"
        :roles="props.roles"
        :departments="props.departments"
        v-model:search="searchQuery"
        v-model:role-id="roleId"
        v-model:department-id="departmentId"
        v-model:entries-per-page="entriesPerPage"
        @reset="resetFilters"
      />
      <!-- Table Section -->
      <UserTable
        :users="props.users"
        @edit="openEdit"
        @detail="handleDetail"
        @paginate="handlePagination"
        @toggleStatus="handleToggleStatus"
      />
      <!-- Form Modal -->
      <UserForm v-if="showForm" :edit-data="editData" :roles="props.roles" :departments="props.departments" @close="closeForm" />
    </div>
  </div>
</template>
