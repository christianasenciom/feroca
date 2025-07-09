import Resource from '@/api/resource';
import request from "@/utils/request.js";

class ComiteResource extends Resource {
  constructor() {
    super('publico/comites');
  }
  getComiteables(type) {
    return request({
      url: '/publico/comites/getComiteables',
      method: 'post',
      params: {
        type: type
      }
    })
  }
  getComiteablesByRondero(id_rondero) {
    return request({
      url: '/publico/comites/' + id_rondero + '/cargos',
      method: 'get',
    })
  }

  getAvailableCargos(params) {
    return request({
      url: '/publico/comites/cargos_validos',
      method: 'post',
      params: params
    })
  }
}

export { ComiteResource as default }

