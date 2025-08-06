<script setup lang="ts">
import { ref, watch, computed } from 'vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps<{ start?: string; end?: string }>();
const emit = defineEmits(['update:start', 'update:end']);

const today = new Date();
const twoWeeksAgo = new Date();
twoWeeksAgo.setDate(today.getDate() - 13);

const range = ref<(Date|null)[]>([
  props.start ? new Date(props.start) : null,
  props.end ? new Date(props.end) : null
]);

const datepickerRange = computed<Date[]|undefined>(() => {
  if (range.value[0] && range.value[1]) return [range.value[0] as Date, range.value[1] as Date];
  if (range.value[0]) return [range.value[0] as Date];
  if (range.value[1]) return [range.value[1] as Date];
  return undefined;
});

function formatRange(dates: Date[]) {
  if (!dates || !dates[0] || !dates[1]) return ''
  const options: Intl.DateTimeFormatOptions = {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  }
  const start = dates[0].toLocaleDateString('id-ID', options)
  const end = dates[1].toLocaleDateString('id-ID', options)
  return `${start} to ${end}`
}


watch(range, (val) => {
  emit('update:start', val[0] ? val[0].toISOString().slice(0, 10) : '');
  emit('update:end', val[1] ? val[1].toISOString().slice(0, 10) : '');
});
watch(() => props.start, (val) => {
  range.value[0] = val ? new Date(val) : null;
});
watch(() => props.end, (val) => {
  range.value[1] = val ? new Date(val) : null;
});

function onDateRangeChange(val: (Date|null)[]) {
  range.value = val;
}
</script>

<template>
  <!-- <div class="date-range-filter"> -->
    <Datepicker
      v-model="datepickerRange"
      @update:model-value="onDateRangeChange"
      range
      :format="formatRange"
      :input-class="'date-input'"
      placeholder="Pilih rentang tanggal"
    />
    <!-- <span class="calendar-icon">
      <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" class="w-5 h-5">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
      </svg>
    </span>
  </div> -->
</template>

<style scoped>
.date-range-input {
  border: none;
  background: transparent;
  outline: none;
  min-width: 90px;
  font-size: 0.875rem;
  color: #374151;
  text-align: center;
  padding: 0;
}
.date-range-input:focus {
  outline: none;
}

/* Datepicker styling for Quicksand font */
.date-input {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
  color: #374151 !important;
  border: 1px solid #d1d5db !important;
  padding: 0.5rem 0.75rem !important;
  border-radius: 0.375rem !important;
  background-color: white !important;
  transition: all 0.2s !important;
}

.date-input::placeholder {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  color: #6b7280 !important;
  font-size: 0.875rem !important;
  line-height: 1.25rem !important;
}

.date-input:focus {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
  border-color: #5856d6 !important;
}

/* Ensure datepicker dropdown also uses Quicksand */
:deep(.dp__main) {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
}

:deep(.dp__calendar_header),
:deep(.dp__calendar_header_separator),
:deep(.dp__calendar_header_cell),
:deep(.dp__calendar_row),
:deep(.dp__calendar_cell),
:deep(.dp__month_year_row),
:deep(.dp__month_year_select),
:deep(.dp__action_buttons),
:deep(.dp__action_button) {
  font-family: 'Quicksand', Instrument Sans, ui-sans-serif, system-ui, sans-serif !important;
}
</style>
