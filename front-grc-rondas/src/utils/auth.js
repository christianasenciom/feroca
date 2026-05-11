import axios from 'axios'

export function getToken() {
  return window.localStorage.getItem('token')
}

export function setToken(token) {
  return window.localStorage.setItem('token', token)
}

export function removeToken() {
  return window.localStorage.removeItem('token')
}

// Obtener CSRF cookie para Sanctum
export function getCsrfCookie() {
  const baseURL = import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api'
  return axios.get('/sanctum/csrf-cookie', {
    withCredentials: true,
    baseURL: baseURL
  })
}

// Verificar si el usuario está autenticado
export function isAuthenticated() {
  return !!getToken()
}

// Limpiar toda la sesión
export function clearSession() {
  removeToken()
  if (typeof window !== 'undefined') {
    window.localStorage.removeItem('user')
    window.localStorage.removeItem('roles')
    window.localStorage.removeItem('permissions')
  }
}