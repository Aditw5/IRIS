<!-- StrukturOrganisasi.vue -->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useHead } from '@vueuse/head'

useHead({ title: 'U-LAB | Struktur Organisasi' })

/* ====== Banner Parallax var (sama seperti halaman Profil) ====== */
const onScroll = () => {
  const y = window.scrollY || 0
  document.documentElement.style.setProperty('--scrollY', String(y))
}

/* ====== Gambar Struktur ====== */
const IMG_SRC = '/struktur_organisasi.svg'

const wrapRef = ref<HTMLDivElement | null>(null)
const imgRef = ref<HTMLImageElement | null>(null)
const imgW = ref(0)
const imgH = ref(0)

/* ========== Magnifier (desktop only) ========== */
const lensVisible = ref(false)
const lensX = ref(0)
const lensY = ref(0)
const lensSize = ref(220)
const magnify = ref(2.2)
const MIN_MAG = 1.5
const MAX_MAG = 5

function updateImgRect() {
  if (!imgRef.value) return
  const r = imgRef.value.getBoundingClientRect()
  imgW.value = r.width
  imgH.value = r.height
}

function onMouseMove(e: MouseEvent) {
  if (!imgRef.value) return
  const r = imgRef.value.getBoundingClientRect()
  let x = e.clientX - r.left
  let y = e.clientY - r.top
  x = Math.max(0, Math.min(x, r.width))
  y = Math.max(0, Math.min(y, r.height))
  lensX.value = x
  lensY.value = y
  lensVisible.value = true
}

function onMouseLeave() { lensVisible.value = false }

/* penting: jangan preventDefault di wheel (hilangkan error passive) */
function onWheel(_e: WheelEvent) {
  const factor = _e.deltaY > 0 ? 0.9 : 1.1
  magnify.value = Math.max(MIN_MAG, Math.min(MAX_MAG, +(magnify.value * factor).toFixed(2)))
  lensVisible.value = true
}

/* ========== Fullscreen pan & zoom ========== */
const showFs = ref(false)
const fsScale = ref(1.2)
const fsMin = 0.6
const fsMax = 6
const fsTx = ref(0)
const fsTy = ref(0)
let drag = { on: false, sx: 0, sy: 0, ox: 0, oy: 0 }

function openFs() {
  showFs.value = true
  fsScale.value = 1.2; fsTx.value = 0; fsTy.value = 0
  document.documentElement.style.overflow = 'hidden'
}
function closeFs() {
  showFs.value = false
  document.documentElement.style.overflow = ''
}

function fsStartDrag(e: MouseEvent | TouchEvent) {
  e.preventDefault()
  let cx: number, cy: number
  if (e instanceof MouseEvent) { cx = e.clientX; cy = e.clientY }
  else {
    if (e.touches.length > 1) return
    cx = e.touches[0].clientX; cy = e.touches[0].clientY
  }
  drag.on = true; drag.sx = cx; drag.sy = cy; drag.ox = fsTx.value; drag.oy = fsTy.value
}
function fsDrag(e: MouseEvent | TouchEvent) {
  if (!drag.on) return
  e.preventDefault()
  let cx: number, cy: number
  if (e instanceof MouseEvent) { cx = e.clientX; cy = e.clientY }
  else {
    if (e.touches.length > 1) return
    cx = e.touches[0].clientX; cy = e.touches[0].clientY
  }
  fsTx.value = drag.ox + (cx - drag.sx)
  fsTy.value = drag.oy + (cy - drag.sy)
}
function fsEndDrag() { drag.on = false }

function fsWheel(e: WheelEvent) {
  e.preventDefault()
  const rect = (e.currentTarget as HTMLElement).getBoundingClientRect()
  const cx = e.clientX - rect.left
  const cy = e.clientY - rect.top
  const prev = fsScale.value
  const next = Math.max(fsMin, Math.min(fsMax, prev * (e.deltaY > 0 ? 0.9 : 1.1)))
  if (next === prev) return
  const k = next / prev
  fsTx.value = (fsTx.value - cx) * k + cx
  fsTy.value = (fsTy.value - cy) * k + cy
  fsScale.value = +(next.toFixed(2))
}
function fsReset() { fsScale.value = 1.2; fsTx.value = 0; fsTy.value = 0 }
function fsZoom(dir: 'in' | 'out') {
  const step = dir === 'in' ? 1.1 : 0.9
  fsScale.value = Math.max(fsMin, Math.min(fsMax, +(fsScale.value * step).toFixed(2)))
}

function onImgLoaded() { updateImgRect() }

/* ========== TOUCH drag-to-scroll untuk container (mobile) ========== */
let touchActive = false
let tStartX = 0
let tStartY = 0
let startScrollLeft = 0

function onTouchStart(e: TouchEvent) {
  if (!wrapRef.value) return
  const t = e.touches[0]
  touchActive = true
  tStartX = t.clientX
  tStartY = t.clientY
  startScrollLeft = wrapRef.value.scrollLeft
}

function onTouchMove(e: TouchEvent) {
  if (!touchActive || !wrapRef.value) return
  const t = e.touches[0]
  const dx = t.clientX - tStartX
  const dy = t.clientY - tStartY
  /* jika geser horizontal > vertical, kita handle manual dan blok vertical scroll page */
  if (Math.abs(dx) > Math.abs(dy)) {
    e.preventDefault()
    wrapRef.value.scrollLeft = startScrollLeft - dx
  }
}
function onTouchEnd() { touchActive = false }

onMounted(() => {
  updateImgRect()
  window.addEventListener('resize', updateImgRect)
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
  if (window.innerWidth < 600) lensSize.value = 180
})
onBeforeUnmount(() => {
  window.removeEventListener('resize', updateImgRect)
  window.removeEventListener('scroll', onScroll)
})
</script>

<template>
  <MinimalLayout theme="light">
    <div class="org-page">
      <LandingNavigation />

      <!-- ===== FULL-BLEED BANNER (sama seperti halaman Profil) ===== -->
      <section class="banner-full">
        <picture>
          <!-- Mobile -->
          <source srcset="/profil2.jpg" media="(max-width: 768px)" />
          <!-- Desktop -->
          <img src="/profil.jpg" alt="Banner U-LAB" class="banner-img" fetchpriority="high" decoding="async" />
        </picture>

        <div class="banner-overlay"></div>

        <div class="banner-glass">
          <div class="glass-label">
            <i class="iconify" data-icon="feather:shield"></i>
            UMRO Calibration Laboratory
          </div>
          <h1 class="glass-title">Struktur Organisasi</h1>
        </div>

        <svg class="banner-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
          <path class="wave-fill" d="M0,40 C240,100 480,0 720,40 C960,80 1200,20 1440,60 L1440,120 L0,120 Z" />
        </svg>
      </section>

      <!-- ===== HALAMAN LAMA (tidak diubah) ===== -->
      <section class="org-section">
        <div class="org-header">
          <h1 class="title is-3">Struktur Organisasi</h1>
          <div class="org-actions">
            <button class="button is-dark is-light is-small" @click="openFs">
              <i class="iconify mr-1" data-icon="feather:maximize-2"></i> Fullscreen
            </button>
            <a class="button is-light is-small" :href="IMG_SRC" download>
              <i class="iconify mr-1" data-icon="feather:download"></i> Download SVG
            </a>
            <a class="button is-primary is-small" :href="IMG_SRC" target="_blank" rel="noopener">
              <i class="iconify mr-1" data-icon="feather:external-link"></i> Buka Tab Baru
            </a>
          </div>
        </div>

        <!-- Container yang bisa di-scroll horizontal -->
        <figure ref="wrapRef" class="org-frame" @mousemove="onMouseMove" @mouseleave="onMouseLeave" @wheel="onWheel"
          @touchstart="onTouchStart" @touchmove="onTouchMove" @touchend="onTouchEnd" @touchcancel="onTouchEnd">
          <img ref="imgRef" :src="IMG_SRC" alt="Struktur Organisasi U-LAB" class="org-image" loading="lazy"
            @load="onImgLoaded" />

          <!-- Magnifier (desktop only) -->
          <div v-show="lensVisible" class="magnifier" :style="{
            width: lensSize + 'px',
            height: lensSize + 'px',
            left: (lensX - lensSize / 2) + 'px',
            top: (lensY - lensSize / 2) + 'px',
            backgroundImage: `url(${IMG_SRC})`,
            backgroundRepeat: 'no-repeat',
            backgroundSize: `${(imgW * magnify).toFixed(2)}px ${(imgH * magnify).toFixed(2)}px`,
            backgroundPosition: `${-(lensX * magnify - lensSize / 2).toFixed(2)}px ${-(lensY * magnify - lensSize / 2).toFixed(2)}px`
          }"></div>

          <div class="mag-toolbar">
            <button class="button is-small" @click="magnify = Math.max(MIN_MAG, +(magnify * 0.9).toFixed(2))">−</button>
            <span class="mag-value">{{ magnify.toFixed(2) }}×</span>
            <button class="button is-small" @click="magnify = Math.min(MAX_MAG, +(magnify * 1.1).toFixed(2))">+</button>
          </div>
        </figure>

        <p class="org-hint">
          <span class="is-hidden-touch">Geser mouse / scroll untuk lihat detail. Klik “Fullscreen” untuk
            panorama.</span>
          <span class="is-hidden-desktop">Geser gambar ke kiri/kanan untuk melihat semua bagian. Ketuk “Fullscreen”
            untuk panorama.</span>
        </p>
      </section>

      <LandingFooter />

      <!-- Fullscreen viewer -->
      <transition name="fade">
        <div v-if="showFs" class="fs-backdrop">
          <div class="fs-toolbar">
            <button class="button is-white is-light" @click="fsZoom('out')">−</button>
            <span class="fs-zoom">{{ (fsScale * 100) | 0 }}%</span>
            <button class="button is-white is-light" @click="fsZoom('in')">+</button>
            <button class="button is-white is-light" @click="fsReset">
              <i class="iconify" data-icon="feather:refresh-cw"></i>
            </button>
            <button class="button is-primary" @click="closeFs">
              <i class="iconify" data-icon="feather:x"></i><span class="ml-1">Tutup</span>
            </button>
          </div>
          <div class="fs-stage" @mousedown="fsStartDrag" @mousemove="fsDrag" @mouseup="fsEndDrag"
            @mouseleave="fsEndDrag" @touchstart.prevent="fsStartDrag" @touchmove.prevent="fsDrag" @touchend="fsEndDrag"
            @touchcancel="fsEndDrag" @wheel="fsWheel" @dblclick="fsReset">
            <img :src="IMG_SRC" alt="Struktur Organisasi U-LAB" class="fs-img"
              :style="{ transform: `translate(${fsTx}px, ${fsTy}px) scale(${fsScale})` }" draggable="false" />
          </div>
        </div>
      </transition>
    </div>
  </MinimalLayout>
</template>

<style lang="scss">
/* ===== Page BG ===== */
.org-page {
  background:
    radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .08), transparent 60%),
    linear-gradient(#f8fafc, #f8fafc);
}

/* ===== FULL-BLEED BANNER (copy dari halaman Profil) ===== */
.banner-full {
  position: relative;
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
  overflow: hidden;
  isolation: isolate;
}

.banner-full picture,
.banner-full img {
  display: block;
  width: 100%;
}

.banner-img {
  width: 100%;
  height: clamp(220px, 28vw, 420px);
  object-fit: cover;
  object-position: 50% 50%;
  user-select: none;

  transform: translateY(calc(var(--scrollY, 0) * 0.15px)) scale(1.08);
  animation: kenburns 18s ease-in-out infinite alternate;
}

@keyframes kenburns {
  from {
    transform: translateY(0) scale(1.05);
  }

  to {
    transform: translateY(-10px) scale(1.12);
  }
}

.banner-overlay {
  position: absolute;
  inset: 0;
  background:
    radial-gradient(1200px 580px at 10% -20%, rgba(15, 23, 42, .25), transparent 60%),
    linear-gradient(to bottom, rgba(0, 0, 0, .15), rgba(0, 0, 0, .35));
  mix-blend-mode: multiply;
  pointer-events: none;
}

.banner-glass {
  position: absolute;
  inset: auto 0 24px 0;
  display: grid;
  place-items: center;
  text-align: center;
  gap: 6px;
  padding-inline: 16px;
  top: 58%;
  bottom: auto;
  transform: translateY(-30%);
}

.glass-label {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 6px 12px;
  border-radius: 999px;
  color: #eaf1ff;
  font-weight: 700;
  backdrop-filter: blur(6px);
  background: linear-gradient(90deg, rgba(255, 255, 255, .18), rgba(255, 255, 255, .10));
  border: 1px solid rgba(255, 255, 255, .35);
}

.glass-title {
  color: #fff;
  font-weight: 900;
  font-size: clamp(28px, 5vw, 48px);
  line-height: 1.1;
  text-shadow: 0 10px 40px rgba(0, 0, 0, .35);
}

/* wave diwarnai via CSS agar bisa ikut dark mode */
.banner-wave {
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 100%;
  height: 100px;
}

.wave-fill {
  fill: #f8fafc;
}

/* ===== Section lama ===== */
.org-section {
  padding: 96px 16px 64px;
  max-width: 1400px;
  margin: 0 auto;
}

.org-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.org-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

/* ===== Scrollable frame ===== */
.org-frame {
  position: relative;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  padding: 10px;
  box-shadow: 0 8px 24px rgba(2, 12, 27, .08);

  overflow-x: auto;
  overflow-y: hidden;
  overscroll-behavior-x: contain;
  -webkit-overflow-scrolling: touch;
  touch-action: pan-x;

  -ms-overflow-style: none;
  scrollbar-width: none;
}

.org-frame::-webkit-scrollbar {
  display: none;
}

.org-image {
  display: block;
  width: 100%;
  height: auto;
  min-width: 1000px;
  user-select: none;
  pointer-events: none;
}

.org-hint {
  text-align: center;
  margin-top: 10px;
  font-size: .9rem;
  color: #6b7280;
}

/* Magnifier – hanya desktop */
.magnifier {
  position: absolute;
  border-radius: 50%;
  border: 2px solid rgba(255, 255, 255, .9);
  box-shadow: 0 10px 28px rgba(0, 0, 0, .35), inset 0 0 0 1px rgba(0, 0, 0, .08);
  pointer-events: none;
  will-change: transform, background-position, background-size;
  display: none;
}

.mag-toolbar {
  position: absolute;
  right: 10px;
  bottom: 10px;
  display: none;
  gap: 6px;
  align-items: center;
  padding: 6px 8px;
  background: rgba(255, 255, 255, .85);
  border: 1px solid #e5e7eb;
  border-radius: 10px;
  backdrop-filter: blur(6px);
  z-index: 10;
}

@media (hover: hover) and (pointer: fine) {

  .magnifier,
  .mag-toolbar {
    display: flex;
  }

  .org-frame {
    cursor: crosshair;
  }
}

/* ===== Fullscreen viewer ===== */
.fade-enter-active,
.fade-leave-active {
  transition: opacity .15s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.fs-backdrop {
  position: fixed;
  inset: 0;
  z-index: 9999;
  background: rgba(0, 0, 0, .85);
  display: grid;
  grid-template-rows: auto 1fr;
}

.fs-toolbar {
  display: flex;
  gap: 8px;
  align-items: center;
  padding: 10px;
  background: rgba(17, 24, 39, .75);
  border-bottom: 1px solid rgba(255, 255, 255, .08);
  color: #e5eefb;
}

.fs-toolbar .fs-zoom {
  width: 60px;
  text-align: center;
  font-weight: 800;
}

.fs-stage {
  position: relative;
  overflow: hidden;
  cursor: grab;
  touch-action: none;
}

.fs-stage:active {
  cursor: grabbing;
}

.fs-img {
  position: absolute;
  top: 50%;
  left: 50%;
  transform-origin: 0 0;
  translate: -50% -50%;
  will-change: transform;
  user-select: none;
  pointer-events: none;
  max-width: none;
}

/* ===== Dark mode ===== */
.is-dark {
  .org-page {
    background:
      radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .14), transparent 60%),
      linear-gradient(#0b1220, #0b1220);
  }

  .wave-fill {
    fill: #0b1220;
  }

  .banner-overlay {
    background: linear-gradient(to bottom, rgba(0, 0, 0, .35), rgba(0, 0, 0, .65));
  }

  .org-frame {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
    box-shadow: 0 14px 30px rgba(0, 0, 0, .45);
  }

  .org-hint {
    color: #9aa7c7;
  }

  .mag-toolbar {
    background: rgba(17, 24, 39, .75);
    border-color: rgba(255, 255, 255, .12);
    color: #e5eefb;
  }
}

/* ===== Mobile tweaks (samakan dengan Profil) ===== */
@media (max-width: 768px) {
  .banner-img {
    height: min(52vh, 420px);
    animation: none;
    transform: none;
    object-position: 50% 50%;
  }

  .banner-glass {
    top: auto;
    bottom: 78px;
    transform: none;
    padding-inline: 12px;
    z-index: 2;
  }

  .banner-wave {
    height: 70px;
    z-index: 1;
  }

  .org-section {
    padding-top: 84px;
  }

  .org-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .org-image {
    min-width: 800px;
  }

  .is-hidden-touch {
    display: none !important;
  }

  .is-hidden-desktop {
    display: block !important;
  }
}

@media (max-width: 420px) {
  .banner-img {
    height: 56vh;
  }

  .banner-glass {
    bottom: 92px;
  }
}

@media (min-width: 769px) {
  .is-hidden-desktop {
    display: none !important;
  }
}
</style>
