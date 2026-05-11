import Resource from '@/api/resource';
import request from "@/utils/request.js";

class DistritoResource extends Resource {
  constructor() {
    super('publico/distritos');
  }

  getDistritos(id_provincia) {
    return request({
      url: '/publico/provincias/' + id_provincia + '/distritos',
      method: 'get'
    })
  }
}

export { DistritoResource as default }

