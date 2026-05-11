<template>
  <div v-loading="loading" class="change-password-wrapper">
    <el-card class="password-card" shadow="hover">
      <template #header>
        <div class="card-header">
          <div class="title-icon">
            <el-icon size="24"><Lock /></el-icon>
            <h3>Cambio de Contraseña</h3>
          </div>
          <p class="subtitle">Por seguridad, elige una contraseña segura</p>
        </div>
      </template>

      <el-form
        ref="form_change"
        :model="form"
        :rules="rules"
        status-icon
        label-position="top"
        @submit.prevent="submitForm(form_change)"
      >
        <!-- Nueva Contraseña -->
        <el-form-item label="Nueva Contraseña" prop="password">
          <el-input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Ingrese su nueva contraseña"
            size="large"
            show-password
          >
            <template #suffix>
              <el-icon class="password-icon" @click="togglePassword">
                <View v-if="showPassword" />
                <Hide v-else />
              </el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- Fortaleza de la contraseña -->
        <div v-if="form.password" class="password-strength">
          <div class="strength-label">Fortaleza de la contraseña:</div>
          <div class="strength-bar">
            <div
              class="strength-progress"
              :class="strengthClass"
              :style="{ width: strengthPercentage + '%' }"
            ></div>
          </div>
          <div class="strength-text" :class="strengthClass">
            {{ strengthText }}
          </div>
          <div class="strength-requirements">
            <div :class="{ met: hasMinLength }">
              <el-icon><Check v-if="hasMinLength" /><Close v-else /></el-icon>
              <span>Mínimo 8 caracteres</span>
            </div>
            <div :class="{ met: hasUpperCase }">
              <el-icon><Check v-if="hasUpperCase" /><Close v-else /></el-icon>
              <span>Mayúscula (A-Z)</span>
            </div>
            <div :class="{ met: hasLowerCase }">
              <el-icon><Check v-if="hasLowerCase" /><Close v-else /></el-icon>
              <span>Minúscula (a-z)</span>
            </div>
            <div :class="{ met: hasNumber }">
              <el-icon><Check v-if="hasNumber" /><Close v-else /></el-icon>
              <span>Número (0-9)</span>
            </div>
            <div :class="{ met: hasSpecialChar }">
              <el-icon><Check v-if="hasSpecialChar" /><Close v-else /></el-icon>
              <span>Carácter especial (!@#$%^&*)</span>
            </div>
          </div>
        </div>

        <!-- Confirmar Contraseña -->
        <el-form-item label="Confirmar Contraseña" prop="password_confirmation">
          <el-input
            v-model="form.password_confirmation"
            :type="showConfirmPassword ? 'text' : 'password'"
            placeholder="Confirme su nueva contraseña"
            size="large"
          >
            <template #suffix>
              <el-icon class="password-icon" @click="toggleConfirmPassword">
                <View v-if="showConfirmPassword" />
                <Hide v-else />
              </el-icon>
            </template>
          </el-input>
        </el-form-item>

        <!-- Mensaje de error de coincidencia -->
        <div v-if="passwordMismatch" class="error-message">
          <el-icon><CircleClose /></el-icon>
          <span>Las contraseñas no coinciden</span>
        </div>

        <!-- Botones -->
        <div class="form-actions">
          <el-button v-if="props.desde === 'ex'" type="danger" @click="close" size="large">
            <el-icon><Close /></el-icon>
            Cancelar
          </el-button>
          <el-button @click="resetForm" size="large">
            <el-icon><Refresh /></el-icon>
            Limpiar
          </el-button>
          <el-button
            type="primary"
            @click="submitForm(form_change)"
            :loading="loading"
            size="large"
            :disabled="!isFormValid"
          >
            <el-icon><Lock /></el-icon>
            Cambiar Contraseña
          </el-button>
        </div>
      </el-form>

      <div class="security-tips">
        <el-alert
          title="Recomendaciones de seguridad"
          type="info"
          :closable="false"
          show-icon
        >
          <template #default>
            <ul>
              <li>No uses contraseñas que hayas usado anteriormente</li>
              <li>Evita usar información personal como nombres o fechas</li>
              <li>No compartas tu contraseña con nadie</li>
            </ul>
          </template>
        </el-alert>
      </div>
    </el-card>
  </div>
</template>

<script setup>
import { reactive, ref, computed } from 'vue'
import { useAuthStore } from '@/stores/AuthStore'
import UserResource from '@/api/auth/usuario'
import { useRouter } from 'vue-router'
import { ElNotification } from 'element-plus'
import { Lock, View, Hide, Check, Close, CircleClose, Refresh } from '@element-plus/icons-vue'

const props = defineProps({
  desde: {
    type: String,
    default: "in"
  }
})

// Stores y recursos
const authStore = useAuthStore()
const userResource = new UserResource()
const router = useRouter()

// Refs
const form_change = ref(null)
const loading = ref(false)

// Mostrar/ocultar contraseñas
const showPassword = ref(false)
const showConfirmPassword = ref(false)

const togglePassword = () => {
  showPassword.value = !showPassword.value
}

const toggleConfirmPassword = () => {
  showConfirmPassword.value = !showConfirmPassword.value
}

// Formulario
const form = reactive({
  password: '',
  password_confirmation: ''
})

// Validaciones de fortaleza
const hasMinLength = computed(() => form.password.length >= 8)
const hasUpperCase = computed(() => /[A-Z]/.test(form.password))
const hasLowerCase = computed(() => /[a-z]/.test(form.password))
const hasNumber = computed(() => /[0-9]/.test(form.password))
const hasSpecialChar = computed(() => /[!@#$%^&*(),.?":{}|<>]/.test(form.password))

const strengthPercentage = computed(() => {
  let score = 0
  if (hasMinLength.value) score += 20
  if (hasUpperCase.value) score += 20
  if (hasLowerCase.value) score += 20
  if (hasNumber.value) score += 20
  if (hasSpecialChar.value) score += 20
  return score
})

const strengthClass = computed(() => {
  const percent = strengthPercentage.value
  if (percent < 40) return 'weak'
  if (percent < 70) return 'medium'
  return 'strong'
})

const strengthText = computed(() => {
  const percent = strengthPercentage.value
  if (percent < 40) return 'Débil'
  if (percent < 70) return 'Media'
  return 'Fuerte'
})

const passwordMismatch = computed(() => {
  return form.password && form.password_confirmation &&
         form.password !== form.password_confirmation
})

const isFormValid = computed(() => {
  return form.password &&
         form.password_confirmation &&
         !passwordMismatch.value &&
         hasMinLength.value
})

// Validación
const validatePassword = (rule, value, callback) => {
  if (!value) {
    callback(new Error('Debe ingresar una contraseña'))
  } else if (value.length < 8) {
    callback(new Error('La contraseña debe tener al menos 8 caracteres'))
  } else if (!/[A-Z]/.test(value)) {
    callback(new Error('Debe contener al menos una letra mayúscula'))
  } else if (!/[a-z]/.test(value)) {
    callback(new Error('Debe contener al menos una letra minúscula'))
  } else if (!/[0-9]/.test(value)) {
    callback(new Error('Debe contener al menos un número'))
  } else {
    callback()
  }
}

const validateCheckPassword = (rule, value, callback) => {
  if (!value) {
    callback(new Error('Debe confirmar su contraseña'))
  } else if (value !== form.password) {
    callback(new Error('Las contraseñas no coinciden'))
  } else {
    callback()
  }
}

const rules = reactive({
  password: [{ validator: validatePassword, trigger: 'blur' }],
  password_confirmation: [{ validator: validateCheckPassword, trigger: 'blur' }]
})

// Resetear formulario
const resetForm = () => {
  form.password = ''
  form.password_confirmation = ''
  form_change.value?.clearValidate()
  ElNotification({
    title: 'Formulario limpiado',
    message: 'Los campos han sido restablecidos',
    type: 'info',
    duration: 2000
  })
}

// Enviar formulario
const submitForm = (formEl) => {
  if (!formEl) return

  formEl.validate((valid) => {
    if (valid) {
      loading.value = true

      userResource.changePass(form)
        .then((response) => {
          ElNotification({
            title: '¡Éxito!',
            message: 'Tu contraseña ha sido cambiada correctamente',
            type: 'success',
            duration: 3000
          })

          if (props.desde === 'ex') {
            // Cerrar sesión después de cambiar contraseña desde externo
            setTimeout(() => {
              close()
            }, 2000)
          } else {
            resetForm()
          }
          loading.value = false
        })
        .catch(error => {
          console.error('Error al cambiar contraseña:', error)
          
          let errorMessage = 'Error al cambiar la contraseña'
          if (error.response?.data?.message) {
            errorMessage = error.response.data.message
          }
          
          ElNotification({
            title: 'Error',
            message: errorMessage,
            type: 'error',
            duration: 5000
          })
          loading.value = false
        })
    } else {
      ElNotification({
        title: 'Error de validación',
        message: 'Por favor, complete todos los campos correctamente',
        type: 'warning',
        duration: 3000
      })
    }
  })
}

// Cerrar sesión
const close = async () => {
  loading.value = true
  await authStore.logout('all')
  await router.push('/signin')
}
</script>

<style scoped>
.change-password-wrapper {
  padding: 20px;
}

.password-card {
  max-width: 550px;
  margin: 0 auto;
  border-radius: 16px;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.card-header {
  text-align: center;
}

.title-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  margin-bottom: 8px;
}

.title-icon h3 {
  margin: 0;
  font-size: 20px;
  color: #333;
}

.subtitle {
  color: #909399;
  font-size: 13px;
  margin: 0;
}

.password-icon {
  cursor: pointer;
  color: #909399;
  transition: color 0.3s;
}

.password-icon:hover {
  color: #409eff;
}

/* Fortaleza de contraseña */
.password-strength {
  margin-top: -10px;
  margin-bottom: 20px;
}

.strength-label {
  font-size: 12px;
  color: #666;
  margin-bottom: 6px;
}

.strength-bar {
  height: 6px;
  background-color: #e4e7ed;
  border-radius: 3px;
  overflow: hidden;
  margin-bottom: 6px;
}

.strength-progress {
  height: 100%;
  border-radius: 3px;
  transition: width 0.3s ease;
}

.strength-progress.weak {
  background-color: #f56c6c;
}

.strength-progress.medium {
  background-color: #e6a23c;
}

.strength-progress.strong {
  background-color: #67c23a;
}

.strength-text {
  font-size: 12px;
  margin-bottom: 10px;
}

.strength-text.weak {
  color: #f56c6c;
}

.strength-text.medium {
  color: #e6a23c;
}

.strength-text.strong {
  color: #67c23a;
}

.strength-requirements {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 8px;
}

.strength-requirements > div {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 11px;
  color: #909399;
}

.strength-requirements > div.met {
  color: #67c23a;
}

.strength-requirements > div .el-icon {
  font-size: 12px;
}

.error-message {
  color: #f56c6c;
  font-size: 12px;
  margin-top: -10px;
  margin-bottom: 20px;
  display: flex;
  align-items: center;
  gap: 5px;
}

.form-actions {
  display: flex;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 20px;
}

.security-tips {
  margin-top: 20px;
}

.security-tips ul {
  margin: 8px 0 0 20px;
  padding: 0;
}

.security-tips li {
  font-size: 13px;
  color: #666;
  margin-bottom: 4px;
}

/* Responsive */
@media (max-width: 768px) {
  .change-password-wrapper {
    padding: 10px;
  }
  
  .password-card {
    max-width: 100%;
  }
  
  .form-actions {
    flex-direction: column;
  }
  
  .form-actions .el-button {
    width: 100%;
  }
  
  .strength-requirements {
    flex-direction: column;
    gap: 6px;
  }
}
</style>