<template>
  <footer class="admin-footer" :class="{ 'footer-collapsed': isCollapse }">
    <div class="footer-content">
      <div class="footer-copyright">
        <span>Copyright CAAM © 2025 Gobierno Regional Cajamarca. Todos los derechos reservados</span>
      </div>
      <div class="footer-developer">
        <span>Desarrollo: DRTD</span>
      </div>
    </div>
  </footer>
</template>

<script setup>
import { computed } from 'vue'
import { useAppStore } from '@/stores/AppStore'

const appStore = useAppStore()
const isCollapse = computed(() => !appStore.sidebarActive)
</script>

<style scoped>
.admin-footer {
  background-color: var(--template-color-primary);
  color: #fff;
  padding: 12px 24px;
  font-size: 13px;
  border-top: 1px solid rgba(255, 255, 255, 0.1);
  transition: margin-left 0.3s ease, width 0.3s ease;
  margin-left: 260px;
  width: calc(100% - 260px);
  position: fixed;
  bottom: 0;
  right: 0;
  z-index: 999;
  flex-shrink: 0;
}

.admin-footer.footer-collapsed {
  margin-left: 64px;
  width: calc(100% - 64px);
}

.footer-content {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 10px;
}

.footer-copyright span {
  opacity: 0.9;
}

.footer-developer span {
  font-weight: 500;
  color: #ffd700;
}

/* Responsive */
@media screen and (max-width: 768px) {
  .admin-footer {
    margin-left: 0;
    width: 100%;
    padding: 10px 16px;
    position: relative;
  }

  .admin-footer.footer-collapsed {
    margin-left: 0;
    width: 100%;
  }

  .footer-content {
    flex-direction: column;
    text-align: center;
    gap: 5px;
  }

  .footer-copyright span,
  .footer-developer span {
    font-size: 11px;
  }
}

@media screen and (max-width: 480px) {
  .admin-footer {
    padding: 8px 12px;
  }

  .footer-copyright span,
  .footer-developer span {
    font-size: 10px;
  }
}
</style>