<template>
  <div class="bg-white rounded-xl shadow p-2">
    <div class="flex items-center justify-between p-2">
      <div class="text-sm text-gray-600">Rows per page</div>
      <select class="select select-bordered select-sm" @change="$emit('update:entries-per-page', Number(($event.target as HTMLSelectElement).value))">
        <option :value="10">10</option>
        <option :value="25">25</option>
        <option :value="50">50</option>
      </select>
    </div>
    <div class="overflow-x-auto">
      <table class="table table-zebra">
        <thead>
          <tr>
            <th></th>
            <th v-for="c in columns" v-show="c.checked" :key="c.key">{{ c.label }}</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="row in data" :key="row.id">
            <td>
              <input type="checkbox" class="checkbox checkbox-sm" :disabled="row.status !== 'Draft'" :checked="selected.includes(row.id)" @change="toggle(row.id, ($event.target as HTMLInputElement).checked)" />
            </td>
            <td v-for="c in columns" v-show="c.checked" :key="c.key">
              <span v-if="c.key === 'department'">{{ row.department?.name }}</span>
              <span v-else-if="c.key === 'no_po_anggaran'">{{ row.poAnggaran?.no_po_anggaran || row.no_po_anggaran }}</span>
              <span v-else>{{ row[c.key] }}</span>
            </td>
            <td class="flex gap-2">
              <button class="btn btn-xs" @click="$emit('action', { action: 'detail', row })">Detail</button>
              <button class="btn btn-xs" :disabled="row.status!=='Draft'" @click="$emit('action', { action: 'edit', row })">Edit</button>
              <button class="btn btn-xs" :disabled="row.status!=='Draft'" @click="$emit('action', { action: 'delete', row })">Cancel</button>
              <button class="btn btn-xs" :disabled="row.status==='Canceled'" @click="$emit('action', { action: 'download', row })">PDF</button>
              <button class="btn btn-xs" @click="$emit('action', { action: 'log', row })">Log</button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
    <div class="p-2 flex justify-end">
      <div class="join">
        <button class="join-item btn btn-sm" :disabled="!pagination?.prev_page_url" @click="$emit('paginate', pagination?.prev_page_url)">Prev</button>
        <button class="join-item btn btn-sm" :disabled="!pagination?.next_page_url" @click="$emit('paginate', pagination?.next_page_url)">Next</button>
      </div>
    </div>
  </div>
</template>
<script setup lang="ts">
const props = defineProps<{ data: any[]; pagination: any; selected: number[]; columns: any[] }>();
const emit = defineEmits(['select','action','paginate','add','update:entries-per-page']);
function toggle(id: number, checked: boolean) {
  const set = new Set(props.selected);
  if (checked) set.add(id); else set.delete(id);
  emit('select', Array.from(set));
}
</script>
