<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useHead } from '@vueuse/head'
import L from 'leaflet'
import 'leaflet/dist/leaflet.css'
import Lenis from 'lenis'
import 'lenis/dist/lenis.css'
import { useApi } from '/@src/composable/useApi'
import { useDarkmode } from '/@src/stores/darkmode'
import LandingFooter from '/@src/components/partials/landing/LandingFooter.vue'
import LandingNavigation from '/@src/components/partials/landing/LandingNavigation.vue'

const darkmode = useDarkmode()
const androidAppUrl = 'https://play.google.com/store/apps/details?id=id.ulabumro.mobile&pcampaignid=web_share'
const cookieConsentKey = 'ulab-cookie-consent-v1'
const cookieBannerVisible = ref(false)

type CookieConsentChoice = 'accepted' | 'rejected'
type LandingStatItem = {
  key: string
  label: string
  value: number
}

type LandingServiceStats = {
  year: number
  period_start: string
  period_end: string
  generated_at?: string
  stats: {
    unit_dilayani: number
    jumlah_kalibrasi: number
    jumlah_repair: number
    total_layanan: number
  }
  items: LandingStatItem[]
}

const currentYear = new Date().getFullYear()
const defaultLandingStats = (): LandingServiceStats => ({
  year: currentYear,
  period_start: `${currentYear}-01-01`,
  period_end: new Date().toISOString().slice(0, 10),
  stats: {
    unit_dilayani: 0,
    jumlah_kalibrasi: 0,
    jumlah_repair: 0,
    total_layanan: 0,
  },
  items: [
    { key: 'unit_dilayani', label: 'Unit Dilayani', value: 0 },
    { key: 'jumlah_kalibrasi', label: 'Kalibrasi', value: 0 },
    { key: 'jumlah_repair', label: 'Repair', value: 0 },
    { key: 'total_layanan', label: 'Total Layanan', value: 0 },
  ],
})

const landingServiceStats = ref<LandingServiceStats>(defaultLandingStats())

const setCookieConsent = (choice: CookieConsentChoice) => {
  try {
    localStorage.setItem(cookieConsentKey, choice)
  } catch (e) {
    // localStorage can be blocked in private browsing; hide for this session.
  }

  cookieBannerVisible.value = false
}

const heroSlides = [
  {
    image: '/BG1.webp',
    position: '65% center',
    title: 'ULAB CEPAT, TEPAT, AKURAT',
    subtitle: 'Layanan kalibrasi, testing, dan repair terintegrasi untuk akurasi maksimal dan efisiensi operasional.',
  },
  {
    image: '/BG2.webp',
    position: '58% center',
    title: 'STANDAR INTERNASIONAL UNTUK OPERASI ANDAL',
    subtitle: 'Dukungan laboratorium ISO/IEC 17025:2017 dengan proses teknis yang tertelusur dan terdokumentasi.',
  },
]

const heroHighlights = [
  { icon: 'fas fa-bullseye', label: 'Hasil Akurat', desc: 'Sangat Andal' },
  { icon: 'fas fa-plane', label: 'Standar SNI &', desc: 'Internasional' },
  { icon: 'fas fa-clock', label: 'Terpercaya &', desc: 'Berpengalaman' },
]

const heroSolution = {
  title: 'One-Stop Service Solution',
  desc: 'Kalibrasi terintegrasi dengan perbaikan - hemat waktu & biaya.',
}
 
const currentSlide = ref(0)
let heroTimer: ReturnType<typeof window.setInterval> | null = null

const nextSlide = () => {
  currentSlide.value = (currentSlide.value + 1) % heroSlides.length
}

const prevSlide = () => {
  currentSlide.value = (currentSlide.value - 1 + heroSlides.length) % heroSlides.length
}

const selectSlide = (index: number) => {
  currentSlide.value = index
}

const accreditationPoints = [
  'Memenuhi persyaratan ISO/IEC 17025:2017',
  'Ruang lingkup terakreditasi sesuai kebutuhan industri',
  'Proses kalibrasi yang andal, telusur, dan konsisten',
  'Diawasi secara berkala oleh Komite Akreditasi Nasional (KAN)',
]

const commonServiceScopes = [
  { icon: '/landing-icons/electricity.svg', label: 'Kelistrikan' },
  { icon: '/landing-icons/pressure.svg', label: 'Tekanan' },
  { icon: '/landing-icons/temperature.svg', label: 'Suhu' },
  { icon: '/landing-icons/vibration.svg', label: 'Vibrasi' },
  { icon: '/landing-icons/repair.svg', label: 'Repair' },
]

const gresikServiceScopes = [
  ...commonServiceScopes.slice(0, 4),
  { icon: '/landing-icons/dimension.svg', label: 'Dimensi' },
  commonServiceScopes[4],
]

const locations = [
  {
    number: '1',
    city: 'ULAB Jakarta',
    landmark: 'Jakarta Utara',
    landmarkIcon: '/landing-icons/monas.svg',
    address: 'Jl. Pluit Utara Raya No. 2B, Komplek PLTU Muara Karang, Jakarta Utara, DKI Jakarta 14450',
    focus: 'Kalibrasi, Vibrasi, Repair & Layanan On Site',
    services: commonServiceScopes,
    photo: '/BG1.webp',
    coordinates: [-6.111229468899915, 106.78263452408453] as [number, number],
  },
  {
    number: '2',
    city: 'ULAB Gresik',
    landmark: 'Kabupaten Gresik',
    landmarkIcon: '/landing-icons/gresik-industry.svg',
    address: 'Jl. Harun Tohir No. 1, Singosari, Pulopancikan, Kec. Gresik, Kabupaten Gresik, Jawa Timur 61111',
    focus: 'Kalibrasi, Vibrasi, Dimensi, Repair & Layanan On Site',
    services: gresikServiceScopes,
    photo: '/BG2.webp',
    coordinates: [-7.160993779776026, 112.65988143834625] as [number, number],
  },
]

const processSteps = [
  {
    icon: '/landing-icons/process-register.svg',
    title: 'Pendaftaran',
    desc: 'Pelanggan mendaftar melalui website atau kontak layanan kami.',
  },
  {
    icon: '/landing-icons/process-review.svg',
    title: 'Kaji Ulang',
    desc: 'Tim kami melakukan kaji ulang kebutuhan dan ruang lingkup.',
  },
  {
    icon: '/landing-icons/process-measure.svg',
    title: 'Pengukuran & Evaluasi',
    desc: 'Peralatan diuji, diukur, dan dievaluasi sesuai standar berlaku.',
  },
  {
    icon: '/landing-icons/process-approve.svg',
    title: 'Pengesahan',
    desc: 'Hasil layanan diverifikasi dan disahkan dalam sertifikat kalibrasi.',
  },
  {
    icon: '/landing-icons/process-survey.svg',
    title: 'Survey Kepuasan',
    desc: 'Masukan pelanggan membantu kami meningkatkan layanan.',
  },
]

const customerLogos = [
  { name: 'PLN', image: '/customer-logos/pln.webp', className: 'is-wide' },
  { name: 'PLN Indonesia Power', image: '/customer-logos/pln-indonesia-power.webp', className: 'is-wide' },
  { name: 'PLN Nusantara Power Services', image: '/customer-logos/pln-nusantara-power-services.webp', className: 'is-extra-wide' },
  { name: 'PT KPJB', image: '/customer-logos/kpjb.webp', className: 'is-emblem' },
  { name: 'Adaro Energy', image: '/customer-logos/adaro-andalan.webp', className: 'is-wide' },
  { name: 'GAE', image: '/customer-logos/gae.webp', className: 'is-compact' },
  { name: 'Navigat Energy', image: '/customer-logos/navigat-energy.webp', className: 'is-icon' },
  { name: 'PT Rekajasa Asyatama', image: '/customer-logos/rekajasa-asyatama.webp', className: 'is-rekajasa' },
  { name: 'GMF AeroAsia', image: '/customer-logos/gmf-aeroasia.webp', className: 'is-wide' },
  { name: 'Mayora Group', image: '/customer-logos/mayora.webp', className: 'is-wide' },
  { name: 'DKI Jakarta', image: '/customer-logos/dki-jakarta.webp', className: 'is-icon' },
  { name: 'Qualis', image: '/customer-logos/qualis-color.webp', className: 'is-qualis' },
  { name: 'G-Energy', image: '/customer-logos/g-energy.webp', className: 'is-extra-wide' },
]

const customerMarqueeItems = computed(() => [...customerLogos, ...customerLogos])
const landingStatsItems = computed(() => {
  return landingServiceStats.value.items?.length
    ? landingServiceStats.value.items
    : defaultLandingStats().items
})

const landingStatsPeriod = computed(() => {
  const year = landingServiceStats.value.year || currentYear

  return `Data tahun berjalan ${year}`
})

const formatLandingNumber = (value: number) => {
  return new Intl.NumberFormat('id-ID').format(Number(value || 0))
}

const loadLandingServiceStats = async () => {
  try {
    const response = await useApi().get('/get-landing-service-stats')
    if (response?.items) {
      landingServiceStats.value = response
    }
  } catch (e) {
    landingServiceStats.value = defaultLandingStats()
  }
}

const labVideos = [
  {
    id: 'ZvrpvVTSNf8',
    title: 'Room Tour U-LAB',
    desc: 'Jelajahi fasilitas laboratorium dan alur pelayanan utama U-LAB.',
    thumb: 'https://i.ytimg.com/vi/ZvrpvVTSNf8/hqdefault.jpg',
  },
  {
    id: 'Zv1W1TprONw',
    title: 'Kalibrasi Tekanan Pneumatic',
    desc: 'Proses kalibrasi tekanan pneumatic dengan standar tertelusur.',
    thumb: 'https://i.ytimg.com/vi/Zv1W1TprONw/hqdefault.jpg',
  },
  {
    id: 'RT2zawGoezU',
    title: 'Kalibrasi Tekanan Vakum',
    desc: 'Pengujian tekanan vakum untuk kebutuhan operasional industri.',
    thumb: 'https://i.ytimg.com/vi/RT2zawGoezU/hqdefault.jpg',
  },
  {
    id: 'Zf18ogzhxdA',
    title: 'Kalibrasi Transmille 3010A',
    desc: 'Dokumentasi kalibrasi instrumen presisi di lingkungan laboratorium.',
    thumb: 'https://i.ytimg.com/vi/Zf18ogzhxdA/hqdefault.jpg',
  },
]

const activeVideoIndex = ref(0)
const videoIsPlaying = ref(false)
const activeVideo = computed(() => labVideos[activeVideoIndex.value] ?? labVideos[0])
const activeVideoEmbedUrl = computed(() => {
  const id = activeVideo.value.id
  const autoplay = videoIsPlaying.value ? 1 : 0

  return `https://www.youtube.com/embed/${id}?autoplay=${autoplay}&mute=1&playsinline=1&controls=1&rel=0&modestbranding=1&playlist=${id}`
})

const playActiveVideo = () => {
  videoIsPlaying.value = true
}

watch(activeVideoIndex, () => {
  videoIsPlaying.value = false
})

const activeProcessStep = ref(-1)
const processSectionRef = ref<HTMLElement | null>(null)
const serviceMapRef = ref<L.Map | null>(null)
const serviceMapTileRef = ref<L.TileLayer | null>(null)
const serviceMapBounds = ref<L.LatLngBounds | null>(null)
const serviceMapMarkers = new Map<string, L.Marker>()
let revealObserver: IntersectionObserver | null = null
let lenis: Lenis | null = null

const processLineWidth = computed(() => {
  if (activeProcessStep.value < 0) return '0%'
  if (processSteps.length <= 1) return '100%'

  return `${(activeProcessStep.value / (processSteps.length - 1)) * 100}%`
})

const updateProcessProgress = () => {
  const section = processSectionRef.value
  if (!section) return

  const rect = section.getBoundingClientRect()
  const viewport = window.innerHeight || 1
  const start = viewport * 0.86
  const end = viewport * 0.18
  const travel = Math.max(1, start - end)
  const raw = (start - rect.top) / travel
  const progress = Math.max(0, Math.min(1, raw))

  if (progress <= 0.02) {
    activeProcessStep.value = -1
    return
  }

  activeProcessStep.value = Math.min(
    processSteps.length - 1,
    Math.max(0, Math.ceil(progress * processSteps.length) - 1)
  )
}

const startHero = () => {
  if (heroTimer) window.clearInterval(heroTimer)
  heroTimer = window.setInterval(nextSlide, 6500)
}

const buildServiceTile = (isDark: boolean) => {
  const url = isDark
    ? 'https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
    : 'https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png'

  return L.tileLayer(url, {
    subdomains: 'abcd',
    maxZoom: 18,
    minZoom: 4,
    updateWhenIdle: true,
    attribution: '© OpenStreetMap © CARTO',
  })
}

const buildServiceMapIcon = (number: string) => L.divIcon({
  className: 'service-map-marker',
  html: `<span><b>${number}</b></span>`,
  iconSize: [34, 42],
  iconAnchor: [17, 36],
  popupAnchor: [0, -34],
  tooltipAnchor: [0, -34],
})

const applyServiceMapTiles = () => {
  const map = serviceMapRef.value
  if (!map) return

  if (serviceMapTileRef.value) {
    serviceMapTileRef.value.remove()
  }

  serviceMapTileRef.value = buildServiceTile(darkmode.isDark).addTo(map)
}

const fitServiceMap = () => {
  const map = serviceMapRef.value
  const bounds = serviceMapBounds.value
  if (!map || !bounds?.isValid()) return

  map.fitBounds(bounds, {
    padding: [54, 54],
    maxZoom: 6,
  })
}

const focusServiceLocation = (location: typeof locations[number]) => {
  const map = serviceMapRef.value
  if (!map) return

  map.flyTo(location.coordinates, 9, {
    animate: true,
    duration: 1.15,
  })
  serviceMapMarkers.get(location.city)?.openPopup()
}

const initServiceMap = () => {
  if (serviceMapRef.value) return

  const el = document.getElementById('service-coordinate-map')
  if (!el) return

  const map = L.map(el, {
    zoomControl: true,
    scrollWheelZoom: false,
    attributionControl: false,
    preferCanvas: true,
  })

  serviceMapRef.value = map
  applyServiceMapTiles()
  serviceMapMarkers.clear()

  const bounds = L.latLngBounds([])
  const markerLayer = L.layerGroup().addTo(map)

  const routeHalo = L.polyline(
    locations.map((location) => location.coordinates),
    {
      color: '#ffffff',
      lineCap: 'round',
      opacity: 0.84,
      weight: 9,
    }
  )

  const route = L.polyline(
    locations.map((location) => location.coordinates),
    {
      color: '#064bb8',
      dashArray: '10 10',
      lineCap: 'round',
      opacity: 0.86,
      weight: 4,
    }
  )

  markerLayer.addLayer(routeHalo)
  markerLayer.addLayer(route)

  locations.forEach((location) => {
    const [lat, lng] = location.coordinates
    const marker = L.marker(location.coordinates, { icon: buildServiceMapIcon(location.number) })
      .bindTooltip(location.city.replace('ULAB ', ''), {
        permanent: true,
        direction: 'top',
        offset: [0, -34],
        className: 'service-map-tooltip',
      })
      .bindPopup(`
        <strong>${location.city}</strong>
        <span>${location.address}</span>
        <em>${lat.toFixed(5)}, ${lng.toFixed(5)}</em>
      `)

    markerLayer.addLayer(marker)
    serviceMapMarkers.set(location.city, marker)
    bounds.extend(location.coordinates)
  })

  serviceMapBounds.value = bounds
  fitServiceMap()

  window.setTimeout(() => map.invalidateSize(), 160)
}

onMounted(() => {
  try {
    cookieBannerVisible.value = !localStorage.getItem(cookieConsentKey)
  } catch (e) {
    cookieBannerVisible.value = true
  }

  startHero()
  loadLandingServiceStats()
  initServiceMap()

  if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    lenis = new Lenis({
      autoRaf: true,
      duration: 1.15,
      smoothWheel: true,
      syncTouch: false,
      wheelMultiplier: 0.9,
      anchors: true,
      easing: (t: number) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
    })
  }

  updateProcessProgress()
  window.addEventListener('scroll', updateProcessProgress, { passive: true })
  window.addEventListener('resize', updateProcessProgress, { passive: true })

  if ('IntersectionObserver' in window) {
    revealObserver = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) {
            ;(entry.target as HTMLElement).classList.add('is-visible')
            revealObserver?.unobserve(entry.target)
          }
        })
      },
      {
        threshold: 0.16,
        rootMargin: '0px 0px -12% 0px',
      }
    )

    document.querySelectorAll('.ulab-landing .reveal').forEach((el) => revealObserver?.observe(el))
  } else {
    document.querySelectorAll('.ulab-landing .reveal').forEach((el) => el.classList.add('is-visible'))
  }
})

onBeforeUnmount(() => {
  if (heroTimer) window.clearInterval(heroTimer)
  heroTimer = null
  window.removeEventListener('scroll', updateProcessProgress)
  window.removeEventListener('resize', updateProcessProgress)
  revealObserver?.disconnect()
  revealObserver = null
  lenis?.destroy()
  lenis = null
  serviceMapRef.value?.remove()
  serviceMapRef.value = null
  serviceMapTileRef.value = null
  serviceMapBounds.value = null
  serviceMapMarkers.clear()
})

watch(
  () => darkmode.isDark,
  () => {
    applyServiceMapTiles()
  }
)

useHead({
  title: 'ULAB | Calibration, Testing & Repair',
  link: [
    { rel: 'preload', as: 'image', href: '/BG1.webp' },
    { rel: 'preload', as: 'image', href: '/BG2.webp' },
    { rel: 'preload', as: 'image', href: '/ulabmobile.webp' },
    { rel: 'preconnect', href: 'https://www.youtube.com' },
    { rel: 'preconnect', href: 'https://i.ytimg.com' },
  ],
})
</script>

<template>
  <MinimalLayout :theme="darkmode.isDark ? 'dark' : 'light'">
    <LandingNavigation />

    <nav class="mobile-auth-nav" aria-label="Akses masuk dan daftar">
      <RouterLink :to="{ name: 'auth-login' }" class="mobile-auth-link is-login">
        <i aria-hidden="true" class="iconify" data-icon="feather:log-in"></i>
        <span>Masuk</span>
      </RouterLink>
      <RouterLink :to="{ name: 'auth-signup-1' }" class="mobile-auth-link is-register">
        <i aria-hidden="true" class="iconify" data-icon="feather:user-plus"></i>
        <span>Daftar</span>
      </RouterLink>
    </nav>

    <main class="ulab-landing">
      <section class="hero-showcase" aria-label="ULAB highlight">
        <div
          v-for="(slide, index) in heroSlides"
          :key="slide.image"
          class="hero-slide"
          :class="{ 'is-active': index === currentSlide }"
        >
          <img
            class="landing-hero-image"
            :src="slide.image"
            :alt="slide.title"
            :style="{ objectPosition: slide.position }"
            :loading="index === 0 ? 'eager' : 'lazy'"
            :fetchpriority="index === 0 ? 'high' : 'low'"
            decoding="async"
          />
        </div>

        <div class="hero-shade" aria-hidden="true"></div>

        <div class="hero-content container">
          <div class="hero-copy reveal is-visible">
            <span class="hero-eyebrow">Calibration, Testing & Repair</span>
            <h1>{{ heroSlides[currentSlide].title }}</h1>
            <p>{{ heroSlides[currentSlide].subtitle }}</p>

            <Transition name="hero-feature" mode="out-in">
              <div
                v-if="currentSlide === 0"
                key="solution"
                class="hero-service-statement"
                aria-label="Solusi layanan U-LAB"
              >
                <strong>{{ heroSolution.title }}</strong>
                <span>{{ heroSolution.desc }}</span>
              </div>

              <div v-else key="badges" class="hero-badges" aria-label="Keunggulan U-LAB">
                <div v-for="item in heroHighlights" :key="item.label" class="hero-badge">
                  <i :class="item.icon" aria-hidden="true"></i>
                  <span>
                    <strong>{{ item.label }}</strong>
                    {{ item.desc }}
                  </span>
                </div>
              </div>
            </Transition>

            <div class="hero-actions">
              <RouterLink :to="{ name: 'auth-login' }" class="btn-main">
                Registrasi Kalibrasi
                <i aria-hidden="true" class="fas fa-arrow-right"></i>
              </RouterLink>
              <RouterLink :to="{ name: 'layanan' }" class="btn-plain">
                Lihat Layanan
                <i aria-hidden="true" class="fas fa-arrow-right"></i>
              </RouterLink>
            </div>
          </div>
        </div>

        <button class="hero-nav is-prev" type="button" aria-label="Slide sebelumnya" @click="prevSlide">
          <i aria-hidden="true" class="fas fa-chevron-left"></i>
        </button>
        <button class="hero-nav is-next" type="button" aria-label="Slide berikutnya" @click="nextSlide">
          <i aria-hidden="true" class="fas fa-chevron-right"></i>
        </button>

        <div class="hero-dots" aria-label="Pilih slide">
          <button
            v-for="(_, index) in heroSlides"
            :key="index"
            type="button"
            :class="{ 'is-active': index === currentSlide }"
            :aria-label="`Tampilkan slide ${index + 1}`"
            @click="selectSlide(index)"
          ></button>
        </div>
      </section>

      <section class="standard-strip" aria-label="Standar akreditasi">
        <div class="standard-card reveal is-visible">
          <div class="partner-logos" aria-label="Mitra dan holding">
            <span class="partner-logo is-danantara">
              <img src="/danantara.png" alt="Danantara Indonesia" loading="eager" decoding="async" />
            </span>
            <span class="partner-logo is-pln">
              <img src="/pln.png" alt="PLN Nusantara Power" loading="eager" decoding="async" />
            </span>
          </div>

          <div class="standard-item">
            <span class="standard-logo is-iso">
              <img src="/ISO.jpg" alt="ISO 17025 Certified" loading="eager" decoding="async" />
            </span>
            <div>
              <strong>ISO/IEC 17025</strong>
              <span>Standar Laboratorium</span>
            </div>
          </div>
          <div class="standard-divider"></div>
          <div class="standard-item">
            <span class="standard-logo is-kan">
              <img src="/KAN.png" alt="Komite Akreditasi Nasional LK-284-IDN" loading="eager" decoding="async" />
            </span>
            <div>
              <strong>LK-284-IDN</strong>
              <span>Akreditasi KAN</span>
            </div>
          </div>
        </div>
      </section>

      <section id="akreditasi" class="section-block accreditation-section">
        <div class="container accreditation-grid">
          <div class="section-copy reveal">
            <span class="section-kicker">Sertifikat Akreditasi</span>
            <h2>Kompetensi Terakreditasi, Kredibilitas Terjamin</h2>
            <p>
              ULAB telah terakreditasi oleh Komite Akreditasi Nasional (KAN) sesuai standar ISO/IEC 17025:2017
              sebagai laboratorium kalibrasi.
            </p>

            <ul class="check-list">
              <li v-for="point in accreditationPoints" :key="point">
                <i aria-hidden="true" class="fas fa-check"></i>
                <span>{{ point }}</span>
              </li>
            </ul>

            <RouterLink :to="{ name: 'akreditasi' }" class="outline-action">
              Lihat Detail Sertifikat
              <i aria-hidden="true" class="fas fa-external-link-alt"></i>
            </RouterLink>
          </div>

          <div class="certificate-showcase reveal">
            <figure class="certificate-frame">
              <img src="/LK284.jpg" alt="Sertifikat Akreditasi LK-284-IDN" loading="eager" decoding="async" fetchpriority="high" />
            </figure>
            <div class="certificate-base" aria-hidden="true"></div>
          </div>
        </div>
      </section>

      <section id="lokasi" class="section-block location-section">
        <div class="container">
          <div class="section-heading reveal">
            <span class="section-kicker">Lokasi Strategis</span>
            <h2>Layanan Tersedia di 2 Lokasi Strategis</h2>
            <p>Kami hadir di Jakarta dan Gresik untuk memberi layanan terbaik bagi pelanggan di seluruh Indonesia.</p>
          </div>

          <div class="location-layout">
            <div class="map-panel reveal" aria-label="Peta cakupan Jakarta dan Gresik">
              <div id="service-coordinate-map" class="coordinate-map" aria-label="Peta koordinat ULAB Jakarta dan ULAB Gresik"></div>

              <div class="map-topbar">
                <span>Jaringan Laboratorium</span>
                <strong>Jakarta <i aria-hidden="true" class="fas fa-long-arrow-alt-right"></i> Gresik</strong>
              </div>

              <div class="map-legend" aria-hidden="true">
                <span v-for="location in locations" :key="location.city">
                  <b>{{ location.number }}</b>
                  {{ location.city.replace('ULAB ', '') }}
                </span>
              </div>
              <button class="map-reset" type="button" @click="fitServiceMap">
                <i aria-hidden="true" class="fas fa-crosshairs"></i>
                Lihat semua titik
              </button>
              <span class="map-attribution">© OpenStreetMap © CARTO</span>
            </div>

            <div class="location-card-list">
              <article v-for="location in locations" :key="location.city" class="location-card reveal">
                <div class="location-card-head">
                  <div class="location-number">{{ location.number }}</div>
                  <span class="location-landmark">
                    <img :src="location.landmarkIcon" :alt="location.landmark" loading="lazy" decoding="async" />
                    <small>{{ location.landmark }}</small>
                  </span>
                </div>

                <span class="location-eyebrow">Laboratorium Kalibrasi</span>
                <h3>{{ location.city }}</h3>
                <address>
                  <i aria-hidden="true" class="fas fa-map-marker-alt"></i>
                  <span>{{ location.address }}</span>
                </address>

                <div class="scope-row" :aria-label="`Layanan tersedia di ${location.city}`">
                  <span v-for="scope in location.services" :key="scope.label">
                    <img :src="scope.icon" alt="" loading="lazy" decoding="async" />
                    <small>{{ scope.label }}</small>
                  </span>
                </div>

                <div class="location-card-foot">
                  <div class="location-focus">
                    <span>Fokus Layanan</span>
                    <strong>{{ location.focus }}</strong>
                  </div>
                  <button type="button" class="location-map-action" @click="focusServiceLocation(location)">
                    Lihat titik
                    <i aria-hidden="true" class="fas fa-arrow-right"></i>
                  </button>
                </div>
              </article>
            </div>
          </div>
        </div>
      </section>

      <section id="proses" ref="processSectionRef" class="section-block process-section">
        <div class="container">
          <div class="process-header reveal">
            <div>
              <span class="section-kicker">Proses Layanan</span>
              <h2>Alur Kalibrasi Tertelusur</h2>
            </div>
          </div>

          <div class="process-stage" :style="{ '--line-width': processLineWidth }">
            <div class="process-line" aria-hidden="true">
              <span></span>
            </div>

            <article
              v-for="(step, index) in processSteps"
              :key="step.title"
              class="process-step"
              :class="{ 'is-active': index <= activeProcessStep }"
              :style="{ '--i': index }"
            >
              <span class="process-number">0{{ index + 1 }}</span>
              <div class="process-icon" :style="{ '--process-icon': `url(${step.icon})` }">
                <span class="process-symbol" aria-hidden="true"></span>
              </div>
              <div class="process-copy">
                <h3>{{ step.title }}</h3>
                <p>{{ step.desc }}</p>
              </div>

              <span
                v-if="index < processSteps.length - 1"
                class="process-arrow"
                :class="{ 'is-active': index < activeProcessStep }"
                aria-hidden="true"
              >
                <i class="iconify" data-icon="lucide:chevron-right"></i>
              </span>
            </article>
          </div>
        </div>
      </section>

      <section class="service-stats-section" aria-label="Statistik layanan U-LAB">
        <div class="container">
          <div class="service-stats-panel reveal">
            <div class="service-stats-copy">
              <span class="section-kicker">Capaian Layanan</span>
              <h2>Statistik Layanan U-LAB</h2>
              <p>{{ landingStatsPeriod }}</p>
            </div>

            <div class="service-stats-metrics">
              <article v-for="item in landingStatsItems" :key="item.key" class="service-stat-item">
                <strong>{{ formatLandingNumber(item.value) }}</strong>
                <span>{{ item.label }}</span>
              </article>
            </div>
          </div>
        </div>
      </section>

      <section class="customer-section" aria-label="Our Customer">
        <div class="customer-heading">
          <span class="section-kicker">Our Customer</span>
        </div>

        <div class="customer-marquee" aria-label="Logo pelanggan ULAB">
          <div class="customer-track">
            <article
              v-for="(customer, index) in customerMarqueeItems"
              :key="`${customer.name}-${index}`"
              class="customer-logo"
              :class="customer.className"
              :aria-label="customer.name"
            >
              <img
                :src="customer.image"
                :alt="customer.name"
                loading="lazy"
                decoding="async"
                draggable="false"
              />
            </article>
          </div>
        </div>
      </section>

      <section class="section-block video-lab-section" aria-label="Video Laboratorium">
        <div class="container">
          <div class="video-showcase reveal">
            <div class="video-feature">
              <div
                class="video-screen"
                :class="{ 'is-playing': videoIsPlaying }"
                :style="{ '--video-poster': `url(${activeVideo.thumb})` }"
              >
                <iframe
                  v-if="videoIsPlaying"
                  :key="activeVideo.id"
                  :src="activeVideoEmbedUrl"
                  :title="activeVideo.title"
                  loading="lazy"
                  allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                  allowfullscreen
                ></iframe>
                <button
                  v-else
                  type="button"
                  class="video-play-hero"
                  :aria-label="`Putar ${activeVideo.title}`"
                  @click="playActiveVideo"
                >
                  <span>
                    <i class="iconify" data-icon="lucide:play"></i>
                  </span>
                </button>
              </div>

              <div class="video-feature-copy">
                <span>Video Laboratorium</span>
                <strong>{{ activeVideo.title }}</strong>
                <p>{{ activeVideo.desc }}</p>
              </div>
            </div>

            <div class="video-panel">
              <span class="section-kicker">U-LAB in Motion</span>
              <h2>Lihat Proses <span class="text-nowrap">U-LAB</span> Lebih Dekat</h2>
              <p>Preview video ditahan tanpa autoplay suara. Klik video utama untuk melihat proses laboratorium dengan nyaman.</p>

              <div class="video-playlist" aria-label="Daftar video U-LAB">
              <button
                v-for="(video, index) in labVideos"
                :key="video.id"
                type="button"
                class="video-card"
                :class="{ 'is-active': index === activeVideoIndex }"
                @click="activeVideoIndex = index"
              >
                <span class="video-thumb">
                  <img :src="video.thumb" :alt="video.title" loading="lazy" decoding="async" />
                  <span class="video-thumb-icon">
                    <img src="/landing-icons/video-play.svg" alt="" loading="lazy" decoding="async" />
                  </span>
                </span>
                <span class="video-card-copy">
                  <strong>{{ video.title }}</strong>
                  <small>{{ video.desc }}</small>
                </span>
              </button>
              </div>
            </div>
          </div>
        </div>
      </section>

      <section id="mobile-app" class="section-block mobile-app-section">
        <div class="container mobile-product-grid">
          <article class="mobile-product-card is-info reveal">
            <span class="section-kicker">Mobile Service Platform</span>
            <h2>Registrasi dan Pantau Layanan dari Ponsel</h2>
            <p>
              Ajukan layanan, pantau progres, cek riwayat alat, dan hubungi tim U-LAB tanpa bolak-balik membuka banyak kanal.
            </p>

            <div class="mobile-feature-list">
              <span>Registrasi alat</span>
              <span>Progress order</span>
              <span>Bantuan pelanggan</span>
            </div>

            <div class="mobile-actions">
              <a class="playstore-button" :href="androidAppUrl" target="_blank" rel="noopener">
                <img
                  class="google-play-badge"
                  src="/google-play-badge-id.png"
                  alt="Dapatkan di Google Play"
                  loading="lazy"
                  decoding="async"
                />
              </a>
              <RouterLink :to="{ name: 'auth-signup-1' }" class="outline-action">
                Daftar Sekarang
                <i aria-hidden="true" class="fas fa-arrow-right"></i>
              </RouterLink>
            </div>
          </article>

          <article class="mobile-product-card is-visual reveal">
            <img
              class="mobile-app-poster"
              src="/ulabmobile.webp"
              alt="Tampilan aplikasi U-LAB Mobile"
              loading="lazy"
              decoding="async"
            />
          </article>
        </div>
      </section>

      <LandingFooter />
    </main>

    <Transition name="cookie-slide">
      <section
        v-if="cookieBannerVisible"
        class="privacy-consent"
        role="dialog"
        aria-live="polite"
        aria-label="Persetujuan cookie dan privasi"
      >
        <div class="privacy-consent-copy">
          <span>Privasi dan Cookie</span>
          <strong>Privasi Anda penting</strong>
          <p>
            Cookie esensial digunakan agar situs berjalan dengan baik. Pilihan Anda akan disimpan di browser ini.
            Baca <a href="/privacy-policy/" target="_blank" rel="noopener">Kebijakan Privasi</a>.
          </p>
        </div>

        <div class="privacy-consent-actions">
          <button type="button" class="privacy-consent-btn is-accept" @click="setCookieConsent('accepted')">
            Setujui semua
          </button>
          <a class="privacy-consent-btn is-manage" href="/privacy-policy/" target="_blank" rel="noopener">
            Kelola privasi
            <i aria-hidden="true" class="fas fa-external-link-alt"></i>
          </a>
          <button type="button" class="privacy-consent-btn is-reject" @click="setCookieConsent('rejected')">
            Tolak
          </button>
        </div>
      </section>
    </Transition>
  </MinimalLayout>
</template>

<style lang="scss">
:root {
  --ulab-blue: #064bb8;
  --ulab-navy: #06245f;
  --ulab-ink: #0e2350;
  --ulab-teal: #11a5b5;
  --ulab-red: #e9282c;
  --ulab-soft: #f3f8ff;
  --ulab-border: #dce9fb;
  --ulab-muted: #587092;
}

.text-nowrap {
  white-space: nowrap;
}

.ulab-landing {
  min-height: 100vh;
  padding-top: 76px;
  overflow-x: hidden;
  background: #fff;
  color: var(--ulab-ink);
  font-family: var(--font, 'Montserrat', sans-serif);
}

.ulab-landing .container {
  width: min(1180px, calc(100% - 40px));
  margin: 0 auto;
}

.section-block {
  padding: clamp(64px, 8vw, 104px) 0;
}

.section-kicker {
  display: inline-flex;
  align-items: center;
  color: var(--ulab-blue);
  font-size: .82rem;
  font-weight: 950;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.section-heading {
  max-width: 760px;
  margin: 0 auto clamp(28px, 4vw, 48px);
  text-align: center;
}

.section-heading h2,
.section-copy h2 {
  margin: 8px 0 0;
  color: var(--ulab-navy);
  font-size: 2.85rem;
  font-weight: 950;
  line-height: 1.05;
  letter-spacing: 0;
}

.section-heading p,
.section-copy p {
  margin: 16px 0 0;
  color: var(--ulab-muted);
  font-size: 1.05rem;
  line-height: 1.7;
}

.reveal {
  opacity: 0;
  transform: translateY(26px);
  transition: opacity .7s ease, transform .7s cubic-bezier(.22, .61, .36, 1);
}

.reveal.is-visible {
  opacity: 1;
  transform: translateY(0);
}

.hero-showcase {
  position: relative;
  height: min(820px, calc(100svh - 76px));
  min-height: 720px;
  isolation: isolate;
  overflow: hidden;
  background: #071f4d;
}

.hero-slide {
  position: absolute;
  inset: 0;
  opacity: 0;
  transform: scale(1.03);
  transition: opacity .75s ease, transform 6.5s ease;
}

.hero-slide.is-active {
  opacity: 1;
  transform: scale(1);
}

.landing-hero-image {
  width: 100%;
  height: 100%;
  min-width: 100%;
  min-height: 100%;
  display: block;
  object-fit: cover;
}

.hero-shade {
  position: absolute;
  inset: 0;
  z-index: 1;
  background:
    linear-gradient(90deg, rgba(2, 22, 62, .92) 0%, rgba(5, 41, 97, .78) 36%, rgba(5, 41, 97, .18) 70%, rgba(5, 41, 97, .04) 100%),
    linear-gradient(0deg, rgba(3, 17, 46, .22), rgba(3, 17, 46, .08));
}

.hero-content {
  position: relative;
  z-index: 2;
  min-height: inherit;
  display: flex;
  align-items: center;
}

.hero-copy {
  width: min(680px, 100%);
  padding: clamp(40px, 8vw, 84px) 0 clamp(92px, 12vw, 132px);
  color: #fff;
}

.hero-eyebrow {
  display: inline-flex;
  padding: 8px 12px;
  border: 1px solid rgba(255, 255, 255, .34);
  border-radius: 999px;
  background: rgba(255, 255, 255, .13);
  color: #eaf6ff;
  font-size: .78rem;
  font-weight: 950;
  letter-spacing: .08em;
  text-transform: uppercase;
  backdrop-filter: blur(10px);
}

.hero-copy h1 {
  max-width: 680px;
  margin: 24px 0 0;
  color: #fff;
  font-size: 5.1rem;
  font-weight: 950;
  line-height: .98;
  letter-spacing: 0;
  text-wrap: balance;
}

.hero-copy p {
  max-width: 610px;
  margin: 20px 0 0;
  color: rgba(255, 255, 255, .92);
  font-size: 1.18rem;
  font-weight: 700;
  line-height: 1.55;
}

.hero-feature-enter-active,
.hero-feature-leave-active {
  transition: opacity .26s ease, transform .26s ease;
}

.hero-feature-enter-from,
.hero-feature-leave-to {
  opacity: 0;
  transform: translateY(10px);
}

.hero-service-statement {
  max-width: 860px;
  margin-top: 32px;
}

.hero-service-statement strong,
.hero-service-statement span {
  display: block;
}

.hero-service-statement strong {
  color: #fff;
  font-size: 3rem;
  font-weight: 950;
  line-height: 1;
  letter-spacing: 0;
  text-shadow: 0 12px 28px rgba(2, 18, 48, .28);
}

.hero-service-statement span {
  max-width: 760px;
  margin-top: 12px;
  color: rgba(255, 255, 255, .88);
  font-size: 1.18rem;
  font-weight: 750;
  line-height: 1.45;
}

.hero-badges {
  display: flex;
  flex-wrap: wrap;
  gap: 18px;
  margin-top: 30px;
}

.hero-badge {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  min-width: 145px;
}

.hero-badge i {
  width: 42px;
  height: 42px;
  display: inline-grid;
  place-items: center;
  border: 2px solid rgba(255, 255, 255, .78);
  border-radius: 999px;
  color: #fff;
  font-size: 1.1rem;
}

.hero-badge span {
  display: block;
  color: rgba(255, 255, 255, .92);
  font-size: .85rem;
  font-weight: 850;
  line-height: 1.2;
}

.hero-badge strong {
  display: block;
  color: #fff;
  font-weight: 950;
}

.hero-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 34px;
}

.btn-main,
.btn-plain,
.outline-action,
.playstore-button {
  min-height: 52px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 0 24px;
  border-radius: 8px;
  font-weight: 950;
  text-decoration: none !important;
  transition: transform .16s ease, box-shadow .16s ease, border-color .16s ease, background .16s ease;
}

.btn-main {
  color: #fff !important;
  background: var(--ulab-blue);
  box-shadow: 0 18px 34px rgba(6, 75, 184, .28);
}

.btn-main:hover,
.btn-plain:hover,
.outline-action:hover,
.playstore-button:hover {
  transform: translateY(-2px);
}

.btn-plain {
  color: var(--ulab-blue) !important;
  background: #fff;
  box-shadow: 0 18px 34px rgba(15, 23, 42, .18);
}

.hero-nav {
  position: absolute;
  top: 50%;
  z-index: 3;
  width: 54px;
  height: 54px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 999px;
  color: #fff;
  background: rgba(5, 35, 85, .78);
  box-shadow: 0 18px 34px rgba(0, 0, 0, .22);
  cursor: pointer;
  transform: translateY(-50%);
  transition: background .16s ease, transform .16s ease;
}

.hero-nav:hover {
  background: rgba(6, 75, 184, .92);
  transform: translateY(-50%) scale(1.04);
}

.hero-nav.is-prev {
  left: 22px;
}

.hero-nav.is-next {
  right: 22px;
}

.hero-dots {
  position: absolute;
  left: 50%;
  bottom: 56px;
  z-index: 3;
  display: flex;
  gap: 12px;
  transform: translateX(-50%);
}

.hero-dots button {
  width: 14px;
  height: 14px;
  padding: 0;
  border: 2px solid rgba(255, 255, 255, .75);
  border-radius: 999px;
  background: rgba(255, 255, 255, .48);
  cursor: pointer;
  transition: width .2s ease, background .2s ease;
}

.hero-dots button.is-active {
  width: 34px;
  background: #fff;
}

.standard-strip {
  position: relative;
  z-index: 5;
  margin-top: -48px;
  padding: 0 20px;
}

.standard-card {
  width: min(1180px, 100%);
  min-height: 108px;
  margin: 0 auto;
  display: grid;
  grid-template-columns: minmax(300px, .98fr) minmax(285px, 1fr) 1px minmax(285px, 1fr);
  align-items: center;
  gap: 28px;
  padding: 18px 34px;
  border: 1px solid rgba(211, 226, 247, .92);
  border-radius: 14px;
  background: rgba(255, 255, 255, .96);
  box-shadow: 0 22px 44px rgba(11, 42, 97, .16);
  backdrop-filter: blur(14px);
}

.partner-logos {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 18px;
  height: 70px;
  min-width: 0;
}

.partner-logo {
  height: 58px;
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
}

.partner-logo img {
  max-width: 100%;
  max-height: 100%;
  display: block;
  object-fit: contain;
  object-position: center;
}

.partner-logo.is-danantara {
  width: 190px;
  height: 62px;
}

.partner-logo.is-pln {
  width: 164px;
  height: 54px;
}

.standard-item {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 16px;
  min-height: 70px;
  min-width: 0;
}

.standard-logo {
  width: 104px;
  height: 60px;
  display: inline-grid;
  place-items: center;
  flex: 0 0 auto;
  overflow: hidden;
  border-radius: 10px;
  background: #fff;
  box-shadow:
    inset 0 0 0 1px #d7e6f7,
    0 10px 22px rgba(15, 58, 116, .08);
}

.standard-logo img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: contain;
  object-position: center;
}

.standard-logo.is-iso {
  width: 108px;
}

.standard-logo.is-kan {
  width: 108px;
}

.standard-item strong,
.standard-item span {
  display: block;
}

.standard-item strong {
  color: var(--ulab-navy) !important;
  font-size: 1.28rem;
  font-weight: 950;
  line-height: 1.05;
}

.standard-item span {
  margin-top: 5px;
  color: #335177 !important;
  font-weight: 800;
}

.standard-divider {
  width: 1px;
  height: 70px;
  background: #d6e2f2;
}

.accreditation-section {
  background:
    linear-gradient(180deg, #fff 0%, #f5faff 100%);
}

.accreditation-grid {
  display: grid;
  grid-template-columns: minmax(0, .86fr) minmax(0, 1.14fr);
  align-items: center;
  gap: clamp(34px, 6vw, 80px);
}

.check-list {
  display: grid;
  gap: 14px;
  margin: 26px 0 30px;
  padding: 0;
  list-style: none;
}

.check-list li {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  color: #203b63;
  font-weight: 800;
  line-height: 1.45;
}

.check-list i {
  width: 22px;
  height: 22px;
  margin-top: 1px;
  display: inline-grid;
  place-items: center;
  flex: 0 0 auto;
  border-radius: 999px;
  color: #fff;
  background: var(--ulab-blue);
  font-size: .72rem;
}

.outline-action {
  min-height: 50px;
  color: var(--ulab-blue) !important;
  border: 2px solid rgba(6, 75, 184, .62);
  background: #fff;
  box-shadow: 0 12px 24px rgba(6, 75, 184, .08);
}

.certificate-showcase {
  position: relative;
  padding-bottom: 34px;
}

.certificate-frame {
  position: relative;
  z-index: 2;
  margin: 0;
  padding: 14px;
  border: 12px solid #1d2633;
  border-radius: 4px;
  background: #f8fbff;
  box-shadow:
    0 30px 60px rgba(10, 38, 84, .18),
    inset 0 0 0 2px rgba(255, 255, 255, .78);
}

.certificate-frame img {
  display: block;
  width: 100%;
  aspect-ratio: 1.35 / 1;
  object-fit: cover;
  background: #fff;
}

.certificate-base {
  position: absolute;
  left: 7%;
  right: 7%;
  bottom: 0;
  height: 64px;
  border-radius: 50%;
  background: linear-gradient(180deg, #edf5ff, #dfeeff);
  box-shadow: inset 0 -16px 24px rgba(56, 106, 178, .09);
}

.location-section {
  background: #fff;
}

.location-layout {
  display: grid;
  grid-template-columns: minmax(250px, .84fr) minmax(280px, 1.25fr) minmax(250px, .84fr);
  align-items: stretch;
  gap: clamp(18px, 3vw, 34px);
}

.location-card {
  position: relative;
  min-height: 330px;
  padding: 30px 28px;
  border: 1px solid var(--ulab-border);
  border-radius: 8px;
  background: #fff;
  box-shadow: 0 18px 38px rgba(16, 58, 118, .08);
}

.location-card-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
}

.location-number {
  width: 34px;
  height: 34px;
  flex: 0 0 auto;
  display: grid;
  place-items: center;
  border-radius: 8px;
  color: #fff;
  background: var(--ulab-blue);
  font-size: 1.1rem;
  font-weight: 950;
}

.location-landmark {
  min-width: 0;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  padding: 7px 10px;
  border: 1px solid #d8e9ff;
  border-radius: 999px;
  color: var(--ulab-blue);
  background: linear-gradient(180deg, #f8fbff, #eef6ff);
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .7);
}

.location-landmark img {
  width: 26px;
  height: 26px;
  display: block;
  object-fit: contain;
}

.location-landmark small {
  color: #17457f;
  font-size: .7rem;
  font-weight: 950;
  line-height: 1.1;
  white-space: nowrap;
}

.location-card h3 {
  margin: 16px 0 10px;
  color: var(--ulab-navy);
  font-size: 1.2rem;
  font-weight: 950;
}

.location-card p {
  min-height: 92px;
  margin: 0;
  color: #445f82;
  font-size: .94rem;
  line-height: 1.55;
}

.scope-row {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  margin: 24px 0;
}

.scope-row span {
  position: relative;
  width: 48px;
  height: 48px;
  display: inline-grid;
  place-items: center;
  border: 1px solid #cfe3ff;
  border-radius: 999px;
  background:
    radial-gradient(circle at 30% 24%, rgba(255, 255, 255, .98), rgba(255, 255, 255, .62) 34%, rgba(232, 244, 255, .92) 72%),
    linear-gradient(180deg, #f8fbff, #eaf4ff);
  box-shadow:
    inset 0 0 0 1px rgba(255, 255, 255, .75),
    0 10px 18px rgba(6, 75, 184, .10);
  transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
}

.scope-row span:hover {
  transform: translateY(-3px);
  border-color: rgba(6, 75, 184, .34);
  box-shadow:
    inset 0 0 0 1px rgba(255, 255, 255, .82),
    0 16px 28px rgba(6, 75, 184, .16);
}

.scope-row span img {
  width: 25px;
  height: 25px;
  display: block;
  object-fit: contain;
}

.location-focus {
  padding: 16px;
  border-radius: 8px;
  background: #edf6ff;
}

.location-focus span,
.location-focus strong {
  display: block;
}

.location-focus span {
  color: #2f6bb5;
  font-size: .86rem;
  font-weight: 950;
}

.location-focus strong {
  margin-top: 5px;
  color: var(--ulab-blue);
  font-size: .94rem;
  line-height: 1.4;
}

.map-panel {
  position: relative;
  height: 420px;
  min-height: 420px;
  overflow: hidden;
  border: 1px solid #e3eefb;
  border-radius: 8px;
  background: #eaf4ff;
  box-shadow:
    inset 0 0 0 1px rgba(255, 255, 255, .72),
    0 18px 38px rgba(16, 58, 118, .08);
}

.map-panel::before {
  content: '';
  position: absolute;
  inset: 0;
  z-index: 2;
  pointer-events: none;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .45);
}

.coordinate-map {
  position: absolute;
  inset: 0;
  z-index: 1;
  width: 100%;
  height: 100%;
}

.coordinate-map .leaflet-container,
.coordinate-map.leaflet-container {
  width: 100%;
  height: 100%;
  font-family: var(--font, 'Montserrat', sans-serif);
  background: #eaf4ff;
}

.coordinate-map .leaflet-tile {
  filter: saturate(.9) contrast(1.04);
}

.coordinate-map .leaflet-marker-icon {
  filter: drop-shadow(0 10px 12px rgba(6, 75, 184, .24));
}

.service-map-marker {
  display: grid;
  place-items: center;
  background: transparent;
  border: 0;
}

.service-map-marker span {
  position: relative;
  width: 28px;
  height: 28px;
  display: block;
  border: 3px solid #fff;
  border-radius: 50% 50% 50% 0;
  background: linear-gradient(135deg, #064bb8, #0b62d6);
  box-shadow: 0 14px 24px rgba(6, 75, 184, .26);
  transform: rotate(-45deg);
}

.service-map-marker span::after {
  content: '';
  position: absolute;
  inset: 7px;
  border-radius: 999px;
  background: #fff;
}

.coordinate-map .leaflet-control-zoom {
  margin: 14px;
  border: 0;
  box-shadow: 0 12px 26px rgba(15, 58, 116, .16);
}

.coordinate-map .leaflet-control-zoom a {
  width: 34px;
  height: 34px;
  border: 0;
  color: var(--ulab-navy);
  font-weight: 950;
  line-height: 34px;
}

.coordinate-map .leaflet-popup-content-wrapper {
  border-radius: 8px;
  box-shadow: 0 18px 36px rgba(8, 35, 79, .18);
}

.coordinate-map .leaflet-popup-content {
  min-width: 220px;
  margin: 14px;
}

.coordinate-map .leaflet-popup-content strong,
.coordinate-map .leaflet-popup-content span,
.coordinate-map .leaflet-popup-content em {
  display: block;
}

.coordinate-map .leaflet-popup-content strong {
  color: var(--ulab-navy);
  font-size: .94rem;
  font-weight: 950;
}

.coordinate-map .leaflet-popup-content span {
  margin-top: 6px;
  color: #536b8c;
  font-size: .78rem;
  line-height: 1.45;
}

.coordinate-map .leaflet-popup-content em {
  margin-top: 8px;
  color: var(--ulab-blue);
  font-size: .72rem;
  font-style: normal;
  font-weight: 900;
}

.service-map-tooltip {
  padding: 8px 12px;
  border: 0 !important;
  border-radius: 999px;
  color: #fff;
  background: var(--ulab-blue);
  box-shadow: 0 12px 24px rgba(6, 75, 184, .24);
  font-size: .78rem;
  font-weight: 950;
}

.service-map-tooltip::before {
  border-top-color: var(--ulab-blue) !important;
}

.map-legend,
.map-reset,
.map-caption,
.map-attribution {
  position: absolute;
  z-index: 4;
}

.map-legend {
  left: 14px;
  bottom: 14px;
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.map-legend span {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 11px;
  border-radius: 999px;
  color: var(--ulab-navy);
  background: rgba(255, 255, 255, .92);
  box-shadow: 0 10px 20px rgba(15, 58, 116, .12);
  font-size: .75rem;
  font-weight: 950;
  backdrop-filter: blur(12px);
}

.map-legend i {
  color: var(--ulab-blue);
}

.map-reset {
  right: 14px;
  top: 14px;
  min-height: 38px;
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 0 13px;
  border: 0;
  border-radius: 8px;
  color: #fff;
  background: var(--ulab-blue);
  box-shadow: 0 14px 28px rgba(6, 75, 184, .20);
  font-size: .78rem;
  font-weight: 950;
  cursor: pointer;
}

.map-reset:hover {
  transform: translateY(-1px);
}

.map-caption {
  left: 14px;
  right: 14px;
  top: 14px;
  width: fit-content;
  max-width: calc(100% - 190px);
  padding: 9px 12px;
  border-radius: 8px;
  color: #315276;
  background: rgba(255, 255, 255, .90);
  box-shadow: 0 10px 20px rgba(15, 58, 116, .10);
  font-size: .74rem;
  font-weight: 850;
  backdrop-filter: blur(12px);
}

.map-attribution {
  right: 12px;
  bottom: 10px;
  color: #5d7798;
  font-size: .62rem;
  font-weight: 800;
  text-shadow: 0 1px 0 rgba(255, 255, 255, .7);
}

/* Location showcase */
.location-section {
  position: relative;
  overflow: hidden;
  background:
    radial-gradient(circle at 8% 16%, rgba(44, 139, 255, .1), transparent 27%),
    radial-gradient(circle at 94% 84%, rgba(22, 196, 176, .08), transparent 24%),
    linear-gradient(180deg, #f7fbff 0%, #fff 42%, #f8fbff 100%);
}

.location-section::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  opacity: .32;
  background-image: radial-gradient(rgba(6, 75, 184, .24) .8px, transparent .8px);
  background-size: 24px 24px;
  mask-image: linear-gradient(to bottom, transparent, #000 15%, #000 82%, transparent);
}

.location-section .container {
  position: relative;
  z-index: 1;
}

.location-layout {
  display: grid;
  grid-template-columns: minmax(0, 1.32fr) minmax(390px, .92fr);
  align-items: stretch;
  gap: clamp(22px, 3vw, 38px);
}

.location-card-list {
  display: grid;
  grid-template-rows: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.location-card {
  position: relative;
  min-height: 0;
  overflow: hidden;
  padding: 20px 22px;
  border: 1px solid rgba(28, 112, 224, .16);
  border-radius: 26px;
  background:
    linear-gradient(135deg, rgba(255, 255, 255, .98), rgba(246, 251, 255, .94)),
    #fff;
  box-shadow:
    0 24px 50px rgba(19, 66, 127, .1),
    inset 0 1px 0 rgba(255, 255, 255, .9);
}

.location-card::after {
  content: '';
  position: absolute;
  top: -72px;
  right: -58px;
  width: 170px;
  height: 170px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(42, 132, 255, .13), transparent 68%);
  pointer-events: none;
}

.location-card-head,
.location-card h3,
.location-card address,
.location-card .scope-row,
.location-card-foot,
.location-eyebrow {
  position: relative;
  z-index: 1;
}

.location-number {
  width: 42px;
  height: 42px;
  border-radius: 14px;
  background: linear-gradient(145deg, #0964df, #0645a8);
  box-shadow: 0 12px 24px rgba(6, 75, 184, .23);
  font-size: 1rem;
}

.location-landmark {
  padding: 7px 11px 7px 8px;
  border-color: rgba(28, 112, 224, .16);
  background: rgba(238, 247, 255, .88);
}

.location-landmark img {
  width: 25px;
  height: 25px;
}

.location-landmark small {
  color: #285a96;
  font-size: .66rem;
  letter-spacing: .02em;
}

.location-eyebrow {
  display: block;
  margin-top: 10px;
  color: #2674cf;
  font-size: .66rem;
  font-weight: 950;
  letter-spacing: .13em;
  text-transform: uppercase;
}

.location-card h3 {
  margin: 3px 0 7px;
  font-size: clamp(1.25rem, 1.7vw, 1.55rem);
  letter-spacing: -.025em;
}

.location-card address {
  min-height: 44px;
  display: grid;
  grid-template-columns: 18px minmax(0, 1fr);
  gap: 8px;
  margin: 0;
  color: #506a8d;
  font-size: .8rem;
  font-style: normal;
  font-weight: 700;
  line-height: 1.5;
}

.location-card address i {
  margin-top: 3px;
  color: #0870e7;
}

.scope-row {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 7px;
  margin: 12px 0;
}

.scope-row span {
  width: auto;
  height: 34px;
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 6px;
  padding: 0 8px;
  border-color: rgba(28, 112, 224, .14);
  border-radius: 12px;
  background: rgba(240, 248, 255, .9);
  box-shadow: none;
}

.scope-row span:hover {
  transform: translateY(-2px);
  border-color: rgba(6, 75, 184, .32);
  background: #fff;
  box-shadow: 0 10px 20px rgba(6, 75, 184, .1);
}

.scope-row span img {
  width: 19px;
  height: 19px;
}

.scope-row span small {
  min-width: 0;
  overflow: hidden;
  color: #244f84;
  font-size: .6rem;
  font-weight: 900;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.location-card-foot {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 12px;
  padding-top: 10px;
  border-top: 1px solid rgba(28, 112, 224, .12);
}

.location-focus {
  min-width: 0;
  padding: 0;
  background: transparent;
}

.location-focus span {
  color: #69809e;
  font-size: .62rem;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.location-focus strong {
  max-width: 245px;
  margin-top: 3px;
  color: #0756ba;
  font-size: .69rem;
  line-height: 1.35;
}

.location-map-action {
  flex: 0 0 auto;
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 12px;
  border: 0;
  border-radius: 12px;
  color: #fff;
  background: linear-gradient(135deg, #0870e7, #064bb8);
  box-shadow: 0 10px 20px rgba(6, 75, 184, .18);
  font-size: .68rem;
  font-weight: 950;
  cursor: pointer;
  transition: transform .18s ease, box-shadow .18s ease;
}

.location-map-action:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 25px rgba(6, 75, 184, .26);
}

.map-panel {
  height: 700px;
  min-height: 700px;
  border-color: rgba(28, 112, 224, .2);
  border-radius: 30px;
  box-shadow:
    0 30px 65px rgba(19, 66, 127, .15),
    inset 0 0 0 1px rgba(255, 255, 255, .72);
}

.map-panel::before {
  border-radius: inherit;
  box-shadow:
    inset 0 0 0 1px rgba(255, 255, 255, .58),
    inset 0 110px 100px -100px rgba(3, 38, 82, .28);
}

.map-topbar {
  position: absolute;
  top: 18px;
  left: 74px;
  z-index: 4;
  min-width: 210px;
  padding: 11px 15px;
  border: 1px solid rgba(255, 255, 255, .72);
  border-radius: 16px;
  color: var(--ulab-navy);
  background: rgba(255, 255, 255, .9);
  box-shadow: 0 16px 32px rgba(15, 58, 116, .14);
  backdrop-filter: blur(16px);
}

.map-topbar span,
.map-topbar strong {
  display: block;
}

.map-topbar span {
  color: #527092;
  font-size: .62rem;
  font-weight: 900;
  letter-spacing: .1em;
  text-transform: uppercase;
}

.map-topbar strong {
  margin-top: 2px;
  font-size: .86rem;
  font-weight: 950;
}

.map-topbar i {
  margin: 0 6px;
  color: #0870e7;
}

.map-legend {
  left: 18px;
  bottom: 18px;
}

.map-legend span {
  min-height: 42px;
  padding: 6px 13px 6px 6px;
  border: 1px solid rgba(255, 255, 255, .8);
  border-radius: 14px;
}

.map-legend b {
  width: 29px;
  height: 29px;
  display: grid;
  place-items: center;
  border-radius: 9px;
  color: #fff;
  background: linear-gradient(145deg, #0870e7, #064bb8);
}

.map-reset {
  top: 18px;
  right: 18px;
  min-height: 44px;
  padding: 0 15px;
  border-radius: 14px;
  background: linear-gradient(135deg, #0870e7, #064bb8);
}

.map-attribution {
  right: 16px;
  bottom: 14px;
}

.service-map-marker span {
  width: 32px;
  height: 32px;
  border-width: 3px;
}

.service-map-marker span::after {
  display: none;
}

.service-map-marker span b {
  position: absolute;
  inset: 0;
  display: grid;
  place-items: center;
  color: #fff;
  font-size: .68rem;
  font-weight: 950;
  transform: rotate(45deg);
}

.service-map-tooltip {
  padding: 7px 11px;
  border: 2px solid rgba(255, 255, 255, .9) !important;
  background: linear-gradient(135deg, #0870e7, #064bb8);
}

.process-section {
  position: relative;
  overflow: hidden;
  background: #fff;
  color: var(--ulab-ink);
}

.process-section::before {
  display: none;
}

.process-section::after {
  display: none;
}

.process-section .container {
  position: relative;
  z-index: 1;
}

.process-header {
  display: flex;
  align-items: end;
  justify-content: space-between;
  gap: 28px;
  margin-bottom: 34px;
}

.process-header h2 {
  max-width: 720px;
  margin: 8px 0 0;
  color: var(--ulab-navy);
  font-size: clamp(2.1rem, 4.2vw, 4.4rem);
  font-weight: 950;
  line-height: .98;
}

.process-header p {
  max-width: 430px;
  margin: 0;
  color: #5d7394;
  font-size: 1rem;
  line-height: 1.6;
  font-weight: 750;
}

.process-stage {
  --line-width: 0%;
  position: relative;
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 22px;
  padding: 0;
  border: 0;
  border-radius: 0;
  background: transparent;
  box-shadow: none;
  backdrop-filter: none;
}

.process-line {
  display: none;
}

.process-line span {
  display: none;
}

.process-step {
  position: relative;
  min-height: 232px;
  display: grid;
  align-content: start;
  gap: 14px;
  padding: 18px;
  border: 1px solid #dce8fb;
  border-radius: 10px;
  color: var(--ulab-ink);
  background: linear-gradient(180deg, #fff, #f8fbff);
  opacity: .96;
  transform: translateY(10px);
  transition:
    opacity .45s ease,
    transform .55s cubic-bezier(.22, .61, .36, 1),
    background .35s ease,
    box-shadow .35s ease,
    border-color .35s ease,
    color .3s ease;
  transition-delay: calc(var(--i) * .05s);
}

.process-step.is-active {
  opacity: 1;
  transform: translateY(0);
  border-color: rgba(6, 75, 184, .34);
  color: #0e2350;
  background: linear-gradient(180deg, #fff, #eef6ff);
  box-shadow: 0 18px 38px rgba(16, 58, 118, .10);
}

.process-number {
  width: max-content;
  min-width: 54px;
  height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  color: #064bb8;
  background: #edf5ff;
  font-size: .82rem;
  font-weight: 950;
  letter-spacing: .08em;
  transition: background .3s ease, color .3s ease;
}

.process-step.is-active .process-number {
  color: #fff;
  background: #064bb8;
}

.process-icon {
  position: relative;
  width: 78px;
  height: 78px;
  display: grid;
  place-items: center;
  border: 1px solid #cfe0f7;
  border-radius: 8px;
  color: #064bb8;
  background: #eef6ff;
  box-shadow: inset 0 0 0 1px rgba(6, 75, 184, .04);
  transition: transform .3s ease, color .3s ease, background .3s ease, box-shadow .3s ease;
}

.process-symbol {
  width: 42px;
  height: 42px;
  display: block;
  background: currentColor;
  mask: var(--process-icon) center / contain no-repeat;
  -webkit-mask: var(--process-icon) center / contain no-repeat;
}

.process-step.is-active .process-icon {
  color: #fff;
  background: #064bb8;
  box-shadow: inset 0 0 0 1px rgba(255, 255, 255, .16), 0 18px 34px rgba(6, 75, 184, .18);
  transform: translateY(-3px);
}

.process-copy {
  display: grid;
  gap: 8px;
}

.process-step h3 {
  margin: 0;
  color: inherit;
  font-size: 1.02rem;
  font-weight: 950;
  line-height: 1.22;
}

.process-step p {
  margin: 0;
  color: #587092;
  font-size: .82rem;
  line-height: 1.55;
  font-weight: 750;
}

.process-step.is-active p {
  color: #587092;
}

.process-arrow {
  position: absolute;
  top: 78px;
  right: -29px;
  z-index: 4;
  width: 36px;
  height: 36px;
  display: inline-grid;
  place-items: center;
  border-radius: 999px;
  color: #064bb8;
  background: #fff;
  border: 1px solid #dce8fb;
  opacity: .86;
  transform: translateX(-10px);
  transition: opacity .35s ease, transform .35s ease, color .35s ease, background .35s ease;
}

.process-arrow svg {
  width: 18px;
  height: 18px;
}

.process-arrow.is-active {
  color: #fff;
  background: #064bb8;
  opacity: 1;
  transform: translateX(0);
  animation: arrow-nudge 1.15s ease-in-out infinite;
}

.service-stats-section {
  padding: 36px 0 56px;
  background: #fff;
}

.service-stats-panel {
  display: grid;
  grid-template-columns: minmax(220px, .55fr) minmax(0, 1fr);
  align-items: center;
  gap: clamp(18px, 3vw, 42px);
  padding: clamp(22px, 3vw, 36px);
  border-radius: 999px;
  color: #fff;
  background: #174b8c;
  box-shadow: 0 24px 54px rgba(23, 75, 140, .18);
}

.service-stats-copy {
  padding-left: clamp(8px, 2vw, 28px);
}

.service-stats-copy .section-kicker {
  color: #bddbff;
}

.service-stats-copy h2 {
  margin: 6px 0 4px;
  color: #fff;
  font-size: clamp(1.35rem, 2vw, 2rem);
  font-weight: 950;
  line-height: 1.05;
}

.service-stats-copy p {
  margin: 0;
  color: rgba(255, 255, 255, .78);
  font-size: .86rem;
  font-weight: 800;
}

.service-stats-metrics {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  align-items: center;
  gap: 8px;
}

.service-stat-item {
  min-height: 96px;
  display: grid;
  place-items: center;
  gap: 8px;
  padding: 10px;
  border-left: 1px solid rgba(255, 255, 255, .18);
  text-align: center;
}

.service-stat-item strong {
  color: #fff;
  font-size: clamp(2rem, 3.8vw, 4.1rem);
  font-weight: 950;
  line-height: .92;
  letter-spacing: 0;
}

.service-stat-item span {
  color: rgba(255, 255, 255, .88);
  font-size: .82rem;
  font-weight: 950;
  letter-spacing: .04em;
  text-transform: uppercase;
}

.customer-section {
  position: relative;
  overflow: hidden;
  padding: 34px 0 42px;
  background: #fff;
  border-top: 1px solid rgba(220, 233, 251, .86);
}

.customer-heading {
  width: min(1180px, calc(100% - 40px));
  margin: 0 auto 18px;
  text-align: center;
}

.customer-marquee {
  position: relative;
  overflow: hidden;
  width: 100%;
  padding: 8px 0;
  background: linear-gradient(180deg, #fff 0%, #fbfdff 100%);
}

.customer-marquee::before,
.customer-marquee::after {
  content: '';
  position: absolute;
  top: 0;
  bottom: 0;
  z-index: 2;
  width: min(150px, 18vw);
  pointer-events: none;
}

.customer-marquee::before {
  left: 0;
  background: linear-gradient(90deg, #fff, rgba(255, 255, 255, 0));
}

.customer-marquee::after {
  right: 0;
  background: linear-gradient(270deg, #fff, rgba(255, 255, 255, 0));
}

.customer-track {
  width: max-content;
  display: flex;
  align-items: center;
  gap: 64px;
  padding: 0 64px;
  animation: customer-marquee 42s linear infinite;
}

.customer-marquee:hover .customer-track {
  animation-play-state: paused;
}

.customer-logo {
  width: 210px;
  height: 74px;
  display: grid;
  place-items: center;
  flex: 0 0 auto;
}

.customer-logo img {
  max-width: 100%;
  max-height: 64px;
  display: block;
  object-fit: contain;
  transition: filter .25s ease, transform .25s ease;
  user-select: none;
}

.customer-logo:hover img {
  filter: saturate(1.08) contrast(1.04);
  transform: translateY(-2px) scale(1.03);
}

.customer-logo.is-extra-wide {
  width: 310px;
}

.customer-logo.is-wide {
  width: 245px;
}

.customer-logo.is-compact {
  width: 160px;
}

.customer-logo.is-emblem,
.customer-logo.is-qualis,
.customer-logo.is-rekajasa {
  width: 118px;
}

.customer-logo.is-icon {
  width: 82px;
}

.customer-logo.is-rekajasa {
  width: 142px;
}

.customer-logo.is-icon img {
  max-height: 50px;
}

.customer-logo.is-emblem img,
.customer-logo.is-qualis img,
.customer-logo.is-rekajasa img {
  max-height: 70px;
}

.customer-logo.is-qualis img {
  max-height: 76px;
}

@keyframes customer-marquee {
  to {
    transform: translateX(-50%);
  }
}

.video-lab-section {
  position: relative;
  overflow: hidden;
  background: #fff;
}

.video-lab-section::before {
  display: none;
}

.video-showcase {
  position: relative;
  z-index: 1;
  display: grid;
  grid-template-columns: minmax(0, 1.08fr) minmax(360px, .72fr);
  gap: clamp(22px, 4vw, 48px);
  align-items: center;
}

.video-feature {
  position: relative;
  overflow: hidden;
  border-radius: 34px;
  background: #061735;
  box-shadow: 0 32px 72px rgba(7, 36, 95, .24);
}

.video-feature::before {
  content: '';
  position: absolute;
  inset: 16px;
  z-index: 2;
  pointer-events: none;
  border: 1px solid rgba(255, 255, 255, .16);
  border-radius: 24px;
}

.video-screen {
  --video-poster: none;
  position: relative;
  overflow: hidden;
  aspect-ratio: 16 / 9;
  background:
    linear-gradient(90deg, rgba(3, 18, 51, .66), rgba(3, 18, 51, .20)),
    var(--video-poster) center / cover no-repeat;
}

.video-screen::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(180deg, rgba(2, 12, 32, .16), rgba(2, 12, 32, .64)),
    radial-gradient(circle at 50% 48%, rgba(255, 255, 255, .24), transparent 28%);
  opacity: .92;
}

.video-screen::after {
  content: 'SEKILAS U-LAB';
  position: absolute;
  left: 28px;
  top: 26px;
  z-index: 2;
  padding: 8px 14px;
  border-radius: 999px;
  color: #fff;
  background: rgba(255, 255, 255, .14);
  font-size: .72rem;
  font-weight: 950;
  letter-spacing: .12em;
}

.video-screen.is-playing::before,
.video-screen.is-playing::after {
  display: none;
}

.video-screen iframe {
  position: relative;
  z-index: 3;
  width: 100%;
  height: 100%;
  display: block;
  border: 0;
}

.video-play-hero {
  position: absolute;
  left: 50%;
  top: 50%;
  z-index: 4;
  width: 96px;
  height: 96px;
  display: grid;
  place-items: center;
  border: 0;
  border-radius: 999px;
  color: #fff;
  background: rgba(6, 75, 184, .96);
  box-shadow: 0 24px 46px rgba(0, 0, 0, .28);
  transform: translate(-50%, -50%);
  cursor: pointer;
  transition: transform .22s ease, box-shadow .22s ease, background .22s ease;
}

.video-play-hero:hover {
  background: #0b62d6;
  box-shadow: 0 30px 58px rgba(6, 75, 184, .34);
  transform: translate(-50%, -50%) scale(1.04);
}

.video-play-hero span {
  width: 72px;
  height: 72px;
  display: grid;
  place-items: center;
  border-radius: inherit;
  background: rgba(255, 255, 255, .16);
}

.video-play-hero svg {
  width: 32px;
  height: 32px;
  margin-left: 4px;
}

.video-feature-copy {
  display: grid;
  gap: 6px;
  padding: 24px 28px 28px;
  color: #fff;
  background:
    radial-gradient(circle at 0% 0%, rgba(31, 139, 255, .36), transparent 38%),
    linear-gradient(135deg, #061b42, #082d72);
}

.video-feature-copy span {
  color: #8bd7ff;
  font-size: .72rem;
  font-weight: 950;
  letter-spacing: .10em;
  text-transform: uppercase;
}

.video-feature-copy strong {
  color: #fff;
  font-size: 1.22rem;
  font-weight: 950;
  line-height: 1.15;
}

.video-feature-copy p {
  margin: 0;
  color: rgba(232, 243, 255, .78);
  font-size: .9rem;
  line-height: 1.55;
}

.video-panel {
  display: grid;
  align-content: center;
  gap: 18px;
  padding: clamp(22px, 3vw, 34px);
  border-radius: 34px 34px 34px 6px;
  background: #fff;
  box-shadow: 0 24px 60px rgba(16, 58, 118, .12);
}

.video-panel h2 {
  margin: 0;
  color: var(--ulab-navy);
  font-size: clamp(1.9rem, 2.8vw, 3.05rem);
  font-weight: 950;
  line-height: 1.04;
}

.video-panel > p {
  max-width: 420px;
  margin: 0;
  color: #60789a;
  font-size: .98rem;
  line-height: 1.58;
  font-weight: 720;
}

.video-playlist {
  display: grid;
  gap: 10px;
}

.video-card {
  width: 100%;
  min-height: 92px;
  display: grid;
  grid-template-columns: 108px minmax(0, 1fr);
  align-items: center;
  gap: 14px;
  padding: 10px;
  border: 1px solid rgba(220, 233, 251, .94);
  border-radius: 18px;
  color: var(--ulab-ink);
  background: rgba(255, 255, 255, .92);
  text-align: left;
  cursor: pointer;
  transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
}

.video-card:hover,
.video-card.is-active {
  transform: translateY(-2px);
  border-color: rgba(6, 75, 184, .42);
  background: #fff;
  box-shadow: 0 22px 44px rgba(6, 75, 184, .12);
}

.video-thumb {
  position: relative;
  overflow: hidden;
  aspect-ratio: 16 / 9;
  border-radius: 12px;
  background: #dbeafe;
}

.video-thumb img:first-child {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  transition: transform .25s ease, filter .25s ease;
}

.video-card:hover .video-thumb img:first-child,
.video-card.is-active .video-thumb img:first-child {
  filter: saturate(1.08) contrast(1.04);
  transform: scale(1.04);
}

.video-thumb-icon {
  position: absolute;
  left: 50%;
  top: 50%;
  width: 36px;
  height: 36px;
  display: grid;
  place-items: center;
  border-radius: 999px;
  background: rgba(255, 255, 255, .92);
  box-shadow: 0 12px 24px rgba(7, 36, 95, .22);
  transform: translate(-50%, -50%);
}

.video-thumb-icon img {
  width: 18px;
  height: 18px;
  display: block;
}

.video-card-copy {
  min-width: 0;
  display: grid;
  gap: 6px;
}

.video-card-copy strong {
  color: var(--ulab-navy);
  font-size: 1rem;
  font-weight: 950;
  line-height: 1.22;
}

.video-card-copy small {
  color: #62799a;
  font-size: .78rem;
  font-weight: 750;
  line-height: 1.42;
}

@keyframes arrow-nudge {
  0%,
  100% {
    transform: translateX(0);
  }

  50% {
    transform: translateX(6px);
  }
}

.mobile-app-section {
  position: relative;
  overflow: hidden;
  padding: clamp(44px, 5vw, 68px) 0;
  background: #f5f8fc;
  color: var(--ulab-ink);
}

.mobile-app-section::before {
  content: '';
  position: absolute;
  inset: 0;
  pointer-events: none;
  background:
    linear-gradient(135deg, rgba(6, 75, 184, .07), transparent 42%),
    linear-gradient(0deg, rgba(255, 255, 255, .9), rgba(255, 255, 255, .16));
}

.mobile-product-grid {
  position: relative;
  z-index: 1;
  max-width: 1040px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(300px, .78fr);
  align-items: center;
  gap: clamp(18px, 2.6vw, 34px);
}

.mobile-product-card {
  position: relative;
  min-height: 410px;
  overflow: hidden;
  border-radius: 24px;
  box-shadow: 0 20px 44px rgba(16, 58, 118, .10);
}

.mobile-product-card.is-visual {
  min-height: 0;
  aspect-ratio: 3 / 4.1;
  display: block;
  padding: 0;
  color: inherit;
  background: #fff;
}

.mobile-product-card.is-visual::after {
  display: none;
}

.mobile-app-poster {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  object-position: center;
}

.mobile-product-card.is-info {
  display: grid;
  align-content: center;
  gap: 18px;
  padding: clamp(28px, 4vw, 48px);
  background: #fff;
}

.mobile-product-card.is-info::before {
  content: '';
  position: absolute;
  right: -68px;
  top: -68px;
  width: 210px;
  height: 210px;
  border-radius: 999px;
  background: rgba(6, 75, 184, .08);
}

.mobile-product-copy {
  position: relative;
  z-index: 2;
  max-width: 340px;
}

.mobile-product-copy span {
  display: block;
  color: rgba(255, 255, 255, .78);
  font-size: .82rem;
  font-weight: 950;
  letter-spacing: .12em;
  text-transform: uppercase;
}

.mobile-product-copy h2 {
  margin: 10px 0 8px;
  color: #fff;
  font-size: clamp(3.4rem, 6vw, 6.8rem);
  font-weight: 950;
  line-height: .86;
}

.mobile-product-copy p {
  margin: 0;
  color: rgba(233, 244, 255, .84);
  font-size: 1rem;
  line-height: 1.55;
  font-weight: 750;
}

.phone-stage {
  position: relative;
  z-index: 2;
  min-height: 320px;
  display: grid;
  place-items: center;
  margin-top: 18px;
}

.phone-shell {
  position: relative;
  width: min(260px, 70vw);
  aspect-ratio: 9 / 18.2;
  padding: 10px;
  border: 9px solid #061735;
  border-radius: 38px;
  background: #071a37;
  box-shadow: 0 28px 54px rgba(0, 0, 0, .34);
  overflow: hidden;
  transform: rotate(-7deg) translateY(8px);
}

.phone-shell::before {
  content: '';
  position: absolute;
  top: 12px;
  left: 50%;
  z-index: 3;
  width: 78px;
  height: 18px;
  border-radius: 999px;
  background: #071a37;
  transform: translateX(-50%);
}

.phone-shell img {
  width: 100%;
  height: 100%;
  display: block;
  object-fit: cover;
  border-radius: 22px;
}

.phone-badge {
  position: absolute;
  z-index: 4;
  min-width: 96px;
  padding: 10px 14px;
  border-radius: 999px;
  color: #064bb8;
  background: rgba(255, 255, 255, .96);
  font-size: .78rem;
  font-weight: 950;
  text-align: center;
  box-shadow: 0 16px 34px rgba(0, 0, 0, .18);
}

.phone-badge.is-top {
  right: 6%;
  top: 16%;
}

.phone-badge.is-bottom {
  left: 4%;
  bottom: 20%;
}

.mobile-product-card.is-info h2 {
  max-width: 560px;
  margin: 0;
  color: var(--ulab-navy);
  font-family: 'Roboto', var(--font, sans-serif);
  font-size: clamp(2rem, 3.2vw, 3.5rem);
  font-weight: 700;
  line-height: 1.05;
}

.mobile-product-card.is-info > p {
  max-width: 520px;
  margin: 0;
  color: #5d7394;
  font-size: 1rem;
  line-height: 1.7;
  font-weight: 500;
}

.mobile-feature-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.mobile-feature-list span {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 38px;
  padding: 0 14px;
  border-radius: 999px;
  color: #064bb8;
  background: #edf5ff;
  font-size: .78rem;
  font-weight: 700;
}

.mobile-feature-list span::before {
  content: '';
  width: 7px;
  height: 7px;
  border-radius: 999px;
  background: currentColor;
}

.mobile-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 14px;
  margin-top: 2px;
}

.playstore-button {
  min-height: auto;
  padding: 0;
  color: #101827 !important;
  background: transparent;
  border: 0;
  border-radius: 9px;
  box-shadow: 0 16px 30px rgba(16, 58, 118, .10);
}

.google-play-badge {
  width: 188px;
  height: auto;
  display: block;
}

.mobile-app-section .outline-action {
  color: #064bb8 !important;
  border-color: rgba(6, 75, 184, .42);
  background: #f7fbff;
  box-shadow: none;
}

.mobile-auth-nav {
  display: none;
}

.privacy-consent {
  position: fixed;
  left: 50%;
  bottom: 0;
  z-index: 10030;
  width: 100%;
  min-height: 200px;
  display: grid;
  grid-template-columns: minmax(0, 1fr) 360px;
  align-items: center;
  gap: clamp(28px, 6vw, 90px);
  padding: 28px clamp(24px, 6vw, 80px) calc(28px + env(safe-area-inset-bottom));
  border-top: 1px solid rgba(148, 163, 184, .22);
  background: rgba(255, 255, 255, .98);
  box-shadow: 0 -22px 64px rgba(15, 23, 42, .18);
  transform: translateX(-50%);
  backdrop-filter: blur(14px);
}

.privacy-consent-copy span {
  display: block;
  color: #064bb8;
  font-size: .78rem;
  font-weight: 950;
  letter-spacing: .10em;
  text-transform: uppercase;
}

.privacy-consent-copy strong {
  display: block;
  margin-top: 8px;
  color: #1f2937;
  font-size: 1.55rem;
  font-weight: 950;
  line-height: 1.15;
}

.privacy-consent-copy p {
  max-width: 720px;
  margin: 12px 0 0;
  color: #475569;
  font-size: 1rem;
  line-height: 1.58;
}

.privacy-consent-copy a {
  color: #111827;
  font-weight: 950;
  text-decoration: underline;
  text-underline-offset: 3px;
}

.privacy-consent-actions {
  display: grid;
  gap: 12px;
}

.privacy-consent-btn {
  width: 100%;
  min-height: 50px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  padding: 0 18px;
  border-radius: 8px;
  border: 2px solid #1f2937;
  color: #1f2937;
  background: #fff;
  font-size: .95rem;
  font-weight: 900;
  text-decoration: none !important;
  cursor: pointer;
}

.privacy-consent-btn.is-accept {
  color: #fff;
  background: #064bb8;
  border-color: #064bb8;
}

.privacy-consent-btn.is-reject:hover,
.privacy-consent-btn.is-manage:hover {
  background: #f8fafc;
}

.cookie-slide-enter-active,
.cookie-slide-leave-active {
  transition: opacity .24s ease, transform .24s ease;
}

.cookie-slide-enter-from,
.cookie-slide-leave-to {
  opacity: 0;
  transform: translate(-50%, 12px);
}

html.is-dark .privacy-consent {
  border-color: rgba(255, 255, 255, .10);
  background: rgba(17, 24, 39, .98);
}

html.is-dark .privacy-consent-copy strong,
html.is-dark .privacy-consent-copy a {
  color: #f8fafc;
}

html.is-dark .privacy-consent-copy p {
  color: #a8b4c5;
}

html.is-dark .ulab-landing {
  --ulab-navy: #eaf2ff;
  --ulab-ink: #d7e4f6;
  --ulab-muted: #9fb3cc;
  --ulab-soft: #0b1729;
  --ulab-border: rgba(148, 163, 184, .24);
  background: #07111f;
  color: #d7e4f6;
}

html.is-dark .standard-card,
html.is-dark .location-card,
html.is-dark .certificate-frame {
  border-color: rgba(148, 163, 184, .24);
  background: rgba(12, 25, 45, .92);
  box-shadow: 0 22px 44px rgba(0, 0, 0, .30);
}

html.is-dark .accreditation-section {
  background: linear-gradient(180deg, #07111f 0%, #0b1729 100%);
}

html.is-dark .location-section,
html.is-dark .customer-section,
html.is-dark .video-lab-section,
html.is-dark .mobile-app-section {
  background: #07111f;
}

html.is-dark .customer-section {
  border-top-color: rgba(148, 163, 184, .18);
}

html.is-dark .customer-marquee {
  background: linear-gradient(180deg, #07111f 0%, #081527 100%);
}

html.is-dark .customer-marquee::before {
  background: linear-gradient(90deg, #07111f, rgba(7, 17, 31, 0));
}

html.is-dark .customer-marquee::after {
  background: linear-gradient(270deg, #07111f, rgba(7, 17, 31, 0));
}

html.is-dark .video-lab-section::before {
  display: none;
}

html.is-dark .section-heading h2,
html.is-dark .location-card h3,
html.is-dark .standard-item strong,
html.is-dark .video-card-copy strong,
html.is-dark .video-panel h2,
html.is-dark .mobile-product-card.is-info h2 {
  color: #f5f9ff !important;
}

html.is-dark .section-heading p,
html.is-dark .location-card p,
html.is-dark .standard-item span,
html.is-dark .check-list li,
html.is-dark .video-card-copy small,
html.is-dark .video-panel > p,
html.is-dark .mobile-product-card.is-info > p {
  color: #a8b7cc !important;
}

html.is-dark .standard-logo,
html.is-dark .scope-row span,
html.is-dark .outline-action,
html.is-dark .btn-plain {
  background: rgba(255, 255, 255, .08);
  border-color: rgba(148, 163, 184, .30);
  color: #eaf2ff !important;
}

html.is-dark .location-landmark {
  border-color: rgba(148, 163, 184, .30);
  background: rgba(255, 255, 255, .08);
  box-shadow: none;
}

html.is-dark .location-landmark small {
  color: #c8e3ff;
}

html.is-dark .video-card {
  border-color: rgba(148, 163, 184, .24);
  background: rgba(12, 25, 45, .86);
  box-shadow: 0 18px 36px rgba(0, 0, 0, .26);
}

html.is-dark .video-card:hover,
html.is-dark .video-card.is-active {
  border-color: rgba(74, 163, 255, .56);
  background: rgba(16, 36, 66, .96);
  box-shadow: 0 22px 46px rgba(0, 0, 0, .34);
}

html.is-dark .video-panel,
html.is-dark .mobile-product-card.is-info {
  border: 1px solid rgba(148, 163, 184, .22);
  background: rgba(12, 25, 45, .92);
  box-shadow: 0 22px 44px rgba(0, 0, 0, .30);
}

html.is-dark .mobile-feature-list span {
  color: #8ec5ff;
  background: rgba(6, 75, 184, .16);
}

html.is-dark .standard-logo {
  box-shadow: inset 0 0 0 1px rgba(148, 163, 184, .24);
}

html.is-dark .standard-divider {
  background: rgba(148, 163, 184, .24);
}

html.is-dark .certificate-frame {
  background: #111827;
}

html.is-dark .certificate-base {
  background: linear-gradient(180deg, rgba(20, 64, 120, .55), rgba(12, 25, 45, .72));
}

html.is-dark .location-focus {
  background: transparent;
}

html.is-dark .location-focus span {
  color: #8ec5ff;
}

html.is-dark .location-focus strong {
  color: #65b6ff;
}

html.is-dark .map-panel {
  border-color: rgba(148, 163, 184, .24);
  background: #0b1729;
}

html.is-dark .location-section {
  background:
    radial-gradient(circle at 8% 16%, rgba(42, 132, 255, .12), transparent 27%),
    radial-gradient(circle at 94% 84%, rgba(22, 196, 176, .08), transparent 24%),
    #07111f;
}

html.is-dark .location-card {
  border-color: rgba(96, 165, 250, .2);
  background: linear-gradient(145deg, rgba(15, 32, 57, .97), rgba(10, 24, 43, .96));
}

html.is-dark .location-landmark,
html.is-dark .scope-row span {
  border-color: rgba(96, 165, 250, .18);
  background: rgba(28, 64, 107, .34);
}

html.is-dark .location-landmark small,
html.is-dark .scope-row span small {
  color: #b9d9ff;
}

html.is-dark .location-eyebrow {
  color: #65b6ff;
}

html.is-dark .location-card address {
  color: #a8b7cc;
}

html.is-dark .location-card-foot {
  border-top-color: rgba(148, 163, 184, .18);
}

html.is-dark .map-topbar {
  border-color: rgba(148, 163, 184, .22);
  color: #f5f9ff;
  background: rgba(12, 25, 45, .9);
}

html.is-dark .map-topbar span {
  color: #91a4bd;
}

html.is-dark .map-caption,
html.is-dark .map-legend span {
  color: #eaf2ff;
  background: rgba(12, 25, 45, .88);
}

html.is-dark .map-attribution {
  color: #91a4bd;
  text-shadow: none;
}

html.is-dark .coordinate-map .leaflet-control-zoom a {
  color: #eaf2ff;
  background: #111827;
}

html.is-dark .coordinate-map .leaflet-popup-content-wrapper,
html.is-dark .coordinate-map .leaflet-popup-tip {
  background: #111827;
}

html.is-dark .coordinate-map .leaflet-popup-content strong {
  color: #f5f9ff;
}

html.is-dark .coordinate-map .leaflet-popup-content span {
  color: #a8b7cc;
}

html.is-dark .playstore-button {
  background: transparent;
}

html.is-dark .process-section,
html.is-dark .service-stats-section {
  background: #07111f;
}

html.is-dark .process-step {
  border-color: rgba(148, 163, 184, .24);
  color: #eaf2ff;
  background: rgba(12, 25, 45, .92);
  box-shadow: none;
}

html.is-dark .process-step.is-active {
  border-color: rgba(96, 165, 250, .48);
  background: rgba(16, 36, 66, .98);
  box-shadow: 0 18px 38px rgba(0, 0, 0, .28);
}

html.is-dark .process-step p {
  color: #a8b7cc;
}

html.is-dark .process-icon {
  border-color: rgba(96, 165, 250, .28);
  background: rgba(6, 75, 184, .16);
}

html.is-dark .process-number,
html.is-dark .process-arrow {
  color: #bfdbfe;
  border-color: rgba(96, 165, 250, .28);
  background: rgba(6, 75, 184, .20);
}

html.is-dark .process-step.is-active .process-number,
html.is-dark .process-step.is-active .process-icon,
html.is-dark .process-arrow.is-active {
  color: #fff;
  background: #0b62d6;
}

html.is-dark .service-stats-panel {
  background: linear-gradient(135deg, #0b2347, #123d75);
  box-shadow: 0 24px 54px rgba(0, 0, 0, .28);
}

@media (max-width: 1100px) {
  .location-layout {
    grid-template-columns: 1fr;
  }

  .location-card-list {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    grid-template-rows: auto;
  }

  .map-panel {
    order: -1;
    height: 520px;
    min-height: 520px;
  }

  .process-header {
    display: grid;
    align-items: start;
  }

  .process-stage {
    grid-template-columns: 1fr;
    max-width: 680px;
    margin: 0 auto;
    gap: 18px;
    padding: 0;
  }

  .process-line {
    display: none;
  }

  .process-line span {
    display: none;
  }

  .process-step {
    min-height: 0;
    display: grid;
    grid-template-columns: 64px minmax(0, 1fr);
    align-items: center;
    column-gap: 16px;
    row-gap: 12px;
    padding: 18px;
    text-align: left;
    transform: translateY(0);
  }

  .process-number {
    position: static;
    grid-column: 1 / -1;
    margin: 0;
  }

  .process-icon {
    width: 66px;
    height: 66px;
  }

  .process-arrow {
    left: 50%;
    right: auto;
    top: auto;
    bottom: -28px;
    width: 34px;
    height: 34px;
    transform: translateX(-50%) rotate(90deg);
  }

  .process-arrow.is-active {
    transform: translateX(-50%) rotate(90deg);
    animation: none;
  }

  .service-stats-panel {
    grid-template-columns: 1fr;
    border-radius: 34px;
    text-align: center;
  }

  .service-stats-copy {
    padding-left: 0;
  }

  .service-stats-metrics {
    grid-template-columns: repeat(4, minmax(0, 1fr));
    width: 100%;
  }
}

@media (max-width: 900px) {
  .location-card-list {
    grid-template-columns: 1fr;
  }

  .ulab-landing {
    padding-top: 72px;
    padding-bottom: 74px;
  }

  .hero-showcase {
    height: 660px;
    min-height: 660px;
  }

  .hero-shade {
    background:
      linear-gradient(90deg, rgba(2, 22, 62, .80), rgba(5, 41, 97, .48), rgba(5, 41, 97, .14)),
      linear-gradient(0deg, rgba(3, 17, 46, .28), rgba(3, 17, 46, .03));
  }

  .hero-copy {
    padding-top: 58px;
    padding-bottom: 112px;
  }

  .hero-copy h1 {
    font-size: 3.65rem;
  }

  .hero-service-statement {
    max-width: 640px;
    margin-top: 26px;
  }

  .hero-service-statement strong {
    font-size: 2.35rem;
  }

  .hero-service-statement span {
    max-width: 560px;
    font-size: 1rem;
  }

  .hero-badges {
    gap: 12px;
  }

  .hero-badge {
    min-width: calc(50% - 8px);
  }

  .hero-nav {
    display: none;
  }

  .standard-card {
    grid-template-columns: 1fr;
    gap: 18px;
    padding: 22px;
  }

  .partner-logos {
    flex-wrap: wrap;
    justify-content: center;
    align-items: center;
  }

  .partner-logo {
    justify-content: center;
  }

  .partner-logo.is-danantara {
    width: 170px;
    height: 48px;
  }

  .partner-logo.is-pln {
    width: 146px;
    height: 42px;
  }

  .partner-logo img {
    object-position: center;
  }

  .customer-section {
    padding: 28px 0 34px;
  }

  .customer-track {
    gap: 38px;
    padding: 0 38px;
    animation-duration: 34s;
  }

  .customer-logo {
    width: 156px;
    height: 60px;
  }

  .customer-logo img {
    max-height: 52px;
  }

  .customer-logo.is-extra-wide {
    width: 230px;
  }

  .customer-logo.is-wide {
    width: 194px;
  }

  .customer-logo.is-compact {
    width: 126px;
  }

  .customer-logo.is-emblem,
  .customer-logo.is-qualis,
  .customer-logo.is-rekajasa {
    width: 86px;
  }

  .customer-logo.is-rekajasa {
    width: 108px;
  }

  .customer-logo.is-icon {
    width: 58px;
  }

  .customer-logo.is-icon img {
    max-height: 42px;
  }

  .service-stats-section {
    padding: 28px 0 44px;
  }

  .service-stats-panel {
    border-radius: 26px;
  }

  .service-stats-metrics {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .service-stat-item {
    border-left: 0;
    border-top: 1px solid rgba(255, 255, 255, .16);
  }

  .customer-logo.is-emblem img,
  .customer-logo.is-qualis img,
  .customer-logo.is-rekajasa img {
    max-height: 56px;
  }

  .video-showcase {
    grid-template-columns: 1fr;
  }

  .video-panel {
    border-radius: 28px;
  }

  .video-playlist {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .video-card {
    grid-template-columns: 118px minmax(0, 1fr);
    min-height: 104px;
  }

  .standard-divider {
    width: 100%;
    height: 1px;
  }

  .accreditation-grid,
  .mobile-product-grid {
    grid-template-columns: 1fr;
  }

  .mobile-product-card {
    min-height: auto;
  }

  .mobile-product-grid {
    max-width: 620px;
  }

  .mobile-product-card.is-visual {
    width: min(390px, 100%);
    margin: 0 auto;
  }

  .certificate-showcase {
    order: -1;
  }

  .mobile-auth-nav {
    position: fixed;
    left: 50%;
    bottom: calc(12px + env(safe-area-inset-bottom));
    z-index: 10019;
    width: min(292px, calc(100% - 118px));
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 8px;
    transform: translateX(-50%);
  }

  .mobile-auth-link {
    min-height: 44px;
    display: inline-flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 2px;
    border-radius: 999px;
    color: #fff !important;
    text-decoration: none !important;
    font-size: .68rem;
    font-weight: 950;
    line-height: 1;
    box-shadow: 0 12px 24px rgba(15, 23, 42, .18);
  }

  .mobile-auth-link svg {
    width: 16px;
    height: 16px;
  }

  .mobile-auth-link.is-login {
    background: linear-gradient(135deg, #064bb8, #0b62d6);
  }

  .mobile-auth-link.is-register {
    background: linear-gradient(135deg, #064bb8, #0b62d6);
  }

  .privacy-consent {
    bottom: calc(76px + env(safe-area-inset-bottom));
    width: min(430px, calc(100% - 24px));
    min-height: 0;
    grid-template-columns: 1fr;
    gap: 12px;
    padding: 14px;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 14px;
  }

  .privacy-consent-copy span {
    font-size: .64rem;
  }

  .privacy-consent-copy strong {
    margin-top: 4px;
    font-size: 1rem;
  }

  .privacy-consent-copy p {
    margin-top: 6px;
    font-size: .78rem;
    line-height: 1.42;
  }

  .privacy-consent-actions {
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 8px;
  }

  .privacy-consent-btn {
    min-height: 38px;
    border-width: 1px;
    font-size: .74rem;
  }

  .privacy-consent-btn.is-accept {
    grid-column: 1 / -1;
  }
}

@media (max-width: 560px) {
  .ulab-landing .container {
    width: min(100% - 28px, 1180px);
  }

  .section-block {
    padding: 58px 0;
  }

  .hero-showcase {
    height: 640px;
    min-height: 640px;
  }

  .landing-hero-image {
    object-position: 62% center !important;
  }

  .hero-shade {
    background:
      linear-gradient(90deg, rgba(2, 22, 62, .78), rgba(5, 41, 97, .46), rgba(5, 41, 97, .18)),
      linear-gradient(0deg, rgba(3, 17, 46, .38), rgba(3, 17, 46, .04));
  }

  .hero-copy {
    text-align: left;
  }

  .hero-copy h1 {
    max-width: 360px;
    font-size: 2.55rem;
  }

  .hero-copy p {
    max-width: 340px;
    font-size: .98rem;
  }

  .hero-service-statement {
    margin-top: 22px;
  }

  .hero-service-statement strong {
    font-size: 1.75rem;
  }

  .hero-service-statement span {
    margin-top: 10px;
    font-size: .9rem;
  }

  .hero-badge {
    min-width: 100%;
  }

  .hero-actions {
    flex-direction: column;
    align-items: stretch;
  }

  .btn-main,
  .btn-plain,
  .outline-action,
  .playstore-button {
    width: 100%;
    min-height: 48px;
    padding-inline: 16px;
  }

  .mobile-actions .playstore-button {
    width: auto;
    min-height: auto;
    padding: 0;
  }

  .mobile-actions .google-play-badge {
    width: 172px;
  }

  .hero-dots {
    bottom: 38px;
  }

  .standard-strip {
    margin-top: -34px;
  }

  .standard-card {
    gap: 24px;
    padding: 24px 18px;
  }

  .partner-logos {
    height: auto;
    flex-direction: column;
    gap: 12px;
  }

  .partner-logo.is-danantara {
    width: min(260px, 100%);
    height: 72px;
  }

  .partner-logo.is-pln {
    width: min(220px, 100%);
    height: 58px;
  }

  .standard-item {
    width: 100%;
    display: grid;
    grid-template-columns: 100px minmax(0, 1fr);
    gap: 14px;
    min-height: auto;
    justify-content: initial;
    text-align: left;
  }

  .standard-logo,
  .standard-logo.is-iso,
  .standard-logo.is-kan {
    width: 100px;
    height: 58px;
  }

  .standard-item strong {
    font-size: 1.12rem;
  }

  .standard-item span {
    font-size: .88rem;
  }

  .standard-divider {
    width: 100%;
    height: 1px;
  }

  .certificate-frame {
    padding: 8px;
    border-width: 8px;
  }

  .location-card {
    padding: 24px 20px;
  }

  .map-panel {
    height: 390px;
    min-height: 390px;
    border-radius: 22px;
  }

  .map-topbar {
    top: 12px;
    left: 58px;
    right: 12px;
    min-width: 0;
    width: fit-content;
    max-width: calc(100% - 70px);
    padding: 9px 12px;
  }

  .map-reset {
    top: auto;
    right: 12px;
    bottom: 58px;
    min-height: 34px;
    padding-inline: 10px;
    font-size: .7rem;
  }

  .map-legend {
    left: 12px;
    right: 12px;
    bottom: 12px;
  }

  .map-legend span {
    padding: 7px 9px;
    font-size: .68rem;
  }

  .map-attribution {
    display: none;
  }

  .location-card-head {
    align-items: flex-start;
  }

  .location-landmark {
    max-width: calc(100% - 50px);
  }

  .location-landmark small {
    white-space: normal;
  }

  .scope-row {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .video-playlist {
    grid-template-columns: 1fr;
  }

  .video-feature,
  .video-panel,
  .mobile-product-card {
    border-radius: 22px;
  }

  .video-play-hero {
    width: 74px;
    height: 74px;
  }

  .video-play-hero span {
    width: 56px;
    height: 56px;
  }

  .video-card {
    grid-template-columns: 112px minmax(0, 1fr);
    min-height: 96px;
    padding: 10px;
  }

  .video-feature-copy {
    padding: 15px;
  }

  .video-card-copy small {
    display: none;
  }

  .service-map-tooltip {
    padding: 7px 10px;
    font-size: .7rem;
  }

  .process-step {
    grid-template-columns: 54px minmax(0, 1fr);
    align-items: start;
    column-gap: 14px;
    row-gap: 10px;
    padding: 16px;
  }

  .process-line {
    display: none;
  }

  .process-number {
    position: static;
    min-width: 44px;
    height: 26px;
    font-size: .72rem;
  }

  .process-icon {
    width: 54px;
    height: 54px;
  }

  .process-symbol {
    width: 30px;
    height: 30px;
  }

  .process-arrow {
    display: none;
  }

  .process-arrow.is-active {
    animation: none;
  }

  .process-step h3 {
    font-size: 1rem;
  }

  .process-step p {
    font-size: .8rem;
    line-height: 1.45;
  }

  .service-stats-panel {
    padding: 22px 18px;
  }

  .service-stats-metrics {
    grid-template-columns: 1fr;
  }

  .service-stat-item {
    min-height: 78px;
    gap: 5px;
  }

  .mobile-product-card.is-info {
    padding: 24px;
  }

  .mobile-product-card.is-visual {
    padding: 0;
  }

  .mobile-product-card.is-info h2 {
    font-size: 2rem;
  }

  .phone-stage {
    min-height: 282px;
  }

  .phone-shell {
    width: min(210px, 68vw);
  }

  .phone-badge {
    min-width: 80px;
    padding: 8px 11px;
    font-size: .7rem;
  }
}

@media (prefers-reduced-motion: reduce) {
  .hero-slide,
  .reveal,
  .process-step,
  .process-line span,
  .process-arrow,
  .btn-main,
  .btn-plain,
  .outline-action,
  .playstore-button,
  .video-card,
  .customer-track,
  .process-arrow.is-active {
    animation: none !important;
    transition: none !important;
  }
}
</style>
