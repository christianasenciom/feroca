<template>
  <section class="BasesView">
  <div>
    <div class="el-header">
      <h3>Lista de Bases Acreditadas</h3>
    </div>
    <el-table border fit :data="tableData" class="py-4" v-loading="listLoading">
      <el-table-column label="ID" prop="id" align="center" width="70" />
      <el-table-column label="Region" prop="sector_zona.distrito.provincia.region.descripcion" />
      <el-table-column label="Provincia" prop="sector_zona.distrito.provincia.descripcion" />
      <el-table-column label="Distrito" prop="sector_zona.distrito.descripcion" />
      <el-table-column label="Sector" prop="sector_zona.descripcion"  width="240"/>
      <el-table-column label="Base" prop="descripcion" />
      <el-table-column prop="estado" label="Estado">
        <template #default="scope">
          <!--          {{ scope.row.estado ? 'Activo' : 'Inactivo' }}-->
          <el-tag v-if="scope.row.estado" type="success">Activo</el-tag>
          <el-tag v-else type="danger">Inactivo</el-tag>
        </template>
      </el-table-column>
    </el-table>
    <div class="paging">
      <el-pagination v-if="meta.total > listQuery.limit" background v-model:currentPage="meta.current_page"
        layout="total, prev, next, pager" :total="meta.total" @current-change="handleCurrentChange"
        :page-size="meta.per_page" />
    </div>

  </div>
  </section>
</template>
<style scoped>
.BasesView
{
  padding: 20px;
  background: #f4f4f4;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap;
  flex-direction: row;
  align-content: flex-start;
  padding-top: 20px;
}
</style>
<script>
import {ref, reactive, onMounted} from 'vue';
import { ElMessage } from 'element-plus';
import { Edit, ArrowDown, Check, Close} from '@element-plus/icons-vue';

import { useAuthStore } from "@/stores/AuthStore";
import BaseResource from '@/api/publico/base';
import {isActionDisabled} from "@/utils/utils.js";


export default {
  name: 'BasesView',
  components: {Edit, Close, Check, ArrowDown},
  methods: {isActionDisabled},
  setup() {
    const baseResource = new BaseResource();
    const authStore = useAuthStore()

    const descripcionField = ref(null);

    const modelBase = reactive({
      id: undefined,
      descripcion: '',
      sector_zona_id: '',
    });



    const tableData = ref([]);
    const meta = ref({});
    const listLoading = ref(true);

    const listQuery = reactive({
      page: 1,
      limit: 8,
      keyword: ''
    });

    const visibleDialogForm = ref(false);
    const titleDialogForm = ref('');
    const loadingSaveDialogForm = ref(false);
    const optionsSectors = ref([]);


    const handleBuscarDatos = () => {
      listQuery.page = 1;
      fetchBases();
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
        .catch(error => {
          console.error('Error fetching data:', error);
          ElMessage.error('Error al obtener datos');
        })
        .finally(() => {
          listLoading.value = false;
        });
    };

    onMounted(fetchBases);

    return {
      authStore,
      modelBase,
      tableData,
      meta,
      listLoading,
      listQuery,
      visibleDialogForm,
      titleDialogForm,
      loadingSaveDialogForm,
      descripcionField,
      handleBuscarDatos,
      handleCurrentChange,
      optionsSectors,
    };
  }
};
</script>
