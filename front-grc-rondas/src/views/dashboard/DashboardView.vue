<template>
  <div class="dashboard-container" v-loading="loading">
    <el-alert
      v-if="errorMessage"
      :title="errorMessage"
      type="error"
      show-icon
      :closable="false"
      style="margin-bottom: 16px;"
    />

    <el-card shadow="never" style="margin-bottom: 16px;">
      <template #header>
        <div class="header-row">
          <div>
            <h2 style="margin: 0;">Panel de Control</h2>
            <small>{{ welcomeText }}</small>
          </div>
          <el-button type="primary" plain @click="fetchDashboard">Actualizar</el-button>
        </div>
      </template>

      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover" class="metric-card">
            <div class="metric-title">Ronderos</div>
            <div class="metric-value">{{ stats.ronderos.total }}</div>
            <small>Activos: {{ stats.ronderos.activos }} | Inactivos: {{ stats.ronderos.inactivos }}</small>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover" class="metric-card">
            <div class="metric-title">Bases</div>
            <div class="metric-value">{{ stats.bases.total }}</div>
            <small>Activas: {{ stats.bases.activas }} | Inactivas: {{ stats.bases.inactivas }}</small>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover" class="metric-card">
            <div class="metric-title">Sectores</div>
            <div class="metric-value">{{ stats.sectores.total }}</div>
            <small>Activos: {{ stats.sectores.activos }} | Inactivos: {{ stats.sectores.inactivos }}</small>
          </el-card>
        </el-col>
        <el-col :xs="24" :sm="12" :md="6">
          <el-card shadow="hover" class="metric-card">
            <div class="metric-title">Denuncias</div>
            <div class="metric-value">{{ stats.denuncias.total }}</div>
            <small>Pendientes: {{ stats.denuncias.pendientes }}</small>
          </el-card>
        </el-col>
      </el-row>

      <el-row :gutter="12" style="margin-top: 12px;">
        <el-col v-if="isSuperAdministrador" :xs="24" :sm="12" :md="6">
          <el-card shadow="hover" class="metric-card">
            <div class="metric-title">Auditoría</div>
            <div class="metric-value">{{ stats.auditoria.total }}</div>
            <small>Hoy: {{ stats.auditoria.hoy }}</small>
          </el-card>
        </el-col>
        <el-col v-if="isSuperAdministrador" :xs="24" :sm="12" :md="18">
          <el-card shadow="hover" class="metric-card">
            <div class="metric-title">Alcance de Datos</div>
            <div class="metric-value" style="font-size: 18px;">{{ stats.scope }}</div>
            <small v-if="stats.bases_asignadas !== null">Bases asignadas: {{ stats.bases_asignadas }}</small>
            <small v-else>Visión global de toda la plataforma</small>
          </el-card>
        </el-col>
      </el-row>
    </el-card>

    <el-card shadow="never">
      <template #header>
        <strong>Actividad Reciente</strong>
      </template>

      <el-table :data="actividad" stripe empty-text="No hay actividad registrada" style="width: 100%;">
        <el-table-column type="index" label="#" width="50" />
        <el-table-column prop="tipo" label="Tipo" width="140" />
        <el-table-column prop="detalle" label="Detalle" min-width="260" />
        <el-table-column prop="usuario" label="Usuario" width="160" />
        <el-table-column prop="fecha" label="Fecha" width="180" />
        <el-table-column label="Estado" width="100" align="center">
          <template #default="scope">
            <el-tag :type="scope.row.exitoso ? 'success' : 'danger'">
              {{ scope.row.exitoso ? 'OK' : 'FAIL' }}
            </el-tag>
          </template>
        </el-table-column>
      </el-table>

      <div class="actividad-pagination">
        <el-pagination
          v-model:current-page="actividadPagination.page"
          v-model:page-size="actividadPagination.limit"
          :page-sizes="[5, 10, 20, 50]"
          :total="actividadPagination.total"
          layout="total, sizes, prev, pager, next"
          background
          @current-change="handleActividadPageChange"
          @size-change="handleActividadSizeChange"
        />
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import axios from '@/utils/request'
import { useAuthStore } from '@/stores/AuthStore'

const authStore = useAuthStore()
const loading = ref(false)
const errorMessage = ref('')
const actividad = ref([])
const actividadPagination = reactive({
  page: 1,
  limit: 10,
  total: 0,
})

const stats = reactive({
  rol: '-',
  scope: '-',
  bases_asignadas: null,
  ronderos: { total: 0, activos: 0, inactivos: 0 },
  bases: { total: 0, activas: 0, inactivas: 0 },
  sectores: { total: 0, activos: 0, inactivos: 0 },
  denuncias: { total: 0, pendientes: 0 },
  auditoria: { total: 0, hoy: 0 },
})

const welcomeText = computed(() => {
  const nombre = authStore.persona || authStore.name || 'usuario'
  return `Bienvenido ${nombre}. Rol activo: ${stats.rol}`
})

const isSuperAdministrador = computed(() => {
  const roles = Array.isArray(authStore.roles) ? authStore.roles : []
  return roles.some((role) => {
    const roleName = typeof role === 'string' ? role : (role?.name || role?.descripcion || '')
    return roleName.toString().trim().toLowerCase().replace(/\s+/g, '') === 'superadministrador'
  })
})

const setStats = (payload) => {
  Object.assign(stats, {
    rol: payload?.rol ?? '-',
    scope: payload?.scope ?? '-',
    bases_asignadas: payload?.bases_asignadas ?? null,
    ronderos: payload?.ronderos ?? { total: 0, activos: 0, inactivos: 0 },
    bases: payload?.bases ?? { total: 0, activas: 0, inactivas: 0 },
    sectores: payload?.sectores ?? { total: 0, activos: 0, inactivos: 0 },
    denuncias: payload?.denuncias ?? { total: 0, pendientes: 0 },
    auditoria: payload?.auditoria ?? { total: 0, hoy: 0 },
  })
}

const fetchDashboard = async () => {
  loading.value = true
  errorMessage.value = ''

  try {
    const response = await axios.get('/admin/dashboard/estadisticas', {
      params: {
        actividad_page: actividadPagination.page,
        actividad_limit: actividadPagination.limit,
      }
    })
    setStats(response?.estadisticas || {})
    actividad.value = response?.actividad_reciente || []
    actividadPagination.total = response?.actividad_meta?.total || 0
    actividadPagination.page = response?.actividad_meta?.current_page || actividadPagination.page
    actividadPagination.limit = response?.actividad_meta?.per_page || actividadPagination.limit
  } catch (error) {
    setStats({})
    actividad.value = []
    actividadPagination.total = 0
    errorMessage.value = error?.response?.data?.message || 'No se pudo cargar el dashboard para este usuario.'
  } finally {
    loading.value = false
  }
}

const handleActividadPageChange = (page) => {
  actividadPagination.page = page
  fetchDashboard()
}

const handleActividadSizeChange = (limit) => {
  actividadPagination.limit = limit
  actividadPagination.page = 1
  fetchDashboard()
}

onMounted(() => {
  fetchDashboard()
})
</script>

<style scoped>
.dashboard-container {
  padding: 12px;
}

.header-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.metric-card {
  min-height: 115px;
}

.metric-title {
  color: #666;
  font-size: 13px;
  margin-bottom: 8px;
}

.metric-value {
  font-size: 28px;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 8px;
}

.actividad-pagination {
  padding-top: 12px;
  display: flex;
  justify-content: flex-end;
}
</style>

