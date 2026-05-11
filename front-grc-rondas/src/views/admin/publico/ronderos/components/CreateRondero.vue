<template>
  <CustomLoading :loading="loading" :loadingText="loadingText">
    <el-form ref="createRonderoForm" :model="persona" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="12">
          <!-- Campo DNI con consulta mejorada -->
          <el-form-item label="DNI" prop="docIdentidad">
            <div style="display: flex; gap: 8px;">
              <el-input 
                v-model="persona.docIdentidad"
                placeholder="Ingrese DNI"
                maxlength="8"
                show-word-limit
                @keypress="isNumber($event)"
                oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                :disabled="consultandoDNI"
                @keyup.enter="buscarDatosPersonaDNI"
                style="flex: 1;"
              />
              <el-button
                :icon="Search"
                :loading="btnBuscarDNILoading"
                @click="buscarDatosPersonaDNI"
                :disabled="!persona.docIdentidad || persona.docIdentidad.length !== 8"
              >
                {{ btnBuscarDNILoading ? 'Consultando...' : 'Consultar' }}
              </el-button>
            </div>
            
            <div v-if="dniNoEncontrado" style="margin-top: 10px;">
              <el-alert 
                title="DNI no encontrado. Debe ingresar los datos manualmente." 
                type="warning" 
                :closable="true"
                show-icon
                @close="dniNoEncontrado = false"
              />
            </div>

            <div v-if="consultaExitosa" style="margin-top: 10px;">
              <el-alert 
                :title="mensajeExito" 
                :type="tipoAlerta"
                :closable="true"
                show-icon
                @close="consultaExitosa = false"
              />
            </div>
          </el-form-item>

          <el-form-item label="Nombres" prop="nombres">
            <el-input 
              v-model="persona.nombres" 
              placeholder="Nombres" 
              :disabled="datosObtenidosAutomaticamente && !modoManual"
              :readonly="datosObtenidosAutomaticamente && !modoManual"
            />
          </el-form-item>
          
          <el-form-item label="Ap. Paterno" prop="apellido_paterno">
            <el-input 
              v-model="persona.apellido_paterno" 
              placeholder="Ap. Paterno" 
              :disabled="datosObtenidosAutomaticamente && !modoManual"
              :readonly="datosObtenidosAutomaticamente && !modoManual"
            />
          </el-form-item>
          
          <el-form-item label="Ap. Materno" prop="apellido_materno">
            <el-input 
              v-model="persona.apellido_materno" 
              placeholder="Ap. Materno" 
              :disabled="datosObtenidosAutomaticamente && !modoManual"
              :readonly="datosObtenidosAutomaticamente && !modoManual"
            />
          </el-form-item>

          <el-form-item label="Fecha Nacimiento" prop="fecha_nacimiento">
            <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="persona.fecha_nacimiento"
              placeholder="Fecha Nacimiento"
            />
          </el-form-item>

          <el-row :gutter="12">
            <el-col :xs="24" :sm="12" :md="12">
              <el-form-item label="Género" prop="genero">
                <el-select 
                  v-model="persona.genero" 
                  placeholder="Género" 
                  style="width: 100%"
                >
                  <el-option label="MASCULINO" value="MASCULINO" />
                  <el-option label="FEMENINO" value="FEMENINO" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <el-form-item label="Celular" prop="celular">
                <el-input 
                  v-model="persona.celular" 
                  placeholder="Celular" 
                />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Dirección" prop="direccion">
            <el-input 
              v-model="persona.direccion" 
              placeholder="Dirección" 
            />
          </el-form-item>

          <el-form-item label="Email" prop="email">
            <el-input 
              v-model.trim="persona.email" 
              placeholder="mail@example.com" 
            />
          </el-form-item>

          <el-form-item label="Fecha Inicio" prop="fecha_inicio">
            <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="persona.fecha_inicio"
              placeholder="Fecha Inicio"
            />
          </el-form-item>
          
          <el-form-item label="Fecha Cese" prop="fecha_cese">
            <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="persona.fecha_cese"
              placeholder="Fecha cese"
            />
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="Región" prop="region_id">
            <el-select v-model="persona.region_id" placeholder="Región" style="width: 100%" searchable @change="obtenerProvincias">
              <el-option label="- Seleccione Región -" value="0"/>
              <el-option
                v-for="item in optionsRegiones"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item label="Provincia" prop="provincia_id">
            <el-select v-model="persona.provincia_id" placeholder="Provincia" style="width: 100%" searchable @change="obtenerDistritos">
              <el-option
                v-for="item in optionsProvincias"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item label="Distrito" prop="distrito_id">
            <el-select v-model="persona.distrito_id" placeholder="Distrito" style="width: 100%" searchable @change="onDistritoChange">
              <el-option
                v-for="item in optionsDistritos"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item label="Sector" prop="sector_zona_id">
            <el-select v-model="persona.sector_zona_id" placeholder="Sector" style="width: 100%" searchable @change="obtenerBases">
              <el-option
                v-for="item in optionsSectores"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
          
          <el-form-item label="Base" prop="base_id">
            <el-select v-model="persona.base_id" placeholder="Base" style="width: 100%" searchable>
              <el-option
                v-for="item in optionsBases"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>
          
          <!-- Código Rondero - Solo se muestra después de crear -->
          <el-form-item label="Código Rondero" v-if="codigoGenerado">
            <el-input 
              :value="codigoGenerado" 
              placeholder="Código autogenerado"
              disabled
            />
            <small class="text-muted">Código único asignado automáticamente</small>
          </el-form-item>

          <!-- Campo para mostrar foto de RENIEC -->
          <el-form-item label="Foto de RENIEC" prop="foto">
            <div v-if="persona.foto && persona.foto.length > 100">
              <el-image 
                :src="formatearFoto(persona.foto)" 
                alt="Foto del rondero desde RENIEC"
                style="width: 160px; height: 200px; border: 1px solid #ddd; border-radius: 4px; object-fit: cover;"
                fit="cover"
                :preview-src-list="[formatearFoto(persona.foto)]"
              >
                <template #error>
                  <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                    <el-icon size="30" color="#909399"><Picture /></el-icon>
                    <span style="font-size: 12px; color: #909399;">Error al cargar foto</span>
                  </div>
                </template>
              </el-image>
              <div style="margin-top: 8px;">
                <el-text size="small" type="success">
                  <el-icon><Camera /></el-icon> Foto obtenida de RENIEC
                </el-text>
              </div>
            </div>
            <div v-else-if="datosObtenidosAutomaticamente">
              <el-empty description="No hay foto disponible en RENIEC para este DNI" :image-size="100" />
              <el-text size="small" type="warning">
                <el-icon><Warning /></el-icon> El servicio de RENIEC no tiene foto asociada a este DNI
              </el-text>
            </div>
            <div v-else>
              <el-empty description="Consulte un DNI para ver su foto" :image-size="100" />
              <el-text size="small" type="info">
                <el-icon><InfoFilled /></el-icon> Ingrese un DNI y presione "Consultar"
              </el-text>
            </div>
          </el-form-item>

          <div v-if="datosObtenidosAutomaticamente && !modoManual" style="margin-top: 20px; padding: 15px; background-color: #f0f9ff; border-radius: 4px; border-left: 4px solid #409eff;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <el-icon color="#409eff"><InfoFilled /></el-icon>
              <span style="color: #409eff; font-weight: 500;">Datos obtenidos del DNI</span>
            </div>
            <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">
              Los nombres y apellidos fueron obtenidos del servicio de consulta DNI.
            </p>
            <div style="margin-top: 10px;">
              <el-button 
                type="info" 
                size="small" 
                @click="activarEdicionManual"
              >
                <el-icon><EditPen /></el-icon>
                Editar nombres/apellidos manualmente
              </el-button>
            </div>
          </div>

          <div v-if="modoManual" style="margin-top: 20px; padding: 15px; background-color: #f0f9ff; border-radius: 4px; border-left: 4px solid #409eff;">
            <div style="display: flex; align-items: center; gap: 10px;">
              <el-icon color="#409eff"><InfoFilled /></el-icon>
              <span style="color: #409eff; font-weight: 500;">Modo edición manual activado</span>
            </div>
            <p style="margin: 8px 0 0 0; color: #666; font-size: 13px;">
              Puede editar los nombres y apellidos manualmente.
            </p>
          </div>
        </el-col>
      </el-row>
    </el-form>
    
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-button v-if="!codigoGenerado" type="primary" @click="submitForm(createRonderoForm)">GUARDAR</el-button>
      <el-button v-if="codigoGenerado" type="success" @click="closeAfterSave">ACEPTAR</el-button>
      <el-button @click="resetFormCreateEdit">RESETEAR</el-button>
      <el-button @click="closeDialog">CANCELAR</el-button>
    </el-row>
  </CustomLoading>
</template>

<script setup>
import Resource from '@/api/resource'
import { Search, EditPen, InfoFilled, Camera, Warning, Picture } from '@element-plus/icons-vue'
import RonderoRequest from '@/api/publico/rondero';
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from '@/api/publico/sector';
import BaseResource from '@/api/publico/base';
import {ElMessage, ElNotification} from 'element-plus'

const ronderoRequest = new RonderoRequest();
const regionResource = new RegionResource();
const provinciaResource = new ProvinciaResource();
const distritoResource = new DistritoResource();
const sectorResource = new SectorResource();
const baseResource = new BaseResource();
const consultaDNIResource = new Resource('getdatadni')

import {onMounted, reactive, ref, watch} from 'vue'
import {isNumber} from "@/utils/utils.js";
import CustomLoading from "@/components/loading/CustomLoading.vue";

const consultandoDNI = ref(false);
const emit = defineEmits(['close'])
const loading = ref(false)
const loadingText = ref('Cargando datos...')
const createRonderoForm = ref()
const btnBuscarDNILoading = ref(false)
const modoManual = ref(false)
const dniNoEncontrado = ref(false)
const consultaExitosa = ref(false)
const mensajeExito = ref('')
const tipoAlerta = ref('success')
const datosObtenidosAutomaticamente = ref(false)
const codigoGenerado = ref('')

const optionsRegiones = ref([])
const optionsProvincias = ref([])
const optionsDistritos = ref([])
const optionsSectores = ref([])
const optionsBases = ref([])

const persona = reactive({
  id: undefined,
  docIdentidad: null,
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  fecha_nacimiento: '',
  genero: '',
  celular: '',
  direccion: '',
  email: '',
  foto: '',
  fecha_inicio: '',
  fecha_cese: '',
  region_id: '',
  provincia_id: '',
  distrito_id: '',
  sector_zona_id: '',
  base_id: '',
})

const formatearFoto = (fotoData) => {
  if (!fotoData) return '';
  if (fotoData.startsWith('data:image')) return fotoData;
  if (fotoData.startsWith('/9j/')) return `data:image/jpeg;base64,${fotoData}`;
  if (fotoData.length > 100) return `data:image/jpeg;base64,${fotoData}`;
  return fotoData;
};

watch(() => persona.docIdentidad, (newVal, oldVal) => {
  if (newVal && newVal.length === 8 && newVal !== oldVal) {
    resetearEstadoConsulta();
  }
});

const resetearEstadoConsulta = () => {
  modoManual.value = false;
  dniNoEncontrado.value = false;
  consultaExitosa.value = false;
  datosObtenidosAutomaticamente.value = false;
  persona.foto = '';
};

const fetchRegiones = async () => {
  await regionResource.list()
    .then(response => {
      const { data } = response
      optionsRegiones.value = data;
    })
    .catch(error => {
      console.error('Error fetching data:', error);
      ElMessage.error('Error al obtener datos');
    });
};

onMounted(fetchRegiones)

const obtenerProvincias = async () => {
  persona.provincia_id = '';
  persona.distrito_id = '';
  persona.sector_zona_id = '';
  persona.base_id = '';
  optionsDistritos.value = [];
  optionsSectores.value = [];
  optionsBases.value = [];
  await provinciaResource.getProvincias(persona.region_id)
    .then(response => {
      optionsProvincias.value = response;
    })
    .catch(error => {
      console.error('Error fetching data:', error);
      ElMessage.error('Error al obtener datos');
    });
};

const obtenerDistritos = async () => {
  persona.distrito_id = '';
  persona.sector_zona_id = '';
  persona.base_id = '';
  optionsSectores.value = [];
  optionsBases.value = [];
  
  await distritoResource.getDistritos(persona.provincia_id)
    .then(response => {
      optionsDistritos.value = response;
    })
    .catch(error => {
      console.error('Error fetching data:', error);
      ElMessage.error('Error al obtener datos');
    });
};

const obtenerSectores = async () => {
  persona.sector_zona_id = '';
  persona.base_id = '';
  optionsBases.value = []; // Limpiar bases al cambiar sector
  await sectorResource.getSectores(persona.distrito_id)
    .then(response => {
      optionsSectores.value = response;
    })
    .catch(error => {
      console.error('Error fetching data:', error);
      ElMessage.error('Error al obtener datos');
    });
};

const cargarBasesPorDistrito = async () => {
  persona.base_id = '';
  optionsBases.value = [];
  
  if (persona.distrito_id && persona.distrito_id !== '' && persona.distrito_id !== 0) {
    console.log('Cargando bases por distrito (sin sector):', persona.distrito_id);
    try {
      const response = await baseResource.getBasesPorDistrito(persona.distrito_id);
      console.log('Bases encontradas:', response);
      optionsBases.value = response.data || response;
    } catch (error) {
      console.error('Error cargando bases por distrito:', error);
      optionsBases.value = [];
    }
  }
};

const onDistritoChange = async () => {
  persona.sector_zona_id = '';
  persona.base_id = '';
  optionsSectores.value = [];
  optionsBases.value = [];
  
  if (persona.distrito_id && persona.distrito_id !== '' && persona.distrito_id !== 0) {
    // Cargar sectores del distrito
    await sectorResource.getSectores(persona.distrito_id)
      .then(response => {
        optionsSectores.value = response;
      })
      .catch(error => {
        console.error('Error fetching sectores:', error);
      });
    
    // Cargar bases directamente por distrito
    await cargarBasesPorDistrito();
  }
};

const obtenerBases = async () => {
  persona.base_id = '';
  optionsBases.value = [];
  
  // Si hay un sector seleccionado (válido, no 0)
  if (persona.sector_zona_id && persona.sector_zona_id !== '' && persona.sector_zona_id !== 0) {
    console.log('Cargando bases por sector:', persona.sector_zona_id);
    try {
      const response = await baseResource.getBases(persona.sector_zona_id);
      console.log('Bases por sector:', response);
      optionsBases.value = response.data || response;
    } catch (error) {
      console.error('Error cargando bases por sector:', error);
      optionsBases.value = [];
    }
  } 
  // Si hay distrito pero NO hay sector (o sector es 0)
  else if (persona.distrito_id && persona.distrito_id !== '' && persona.distrito_id !== 0) {
    console.log('Cargando bases por distrito:', persona.distrito_id);
    try {
      const response = await baseResource.getBasesPorDistrito(persona.distrito_id);
      console.log('Bases por distrito:', response);
      optionsBases.value = response.data || response;
    } catch (error) {
      console.error('Error cargando bases por distrito:', error);
      optionsBases.value = [];
    }
  } else {
    console.log('No hay distrito ni sector seleccionado');
    optionsBases.value = [];
  }
};

const activarEdicionManual = () => {
  modoManual.value = true;
  ElMessage.info('Puede editar los nombres y apellidos manualmente.');
};

const rules = reactive({
  docIdentidad: [
    { required: true, message: 'El DNI es requerido', trigger: 'blur' },
    { min: 8, max: 8, message: 'El DNI debe tener 8 dígitos', trigger: 'blur' }
  ],
  nombres: [
    { 
      required: true, 
      message: 'Los nombres son requeridos', 
      trigger: 'blur',
      validator: (rule, value, callback) => {
        if (!value || value.trim() === '') {
          callback(new Error('Los nombres son requeridos'));
        } else {
          callback();
        }
      }
    }
  ],
  apellido_paterno: [
    { required: true, message: 'El apellido paterno es requerido', trigger: 'blur' }
  ],
  apellido_materno: [
    { required: true, message: 'El apellido materno es requerido', trigger: 'blur' }
  ],
  fecha_inicio: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  region_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  provincia_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  distrito_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  // sector_zona_id NO es requerido - línea eliminada
  base_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
});

const submitForm = async (formEl) => {
  if (!formEl) return;
  loading.value = true;
  
  await formEl.validate(async (valid) => {
    if (valid) {
      try {
        const datosEnviar = {
          documento_tipo: 'DNI',
          docIdentidad: persona.docIdentidad,
          nombres: persona.nombres,
          apellido_paterno: persona.apellido_paterno,
          apellido_materno: persona.apellido_materno,
          fecha_nacimiento: persona.fecha_nacimiento || null,
          genero: persona.genero || null,
          celular: persona.celular || null,
          direccion: persona.direccion || null,
          email: persona.email || null,
          tipo: 'Natural',
          foto: persona.foto && persona.foto.startsWith('data:') 
            ? persona.foto.split(',')[1] 
            : persona.foto || null,
          fecha_inicio: persona.fecha_inicio,
          fecha_cese: persona.fecha_cese || null,
          region_id: Number(persona.region_id),
          provincia_id: Number(persona.provincia_id),
          distrito_id: Number(persona.distrito_id),
          sector_zona_id: persona.sector_zona_id ? Number(persona.sector_zona_id) : null,
          base_id: Number(persona.base_id),
          estado: true
        };

        const response = await ronderoRequest.store(datosEnviar);
        
        if (response.data && response.data.codigo_rondero) {
          codigoGenerado.value = response.data.codigo_rondero;

          if (response.data.usuario) {
            ElNotification({
              type: 'success',
              title: 'Rondero y Usuario creados',
              message: `Usuario: ${response.data.usuario}\nContraseña temporal: ${response.data.password_temporal}\nEl usuario deberá cambiar su contraseña en el primer inicio.`,
              duration: 8000
            });
          } else {
            ElNotification({
              type: 'success',
              title: 'Rondero creado',
              message: `Código de rondero: ${response.data.codigo_rondero}`,
              duration: 5000
            });
          }

          ElNotification({
            type: 'success',
            title: 'Rondero creado',
            message: `Código de rondero: ${response.data.codigo_rondero}`,
            duration: 5000
          });
        } else {
          ElNotification({
            type: 'success',
            title: 'Rondero creado',
            message: 'El rondero ha sido creado exitosamente',
            duration: 3000
          });
        }
        
      } catch (error) {
        console.error('❌ ERROR:', error);
        
        if (error.response) {
          let errorMessage = 'Error al crear el rondero';
          
          if (error.response.data && error.response.data.message) {
            errorMessage = error.response.data.message;
          }
          
          ElMessage.error(`Error ${error.response.status}: ${errorMessage}`);
          
          if (error.response.status === 422 && error.response.data.errors) {
            const errors = error.response.data.errors;
            for (const field in errors) {
              ElMessage.warning(`${field}: ${errors[field].join(', ')}`);
            }
          }
        } else if (error.request) {
          ElMessage.error('No se recibió respuesta del servidor');
        } else {
          ElMessage.error(`Error: ${error.message}`);
        }
      } finally {
        loading.value = false;
      }
    } else {
      loading.value = false;
      ElMessage.warning('Por favor, complete todos los campos requeridos');
    }
  });
};

const resetFormCreateEdit = () => {
  Object.assign(persona, {
    docIdentidad: null,
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    fecha_nacimiento: '',
    genero: '',
    celular: '',
    direccion: '',
    email: '',
    foto: '',
    fecha_inicio: '',
    fecha_cese: '',
    region_id: '',
    provincia_id: '',
    distrito_id: '',
    sector_zona_id: '',
    base_id: '',
  });
  modoManual.value = false;
  dniNoEncontrado.value = false;
  consultaExitosa.value = false;
  datosObtenidosAutomaticamente.value = false;
  codigoGenerado.value = '';
  createRonderoForm.value?.clearValidate();
}

const closeAfterSave = () => {
  codigoGenerado.value = '';
  close('success');
};

const closeDialog = () => {
  codigoGenerado.value = '';
  resetFormCreateEdit();
  close('canceled');
};

const close = (status) => {
  emit('close', status);
  resetFormCreateEdit();
};

const buscarDatosPersonaDNI = () => {
  if (!persona.docIdentidad || persona.docIdentidad.length !== 8) {
    ElMessage.warning('Ingrese un DNI válido de 8 dígitos');
    return;
  }

  loading.value = true;
  loadingText.value = 'Consultando servicio de DNI...';
  btnBuscarDNILoading.value = true;
  dniNoEncontrado.value = false;
  consultaExitosa.value = false;
  modoManual.value = false;
  datosObtenidosAutomaticamente.value = false;

  persona.nombres = '';
  persona.apellido_paterno = '';
  persona.apellido_materno = '';
  persona.fecha_nacimiento = '';
  persona.genero = '';
  persona.direccion = '';
  persona.foto = '';

  consultaDNIResource
    .get_data_dni(persona.docIdentidad, 'RONDERO')
    .then((response) => {
      let responseData = response;
      if (response && typeof response === 'object' && 'data' in response) {
        responseData = response.data;
      }
      
      if (responseData && responseData.success === true) {
        const datos = responseData.datos || responseData;
        
        persona.apellido_paterno = datos.apellido_paterno || datos.apellidoPaterno || '';
        persona.apellido_materno = datos.apellido_materno || datos.apellidoMaterno || '';
        persona.nombres = datos.nombres || '';
        
        if (datos.fecha_nacimiento) {
          persona.fecha_nacimiento = datos.fecha_nacimiento;
        }
        
        if (datos.genero) {
          persona.genero = datos.genero;
        } else {
          const ultimoDigito = persona.docIdentidad.slice(-1);
          persona.genero = (parseInt(ultimoDigito) % 2 === 0) ? 'FEMENINO' : 'MASCULINO';
        }
        
        if (datos.direccion) {
          persona.direccion = datos.direccion;
        }
        
        if (datos.foto_base64 || datos.foto) {
          const fotoData = datos.foto_base64 || datos.foto;
          if (fotoData && typeof fotoData === 'string' && fotoData.length > 100) {
            if (fotoData.startsWith('/9j/')) {
              persona.foto = `data:image/jpeg;base64,${fotoData}`;
            } else if (fotoData.startsWith('data:image')) {
              persona.foto = fotoData;
            } else {
              persona.foto = `data:image/jpeg;base64,${fotoData}`;
            }
          }
        }
        
        const tieneDatos = persona.nombres || persona.apellido_paterno || persona.apellido_materno;
        
        if (tieneDatos) {
          datosObtenidosAutomaticamente.value = true;
          
          if (responseData.fuente === 'reniec_rest') {
            mensajeExito.value = '✅ Datos obtenidos de RENIEC (consulta oficial)';
            tipoAlerta.value = 'success';
          } else if (responseData.modo_prueba) {
            mensajeExito.value = '🔧 Modo desarrollo: Usando datos de prueba';
            tipoAlerta.value = 'info';
          } else {
            mensajeExito.value = 'Datos cargados correctamente';
            tipoAlerta.value = 'success';
          }
          
          consultaExitosa.value = true;
          ElMessage.success(`Datos cargados para DNI ${persona.docIdentidad}`);
        } else {
          dniNoEncontrado.value = true;
          ElMessage.warning('No se encontraron nombres para este DNI');
        }
        
      } else {
        const mensajeError = responseData?.message || responseData?.error || 'DNI no encontrado';
        ElNotification({
          type: 'warning',
          title: 'DNI no encontrado',
          message: mensajeError,
          duration: 5000
        });
        dniNoEncontrado.value = true;
        datosObtenidosAutomaticamente.value = false;
      }
    })
    .catch((error) => {
      let errorMessage = 'Error en la consulta al servicio DNI';
      
      if (error.response) {
        if (error.response.status === 401) {
          errorMessage = 'Su sesión ha expirado. Por favor, recargue la página.';
        } else if (error.response.status === 404) {
          errorMessage = 'El servicio de consulta no está disponible';
        } else if (error.response.status === 500) {
          errorMessage = 'Error interno del servidor';
        } else if (error.response.data?.message) {
          errorMessage = error.response.data.message;
        }
      } else if (error.request) {
        errorMessage = 'Error de conexión. Verifique su red.';
      }

      ElNotification({
        type: 'error',
        title: 'Error de consulta',
        message: errorMessage,
        duration: 6000
      });
      
      dniNoEncontrado.value = true;
      datosObtenidosAutomaticamente.value = false;
    })
    .finally(() => {
      btnBuscarDNILoading.value = false;
      loading.value = false;
      loadingText.value = 'Cargando datos...';
    });
};
</script>

<style scoped>
.upload-demo {
  display: block;
}
.upload-demo :deep(.el-upload) {
  display: block;
}
.text-muted {
  color: #666;
  font-size: 12px;
  display: block;
  margin-top: 4px;
}
</style>