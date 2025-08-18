<script setup lang="ts">
import { ref } from 'vue'
import axios from 'axios'
import { useMessagePanel } from '@/composables/useMessagePanel'

const emit = defineEmits(['close', 'created'])

const { addSuccess, addError, clearAll } = useMessagePanel()

const form = ref({
	 nama: '',
	 deskripsi: '',
	 status: 'active'
})
const errors = ref<{ [key: string]: string }>({})
const loading = ref(false)

function validate() {
	 errors.value = {}
	 if (!form.value.nama) errors.value.nama = 'Nama perihal wajib diisi'
	 return Object.keys(errors.value).length === 0
}

async function submit() {
	 if (!validate()) return
	 loading.value = true
	 clearAll()
	 try {
		 const res = await axios.post('/purchase-orders/add-perihal', {
			 nama: form.value.nama,
			 deskripsi: form.value.deskripsi,
			 status: form.value.status,
		 })
		 addSuccess('Perihal berhasil ditambahkan')
		 emit('created', res?.data?.data || null)
		 emit('close')
	 } catch (e: any) {
		 if (e?.response?.data?.errors) {
			 const srvErr = e.response.data.errors
			 Object.keys(srvErr).forEach((k: string) => {
				 (errors.value as any)[k] = srvErr[k][0]
			 })
		 } else {
			 addError(e?.response?.data?.message || 'Gagal menambahkan perihal')
		 }
	 } finally {
		 loading.value = false
	 }
}
</script>

<template>
	<div class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
		<div class="bg-white rounded-lg w-full max-w-lg shadow-xl">
			<div class="p-5">
				<div class="flex items-center justify-between mb-4">
					<h2 class="text-lg font-semibold text-gray-800">Tambah Perihal</h2>
					<button @click="emit('close')" class="text-gray-400 hover:text-gray-600">
						<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
							<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
						</svg>
					</button>
				</div>

				<div class="space-y-4">
					<div class="floating-input">
						<input
							v-model="form.nama"
							id="nama"
							type="text"
							class="floating-input-field"
							:class="{ 'border-red-500': errors.nama }"
							placeholder=" "
							required
						/>
						<label for="nama" class="floating-label">Nama Perihal<span class="text-red-500">*</span></label>
						<div v-if="errors.nama" class="text-red-500 text-xs mt-1">{{ errors.nama }}</div>
					</div>

					<div class="floating-input">
						<textarea
							v-model="form.deskripsi"
							id="deskripsi"
							class="floating-input-field"
							placeholder=" "
							rows="3"
						/>
						<label for="deskripsi" class="floating-label">Deskripsi</label>
					</div>
				</div>

				<div class="flex justify-start gap-3 pt-6 border-t border-gray-200 mt-6">
					<button
						type="button"
						class="px-6 py-2 text-sm font-medium text-white bg-[#7F9BE6] rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
						@click="submit"
						:disabled="loading"
					>
						Simpan
					</button>
					<button
						type="button"
						class="px-6 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none"
						@click="emit('close')"
					>
						Batal
					</button>
				</div>
			</div>
		</div>
	</div>
</template>

<style scoped>
.floating-input { position: relative; }
.floating-input-field {
	width: 100%;
	padding: 1rem 0.75rem;
	border: 1px solid #d1d5db;
	border-radius: 0.375rem;
	font-size: 0.875rem;
	background-color: white;
}
.floating-label {
	position: absolute;
	left: 0.75rem;
	top: 1rem;
	font-size: 0.875rem;
	color: #9ca3af;
	background-color: white;
	padding: 0 0.25rem;
}
.floating-input-field:focus { outline: none; border-color: #1f9254; box-shadow: 0 0 0 2px rgba(59,130,246,.2) }
.floating-input-field:focus ~ .floating-label,
.floating-input-field:not(:placeholder-shown) ~ .floating-label { top: -0.5rem; font-size: 0.75rem; color: #333 }
</style>

