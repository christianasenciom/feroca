<template>
  <div>
    <div class="el-header">
      <h3>Lista de Bases</h3>
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
      <el-table-column label="Base" prop="descripcion" />
      <el-table-column label="Sector" prop="sector_zona.descripcion"  width="240"/>
      <el-table-column label="Distrito" prop="sector_zona.distrito.descripcion" />
      <el-table-column label="Provincia" prop="sector_zona.distrito.provincia.descripcion" />
      <el-table-column label="Region" prop="sector_zona.distrito.provincia.region.descripcion" />
      <el-table-column prop="estado" label="Estado">
        <template #default="scope">
          <!--          {{ scope.row.estado ? 'Activo' : 'Inactivo' }}-->
          <el-tag v-if="scope.row.estado" type="success">Activo</el-tag>
          <el-tag v-else type="danger">Inactivo</el-tag>
        </template>
      </el-table-column>
      <el-table-column align="right" width="120">
        <template #header>
          <el-button type="primary" class="ache-background-color-template" :icon="Plus" @click="openFormCreate" v-if="!isActionDisabled('pub.bases.crear')">
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
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="openFormEditar(scope.row.id)" v-if="!isActionDisabled('pub.bases.actualizar')" color="#E3CB2DFF"><Edit /></el-icon>
          </el-tooltip>
          <slot v-if="!isActionDisabled('pub.bases.eliminar')">
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
        <el-form ref="formCreateEditBase" v-loading="loadingSaveDialogForm" :model="modelBase"
          :rules="reglasValidacion" status-icon label-width="120px" label-position="top" v-on:submit.prevent>
          <el-form-item label="Base" prop="descripcion">
            <el-input ref="descripcionField" v-model="modelBase.descripcion" type="text" autocomplete="off"
              placeholder="Base" />
          </el-form-item>
          <el-form-item label="Sector" prop="sector_zona_id">
            <el-select ref="sector_zona_idField" v-model="modelBase.sector_zona_id" placeholder="Sector" style="width: 100%" searchable>
              <el-option
                  v-for="item in optionsSectors"
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
          <el-button type="primary" @click="saveFormCreateEdit('formCreateBase')">
            Guardar
          </el-button>
          <el-button type="danger" @click="resetFormCreateEdit('formCreateBase')">Resetear</el-button>
        </div>
      </template>
    </el-dialog>

    <el-dialog v-model="visibleDialogFormEdit" :close-on-click-modal="false" top="7vh" width="500" :title="titleDialogForm">
      <div>
        <el-form ref="formCreateEditBase" v-loading="loadingSaveDialogForm" :model="modelBase"
          :rules="reglasValidacion" status-icon label-width="120px" label-position="top" v-on:submit.prevent>
          <el-form-item label="Base" prop="descripcion">
            <el-input ref="descripcionField" v-model="modelBase.descripcion" type="text" autocomplete="off"
              placeholder="Base" />
          </el-form-item>
          <el-form-item label="Sector" prop="sector_zona_id">
            <el-select ref="sector_zona_idField" v-model="modelBase.sector_zona_id" placeholder="Sector" style="width: 100%" searchable>
              <el-option
                  v-for="item in optionsSectors"
                  :key="item.id"
                  :label="item.descripcion"
                  :value="item.id"
              >
              </el-option>
            </el-select>
          </el-form-item>
          <el-form-item label="Ronderos" prop="admin_id">
            <el-select v-model="modelBase.admin_id" placeholder="Ronderos" style="width: 100%" searchable>
              <el-option
                  v-for="item in detalleRonderos"
                  :key="item.rondero_id"
                  :label="item.nombres + ' ' + item.apellido_paterno + ' ' + item.apellido_materno"
                  :value="item.persona_id"
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
import {Search, Plus, Edit, Delete, ArrowDown, Check, Close, List} from '@element-plus/icons-vue';
import { useAuthStore } from "@/stores/AuthStore";
import BaseResource from '@/api/publico/base';
import {isActionDisabled} from "@/utils/utils.js";
import SectorResource from "@/api/publico/sector";
import {calcularAnchoDialog} from "@/utils/responsive.js";
import AsignarAdmin from './components/AsignarAdmin.vue';


export default {
  name: 'BasesView',
  components: {AsignarAdmin, Edit, Close, Check, ArrowDown, List},
  methods: {isActionDisabled, calcularAnchoDialog},
  setup() {
    const sector_zonaResource = new SectorResource();
    const baseResource = new BaseResource();
    const authStore = useAuthStore()
    const detalleRonderos = ref([])
    const formCreateEditBase = ref(null);
    const formAsignarAdmin = ref(null);
    const descripcionField = ref(null);
    const id_base = ref(0);
    const modelBase = reactive({
      id: undefined,
      descripcion: '',
      sector_zona_id: '',
      admin_id: 0,
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
    const visibleDialogFormEdit = ref(false);
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
      await getRonderos(id);
      titleDialogForm.value = 'Editar Base';
      visibleDialogFormEdit.value = true;
      loadingSaveDialogForm.value = true;
      try {
        const { data } = await baseResource.get(id);
        Object.assign(modelBase, {
          id: data.id,
          descripcion: data.descripcion,
          sector_zona_id: data.sector_zona.id,
          admin_id: data.admin_id,
        });
        nextTick(() => {
          formCreateEditBase.value?.clearValidate();
          descripcionField.value?.focus();
        });
      } catch (error) {
        visibleDialogFormEdit.value = false;
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
          visibleDialogFormEdit.value = false;
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
    
  const keywordByBase = reactive({
  keyword: '',
  limit: 8,
  page: 1,
  orderby: 'ASC',
  ids_excluir: [],
  base_id: 0,
});
    

  const getRonderos =  async (id)  => {

  detalleRonderos.value = []
  keywordByBase.base_id = id
  baseResource.getRonderosByBase(keywordByBase)
      .then(response => {
        const {data} = response;
        if(response.data.length === 0) {
          ElMessage({
            message: 'No hay ronderos en esta base',
            type: 'warning'
          })
          return;
        }
        data.forEach(item => {
          let rowRondero = {
            id: item.id,
            rondero_id: item.id,
            persona_id: item.persona.id,
            docIdentidad: item.persona.docIdentidad,
            apellido_paterno: item.persona.apellido_paterno,
            apellido_materno: item.persona.apellido_materno,
            nombres: item.persona.nombres,
          };
          detalleRonderos.value.push(rowRondero);
        });
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
  }

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
      visibleDialogFormEdit,
      titleDialogForm,
      loadingSaveDialogForm,
      formCreateEditBase,
      formAsignarAdmin,
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
      detalleRonderos,

      Search,
      Plus,
      Edit,
      Delete,

    };
  }
};
</script>
