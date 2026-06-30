<template>
  <CustomLoading class="editRondero" :loading="loading" loadingText="Cargando datos...">
    <el-form ref="editRonderoForm" :model="form_rondero" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="12">
          <!-- CAMPOS BLOQUEADOS (PROVIENEN DE RENIEC) -->
          <el-form-item label="DNI" prop="docIdentidad">
            <el-input v-model="form_rondero.persona.docIdentidad" placeholder="DNI" maxlength="8" disabled/>
          </el-form-item>

          <el-form-item label="Nombres" prop="nombres">
            <el-input v-model="form_rondero.persona.nombres" placeholder="Nombres" disabled/>
          </el-form-item>

          <el-form-item label="Ap.Paterno" prop="apellido_paterno">
            <el-input v-model="form_rondero.persona.apellido_paterno" placeholder="Ap. Paterno" disabled/>
          </el-form-item>

          <el-form-item label="Ap. Materno" prop="apellido_materno">
            <el-input v-model="form_rondero.persona.apellido_materno" placeholder="Ap. Materno" disabled/>
          </el-form-item>

          <el-form-item label="Fecha Inicio" prop="fecha_inicio">
            <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="form_rondero.fecha_inicio"
              placeholder="Fecha Inicio"
            />
          </el-form-item>

          <el-form-item label="Fecha Cese" prop="fecha_cese">
            <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="form_rondero.fecha_cese"
              placeholder="Fecha cese"
            />
          </el-form-item>

          <el-form-item label="Email" prop="email">
            <el-input v-model.trim="form_rondero.persona.email" placeholder="mail@example.com" />
          </el-form-item>

          <el-form-item label="Fecha Nacimiento" prop="fecha_nacimiento">
            <el-date-picker
              style="width: 100%"
              type="date"
              format="DD/MM/YYYY"
              value-format="YYYY-MM-DD"
              v-model="form_rondero.persona.fecha_nacimiento"
              placeholder="Fecha Nacimiento"
              disabled
            />
          </el-form-item>

          <el-row :gutter="12">
            <el-col :xs="24" :sm="12" :md="12">
              <el-form-item label="Género" prop="genero">
                <el-select v-model="form_rondero.persona.genero" placeholder="Genero" style="width: 100%" disabled>
                  <el-option label="MASCULINO" value="MASCULINO" />
                  <el-option label="FEMENINO" value="FEMENINO" />
                </el-select>
              </el-form-item>
            </el-col>
            <el-col :xs="24" :sm="12" :md="12">
              <el-form-item label="Celular" prop="celular">
                <el-input v-model="form_rondero.persona.celular" placeholder="Celular" />
              </el-form-item>
            </el-col>
          </el-row>

          <el-form-item label="Dirección" prop="direccion">
            <el-input v-model="form_rondero.persona.direccion" placeholder="Dirección" disabled/>
          </el-form-item>
        </el-col>

        <el-col :xs="24" :sm="12" :md="12">
          <el-form-item label="Región" prop="region_id">
            <el-select v-model="form_rondero.region_id" placeholder="Región" style="width: 100%" @change="obtenerProvincias">
              <el-option label="- Seleccione Región -" value="0"/>
              <el-option
                v-for="item in optionsRegiones"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Provincia" prop="provincia_id">
            <el-select v-model="form_rondero.provincia_id" placeholder="Provincia" style="width: 100%" @change="obtenerDistritos">
              <el-option
                v-for="item in optionsProvincias"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Distrito" prop="distrito_id">
            <el-select v-model="form_rondero.distrito_id" placeholder="Distrito" style="width: 100%" searchable @change="onDistritoChange">
              <el-option
                v-for="item in optionsDistritos"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Sector / Zona" prop="sector_zona_id">
            <el-select v-model="form_rondero.sector_zona_id" placeholder="Sector" style="width: 100%" searchable @change="onSectorChange">
              <el-option
                v-for="item in optionsSectores"
                :key="item.id"
                :label="item.descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <el-form-item label="Base" prop="base_id">
            <el-select v-model="form_rondero.base_id" placeholder="Base" style="width: 100%" searchable>
              <el-option
                v-for="item in optionsBases"
                :key="item.id"
                :label="item.descripcion || item.nombre_descripcion"
                :value="item.id"
              />
            </el-select>
          </el-form-item>

          <!-- Código Rondero - SOLO LECTURA -->
          <el-form-item label="Código Rondero" prop="codigo_rondero">
            <el-input
              v-model="form_rondero.codigo_rondero"
              placeholder="Código autogenerado"
              disabled
            />
            <small class="text-muted">Código único asignado automáticamente</small>
          </el-form-item>

          <el-form-item label="Cargos" v-if="tagsCargos && tagsCargos.length > 0">
            <el-table :data="tagsCargos" style="width: 100%" size="small">
              <el-table-column prop="fecha_inicio" label="Inicio" width="100" :formatter="dateFormatter" />
              <el-table-column prop="fecha_fin" label="Fin" width="100" :formatter="dateFormatter"/>
              <el-table-column prop="cargo.descripcion" label="Cargo" width="120" />
              <el-table-column label="Entidad" width="100">
                <template #default="scope">
                  {{ scope.row.comiteable_type }} {{ scope.row.comiteable.descripcion }}
                </template>
              </el-table-column>
              <el-table-column fixed="right" align="right" min-width="10">
                <template #default="scope">
                  <el-button
                    link
                    type="primary"
                    size="small"
                    @click.prevent="deleteRowCargo(scope.$index, scope.row.id)"
                  >
                    <el-icon color="red" size="18"><Delete /></el-icon>
                  </el-button>
                </template>
              </el-table-column>
            </el-table>
          </el-form-item>

          <el-form-item>
            <el-button type="primary" @click="openDialog(form_rondero.id)">Asignar Cargo</el-button>
          </el-form-item>
        </el-col>
      </el-row>
    </el-form>

    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-button @click="close('canceled')" v-if="!isActionDisabled('pub.rondero.actualizar')">CANCELAR</el-button>
      <el-button type="primary" @click="submitForm(editRonderoForm)" v-if="!isActionDisabled('pub.rondero.actualizar')">GUARDAR</el-button>
    </el-row>
  </CustomLoading>

  <AsignarComiteDialog
    v-if="dialogVisible"
    :rondero-id="selectedRonderoId"
    @close-dialog="close_dialog_cargo"
  />
</template>

<script setup>
import AsignarComiteDialog from '@/views/admin/publico/ronderos/components/AsignarComiteDialog.vue';
import RonderoRequest from '@/api/publico/rondero';
import RegionResource from '@/api/publico/region';
import ProvinciaResource from '@/api/publico/provincia';
import DistritoResource from '@/api/publico/distrito';
import SectorResource from '@/api/publico/sector';
import BaseResource from '@/api/publico/base';
import {ElMessage, ElMessageBox, ElNotification} from 'element-plus'
import ComiteResource from "@/api/publico/comite";
import { reactive, ref, onMounted, watch } from 'vue'
import { Delete } from "@element-plus/icons-vue";
import { dateFormatter, isActionDisabled } from "@/utils/utils.js";
import CustomLoading from "@/components/loading/CustomLoading.vue";

const comiteResource = new ComiteResource();
const ronderoRequest = new RonderoRequest();
const regionResource = new RegionResource();
const provinciaResource = new ProvinciaResource();
const distritoResource = new DistritoResource();
const sectorResource = new SectorResource();
const baseResource = new BaseResource();

const optionsRegiones = ref([])
const optionsProvincias = ref([])
const optionsDistritos = ref([])
const optionsSectores = ref([])
const optionsBases = ref([])
const dialogVisible = ref(false)
const selectedRonderoId = ref(null)

const props = defineProps({
  idRondero: {
    type: Number,
    required: true,
    default: 0
  }
})

watch(() => props.idRondero, (newValue, oldValue) => {
  if(newValue != oldValue && newValue != '' && newValue != null) {
    cargarRegistro()
  }
});

const emit = defineEmits(['close', 'close-dialog'])

const loading = ref(false)
const editRonderoForm = ref()
const tagsCargos = ref([])

const form_rondero = reactive({
  id: undefined,
  persona: {
    id: undefined,
    docIdentidad: null,
    nombres: '',
    apellido_paterno: '',
    apellido_materno: '',
    fecha_nacimiento: '',
    genero: '',
    celular: '',
    direccion: '',
    email: '',
    foto: '',
  },
  fecha_inicio: '',
  fecha_cese: '',
  region_id: '',
  provincia_id: '',
  distrito_id: '',
  sector_zona_id: '',
  base_id: '',
  codigo_rondero: '',
  // Objetos completos que vienen de la API
  region: null,
  provincia: null,
  distrito: null,
  sector: null,
  base: null
})

const openDialog = (id_rondero) => {
  selectedRonderoId.value = id_rondero;
  dialogVisible.value = true;
};

const close_dialog_cargo = () => {
  dialogVisible.value = false;
  emit('close-dialog');
  getCargos();
};

const deleteRowCargo = (index, id_comite) => {
  ElMessageBox.confirm(`Seguro que desea eliminar el cargo?`, 'Atención', {
    confirmButtonText: 'Si, eliminar',
    cancelButtonText: 'Cancelar',
    type: 'warning',
    dangerouslyUseHTMLString: true
  })
    .then(() => {
      comiteResource.destroy(id_comite).then(response => {
        if (response && response.state === 'success') {
          ElNotification({ type: 'success', title: 'Cargo eliminado', duration: 2000 })
          tagsCargos.value.splice(index, 1)
          getCargos();
        }
      })
    })
    .catch(() => {
      ElMessage.info('Operación cancelada');
    });
};

const getCargos = async () => {
  try {
    const response = await comiteResource.getComiteablesByRondero(props.idRondero);
    const { data } = response;
    tagsCargos.value = data || [];
  } catch (error) {
    tagsCargos.value = [];
  }
};

const fetchRegiones = async () => {
  try {
    const response = await regionResource.list();
    const { data } = response;
    optionsRegiones.value = data || [];

    // Si hay una región asignada, seleccionarla
    if (form_rondero.region && form_rondero.region.id) {
      const region = optionsRegiones.value.find(r => r.id === form_rondero.region.id);
      if (region) {
        form_rondero.region_id = region.id;
        await obtenerProvincias(form_rondero.region_id);
      }
    }
  } catch (error) {
    optionsRegiones.value = [];
  }
};

const obtenerProvincias = async (region_id) => {
  // Resetear campos dependientes
  form_rondero.provincia_id = '';
  form_rondero.distrito_id = '';
  form_rondero.sector_zona_id = '';
  form_rondero.base_id = '';
  optionsDistritos.value = [];
  optionsSectores.value = [];
  optionsBases.value = [];

  if (!region_id || region_id === '' || region_id === 0) {
    optionsProvincias.value = [];
    return;
  }

  try {
    const response = await provinciaResource.getProvincias(region_id);
    optionsProvincias.value = response || [];

    // Si hay una provincia asignada, seleccionarla
    if (form_rondero.provincia && form_rondero.provincia.id) {
      const provincia = optionsProvincias.value.find(p => p.id === form_rondero.provincia.id);
      if (provincia) {
        form_rondero.provincia_id = provincia.id;
        await obtenerDistritos(form_rondero.provincia_id);
      }
    }
  } catch (error) {
    optionsProvincias.value = [];
  }
};

const obtenerDistritos = async (provincia_id) => {
  // Resetear campos dependientes
  form_rondero.distrito_id = '';
  form_rondero.sector_zona_id = '';
  form_rondero.base_id = '';
  optionsSectores.value = [];
  optionsBases.value = [];

  if (!provincia_id || provincia_id === '' || provincia_id === 0) {
    optionsDistritos.value = [];
    return;
  }

  try {
    const response = await distritoResource.getDistritos(provincia_id);
    optionsDistritos.value = response || [];

    // Si hay un distrito asignado, seleccionarlo
    if (form_rondero.distrito && form_rondero.distrito.id) {
      const distrito = optionsDistritos.value.find(d => d.id === form_rondero.distrito.id);
      if (distrito) {
        form_rondero.distrito_id = distrito.id;
        await onDistritoChange();
      }
    }
  } catch (error) {
    optionsDistritos.value = [];
  }
};

const cargarBasesPorDistrito = async () => {
  form_rondero.base_id = '';
  optionsBases.value = [];

  if (form_rondero.distrito_id && form_rondero.distrito_id !== '' && form_rondero.distrito_id !== 0) {
    try {
      const response = await baseResource.getBasesPorDistrito(form_rondero.distrito_id);
      optionsBases.value = response.data || response || [];

      // Si hay una base asignada, seleccionarla
      if (form_rondero.base && form_rondero.base.id) {
        const base = optionsBases.value.find(b => b.id === form_rondero.base.id);
        if (base) {
          form_rondero.base_id = base.id;
        }
      }
    } catch (error) {
      optionsBases.value = [];
    }
  }
};

const onDistritoChange = async () => {
  form_rondero.sector_zona_id = '';
  form_rondero.base_id = '';
  optionsSectores.value = [];
  optionsBases.value = [];

  if (form_rondero.distrito_id && form_rondero.distrito_id !== '' && form_rondero.distrito_id !== 0) {
    try {
      const response = await sectorResource.getSectores(form_rondero.distrito_id);
      optionsSectores.value = response || [];

      // Si hay un sector asignado, seleccionarlo
      if (form_rondero.sector && form_rondero.sector.id) {
        const sector = optionsSectores.value.find(s => s.id === form_rondero.sector.id);
        if (sector) {
          form_rondero.sector_zona_id = sector.id;
        }
      }
    } catch (error) {
      optionsSectores.value = [];
    }

    await cargarBasesPorDistrito();
  }
};

const onSectorChange = async () => {
  form_rondero.base_id = '';
  optionsBases.value = [];

  if (form_rondero.sector_zona_id && form_rondero.sector_zona_id !== '' && form_rondero.sector_zona_id !== 0) {
    try {
      const response = await baseResource.getBases(form_rondero.sector_zona_id);
      optionsBases.value = response.data || response || [];

      // Si hay una base asignada, seleccionarla
      if (form_rondero.base && form_rondero.base.id) {
        const base = optionsBases.value.find(b => b.id === form_rondero.base.id);
        if (base) {
          form_rondero.base_id = base.id;
        }
      }
    } catch (error) {
      optionsBases.value = [];
    }
  } else if (form_rondero.distrito_id && form_rondero.distrito_id !== '' && form_rondero.distrito_id !== 0) {
    await cargarBasesPorDistrito();
  } else {
    optionsBases.value = [];
  }
};

const cargarRegistro = async () => {
  resetForm();
  loading.value = true;

  try {
    const response = await ronderoRequest.get(props.idRondero);
    const { data } = response;

    // Asignar todos los datos del rondero
    Object.assign(form_rondero, data);

    if (data.persona && data.persona.id) {
      form_rondero.persona.id = data.persona.id;
      form_rondero.id = data.id;
    }

    // Cargar regiones y luego el resto de ubicaciones
    await fetchRegiones();
    await getCargos();

  } catch (error) {
    ElNotification({ type: 'error', title: 'Error al recuperar el registro', duration: 2000 });
    close('canceled');
  } finally {
    loading.value = false;
  }
};

onMounted(() => {
  cargarRegistro();
});

const rules = reactive({
  'persona.apellido_paterno': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  'persona.apellido_materno': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  'persona.nombres': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  'persona.docIdentidad': [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
  'persona.email': [
    {
      type: 'email',
      message: 'Ingrese un email válido',
      trigger: 'blur'
    }
  ],
});

const submitForm = async (formEl) => {
  if (!formEl) return;
  loading.value = true;

  await formEl.validate(async (valid) => {
    if (valid) {
      try {
        const datosParaEnviar = {
          id: form_rondero.id,
          fecha_inicio: form_rondero.fecha_inicio || null,
          fecha_cese: form_rondero.fecha_cese || null,
          estado: form_rondero.estado !== undefined ? form_rondero.estado : true,
          region_id: form_rondero.region_id ? Number(form_rondero.region_id) : null,
          provincia_id: form_rondero.provincia_id ? Number(form_rondero.provincia_id) : null,
          distrito_id: form_rondero.distrito_id ? Number(form_rondero.distrito_id) : null,
          sector_zona_id: form_rondero.sector_zona_id ? Number(form_rondero.sector_zona_id) : null,
          base_id: form_rondero.base_id ? Number(form_rondero.base_id) : null,
          persona: {
            id: form_rondero.persona.id,
            docIdentidad: form_rondero.persona.docIdentidad,
            nombres: form_rondero.persona.nombres,
            apellido_paterno: form_rondero.persona.apellido_paterno,
            apellido_materno: form_rondero.persona.apellido_materno,
            fecha_nacimiento: form_rondero.persona.fecha_nacimiento || null,
            genero: form_rondero.persona.genero || null,
            direccion: form_rondero.persona.direccion || null,
            celular: form_rondero.persona.celular || null,
            email: form_rondero.persona.email || null,
            foto: form_rondero.persona.foto || null
          }
        };

        await ronderoRequest.update(form_rondero.id, datosParaEnviar);

        ElNotification({
          type: 'success',
          title: 'Rondero actualizado',
          message: 'Los cambios se guardaron correctamente',
          duration: 3000
        });

        close('success');

      } catch (error) {
        if (error.response) {
          if (error.response.status === 422 && error.response.data.errors) {
            const errors = error.response.data.errors;
            Object.keys(errors).forEach(key => {
              ElMessage.error(`${key}: ${errors[key].join(', ')}`);
            });
          } else if (error.response.data && error.response.data.message) {
            ElMessage.error(`Error ${error.response.status}: ${error.response.data.message}`);
          } else {
            ElMessage.error(`Error ${error.response.status}: Error al actualizar`);
          }
        } else if (error.request) {
          ElMessage.error('No se recibió respuesta del servidor.');
        } else {
          ElMessage.error(`Error: ${error.message}`);
        }
      } finally {
        loading.value = false;
      }
    } else {
      loading.value = false;
      ElMessage.warning('Por favor, complete todos los campos requeridos');
    }
  });
};

const resetForm = () => {
  Object.assign(form_rondero, {
    id: undefined,
    persona: {
      id: undefined,
      docIdentidad: null,
      nombres: '',
      apellido_paterno: '',
      apellido_materno: '',
      fecha_nacimiento: '',
      genero: '',
      celular: '',
      direccion: '',
      email: '',
      foto: '',
    },
    fecha_inicio: '',
    fecha_cese: '',
    region_id: '',
    provincia_id: '',
    distrito_id: '',
    sector_zona_id: '',
    base_id: '',
    codigo_rondero: '',
    region: null,
    provincia: null,
    distrito: null,
    sector: null,
    base: null
  });
  optionsProvincias.value = [];
  optionsDistritos.value = [];
  optionsSectores.value = [];
  optionsBases.value = [];
};

const close = (status) => {
  emit('close', status);
  resetForm();
};
</script>

<style scoped>
.editRondero .image-slot {
  display: flex;
  justify-content: center;
  align-items: center;
  width: 100%;
  height: 100%;
  background: var(--el-fill-color-light);
  color: var(--el-text-color-secondary);
  font-size: 30px;
}

.text-muted {
  color: #666;
  font-size: 12px;
  display: block;
  margin-top: 4px;
}
</style>