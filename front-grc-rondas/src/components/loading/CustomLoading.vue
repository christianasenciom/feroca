<template>
  <div v-loading="loading" :element-loading-text="loadingText" :element-loading-spinner="customSpinner">
    <slot></slot>
  </div>
</template>

<script>
export default {
  props: {
    loading: {
      type: Boolean,
      default: false,
    },
    loadingText: {
      type: String,
      default: 'Cargando...',
    },
    customSpinner: {
      type: String,
      default: '<circle class="path" cx="25" cy="25" r="20" fill="none"></circle>', // Usa un icono personalizado o SVG
    },
  },
};
</script>

<style scoped>
/* Personalizar colores y estilos del loader */
::v-deep(.el-loading-mask) {
  background-color: rgba(255, 255, 255, 0.7); /* Fondo oscuro */
}

::v-deep(.el-loading-spinner) {
  font-size: 30px; /* Tamaño del spinner */
  color: var(--el-color-primary); /* Color del texto y el ícono */
}

/* Ajustes adicionales */
::v-deep(.el-loading-text) {
  font-size: 18px;
  color: var(--el-color-primary);
  font-weight: bold;
}
</style>
