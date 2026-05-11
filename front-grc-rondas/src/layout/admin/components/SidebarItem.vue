<template>
  <div v-if="!item.hidden && item.children" class="menu-wrapper">
    <template
      v-if="
        hasOneShowingChild(item.children, item) &&
        (!onlyOneChild.children || onlyOneChild.noShowingChildren) &&
        !item.alwaysShow
      "
    >
      <app-link :to="resolvePath(onlyOneChild.path)">
        <el-menu-item
          :index="resolvePath(onlyOneChild.path)"
          :class="{ 'submenu-title-noDropdown': !isNest }"
        >
          <CustomItem
            v-if="onlyOneChild.meta"
            :icon="onlyOneChild.meta.icon || item.meta.icon"
            :title="onlyOneChild.meta.title"
          />
        </el-menu-item>
      </app-link>
    </template>

    <el-sub-menu v-else :index="resolvePath(item.path)">
      <template #title>
        <CustomItem v-if="item.meta" :icon="item.meta.icon" :title="item.meta.title" />
      </template>

      <template v-for="child in visibleChildren">
        <sidebar-item
          v-if="child.children && child.children.length > 0"
          :key="child.path"
          :is-nest="true"
          :item="child"
          :base-path="resolvePath(child.path)"
          class="nest-menu"
        />
        <app-link v-else :key="child.name" :to="resolvePath(child.path)">
          <el-menu-item :index="resolvePath(child.path)">
            <CustomChildrenItem v-if="child.meta" :icon="child.meta.icon" :title="child.meta.title" />
          </el-menu-item>
        </app-link>
      </template>
    </el-sub-menu>
  </div>
</template>

<script setup>
import { isExternal } from '@/utils/validate'
import { computed } from 'vue'
import CustomItem from './CustomItem.vue'
import AppLink from './AppLink.vue'
import CustomChildrenItem from "@/layout/admin/components/CustomChildrenItem.vue";

const props = defineProps({
  item: {
    type: Object,
    required: true
  },
  isNest: {
    type: Boolean,
    default: false
  },
  basePath: {
    type: String,
    default: ''
  }
})

let onlyOneChild = null

const visibleChildren = computed(() => {
  return props.item.children.filter((item) => !item.hidden)
})

function hasOneShowingChild(children, parent) {
  const showingChildren = children.filter((item) => {
    if (item.hidden) {
      return false
    } else {
      // Temp set(will be used if only has one showing child)
      onlyOneChild = item
      return true
    }
  })

  // When there is only one child router, the child router is displayed by default
  if (showingChildren.length === 1) {
    return true
  }

  // Show parent if there are no child router to display
  if (showingChildren.length === 0) {
    onlyOneChild = { ...parent, path: '', noShowingChildren: true }
    return true
  }

  return false
}

function resolvePath(routePath) {
  if (isExternalLink(routePath)) {
    return routePath
  }
  return resolverRuta(props.basePath, routePath)
}

function isExternalLink(routePath) {
  return isExternal(routePath)
}

function resolverRuta(basePath, routePath) {
  if (basePath == routePath) {
    return basePath
  }
  // Normaliza la ruta base y la ruta relativa
  const rutaBaseNormalizada = basePath.endsWith('/') ? basePath : basePath + '/'
  const rutaRelativaNormalizada = routePath.startsWith('/') ? routePath.slice(1) : routePath

  // Concatena las rutas normalizadas
  const rutaAbsoluta = rutaBaseNormalizada + rutaRelativaNormalizada

  // Normaliza la ruta final
  const rutaAbsolutaNormalizada = rutaAbsoluta
    .split('/')
    .reduce((ruta, segmento) => {
      if (segmento === '..') {
        // Elimina el último segmento si encontramos un '..'
        ruta.pop()
      } else if (segmento !== '.') {
        // Agrega el segmento a la ruta si no es '.'
        ruta.push(segmento)
      }
      return ruta
    }, [])
    .join('/')

  return rutaAbsolutaNormalizada
}
</script>
