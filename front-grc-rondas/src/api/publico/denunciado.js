import Resource from '@/api/resource';

class DenunciadoResource extends Resource {
  constructor() {
    super('publico/denunciados');
  }
}

export { DenunciadoResource as default }