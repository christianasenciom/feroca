<template>
  <CustomLoading :loading="loading" :loadingText="loadingText">
  <el-form ref="sendMailsFormRef" :model="form_send_mail" label-position="top" label-width="120px" v-loading="loadingSaveDialogForm" v-on:submit.prevent>
    <el-row type="flex" justify="center" :gutter="20">
      <el-col :span="12">
        <el-form-item label="Fecha" prop="fecha">
          <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="form_send_mail.fecha"
              placeholder="Fecha"
              disabled
          />
        </el-form-item>
      </el-col>
      <el-col :span="12">
      <el-form-item label="Nombres" prop="nombres">
            <el-input v-model="form_send_mail.nombres" placeholder="Nombres" disabled/>
          </el-form-item>
        </el-col>
        <el-col :span="12">
        <el-form-item label="Tipo de conflicto" prop="conflicto" style="width: 100%" >
          <el-input v-model="form_send_mail.conflicto" placeholder="Tipo de conflicto" type="text" disabled />
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-form-item label="Descripción" prop="descripcion" style="width: 100%" >
          <el-input v-model="form_send_mail.descripcion" placeholder="Descripción" type="text"  disabled/>
        </el-form-item>
      </el-col>
      <el-col :span="24">
        <el-form-item label="Seleccione la provincia, distrito, sector o base que desea notificar. Por defecto si presiona el botón 'Enviar Notificaciones' se enviarán a todas las bases de la región CAJAMARCA" prop="observaciones" style="width: 100%" >
        </el-form-item>
      </el-col>
      <el-col :span="24">
        <el-row type="flex" justify="center" :gutter="20">
          <el-col :span="12">
            <el-form-item label="Región" prop="region_id">
              <el-select v-model="form_send_mail.region_id" placeholder="Región" disabled style="width: 100%" searchable @change="obtenerProvincias">
                <!-- <el-option label="- Seleccione Región -" value="0"/> -->
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
              <el-select v-model="form_send_mail.provincia_id" placeholder="Provincia" style="width: 100%" searchable @change="obtenerDistritos">
                <el-option label="- Seleccione una provincia -" value="0"/>
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
              <el-select v-model="form_send_mail.distrito_id" placeholder="Distrito" style="width: 100%" searchable @change="obtenerSectores">
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
              <el-select v-model="form_send_mail.sector_zona_id" placeholder="Sector" style="width: 100%" searchable @change="obtenerBases">
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
          <el-select v-model="form_send_mail.base_id" placeholder="Base" style="width: 100%" searchable>
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
    <el-row type="flex" justify="center" :gutter="20">
      <el-col :span="24">
        <el-table
              :data="detalleNotificaciones"
              style="width: 100%"
              max-height="500"
              v-loading="loadingTable"
              element-loading-text="Cargando..."
              :empty-text="'No hay notificaciones...'"
          >
            <el-table-column
                prop="fecha"
                label="Fecha de la notificacion"
            />
            <el-table-column
                prop="destino"
                label="Destino"
            />
            <el-table-column label="Estado">
              <el-icon  size="30"  color="green"><Check /></el-icon>
            </el-table-column>
          </el-table>
      </el-col>
    </el-row>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3" style="margin-top: 10px">
      <el-button @click="close('canceled')" :icon="CloseBold">CANCELAR</el-button>
      <el-button type="primary" @click="submitFormSendMails" :icon="Select"  v-if="disableEnviar === false">ENVIAR NOTIFICACIONES</el-button>
    </el-row>
  </el-form>
</CustomLoading>
</template>
<script setup>
import {nextTick, onMounted, reactive, ref, watch} from 'vue'
import {disabledPastDates} from "@/utils/utils.js";
import DenunciaResource from '@/api/publico/denuncia';
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from '@/api/publico/sector';
import BaseResource from '@/api/publico/base';
import {ElMessage} from "element-plus";
import {CloseBold, Select} from "@element-plus/icons-vue";
import CustomLoading from "@/components/loading/CustomLoading.vue";
import { Check } from '@element-plus/icons-vue';

const denunciaResource = new DenunciaResource();
const regionResource = new RegionResource();
const provinciaResource = new ProvinciaResource();
const distritoResource = new DistritoResource();
const sectorResource = new SectorResource();
const baseResource = new BaseResource();
const detalleNotificaciones = ref([])
const optionsRegiones = ref([])
const optionsProvincias = ref([])
const optionsDistritos = ref([])
const optionsSectores = ref([])
const optionsBases = ref([])
const loadingTable = ref(false)
const loading = ref(false)
const loadingText = ref('Cargando datos...')
const query = reactive({
  denuncia_id: 0,
  base_id: 0,
  sector_id: 0,
  distrito_id: 0,
  provincia_id: 0,
  region_id: 1,
  fecha_denuncia : 0,
  denunciante : 0,
  conflicto : 0,
  descripcion : 0,
});

const disableEnviar = ref(false)

const loadingSaveDialogForm = ref('Cargando datos...')
const emit = defineEmits(['close'])
const close = (status) => {
  emit('close', status)
  resetForm()
}


  const desabilitarBotones = () => {
    //const fecha_hoy = new Date().toISOString().split('T')[0]
    disableEnviar.value = true;
  }

const props = defineProps({
  idDenuncia: {
    type: Number,
    required: true,
    default: 0
  },
})

onMounted(() => {
  cargarRegistro()
  fetchRegiones()
  fetchDenuncia()
})
const fetchDenuncia = async () => {
  loadingSaveDialogForm.value = true
  await denunciaResource.get(props.idDenuncia)
      .then(response => {
        console.log(response);
        const { data } = response
        console.log(data.fecha);
        form_send_mail.fecha = data.fecha;
        form_send_mail.descripcion = data.descripcion;
        form_send_mail.conflicto = data.tipo_conflicto_id.descripcion;
        form_send_mail.nombres = data.denunciante_id.nombres+' '+data.denunciante_id.apellido_paterno+' '+data.denunciante_id.apellido_materno;
        //loadingSaveDialogForm.value = false
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
      obtenerProvincias()
};
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
      obtenerProvincias()
};
const obtenerProvincias = async () => {
  form_send_mail.region_id = 1;
  form_send_mail.provincia_id = '';
  form_send_mail.distrito_id = '';
  form_send_mail.sector_zona_id = '';
  form_send_mail.base_id = '';
  await provinciaResource.getProvincias(form_send_mail.region_id)
      .then(response => {
        optionsProvincias.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
      //form_send_mail.provincia_id = 'Enviar a las provincias de la región';
      //form_send_mail.distrito_id = 'Enviar a los distritos de la provincia';
};
const obtenerDistritos = async () => {
  form_send_mail.distrito_id = '';
  form_send_mail.sector_zona_id = '';
  form_send_mail.base_id = '';
  await distritoResource.getDistritos(form_send_mail.provincia_id)
      .then(response => {
        optionsDistritos.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const obtenerSectores = async () => {
  form_send_mail.sector_zona_id = '';
  form_send_mail.base_id = '';
  await sectorResource.getSectores(form_send_mail.distrito_id)
      .then(response => {
        optionsSectores.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};

const obtenerBases = async () => {
  form_send_mail.base_id = '';
  await baseResource.getBases(form_send_mail.sector_zona_id)
      .then(response => {
        console.log(response[0].admin_id);
        optionsBases.value = response;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const cargarRegistro = async () => {
  resetForm()
  //console.log(props.idDenuncia)
  //loadingSaveDialogForm.value = true
  loading.value = true
  await denunciaResource.getNotificaciones(props.idDenuncia)
      .then((response) => {
        console.log(response)
        const { data } = response
        nextTick(() => {
          let notificaciones_array = [];
          if(data.length > 0) {
            data.forEach((item) => {
              notificaciones_array.push({
                fecha: item.fecha,
                denuncia_id: item.denuncia_id,
                destino: item.destino,
                estado: 'Enviado'
              })
            })
            detalleNotificaciones.value = notificaciones_array;
          }
        })
      })
      .catch((error) => {
        console.log(error)
        //loadingSaveDialogForm.value = true
        ElNotification({
          type: 'error',
          title: 'Error al recuperar el registro',
          duration: 2000
        })
        close('canceled')
      })
      loading.value = false;
}
const form_send_mail = reactive({
  idDenuncia: undefined,
  observaciones: '',
  fecha_cita: '',
  region_id: '',
  provincia_id: '',
  distrito_id: '',
  sector_zona_id: '',
  base_id: '',
  nombres:'',
})


const resetForm = () => {
  //form_send_mail.denuncia_id = undefined
  form_send_mail.observaciones = ''
  form_send_mail.fecha_cita = ''
  form_send_mail.region_id = 1
  form_send_mail.provincia_id = ''
  form_send_mail.distrito_id = ''
  form_send_mail.sector_zona_id = ''
  form_send_mail.base_id = ''
  detalleNotificaciones.value = []
  //loadingTable.value = false
}


const validateForm = () => {
  if (!form_send_mail.fecha_cita) {
    ElMessage.error('el campo fecha es requerido');
    return false
  }
  if (!form_send_mail.observaciones) {
    ElMessage.error('el campo observaciones es requerido');
    return false
  }
  return true
}


const sendMailsFormRef = ref(null);
const submitFormSendMails = () => {
  loading.value = true
  loadingText.value = 'Enviando notificaciones...';
  query.denuncia_id = props.idDenuncia;
  query.base_id = form_send_mail.base_id;
  query.sector_id = form_send_mail.sector_zona_id;
  query.distrito_id = form_send_mail.distrito_id;
  query.provincia_id = form_send_mail.provincia_id;
  query.fecha_denuncia = form_send_mail.fecha;
  query.denunciante = form_send_mail.nombres;
  query.conflicto = form_send_mail.conflicto;
  query.descripcion = form_send_mail.descripcion;
  console.log(query)
  //desabilitarBotones();
  denunciaResource.enviarMail(query)
  .then(() => {
        ElMessage({
          message: 'Notificaciones enviadas con exito',
          type: 'success'
        })
        loading.value = false
        loading.value = false
        cargarRegistro();
        //close('success');
      })
      .catch((error) => {
        ElMessage.error('Error al guardar')
        console.log(error)
        loading.value = false
        loading.value = false
      })
      .finally(() => {
        loading.value = false
        loading.value = false
      })
};



</script>
<style scoped>
.hidden{
  display: none!important;
}
</style>
