<template>
  <div class="header-container">
    <div>
      <img src="@/assets/img/logo.png" width="50" alt="Logo Principal" />
    </div>
    <div class="color-white">

      <el-row>
<!--        <div class="dark-controller">-->
<!--          <UseDark v-slot="{ toggleDark }">-->
<!--            <el-switch-->
<!--              v-model="appStore.isDark"-->
<!--              @click="toggleDark()"-->
<!--              size="large"-->
<!--              class="dark-switch"-->
<!--            >-->
<!--              <template #active-action>-->
<!--                <v-icon name="md-darkmode-outlined" />-->
<!--              </template>-->
<!--              <template #inactive-action>-->
<!--                <v-icon name="md-sunny" />-->
<!--              </template>-->
<!--            </el-switch>-->
<!--          </UseDark>-->
<!--        </div>-->
<!--        <a class="link" href="#">-->
<!--          <v-icon style="width: 18px; height: 18px; margin-right: 5px" name="io-home-outline" />-->
<!--          Página Principal-->
<!--        </a>-->
              <div>
                <img src="@/assets/img/cunarc_logo.png" width="50" alt="Logo Principal" />
              </div>
      </el-row>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { UseDark } from '@vueuse/components'
import { useAppStore } from '@/stores/AppStore'

const appStore = useAppStore()
appStore.isDark = localStorage.getItem('isDark') === 'false' //desactivar el dark auto

const isMobile = ref(false)

const handleResize = () => {
  isMobile.value = window.innerWidth < 1024
}

onMounted(() => {
  window.addEventListener('resize', handleResize)
  handleResize()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', handleResize)
})
</script>
<style scope>
.header-container {
  width: 100vw;
  height: 9vh;
  padding: 0 10vw;
  background-color: var(--template-color-primary);
  box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
  display: flex;
  justify-content: space-between;
  align-items: center;
}
.link {
  color: #c1c3c7;
  font-size: 15px;
  display: flex;
  align-items: center;
  text-decoration: none;
}
.dark-controller {
  margin-right: 10px;
}
@media (hover: hover) {
  .link:hover {
    color: white;
    background-color: transparent;
  }
}
</style>
