import Resource from '@/api/resource';

class ConflictoResource extends Resource {
  constructor() {
    super('publico/conflictos');
  }
}

export { ConflictoResource as default }

