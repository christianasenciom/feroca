<template>
  <section class="BasesView">
    <div>
      <div class="el-header">
        <h3>Lista de Bases Acreditadas</h3>
      </div>
      <el-table border fit :data="tableData" class="py-4" v-loading="listLoading">
        <el-table-column label="ID" prop="id" align="center" width="70" />
        <el-table-column label="Region">
          <template #default="scope">
            {{ scope.row.region?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Provincia">
          <template #default="scope">
            {{ scope.row.provincia?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Distrito">
          <template #default="scope">
            {{ scope.row.distrito?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Sector" width="240">
          <template #default="scope">
            {{ scope.row.sector?.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Base">
          <template #default="scope">
            {{ scope.row.nombre_descripcion || scope.row.descripcion || '-' }}
          </template>
        </el-table-column>
        <el-table-column label="Partida Registral" width="120">
          <template #default="scope">
            {{ scope.row.numero_partida_registral || '-' }}
          </template>
        </el-table-column>
        <el-table-column prop="estado" label="Estado">
          <template #default="scope">
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
.BasesView {
  padding: 20px;
  background: #f4f4f4;
  justify-content: center;
  align-items: center;
  flex-wrap: nowrap;
  flex-direction: row;
  align-content: flex-start;
  padding-top: 20px;
}
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
import {ref, reactive, onMounted} from 'vue';
import { ElMessage } from 'element-plus';
import { Edit, ArrowDown, Check, Close} from '@element-plus/icons-vue';
import { useAuthStore } from "@/stores/AuthStore";
import BaseResource from '@/api/publico/base';

export default {
  name: 'BasesView',
  components: {Edit, Close, Check, ArrowDown},
  setup() {
    const baseResource = new BaseResource();
    const authStore = useAuthStore();

    const tableData = ref([]);
    const meta = ref({});
    const listLoading = ref(true);

    const listQuery = reactive({
      page: 1,
      limit: 8,
      keyword: ''
    });

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
          console.log('📋 Bases cargadas (vista pública):', response.data.length);
        })
        .catch(error => {
          console.error('Error fetching data:', error);
          ElMessage.error('Error al obtener bases');
        })
        .finally(() => {
          listLoading.value = false;
        });
    };

    onMounted(fetchBases);

    return {
      authStore,
      tableData,
      meta,
      listLoading,
      listQuery,
      handleCurrentChange,
    };
  }
};
</script>