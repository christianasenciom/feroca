<template>
  <div class="configuracion-container">
    <el-card class="config-card" shadow="never">
      <template #header>
        <div class="card-header">
          <div class="header-title">
            <el-icon><Tools /></el-icon>
            <span>Configuración del Sistema</span>
          </div>
          <el-tag type="warning" size="large">Solo SuperAdministrador</el-tag>
        </div>
      </template>

      <el-alert
          title="Información importante"
          type="info"
          :closable="false"
          show-icon
          class="mb-4"
      >
        <template #default>
          <p>Las credenciales de RENIEC y la URL de Consulta RQ se guardan en la base de datos.</p>
          <p class="text-muted">Modifícalas solo si las credenciales han cambiado o han caducado.</p>
        </template>
      </el-alert>

      <el-tabs v-model="activeTab" type="border-card">

        <!-- ==================== TAB 1: RENIEC ==================== -->
        <el-tab-pane label="RENIEC" name="reniec">
          <el-form
              ref="reniecFormRef"
              :model="reniecForm"
              :rules="reniecRules"
              label-width="200px"
              v-loading="loading"
          >
            <el-form-item label="URL API RENIEC REST" prop="RENIEC_REST_URL">
              <el-input v-model="reniecForm.RENIEC_REST_URL" placeholder="https://ws2.pide.gob.pe/Rest/RENIEC/Consultar" />
              <div class="form-hint">URL del servicio REST de RENIEC</div>
            </el-form-item>

            <el-form-item label="Usuario DNI" prop="RENIEC_DNI_USUARIO">
              <el-input v-model="reniecForm.RENIEC_DNI_USUARIO" maxlength="8" placeholder="41884337" />
              <div class="form-hint">Usuario DNI para autenticación (8 dígitos)</div>
            </el-form-item>

            <el-form-item label="Contraseña" prop="RENIEC_PASSWORD">
              <el-input v-model="reniecForm.RENIEC_PASSWORD" type="password" show-password placeholder="********" />
              <div class="form-hint">Contraseña para autenticación</div>
            </el-form-item>

            <el-form-item label="Usuario RUC" prop="RENIEC_RUC_USUARIO">
              <el-input v-model="reniecForm.RENIEC_RUC_USUARIO" maxlength="11" placeholder="20453744168" />
              <div class="form-hint">Usuario RUC para autenticación (11 dígitos)</div>
            </el-form-item>

            <el-form-item label="Timeout (segundos)" prop="RENIEC_TIMEOUT">
              <el-input-number v-model="reniecForm.RENIEC_TIMEOUT" :min="10" :max="300" :step="5" />
              <div class="form-hint">Tiempo máximo de espera para consultas</div>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" @click="saveReniec" :loading="saving">
                <el-icon><CircleCheck /> </el-icon>
                Guardar cambios
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <!-- ==================== TAB 2: CONSULTA RQ ==================== -->
        <el-tab-pane label="Consulta RQ" name="consulta-rq">
          <el-form
              ref="consultaRQFormRef"
              :model="consultaRQForm"
              :rules="consultaRQRules"
              label-width="200px"
              v-loading="loading"
          >
            <el-form-item label="URL API Consulta RQ" prop="CONSULTA_RQ_URL">
              <el-input v-model="consultaRQForm.CONSULTA_RQ_URL" placeholder="https://sispasvehapp.mininter.gob.pe/api-recompensas/requisitoriados" />
              <div class="form-hint">URL del servicio de Consulta RQ</div>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" @click="saveConsultaRQ" :loading="saving">
                <el-icon><CircleCheck /></el-icon>
                Guardar cambios
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

        <!-- ==================== TAB 3: ACTUALIZAR CREDENCIAL ==================== -->
        <el-tab-pane label="Actualizar Credencial" name="actualizar-credencial">
          <el-alert
              title="Importante"
              type="warning"
              :closable="false"
              show-icon
              class="mb-4"
          >
            <template #default>
              <p>Este método permite cambiar la contraseña del usuario consultor en el servicio RENIEC.</p>
              <p class="text-muted">La nueva contraseña se guardará automáticamente en la configuración.</p>
            </template>
          </el-alert>

          <el-form
              ref="actualizarFormRef"
              :model="actualizarForm"
              :rules="actualizarRules"
              label-width="200px"
              v-loading="actualizando"
          >
            <el-form-item label="DNI del Usuario" prop="nuDni">
              <el-input v-model="actualizarForm.nuDni" maxlength="8" placeholder="41884337" />
              <div class="form-hint">DNI del usuario registrado en RENIEC (se carga automáticamente)</div>
            </el-form-item>

            <el-form-item label="RUC de la Entidad" prop="nuRuc">
              <el-input v-model="actualizarForm.nuRuc" maxlength="11" placeholder="20453744168" />
              <div class="form-hint">RUC de la entidad (se carga automáticamente)</div>
            </el-form-item>

            <el-form-item label="Contraseña Actual" prop="credencialAnterior">
              <el-input v-model="actualizarForm.credencialAnterior" type="password" show-password />
              <div class="form-hint">Contraseña actual del usuario consultor (se carga automáticamente)</div>
            </el-form-item>

            <el-form-item label="Nueva Contraseña" prop="credencialNueva">
              <el-input v-model="actualizarForm.credencialNueva" type="password" show-password />
              <div class="form-hint">Nueva contraseña (mínimo 6 caracteres)</div>
            </el-form-item>

            <el-form-item label="Confirmar Nueva Contraseña" prop="confirmarPassword">
              <el-input v-model="actualizarForm.confirmarPassword" type="password" show-password />
              <div class="form-hint">Confirme la nueva contraseña</div>
            </el-form-item>

            <el-form-item>
              <el-button type="primary" @click="actualizarCredencial" :loading="actualizando">
                <el-icon><Key /></el-icon>
                Actualizar Credencial
              </el-button>
            </el-form-item>
          </el-form>
        </el-tab-pane>

      </el-tabs>

      <div class="actions-bar">
        <el-button @click="loadDefaults">
          <el-icon><RefreshLeft /></el-icon>
          Restaurar valores por defecto
        </el-button>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, watch } from 'vue'
import { ElMessage } from 'element-plus'
import { RefreshLeft, CircleCheck, Tools, Key } from '@element-plus/icons-vue'
import axios from '@/utils/request'

const activeTab = ref('reniec')
const loading = ref(false)
const saving = ref(false)
const actualizando = ref(false)

// ==================== FORMULARIO RENIEC ====================
const reniecFormRef = ref(null)
const reniecForm = reactive({
  RENIEC_REST_URL: '',
  RENIEC_DNI_USUARIO: '',
  RENIEC_PASSWORD: '',
  RENIEC_RUC_USUARIO: '',
  RENIEC_TIMEOUT: 60
})

const reniecRules = {
  RENIEC_REST_URL: [
    { required: true, message: 'La URL es requerida', trigger: 'blur' },
    { type: 'url', message: 'Ingrese una URL válida', trigger: 'blur' }
  ],
  RENIEC_DNI_USUARIO: [
    { required: true, message: 'El usuario DNI es requerido', trigger: 'blur' },
    { len: 8, message: 'El DNI debe tener 8 dígitos', trigger: 'blur' },
    { pattern: /^[0-9]{8}$/, message: 'Solo números', trigger: 'blur' }
  ],
  RENIEC_PASSWORD: [
    { required: true, message: 'La contraseña es requerida', trigger: 'blur' },
    { min: 6, message: 'Mínimo 6 caracteres', trigger: 'blur' }
  ],
  RENIEC_RUC_USUARIO: [
    { required: true, message: 'El usuario RUC es requerido', trigger: 'blur' },
    { len: 11, message: 'El RUC debe tener 11 dígitos', trigger: 'blur' },
    { pattern: /^[0-9]{11}$/, message: 'Solo números', trigger: 'blur' }
  ],
  RENIEC_TIMEOUT: [
    { required: true, message: 'El timeout es requerido', trigger: 'blur' },
    { type: 'number', min: 10, max: 300, message: 'Debe estar entre 10 y 300', trigger: 'blur' }
  ]
}

// ==================== FORMULARIO CONSULTA RQ ====================
const consultaRQFormRef = ref(null)
const consultaRQForm = reactive({
  CONSULTA_RQ_URL: ''
})

const consultaRQRules = {
  CONSULTA_RQ_URL: [
    { required: true, message: 'La URL es requerida', trigger: 'blur' },
    { type: 'url', message: 'Ingrese una URL válida', trigger: 'blur' }
  ]
}

// ==================== FORMULARIO ACTUALIZAR CREDENCIAL ====================
const actualizarFormRef = ref(null)
const actualizarForm = reactive({
  nuDni: '',
  nuRuc: '',
  credencialAnterior: '',
  credencialNueva: '',
  confirmarPassword: ''
})

const actualizarRules = {
  nuDni: [
    { required: true, message: 'El DNI es requerido', trigger: 'blur' },
    { len: 8, message: 'El DNI debe tener 8 dígitos', trigger: 'blur' },
    { pattern: /^[0-9]{8}$/, message: 'Solo números', trigger: 'blur' }
  ],
  nuRuc: [
    { required: true, message: 'El RUC es requerido', trigger: 'blur' },
    { len: 11, message: 'El RUC debe tener 11 dígitos', trigger: 'blur' },
    { pattern: /^[0-9]{11}$/, message: 'Solo números', trigger: 'blur' }
  ],
  credencialAnterior: [
    { required: true, message: 'La contraseña actual es requerida', trigger: 'blur' }
  ],
  credencialNueva: [
    { required: true, message: 'La nueva contraseña es requerida', trigger: 'blur' },
    { min: 6, message: 'La contraseña debe tener al menos 6 caracteres', trigger: 'blur' }
  ],
  confirmarPassword: [
    { required: true, message: 'Confirme la nueva contraseña', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (value !== actualizarForm.credencialNueva) {
          callback(new Error('Las contraseñas no coinciden'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ]
}

// ==================== FUNCIONES ====================

// Cargar configuración desde la BD
const loadConfig = async () => {
  loading.value = true
  try {
    const response = await axios.get('/admin/configuracion')
    const data = response.data || response

    if (data) {
      // Cargar datos en TAB 1
      reniecForm.RENIEC_REST_URL = data.RENIEC_REST_URL || ''
      reniecForm.RENIEC_DNI_USUARIO = data.RENIEC_DNI_USUARIO || ''
      reniecForm.RENIEC_PASSWORD = data.RENIEC_PASSWORD || ''
      reniecForm.RENIEC_RUC_USUARIO = data.RENIEC_RUC_USUARIO || ''
      reniecForm.RENIEC_TIMEOUT = Number(data.RENIEC_TIMEOUT) || 60

      // Cargar datos en TAB 2
      consultaRQForm.CONSULTA_RQ_URL = data.CONSULTA_RQ_URL || ''

      // 🔥 Cargar los mismos datos en TAB 3 (Actualizar Credencial)
      actualizarForm.nuDni = data.RENIEC_DNI_USUARIO || ''
      actualizarForm.nuRuc = data.RENIEC_RUC_USUARIO || ''
      actualizarForm.credencialAnterior = data.RENIEC_PASSWORD || ''
    }
  } catch (error) {
    console.error('Error cargando configuración:', error)
    ElMessage.error('Error al cargar la configuración')
  } finally {
    loading.value = false
  }
}

// Guardar configuración RENIEC
const saveReniec = async () => {
  try {
    const valid = await reniecFormRef.value?.validate()
    if (!valid) return

    saving.value = true
    const response = await axios.post('/admin/configuracion/reniec', reniecForm)

    if (response && response.success) {
      ElMessage.success(response.message || 'Configuración RENIEC guardada correctamente')
      await loadConfig()
    } else {
      ElMessage.error(response?.message || 'Error al guardar')
    }
  } catch (error) {
    console.error('Error guardando:', error)
    ElMessage.error(error.response?.data?.message || 'Error al guardar la configuración')
  } finally {
    saving.value = false
  }
}

// Guardar configuración Consulta RQ
const saveConsultaRQ = async () => {
  try {
    const valid = await consultaRQFormRef.value?.validate()
    if (!valid) return

    saving.value = true
    const response = await axios.post('/admin/configuracion/consulta-rq', consultaRQForm)

    if (response && response.success) {
      ElMessage.success(response.message || 'URL de Consulta RQ guardada correctamente')
      await loadConfig()
    } else {
      ElMessage.error(response?.message || 'Error al guardar')
    }
  } catch (error) {
    console.error('Error guardando:', error)
    ElMessage.error(error.response?.data?.message || 'Error al guardar la URL de Consulta RQ')
  } finally {
    saving.value = false
  }
}

// Actualizar credencial en RENIEC
const actualizarCredencial = async () => {
  try {
    const valid = await actualizarFormRef.value?.validate()
    if (!valid) return

    actualizando.value = true
    const response = await axios.post('/admin/configuracion/reniec/actualizar-credencial', {
      nuDni: actualizarForm.nuDni,
      nuRuc: actualizarForm.nuRuc,
      credencialAnterior: actualizarForm.credencialAnterior,
      credencialNueva: actualizarForm.credencialNueva
    })

    // 🔥 CORREGIDO: Verificar response.success (no response.data.success)
    if (response && response.success) {
      // Mostrar mensaje detallado
      ElMessage.success({
        message: response.message || `Contraseña actualizada correctamente`,
        duration: 5000
      })

      // Limpiar campos de nueva contraseña
      actualizarForm.credencialNueva = ''
      actualizarForm.confirmarPassword = ''

      // Recargar configuración para actualizar la contraseña guardada
      await loadConfig()
    } else {
      ElMessage.error(response?.message || 'Error al actualizar la credencial')
    }
  } catch (error) {
    console.error('Error actualizando credencial:', error)
    const errorMsg = error.response?.data?.message || 'Error al actualizar la credencial'
    ElMessage.error(errorMsg)
  } finally {
    actualizando.value = false
  }
}

// Cargar valores por defecto
const loadDefaults = async () => {
  try {
    const response = await axios.post('/admin/configuracion/defaults')
    if (response.data && response.data.success) {
      ElMessage.success('Valores por defecto cargados correctamente')
      await loadConfig()
    } else {
      ElMessage.error(response.data?.message || 'Error al cargar valores por defecto')
    }
  } catch (error) {
    console.error('Error cargando valores por defecto:', error)
    ElMessage.error('Error al cargar valores por defecto')
  }
}

onMounted(() => {
  loadConfig()
})
</script>

<style scoped>
.configuracion-container {
  padding: 20px;
  background: #f5f7fa;
  min-height: 100vh;
}

.config-card {
  max-width: 800px;
  margin: 0 auto;
  border-radius: 12px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.header-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 18px;
  font-weight: 500;
}

.header-title .el-icon {
  font-size: 22px;
  color: #409eff;
}

.mb-4 {
  margin-bottom: 20px;
}

.text-muted {
  color: #909399;
  font-size: 13px;
  margin-top: 5px;
}

.form-hint {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.actions-bar {
  max-width: 800px;
  margin: 20px auto 0;
  text-align: center;
  padding-top: 20px;
  border-top: 1px solid #ebeef5;
}

/* Responsive */
@media screen and (max-width: 768px) {
  .configuracion-container {
    padding: 12px;
  }

  .config-card {
    max-width: 100%;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }
}
</style>