<script setup lang="ts">
import { computed, ref, watch, onMounted } from "vue";
import { usePage } from "@inertiajs/vue3";

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
  disabled?: boolean;
  required?: boolean;
  showLabel?: boolean;
  loading?: boolean;
  searchable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
  showLabel: true,
  required: false,
  disabled: false,
  loading: false,
  searchable: false,
  placeholder: "Pilih Departemen",
});

const emit = defineEmits(["update:modelValue", "search"]);

const page = usePage();
const user = computed(() => page.props.auth.user);

// Refs for dropdown functionality
const open = ref(false);
const root = ref<HTMLElement | null>(null);
const searchQuery = ref("");

// Check if user has 'All' department
const hasAllDepartment = computed(() => {
  return user.value?.departments?.some((dept) => dept.name === "All") || false;
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

// Transform departments to select format
const selectOptions = computed(() => {
  return props.departments.map((dept) => ({
    label: dept.name,
    value: dept.id,
  }));
});

// Handle option selection
function selectOption(option: { label: string; value: string | number }) {
  emit("update:modelValue", option.value);
  open.value = false;
  searchQuery.value = "";
  // Dispatch event untuk memberitahu sidebar bahwa ada perubahan
  window.dispatchEvent(new CustomEvent("table-changed"));
}

// Handle click outside to close dropdown
function handleClickOutside(event: MouseEvent) {
  if (root.value && !root.value.contains(event.target as Node)) {
    open.value = false;
    searchQuery.value = "";
  }
}

// Handle search functionality
function handleSearch(event: Event) {
  const target = event.target as HTMLInputElement;
  searchQuery.value = target.value;
  if (props.searchable) {
    emit("search", target.value);
  }
}

// Mount event listener
onMounted(() => {
  document.addEventListener("mousedown", handleClickOutside);
});

// Watch open state for scroll behavior
watch(open, (val) => {
  if (!val) {
    searchQuery.value = "";
    return;
  }
  setTimeout(() => {
    const selected = root.value?.querySelector(".custom-option.selected") as HTMLElement;
    if (selected) selected.scrollIntoView({ block: "nearest" });
  }, 0);
});

// Auto-select single department if user only has one
const computedValue = computed({
  get() {
    // If user has single department and no value is set, auto-select it
    if (hasSingleDepartment.value && !props.modelValue && singleDepartment.value) {
      return singleDepartment.value.id;
    }
    return props.modelValue;
  },
  set(value) {
    emit("update:modelValue", value);
  },
});

// Check if this field should be disabled
const isDisabled = computed(() => {
  // Disable if user only has one department (auto-selected)
  if (hasSingleDepartment.value) {
    return true;
  }
  return props.disabled;
});

// Get display text for single department
const singleDepartmentText = computed(() => {
  if (hasSingleDepartment.value && singleDepartment.value) {
    return singleDepartment.value.name;
  }
  return "";
});

// Check if floating label should be active
const isFloating = computed(() => {
  return (
    open.value ||
    (props.modelValue !== undefined &&
      props.modelValue !== null &&
      props.modelValue !== "")
  );
});

// Filtered options for search
const filteredOptions = computed(() => {
  if (!props.searchable || !searchQuery.value) return selectOptions.value;
  return selectOptions.value.filter((option) =>
    option.label.toLowerCase().includes(searchQuery.value.toLowerCase())
  );
});
</script>

<template>
  <div class="w-full">
    <!-- Single Department Display (when user has only one) -->
    <div v-if="hasSingleDepartment" class="relative w-full floating-input">
      <div class="floating-input-field w-full bg-gray-50 cursor-not-allowed opacity-75">
        <span class="text-gray-900">
          {{ singleDepartmentText }}
        </span>
        <svg
          class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none w-4 h-4 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </div>

      <!-- Floating label for single department -->
      <label
        v-if="showLabel && label"
        class="floating-label floating"
        style="left: 0.75rem"
      >
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>

      <!-- Hidden input to maintain form data -->
      <input type="hidden" :value="singleDepartment?.id || ''" />
    </div>

    <!-- Department Dropdown (when user has multiple departments or 'All') -->
    <div v-else ref="root" class="relative w-full floating-input">
      <button
        class="floating-input-field w-full text-left"
        :class="{
          'cursor-pointer': !isDisabled,
          'cursor-not-allowed opacity-50': isDisabled,
        }"
        @click="!isDisabled && (open = !open)"
        type="button"
        :disabled="isDisabled"
      >
        <span
          :class="{
            'text-gray-900': (computedValue ?? '') !== '',
            'text-gray-400': (computedValue ?? '') === '',
          }"
        >
          <!-- Tampilkan label option jika sudah dipilih -->
          <template v-if="(computedValue ?? '') !== ''">
            {{
              selectOptions.find(
                (o) => o.value.toString() === (computedValue ?? "").toString()
              )?.label
            }}
          </template>
          <!-- Tampilkan placeholder hanya saat dropdown dibuka dan belum memilih -->
          <template v-else-if="open">
            {{ placeholder || "Pilih Departemen" }}
          </template>
          <!-- Jika belum memilih dan dropdown belum dibuka, kosong -->
          <template v-else> &nbsp; </template>
        </span>
        <svg
          class="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none w-4 h-4 text-gray-400"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
          :class="{ 'rotate-180': open }"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M19 9l-7 7-7-7"
          />
        </svg>
      </button>

      <!-- Floating label -->
      <label
        v-if="showLabel && label"
        class="floating-label"
        :class="{ floating: isFloating }"
        @click="!isDisabled && (open = true)"
        style="left: 0.75rem"
      >
        {{ label }}
        <span v-if="required" class="text-red-500">*</span>
      </label>

      <!-- Dropdown menu -->
      <div
        v-if="open"
        class="absolute z-[9999] mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-60 overflow-y-auto"
      >
        <!-- Search input -->
        <div v-if="searchable" class="sticky top-0 bg-white border-b border-gray-200 p-2">
          <input
            v-model="searchQuery"
            @input="handleSearch"
            type="text"
            placeholder="Cari departemen..."
            class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
          />
        </div>

        <!-- Loading state -->
        <div v-if="loading" class="p-4 text-center text-gray-500 text-sm">
          Memuat data...
        </div>

        <!-- Options list -->
        <ul v-else>
          <li
            v-for="(option, idx) in filteredOptions"
            :key="option.value"
            @click="selectOption(option)"
            class="custom-option px-4 py-2 cursor-pointer text-sm border-b border-dotted border-gray-300 hover:bg-[#5D42FF14] hover:text-[#644DED]"
            :class="{
              'rounded-t-lg': idx === 0,
              'rounded-b-lg border-b-0': idx === filteredOptions.length - 1,
              'bg-[#EFF6F9] text-[#333] selected':
                (computedValue ?? '').toString() === option.value.toString(),
            }"
          >
            {{ option.label }}
          </li>
          <!-- No results message -->
          <li
            v-if="filteredOptions.length === 0 && searchQuery"
            class="px-4 py-2 text-sm text-gray-500 text-center"
          >
            Tidak ada hasil untuk "{{ searchQuery }}"
          </li>
        </ul>
      </div>
    </div>
  </div>
</template>

<style scoped>
.custom-option {
  transition: background 0.2s, color 0.2s;
}

/* Floating label style mirip input */
.floating-label {
  position: absolute;
  left: 0.75rem;
  top: 1rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #9ca3af;
  transition: all 0.3s ease-in-out;
  pointer-events: none;
  transform-origin: left top;
  background-color: white;
  padding: 0 0.25rem;
  z-index: 1;
}

.floating-label.floating {
  top: -0.5rem;
  font-size: 0.75rem;
  line-height: 1rem;
  color: #333333;
  background-color: white;
  padding: 0 0.25rem;
  pointer-events: auto;
}

.floating-input-field {
  width: 100%;
  padding: 1rem 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  background-color: white;
  min-height: 48px;
  transition: all 0.3s ease-in-out;
  box-sizing: border-box;
  display: flex;
  align-items: center;
}

.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

.floating-input {
  position: relative;
}

/* Button specific styling */
button.floating-input-field {
  appearance: none;
  -webkit-appearance: none;
  background: white;
  border: 1px solid #d1d5db;
  border-radius: 0.375rem;
  padding: 1rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  min-height: 48px;
  transition: all 0.3s ease-in-out;
  box-sizing: border-box;
  text-align: left;
}

button.floating-input-field:focus {
  outline: none;
  border-color: #1f9254;
  box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.2);
}

/* Single department specific styling */
.floating-input-field.bg-gray-50 {
  background-color: #f9fafb;
  border-color: #d1d5db;
}
</style>
