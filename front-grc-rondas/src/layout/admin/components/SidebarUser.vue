<template>
  <div class="sidebar-user-container">
    <el-row type="flex" align="middle" justify="end">
<!--      <div>-->
<!--        <UseDark v-slot="{ toggleDark }">-->
<!--          <el-switch v-model="appStore.isDark" @click="toggleDark" size="large" class="dark-switch">-->
<!--            <template #active-action>-->
<!--              <v-icon name="md-darkmode-outlined" />-->
<!--            </template>-->
<!--            <template #inactive-action>-->
<!--              <v-icon name="md-sunny" />-->
<!--            </template>-->
<!--          </el-switch>-->
<!--        </UseDark>-->
<!--      </div>-->
      <div id="useremail" style="overflow: hidden; margin-right: 12px">
        <h5>{{ username }}</h5>
      </div>
      <img v-if="avatar" :src="avatar" class="user-profile" alt="User Profile Image" />
      <v-icon
        v-else
        scale="1.5"
        name="fa-user-circle"
        style="margin-right: 10px; color: var(--el-text-color-primary)"
      />
      <el-dropdown trigger="click">
        <span class="el-dropdown-link">
          <v-icon name="co-options" scale="1.3" />
        </span>
        <template #dropdown>
          <el-dropdown-menu>
            <el-dropdown-item @click="showPerfil()">
              <span>
                <v-icon
                  name="fa-user-circle"
                  style="margin-right: 10px; color: var(--el-text-color-primary)"
                />
                Perfil
              </span>
            </el-dropdown-item>
            <el-dropdown-item divided @click="openDialog">
              <span>
                <v-icon
                  name="hi-logout"
                  style="margin-right: 10px; color: var(--el-text-color-primary)"
                />
                Cerrar sesión
              </span>
            </el-dropdown-item>
          </el-dropdown-menu>
        </template>
      </el-dropdown>
    </el-row>
    <el-dialog
      v-model="dialogVisible"
      title="CERRAR SESIÓN"
      :width="dialogWidth"
      top="10vh"
      center
      :close-on-click-modal="false"
      :close-on-press-escape="false"
    >
      <div v-loading.fullscreen.lock="loading" style="text-align: center">
        <p>¿Estás seguro de que deseas cerrar sesión?</p>
        <el-divider />
        <el-row :gutter="20" type="flex" justify="center">
          <el-button @click="handleClose('all')" style="margin: 5px">Todas las sesiones</el-button>
          <el-button type="primary" @click="handleClose('current')" style="margin: 5px">
            Sesión actual
          </el-button>
        </el-row>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import { useAuthStore } from '@/stores/AuthStore'
import { useRouter } from 'vue-router'
import { useAppStore } from '@/stores/AppStore'
import { UseDark } from '@vueuse/components'

const appStore = useAppStore()
appStore.isDark = localStorage.getItem('isDark') === 'false' //desactivar el dark auto
const dialogWidth = ref('')

const router = useRouter()

const authStore = useAuthStore()

const username = computed(() => authStore.persona)
const avatar = computed(() => authStore.avatar)

const dialogVisible = ref(false)
const loading = ref(false)

const handleClose = async (action) => {
  try {
    loading.value = true
    await authStore.logout(action)
    router.push(`/signin`)
  } catch (error) {
    console.error(error)
  } finally {
    loading.value = false
  }
}

const openDialog = () => {
  dialogVisible.value = true
}

const showPerfil = () => {
  router.push(`/profile`)
}

const setDialogWidth = () => {
  if (window.innerWidth < 361) {
    dialogWidth.value = '95%'
  } else {
    dialogWidth.value = '320px'
  }
}

onMounted(() => {
  setDialogWidth()
  window.addEventListener('resize', setDialogWidth)
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', setDialogWidth)
})
</script>
<style>
.sidebar-user-container {
  min-height: 30px;
}

.user-profile {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  margin-right: 10px;
}

#useremail {
  display: block;
}

@media screen and (max-width: 600px) {
  #useremail {
    display: none;
  }
}
</style>
