<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

const emit = defineEmits(['update:activeDepartment']);
const page = usePage();
const user = computed(() => page.props.auth.user);

const departments = computed(() => user.value?.departments || []);
const showDropdown = computed(() => departments.value.length > 1);

const activeDepartment = ref(''); // '' = semua

// Load dari URL parameter saat mount (prioritas utama)
onMounted(() => {
  // Cek URL parameter dulu
  const urlParams = new URLSearchParams(window.location.search);
  const urlDept = urlParams.get('activeDepartment');

  if (urlDept && departments.value.some(d => d.id.toString() === urlDept)) {
    activeDepartment.value = urlDept;
  } else {
    // Fallback ke localStorage
    const saved = localStorage.getItem('activeDepartment');
    if (saved && departments.value.some(d => d.id.toString() === saved)) {
      activeDepartment.value = saved;
    }
  }
});

// Hanya emit jika benar-benar berubah (bukan dari URL)
watch(activeDepartment, (val, oldVal) => {
  // Jangan trigger jika ini dari URL parameter
  if (oldVal !== undefined) { // oldVal undefined = initial load
    localStorage.setItem('activeDepartment', val);
    emit('update:activeDepartment', val);
  }
});
</script>

<template>
  <div v-if="showDropdown" class="flex items-center justify-center">
    <select v-model="activeDepartment" class="border rounded px-2 py-1 text-sm">
      <option value="">Semua Departemen Saya</option>
      <option v-for="dept in departments" :key="dept.id" :value="dept.id">
        {{ dept.name }}
      </option>
    </select>
  </div>
</template>
