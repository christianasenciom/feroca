<template>
  <div class="header-container">
    <div>
      <img src="@/assets/img/logo.png" width="50" alt="Logo Principal" />
    </div>
    <div class="color-white">
      <div>
        <img src="@/assets/img/cunarc_logo.png" width="50" alt="Logo Principal" />
      </div>
    </div>
  </div>
  <div v-loading.fullscreen.lock="loading" class="sign-in-container authenticate-bg">
    <el-row class="login-card" style="top: 3vh;" type="flex" align="top" justify="center"  v-if="qrStatus">
      <el-col class="div_left" :span="12">
        <el-card style="width: 100%; height: 100%">
          <el-row class="logo" type="flex" justify="center">
            <el-tag :type="habilitado ? 'success' : 'danger'" style="font-size: 25px">{{ habilitado ? 'HABILITADO' : 'INHABILITADO' }}</el-tag>
          </el-row>
          <el-divider />
          <el-row style="justify-content: center;">
            <el-image :src="foto" style="width: 130px; height: 160px; "/>
          </el-row>
          <el-divider />
          <el-row>
            <el-text type="primary" style="font-size: 20px">{{ rondero }}</el-text>
          </el-row>
          <el-row>
              <el-text type="info" style="font-size: 15px" v-if="cargo.length > 1">
                <slot v-for="c in cargo" :key="c">
                  {{ c }}<br>
                </slot>
              </el-text>
              <el-text type="info" style="font-size: 15px" v-else>RONDERO</el-text>
          </el-row>
          <el-row>
            <el-text type="info" style="font-size: 15px">Base: {{ base }}</el-text>
          </el-row>
          <el-row>
            <el-text type="info" style="font-size: 12px">Fecha Inicio: {{ format({date: fecha_incorporacion, format: 'D/M/YYYY', tz: 'America/Lima'}) }}</el-text>
          </el-row>
          <el-row>
            <el-text type="info" style="font-size: 12px">{{ prov_dis_regi }}</el-text>
          </el-row>
        </el-card>
      </el-col>
    </el-row>
  </div>

</template>

<script setup>
import axios from 'axios';
import {nextTick, onMounted, ref} from "vue";
import {ElMessage} from "element-plus";
import router from "@/router/index.js";
import { format } from '@formkit/tempo'
const loading = ref(false);
const habilitado = ref(false);
const foto = ref('');
const rondero = ref('');
const cargo = ref('');
const dni = ref('');
const base = ref('');
const prov_dis_regi = ref('');
const fecha_incorporacion = ref('');
const qrStatus = ref(false);

onMounted(() => {
  loading.value = true;
  nextTick(() => {
    validarQR();
  })
})

const validarQR = async () => {
  const urlParams = new URLSearchParams(window.location.search);
  let qrData = urlParams.get('qr');
  await axios.get(import.meta.env.VITE_API_URL+`/validar-qr/${qrData}`)
      .then(response => {
        if (response.data.valid) {
          habilitado.value = response.data.habilitado;
          foto.value = response.data.datos.foto;
          rondero.value = response.data.datos.nombre_completo;
          cargo.value = response.data.datos.cargo;
          dni.value = response.data.datos.dni;
          base.value = response.data.datos.base;
          prov_dis_regi.value = response.data.datos.prov_dis_regi;
          fecha_incorporacion.value = response.data.datos.fecha_inicio;
          ElMessage.success('QR Válido')
          qrStatus.value = true;
        } else {
          ElMessage.error('QR no Válido')
          qrStatus.value = false;
          router.push({name: 'error-qr'})
        }
      })
      .catch(error => {
        console.log(error)
        ElMessage.error('Error al validar el QR')
        qrStatus.value = false;
      })
      .finally(() => {
        loading.value = false;
      })
}
</script>
<style scoped>
.sign-in-container {
  display: flex;
  width: 100vw;

  justify-content: center;
  align-items: center;
}

.titulo {
  color: #889aa4;
  font-size: 30px;
  /* font-family: 'Times New Roman', Times, serif; */
  margin-top: 5px;
  margin-bottom: 15px;
}

/** Delete */
.login-card {
  text-align: center;
  overflow: hidden;
  display: flex;
}
.login-card .div_left {
  display: inline-block;
  background: #ffffff;
  min-width: 300px;
  width: 100%;
  max-width: 570px;
  /* border-color: #dfdfdf;
  border-style: solid;
  border-width: 1px;
  border-top-left-radius: 3px;
  border-bottom-left-radius: 3px;  */
}
</style>
