import request from '@/utils/request'

export function signin(data) {
  return request({
    url: '/auth/signin',
    method: 'post',
    data
  })
}

export function userInfo() {
  return request({
    url: '/auth/webuser',
    method: 'get'
  })
}

export function logout() {
  return request({
    url: '/auth/weblogout',
    method: 'post'
  })
}

export function newpassword(data) {
  return request({
    url: '/auth/newpassword',
    method: 'post',
    data
  })
}

export const updateimage = (formData) => {
  return request({
    url: '/user/avatar',
    method: 'post',
    data: formData,
    headers: { 'Content-Type': 'multipart/form-data' }
  })
}

export function logoutAllSessions() {
  return request({
    url: '/auth/weblogoutall',
    method: 'post'
  })
}

export function csrf() {
  return request({
    url: '/sanctum/csrf-cookie',
    method: 'get'
  })
}

export function getCsrfCookie() {
  return csrf()
}
