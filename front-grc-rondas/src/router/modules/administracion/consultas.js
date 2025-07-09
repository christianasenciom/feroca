import AdminLayout from '@/layout/admin/AdminLayout.vue'

const consultasRoutes = {
  path: '/consultas',
  component: AdminLayout,
  redirect: '/dashboard/index',
  name: 'Consultas',
  meta: {
    title: 'Consultas',
    icon: 'bi-search',
    noCache: true,
    // permissions: ['pub.administracion.menu']
  },
  children: [
    {
      path: 'consulta-rq',
      component: () => import('@/views/admin/consultas/consulta-rq/ConsultaRQView.vue'),
      name: 'Consulta RQ',
      meta: { title: 'Consulta RQ', icon: 'hi-solid-users', noCache: true }
      // meta: { title: 'Usuarios', noCache: true, permissions: ['auth.usuarios.listar'] }
    },
  ]
}

export default consultasRoutes
