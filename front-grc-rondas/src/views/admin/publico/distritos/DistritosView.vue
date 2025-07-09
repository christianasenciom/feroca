<template>
  <div>
    <div class="el-header">
      <h3>Lista de Distritos</h3>
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
      <el-table-column label="Distrito" prop="descripcion" />
      <el-table-column label="Provincia" prop="provincia.descripcion" />
      <el-table-column label="Region" prop="provincia.region.descripcion" />
      <el-table-column prop="estado" label="Estado">
        <template #default="scope">
          <!--          {{ scope.row.estado ? 'Activo' : 'Inactivo' }}-->
          <el-tag v-if="scope.row.estado" type="success">Activo</el-tag>
          <el-tag v-else type="danger">Inactivo</el-tag>
        </template>
      </el-table-column>
      <el-table-column align="right" width="120">
        <template #header>
          <el-button type="primary" class="ache-background-color-template" :icon="Plus" @click="openFormCreate" v-if="!isActionDisabled('pub.distritos.crear')">
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
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="openFormEditar(scope.row.id)" v-if="!isActionDisabled('pub.distritos.actualizar')" color="#E3CB2DFF"><Edit /></el-icon>
          </el-tooltip>
          <slot v-if="!isActionDisabled('pub.distritos.eliminar')">
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
        <el-form ref="formCreateEditDistrito" v-loading="loadingSaveDialogForm" :model="modelDistrito"
          :rules="reglasValidacion" status-icon label-width="120px" label-position="top" v-on:submit.prevent>
          <el-form-item label="Distrito" prop="descripcion">
            <el-input ref="descripcionField" v-model="modelDistrito.descripcion" type="text" autocomplete="off"
              placeholder="Distrito" />
          </el-form-item>
          <el-form-item label="Provincia" prop="provincia_id">
            <el-select ref="provincia_idField" v-model="modelDistrito.provincia_id" placeholder="Provincia" style="width: 100%" searchable>
              <el-option
                  v-for="item in optionsProvincias"
                  :key="item.id"
                  :label="item.descripcion"
                  :value="item.id"
              >
              </el-option>
            </el-select>
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="saveFormCreateEdit('formCreateDistrito')">
            Guardar
          </el-button>
          <el-button type="danger" @click="resetFormCreateEdit('formCreateDistrito')">Resetear</el-button>
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
import DistritoResource from '@/api/publico/distrito';
import {isActionDisabled} from "@/utils/utils.js";
import ProvinciaResource from "@/api/publico/provincia";


export default {
  name: 'ProvinciasView',
  components: {Edit, Close, Check, ArrowDown},
  methods: {isActionDisabled},
  setup() {
    const provinciaResource = new ProvinciaResource();
    const distritoResource = new DistritoResource();
    const authStore = useAuthStore()

    const formCreateEditDistrito = ref(null);
    const descripcionField = ref(null);

    const modelDistrito = reactive({
      id: undefined,
      descripcion: '',
      provincia_id: '',
    });
    const reglasValidacion = {
      descripcion: [
        { required: true, message: 'El campo es requerido', trigger: 'blur' }
      ],
      provincia_id: [
        { required: true, message: 'El campo es requerido', trigger: 'blur' }
      ],
    };


    const tableData = ref([]);
    const meta = ref({});
    const listLoading = ref(true);

    const listQuery = reactive({
      page: 1,
      limit: 10,
      keyword: ''
    });

    const visibleDialogForm = ref(false);
    const titleDialogForm = ref('');
    const loadingSaveDialogForm = ref(false);
    const optionsProvincias = ref([]);

    const openFormCreate = () => {
      resetModelDistrito();
      titleDialogForm.value = 'Registrar Distrito';
      visibleDialogForm.value = true;
      nextTick(() => {
        fetchProvincias(); //cargar provinciaes
        formCreateEditDistrito.value?.clearValidate();
        descripcionField.value?.focus();
      });
    };

    const openFormEditar = async (id) => {
      await fetchProvincias(); //cargar provinciaes
      titleDialogForm.value = 'Editar Distrito';
      visibleDialogForm.value = true;
      loadingSaveDialogForm.value = true;
      try {
        const { data } = await distritoResource.get(id);
        Object.assign(modelDistrito, {
          id: data.id,
          descripcion: data.descripcion,
          provincia_id: data.provincia.id,
        });
        nextTick(() => {
          formCreateEditDistrito.value?.clearValidate();
          descripcionField.value?.focus();
        });
      } catch (error) {
        visibleDialogForm.value = false;
        ElMessage.info('Error al obtener datos');
      } finally {
        loadingSaveDialogForm.value = false;
      }
    };

    const resetModelDistrito = () => {
      Object.assign(modelDistrito, {
        id: undefined,
        descripcion: '',
        provincia_id: '',
      });
    };

    const saveFormCreateEdit = () => {
      formCreateEditDistrito.value?.validate((valid) => {
        if (valid) {
          if (modelDistrito.id === undefined) {
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
      distritoResource.store(modelDistrito)
        .then(() => {
          ElMessage.success('Datos guardados correctamente');
          resetModelDistrito();
          visibleDialogForm.value = false;
          fetchDistritos();
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
      distritoResource.update(modelDistrito.id, modelDistrito)
        .then(() => {
          ElMessage.success('Datos actualizados correctamente');
          resetModelDistrito();
          visibleDialogForm.value = false;
          fetchDistritos();
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
          distritoResource.destroy(id)
            .then(() => {
              ElMessage.success('Registro eliminado');
              fetchDistritos();
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
          distritoResource.inactivar(id)
            .then(() => {
              ElMessage.success('Registro desactivado');
              fetchDistritos();
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
          distritoResource.activar(id)
            .then(() => {
              ElMessage.success('Registro activado');
              fetchDistritos();
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
      formCreateEditDistrito.value?.resetFields();
      nextTick(() => {
        descripcionField.value?.focus();
      });
    };


    const handleBuscarDatos = () => {
      listQuery.page = 1;
      fetchDistritos();
    };

    const handleCurrentChange = (val) => {
      listQuery.page = val;
      fetchDistritos();
    };

    const fetchDistritos = () => {
      listLoading.value = true;
      distritoResource.list(listQuery)
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

    const fetchProvincias = async () => {
      await provinciaResource.list(listQuery)
        .then(response => {
          const { data } = response
          optionsProvincias.value = data;
        })
        .catch(error => {
          console.error('Error fetching data:', error);
          ElMessage.error('Error al obtener datos');
        });
    };

    onMounted(fetchDistritos);

    return {
      authStore,
      modelDistrito,
      reglasValidacion, // reglas de validacion
      tableData,
      meta,
      listLoading,
      listQuery,
      visibleDialogForm,
      titleDialogForm,
      loadingSaveDialogForm,
      formCreateEditDistrito,
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
      optionsProvincias,

      Search,
      Plus,
      Edit,
      Delete,

    };
  }
};
</script>
