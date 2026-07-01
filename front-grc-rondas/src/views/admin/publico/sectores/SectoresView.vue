<template>
  <div>
    <div class="el-header">
      <h3>Lista de Sectores</h3>
      <el-button
        v-if="isSuperAdministrador"
        type="danger"
        plain
        :icon="Delete"
        style="margin-left: 10px"
        @click="accionEliminarTodoRegistros"
      >
        Eliminar Todo
      </el-button>
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
    <el-row :gutter="12" style="margin-bottom: 12px;">
      <el-col :xs="24" :sm="8">
        <el-select v-model="listQuery.region_id" placeholder="Filtrar por Región" clearable filterable style="width: 100%" @change="handleRegionListFilterChange">
          <el-option v-for="item in listOptionsRegiones" :key="item.id" :label="item.descripcion" :value="item.id" />
        </el-select>
      </el-col>
      <el-col :xs="24" :sm="8">
        <el-select v-model="listQuery.provincia_id" placeholder="Filtrar por Provincia" clearable filterable style="width: 100%" :disabled="!listQuery.region_id" @change="handleProvinciaListFilterChange">
          <el-option v-for="item in listOptionsProvincias" :key="item.id" :label="item.descripcion" :value="item.id" />
        </el-select>
      </el-col>
      <el-col :xs="24" :sm="8">
        <el-select v-model="listQuery.distrito_id" placeholder="Filtrar por Distrito" clearable filterable style="width: 100%" :disabled="!listQuery.provincia_id" @change="applySectorListFilters">
          <el-option v-for="item in listOptionsDistritos" :key="item.id" :label="item.descripcion" :value="item.id" />
        </el-select>
      </el-col>
    </el-row>
    <el-table border fit :data="tableData" class="py-4" v-loading="listLoading">
      <el-table-column label="ID" prop="id" align="center" width="70" />
      <el-table-column label="Sector" prop="descripcion" />
      <el-table-column label="Distrito" prop="distrito.descripcion" />
      <el-table-column label="Provincia" prop="distrito.provincia.descripcion" />
      <el-table-column label="Region" prop="distrito.provincia.region.descripcion" />
      <el-table-column prop="estado" label="Estado">
        <template #default="scope">
          <el-tag v-if="scope.row.estado" type="success">Activo</el-tag>
          <el-tag v-else type="danger">Inactivo</el-tag>
        </template>
      </el-table-column>
      <el-table-column align="right" width="150">
        <template #header>
          <el-button type="primary" class="ache-background-color-template" :icon="Plus" @click="openFormCreate" v-if="!isActionDisabled('pub.sectores.crear')">
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
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="openFormEditar(scope.row.id)" v-if="!isActionDisabled('pub.sectores.actualizar')" color="#E3CB2DFF"><Edit /></el-icon>
          </el-tooltip>
          <el-tooltip
              class="box-item"
              effect="dark"
              content="Eliminar"
              placement="top-start"
              v-if="isSuperAdministrador"
          >
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="accionEliminarRegistro(scope.row.id, scope.row.descripcion)" color="#d03050"><Delete /></el-icon>
          </el-tooltip>
          <slot v-if="!isActionDisabled('pub.sectores.eliminar')">
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
        <el-form ref="formCreateEditSector" v-loading="loadingSaveDialogForm" :model="modelSector"
          :rules="reglasValidacion" status-icon label-width="120px" label-position="top" v-on:submit.prevent>
          <el-form-item label="Sector" prop="descripcion">
            <el-input ref="descripcionField" v-model="modelSector.descripcion" type="text" autocomplete="off"
              placeholder="Sector" />
          </el-form-item>
          <el-form-item label="Región" prop="region_id">
            <el-select v-model="modelSector.region_id" placeholder="Región" style="width: 100%" filterable @change="fetchProvinciasByRegion">
              <el-option
                  v-for="item in optionsRegiones"
                  :key="item.id"
                  :label="item.descripcion"
                  :value="item.id"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="Provincia" prop="provincia_id">
            <el-select v-model="modelSector.provincia_id" placeholder="Provincia" style="width: 100%" filterable :disabled="!modelSector.region_id" @change="fetchDistritosByProvincia">
              <el-option
                  v-for="item in optionsProvincias"
                  :key="item.id"
                  :label="item.descripcion"
                  :value="item.id"
              />
            </el-select>
          </el-form-item>
          <el-form-item label="Distrito" prop="distrito_id">
            <el-select ref="distrito_idField" v-model="modelSector.distrito_id" placeholder="Distrito" style="width: 100%" filterable :disabled="!modelSector.provincia_id">
              <el-option
                  v-for="item in optionsDistritos"
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
          <el-button type="primary" @click="saveFormCreateEdit('formCreateSector')">
            Guardar
          </el-button>
          <el-button type="danger" @click="resetFormCreateEdit('formCreateSector')">Resetear</el-button>
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
import {ref, reactive, onMounted, nextTick, markRaw, computed} from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import {Search, Plus, Edit, Delete, Check, Close} from '@element-plus/icons-vue';

import { useAuthStore } from "@/stores/AuthStore";
import SectorResource from '@/api/publico/sector';
import {isActionDisabled} from "@/utils/utils.js";
import DistritoResource from "@/api/publico/distrito";
import RegionResource from "@/api/publico/region";
import ProvinciaResource from "@/api/publico/provincia";


export default {
  name: 'SectoresView',
  components: {Edit, Delete, Close, Check},
  methods: {isActionDisabled},
  setup() {
    const regionResource = new RegionResource();
    const provinciaResource = new ProvinciaResource();
    const distritoResource = new DistritoResource();
    const sectorResource = new SectorResource();
    const authStore = useAuthStore()
    const isSuperAdministrador = computed(() => {
      const roles = Array.isArray(authStore.roles) ? authStore.roles : [];

      return roles.some((role) => {
        const roleName = typeof role === 'string'
          ? role
          : (role?.name || role?.descripcion || '');

        return roleName
          .toString()
          .trim()
          .toLowerCase()
          .replace(/\s+/g, '') === 'superadministrador';
      });
    });

    const formCreateEditSector = ref(null);
    const descripcionField = ref(null);

    const modelSector = reactive({
      id: undefined,
      descripcion: '',
      region_id: '',
      provincia_id: '',
      distrito_id: '',
    });
    const reglasValidacion = {
      descripcion: [
        { required: true, message: 'El campo es requerido', trigger: 'blur' }
      ],
      region_id: [
        { required: true, message: 'El campo es requerido', trigger: 'change' }
      ],
      provincia_id: [
        { required: true, message: 'El campo es requerido', trigger: 'change' }
      ],
      distrito_id: [
        { required: true, message: 'El campo es requerido', trigger: 'change' }
      ],
    };


    const tableData = ref([]);
    const meta = ref({});
    const listLoading = ref(true);

    const listQuery = reactive({
      page: 1,
      limit: 8,
      keyword: '',
      region_id: null,
      provincia_id: null,
      distrito_id: null,
    });

    const visibleDialogForm = ref(false);
    const titleDialogForm = ref('');
    const loadingSaveDialogForm = ref(false);
    const optionsRegiones = ref([]);
    const optionsProvincias = ref([]);
    const optionsDistritos = ref([]);
    const listOptionsRegiones = ref([]);
    const listOptionsProvincias = ref([]);
    const listOptionsDistritos = ref([]);

    const openFormCreate = async () => {
      resetModelSector();
      titleDialogForm.value = 'Registrar Sector';
      visibleDialogForm.value = true;
      await fetchRegiones();
      nextTick(() => {
        formCreateEditSector.value?.clearValidate();
        descripcionField.value?.focus();
      });
    };

    const openFormEditar = async (id) => {
      titleDialogForm.value = 'Editar Sector';
      visibleDialogForm.value = true;
      loadingSaveDialogForm.value = true;
      try {
        const { data } = await sectorResource.get(id);
        await fetchRegiones();

        const regionId = data.region_id || data.region?.id || data.distrito?.provincia?.region?.id || '';
        const provinciaId = data.provincia_id || data.provincia?.id || data.distrito?.provincia?.id || '';

        Object.assign(modelSector, {
          id: data.id,
          descripcion: data.descripcion,
          region_id: regionId,
          provincia_id: provinciaId,
          distrito_id: data.distrito_id || data.distrito?.id,
        });

        if (modelSector.region_id) {
          await fetchProvinciasByRegion(modelSector.region_id);
          if (modelSector.provincia_id) {
            await fetchDistritosByProvincia(modelSector.provincia_id);
          }
        }

        nextTick(() => {
          formCreateEditSector.value?.clearValidate();
          descripcionField.value?.focus();
        });
      } catch (error) {
        visibleDialogForm.value = false;
        ElMessage.info('Error al obtener datos');
      } finally {
        loadingSaveDialogForm.value = false;
      }
    };

    const resetModelSector = () => {
      Object.assign(modelSector, {
        id: undefined,
        descripcion: '',
        region_id: '',
        provincia_id: '',
        distrito_id: '',
      });
      optionsProvincias.value = [];
      optionsDistritos.value = [];
    };

    const saveFormCreateEdit = () => {
      formCreateEditSector.value?.validate((valid) => {
        if (valid) {
          if (modelSector.id === undefined) {
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
      const payload = {
        descripcion: modelSector.descripcion,
        distrito_id: modelSector.distrito_id,
      };
      sectorResource.store(payload)
        .then(() => {
          ElMessage.success('Datos guardados correctamente');
          resetModelSector();
          visibleDialogForm.value = false;
          fetchSectors();
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
      const payload = {
        descripcion: modelSector.descripcion,
        distrito_id: modelSector.distrito_id,
      };
      sectorResource.update(modelSector.id, payload)
        .then(() => {
          ElMessage.success('Datos actualizados correctamente');
          resetModelSector();
          visibleDialogForm.value = false;
          fetchSectors();
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
          sectorResource.destroy(id)
            .then(() => {
              ElMessage.success('Registro eliminado');
              fetchSectors();
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

    const accionEliminarTodoRegistros = () => {
      ElMessageBox.confirm(
        '¿Seguro que desea eliminar todos los sectores encontrados con los filtros actuales?',
        'Atención',
        {
          top: '5vh',
          icon: markRaw(Delete),
          confirmButtonText: 'Si, eliminar todo',
          cancelButtonText: 'Cancelar',
          type: 'warning',
        }
      )
        .then(() => {
          sectorResource.eliminarMasivo({ ...listQuery })
            .then((response) => {
              const eliminados = response?.deleted_count ?? response?.data?.deleted_count ?? 0;
              ElMessage.success(`Sectores eliminados: ${eliminados}`);
              fetchSectors();
            })
            .catch((error) => {
              ElMessage.error(error.response?.data?.message || 'Se ha producido un error al eliminar sectores');
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
          sectorResource.inactivar(id)
            .then(() => {
              ElMessage.success('Registro desactivado');
              fetchSectors();
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
          sectorResource.activar(id)
            .then(() => {
              ElMessage.success('Registro activado');
              fetchSectors();
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
      formCreateEditSector.value?.resetFields();
      nextTick(() => {
        descripcionField.value?.focus();
      });
    };


    const handleBuscarDatos = () => {
      listQuery.page = 1;
      fetchSectors();
    };

    const applySectorListFilters = () => {
      listQuery.page = 1;
      fetchSectors();
    };

    const loadListFilterRegiones = async () => {
      try {
        const { data } = await regionResource.list();
        listOptionsRegiones.value = data || [];
      } catch (error) {
        console.error('Error al cargar regiones filtro:', error);
      }
    };

    const handleRegionListFilterChange = async (regionId) => {
      listQuery.provincia_id = null;
      listQuery.distrito_id = null;
      listOptionsDistritos.value = [];

      if (!regionId) {
        listOptionsProvincias.value = [];
        applySectorListFilters();
        return;
      }

      try {
        const response = await provinciaResource.getProvincias(regionId);
        listOptionsProvincias.value = response.data || response || [];
      } catch (error) {
        console.error('Error al cargar provincias filtro:', error);
      }

      applySectorListFilters();
    };

    const handleProvinciaListFilterChange = async (provinciaId) => {
      listQuery.distrito_id = null;

      if (!provinciaId) {
        listOptionsDistritos.value = [];
        applySectorListFilters();
        return;
      }

      try {
        const response = await distritoResource.getDistritos(provinciaId);
        listOptionsDistritos.value = response.data || response || [];
      } catch (error) {
        console.error('Error al cargar distritos filtro:', error);
      }

      applySectorListFilters();
    };

    const handleCurrentChange = (val) => {
      listQuery.page = val;
      fetchSectors();
    };

    const fetchSectors = () => {
      listLoading.value = true;
      sectorResource.list(listQuery)
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

    const fetchRegiones = async () => {
      try {
        const { data } = await regionResource.list();
        optionsRegiones.value = data || [];
      } catch (error) {
        console.error('Error fetching regiones:', error);
        ElMessage.error('Error al obtener regiones');
      }
    };

    const fetchProvinciasByRegion = async (regionId) => {
      modelSector.provincia_id = '';
      modelSector.distrito_id = '';
      optionsDistritos.value = [];

      if (!regionId) {
        optionsProvincias.value = [];
        return;
      }

      try {
        const response = await provinciaResource.getProvincias(regionId);
        optionsProvincias.value = response.data || response || [];
      } catch (error) {
        console.error('Error fetching provincias:', error);
        ElMessage.error('Error al obtener provincias');
      }
    };

    const fetchDistritosByProvincia = async (provinciaId) => {
      modelSector.distrito_id = '';

      if (!provinciaId) {
        optionsDistritos.value = [];
        return;
      }

      try {
        const response = await distritoResource.getDistritos(provinciaId);
        optionsDistritos.value = response.data || response || [];
      } catch (error) {
        console.error('Error fetching distritos:', error);
        ElMessage.error('Error al obtener distritos');
      }
    };

    onMounted(() => {
      fetchSectors();
      loadListFilterRegiones();
    });

    return {
      authStore,
      modelSector,
      reglasValidacion,
      tableData,
      meta,
      listLoading,
      listQuery,
      visibleDialogForm,
      titleDialogForm,
      loadingSaveDialogForm,
      formCreateEditSector,
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
      accionEliminarTodoRegistros,
      isSuperAdministrador,
      listOptionsRegiones,
      listOptionsProvincias,
      listOptionsDistritos,
      handleRegionListFilterChange,
      handleProvinciaListFilterChange,
      applySectorListFilters,
      fetchProvinciasByRegion,
      fetchDistritosByProvincia,
      optionsRegiones,
      optionsProvincias,
      optionsDistritos,

      Search,
      Plus,
      Edit,
      Delete,

    };
  }
};
</script>