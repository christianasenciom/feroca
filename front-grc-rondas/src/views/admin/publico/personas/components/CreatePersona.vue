<template>
  <div v-loading="loading">
    <el-form ref="createPersonaForm" :model="persona" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="DNI" prop="docIdentidad">
            <el-input v-model="persona.docIdentidad"
                      placeholder="DNI"
                      maxlength="8"
                      @keypress="isNumber($event)"
                      class="input-with-select">
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
            <el-input v-model="persona.nombres" placeholder="Nombres" />
          </el-form-item>
          <el-form-item label="Ap.Paterno" prop="apellido_paterno">
            <el-input v-model="persona.apellido_paterno" placeholder="Ap. Paterno" />
          </el-form-item>
          <el-form-item label="Ap. Materno" prop="apellido_materno">
            <el-input v-model="persona.apellido_materno" placeholder="Ap. Materno" />
          </el-form-item>
          <el-form-item label="Email" prop="email">
            <el-input v-model.trim="persona.email" placeholder="mail@example.com" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12">
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
        <el-form-item label="genero" prop="genero">
          <el-select v-model="persona.genero" placeholder="Genero" style="width: 100%">
            <el-option label="MASCULINO" value="MASCULINO" />
            <el-option label="FEMENINO" value="FEMENINO" />
          </el-select>
        </el-form-item>
        <el-form-item label="Celular" prop="celular">
          <el-input v-model="persona.celular" placeholder="Celular" />
        </el-form-item>
        <el-form-item label="Dirección" prop="direccion">
          <el-input v-model="persona.direccion" placeholder="Dirección" />
        </el-form-item>
        <el-form-item label="Imagen" prop="foto">
          <img v-bind:src="'data:image/jpeg;base64,'+persona.foto"  alt=""/>
        </el-form-item>

      </el-col>
      </el-row>
    </el-form>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-button @click="close('canceled')">CANCELAR</el-button>
      <el-button type="primary" @click="submitForm(createPersonaForm)">GUARDAR</el-button>
    </el-row>
  </div>
</template>

<script setup>
import Resource from '@/api/resource'
import { Search } from '@element-plus/icons-vue'
import PersonaRequest from '@/api/publico/persona';
import { ElNotification } from 'element-plus'
const personaRequest = new PersonaRequest();
const consultaDNIResource = new Resource('getdatadni')
import { reactive, ref } from 'vue'

const emit = defineEmits(['close'])
const loading = ref(false)
const createPersonaForm = ref()
const btnBuscarDNILoading = ref(false)
const persona = reactive({
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
})

const rules = reactive({
  apellido_paterno: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  apellido_materno: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  nombres: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  docIdentidad: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  // email: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
})

const submitForm = async (formEl) => {
  if (!formEl) return
  loading.value = true
  await formEl.validate((valid) => {
    if (valid) {
      personaRequest
        .store(persona)
        // eslint-disable-next-line no-unused-vars
        .then((response) => {
          ElNotification({
            type: 'success',
            title: 'Persona creada',
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
    email: ''
  }));
}

const close = (status) => {
  emit('close', status)
  resetForm()
}

const  isNumber = (evt) => {
  evt = (evt) ? evt : window.event;
  var charCode = (evt.which) ? evt.which : evt.keyCode;
  if ((charCode > 31 && (charCode < 48 || charCode > 57)) && charCode != 46) {
    evt.preventDefault();
  } else {
    return true;
  }
}

const buscarDatosPersonaDNI = () => {

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
        .get(persona.docIdentidad)
        .then((data) => {
          const getpersona = data
          console.log(getpersona)
          persona.foto = getpersona.foto;
          btnBuscarDNILoading.value = false
        })
        .catch((error) => {
          console.log(error)

          btnBuscarDNILoading.value = false
        })
        .finally(() => {
          btnBuscarDNILoading.value = false
        })
}

</script>
