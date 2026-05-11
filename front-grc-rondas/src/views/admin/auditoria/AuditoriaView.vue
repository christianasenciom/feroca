<template>
  <div class="auditoria-container">
    <div class="page-header">
      <div class="header-title">
        <h2>
          <el-icon><List /></el-icon>
          Auditoría de Consultas
        </h2>
        <p class="subtitle">Registro de todas las consultas realizadas a RENIEC y Requisitoriados</p>
      </div>
      <div class="header-actions">
        <el-button type="primary" plain @click="refreshData" :loading="loading">
          <el-icon><Refresh /></el-icon>
          Actualizar
        </el-button>
      </div>
    </div>

    <!-- Filtros -->
    <el-card class="filters-card" shadow="never">
      <div class="filters-header" @click="toggleFilters">
        <span>
          <el-icon><Filter /></el-icon>
          Filtros de búsqueda
        </span>
        <el-icon :class="{ 'rotate-icon': filtersVisible }"><ArrowDown /></el-icon>
      </div>
      <el-collapse-transition>
        <div v-show="filtersVisible" class="filters-content">
          <el-row :gutter="16">
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-input
                v-model="filtros.dni"
                placeholder="DNI consultado"
                clearable
                @clear="applyFilters"
                @keyup.enter="applyFilters"
              >
                <template #prefix>
                  <el-icon><Search /></el-icon>
                </template>
              </el-input>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-input
                v-model="filtros.nombre"
                placeholder="Nombre consultado"
                clearable
                @clear="applyFilters"
                @keyup.enter="applyFilters"
              >
                <template #prefix>
                  <el-icon><User /></el-icon>
                </template>
              </el-input>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-select
                v-model="filtros.tipo_consulta"
                placeholder="Tipo de consulta"
                clearable
                @change="applyFilters"
                style="width: 100%"
              >
                <el-option label="RENIEC" value="reniec" />
                <el-option label="REQUISITORIADOS" value="requisitoriado" />
              </el-select>
            </el-col>
            <el-col :xs="24" :sm="12" :md="6" :lg="6">
              <el-select
                v-model="filtros.encontrado"
                placeholder="Estado"
                clearable
                @change="applyFilters"
                style="width: 100%"
              >
                <el-option label="Requisitoriado" :value="true" />
                <el-option label="No Requisitoriado" :value="false" />
              </el-select>
            </el-col>
          </el-row>
        </div>
      </el-collapse-transition>
    </el-card>

    <!-- Tabla de datos -->
    <el-card class="table-card" shadow="never" :body-style="{ padding: '0px' }">
      <div class="table-header">
        <div class="table-title">
          <el-icon><DocumentCopy /></el-icon>
          <span>Registro de Consultas</span>
        </div>
        <div class="table-info">
          <el-tag type="info">Total: {{ pagination.total }} registros</el-tag>
        </div>
      </div>

      <el-table
        :data="auditorias"
        v-loading="loading"
        stripe
        style="width: 100%"
        empty-text="No hay consultas registradas"
      >
        <el-table-column type="index" label="#" width="50" fixed="left" />
        <el-table-column prop="id" label="ID" width="70" />
        <el-table-column prop="tipo_consulta" label="Servicio" width="140">
          <template #default="scope">
            <el-tag :type="scope.row.tipo_consulta === 'reniec' ? 'primary' : 'warning'">
              {{ scope.row.tipo_consulta === 'reniec' ? 'RENIEC' : 'REQUISITORIADOS' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="dni_consultado" label="DNI" width="120">
          <template #default="scope">
            {{ scope.row.dni_consultado || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="nombre_consultado" label="Nombre consultado" min-width="200">
          {{ scope.row.nombre_consultado || '-' }}
        </el-table-column>
        <el-table-column prop="usuario.name" label="Usuario" width="150">
          {{ scope.row.usuario?.name || '-' }}
        </el-table-column>
        <el-table-column prop="rondero_id" label="ID Rondero" width="100" align="center">
          {{ scope.row.rondero_id || '-' }}
        </el-table-column>
        <el-table-column prop="base_id" label="ID Base" width="100" align="center">
          {{ scope.row.base_id || '-' }}
        </el-table-column>
        <el-table-column prop="encontrado" label="Resultado" width="140" align="center">
          <template #default="scope">
            <el-tag :type="scope.row.encontrado ? 'success' : 'danger'">
              {{ scope.row.encontrado ? 'Requisitoriado' : 'No Requisitoriado' }}
            </el-tag>
          </template>
        </el-table-column>
        <el-table-column prop="created_at" label="Fecha" width="180">
          <template #default="scope">
            {{ formatFecha(scope.row.created_at) }}
          </template>
        </el-table-column>
        <el-table-column label="Detalle" width="80" fixed="right" align="center">
          <template #default="scope">
            <el-button link type="primary" @click="verDetalle(scope.row)">
              Ver
            </el-button>
          </template>
        </el-table-column>
      </el-table>

      <div class="pagination-container">
        <el-pagination
          v-model:current-page="pagination.page"
          v-model:page-size="pagination.limit"
          :page-sizes="[10, 15, 25, 50]"
          :total="pagination.total"
          layout="total, sizes, prev, pager, next, jumper"
          @size-change="handleSizeChange"
          @current-change="handleCurrentChange"
          background
        />
      </div>
    </el-card>

    <!-- Modal de detalle -->
    <el-dialog v-model="detalleVisible" title="Detalle de Consulta" width="600px">
      <div v-if="consultaSeleccionada" class="detail-content">
        <el-descriptions :column="1" border>
          <el-descriptions-item label="ID">
            {{ consultaSeleccionada.id }}
          </el-descriptions-item>
          <el-descriptions-item label="Servicio">
            {{ consultaSeleccionada.tipo_consulta === 'reniec' ? 'RENIEC' : 'REQUISITORIADOS' }}
          </el-descriptions-item>
          <el-descriptions-item label="DNI Consultado">
            {{ consultaSeleccionada.dni_consultado || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Nombre Consultado">
            {{ consultaSeleccionada.nombre_consultado || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Usuario">
            {{ consultaSeleccionada.usuario?.name || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Resultado">
            <el-tag :type="consultaSeleccionada.encontrado ? 'success' : 'danger'">
              {{ consultaSeleccionada.encontrado ? 'Requisitoriado' : 'No Requisitoriado' }}
            </el-tag>
          </el-descriptions-item>
          <el-descriptions-item label="IP Usuario">
            {{ consultaSeleccionada.ip_usuario || '-' }}
          </el-descriptions-item>
          <el-descriptions-item label="Fecha Consulta">
            {{ formatFecha(consultaSeleccionada.created_at) }}
          </el-descriptions-item>
        </el-descriptions>
      </div>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import axios from '@/utils/request'
import {
  List, Document, User, Search, Filter, ArrowDown,
  DocumentCopy, Refresh
} from '@element-plus/icons-vue'

// Estado
const auditorias = ref([])
const loading = ref(false)
const detalleVisible = ref(false)
const consultaSeleccionada = ref(null)
const filtersVisible = ref(true)

// Filtros
const filtros = reactive({
  dni: '',
  nombre: '',
  tipo_consulta: '',
  encontrado: null
})

// Paginación
const pagination = reactive({
  page: 1,
  limit: 15,
  total: 0
})

// Funciones
const formatFecha = (fecha) => {
  if (!fecha) return '-'
  const date = new Date(fecha)
  return date.toLocaleString('es-PE')
}

const toggleFilters = () => {
  filtersVisible.value = !filtersVisible.value
}

const applyFilters = () => {
  pagination.page = 1
  fetchAuditorias()
}

const handleSizeChange = (val) => {
  pagination.limit = val
  fetchAuditorias()
}

const handleCurrentChange = (val) => {
  pagination.page = val
  fetchAuditorias()
}

const verDetalle = (row) => {
  consultaSeleccionada.value = row
  detalleVisible.value = true
}

const refreshData = () => {
  fetchAuditorias()
  ElMessage.success('Datos actualizados')
}

const fetchAuditorias = async () => {
  loading.value = true
  try {
    const params = {
      page: pagination.page,
      limit: pagination.limit,
      ...filtros
    }

    // Limpiar parámetros vacíos
    Object.keys(params).forEach(key => {
      if (params[key] === '' || params[key] === null || params[key] === undefined) {
        delete params[key]
      }
    })

    const response = await axios.get('/admin/auditoria', { params })

    if (response.data && response.data.data) {
      auditorias.value = response.data.data
      pagination.total = response.data.meta?.total || 0
    } else {
      auditorias.value = []
      pagination.total = 0
    }
  } catch (error) {
    console.error('Error al cargar auditorías:', error)
    ElMessage.error('Error al cargar auditorías')
    auditorias.value = []
    pagination.total = 0
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchAuditorias()
})
</script>

<style scoped>
.auditoria-container {
  padding: 20px;
  background: #f5f7fa;
  min-height: 100vh;
}

/* Header */
.page-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  margin-bottom: 24px;
  padding: 16px 20px;
  background: white;
  border-radius: 12px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.06);
}

.header-title h2 {
  margin: 0 0 4px 0;
  font-size: 1.5rem;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #2c3e50;
}

.subtitle {
  margin: 0;
  color: #7f8c8d;
  font-size: 0.875rem;
}

/* Filters Card */
.filters-card {
  margin-bottom: 24px;
  border-radius: 12px;
}

.filters-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  cursor: pointer;
  padding: 12px 0;
  font-weight: 500;
  color: #2c3e50;
  user-select: none;
}

.filters-header span {
  display: flex;
  align-items: center;
  gap: 8px;
}

.rotate-icon {
  transform: rotate(180deg);
  transition: transform 0.3s;
}

.filters-content {
  padding-top: 16px;
  border-top: 1px solid #ebeef5;
}

/* Table Card */
.table-card {
  border-radius: 12px;
  overflow: hidden;
}

.table-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 16px 20px;
  border-bottom: 1px solid #ebeef5;
  background: #fafafa;
}

.table-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-weight: 500;
  color: #2c3e50;
}

.table-info {
  display: flex;
  gap: 8px;
}

/* Pagination */
.pagination-container {
  padding: 16px 20px;
  display: flex;
  justify-content: flex-end;
  border-top: 1px solid #ebeef5;
}

/* Dialog */
.detail-content {
  max-height: 60vh;
  overflow-y: auto;
}

/* Responsive */
@media screen and (max-width: 768px) {
  .auditoria-container {
    padding: 12px;
  }

  .page-header {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  .table-header {
    flex-direction: column;
    gap: 12px;
    align-items: flex-start;
  }

  .pagination-container {
    justify-content: center;
  }
}
</style>