<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useHead } from '@vueuse/head'
import { gsap } from 'gsap'
import IrisBrand from '/@src/components/partials/landing/IrisBrand.vue'
import IrisIcon from '/@src/components/partials/landing/IrisIcon.vue'
import IrisScene from '/@src/components/partials/landing/IrisScene.vue'

const page = ref<HTMLElement | null>(null)
const mobileMenu = ref(false)
const paused = ref(false)
const reducedMotion = ref(false)
const motionStopped = computed(() => paused.value || reducedMotion.value)
const activeModule = ref(0)
const activeStep = ref(0)
const currentYear = new Date().getFullYear()
const login = { name: 'auth-login' }
const signup = { name: 'auth-signup-1' }
const modules = [
  {
    id: 'kalibrasi',
    name: 'Kalibrasi',
    icon: 'calibration',
    eyebrow: 'PRECISION, AT EVERY STEP',
    title: 'Setiap pengukuran.\nSelalu tertelusur.',
    description:
      'Pelanggan dapat membuat order kalibrasi dari akun sendiri. Tim mengelola penugasan, lembar kerja, hingga sertifikat dalam alur yang tertelusur.',
    points: [
      'Order langsung dari akun customer',
      'Riwayat & identitas instrumen',
      'Lembar kerja dan verifikasi hasil',
      'Sertifikat dalam satu tempat',
    ],
    image: '/ekosistem/kalibrasi.png',
    imageHeight: 1893,
    imageAlt: 'Lembar kerja kalibrasi clamp meter, unggahan hasil pengukuran, dan cetak sertifikat',
    previewLabel: 'Lembar kerja & hasil kalibrasi',
  },
  {
    id: 'repair',
    name: 'Repair',
    icon: 'repair',
    eyebrow: 'RESTORE. RECORD. RELY.',
    title: 'Pulihkan performa.\nPantau setiap progres.',
    description:
      'Dari diagnosis awal hingga pengujian akhir, setiap tahapan perbaikan terdokumentasi dan mudah dipantau.',
    points: [
      'Diagnosis & tindak lanjut perbaikan',
      'Dokumentasi kondisi sebelum–sesudah',
      'Laporan hasil repair terintegrasi',
    ],
    image: '/ekosistem/repair.png',
    imageHeight: 1896,
    imageAlt: 'Status alat repair, diagnosis kerusakan, dokumentasi foto, dan tindakan teknis perbaikan',
    previewLabel: 'Status alat & tindakan teknis repair',
  },
  {
    id: 'mutu',
    name: 'Manajemen Mutu',
    icon: 'shield',
    eyebrow: 'QUALITY, BUILT INTO THE PROCESS',
    title: 'Mutu terjaga.\nProses lebih percaya diri.',
    description:
      'Hubungkan dokumen mutu, audit internal, temuan, dan tindak lanjut dalam ruang kerja yang tertata.',
    points: [
      'Pengendalian dokumen & instruksi kerja',
      'Audit internal dan tindak lanjut',
      'Verifikasi yang terdokumentasi',
    ],
    image: '/ekosistem/mutu.png',
    imageHeight: 1716,
    imageAlt: 'Dashboard mutu dengan dokumen audit internal, surveilan, pelatihan, dan penilaian pelanggan',
    previewLabel: 'Dashboard manajemen mutu',
  },
  {
    id: 'cloud',
    name: 'Cloud & Dokumen',
    icon: 'cloud',
    eyebrow: 'EVERY FILE. ONE CONNECTED SPACE.',
    title: 'Semua dokumen.\nSatu ruang terhubung.',
    description:
      'Temukan sertifikat, laporan, dan dokumen kerja di repositori terpusat, dengan akses sesuai peran tim.',
    points: ['Folder & dokumen terorganisasi', 'Riwayat aktivitas dokumen', 'Akses berbasis peran pengguna'],
    image: '/ekosistem/cloud.png',
    imageHeight: 1896,
    imageAlt: 'Digital storage dengan folder program mutu, unggah file, pencarian, dan pengelolaan dokumen',
    previewLabel: 'Cloud & penyimpanan dokumen',
  },
  {
    id: 'customer',
    name: 'Customer',
    icon: 'user',
    eyebrow: 'YOUR ACCOUNT. YOUR CONTROL.',
    title: 'Akun Anda.\nLayanan dalam kendali.',
    description:
      'Order kalibrasi langsung dari akun sendiri. Kelola daftar alat, ajukan layanan, dan pantau riwayat pekerjaan melalui dashboard customer.',
    points: [
      'Tambah & kelola alat milik Anda',
      'Order kalibrasi dari dashboard customer',
      'Pantau riwayat order dan laporan',
    ],
    image: '/ekosistem/customer.png',
    imageHeight: 1914,
    imageAlt:
      'Dashboard customer dengan daftar alat, tambah alat, order scan QR, keranjang, riwayat, dan laporan',
    previewLabel: 'Dashboard & order mandiri customer',
  },
]
const selectedModule = computed(() => modules[activeModule.value])
const steps = [
  {
    title: 'Daftar & order kalibrasi',
    text: 'Buat akun customer, lengkapi identitas alat, lalu ajukan order kalibrasi langsung dari dashboard Anda.',
    icon: 'layers',
    detail: 'Akun sendiri, order kalibrasi mandiri. Mulai layanan langsung dari dashboard Anda.',
    tags: ['Akun customer', 'Order kalibrasi', 'Identitas alat'],
  },
  {
    title: 'Proses & validasi',
    text: 'Tim menjalankan kalibrasi atau repair, mencatat hasil, dan memverifikasi setiap tahapan.',
    icon: 'calibration',
    detail: 'Setiap pekerjaan memiliki alur dan jejak yang jelas.',
    tags: ['Penugasan', 'Lembar kerja', 'Verifikasi'],
  },
  {
    title: 'Pantau & evaluasi',
    text: 'Lihat progres layanan dan tinjau hasil untuk mendukung keputusan operasional.',
    icon: 'chart',
    detail: 'Data dari berbagai proses, dalam satu pandangan.',
    tags: ['Monitoring', 'Laporan', 'Evaluasi mutu'],
  },
  {
    title: 'Simpan & terhubung',
    text: 'Arsipkan sertifikat serta laporan di cloud agar mudah ditemukan kembali oleh tim.',
    icon: 'cloud',
    detail: 'Pekerjaan selesai. Pengetahuan tetap terhubung.',
    tags: ['Sertifikat', 'Cloud dokumen', 'Riwayat'],
  },
]
const features = [
  {
    icon: 'activity',
    title: 'Visibilitas menyeluruh',
    text: 'Lihat progres kalibrasi, repair, dan mutu dari satu sudut pandang yang utuh.',
    label: 'MONITORING TERPUSAT',
    visual: 'monitor',
  },
  {
    icon: 'link',
    title: 'Kolaborasi tanpa jeda',
    text: 'Hubungkan kebutuhan pelanggan dan pekerjaan tim dengan alur yang jelas.',
    label: 'TIM YANG TERHUBUNG',
    visual: 'connect',
  },
  {
    icon: 'lock',
    title: 'Dokumen terkendali',
    text: 'Kelola dokumen, riwayat, serta hak akses sesuai peran dalam organisasi.',
    label: 'AKSES SESUAI PERAN',
    visual: 'secure',
  },
]
let animations: ReturnType<typeof gsap.context> | undefined
let observer: IntersectionObserver | undefined
let preference: MediaQueryList | undefined
let mounted = false
const updatePreference = () => {
  reducedMotion.value = preference?.matches ?? false
  if (reducedMotion.value) {
    animations?.revert()
    observer?.disconnect()
  }
}

function selectModule(index: number, moveFocus = false) {
  activeModule.value = index
  if (moveFocus) nextTick(() => page.value?.querySelector<HTMLButtonElement>(`#module-tab-${index}`)?.focus())
}
function exploreModule(index: number) {
  selectModule(index)
  goTo('ekosistem')
}
function onTabKey(event: KeyboardEvent, index: number) {
  let target = index
  if (event.key === 'ArrowRight') target = (index + 1) % modules.length
  else if (event.key === 'ArrowLeft') target = (index + modules.length - 1) % modules.length
  else if (event.key === 'Home') target = 0
  else if (event.key === 'End') target = modules.length - 1
  else return
  event.preventDefault()
  selectModule(target, true)
}
function goTo(id: string) {
  mobileMenu.value = false
  document
    .getElementById(id)
    ?.scrollIntoView({ behavior: motionStopped.value ? 'auto' : 'smooth', block: 'start' })
}
function toggleMotion() {
  paused.value = !paused.value
  if (paused.value) {
    animations?.revert()
    observer?.disconnect()
    page.value?.querySelectorAll<HTMLElement>('[data-reveal]').forEach((element) => {
      element.style.opacity = '1'
      element.style.transform = 'none'
    })
  }
}
onMounted(() => {
  mounted = true
  preference = window.matchMedia('(prefers-reduced-motion: reduce)')
  updatePreference()
  preference.addEventListener('change', updatePreference)
  if (motionStopped.value || !page.value) return
  animations = gsap.context(() => {
    gsap.from('[data-hero]', {
      y: 28,
      opacity: 0,
      duration: 1.05,
      stagger: 0.13,
      ease: 'power3.out',
      clearProps: 'all',
    })
    gsap.from('.scene-label', {
      y: 22,
      opacity: 0,
      duration: 1,
      stagger: 0.16,
      delay: 0.55,
      ease: 'power3.out',
      clearProps: 'all',
    })
  }, page.value)
  if ('IntersectionObserver' in window) {
    observer = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return
          if (!motionStopped.value && mounted)
            animations?.add(() => {
              gsap.from(entry.target, {
                y: 38,
                opacity: 0,
                duration: 0.85,
                ease: 'power3.out',
                clearProps: 'all',
              })
            })
          observer?.unobserve(entry.target)
        })
      },
      { threshold: 0.12 }
    )
    page.value.querySelectorAll('[data-reveal]').forEach((element) => observer?.observe(element))
  }
})
onBeforeUnmount(() => {
  mounted = false
  preference?.removeEventListener('change', updatePreference)
  observer?.disconnect()
  animations?.revert()
})

useHead({
  title: 'IRIS — Kalibrasi, Repair & Mutu dalam Satu Sistem',
  htmlAttrs: { lang: 'id' },
  meta: [
    {
      name: 'description',
      content:
        'IRIS menghubungkan kalibrasi, repair, manajemen mutu, dan cloud dokumen dalam satu sistem informasi terintegrasi. Presisi dalam proses. Kepastian dalam data.',
    },
    { name: 'theme-color', content: '#f7fafc' },
  ],
  link: [
    { rel: 'preconnect', href: 'https://fonts.googleapis.com' },
    { rel: 'preconnect', href: 'https://fonts.gstatic.com', crossorigin: '' },
    {
      rel: 'stylesheet',
      href: 'https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Manrope:wght@400;500;600;700;800&display=swap',
    },
  ],
})
</script>

<template>
  <div ref="page" class="iris-landing" :class="{ 'motion-paused': motionStopped }">
    <a class="iris-skip" href="#iris-main">Lewati ke konten utama</a>
    <header class="iris-header">
      <div class="iris-shell iris-nav">
        <a href="#beranda" class="brand-link" aria-label="IRIS beranda" @click.prevent="goTo('beranda')"
          ><IrisBrand
        /></a>
        <nav class="desktop-nav" aria-label="Navigasi utama">
          <a href="#ekosistem" @click.prevent="goTo('ekosistem')">Ekosistem <span>↗</span></a>
          <a href="#keunggulan" @click.prevent="goTo('keunggulan')">Mengapa IRIS</a>
          <a href="#alur-kerja" @click.prevent="goTo('alur-kerja')">Cara kerja</a>
        </nav>
        <div class="nav-actions">
          <RouterLink :to="login" class="nav-login">Login</RouterLink>
          <RouterLink :to="signup" class="iris-button button-small"
            >Sign up <IrisIcon name="diagonal" :size="16"
          /></RouterLink>
          <button
            class="mobile-toggle"
            :aria-expanded="mobileMenu"
            aria-controls="iris-mobile-nav"
            :aria-label="mobileMenu ? 'Tutup navigasi' : 'Buka navigasi'"
            @click="mobileMenu = !mobileMenu"
          >
            <IrisIcon :name="mobileMenu ? 'close' : 'menu'" />
          </button>
        </div>
      </div>
      <nav
        v-if="mobileMenu"
        id="iris-mobile-nav"
        class="iris-mobile-nav"
        aria-label="Navigasi mobile"
        @keydown.esc="mobileMenu = false"
      >
        <a href="#ekosistem" @click.prevent="goTo('ekosistem')"
          >Ekosistem <IrisIcon name="diagonal" :size="18"
        /></a>
        <a href="#keunggulan" @click.prevent="goTo('keunggulan')"
          >Mengapa IRIS <IrisIcon name="diagonal" :size="18"
        /></a>
        <a href="#alur-kerja" @click.prevent="goTo('alur-kerja')"
          >Cara kerja <IrisIcon name="diagonal" :size="18"
        /></a>
      </nav>
    </header>

    <main id="iris-main">
      <section id="beranda" class="iris-hero iris-hero--centered">
        <div class="hero-grid" aria-hidden="true"></div>
        <div class="hero-glow" aria-hidden="true"></div>
        <div class="iris-shell hero-layout">
          <div class="hero-copy">
            <div class="hero-eyebrow" data-hero>
              <span class="status-dot"></span> SATU SISTEM. SELURUH KEMUNGKINAN.
            </div>
            <h1 data-hero>Presisi dalam proses.<br /><span>Kepastian dalam data.</span></h1>
            <p class="hero-description" data-hero>
              Order kalibrasi langsung dari akun Anda sendiri. Pantau progres, akses sertifikat, dan simpan
              dokumen dalam satu sistem yang terhubung.
            </p>
            <div class="hero-actions" data-hero>
              <RouterLink :to="login" class="iris-button"
                >Order kalibrasi <IrisIcon name="diagonal" :size="19"
              /></RouterLink>
              <RouterLink :to="signup" class="iris-text-button">
                Buat akun customer <IrisIcon name="arrow" :size="16" />
              </RouterLink>
            </div>
            <div class="hero-footnote" data-hero>
              <IrisIcon name="layers" :size="15" /><span>Kalibrasi. Repair. Mutu. Cloud dokumen.</span>
            </div>
          </div>
          <div class="hero-art">
            <div class="hero-wordmark" aria-hidden="true">
              <img src="/IRIS.png" alt="" width="1024" height="1536" decoding="async" fetchpriority="high" />
            </div>
            <div class="scene-coordinate coordinate-top" aria-hidden="true">
              IRIS CORE / 01 <span>CONNECTED INTELLIGENCE</span>
            </div>
            <IrisScene :paused="motionStopped" />
            <div class="scene-label label-calibration">
              <span class="scene-label-icon"><IrisIcon name="calibration" :size="21" /></span>
              <div><small>CUSTOMER SELF-SERVICE</small><strong>Order dari akun Anda</strong></div>
              <span class="tiny-dot"></span>
            </div>
            <div class="scene-label label-quality">
              <span class="scene-label-icon"><IrisIcon name="shield" :size="21" /></span>
              <div><small>QUALITY CONNECTED</small><strong>Mutu dalam kendali</strong></div>
            </div>
            <div class="scene-label label-cloud">
              <span class="scene-label-icon"><IrisIcon name="cloud" :size="21" /></span>
              <div><small>EVERYTHING IN SYNC</small><strong>Satu cloud. Semua data.</strong></div>
              <span class="tiny-dot"></span>
            </div>
            <div class="scene-caption">
              <span><span class="mini-cross">+</span> INTELLIGENT REAL-TIME INSIGHT SYSTEM</span
              ><button
                class="motion-control"
                :aria-label="paused ? 'Aktifkan animasi' : 'Jeda animasi'"
                :aria-pressed="paused"
                :disabled="reducedMotion"
                @click="toggleMotion"
              >
                <IrisIcon :name="motionStopped ? 'play' : 'pause'" :size="13" />{{
                  motionStopped ? 'Motion off' : 'Motion on'
                }}
              </button>
            </div>
          </div>
          <aside class="hero-customer" aria-label="Alur layanan untuk customer" data-hero>
            <span class="customer-eyebrow"><span class="tiny-dot"></span> KENDALI ADA DI TANGAN ANDA</span>
            <ol>
              <li><span>01</span> Buat akun customer</li>
              <li><span>02</span> Order & pantau kalibrasi</li>
              <li><span>03</span> Akses sertifikat & dokumen</li>
            </ol>
            <a href="#ekosistem" @click.prevent="goTo('ekosistem')"
              >Jelajahi sistem <IrisIcon name="arrow" :size="15"
            /></a>
          </aside>
        </div>
        <div class="iris-shell hero-bottom">
          <a href="#ekosistem" @click.prevent="goTo('ekosistem')"
            ><IrisIcon name="down" :size="17" /> SCROLL UNTUK MENJELAJAHI</a
          ><span>Dirancang untuk presisi. Dibangun untuk terhubung.</span
          ><span class="hero-page-index">01 — 04</span>
        </div>
      </section>

      <section class="ecosystem-strip" aria-label="Kapabilitas IRIS">
        <div class="iris-shell strip-layout">
          <div class="strip-intro">Terhubung dari awal.<br /><strong>Tertelusur hingga akhir.</strong></div>
          <button v-for="(item, index) in modules" :key="item.id" @click="exploreModule(index)">
            <IrisIcon :name="item.icon" :size="25" /><span>{{ item.name }}</span
            ><IrisIcon name="diagonal" :size="14" />
          </button>
        </div>
      </section>

      <section id="ekosistem" class="iris-ecosystem iris-shell section-space">
        <div class="section-heading" data-reveal>
          <div>
            <span class="section-eyebrow"><span></span> EKOSISTEM IRIS</span>
            <h2>Kompleks di balik layar.<br /><span>Sederhana di tangan Anda.</span></h2>
          </div>
          <p>
            Seluruh proses penting, saling terhubung.<br />Pilih kapabilitas dan jelajahi cara IRIS<br
              class="desktop-break"
            />
            membantu pekerjaan Anda.
          </p>
        </div>
        <div class="module-tabs" role="tablist" aria-label="Kapabilitas sistem IRIS">
          <button
            v-for="(item, index) in modules"
            :id="`module-tab-${index}`"
            :key="item.id"
            role="tab"
            :aria-selected="activeModule === index"
            :aria-controls="`module-panel-${index}`"
            :tabindex="activeModule === index ? 0 : -1"
            :class="{ 'is-active': activeModule === index }"
            @click="selectModule(index)"
            @keydown="onTabKey($event, index)"
          >
            <IrisIcon :name="item.icon" :size="20" />{{ item.name
            }}<span class="tab-index">0{{ index + 1 }}</span>
          </button>
        </div>
        <div
          :id="`module-panel-${activeModule}`"
          class="module-panel"
          role="tabpanel"
          :aria-labelledby="`module-tab-${activeModule}`"
          tabindex="0"
        >
          <Transition name="module-fade" mode="out-in">
            <div :key="selectedModule.id" class="module-panel-inner">
              <div class="module-copy">
                <span class="module-eyebrow">{{ selectedModule.eyebrow }}</span>
                <h3>{{ selectedModule.title }}</h3>
                <p>{{ selectedModule.description }}</p>
                <ul>
                  <li v-for="point in selectedModule.points" :key="point">
                    <span><IrisIcon name="check" :size="13" /></span>{{ point }}
                  </li>
                </ul>
                <RouterLink :to="selectedModule.id === 'customer' ? signup : login" class="module-link"
                  >{{
                    selectedModule.id === 'customer' ? 'Daftar sebagai customer' : 'Masuk ke ruang kerja'
                  }}
                  <IrisIcon name="arrow" :size="18"
                /></RouterLink>
              </div>
              <div class="preview-wrap">
                <a
                  class="dashboard-preview screenshot-preview"
                  :href="selectedModule.image"
                  target="_blank"
                  rel="noopener noreferrer"
                  :aria-label="`Lihat screenshot ${selectedModule.name} ukuran penuh (tab baru)`"
                >
                  <div class="preview-browser" aria-hidden="true">
                    <div><i></i><i></i><i></i></div>
                    <span
                      ><IrisIcon :name="selectedModule.icon" :size="10" /> IRIS /
                      {{ selectedModule.name }}</span
                    ><IrisIcon name="grid" :size="12" />
                  </div>
                  <div class="screenshot-stage">
                    <img
                      :src="selectedModule.image"
                      :alt="selectedModule.imageAlt"
                      width="3837"
                      :height="selectedModule.imageHeight"
                      loading="lazy"
                      decoding="async"
                    />
                  </div>
                </a>
                <div class="screenshot-caption">
                  <span>{{ selectedModule.previewLabel }}</span>
                  <a :href="selectedModule.image" target="_blank" rel="noopener noreferrer">
                    Ukuran penuh <IrisIcon name="diagonal" :size="13" />
                    <span class="iris-visually-hidden"> (tab baru)</span>
                  </a>
                </div>
              </div>
            </div>
          </Transition>
        </div>
      </section>

      <section id="keunggulan" class="iris-benefits section-space">
        <div class="iris-shell">
          <div class="section-heading" data-reveal>
            <div>
              <span class="section-eyebrow"><span></span> LEBIH DARI SEKADAR SISTEM</span>
              <h2>Lebih terarah.<br /><span>Lebih jauh melangkah.</span></h2>
            </div>
            <p>
              Ketika data dan proses saling terhubung,<br />tim Anda punya ruang untuk fokus<br
                class="desktop-break"
              />
              pada hal yang paling berarti.
            </p>
          </div>
          <div class="benefit-grid">
            <article v-for="feature in features" :key="feature.title" class="benefit-card" data-reveal>
              <div class="benefit-visual" :class="`visual-${feature.visual}`" aria-hidden="true">
                <template v-if="feature.visual === 'monitor'"
                  ><div class="mini-chart-label">
                    <span><span class="tiny-dot"></span> Aktivitas layanan</span
                    ><IrisIcon name="activity" :size="17" />
                  </div>
                  <svg class="mini-chart" viewBox="0 0 300 110">
                    <defs>
                      <linearGradient id="iris-chart-fill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="#28b9ea" stop-opacity=".25" />
                        <stop offset="100%" stop-color="#28b9ea" stop-opacity="0" />
                      </linearGradient>
                    </defs>
                    <path class="chart-grid" d="M0 25h300M0 60h300M0 95h300" />
                    <path
                      fill="url(#iris-chart-fill)"
                      d="M0 95C25 95 20 60 50 63S78 90 101 63 123 45 145 49 172 67 193 36 221 56 247 25 282 44 300 8V110H0Z"
                    />
                    <path
                      class="chart-line"
                      d="M0 95C25 95 20 60 50 63S78 90 101 63 123 45 145 49 172 67 193 36 221 56 247 25 282 44 300 8"
                    />
                    <circle cx="247" cy="25" r="5" fill="#089bda" stroke="white" stroke-width="3" />
                  </svg>
                  <div class="chart-axis">
                    <span>REGISTRASI</span><span>PROSES</span><span>SELESAI</span>
                  </div></template
                >
                <template v-else-if="feature.visual === 'connect'"
                  ><div class="connect-orbit"></div>
                  <div class="connect-line line-one"></div>
                  <div class="connect-line line-two"></div>
                  <span class="connect-node node-one"><IrisIcon name="calibration" /></span
                  ><span class="connect-node node-two"><IrisIcon name="repair" /></span
                  ><span class="connect-node node-three"><IrisIcon name="cloud" /></span
                  ><span class="connect-core"><IrisBrand /></span
                ></template>
                <template v-else
                  ><div class="secure-ring"></div>
                  <div class="secure-shield"><IrisIcon name="shield" :size="54" /></div>
                  <span class="secure-badge badge-one"><IrisIcon name="check" :size="12" /> Hak akses</span
                  ><span class="secure-badge badge-two"
                    ><IrisIcon name="check" :size="12" /> Jejak aktivitas</span
                  ></template
                >
              </div>
              <span class="benefit-label">{{ feature.label }}</span>
              <h3>{{ feature.title }}</h3>
              <p>{{ feature.text }}</p>
            </article>
          </div>
          <div class="benefit-note" data-reveal>
            <IrisIcon name="globe" :size="19" />
            <p>Satu pandangan yang utuh, di layar mana pun.</p>
            <span>DESKTOP <span>/</span> TABLET <span>/</span> MOBILE</span>
          </div>
        </div>
      </section>

      <section id="alur-kerja" class="iris-workflow iris-shell section-space">
        <div class="section-heading" data-reveal>
          <div>
            <span class="section-eyebrow"><span></span> DARI PROSES MENUJU INSIGHT</span>
            <h2>Alur yang jelas.<br /><span>Hasil yang bermakna.</span></h2>
          </div>
          <p>
            Dari kebutuhan pertama hingga dokumen akhir.<br />IRIS menjaga setiap langkah tetap terhubung.
          </p>
        </div>
        <div class="workflow-steps" data-reveal>
          <button
            v-for="(step, index) in steps"
            :key="step.title"
            :class="{ 'is-active': activeStep === index }"
            :aria-pressed="activeStep === index"
            aria-controls="workflow-detail"
            @click="activeStep = index"
          >
            <span class="step-number">0{{ index + 1 }}<IrisIcon :name="step.icon" :size="19" /></span>
            <h3>{{ step.title }}</h3>
            <p>{{ step.text }}</p>
            <span class="step-bottom"
              >{{ activeStep === index ? 'LANGKAH TERPILIH' : 'JELAJAHI LANGKAH'
              }}<IrisIcon name="arrow" :size="17"
            /></span>
          </button>
        </div>
        <div id="workflow-detail" class="workflow-detail" aria-live="polite">
          <span class="detail-icon"><IrisIcon :name="steps[activeStep].icon" :size="22" /></span>
          <p>{{ steps[activeStep].detail }}</p>
          <div>
            <span v-for="tag in steps[activeStep].tags" :key="tag">{{ tag }}</span>
          </div>
        </div>
      </section>

      <section class="iris-cta iris-shell" data-reveal>
        <div class="cta-orbits" aria-hidden="true"><i></i><i></i><i></i><span></span></div>
        <div class="cta-content">
          <span class="section-eyebrow"><span></span> LANGKAH BESAR DIMULAI DARI SINI</span>
          <h2>Presisi hari ini.<br /><span>Kemungkinan tanpa batas.</span></h2>
          <p>
            Satukan proses, hubungkan tim, dan temukan cara<br class="desktop-break" />
            yang lebih baik untuk bekerja bersama IRIS.
          </p>
          <div class="cta-actions">
            <RouterLink :to="signup" class="iris-button button-white"
              >Buat akun IRIS <IrisIcon name="diagonal" :size="19" /></RouterLink
            ><RouterLink :to="login" class="cta-login"
              >Sudah punya akun? <span>Login <IrisIcon name="arrow" :size="17" /></span
            ></RouterLink>
          </div>
        </div>
        <span class="cta-side-label">TURN PRECISION INTO POSSIBILITY.</span>
      </section>
    </main>

    <footer class="iris-footer iris-shell">
      <div class="footer-main">
        <div class="footer-brand">
          <a href="#beranda" aria-label="Kembali ke beranda IRIS" @click.prevent="goTo('beranda')"
            ><IrisBrand
          /></a>
          <p>Intelligent Real-time Insight System.</p>
          <span>Seluruh proses. Satu pandangan.</span>
        </div>
        <div class="footer-column">
          <h3>Platform</h3>
          <a href="#ekosistem" @click.prevent="exploreModule(0)">Kalibrasi & Repair</a
          ><a href="#ekosistem" @click.prevent="exploreModule(2)">Manajemen Mutu</a
          ><a href="#ekosistem" @click.prevent="exploreModule(3)">Cloud & Dokumen</a>
          <a href="#ekosistem" @click.prevent="exploreModule(4)">Customer</a>
        </div>
        <div class="footer-column">
          <h3>Jelajahi IRIS</h3>
          <a href="#keunggulan" @click.prevent="goTo('keunggulan')">Mengapa IRIS</a
          ><a href="#alur-kerja" @click.prevent="goTo('alur-kerja')">Cara kerja</a
          ><RouterLink :to="login">Masuk ke sistem</RouterLink>
        </div>
        <div class="footer-invitation">
          <span>MULAI SESUATU YANG LEBIH BAIK.</span
          ><RouterLink :to="signup">Mari terhubung.<IrisIcon name="diagonal" :size="27" /></RouterLink>
        </div>
      </div>
      <div class="footer-bottom">
        <span>© {{ currentYear }} IRIS. Seluruh hak cipta dilindungi.</span
        ><span><span class="tiny-dot"></span> BUILT FOR PRECISION. CONNECTED BY DESIGN.</span
        ><button aria-label="Kembali ke atas" @click="goTo('beranda')">
          <IrisIcon name="arrow" :size="16" />
        </button>
      </div>
    </footer>
  </div>
</template>

<style scoped lang="scss" src="./iris-landing.scss"></style>
