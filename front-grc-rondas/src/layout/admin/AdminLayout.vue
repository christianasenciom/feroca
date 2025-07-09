<template>
  <div>
    <el-container>
      <el-aside width="max-content">
        <SideBar />
      </el-aside>
      <el-container style="padding: 0; width: 100%">
        <AdminMainContent />
      </el-container>
    </el-container>
  </div>
</template>

<script setup>
import AdminMainContent from './components/AdminMainContent.vue'
import SideBar from './components/SideBar.vue'
// import { useAppStore } from '@/stores/AppStore'
// import { computed } from 'vue'

// const appStore = useAppStore()

// const widthLateral = computed(() => {
//   return appStore.widthSidebar
// })
</script>

<style scoped></style>
