<template>
  <div v-loading="loading" element-loading-text="Cargando...">
    <!-- Fila de filtros -->
    <el-row :gutter="12">
      <!-- Filtro por nombre -->
      <el-col :xs="24" :sm="12" :md="6" class="actions-component">
        <el-input
            v-model="query.keyword"
            placeholder="Buscar por nombre"
            @change="getLista"
            clearable
        >
          <template #append>
            <el-button @click="getLista">
              <template #icon>
                <v-icon name="hi-search" class="pointer" />
              </template>
            </el-button>
          </template>
        </el-input>
      </el-col>

      <!-- Filtro por Región -->
      <el-col :xs="24" :sm="12" :md="4" class="actions-component">
        <el-select
            v-model="query.region_id"
            placeholder="Región"
            clearable
            filterable
            @change="cargarProvincias"
            style="width: 100%"
        >
          <el-option
              v-for="item in opcionesRegiones"
              :key="item.id"
              :label="item.descripcion"
              :value="item.id"
          />
        </el-select>
      </el-col>

      <!-- Filtro por Provincia -->
      <el-col :xs="24" :sm="12" :md="4" class="actions-component">
        <el-select
            v-model="query.provincia_id"
            placeholder="Provincia"
            clearable
            filterable
            :disabled="!query.region_id"
            @change="cargarDistritos"
            style="width: 100%"
        >
          <el-option
              v-for="item in opcionesProvincias"
              :key="item.id"
              :label="item.descripcion"
              :value="item.id"
          />
        </el-select>
      </el-col>

      <!-- Filtro por Distrito -->
      <el-col :xs="24" :sm="12" :md="4" class="actions-component">
        <el-select
            v-model="query.distrito_id"
            placeholder="Distrito"
            clearable
            filterable
            :disabled="!query.provincia_id"
            @change="cargarSectores"
            style="width: 100%"
        >
          <el-option
              v-for="item in opcionesDistritos"
              :key="item.id"
              :label="item.descripcion"
              :value="item.id"
          />
        </el-select>
      </el-col>

      <!-- Filtro por Sector -->
      <el-col :xs="24" :sm="12" :md="3" class="actions-component">
        <el-select
            v-model="query.sector_zona_id"
            placeholder="Sector"
            clearable
            filterable
            :disabled="!query.distrito_id"
            @change="cargarBases"
            style="width: 100%"
        >
          <el-option
              v-for="item in opcionesSectores"
              :key="item.id"
              :label="item.descripcion"
              :value="item.id"
          />
        </el-select>
      </el-col>

      <!-- Filtro por Base -->
      <el-col :xs="24" :sm="12" :md="3" class="actions-component">
        <el-select
            v-model="query.base_id_filtro"
            placeholder="Base"
            clearable
            filterable
            :disabled="!query.distrito_id"
            @change="getLista"
            style="width: 100%"
        >
          <el-option
              v-for="item in opcionesBases"
              :key="item.id"
              :label="item.nombre_descripcion || item.descripcion"
              :value="item.id"
          />
        </el-select>
      </el-col>
    </el-row>

    <!-- Tabla de datos -->
    <el-table
        :data="tableData"
        :border="true"
        style="width: 100%; margin-top: 15px !important; font-size: 12px;"
        header-row-class-name="table-header-custom"
        row-class-name="table-row-custom"
    >
      <el-table-column type="index" label="#" width="40" fixed="left" />
      <el-table-column prop="codigo_rondero" label="Código Rondero" width="120" />
      <el-table-column prop="persona.docIdentidad" label="DNI" width="100" />
      <el-table-column prop="persona.nombre_completo" label="Persona" width="200" />
      <el-table-column prop="persona.genero" label="Sexo" width="60" align="center">
        <template #default="scope">
          <el-tag
              :type="scope.row.persona.genero?.toUpperCase() === 'MASCULINO' ? 'primary' : 'danger'"
              size="small"
              effect="dark"
          >
            {{ obtenerInicialGenero(scope.row.persona.genero) }}
          </el-tag>
        </template>
      </el-table-column>
      <el-table-column prop="persona.celular" label="Celular" min-width="100" />
      <el-table-column prop="provincia?.descripcion" label="Provincia" min-width="150">
        <template #default="scope">
          {{ scope.row.provincia?.descripcion || '-' }}
        </template>
      </el-table-column>
      <el-table-column prop="sector?.descripcion" label="Sector" min-width="150">
        <template #default="scope">
          {{ scope.row.sector?.descripcion || '-' }}
        </template>
      </el-table-column>
      <el-table-column prop="base?.nombre_descripcion" label="Base" min-width="150">
        <template #default="scope">
          {{ scope.row.base?.nombre_descripcion || scope.row.base?.descripcion || '-' }}
        </template>
      </el-table-column>
      <el-table-column prop="estado" label="Estado" width="80">
        <template #default="scope">
          <el-tag v-if="scope.row.estado" type="success">Activo</el-tag>
          <el-tag v-else type="danger">Inactivo</el-tag>
        </template>
      </el-table-column>
      <el-table-column align="center" width="120" fixed="right">
        <template #header>
          <el-button type="primary" class="ache-background-color-template" :icon="Plus" @click="handleCommandOpciones({ action: 'ADD' })" v-if="!isActionDisabled('pub.rondero.crear')">
            Agregar
          </el-button>
        </template>
        <template #default="scope">
          <el-tooltip class="box-item" effect="dark" content="Ver Carnet" placement="top-start">
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="handleCommandAcciones({ item: scope.row, action: 'CARNET' })" v-if="!isActionDisabled('pub.rondero.actualizar')" color="#1890ff">
              <Tickets />
            </el-icon>
          </el-tooltip>
          <el-tooltip class="box-item" effect="dark" content="Editar" placement="top-start">
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="handleCommandAcciones({ item: scope.row, action: 'EDIT' })" v-if="!isActionDisabled('pub.rondero.actualizar')" color="#E3CB2DFF">
              <Edit />
            </el-icon>
          </el-tooltip>
          <el-tooltip class="box-item" effect="dark" content="Eliminar (solo SuperAdministrador)" placement="top-start" v-if="isSuperAdministrador">
            <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="handleCommandAcciones({ item: scope.row, action: 'DELETE' })" color="#d03050">
              <Delete />
            </el-icon>
          </el-tooltip>
          <slot v-if="!isActionDisabled('pub.rondero.eliminar')">
            <el-tooltip class="box-item" effect="dark" content="Desactivar" placement="top-start" v-if="scope.row.estado">
              <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="handleCommandAcciones({ item: scope.row, action: 'DESACTIVAR' })" color="red">
                <Close />
              </el-icon>
            </el-tooltip>
            <el-tooltip class="box-item" effect="dark" content="Activar" placement="top-start" v-else>
              <el-icon size="20" style="margin-right: 10px; cursor: pointer;" @click="handleCommandAcciones({ item: scope.row, action: 'ACTIVAR' })" color="green">
                <Check />
              </el-icon>
            </el-tooltip>
          </slot>
        </template>
      </el-table-column>
    </el-table>

    <el-divider />

    <el-row type="flex" justify="center">
      <el-pagination
          style="font-size: 12px;"
          v-model:current-page="query.page"
          v-model:page-size="query.limit"
          :total="total"
          :page-sizes="[10, 15, 25, 50]"
          layout="total, sizes, prev, pager, next, jumper"
          background
          @size-change="getLista"
          @current-change="getLista"
      />
    </el-row>

    <!-- Diálogos -->
    <el-dialog top="5vh" v-model="openDialogCreate" :width="calcularAnchoDialog('75%','98%')">
      <template #header>Nuevo Rondero<hr style="border-top: 1px solid #ececec;"></template>
      <create-rondero @close="handleCloseCreate" />
    </el-dialog>

    <el-dialog top="5vh" v-model="openDialogEdit" :width="calcularAnchoDialog('75%','98%')" :before-close="bcDialogEdit">
      <template #header>
        Editar Rondero
        <hr style="border-top: 1px solid #ececec;">
      </template>
      <edit-rondero :id-rondero="idItemToEdit" @close="handleCloseEdit" />
    </el-dialog>
  </div>
</template>

<script setup>
import Resource from '@/api/resource'
import { Delete, Check, Plus, Tickets, Edit, Close } from '@element-plus/icons-vue'
import { ElMessageBox, ElNotification } from 'element-plus'
import { computed, nextTick, onMounted, reactive, ref, markRaw, watch } from 'vue';
import RonderoRequest from '@/api/publico/rondero';
import { calcularAnchoDialog } from '@/utils/responsive';
import { useAuthStore } from "@/stores/AuthStore";
import CreateRondero from "@/views/admin/publico/ronderos/components/CreateRondero.vue";
import EditRondero from "@/views/admin/publico/ronderos/components/EditRondero.vue";
import VIcon from "@/components/Icons/SvgIcon.vue";

// Recursos para ubicación
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from '@/api/publico/sector';
import BaseResource from '@/api/publico/base';

const regionResource = new RegionResource();
const provinciaResource = new ProvinciaResource();
const distritoResource = new DistritoResource();
const sectorResource = new SectorResource();
const baseResource = new BaseResource();

const estadoRonderoResource = new Resource('publico/ronderos');
const ronderoCarnetResource = new Resource('generar-carnet');
const ronderoRequest = new RonderoRequest();
const authStore = useAuthStore()
const validPermision = authStore.validPermision
const isSuperAdministrador = computed(() => authStore.roles.includes('SuperAdministrador'))

const loading = ref(false);
const openDialogCreate = ref(false);
const openDialogEdit = ref(false);
const tableData = ref([]);
const total = ref(0);
const bases = ref([]);
const idItemToEdit = ref(0);

// Opciones para filtros de ubicación
const opcionesRegiones = ref([]);
const opcionesProvincias = ref([]);
const opcionesDistritos = ref([]);
const opcionesSectores = ref([]);
const opcionesBases = ref([]);

// Query con todos los filtros
const query = reactive({
  keyword: '',
  base_id: null,
  limit: 8,
  page: 1,
  // Nuevos filtros de ubicación
  region_id: null,
  provincia_id: null,
  distrito_id: null,
  sector_zona_id: null,
  base_id_filtro: null
});

// =============================================
// FUNCIÓN PARA OBTENER LA INICIAL DEL GÉNERO
// =============================================
const obtenerInicialGenero = (genero) => {
  if (!genero) return '-';

  const generoNormalizado = genero.toUpperCase().trim();

  // Verificar si es MASCULINO (acepta varias variantes)
  if (generoNormalizado === 'MASCULINO' ||
      generoNormalizado === 'M' ||
      generoNormalizado.startsWith('MASC')) {
    return 'M';
  }

  // Verificar si es FEMENINO (acepta varias variantes)
  if (generoNormalizado === 'FEMENINO' ||
      generoNormalizado === 'F' ||
      generoNormalizado.startsWith('FEM')) {
    return 'F';
  }

  // Si no se puede determinar, devolver el valor original o '-'
  return genero.charAt(0).toUpperCase() || '-';
};

// Watch para resetear filtros dependientes cuando cambia región
watch(() => query.region_id, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    query.provincia_id = null;
    query.distrito_id = null;
    query.sector_zona_id = null;
    query.base_id_filtro = null;
    opcionesProvincias.value = [];
    opcionesDistritos.value = [];
    opcionesSectores.value = [];
    opcionesBases.value = [];
    if (newVal) {
      getLista();
    }
  }
});

// Watch para resetear filtros dependientes cuando cambia provincia
watch(() => query.provincia_id, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    query.distrito_id = null;
    query.sector_zona_id = null;
    query.base_id_filtro = null;
    opcionesDistritos.value = [];
    opcionesSectores.value = [];
    opcionesBases.value = [];
    if (newVal) {
      getLista();
    }
  }
});

// Watch para resetear filtros dependientes cuando cambia distrito
watch(() => query.distrito_id, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    query.sector_zona_id = null;
    query.base_id_filtro = null;
    opcionesSectores.value = [];
    opcionesBases.value = [];
    if (newVal) {
      getLista();
    }
  }
});

// Watch para resetear filtros dependientes cuando cambia sector
watch(() => query.sector_zona_id, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    query.base_id_filtro = null;
    opcionesBases.value = [];
    if (newVal) {
      getLista();
    }
  }
});

// Watch para base_id_filtro
watch(() => query.base_id_filtro, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    getLista();
  }
});

// Watch para keyword
watch(() => query.keyword, (newVal, oldVal) => {
  if (newVal !== oldVal) {
    getLista();
  }
});

// Cargar regiones al iniciar
const cargarRegiones = async () => {
  try {
    const { data } = await regionResource.list();
    opcionesRegiones.value = data || [];
  } catch (error) {
    console.error('Error cargando regiones:', error);
  }
};

// Cargar provincias según región seleccionada
const cargarProvincias = async () => {
  if (!query.region_id) {
    opcionesProvincias.value = [];
    return;
  }
  try {
    const provincias = await provinciaResource.getProvincias(query.region_id);
    opcionesProvincias.value = provincias || [];
  } catch (error) {
    console.error('Error cargando provincias:', error);
    opcionesProvincias.value = [];
  }
};

// Cargar distritos según provincia seleccionada
const cargarDistritos = async () => {
  if (!query.provincia_id) {
    opcionesDistritos.value = [];
    return;
  }
  try {
    const distritos = await distritoResource.getDistritos(query.provincia_id);
    opcionesDistritos.value = distritos || [];
  } catch (error) {
    console.error('Error cargando distritos:', error);
    opcionesDistritos.value = [];
  }
};

// Cargar sectores según distrito seleccionado
const cargarSectores = async () => {
  if (!query.distrito_id) {
    opcionesSectores.value = [];
    opcionesBases.value = [];
    return;
  }
  try {
    const sectores = await sectorResource.getSectores(query.distrito_id);
    opcionesSectores.value = sectores || [];

    // Si todavía no hay sector seleccionado, mostrar bases del distrito
    if (!query.sector_zona_id) {
      await cargarBases();
    }
  } catch (error) {
    console.error('Error cargando sectores:', error);
    opcionesSectores.value = [];
    opcionesBases.value = [];
  }
};

// Cargar bases según sector seleccionado
const cargarBases = async () => {
  const tieneDistrito = query.distrito_id && query.distrito_id !== '' && query.distrito_id !== 0;
  const tieneSector = query.sector_zona_id && query.sector_zona_id !== '' && query.sector_zona_id !== 0;

  if (!tieneDistrito && !tieneSector) {
    opcionesBases.value = [];
    return;
  }

  try {
    const basesData = tieneSector
      ? await baseResource.getBases(query.sector_zona_id)
      : await baseResource.getBasesPorDistrito(query.distrito_id);

    const payload = basesData?.data ?? basesData;
    opcionesBases.value = Array.isArray(payload) ? payload : [];
  } catch (error) {
    console.error('Error cargando bases:', error);
    opcionesBases.value = [];
  }
};

// =============================================
// FUNCIÓN CORREGIDA PARA CARGAR BASES
// =============================================
const fetchBases = async () => {
  try {
    // Usar el resource en lugar de fetch directo
    const response = await baseResource.list();
    bases.value = response.data || [];
    console.log('Bases cargadas:', bases.value);
  } catch (error) {
    console.error('Error cargando bases:', error);
    bases.value = [];
  }
};

const getLista = () => {
  loading.value = true;
  console.log('Consultando ronderos con query:', query);

  ronderoRequest
      .list(query)
      .then((response) => {
        console.log('Respuesta de ronderos:', response);
        // Manejar diferentes estructuras de respuesta
        if (response.data && response.meta) {
          tableData.value = response.data;
          total.value = response.meta.total;
        } else if (Array.isArray(response)) {
          tableData.value = response;
          total.value = response.length;
        } else if (response.data && Array.isArray(response.data)) {
          tableData.value = response.data;
          total.value = response.data.length;
        } else {
          tableData.value = [];
          total.value = 0;
        }
        loading.value = false;
      })
      .catch((err) => {
        console.error('Error al obtener ronderos:', err);
        ElNotification({
          type: 'error',
          title: 'Error al cargar ronderos',
          message: err.message || 'No se pudieron cargar los ronderos',
          duration: 5000
        });
        tableData.value = [];
        total.value = 0;
        loading.value = false;
      });
};

const addItem = () => {
  openDialogCreate.value = true
}

const handleCloseCreate = (status) => {
  if (status == 'success') getLista();
  openDialogCreate.value = false;
};

const handleCloseEdit = (status) => {
  if (status == 'success') getLista();
  idItemToEdit.value = 0;
  openDialogEdit.value = false;
};

const handleCommandOpciones = ({ action }) => {
  if (action === 'ADD' && validPermision('pub.rondero.crear')) {
    addItem()
  } else {
    ElNotification({message: 'Usted no tiene permiso para realizar esta acción', type: 'info' })
  }
};

const handleCommandAcciones = ({ item, action }) => {
  if (action == 'CARNET' && validPermision('pub.rondero.actualizar')) {
    idItemToEdit.value = item.id;
    nextTick(() => {
      loading.value = true;
      ElNotification({
        title: 'Generando Carnet de Rondero',
        message: 'Espere un momento...',
        type: 'success',
        duration: 3000,
      });
      ronderoCarnetResource
          .generarPDF({ id: item.id, url_front: window.location.origin })
          .then((pdfBlob) => {
            const blob = new Blob([pdfBlob], { type: 'application/pdf' });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement('a');
            link.href = url;
            link.download = `carnet_rondero_${item.id}.pdf`;
            link.style.display = 'none';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            setTimeout(() => URL.revokeObjectURL(url), 10000);
            loading.value = false;
          })
          .catch((err) => {
            console.log(err)
            loading.value = false;
          })
          .finally(() => {
            loading.value = false;
          });
    });
  }
  else if (action == 'EDIT' && validPermision('pub.rondero.actualizar')) {
    idItemToEdit.value = item.id;
    nextTick(() => {
      openDialogEdit.value = true;
    });
  }
  else if (action == 'DELETE' && isSuperAdministrador.value) {
    const msg = `¿Seguro que desea eliminar el registro?<br /><br />${item.persona.docIdentidad}`
    ElMessageBox.confirm(msg, 'Atención', {
      top: '5vh',
      icon: markRaw(Delete),
      confirmButtonText: 'Sí',
      cancelButtonText: 'Cancelar',
      type: 'warning',
      dangerouslyUseHTMLString: true
    })
        .then(() => {
          ronderoRequest.destroy(item.id)
              .then(() => {
                ElNotification({ title: 'Rondero eliminado', type: 'success', duration: 2000 });
                getLista();
              })
              .catch((err) => console.log(err));
        })
        .catch((err) => { console.log(err); });
  }
  else if (action == 'DESACTIVAR' && validPermision('pub.rondero.eliminar')) {
    const msg = `¿Seguro que desea desactivar el registro?<br /><br />${item.persona.docIdentidad}`
    ElMessageBox.confirm(msg, 'Atención', {
      top: '5vh',
      icon: markRaw(Delete),
      confirmButtonText: 'Sí',
      cancelButtonText: 'Cancelar',
      type: 'warning',
      dangerouslyUseHTMLString: true
    })
        .then(() => {
          estadoRonderoResource.inactivar(item.id)
              .then(() => {
                ElNotification({ title: 'Rondero desactivado', type: 'success', duration: 2000 });
                getLista();
              })
              .catch((err) => console.log(err));
        })
        .catch((err) => { console.log(err); });
  }
  else if (action == 'ACTIVAR' && validPermision('pub.rondero.eliminar')) {
    const msg = `¿Seguro que desea Activar el registro?<br /><br />${item.persona.docIdentidad}`
    ElMessageBox.confirm(msg, 'Atención', {
      top: '5vh',
      icon: markRaw(Delete),
      confirmButtonText: 'Sí',
      cancelButtonText: 'Cancelar',
      type: 'warning',
      dangerouslyUseHTMLString: true
    })
        .then(() => {
          estadoRonderoResource.activar(item.id)
              .then(() => {
                ElNotification({ title: 'Rondero Activo', type: 'success', duration: 2000 });
                getLista();
              })
              .catch((err) => console.log(err));
        })
        .catch((err) => { console.log(err); });
  }
};

const isActionDisabled = (action) => {
  return !validPermision(action.toLowerCase());
}

const bcDialogEdit = (done) => {
  done()
  idItemToEdit.value = 0
  openDialogEdit.value = false
}

// Inicialización
onMounted(() => {
  cargarRegiones();
  fetchBases();
  getLista();
})
</script>


