import Resource from '@/api/resource';
import request from "@/utils/request.js";

class ComiteResource extends Resource {
  constructor() {
    super('publico/comites');
  }
  
  /**
   * Obtiene las entidades disponibles (Regiones, Provincias, Distritos, Sectores, Bases)
   * @param {string} type - Tipo de entidad
   */
  getComiteables(type) {
    return request({
      url: '/publico/comites/getComiteables',
      method: 'get',
      params: { type: type }
    })
  }

  getAvailableCargos(params) {
    return request({
      url: '/publico/comites/cargos_validos',
      method: 'get',
      params: params
    })
  }
  
  /**
   * Obtiene los cargos asignados a un rondero específico
   */
  getComiteablesByRondero(id_rondero) {
    return request({
      url: '/publico/comites/' + id_rondero + '/cargos',
      method: 'get',
    })
  }

}

export { ComiteResource as default }