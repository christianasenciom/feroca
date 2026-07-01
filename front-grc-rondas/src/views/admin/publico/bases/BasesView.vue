<template>
  <div class="bases-view-container">
    <div class="header-section">
      <h3>Lista de Bases</h3>
      <el-button
        v-if="isSuperAdministrador"
        type="danger"
        plain
        :icon="Delete"
        @click="accionEliminarTodoRegistros"
      >
        Eliminar Todo
      </el-button>
      <div class="filter-container">
        <el-input
          v-model="listQuery.keyword"
          placeholder="Buscar"
          class="search-input"
          clearable
          @clear="handleBuscarDatos"
          @keyup.enter="handleBuscarDatos"
        >
          <template #append>
            <el-button type="primary" :icon="Search" @click="handleBuscarDatos">
              Buscar
            </el-button>
          </template>
        </el-input>
      </div>
    </div>

    <el-row :gutter="12" style="margin-bottom: 14px;">
      <el-col :xs="24" :sm="12" :md="6">
        <el-select v-model="listQuery.region_id" placeholder="Filtrar por Región" clearable filterable style="width: 100%" @change="handleRegionFilterChange">
          <el-option v-for="item in listOptionsRegiones" :key="item.id" :label="item.descripcion" :value="item.id" />
        </el-select>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-select v-model="listQuery.provincia_id" placeholder="Filtrar por Provincia" clearable filterable style="width: 100%" :disabled="!listQuery.region_id" @change="handleProvinciaFilterChange">
          <el-option v-for="item in listOptionsProvincias" :key="item.id" :label="item.descripcion" :value="item.id" />
        </el-select>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-select v-model="listQuery.distrito_id" placeholder="Filtrar por Distrito" clearable filterable style="width: 100%" :disabled="!listQuery.provincia_id" @change="handleDistritoFilterChange">
          <el-option v-for="item in listOptionsDistritos" :key="item.id" :label="item.descripcion" :value="item.id" />
        </el-select>
      </el-col>
      <el-col :xs="24" :sm="12" :md="6">
        <el-select v-model="listQuery.sector_zona_id" placeholder="Filtrar por Sector" clearable filterable style="width: 100%" :disabled="!listQuery.distrito_id" @change="applyListFilters">
          <el-option label="Sin Sector" value="sin-sector" />
          <el-option v-for="item in listOptionsSectors" :key="item.id" :label="item.descripcion" :value="item.id" />
        </el-select>
      </el-col>
    </el-row>

    <!-- Tabla responsiva sin saltos de línea -->
    <div class="table-container">
      <el-table
        border
        fit
        :data="tableData"
        v-loading="listLoading"
        class="responsive-table"
        :row-style="{ whiteSpace: 'nowrap' }"
      >
        <el-table-column label="ID" prop="id" align="center" width="60" />
        <el-table-column label="Base" prop="nombre_descripcion" min-width="180" show-overflow-tooltip />
        <el-table-column label="Sector / Zona" min-width="160" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.sector?.descripcion || scope.row.sector_zona?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Distrito" min-width="140" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.distrito?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Provincia" min-width="140" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.provincia?.descripcion || scope.row.sector_zona?.distrito?.provincia?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Región" min-width="140" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.region?.descripcion || scope.row.sector_zona?.distrito?.provincia?.region?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Partida Registral" width="110" show-overflow-tooltip>
          <template #default="scope">
            {{ scope.row.numero_partida_registral || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Administrador" min-width="180" show-overflow-tooltip>
          <template #default="scope">
            <template v-if="scope.row.admin">
              {{ formatNombre(scope.row.admin.nombres, scope.row.admin.apellido_paterno, scope.row.admin.apellido_materno) }}
            </template>
            <template v-else>-</template>
          </template>
        </el-table-column>
        <el-table-column prop="estado" label="Estado" width="80" align="center">
          <template #default="scope">
            <el-tag v-if="scope.row.estado" type="success" size="small">Activo</el-tag>
            <el-tag v-else type="danger" size="small">Inactivo</el-tag>
          </template>
        </el-table-column>
        <el-table-column align="center" width="150" fixed="right">
          <template #header>
            <el-button type="primary" size="small" :icon="Plus" @click="openFormCreate" v-if="!isActionDisabled('pub.bases.crear')">
              Agregar
            </el-button>
          </template>
          <template #default="scope">
            <div class="action-buttons">
              <el-tooltip effect="dark" content="Editar" placement="top">
                <el-icon size="18" style="cursor: pointer; color: #E3CB2DFF;" @click="openFormEditar(scope.row.id)" v-if="!isActionDisabled('pub.bases.actualizar')">
                  <Edit />
                </el-icon>
              </el-tooltip>
              <el-tooltip v-if="isSuperAdministrador" effect="dark" content="Eliminar" placement="top">
                <el-icon size="18" style="cursor: pointer; color: #d03050;" @click="accionEliminarRegistro(scope.row.id, scope.row.nombre_descripcion)">
                  <Delete />
                </el-icon>
              </el-tooltip>
              <slot v-if="!isActionDisabled('pub.bases.eliminar')">
                <el-tooltip effect="dark" :content="scope.row.estado ? 'Desactivar' : 'Activar'" placement="top">
                  <el-icon size="18" style="cursor: pointer; margin-left: 12px;" :color="scope.row.estado ? 'red' : 'green'" @click="scope.row.estado ? accionDesactivarRegistro(scope.row.id, scope.row.nombre_descripcion) : accionActivarRegistro(scope.row.id, scope.row.nombre_descripcion)">
                    <Close v-if="scope.row.estado" />
                    <Check v-else />
                  </el-icon>
                </el-tooltip>
              </slot>
            </div>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <div class="paging">
      <el-pagination
        v-if="meta.total > listQuery.limit"
        background
        v-model:current-page="meta.current_page"
        layout="total, prev, pager, next"
        :total="meta.total"
        @current-change="handleCurrentChange"
        :page-size="meta.per_page"
      />
    </div>

    <!-- Diálogo responsivo -->
    <el-dialog
      v-model="visibleDialogForm"
      :close-on-click-modal="false"
      top="5vh"
      :width="dialogWidth"
      :fullscreen="isMobile"
      :title="titleDialogForm"
    >
      <el-form ref="formCreateEditBase" v-loading="loadingSaveDialogForm" :model="modelBase" :rules="reglasValidacion" status-icon label-position="top">
        <el-row :gutter="16">
          <el-col :xs="24" :sm="24" :md="24">
            <el-form-item label="Nombre/Descripción de la Base" prop="nombre_descripcion">
              <el-input ref="nombre_descripcionField" v-model="modelBase.nombre_descripcion" type="text" autocomplete="off" placeholder="Nombre o descripción de la base" />
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="24" :md="24">
            <el-form-item label="N° Partida Registral (8 dígitos)" prop="numero_partida_registral">
              <el-input v-model="modelBase.numero_partida_registral" placeholder="00000000" maxlength="8" @input="modelBase.numero_partida_registral = modelBase.numero_partida_registral.replace(/\D/g, '')" />
              <small class="text-muted">Opcional - 8 dígitos numéricos</small>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="12" :md="12">
            <el-form-item label="Región" prop="region_id">
              <el-select v-model="modelBase.region_id" placeholder="Seleccione Región" style="width: 100%" @change="fetchProvinciasByRegion" filterable>
                <el-option v-for="item in optionsRegiones" :key="item.id" :label="item.descripcion" :value="item.id" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="12" :md="12">
            <el-form-item label="Provincia" prop="provincia_id">
              <el-select v-model="modelBase.provincia_id" placeholder="Seleccione Provincia" style="width: 100%" :disabled="!modelBase.region_id" @change="fetchDistritosByProvincia" filterable>
                <el-option v-for="item in optionsProvincias" :key="item.id" :label="item.descripcion" :value="item.id" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="12" :md="12">
            <el-form-item label="Distrito" prop="distrito_id">
              <el-select v-model="modelBase.distrito_id" placeholder="Seleccione Distrito" style="width: 100%" :disabled="!modelBase.provincia_id" @change="fetchSectoresByDistrito" filterable>
                <el-option v-for="item in optionsDistritos" :key="item.id" :label="item.descripcion" :value="item.id" />
              </el-select>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="12" :md="12">
            <el-form-item label="Sector (Opcional)" prop="sector_zona_id">
              <el-select v-model="modelBase.sector_zona_id" placeholder="Seleccione Sector (Opcional)" style="width: 100%" :disabled="!modelBase.distrito_id" filterable clearable>
                <el-option v-for="item in optionsSectors" :key="item.id" :label="item.descripcion" :value="item.id" />
                <el-option label="Sin Sector (directamente en distrito)" :value="null" />
              </el-select>
              <small class="text-muted">La base puede estar directamente en un distrito o en un sector específico</small>
            </el-form-item>
          </el-col>

          <el-col :xs="24" :sm="24" :md="24">
            <el-form-item label="Administrador (Opcional)" prop="admin_id">
              <el-select v-model="modelBase.admin_id" placeholder="Seleccione administrador" style="width: 100%" filterable clearable>
                <el-option v-for="item in detalleRonderos" :key="item.rondero_id" :label="`${item.nombres} ${item.apellido_paterno} ${item.apellido_materno}`.trim()" :value="item.persona_id" />
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="saveFormCreateEdit">Guardar</el-button>
          <el-button type="danger" @click="resetFormCreateEdit">Resetear</el-button>
          <el-button @click="visibleDialogForm = false">Cancelar</el-button>
        </div>
      </template>
    </el-dialog>
  </div>
</template>

<style scoped>
.bases-view-container {
  padding: 16px;
}

.header-section {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 20px;
}

.header-section h3 {
  margin: 0;
  font-size: 18px;
}

.filter-container {
  flex-shrink: 0;
}

.search-input {
  width: 280px;
}

.table-container {
  width: 100%;
  overflow-x: auto;
}

.responsive-table {
  width: 100%;
  min-width: 800px;
}

.action-buttons {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
}

.paging {
  display: flex;
  justify-content: center;
  padding-top: 20px;
}

.text-muted {
  color: #666;
  font-size: 12px;
  display: block;
  margin-top: 4px;
}

.dialog-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

/* Responsive */
@media screen and (max-width: 768px) {
  .bases-view-container {
    padding: 12px;
  }

  .header-section {
    flex-direction: column;
    align-items: stretch;
  }

  .header-section h3 {
    text-align: center;
  }

  .search-input {
    width: 100%;
  }

  .filter-container {
    width: 100%;
  }

  .action-buttons {
    gap: 4px;
  }
}

@media screen and (max-width: 480px) {
  .bases-view-container {
    padding: 8px;
  }

  .header-section h3 {
    font-size: 16px;
  }

  .dialog-footer {
    justify-content: center;
  }

  .dialog-footer .el-button {
    flex: 1;
  }
}
</style>

<script>
import { ref, reactive, onMounted, nextTick, markRaw, computed } from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import { Search, Plus, Edit, Delete, Check, Close } from '@element-plus/icons-vue';
import { useAuthStore } from "@/stores/AuthStore";
import BaseResource from '@/api/publico/base';
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from "@/api/publico/sector";
import { isActionDisabled } from "@/utils/utils.js";
import axios from 'axios';

export default {
  name: 'BasesView',
  components: { Edit, Delete, Close, Check },
  methods: { isActionDisabled },
  setup() {
    const regionResource = new RegionResource();
    const provinciaResource = new ProvinciaResource();
    const distritoResource = new DistritoResource();
    const sectorResource = new SectorResource();
    const baseResource = new BaseResource();
    const authStore = useAuthStore();
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
    const detalleRonderos = ref([]);
    const formCreateEditBase = ref(null);
    const nombre_descripcionField = ref(null);

    // Detectar si es móvil
    const isMobile = ref(window.innerWidth <= 768);
    const dialogWidth = computed(() => isMobile.value ? '95%' : '650px');

    window.addEventListener('resize', () => {
      isMobile.value = window.innerWidth <= 768;
    });

    const modelBase = reactive({
      id: undefined,
      nombre_descripcion: '',
      numero_partida_registral: '',
      region_id: '',
      provincia_id: '',
      distrito_id: '',
      sector_zona_id: null,
      admin_id: null,
    });

    const reglasValidacion = {
      nombre_descripcion: [
        { required: true, message: 'El campo es requerido', trigger: 'blur' },
        { min: 3, message: 'Mínimo 3 caracteres', trigger: 'blur' }
      ],
      numero_partida_registral: [{ pattern: /^[0-9]{0,8}$/, message: 'Máximo 8 dígitos numéricos', trigger: 'blur' }],
      region_id: [{ required: true, message: 'La región es requerida', trigger: 'change' }],
      provincia_id: [{ required: true, message: 'La provincia es requerida', trigger: 'change' }],
      distrito_id: [{ required: true, message: 'El distrito es requerido', trigger: 'change' }],
    };

    const tableData = ref([]);
    const meta = ref({});
    const listLoading = ref(true);
    const listQuery = reactive({
      page: 1,
      limit: 10,
      keyword: '',
      region_id: null,
      provincia_id: null,
      distrito_id: null,
      sector_zona_id: null,
    });
    const visibleDialogForm = ref(false);
    const titleDialogForm = ref('');
    const loadingSaveDialogForm = ref(false);
    const optionsRegiones = ref([]);
    const optionsProvincias = ref([]);
    const optionsDistritos = ref([]);
    const optionsSectors = ref([]);
    const listOptionsRegiones = ref([]);
    const listOptionsProvincias = ref([]);
    const listOptionsDistritos = ref([]);
    const listOptionsSectors = ref([]);

    const formatNombre = (nombres, paterno, materno) => {
      const nombreCompleto = `${nombres || ''} ${paterno || ''} ${materno || ''}`.trim();
      return nombreCompleto.toLowerCase().replace(/\b\w/g, l => l.toUpperCase());
    };

    const fetchAllRonderos = async () => {
      try {
        const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
        const response = await axios.get(`${apiUrl}/publico/ronderos/potenciales-administradores`);
        const data = response.data.data || response.data;
        if (data && Array.isArray(data) && data.length > 0) {
          detalleRonderos.value = data.map(item => ({
            id: item.id,
            rondero_id: item.id,
            persona_id: item.persona?.id,
            docIdentidad: item.persona?.docIdentidad || '',
            apellido_paterno: item.persona?.apellido_paterno || '',
            apellido_materno: item.persona?.apellido_materno || '',
            nombres: item.persona?.nombres || '',
          }));
        } else {
          detalleRonderos.value = [];
        }
      } catch (error) {
        console.error('Error fetching ronderos:', error);
        detalleRonderos.value = [];
      }
    };

    const openFormCreate = async () => {
      resetModelBase();
      titleDialogForm.value = 'Registrar Base';
      visibleDialogForm.value = true;
      nextTick(async () => {
        await fetchRegiones();
        await fetchAllRonderos();
        formCreateEditBase.value?.clearValidate();
        nombre_descripcionField.value?.focus();
      });
    };

    const openFormEditar = async (id) => {
      titleDialogForm.value = 'Editar Base';
      visibleDialogForm.value = true;
      loadingSaveDialogForm.value = true;
      try {
        const { data } = await baseResource.get(id);
        await fetchRegiones();
        Object.assign(modelBase, {
          id: data.id,
          nombre_descripcion: data.nombre_descripcion || data.nombre || data.descripcion,
          numero_partida_registral: data.numero_partida_registral || data.partida_registral || '',
          region_id: data.region_id || (data.region?.id) || '',
          provincia_id: data.provincia_id || (data.provincia?.id) || '',
          distrito_id: data.distrito_id || (data.distrito?.id) || '',
          sector_zona_id: data.sector_zona_id || (data.sector?.id) || null,
          admin_id: data.admin_id || null,
        });
        if (modelBase.region_id) {
          await fetchProvinciasByRegion(modelBase.region_id);
          await nextTick();
          if (modelBase.provincia_id) {
            await fetchDistritosByProvincia(modelBase.provincia_id);
            await nextTick();
            if (modelBase.distrito_id) {
              await fetchSectoresByDistrito(modelBase.distrito_id);
            }
          }
        }
        await fetchAllRonderos();
        nextTick(() => {
          formCreateEditBase.value?.clearValidate();
          nombre_descripcionField.value?.focus();
        });
      } catch (error) {
        console.error('Error al cargar datos:', error);
        visibleDialogForm.value = false;
        ElMessage.error('Error al obtener datos de la base');
      } finally {
        loadingSaveDialogForm.value = false;
      }
    };

    const resetModelBase = () => {
      Object.assign(modelBase, {
        id: undefined,
        nombre_descripcion: '',
        numero_partida_registral: '',
        region_id: '',
        provincia_id: '',
        distrito_id: '',
        sector_zona_id: null,
        admin_id: null,
      });
      optionsProvincias.value = [];
      optionsDistritos.value = [];
      optionsSectors.value = [];
    };

    const saveFormCreateEdit = () => {
      formCreateEditBase.value?.validate((valid) => {
        if (valid) {
          if (!modelBase.region_id || !modelBase.provincia_id || !modelBase.distrito_id) {
            ElMessage.error('Debe seleccionar región, provincia y distrito');
            return;
          }
          if (modelBase.id === undefined) saveDataForm();
          else saveEditDataForm();
        } else {
          ElMessage.warning('Por favor complete todos los campos requeridos');
        }
      });
    };

    const saveDataForm = () => {
      loadingSaveDialogForm.value = true;
      const datosEnviar = {
        nombre_descripcion: modelBase.nombre_descripcion.trim(),
        numero_partida_registral: modelBase.numero_partida_registral || null,
        region_id: modelBase.region_id,
        provincia_id: modelBase.provincia_id,
        distrito_id: modelBase.distrito_id,
        sector_zona_id: modelBase.sector_zona_id,
        admin_id: modelBase.admin_id,
      };
      baseResource.store(datosEnviar)
        .then(() => {
          ElMessage.success('Base registrada correctamente');
          resetModelBase();
          visibleDialogForm.value = false;
          fetchBases();
        })
        .catch(error => {
          console.error(error);
          ElMessage.error(error.response?.data?.message || 'Error al registrar');
        })
        .finally(() => { loadingSaveDialogForm.value = false; });
    };

    const saveEditDataForm = () => {
      loadingSaveDialogForm.value = true;
      const datosEnviar = {
        nombre_descripcion: modelBase.nombre_descripcion.trim(),
        numero_partida_registral: modelBase.numero_partida_registral || null,
        region_id: modelBase.region_id,
        provincia_id: modelBase.provincia_id,
        distrito_id: modelBase.distrito_id,
        sector_zona_id: modelBase.sector_zona_id,
        admin_id: modelBase.admin_id,
      };
      baseResource.update(modelBase.id, datosEnviar)
        .then(() => {
          ElMessage.success('Base actualizada correctamente');
          resetModelBase();
          visibleDialogForm.value = false;
          fetchBases();
        })
        .catch(error => {
          console.error(error);
          ElMessage.error(error.response?.data?.message || 'Error al actualizar');
        })
        .finally(() => { loadingSaveDialogForm.value = false; });
    };

    const accionDesactivarRegistro = (id, nombre) => {
      ElMessageBox.confirm(`¿Seguro que desea Desactivar la base <em>${nombre}</em>?`, 'Atención', {
        top: '5vh',
        icon: markRaw(Close),
        confirmButtonText: 'Si, desactivar',
        cancelButtonText: 'Cancelar',
        type: 'warning',
        dangerouslyUseHTMLString: true
      }).then(() => {
        baseResource.inactivar(id).then(() => {
          ElMessage.success('Base desactivada');
          fetchBases();
        }).catch(() => ElMessage.error('Error al desactivar'));
      }).catch(() => ElMessage.info('Operación cancelada'));
    };

    const accionEliminarRegistro = (id, nombre) => {
      ElMessageBox.confirm(`¿Seguro que desea eliminar la base <em>${nombre}</em>?`, 'Atención', {
        top: '5vh',
        icon: markRaw(Delete),
        confirmButtonText: 'Si, eliminar',
        cancelButtonText: 'Cancelar',
        type: 'warning',
        dangerouslyUseHTMLString: true,
      }).then(() => {
        baseResource.destroy(id).then(() => {
          ElMessage.success('Base eliminada');
          fetchBases();
        }).catch((error) => {
          ElMessage.error(error.response?.data?.message || 'Error al eliminar la base');
        });
      }).catch(() => ElMessage.info('Operación cancelada'));
    };

    const accionEliminarTodoRegistros = () => {
      ElMessageBox.confirm('¿Seguro que desea eliminar todas las bases encontradas con los filtros actuales?', 'Atención', {
        top: '5vh',
        icon: markRaw(Delete),
        confirmButtonText: 'Si, eliminar todo',
        cancelButtonText: 'Cancelar',
        type: 'warning',
      }).then(() => {
        baseResource.eliminarMasivo({ ...listQuery }).then((response) => {
          const eliminados = response?.deleted_count ?? response?.data?.deleted_count ?? 0;
          ElMessage.success(`Bases eliminadas: ${eliminados}`);
          fetchBases();
        }).catch((error) => {
          ElMessage.error(error.response?.data?.message || 'Error al eliminar bases');
        });
      }).catch(() => ElMessage.info('Operación cancelada'));
    };

    const accionActivarRegistro = (id, nombre) => {
      ElMessageBox.confirm(`¿Seguro que desea Activar la base <em>${nombre}</em>?`, 'Atención', {
        top: '5vh',
        icon: markRaw(Check),
        confirmButtonText: 'Si, activar',
        cancelButtonText: 'Cancelar',
        type: 'warning',
        dangerouslyUseHTMLString: true
      }).then(() => {
        baseResource.activar(id).then(() => {
          ElMessage.success('Base activada');
          fetchBases();
        }).catch(() => ElMessage.error('Error al activar'));
      }).catch(() => ElMessage.info('Operación cancelada'));
    };

    const resetFormCreateEdit = () => {
      formCreateEditBase.value?.resetFields();
      nextTick(() => nombre_descripcionField.value?.focus());
    };

    const handleBuscarDatos = () => {
      listQuery.page = 1;
      fetchBases();
    };

    const applyListFilters = () => {
      listQuery.page = 1;
      fetchBases();
    };

    const loadFilterRegiones = async () => {
      try {
        const { data } = await regionResource.list();
        listOptionsRegiones.value = data || [];
      } catch (error) {
        console.error('Error cargando regiones filtro:', error);
      }
    };

    const handleRegionFilterChange = async (regionId) => {
      listQuery.provincia_id = null;
      listQuery.distrito_id = null;
      listQuery.sector_zona_id = null;
      listOptionsDistritos.value = [];
      listOptionsSectors.value = [];

      if (!regionId) {
        listOptionsProvincias.value = [];
        applyListFilters();
        return;
      }

      try {
        const response = await provinciaResource.getProvincias(regionId);
        listOptionsProvincias.value = response.data || response || [];
      } catch (error) {
        console.error('Error cargando provincias filtro:', error);
      }

      applyListFilters();
    };

    const handleProvinciaFilterChange = async (provinciaId) => {
      listQuery.distrito_id = null;
      listQuery.sector_zona_id = null;
      listOptionsSectors.value = [];

      if (!provinciaId) {
        listOptionsDistritos.value = [];
        applyListFilters();
        return;
      }

      try {
        const response = await distritoResource.getDistritos(provinciaId);
        listOptionsDistritos.value = response.data || response || [];
      } catch (error) {
        console.error('Error cargando distritos filtro:', error);
      }

      applyListFilters();
    };

    const handleDistritoFilterChange = async (distritoId) => {
      listQuery.sector_zona_id = null;

      if (!distritoId) {
        listOptionsSectors.value = [];
        applyListFilters();
        return;
      }

      try {
        const response = await sectorResource.getSectores(distritoId);
        listOptionsSectors.value = response.data || response || [];
      } catch (error) {
        console.error('Error cargando sectores filtro:', error);
      }

      applyListFilters();
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
        .catch(error => console.error('Error fetching data:', error))
        .finally(() => { listLoading.value = false; });
    };

    const fetchRegiones = async () => {
      try {
        const { data } = await regionResource.list();
        optionsRegiones.value = data;
      } catch (error) {
        console.error('Error fetching regiones:', error);
      }
    };

    const fetchProvinciasByRegion = async (regionId) => {
      if (!regionId) { optionsProvincias.value = []; optionsDistritos.value = []; optionsSectors.value = []; return; }
      try {
        const response = await provinciaResource.getProvincias(regionId);
        optionsProvincias.value = response.data || response;
      } catch (error) { console.error('Error fetching provincias:', error); }
    };

    const fetchDistritosByProvincia = async (provinciaId) => {
      if (!provinciaId) { optionsDistritos.value = []; optionsSectors.value = []; return; }
      try {
        const response = await distritoResource.getDistritos(provinciaId);
        optionsDistritos.value = response.data || response;
      } catch (error) { console.error('Error fetching distritos:', error); }
    };

    const fetchSectoresByDistrito = async (distritoId) => {
      if (!distritoId) { optionsSectors.value = []; modelBase.sector_zona_id = null; return; }
      try {
        const response = await sectorResource.getSectores(distritoId);
        optionsSectors.value = response.data || response;
        modelBase.sector_zona_id = null;
      } catch (error) { console.error('Error fetching sectores:', error); }
    };

    onMounted(() => {
      fetchBases();
      fetchRegiones();
      loadFilterRegiones();
    });

    return {
      authStore,
      modelBase,
      reglasValidacion,
      tableData,
      meta,
      listLoading,
      listQuery,
      visibleDialogForm,
      titleDialogForm,
      loadingSaveDialogForm,
      formCreateEditBase,
      nombre_descripcionField,
      openFormCreate,
      openFormEditar,
      saveFormCreateEdit,
      resetFormCreateEdit,
      handleBuscarDatos,
      handleCurrentChange,
      accionActivarRegistro,
      accionDesactivarRegistro,
      accionEliminarRegistro,
      accionEliminarTodoRegistros,
      isSuperAdministrador,
      optionsRegiones,
      optionsProvincias,
      optionsDistritos,
      optionsSectors,
      listOptionsRegiones,
      listOptionsProvincias,
      listOptionsDistritos,
      listOptionsSectors,
      detalleRonderos,
      fetchProvinciasByRegion,
      fetchDistritosByProvincia,
      fetchSectoresByDistrito,
      handleRegionFilterChange,
      handleProvinciaFilterChange,
      handleDistritoFilterChange,
      applyListFilters,
      formatNombre,
      isMobile,
      dialogWidth,
      Search,
      Plus,
      Edit,
      Delete,
    };
  }
};
</script>