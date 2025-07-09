import Resource from '@/api/resource';
import request from "@/utils/request.js";

class DenunciasResource extends Resource {
  constructor() {
    super('publico/denuncias');
  }
  enviarMail(query) {
    return request({
      url: '/publico/sendMail',
      method: 'post',
      data: JSON.stringify(query), // data can be `string` or {object}!
      headers:{
        'Content-Type': 'application/json'
      }
    })
  }

  getNotificaciones(id_denuncia) {
    return request({
      url: '/publico/denuncias/' + id_denuncia + '/ListaNotificaciones',
      method: 'get'
    })
  }
}


export { DenunciasResource as default }

