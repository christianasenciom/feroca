<template>
  <CustomLoading :loading="loading" :loadingText="loadingText">
    <el-form ref="createRonderoForm" :model="persona" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="DNI" prop="docIdentidad">
            <el-input v-model="persona.docIdentidad"
                      placeholder="DNI"
                      maxlength="8"
                      show-word-limit
                      @keypress="isNumber($event)"
                      oninput="javascript: if (this.value.length > this.maxLength) this.value = this.value.slice(0, this.maxLength);"
                      class="input-with-select"
                      @keyup.enter="buscarDatosPersonaDNI">
              <template #append>
                <el-button
                    :icon="Search"
                    :loading="btnBuscarDNILoading"
                    @click="buscarDatosPersonaDNI"
                />
              </template>
            </el-input>
          </el-form-item>
          <el-form-item label="Nombres" prop="nombres">
            <el-input v-model="persona.nombres" placeholder="Nombres" disabled/>
          </el-form-item>
          <el-form-item label="Ap.Paterno" prop="apellido_paterno">
            <el-input v-model="persona.apellido_paterno" placeholder="Ap. Paterno" disabled/>
          </el-form-item>
          <el-form-item label="Ap. Materno" prop="apellido_materno">
            <el-input v-model="persona.apellido_materno" placeholder="Ap. Materno" disabled/>
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
          <el-form-item label="Email" prop="email">
            <el-input v-model.trim="persona.email" placeholder="mail@example.com" />
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
              <el-form-item label="Genero" prop="genero" :xs="24" :sm="12" :md="12">
                <el-select v-model="persona.genero" placeholder="Genero" style="width: 100%">
                  <el-option label="MASCULINO" value="MASCULINO" />
                  <el-option label="FEMENINO" value="FEMENINO" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <el-form-item label="Celular" prop="celular" :xs="24" :sm="12" :md="12">
                <el-input v-model="persona.celular" placeholder="Celular" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12">

        <el-form-item label="Dirección" prop="direccion">
          <el-input v-model="persona.direccion" placeholder="Dirección" />
        </el-form-item>
        <el-form-item label="Región" prop="region_id">
          <el-select v-model="persona.region_id" placeholder="Región" style="width: 100%" searchable @change="obtenerProvincias">
            <el-option label="- Seleccione Región -" value="0"/>
            <el-option
                v-for="item in optionsRegiones"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="Provincia" prop="provincia_id">
          <el-select v-model="persona.provincia_id" placeholder="Provincia" style="width: 100%" searchable @change="obtenerDistritos">
            <el-option
                v-for="item in optionsProvincias"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="Distrito" prop="distrito_id">
          <el-select v-model="persona.distrito_id" placeholder="Distrito" style="width: 100%" searchable @change="obtenerSectores">
            <el-option
                v-for="item in optionsDistritos"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="Sector" prop="sector_zona_id">
          <el-select v-model="persona.sector_zona_id" placeholder="Sector" style="width: 100%" searchable @change="obtenerBases">
            <el-option
                v-for="item in optionsSectores"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="Base" prop="base_id">
          <el-select v-model="persona.base_id" placeholder="Base" style="width: 100%" searchable>
            <el-option
                v-for="item in optionsBases"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
            >
            </el-option>
          </el-select>
        </el-form-item>
        <el-form-item label="Partida Registral" prop="partida_registral">
          <el-input v-model="persona.partida_registral" placeholder="Partida Registral"/>
        </el-form-item>
        <el-form-item label="Foto para Carnet" prop="foto">
          <el-image v-if="persona.foto" v-bind:src="'data:image/jpeg;base64,'+persona.foto"  alt=""/>
        </el-form-item>

      </el-col>
      </el-row>
    </el-form>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-button @click="close('canceled')">CANCELAR</el-button>
      <el-button type="primary" @click="submitForm(createRonderoForm)">GUARDAR</el-button>
    </el-row>
  </CustomLoading>
</template>

<script setup>
import Resource from '@/api/resource'
import { Search } from '@element-plus/icons-vue'
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
import {onMounted, reactive, ref} from 'vue'
import {isNumber} from "@/utils/utils.js";
import CustomLoading from "@/components/loading/CustomLoading.vue";

const emit = defineEmits(['close'])
const loading = ref(false)
const loadingText = ref('Cargando datos...')
const createRonderoForm = ref()
const btnBuscarDNILoading = ref(false)
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
  await sectorResource.getSectores(persona.distrito_id)
      .then(response => {
        optionsSectores.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};

const obtenerBases = async () => {
  persona.base_id = '';
  await baseResource.getBases(persona.sector_zona_id)
      .then(response => {
        optionsBases.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};

const rules = reactive({
  apellido_paterno: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  apellido_materno: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  nombres: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  docIdentidad: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  fecha_inicio: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  region_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  provincia_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  distrito_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  sector_zona_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  base_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  // fecha_cese: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  // email: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
})

const submitForm = async (formEl) => {
  if (!formEl) return
  loading.value = true
  await formEl.validate((valid) => {
    if (valid) {
      ronderoRequest
        .store(persona)
        // eslint-disable-next-line no-unused-vars
        .then((response) => {
          ElNotification({
            type: 'success',
            title: 'Rondero creado',
            duration: 2000
          })
          close('success')
          loading.value = false
        })
        .catch((error) => {
          console.log(error)
          loading.value = false
        })
    } else {
      loading.value = false
    }
  })
}

const resetForm = () => {
  Object.assign(persona, reactive({
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
  }));
}

const close = (status) => {
  emit('close', status)
  resetForm()
}

const buscarDatosPersonaDNI = () => {
  loading.value = true
  loadingText.value = 'Buscando persona...'
  if (!persona.docIdentidad) {
    this.$message({
      message:
          'Ingrese el nro de documento a buscar',
      type: 'error',
      duration: 5.6 * 1000
    })
    return false
  }

  btnBuscarDNILoading.value = true

  consultaDNIResource
        .get_data_dni(persona.docIdentidad, 'RONDERO')
        .then((data) => {
          if(data.success === false) {
            ElNotification({
              type: 'error',
              title: 'Incoveniente con el DNI',
              message: 'El DNI no existe',
              duration: 4000
            })
            btnBuscarDNILoading.value = false
            return false
          }

          const getpersona = data
          persona.apellido_paterno = getpersona.apellidoPaterno;
          persona.apellido_materno = getpersona.apellidoMaterno;
          persona.nombres = getpersona.nombres;
          persona.fecha_nacimiento = getpersona.nacimiento;
          persona.genero = getpersona.sexo === 'M' ? 'MASCULINO' : 'FEMENINO';
          persona.celular = getpersona.fono;
          persona.direccion = getpersona.direccion;
          persona.email = getpersona.correo;
          persona.foto = getpersona.foto;



          btnBuscarDNILoading.value = false
        })
        .catch((error) => {
          console.log(error)

          btnBuscarDNILoading.value = false
        })
        .finally(() => {
          btnBuscarDNILoading.value = false
          loading.value = false
        })
}

</script>
