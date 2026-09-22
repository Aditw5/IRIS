<!-- AkreditasiRuangLingkup.vue -->
<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref, nextTick } from 'vue'
import { useHead } from '@vueuse/head'
import LandingFooter from '/@src/components/partials/landing/LandingFooter.vue'
import LandingNavigation from '/@src/components/partials/landing/LandingNavigation.vue'

useHead({ title: 'U-LAB | Akreditasi & Ruang Lingkup' })

/* Banner parallax */
const onScroll = () => {
  const y = window.scrollY || 0
  document.documentElement.style.setProperty('--scrollY', String(y))
}

const PDF1 = '/LK284.pdf'
const PDF2 = '/LK284_2.pdf'
const IMG_AKREDITASI = '/LK284.jpg'

// 18 halaman lampiran
const IMG_LAMPIRAN = Array.from({ length: 18 }, (_, i) =>
  `/LK284_2_page-${String(i + 1).padStart(4, '0')}.jpg`
)

/* Ref ke kotak scroll agar bisa direset ke atas */
const scrollBoxRef = ref<HTMLDivElement | null>(null)

onMounted(async () => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
  // Setelah DOM render, pastikan posisi scroll kotak di paling atas
  await nextTick()
  if (scrollBoxRef.value) {
    scrollBoxRef.value.scrollTop = 0
  }
})
onBeforeUnmount(() => window.removeEventListener('scroll', onScroll))
</script>

<template>
  <MinimalLayout theme="light">
    <div class="ak-page">
      <LandingNavigation />

      <!-- ===== Banner ===== -->
      <section class="banner-full">
        <picture>
          <source srcset="/profil2.jpg" media="(max-width: 768px)" />
          <img src="/profil.jpg" alt="Banner U-LAB" class="banner-img" fetchpriority="high" decoding="async" />
        </picture>

        <div class="banner-overlay"></div>

        <div class="banner-glass">
          <div class="glass-label">
            <i class="iconify" data-icon="feather:award"></i>
            UMRO Calibration Laboratory
          </div>
          <h1 class="glass-title">Akreditasi &amp; Ruang Lingkup</h1>
        </div>

        <svg class="banner-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
          <path class="wave-fill" d="M0,40 C240,100 480,0 720,40 C960,80 1200,20 1440,60 L1440,120 L0,120 Z" />
        </svg>
      </section>

      <!-- ===== Sertifikat (1 kotak, fit 100%) ===== -->
      <section class="ak-section">
        <div class="ak-header">
          <h2 class="title is-3">Sertifikat Akreditasi KAN (LK-284)</h2>
          <div class="ak-actions">
            <a class="button is-light is-small" :href="PDF1" download>
              <i class="iconify mr-1" data-icon="feather:download"></i> Download PDF
            </a>
            <a class="button is-primary is-small" :href="PDF1" target="_blank" rel="noopener">
              <i class="iconify mr-1" data-icon="feather:external-link"></i> Buka Tab Baru
            </a>
          </div>
        </div>

        <div class="doc-container">
          <figure class="doc-card">
            <img :src="IMG_AKREDITASI" alt="Sertifikat Akreditasi" class="doc-img" loading="lazy" />
          </figure>
        </div>
      </section>

      <!-- ===== Lampiran (scroll dalam 1 kotak) ===== -->
      <section class="ak-section">
        <div class="ak-header">
          <h2 class="title is-3">Lampiran Ruang Lingkup (LK-284)</h2>
          <div class="ak-actions">
            <a class="button is-light is-small" :href="PDF2" download>
              <i class="iconify mr-1" data-icon="feather:download"></i> Download PDF
            </a>
            <a class="button is-primary is-small" :href="PDF2" target="_blank" rel="noopener">
              <i class="iconify mr-1" data-icon="feather:external-link"></i> Buka Tab Baru
            </a>
          </div>
        </div>

        <p class="ak-note">Scroll ke bawah di dalam kotak untuk melihat semua halaman.</p>

        <div ref="scrollBoxRef" class="pdf-box scroll-box">
          <div class="doc-stack">
            <img v-for="(src, i) in IMG_LAMPIRAN" :key="i" :src="src" :alt="`Lampiran Halaman ${i + 1}`" class="doc-img"
              loading="lazy" />
          </div>
        </div>
      </section>

      <LandingFooter />
    </div>
  </MinimalLayout>
</template>

<style lang="scss">
/* ===== Page BG ===== */
.ak-page {
  background:
    radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .08), transparent 60%),
    linear-gradient(#f8fafc, #f8fafc);
}

/* ===== Banner ===== */
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
  background: linear-gradient(to bottom, rgba(0, 0, 0, .15), rgba(0, 0, 0, .35));
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
  background: rgba(255, 255, 255, .2);
  border: 1px solid rgba(255, 255, 255, .35);
}

.glass-title {
  color: #fff;
  font-weight: 900;
  font-size: clamp(28px, 5vw, 48px);
}

/* ===== Section ===== */
.ak-section {
  padding: 96px 16px 48px;
  max-width: 1100px;
  margin: 0 auto;
}

.ak-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
  flex-wrap: wrap;
}

.ak-actions {
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
}

.ak-note {
  margin: 8px 0 14px;
  color: #4b5563;
  font-size: .95rem;
}

/* ===== Containers ===== */
.doc-container {
  max-width: 1100px;
  margin: 0 auto;
}

.doc-card {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 8px 24px rgba(2, 12, 27, .08);
  padding: 12px;
}

.doc-img {
  width: 100%;
  height: auto;
  display: block;
  border-radius: 8px;
  background: #f9fafb;
}

/* Kotak scroll internal untuk lampiran */
.pdf-box {
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow: 0 8px 24px rgba(2, 12, 27, .08);
  padding: 12px;
}

.scroll-box {
  max-height: 90vh;
  /* tinggi tetap */
  overflow-y: auto;
  /* scroll di dalam kotak */
  overflow-anchor: none;
  /* cegah “loncat” saat gambar load */
  overscroll-behavior: contain;
}

.doc-stack {
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* Dark mode */
.is-dark {
  .ak-page {
    background:
      radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .14), transparent 60%),
      linear-gradient(#0b1220, #0b1220);
  }

  .pdf-box,
  .doc-card {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
    box-shadow: 0 14px 30px rgba(0, 0, 0, .45);
  }

  .ak-note {
    color: #9aa7c7;
  }
}

/* Mobile */
@media (max-width: 768px) {
  .banner-img {
    height: min(52vh, 420px);
    animation: none;
    transform: none;
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

  .ak-section {
    padding-top: 84px;
  }

  .ak-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .scroll-box {
    max-height: 80vh;
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
</style>
