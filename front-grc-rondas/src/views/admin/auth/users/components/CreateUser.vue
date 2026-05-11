<template>
  <el-form
    ref="formRefUser"
    :model="formData"
    :rules="reglasValidacion"
    label-width="120px"
    class="demo-form"
    label-position="top"
  >
    <div v-loading="loadingData" class="p-3">
      <el-row :gutter="12">
        <el-col :xs="24" :sm="12" :md="8">
          <el-form-item label="DNI (El N° de DNI será el USUARIO de ingreso)" prop="docIdentidad">
            <el-input v-model="formData.persona.docIdentidad" placeholder="DNI" maxlength="8" :disabled="fromVista === 'viewPerfil'"/>
          </el-form-item>
          <el-form-item label="Nombres" prop="nombres">
            <el-input v-model="formData.persona.nombres" placeholder="Nombres" />
          </el-form-item>
          <el-form-item label="Ap.Paterno" prop="apellido_paterno">
            <el-input v-model="formData.persona.apellido_paterno" placeholder="Ap. Paterno" />
          </el-form-item>
          <el-form-item label="Ap. Materno" prop="apellido_materno">
            <el-input v-model="formData.persona.apellido_materno" placeholder="Ap. Materno" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12" :md="8">
          <el-form-item label="Fecha Nacimiento" prop="fecha_nacimiento">
            <el-date-picker
                style="width: 100%"
                type="date"
                format="DD/MM/YYYY"
                value-format="YYYY-MM-DD"
                v-model="formData.persona.fecha_nacimiento"
                placeholder="Fecha Nacimiento"
            />
          </el-form-item>
          <el-form-item label="genero" prop="genero">
            <el-select v-model="formData.persona.genero" placeholder="Genero" style="width: 100%">
              <el-option label="MASCULINO" value="MASCULINO" />
              <el-option label="FEMENINO" value="FEMENINO" />
            </el-select>
          </el-form-item>
          <el-form-item label="Celular" prop="celular">
            <el-input v-model="formData.persona.celular" placeholder="Celular" />
          </el-form-item>
          <el-form-item label="Dirección" prop="direccion">
            <el-input v-model="formData.persona.direccion" placeholder="Dirección" />
          </el-form-item>
        </el-col>
        <el-col :xs="24" :sm="12" :md="8">
          <el-form-item label="Email" prop="email">
            <el-input ref="emailField" v-model="formData.email" placeholder="Email" />
          </el-form-item>
          <el-form-item label="Rol" prop="role_id" v-if="fromVista !== 'viewPerfil'">
            <el-select v-model="formData.role_id" placeholder="Rol" style="width: 100%">
              <el-option
                  v-for="rol in roles"
                  :key="rol.id"
                  :label="rol.name"
                  :value="rol.id"
              />
            </el-select>
          </el-form-item>
          <slot  v-if="fromVista !== 'viewPerfil'">
            <el-form-item v-if="formData.id === undefined" label="Clave" prop="password">
              <el-input v-model="formData.password" placeholder="Clave" />
            </el-form-item>
            <el-form-item v-else label="Nueva clave" prop="password">
              <el-input v-model="formData.password" placeholder="Clave" />
              <span class="text-muted small">Dejar en blanco si no desea cambiar</span>
            </el-form-item>
          </slot>
        </el-col>
      </el-row>
      <el-row :gutter="12" type="flex" class="row-bg mt-4" justify="end">
        <el-button v-if="formData.id === undefined" @click="resetForm('formRefUser')">Cancelar</el-button>
        <el-button type="primary" @click="submitForm('formRefUser')">
          {{ formData.id === undefined ? 'Guardar' : 'Actualizar' }}
        </el-button>
      </el-row>
    </div>
  </el-form>
</template>

<script>
import { ElNotification } from 'element-plus'
import RoleRequest from '@/api/auth/role'
import UserRequest from '@/api/auth/usuario'

const roleRequest = new RoleRequest()
const userRequest = new UserRequest()
export default {
  name: 'FormUser',
  components: {},
  props: {
    itemid: {
      type: Number,
      default: () => {
        return 0
      }
    },
    fromVista: {
      type: String,
      default: () => {
        return ''
      }
    }
  },
  data() {
    const validatePasswordUpdate = (rule, value, callback) => {
      // console.log(this.formData)
      if (this.formData.id === undefined) {
        if (!value) {
          callback(new Error('El campo es requerido'));
        } else {
          callback();
        }
      } else {
        callback();
      }
    }
    return {
      loadingData: false,
      createUserForm: '',
      tiposDocIdentidad: [],
      roles: [],
      dialogBuscarPersona: false,
      formData: {
        id: undefined,
        persona: {
          persona_id: undefined,
          documento_tipo: 'DNI',
          docIdentidad: undefined,
          apellido_paterno: undefined,
          apellido_materno: undefined,
          nombres: undefined,
          fecha_nacimiento: undefined,
          genero: undefined,
          celular: undefined,
          direccion: undefined,
          tipo: 'Natural'
        },
        // docIdentidad: undefined,
        // apellido_paterno: undefined,
        // apellido_materno: undefined,
        // nombres: undefined,
        // fecha_nacimiento: undefined,
        // genero: undefined,
        // celular: undefined,
        // direccion: undefined,
        email: undefined,
        role_id: undefined,
        password: undefined
      },
      reglasValidacion: {
        "persona.apellido_paterno": [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
        "persona.apellido_materno": [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
        "persona.nombres": [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
        "persona.docIdentidad": [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
        email: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
        role_id: [{ required: true, message: 'Campo requerido', trigger: 'blur' }],
        password: [{ validator: validatePasswordUpdate, trigger: 'blur' }],
      },
    }
  },
  watch: {
    itemid: {
      deep: true,
      immediate: true,
      handler: function(newV, oldV) {
        // console.log(newV, oldV)
        this.setCrearOUpdate(newV, oldV)
      }
    }
  },
  computed: {},
  created() {
    this.fetchRoles()
  },
  methods: {
    async fetchRoles() {
      await roleRequest.all()
        .then((response) => {
          console.log(response)
          this.roles = response.data
        })
        .catch((error) => {
          console.log(error)
          ElNotification({
            type: 'error',
            title: 'Error al precargar data del formulario',
            duration: 2000
          })
          close('canceled')
        })
    },
    setCrearOUpdate() {
      console.log('Create User' + this.itemid)
      this.$nextTick(() => {
        if (this.itemid > 0) {
          this.item_id = this.itemid
          this.getDataUpdate()
        } else {
          this.item_id = undefined
          this.handleCreate()
        }
        this.resetForm('formRefUser')
        this.resetModel()
      })
    },
    handleCreate() {
      console.log('Open form create, set focus')
      // this.$refs['inputFocusCreate'].focus()
    },
    getDataUpdate() {
      // this.$refs['inputFocusCreate'].focus()
      this.resetModel()
      this.loadingData = true
      userRequest
      .get(this.item_id)
      .then((response) => {
        const { data } = response
        this.formData = {...data}
        this.loadingData = false
      })
      .catch(() => {
        this.loadingData = false
      })
    },
    submitForm() {
      this.$refs['formRefUser'].validate((valid) => {
        if (valid) {
          if (this.formData.id === undefined) {
            this.saveCreateNewForm()
          } else {
            this.saveEditForm()
          }
        } else {
          return false
        }
      })
    },
    saveCreateNewForm() {
      this.loadingData = true
      userRequest
        .store(this.formData)
        .then((response) => {
          // console.log(response);
          const {state, message} = response
          this.$message({
            type: state,
            message
          })
          this.loadingData = false
          this.close('success')
        })
        .catch(() => {
          this.loadingData = false
        })
    },
    saveEditForm() {
      this.loadingData = true
      userRequest
        .update(this.item_id, this.formData)
        .then((response) => {
          const {state, message} = response
          this.$message({
            type: state,
            message
          })
          this.loadingData = false
          this.close('success')
        })
        .catch(() => {
          this.loadingData = false
        })
    },
    close(status) {
      console.log("Cerrar Dialog Create222: "+status)
      if (this.createUserForm) {
        this.createUserForm.resetFields()
      }
      this.$emit('closeChild', status)
    },
    searchAsociacion() {
      this.dialogBuscarPersona = true
    },
    resetForm(formName) {
      this.$refs[formName].resetFields()
    },
    resetModel() {
      this.formData = {
        id: this.itemid > 0 ? this.itemid : undefined,
        persona: {
          persona_id: undefined,
          documento_tipo: 'DNI',
          docIdentidad: undefined,
          apellido_paterno: undefined,
          apellido_materno: undefined,
          nombres: undefined,
          fecha_nacimiento: undefined,
          genero: undefined,
          celular: undefined,
          direccion: undefined,
          tipo: 'Natural'
        },
        email: undefined,
        role_id: undefined,
        password: undefined
      }
    },
    parentProcessEmitPersona(data) {
      // console.log(data)
      this.formData.persona_id = data.id
      this.formData.persona.docIdentidad = data.docIdentidad
      this.formData.persona.documento_tipo = data.documento_tipo
      this.formData.persona.apellido_paterno = data.apellido_paterno
      this.formData.persona.apellido_materno = data.apellido_materno
      this.formData.persona.nombres = data.nombres
      this.formData.persona.fecha_nacimiento = data.fecha_nacimiento
      this.formData.persona.genero = data.genero
      this.formData.persona.celular = data.celular
      this.formData.persona.direccion = data.direccion
      this.formData.persona.tipo = data.tipo
      this.formData.email = data.email
      this.formData.password = data.nro_doc_identidad
      this.dialogBuscarPersona = false
      this.$refs['emailField'].focus()
    }
  }
}
</script>
