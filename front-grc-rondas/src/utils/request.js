import axios from 'axios'
import { getToken, removeToken } from './auth'
import { ElMessage, ElMessageBox } from 'element-plus'

const service = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
    'Accept': 'application/json',
    'Content-Type': 'application/json'
  },
  withCredentials: true,
  timeout: 1000 * 60,
})

// Interceptor de solicitud
service.interceptors.request.use(
  (config) => {
    const token = getToken()
    if (token) {
      config.headers['Authorization'] = 'Bearer ' + token
    }

    if (import.meta.env.DEV) {
      console.log('📤 API Request:', config.method.toUpperCase(), config.url, config.data || '')
    }

    return config
  },
  (error) => {
    console.error('❌ Request Error:', error)
    return Promise.reject(error)
  }
)

// Interceptor de respuesta
service.interceptors.response.use(
  (response) => {
    if (import.meta.env.DEV) {
      console.log('📥 API Response:', response.status, response.config.url, response.data)
    }

    if (response.headers.authorization) {
      const token = response.headers.authorization.replace('Bearer ', '')
    }

    return response.data
  },
  (error) => {
    if (!error.response) {
      ElMessage({
        type: 'error',
        message: 'Error de conexión. Verifique su red o que el servidor esté funcionando.',
        duration: 5000
      })
      return Promise.reject(error)
    }

    const { status, data } = error.response
    const message = data?.message || data?.error?.message || 'Error desconocido'

    switch (status) {
      case 400:
        ElMessage({
          type: 'warning',
          message: `<strong>Error de solicitud:</strong> ${message}`,
          dangerouslyUseHTMLString: true,
          duration: 6250
        })
        break

      case 401:
        if (getToken()) {
          ElMessageBox.confirm(
            'Su sesión ha caducado, por favor vuelva a iniciar sesión',
            'Sesión expirada',
            {
              confirmButtonText: 'Iniciar sesión',
              cancelButtonText: 'Cancelar',
              type: 'warning'
            }
          ).then(() => {
            removeToken()
            window.location.href = '/signin'
          }).catch(() => {
            removeToken()
          })
        } else {
          ElMessage({
            type: 'info',
            message: `<strong>Autenticación requerida</strong><br>${message || 'Por favor inicie sesión'}`,
            dangerouslyUseHTMLString: true,
            duration: 6250
          })
        }
        break

      case 403:
        ElMessage({
          type: 'error',
          message: `<strong>Acceso denegado</strong><br>No tiene permisos para realizar esta acción`,
          dangerouslyUseHTMLString: true,
          duration: 5000
        })
        break

      case 404:
        ElMessage({
          type: 'warning',
          message: `<strong>Recurso no encontrado</strong><br>La dirección solicitada no existe`,
          dangerouslyUseHTMLString: true,
          duration: 5000
        })
        break

      case 419:
        location.reload()
        break

      case 422:
        let errorsHtml = '<ul>'
        if (data?.errors) {
          Object.entries(data.errors).forEach(([key, value]) => {
            if (Array.isArray(value)) {
              value.forEach((element) => {
                errorsHtml += `<li><strong>${key}:</strong> ${element}</li>`
              })
            } else {
              errorsHtml += `<li><strong>${key}:</strong> ${value}</li>`
            }
          })
        }
        errorsHtml += '</ul>'

        ElMessage({
          type: 'warning',
          message: `<strong>Error de validación</strong><br>${errorsHtml}`,
          dangerouslyUseHTMLString: true,
          duration: 6250
        })
        break

      case 429:
        ElMessage({
          type: 'warning',
          message: `<strong>Demasiadas solicitudes</strong><br>Por favor espere un momento antes de intentar nuevamente`,
          dangerouslyUseHTMLString: true,
          duration: 5000
        })
        break

      case 500:
        ElMessage({
          type: 'error',
          message: `<strong>Error interno del servidor</strong><br>Por favor intente más tarde o contacte al administrador`,
          dangerouslyUseHTMLString: true,
          duration: 6250
        })
        break

      default:
        ElMessage({
          type: 'error',
          message: `<strong>Error ${status}</strong><br>${message || 'Ocurrió un error inesperado'}`,
          dangerouslyUseHTMLString: true,
          duration: 5000
        })
    }

    if (import.meta.env.DEV) {
      console.error('❌ API Error:', {
        status,
        url: error.config?.url,
        method: error.config?.method,
        data: data
      })
    }

    return Promise.reject(error)
  }
)

export default service