import Resource from '../resource'
import request from "@/utils/request.js";

class RonderoRequest extends Resource {
  constructor() {
    super('publico/ronderos')
  }

}

export { RonderoRequest as default }
