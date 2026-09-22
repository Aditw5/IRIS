<!-- NavbarTransparent.vue -->
<script setup lang="ts">
import { ref, onMounted, onBeforeUnmount, watchEffect, computed, watch } from 'vue'
import { useRoute } from 'vue-router'
import { isLargeScreen } from '/@src/utils/responsive'
import VueScrollTo from 'vue-scrollto'
import { useDarkmode } from '/@src/stores/darkmode'
import { useApi } from '/@src/composable/useApi'
import QrcodeVue from 'qrcode.vue'

const route = useRoute()
const darkmode = useDarkmode()
const scrollTo = VueScrollTo.scrollTo
const androidAppUrl = 'https://play.google.com/store/apps/details?id=id.ulabumro.mobile&pcampaignid=web_share'

const isMobileNavOpen = ref(false)
const isAppDownloadOpen = ref(false)
const appDownloadNavRef = ref<HTMLElement | null>(null)
const mobileAppPopoverRef = ref<HTMLElement | null>(null)
const atTop = ref(true)
const onScroll = () => {
  atTop.value = window.scrollY <= 10
  closeAppDownload()
}
const closeAppDownload = () => { isAppDownloadOpen.value = false }
const toggleAppDownload = () => { isAppDownloadOpen.value = !isAppDownloadOpen.value }
const closeAppDownloadFromPointer = (event: PointerEvent) => {
  const target = event.target as Node | null

  if (!target) return
  if (appDownloadNavRef.value?.contains(target)) return
  if (mobileAppPopoverRef.value?.contains(target)) return

  closeAppDownload()
}

/* ===== NAV DINAMIS ===== */
type NavChild = { id: number; parent_id: number; namaitem: string; link: string; is_external: boolean; urut: number }
type NavParent = {
  id: number; namamenu: string; slug: string; link: string | null;
  has_dropdown: boolean; is_external: boolean; urut: number; children: NavChild[]
}

const defaultNavItems: NavParent[] = [
  { id: 1, namamenu: 'Layanan', slug: 'layanan', link: 'layanan', has_dropdown: false, is_external: false, urut: 1, children: [] },
  { id: 2, namamenu: 'Lokasi', slug: 'lokasi', link: 'index', has_dropdown: false, is_external: false, urut: 2, children: [] },
  { id: 3, namamenu: 'Tentang Kami', slug: 'tentang-kami', link: 'profil-ulab', has_dropdown: false, is_external: false, urut: 3, children: [] },
  {
    id: 4,
    namamenu: 'Informasi',
    slug: 'informasi',
    link: null,
    has_dropdown: true,
    is_external: false,
    urut: 4,
    children: [
      { id: 41, parent_id: 4, namaitem: 'Akreditasi', link: 'akreditasi', is_external: false, urut: 1 },
      { id: 42, parent_id: 4, namaitem: 'Layanan', link: 'layanan', is_external: false, urut: 2 },
    ],
  },
  { id: 5, namamenu: 'Kontak', slug: 'kontak', link: 'index', has_dropdown: false, is_external: false, urut: 5, children: [] },
]

const navItems = ref<NavParent[]>(defaultNavItems)
const loadingNav = ref(false)

const loadNav = async () => {
  try {
    loadingNav.value = true
    const resp = await useApi().get('/get-nav')
    const raw = (resp?.data?.data ?? resp?.data) as unknown
    if (Array.isArray(raw) && raw.length) {
      navItems.value = raw
    }
  } catch (e) {
    navItems.value = defaultNavItems
  } finally {
    loadingNav.value = false
  }
}

const mobileCloseAndScrollTop = () => { isMobileNavOpen.value = false; scrollTo('#app', 300) }

/* ====== STATE: buka/tutup dropdown mobile (default tertutup) ====== */
const expanded = ref<Record<number, boolean>>({})
const isOpen = (id: number) => !!expanded.value[id]
const toggleParent = (id: number) => {
  if (!isLargeScreen.value) expanded.value[id] = !expanded.value[id]
}

/* ====== HANYA HOME YANG BOLEH TRANSPARAN ====== */
const isHome = computed(() => {
  const name = (route.name as string | undefined)?.toLowerCase() || ''
  return route.path === '/' || name === 'index' || name === 'home'
})

/* Kelas nav final: kalau bukan Home => selalu solid */
const navClass = computed(() => {
  return 'is-solid'
})

/* Tutup menu ketika pindah halaman */
watch(() => route.fullPath, () => {
  isMobileNavOpen.value = false
  closeAppDownload()
})

onMounted(() => {
  window.addEventListener('scroll', onScroll, { passive: true })
  document.addEventListener('pointerdown', closeAppDownloadFromPointer, true)
  onScroll()
  window.setTimeout(() => {
    isAppDownloadOpen.value = true
  }, 450)
  loadNav()
})
onBeforeUnmount(() => {
  window.removeEventListener('scroll', onScroll)
  document.removeEventListener('pointerdown', closeAppDownloadFromPointer, true)
})
watchEffect(() => { if (isLargeScreen.value) isMobileNavOpen.value = false })
</script>

<template>
  <nav class="navbar is-fixed-top" :class="navClass" aria-label="main navigation">
    <div class="navbar-brand">
      <RouterLink to="/" class="navbar-item brand-lockup" @click.prevent="scrollTo('#app', 800)">
        <div class="brand-icon">
          <AnimatedLogoULAB width="58px" height="58px" />
        </div>
      </RouterLink>

      <a role="button" class="navbar-burger burger" :class="[isMobileNavOpen && 'is-active']" aria-label="menu"
        aria-expanded="false" tabindex="0" @keydown.space.prevent="isMobileNavOpen = !isMobileNavOpen"
        @click="isMobileNavOpen = !isMobileNavOpen">
        <span aria-hidden="true"></span><span aria-hidden="true"></span><span aria-hidden="true"></span>
      </a>
    </div>

    <div class="navbar-menu" :class="[isMobileNavOpen && 'is-active']">
      <div class="navbar-start">
        <div v-if="loadingNav" class="navbar-item">Loading menu…</div>

        <template v-for="p in navItems" :key="p.id">
          <!-- PARENT TANPA DROPDOWN -->
          <div v-if="!p.has_dropdown" class="navbar-item">
            <RouterLink v-if="!p.is_external && p.link" class="nav-link is-scroll" :to="{ name: p.link }"
              @click="mobileCloseAndScrollTop()">
              {{ p.namamenu }}
            </RouterLink>
            <a v-else-if="p.is_external && p.link" class="nav-link is-scroll" :href="p.link" target="_blank"
              rel="noopener" @click="mobileCloseAndScrollTop()">
              {{ p.namamenu }}
            </a>
            <span v-else class="nav-link">{{ p.namamenu }}</span>
          </div>

          <!-- PARENT DENGAN DROPDOWN -->
          <div v-else class="navbar-item has-dropdown"
            :class="[isLargeScreen ? 'is-hoverable' : '', isOpen(p.id) ? 'is-open' : '']">
            <a class="navbar-link" @click.prevent="toggleParent(p.id)">
              {{ p.namamenu }}
            </a>
            <div class="navbar-dropdown">
              <template v-for="c in p.children" :key="c.id">
                <RouterLink v-if="!c.is_external" class="navbar-item" :to="{ name: c.link }"
                  @click="mobileCloseAndScrollTop()">
                  {{ c.namaitem }}
                </RouterLink>
                <a v-else class="navbar-item" :href="c.link" target="_blank" rel="noopener"
                  @click="mobileCloseAndScrollTop()">
                  {{ c.namaitem }}
                </a>
              </template>
            </div>
          </div>
        </template>
      </div>

      <div class="navbar-end">
        <div
          ref="appDownloadNavRef"
          class="navbar-item app-download-nav"
          :class="{ 'is-open': isAppDownloadOpen }"
          @click.stop
        >
          <button
            type="button"
            class="app-download-trigger"
            aria-haspopup="dialog"
            :aria-expanded="isAppDownloadOpen"
            @click="toggleAppDownload"
          >
            <span class="app-download-icon">
              <i aria-hidden="true" class="iconify" data-icon="feather:smartphone"></i>
            </span>
            <span class="app-download-label">
              <strong>U-LAB Mobile</strong>
              <small>Android</small>
            </span>
          </button>

          <div class="app-download-popover" role="dialog" aria-label="Download U-LAB Mobile">
            <div class="app-popover-copy">
              <span>Download aplikasi Android</span>
              <strong>U-LAB Mobile</strong>
              <p>Scan QR atau buka Play Store untuk registrasi dan pantau layanan dari ponsel.</p>
            </div>

            <div class="app-popover-body">
              <div class="app-qr-box" aria-label="QR Code Play Store U-LAB Mobile">
                <QrcodeVue :value="androidAppUrl" :size="104" render-as="svg" />
              </div>

              <a
                class="playstore-action"
                :href="androidAppUrl"
                target="_blank"
                rel="noopener"
              >
                <img class="playstore-action-logo" src="/google-play-mark.png" alt="" loading="eager" decoding="async" />
                <span>Buka Play Store</span>
              </a>
            </div>
          </div>
        </div>

        <!-- THEME TOGGLE (pill) -->
        <div class="navbar-item is-theme-toggle">
          <label class="theme-toggle">
            <input id="navbar-night-toggle--daynight" v-model="darkmode.isDark" type="checkbox" />
            <span class="toggler">
              <span class="knob"></span>
              <span class="light"><i aria-hidden="true" class="iconify" data-icon="feather:sun"></i></span>
              <span class="dark"><i aria-hidden="true" class="iconify" data-icon="feather:moon"></i></span>
            </span>
          </label>
        </div>

        <div class="navbar-item">
          <RouterLink :to="{ name: 'auth-login' }" class="nav-link auth-nav-link is-login">Masuk</RouterLink>
        </div>
        <div class="navbar-item">
          <VButton :to="{ name: 'auth-signup-1' }" color="primary" rounded raised><strong>Daftar</strong></VButton>
        </div>
      </div>
    </div>

    <Transition name="mobile-app-pop">
      <div
        ref="mobileAppPopoverRef"
        v-if="isAppDownloadOpen && !isMobileNavOpen"
        class="mobile-app-popover"
        role="dialog"
        aria-label="Download U-LAB Mobile"
        @click.stop
      >
        <span class="mobile-app-popover-icon">
          <i aria-hidden="true" class="iconify" data-icon="feather:smartphone"></i>
        </span>

        <div class="mobile-app-popover-copy">
          <strong>U-LAB Mobile</strong>
          <span>Android</span>
        </div>

        <a
          class="mobile-app-popover-action"
          :href="androidAppUrl"
          target="_blank"
          rel="noopener"
        >
          <img class="mobile-playstore-logo" src="/google-play-mark.png" alt="" loading="eager" decoding="async" />
          <span>Play Store</span>
        </a>

        <button
          type="button"
          class="mobile-app-popover-close"
          aria-label="Tutup popup U-LAB Mobile"
          @click="closeAppDownload"
        >
          <i aria-hidden="true" class="iconify" data-icon="feather:x"></i>
        </button>
      </div>
    </Transition>
  </nav>
</template>

<style lang="scss">
:root {
  --nav-bg: rgba(255, 255, 255, .96);
  --nav-text: #1f2937;
  --nav-border: #e5e7eb;
  --nav-shadow: 0 6px 24px rgba(2, 12, 27, .08);

  --dd-bg: rgba(255, 255, 255, .97);
  --dd-text: #0f172a;
  --dd-border: #e5e7eb;
  --dd-shadow: 0 18px 40px rgba(2, 12, 27, .18);

  --nav-font: .86rem;
  --dd-font: 1.10rem;
  --dd-radius: 20px;

  /* compact spacing */
  --dd-pad-y: 8px;
  --dd-pad-x: 8px;
  --dd-item-pad-y: 10px;
  --dd-item-pad-x: 16px;
  --dd-item-gap: 3px;
  --dd-line: 1.32;
}

html.is-dark {
  --nav-bg: rgba(5, 18, 42, .96);
  --nav-text: #e8f4ff;
  --nav-border: rgba(96, 165, 250, .20);
  --nav-shadow: 0 14px 32px rgba(0, 0, 0, .34);

  --dd-bg: rgba(8, 25, 54, .98);
  --dd-text: #e8f4ff;
  --dd-border: rgba(96, 165, 250, .20);
  --dd-shadow: 0 22px 44px rgba(0, 0, 0, .34);
}

/* base */
html body .navbar.is-fixed-top {
  --font: 'Montserrat', sans-serif;
  --font-alt: 'Montserrat', sans-serif;
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999 !important;
  min-height: 76px;
  height: 76px;
  padding-inline: clamp(18px, 4vw, 54px);
  font-family: 'Montserrat', sans-serif;
  transition: background-color .25s, box-shadow .25s, border-color .25s, color .25s;
}

.navbar .navbar-brand {
  min-height: 76px;
  align-items: center;
}

.navbar .brand-lockup {
  min-height: 76px;
  display: inline-flex;
  align-items: center;
  gap: 0;
  padding: 0;
  color: #0a2f6b !important;
}

.navbar .brand-icon {
  width: 58px;
  height: 58px;
  display: grid;
  place-items: center;
  overflow: hidden;
}

.navbar .brand-icon img {
  display: block;
  width: 58px;
  height: 58px;
  object-fit: contain;
  transform: scale(1.65);
  transform-origin: 50% 32%;
}

.navbar .brand-copy {
  display: flex;
  flex-direction: column;
  line-height: 1.05;
}

.navbar .brand-copy strong {
  color: #0a2f6b;
  font-size: .96rem;
  font-weight: 950;
  letter-spacing: .01em;
  white-space: nowrap;
}

.navbar .brand-copy span {
  margin-top: 4px;
  color: #426085;
  font-size: .63rem;
  font-weight: 800;
  white-space: nowrap;
}

.navbar .nav-link,
.navbar .navbar-link {
  color: inherit !important;
  text-decoration: none;
}

/* transparent (aktif HANYA di Home lewat :class="navClass") */
html body .navbar.is-fixed-top.is-transparent {
  background: transparent !important;
  border-bottom: 0 !important;
  box-shadow: none !important;
}

.navbar.is-fixed-top.is-transparent>.navbar-menu>.navbar-start>.navbar-item>.nav-link,
.navbar.is-fixed-top.is-transparent>.navbar-menu>.navbar-start>.navbar-item>.navbar-link,
.navbar.is-fixed-top.is-transparent>.navbar-menu>.navbar-end>.navbar-item>.nav-link,
.navbar.is-fixed-top.is-transparent>.navbar-menu>.navbar-end>.navbar-item>.navbar-link,
.navbar.is-fixed-top.is-transparent .navbar-burger {
  color: #fff !important;
}

.navbar.is-fixed-top.is-transparent>.navbar-menu a.nav-link:visited {
  color: #fff !important;
}

.navbar.is-fixed-top.is-transparent .navbar-burger span {
  background: #fff !important;
}

/* solid */
html body .navbar.is-fixed-top.is-solid {
  background: var(--nav-bg) !important;
  border-bottom: 1px solid var(--nav-border) !important;
  box-shadow: var(--nav-shadow) !important;
  backdrop-filter: saturate(120%) blur(10px);
}

html body .navbar.is-fixed-top.is-solid .navbar-item,
html body .navbar.is-fixed-top.is-solid .nav-link,
html body .navbar.is-fixed-top.is-solid .navbar-burger {
  color: var(--nav-text) !important;
}

html body .navbar.is-fixed-top.is-solid .navbar-burger span {
  background: var(--nav-text) !important;
}

html.is-dark body .navbar.is-fixed-top.is-solid .brand-lockup,
html.is-dark body .navbar.is-fixed-top.is-solid .brand-copy strong,
html.is-dark body .navbar.is-fixed-top.is-solid .navbar-item,
html.is-dark body .navbar.is-fixed-top.is-solid .nav-link,
html.is-dark body .navbar.is-fixed-top.is-solid .navbar-link {
  color: #e8f4ff !important;
}

html.is-dark body .navbar.is-fixed-top.is-solid .brand-copy span {
  color: #9dc5ee !important;
}

html.is-dark body .navbar.is-fixed-top.is-solid .navbar-dropdown .navbar-item {
  color: #e8f4ff !important;
}

/* top-level */
.navbar .navbar-item .nav-link,
.navbar .navbar-item>.navbar-link {
  font-family: var(--font-alt);
  font-size: var(--nav-font);
  font-weight: 800;
  letter-spacing: .02em;
  text-transform: uppercase;
  position: relative;
  display: inline-flex;
  align-items: center;
  gap: 8px;
}

.navbar .navbar-item .nav-link::before {
  content: '';
  position: absolute;
  left: 2px;
  bottom: -8px;
  height: 3px;
  width: 0;
  border-radius: 50px;
  background: currentColor;
  transition: width .28s cubic-bezier(.4, 0, .2, 1);
}

.navbar .navbar-item .nav-link:hover::before,
.navbar .navbar-item .nav-link.is-active::before {
  width: 50%;
}

.app-download-nav {
  position: relative;
  align-items: center;
}

.app-download-trigger {
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  gap: 9px;
  padding: 5px 12px 5px 6px;
  border: 1px solid rgba(255, 255, 255, .42);
  border-radius: 999px;
  background: rgba(15, 23, 42, .58);
  color: #fff;
  font-family: 'Roboto', var(--font, sans-serif);
  cursor: pointer;
  outline: 0;
  box-shadow: 0 10px 24px rgba(2, 6, 23, .24);
  backdrop-filter: blur(12px);
  transition: background .18s ease, border-color .18s ease, transform .18s ease, box-shadow .18s ease;
}

.app-download-trigger:hover,
.app-download-trigger:focus-visible {
  background: rgba(15, 23, 42, .74);
  border-color: rgba(255, 255, 255, .62);
  transform: translateY(-1px);
}

.app-download-icon {
  width: 30px;
  height: 30px;
  display: inline-grid;
  place-items: center;
  border-radius: 999px;
  color: #064bb8;
  background: #fff;
  box-shadow: inset 0 0 0 1px rgba(15, 23, 42, .06);
}

.app-download-icon svg {
  width: 16px;
  height: 16px;
}

.app-download-label {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  line-height: 1.05;
}

.app-download-label strong {
  font-size: .76rem;
  font-weight: 700;
  letter-spacing: .01em;
  color: #fff;
  text-shadow: 0 1px 8px rgba(0, 0, 0, .35);
  white-space: nowrap;
}

.app-download-label small {
  margin-top: 2px;
  color: rgba(255, 255, 255, .92);
  font-size: .64rem;
  font-weight: 700;
  letter-spacing: .04em;
  text-transform: none;
}

.navbar .navbar-start {
  flex: 1;
  justify-content: center;
}

.navbar .navbar-end {
  align-items: center;
}

.navbar .navbar-item {
  padding-inline: .65rem;
}

.auth-nav-link.is-login {
  min-height: 40px;
  padding: 0 18px;
  border: 2px solid #064bb8;
  border-radius: 8px;
  color: #064bb8 !important;
  background: #fff;
  font-family: 'Roboto', var(--font, sans-serif);
  font-weight: 700 !important;
  letter-spacing: .01em !important;
}

.auth-nav-link.is-login::before {
  display: none;
}

.navbar .navbar-menu .button {
  height: 42px;
  min-width: 84px;
  border-radius: 8px !important;
  border-color: #064bb8 !important;
  background: #064bb8 !important;
  color: #fff !important;
  font-family: 'Roboto', var(--font, sans-serif);
  font-weight: 700;
  letter-spacing: .01em;
  box-shadow: 0 12px 24px rgba(6, 75, 184, .22) !important;
}

.navbar .navbar-menu .button:hover,
.navbar .navbar-menu .button:focus-visible {
  border-color: #053d96 !important;
  background: #053d96 !important;
  color: #fff !important;
}

html body .navbar.is-fixed-top.is-solid .app-download-trigger {
  color: #0f172a;
  background: #f8fafc;
  border-color: rgba(15, 23, 42, .10);
  box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
}

html body .navbar.is-fixed-top.is-solid .app-download-trigger:hover,
html body .navbar.is-fixed-top.is-solid .app-download-trigger:focus-visible {
  background: #fff;
  border-color: rgba(14, 165, 233, .35);
  box-shadow: 0 14px 30px rgba(14, 165, 233, .16);
}

html body .navbar.is-fixed-top.is-solid .app-download-icon {
  color: #fff;
  background: #064bb8;
}

html body .navbar.is-fixed-top.is-solid .app-download-label small {
  color: #64748b;
}

html body .navbar.is-fixed-top.is-solid .app-download-label strong {
  color: #0f172a;
  text-shadow: none;
}

html.is-dark body .navbar.is-fixed-top.is-solid .app-download-trigger {
  color: #e8f4ff;
  background: rgba(12, 31, 65, .90);
  border-color: rgba(96, 165, 250, .26);
  box-shadow: 0 12px 28px rgba(0, 0, 0, .24);
}

html.is-dark body .navbar.is-fixed-top.is-solid .app-download-trigger:hover,
html.is-dark body .navbar.is-fixed-top.is-solid .app-download-trigger:focus-visible {
  background: rgba(17, 42, 86, .98);
  border-color: rgba(96, 165, 250, .44);
  box-shadow: 0 16px 34px rgba(0, 0, 0, .34);
}

html.is-dark body .navbar.is-fixed-top.is-solid .app-download-label strong {
  color: #f8fbff;
}

html.is-dark body .navbar.is-fixed-top.is-solid .app-download-label small {
  color: #a7c6e9;
}

html.is-dark body .navbar.is-fixed-top.is-solid .auth-nav-link.is-login {
  color: #bfdbfe !important;
  border-color: #0b62d6;
  background: rgba(6, 75, 184, .14);
}

.app-download-popover {
  position: absolute;
  top: calc(100% + 12px);
  left: 50%;
  right: auto;
  width: min(348px, calc(100vw - 24px));
  max-width: calc(100vw - 24px);
  padding: 13px;
  border: 1px solid rgba(148, 163, 184, .25);
  border-radius: 18px;
  background: rgba(255, 255, 255, .98);
  color: #0f172a;
  box-shadow: 0 22px 52px rgba(15, 23, 42, .22);
  opacity: 0;
  visibility: hidden;
  pointer-events: none;
  transform: translate(-50%, 8px);
  transition: opacity .16s ease, transform .18s ease, visibility 0s linear .18s;
}

.app-download-nav.is-open .app-download-popover {
  opacity: 1;
  visibility: visible;
  pointer-events: auto;
  transform: translate(-50%, 0);
  transition-delay: 0s;
}

.app-download-popover::before {
  content: '';
  position: absolute;
  top: -7px;
  left: 50%;
  width: 14px;
  height: 14px;
  border-left: 1px solid rgba(148, 163, 184, .25);
  border-top: 1px solid rgba(148, 163, 184, .25);
  background: #fff;
  transform: translateX(-50%) rotate(45deg);
}

.app-popover-copy span {
  display: block;
  color: #064bb8;
  font-size: .68rem;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.app-popover-copy strong {
  display: block;
  margin-top: 2px;
  color: #0f172a;
  font-family: 'Roboto', var(--font, sans-serif);
  font-size: 1rem;
  font-weight: 700;
}

.app-popover-copy p {
  margin: 6px 0 0;
  color: #64748b;
  font-size: .82rem;
  line-height: 1.45;
  font-weight: 400;
}

.app-popover-body {
  display: grid;
  grid-template-columns: 104px minmax(0, 1fr);
  align-items: center;
  gap: 10px;
  margin-top: 12px;
}

.app-qr-box {
  width: 104px;
  height: 104px;
  display: grid;
  place-items: center;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
}

.playstore-action {
  min-width: 0;
  min-height: 42px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 9px;
  padding: 0 12px;
  border-radius: 12px;
  background: #064bb8;
  color: #fff !important;
  font-family: 'Roboto', var(--font, sans-serif);
  font-weight: 700;
  line-height: 1.12;
  text-align: left;
  text-decoration: none !important;
  box-shadow: 0 12px 24px rgba(6, 75, 184, .22);
  transition: transform .16s ease, box-shadow .16s ease;
}

.playstore-action:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 30px rgba(6, 75, 184, .30);
}

.playstore-action-logo {
  width: 20px;
  height: 20px;
  flex: 0 0 auto;
  display: block;
  object-fit: contain;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .16));
}

html.is-dark .app-download-popover {
  border-color: rgba(255, 255, 255, .08);
  background: rgba(17, 24, 39, .98);
  color: #e5eefb;
  box-shadow: 0 22px 52px rgba(0, 0, 0, .46);
}

html.is-dark .app-download-popover::before {
  border-color: rgba(255, 255, 255, .08);
  background: #111827;
}

html.is-dark .app-popover-copy strong {
  color: #f8fafc;
}

html.is-dark .app-popover-copy p {
  color: #a8b4c5;
}

html.is-dark .app-qr-box {
  border-color: rgba(255, 255, 255, .10);
}

.mobile-app-popover {
  display: none;
}

.mobile-app-pop-enter-active,
.mobile-app-pop-leave-active {
  transition: opacity .18s ease, transform .18s ease;
}

.mobile-app-pop-enter-from,
.mobile-app-pop-leave-to {
  opacity: 0;
  transform: translateY(-8px);
}

/* caret */
.navbar .navbar-item.has-dropdown>.navbar-link::after {
  content: "";
  display: inline-block;
  width: 0;
  height: 0;
  border: .40em solid transparent;
  border-top-color: currentColor !important;
  transform: translateY(2px);
  margin-left: 2px;
}

.navbar .navbar-item.has-dropdown:hover>.navbar-link::after {
  transform: translateY(2px) rotate(180deg);
  transition: transform .15s ease;
}

/* caret mobile saat terbuka */
.navbar .navbar-item.has-dropdown.is-open>.navbar-link::after {
  transform: translateY(2px) rotate(180deg);
}

/* ===== DESKTOP DROPDOWN ===== */
.navbar .navbar-item.has-dropdown {
  position: relative;
}

.navbar .navbar-dropdown {
  position: absolute;
  top: 100%;
  left: 0;
  z-index: 10001;
  margin-top: 0 !important;
  display: block;
  opacity: 0;
  visibility: hidden;
  transform: translateY(6px) scale(.985);
  transition: opacity .14s ease, transform .16s ease, visibility 0s linear .16s;
  background: var(--dd-bg) !important;
  border: 1px solid var(--dd-border);
  border-radius: var(--dd-radius);
  box-shadow: var(--dd-shadow);
  min-width: 360px;
  padding: var(--dd-pad-y) var(--dd-pad-x);
  backdrop-filter: blur(6px);
}

.navbar .navbar-item.has-dropdown.is-hoverable:hover>.navbar-dropdown {
  opacity: 1;
  visibility: visible;
  transform: translateY(0) scale(1);
  transition-delay: 0s;
}

.navbar .navbar-item.has-dropdown.is-hoverable:hover>.navbar-dropdown::before {
  content: '';
  position: absolute;
  top: -7px;
  left: 26px;
  width: 14px;
  height: 14px;
  background: var(--dd-bg);
  border-left: 1px solid var(--dd-border);
  border-top: 1px solid var(--dd-border);
  transform: rotate(45deg);
}

.navbar .navbar-dropdown .navbar-item {
  color: var(--dd-text) !important;
  font-size: var(--dd-font);
  font-weight: 700;
  line-height: var(--dd-line);
  border-radius: 10px;
  padding: var(--dd-item-pad-y) var(--dd-item-pad-x);
  margin: var(--dd-item-gap) 4px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.navbar .navbar-dropdown .navbar-item:hover {
  background: rgba(59, 130, 246, .10);
}

/* ===== MOBILE: default dropdown tertutup ===== */
@media (max-width:1023px) {
  .navbar .navbar-item.has-dropdown {
    position: static;
  }

  .navbar .navbar-item.has-dropdown>.navbar-link {
    width: 100%;
    justify-content: center;
  }

  .navbar .navbar-dropdown {
    position: static;
    box-shadow: none;
    border: 0;
    border-radius: 12px;
    background: var(--dd-bg) !important;
    min-width: 0;
    padding: var(--dd-pad-y) var(--dd-pad-x);
    margin-top: 8px !important;
    display: none;
    opacity: 1;
    visibility: visible;
    transform: none;
  }

  .navbar .navbar-item.has-dropdown.is-open>.navbar-dropdown {
    display: block;
  }

  .navbar .navbar-dropdown .navbar-item {
    margin: 1px 0;
    border-radius: 8px;
  }

  .navbar .app-download-nav {
    display: block;
    width: 100%;
    padding-inline: 0;
  }

  .app-download-trigger {
    width: 100%;
    min-height: 48px;
    justify-content: flex-start;
    padding: 7px 12px 7px 8px;
    color: #0f172a;
    border-color: rgba(15, 23, 42, .10);
    background: #f8fafc;
    box-shadow: 0 10px 24px rgba(15, 23, 42, .08);
  }

  .app-download-icon {
    width: 34px;
    height: 34px;
    color: #fff;
    background: #064bb8;
  }

  .app-download-icon svg {
    width: 17px;
    height: 17px;
  }

  .app-download-label strong {
    font-size: .86rem;
  }

  .app-download-label small {
    color: #64748b;
    font-size: .66rem;
  }

  .app-download-popover {
    display: none;
  }

  .app-download-popover::before {
    display: none;
  }

  .app-popover-body {
    grid-template-columns: 86px minmax(0, 1fr);
  }

  .app-qr-box {
    width: 86px;
    height: 86px;
  }

  .app-qr-box svg {
    width: 78px;
    height: 78px;
  }

  .playstore-action {
    width: 100%;
  }

  .mobile-app-popover {
    position: fixed;
    top: 78px;
    left: 12px;
    right: 12px;
    z-index: 10002;
    min-height: 50px;
    display: grid;
    grid-template-columns: 32px minmax(0, 1fr) auto 28px;
    align-items: center;
    gap: 8px;
    padding: 7px 8px;
    border: 1px solid rgba(148, 163, 184, .22);
    border-radius: 12px;
    background: rgba(255, 255, 255, .97);
    box-shadow: 0 16px 34px rgba(15, 23, 42, .20);
    backdrop-filter: blur(12px);
  }

  .mobile-app-popover-icon {
    width: 32px;
    height: 32px;
    display: inline-grid;
    place-items: center;
    border-radius: 11px;
    color: #fff;
    background: #064bb8;
  }

  .mobile-app-popover-icon svg {
    width: 16px;
    height: 16px;
  }

  .mobile-app-popover-copy {
    min-width: 0;
    line-height: 1.1;
  }

  .mobile-app-popover-copy strong,
  .mobile-app-popover-copy span {
    display: block;
  }

  .mobile-app-popover-copy strong {
    color: #0f172a;
    font-family: 'Roboto', var(--font, sans-serif);
    font-size: .84rem;
    font-weight: 700;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
  }

  .mobile-app-popover-copy span {
    margin-top: 2px;
    color: #64748b;
    font-size: .62rem;
    font-weight: 700;
    letter-spacing: .04em;
    text-transform: uppercase;
  }

.mobile-app-popover-action {
  min-height: 30px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 0 10px;
    border-radius: 8px;
    color: #fff !important;
    background: #064bb8;
    font-family: 'Roboto', var(--font, sans-serif);
    font-size: .72rem;
    font-weight: 700;
    text-decoration: none !important;
  white-space: nowrap;
}

.mobile-playstore-logo {
  width: 17px;
  height: 17px;
  display: block;
  object-fit: contain;
  filter: drop-shadow(0 1px 1px rgba(0, 0, 0, .14));
}

  .mobile-app-popover-close {
    width: 28px;
    height: 28px;
    display: inline-grid;
    place-items: center;
    border: 0;
    border-radius: 999px;
    background: #f1f5f9;
    color: #475569;
    cursor: pointer;
  }

  .mobile-app-popover-close i,
  .mobile-app-popover-close svg {
    font-size: .72rem;
    width: 16px;
    height: 16px;
  }

  html.is-dark .mobile-app-popover {
    border-color: rgba(255, 255, 255, .10);
    background: rgba(17, 24, 39, .96);
    box-shadow: 0 16px 34px rgba(0, 0, 0, .42);
  }

  html.is-dark .mobile-app-popover-copy strong {
    color: #f8fafc;
  }

  html.is-dark .mobile-app-popover-copy span {
    color: #a8b4c5;
  }

  html.is-dark .mobile-app-popover-close {
    background: rgba(255, 255, 255, .08);
    color: #cbd5e1;
  }
}

/* kecil */
@media (max-width:767px) {
  .navbar.is-fixed-top {
    padding-inline: 12px;
  }

  .mobile-app-popover {
    top: 76px;
    grid-template-columns: 32px minmax(0, 1fr) 28px;
    grid-template-areas:
      "icon copy close"
      "action action action";
    row-gap: 8px;
  }

  .mobile-app-popover-icon {
    grid-area: icon;
  }

  .mobile-app-popover-copy {
    grid-area: copy;
  }

  .mobile-app-popover-action {
    grid-area: action;
    width: 100%;
  }

  .mobile-app-popover-close {
    grid-area: close;
  }

  .navbar .navbar-brand {
    position: relative;
    width: 100%;
    justify-content: center;
  }

  .navbar .brand-lockup {
    position: absolute;
    left: 50%;
    min-width: 0;
    transform: translateX(-50%);
    z-index: 1;
  }

  .navbar .brand-icon {
    width: 52px;
    height: 52px;
  }

  .navbar .brand-icon img {
    width: 52px;
    height: 52px;
  }

  .navbar .navbar-brand .navbar-burger {
    position: relative;
    z-index: 2;
    border-radius: var(--radius-rounded);
    margin-left: auto;
    margin-right: 12px;
  }

  .navbar .navbar-menu {
    width: calc(100% - 32px);
    position: fixed;
    top: 78px;
    left: 0;
    right: 0;
    margin: 0 auto;
    border-radius: 0 0 10px 10px;
    padding: 30px;
    text-align: center;
    border: 1px solid var(--nav-border);
    box-shadow: var(--nav-shadow);
    background: var(--nav-bg) !important;
  }

  .navbar .navbar-item.is-theme-toggle .theme-toggle {
    margin: 0 auto;
  }

  .navbar .navbar-menu .button {
    width: 100%;
  }
}

/* ===========================
   THEME TOGGLE (restore pill)
=========================== */
.navbar-item.is-theme-toggle {
  display: flex;
  align-items: center;
}

.theme-toggle {
  position: relative;
  width: 56px;
  height: 28px;
  display: inline-block;
}

.theme-toggle input {
  position: absolute;
  inset: 0;
  width: 100%;
  height: 100%;
  margin: 0;
  opacity: 0 !important;
  appearance: none;
  -webkit-appearance: none;
  -moz-appearance: none;
  cursor: pointer;
  z-index: 2;
}

.theme-toggle .toggler {
  position: absolute;
  inset: 0;
  border: 2px solid #064bb8;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 3px 6px;
  background: #f8fbff;
  transition: border-color .2s ease, background .2s ease;
  overflow: hidden;
}

.theme-toggle .toggler .knob {
  position: absolute;
  top: 2px;
  left: 2px;
  width: 22px;
  height: 22px;
  border-radius: 999px;
  background: #064bb8;
  transition: transform .25s ease;
  z-index: 0;
}

.theme-toggle .toggler .dark,
.theme-toggle .toggler .light {
  position: relative;
  z-index: 1;
  width: 18px;
  height: 18px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: #fff;
  opacity: .9;
}

/* move knob when checked */
.theme-toggle input:checked+.toggler .knob {
  transform: translateX(26px);
}

.theme-toggle input:checked+.toggler .dark {
  opacity: 1;
}

.theme-toggle input+.toggler .light {
  opacity: .8;
}

.theme-toggle:hover .toggler {
  border-color: #0b55dc;
}

html.is-dark .theme-toggle .toggler {
  border-color: #38bdf8;
  background: rgba(56, 189, 248, .10);
}

html.is-dark .theme-toggle .toggler .knob {
  background: #38bdf8;
}

/* dark transparent (khusus saat transparan aktif di Home) */
.is-dark .navbar.is-fixed-top.is-transparent .navbar-item,
.is-dark .navbar.is-fixed-top.is-transparent .nav-link,
.is-dark .navbar.is-fixed-top.is-transparent .navbar-burger {
  color: #eef2f7 !important;
}

.is-dark .navbar.is-fixed-top.is-transparent .navbar-burger span {
  background: #eef2f7 !important;
}
</style>
