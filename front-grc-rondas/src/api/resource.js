import request from '@/utils/request'

/**
 * Simple RESTful resource class
 */
class Resource {
  constructor(uri) {
    this.uri = uri
  }
  list(query) {
    return request({
      url: '/' + this.uri,
      method: 'get',
      params: query
    })
  }
  get(id) {
    return request({
      url: '/' + this.uri + '/' + id,
      method: 'get'
    })
  }
  store(resource) {
    return request({
      url: '/' + this.uri,
      method: 'post',
      data: resource
    })
  }
  update(id, resource) {
    return request({
      url: '/' + this.uri + '/' + id,
      method: 'put',
      data: resource
    })
  }
  destroy(id) {
    return request({
      url: '/' + this.uri + '/' + id,
      method: 'delete'
    })
  }
  exportar(query) {
    return request({
      url: '/' + this.uri,
      method: 'get',
      params: query
    })
  }
  get_data_dni(dni, tipo) {
    return request({
      url: '/' + this.uri + '/' + dni + '/' + tipo,
      method: 'get'
    })
  }

  //enviar un parametro key: '_method', value: 'PUT' para actualizar un registro
  updateFormDataPost(id, resource) {
    return request({
      url: '/' + this.uri + '/' + id,
      method: 'post',
      data: resource
    })
  }

  activar(id) {
    return request({
      url: '/' + this.uri + '/' + id + '/activar',
      method: 'post'
    })
  }
  inactivar(id) {
    return request({
      url: '/' + this.uri + '/' + id + '/desactivar',
      method: 'post'
    })
  }

  generarPDF(params) {
    return request({
      url: '/' + this.uri,
      method: 'post',
      responseType: 'blob', //para generar PDF  y recepcionar el pdf como blob
      params: params
    })
  }

  validarqrCarnet(data) {
    return request({
      url: '/' + data,
      method: 'get'
    })
  }
  
  registrarCita(id, resource) {
    return request({
      url: '/' + this.uri + '/' + id + '/registrarCita',
      method: 'post',
      data: resource
    })
  }

}

export { Resource as default }
