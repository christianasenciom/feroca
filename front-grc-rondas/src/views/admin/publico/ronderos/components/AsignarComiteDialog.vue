<template>
  <div v-loading="loading">
    <el-dialog
        v-model="dialogVisible"
        title="Asignar Comité"
        :width="calcularAnchoDialog('45%','98%')"
        @close="closeDialog"
    >
      <el-form ref="asignarComiteForm" :model="formModel" :rules="rules" label-position="top" v-on:submit.prevent>
        <el-row :gutter="12">
          <el-col :xs="24">
            <!-- Paso 1: Seleccionar tipo de entidad -->
            <el-form-item label="Entidad" prop="comiteable_type">
              <el-select 
                v-model="formModel.comiteable_type"
                @change="onComiteableTypeChange"
                placeholder="Seleccione un tipo de entidad"
                clearable
              >
                <el-option label="Región" value="App\Models\Publico\Region" />
                <el-option label="Provincia" value="App\Models\Publico\Provincia" />
                <el-option label="Distrito" value="App\Models\Publico\Distrito" />
                <el-option label="Sector" value="App\Models\Publico\Sector" />
                <el-option label="Base" value="App\Models\Publico\Base" />
              </el-select>
            </el-form-item>

            <!-- Paso 2: Seleccionar la entidad específica -->
            <el-form-item :label="'Seleccionar ' + selectedComiteableTypeLabel" prop="comiteable_id">
              <el-select
                  v-model="formModel.comiteable_id"
                  filterable
                  :options="comiteables"
                  placeholder="Seleccione una opción"
                  clearable
                  @change="onComiteableChange"
                  :loading="cargandoComiteables"
              >
                <el-option 
                  v-for="item in comiteables" 
                  :key="item.value" 
                  :label="item.label" 
                  :value="item.value" 
                />
              </el-select>
            </el-form-item>

            <!-- Paso 3: Seleccionar fechas -->
            <el-row :gutter="12">
              <el-col :span="12">
                <el-form-item label="Fecha inicio" prop="fecha_inicio">
                  <el-date-picker 
                    v-model="formModel.fecha_inicio"
                    type="date"
                    placeholder="Seleccione una fecha" 
                    format="DD/MM/YYYY" 
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                    @change="onFechaChange"
                  />
                </el-form-item>
              </el-col>
              <el-col :span="12">
                <el-form-item label="Fecha fin" prop="fecha_fin">
                  <el-date-picker 
                    v-model="formModel.fecha_fin"
                    type="date"
                    placeholder="Seleccione una fecha" 
                    format="DD/MM/YYYY" 
                    value-format="YYYY-MM-DD"
                    style="width: 100%"
                    @change="onFechaChange"
                  />
                </el-form-item>
              </el-col>
            </el-row>
            
            <!-- Paso 4: Seleccionar cargo -->
            <el-form-item label="Cargo" prop="cargo_id">
              <el-select
                  v-model="formModel.cargo_id"
                  filterable
                  :options="cargos"
                  placeholder="Seleccione un cargo"
                  clearable
                  :loading="cargandoCargos"
              >
                <el-option 
                  v-for="item in cargos" 
                  :key="item.value" 
                  :label="item.label" 
                  :value="item.value" 
                />
                <template #empty>
                  <div style="text-align: center; padding: 10px;">
                    {{ cargandoCargos ? 'Cargando...' : 'No hay cargos registrados' }}
                  </div>
                </template>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <template #footer>
        <el-button @click="closeDialog">Cancelar</el-button>
        <el-button type="primary" @click="guardarComite" :loading="guardando" :disabled="!formModel.cargo_id">
          Guardar
        </el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import { calcularAnchoDialog } from "@/utils/responsive.js";
import ComiteResource from "@/api/publico/comite";

const comiteResource = new ComiteResource();

export default {
  props: {
    ronderoId: {
      type: Number,
      required: true,
      default: 0
    },
  },
  data() {
    return {
      cargos: [],
      comiteables: [],
      formModel: {
        cargo_id: null,
        comiteable_type: '',
        comiteable_id: null,
        fecha_inicio: null,
        fecha_fin: null,
      },
      dialogVisible: true,
      loading: false,
      guardando: false,
      cargandoCargos: false,
      cargandoComiteables: false,
      rules: {
        comiteable_type: [
          { required: true, message: 'Por favor seleccione un tipo de entidad', trigger: 'change' },
        ],
        comiteable_id: [
          { required: true, message: 'Por favor seleccione una entidad', trigger: 'change' },
        ],
        fecha_inicio: [
          { required: true, message: 'Por favor seleccione una fecha de inicio', trigger: 'change' },
        ],
        fecha_fin: [
          { required: true, message: 'Por favor seleccione una fecha de fin', trigger: 'change' },
        ],
        cargo_id: [
          { required: true, message: 'Por favor seleccione un cargo', trigger: 'change' },
        ],
      }
    };
  },
  computed: {
    selectedComiteableTypeLabel() {
      switch (this.formModel.comiteable_type) {
        case 'App\\Models\\Publico\\Region': return 'Región';
        case 'App\\Models\\Publico\\Provincia': return 'Provincia';
        case 'App\\Models\\Publico\\Distrito': return 'Distrito';
        case 'App\\Models\\Publico\\Sector': return 'Sector';
        case 'App\\Models\\Publico\\Base': return 'Base';
        default: return 'Entidad';
      }
    },
  },
  watch: {
    ronderoId: {
      handler(newVal) {
        if (newVal) {
          console.log('🔄 AsignarComiteDialog - ronderoId recibido:', newVal);
          this.resetForm();
        }
      },
      immediate: true
    }
  },
  mounted() {
    this.fetchAllCargos();
  },
  methods: {
    calcularAnchoDialog,

    async fetchAllCargos() {
      this.cargandoCargos = true;
      try {
        const response = await comiteResource.getAllCargos();
        // request.js devuelve el payload directo
        const items = Array.isArray(response)
          ? response
          : (Array.isArray(response?.data) ? response.data : []);
        this.cargos = items.map((c) => ({ value: c.id, label: c.descripcion }));
        console.log('📋 Cargos cargados:', this.cargos.length);
      } catch (error) {
        console.error('❌ Error al cargar cargos:', error);
        this.cargos = [];
      } finally {
        this.cargandoCargos = false;
      }
    },

    async onComiteableTypeChange() {
      console.log('📌 Cambió tipo de entidad:', this.formModel.comiteable_type);
      this.formModel.comiteable_id = null;
      this.formModel.fecha_inicio = null;
      this.formModel.fecha_fin = null;
      this.comiteables = [];

      if (this.formModel.comiteable_type && this.formModel.comiteable_type !== '') {
        await this.fetchComiteables();
      }
    },

    async onComiteableChange() {
      console.log('📌 Cambió entidad seleccionada:', this.formModel.comiteable_id);
    },

    async onFechaChange() {
      // Las fechas ya no controlan el select de cargos
    },

    async fetchComiteables() {
      console.log('🔍 fetchComiteables para tipo:', this.formModel.comiteable_type);
      this.cargandoComiteables = true;
      try {
        const response = await comiteResource.getComiteables(this.formModel.comiteable_type);
        // request.js ya devuelve response.data (el payload directo)
        const items = Array.isArray(response)
          ? response
          : (Array.isArray(response?.data) ? response.data : []);

        this.comiteables = items.map((item) => ({
          value: item.id,
          label: item.nombre_descripcion || item.descripcion || item.nombre || `ID ${item.id}`,
        }));

        console.log('📋 Comiteables cargados:', this.comiteables.length);
      } catch (error) {
        console.error('❌ Error fetching comiteables:', error);
        this.comiteables = [];
      } finally {
        this.cargandoComiteables = false;
      }
    },
    
    resetForm() {
      this.formModel.cargo_id = null;
      this.formModel.comiteable_type = '';
      this.formModel.comiteable_id = null;
      this.formModel.fecha_inicio = null;
      this.formModel.fecha_fin = null;
      this.comiteables = [];
      // No limpiar this.cargos — ya están cargados al montar el componente
    },
    
    async guardarComite() {
      if (!this.formModel.cargo_id) {
        this.$message.error('Por favor seleccione un cargo');
        return;
      }
      if (!this.formModel.comiteable_id) {
        this.$message.error('Por favor seleccione una entidad');
        return;
      }
      if (!this.formModel.fecha_inicio) {
        this.$message.error('Por favor seleccione una fecha de inicio');
        return;
      }
      if (!this.formModel.fecha_fin) {
        this.$message.error('Por favor seleccione una fecha de fin');
        return;
      }
      
      this.guardando = true;
      try {
        const payload = {
          rondero_id: this.ronderoId,
          cargo_id: this.formModel.cargo_id,
          comiteable_id: this.formModel.comiteable_id,
          comiteable_type: this.formModel.comiteable_type,
          fecha_inicio: this.formModel.fecha_inicio,
          fecha_fin: this.formModel.fecha_fin
        };
        
        console.log('📤 Guardando comité:', payload);
        
        const response = await comiteResource.store(payload);
        console.log('✅ Respuesta:', response);
        
        this.$message.success('Cargo asignado correctamente');
        this.$emit("close-dialog");
        this.closeDialog();
      } catch (error) {
        console.error('❌ Error al guardar:', error);
        this.$message.error(error.response?.data?.message || 'Error al asignar el cargo');
      } finally {
        this.guardando = false;
      }
    },
    
    closeDialog() {
      this.dialogVisible = false;
      this.$emit("close-dialog");
      this.resetForm();
    }
  }
};
</script>