<template>
  <CustomLoading class="editRondero" :loading="loading" loadingText="Cargando datos...">
    <el-form ref="editRonderoForm" :model="form_rondero" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="DNI" prop="docIdentidad" >
            <el-input v-model="form_rondero.persona.docIdentidad" placeholder="DNI" maxlength="8" @keypress="isNumber($event)" disabled/>
          </el-form-item>
          <el-form-item label="Nombres" prop="nombres">
            <el-input v-model="form_rondero.persona.nombres" placeholder="Nombres" disabled/>
          </el-form-item>
          <el-form-item label="Ap.Paterno" prop="apellido_paterno">
            <el-input v-model="form_rondero.persona.apellido_paterno" placeholder="Ap. Paterno" disabled/>
          </el-form-item>
          <el-form-item label="Ap. Materno" prop="apellido_materno">
            <el-input v-model="form_rondero.persona.apellido_materno" placeholder="Ap. Materno" disabled/>
          </el-form-item>
          <el-form-item label="Fecha Inicio" prop="fecha_inicio">
            <el-date-picker
                style="width: 100%"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                v-model="form_rondero.fecha_inicio"
                placeholder="Fecha Inicio"
            />
          </el-form-item>
          <el-form-item label="Fecha Cese" prop="fecha_cese">
            <el-date-picker
                style="width: 100%"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                v-model="form_rondero.fecha_cese"
                placeholder="Fecha cese"
            />
          </el-form-item>
          <el-form-item label="Email" prop="email">
            <el-input v-model.trim="form_rondero.persona.email" placeholder="mail@example.com" />
          </el-form-item>
          <el-form-item label="Fecha Nacimiento" prop="fecha_nacimiento">
            <el-date-picker
                style="width: 100%"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                v-model="form_rondero.persona.fecha_nacimiento"
                placeholder="Fecha Nacimiento"
            />
          </el-form-item>
          <el-row :gutter="12">
            <el-col :xs="24" :sm="12" :md="12">
              <el-form-item label="genero" prop="genero">
                <el-select v-model="form_rondero.persona.genero" placeholder="Genero" style="width: 100%">
                  <el-option label="MASCULINO" value="MASCULINO" />
                  <el-option label="FEMENINO" value="FEMENINO" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <el-form-item label="Celular" prop="celular">
                <el-input v-model="form_rondero.persona.celular" placeholder="Celular" />
              </el-form-item>
            </el-col>
          </el-row>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="Dirección" prop="direccion">
            <el-input v-model="form_rondero.persona.direccion" placeholder="Dirección" />
          </el-form-item>
          <el-form-item label="Región" prop="region_id">
            <el-select v-model="form_rondero.region_id" placeholder="Región" style="width: 100%" @change="obtenerProvincias">
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
            <el-select v-model="form_rondero.provincia_id" placeholder="Provincia" style="width: 100%" @change="obtenerDistritos">
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
            <el-select v-model="form_rondero.distrito_id" placeholder="Distrito" style="width: 100%" searchable @change="obtenerSectores">
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
            <el-select v-model="form_rondero.sector_zona_id" placeholder="Sector" style="width: 100%" searchable @change="obtenerBases">
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
            <el-select v-model="form_rondero.base_id" placeholder="Base" style="width: 100%" searchable>
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
            <el-input v-model="form_rondero.partida_registral" placeholder="Partida Registral"/>
          </el-form-item>
          <el-form-item label="Cargos" v-if="tagsCargos && tagsCargos.length > 0">
            <el-table :data="tagsCargos" style="width: 100%" size="small">
              <el-table-column prop="fecha_inicio" label="Inicio" width="100" :formatter="dateFormatter" />
              <el-table-column prop="fecha_fin" label="Fin" width="100" :formatter="dateFormatter"/>
              <el-table-column prop="cargo.descripcion" label="Cargo" width="120" />
              <el-table-column label="Entidad" width="100">
                <template #default="scope">
                  {{ scope.row.comiteable_type }} {{ scope.row.comiteable.descripcion }}
                </template>
              </el-table-column>
              <el-table-column fixed="right" align="right" min-width="10">
                <template #default="scope">
                  <el-button
                      link
                      type="primary"
                      size="small"
                      @click.prevent="deleteRowCargo(scope.$index, scope.row.id)"
                  >
                    <el-icon color="red" size="18"><Delete /></el-icon>
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-form-item>
          <el-form-item>
            <!-- Botón para abrir el diálogo -->
            <el-button type="primary" @click="openDialog(form_rondero.id)">Asignar Cargo</el-button>
          </el-form-item>
          <el-form-item label="Foto para Carnet" prop="foto">
            <el-image v-if="form_rondero.persona.foto" v-bind:src="form_rondero.persona.foto"  alt="" style="width: 160px;  height: 224px;">
              <template #error>
                <div class="image-slot">
                  <el-icon><Picture /></el-icon>
                </div>
              </template>
            </el-image>
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-button @click="close('canceled')"  v-if="!isActionDisabled('pub.rondero.actualizar')">CANCELAR</el-button>
      <el-button type="primary" @click="submitForm(editRonderoForm)"  v-if="!isActionDisabled('pub.rondero.actualizar')">GUARDAR</el-button>

    </el-row>
  </CustomLoading>
  <!-- Componente de diálogo -->
  <AsignarComiteDialog
      v-if="dialogVisible"
      :rondero-id="selectedRonderoId"
      @close-dialog="close_dialog_cargo"
  />
</template>

<script setup>
import AsignarComiteDialog from '@/views/admin/publico/ronderos/components/AsignarComiteDialog.vue';
import RonderoRequest from '@/api/publico/rondero';
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from '@/api/publico/sector';
import BaseResource from '@/api/publico/base';
import {ElMessage, ElMessageBox, ElNotification} from 'element-plus'
import ComiteResource from "@/api/publico/comite";

const comiteResource = new ComiteResource();
const ronderoRequest = new RonderoRequest();
const regionResource = new RegionResource();
const provinciaResource = new ProvinciaResource();
const distritoResource = new DistritoResource();
const sectorResource = new SectorResource();
const baseResource = new BaseResource();
import {reactive, ref, onMounted, watch} from 'vue'
import {Delete, Picture} from "@element-plus/icons-vue";
import {dateFormatter, isActionDisabled, isNumber} from "@/utils/utils.js";
import CustomLoading from "@/components/loading/CustomLoading.vue";

const optionsRegiones = ref([])
const optionsProvincias = ref([])
const optionsDistritos = ref([])
const optionsSectores = ref([])
const optionsBases = ref([])
const dialogVisible = ref(false)
const selectedRonderoId = ref(null)
// eslint-disable-next-line no-unused-vars
const props = defineProps({
  idRondero: {
    type: Number,
    required: true,
    default: 0
  }
})

watch(() => {
  return props.idRondero;
}, (newValue, oldValue) => {
  // console.log(newValue, oldValue)
  if(newValue != oldValue && newValue != '' && newValue != null) {
    cargarRegistro()

  }
});
const emit = defineEmits(['close', 'close-dialog'])

const loading = ref(false)
const editRonderoForm = ref()
const tagsCargos = ref([])
const form_rondero = reactive({
  id: undefined,
  persona: {
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
  },
  fecha_inicio: '',
  fecha_cese: '',
  region_id: '',
  provincia_id: '',
  distrito_id: '',
  sector_zona_id: '',
  base_id: '',
  partida_registral: ''
})

const openDialog = (id_rondero) => {
  selectedRonderoId.value = id_rondero;
  dialogVisible.value = true;
}

const close_dialog_cargo = () => {
  dialogVisible.value = false;
  emit('close-dialog');
  getCargos();
}

const deleteRowCargo = (index, id_comite) => {
  ElMessageBox.confirm(
      `Seguro que desea eliminar el cargo?`,
      'Atención',
      {
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
        type: 'warning',
        dangerouslyUseHTMLString: true
      }
  )
      .then(() => {
        comiteResource.destroy(id_comite).then(response => {
          if (response && response.state === 'success') {
            ElNotification({
              type: 'success',
              title: 'Cargo eliminado',
              duration: 2000
            })
            tagsCargos.value.splice(index, 1)
            getCargos();
          }
        })
      })
      .catch(() => {
        ElMessage.info('Operación cancelada');
      });


}

const getCargos = async () => {
  await comiteResource
      .getComiteablesByRondero(props.idRondero)
      .then(response => {
        const { data } = response
        tagsCargos.value = data;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};

const fetchRegiones = async () => {
  await regionResource.list()
      .then(response => {
        const { data } = response
        optionsRegiones.value = data;

        if (form_rondero.region){
          const region = optionsRegiones.value.find(region => region.id === form_rondero.region.id);
          if (region) {
            form_rondero.region_id = region.id;
            obtenerProvincias(form_rondero.region_id);
          }
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};

const obtenerProvincias = async (region_id) => {
  form_rondero.provincia_id = '';
  form_rondero.distrito_id = '';
  form_rondero.sector_zona_id = '';
  form_rondero.base_id = '';
  await provinciaResource.getProvincias(region_id)
      .then(response => {
        optionsProvincias.value = response;
        if (form_rondero.provincia){
          const provincia = optionsProvincias.value.find(provincia => provincia.id === form_rondero.provincia.id);
          if (provincia) {
            form_rondero.provincia_id = provincia.id;
            obtenerDistritos(form_rondero.provincia_id);
          }
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const obtenerDistritos = async (provincia_id) => {
  form_rondero.distrito_id = '';
  form_rondero.sector_zona_id = '';
  form_rondero.base_id = '';
  await distritoResource.getDistritos(provincia_id)
      .then(response => {
        optionsDistritos.value = response;
        if (form_rondero.distrito){
          const distrito = optionsDistritos.value.find(distrito => distrito.id === form_rondero.distrito.id);
          if (distrito) {
            form_rondero.distrito_id = distrito.id;
            obtenerSectores(form_rondero.distrito_id);
          }
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const obtenerSectores = async (distrito_id) => {
  form_rondero.sector_zona_id = '';
  form_rondero.base_id = '';
  await sectorResource.getSectores(distrito_id)
      .then(response => {
        optionsSectores.value = response;
        if (form_rondero.sector){
          const sector = optionsSectores.value.find(sector => sector.id === form_rondero.sector.id);

          if (sector) {
            form_rondero.sector_zona_id = sector.id;
            obtenerBases(form_rondero.sector_zona_id);
          }
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};

const obtenerBases = async (sector_zona_id) => {
  form_rondero.base_id = '';
  await baseResource.getBases(sector_zona_id)
      .then(response => {
        optionsBases.value = response;
        if (form_rondero.base){
          const base = optionsBases.value.find(base => base.id === form_rondero.base.id);
          if (base) {
            form_rondero.base_id = base.id;
          }
        }
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const cargarRegistro = async () => {
  resetForm()
  loading.value = true
  await ronderoRequest
    .get(props.idRondero)
    .then((response) => {
      const { data } = response
      Object.assign(form_rondero, data)
      // permission.value = data
      loading.value = false
      fetchRegiones()
      getCargos();
    })
    .catch((error) => {
      console.log(error)
      loading.value = true
      ElNotification({
        type: 'error',
        title: 'Error al recuperar el registro',
        duration: 2000
      })
      close('canceled')
    })
}

onMounted(() => {
  cargarRegistro()
})


const rules = reactive({
  'persona.apellido_paterno': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  'persona.apellido_materno': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  'persona.nombres': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  'persona.docIdentidad': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
})


const submitForm = async (formEl) => {
  if (!formEl) return
  loading.value = true
  await formEl.validate((valid) => {
    if (valid) {
      ronderoRequest
        .update(form_rondero.id, form_rondero)
        // eslint-disable-next-line no-unused-vars
        .then((response) => {
          ElNotification({
            type: 'success',
            title: 'Rondero actualizado',
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
  Object.assign(
    form_rondero,
    reactive({
      id: undefined,
      persona: {
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
      },
      fecha_inicio: '',
      fecha_cese: '',
      region_id: '',
      provincia_id: '',
      distrito_id: '',
      sector_zona_id: '',
      base_id: '',
    })
  )
}

const close = (status) => {
  emit('close', status)
  resetForm()
}
</script>

<style scoped>
.editRondero .image-slot {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 100%;
  background: var(--el-fill-color-light);
  color: var(--el-text-color-secondary);
  font-size: 30px;
}
</style>
