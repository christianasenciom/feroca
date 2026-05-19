<template>
  <div class="reniec-config-container">
    <el-card class="config-card" shadow="never">
      <template #header>
        <div class="card-header">
          <div class="header-title">
            <el-icon><Setting /></el-icon>
            <span>Configuración de API RENIEC</span>
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
          <p>Estas credenciales son utilizadas para consultar datos de DNI y RUC.</p>
          <p class="text-muted">Modifícalas solo si las credenciales han cambiado o han caducado.</p>
        </template>
      </el-alert>

      <el-form
          ref="formRef"
          :model="form"
          :rules="rules"
          label-width="200px"
          label-position="right"
          v-loading="loading"
      >
        <el-form-item label="URL API RENIEC REST" prop="RENIEC_REST_URL">
          <!-- 🔥 DATOS REALES en el VALUE, no en placeholder -->
          <el-input
              v-model="form.RENIEC_REST_URL"
          />
          <div class="form-hint">URL del servicio REST de RENIEC</div>
        </el-form-item>

        <el-form-item label="Usuario DNI" prop="RENIEC_DNI_USUARIO">
          <el-input
              v-model="form.RENIEC_DNI_USUARIO"
              maxlength="8"
          />
          <div class="form-hint">Usuario DNI para autenticación (8 dígitos)</div>
        </el-form-item>

        <el-form-item label="Contraseña" prop="RENIEC_PASSWORD">
          <!-- 🔥 DATOS REALES en el VALUE -->
          <el-input
              v-model="form.RENIEC_PASSWORD"
          />
          <div class="form-hint">Contraseña para autenticación</div>
        </el-form-item>

        <el-form-item label="Usuario RUC" prop="RENIEC_RUC_USUARIO">
          <el-input
              v-model="form.RENIEC_RUC_USUARIO"
              maxlength="11"
          />
          <div class="form-hint">Usuario RUC para autenticación (11 dígitos)</div>
        </el-form-item>

        <el-form-item label="Timeout (segundos)" prop="RENIEC_TIMEOUT">
          <el-input-number
              v-model="form.RENIEC_TIMEOUT"
              :min="10"
              :max="300"
              :step="5"
          />
          <div class="form-hint">Tiempo máximo de espera para consultas (10-300 segundos)</div>
        </el-form-item>

        <el-form-item>
          <div class="button-group">
            <el-button type="primary" @click="saveConfig" :loading="saving">
              <el-icon><CircleCheck /></el-icon>
              Guardar Cambios
            </el-button>
            <el-button @click="loadDefaults">
              <el-icon><RefreshLeft /></el-icon>
              Restaurar Valores por Defecto
            </el-button>
            <el-button @click="testConnection" :loading="testing">
              <el-icon><Connection /></el-icon>
              Probar Conexión
            </el-button>
          </div>
        </el-form-item>
      </el-form>

      <el-divider />

      <div class="info-section">
        <h4>📌 ¿Dónde obtener estas credenciales?</h4>
        <ul>
          <li>Las credenciales son proporcionadas por la entidad que administra el servicio RENIEC</li>
          <li>Si las credenciales han caducado, contacta al administrador del sistema</li>
          <li>Las credenciales se guardan de forma segura en la base de datos</li>
        </ul>
      </div>
    </el-card>

    <!-- Modal de prueba de conexión -->
    <el-dialog
        v-model="testDialogVisible"
        title="Resultado de prueba de conexión"
        width="600px"
        :close-on-click-modal="false"
    >
      <div v-if="testResult" class="test-result">
        <el-alert
            :title="testResult.success ? '✅ Conexión exitosa' : '❌ Error de conexión'"
            :type="testResult.success ? 'success' : 'error'"
            :description="testResult.message"
            show-icon
        />
        <div v-if="testResult.data" class="response-preview">
          <h4>Respuesta de la API:</h4>
          <pre>{{ JSON.stringify(testResult.data, null, 2) }}</pre>
        </div>
      </div>
      <div v-else class="test-loading">
        <el-icon class="is-loading"><Loading /></el-icon>
        <span>Probando conexión...</span>
      </div>
      <template #footer>
        <el-button @click="testDialogVisible = false">Cerrar</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { ElMessage } from 'element-plus'
import { Setting, RefreshLeft, Connection, Loading, CircleCheck } from '@element-plus/icons-vue'
import axios from '@/utils/request'

// Estado
const formRef = ref(null)
const loading = ref(false)
const saving = ref(false)
const testing = ref(false)
const testDialogVisible = ref(false)
const testResult = ref(null)

// Formulario - DATOS REALES que vienen de la BD
const form = ref({
  RENIEC_REST_URL: '',
  RENIEC_DNI_USUARIO: '',
  RENIEC_PASSWORD: '',
  RENIEC_RUC_USUARIO: '',
  RENIEC_TIMEOUT: 60
})

// Reglas de validación
const rules = {
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
    { min: 6, message: 'La contraseña debe tener al menos 6 caracteres', trigger: 'blur' }
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

// Cargar configuración actual desde la BD
const loadConfig = async () => {
  loading.value = true
  try {
    const response = await axios.get('/admin/configuracion/reniec')


    if (response.data) {
      // Los datos se asignan como VALOR REAL del campo
      form.value.RENIEC_REST_URL = response.data.RENIEC_REST_URL || ''
      form.value.RENIEC_DNI_USUARIO = response.data.RENIEC_DNI_USUARIO || ''
      form.value.RENIEC_PASSWORD = response.data.RENIEC_PASSWORD || ''
      form.value.RENIEC_RUC_USUARIO = response.data.RENIEC_RUC_USUARIO || ''
      form.value.RENIEC_TIMEOUT = response.data.RENIEC_TIMEOUT || 60

    }
  } catch (error) {
    console.error('Error cargando configuración:', error)
    ElMessage.error('Error al cargar la configuración actual')
  } finally {
    loading.value = false
  }
}

// Guardar configuración
const saveConfig = async () => {
  try {
    const valid = await formRef.value?.validate()
    if (!valid) return

    saving.value = true
    const response = await axios.post('/admin/configuracion/reniec', form.value)

    if (response.data.success) {
      ElMessage.success('Configuración guardada exitosamente')
      await loadConfig()
    } else {
      ElMessage.error(response.data.message || 'Error al guardar')
    }
  } catch (error) {
    console.error('Error guardando configuración:', error)
    ElMessage.error('Error al guardar la configuración')
  } finally {
    saving.value = false
  }
}

// Cargar valores por defecto
const loadDefaults = async () => {
  try {
    const response = await axios.post('/admin/configuracion/reniec/defaults')
    if (response.data.success) {
      await loadConfig()
      ElMessage.success('Valores por defecto cargados correctamente')
    }
  } catch (error) {
    console.error('Error cargando valores por defecto:', error)
    ElMessage.error('Error al cargar valores por defecto')
  }
}

// Probar conexión
const testConnection = async () => {
  testDialogVisible.value = true
  testResult.value = null
  testing.value = true

  try {
    const dni = '41884337'
    const response = await axios.post('/admin/configuracion/reniec/test', { dni })
    testResult.value = response.data
  } catch (error) {
    testResult.value = {
      success: false,
      message: error.response?.data?.message || 'Error al probar la conexión'
    }
  } finally {
    testing.value = false
  }
}

onMounted(() => {
  loadConfig()
})
</script>

<style scoped>
.reniec-config-container {
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
  font-size: 14px;
  margin-top: 5px;
}

.form-hint {
  font-size: 12px;
  color: #909399;
  margin-top: 4px;
}

.info-section {
  background-color: #f5f7fa;
  padding: 16px 20px;
  border-radius: 8px;
  margin-top: 10px;
}

.info-section h4 {
  margin-top: 0;
  margin-bottom: 12px;
  color: #2c3e50;
}

.info-section ul {
  margin: 0;
  padding-left: 20px;
}

.info-section li {
  margin: 8px 0;
  color: #606266;
  line-height: 1.5;
}

.button-group {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
}

.button-group .el-button {
  margin-left: 0;
}

.test-result {
  margin-top: 16px;
}

.response-preview {
  margin-top: 20px;
}

.response-preview h4 {
  margin-bottom: 10px;
  font-size: 14px;
  color: #2c3e50;
}

.response-preview pre {
  background-color: #f5f7fa;
  padding: 12px;
  border-radius: 8px;
  font-size: 12px;
  overflow-x: auto;
  max-height: 300px;
  overflow-y: auto;
}

.test-loading {
  text-align: center;
  padding: 30px;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  color: #409eff;
}

.test-loading .el-icon {
  font-size: 24px;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from {
    transform: rotate(0deg);
  }
  to {
    transform: rotate(360deg);
  }
}

/* Responsive */
@media screen and (max-width: 768px) {
  .reniec-config-container {
    padding: 12px;
  }

  .config-card {
    max-width: 100%;
  }

  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .info-section ul {
    padding-left: 16px;
  }

  .button-group {
    flex-direction: column;
    width: 100%;
  }

  .button-group .el-button {
    width: 100%;
  }
}
</style>