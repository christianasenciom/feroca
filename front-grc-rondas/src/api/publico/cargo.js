import Resource from '@/api/resource';

class CargoResource extends Resource {
  constructor() {
    super('publico/cargos');
  }
}

export { CargoResource as default }

