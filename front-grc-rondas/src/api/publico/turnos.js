import Resource from '@/api/resource';
import request from "@/utils/request.js";

class TurnosResource extends Resource {
  constructor() {
    super('publico/turnos');
  }

  updateAsistencia(id, resource) {
    return request({
      url: '/asamblea/updateAsistencia/' + id,
      method: 'put',
      data: resource
    })
  }

  subirFotoDocumento(resource, id) {
    return request({
      url: '/asamblea/subirFotoDocumento/' + id,
      method: 'post',
      data: resource
    })
  }
}

export { TurnosResource as default }

