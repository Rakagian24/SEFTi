<script setup lang="ts">
import { computed, ref, watch, onMounted } from 'vue';
import { usePage } from '@inertiajs/vue3';

interface Department {
  id: number | string;
  name: string;
  disabled?: boolean;
}

interface Props {
  modelValue?: string | number;
  departments: Department[];
  label?: string;
  placeholder?: string;
  showLabel?: boolean;
  emitOnChange?: boolean;
  width?: string;
}

const props = withDefaults(defineProps<Props>(), {
  showLabel: true,
  emitOnChange: true,
  label: 'Departemen',
  placeholder: 'Pilih Departemen'
});

const emit = defineEmits(['update:modelValue', 'change']);

const page = usePage();
const user = computed(() => page.props.auth.user);

// Refs for dropdown functionality
const open = ref(false);
const root = ref<HTMLElement | null>(null);

// Local value for the filter
const localValue = ref(props.modelValue || '');

// Check if user has 'All' department
const hasAllDepartment = computed(() => {
  return user.value?.departments?.some(dept => dept.name === 'All') || false;
});

// Check if user has multiple departments
const hasMultipleDepartments = computed(() => {
  return (user.value?.departments?.length || 0) > 1;
});

// Check if user has only one department (excluding 'All')
const hasSingleDepartment = computed(() => {
  const depts = user.value?.departments || [];
  // Jika user punya 'All', tidak dianggap single department
  if (hasAllDepartment.value) return false;
  return depts.length === 1;
});

// Get the single department if user only has one
const singleDepartment = computed(() => {
  if (hasSingleDepartment.value && user.value?.departments?.[0]) {
    return user.value.departments[0];
  }
  return null;
});

// Transform departments to CustomSelect format with "All" option
const selectOptions = computed(() => {
  const options = [];

  // Add "All" option at the top for resetting filter
  options.push({
    label: props.placeholder || 'Semua Departemen',
    value: ''
  });

  // Add user's departments
  options.push(...props.departments.map(dept => ({
    label: dept.name,
    value: dept.id
  })));

  return options;
});

// Handle value change
function handleValueChange(value: string | number) {
  localValue.value = value;

  if (props.emitOnChange) {
    emit('update:modelValue', value);
    emit('change', value);

    // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
    window.dispatchEvent(new CustomEvent('table-changed'));
  }
}

// Handle option selection for dropdown
function selectOption(option: { label: string, value: string | number }) {
  console.log('SmartDepartmentFilter: Selecting option:', { option, oldValue: localValue.value });

  handleValueChange(option.value);
  open.value = false;
}

// Handle click outside to close dropdown
function handleClickOutside(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false;
  }
}

// Mount event listener
onMounted(() => {
  document.addEventListener('mousedown', handleClickOutside);
});

// Watch for external value changes
watch(() => props.modelValue, (newValue) => {
  localValue.value = newValue || '';
});

// Auto-select single department if user only has one
const computedValue = computed({
  get() {
    // If user has single department and no value is set, auto-select it
    if (hasSingleDepartment.value && !localValue.value && singleDepartment.value) {
      return singleDepartment.value.id;
    }
    return localValue.value;
  },
  set(value) {
    handleValueChange(value);
  }
});

// Get display text for single department
const singleDepartmentText = computed(() => {
  if (hasSingleDepartment.value && singleDepartment.value) {
    return singleDepartment.value.name;
  }
  return '';
});

// Check if filter should be visible
const shouldShowFilter = computed(() => {
  // Always show if user has 'All' department
  if (hasAllDepartment.value) {
    return true;
  }

  // Always show if user has multiple departments
  if (hasMultipleDepartments.value) {
    return true;
  }

  // Show if user has single department but we want to display it
  if (hasSingleDepartment.value) {
    return true;
  }

  return false;
});
</script>

<template>
  <div v-if="shouldShowFilter" ref="root" class="relative" :style="{ display: 'inline-block', minWidth: width || '12rem' }">
    <!-- Single Department Display (when user has only one) -->
    <div v-if="hasSingleDepartment" class="w-full">
      <div class="smart-dept-single-btn border border-gray-300 rounded-md text-left bg-white transition-all duration-300">
        <div class="flex items-center justify-between">
          <span class="text-gray-900">{{ singleDepartmentText }}</span>
          <span class="smart-dept-badge">
            Aktif
          </span>
        </div>
      </div>
      <!-- Hidden input to maintain filter data -->
      <input
        type="hidden"
        :value="singleDepartment?.id || ''"
        @input="handleValueChange(singleDepartment?.id || '')"
      />
    </div>

    <!-- Department Filter Dropdown (when user has multiple departments or 'All') -->
    <div v-else class="relative">
      <button
        class="smart-dept-filter-btn border border-gray-300 rounded-md text-left bg-white focus:outline-none focus:ring-2 focus:ring-[#5856D6] transition-all duration-300"
        @click="open = !open"
        type="button"
      >
        <span :class="{ 'text-gray-900': (computedValue ?? '') !== '', 'text-gray-400': (computedValue ?? '') === '' }" style="margin-right: 2.25rem;">
          {{ selectOptions.find(o => o.value === (computedValue ?? ''))?.label || placeholder || 'Pilih Departemen' }}
        </span>
        <span class="smart-dept-arrow">
          <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="1rem" height="1rem" class="text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
          </svg>
        </span>
      </button>

      <div
        v-if="open"
        class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg"
      >
        <ul>
          <li
            v-for="(option, idx) in selectOptions"
            :key="option.value"
            @click="selectOption(option)"
            class="smart-dept-option px-4 py-2 cursor-pointer text-sm border-b border-dotted border-gray-300 hover:bg-[#5D42FF14] hover:text-[#644DED]"
            :class="{
              'rounded-t-lg': idx === 0,
              'rounded-b-lg border-b-0': idx === selectOptions.length - 1,
              'bg-[#EFF6F9] text-[#333] selected': (computedValue ?? '') === option.value,
              'font-medium text-gray-600': idx === 0 && option.value === ''
            }"
          >
            {{ option.label }}
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.smart-dept-single-btn {
  min-width: inherit;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.2s;
  position: relative;
}

.smart-dept-filter-btn {
  min-width: inherit;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  transition: all 0.2s;
  position: relative;
  width: 100%;
}

.smart-dept-arrow {
  position: absolute;
  right: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
}

.smart-dept-option {
  transition: background 0.2s, color 0.2s;
}

/* Styling khusus untuk option "All" (pertama) */
.smart-dept-option:first-child {
  font-weight: 500;
  color: #4b5563;
  background-color: #f9fafb;
}

.smart-dept-option:first-child:hover {
  background-color: #f3f4f6;
  color: #374151;
}

.smart-dept-badge {
  font-size: 0.75rem;
  color: #2563eb;
  background-color: #dbeafe;
  padding: 0.25rem 0.5rem;
  border-radius: 0.25rem;
  font-weight: 500;
}

.smart-dept-help-text {
  font-size: 0.75rem;
  margin-top: 0.25rem;
  line-height: 1rem;
}
</style>
