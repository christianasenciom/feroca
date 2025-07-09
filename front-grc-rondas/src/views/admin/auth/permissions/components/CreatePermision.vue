<template>
  <el-card shadow="never" v-loading="loading">
    <el-form ref="createPermissionForm" :model="permission" :rules="rules" label-position="top" v-on:submit.prevent>
      <el-form-item prop="name" label="Nombre">
        <el-input v-model="permission.name" placeholder="auth.usuarios.listar" />
      </el-form-item>
    </el-form>
    <el-row :gutter="10" type="flex" justify="end" class="mt-3">
      <el-button @click="close('canceled')">CANCELAR</el-button>
      <el-button type="primary" @click="submitForm(createPermissionForm)">GUARDAR</el-button>
    </el-row>
  </el-card>
</template>

<script setup>
import PermissionRequest from '@/api/auth/permision'
import { ElNotification } from 'element-plus'
const permissionRequest = new PermissionRequest()
import { reactive, ref } from 'vue'

const emit = defineEmits(['close'])
const loading = ref(false)
const createPermissionForm = ref()
const permission = reactive({
  name: ''
})

const rules = reactive({
  name: [{ required: true, message: 'Campo obligatorio', trigger: 'blur' }]
})

const submitForm = async (formEl) => {
  if (!formEl) return
  loading.value = true
  await formEl.validate((valid) => {
    if (valid) {
      permissionRequest
        .store(permission)
        // eslint-disable-next-line no-unused-vars
        .then((response) => {
          ElNotification({
            type: 'success',
            title: 'Permiso creado',
            duration: 2000
          })
          close('success')
          loading.value = false
        })
        .catch((error) => {
          console.log(error)
          loading.value = false
        })
    } else {
      loading.value = false
    }
  })
}

const resetForm = () => {
  Object.assign(permission, reactive({
    name: ''
  }));
}

const close = (status) => {
  emit('close', status)
  resetForm()
}
</script>
