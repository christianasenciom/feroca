<template>
  <div class="admin-layout">
    <el-container class="full-height-container">
      <el-aside width="max-content" class="admin-aside">
        <SideBar />
      </el-aside>
      <el-container class="main-container" direction="vertical">
        <AdminMainContent />
      </el-container>
    </el-container>
    <!-- Footer fijo -->
    <AppFooter />
  </div>
</template>

<script setup>
import AdminMainContent from './components/AdminMainContent.vue'
import SideBar from './components/SideBar.vue'
import AppFooter from '@/layout/components/Footer.vue'
</script>

<style scoped>
.admin-layout {
  height: 100vh;
  overflow: hidden;
  position: relative;
}

.full-height-container {
  height: 100%;
}

.admin-aside {
  background-color: var(--template-color-primary);
  height: 100%;
  overflow-y: auto;
}

.main-container {
  display: flex;
  flex-direction: column;
  height: 100%;
  width: 100%;
  overflow: hidden;
}

/* Responsive */
@media screen and (max-width: 768px) {
  .admin-layout {
    height: 100%;
    min-height: 100vh;
    overflow: visible;
  }

  .full-height-container {
    flex-direction: column;
  }

  .admin-aside {
    position: fixed;
    z-index: 1000;
    height: 100vh;
    transform: translateX(-100%);
    transition: transform 0.3s ease;
  }

  .admin-aside.mobile-open {
    transform: translateX(0);
  }

  .main-container {
    margin-left: 0;
  }
}
</style>