<template>
  <div v-loading="loading" style="width: 100%">
    <el-row :gutter="10" justify="space-between" align="middle" style="margin-bottom: 15px">
      <el-col :xs="24" :sm="16" :md="16">
        <el-form
          ref="formAsambleasPorFechas"
          label-position="top"
          :model="formData"
          :rules="rules"
          style="width: 100%"
        >
          <el-form-item prop="placa" label="Buscar por fechas" style="width: 100%">
            <el-row :gutter="10" style="width: 100%">
<!--              <el-col :xs="24" :sm="4" :md="6">-->
<!--                <el-input v-model="formData.placa" placeholder="Placa" @input="formData.placa = formData.placa.toUpperCase()" />-->
<!--              </el-col>-->
              <el-col :xs="24" :sm="16" :md="12">
                <el-date-picker
                  v-model="formData.dates"
                  type="daterange"
                  format="DD/MM/YYYY"
                  range-separator="Hasta"
                  start-placeholder="Fecha de inicio"
                  end-placeholder="Fecha de fin"
                  style="width: 100%"
                  @change="consultarDetalle"
                />
              </el-col>
              <el-col :xs="24" :sm="6" :md="3">
                <el-button type="primary" style="width: 100%" @click="consultarDetalle()">
                  Buscar
                </el-button>
              </el-col>
            </el-row>
          </el-form-item>
        </el-form>
      </el-col>
    </el-row>

    <el-row :gutter="10">
      <el-col :xs="24">
        <h3>Asambleas</h3>
        <el-divider />
        <el-table :data="resultados_denuncias" border>
          <el-table-column prop="fecha" label="FECHA" />
          <el-table-column prop="base" label="Base" />
          <el-table-column align="right" width="120">
            <template #default="scope">
              <el-button type="primary" circle :icon="View" @click="openFormAsistencia(scope.row.id)"/>
            </template>
          </el-table-column>
        </el-table>
      </el-col>
    </el-row>
  </div>

  <el-dialog
      top="5vh"
      v-model="visibleDialogFormAsistencia"
      :width="calcularAnchoDialog('75%','98%')"
      :close-on-click-modal="false"
      destroy-on-close
  >
    <template #header>
      <div class="dialog-header">
        <span class="dialog-title">Asamblea</span>
      </div>
    </template>

    <AsistenciaView @close="visibleDialogFormAsistencia = false" :id-turno="id_turno" :ver-botones="false" />
  </el-dialog>

</template>

<script setup>
import { format } from "@formkit/tempo"
import {ref} from 'vue'
import ReportesResource from '@/api/publico/reportes'
import {View} from "@element-plus/icons-vue";
import AsistenciaView from "@/views/admin/publico/asambleas/components/AsistenciaView.vue";
import {calcularAnchoDialog} from "@/utils/responsive.js";
const reportesResource = new ReportesResource()

const loading = ref(false)

const formAsambleasPorFechas = ref(null)
const formData = ref({
  dates: [new Date(), new Date()]
})

const rules = {
  dates: [
    {
      type: 'array',
      required: true,
      message: 'Por favor selecciona un rango de fechas',
      trigger: 'change'
    },
    { validator: validateDateRange, trigger: 'change' }
  ]
}

function validateDateRange(rule, value, callback) {
  if (value && value.length === 2) {
    const startDate = value[0]
    const endDate = value[1]
    if (startDate > endDate) {
      callback(new Error('La fecha de inicio debe ser anterior a la fecha de fin'))
    } else {
      callback()
    }
  } else {
    callback(new Error('Por favor selecciona un rango de fechas'))
  }
}

const id_turno = ref(0)
const titleDialogForm = ref('')
const visibleDialogFormAsistencia = ref(false)
const openFormAsistencia = (id) => {
  id_turno.value = id
  titleDialogForm.value = 'Asamblea';
  visibleDialogFormAsistencia.value = true;

}

const resultados_denuncias = ref([])

const consultarDetalle = () => {
  if (formAsambleasPorFechas.value) {
    formAsambleasPorFechas.value.validate((valid) => {
      if (valid) {
        loading.value = true
        reportesResource
          .asambleasPorFechas(formData.value)
          .then((response) => {
            resultados_denuncias.value = response.data

            loading.value = false
          })
          .catch(() => {
            console.error('Error al consultar asambleas por fechas')
            loading.value = false
          })
      } else {
        console.error('Error de formulario')
      }
    })
  }
}

const downloadExcel = () => {
  const date_from = format(formData.value.dates[0], "DD-MM-YYYY")
  const date_to = format(formData.value.dates[1], "DD-MM-YYYY")

  if (formAsambleasPorFechas.value) {
    formAsambleasPorFechas.value.validate((valid) => {
      if (valid) {
        loading.value = true
        reportesResource
          .asambleasPorFechasExcel(formData.value)
          .then((response) => {
            const filename = `asambleas_por_fechas_${date_from}_${date_to}.xlsx`
            const url = window.URL.createObjectURL(response)
            const link = document.createElement('a')
            link.href = url
            link.setAttribute('download', filename)
            link.style.display = 'none'
            document.body.appendChild(link)
            link.click()
            setTimeout(() => {
              document.body.removeChild(link)
              window.URL.revokeObjectURL(url)
            }, 0)
            loading.value = false
          })
          .catch(() => {
            console.error('Error al descargar execel')
            loading.value = false
          })
      } else {
        console.error('Error de formulario')
      }
    })
  }
}

// onMounted(() => {
//   search()
// })
</script>
<style scoped>
.el-date-editor .is-active {
  background-color: #ffffffff !important;
}
.total-infracciones {
  margin-top: 20px;
}
.total-infracciones p {
  font-size: 20px;
  font-weight: bold;
  color: #000000;
  background-color: #f0f0f0;
  padding: 10px;
  border-radius: 5px;
  box-shadow: 0 0 5px rgba(0, 0, 0, 0.1);
  text-align: center;
  width: 100%;
  margin: 0;
}
</style>
