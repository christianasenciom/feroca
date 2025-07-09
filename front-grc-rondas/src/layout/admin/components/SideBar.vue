<template>
  <div :class="{ 'sidebar-hidden': isCollapse }" class="sidebar-container">
    <div class="floating-toggle-sidebar">
      <el-button
        circle
        @click="appStore.toggleSideBar()"
        style="
          background-color: #ffffff;
          color: var(--template-color-primary);
          border: 2.5px solid var(--template-color-primary);
        "
      >
        <template #icon>
          <v-icon v-if="isCollapse" name="md-arrowforwardios-round" />
          <v-icon v-else name="md-arrowbackiosnew-round" />
        </template>
      </el-button>
    </div>
    <div>
      <div v-if="!isCollapse" class="sidebar-header">
        <img class="sidebar-image" src="@/assets/img/logo.png" alt="Main Logo" />
      </div>
      <div v-else class="sidebar-header-collapsed">
        <img class="sidebar-image-collapsed" src="@/assets/img/icono.png" alt="Main Icon" />
      </div>
      <div class="sidebar-body">
        <el-scrollbar style="height: 80vh">
          <el-menu
            default-active="/dashboard/index"
            :collapse="isCollapse"
            :collapse-transition="false"
          >
            <sidebar-item
              v-for="route in MenuItems"
              :key="route.path"
              :item="route"
              :base-path="route.path"
            />
          </el-menu>
        </el-scrollbar>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useAuthStore } from '@/stores/AuthStore'
import SidebarItem from './SidebarItem.vue'
import { useAppStore } from '@/stores/AppStore'

const isSmallScreen = ref(false)
const authStore = useAuthStore()
const appStore = useAppStore()

const MenuItems = computed(() => {
  return authStore.userAccessRoutes || []
})

const isCollapse = computed(() => {
  return !appStore.sidebarActive
})

const checkScreenSize = () => {
  isSmallScreen.value = window.innerWidth <= 600

  // El menu debe mostrar u ocultarse responsivamente
  if (!appStore.sidebarActive) {
    if (isSmallScreen.value) {
      // appStore.sidebarActive = true
      appStore.widthSidebar = '0px'
    } else {
      // appStore.sidebarActive = false
      appStore.widthSidebar = '260px'
    }
  }
}

onMounted(() => {
  // Llama a la función para comprobar el tamaño de la pantalla una vez al cargar la página
  checkScreenSize()
  // Escucha el evento resize para ajustar la clase cuando cambie el tamaño de la pantalla
  window.addEventListener('resize', checkScreenSize)
})

onBeforeUnmount(() => {
  // Asegúrate de eliminar el evento de escucha cuando el componente se destruye
  window.removeEventListener('resize', checkScreenSize)
})
</script>

<style>
.sb-icon-close {
  display: none;
}

.sidebar-container {
  width: 260px;
  height: 100vh;
  background-color: var(--template-color-primary);
  transition: margin-left 0.3s ease;
  padding: 15px 0;
  display: block;
  position: relative;
  transform: translateZ(0);
}

.sidebar-hidden {
  width: max-content !important;
}

.sidebar-header {
  display: flex;
  flex-direction: column;
}
.sidebar-header-collapsed {
  display: flex;
  justify-content: center;
  align-items: center;
}

.sidebar-image {
  margin: 0 auto 5px auto;
  padding: 0;
  width: 180px !important;
  height: auto !important;
}

.sidebar-image-collapsed {
  margin: 0 auto 5px auto;
  padding: 0;
  width: 30px;
  height: auto !important;
}

.sidebar-body {
  padding: 10px 0;
}

.sidebar-body .el-menu {
  border-right: 1px solid #204a63;
}

.floating-toggle-sidebar {
  position: fixed;
  right: 0;
  transform: translateY(120%) translateX(50%);
  z-index: 9991;
  transition: transform 0.4s ease-out;
}

/* Media query para pantallas más pequeñas (por ejemplo, dispositivos móviles) */
@media screen and (max-width: 600px) {
  .sidebar-container {
    position: absolute;
    z-index: 9999;
    display: block;
    width: 260px; /* Ancho de menú en resoluciones pequeñas */
    transform: translateX(0);
    transition: transform 0.4s ease;
  }

  .sidebar-hidden {
    transform: translateX(-100%);
  }

  .sidebar-hidden .floating-toggle-sidebar {
    transform: translateY(120%) translateX(70%);
  }
}
</style>
