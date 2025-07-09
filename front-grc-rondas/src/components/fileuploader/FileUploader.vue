<template>
  <div>
    <input
      type="file"
      ref="fileInput"
      :accept="props.accept"
      style="display: none"
      @change="handleFileChange"
    />
    <el-row>
      <el-button @click="openFilePicker" style="width: 100%">
        <slot>
          <v-icon name="hi-upload" style="margin-right: 10px" />
          {{ file?.name || 'Seleccionar archivo' }}
        </slot>
      </el-button>
      <div v-if="props.maxSize">
        <p v-if="props.maxSize < 1024">Tamaño máximo permitido: {{ props.maxSize }} bytes</p>
        <p v-else-if="props.maxSize < 1048576">
          Tamaño máximo permitido: {{ (props.maxSize / 1024).toFixed(2) }} KB
        </p>
        <p v-else>Tamaño máximo permitido: {{ (props.maxSize / 1048576).toFixed(2) }} MB</p>
      </div>
    </el-row>
  </div>
</template>

<script setup>
import { ElMessage } from 'element-plus'
import { ref, watch } from 'vue'

const props = defineProps(['modelValue', 'accept', 'maxSize'])
const emit = defineEmits(['update:modelValue'])
const fileInput = ref(null)
const file = ref(props.modelValue)

watch(file, () => {
  emitInput()
})

watch(
  () => props.modelValue,
  (newValue) => {
    file.value = newValue
  }
)

const emitInput = () => {
  emit('update:modelValue', file.value)
}

const openFilePicker = () => {
  fileInput.value.click()
}

const handleFileChange = (event) => {
  const selectedFile = event.target.files[0]

  if (props.maxSize && selectedFile.size > props.maxSize) {
    ElMessage({
      message: 'El archivo seleccionado excede el tamaño máximo permitido',
      type: 'error'
    })
    fileInput.value.value = null
    return
  }

  file.value = {
    file: selectedFile,
    type: selectedFile.type,
    name: selectedFile.name,
    raw: selectedFile
  }
}
</script>
