<template>
  <div>
    <div class="el-header">
      <h3>Lista de cargos</h3>
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
      <el-table-column label="Región" prop="descripcion" />
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
        <el-form ref="formCreateEditCargo" v-loading="loadingSaveDialogForm" :model="modelCargo"
          :rules="reglasValidacion" status-icon label-width="120px" label-position="top" v-on:submit.prevent>
          <el-form-item label="Descripción" prop="descripcion">
            <el-input ref="descripcionField" v-model="modelCargo.descripcion" type="text" autocomplete="off"
              placeholder="Cargo" />
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="saveFormCreateEdit('formCreateCargo')">
            Guardar
          </el-button>
          <el-button type="danger" @click="resetFormCreateEdit('formCreateCargo')">Resetear</el-button>
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
import CargoResource from '@/api/publico/cargo';
import {isActionDisabled} from "@/utils/utils.js";


export default {
  name: 'CargosView',
  components: {Edit, Close, Check},
  methods: {isActionDisabled},
  setup() {
    const cargoResource = new CargoResource();
    const authStore = useAuthStore()

    const formCreateEditCargo = ref(null);
    const descripcionField = ref(null);

    const modelCargo = reactive({
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
      titleDialogForm.value = 'Registrar cargo';
      visibleDialogForm.value = true;
      nextTick(() => {
        formCreateEditCargo.value?.clearValidate();
        descripcionField.value?.focus();
      });
    };

    const openFormEditar = async (id) => {
      titleDialogForm.value = 'Editar cargo';
      visibleDialogForm.value = true;
      loadingSaveDialogForm.value = true;
      try {
        const { data } = await cargoResource.get(id);
        Object.assign(modelCargo, data);
        nextTick(() => {
          formCreateEditCargo.value?.clearValidate();
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
      Object.assign(modelCargo, {
        id: undefined,
        descripcion: '',
      });
    };

    const saveFormCreateEdit = () => {
      formCreateEditCargo.value?.validate((valid) => {
        if (valid) {
          if (modelCargo.id === undefined) {
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
      cargoResource.store(modelCargo)
        .then(() => {
          ElMessage.success('Datos guardados correctamente');
          resetModelRegion();
          visibleDialogForm.value = false;
          fetchCargo();
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
      cargoResource.update(modelCargo.id, modelCargo)
        .then(() => {
          ElMessage.success('Datos actualizados correctamente');
          resetModelRegion();
          visibleDialogForm.value = false;
          fetchCargo();
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
          cargoResource.destroy(id)
            .then(() => {
              ElMessage.success('Registro eliminado');
              fetchCargo();
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
          cargoResource.inactivar(id)
            .then(() => {
              ElMessage.success('Registro desactivado');
              fetchCargo();
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
          cargoResource.activar(id)
            .then(() => {
              ElMessage.success('Registro activado');
              fetchCargo();
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
      formCreateEditCargo.value?.resetFields();
      nextTick(() => {
        descripcionField.value?.focus();
      });
    };


    const handleBuscarDatos = () => {
      listQuery.page = 1;
      fetchCargo();
    };

    const handleCurrentChange = (val) => {
      listQuery.page = val;
      fetchCargo();
    };

    const fetchCargo = () => {
      listLoading.value = true;
      cargoResource.list(listQuery)
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

    onMounted(fetchCargo);

    return {
      authStore,
      modelCargo,
      reglasValidacion, // reglas de validacion
      tableData,
      meta,
      listLoading,
      listQuery,
      visibleDialogForm,
      titleDialogForm,
      loadingSaveDialogForm,
      formCreateEditCargo,
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
