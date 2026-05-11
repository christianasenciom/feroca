<template>
  <CustomLoading :loading="loading" :loadingText="loadingText">
    <div class="consulta-rq-container">
      <el-card class="search-card">
        <template #header>
          <div class="card-header">
            <span class="title">
              <el-icon><Search /></el-icon>
              Consulta de Requisitoriados
            </span>
            <span class="subtitle">Sistema de Recompensas - Ministerio del Interior</span>
          </div>
        </template>

        <el-form @submit.prevent="consultarRQ">
          <el-row :gutter="12" align="middle">
            <el-col :xs="24" :sm="6" :md="5">
              <el-form-item label="Nombres">
                <el-input 
                  v-model="nombres" 
                  placeholder="Nombres" 
                  clearable
                  @keyup.enter="consultarRQ"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="6" :md="5">
              <el-form-item label="Apellido Paterno">
                <el-input 
                  v-model="apellido_paterno" 
                  placeholder="Apellido paterno" 
                  clearable
                  @keyup.enter="consultarRQ"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="6" :md="5">
              <el-form-item label="Apellido Materno">
                <el-input 
                  v-model="apellido_materno" 
                  placeholder="Apellido materno" 
                  clearable
                  @keyup.enter="consultarRQ"
                />
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="6" :md="9">
              <div class="button-group-wrapper">
                <div class="button-group">
                  <el-button 
                    type="primary" 
                    @click="consultarRQ" 
                    :loading="loading"
                    :icon="Search"
                  >
                    Consultar
                  </el-button>
                  <el-button @click="limpiarCampos" :icon="Delete">Limpiar</el-button>
                </div>
              </div>
            </el-col>
          </el-row>
        </el-form>

        <el-divider />
        
        <el-alert
          title="Información"
          type="info"
          :closable="false"
          show-icon
        >
          <template #default>
            <span>La búsqueda no distingue entre mayúsculas/minúsculas ni tildes. Ingrese los nombres y apellidos de la persona a consultar.</span>
          </template>
        </el-alert>
      </el-card>

      <!-- Mensaje de error -->
      <el-alert
        v-if="errorMessage"
        :title="errorMessage"
        type="error"
        show-icon
        :closable="true"
        @close="errorMessage = ''"
        style="margin-top: 20px"
      />

      <!-- RESULTADO POSITIVO - TIENE REQUISITORIA -->
      <el-card v-if="mostrarResultados && tieneRequisitoria" class="results-card results-positive" style="margin-top: 20px">
        <template #header>
          <div class="results-header positive">
            <div class="title-icon">
              <el-icon size="24" color="#f56c6c"><WarningFilled /></el-icon>
              <span class="title">¡ALERTA! PERSONA REQUISITORIADA</span>
            </div>
            <el-tag type="danger" size="large" effect="dark">REQUISITORIADO</el-tag>
          </div>
        </template>

        <el-row :gutter="20">
          <el-col :xs="24" :sm="8" :md="6">
            <div class="foto-container">
              <el-image
                v-if="foto_rq"
                :src="`data:image/jpeg;base64,${foto_rq}`"
                class="foto-requisitoriado"
                fit="cover"
                :preview-src-list="[`data:image/jpeg;base64,${foto_rq}`]"
              >
                <template #error>
                  <div class="foto-placeholder">
                    <el-icon size="40"><Picture /></el-icon>
                    <span>Sin foto</span>
                  </div>
                </template>
              </el-image>
              <div v-else class="foto-placeholder">
                <el-icon size="40"><Picture /></el-icon>
                <span>Sin foto disponible</span>
              </div>
            </div>
          </el-col>

          <el-col :xs="24" :sm="16" :md="18">
            <el-descriptions :column="1" border>
              <el-descriptions-item label="Nombre Completo">
                <strong class="nombre-alerta">{{ nombreCompletoMostrar }}</strong>
              </el-descriptions-item>
              <el-descriptions-item label="Delito(s)">
                <el-tag 
                  v-for="(delito, index) in delitos_rq" 
                  :key="index" 
                  type="danger" 
                  size="large"
                  style="margin-right: 8px; margin-bottom: 4px;"
                >
                  {{ delito }}
                </el-tag>
                <span v-if="delitos_rq.length === 0">No especificado</span>
              </el-descriptions-item>
              <el-descriptions-item label="Recompensa">
                <el-tag type="success" size="large">{{ recompensa_rq || 'No especificada' }}</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Estado">
                <el-tag type="danger" size="large" effect="dark">REQUISITORIADO</el-tag>
              </el-descriptions-item>
              <el-descriptions-item label="Lugar de Requisitoria" v-if="lugar_rq">
                {{ lugar_rq }}
              </el-descriptions-item>
            </el-descriptions>
          </el-col>
        </el-row>
      </el-card>

      <!-- RESULTADO NEGATIVO - NO TIENE REQUISITORIA -->
      <el-card v-if="consultaRealizada && !tieneRequisitoria && !mostrarResultados" class="results-card results-negative" style="margin-top: 20px">
        <div class="negative-result">
          <el-icon size="48" color="#67c23a"><CircleCheck /></el-icon>
          <h3>No se encontraron requisitorias</h3>
          <p>La persona consultada no presenta ningún requerimiento judicial vigente.</p>
        </div>
      </el-card>
    </div>
  </CustomLoading>
</template>

<script setup>
import { ref } from 'vue'
import UtilsResource from '@/api/publico/utils';
import { ElNotification } from "element-plus";
import { Search, Delete, WarningFilled, Picture, CircleCheck } from "@element-plus/icons-vue";
import CustomLoading from "@/components/loading/CustomLoading.vue";

const utilsResource = new UtilsResource();

// Campos del formulario
const apellido_paterno = ref('');
const apellido_materno = ref('');
const nombres = ref('');
const nombreCompletoMostrar = ref('');

// Estados
const loading = ref(false)
const loadingText = ref('Consultando el sistema de requisitoriados...')
const consultaRealizada = ref(false)
const errorMessage = ref('')
const mostrarResultados = ref(false)
const tieneRequisitoria = ref(false)

// Datos de requisitoriado
const foto_rq = ref(null);
const lugar_rq = ref(null);
const recompensa_rq = ref(null);
const delitos_rq = ref([]);

/**
 * Normaliza un texto eliminando tildes y convirtiendo a minúsculas
 */
const normalizarTexto = (texto) => {
  if (!texto) return '';
  return texto
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();
};

/**
 * Construye el nombre completo para la búsqueda
 */
const construirNombreBusqueda = () => {
  const partes = [
    normalizarTexto(nombres.value),
    normalizarTexto(apellido_paterno.value),
    normalizarTexto(apellido_materno.value)
  ].filter(parte => parte !== '');
  
  return partes.join(' ');
};

/**
 * Capitaliza un texto (primera letra de cada palabra en mayúscula)
 */
const capitalizarTexto = (texto) => {
  if (!texto) return '';
  return texto
    .toLowerCase()
    .split(' ')
    .map(palabra => palabra.charAt(0).toUpperCase() + palabra.slice(1))
    .join(' ');
};

/**
 * Limpiar campos del formulario
 */
const limpiarCampos = () => {
  nombres.value = '';
  apellido_paterno.value = '';
  apellido_materno.value = '';
  consultaRealizada.value = false;
  mostrarResultados.value = false;
  tieneRequisitoria.value = false;
  errorMessage.value = '';
  foto_rq.value = null;
  delitos_rq.value = [];
  recompensa_rq.value = null;
  lugar_rq.value = null;
};

/**
 * Consultar requisitoriado
 */
const consultarRQ = async () => {
  if (!nombres.value && !apellido_paterno.value && !apellido_materno.value) {
    ElNotification({ 
      message: 'Ingrese al menos un nombre o apellido para realizar la consulta', 
      type: 'warning' 
    });
    return;
  }

  loading.value = true
  loadingText.value = 'Consultando el sistema de requisitoriados...'
  consultaRealizada.value = false
  mostrarResultados.value = false
  tieneRequisitoria.value = false
  errorMessage.value = ''
  
  const nombreBusqueda = construirNombreBusqueda();
  nombreCompletoMostrar.value = [nombres.value, apellido_paterno.value, apellido_materno.value]
    .filter(p => p)
    .join(' ');
  
  console.log('🔍 Buscando:', nombreBusqueda);
  console.log('🔍 URL:', import.meta.env.VITE_API_URL);
  
  try {
    const response = await utilsResource.consultarRQ({ nombreCompleto: nombreBusqueda })
    console.log('📡 Respuesta COMPLETA:', JSON.stringify(response, null, 2));
    console.log('📡 Tipo de respuesta:', typeof response);
    console.log('📡 ¿Tiene content?', response?.content);
    
    // 🔥 Verificar diferentes estructuras de respuesta
    let dataEncontrada = null;
    
    // Estructura 1: response.content (paginación)
    if (response && response.content && Array.isArray(response.content) && response.content.length > 0) {
      dataEncontrada = response.content[0];
      console.log('✅ Datos encontrados en content');
    }
    // Estructura 2: response.data (directo)
    else if (response && response.data && response.data.content && response.data.content.length > 0) {
      dataEncontrada = response.data.content[0];
      console.log('✅ Datos encontrados en data.content');
    }
    // Estructura 3: response directo es un array
    else if (response && Array.isArray(response) && response.length > 0) {
      dataEncontrada = response[0];
      console.log('✅ Datos encontrados en array directo');
    }
    // Estructura 4: response tiene data directo
    else if (response && response.data && !response.data.content) {
      dataEncontrada = response.data;
      console.log('✅ Datos encontrados en data directo');
    }
    
    if (dataEncontrada) {
      console.log('✅ DATOS ENCONTRADOS:', dataEncontrada);
      
      foto_rq.value = dataEncontrada.foto || null
      recompensa_rq.value = dataEncontrada.montoRecompensaSpace 
        ? `S/${dataEncontrada.montoRecompensaSpace}` 
        : (dataEncontrada.montoRecompensa ? `S/${dataEncontrada.montoRecompensa}` : null)
      delitos_rq.value = dataEncontrada.delito ? [dataEncontrada.delito] : (dataEncontrada.delitos || [])
      lugar_rq.value = dataEncontrada.ubicacion || (dataEncontrada.departamento && dataEncontrada.provincia 
        ? `${dataEncontrada.departamento} - ${dataEncontrada.provincia}` 
        : null)
      
      if (dataEncontrada.nombreCompleto) {
        nombreCompletoMostrar.value = capitalizarTexto(dataEncontrada.nombreCompleto);
      }
      
      tieneRequisitoria.value = true
      mostrarResultados.value = true
      consultaRealizada.value = true
      ElNotification({ 
        title: 'ALERTA', 
        message: 'La persona consultada TIENE REQUISITORIA vigente', 
        type: 'error',
        duration: 0
      })
    } else {
      console.log('❌ No se encontraron datos en ninguna estructura')
      tieneRequisitoria.value = false
      mostrarResultados.value = false
      consultaRealizada.value = true
      ElNotification({ 
        message: 'La persona consultada NO tiene requisitoria vigente', 
        type: 'success' 
      })
    }
  } catch (error) {
    console.error('❌ Error en consulta:', error)
    errorMessage.value = error.response?.data?.message || 'Error al consultar el servicio de requisitoriados. Intente más tarde.'
    ElNotification({ message: errorMessage.value, type: 'error' })
    consultaRealizada.value = true
    tieneRequisitoria.value = false
    mostrarResultados.value = false
  } finally {
    loading.value = false
  }
};
</script>

<style scoped>
.consulta-rq-container {
  padding: 20px;
}

.search-card {
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
  font-size: 18px;
  display: flex;
  align-items: center;
  gap: 8px;
}

.card-header .subtitle {
  font-size: 12px;
  color: #909399;
}

.button-group-wrapper {
  display: flex;
  align-items: flex-end;
  height: 100%;
}

.button-group {
  display: flex;
  gap: 10px;
  margin-bottom: 2px;
}

/* Alinear botones verticalmente con los inputs */
@media (min-width: 768px) {
  .button-group-wrapper {
    padding-bottom: 2px;
  }
  
  .button-group .el-button {
    height: 32px;
  }
}

.results-card {
  border-radius: 12px;
}

.results-positive {
  border-left: 4px solid #f56c6c;
}

.results-negative {
  border-left: 4px solid #67c23a;
}

.results-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.results-header.positive {
  background-color: #fef0f0;
  padding: 10px;
  border-radius: 8px;
}

.title-icon {
  display: flex;
  align-items: center;
  gap: 10px;
}

.results-header .title {
  font-weight: bold;
  font-size: 18px;
  color: #f56c6c;
}

.nombre-alerta {
  color: #f56c6c;
  font-size: 16px;
}

.foto-container {
  display: flex;
  justify-content: center;
  align-items: center;
}

.foto-requisitoriado {
  width: 100%;
  max-width: 200px;
  height: auto;
  border-radius: 8px;
  border: 2px solid #ddd;
}

.foto-placeholder {
  width: 100%;
  max-width: 200px;
  height: 250px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  background-color: #f5f5f5;
  border-radius: 8px;
  border: 2px solid #ddd;
  color: #999;
  gap: 10px;
}

.negative-result {
  text-align: center;
  padding: 40px 20px;
}

.negative-result h3 {
  margin: 15px 0 10px;
  color: #67c23a;
}

.negative-result p {
  color: #666;
}

/* Responsive */
@media (max-width: 768px) {
  .consulta-rq-container {
    padding: 10px;
  }
  
  .card-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .results-header {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .button-group-wrapper {
    margin-top: 0;
  }
  
  .button-group {
    margin-top: 5px;
  }
  
  .foto-requisitoriado {
    max-width: 150px;
  }
  
  .foto-placeholder {
    max-width: 150px;
    height: 200px;
  }
}
</style>