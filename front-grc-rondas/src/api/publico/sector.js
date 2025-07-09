import Resource from '@/api/resource';
import request from "@/utils/request.js";

class SectorResource extends Resource {
  constructor() {
    super('publico/sectores');
  }
  getSectores(id_distrito) {
    return request({
      url: '/publico/distritos/' + id_distrito + '/getSectores',
      method: 'get'
    })
  }
}

export { SectorResource as default }

