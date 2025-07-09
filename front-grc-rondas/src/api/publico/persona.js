// import request from '@/utils/request'

import Resource from '../resource'

class PersonaRequest extends Resource {
  constructor() {
    super('publico/personas')
  }
}

export { PersonaRequest as default }
