<template>
  <div class="vt-wrapper">
      <LandingNavigation />
    <div ref="viewerEl" class="vt-canvas"></div>

    <div class="vt-header">
      <div class="vt-title">Virtual Tour</div>
      <div class="vt-sub">{{ currentScene?.title }}</div>
    </div>

    <div class="vt-footer">
      <button class="vt-btn" @click="goPrev">⟵ Prev</button>
      <span class="vt-id">{{ currentScene?.id }}</span>
      <button class="vt-btn" @click="goNext">Next ⟶</button>
    </div>
  </div>
</template>

<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, reactive, ref, nextTick } from 'vue'
import '@photo-sphere-viewer/core/index.css'
import '@photo-sphere-viewer/markers-plugin/index.css'
import { Viewer } from '@photo-sphere-viewer/core'
import { MarkersPlugin } from '@photo-sphere-viewer/markers-plugin'
import { useHead } from '@vueuse/head'

useHead({ title: 'Virtual Tour Suhu- ' + import.meta.env.VITE_PROJECT })

const FIRST_INDEX = 10
const LAST_INDEX = 11
const pad3 = (n: number) => n.toString().padStart(3, '0')

type LinkMarker = { id: string; to: string; yawDeg: number; pitchDeg: number; tooltip?: string }
type Scene = { id: string; title: string; src: string; links: LinkMarker[] }

const scenes: Scene[] = Array.from({ length: LAST_INDEX - FIRST_INDEX + 1 }, (_, i) => {
  const n = i + FIRST_INDEX
  const id = `sc-${pad3(n)}`
  const src = `/panos/${pad3(n)}.jpg`
  const links: LinkMarker[] = []
  if (n < LAST_INDEX) links.push({ id: `to-${pad3(n + 1)}`, to: `sc-${pad3(n + 1)}`, yawDeg: 0, pitchDeg: -6, tooltip: `Ke ${pad3(n + 1)}` })
  if (n > FIRST_INDEX) links.push({ id: `to-${pad3(n - 1)}`, to: `sc-${pad3(n - 1)}`, yawDeg: 180, pitchDeg: -6, tooltip: `Kembali ${pad3(n - 1)}` })
  return { id, title: `Scene ${pad3(n)}`, src, links }
})
const byId = (id: string) => scenes.find(s => s.id === id)
const s010 = scenes.find(s => s.id === 'sc-010')
const s011 = scenes.find(s => s.id === 'sc-011')
if (s010) {
  s010.links = [
    { id: 'to-011', to: 'sc-011', yawDeg: 31.73, pitchDeg: -32.38, tooltip: 'Ruang Kalibrasi suhu' },
  ]
}
if (s011) {
  s011.links = [
    { id: 'to-010', to: 'sc-010', yawDeg: 171.70, pitchDeg: -55.39, tooltip: 'Kembali Ke Ruang suhu' },
  ]
}

const viewerEl = ref<HTMLDivElement | null>(null)
let viewer: Viewer | null = null
let markers: MarkersPlugin | null = null

const savedSceneId = localStorage.getItem('lastSceneId');
const initialSceneId = savedSceneId && byId(savedSceneId) ? savedSceneId : scenes[0].id;
const state = reactive({ currentId: initialSceneId });

const currentScene = computed(() => byId(state.currentId))
const idx = computed(() => scenes.findIndex(s => s.id === state.currentId))
const hasPrev = computed(() => idx.value > 0)
const hasNext = computed(() => idx.value < scenes.length - 1)

function buildLinkMarker(m: LinkMarker) {
  return {
    id: m.id,
    position: { yaw: `${m.yawDeg}deg`, pitch: `${m.pitchDeg}deg` },
    image: '/arrow.png',
    size: { width: 64, height: 64 },
    anchor: 'bottom center',
    tooltip: m.tooltip ?? 'Go',
    data: { to: m.to, yawDeg: m.yawDeg },
  }
}

async function setPanoramaWithPanoData(src: string) {
  try {
    const dim = await new Promise<{ w: number; h: number }>((resolve, reject) => {
      const img = new Image()
      img.onload = () => resolve({ w: img.naturalWidth, h: img.naturalHeight })
      img.onerror = () => reject(new Error('Cannot load image: ' + src))
      img.src = src
    })
    await viewer!.setPanorama(src, {
      showLoader: true,
      panoData: {
        fullWidth: dim.w,
        fullHeight: dim.h,
        croppedWidth: dim.w,
        croppedHeight: dim.h,
        croppedX: 0,
        croppedY: 0,
      },
    })
  } catch (error) {
    console.error("Error in setPanoramaWithPanoData:", error);
    viewer?.showError("Gagal memuat panorama: " + src.split('/').pop());
  }
}

async function loadScene(id: string, opts?: { yawDeg?: number }) {
  const sc = byId(id); if (!viewer || !sc || !markers) return
  await setPanoramaWithPanoData(sc.src)
  if (opts?.yawDeg !== undefined) {
    await viewer.rotate({ yaw: `${opts.yawDeg}deg`, pitch: '0deg' }, 600)
  }
  markers.setMarkers(sc.links.map(buildLinkMarker))
  state.currentId = id
  localStorage.setItem('lastSceneId', id)
}

function goPrev() { if (!hasPrev.value) return; loadScene(scenes[idx.value - 1].id, { yawDeg: 180 }) }
function goNext() { if (!hasNext.value) return; loadScene(scenes[idx.value + 1].id, { yawDeg: 0 }) }

onMounted(async () => {
  await nextTick()
  if (!viewerEl.value) return

  Object.assign(viewerEl.value.style, { position: 'absolute', left: '0', top: '0', width: '100%', height: '100%' })

  viewer = new Viewer({
    container: viewerEl.value!,
    panorama: currentScene.value!.src,
    renderer: 'canvas',
    defaultYaw: '0deg',
    defaultPitch: '0deg',      // <-- PERUBAHAN DI SINI
    defaultZoomLvl: 0,         // <-- PERUBAHAN DI SINI
    mousewheel: true,
    touchmoveTwoFingers: true,
    navbar: ['zoom', 'fullscreen'],
    plugins: [
      [MarkersPlugin, { visibleOnLoad: true }],
    ],
  })

  viewer.addEventListener('ready', () => {
    console.log('[PSV] ready');
    loadScene(state.currentId);
  });

  viewer.addEventListener('panorama-loaded', () => console.log('[PSV] panorama-loaded'))
  viewer.addEventListener('panorama-load-failed', (e) => console.error('[PSV] load failed', e))

  markers = viewer.getPlugin(MarkersPlugin) as MarkersPlugin

  markers.addEventListener('select-marker', (ev: any) => {
    const cfg = (ev?.marker as any)?.config
    const dat = cfg?.data ?? (ev?.marker as any)?.data
    const to = dat?.to as string | undefined
    const yawDeg = typeof dat?.yawDeg === 'number' ? dat.yawDeg : undefined
    if (to) loadScene(to, { yawDeg: (yawDeg ?? 0) + 180 })
  })

  viewer.addEventListener('click', (e: any) => {
    if (!e?.data) {
      console.error('Tidak ada data di event click');
      return;
    }
    const yaw = e?.data?.yaw ?? null;
    const pitch = e?.data?.pitch ?? null;

    if (yaw === null || pitch === null) {
      console.error('Yaw atau pitch tidak ditemukan dalam data event');
      return;
    }
    const yawDeg = yaw * (180 / Math.PI);
    const pitchDeg = pitch * (180 / Math.PI);
    console.log('Place marker at:', { yawDeg: yawDeg.toFixed(2), pitchDeg: pitchDeg.toFixed(2) });
  });
})

onBeforeUnmount(() => { viewer?.destroy(); viewer = null })
</script>

<style scoped>
.vt-wrapper {
  position: relative;
  width: 100%;
  height: 100vh;
  background: #000;
  overflow: hidden;
}

.vt-canvas {
  position: absolute;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
}

.vt-header {
  position: absolute;
  left: 16px;
  top: 16px;
  z-index: 10;
  display: flex;
  flex-direction: column;
  gap: 4px;
  padding: 10px 12px;
  color: #fff;
  background: rgba(0, 0, 0, .35);
  border-radius: 12px;
  backdrop-filter: blur(6px);
}

.vt-title {
  font-weight: 700;
  letter-spacing: .2px;
}

.vt-sub {
  font-size: 12px;
  opacity: .9;
}

.vt-footer {
  position: absolute;
  right: 16px;
  bottom: 16px;
  z-index: 10;
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 8px 12px;
  color: #fff;
  background: rgba(0, 0, 0, .35);
  border-radius: 12px;
  backdrop-filter: blur(6px);
}

.vt-btn {
  padding: 6px 10px;
  border-radius: 10px;
  border: 0;
  cursor: pointer;
  background: rgba(255, 255, 255, .15);
  color: #fff;
}

.vt-btn:disabled {
  opacity: .4;
  cursor: not-allowed;
}

.vt-id {
  font-size: 12px;
  opacity: .9;
}
</style>