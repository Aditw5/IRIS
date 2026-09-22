<!-- ContactULab.vue -->
<script setup lang="ts">
import { onMounted, onBeforeUnmount, ref } from 'vue'
import { useHead } from '@vueuse/head'

useHead({ title: 'U-LAB | Contact Us' })

/* (PARALLAX DIMATIKAN—tidak mengubah foto) */
const onScroll = () => {
  /* no-op */
}

type ContactItem = {
  id: string
  label: string
  value: string
  href: string
  icon: string
  hint?: string
  copyText?: string
  targetBlank?: boolean
}

const contacts = ref<ContactItem[]>([
  {
    id: 'wa',
    label: 'WhatsApp',
    value: '0812-10000-284',
    href: 'https://wa.me/6281210000284',
    icon: 'mdi:whatsapp',
    hint: 'Fast response',
    copyText: '+62 812-10000-284',
    targetBlank: true,
  },
  {
    id: 'email',
    label: 'Email',
    value: 'lab.umro@gmail.com',
    href: 'mailto:lab.umro@gmail.com?subject=Inquiry%20U-LAB&body=Halo%20U-LAB,',
    icon: 'mdi:email-outline',
    hint: 'Untuk penawaran & pertanyaan teknis',
    copyText: 'lab.umro@gmail.com',
  },
  {
    id: 'ig',
    label: 'Instagram',
    value: '@plnnp.ulab',
    href: 'https://instagram.com/plnnp.ulab',
    icon: 'mdi:instagram',
    hint: 'Update kegiatan & behind the scene',
    copyText: '@plnnp.ulab',
    targetBlank: true,
  },
  {
    id: 'tiktok',
    label: 'TikTok',
    value: '@plnnp.ulab',
    href: 'https://www.tiktok.com/@plnnp.ulab',
    icon: 'logos:tiktok-icon', // stabil tampil
    hint: 'Konten edukasi & tips kalibrasi',
    copyText: '@plnnp.ulab',
    targetBlank: true,
  },
  {
    id: 'web',
    label: 'Website',
    value: 'www.ulabumro.id',
    href: 'https://www.ulabumro.id',
    icon: 'mdi:earth',
    hint: 'Profil, layanan & status pekerjaan',
    copyText: 'https://www.ulabumro.id',
    targetBlank: true,
  },
])

/* Copy helper */
const copiedId = ref<string | null>(null)
async function copyToClipboard(item: ContactItem) {
  try {
    await navigator.clipboard.writeText(item.copyText || item.value)
    copiedId.value = item.id
    setTimeout(() => (copiedId.value = null), 1400)
  } catch {
    alert('Gagal menyalin ke clipboard.')
  }
}

/* Reveal */
let io: IntersectionObserver | null = null
onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
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

      <!-- ===== BANNER: Foto apa adanya (NO overlay/animasi/crop) ===== -->
      <section class="banner-full">
        <picture>
          <source srcset="/profil2.jpg" media="(max-width: 768px)" />
          <img src="/profil.jpg" alt="Banner U-LAB" class="banner-img-pure" />
        </picture>

        <!-- Headline di atas gambar tanpa mengubah foto -->
        <div class="banner-title">
          <h1 class="glass-title">Contact <span>U-LAB</span></h1>
          <p class="glass-sub">Hubungi kami untuk info kalibrasi & perbaikan alat.</p>
        </div>

        <!-- gelombang dekoratif -->
        <svg class="banner-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
          <path class="wave-fill" d="M0,40 C240,100 480,0 720,40 C960,80 1200,20 1440,60 L1440,120 L0,120 Z" />
        </svg>
      </section>

      <!-- ===== CONTACT ===== -->
      <section id="contact" class="container reveal">
        <div class="card contact-card">
          <div class="contact-header">
            <div>
              <h3 class="sec-title">Contact Us</h3>
              <p class="contact-sub">Pilih kanal komunikasi yang nyaman untuk Anda.</p>
            </div>
            <div class="cta-strip">
              <a class="cta-btn primary" href="https://wa.me/6281210000284" target="_blank" rel="noopener">
                <i class="iconify" data-icon="mdi:whatsapp"></i> Chat WhatsApp
              </a>
              <a class="cta-btn ghost" href="mailto:lab.umro@gmail.com">
                <i class="iconify" data-icon="mdi:email-outline"></i> Kirim Email
              </a>
            </div>
          </div>

          <div class="contact-grid">
            <div v-for="(c, idx) in contacts" :key="c.id" class="contact-item" :style="{ '--delay': `${idx * 60}ms` }">
              <div class="ci-icon" :data-kind="c.id">
                <i class="iconify" :data-icon="c.icon"></i>
              </div>

              <div class="ci-body">
                <div class="ci-label">{{ c.label }}</div>
                <div class="ci-value">{{ c.value }}</div>
                <div v-if="c.hint" class="ci-hint">{{ c.hint }}</div>
              </div>

              <div class="ci-actions">
                <a :href="c.href" :target="c.targetBlank ? '_blank' : undefined" class="act-btn">
                  <i class="iconify" data-icon="mdi:open-in-new"></i> Open
                </a>
                <button class="act-btn" @click="copyToClipboard(c)">
                  <i class="iconify" :data-icon="copiedId === c.id ? 'mdi:check' : 'mdi:content-copy'"></i>
                  {{ copiedId === c.id ? 'Copied' : 'Copy' }}
                </button>
              </div>

              <span class="ci-glow" aria-hidden="true"></span>
            </div>
          </div>

          <div class="contact-foot">
            <i class="iconify" data-icon="mdi:shield-check-outline"></i>
            Akun resmi U-LAB. Mohon waspada terhadap penipuan.
          </div>
        </div>
      </section>

      <LandingFooter />
    </div>
  </MinimalLayout>
</template>

<style lang="scss">
/* ===== BG halaman ===== */
.profile-page {
  background:
    radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .08), transparent 60%),
    linear-gradient(#f8fafc, #f8fafc);
}

/* ===== Banner: foto tampil asli ===== */
.banner-full {
  position: relative;
  width: 100vw;
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
  overflow: visible;
}

.banner-img-pure {
  display: block;
  width: 100%;
  height: auto;
  /* tidak mengubah rasio */
  object-fit: contain;
  /* TIDAK crop */
  object-position: center;
  user-select: none;
  /* Tidak ada overlay/filter/animasi */
}

/* Title di atas foto tanpa memodifikasi fotonya */
.banner-title {
  position: absolute;
  left: 50%;
  top: min(62%, 380px);
  transform: translate(-50%, -50%);
  text-align: center;
  padding: 0 16px;
}

.glass-title {
  color: #ffffff;
  font-weight: 900;
  letter-spacing: .2px;
  font-size: clamp(30px, 5vw, 56px);
  /* font lebih tegas */
  line-height: 1.08;
  text-shadow: 0 14px 44px rgba(0, 0, 0, .42);
}

.glass-sub {
  color: #eef2ff;
  font-weight: 500;
  margin-top: 6px;
  text-shadow: 0 10px 36px rgba(0, 0, 0, .38);
}

.banner-wave {
  position: absolute;
  bottom: -1px;
  left: 0;
  width: 100%;
  height: 92px;
}

.wave-fill {
  fill: #f8fafc;
}

/* ===== Container & Card ===== */
.container {
  max-width: 1100px;
  margin: 22px auto;
  padding: 0 16px;
}

.card {
  background: #ffffff;
  border: 1px solid #e6e9ee;
  border-radius: 18px;
  box-shadow: 0 10px 38px rgba(2, 12, 27, .06);
}

.sec-title {
  font-weight: 900;
  font-size: 1.2rem;
  margin: 0;
}

.contact-card {
  padding: 16px 16px 18px;
}

.contact-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-end;
  gap: 14px;
  flex-wrap: wrap;
}

.contact-sub {
  color: #4a5a72;
  margin-top: 6px;
}

.cta-strip {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
}

.cta-btn {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 12px;
  border: 1px solid #e5ebf4;
  background: #fff;
  font-weight: 800;
  transition: transform .15s ease, box-shadow .2s ease, background .2s ease;
}

.cta-btn .iconify {
  font-size: 18px;
}

.cta-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 14px 26px rgba(2, 12, 27, .06);
}

.cta-btn.primary {
  background: #1f6bff;
  color: #fff;
  border-color: #1f6bff;
}

.cta-btn.ghost {
  background: #f7f9ff;
  color: #1d2b44;
  border-color: #dfe7ff;
}

/* ===== Grid kontak ===== */
.contact-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 14px;
}

@media (max-width: 820px) {
  .contact-grid {
    grid-template-columns: 1fr;
  }
}

.contact-item {
  position: relative;
  display: grid;
  grid-template-columns: 72px 1fr auto;
  gap: 14px;
  padding: 16px;
  border: 1px solid #e6ecf5;
  border-radius: 16px;
  background: linear-gradient(180deg, #ffffff, #fbfdff);
  box-shadow: 0 8px 26px rgba(2, 12, 27, .05);
  transform: translateY(12px);
  opacity: 0;
  animation: ciIn .55s cubic-bezier(.22, .7, .26, 1) forwards;
  animation-delay: var(--delay, 0ms);
}

@keyframes ciIn {
  to {
    transform: none;
    opacity: 1;
  }
}

/* Ikon lebih besar + warna brand */
.ci-icon {
  width: 64px;
  height: 64px;
  border-radius: 16px;
  display: grid;
  place-items: center;
  border: 1px solid #e3ebff;
  background: #eef4ff;
  color: #1f6bff;
}

.ci-icon .iconify {
  font-size: 34px;
}

.ci-icon[data-kind="wa"] {
  background: #e8fff1;
  border-color: #c6f5db;
  color: #23b26b;
}

.ci-icon[data-kind="email"] {
  background: #eef5ff;
  border-color: #dbe8ff;
  color: #1f6bff;
}

.ci-icon[data-kind="ig"] {
  background: #fff0fd;
  border-color: #ffd7f8;
  color: #c13584;
}

.ci-icon[data-kind="tiktok"] {
  background: #f3f4f6;
  border-color: #e5e7eb;
  color: #111827;
}

.ci-icon[data-kind="web"] {
  background: #eef6ff;
  border-color: #d9ecff;
  color: #2563eb;
}

.ci-body .ci-label {
  font-weight: 800;
  font-size: 1.06rem;
  letter-spacing: .1px;
}

.ci-body .ci-value {
  font-weight: 900;
  color: #1f2b3c;
  margin-top: 2px;
}

.ci-body .ci-hint {
  color: #6b7c94;
  font-size: .92rem;
  margin-top: 2px;
}

.ci-actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.act-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 8px 12px;
  border-radius: 10px;
  border: 1px solid #e4eaf4;
  background: #fff;
  font-weight: 800;
  transition: all .2s ease;
}

.act-btn .iconify {
  font-size: 18px;
}

.act-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 12px 20px rgba(2, 12, 27, .06);
  background: #1f6bff;
  color: #fff;
  border-color: #1f6bff;
}

/* Glow efek hover */
.ci-glow {
  position: absolute;
  inset: -40% -10%;
  background: radial-gradient(320px 130px at var(--mx, 50%) var(--my, 50%), rgba(59, 130, 246, .08), transparent 60%);
  opacity: 0;
  transition: opacity .25s ease;
  pointer-events: none;
}

.contact-item:hover .ci-glow {
  opacity: 1;
}

/* Foot note */
.contact-foot {
  margin-top: 14px;
  padding-top: 12px;
  border-top: 1px dashed #e3e9f4;
  color: #4a5a72;
  display: flex;
  align-items: center;
  gap: 8px;
}

/* ===== Reveal ===== */
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
  .profile-page {
    background:
      radial-gradient(1200px 600px at 30% -10%, rgba(59, 130, 246, .14), transparent 60%),
      linear-gradient(#0b1220, #0b1220);
  }

  .wave-fill {
    fill: #0b1220;
  }

  .glass-title,
  .glass-sub {
    color: #fff;
  }

  .card {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
  }

  .sec-title,
  .ci-body .ci-value {
    color: #e6edf8;
  }

  .contact-sub,
  .ci-body .ci-hint,
  .contact-foot {
    color: #a7b2c6;
  }

  .contact-item {
    background: rgba(255, 255, 255, .04);
    border-color: rgba(255, 255, 255, .12);
  }

  .act-btn {
    background: rgba(255, 255, 255, .06);
    border-color: rgba(255, 255, 255, .16);
  }
}

/* ===== Mobile ===== */
@media (max-width: 768px) {
  .banner-title {
    top: auto;
    bottom: 80px;
    transform: translate(-50%, 0);
  }

  .glass-title {
    font-size: clamp(24px, 7vw, 36px);
  }

  .glass-sub {
    display: none;
  }
}
</style>
