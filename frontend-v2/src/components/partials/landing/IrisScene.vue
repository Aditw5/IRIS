<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue'

const props = defineProps<{ paused: boolean }>()
const host = ref<HTMLElement | null>(null)
const ready = ref(false)
let disposed = false
let scene: { setPaused: (paused: boolean) => void; dispose: () => void } | undefined
onMounted(async () => {
  try {
    const { createIrisScene } = await import('./irisScene.js')
    if (disposed || !host.value) return
    scene = createIrisScene(host.value, {
      paused: props.paused,
      onReady: () => {
        ready.value = true
      },
      onFailure: () => {
        ready.value = false
      },
    })
  } catch {
    // The provided brand asset is the fallback on devices without WebGL.
    ready.value = false
  }
})
watch(
  () => props.paused,
  (value) => scene?.setPaused(value)
)
onBeforeUnmount(() => {
  disposed = true
  scene?.dispose()
})
</script>

<template>
  <div
    class="iris-scene"
    :class="{ 'is-ready': ready }"
    role="img"
    aria-label="Simbol IRIS tiga dimensi di pusat ekosistem yang terhubung"
  >
    <div class="iris-scene-fallback" aria-hidden="true"><img src="/IRIS_1.png" alt="" /></div>
    <div ref="host" class="iris-scene-canvas" aria-hidden="true"></div>
  </div>
</template>

<style scoped>
.iris-scene {
  position: absolute;
  inset: -6% -10%;
}
.iris-scene-canvas {
  position: absolute;
  inset: 0;
  opacity: 0;
  transition: opacity 1s ease;
}
.is-ready .iris-scene-canvas {
  opacity: 1;
}
.iris-scene-canvas :deep(canvas) {
  display: block;
  width: 100%;
  height: 100%;
}
.iris-scene-fallback {
  position: absolute;
  left: 17.5%;
  top: 50%;
  width: 65%;
  aspect-ratio: 1;
  transform: translateY(-50%);
  overflow: hidden;
  opacity: 1;
  transition: opacity 0.6s;
}
.iris-scene-fallback img {
  position: absolute;
  width: 154%;
  max-width: none;
  left: -26%;
  top: -5%;
  height: auto;
}
.is-ready .iris-scene-fallback {
  opacity: 0;
}
@media (prefers-reduced-motion: reduce) {
  .iris-scene-canvas,
  .iris-scene-fallback {
    transition: none;
  }
}
</style>
