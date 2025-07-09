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
            <el-form-item label="Entidad" prop="comiteable_type">
              <el-select v-model="selectedComiteableType" @change="fetchComiteables" placeholder="Seleccione un tipo de entidad">
                <el-option label="Región" value="App\Models\Publico\Region" />
                <el-option label="Provincia" value="App\Models\Publico\Provincia" />
                <el-option label="Distrito" value="App\Models\Publico\Distrito" />
                <el-option label="Sector" value="App\Models\Publico\Sector" />
                <el-option label="Base" value="App\Models\Publico\Base" />
              </el-select>
            </el-form-item>

            <el-form-item :label="'Seleccionar '+ selectedComiteableTypeLabel" prop="comiteable_id">
              <el-select-v2
                  v-model="selectedComiteableId"
                  filterable
                  :options="comiteables"
                  placeholder="Seleccione "
                  clearable
                  @visible-change="handleVisibleChangeComiteables"
              >
                <template #default="{ item }">
                  <span>{{ item.label }}</span>
                </template>

              </el-select-v2>
              <!--            <el-select v-model="selectedComiteableId" placeholder="Seleccione una opción">-->
              <!--              <el-option v-for="comiteable in comiteables" :key="comiteable.id" :label="comiteable.descripcion" :value="comiteable.id" />-->
              <!--            </el-select>-->
            </el-form-item>

            <el-form-item label="Fecha inicio" prop="fecha_inicio">
              <el-date-picker v-model="fecha_inicio" type="date" placeholder="Seleccione una fecha" format="DD/MM/YYYY" />
            </el-form-item>
            <el-form-item label="Fecha fin" prop="fecha_fin">
              <el-date-picker v-model="fecha_fin" type="date" placeholder="Seleccione una fecha" format="DD/MM/YYYY" />
            </el-form-item>
            <el-form-item label="Cargo" prop="cargo_id">
              <el-select-v2
                  v-model="selectedCargo"
                  filterable
                  :options="cargos"
                  placeholder="Seleccione un cargo"
                  clearable
              >
                <template #default="{ item }">
                  <span>{{ item.label }}</span>
                </template>

              </el-select-v2>
              <!--            <el-select v-model="selectedCargo" placeholder="Seleccione un cargo">-->
              <!--              <el-option v-for="cargo in cargos" :key="cargo.id" :label="cargo.descripcion" :value="cargo.id" />-->
              <!--            </el-select>-->
            </el-form-item>

          </el-col>
        </el-row>
      </el-form>

      <template #footer>
        <el-button type="primary" @click="guardarComite">Guardar</el-button>
      </template>
    </el-dialog>
  </div>
</template>

<script>
import {calcularAnchoDialog} from "@/utils/responsive.js";
import CargoResource from "@/api/publico/cargo";
import ComiteResource from "@/api/publico/comite";
import {ref} from "vue";
const cargoResource = new CargoResource();
const comiteResource = new ComiteResource();
export default {
  props: {
    ronderoId: {
      type: Number,
      required: true,
    },
  },
  data() {
    return {
      cargos: ref([]),
      comiteables: ref([]), // Regiones, Provincias, Distritos, Sectores y Bases,
      selectedCargo: ref(null),
      selectedComiteableType: ref('App\\Models\\Publico\\Region'),
      selectedComiteableId: ref(null),
      dialogVisible: ref(true),
      loading: ref(false),
      fecha_inicio: ref(null),
      fecha_fin: ref(null),
      rules: {
        cargo_id: [
          { required: true, message: 'Por favor seleccione un cargo', trigger: 'blur' },
        ],
        comiteable_type: [
          { required: true, message: 'Por favor seleccione un tipo de entidad', trigger: 'blur' },
        ],
        comiteable_id: [
          { required: true, message: 'Por favor seleccione una entidad', trigger: 'blur' },
        ],
        fecha_inicio: [
          { required: true, message: 'Por favor seleccione una fecha', trigger: 'blur' },
        ],
        fecha_fin: [
          { required: true, message: 'Por favor seleccione una fecha', trigger: 'blur' },
        ],
      }
    };
  },
  computed: {
    // eslint-disable-next-line vue/return-in-computed-property
    selectedComiteableTypeLabel() {
      switch (this.selectedComiteableType) {
        case 'App\\Models\\Publico\\Region': return 'Región';
        case 'App\\Models\\Publico\\Provincia': return 'Provincia';
        case 'App\\Models\\Publico\\Distrito': return 'Distrito';
        case 'App\\Models\\Publico\\Sector': return 'Sector';
        case 'App\\Models\\Publico\\Base': return 'Base';
      }
    },
  },
  watch: {
    // Llama a fetchAvailableCargos cuando cambien los filtros
    comiteable_id: 'fetchAvailableCargos',
    comiteable_type: 'fetchAvailableCargos',
    fecha_inicio: 'fetchAvailableCargos',
    fecha_fin: 'fetchAvailableCargos',
  },
  methods: {
    async fetchAvailableCargos() {
      try {
        if (this.selectedComiteableId && this.selectedComiteableType && this.fecha_inicio && this.fecha_fin) {
            await comiteResource.getAvailableCargos( {
              comiteable_id: this.selectedComiteableId,
              comiteable_type: this.selectedComiteableType,
              fecha_inicio: this.fecha_inicio,
              fecha_fin: this.fecha_fin
            }).then((response) => {
              this.cargos = response.data;
              this.cargos = this.cargos.map((cargo) => ({
                value: cargo.id,  // Asegúrate de que coincide con los datos de tu API
                label: cargo.descripcion,
              }));
            });
        }
      } catch (error) {
        console.error('Error fetching available cargos:', error);
      }
    },
    calcularAnchoDialog,
    async fetchComiteables() {
      this.resetAgainSelectedComiteable();
      const response = await comiteResource.getComiteables(this.selectedComiteableType);
      // this.comiteables = response;
      this.comiteables = response.map((item) => ({
        value: item.id,  // Asegúrate de que coincide con los datos de tu API
        label: item.descripcion,
      }));
    },
    // Maneja la apertura del dropdown para recargar opciones si es necesario
    handleVisibleChangeComiteables (visible) {
      if (visible && this.comiteables.length === 0) {
        this.fetchComiteables();
      }
    },
    resetForm() {
      this.selectedCargo = null;
      this.selectedComiteableType = 'App\\Models\\Publico\\Region';
      this.selectedComiteableId = null;
    },
    resetAgainSelectedComiteable() {
      this.selectedComiteableId = null;
      this.selectedCargo = null;
      this.fecha_fin = null;
      this.fecha_inicio = null;
    },
    async guardarComite() {
      await comiteResource.store({
        rondero_id: this.ronderoId, // Aquí se usa la prop ronderoId
        cargo_id: this.selectedCargo,
        comiteable_id: this.selectedComiteableId,
        comiteable_type: this.selectedComiteableType,
        fecha_inicio: this.fecha_inicio,
        fecha_fin: this.fecha_fin
      })
      this.$emit("close-dialog");
      this.resetForm();
    },
    closeDialog() {
      this.dialogVisible = false;
      this.$emit("close-dialog");
      this.resetForm();
    },
  },
  mounted() {
    this.fetchComiteables();
  },
};
</script>
