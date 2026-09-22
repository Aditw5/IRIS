<script setup lang="ts">
import { useHead } from '@vueuse/head'
import { ref, computed, onMounted, onBeforeUnmount, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

useHead({ title: 'ULAB - Layanan' })

const route = useRoute()
const router = useRouter()

/* ===== Parallax var utk banner (seperti halaman Profil) ===== */
const onScroll = () => {
  const y = window.scrollY || 0
  document.documentElement.style.setProperty('--scrollY', String(y))
}
onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})

function redirectNestedDetail(scope: unknown) {
  const value = Array.isArray(scope) ? scope[0] : scope
  if (value) {
    router.replace(`/layanan-detail/${value}`)
  }
}

watch(
  () => route.params.scope,
  (scope) => redirectNestedDetail(scope),
  { immediate: true }
)

/* ===== STATE MODAL ===== */
const isModalOpen = ref(false)
const selectedService = ref<any>(null)

/* ===== DATA LAYANAN ===== */
const services = ref([
  {
    id: 'kelistrikan',
    title: 'Kelistrikan',
    description: 'Melayani Kalibrasi Peralatan Lingkup Kelistrikan.',
    image: '/layanan1.jpg',
    floatingImages: ['/layanan2.jpg', '/layanan1.jpg', '/layanan3.jpg'],
    linkJakarta: 'virtualtour-kelistrikan',
    linkGresik: 'error-page-5',
  },
  {
    id: 'tekanan',
    title: 'Tekanan',
    description: 'Melayani Kalibrasi Peralatan Lingkup Tekanan.',
    image: '/layanan4.jpg',
    floatingImages: ['/layanan5.jpg', '/layanan4.jpg', '/layanan6.jpg'],
    linkJakarta: 'virtualtour-tekanan-vibrasi',
    linkGresik: 'error-page-5',
  },
  {
    id: 'suhu',
    title: 'Suhu',
    description: 'Melayani Kalibrasi Peralatan Lingkup Suhu.',
    image: '/layanan7.jpg',
    floatingImages: ['/layanan8.jpg', '/layanan7.jpg', '/layanan9.jpg'],
    linkJakarta: 'virtualtour-suhu',
    linkGresik: 'error-page-5',
  },
  {
    id: 'repair',
    title: 'Repair',
    description: 'Melayani Jasa Perbaikan Peralatan Standar Industri.',
    image: '/layanan15.jpg',
    floatingImages: ['/layanan15.jpg', '/layanan15.jpg', '/layanan15.jpg'],
    linkJakarta: 'virtualtour-repair',
    linkGresik: 'error-page-5',
  },
  {
    id: 'vibrasi',
    title: 'Vibrasi',
    description: 'Melayani Kalibrasi Peralatan Lingkup Vibrasi.',
    image: '/layanan10.jpg',
    floatingImages: ['/layanan8.jpg', '/layanan10.jpg', '/layanan11.jpg'],
    linkJakarta: 'virtualtour-tekanan-vibrasi',
    linkGresik: 'error-page-5',
  },
  {
    id: 'dimensi',
    title: 'Dimensi dan Gaya',
    description: 'Melayani kalibrasi peralatan ukur di lingkup Dimensi.',
    image: '/layanan12.jpg',
    floatingImages: ['/layanan12.jpg', '/layanan13.jpg', '/layanan14.jpg'],
    linkJakarta: 'error-page-5',
    linkGresik: 'error-page-5',
  },
])

/* ===== MODAL HANDLERS ===== */
function openModal(service: any) {
  selectedService.value = service
  isModalOpen.value = true
}
function closeModal() { isModalOpen.value = false }

/* ===== COMPUTED LINKS ===== */
const linkRincian = computed(() =>
  selectedService.value?.id ? { path: `/layanan-detail/${selectedService.value.id}` } : { name: 'layanan' }
)
const linkGresikComputed = computed(() =>
  (selectedService.value?.linkGresik ? { name: selectedService.value.linkGresik } : { name: 'error-page-5' })
)
const linkJakartaComputed = computed(() =>
  (selectedService.value?.linkJakarta ? { name: selectedService.value.linkJakarta } : { name: 'error-page-5' })
)
</script>

<template>
  <MinimalLayout theme="light">
    <div class="landing-page-wrapper layanan-page">
      <!-- NAV -->
      <LandingNavigation />

      <!-- ===== HERO BANNER (full-bleed, 2 gambar: desktop & mobile) ===== -->
      <section class="banner-full">
        <picture>
          <!-- Mobile -->
          <source srcset="/layananmobile.jpg" media="(max-width: 768px)" />
          <!-- Desktop -->
          <img src="/layanan.jpg" alt="Banner U-LAB Layanan" class="banner-img" fetchpriority="high" decoding="async" />
        </picture>

        <div class="banner-overlay"></div>

        <div class="banner-glass">
          <div class="glass-label">
            <i class="iconify" data-icon="feather:tool"></i>
            Informasi Layanan
          </div>
          <h1 class="glass-title">Layanan U-LAB</h1>
        </div>

        <svg class="banner-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
          <path class="wave-fill" d="M0,40 C240,100 480,0 720,40 C960,80 1200,20 1440,60 L1440,120 L0,120 Z" />
        </svg>
      </section>

      <!-- ===== KONTEN LAMA (tidak diubah) ===== -->
      <div id="Vuero-marketing" class="hero marketing-hero is-left">
        <div class="section">
          <div class="container">
            <div class="section-title has-text-centered py-6">
              <h2 class="title is-2">Informasi Layanan</h2>
            </div>

            <div class="py-12">
              <div class="columns is-vcentered is-multiline card-icon-boxes">
                <div v-for="service in services" :key="service.id" class="column is-4 layanan-card-col"
                  @click="openModal(service)" style="cursor: pointer;">
                  <div class="card card-icon-box layanan-card" style="position:relative; z-index:2;">
                    <div class="card-content">
                      <img :src="service.image" :alt="service.title"
                        style="border-radius:8px; margin-bottom:10px; width:100%; height:180px; object-fit:cover;" />
                      <h4 class="title is-5">{{ service.title }}</h4>
                      <p class="subtitle is-6 light-text">{{ service.description }}</p>
                    </div>
                  </div>

                  <!-- float preview images -->
                  <img :src="service.floatingImages[0]" alt="FloatLeft" class="layanan-float-img float-left" />
                  <img :src="service.floatingImages[1]" alt="FloatTop" class="layanan-float-img float-top" />
                  <img :src="service.floatingImages[2]" alt="FloatRight" class="layanan-float-img float-right" />
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <LandingFooter />

      <div id="backtotop">
        <a href="#" aria-label="back to top"><i aria-hidden="true" class="fas fa-angle-up"></i></a>
      </div>

      <!-- ===== MODAL ===== -->
      <div v-if="isModalOpen && selectedService" class="modal is-active">
        <div class="modal-background" @click="closeModal"></div>
        <div class="modal-content">
          <div class="box layanan-pilihan-box">
            <button class="delete is-pulled-right" aria-label="close" @click="closeModal"></button>
            <div class="has-text-centered">
              <img :src="selectedService.image" :alt="selectedService.title" class="service-image" />
              <h3 class="title is-4 mt-4">{{ selectedService.title }}</h3>
            </div>

            <div class="options-grid">
              <RouterLink :to="linkRincian" class="option-card">
                <div class="option-icon-wrapper is-info">
                  <i class="iconify" data-icon="feather:file-text"></i>
                </div>
                <div class="option-content">
                  <h4 class="title is-6">Lihat Rincian</h4>
                  <p>Info detail layanan</p>
                </div>
              </RouterLink>

              <RouterLink :to="linkJakartaComputed" class="option-card">
                <div class="option-icon-wrapper is-primary">
                  <i class="iconify" data-icon="feather:map-pin"></i>
                </div>
                <div class="option-content">
                  <h4 class="title is-6">VT Lab Jakarta</h4>
                  <p>Tur virtual lokasi Jakarta</p>
                </div>
              </RouterLink>

              <RouterLink :to="linkGresikComputed" class="option-card">
                <div class="option-icon-wrapper is-success">
                  <i class="iconify" data-icon="feather:map"></i>
                </div>
                <div class="option-content">
                  <h4 class="title is-6">VT Lab Gresik</h4>
                  <p>Tur virtual lokasi Gresik</p>
                </div>
              </RouterLink>
            </div>
          </div>
        </div>
      </div>
    </div>
  </MinimalLayout>
</template>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/_demo/landing';

/* ===== Page bg + dark friendly ===== */
.layanan-page {
  background:
    radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .08), transparent 60%),
    linear-gradient(#f8fafc, #f8fafc);
}

/* ===== FULL-BLEED BANNER (sama gaya dengan Profil) ===== */
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

  /* parallax + kenburns desktop */
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

/* ===== KONTEN YANG SUDAH ADA ===== */
.layanan-card-col {
  position: relative !important;
  overflow: visible !important;
}

.layanan-float-img {
  position: absolute;
  width: 110px;
  height: 75px;
  border-radius: 10px;
  box-shadow: 0 8px 32px rgba(0, 0, 0, .20);
  opacity: 0;
  pointer-events: none;
  z-index: 10;
  transition: all .4s cubic-bezier(.27, .73, .5, 1.29);
}

.layanan-card-col:hover .layanan-float-img {
  opacity: 1;
  pointer-events: auto;
  transform: scale(1.12) rotate(-4deg);
}

.layanan-float-img.float-left {
  left: -80px;
  top: 10px;
  transform: translateY(30px) scale(.7);
}

.layanan-card-col:hover .float-left {
  transform: translateY(-30px) scale(1.12) rotate(-8deg);
}

.layanan-float-img.float-top {
  left: 50%;
  top: -70px;
  transform: translate(-50%, 40px) scale(.7);
}

.layanan-card-col:hover .float-top {
  transform: translate(-50%, -10px) scale(1.12) rotate(0deg);
}

.layanan-float-img.float-right {
  right: -80px;
  top: 30px;
  transform: translateY(30px) scale(.7);
}

.layanan-card-col:hover .float-right {
  transform: translateY(-30px) scale(1.12) rotate(8deg);
}

.marketing-hero {
  position: relative;
}

/* ===== Modal (tidak diubah, hanya gaya) ===== */
.layanan-pilihan-box {
  border-radius: 16px;
  padding: 2rem;
  overflow: hidden;
}

.service-image {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 50%;
  border: 4px solid var(--fade-grey-light-3);
  box-shadow: var(--light-box-shadow);
  margin: 0 auto;
}

.options-grid {
  display: grid;
  gap: 1rem;
  margin-top: 2rem;
}

.option-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: 1rem;
  background-color: var(--widget-grey);
  border-radius: 12px;
  border: 1px solid var(--border);
  transition: all .3s ease;
  color: var(--text);
}

.option-card:hover {
  transform: translateY(-4px);
  box-shadow: var(--light-box-shadow);
  border-color: var(--primary);
}

.option-icon-wrapper {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 48px;
  width: 48px;
  min-width: 48px;
  border-radius: 50%;
  font-size: 1.25rem;
}

.option-icon-wrapper.is-info {
  background-color: var(--info-light-38);
  color: var(--info);
}

.option-icon-wrapper.is-primary {
  background-color: var(--primary-light-38);
  color: var(--primary);
}

.option-icon-wrapper.is-success {
  background-color: var(--success-light-38);
  color: var(--success);
}

.option-content p {
  font-size: .9rem;
  color: var(--light-text);
}

/* ===== DARK MODE FIXES ===== */
.is-dark {
  .layanan-page {
    background:
      radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .14), transparent 60%),
      linear-gradient(#0b1220, #0b1220);
  }

  .banner-overlay {
    background: linear-gradient(to bottom, rgba(0, 0, 0, .35), rgba(0, 0, 0, .65));
  }

  .wave-fill {
    fill: #0b1220;
  }

  .layanan-card {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
    box-shadow: 0 10px 28px rgba(0, 0, 0, .35);
  }

  .layanan-float-img {
    box-shadow: 0 10px 32px rgba(0, 0, 0, .5);
  }

  .layanan-pilihan-box {
    background: rgba(255, 255, 255, .04);
    border: 1px solid rgba(255, 255, 255, .12);
  }
}

/* ===== Mobile tweaks utk banner ===== */
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
}

@media (max-width: 420px) {
  .banner-img {
    height: 56vh;
  }

  .banner-glass {
    bottom: 92px;
  }

  .glass-title {
    font-size: clamp(22px, 8vw, 28px);
  }
}

/* Responsif modal */
@media (max-width: 767px) {
  .layanan-pilihan-box {
    padding: 1.5rem;
  }

  .service-image {
    width: 100px;
    height: 100px;
  }
}
</style>
