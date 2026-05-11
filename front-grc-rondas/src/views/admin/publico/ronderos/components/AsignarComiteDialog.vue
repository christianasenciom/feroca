<template>
  <div v-loading="loading">
    <el-dialog
        v-model="dialogVisible"
        title="Asignar Comité"
        :width="calcularAnchoDialog('45%','98%')"
        @close="closeDialog"
    >
      <el-form ref="asignarComiteForm" :rules="rules" label-position="top" v-on:submit.prevent>
        <el-row :gutter="12">
          <el-col :xs="24">
            <!-- Paso 1: Seleccionar tipo de entidad -->
            <el-form-item label="Entidad" prop="comiteable_type">
              <el-select 
                v-model="selectedComiteableType" 
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
                  v-model="selectedComiteableId"
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
                    v-model="fecha_inicio" 
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
                    v-model="fecha_fin" 
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
                  v-model="selectedCargo"
                  filterable
                  :options="cargos"
                  placeholder="Seleccione un cargo"
                  clearable
                  :loading="cargandoCargos"
                  :disabled="!selectedComiteableId || !fecha_inicio || !fecha_fin"
              >
                <el-option 
                  v-for="item in cargos" 
                  :key="item.value" 
                  :label="item.label" 
                  :value="item.value" 
                />
                <template #empty>
                  <div style="text-align: center; padding: 10px;">
                    {{ cargandoCargos ? 'Cargando...' : 'No hay cargos disponibles para este período' }}
                  </div>
                </template>
              </el-select>
            </el-form-item>
          </el-col>
        </el-row>
      </el-form>

      <template #footer>
        <el-button @click="closeDialog">Cancelar</el-button>
        <el-button type="primary" @click="guardarComite" :loading="guardando" :disabled="!selectedCargo">
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
      selectedCargo: null,
      selectedComiteableType: '',
      selectedComiteableId: null,
      dialogVisible: true,
      loading: false,
      guardando: false,
      cargandoCargos: false,
      cargandoComiteables: false,
      fecha_inicio: null,
      fecha_fin: null,
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
      switch (this.selectedComiteableType) {
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
  methods: {
    calcularAnchoDialog,
    
    async onComiteableTypeChange() {
      console.log('📌 Cambió tipo de entidad:', this.selectedComiteableType);
      this.selectedComiteableId = null;
      this.selectedCargo = null;
      this.cargos = [];
      this.fecha_inicio = null;
      this.fecha_fin = null;
      
      if (this.selectedComiteableType && this.selectedComiteableType !== '') {
        await this.fetchComiteables();
      } else {
        this.comiteables = [];
      }
    },
    
    async onComiteableChange() {
      console.log('📌 Cambió entidad seleccionada:', this.selectedComiteableId);
      this.selectedCargo = null;
      this.cargos = [];
      // No cargar cargos hasta que se seleccionen las fechas
    },
    
    async onFechaChange() {
      console.log('📌 Cambió fecha - inicio:', this.fecha_inicio, 'fin:', this.fecha_fin);
      if (this.selectedComiteableId && this.fecha_inicio && this.fecha_fin) {
        await this.fetchAvailableCargos();
      }
    },
    
    async fetchAvailableCargos() {
      console.log('🔍 fetchAvailableCargos llamado');
      console.log('  - selectedComiteableId:', this.selectedComiteableId);
      console.log('  - selectedComiteableType:', this.selectedComiteableType);
      console.log('  - fecha_inicio:', this.fecha_inicio);
      console.log('  - fecha_fin:', this.fecha_fin);
      
      if (!this.selectedComiteableId || !this.selectedComiteableType) {
        console.log('⚠️ Faltan datos obligatorios (entidad)');
        this.cargos = [];
        return;
      }
      
      if (!this.fecha_inicio || !this.fecha_fin) {
        console.log('⚠️ Faltan fechas');
        this.cargos = [];
        return;
      }
      
      this.cargandoCargos = true;
      try {
        const payload = {
          comiteable_id: this.selectedComiteableId,
          comiteable_type: this.selectedComiteableType,
          fecha_inicio: this.fecha_inicio,
          fecha_fin: this.fecha_fin
        };
        
        console.log('📤 Enviando payload:', payload);
        
        const response = await comiteResource.getAvailableCargos(payload);
        
        console.log('✅ Respuesta de getAvailableCargos:', response);
        
        let cargosData = [];
        if (response && response.data && Array.isArray(response.data)) {
          cargosData = response.data;
        } else if (Array.isArray(response)) {
          cargosData = response;
        }
        
        this.cargos = cargosData.map((cargo) => ({
          value: cargo.id,
          label: cargo.descripcion,
        }));
        
        console.log('📋 Cargos formateados:', this.cargos);
        
        if (this.cargos.length === 0) {
          console.log('⚠️ No hay cargos disponibles para estos filtros');
        }
      } catch (error) {
        console.error('❌ Error fetching available cargos:', error);
        console.error('Detalles:', error.response?.data);
        this.cargos = [];
      } finally {
        this.cargandoCargos = false;
      }
    },
    
    async fetchComiteables() {
      console.log('🔍 fetchComiteables llamado para tipo:', this.selectedComiteableType);
      this.cargandoComiteables = true;
      try {
        const response = await comiteResource.getComiteables(this.selectedComiteableType);
        console.log('✅ Comiteables recibidos:', response);
        
        let comiteablesData = [];
        if (response && response.data && Array.isArray(response.data)) {
          comiteablesData = response.data;
        } else if (Array.isArray(response)) {
          comiteablesData = response;
        }
        
        this.comiteables = comiteablesData.map((item) => ({
          value: item.id,
          label: item.nombre_descripcion || item.descripcion || item.nombre,
        }));
        
        console.log('📋 Comiteables formateados:', this.comiteables);
      } catch (error) {
        console.error('❌ Error fetching comiteables:', error);
        console.error('Detalles:', error.response?.data);
        this.comiteables = [];
      } finally {
        this.cargandoComiteables = false;
      }
    },
    
    resetForm() {
      this.selectedCargo = null;
      this.selectedComiteableType = '';
      this.selectedComiteableId = null;
      this.fecha_inicio = null;
      this.fecha_fin = null;
      this.cargos = [];
      this.comiteables = [];
    },
    
    async guardarComite() {
      if (!this.selectedCargo) {
        this.$message.error('Por favor seleccione un cargo');
        return;
      }
      if (!this.selectedComiteableId) {
        this.$message.error('Por favor seleccione una entidad');
        return;
      }
      if (!this.fecha_inicio) {
        this.$message.error('Por favor seleccione una fecha de inicio');
        return;
      }
      if (!this.fecha_fin) {
        this.$message.error('Por favor seleccione una fecha de fin');
        return;
      }
      
      this.guardando = true;
      try {
        const payload = {
          rondero_id: this.ronderoId,
          cargo_id: this.selectedCargo,
          comiteable_id: this.selectedComiteableId,
          comiteable_type: this.selectedComiteableType,
          fecha_inicio: this.fecha_inicio,
          fecha_fin: this.fecha_fin
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