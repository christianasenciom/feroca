<template>
  <div class="sign-in-container">
     <el-card :style="{ width: calcularAnchoDialog('500px', '90%') }">
      <div>
        <el-row type="flex" justify="center">
          <h1 style="font-size: 1.95rem; margin-bottom: 2%;"><strong>Cambio de contraseña</strong></h1>
        </el-row>
        <!-- <el-row type="flex" justify="center" style="margin-top: 2%; margin-bottom: 2%;">
          <p style="color: #031B3D;">Logueate y sigue aprendiendo</p>
        </el-row> -->
        <el-row type="flex" justify="center">
          <FormChangePass :desde="'ex'"></FormChangePass>
        </el-row>
      </div>
    </el-card>
  </div>
</template>
<script setup>
import { calcularAnchoDialog } from '@/utils/responsive'
import FormChangePass from './components/changePassComponent.vue'

</script>
<style scoped>
.sign-in-container {
  display: flex;
  width: 100vw;
  height: 91vh;
  justify-content: center;
  align-items: center;
}
</style>