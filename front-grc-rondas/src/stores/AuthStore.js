import { defineStore } from 'pinia'
import { csrf, signin, userInfo, logout, logoutAllSessions } from '@/api/auth'
import { setToken, getToken, removeToken } from '@/utils/auth'
import router, { asyncRoutes, routes, resetRouter } from '@/router'

export const useAuthStore = defineStore('AuthStore', {
  state: () => ({
    token: getToken(),
    id: null,
    name: null,
    persona: null,
    fecha_nacimiento: null,
    email: null,
    avatar: null,
    roles: [],
    permissions: [],
    userAccessRoutes: [],
    currentPageInfo: { title: null, icon: null },
    cambioPassword: false,
    permissionsLoaded: false,
  }),
  getters: {
    getUser: (state) => state.email,
    getRoles: (state) => state.roles,
    getPermissions: (state) => state.permissions,
    getCurrentPageInfo: (state) => state.currentPageInfo,
    validPermision: (state) => (param) => {
      return state.permissions.includes(param)
    },
    changePass: (state) => state.cambioPassword,
  },
  actions: {
    setToken(token) {
      this.token = token
    },

    async signIn(userCredentials) {
      try {
        // 1. Obtener CSRF cookie (necesario para Sanctum en producción)
        await csrf()

        // 2. Hacer login
        const response = await signin(userCredentials)
        const { token, cambioPassword } = response

        this.cambioPassword = cambioPassword
        this.setToken(token)
        setToken(token)

        // Prefetch user info and routes to avoid heavy work during router navigation
        try {
          const { roles, permissions } = await this.userInfo()
          await this.generateRoutes(roles, permissions)
        } catch (err) {
          // If prefetch fails, clear session and rethrow to let UI handle it
          console.error('Prefetch user info failed:', err)
        }

        return response
      } catch (error) {
        console.error('SignIn error:', error)
        throw error
      }
    },


    async userInfo() {
      try {
        const response = await userInfo()
        const { data } = response
        if (!data) {
          throw new Error('Ha surgido un error, inténtelo nuevamente')
        }
        const { id, name, persona, fecha_nacimiento, email, avatar, roles, permissions } = data
        this.id = id
        this.name = name
        this.persona = persona
        this.fecha_nacimiento = fecha_nacimiento
        this.email = email
        this.avatar = avatar
        this.roles = roles
        this.permissions = permissions
        this.permissionsLoaded = true
        return { roles, permissions }
      } catch (error) {
        this.resetSession()
        throw error
      }
    },

    async logout(action) {
      if (action === 'all') {
        await logoutAllSessions()
      } else {
        await logout()
      }
      this.resetSession()
    },

    resetSession() {
      this.token = null
      this.id = null
      this.name = null
      this.email = null
      this.avatar = null
      this.roles = []
      this.permissions = []
      this.userAccessRoutes = []
      this.permissionsLoaded = false
      removeToken()
      resetRouter()
    },

    async generateRoutes(roles, permissions) {
      try {
        console.log('[AuthStore] generateRoutes start', { roles, permissions })
        const t0 = Date.now()
        let accessedRoutes
        if (roles.includes('SuperAdministrador')) {
          accessedRoutes = asyncRoutes || []
        } else {
          accessedRoutes = defineUserAccessRoutes(asyncRoutes, roles, permissions)
        }
        const t1 = Date.now()
        console.log('[AuthStore] computed accessedRoutes length', accessedRoutes.length, 'compute_ms', t1 - t0)

        this.userAccessRoutes = routes.concat(accessedRoutes)
        let added = 0
        accessedRoutes.forEach((route) => {
          try {
            router.addRoute(route)
            added++
          } catch (e) {
            console.error('[AuthStore] error adding route', route.name || route.path, e)
          }
        })
        const t2 = Date.now()
        console.log('[AuthStore] generateRoutes finished', { added, total: accessedRoutes.length, duration_ms: t2 - t0 })
        return this.userAccessRoutes
      } catch (e) {
        console.error('[AuthStore] generateRoutes failed', e)
        throw e
      }
    },

    resetToken() {
      this.token = null
      this.roles = []
      removeToken()
    },

    changePageCurrentInfo(info) {
      this.currentPageInfo = info
    },

    changeAvatar(avatar) {
      console.log('Actualizando avatar: ', avatar)
      this.avatar = avatar
    }
  }
})

function defineUserAccessRoutes(routes, roles, permissions) {
  const userAccessRoutes = []
  routes.forEach((route) => {
    const tmp = { ...route }
    if (canAccess(roles, permissions, tmp)) {
      if (tmp.children) {
        tmp.children = defineUserAccessRoutes(tmp.children, roles, permissions)
      }
      userAccessRoutes.push(tmp)
    }
  })
  return userAccessRoutes
}

function canAccess(roles, permissions, route) {
  if (route.meta) {
    let hasRole = true
    let hasPermission = true
    if (route.meta.roles || route.meta.permissions) {
      hasRole = false
      hasPermission = false
      if (route.meta.roles) {
        hasRole = roles.some((role) => route.meta.roles.includes(role))
      }
      if (route.meta.permissions) {
        hasPermission = permissions.some((permission) =>
          route.meta.permissions.includes(permission)
        )
      }
    }
    return hasRole || hasPermission
  }
  return true
}