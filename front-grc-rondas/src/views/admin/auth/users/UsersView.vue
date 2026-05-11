<template>
  <el-row :gutter="10" type="flex" justify="end" class="my-3">
    <el-col :xs="24" :sm="24" :md="24" class="actions-component">
      <el-input
        v-model="query.keyword"
        placeholder="Buscar por nombre"
        @change="getList"
      >
        <template #append>
          <el-button @click="getList">
            <template #icon>
              <v-icon :name="'bi-search'" />
            </template>
          </el-button>
        </template>
      </el-input>
    </el-col>
  </el-row>
  <el-table
    v-loading="loading"
    border
    :data="tableData"
    style="width: 100%"
    header-row-class-name="table-header-custom"
    row-class-name="table-row-custom"
  >
    <el-table-column type="index" label="#" width="100" align="center" header-align="center" />
    <el-table-column label="DOC. IDENTIDAD" width="140">
      <template #default="scope">
        {{ scope.row.persona?.docIdentidad }}
      </template>
    </el-table-column>
    <el-table-column label="NOMBRE" min-width="160">
      <template #default="scope">
        {{ scope.row.persona?.nombre_completo }}
      </template>
    </el-table-column>
    <el-table-column prop="role_name" label="ROL" min-width="160" />
    <el-table-column prop="email" label="CORREO" min-width="160" />
    <el-table-column fixed="right" align="center" width="160" >
      <template #header>
        <el-dropdown trigger="click" @command="handleCommandOpciones">
          <span class="el-dropdown-link">
            Opciones<el-icon class="el-icon--right"><arrow-down /></el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu ref="dropdown">
              <el-dropdown-item
                :command="{ action: 'ADD' }"
                :disabled="isActionDisabled('auth.usuarios.crear')"
              >
                <span>
                  <v-icon name="hi-plus" style="margin-right: 10px" />
                  Agregar
                </span>
              </el-dropdown-item>
<!--              <el-dropdown-item-->
<!--                :command="{ action: 'EXPORT' }"-->
<!--                :disabled="isActionDisabled('auth.usuarios.exportar')"-->
<!--              >-->
<!--                <span>-->
<!--                  <v-icon name="md-excel" style="margin-right: 10px" />-->
<!--                  Exportar-->
<!--                </span>-->
<!--              </el-dropdown-item>-->
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </template>
      <template #default="scope">
        <el-dropdown trigger="click" @command="handleCommandAcciones">
          <span class="el-dropdown-link">
            Opciones
            <v-icon name="fa-caret-down" />
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item :command="{ item: scope.row, action: 'EDIT' }" :disabled="isActionDisabled('auth.usuarios.actualizar')">
                <span>
                  <v-icon name="md-edit-round" style="margin-right: 10px" />
                  Editar
                </span>
              </el-dropdown-item>
              <el-dropdown-item :command="{ item: scope.row, action: 'DELETE' }" :disabled="isActionDisabled('auth.usuarios.eliminar')">
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
      @size-change="getList"
      @current-change="getList"
    />
  </el-row>
  <el-dialog
    v-model="openDialog"
    :width="calcularAnchoDialog('80%','98%')"
    top="5vh"
    :before-close="handleClose"
  >
    <template #header>
      <h2>{{ titleForm }}</h2>
    </template>
    <create-user
      ref="formCreateUser"
      :itemid="item_id"
      @closeChild="handleCloseCreate($event)"
    />
  </el-dialog>
</template>

<script>
import UserRequest from '@/api/auth/usuario'
import { ArrowDown, /* Delete*/ } from '@element-plus/icons-vue'
import { ElMessageBox, ElNotification } from 'element-plus'
import { calcularAnchoDialog } from '@/utils/responsive'
import CreateUser from './components/CreateUser.vue'
import { useAuthStore } from "@/stores/AuthStore";

const userRequest = new UserRequest()
const authStore = useAuthStore()
const validPermision = authStore.validPermision

export default {
  name: 'UsersView',
  components: {
    CreateUser,
    ArrowDown,
    // Delete
  },
  data() {
    return {
      openDialog: false,
      openDialogEdit: false,
      loading: false,
      tableData: [],
      total: 0,
      item_id: 0,
      query: {
        keyword: '',
        limit: 10,
        page: 1
      },
      idItemToEdit: '',
      calcularAnchoDialog,
      titleForm: 'Agregar registro'
    }
  },
  computed: {},
  mounted() {
    this.getList()
  },
  created() {},
  methods: {
    getList() {
      this.loading = true
      this.query.page = 1
      userRequest
        .list(this.query)
        .then((response) => {
          const { data, meta } = response
          this.tableData = data
          this.total = meta.total
          this.loading = false
        })
        .catch((error) => {
          console.log(error)
          this.loading = false
        })
    },
    handleCloseCreate(status) {
      console.log(status)
      this.openDialog = false
      if (status == 'success') {
        this.getList()
      }
      this.$nextTick(() => {
        this.item_id = 0
      })
    },
    handleCloseEdit(status) {
      if (status == 'success') this.getList()
      this.openDialogEdit = false
      this.idItemToEdit = ''
    },
    isActionDisabled(action) {
      return !validPermision(action.toLowerCase());
    },
    handleCommandOpciones({ action }) {
      if (action === 'ADD' && validPermision('auth.usuarios.crear')) {
        this.addItem()
      } else if (action === 'EXPORT' && validPermision('auth.usuarios.exportar')) {
        this.exportarDatos()
      } else {
        ElNotification({message: 'Usted no tiene permiso para realizar esta acción', type: 'info' })
      }
    },
    handleCommandAcciones(commandData) {
      const { item, action } = commandData
      // console.log(item, action)
      if (action == 'EDIT' && validPermision('auth.usuarios.actualizar')) {
        this.editItem(item)
      } else if (action == 'DELETE' && validPermision('auth.usuarios.eliminar')) {
        const msg = `
        ¿Está seguro que desea eliminar el usuario? <br>
        ${item.role_name} - ${item.name}
        `
        ElMessageBox.confirm(msg, 'Atención', {
          confirmButtonText: 'Sí',
          cancelButtonText: 'Cancelar',
          type: 'warning',
          dangerouslyUseHTMLString: true
        })
          .then(() => {
            userRequest
              .destroy(item.id)
              .then((response) => {
                ElNotification({
                  title: 'Usuario eliminado',
                  type: 'success',
                  duration: 2000
                })
                console.log(response)
                this.getList()
              })
              .catch((error) => console.log(error))
          })
          .catch((error) => {
            console.log(error)
          })
      } else {
        ElNotification({
          message: 'Usted no tiene permiso para realizar esta acción',
          type: 'info'
        })
      }
    },
    addItem() {
      this.titleForm = 'Agregar registro'
      this.openDialog = true
      this.$nextTick(() => {
        this.item_id = 0
      })
    },
    editItem(item) {
      this.titleForm = 'Modificar registro'
      this.openDialog = true;
      this.$nextTick(() => {
        this.item_id = item.id
      })
    },
    handleClose(done) {
      this.$nextTick(() => {
        this.item_id = 0
      })
      done()
    },
    exportarDatos() {
      this.loading = true
      userRequest
        .exportar()
        .then((response) => {
          this.loading = false
          const link = document.createElement('a')
          link.href = response
          document.body.appendChild(link)
          link.click()
        })
        .catch(() => {
          this.loading = false
        })
    }
  }
}
</script>
