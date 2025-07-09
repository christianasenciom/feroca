<template>
  <div>
    <div class="el-header">
      <h3>Lista de tipos de conflictos</h3>
      <div class="filter-container" style="float: right">
        <el-input v-model="listQuery.keyword" placeholder="Buscar" class="input-with-select" clearable
          style="max-width: 600px" @clear="handleBuscarDatos" @keyup.enter="handleBuscarDatos">
          <template #append>
            <el-button type="primary" :icon="Search" @click="handleBuscarDatos">
              <span style="vertical-align: middle"> Buscar </span>
            </el-button>
          </template>
        </el-input>
      </div>
    </div>
    <el-table border fit :data="tableData" class="py-4" v-loading="listLoading">
      <el-table-column label="ID" prop="id" align="center" width="70" />
      <el-table-column label="Tipo de Conflicto" prop="descripcion" />
      <el-table-column prop="estado" label="Estado">
        <template #default="scope">
          <!--          {{ scope.row.estado ? 'Activo' : 'Inactivo' }}-->
          <el-tag v-if="scope.row.estado" type="success">Activo</el-tag>
          <el-tag v-else type="danger">Inactivo</el-tag>
        </template>
      </el-table-column>
      <el-table-column align="right" width="120">
        <template #header>
          <el-button type="primary" class="ache-background-color-template" :icon="Plus" @click="openFormCreate" v-if="!isActionDisabled('pub.cargos.crear')">
            Agregar
          </el-button>
        </template>
        <template #default="scope">
          <el-tooltip
              class="box-item"
              effect="dark"
              content="Editar"
              placement="top-start"
          >
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="openFormEditar(scope.row.id)" v-if="!isActionDisabled('pub.cargos.actualizar')" color="#E3CB2DFF"><Edit /></el-icon>
          </el-tooltip>
          <slot v-if="!isActionDisabled('pub.cargos.eliminar')">
            <el-tooltip
                class="box-item"
                effect="dark"
                content="Desactivar"
                placement="top-start"
                v-if="scope.row.estado"
            >
              <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="accionDesactivarRegistro(scope.row.id, scope.row.descripcion)" color="red"><Close /></el-icon>
            </el-tooltip>
            <el-tooltip
                class="box-item"
                effect="dark"
                content="Activar"
                placement="top-start"
                v-else
            >
              <el-icon  size="20" style="margin-right: 10px; cursor: pointer;" @click="accionActivarRegistro(scope.row.id, scope.row.descripcion)" color="green"><Check /></el-icon>
            </el-tooltip>
          </slot>
        </template>
      </el-table-column>
    </el-table>
    <div class="paging">
      <el-pagination v-if="meta.total > listQuery.limit" background v-model:currentPage="meta.current_page"
        layout="total, prev, next, pager" :total="meta.total" @current-change="handleCurrentChange"
        :page-size="meta.per_page" />
    </div>

    <!-- Dialogo para editar o crear estado TUC -->
    <el-dialog v-model="visibleDialogForm" :close-on-click-modal="false" top="7vh" width="500" :title="titleDialogForm">
      <div>
        <el-form ref="formCreateEditConflicto" v-loading="loadingSaveDialogForm" :model="modelConflicto"
          :rules="reglasValidacion" status-icon label-width="120px" label-position="top" v-on:submit.prevent>
          <el-form-item label="Descripción" prop="descripcion">
            <el-input ref="descripcionField" v-model="modelConflicto.descripcion" type="text" autocomplete="off"
              placeholder="Cargo" />
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="saveFormCreateEdit('formCreateConflicto')">
            Guardar
          </el-button>
          <el-button type="danger" @click="resetFormCreateEdit('formCreateConflicto')">Resetear</el-button>
        </div>
      </template>
    </el-dialog>

  </div>
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
import ConflictoResource from '@/api/publico/conflicto';
import {isActionDisabled} from "@/utils/utils.js";


export default {
  name: 'CargosView',
  components: {Edit, Close, Check},
  methods: {isActionDisabled},
  setup() {
    const conflictoResource = new ConflictoResource();
    const authStore = useAuthStore()

    const formCreateEditConflicto = ref(null);
    const descripcionField = ref(null);

    const modelConflicto = reactive({
      id: undefined,
      descripcion: '',
    });
    const reglasValidacion = {
      descripcion: [
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

    const openFormCreate = () => {
      resetModelRegion();
      titleDialogForm.value = 'Registrar tipo de conflicto';
      visibleDialogForm.value = true;
      nextTick(() => {
        formCreateEditConflicto.value?.clearValidate();
        descripcionField.value?.focus();
      });
    };

    const openFormEditar = async (id) => {
      titleDialogForm.value = 'Editar tipo de conflicto';
      visibleDialogForm.value = true;
      loadingSaveDialogForm.value = true;
      try {
        const { data } = await conflictoResource.get(id);
        Object.assign(modelConflicto, data);
        nextTick(() => {
          formCreateEditConflicto.value?.clearValidate();
          descripcionField.value?.focus();
        });
      } catch (error) {
        visibleDialogForm.value = false;
        ElMessage.info('Error al obtener datos');
      } finally {
        loadingSaveDialogForm.value = false;
      }
    };

    const resetModelRegion = () => {
      Object.assign(modelConflicto, {
        id: undefined,
        descripcion: '',
      });
    };

    const saveFormCreateEdit = () => {
      formCreateEditConflicto.value?.validate((valid) => {
        if (valid) {
          if (modelConflicto.id === undefined) {
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
      conflictoResource.store(modelConflicto)
        .then(() => {
          ElMessage.success('Datos guardados correctamente');
          resetModelRegion();
          visibleDialogForm.value = false;
          fetchConflicto();
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
      conflictoResource.update(modelConflicto.id, modelConflicto)
        .then(() => {
          ElMessage.success('Datos actualizados correctamente');
          resetModelRegion();
          visibleDialogForm.value = false;
          fetchConflicto();
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
          conflictoResource.destroy(id)
            .then(() => {
              ElMessage.success('Registro eliminado');
              fetchConflicto();
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
          conflictoResource.inactivar(id)
            .then(() => {
              ElMessage.success('Registro desactivado');
              fetchConflicto();
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
          conflictoResource.activar(id)
            .then(() => {
              ElMessage.success('Registro activado');
              fetchConflicto();
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
      formCreateEditConflicto.value?.resetFields();
      nextTick(() => {
        descripcionField.value?.focus();
      });
    };


    const handleBuscarDatos = () => {
      listQuery.page = 1;
      fetchConflicto();
    };

    const handleCurrentChange = (val) => {
      listQuery.page = val;
      fetchConflicto();
    };

    const fetchConflicto = () => {
      listLoading.value = true;
      conflictoResource.list(listQuery)
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

    onMounted(fetchConflicto);

    return {
      authStore,
      modelConflicto,
      reglasValidacion, // reglas de validacion
      tableData,
      meta,
      listLoading,
      listQuery,
      visibleDialogForm,
      titleDialogForm,
      loadingSaveDialogForm,
      formCreateEditConflicto,
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

      Search,
      Plus,
      Edit,
      Delete,

    };
  }
};
</script>
