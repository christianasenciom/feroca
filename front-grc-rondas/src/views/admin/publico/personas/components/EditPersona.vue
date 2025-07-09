<template>
  <div v-loading="loading">
    <el-form ref="editPersonaForm" :model="persona" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="DNI" prop="docIdentidad">
            <el-input v-model="persona.docIdentidad" placeholder="DNI" maxlength="8" @keypress="isNumber($event)"/>
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
        </el-col>
      </el-row>
    </el-form>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-button @click="close('canceled')">CANCELAR</el-button>
      <el-button type="primary" @click="submitForm(editPersonaForm)">GUARDAR</el-button>
    </el-row>
  </div>
</template>

<script setup>
import PersonaRequest from '@/api/publico/persona';
import { ElNotification } from 'element-plus'
const personaRequest = new PersonaRequest();
import { reactive, ref, onMounted, watch } from 'vue'

// eslint-disable-next-line no-unused-vars
const props = defineProps({
  idPersona: {
    type: Number,
    required: true,
    default: 0
  }
})



watch(() => {
  return props.idPersona;
}, (newValue, oldValue) => {
  // console.log(newValue, oldValue)
  if(newValue != oldValue && newValue != '' && newValue != null) {
    cargarRegistro()
  }
});
const emit = defineEmits(['close'])

const loading = ref(false)
const editPersonaForm = ref()
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
  email: ''
})

const cargarRegistro = () => {
  loading.value = true
  personaRequest
    .get(props.idPersona)
    .then((response) => {
      const { data } = response
      Object.assign(persona, data)
      // permission.value = data
      loading.value = false
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
  apellido_paterno: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  apellido_materno: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  nombres: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  docIdentidad: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  email: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
})


const submitForm = async (formEl) => {
  if (!formEl) return
  loading.value = true
  await formEl.validate((valid) => {
    if (valid) {
      personaRequest
        .update(persona.id, persona)
        // eslint-disable-next-line no-unused-vars
        .then((response) => {
          ElNotification({
            type: 'success',
            title: 'Persona actualizada',
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
    persona,
    reactive({
      id: undefined,
      docIdentidad: null,
      nombres: '',
      apellido_paterno: '',
      apellido_materno: '',
      fecha_nacimiento: '',
      genero: '',
      celular: '',
      direccion: '',
      email: ''
    })
  )
}

const close = (status) => {
  emit('close', status)
  resetForm()
}
</script>
