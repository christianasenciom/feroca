<template>
  <CustomLoading :loading="loading" :loadingText="loadingText">
    Buscar Por:
    <el-tabs v-model="activeName" class="demo-tabs" @tab-click="handleClick">
      <el-tab-pane label="Nombres y Apellidos" name="tab_datos">
        <el-row :gutter="20" >
          <el-col :xs="24" :sm="12" :md="6">
            <el-form-item>
              <el-input v-model="apellido_paterno" placeholder="Apellido Paterno" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6">
            <el-form-item>
              <el-input v-model="apellido_materno" placeholder="Apellido Materno" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6">
            <el-form-item>
              <el-input v-model="nombres" placeholder="Nombres" />
            </el-form-item>
          </el-col>
          <el-col :xs="24" :sm="12" :md="6">
            <el-button type="primary" @click="consultarRQ" :icon="Search">Buscar</el-button>
          </el-col>
        </el-row>
      </el-tab-pane>
      <el-tab-pane label="DNI" name="tab_dni">
        <el-row :gutter="20">
          <el-col :xs="24" :sm="12" :md="8">
            <el-form-item>
              <el-input v-model="docIdentidad"
                        placeholder="Ingrese DNI"
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
          </el-col>
        </el-row>
      </el-tab-pane>
    </el-tabs>
    <el-descriptions
        direction="vertical"
        border
        style="margin-top: 20px"
        v-if="foto_rq"
    >
      <el-descriptions-item
          :rowspan="2"
          :width="140"
          label="Foto"
          align="center"
      >
        <el-image
            style="width: 200px; height: 250px"
            :src="foto_rq ? `data:image/jpeg;base64,${foto_rq}` : ''"
        />
      </el-descriptions-item>
      <el-descriptions-item label="Nombres y Apellidos">{{ mayusculas(nombreCompleto) }}</el-descriptions-item>
      <el-descriptions-item label="Estado">{{ estado_rq }}</el-descriptions-item>
      <el-descriptions-item label="Lugar RQ">{{ lugar_rq }}</el-descriptions-item>
      <el-descriptions-item label="Recompensa">
        <el-tag size="small">{{ recompensa_rq }}</el-tag>
      </el-descriptions-item>
      <el-descriptions-item label="Delito(s)">
        <el-timeline style="max-width: 600px">
          <el-timeline-item
              v-for="(delito, index) in delitos_rq"
              :key="index"
          >
            {{ delito }}
          </el-timeline-item>
        </el-timeline>
      </el-descriptions-item>
    </el-descriptions>

    <el-card v-else style="margin-top: 20px">
      <el-row class="logo" type="flex" justify="center">
        <el-text type="info" style="text-align: center; font-size: 15px">No se encontraron datos</el-text>
      </el-row>
    </el-card>

  </CustomLoading>
</template>
<script setup>
import { ref } from 'vue'
import UtilsResource from '@/api/publico/utils';
import {ElNotification} from "element-plus";
import {isNumber} from "@/utils/utils.js";
import {Search} from "@element-plus/icons-vue";
import Resource from "@/api/resource.js";
import CustomLoading from "@/components/loading/CustomLoading.vue";
const utilsResource = new UtilsResource();
const consultaDNIResource = new Resource('getdatadni')

const docIdentidad = ref('');
const apellido_paterno = ref('');
const apellido_materno = ref('');
const nombres = ref('');
const nombreCompleto = ref(null);
const activeName = ref('tab_datos')
const loading = ref(false)
const loadingText = ref('Cargando datos...')
const btnBuscarDNILoading = ref(false)

//datosrq
const foto_rq = ref(null);
const estado_rq = ref('REQUISITORIADO');
const lugar_rq = ref(null);
const recompensa_rq = ref(null);
const delitos_rq = ref([]);

const mayusculas = (str) => {
  return str.toUpperCase();
}
const handleClick = (tab, event) => {
  console.log(tab, event)
}

const consultarRQ = async () => {
  loading.value = true
  loadingText.value = 'Buscando persona...'
  foto_rq.value = null;
  if(!nombres.value || !apellido_paterno.value || !apellido_materno.value) return;
  nombreCompleto.value = `${nombres.value} ${apellido_paterno.value} ${apellido_materno.value}`
  await utilsResource.consultarRQ({nombreCompleto: nombreCompleto.value})
      .then(response => {
        if(response.error === 'SIN DATOS'){
          ElNotification({message: 'La persona no tiene Requisitoria', type: 'info'})
        }else{
          let { data } = response;
          foto_rq.value = data.foto;
          recompensa_rq.value = 'S/' +data.montoRecompensaSpace;
          lugar_rq.value = data.departamento + ' - ' + data.provincia;
          delitos_rq.value = data.delitos;
        }
        loading.value = false
        nombres.value = "";
        apellido_paterno.value = "";
        apellido_materno.value = "";
  })
      .catch((error) => {
        console.log(error)
      })
      .finally(() => {

      })
}

const buscarDatosPersonaDNI = () => {
  foto_rq.value = null;
  loading.value = true
  loadingText.value = 'Buscando persona...'
  if (!docIdentidad.value) {
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
      .get_data_dni(docIdentidad.value, 'PERSONA')
      .then((data) => {
        if(data.success === false) {
          ElNotification({
            type: 'error',
            title: 'Inconveniente con el DNI',
            message: 'El DNI no existe',
            duration: 4000
          })
          btnBuscarDNILoading.value = false
          loading.value = false
          return false
        }

        const getpersona = data
        nombres.value = getpersona.nombres
        apellido_paterno.value = getpersona.paterno
        apellido_materno.value = getpersona.materno
        consultarRQ();
        btnBuscarDNILoading.value = false

      })
      .catch((error) => {
        console.log(error)
        loading.value = false
        btnBuscarDNILoading.value = false
      })
      .finally(() => {
        btnBuscarDNILoading.value = false

      })
}
</script>

<style scoped>

</style>
