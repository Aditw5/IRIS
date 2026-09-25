<script setup lang="ts">
import { useHead } from '@vueuse/head'
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { publicFileUrl } from '/@src/utils/publicFileUrl'
import { onceImageErrored } from '/@src/utils/via-placeholder'

useHead({ title: 'ULAB - Rincian Layanan' })

type MappingService = {
  id: number
  kategori: string
  objectlingkupfk: number
  namalayanan: string
  merktipe: string
  gambar: string | null
  gambar_url: string | null
  lingkup: string
}

type MappingSummary = {
  total: number
  kan: number
  non_kan: number
  lingkup: string[]
}

const route = useRoute()
const serviceScopes = [
  {
    id: 'kelistrikan',
    title: 'Kelistrikan',
    description: 'Daftar alat kalibrasi untuk ruang lingkup kelistrikan.',
    image: '/layanan1.jpg',
    icon: 'feather:zap',
  },
  {
    id: 'tekanan',
    title: 'Tekanan',
    description: 'Daftar alat kalibrasi untuk ruang lingkup tekanan.',
    image: '/layanan4.jpg',
    icon: 'feather:activity',
  },
  {
    id: 'suhu',
    title: 'Suhu',
    description: 'Daftar alat kalibrasi untuk ruang lingkup suhu dan kelembaban.',
    image: '/layanan7.jpg',
    icon: 'feather:thermometer',
  },
  {
    id: 'repair',
    title: 'Repair',
    description: 'Daftar alat dan layanan repair yang aktif ditampilkan.',
    image: '/layanan15.jpg',
    icon: 'feather:tool',
  },
  {
    id: 'vibrasi',
    title: 'Vibrasi',
    description: 'Daftar alat kalibrasi untuk ruang lingkup vibrasi.',
    image: '/layanan10.jpg',
    icon: 'feather:radio',
  },
  {
    id: 'dimensi',
    title: 'Dimensi dan Gaya',
    description: 'Daftar alat kalibrasi untuk ruang lingkup dimensi dan gaya.',
    image: '/layanan12.jpg',
    icon: 'feather:box',
  },
]

const scopeParam = computed(() => {
  const value = route.params.scope
  return Array.isArray(value)
    ? String(value[0] || '').toLowerCase()
    : String(value || '').toLowerCase()
})

const humanizeScope = (value: string) =>
  value
    .replace(/[-_]/g, ' ')
    .replace(/\s+/g, ' ')
    .trim()
    .replace(/\b\w/g, (char) => char.toUpperCase())

const mappedServices = ref<MappingService[]>([])
const summary = ref<MappingSummary>({
  total: 0,
  kan: 0,
  non_kan: 0,
  lingkup: [],
})
const search = ref('')
const kategoriFilter = ref('')
const isLoading = ref(false)
const loadError = ref('')

const currentScope = computed(() => {
  return serviceScopes.find((service) => service.id === scopeParam.value) || {
    id: scopeParam.value,
    title: humanizeScope(scopeParam.value || 'layanan'),
    description: 'Daftar alat layanan U-LAB berdasarkan mapping aktif.',
    image: '/layanan.jpg',
    icon: 'feather:grid',
  }
})

const categoryOptions = computed(() =>
  Array.from(new Set(mappedServices.value.map((item) => item.kategori).filter(Boolean))).sort()
)

const visibleServices = computed(() => {
  const keyword = search.value.trim().toLowerCase()
  return mappedServices.value.filter((item) => {
    const matchCategory = !kategoriFilter.value || item.kategori === kategoriFilter.value
    const matchKeyword = !keyword || [
      item.namalayanan,
      item.merktipe,
      item.lingkup,
      item.kategori,
    ].some((value) => String(value || '').toLowerCase().includes(keyword))

    return matchCategory && matchKeyword
  })
})

function buildImageSrc(item: MappingService) {
  if (item.gambar_url) return item.gambar_url
  if (item.gambar) return publicFileUrl('mapping-layanan', item.gambar)
  return currentScope.value.image
}

async function loadMappingServices() {
  isLoading.value = true
  loadError.value = ''

  try {
    const params = new URLSearchParams()
    params.append('scope', scopeParam.value)

    const result: any = await useApi().get(`/get-landing-mapping-layanan?${params.toString()}`)
    mappedServices.value = result?.data || []
    summary.value = {
      total: Number(result?.summary?.total || 0),
      kan: Number(result?.summary?.kan || 0),
      non_kan: Number(result?.summary?.non_kan || 0),
      lingkup: result?.summary?.lingkup || [],
    }
  } catch (error) {
    mappedServices.value = []
    summary.value = { total: 0, kan: 0, non_kan: 0, lingkup: [] }
    loadError.value = 'Data alat layanan belum bisa dimuat.'
  } finally {
    isLoading.value = false
  }
}

const onScroll = () => {
  const y = window.scrollY || 0
  document.documentElement.style.setProperty('--scrollY', String(y))
}

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  onScroll()
  loadMappingServices()
})

onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
})

watch(scopeParam, () => {
  search.value = ''
  kategoriFilter.value = ''
  loadMappingServices()
})
</script>

<template>
  <MinimalLayout theme="light">
    <div class="landing-page-wrapper layanan-detail-page">
      <LandingNavigation />

      <section class="detail-hero">
        <img :src="currentScope.image" :alt="currentScope.title" class="detail-hero-image" />
        <div class="detail-hero-overlay"></div>

        <div class="container detail-hero-content">
          <RouterLink to="/layanan" class="back-link">
            <i class="iconify" data-icon="feather:arrow-left"></i>
            <span>Kembali ke Layanan</span>
          </RouterLink>

          <div class="detail-label">
            <i class="iconify" :data-icon="currentScope.icon"></i>
            Rincian Layanan
          </div>
          <h1>{{ currentScope.title }}</h1>
          <p>{{ currentScope.description }}</p>

          <div class="scope-stats">
            <div>
              <strong>{{ summary.total }}</strong>
              <span>Alat Aktif</span>
            </div>
            <div>
              <strong>{{ summary.kan }}</strong>
              <span>KAN</span>
            </div>
            <div>
              <strong>{{ summary.non_kan }}</strong>
              <span>Non KAN</span>
            </div>
          </div>
        </div>

        <svg class="detail-wave" viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">
          <path d="M0,60 C260,112 460,16 720,58 C980,100 1180,26 1440,70 L1440,120 L0,120 Z" />
        </svg>
      </section>

      <main class="section layanan-detail-section">
        <div class="container">
          <div class="detail-toolbar">
            <div class="toolbar-heading">
              <!-- <span class="eyebrow">DATA MAPPING LAYANAN</span> -->
              <h2>Alat yang Tersedia</h2>
              <p v-if="summary.lingkup.length">
                Ruang lingkup:
                <strong>{{ summary.lingkup.join(', ') }}</strong>
              </p>
            </div>

            <div class="toolbar-controls">
              <label class="search-control">
                <i class="iconify" data-icon="feather:search"></i>
                <input v-model="search" type="search" placeholder="Cari nama alat atau merk" />
              </label>

              <div class="select is-fullwidth">
                <select v-model="kategoriFilter">
                  <option value="">Semua Kategori</option>
                  <option v-for="category in categoryOptions" :key="category" :value="category">
                    {{ category }}
                  </option>
                </select>
              </div>
            </div>
          </div>

          <div v-if="isLoading" class="mapping-grid">
            <article v-for="index in 6" :key="index" class="mapping-card is-loading-card">
              <div class="loading-image"></div>
              <div class="loading-line is-short"></div>
              <div class="loading-line"></div>
              <div class="loading-line"></div>
            </article>
          </div>

          <div v-else-if="loadError" class="detail-empty-state">
            <i class="iconify" data-icon="feather:alert-circle"></i>
            <h3>{{ loadError }}</h3>
            <button class="button is-primary" @click="loadMappingServices">Muat Ulang</button>
          </div>

          <div v-else-if="visibleServices.length" class="mapping-grid">
            <article v-for="item in visibleServices" :key="item.id" class="mapping-card">
              <div class="mapping-image">
                <img
                  :src="buildImageSrc(item)"
                  :alt="item.namalayanan"
                  loading="lazy"
                  @error.once="(event) => onceImageErrored(event, '420x280')"
                />
              </div>

              <div class="mapping-content">
                <div class="mapping-tags">
                  <span class="tag" :class="item.kategori === 'KAN' ? 'is-success is-light' : 'is-warning is-light'">
                    {{ item.kategori }}
                  </span>
                  <span class="tag is-info is-light">{{ item.lingkup }}</span>
                </div>

                <h2>{{ item.namalayanan }}</h2>
                <dl>
                  <div>
                    <dt>Merk / Tipe</dt>
                    <dd>{{ item.merktipe }}</dd>
                  </div>
                  <div>
                    <dt>Ruang Lingkup</dt>
                    <dd>{{ item.lingkup }}</dd>
                  </div>
                </dl>
              </div>
            </article>
          </div>

          <div v-else class="detail-empty-state">
            <i class="iconify" data-icon="feather:inbox"></i>
            <h3>Belum ada alat yang tampil untuk lingkup ini.</h3>
            <p>Data akan muncul saat mapping aktif dan status tampil layanan sudah diaktifkan.</p>
          </div>
        </div>
      </main>

      <LandingFooter />
    </div>
  </MinimalLayout>
</template>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/_demo/landing';

.layanan-detail-page {
  min-height: 100vh;
  background:
    radial-gradient(900px 420px at 18% 6%, rgba(34, 197, 94, 0.08), transparent 60%),
    radial-gradient(760px 360px at 90% 10%, rgba(14, 165, 233, 0.1), transparent 58%),
    linear-gradient(#f8fafc, #eef4f8);
}

.detail-hero {
  position: relative;
  width: 100vw;
  min-height: clamp(320px, 38vw, 470px);
  margin-left: calc(50% - 50vw);
  margin-right: calc(50% - 50vw);
  overflow: hidden;
  isolation: isolate;
}

.detail-hero-image {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  object-fit: cover;
  object-position: center;
  transform: translateY(calc(var(--scrollY, 0) * 0.12px)) scale(1.06);
}

.detail-hero-overlay {
  position: absolute;
  inset: 0;
  background:
    linear-gradient(90deg, rgba(8, 18, 34, 0.82), rgba(8, 18, 34, 0.5) 48%, rgba(8, 18, 34, 0.32)),
    radial-gradient(900px 360px at 12% 12%, rgba(37, 99, 235, 0.36), transparent 62%);
  pointer-events: none;
}

.detail-hero-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  justify-content: center;
  min-height: clamp(320px, 38vw, 470px);
  padding-top: 74px;
  padding-bottom: 78px;
  color: #fff;
}

.back-link,
.detail-label {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.back-link {
  margin-bottom: 1.2rem;
  color: #dbeafe;
  font-weight: 800;
  transition: color 0.2s ease, transform 0.2s ease;

  &:hover {
    color: #fff;
    transform: translateX(-3px);
  }
}

.detail-label {
  padding: 0.42rem 0.8rem;
  border: 1px solid rgba(255, 255, 255, 0.34);
  border-radius: 999px;
  background: rgba(255, 255, 255, 0.14);
  backdrop-filter: blur(8px);
  color: #eff6ff;
  font-size: 0.78rem;
  font-weight: 800;
  text-transform: uppercase;
}

.detail-hero-content h1 {
  max-width: 760px;
  margin: 0.85rem 0 0.7rem;
  color: #fff;
  font-size: clamp(2.2rem, 6vw, 4.9rem);
  font-weight: 900;
  line-height: 1;
}

.detail-hero-content p {
  max-width: 620px;
  color: rgba(255, 255, 255, 0.86);
  font-size: clamp(1rem, 2vw, 1.2rem);
}

.scope-stats {
  display: grid;
  grid-template-columns: repeat(3, minmax(104px, 1fr));
  gap: 0.75rem;
  width: min(520px, 100%);
  margin-top: 1.4rem;
}

.scope-stats div {
  padding: 0.95rem 1rem;
  border: 1px solid rgba(255, 255, 255, 0.22);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.13);
  backdrop-filter: blur(10px);
}

.scope-stats strong,
.scope-stats span {
  display: block;
}

.scope-stats strong {
  color: #fff;
  font-size: 1.55rem;
  line-height: 1;
}

.scope-stats span {
  margin-top: 0.35rem;
  color: rgba(255, 255, 255, 0.78);
  font-size: 0.78rem;
  font-weight: 700;
}

.detail-wave {
  position: absolute;
  right: 0;
  bottom: -1px;
  left: 0;
  z-index: 3;
  width: 100%;
  height: 86px;

  path {
    fill: #f8fafc;
  }
}

.layanan-detail-section {
  padding-top: 2rem;
}

.detail-toolbar {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(320px, 520px);
  gap: 1.5rem;
  align-items: end;
  margin-bottom: 1.5rem;
}

.toolbar-heading h2 {
  margin: 0.2rem 0 0.35rem;
  color: var(--dark-text);
  font-size: clamp(1.65rem, 3vw, 2.4rem);
  font-weight: 900;
}

.toolbar-heading p {
  color: var(--light-text);
}

.eyebrow {
  color: var(--primary);
  font-size: 0.72rem;
  font-weight: 900;
  letter-spacing: 0.12em;
}

.toolbar-controls {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 170px;
  gap: 0.75rem;
}

.search-control {
  display: flex;
  align-items: center;
  gap: 0.6rem;
  height: 42px;
  padding: 0 0.85rem;
  border: 1px solid var(--border);
  border-radius: 8px;
  background: var(--white);
  color: var(--light-text);
  box-shadow: 0 8px 22px rgba(15, 23, 42, 0.05);

  input {
    width: 100%;
    min-width: 0;
    border: 0;
    outline: 0;
    background: transparent;
    color: var(--dark-text);
    font: inherit;
  }
}

.mapping-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 1.1rem;
}

.mapping-card {
  display: flex;
  min-width: 0;
  flex-direction: column;
  overflow: hidden;
  border: 1px solid rgba(148, 163, 184, 0.24);
  border-radius: 8px;
  background: var(--white);
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
  transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s ease;

  &:hover {
    border-color: rgba(37, 99, 235, 0.28);
    box-shadow: 0 22px 48px rgba(15, 23, 42, 0.12);
    transform: translateY(-4px);
  }
}

.mapping-image {
  width: 100%;
  aspect-ratio: 4 / 3;
  background: #dbe7ef;

  img {
    display: block;
    width: 100%;
    height: 100%;
    object-fit: cover;
  }
}

.mapping-content {
  display: flex;
  min-height: 224px;
  flex: 1;
  flex-direction: column;
  padding: 1rem;
}

.mapping-tags {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  margin-bottom: 0.85rem;
}

.mapping-content h2 {
  margin: 0 0 0.9rem;
  color: var(--dark-text);
  font-size: 1.1rem;
  font-weight: 900;
  line-height: 1.25;
}

.mapping-content dl {
  display: grid;
  gap: 0.7rem;
  margin: auto 0 0;
}

.mapping-content dl div {
  padding-top: 0.7rem;
  border-top: 1px solid rgba(148, 163, 184, 0.22);
}

.mapping-content dt {
  color: var(--light-text);
  font-size: 0.72rem;
  font-weight: 800;
  text-transform: uppercase;
}

.mapping-content dd {
  margin: 0.18rem 0 0;
  color: var(--dark-text);
  font-weight: 700;
  line-height: 1.35;
}

.detail-empty-state {
  display: grid;
  min-height: 280px;
  place-items: center;
  padding: 2rem;
  border: 1px dashed rgba(148, 163, 184, 0.6);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.72);
  text-align: center;

  .iconify {
    color: var(--primary);
    font-size: 2.4rem;
  }

  h3 {
    margin: 0.75rem 0 0.25rem;
    color: var(--dark-text);
    font-size: 1.2rem;
    font-weight: 900;
  }

  p {
    max-width: 460px;
    color: var(--light-text);
  }
}

.is-loading-card {
  padding-bottom: 1rem;
  pointer-events: none;
}

.loading-image,
.loading-line {
  position: relative;
  overflow: hidden;
  background: #e2e8f0;

  &::after {
    position: absolute;
    inset: 0;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.62), transparent);
    animation: shimmer 1.35s infinite;
    content: '';
    transform: translateX(-100%);
  }
}

.loading-image {
  aspect-ratio: 4 / 3;
}

.loading-line {
  height: 13px;
  margin: 1rem 1rem 0;
  border-radius: 999px;

  &.is-short {
    width: 46%;
  }
}

@keyframes shimmer {
  100% {
    transform: translateX(100%);
  }
}

.is-dark {
  .layanan-detail-page {
    background:
      radial-gradient(900px 420px at 18% 6%, rgba(34, 197, 94, 0.12), transparent 60%),
      radial-gradient(760px 360px at 90% 10%, rgba(14, 165, 233, 0.14), transparent 58%),
      linear-gradient(#0b1220, #101827);
  }

  .detail-wave path {
    fill: #0b1220;
  }

  .mapping-card,
  .search-control {
    border-color: rgba(255, 255, 255, 0.12);
    background: rgba(255, 255, 255, 0.04);
  }

  .mapping-image {
    background: rgba(255, 255, 255, 0.08);
  }

  .detail-empty-state {
    border-color: rgba(255, 255, 255, 0.16);
    background: rgba(255, 255, 255, 0.04);
  }

  .loading-image,
  .loading-line {
    background: rgba(255, 255, 255, 0.08);
  }
}

@media (max-width: 1024px) {
  .mapping-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .detail-toolbar {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 768px) {
  .detail-hero,
  .detail-hero-content {
    min-height: 520px;
  }

  .detail-hero-content {
    padding-top: 86px;
  }

  .scope-stats {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }

  .toolbar-controls {
    grid-template-columns: 1fr;
  }
}

@media (max-width: 560px) {
  .mapping-grid {
    grid-template-columns: 1fr;
  }

  .scope-stats {
    grid-template-columns: 1fr;
  }

  .detail-hero-content h1 {
    font-size: 2.4rem;
  }
}
</style>
