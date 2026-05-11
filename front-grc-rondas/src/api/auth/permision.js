import request from '@/utils/request'

import Resource from '../resource'

class PermissionRequest extends Resource {
  constructor() {
    super('auth/permisos')
  }
  all() {
    return request({
      url: '/' + this.uri + 'all',
      method: 'get'
    })
  }
  fetchPermissionsForRole(params) {
    return request({
      url: '/permissionsforrole',
      method: 'get',
      params
    })
  }
}

export { PermissionRequest as default }