import Resource from '@/api/resource';
import request from "@/utils/request.js";

class UtilsResource extends Resource {
  consultarRQ(data) {
    return request({
      url: 'consultar-rq',
      method: 'post',
      data
    })
  }
}

export { UtilsResource as default }

