<script setup lang="ts">
import { ref, watch } from 'vue';
import Datepicker from '@vuepic/vue-datepicker';
import '@vuepic/vue-datepicker/dist/main.css';

const props = defineProps<{ start?: string; end?: string }>();
const emit = defineEmits(['update:start', 'update:end']);

const today = new Date();
const twoWeeksAgo = new Date();
twoWeeksAgo.setDate(today.getDate() - 13);

const range = ref<Date[]>([
  props.start ? new Date(props.start) : twoWeeksAgo,
  props.end ? new Date(props.end) : today
]);

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
  if (val) range.value[0] = new Date(val);
  else range.value[0] = twoWeeksAgo;
});
watch(() => props.end, (val) => {
  if (val) range.value[1] = new Date(val);
  else range.value[1] = today;
});
</script>

<template>
  <!-- <div class="date-range-filter"> -->
    <Datepicker
      v-model="range"
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
</style>
