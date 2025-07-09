import { useAuthStore } from '@/stores/AuthStore'

/**
 * Directiva para verificar si el usuario tiene los permisos y roles requeridos para acceder a un elemento.
 *
 * @directiva canAccess
 * @param {Object} binding - El objeto de enlace de la directiva.
 * @param {Object} binding.value - El objeto de valor de la directiva.
 * @param {string[]} [binding.value.permissions] - Los permisos requeridos.
 * @param {string[]} [binding.value.roles] - Los roles requeridos.
 *
 * @example
 * <div v-canAccess="{ permissions: ['read'], roles: ['admin'] }">
 *   Este contenido solo es visible para usuarios con el permiso 'read' y el rol 'admin'.
 * </div>
 *
 * @example
 * <div v-canAccess="{ permissions: ['write'] }">
 *   Este contenido solo es visible para usuarios con el permiso 'write'.
 * </div>
 *
 * @example
 * <div v-canAccess="{ roles: ['user'] }">
 *   Este contenido solo es visible para usuarios con el rol 'user'.
 * </div>
 */
const canAccess = {
  mounted(el, binding) {
    const { value } = binding
    const authStore = useAuthStore()

    // Verificar si el usuario tiene los permisos requeridos
    const hasPermissions = value.permissions
      ? value.permissions.every((permission) => authStore.permissions.includes(permission))
      : true

    // Verificar si el usuario tiene los roles requeridos
    const hasRoles = value.roles
      ? value.roles.every((role) => authStore.roles.includes(role))
      : true

    // Ocultar el elemento si no cumple con los permisos y/o roles requeridos
    if (!hasPermissions || !hasRoles) {
      el.parentNode.removeChild(el)
    }
  }
}

export default canAccess
