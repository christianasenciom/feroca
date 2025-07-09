<template>
  <div v-loading="loading" style="width: 100%">
    <el-row :gutter="10" justify="space-between" align="middle" style="margin-bottom: 15px">
      <el-col :xs="24" :sm="16" :md="16">
        <el-form
          ref="formDenunciasPorPersona"
          label-position="top"
          :model="formData"
          :rules="rules"
          style="width: 100%"
        >
          <el-form-item label="Busqueda de denuncias por DNI" style="width: 100%" prop="keyword">
            <el-row :gutter="10" style="width: 100%">
              <el-col :xs="24" :sm="8" :md="6" :lg="6">
                <el-select v-model="formData.type">
                  <el-option label="Denunciado" value="DENUNCIADO" />
                  <el-option label="Denunciante" value="DENUNCIANTE" />
                </el-select>
              </el-col>
              <el-col :xs="24" :sm="12" :md="12" :lg="12">
                <el-input
                    v-model="formData.keyword"
                    :placeholder="placeholder"
                    @keyup.enter="search"
                />
              </el-col>
              <el-col :xs="24" :sm="6" :md="3">
                <el-button type="primary" style="width: 100%" @click="search"> Buscar </el-button>
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
import {computed, ref} from 'vue'
import ReportesResource from '@/api/publico/reportes'
const reportesResource = new ReportesResource()

const loading = ref(false)

const formDenunciasPorPersona = ref(null)
const formData = ref({
  type: 'DENUNCIADO',
  keyword: null
})

const placeholder = computed(() => {
  if (formData.value.type === 'DENUNCIADO') {
    return 'Apellidos y Nombres del denunciado'
  } else if (formData.value.type === 'DENUNCIANTE') {
    return 'Ingrese el DNI del denunciante'
  } else {
    return ''
  }
})

const rules = {
  keyword: [
    { required: true, message: 'Ingrese el valor a buscar', trigger: 'blur' },
    {
      validator: (rule, value, callback) => {
        if (formData.value.type === 'DNI' && value && value.length < 8) {
          callback(new Error('El DNI debe tener al menos 8 dígitos'))
        } else {
          callback()
        }
      },
      trigger: 'blur'
    }
  ]
}

const resultados_denuncias = ref([])

const search = () => {
  if (formDenunciasPorPersona.value) {
    formDenunciasPorPersona.value.validate((valid) => {
      if (valid) {
        loading.value = true
        reportesResource
          .denunciasPorPersona(formData.value)
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
  if (formDenunciasPorPersona.value) {
    formDenunciasPorPersona.value.validate((valid) => {
      if (valid) {
        loading.value = true
        reportesResource
          .denunciasPorPersonaExcel(formData.value)
          .then((response) => {
            const filename = `denuncias-por-${formData.value.type}.xlsx`
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
