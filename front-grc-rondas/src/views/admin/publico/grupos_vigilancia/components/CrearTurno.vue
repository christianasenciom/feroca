<template>
  <el-form ref="turnoFormRef" :model="form_turno" label-position="top" :rules="rules" label-width="120px" v-loading="loadingSaveDialogForm" v-on:submit.prevent>
    <el-row type="flex" justify="center" :gutter="20">
      <el-col :span="12">
<!--        <el-form-item label="Turno" prop="turno">-->
<!--          <el-select v-model="form.turno" placeholder="Turno" style="width: 100%">-->
<!--            <el-option label="Mañana" value="Mañana" />-->
<!--            <el-option label="Tarde" value="Tarde" />-->
<!--            <el-option label="Noche" value="Noche" />-->
<!--          </el-select>-->
<!--        </el-form-item>-->
        <el-form-item label="Fecha" prop="fecha">
          <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="form_turno.fecha"
              placeholder="Fecha"
              :disabled-date="disabledPastDates"
          />
        </el-form-item>
        <el-form-item label="Descripción" prop="descripcion" style="width: 100%">
          <el-input v-model="form_turno.descripcion" placeholder="Descripción" type="textarea" maxlength="255"/>
        </el-form-item>
        <el-form-item label="Responsable de Grupo" prop="responsable_turno" v-if="detalleProgramacionronderos && detalleProgramacionronderos.length > 0">
          <el-select v-model="form_turno.responsable_turno" placeholder="Responsable" style="width: 100%" searchable>
            <el-option
                v-for="item in detalleProgramacionronderos"
                :key="item.id"
                :label="item.nombres + ' ' + item.apellido_paterno + ' ' + item.apellido_materno"
                :value="item.id"
            >
            </el-option>
          </el-select>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-row type="flex" justify="center" :gutter="20">
          <el-col :span="12">
            <el-form-item label="Región" prop="region_id">
              <el-select v-model="form_turno.region_id" placeholder="Región" style="width: 100%" searchable @change="obtenerProvincias">
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
          </el-col>
          <el-col :span="12">
            <el-form-item label="Provincia" prop="provincia_id">
              <el-select v-model="form_turno.provincia_id" placeholder="Provincia" style="width: 100%" searchable @change="obtenerDistritos">
                <el-option
                    v-for="item in optionsProvincias"
                    :key="item.id"
                    :label="item.descripcion"
                    :value="item.id"
                >
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row type="flex" justify="center" :gutter="20">
          <el-col :span="12">
            <el-form-item label="Distrito" prop="distrito_id">
              <el-select v-model="form_turno.distrito_id" placeholder="Distrito" style="width: 100%" searchable @change="obtenerSectores">
                <el-option
                    v-for="item in optionsDistritos"
                    :key="item.id"
                    :label="item.descripcion"
                    :value="item.id"
                >
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Sector" prop="sector_zona_id">
              <el-select v-model="form_turno.sector_zona_id" placeholder="Sector" style="width: 100%" searchable @change="obtenerBases">
                <el-option
                    v-for="item in optionsSectores"
                    :key="item.id"
                    :label="item.descripcion"
                    :value="item.id"
                >
                </el-option>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="Base" prop="base_id">
          <el-select v-model="form_turno.base_id" placeholder="Base" style="width: 100%" searchable>
            <el-option
                v-for="item in optionsBases"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
            >
            </el-option>
          </el-select>
        </el-form-item>
      </el-col>
    </el-row>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-col :span="24">
        <div class="mt-3 d-flex justify-content-end" style="text-align: right">
          <el-button type="primary" @click="showSelectRonderosModal" :icon="Plus">Agregar Ronderos</el-button>
        </div>
        <el-table
            :data="detalleProgramacionronderos"
            style="width: 100%"
            max-height="500"
            v-loading="loadingTable"
            element-loading-text="Cargando..."
            :empty-text="'No hay ronderos...'"
        >
          <el-table-column
              prop="docIdentidad"
              label="DNI"
          />
          <el-table-column
              prop="nombres"
              label="Nombres"
          />
          <el-table-column label="Apellidos">
            <template #default="scope">
              {{ scope.row.apellido_paterno }} {{ scope.row.apellido_materno }}
            </template>
          </el-table-column>
          <el-table-column align="right" width="120">
            <template #default="scope">
              <el-button
                  size="small"
                  type="danger"
                  :icon="Delete"
                  @click="eliminarRondero(scope.$index, scope.row)"
                  :class="parseInt(scope.row.id_detalle_multa) === 0 ? '' : 'hidden'"
              >
                Quitar
              </el-button>
            </template>
          </el-table-column>

        </el-table>

      </el-col>
    </el-row>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3" style="margin-top: 10px">
      <el-button @click="close('canceled')" :icon="CloseBold">CANCELAR</el-button>
      <el-button type="primary" @click="submitFormTurno" :icon="Select">GUARDAR</el-button>
    </el-row>
  </el-form>
  <el-dialog
      top="10vh"
      v-model="dialogListRonderosForm"
      :width="calcularAnchoDialog('65%','98%')"
      :close-on-click-modal="false"
      destroy-on-close
      tooltip-effect
  >
    <template #header>
      <div class="dialog-header">
        <span class="dialog-title">Lista de ronderos</span>
      </div>
    </template>
    <ListaRonderos :base-id="id_base"
                   @closeListRonderos="dialogListRonderosForm = false"
                   @enviar-rondero="manejarDatosDelHijo"
                   :ids-excluir="ids_excluir"
    />
  </el-dialog>
</template>
<script setup>
import RonderoRequest from '@/api/publico/rondero';
import {onMounted, reactive, ref} from 'vue'
import {disabledPastDates} from "@/utils/utils.js";
import TurnoResource from '@/api/publico/turnos';
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from '@/api/publico/sector';
import BaseResource from '@/api/publico/base';
import {ElMessage} from "element-plus";
import {calcularAnchoDialog} from "@/utils/responsive.js";
import ListaRonderos from "@/views/admin/publico/grupos_vigilancia/components/ListaRonderos.vue";
import {CloseBold, Delete, Plus, Select} from "@element-plus/icons-vue";
const ronderoRequest = new RonderoRequest();
const turnoResource = new TurnoResource();
const regionResource = new RegionResource();
const provinciaResource = new ProvinciaResource();
const distritoResource = new DistritoResource();
const sectorResource = new SectorResource();
const baseResource = new BaseResource();

const ids_excluir = ref([])
const optionsRegiones = ref([])
const optionsProvincias = ref([])
const optionsDistritos = ref([])
const optionsSectores = ref([])
const optionsBases = ref([])
const loadingSaveDialogForm = ref(false)
const detalleProgramacionronderos = ref([])
const loadingTable = ref(false)
const dialogListRonderosForm = ref(false)
const emit = defineEmits(['close','closeListRonderos','reloadData'])
const close = (status) => {
  emit('close', status)
  resetForm()
}
const reloadData = () => {
  emit('reloadData')
}

const form_turno = reactive({
  id: undefined,
  region_id: '',
  provincia_id: '',
  distrito_id: '',
  sector_zona_id: '',
  base_id: '',
  descripcion: '',
  fecha: '',
  responsable_turno: '',
  tipo_reunion: 'Vigilancia', // Asamblea o Vigilancia
})


const resetForm = () => {
  form_turno.id = undefined
  form_turno.region_id = ''
  form_turno.provincia_id = ''
  form_turno.distrito_id = ''
  form_turno.sector_zona_id = ''
  form_turno.base_id = ''
  form_turno.descripcion = ''
  form_turno.fecha = ''
  form_turno.responsable_turno = ''
  form_turno.tipo_reunion = 'Vigilancia'
  detalleProgramacionronderos.value = []
  ids_excluir.value = []
  loadingTable.value = false
}


const rules = ref({
  fecha: [
    {required: true, message: 'Fecha es requerido', trigger: 'blur'},
  ],
  // descripcion: [
  //   {required: true, message: 'Descripción es requerido', trigger: 'blur'},
  // ],
  region_id: [
    {required: true, message: 'Región es requerido', trigger: 'blur'},
  ],
  provincia_id: [
    {required: true, message: 'Provincia es requerido', trigger: 'blur'},
  ],
  distrito_id: [
    {required: true, message: 'Distrito es requerido', trigger: 'blur'},
  ],
  sector_zona_id: [
    {required: true, message: 'Sector es requerido', trigger: 'blur'},
  ],
  base_id: [
    {required: true, message: 'Base es requerido', trigger: 'blur'},
  ],
  responsable_turno: [
    {required: true, message: 'Responsable turno es requerido', trigger: 'blur'},
  ]
})

const fetchRegiones = async () => {
  loadingSaveDialogForm.value = true
  await regionResource.list()
      .then(response => {
        const { data } = response
        optionsRegiones.value = data;
        loadingSaveDialogForm.value = false
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const obtenerProvincias = async () => {
  form_turno.provincia_id = '';
  form_turno.distrito_id = '';
  form_turno.sector_zona_id = '';
  form_turno.base_id = '';
  await provinciaResource.getProvincias(form_turno.region_id)
      .then(response => {
        optionsProvincias.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const obtenerDistritos = async () => {
  form_turno.distrito_id = '';
  form_turno.sector_zona_id = '';
  form_turno.base_id = '';
  await distritoResource.getDistritos(form_turno.provincia_id)
      .then(response => {
        optionsDistritos.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const obtenerSectores = async () => {
  form_turno.sector_zona_id = '';
  form_turno.base_id = '';
  await sectorResource.getSectores(form_turno.distrito_id)
      .then(response => {
        optionsSectores.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};

const obtenerBases = async () => {
  form_turno.base_id = '';
  await baseResource.getBases(form_turno.sector_zona_id)
      .then(response => {
        optionsBases.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const validateForm = () => {
  if (!form_turno.region_id || !form_turno.provincia_id || !form_turno.distrito_id || !form_turno.sector_zona_id || !form_turno.base_id || !form_turno.fecha) {
    ElMessage.error('Todos los campos son requeridos');
    return false
  }
  return true
}
const id_base = ref(0)
const showSelectRonderosModal = () => {
  if (!validateForm()) return
  dialogListRonderosForm.value = true;
  id_base.value = parseInt(form_turno.base_id)
}

// Maneja el evento y almacena los datos recibidos
const manejarDatosDelHijo = (dato) => {
  detalleProgramacionronderos.value.push(dato);
};
// const manejadorIdsExcluir = (dato) => {
//   ids_excluir.value.push(dato);
// };

const eliminarRondero = (index, row) => {
  detalleProgramacionronderos.value.splice(index, 1)
  if (row.rondero_id === form_turno.responsable_turno) {
      form_turno.responsable_turno = '';
  }
  ids_excluir.value.splice(ids_excluir.value.indexOf(row.id), 1)
}

onMounted(fetchRegiones)

const turnoFormRef = ref(null);
const submitFormTurno = () => {
  turnoFormRef.value.validate((valid) => {

    if(detalleProgramacionronderos.value.length === 0){
      ElMessage.error('Tiene que agregar ronderos al Turno de Vigilancia')
      return false
    }

    if(form_turno.responsable_turno === ''){
      ElMessage.error('El responsable del turno es requerido')
      return false
    }

    if (valid) {
      if (form_turno.id === undefined) {
        console.log('Guardar!!')
        saveDataForm()
      } else {
        console.log('Editar!!')
        // this.saveEditDataForm()
      }
    } else {
      console.log('error submit!!')
      return false
    }
  });
};

const saveDataForm = () => {
  loadingSaveDialogForm.value = true

  turnoResource.store({
    region_id: form_turno.region_id,
    provincia_id: form_turno.provincia_id,
    distrito_id: form_turno.distrito_id,
    sector_zona_id: form_turno.sector_zona_id,
    base_id: form_turno.base_id,
    descripcion: form_turno.descripcion,
    fecha: form_turno.fecha,
    responsable_turno: form_turno.responsable_turno,
    tipo_reunion: form_turno.tipo_reunion,
    ronderos: detalleProgramacionronderos.value
  })
      .then(() => {
        ElMessage({
          message: 'Guardado con exito',
          type: 'success'
        })
        loadingSaveDialogForm.value = false
        reloadData();
        close('success');
      })
      .catch((error) => {
        ElMessage.error('Error al guardar')
        console.log(error)
        loadingSaveDialogForm.value = false
      })
      .finally(() => {
        loadingSaveDialogForm.value = false
      })
}

</script>
<style scoped>

</style>
