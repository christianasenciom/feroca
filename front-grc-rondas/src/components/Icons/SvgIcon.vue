<template>
  <div class="svg-container" :style="svgStyle">
    <component :is="dynamicSvgComponent" class="svg-icon" />
  </div>
</template>

<script setup>
import { defineAsyncComponent } from 'vue'

const props = defineProps({
  name: {
    type: String,
    required: true
  },
  scale: {
    type: String,
    default: '1'
  }
})

const importSvgComponent = (name) => {
  return defineAsyncComponent(() =>
    import(`./svg/${name}.svg`)
      .then((module) => module.default)
      .catch((error) => {
        console.error(`Error loading SVG: ${name}`, error)
        return null
      })
  )
}

const dynamicSvgComponent = importSvgComponent(props.name)

const svgStyle = {
  width: `calc( 1.5em *${parseFloat(props.scale)})`,
  display: 'inline-block',
  verticalAlign: 'middle'
}
</script>

<style scoped>
.svg-container {
  line-height: 0;
}

.svg-icon {
  fill: currentColor;
  stroke: currentColor;
}
</style>
