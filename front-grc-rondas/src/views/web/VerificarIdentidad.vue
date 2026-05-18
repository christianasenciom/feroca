<template>
  <div class="verificar-identidad-container">
    <div class="page-header">
      <h3>Verificación de Identidad</h3>
      <p class="text-muted">Ingrese su número de DNI para consultar sus datos en RENIEC</p>
    </div>

    <el-card class="search-card" shadow="hover">
      <div class="search-section">
        <div class="search-input-group">
          <el-input 
            v-model="dni"
            placeholder="Ingrese DNI"
            maxlength="8"
            show-word-limit
            @keypress="isNumber($event)"
            oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
            @keyup.enter="buscarPorDNI"
            class="dni-input"
            size="large"
          />
          <el-button
            type="primary"
            :loading="cargando"
            @click="buscarPorDNI"
            :disabled="dni.length !== 8"
            class="consultar-btn"
            size="large"
          >
            {{ cargando ? 'Consultando...' : 'Consultar' }}
          </el-button>
        </div>
        <small class="hint">Ingrese los 8 dígitos de su DNI</small>
      </div>
    </el-card>

    <!-- Resultados de la verificación -->
    <div v-if="resultado" class="results-section">
      <el-card class="result-card" shadow="hover">
        <template #header>
          <div class="card-header">
            <span class="title">
              <el-icon><User /></el-icon>
              Datos de RENIEC
            </span>
            <el-tag :type="esRondero ? 'success' : 'info'" size="large">
              {{ esRondero ? 'RONDERO REGISTRADO' : 'CONSULTA GENERAL' }}
            </el-tag>
          </div>
        </template>

        <!-- Datos de RENIEC - Responsive -->
        <div class="datos-grid">
          <div class="dato-item">
            <div class="dato-label">DNI</div>
            <div class="dato-valor"><strong>{{ resultado.dni || '-' }}</strong></div>
          </div>
          <div class="dato-item">
            <div class="dato-label">Nombre Completo</div>
            <div class="dato-valor"><strong>{{ resultado.nombre_completo || '-' }}</strong></div>
          </div>
          <div class="dato-item">
            <div class="dato-label">Nombres</div>
            <div class="dato-valor">{{ resultado.nombres || '-' }}</div>
          </div>
          <div class="dato-item">
            <div class="dato-label">Apellido Paterno</div>
            <div class="dato-valor">{{ resultado.apellido_paterno || '-' }}</div>
          </div>
          <div class="dato-item">
            <div class="dato-label">Apellido Materno</div>
            <div class="dato-valor">{{ resultado.apellido_materno || '-' }}</div>
          </div>
          
          <div class="dato-item full-width">
            <div class="dato-label">Dirección</div>
            <div class="dato-valor">{{ resultado.direccion || '-' }}</div>
          </div>
          <div class="dato-item full-width">
            <div class="dato-label">Estado Civil</div>
            <div class="dato-valor">{{ resultado.estado_civil || '-' }}</div>
          </div>
        </div>

        <!-- Foto -->
        <div class="foto-section">
          <h4>Foto de RENIEC</h4>
          <div v-if="resultado.foto_base64 && resultado.foto_base64.length > 100" class="foto-wrapper">
            <el-image 
              :src="formatearFoto(resultado.foto_base64)" 
              alt="Foto de RENIEC"
              class="foto-reniec"
              fit="cover"
              :preview-src-list="[formatearFoto(resultado.foto_base64)]"
            >
              <template #error>
                <div class="foto-error">
                  <el-icon size="30" color="#909399"><Picture /></el-icon>
                  <span>Error al cargar foto</span>
                </div>
              </template>
            </el-image>
            <div class="foto-info">
              <el-text size="small" type="success">
                <el-icon><Camera /></el-icon> Foto obtenida de RENIEC
              </el-text>
            </div>
          </div>
          <div v-else class="sin-foto">
            <el-empty description="No hay foto disponible en RENIEC" :image-size="80" />
          </div>
        </div>

        <!-- Estado de rondero -->
        <div v-if="esRondero" class="rondero-section">
          <el-alert
            title="✓ Esta persona está registrada como Rondero"
            type="success"
            :closable="false"
            show-icon
          >
            <template #default>
              <div class="rondero-info">
                <p><strong>Código de Rondero:</strong> {{ ronderoInfo.codigo_rondero }}</p>
                <p><strong>Estado:</strong> 
                  <el-tag :type="ronderoInfo.estado ? 'success' : 'danger'" size="small">
                    {{ ronderoInfo.estado ? 'Activo' : 'Inactivo' }}
                  </el-tag>
                </p>
              </div>
            </template>
          </el-alert>
        </div>
        <div v-else class="no-rondero">
          <el-alert
            title="⚠ Esta persona NO está registrada como Rondero"
            type="info"
            :closable="false"
            show-icon
          />
        </div>

        <!-- Fuente de datos -->
        <div class="fuente-info">
          <small>Fuente: {{ fuente === 'reniec_rest' ? 'RENIEC - Consulta oficial' : fuente }}</small>
        </div>
      </el-card>
    </div>

    <!-- Mensaje de error -->
    <div v-if="errorMessage" class="error-section">
      <el-alert
        :title="errorMessage"
        type="error"
        :closable="true"
        show-icon
        @close="errorMessage = ''"
      />
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import { ElMessage } from 'element-plus'
import { User, Camera, Picture } from '@element-plus/icons-vue'
import axios from 'axios'

const dni = ref('')
const cargando = ref(false)
const resultado = ref(null)
const errorMessage = ref('')
const esRondero = ref(false)
const ronderoInfo = ref(null)
const fuente = ref('')

// Función para validar que solo se ingresen números
const isNumber = (evt) => {
  const charCode = evt.which ? evt.which : evt.keyCode
  if (charCode > 31 && (charCode < 48 || charCode > 57)) {
    evt.preventDefault()
    return false
  }
  return true
}

// Función para formatear la foto
const formatearFoto = (fotoData) => {
  if (!fotoData) return ''
  if (fotoData.startsWith('data:image')) return fotoData
  if (fotoData.startsWith('/9j/')) return `data:image/jpeg;base64,${fotoData}`
  if (fotoData.length > 100) return `data:image/jpeg;base64,${fotoData}`
  return fotoData
}

const buscarPorDNI = async () => {
  if (dni.value.length !== 8) {
    ElMessage.warning('Ingrese un DNI válido de 8 dígitos')
    return
  }

  cargando.value = true
  errorMessage.value = ''
  resultado.value = null
  esRondero.value = false
  ronderoInfo.value = null

  try {
    const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
    const response = await axios.get(`${apiUrl}/web/verificar/dni/${dni.value}`)
    
    if (response.data.success) {
      resultado.value = response.data.data
      esRondero.value = response.data.es_rondero || false
      ronderoInfo.value = response.data.rondero_info
      fuente.value = response.data.fuente || 'RENIEC'
      
      ElMessage.success('Datos obtenidos correctamente')
    } else {
      errorMessage.value = response.data.message || 'Error al consultar el DNI'
    }
  } catch (error) {
    console.error('Error:', error)
    if (error.response?.status === 404) {
      errorMessage.value = 'El servicio de verificación no está disponible'
    } else if (error.response?.data?.message) {
      errorMessage.value = error.response.data.message
    } else {
      errorMessage.value = 'Error al conectar con el servidor. Intente más tarde.'
    }
  } finally {
    cargando.value = false
  }
}
</script>

<style scoped>
.verificar-identidad-container {
  padding: 20px;
}

.page-header {
  margin-bottom: 20px;
}

.page-header h3 {
  font-size: 18px;
  font-weight: 600;
  color: #333;
  margin: 0 0 5px 0;
}

.text-muted {
  color: #909399;
  font-size: 14px;
  margin: 0;
}

.search-card {
  margin-bottom: 20px;
  border-radius: 12px;
}

.search-section {
  width: 100%;
}

.search-input-group {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.dni-input {
  flex: 1;
  min-width: 180px;
}

.consultar-btn {
  min-width: 100px;
}

.hint {
  display: block;
  margin-top: 8px;
  font-size: 12px;
  color: #999;
}

.results-section {
  margin-top: 20px;
}

.result-card {
  border-radius: 12px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.card-header .title {
  font-weight: bold;
  font-size: 16px;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Grid responsivo para los datos */
.datos-grid {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 12px;
  margin-bottom: 20px;
}

.dato-item {
  background: #f5f7fa;
  padding: 10px;
  border-radius: 8px;
}

.dato-item.full-width {
  grid-column: span 2;
}

.dato-label {
  font-size: 11px;
  color: #909399;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.dato-valor {
  font-size: 14px;
  color: #333;
  word-break: break-word;
}

.foto-section {
  text-align: center;
  margin-top: 20px;
  margin-bottom: 20px;
  padding: 15px;
  background: #f5f7fa;
  border-radius: 12px;
}

.foto-section h4 {
  margin-bottom: 15px;
  color: #333;
  font-size: 16px;
}

.foto-wrapper {
  display: flex;
  flex-direction: column;
  align-items: center;
}

.foto-reniec {
  width: 160px;
  height: 200px;
  border-radius: 8px;
  border: 2px solid #ddd;
  object-fit: cover;
  margin-bottom: 8px;
}

.foto-info {
  margin-top: 8px;
}

.foto-error {
  width: 160px;
  height: 200px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
  border-radius: 8px;
  border: 2px solid #ddd;
  margin: 0 auto;
}

.sin-foto {
  padding: 20px;
}

.rondero-section, .no-rondero {
  margin-bottom: 20px;
}

.rondero-info {
  margin-top: 8px;
}

.rondero-info p {
  margin: 5px 0;
}

.fuente-info {
  text-align: right;
  margin-top: 15px;
  padding-top: 10px;
  border-top: 1px solid #eee;
  color: #999;
  font-size: 11px;
}

.error-section {
  margin-top: 20px;
}

/* ============================================
   RESPONSIVE PARA MÓVILES
   ============================================ */

/* Tablets y móviles grandes (menos de 768px) */
@media screen and (max-width: 768px) {
  .verificar-identidad-container {
    padding: 15px;
  }
  
  .page-header h3 {
    font-size: 16px;
  }
  
  .text-muted {
    font-size: 12px;
  }
  
  .datos-grid {
    grid-template-columns: 1fr;
    gap: 8px;
  }
  
  .dato-item.full-width {
    grid-column: span 1;
  }
  
  .dato-item {
    padding: 8px;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .card-header .title {
    font-size: 14px;
  }
}

/* Móviles pequeños (menos de 480px) */
@media screen and (max-width: 480px) {
  .verificar-identidad-container {
    padding: 10px;
  }
  
  .search-input-group {
    flex-direction: column;
    gap: 8px;
  }
  
  .dni-input {
    width: 100%;
    min-width: auto;
  }
  
  .consultar-btn {
    width: 100%;
    min-width: auto;
  }
  
  .dato-label {
    font-size: 10px;
  }
  
  .dato-valor {
    font-size: 13px;
  }
  
  .foto-reniec {
    width: 120px;
    height: 150px;
  }
  
  .foto-error {
    width: 120px;
    height: 150px;
  }
  
  .result-card :deep(.el-card__header) {
    padding: 12px;
  }
  
  .result-card :deep(.el-card__body) {
    padding: 12px;
  }
  
  .rondero-info p {
    font-size: 13px;
  }
}

/* Pantallas muy pequeñas (menos de 360px) */
@media screen and (max-width: 360px) {
  .verificar-identidad-container {
    padding: 8px;
  }
  
  .page-header h3 {
    font-size: 14px;
  }
  
  .dato-item {
    padding: 6px;
  }
  
  .dato-label {
    font-size: 9px;
  }
  
  .dato-valor {
    font-size: 12px;
  }
  
  .foto-reniec {
    width: 100px;
    height: 125px;
  }
  
  .foto-error {
    width: 100px;
    height: 125px;
  }
}
</style>