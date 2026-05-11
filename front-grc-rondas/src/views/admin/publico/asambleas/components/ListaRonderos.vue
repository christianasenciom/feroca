<template>
  <el-row :gutter="10" class="pt-3">
    <el-col :span="24">
      <el-form-item prop="inputBuscar">
        <el-input
            ref="inputBuscarField"
            v-model="query.keyword"
            placeholder="Buscar rondero"
            @clear="handleBuscar"
            clearable
            @keyup.enter="handleBuscar"
        >
          <template #append>
            <el-button
                type="primary"
                class="ache-button-busqueda-color"
                :icon="Search"
                @click="handleBuscar"
            >
              <span style="vertical-align: middle">Buscar</span>
            </el-button>
          </template>
        </el-input>
      </el-form-item>
    </el-col>
  </el-row>
  <el-table :data="ronderosListDialog" style="width: 100%" v-loading="loadingTable">
    <el-table-column prop="persona.nombres" label="Nombre" />
    <el-table-column label="Apellidos">
      <template #default="scope">
        {{ scope.row.persona.apellido_paterno }} {{ scope.row.persona.apellido_materno }}
      </template>
    </el-table-column>
    <el-table-column prop="persona.docIdentidad" label="DNI" width="120" />
    <el-table-column fixed align="center" width="65">
      <template #default="scope">
        <el-button
            size="small"
            type="success"
            class="ache-background-color-template"
            :icon="Plus"
            @click="handleAgregarAlPadre(scope.$index, scope.row)"
        >
        </el-button>
      </template>
    </el-table-column>
  </el-table>
  <div class="paging">
    <el-pagination
        background
        v-model:currentPage="meta_list.current_page"
        layout="prev, next, pager"
        :total="meta_list.total"
        @current-change="handleCurrentChangeInfrac"
        :page-size="meta_list.per_page"
    />

  </div>
</template>
<script setup>
import BaseResource from '@/api/publico/base';
const baseResource = new BaseResource();
import {nextTick, reactive, ref} from 'vue';
import { onMounted } from 'vue'
import {ElMessage} from "element-plus";
import {Plus, Search} from "@element-plus/icons-vue";
const query = reactive({
  keyword: '',
  limit: 8,
  page: 1,
  orderby: 'ASC',
  ids_excluir: [],
  base_id: 0,
});
const meta_list = ref([])
const emit = defineEmits(['closeListRonderos', 'enviar-rondero','ids-excluir']);

onMounted(() => {
  fetchListaRonderos();
})

const handleBuscar = () =>{
  query.page = 1;
  fetchListaRonderos();
}

const props = defineProps({
  baseId: {
    type: Number,
    required: true,
    default: 0
  },
  idsExcluir: {
    type: Array,
    required: false,
    default: () => []
  }
})

const ronderosListDialog = ref([]);
const loadingTable = ref(false);
const inputBuscarField = ref(null);

const fetchListaRonderos = () => {

  loadingTable.value = true;

  query.base_id = props.baseId
  query.ids_excluir = props.idsExcluir
  baseResource.getRonderosByBase(query)
      .then(response => {
        const { data } = response;
        ronderosListDialog.value = data;
        meta_list.value = response.meta;
        loadingTable.value = false;
        nextTick(() => {
          inputBuscarField.value.focus()
        })
      })
      .catch(error => {
        console.error('Error fetching data:', error);
        ElMessage.error('Error al obtener datos');
      });
}

const handleCurrentChangeInfrac = (val) =>{
  // console.log(`current page: ${val}`);
  query.page = val;
  fetchListaRonderos()
}

const handleAgregarAlPadre = (index, row) =>{
  let rowRondero = {
    id: row.id,
    rondero_id: row.id,
    persona_id: row.persona.id,
    docIdentidad: row.persona.docIdentidad,
    apellido_paterno: row.persona.apellido_paterno,
    apellido_materno: row.persona.apellido_materno,
    nombres: row.persona.nombres,
  };
  // console.log(row.descripcion);
  query.ids_excluir.push(row.id);
  emit('enviar-rondero', rowRondero); // Emitir evento con los datos
  emit('ids-excluir', row.id); // Emitir evento con los datos
  ronderosListDialog.value.splice(index, 1)
}

</script>
<style scoped>

</style>
