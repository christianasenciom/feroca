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
            <!-- Menú dinámico desde backend -->
            <sidebar-item
              v-for="route in menuItemsFiltrados"
              :key="route.path"
              :item="route"
              :base-path="route.path"
            />

            <!-- ==================== -->
            <!-- GRUPO: SERVICIOS -->
            <!-- ==================== -->
            <el-sub-menu v-if="mostrarServicios" index="servicios">
              <template #title>
                <el-icon><Service /></el-icon>
                <span>Servicios</span>
              </template>

              <!-- Consulta RQ -->
              <el-menu-item index="/consulta-rq/index" @click="navegarA('/consulta-rq/index')">
                <el-icon><Search /></el-icon>
                <span>Consulta RQ</span>
              </el-menu-item>

              <!-- Verificar Identidad -->
              <el-menu-item index="/verificar-identidad/index" @click="navegarA('/verificar-identidad/index')">
                <el-icon><UserFilled /></el-icon>
                <span>Verificar Identidad</span>
              </el-menu-item>
            </el-sub-menu>

            <!-- ==================== -->
            <!-- GRUPO: AUDITORÍA (solo SuperAdmin y Admin) -->
            <!-- ==================== -->
            <el-sub-menu v-if="mostrarAuditoria" index="auditoria">
              <template #title>
                <el-icon><List /></el-icon>
                <span>Auditoría</span>
              </template>

              <!-- Auditoría de Consultas -->
              <el-menu-item index="/dashboard/admin/auditoria" @click="navegarA('/dashboard/admin/auditoria')">
                <el-icon><Document /></el-icon>
                <span>Auditoría de Consultas</span>
              </el-menu-item>
            </el-sub-menu>

          </el-menu>
        </el-scrollbar>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/AuthStore'
import SidebarItem from './SidebarItem.vue'
import { useAppStore } from '@/stores/AppStore'
import { Service, Search, UserFilled, List, Document } from '@element-plus/icons-vue'

const router = useRouter()
const isSmallScreen = ref(false)
const authStore = useAuthStore()
const appStore = useAppStore()

const MenuItems = computed(() => {
  return authStore.userAccessRoutes || []
})

const isCollapse = computed(() => {
  return !appStore.sidebarActive
})

// MOSTRAR SERVICIOS (todos los usuarios autenticados)
const mostrarServicios = computed(() => {
  const roles = authStore.roles || []
  return roles.length > 0
})

// MOSTRAR AUDITORÍA SOLO PARA SUPERADMIN Y ADMIN
const mostrarAuditoria = computed(() => {
  const roles = authStore.roles || []
  if (!roles.length) return false
  const esSuperAdmin = roles.includes('SuperAdministrador')
  const esAdmin = roles.includes('Administrador')
  return esSuperAdmin || esAdmin
})

// 🔥 FUNCIÓN PARA VERIFICAR SI UN ITEM DEBE MOSTRARSE
const puedeVerItem = (item) => {
  const roles = authStore.roles || []
  const permissions = authStore.permissions || []

  // 1. Ocultar por título (para evitar duplicados con grupos manuales)
  const titulosOcultos = ['Consulta RQ', 'Verificar Identidad', 'Auditoría']
  if (item.meta?.title && titulosOcultos.includes(item.meta.title)) {
    return false
  }

  // 2. Ocultar por path
  const rutasOcultas = [
    '/consulta-rq/index',
    '/verificar-identidad/index',
    '/dashboard/admin/auditoria'
  ]
  if (rutasOcultas.includes(item.path)) {
    return false
  }

  // 3. Ocultar por nombre
  const nombresOcultos = ['ConsultaRQ', 'VerificarIdentidad', 'Auditoria']
  if (nombresOcultos.includes(item.name)) {
    return false
  }

  // 4. Verificar roles
  if (item.meta?.roles && item.meta.roles.length > 0) {
    const tieneRolPermitido = item.meta.roles.some(rol => roles.includes(rol))
    if (!tieneRolPermitido) {
      return false
    }
  }

  // 5. Verificar permisos
  if (item.meta?.permission) {
    const tienePermiso = permissions.some(p => p.name === item.meta.permission || p === item.meta.permission)
    if (!tienePermiso) {
      return false
    }
  }

  // 6. Ocultar si está marcado como hidden
  if (item.hidden) {
    return false
  }

  return true
}

// FILTRAR MENÚ RECURSIVAMENTE
const filtrarMenuPorPermisos = (items) => {
  if (!items || !items.length) return []

  return items.filter(item => {
    if (!puedeVerItem(item)) {
      return false
    }

    if (item.children && item.children.length) {
      item.children = filtrarMenuPorPermisos(item.children)
      if (item.children.length === 0) {
        return false
      }
    }

    return true
  })
}

const menuItemsFiltrados = computed(() => {
  const menuOriginal = MenuItems.value
  const menuFiltrado = filtrarMenuPorPermisos(menuOriginal)
  return menuFiltrado
})

const navegarA = (path) => {
  router.push(path)
}

const checkScreenSize = () => {
  isSmallScreen.value = window.innerWidth <= 600

  if (!appStore.sidebarActive) {
    if (isSmallScreen.value) {
      appStore.widthSidebar = '0px'
    } else {
      appStore.widthSidebar = '260px'
    }
  }
}

onMounted(() => {
  checkScreenSize()
  window.addEventListener('resize', checkScreenSize)
})

onMounted(() => {
  checkScreenSize()
  window.addEventListener('resize', checkScreenSize)
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

@media screen and (max-width: 600px) {
  .sidebar-container {
    position: absolute;
    z-index: 9999;
    display: block;
    width: 260px;
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