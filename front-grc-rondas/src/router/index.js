import { createRouter, createWebHistory } from 'vue-router'
import PublicLayout from '@/layout/public/PublicLayout.vue'
import AdminLayout from '@/layout/admin/AdminLayout.vue'
import { useAuthStore } from '@/stores/AuthStore'
import getPageTitle from '@/utils/name'
import { getToken, removeToken } from '@/utils/auth'
import authRoutes from './modules/auth';
import administracionRoutes from './modules/administracion/administracion';
import { ElMessage } from 'element-plus'
import consultasRoutes from "@/router/modules/administracion/consultas.js";
import reportesRoutes from "@/router/modules/administracion/reportes.js";
import VerificarIdentidad from '@/views/web/VerificarIdentidad.vue'

const whiteList = ['/', '/home', '/about', '/signin', '/404','/validar-qr', '/error-qr']

export const routes = [
  {
    path: '/publico',
    name: 'Index',
    component: () => import('@/views/web/Home.vue'),
    meta: { title: 'Rondas'}
  },
  {
    path: '/',
    component: PublicLayout,
    redirect: '/signin',
    hidden: true,
    children: [
      {
        path: '/signin',
        name: 'SignIn',
        hidden: true,
        component: () => import('@/views/auth/SignIn.vue'),
        meta: { title: 'Login' }
      },
      {
        path: 'password',
        name: 'password',
        component: () => import('@/views/password/internalView.vue'),
        meta: { title: 'Cambio de Contraseña'}
      },
    ]
  },
  {
    path: '/dashboard',
    component: AdminLayout,
    name: 'Dashboard',
    redirect: '/dashboard/index',
    meta: {
      title: 'Panel',
      icon: 'md-spacedashboard',
      roles: ['SuperAdministrador', 'Administrador', 'RONDERO']
    },
    children: [
      {
        path: 'index',
        component: () => import('@/views/dashboard/DashboardView.vue'),
        name: 'Panel',
        meta: {
          title: 'Panel de control',
          icon: 'md-spacedashboard',
          roles: ['SuperAdministrador', 'Administrador', 'RONDERO']
        }
      },
      {
        path: 'admin/auditoria',
        name: 'Auditoria',
        component: () => import('@/views/admin/auditoria/AuditoriaView.vue'),
        meta: {
          title: 'Auditoría',
          icon: 'List',
          roles: ['SuperAdministrador', 'Administrador']
        }
      }
    ]
  },
  // 🔥 RUTA CONSULTA RQ (CON ADMINLAYOUT PARA QUE MUESTRE SIDEBAR)
  {
    path: '/consulta-rq',
    component: AdminLayout,
    redirect: '/consulta-rq/index',
    children: [
      {
        path: 'index',
        name: 'ConsultaRQ',
        component: () => import('@/views/admin/consultas/consulta-rq/ConsultaRQView.vue'),
        meta: {
          title: 'Consulta RQ',
          icon: 'Search',
          roles: ['SuperAdministrador', 'Administrador', 'RONDERO']
        }
      }
    ]
  },
  {
    path: '/verificar-identidad',
    component: AdminLayout,
    redirect: '/verificar-identidad/index',
    children: [
      {
        path: 'index',
        name: 'VerificarIdentidad',
        component: VerificarIdentidad,
        meta: {
          title: 'Verificar Identidad',
          icon: 'User',
          roles: ['SuperAdministrador', 'Administrador', 'RONDERO']
        }
      }
    ]
  },
  {
    path: '/profile',
    component: AdminLayout,
    redirect: '/profile/index',
    hidden: true,
    children: [
      {
        path: 'index',
        component: () => import('@/views/profile/ProfileView.vue'),
        name: 'Profile',
        meta: {
          title: 'Perfil',
          icon: 'fa-user-circle',
          noCache: true,
          roles: ['SuperAdministrador', 'Administrador', 'RONDERO']
        }
      }
    ]
  },
  {
    path: '/404',
    name: 'NotFound',
    component: () => import('@/views/error-pages/404ErrorPage.vue'),
    hidden: true
  },
  {
    path: '/500',
    name: 'ServerError',
    component: () => import('@/views/error-pages/500ErrorPage.vue'),
    hidden: true
  },
  {
    path: '/403',
    name: 'Forbidden',
    component: () => import('@/views/error-pages/403ErrorPage.vue'),
    hidden: true
  },
  {
    path: '/validar-qr',
    name: 'validar-qr',
    component: () => import('@/views/qr/QRView.vue'),
    meta: { title: 'Validar QR'}
  },
  {
    path: '/error-qr',
    name: 'error-qr',
    component: () => import('@/views/qr/404ErrorQR.vue'),
    meta: { title: 'ERROR QR'}
  }
]

export const asyncRoutes = [
  authRoutes,
  administracionRoutes,
  consultasRoutes,
  reportesRoutes,
  {
    path: '/:pathMatch(.*)*',
    name: 'NotFound',
    component: () => import('@/views/error-pages/404ErrorPage.vue'),
    hidden: true
  },
]

const initRouter = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
})
const router = initRouter

export function resetRouter() {
  const newRouter = initRouter
  router.matcher = newRouter.matcher
}

router.beforeEach(
  async (to, from, next) => {
    const authStore = useAuthStore()
    document.title = getPageTitle(to.meta.title)
    authStore.changePageCurrentInfo(to.meta)

    const requiereRoles = to.meta?.roles && to.meta.roles.length > 0

    if (getToken()) {
      if (to.path === '/signin') {
        next('/dashboard')
      } else {
        if (authStore.getRoles && authStore.getRoles.length > 0) {
          if (requiereRoles) {
            const tieneRolPermitido = to.meta.roles.some(rol => authStore.getRoles.includes(rol))
            if (!tieneRolPermitido) {
              ElMessage.error('No tiene permiso para acceder a esta página')
              next('/dashboard')
              return
            }
          }
          next()
        } else {
          try {
            const { roles, permissions } = await authStore.userInfo()
            if (roles.length === 0 && permissions.length === 0) {
              await ElMessage({
                message: 'No cuenta con roles asignados, por favor contacte al administrador del sistema.',
                type: 'info'
              })
              removeToken()
              next(`/signin`)
            } else {
              await authStore.generateRoutes(roles, permissions)
              next({ ...to, replace: true })
            }
          } catch (error) {
            console.log(error)
            next(`/signin`)
          }
        }
      }
    } else {
      if (whiteList.indexOf(to.path) !== -1) {
        next()
      } else {
        next(`/signin`)
      }
    }
  },
  { once: true }
)

export default router