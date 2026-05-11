<template>
  <div v-loading.fullscreen.lock="loading">
    <el-row justify="center" :gutter="15">
      <el-col :xs="24" :sm="8" :md="6">
        <el-card>
          <template #header>
            <h2>Información del usuario</h2>
          </template>
          <div style="text-align: center">
            
            <el-row style="margin-top: 15px">
              <p>
                <strong>{{ nombre }}</strong>
              </p>
              <el-divider />
              <ul>
                <li>
                  <span>
                    <v-icon
                      name="la-birthday-cake-solid"
                      style="margin-right: 10px; fill: var(--template-color-primary)"
                    />
                    {{ birthday }}
                  </span>
                </li>
                <li>
                  <span>
                    <v-icon
                      name="io-settings-sharp"
                      style="margin-right: 10px; fill: var(--template-color-primary)"
                    />
                    {{ rol_name }}
                  </span>
                </li>
              </ul>
            </el-row>
          </div>
        </el-card>
      </el-col>
      <el-col :xs="24" :sm="16" :md="18">
        <el-card>
          <el-tabs v-model="activeName" class="demo-tabs">
            <el-tab-pane label="Configuración" name="configuracion">
              <template #label>
                <span style="font-weight: 600; padding-right: 8px">
                  <v-icon name="io-settings-sharp" />
                  Configuración
                </span>
              </template>
              <div>
                <el-row :gutter="10">
                  
                  <el-col :xs="24" :md="12">
                    <h3>Cambio de contraseña</h3>
                    <el-divider />
                    <div>
                      <el-form
                        ref="newPasswordForm"
                        :model="changePassFormData"
                        :rules="changePassRules"
                        label-position="top"
                      >
                        <el-form-item label="Contraseña actual" prop="current_password">
                          <el-input type="password" v-model="changePassFormData.current_password" />
                        </el-form-item>
                        <el-form-item label="Nueva contraseña" prop="new_password">
                          <el-input type="password" v-model="changePassFormData.new_password" />
                        </el-form-item>
                        <el-form-item label="Confirmar contraseña" prop="new_password_confirmation">
                          <el-input
                            type="password"
                            v-model="changePassFormData.new_password_confirmation"
                          />
                        </el-form-item>
                        <el-form-item>
                          <el-button type="primary" @click="newPassword(newPasswordForm)">
                            Cambiar contraseña
                          </el-button>
                        </el-form-item>
                      </el-form>
                    </div>
                  </el-col>
                </el-row>
              </div>
            </el-tab-pane>
            <el-tab-pane label="Datos de la cuenta" name="first">
              <create-user
                  ref="formCreateUser"
                  :itemid="item_id"
                  :fromVista="'viewPerfil'"
              />
            </el-tab-pane>
          </el-tabs>
        </el-card>
      </el-col>
    </el-row>
  </div>
</template>

<script setup>
import { useAuthStore } from '@/stores/AuthStore'
import UserCircle from '@/components/Icons/svg/fa-user-circle.svg'
import { computed, ref } from 'vue'
import { format } from '@formkit/tempo'
import { newpassword, updateimage } from '@/api/auth'
import { ElNotification } from 'element-plus'
import FileUploader from '@/components/fileuploader/FileUploader.vue'
import CreateUser from "@/views/admin/auth/users/components/CreateUser.vue";

const authStore = useAuthStore()

const item_id = ref(authStore.id)
const nombre = computed(() => authStore.persona)
const rol_name = computed(() => authStore.roles[0])
const birthday = computed(() => {
  if (authStore.fecha_nacimiento) {
    const dia = format({
      date: authStore.fecha_nacimiento,
      format: 'D',
      tz: 'America/Lima'
    })
    const mes = format({
      date: authStore.fecha_nacimiento,
      format: 'MMMM',
      tz: 'America/Lima'
    })
    return `${dia} de ${mes}`
  }
  return 'NO REGISTRADO'
})
const activeName = ref('configuracion')
const loading = ref(false)
const updateImageForm = ref()
const updateImageFormData = ref({
  file: undefined
})
const updateImageRules = ref({
  file: [{ required: true, message: 'Debe seleccionar la imagen', trigger: 'blur' }]
})
const newPasswordForm = ref()
const changePassFormData = ref({
  current_password: '',
  new_password: '',
  new_password_confirmation: ''
})

// 🔥 CORRECCIÓN: Generar la URL completa del avatar
// Busca la foto en: authStore.persona?.foto o authStore.avatar
const avatarUrl = computed(() => {
  // Obtener el nombre de la foto desde persona o avatar
  const fotoNombre = authStore.avatar;
  
  console.log('Avatar desde store:', fotoNombre);
  
  if (!fotoNombre) return '';
  
  // Si ya es una URL completa o base64
  if (fotoNombre.startsWith('http') || fotoNombre.startsWith('data:')) {
    return fotoNombre;
  }
  
  // Construir URL para obtener la foto
  const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
  const baseUrl = apiUrl.replace(/\/api$/, '');
  const url = `${baseUrl}/api/user/avatar/${fotoNombre}?t=${Date.now()}`;
  console.log('URL del avatar:', url);
  return url;
});

// Manejar error de carga de imagen
const handleImageError = (event) => {
  console.error('Error al cargar avatar:', event);
  event.target.src = '';
  event.target.alt = 'No se pudo cargar la imagen';
};

const changePassRules = ref({
  current_password: [
    { required: true, message: 'La contraseña actual es requerida', trigger: 'blur' }
  ],
  new_password: [
    { required: true, message: 'La nueva contraseña es requerida', trigger: 'blur' },
    { min: 8, message: 'La nueva contraseña debe tener al menos 8 caracteres', trigger: 'blur' },
    { validator: validatePasswordConfirmation, trigger: 'blur' }
  ],
  new_password_confirmation: [
    {
      required: true,
      message: 'La confirmación de la nueva contraseña es requerida',
      trigger: 'blur'
    },
    { validator: validatePasswordConfirmation, trigger: 'blur' }
  ]
})

function validatePasswordConfirmation(rule, value, callback) {
  if (value !== changePassFormData.value.new_password) {
    callback(new Error('Nueva contraseña no coincide, asegúrate que las contraseñas sean iguales.'))
  } else {
    callback()
  }
}

const newPassword = async (formEl) => {
  if (!formEl) return
  await formEl.validate((valid, fields) => {
    if (valid) {
      loading.value = true
      newpassword(changePassFormData.value)
        .then(() => {
          newPasswordForm.value.resetFields()
          changePassFormData.value = {
            current_password: '',
            new_password: '',
            new_password_confirmation: ''
          }
          ElNotification({
            message: 'Contraseña cambiada correctamente',
            type: 'success'
          })
          loading.value = false
        })
        .catch(() => {
          loading.value = false
        })
    } else {
      console.log('error submit!', fields)
    }
  })
}

const updateImage = async (formEl) => {
  if (!formEl) return
  await formEl.validate((valid, fields) => {
    if (valid) {
      const formData = new FormData()
      formData.append('file', updateImageFormData.value.file.raw)
      loading.value = true
      updateimage(formData)
        .then((response) => {
          // Actualizar el avatar en el store
          authStore.changeAvatar(response.path)
          // Forzar actualización de la imagen cambiando el timestamp
          const newUrl = computed(() => {
            const apiUrl = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api';
            const baseUrl = apiUrl.replace(/\/api$/, '');
            return `${baseUrl}/api/user/avatar/${response.path}?t=${Date.now()}`;
          });
          loading.value = false
          updateImageFormData.value.file = null
          ElNotification({
            message: 'Imagen de perfil actualizada correctamente',
            type: 'success'
          })
          // Recargar la página para ver el cambio
          setTimeout(() => {
            window.location.reload();
          }, 500);
        })
        .catch((error) => {
          console.error('Error al actualizar imagen:', error);
          loading.value = false
          ElNotification({
            message: error.response?.data?.message || 'Error al actualizar la imagen',
            type: 'error'
          })
        })
    } else {
      console.error('error submit!', fields)
    }
  })
}
</script>

<style scoped>
ul {
  list-style: none;
  padding: 0;
}
.user-profile-image {
  width: 200px;
  height: 200px;
  border-radius: 50%;
  object-fit: cover;
}
</style>