<template>
  <!-- Layer background: hanya gambar, diblur -->
  <div class="dashboard-background-layer"></div>

  <!-- WELCOME BAR PALING ATAS, centered -->
  <div class="dashboard-welcome-bar iris-dashboard-hero">
    <div class="welcome-inner">
      <h3>
        <i class="fas fa-home"></i>
        Dashboard Mutu
      </h3>
      <span>
        Selamat Datang, <b>{{ userLogin.pegawai.namaLengkap }}</b>
      </span>
    </div>
  </div>

  <!-- Konten dashboard -->
  <div class="dashboard-content">
    <div class="floating-center-wrapper" @mouseenter="showMenu = true" @mouseleave="showMenu = false">
      <!-- Pita/Tulisan di tengah-tengah globe -->
      <div class="ribbon-banner-center">
        <span class="ribbon-text-center">ISO 17025 : 2017</span>
      </div>
      <div class="main-globe" ref="globeContainer"></div>
      <div class="globe-shadow"></div>

      <div v-if="showMenu" class="floating-menu">
        <div class="menu-wrapper" :style="circlePos(270)">
          <div class="menu-circle" @click="KendaliDokumen()">
            <i class="iconify menu-iconify menu-icon-doc" data-icon="mdi:file-document-multiple-outline"></i>
          </div>
          <div class="menu-label">Nomor Induk Dokumen</div>
        </div>

        <div class="menu-wrapper" :style="circlePos(330)">
          <div class="menu-circle" @click="UlabDigitalStorage()">
            <i class="iconify menu-iconify menu-icon-uds" data-icon="mdi:folder-multiple-outline"></i>
          </div>
          <div class="menu-label">ULAB Digital Storage</div>
        </div>

        <div class="menu-wrapper" :style="circlePos(210)">
          <div class="menu-circle" @click="DokumenSurveilan()">
            <i class="iconify menu-iconify menu-icon-surveilan" data-icon="mdi:file-search-outline"></i>
          </div>
          <div class="menu-label">Dokumen Surveilan</div>
        </div>

        <div class="menu-wrapper" :style="circlePos(90)">
          <div class="menu-circle" @click="DokumenAuditInternal()">
            <i class="iconify menu-iconify menu-icon-doc" data-icon="mdi:folder-check-outline"></i>
          </div>
          <div class="menu-label">Dokumen Audit Internal</div>
        </div>

        <div class="menu-wrapper" :style="circlePos(30)">
          <div class="menu-circle" @click="PenilaianPelanggan()">📝</div>
          <div class="menu-label">Penilaian Pelanggan</div>
        </div>

        <div class="menu-wrapper" :style="circlePos(150)">
          <div class="menu-circle" @click="pelatihan()">🎓</div>
          <div class="menu-label">Pelatihan</div>
        </div>
      </div>
    </div>
  </div>

  <WelcomeBannerModalV v-model="openWelcome" :images="[
    '/welcome1.png',
    '/welcome2.png'
  ]" title="Seputar Layanan" subtitle="Info terbaru sebelum menggunakan web"
    storage-key="ulab_welcome_banner_customer_v1" />
</template>

<script setup lang="ts">
import { ref, onMounted } from 'vue'
import * as THREE from 'three'
import { useRouter } from 'vue-router'
import { useHead } from '@vueuse/head'
import { useUserSession } from '/@src/stores/userSession'
import WelcomeBannerModalV from '/@src/components/WelcomeBannerModalV.vue'

useHead({ title: 'Dashboard Mutu - ' + import.meta.env.VITE_PROJECT })

const userLogin = useUserSession().getUser()
const router = useRouter()
const showMenu = ref(false)
const globeContainer = ref<HTMLDivElement | null>(null)
const openWelcome = ref(false)

const goTo = (routeName: string) => {
  router.push({ name: routeName })
}

const KendaliDokumen = () => {
  router.push({ name: 'module-mutu-daftar-induk-dokumen' })
}

const UlabDigitalStorage = () => {
  router.push({ name: 'module-uds-ulab-digital-storage' })
}

const DokumenSurveilan = () => {
  router.push({ name: 'module-mutu-dokumen-surveilan' })
}

const DokumenAuditInternal = () => {
  router.push({ name: 'module-mutu-dokumen-audit-internal' })
}

const PenilaianPelanggan = () => {
  router.push({ name: 'module-mutu-penilaian-pelanggan' })
}

const pelatihan = () => {
  router.push({ name: 'module-pelatihan-pelatihan' })
}

onMounted(() => {
  const key = 'ulab_welcome_banner_customer_v1'
  if (localStorage.getItem(key) !== '1') {
    openWelcome.value = true
  }

  const container = globeContainer.value
  if (!container) return

  const scene = new THREE.Scene()
  const camera = new THREE.PerspectiveCamera(75, 1, 0.1, 1000)
  const renderer = new THREE.WebGLRenderer({ alpha: true, antialias: true })
  renderer.setSize(320, 320)
  container.appendChild(renderer.domElement)

  const light = new THREE.AmbientLight(0xffffff, 1)
  scene.add(light)

  // Globe Utama
  const globeTexture = new THREE.TextureLoader().load('/UMRO.png')
  globeTexture.wrapS = THREE.RepeatWrapping
  globeTexture.wrapT = THREE.RepeatWrapping
  globeTexture.repeat.set(6.5, 6.5)
  globeTexture.offset.set(2.25, 2.25)

  const globeMaterial = new THREE.MeshStandardMaterial({
    map: globeTexture,
    metalness: 0.2,
    roughness: 0.7
  })
  const globeGeometry = new THREE.SphereGeometry(3.5, 64, 64)
  const globe = new THREE.Mesh(globeGeometry, globeMaterial)
  scene.add(globe)

  camera.position.z = 6

  const animate = () => {
    requestAnimationFrame(animate)
    globe.rotation.y += 0.002
    renderer.render(scene, camera)
  }
  animate()
})

const circlePos = (deg: number) => {
  const r = 250
  const rad = (deg * Math.PI) / 180
  const x = Math.cos(rad) * r
  const y = Math.sin(rad) * r
  return { left: `calc(50% + ${x}px)`, top: `calc(50% + ${y}px)` }
}
</script>

<style scoped lang="scss">
.dashboard-background-layer {
  margin-top: 70px;
  position: fixed;
  inset: 0;
  z-index: 1;
  width: 100vw;
  height: 100vh;
  background: url('/@src/assets/illustrations/landing/bgmutu.png') center center no-repeat;
  background-size: cover;
  filter: blur(4px);
  pointer-events: none;
}

/* WELCOME BAR PALING ATAS */
.dashboard-welcome-bar {
  position: fixed;
  top: 90px;
  left: 0;
  width: 50vw;
  z-index: 30;
  display: flex;
  justify-content: center;
  pointer-events: none;
}

.welcome-inner {
  background: linear-gradient(90deg, #0b2946 70%, #3792e7 100%);
  box-shadow: 0 6px 24px 0 rgba(45, 55, 90, 0.16);
  border-radius: 30px;
  padding: 16px 50px 14px 36px;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 3px;
  font-size: 1.2rem;
  font-weight: 600;
  color: #fff;
  letter-spacing: 1px;
  min-width: 330px;
  max-width: 480px;
  pointer-events: auto;
  border: 2px solid #ffe48c;
  text-shadow: 0 2px 12px #00244e88;
  box-sizing: border-box;
}

.welcome-inner h3 {
  margin: 0 0 4px 0;
  font-size: 1.4em;
  font-weight: 700;
  color: #ffe48c;
  letter-spacing: 1px;
}

.welcome-inner span {
  font-size: 1.18em;
}

.welcome-inner b {
  color: #fffde0;
  font-weight: 800;
}

/* SEMBUNYIKAN BAR DI MOBILE */
@media (max-width: 600px) {
  .dashboard-welcome-bar {
    display: none !important;
  }

  .dashboard-content {
    padding-top: 12px;
  }
}

/* Globe dan floating menu */
.dashboard-content {
  position: relative;
  z-index: 10;
  min-height: 100vh;
}

.floating-center-wrapper {
  position: fixed;
  top: 60%;
  left: 50%;
  width: 400px;
  height: 400px;
  transform: translate(-50%, -50%);
  z-index: 10;
  pointer-events: auto;
}

.main-globe {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 320px;
  height: 320px;
  transform: translate(-50%, -50%);
  border-radius: 50%;
  overflow: hidden;
  z-index: 3;
}

.globe-shadow {
  position: absolute;
  top: calc(50% + 170px);
  left: 50%;
  transform: translate(-50%, 0);
  width: 180px;
  height: 30px;
  background: radial-gradient(ellipse at center, rgba(0, 0, 0, 0.3), transparent);
  filter: blur(10px);
  z-index: 2;
  pointer-events: none;
}

/* Ribbon/Pita di tengah globe */
.ribbon-banner-center {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 340px;
  height: 50px;
  transform: translate(-50%, -50%);
  display: flex;
  align-items: center;
  justify-content: center;
  pointer-events: none;
  z-index: 5;
}

.ribbon-text-center {
  font-size: 25px;
  font-weight: bold;
  color: #fff;
  letter-spacing: 2px;
  padding: 6px 50px;
  background: linear-gradient(90deg, #003366 80%, #3399ff 100%);
  border-radius: 80px 80px 80px 80px / 50px 50px 50px 50px;
  box-shadow: 0 2px 12px rgba(30, 50, 100, 0.18);
  opacity: 0.82;
  transform: perspective(100px) rotateX(18deg);
  border-top: 3px solid #ffe48c;
  border-bottom: 3px solid #ffe48c;
  text-shadow: 1px 2px 6px #00244e;
}

.floating-menu {
  position: absolute;
  width: 100%;
  height: 100%;
  top: 0;
  left: 0;
  z-index: 4;
}

.menu-wrapper {
  position: absolute;
  transform: translate(-50%, -50%);
  display: flex;
  flex-direction: column;
  align-items: center;
  pointer-events: auto;
}

.menu-circle {
  width: 100px;
  height: 100px;
  background-color: #ffffff;
  border-radius: 50%;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 30px;
  font-weight: bold;
  cursor: pointer;
  transition: all 0.3s ease-in-out;
}

.menu-circle:hover {
  background-color: #f0f0f0;
}

.menu-iconify {
  font-size: 42px;
  line-height: 1;
}

.menu-icon-doc {
  color: #8b5cf6;
}

.menu-icon-uds {
  color: #2563eb;
}

.menu-icon-surveilan {
  color: #0f766e;
}

.menu-label {
  margin-top: 8px;
  font-size: 12px;
  font-weight: bold;
  color: #000000;
  text-align: center;
  max-width: 120px;
}
</style>
