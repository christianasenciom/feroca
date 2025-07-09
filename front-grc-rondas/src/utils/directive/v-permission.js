import { watchEffect } from 'vue';
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();

function checkPermission(el, binding) {
  const { value } = binding;
  const permissions = authStore.getPermissions();

  if (value && value instanceof Array && value.length > 0) {
    const permissionRoles = value;

    const hasPermission = permissions.some(permission => {
      return permissionRoles.includes(permission);
    });

    if (!hasPermission) {
      el.parentNode && el.parentNode.removeChild(el);
    }
  } else {
    throw new Error(
      `Necesita tener permisos! como v-permission="['litar.permisos']"`
    );
  }
}

export default {
  mounted(el, binding) {
    const unwatch = watchEffect(() => {
      checkPermission(el, binding);
    });

    // Limpia el watcher cuando el elemento se destruye
    el._cleanup = () => unwatch();
    checkPermission(el, binding);
  },
  updated(el, binding) {
    checkPermission(el, binding);
  },
  unmounted(el) {
    // Limpia el watcher cuando el elemento se destruye
    el._cleanup && el._cleanup();
  }
};
