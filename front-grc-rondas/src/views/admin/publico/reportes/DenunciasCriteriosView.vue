<template>
  <div v-loading="loading" style="width: 100%">
    <el-row :gutter="10" justify="space-between" align="middle" style="margin-bottom: 15px">
      <el-col :xs="24" :sm="18" :md="20">
        <el-form
          ref="formDenunciasPorFechas"
          label-position="top"
          :model="formData"
          :rules="rules"
          style="width: 100%"
        >
          <el-form-item prop="placa" label="Buscar por fechas" style="width: 100%">
            <el-row :gutter="10" style="width: 100%">
              <el-col :xs="24" :sm="16" :md="8">
                <el-date-picker
                  v-model="formData.dates"
                  type="daterange"
                  format="DD/MM/YYYY"
                  range-separator="Hasta"
                  start-placeholder="Fecha de inicio"
                  end-placeholder="Fecha de fin"
                  style="width: 100%"
                />
              </el-col>
              <el-col :xs="24" :sm="6" :md="4">
                  <el-select v-model="formData.tipo" placeholder="Seleccione" @change="cambiarSelect">
                    <el-option label="Base" value="base" />
                    <el-option label="Zona" value="zona" />
                    <el-option label="Distrito" value="distrito" />
                    <el-option label="Provincia" value="provincia" />
                    <el-option label="Region" value="region" />
                  </el-select>
              </el-col>
              <el-col :xs="24" :sm="6">
                  <el-select filterable v-model="formData.id_tipo" placeholder="Seleccione" :disabled="formData.tipo === ''">
                    <el-option v-for="item in optionsTipos" :key="item.id" :label="item.descripcion" :value="item.id" />
                  </el-select>
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
      <el-col :xs="24" :sm="6" :md="4">
        <el-button @click="downloadExcel">
          <v-icon name="md-excel" />
          Exportar a Excel
        </el-button>
      </el-col>
    </el-row>

    <el-row :gutter="10">
      <el-col :xs="24">
        <h3>Denuncias</h3>
        <el-divider />
        <el-table :data="resultados_denuncias" border>
          <el-table-column prop="num_denuncia" label="NRO." />
          <el-table-column prop="fecha" label="FECHA" />
          <el-table-column prop="denunciante" label="Denunciante" />
          <el-table-column prop="denunciado" label="Denunciado" >
            <template #default="scope">
              <ul v-if="scope.row.listaDenunciados" style="list-style: none; padding: 0; margin: 0">
                <li v-for="item in scope.row.listaDenunciados" :key="item.denunciado.id">
                  <small>{{ item.denunciado.nombres }} {{ item.denunciado.apellido_paterno }} {{ item.denunciado.apellido_materno }}</small>
                </li>
              </ul>
              <small v-else>No tiene denunciados</small>
            </template>
          </el-table-column>
          <el-table-column prop="tipo_conflicto" label="Conflicto" />
          <el-table-column prop="descripcion" label="Desc." />
          <el-table-column prop="estado_denuncia" label="ESTADO" />
        </el-table>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { format } from "@formkit/tempo"
import {onMounted, ref} from 'vue'
import ReportesResource from '@/api/publico/reportes'
const reportesResource = new ReportesResource()

const loading = ref(false)

const formDenunciasPorFechas = ref(null)
const formData = ref({
  dates: [new Date(), new Date()],
  tipo: 'base',
  id_tipo: undefined,
})

const optionsTipos = ref([])

onMounted (() => {
  getTipos()
})

const cambiarSelect = () => {
  formData.value.id_tipo = ''
  optionsTipos.value = []
  getTipos()
}

const getTipos = () => {
  reportesResource
    .tipos(formData.value)
    .then((response) => {
      optionsTipos.value = response
    })
    .catch(() => {
      console.error('Error al consultar tipos')
    })
}

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

const resultados_denuncias = ref([])

const consultarDetalle = () => {
  if (formDenunciasPorFechas.value) {
    formDenunciasPorFechas.value.validate((valid) => {
      if (valid) {
        loading.value = true
        reportesResource
          .denunciasPorCriterios(formData.value)
          .then((response) => {
            resultados_denuncias.value = response.data

            loading.value = false
          })
          .catch(() => {
            console.error('Error al consultar denuncias por fechas')
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

  if (formDenunciasPorFechas.value) {
    formDenunciasPorFechas.value.validate((valid) => {
      if (valid) {
        loading.value = true
        reportesResource
          .denunciasPorCriteriosExcel(formData.value)
          .then((response) => {
            const filename = `denuncias_por_criterios_${date_from}_${date_to}.xlsx`
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
