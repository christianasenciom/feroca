<template>
  <div>
    <v-icon v-if="props.icon" :name="props.icon" />
    <span v-if="appStore.sidebarActive" slot="title" style="margin-left: 10px">{{
      props.title
    }}</span>
  </div>
</template>

<script setup>
import { defineProps } from 'vue'
import { useAppStore } from '@/stores/AppStore'
const appStore = useAppStore()
const props = defineProps({
  icon: {
    type: String,
    default: ''
  },
  title: {
    type: String,
    default: ''
  }
})
</script>

<style scoped></style>
