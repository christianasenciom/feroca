<template>
  <CustomLoading :loading="loading" :loadingText="loadingText">
    <el-form ref="createDenunciadoForm" :model="persona" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="DNI" prop="docIdentidad">
            <el-input v-model="persona.docIdentidad"
                      placeholder="DNI"
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
          <el-form-item label="Nombres" prop="nombres">
            <el-input v-model="denunciado.nombres" placeholder="Nombres"/>
          </el-form-item>
          <el-form-item label="Ap.Paterno" prop="apellido_paterno">
            <el-input v-model="denunciado.apellido_paterno" placeholder="Ap. Paterno"/>
          </el-form-item>
          <el-form-item label="Ap. Materno" prop="apellido_materno">
            <el-input v-model="denunciado.apellido_materno" placeholder="Ap. Materno"/>
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12" :md="12">

        <el-form-item label="Dirección" prop="direccion">
          <el-input v-model="denunciado.direccion" placeholder="Dirección" />
        </el-form-item>
        <el-form-item label="Celular" prop="celular">
            <el-input v-model.trim="denunciado.celular" placeholder="Celular" />
        </el-form-item>
        <el-form-item label="Observaciones" prop="observaciones" style="width: 100%">
          <el-input v-model="denunciado.observaciones" placeholder="Observaciones" type="textarea" maxlength="255"/>
        </el-form-item>
        <!-- <el-form-item label="Foto" prop="foto">
          <el-image v-if="denunciado.foto" v-bind:src="'data:image/jpeg;base64,'+denunciado.id"  alt=""/>
        </el-form-item> -->

      </el-col>
      </el-row>
    </el-form>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <template #default="scope">
      <el-button @click="close('canceled')">CANCELAR</el-button>
      <el-button type="primary" 
                 @click="handleAgregarAlPadre(scope.$index, denunciado)">AGREGAR PERSONA A DENUNCIAR</el-button>
      </template>
    </el-row>
  </CustomLoading>
</template>

<script setup>
import Resource from '@/api/resource'
import { Search } from '@element-plus/icons-vue'
import RonderoRequest from '@/api/publico/rondero';
import {ElMessage, ElNotification} from 'element-plus'
const ronderoRequest = new RonderoRequest();
const consultaDNIResource = new Resource('getdatadni')
import {onMounted, reactive, ref} from 'vue'
import {isNumber} from "@/utils/utils.js";
import DenunciadoResource from '@/api/publico/denunciado';
const denunciadoResource = new DenunciadoResource();
import CustomLoading from "@/components/loading/CustomLoading.vue";
const query = reactive({
  keyword: '',
  limit: 8,
  page: 1,
  orderby: 'ASC',
  ids_excluir: [],
  base_id: 0,
});
const props = defineProps({
  idsExcluir: {
    type: Array,
    required: false,
    default: () => []
  }
})
const meta_list = ref([])
const emit = defineEmits(['closeListDenunciados', 'enviar-denunciado','ids-excluir']);
//const emit = defineEmits(['close'])
const loading = ref(false)
const loadingText = ref('Cargando datos...')
const createDenunciadoForm = ref()
const btnBuscarDNILoading = ref(false)
const optionsRegiones = ref([])

const persona = reactive({
  id: undefined,
  docIdentidad: null,
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  celular: '',
  direccion: '',
  // foto: '',
})

const denunciado = reactive({
  id: undefined,
  // docIdentidad: null,
  nombres: '',
  apellido_paterno: '',
  apellido_materno: '',
  celular: '',
  direccion: '',
  observaciones: '',
  // foto: '',
})

const fetchRegiones = async () => {
  await regionResource.list()
      .then(response => {
        const { data } = response
        optionsRegiones.value = data;
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
};
const rules = ({
  // apellido_paterno: { required: true, message: 'Campo requerido', trigger: 'blur' },
  // apellido_materno: { required: true, message: 'Campo requerido', trigger: 'blur' },
  // nombres: { required: true, message: 'Campo requerido', trigger: 'blur' },
  // docIdentidad: [{  message: 'Campo requerido', trigger: 'blur' }],
  // direccion: { required: true, message: 'Campo requerido', trigger: 'blur' },
  // celular: { required: true, message: 'Campo requerido', trigger: 'blur' },
})

const validateForm = () => {
  console.log(denunciado.nombres);
  if (!denunciado.nombres) {
    ElMessage.error('Nombres son requeridos');
    return false
  }
  if (!denunciado.apellido_paterno) {
    ElMessage.error('Apellido paterno es requerido');
    return false
  }
  if (!denunciado.apellido_materno) {
    ElMessage.error('Apellido materno es requerido');
    return false
  }
  if (!denunciado.direccion) {
    ElMessage.error('Dirección es requerido');
    return false
  }
  return true
}


const handleAgregarAlPadre = (index, denunciado) =>{
  
  // createDenunciadoForm.value?.validate((valid) => {
  // if (valid) {
      //loadingSaveDialogForm.value = true;
      if (!validateForm()) return
      loading.value = true
      loadingText.value = 'Guardando...'
      denunciadoResource.store(denunciado)
        .then((response) => {
          console.log(response.data.id)
          denunciado.id= response.data.id
          console.log(denunciado)
          let rowDenunciado = {
            id: denunciado.id,
            denunciado_id: denunciado.id,
            apellido_paterno: denunciado.apellido_paterno,
            apellido_materno: denunciado.apellido_materno,
            nombres: denunciado.nombres,
            observaciones: denunciado.observaciones
            // docIdentidad: row.docIdentidad,
          };
          //query.ids_excluir.push(row.id);
          emit('enviar-denunciado', rowDenunciado); // Emitir evento con los datos
          //emit('ids-excluir', row.id); // Emitir evento con los datos
        
          //denunciadosListDialog.value.splice(index, 1)
          loading.value = false
  
          //denunciado.id= response.data.id
          ElMessage.success('Datos guardados correctamente');
          createDenunciadoForm.value = false;
          emit('closeListDenunciados');
          //resetModelRegion();
          //visibleDialogForm.value = false;
          //fetchConflicto();
        })
        .catch((error) => {
          console.error('Error saving data:', error);
          ElMessage.error('Se ha producido un error al guardar');
          loading.value = false;
          //showSelectPersonasModal.value = false;
        })
        .finally(() => {
          createDenunciadoForm.value = false;
          //showSelectPersonasModal.value = false;
          console.log(createDenunciadoForm.value)
          //loadingSaveDialogForm.value = false;
         // loading.value = false
        });
  console.log(denunciado);
    //   }
    //   else {
    //       console.log('Formulario no válido');
    //       loading.value = false
    //     }
    // });
}



const saveDataForm = () => {
  // console.log('createDenunciadoForm.value')
  // console.log(createDenunciadoForm.value)
  //     //loadingSaveDialogForm.value = true;
  //     //loading.value = true
  //     denunciadoResource.store(denunciado)
  //       .then((response) => {
  //         console.log(response.data.id)
  //         denunciado.id= response.data.id
  //         console.log(denunciado)
  //         //denunciado.id= response.data.id
  //         ElMessage.success('Datos guardados correctamente');
  //         createDenunciadoForm.value = false;
  //         //resetModelRegion();
  //         //visibleDialogForm.value = false;
  //         //fetchConflicto();
  //       })
  //       .catch((error) => {
  //         console.error('Error saving data:', error);
  //         ElMessage.error('Se ha producido un error al guardar');
  //       })
  //       .finally(() => {
  //         createDenunciadoForm.value = false;
  //         showSelectPersonasModal.value = false;
  //         console.log(createDenunciadoForm.value)
  //         //loadingSaveDialogForm.value = false;
  //        // loading.value = false
  //       });
    };

const submitForm = async (formEl) => {
  if (!formEl) return
  loading.value = true
  await formEl.validate((valid) => {
    if (valid) {
      ronderoRequest
        .store(persona)
        // eslint-disable-next-line no-unused-vars
        .then((response) => {
          ElNotification({
            type: 'success',
            title: 'Rondero creado',
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
  Object.assign(denunciado, reactive({
    // docIdentidad: null,
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    celular: '',
    direccion: '',
    // foto: '',
  }));
}

const close = (status) => {
  emit('close', status)
  resetForm()
}


const buscarDatosPersonaDNI = () => {
  loading.value = true
  loadingText.value = 'Buscando persona...'
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
        .get_data_dni(persona.docIdentidad, 'DENUNCIADO')
        .then((data) => {
          if(data.success === false) {
            ElNotification({
              type: 'error',
              title: 'Incoveniente con el DNI',
              message: 'El DNI no existe',
              duration: 4000
            })
            btnBuscarDNILoading.value = false
            return false
          }

          const getpersona = data
          //denunciado.id = getpersona.id;
          denunciado.apellido_paterno = getpersona.paterno;
          denunciado.apellido_materno = getpersona.materno;
          denunciado.nombres = getpersona.nombres;
          denunciado.celular = getpersona.fono;
          denunciado.direccion = getpersona.direccion;
          // persona.foto = getpersona.foto;



          btnBuscarDNILoading.value = false
        })
        .catch((error) => {
          console.log(error)

          btnBuscarDNILoading.value = false
        })
        .finally(() => {
          btnBuscarDNILoading.value = false
          loading.value = false
        })
} 

</script>
