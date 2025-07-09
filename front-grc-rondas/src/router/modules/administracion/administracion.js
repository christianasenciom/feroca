import AdminLayout from '@/layout/admin/AdminLayout.vue'

const administracionRoutes = {
  path: '/administracion',
  component: AdminLayout,
  redirect: '/dashboard/index',
  name: 'Administración',
  meta: {
    title: 'Administración',
    icon: 'md-playlistaddcheck-round',
    noCache: true,
    permissions: ['pub.administracion.menu']
  },
  children: [
    {
      path: 'ronderos',
      component: () => import('@/views/admin/publico/ronderos/RonderosView.vue'),
      name: 'Ronderos',
      meta: { title: 'Ronderos', icon: 'hi-solid-users', noCache: true, permissions: ['pub.rondero.listar'] }
      // meta: { title: 'Usuarios', noCache: true, permissions: ['auth.usuarios.listar'] }
    },
    // {
    //   path: 'personas',
    //   component: () => import('@/views/admin/publico/personas/PersonasView.vue'),
    //   name: 'PersonasPub',
    //   meta: { title: 'Personas', icon: 'hi-solid-users', noCache: true, permissions: 'pub.personas.listar' },
    // },
    {
      path: 'regiones',
      component: () => import('@/views/admin/publico/regiones/RegionesView.vue'),
      name: 'RegionesPub',
      meta: { title: 'Regiones', icon: 'map-icon', noCache: true, permissions: 'pub.regiones.listar' },
    },
    {
      path: 'provincias',
      component: () => import('@/views/admin/publico/provincias/ProvinciasView.vue'),
      name: 'ProvinciasPub',
      meta: { title: 'Provincias', icon: 'map-icon', noCache: true, permissions: 'pub.provincias.listar' },
    },
    {
      path: 'distritos',
      component: () => import('@/views/admin/publico/distritos/DistritosView.vue'),
      name: 'DistritosPub',
      meta: { title: 'Distritos', icon: 'map-icon', noCache: true, permissions: 'pub.distritos.listar' },
    },
    {
      path: 'sectores',
      component: () => import('@/views/admin/publico/sectores/SectoresView.vue'),
      name: 'SectoresPub',
      meta: { title: 'Sectores', icon: 'map-icon', noCache: true, permissions: 'pub.sectores.listar' },
    },
    {
      path: 'bases',
      component: () => import('@/views/admin/publico/bases/BasesView.vue'),
      name: 'BasesPub',
      meta: { title: 'Bases', icon: 'map-icon', noCache: true, permissions: 'pub.bases.listar' },
    },
    {
      path: 'cargos',
      component: () => import('@/views/admin/publico/cargos/CargosView.vue'),
      name: 'CargosPub',
      meta: { title: 'Cargos', icon: 'md-accounttree-round', noCache: true, permissions: 'pub.cargos.listar' },
    },
    {
      path: 'conflictos',
      component: () => import('@/views/admin/publico/conflictos/ConflictosView.vue'),
      name: 'ConflictosPub',
      meta: { title: 'Conflictos', icon: 'md-inventory-outlined', noCache: true, permissions: 'pub.conflictos.listar' },
    },
    {
      path: 'turnos',
      component: () => import('@/views/admin/publico/grupos_vigilancia/GruposVigilanciaView.vue'),
      name: 'TurnosPub',
      meta: { title: 'Turnos Vigilancia', icon: 'md-cloudsync', noCache: true, permissions: 'pub.gruposvigilancia.listar' },
    },
    {
      path: 'denuncias',
      component: () => import('@/views/admin/publico/denuncias/DenunciasView.vue'),
      name: 'DenunciasPub',
      meta: { title: 'Denuncias', icon: 'md-pointofsale', noCache: true, permissions: 'pub.denuncias.listar' },
    },
    {
      path: 'asambleas',
      component: () => import('@/views/admin/publico/asambleas/AsambleasGeneralesView.vue'),
      name: 'AsambleasPub',
      meta: { title: 'Asambleas Generales', icon: 'hi-solid-users', noCache: true },
    },
  ]
}

export default administracionRoutes
