<template>
  <form class="chat-input" @submit.prevent="send">
    <input type="text" v-model="message" maxlength="250" placeholder="Tulis pesan..." />
    <input type="file" @change="onFileChange" />
    <button type="submit">Kirim</button>
    <button type="button" @click="reset">Batal</button>
  </form>
</template>

<script setup>
import { ref } from 'vue'
const props = defineProps({
  conversation: Object
})
const emit = defineEmits(['send'])
const message = ref('')
const file = ref(null)
function onFileChange(e) {
  const f = e.target.files[0]
  if (f && f.size > 5 * 1024 * 1024) {
    alert('Ukuran file maksimal 5MB')
    e.target.value = ''
    return
  }
  file.value = f
}
function send() {
  if (message.value.length > 250) {
    alert('Pesan maksimal 250 karakter')
    return
  }
  emit('send', { message: message.value, file: file.value })
  reset()
}
function reset() {
  message.value = ''
  file.value = null
}
</script>

<style scoped>
.chat-input {
  display: flex;
  gap: 0.5rem;
  padding: 1rem;
  border-top: 1px solid #eee;
}
.chat-input input[type="text"] {
  flex: 1;
  padding: 0.5rem;
}
</style>
