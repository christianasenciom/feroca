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
              :disabled="disableEditarFecha === true"
          />
        </el-form-item>
        <el-form-item label="Descripción" prop="descripcion" style="width: 100%" >
          <el-input v-model="form_turno.descripcion" placeholder="Descripción" type="textarea" maxlength="255" :disabled="disableEditarFecha === true"/>
        </el-form-item>
      </el-col>
      <el-col :span="12">
        <el-row type="flex" justify="center" :gutter="20">
          <el-col :span="12">
            <el-form-item label="Región">
              <el-text>{{ form_turno.region?.descripcion }}</el-text>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Provincia">
              <el-text>{{ form_turno.provincia?.descripcion }}</el-text>
            </el-form-item>
          </el-col>
        </el-row>
        <el-row type="flex" justify="center" :gutter="20">
          <el-col :span="12">
            <el-form-item label="Distrito">
              <el-text>{{ form_turno.distrito?.descripcion }}</el-text>
            </el-form-item>
          </el-col>
          <el-col :span="12">
            <el-form-item label="Sector">
              <el-text>{{ form_turno.sector?.descripcion }}</el-text>
            </el-form-item>
          </el-col>
        </el-row>
        <el-form-item label="Base">
          <el-text>{{ form_turno.base?.descripcion }}</el-text>
        </el-form-item>
      </el-col>
    </el-row>
    <el-row>
      <el-col  :span="24">
        <el-collapse>

        <el-collapse-item name="1" >
          <template #title>
            <div class="collapse-title">
              Documentos: <el-tag size="small">{{ imageUrls?.length || 0 }}</el-tag>
            </div>
          </template>
          <el-col  :span="24">
            <div class>
              <el-upload

                  ref="upload_evidencia"
                  action=""
                  list-type="picture-card"
                  :http-request="customUpload"
                  :on-preview="handlePictureCardPreview"
                  :on-change="updateImageList"
                  :on-remove="removeImageList"
                  :before-remove="beforeRemove"
                  :auto-upload="true"
                  :before-upload="beforeUpload"
                  accept="image/*"
              >
                <i class="el-icon"><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1024 1024">
                  <path fill="currentColor" d="M480 480V128a32 32 0 0 1 64 0v352h352a32 32 0 1 1 0 64H544v352a32 32 0 1 1-64 0V544H128a32 32 0 0 1 0-64z"></path></svg></i>
              </el-upload>
              <ImageList :images="imageUrls" />
              <el-dialog v-model="dialogVisible">
                <img style="width: 100%" :src="dialogImageUrl" alt />
              </el-dialog>
            </div>

          </el-col>
        </el-collapse-item>
      </el-collapse>
      </el-col>
    </el-row>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-col :span="24">
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
          <el-table-column label="Asistencia">
            <template #default="scope">
              <el-select v-model="scope.row.tipo_asistencia" size="small">
                <el-option label="Asistio" :value="1" :selected="scope.row.tipo_asistencia === 1"></el-option>
                <el-option label="Inasistencia" :value="2"></el-option>
                <el-option label="Tardanza" :value="3"></el-option>
              </el-select>
            </template>
          </el-table-column>
          <el-table-column label="Observaciones">
            <template #default="scope">
              <el-input v-model="scope.row.observaciones" placeholder="Observaciones" size="small" />
            </template>
          </el-table-column>

        </el-table>

      </el-col>
    </el-row>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3" style="margin-top: 10px" v-if="props.verBotones">
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
import {nextTick, onMounted, reactive, ref, watch} from 'vue'
import {disabledPastDates} from "@/utils/utils.js";
import TurnoResource from '@/api/publico/turnos';
import {ElMessage, ElMessageBox, ElNotification} from "element-plus";
import {calcularAnchoDialog} from "@/utils/responsive.js";
import ListaRonderos from "@/views/admin/publico/asambleas/components/ListaRonderos.vue";
import {CloseBold, Select} from "@element-plus/icons-vue";
import ImageList from "@/views/admin/publico/asambleas/components/ImageList.vue";

const turnoResource = new TurnoResource();

const imageUrls = ref([]);
const imageList = ref([])
const dialogImageUrl = ref('')
const dialogVisible = ref(false)

const disableEditarFecha = ref(false)
const ids_excluir = ref([])
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

  const desabilitarBotones = () => {
    const fecha_hoy = new Date().toISOString().split('T')[0]
    disableEditarFecha.value = form_turno.fecha < fecha_hoy;
  }

const props = defineProps({
  idTurno: {
    type: Number,
    required: true,
    default: 0
  },
  verBotones: {
    type: Boolean,
    required: false,
    default: true
  }
})
watch(() => {
  return props.idTurno;
}, (newValue, oldValue) => {
  // console.log(newValue, oldValue)
  if(newValue != oldValue && newValue != '' && newValue != null) {
    cargarRegistro()
  }
});
onMounted(() => {
  cargarRegistro()
})
const form_turno = reactive({
  id: undefined,
  descripcion: '',
  fecha: '',
  responsable_turno: '',
  tipo_reunion: 'Vigilancia', // Asamblea o Vigilancia
})


const resetForm = () => {
  form_turno.id = undefined
  form_turno.descripcion = ''
  form_turno.fecha = ''
  form_turno.tipo_reunion = 'Vigilancia'
  detalleProgramacionronderos.value = []
  ids_excluir.value = []
  loadingTable.value = false
}


const rules = ref({
  // descripcion: [
  //   {required: true, message: 'Descripción es requerido', trigger: 'blur'},
  // ],
})

const cargarRegistro = async () => {
  resetForm()
  loadingSaveDialogForm.value = true
  await turnoResource
      .get(props.idTurno)
      .then((response) => {
        const { data } = response
        // console.log(data)
        nextTick(() => {
          Object.assign(form_turno, data)
          let ronderos_array = [];
          if(data.detalle_ronderos.length > 0) {
            data.detalle_ronderos.forEach((item) => {
              ronderos_array.push({
                id: item.id,
                turno_id: item.turno_id,
                rondero_id: item.rondero_id,
                tipo_asistencia: item.tipo_asistencia,
                observaciones: item.observaciones,
                estado: item.estado,
                nombres: item.nombres,
                apellido_paterno: item.apellido_paterno,
                apellido_materno: item.apellido_materno,
                docIdentidad: item.docIdentidad,
              })

              ids_excluir.value.push(item.rondero_id);
            })
            detalleProgramacionronderos.value = ronderos_array;

            //traer imagenes desde el back con storage
            imageUrls.value = [];
            response.data.archivos_asamblea.forEach((val) => {
              imageUrls.value.push(val.url)
            });
          }
          desabilitarBotones()
          loadingSaveDialogForm.value = false
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

// Maneja el evento y almacena los datos recibidos
const manejarDatosDelHijo = (dato) => {
  detalleProgramacionronderos.value.push(dato);
};

const turnoFormRef = ref(null);
const submitFormTurno = () => {
  turnoFormRef.value.validate((valid) => {

    if(detalleProgramacionronderos.value.length === 0){
      ElMessage.error('Tiene que agregar ronderos al Turno de Vigilancia')
      return false
    }

    // if(form_turno.responsable_turno === ''){
    //   ElMessage.error('El responsable del turno es requerido')
    //   return false
    // }

    if (valid) {
      if (form_turno.id === undefined) {
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
  loadingSaveDialogForm.value = true

  turnoResource.updateAsistencia(form_turno.id, {
    descripcion: form_turno.descripcion,
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

const beforeUpload = (file) => {
  const isImage = file.type.startsWith('image/')

  if (!isImage) {
    ElMessage.error('El archivo debe ser una imagen')
  }

  return isImage;
}

const beforeRemove = (file) => {
  return ElMessageBox.confirm(`¿Desea borrar ${file.name}？`);
}

const removeImageList = (file) => {
  // console.log(file, fileList)
  imageList.value.splice(imageList.value.indexOf(file.name), 1)
}

const updateImageList = (file) =>{
  imageList.value.push(file)

}

const handlePictureCardPreview = (file) =>{
  dialogImageUrl.value = file.url
  // this.imageList.push(file)
  dialogVisible.value = true
}

const customUpload = async ( file ) => {

  // console.log(file.file)
  const formData = new FormData();
  formData.append("file", file.file);

  try {
    const response = await turnoResource.subirFotoDocumento(formData, form_turno.id);
    if (response.success) {
      // this.uploadedImage = response.data.path;
      ElMessage.success("Imagen cargada correctamente");
    }
  } catch (error) {
    ElMessage.error("Error al cargar la imagen");
    console.error(error);
  }
}

</script>
<style scoped>
.hidden{
  display: none!important;
}
</style>
