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
      <el-table-column label="Base" prop="nombre_descripcion" />
      <el-table-column label="Sector / Zona" width="240">
        <template #default="scope">
          {{ scope.row.sector?.descripcion || scope.row.sector_zona?.descripcion || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Distrito">
        <template #default="scope">
          {{ scope.row.distrito?.descripcion ||  '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Provincia">
        <template #default="scope">
          {{ scope.row.provincia?.descripcion || scope.row.sector_zona?.distrito?.provincia?.descripcion || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Region">
        <template #default="scope">
          {{ scope.row.region?.descripcion || scope.row.sector_zona?.distrito?.provincia?.region?.descripcion || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Partida Registral" width="120">
        <template #default="scope">
          {{ scope.row.numero_partida_registral || '-' }}
        </template>
      </el-table-column>
      <el-table-column label="Administrador" width="200">
        <template #default="scope">
          <template v-if="scope.row.admin">
            {{ (scope.row.admin.nombres + ' ' + scope.row.admin.apellido_paterno + ' ' + scope.row.admin.apellido_materno).toLowerCase().replace(/\b\w/g, l => l.toUpperCase()) }}
          </template>
          <template v-else>-</template>
        </template>
      </el-table-column>
      <el-table-column prop="estado" label="Estado">
        <template #default="scope">
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
              <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="accionDesactivarRegistro(scope.row.id, scope.row.nombre_descripcion)" color="red"><Close /></el-icon>
            </el-tooltip>
            <el-tooltip
              class="box-item"
              effect="dark"
              content="Activar"
              placement="top-start"
              v-else
            >
              <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="accionActivarRegistro(scope.row.id, scope.row.nombre_descripcion)" color="green"><Check /></el-icon>
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

    <!-- Dialogo para crear o editar base -->
    <el-dialog v-model="visibleDialogForm" :close-on-click-modal="false" top="7vh" width="550" :title="titleDialogForm">
      <div>
        <el-form ref="formCreateEditBase" v-loading="loadingSaveDialogForm" :model="modelBase"
                 :rules="reglasValidacion" status-icon label-width="140px" label-position="top" v-on:submit.prevent>

          <el-form-item label="Nombre/Descripción de la Base" prop="nombre_descripcion">
            <el-input ref="nombre_descripcionField" v-model="modelBase.nombre_descripcion" type="text" autocomplete="off"
                      placeholder="Nombre o descripción de la base" />
          </el-form-item>

          <el-form-item label="N° Partida Registral (8 dígitos)" prop="numero_partida_registral">
            <el-input
              v-model="modelBase.numero_partida_registral"
              placeholder="00000000"
              maxlength="8"
              @input="modelBase.numero_partida_registral = modelBase.numero_partida_registral.replace(/\D/g, '')"
            />
            <small class="text-muted">Opcional - 8 dígitos numéricos</small>
          </el-form-item>

          <el-form-item label="Región" prop="region_id">
            <el-select
              ref="region_idField"
              v-model="modelBase.region_id"
              placeholder="Seleccione Región"
              style="width: 100%"
              @change="fetchProvinciasByRegion"
              filterable
            >
              <el-option
                v-for="item in optionsRegiones"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Provincia" prop="provincia_id">
            <el-select
              ref="provincia_idField"
              v-model="modelBase.provincia_id"
              placeholder="Seleccione Provincia"
              style="width: 100%"
              :disabled="!modelBase.region_id"
              @change="fetchDistritosByProvincia"
              filterable
            >
              <el-option
                v-for="item in optionsProvincias"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Distrito" prop="distrito_id">
            <el-select
              ref="distrito_idField"
              v-model="modelBase.distrito_id"
              placeholder="Seleccione Distrito"
              style="width: 100%"
              :disabled="!modelBase.provincia_id"
              @change="fetchSectoresByDistrito"
              filterable
            >
              <el-option
                v-for="item in optionsDistritos"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Sector (Opcional)" prop="sector_zona_id">
            <el-select
              ref="sector_zona_idField"
              v-model="modelBase.sector_zona_id"
              placeholder="Seleccione Sector (Opcional)"
              style="width: 100%"
              :disabled="!modelBase.distrito_id"
              filterable
            >
              <el-option
                v-for="item in optionsSectors"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
              <el-option
                :label="'Sin Sector (directamente en distrito)'"
                :value="null"
              />
            </el-select>
            <small class="text-muted">La base puede estar directamente en un distrito o en un sector específico</small>
          </el-form-item>

          <el-form-item label="Administrador (Opcional)" prop="admin_id">
            <el-select v-model="modelBase.admin_id" placeholder="Seleccione administrador" style="width: 100%" filterable clearable>
              <el-option
                v-for="item in detalleRonderos"
                :key="item.rondero_id"
                :label="item.nombres + ' ' + item.apellido_paterno + ' ' + item.apellido_materno"
                :value="item.persona_id"
              />
            </el-select>
          </el-form-item>
        </el-form>
      </div>
      <template #footer>
        <div class="dialog-footer">
          <el-button type="primary" @click="saveFormCreateEdit">
            Guardar
          </el-button>
          <el-button type="danger" @click="resetFormCreateEdit">Resetear</el-button>
          <el-button @click="visibleDialogForm = false">Cancelar</el-button>
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
.text-muted {
  color: #666;
  font-size: 12px;
  display: block;
  margin-top: 4px;
}
</style>

<script>
import {ref, reactive, onMounted, nextTick, markRaw} from 'vue';
import { ElMessage, ElMessageBox } from 'element-plus';
import {Search, Plus, Edit, Delete, ArrowDown, Check, Close, List} from '@element-plus/icons-vue';
import { useAuthStore } from "@/stores/AuthStore";
import BaseResource from '@/api/publico/base';
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from "@/api/publico/sector";
import {isActionDisabled} from "@/utils/utils.js";
import axios from 'axios'; // 🔥 IMPORTANTE: Agregar axios

export default {
  name: 'BasesView',
  components: {Edit, Close, Check, ArrowDown, List},
  methods: {isActionDisabled},
  setup() {
    const regionResource = new RegionResource();
    const provinciaResource = new ProvinciaResource();
    const distritoResource = new DistritoResource();
    const sectorResource = new SectorResource();
    const baseResource = new BaseResource();
    const authStore = useAuthStore()
    const detalleRonderos = ref([])
    const formCreateEditBase = ref(null);
    const nombre_descripcionField = ref(null);

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
      numero_partida_registral: [
        {
          pattern: /^[0-9]{0,8}$/,
          message: 'Máximo 8 dígitos numéricos',
          trigger: 'blur'
        }
      ],
      region_id: [
        { required: true, message: 'La región es requerida', trigger: 'change' }
      ],
      provincia_id: [
        { required: true, message: 'La provincia es requerida', trigger: 'change' }
      ],
      distrito_id: [
        { required: true, message: 'El distrito es requerido', trigger: 'change' }
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

    const optionsRegiones = ref([]);
    const optionsProvincias = ref([]);
    const optionsDistritos = ref([]);
    const optionsSectors = ref([]);


  const fetchAllRonderos = async () => {
    console.log('🚀🚀🚀 fetchAllRonderos INICIADO');
    try {
      const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
      const url = `${apiUrl}/publico/ronderos/potenciales-administradores`;
      console.log('📡 URL:', url);
      
      const response = await axios.get(url);
      console.log('✅ Status:', response.status);
      console.log('✅ Data completa:', response.data);
      
      const data = response.data.data || response.data;
      console.log('📊 data extraída:', data);openFormEditar 
      console.log('📊 data es array?', Array.isArray(data));
      console.log('📊 Cantidad:', data ? data.length : 0);
      
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
        console.log('✅ detalleRonderos actualizado, cantidad:', detalleRonderos.value.length);
        console.log('📋 Primer rondero:', detalleRonderos.value[0]);
      } else {
        console.log('⚠️ No hay datos');
        detalleRonderos.value = [];
      }
    } catch (error) {
      console.error('❌ Error en fetchAllRonderos:', error.message);
      console.error('❌ Status:', error.response?.status);
      console.error('❌ Data:', error.response?.data);
      detalleRonderos.value = [];
    }
    console.log('🏁 fetchAllRonderos FINALIZADO - detalleRonderos tiene:', detalleRonderos.value.length);
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
      console.log('✏️ openFormEditar - Editando base ID:', id);
      titleDialogForm.value = 'Editar Base';
      visibleDialogForm.value = true;
      loadingSaveDialogForm.value = true;

      try {
        const { data } = await baseResource.get(id);
        console.log('📋 Datos recibidos de la base:', data);

        // Cargar regiones primero
        await fetchRegiones();
        console.log('✅ Regiones cargadas');

        // Asignar valores básicos
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

        console.log('📝 Modelo cargado:', modelBase);

        // 🔥 Cargar provincias si hay región
        if (modelBase.region_id) {
          await fetchProvinciasByRegion(modelBase.region_id);
          console.log('✅ Provincias cargadas para región:', modelBase.region_id);
          
          // Esperar a que el select de provincia tenga las opciones
          await nextTick();
          
          // 🔥 Cargar distritos si hay provincia
          if (modelBase.provincia_id) {
            await fetchDistritosByProvincia(modelBase.provincia_id);
            console.log('✅ Distritos cargados para provincia:', modelBase.provincia_id);
            
            await nextTick();
            
            // 🔥 Cargar sectores si hay distrito
            if (modelBase.distrito_id) {
              await fetchSectoresByDistrito(modelBase.distrito_id);
              console.log('✅ Sectores cargados para distrito:', modelBase.distrito_id);
            }
          }
        }

        // Cargar ronderos para el selector de administrador
        await fetchAllRonderos();

        nextTick(() => {
          formCreateEditBase.value?.clearValidate();
          nombre_descripcionField.value?.focus();
        });
      } catch (error) {
        console.error('❌ Error al cargar datos:', error);
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
        console.log('✅ Validación del formulario:', valid);
        console.log('📝 Datos del modelo:', JSON.parse(JSON.stringify(modelBase)));

        if (valid) {
          if (!modelBase.region_id) {
            ElMessage.error('Debe seleccionar una región');
            return;
          }
          if (!modelBase.provincia_id) {
            ElMessage.error('Debe seleccionar una provincia');
            return;
          }
          if (!modelBase.distrito_id) {
            ElMessage.error('Debe seleccionar un distrito');
            return;
          }

          console.log('🚀 Enviando datos con:', {
            region_id: modelBase.region_id,
            provincia_id: modelBase.provincia_id,
            distrito_id: modelBase.distrito_id
          });

          if (modelBase.id === undefined) {
            saveDataForm();
          } else {
            saveEditDataForm();
          }
        } else {
          console.log('Formulario no válido');
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

      console.log('📤 Enviando datos al backend:', datosEnviar);

      baseResource.store(datosEnviar)
        .then(response => {
          console.log('✅ Respuesta del servidor:', response);

          if (response && (response.state === 'success' || response.data)) {
            ElMessage.success(response.message || 'Base registrada correctamente');
            resetModelBase();
            visibleDialogForm.value = false;
            fetchBases();
          } else {
            throw new Error('Respuesta inesperada del servidor');
          }
        })
        .catch(error => {
          console.error('❌ ERROR EN LA SOLICITUD:', error);

          if (error.response) {
            const status = error.response.status;
            const data = error.response.data;

            if (status === 422 && data.errors) {
              let mensajesError = [];

              Object.keys(data.errors).forEach(key => {
                if (Array.isArray(data.errors[key])) {
                  data.errors[key].forEach(msg => {
                    mensajesError.push(`${key}: ${msg}`);
                  });
                } else {
                  mensajesError.push(`${key}: ${data.errors[key]}`);
                }
              });

              mensajesError.forEach(msg => {
                ElMessage.error(msg);
              });
            }
            else if (status === 500) {
              ElMessage.error('Error interno del servidor. Contacte al administrador.');
              console.error('Error 500 detalle:', data);
            }
            else if (status === 401 || status === 403) {
              ElMessage.error('No autorizado. Por favor, inicie sesión nuevamente.');
              authStore.logout();
              setTimeout(() => {
                window.location.href = '/login';
              }, 2000);
            }
            else if (data.message) {
              ElMessage.error(data.message);
            } else {
              ElMessage.error(`Error ${status}: ${data || 'Error desconocido'}`);
            }

          } else if (error.request) {
            console.error('No se recibió respuesta del servidor:', error.request);

            if (error.request.status === 0) {
              ElMessage.error('Error de conexión. Verifique: \n1. El servidor está corriendo\n2. No hay problemas de CORS\n3. La URL es correcta');
            } else {
              ElMessage.error('El servidor no respondió. Verifique su conexión.');
            }

          } else {
            console.error('Error de configuración:', error.message);
            ElMessage.error(`Error: ${error.message}`);
          }
        })
        .finally(() => {
          loadingSaveDialogForm.value = false;
        });
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

      console.log('📤 Actualizando datos:', datosEnviar);

      baseResource.update(modelBase.id, datosEnviar)
        .then(response => {
          console.log('✅ Actualización exitosa:', response);

          if (response && (response.state === 'success' || response.data)) {
            ElMessage.success(response.message || 'Base actualizada correctamente');
            resetModelBase();
            visibleDialogForm.value = false;
            fetchBases();
          } else {
            throw new Error('Respuesta inesperada del servidor');
          }
        })
        .catch(error => {
          console.error('❌ Error al actualizar:', error);

          if (error.response) {
            const data = error.response.data;
            if (error.response.status === 422 && data.errors) {
              Object.keys(data.errors).forEach(key => {
                if (Array.isArray(data.errors[key])) {
                  data.errors[key].forEach(msg => ElMessage.error(`${key}: ${msg}`));
                }
              });
            } else if (data.message) {
              ElMessage.error(data.message);
            } else {
              ElMessage.error('Error al actualizar los datos');
            }
          } else {
            ElMessage.error('Error de conexión con el servidor');
          }
        })
        .finally(() => {
          loadingSaveDialogForm.value = false;
        });
    };

    const accionDesactivarRegistro = (id, nombre) => {
      ElMessageBox.confirm(
        `¿Seguro que desea Desactivar la base <em>${nombre}</em>?`,
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
              ElMessage.success('Base desactivada');
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
        `¿Seguro que desea Activar la base <em>${nombre}</em>?`,
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
              ElMessage.success('Base activada');
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
        nombre_descripcionField.value?.focus();
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
      limit: 1000,
      page: 1,
      orderby: 'ASC',
      ids_excluir: [],
      base_id: 0,
    });

    const getRonderos = async (id) => {
      console.log('getRonderos llamado para base ID:', id);
    }

    const fetchBases = () => {
      listLoading.value = true;
      baseResource.list(listQuery)
        .then(response => {
          tableData.value = response.data;
          meta.value = response.meta;
          console.log('📊 Bases cargadas:', response.data.length);

          if (response.data.length > 0) {
            console.log('🔍 Estructura de la primera base:', response.data[0]);
          }
        })
        .catch(error => {
          console.error('Error fetching data:', error);
          ElMessage.error('Error al obtener bases');
        })
        .finally(() => {
          listLoading.value = false;
        });
    };

    const fetchRegiones = async () => {
      try {
        await regionResource.list()
          .then(response => {
            const { data } = response
            optionsRegiones.value = data;
            console.log('📊 Regiones cargadas:', data.length);
          })
          .catch(error => {
            console.error('Error fetching regiones:', error);
            ElMessage.error('Error al obtener regiones');
          });
      } catch (error) {
        console.error('Error en fetchRegiones:', error);
      }
    };

    const fetchProvinciasByRegion = async (regionId) => {
      if (!regionId) {
        optionsProvincias.value = [];
        optionsDistritos.value = [];
        optionsSectors.value = [];
        return;
      }

      try {
        await provinciaResource.getProvincias(regionId)
          .then(response => {
            optionsProvincias.value = response.data || response;
            console.log('📊 Provincias cargadas:', optionsProvincias.value.length);
          })
          .catch(error => {
            console.error('Error fetching provincias:', error);
            ElMessage.error('Error al obtener provincias');
          });
      } catch (error) {
        console.error('Error en fetchProvinciasByRegion:', error);
      }
    };

    const fetchDistritosByProvincia = async (provinciaId) => {
      if (!provinciaId) {
        optionsDistritos.value = [];
        optionsSectors.value = [];
        return;
      }

      try {
        await distritoResource.getDistritos(provinciaId)
          .then(response => {
            optionsDistritos.value = response.data || response;
            console.log('📊 Distritos cargados:', optionsDistritos.value.length);
          })
          .catch(error => {
            console.error('Error fetching distritos:', error);
            ElMessage.error('Error al obtener distritos');
          });
      } catch (error) {
        console.error('Error en fetchDistritosByProvincia:', error);
      }
    };

    const fetchSectoresByDistrito = async (distritoId) => {
      if (!distritoId) {
        optionsSectors.value = [];
        modelBase.sector_zona_id = null;
        return;
      }

      try {
        await sectorResource.getSectores(distritoId)
          .then(response => {
            optionsSectors.value = response.data || response;
            console.log('📊 Sectores cargados para distrito', distritoId, ':', optionsSectors.value);
            modelBase.sector_zona_id = null;
          })
          .catch(error => {
            console.error('Error fetching sectores:', error);
          });
      } catch (error) {
        console.error('Error en fetchSectoresByDistrito:', error);
      }
    };

    onMounted(() => {
      fetchBases();
      fetchRegiones();
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
      optionsRegiones,
      optionsProvincias,
      optionsDistritos,
      optionsSectors,
      detalleRonderos,
      fetchProvinciasByRegion,
      fetchDistritosByProvincia,
      fetchSectoresByDistrito,

      Search,
      Plus,
      Edit,
      Delete,
    };
  }
};
</script>