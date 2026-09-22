<!-- ProfilULab.vue -->
<script setup lang="ts">
import { onMounted, onBeforeUnmount } from 'vue'
import { useHead } from '@vueuse/head'

useHead({ title: 'U-LAB | Profil Perusahaan' })

const stats = [
  { icon: 'feather:calendar', title: 'Sejak 2018', sub: 'Penerapan ISO/IEC 17025' },
  { icon: 'feather:award', title: '2 Sep 2019', sub: 'Akreditasi KAN' },
  { icon: 'feather:hash', title: 'LK-284-IDN', sub: 'Nomor Akreditasi' },
  { icon: 'feather:briefcase', title: 'UPHB – PT PJB', sub: 'Unit' },
]

const chips = [
  { label: 'Kelistrikan', note: 'Terakreditasi' },
  { label: 'Tekanan', note: 'PRL 2020' },
  { label: 'Suhu', note: 'PRL 2020' },
]

const timeline = [
  { year: '2018', title: 'Penerapan ISO/IEC 17025:2017', desc: 'U-LAB mulai menerapkan SNI ISO/IEC 17025:2017 secara konsisten.' },
  { year: '2019', title: 'Akreditasi KAN (LK-284-IDN)', desc: 'Resmi terakreditasi KAN per 2 September 2019.' },
  { year: '2020', title: 'Perluasan Ruang Lingkup (PRL)', desc: 'Perluasan lingkup untuk Tekanan dan Suhu.' },
]

/* Parallax & reveal */
let io: IntersectionObserver | null = null
const onScroll = () => {
  const y = window.scrollY || 0
  document.documentElement.style.setProperty('--scrollY', String(y))
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
  io = new IntersectionObserver((entries) => {
    for (const e of entries) {
      if (e.isIntersecting) {
        e.target.classList.add('in')
        io?.unobserve(e.target)
      }
    }
  }, { threshold: 0.12 })
  document.querySelectorAll('.reveal').forEach((el) => io?.observe(el))
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  io?.disconnect()
})
</script>

<template>
  <MinimalLayout theme="light">
    <div class="profile-page">
      <LandingNavigation />

      <!-- ===== FULL-BLEED BANNER ===== -->
      <section class="banner-full">
        <picture>
          <!-- Mobile -->
          <source srcset="/profil2.jpg" media="(max-width: 768px)" />
          <!-- Desktop (fallback) -->
          <img src="/profil.jpg" alt="Banner Profil U-LAB" class="banner-img" fetchpriority="high" decoding="async" />
        </picture>

        <div class="banner-overlay"></div>

        <div class="banner-glass">
          <div class="glass-label">
            <i class="iconify" data-icon="feather:shield"></i>
            UMRO Calibration Laboratory
          </div>
          <h1 class="glass-title">Profil <span>U-LAB</span></h1>
          <p class="glass-sub">
            Laboratorium Kalibrasi pertama di PT PLN Nusantara Power (PT PLN NP) yang konsisten menerapkan
            <b>SNI ISO/IEC 17025:2017</b>.
          </p>
        </div>

        <!-- gelombang dekoratif di bawah banner -->
        <svg class="banner-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
          <path class="wave-fill" d="M0,40 C240,100 480,0 720,40 C960,80 1200,20 1440,60 L1440,120 L0,120 Z" />
        </svg>

        <a href="#content" class="scroll-cue" aria-label="Scroll ke konten">
          <i class="iconify" data-icon="feather:chevrons-down"></i>
        </a>
      </section>

      <div id="content"></div>

      <!-- ===== STATS ===== -->
      <section class="container reveal">
        <div class="stats-grid">
          <div v-for="(s, i) in stats" :key="s.title" class="stat-card" :style="{ '--d': `${i * 70}ms` }">
            <div class="icon-badge"><i class="iconify" :data-icon="s.icon"></i></div>
            <div class="stat-title">{{ s.title }}</div>
            <div class="stat-sub">{{ s.sub }}</div>
          </div>
        </div>
      </section>

      <!-- ===== ABOUT ===== -->
      <section class="container reveal">
        <div class="card about-card">
          <h3 class="sec-title">Tentang U-LAB</h3>

          <p>
            <b>U-LAB</b> merupakan singkatan dari <b>UMRO Calibration Laboratory</b> yang menerapkan
            <b>ISO/IEC 17025:2017</b> sejak tahun 2018 dan mendapat
            <b>Akreditasi dari Komite Akreditasi Nasional (KAN)</b> pada <b>2 September 2019</b> dengan Nomor
            <b>LK-284-IDN</b>. U-LAB merupakan Laboratorium Kalibrasi pertama di
            <b>PT PLN Nusantara Power (PT PLN NP)</b> yang dengan konsisten menerapkan
            <b>SNI ISO/IEC 17025:2017</b>.
          </p>

          <p>
            Organisasi U-LAB berada di bawah <b>Unit Pelayanan Pemeliharaan Wilayah Barat (UPHB) PT PJB</b>
            dengan Manajemen Puncak yaitu <b>General Manager (GM) UPHB</b> dan Wakil Manajemen Puncak yaitu
            <b>Manajer RenBinTek UPHB</b>.
          </p>

          <p>
            Saat ini U-LAB telah memiliki <b>Lingkup Kelistrikan</b> yang telah diakreditasi dan pada tahun 2020
            melakukan <b>Perluasan Ruang Lingkup (PRL)</b> untuk <b>Tekanan</b> dan <b>Suhu</b>. Sejalan dengan
            meningkatnya permintaan kalibrasi, U-LAB terus melakukan kajian kebutuhan PRL di PJB Group untuk
            berkontribusi pada <b>efisiensi biaya pemeliharaan</b> alat pembangkitan listrik.
          </p>

          <p>
            Permintaan dari luar PJB Group mendorong U-LAB melakukan improvement layanan di luar internal perusahaan.
            Layanan yang <b>Cepat</b>, <b>Tepat</b>, <b>Akurat</b>, <b>Dapat Dipercaya</b>, dan <b>Murah</b> menjadi
            moto kami untuk melayani di pasar global. Berkat pengakuan dari <b>KAN</b>, Laboratorium U-LAB diakui
            secara <b>Nasional</b> dan <b>Internasional</b> karena KAN merupakan member <b>ILAC MRA</b>. Sertifikat
            U-LAB dapat diterima oleh seluruh negara member ILAC MRA.
          </p>

          <div class="chip-row">
            <span v-for="c in chips" :key="c.label" class="chip">
              {{ c.label }} <small>{{ c.note }}</small>
            </span>
            <span class="chip is-accredit">
              <i class="iconify" data-icon="feather:award"></i>
              Akreditasi KAN <small>LK-284-IDN • Member ILAC MRA</small>
            </span>
          </div>
        </div>
      </section>

      <!-- ===== VALUES / MOTO ===== -->
      <section class="container reveal">
        <div class="card values-card">
          <h3 class="sec-title">Nilai &amp; Moto U-LAB</h3>

          <div class="values-grid">
            <div class="value-card" style="--h: 0">
              <div class="value-icon"><i class="iconify" data-icon="feather:zap"></i></div>
              <h4 class="value-title">Cepat</h4>
              <p>Alur layanan ramping, SLA jelas, dan responsif terhadap kebutuhan pelanggan.</p>
            </div>

            <div class="value-card" style="--h: 1">
              <div class="value-icon"><i class="iconify" data-icon="feather:target"></i></div>
              <h4 class="value-title">Tepat</h4>
              <p>Kompetensi teknis dan pengendalian mutu memastikan hasil sesuai standar.</p>
            </div>

            <div class="value-card" style="--h: 2">
              <div class="value-icon"><i class="iconify" data-icon="feather:check-square"></i></div>
              <h4 class="value-title">Akurat</h4>
              <p>Tertelusur, terdokumentasi, dan diaudit berbasis SNI ISO/IEC 17025:2017.</p>
            </div>
          </div>

          <div class="motto-bar">
            <span class="tag ghost">MOTO KAMI</span>
            <span><b>ULAB</b> Cepat, Tepat, Akurat</span>
          </div>
        </div>
      </section>

      <!-- ===== TIMELINE ===== -->
      <section class="container reveal">
        <div class="card timeline-card">
          <h3 class="sec-title">Tonggak Pencapaian</h3>
          <div class="timeline">
            <div v-for="(t, i) in timeline" :key="t.title" class="tl-item">
              <div class="tl-line" :class="{ 'is-first': i === 0, 'is-last': i === timeline.length - 1 }"></div>
              <div class="tl-year">{{ t.year }}</div>
              <div class="tl-body">
                <div class="tl-title">{{ t.title }}</div>
                <div class="tl-desc">{{ t.desc }}</div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <LandingFooter />
    </div>
  </MinimalLayout>
</template>

<style lang="scss">
/* ===== Page BG ===== */
.profile-page {
  background:
    radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .08), transparent 60%),
    linear-gradient(#f8fafc, #f8fafc);
}

/* ===== FULL-BLEED BANNER ===== */
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
  display: block;
  user-select: none;

  /* Ken Burns + parallax (desktop) */
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
    linear-gradient(to bottom, rgba(0, 0, 0, 0.15), rgba(0, 0, 0, .35));
  mix-blend-mode: multiply;
  pointer-events: none;
}

/* Glass title on image */
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
  color: #ffffff;
  font-weight: 900;
  font-size: clamp(28px, 5vw, 48px);
  line-height: 1.1;
  text-shadow: 0 10px 40px rgba(0, 0, 0, .35);
}

.glass-title span {
  color: #ffffff;
}

.glass-sub {
  color: #e8eefb;
  max-width: 900px;
  text-shadow: 0 4px 28px rgba(0, 0, 0, .35);
}

/* Decorative wave bottom (pakai CSS, mudah di-theme) */
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

/* light */

/* Scroll cue */
.scroll-cue {
  position: absolute;
  left: 50%;
  bottom: 8px;
  transform: translateX(-50%);
  color: #fff;
  opacity: .85;
  animation: bob 1.6s ease-in-out infinite;
}

.scroll-cue:hover {
  opacity: 1;
}

@keyframes bob {

  0%,
  100% {
    transform: translate(-50%, 0);
  }

  50% {
    transform: translate(-50%, 8px);
  }
}

/* ===== Containers & Card base ===== */
.container {
  max-width: 1100px;
  margin: 22px auto;
  padding: 0 16px;
}

.card {
  background: #fff;
  border: 1px solid #e6e9ee;
  border-radius: 16px;
  box-shadow: 0 8px 30px rgba(2, 12, 27, .06);
}

.sec-title {
  font-weight: 800;
  font-size: 1.15rem;
  padding: 16px 16px 0;
  margin: 0 0 8px;
}

/* ===== Stats ===== */
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 14px;
}

@media (max-width:820px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width:520px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
}

.stat-card {
  padding: 16px;
  border-radius: 14px;
  background: linear-gradient(180deg, #ffffff, #fbfdff);
  border: 1px solid #e6ebf4;
  transform: translateY(10px);
  opacity: 0;
  animation: cardIn .6s cubic-bezier(.22, .7, .26, 1) forwards;
  animation-delay: var(--d, 0ms);
  transition: transform .25s ease, box-shadow .25s ease;
}

.stat-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 20px 40px rgba(2, 12, 27, .08);
}

@keyframes cardIn {
  to {
    transform: none;
    opacity: 1;
  }
}

.icon-badge {
  width: 36px;
  height: 36px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: conic-gradient(from 0deg, #eef4ff, #eef4ff), #eef4ff;
  border: 1px solid #e6ebf4;
  color: #3b82f6;
  margin-bottom: 8px;
}

.stat-title {
  font-weight: 900;
  font-size: 1.08rem;
}

.stat-sub {
  color: #6c7d95;
  font-size: .92rem;
}

/* ===== About ===== */
.about-card {
  padding: 12px 16px 16px;
}

.about-card p {
  color: #364255;
  line-height: 1.7;
  margin: 10px 0;
}

.chip-row {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 12px;
}

.chip {
  background: #f3f7ff;
  color: #203047;
  border: 1px solid #e6ecff;
  border-radius: 999px;
  padding: 6px 10px;
  font-weight: 700;
  font-size: .9rem;
}

.chip small {
  margin-left: 6px;
  font-weight: 800;
  color: #6d7b93;
  text-transform: uppercase;
  font-size: .72rem;
}

.chip.is-accredit {
  background: #eefaf4;
  border-color: #dff5e7;
  color: #185c39;
}

/* ===== Values / Moto ===== */
.values-card {
  padding: 12px 16px 16px;
}

.values-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 14px;
  margin-top: 6px;
  grid-auto-rows: 1fr;
}

@media (max-width:860px) {
  .values-grid {
    grid-template-columns: 1fr;
  }
}

.value-card {
  padding: 16px;
  background: #f8fbff;
  border: 1px solid #e6ebf4;
  border-radius: 14px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  overflow: hidden;
  transform: translateY(16px);
  opacity: 0;
  animation: fadeUp .6s ease forwards;
  animation-delay: calc(var(--h) * .12s + .2s);
}

@keyframes fadeUp {
  to {
    transform: none;
    opacity: 1;
  }
}

.value-icon {
  width: 40px;
  height: 40px;
  border-radius: 12px;
  display: grid;
  place-items: center;
  background: #eef4ff;
  color: #3467f1;
}

.value-title {
  font-weight: 900;
  font-size: 20px;
  line-height: 1.2;
  margin: 2px 0 6px;
}

@media (max-width:640px) {
  .value-title {
    font-size: 18px;
  }
}

.value-card p {
  color: #4b5b73;
  margin: 0;
}

.motto-bar {
  margin-top: 10px;
  padding: 10px 12px;
  border: 1px dashed #d8dee9;
  border-radius: 12px;
  background: #fff;
  display: flex;
  gap: 10px;
  align-items: center;
}

.tag.ghost {
  background: #f5f7fb;
  color: #5a6a85;
  border: 1px solid #e3e7ef;
  font-weight: 800;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: .75rem;
}

/* ===== Timeline ===== */
.timeline-card {
  padding: 12px 16px 18px;
}

.timeline {
  position: relative;
  margin-top: 8px;
}

.tl-item {
  display: grid;
  grid-template-columns: 84px 1fr;
  gap: 12px;
  position: relative;
  padding: 10px 0;
}

.tl-line {
  position: absolute;
  left: 38px;
  top: 0;
  bottom: 0;
  border-left: 2px dashed #d4dbe7;
}

.tl-line.is-first {
  top: 20px;
}

.tl-line.is-last {
  bottom: 20px;
}

.tl-year {
  font-weight: 900;
  color: #243042;
  display: grid;
  place-items: center;
  width: 76px;
  height: 40px;
  border-radius: 999px;
  background: #eef2f7;
  border: 1px solid #e5e9ef;
  z-index: 1;
}

.tl-body {
  padding-top: 2px;
}

.tl-title {
  font-weight: 800;
}

.tl-desc {
  color: #5a6a85;
}

/* ===== Reveal util ===== */
.reveal {
  opacity: 0;
  transform: translateY(18px);
  transition: transform .5s ease, opacity .5s ease;
}

.reveal.in {
  opacity: 1;
  transform: none;
}

/* ===== Dark mode ===== */
.is-dark {

  /* latar halaman gelap */
  .profile-page {
    background:
      radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .14), transparent 60%),
      linear-gradient(#0b1220, #0b1220);
  }

  /* wave ikut gelap */
  .wave-fill {
    fill: #0b1220;
  }

  /* overlay lebih kuat supaya teks kontras */
  .banner-overlay {
    background: linear-gradient(to bottom, rgba(0, 0, 0, .35), rgba(0, 0, 0, .65));
  }

  .glass-label {
    color: #eaf1ff;
    border-color: rgba(255, 255, 255, .35);
    background: linear-gradient(90deg, rgba(255, 255, 255, .12), rgba(255, 255, 255, .08));
  }

  .glass-title,
  .glass-sub {
    color: #fff;
  }

  /* kartu umum */
  .card {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
  }

  .sec-title {
    color: #e6edf8;
  }

  .about-card p,
  .tl-desc {
    color: #b9c6dc;
  }

  /* chip */
  .chip {
    background: rgba(59, 130, 246, .15);
    border-color: rgba(59, 130, 246, .2);
    color: #e6edf8;
  }

  .chip.is-accredit {
    background: rgba(16, 185, 129, .18);
    border-color: rgba(16, 185, 129, .24);
    color: #e6f6ee;
  }

  /* kartu values */
  .value-card {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
  }

  .motto-bar {
    background: rgba(255, 255, 255, .03);
    border-color: rgba(255, 255, 255, .16);
    color: #e6edf8;
  }

  /* ikon latar */
  .icon-badge,
  .value-icon {
    background: rgba(59, 130, 246, .15);
    color: #b7cdfc;
  }

  /* STAT CARD yang tadinya terang */
  .stat-card {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
  }

  .stat-title {
    color: #e6edf8;
  }

  .stat-sub {
    color: #a7b2c6;
  }

  /* timeline */
  .tl-year {
    background: rgba(255, 255, 255, .05);
    border-color: rgba(255, 255, 255, .12);
    color: #e6edf8;
  }

  .tl-line {
    border-color: rgba(255, 255, 255, .22);
  }
}

/* ===== MOBILE ===== */
@media (max-width: 768px) {
  .banner-img {
    height: min(52vh, 420px);
    animation: none;
    /* matikan Ken Burns di HP */
    transform: none;
    /* tanpa parallax di HP */
    object-position: 50% 50%;
  }

  .banner-glass {
    top: auto;
    bottom: 78px;
    transform: none;
    padding-inline: 12px;
    z-index: 2;
  }

  .glass-title {
    font-size: clamp(24px, 7vw, 34px);
    line-height: 1.15;
  }

  .glass-sub {
    display: none;
  }

  .glass-label {
    font-size: .78rem;
    padding: 4px 10px;
    backdrop-filter: blur(4px);
  }

  .banner-wave {
    height: 70px;
    z-index: 1;
  }
}

@media (max-width: 420px) {
  .banner-img {
    height: 56vh;
    object-position: 50% 50%;
  }

  .banner-glass {
    bottom: 92px;
  }

  .glass-title {
    font-size: clamp(22px, 8vw, 28px);
  }
}

/* Reduce motion */
@media (prefers-reduced-motion: reduce) {
  .banner-img {
    animation: none;
    transform: none;
  }
}
</style>
