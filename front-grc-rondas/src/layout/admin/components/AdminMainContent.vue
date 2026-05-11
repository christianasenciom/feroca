<template>
  <div style="width: 100% !important; height: 100vh; display: flex; flex-direction: column;">
    <el-header class="header-custom">
      <div style="display: flex">
        <div style="flex: 1">
          <el-row class="py-3 pl-3">
            <el-col :xs="12" :sm="12" :md="12" :lg="12">
              <div style="display: flex; align-items: center; overflow: hidden">
                <v-icon
                  v-if="PageInfo?.icon"
                  :name="PageInfo?.icon"
                  style="margin-right: 15px"
                  scale="1.5"
                />
                <div v-else>
                  <img
                    src="@/assets/img/icono.png"
                    alt="Icon"
                    style="
                      width: 30px;
                      filter: drop-shadow(0px 0px 2px rgba(0, 0, 0, 0.2));
                      margin-right: 15px;
                    "
                  />
                </div>
                <h4 style="white-space: nowrap; overflow: hidden; text-overflow: ellipsis">
                  {{ PageInfo?.title || 'SYS ACHE' }}
                </h4>
              </div>
            </el-col>
            <el-col :xs="12" :sm="12" :md="12" :lg="12">
              <sidebar-user />
            </el-col>
          </el-row>
        </div>
      </div>
    </el-header>
    <el-main class="main-custom">
      <div class="admin-main-container-body">
        <el-card class="mb-3">
          <RouterView />
        </el-card>
      </div>
    </el-main>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useAuthStore } from '@/stores/AuthStore'
import SidebarUser from './SidebarUser.vue'

const authStore = useAuthStore()
const PageInfo = computed(() => {
  return authStore.getCurrentPageInfo
})
</script>

<style scoped>
.header-custom {
  padding: 8px 8px !important;
  background: var(--el-bg-color-page) !important;
  box-shadow: var(--el-box-shadow);
  flex-shrink: 0; /* Evita que el header se encoja */
}

.main-custom {
  flex: 1;
  padding: 10px !important;
  overflow-y: auto;
  padding-bottom: 70px !important; /* Espacio para el footer fijo */
}

.admin-main-container-body {
  width: 100%;
  min-height: calc(100vh - 160px);
}

/* Scroll personalizado */
.main-custom::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

.main-custom::-webkit-scrollbar-track {
  background: #f1f1f1;
  border-radius: 4px;
}

.main-custom::-webkit-scrollbar-thumb {
  background: #c1c1c1;
  border-radius: 4px;
}

.main-custom::-webkit-scrollbar-thumb:hover {
  background: #a8a8a8;
}

/* Responsive */
@media screen and (max-width: 768px) {
  .main-custom {
    padding-bottom: 80px !important;
  }

  .admin-main-container-body {
    min-height: calc(100vh - 140px);
  }
}

@media screen and (max-width: 480px) {
  .main-custom {
    padding: 8px !important;
    padding-bottom: 90px !important;
  }
}
</style>