import Resource from '@/api/resource';
import request from "@/utils/request.js";

class ReportesResource extends Resource {
  constructor() {
    super('publico/reportes');
  }

  denunciasPorFechas(data) {
    return request({
      url: '/' + this.uri + '/denuncias-fechas',
      method: 'post',
      data
    })
  }

  denunciasPorFechasExcel(data) {
    return request({
      url: '/' + this.uri + '/denuncias-fechas-excel',
      method: 'post',
      responseType: 'blob',
      data,
    })
  }
  denunciasPorPersona(data) {
    return request({
      url: '/' + this.uri + '/denuncias-persona',
      method: 'post',
      data
    })
  }

  denunciasPorPersonaExcel(data) {
    return request({
      url: '/' + this.uri + '/denuncias-persona-excel',
      method: 'post',
      responseType: 'blob',
      data,
    })
  }
  tipos(data) {
    return request({
      url: '/' + this.uri + '/tipos',
      method: 'post',
      data,
    })
  }

  denunciasPorCriterios(data) {
    return request({
      url: '/' + this.uri + '/denuncias-criterios',
      method: 'post',
      data
    })
  }

  denunciasPorCriteriosExcel(data) {
    return request({
      url: '/' + this.uri + '/denuncias-criterios-excel',
      method: 'post',
      responseType: 'blob',
      data,
    })
  }

  asambleasPorFechas(data) {
    return request({
      url: '/' + this.uri + '/asambleas-fechas',
      method: 'post',
      data
    })
  }

  asambleasPorFechasExcel(data) {
    return request({
      url: '/' + this.uri + '/asambleas-fechas-excel',
      method: 'post',
      responseType: 'blob',
      data,
    })
  }

}

export { ReportesResource as default }

