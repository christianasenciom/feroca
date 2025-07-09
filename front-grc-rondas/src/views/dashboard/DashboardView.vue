<template>
  <div>
    <h1>Bienvenido, {{ persona }}</h1>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useAuthStore } from '@/stores/AuthStore'

const authStore = useAuthStore()
const persona = ref('')

onMounted(() => {
  persona.value = authStore.persona
})
</script>
