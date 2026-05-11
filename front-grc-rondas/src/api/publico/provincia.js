import Resource from '@/api/resource';
import request from "@/utils/request.js";

class ProvinciaResource extends Resource {
  constructor() {
    super('publico/provincias');
  }
  getProvincias(id_region) {
    return request({
      url: '/publico/regiones/' + id_region + '/provincias',
      method: 'get'
    })
  }
}

export { ProvinciaResource as default }

