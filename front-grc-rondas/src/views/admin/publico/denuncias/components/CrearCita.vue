<template>
  <el-form ref="citaFormRef" :model="form_cita" label-position="top" label-width="120px" v-loading="loadingSaveDialogForm" v-on:submit.prevent>
    <el-row type="flex" justify="center" :gutter="20">
      <el-col :span="18">
        <el-form-item label="Fecha" prop="fecha_cita">
          <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="form_cita.fecha_cita"
              placeholder="Fecha"
              :disabled-date="disabledPastDates"
              :disabled="disableEditarFecha === true"
          />
        </el-form-item>
        <el-form-item label="Observaciones" prop="observaciones" style="width: 100%" >
          <el-input v-model="form_cita.observaciones" placeholder="Observaciones" type="textarea" maxlength="255" :disabled="disableEditarFecha === true"/>
        </el-form-item>
      </el-col>
    </el-row>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3" style="margin-top: 10px">
      <el-button @click="close('canceled')" :icon="CloseBold">CANCELAR</el-button>
      <el-button type="primary" @click="submitFormCita" :icon="Select"  v-if="disableEditarFecha === false">GUARDAR</el-button>
    </el-row>
  </el-form>
</template>
<script setup>
import {nextTick, onMounted, reactive, ref, watch} from 'vue'
import {disabledPastDates} from "@/utils/utils.js";
import DenunciaResource from '@/api/publico/denuncia';
import {ElMessage} from "element-plus";
import {CloseBold, Select} from "@element-plus/icons-vue";

const denunciaResource = new DenunciaResource();


const disableEditarFecha = ref(false)

const loadingSaveDialogForm = ref(false)
const loadingTable = ref(false)
const emit = defineEmits(['close','reloadData'])
const close = (status) => {
  emit('close', status)
  resetForm()
}
const reloadData = () => {
  emit('reloadData')
}


const props = defineProps({
  idDenuncia: {
    type: Number,
    required: true,
    default: 0
  }
})
// watch(() => {
//   return props.idDenuncia;
// }, (newValue, oldValue) => {
//    console.log(newValue, oldValue)
//   if(newValue != oldValue && newValue != '' && newValue != null) {
//     //cargarRegistro()
//   }
// });
onMounted(() => {
  cargarRegistro()
})

const cargarRegistro = async () => {
  resetForm()
  //loadingSaveDialogForm.value = true
  await denunciaResource
      .get(props.idDenuncia)
      .then((response) => {
        const { data } = response
        console.log(data)
        nextTick(() => {
          Object.assign(form_cita, data)
          //desabilitarBotones()
        })
      })
      .catch((error) => {
        console.log(error)
        loadingSaveDialogForm.value = true
        ElNotification({
          type: 'error',
          title: 'Error al recuperar el registro',
          duration: 2000
        })
        close('canceled')
      })
}
const form_cita = reactive({
  idDenuncia: undefined,
  observaciones: '',
  fecha_cita: '',
})


const resetForm = () => {
  form_cita.denuncia_id = undefined
  form_cita.observaciones = ''
  form_cita.fecha_cita = ''
  loadingTable.value = false
}


const validateForm = () => {
  if (!form_cita.fecha_cita) {
    ElMessage.error('el campo fecha es requerido');
    return false
  }
  if (!form_cita.observaciones) {
    ElMessage.error('el campo observaciones es requerido');
    return false
  }
  return true
}


const citaFormRef = ref(null);
const submitFormCita = () => {
  citaFormRef.value.validate((valid) => {
    if (valid) {
      if (props.idDenuncia === undefined) {
        console.log(props.idDenuncia )
        console.log('Guardar!!')

      } else {
        console.log('Editar!!')
        saveDataForm()
      }
    } else {
      console.log('error submit!!')
      return false
    }
  });
};

const saveDataForm = () => {
  if (!validateForm()) return
  loadingSaveDialogForm.value = true

  denunciaResource.registrarCita(props.idDenuncia, {
    observaciones: form_cita.observaciones,
    fecha_cita: form_cita.fecha_cita,
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
.hidden{
  display: none!important;
}
</style>
