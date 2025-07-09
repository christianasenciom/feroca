<template>
  <el-dialog v-model="visibleDialogFormRegAdmin" :close-on-click-modal="false" top="7vh" width="500" :title="titleDialogForm">
      <div>
        <el-form ref="formAsignarAdmin" v-loading="loadingSaveDialogForm" :model="modelBase"
          :rules="reglasValidacion" status-icon label-width="120px" label-position="top" v-on:submit.prevent>
          <el-form-item label="Base" prop="descripcion">
            <el-input ref="descripcionField" v-model="modelBase.descripcion" type="text" autocomplete="off"
              placeholder="Base" />
          </el-form-item>
          <el-form-item label="Ronderos" prop="admin_id">
            <el-select v-model="modelBase.admin_id" placeholder="Ronderos" style="width: 100%" searchable>
              <el-option
                  v-for="item in detalleRonderos"
                  :key="item.rondero_id"
                  :label="item.nombres + ' ' + item.apellido_paterno + ' ' + item.apellido_materno"
                  :value="item.rondero_id"
              >
              </el-option>
            </el-select>
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="saveFormCreateEdit('formCreateBase')">
            Guardar
          </el-button>
          <el-button type="danger" @click="resetFormCreateEdit('formCreateBase')">Resetear</el-button>
        </div>
      </template>
    </el-dialog>
  <!-- <el-dialog
        top="5vh"
        v-model="visibleDialogFormRegAdmin"
        :width="calcularAnchoDialog('75%','98%')"
        :close-on-click-modal="false"
        destroy-on-close
    >
      <template #header>
        <div class="dialog-header">
          <span class="dialog-title">Asignar Administrador</span>
        </div>
      </template>

      <AsignarAdmin @close="visibleDialogFormRegAdmin = false" @reload-data="fetchTurnos" :id-base="id_base" />
    </el-dialog> -->
</template>
<style scoped>
.paging {
  display: flex;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap;
  flex-direction: row;
  align-content: flex-start;
  padding-top: 20px;
}
</style>
<script>
import {ref, reactive, onMounted, nextTick, markRaw} from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import {Search, Plus, Edit, Delete, ArrowDown, Check, Close} from '@element-plus/icons-vue';

import { useAuthStore } from "@/stores/AuthStore";
import BaseResource from '@/api/publico/base';
import {isActionDisabled} from "@/utils/utils.js";
import SectorResource from "@/api/publico/sector";


export default {
  name: 'BasesView',
  components: {Edit, Close, Check, ArrowDown},
  methods: {isActionDisabled},
  setup() {
    const sector_zonaResource = new SectorResource();
    const baseResource = new BaseResource();
    const authStore = useAuthStore()

    const formCreateEditBase = ref(null);
    const descripcionField = ref(null);

    const modelBase = reactive({
      id: undefined,
      descripcion: '',
      sector_zona_id: '',
    });
    const reglasValidacion = {
      descripcion: [
        { required: true, message: 'El campo es requerido', trigger: 'blur' }
      ],
      sector_zona_id: [
        { required: true, message: 'El campo es requerido', trigger: 'blur' }
      ],
    };


    const tableData = ref([]);
    const meta = ref({});
    const listLoading = ref(true);

    const listQuery = reactive({
      page: 1,
      limit: 8,
      keyword: ''
    });

    const visibleDialogForm = ref(false);
    const titleDialogForm = ref('');
    const loadingSaveDialogForm = ref(false);
    const optionsSectors = ref([]);

    const openFormCreate = () => {
      resetModelBase();
      titleDialogForm.value = 'Registrar Base';
      visibleDialogForm.value = true;
      nextTick(() => {
        fetchSectors(); //cargar provinciaes
        formCreateEditBase.value?.clearValidate();
        descripcionField.value?.focus();
      });
    };

    const openFormEditar = async (id) => {
      await fetchSectors(); //cargar provinciaes
      titleDialogForm.value = 'Editar Base';
      visibleDialogForm.value = true;
      loadingSaveDialogForm.value = true;
      try {
        const { data } = await baseResource.get(id);
        Object.assign(modelBase, {
          id: data.id,
          descripcion: data.descripcion,
          sector_zona_id: data.sector_zona.id,
        });
        nextTick(() => {
          formCreateEditBase.value?.clearValidate();
          descripcionField.value?.focus();
        });
      } catch (error) {
        visibleDialogForm.value = false;
        ElMessage.info('Error al obtener datos');
      } finally {
        loadingSaveDialogForm.value = false;
      }
    };

    const resetModelBase = () => {
      Object.assign(modelBase, {
        id: undefined,
        descripcion: '',
        sector_zona_id: '',
      });
    };

    const saveFormCreateEdit = () => {
      formCreateEditBase.value?.validate((valid) => {
        if (valid) {
          if (modelBase.id === undefined) {
            saveDataForm();
          } else {
            saveEditDataForm();
          }
        } else {
          console.log('Formulario no válido');
        }
      });
    };


    const saveDataForm = () => {
      loadingSaveDialogForm.value = true;
      baseResource.store(modelBase)
        .then(() => {
          ElMessage.success('Datos guardados correctamente');
          resetModelBase();
          visibleDialogForm.value = false;
          fetchBases();
        })
        .catch((error) => {
          console.error('Error saving data:', error);
          ElMessage.error('Se ha producido un error al guardar');
        })
        .finally(() => {
          loadingSaveDialogForm.value = false;
        });
    };

    const saveEditDataForm = () => {
      loadingSaveDialogForm.value = true;
      baseResource.update(modelBase.id, modelBase)
        .then(() => {
          ElMessage.success('Datos actualizados correctamente');
          resetModelBase();
          visibleDialogForm.value = false;
          fetchBases();
        })
        .catch((error) => {
          console.error('Error updating data:', error);
          ElMessage.error('Se ha producido un error al actualizar');
        })
        .finally(() => {
          loadingSaveDialogForm.value = false;
        });
    };

    const accionEliminarRegistro = (id, nombre) => {
      ElMessageBox.confirm(
        `Seguro que desea eliminar el registro <em>${nombre}</em>?`,
        'Atención',
        {
          confirmButtonText: 'Si, eliminar',
          cancelButtonText: 'Cancelar',
          type: 'warning',
          dangerouslyUseHTMLString: true
        }
      )
        .then(() => {
          baseResource.destroy(id)
            .then(() => {
              ElMessage.success('Registro eliminado');
              fetchBases();
            })
            .catch((error) => {
              console.error('Error deleting data:', error);
              ElMessage.error('Se ha producido un error al eliminar');
            });
        })
        .catch(() => {
          ElMessage.info('Operación cancelada');
        });
    };

    const accionDesactivarRegistro = (id, nombre) => {

      ElMessageBox.confirm(
        `Seguro que desea Desactivar el registro <em>${nombre}</em>?`,
        'Atención',
        {
          top: '5vh',
          icon: markRaw(Delete),
          confirmButtonText: 'Si, desactivar',
          cancelButtonText: 'Cancelar',
          type: 'warning',
          dangerouslyUseHTMLString: true
        }
      )
        .then(() => {
          baseResource.inactivar(id)
            .then(() => {
              ElMessage.success('Registro desactivado');
              fetchBases();
            })
            .catch((error) => {
              console.error('Error desactivating data:', error);
              ElMessage.error('Se ha producido un error al desactivar');
            });
        })
        .catch(() => {
          ElMessage.info('Operación cancelada');
        });
    };

    const accionActivarRegistro = (id, nombre) => {

      ElMessageBox.confirm(
        `Seguro que desea Activar el registro <em>${nombre}</em>?`,
        'Atención',
        {
          top: '5vh',
          icon: markRaw(Check),
          confirmButtonText: 'Si, activar',
          cancelButtonText: 'Cancelar',
          type: 'warning',
          dangerouslyUseHTMLString: true
        }
      )
        .then(() => {
          baseResource.activar(id)
            .then(() => {
              ElMessage.success('Registro activado');
              fetchBases();
            })
            .catch((error) => {
              console.error('Error activating data:', error);
              ElMessage.error('Se ha producido un error al activar');
            });
        })
        .catch(() => {
          ElMessage.info('Operación cancelada');
        });
    };

    const resetFormCreateEdit = () => {
      formCreateEditBase.value?.resetFields();
      nextTick(() => {
        descripcionField.value?.focus();
      });
    };


    const handleBuscarDatos = () => {
      listQuery.page = 1;
      fetchBases();
    };

    const handleCurrentChange = (val) => {
      listQuery.page = val;
      fetchBases();
    };

    const fetchBases = () => {
      listLoading.value = true;
      baseResource.list(listQuery)
        .then(response => {
          tableData.value = response.data;
          meta.value = response.meta;
        })
        .catch(error => {
          console.error('Error fetching data:', error);
          ElMessage.error('Error al obtener datos');
        })
        .finally(() => {
          listLoading.value = false;
        });
    };

    const fetchSectors = async () => {
      await sector_zonaResource.list(listQuery)
        .then(response => {
          const { data } = response
          optionsSectors.value = data;
        })
        .catch(error => {
          console.error('Error fetching data:', error);
          ElMessage.error('Error al obtener datos');
        });
    };

    onMounted(fetchBases);

    return {
      authStore,
      modelBase,
      reglasValidacion, // reglas de validacion
      tableData,
      meta,
      listLoading,
      listQuery,
      visibleDialogForm,
      titleDialogForm,
      loadingSaveDialogForm,
      formCreateEditBase,
      descripcionField,
      openFormCreate,
      openFormEditar,
      saveFormCreateEdit,
      accionEliminarRegistro,
      resetFormCreateEdit,
      handleBuscarDatos,
      handleCurrentChange,
      accionActivarRegistro,
      accionDesactivarRegistro,
      optionsSectors,

      Search,
      Plus,
      Edit,
      Delete,

    };
  }
};
</script>
