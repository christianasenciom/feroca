import request from '@/utils/request'

import Resource from '../resource'

class UserRequest extends Resource {
  constructor() {
    super('auth/usuarios')
  }
  changePass(data) {
    return request({
      // url: '/+ this.uri +/initnewpassword',
      url: '/auth/initnewpassword',
      method: 'post',
      data
    })
  }
}

export { UserRequest as default }