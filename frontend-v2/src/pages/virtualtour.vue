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

useHead({ title: 'Virtual Tour - ' + import.meta.env.VITE_PROJECT })

const FIRST_INDEX = 1
const LAST_INDEX = 21
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

const s001 = scenes.find(s => s.id === 'sc-001')
const s002 = scenes.find(s => s.id === 'sc-002')
const s003 = scenes.find(s => s.id === 'sc-003')
const s004 = scenes.find(s => s.id === 'sc-004')
const s005 = scenes.find(s => s.id === 'sc-005')
const s006 = scenes.find(s => s.id === 'sc-006')
const s007 = scenes.find(s => s.id === 'sc-007')
const s008 = scenes.find(s => s.id === 'sc-008')
const s009 = scenes.find(s => s.id === 'sc-009')
const s010 = scenes.find(s => s.id === 'sc-010')
const s011 = scenes.find(s => s.id === 'sc-011')
const s012 = scenes.find(s => s.id === 'sc-012')
const s013 = scenes.find(s => s.id === 'sc-013')
const s014 = scenes.find(s => s.id === 'sc-014')
const s015 = scenes.find(s => s.id === 'sc-015')
const s016 = scenes.find(s => s.id === 'sc-016')
const s017 = scenes.find(s => s.id === 'sc-017')
const s018 = scenes.find(s => s.id === 'sc-018')
const s019 = scenes.find(s => s.id === 'sc-019')
const s020 = scenes.find(s => s.id === 'sc-020')
const s021 = scenes.find(s => s.id === 'sc-021')
if (s001) {
  s001.links = [
    { id: 'to-002', to: 'sc-002', yawDeg: 149.10, pitchDeg: 4.85, tooltip: 'Menuju Papan Informasi' },
  ]
}
if (s002) {
  s002.links = [
    { id: 'to-003', to: 'sc-003', yawDeg: 356.32, pitchDeg: -24.75, tooltip: 'Menuju Lobby' },
    { id: 'to-001', to: 'sc-001', yawDeg: 86.72, pitchDeg: -28.67, tooltip: 'Menuju Lantai 2' },
  ]
}
if (s003) {
  s003.links = [
    { id: 'to-002', to: 'sc-002', yawDeg: 194.30, pitchDeg: -29.56, tooltip: 'Menuju Papan Informasi' },
    { id: 'to-004', to: 'sc-004', yawDeg: 321.84, pitchDeg: -24.03, tooltip: 'Menuju Ruang Karantina' },
    { id: 'to-005', to: 'sc-005', yawDeg: 38.61, pitchDeg: -24.71, tooltip: 'Menuju Lorong Utama' },
  ]
}
if (s004) {
  s004.links = [
    { id: 'to-003', to: 'sc-003', yawDeg: 177.05, pitchDeg: -28.53, tooltip: 'Kembali ke Lobby' },
  ]
}
if (s005) {
  s005.links = [
    { id: 'to-006', to: 'sc-006', yawDeg: 6.91, pitchDeg: -28.87, tooltip: 'Menuju Ruang Admin' },
  ]
}
if (s006) {
  s006.links = [
    { id: 'to-007', to: 'sc-007', yawDeg: 82.46, pitchDeg: -34.57, tooltip: 'Ruang Admin' },
    { id: 'to-008', to: 'sc-008', yawDeg: 300.91, pitchDeg: -46.33, tooltip: 'Menuju Ruangan Repair' },
    { id: 'to-009', to: 'sc-009', yawDeg: 358.24, pitchDeg: -18.81, tooltip: 'Lorong Utama' },
  ]
}
if (s007) {
  s007.links = [
    { id: 'to-006', to: 'sc-006', yawDeg: 234.72, pitchDeg: -33.13, tooltip: 'Kembali Ke Lorong Utama' },
    { id: 'to-008', to: 'sc-008', yawDeg: 266.58, pitchDeg: -23.87, tooltip: 'Menuju Ruangan Repair' },
  ]
}
if (s008) {
  s008.links = [
    { id: 'to-006', to: 'sc-006', yawDeg: 170.36, pitchDeg: -25.62, tooltip: 'Kembali Ke Lorong Utama' },
  ]
}
if (s009) {
  s009.links = [
    { id: 'to-010', to: 'sc-010', yawDeg: 263.32, pitchDeg: -44.07, tooltip: 'Menuju Ruangan Suhu' },
    { id: 'to-012', to: 'sc-012', yawDeg: 28.90, pitchDeg: -29.48, tooltip: 'Ruang Baca' },
    { id: 'to-013', to: 'sc-013', yawDeg: 345.51, pitchDeg: -19.13, tooltip: 'Lorong Utama' },
  ]
}
if (s010) {
  s010.links = [
    { id: 'to-011', to: 'sc-011', yawDeg: 31.73, pitchDeg: -32.38, tooltip: 'Ruang Kalibrasi suhu' },
    { id: 'to-009', to: 'sc-009', yawDeg: 225.75, pitchDeg: -26.74, tooltip: 'Kembali Ke Lorong Utama' },
  ]
}
if (s011) {
  s011.links = [
    { id: 'to-010', to: 'sc-010', yawDeg: 171.70, pitchDeg: -55.39, tooltip: 'Kembali Ke Ruang suhu' },
  ]
}
if (s012) {
  s012.links = [
    { id: 'to-009', to: 'sc-009', yawDeg: 220.40, pitchDeg: -27.74, tooltip: 'Kembali Ke Lorong Utama' },
  ]
}
if (s013) {
  s013.links = [
    { id: 'to-014', to: 'sc-014', yawDeg: 16.54, pitchDeg: -10.84, tooltip: 'Pantry' },
    { id: 'to-009', to: 'sc-009', yawDeg: 170.21, pitchDeg: -23.65, tooltip: 'Lorong Utama' },
    { id: 'to-012', to: 'sc-012', yawDeg: 139.03, pitchDeg: -17.41, tooltip: 'Ruang Baca' },
    { id: 'to-015', to: 'sc-015', yawDeg: 353.04, pitchDeg: -13.50, tooltip: 'Lorong Utama' },
  ]
}
if (s014) {
  s014.links = [
    { id: 'to-015', to: 'sc-015', yawDeg: 266.51, pitchDeg: -30.61, tooltip: 'Lorong Utama' },
  ]
}
if (s015) {
  s015.links = [
    { id: 'to-013', to: 'sc-013', yawDeg: 213.02, pitchDeg: -30.72, tooltip: 'Lorong Utama' },
    { id: 'to-016', to: 'sc-016', yawDeg: 247.68, pitchDeg: -30.16, tooltip: 'Menuju Ruang Tenakan & Vibrasi' },
    { id: 'to-018', to: 'sc-018', yawDeg: 281.51, pitchDeg: -32.26, tooltip: 'Menuju Ruang Kelistrikan' },
    { id: 'to-020', to: 'sc-020', yawDeg: 85.92, pitchDeg: -41.13, tooltip: 'Menuju Ruang Asman' },
    { id: 'to-021', to: 'sc-021', yawDeg: 48.15, pitchDeg: -31.27, tooltip: 'Menuju Ruang Penyelia' },
  ]
}
if (s016) {
  s016.links = [
    { id: 'to-017', to: 'sc-017', yawDeg: 356.14, pitchDeg: -28.74, tooltip: 'Ruang Kalibrasi Tekanan & Vibrasi' },
    { id: 'to-015', to: 'sc-015', yawDeg: 234.32, pitchDeg: -27.39, tooltip: 'Kembali Ke Lorong Utama' },
  ]
}
if (s017) {
  s017.links = [
    { id: 'to-015', to: 'sc-015', yawDeg: 209.19, pitchDeg: -11.01, tooltip: 'Kembali Ke Lorong Utama' },
    { id: 'to-016', to: 'sc-016', yawDeg: 176.60, pitchDeg: -24.09, tooltip: 'Ruang Kalibrasi Tekanan & Vibrasi' },
  ]
}
if (s018) {
  s018.links = [
    { id: 'to-015', to: 'sc-015', yawDeg: 109.11, pitchDeg: -33.91, tooltip: 'Kembali Ke Lorong Utama' },
    { id: 'to-019', to: 'sc-019', yawDeg: 356.84, pitchDeg: -22.92, tooltip: 'Ruang Kalibrasi Kelistrikan' },
  ]
}
if (s019) {
  s019.links = [
    { id: 'to-015', to: 'sc-015', yawDeg: 163.42, pitchDeg: -11.49, tooltip: 'Kembali Ke Lorong Utama' },
    { id: 'to-018', to: 'sc-018', yawDeg: 188.15, pitchDeg: -20.26, tooltip: 'Ruang Kalibrasi Kelistrikan' },
  ]
}
if (s020) {
  s020.links = [
    { id: 'to-015', to: 'sc-015', yawDeg: 185.55, pitchDeg: -40.08, tooltip: 'Kembali Ke Lorong Utama' },
  ]
}
if (s021) {
  s021.links = [
    { id: 'to-015', to: 'sc-015', yawDeg: 241.88, pitchDeg: -42.21, tooltip: 'Kembali Ke Lorong Utama' },
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