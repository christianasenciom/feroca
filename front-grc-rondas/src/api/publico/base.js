import Resource from '@/api/resource';
import request from "@/utils/request.js";

class BasesResource extends Resource {
  constructor() {
    super('publico/bases');
  }

    getBases(id_sector) {
    return request({
      url: '/publico/sectores/' + id_sector + '/bases',
      method: 'get'
    })
  }

  getRonderosByBase(query) {
    return request({
      url: '/publico/bases/ronderos',
      method: 'get',
      params: query
    })
  }
  getBasesPorDistrito(distrito_id) {
    console.log('📤 getBasesPorDistrito llamado con distrito_id:', distrito_id);
    return request({
      url: `/publico/bases/distrito/${distrito_id}/bases`,
      method: 'get'
    })
  }
}

export { BasesResource as default }

