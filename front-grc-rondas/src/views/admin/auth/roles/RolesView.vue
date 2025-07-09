<template>
  <div v-loading="loading" >
    <el-row :gutter="12">
      <el-col :xs="24" :sm="24" :md="24" class="actions-component">
        <el-input
          v-model="query.keyword"
          placeholder="Buscar por nombre"
          @change="getLista"
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
    </el-row>
    <el-table
      :data="tableData"
      :border="true"
      style="width: 100%; margin-top: 15px !important;"
      header-row-class-name="table-header-custom"
      row-class-name="table-row-custom"
    >
      <el-table-column type="index" label="#" width="100" />
      <el-table-column prop="name" label="NOMBRE" />
      <el-table-column prop="count_user" label="NRO. USUARIOS" />
      <el-table-column align="center" width="150px">
        <template #header>
          <el-dropdown trigger="click" @command="handleCommandOpciones">
            <span class="el-dropdown-link">
              Opciones<el-icon class="el-icon--right"><arrow-down /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu ref="dropdown">
                <el-dropdown-item
                  :command="{ action: 'ADD' }"
                  :disabled="isActionDisabled('auth.roles.crear')"
                >
                  <span>
                    <v-icon name="hi-plus" style="margin-right: 10px" />
                    Agregar
                  </span>
                </el-dropdown-item>
<!--                <el-dropdown-item-->
<!--                  :command="{ action: 'EXPORT' }"-->
<!--                  :disabled="isActionDisabled('auth.roles.exportar')"-->
<!--                >-->
<!--                  <span>-->
<!--                    <v-icon name="md-excel" style="margin-right: 10px" />-->
<!--                    Exportar-->
<!--                  </span>-->
<!--                </el-dropdown-item>-->
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
        <template #default="scope">
          <el-dropdown trigger="click" @command="handleCommandAcciones">
            <span class="el-dropdown-link">
              Acciones<el-icon class="el-icon--right"><arrow-down /></el-icon>
            </span>
            <template #dropdown>
              <el-dropdown-menu>
                <el-dropdown-item :command="{ item: scope.row, action: 'PERMISSIONS' }" :disabled="isActionDisabled('auth.roles.asignarpermisos')">
                  <span>
                    <v-icon name="md-playlistaddcheck-round" style="margin-right: 10px" />
                    Permisos del rol
                  </span>
                </el-dropdown-item>
                <el-dropdown-item :command="{ item: scope.row, action: 'EDIT' }" :disabled="isActionDisabled('auth.roles.actualizar')">
                  <span>
                    <v-icon name="md-edit-round" style="margin-right: 10px" />
                    Editar
                  </span>
                </el-dropdown-item>
                <el-dropdown-item :command="{ item: scope.row, action: 'DELETE' }" :disabled="isActionDisabled('auth.roles.eliminar')">
                  <span>
                    <v-icon name="md-delete" style="margin-right: 10px" />
                    Eliminar
                  </span>
                </el-dropdown-item>
              </el-dropdown-menu>
            </template>
          </el-dropdown>
        </template>
      </el-table-column>
    </el-table>
    <el-divider />
    <el-row type="flex" justify="center">
      <el-pagination
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
    <el-dialog top="5vh" v-model="openDialogCreate" :width="calcularAnchoDialog('75%','98%')">
      <template #header>Nuevo rol</template>
      <create-role @close="handleCloseCreate" />
    </el-dialog>
    <el-dialog top="5vh" v-model="openDialogEdit" :width="calcularAnchoDialog('75%','98%')" :before-close="bcDialogEdit">
      <template #header>Editar rol</template>
      <edit-role :id-role="idItemToEdit" @close="handleCloseEdit"  />
    </el-dialog>
    <el-dialog top="5vh" v-model="openDialogPermissions"  :width="calcularAnchoDialog('75%','90%')" :before-close="bcDialogPermissions">
      <template #header>Permisos del rol</template>
      <role-permissions
        :id-role="idItemToEditPermissions"
        @close="handleClosePermissions"
        :before-close="handleClosePermissions"
      />
    </el-dialog>
  </div>
</template>

<script setup>
import { ArrowDown, Delete } from '@element-plus/icons-vue'
import CreateRole from './components/CreateRole.vue'
import EditRole from './components/EditRole.vue'
import RolePermissions from './components/RolePermissions.vue'
import { ElMessageBox, ElNotification } from 'element-plus'
import { nextTick, onMounted, reactive, ref, markRaw } from 'vue';
import RoleRequest from '@/api/auth/role';
import { calcularAnchoDialog } from '@/utils/responsive';
import { useAuthStore } from "@/stores/AuthStore";

const roleRequest = new RoleRequest();
const authStore = useAuthStore()
const validPermision = authStore.validPermision

const loading = ref(false);
const openDialogCreate = ref(false);
const openDialogEdit = ref(false);
const openDialogPermissions = ref(false);
const tableData = ref([]);
const total = ref(0);
const query = reactive({
  keyword: '',
  limit: 10,
  page: 1,
});
const idItemToEdit = ref(0);
const idItemToEditPermissions = ref(0);

onMounted(() => {
  getLista()
})

const getLista = () => {
  loading.value = true;
  roleRequest
    .list(query)
    .then((response) => {
      const { data, meta } = response;
      tableData.value = data;
      total.value = meta.total;
      loading.value = false;
    })
    .catch((error) => {
      console.log(error);
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
  console.log(idItemToEdit.value)
};

const handleClosePermissions = () => {
  idItemToEditPermissions.value = 0;
  openDialogPermissions.value = false;
};

const handleCommandOpciones = ({ action }) => {
  // console.log(item, action )
  if (action === 'ADD' && validPermision('auth.roles.crear')) {
    addItem()
  } else if (action === 'EXPORT' && validPermision('auth.roles.exportar')) {
    this.exportarDatos()
  } else {
    ElNotification({message: 'Usted no tiene permiso para realizar esta acción', type: 'info' })
  }
};

const handleCommandAcciones = ({ item, action }) => {
  // console.log(item, action )
  if (action == 'PERMISSIONS' && validPermision('auth.roles.asignarpermisos')) {
    idItemToEditPermissions.value = item.id;
    nextTick(() => {
      openDialogPermissions.value = true;
    });
  } else if (action == 'EDIT' && validPermision('auth.roles.actualizar')) {
    idItemToEdit.value = item.id;
    nextTick(() => {
      openDialogEdit.value = true;
    });
  } else if (action == 'DELETE' && validPermision('auth.roles.eliminar')) {
    const msg = `
    ¿Seguro que desea eliminar el registro?<br /><br />
    ${item.name}
    `
    ElMessageBox.confirm(msg, 'Atención', {
      top: '5vh',
      icon: markRaw(Delete),
      confirmButtonText: 'Sí',
      cancelButtonText: 'Cancelar',
      type: 'warning',
      dangerouslyUseHTMLString: true
    })
      .then(() => {
        roleRequest
          .destroy(item.id)
          .then((response) => {
            ElNotification({
              title: 'Rol eliminado',
              type: 'success',
              duration: 2000,
            });
            console.log(response)
            getLista();
          })
          .catch((error) => console.log(error));
      })
      .catch((error) => {
        console.log(error);
      });
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

const bcDialogPermissions = (done) => {
  done()
  idItemToEditPermissions.value = 0
  openDialogPermissions.value = false
}
</script>
