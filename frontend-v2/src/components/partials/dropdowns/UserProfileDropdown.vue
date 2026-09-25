<template>
  <VDropdown right spaced class="user-dropdown profile-dropdown">
    <template #button="{ toggle }">
      <a tabindex="0" class="is-trigger dropdown-trigger" aria-haspopup="true" @keydown.space.prevent="toggle"
        @click="toggle">
        <VAvatar :picture="icon" />
      </a>
    </template>

    <template #content>
      <div class="dropdown-head">
        <VAvatar size="large" :picture="icon" />

        <div class="meta">
          <span>{{ user.pegawai.namaLengkap }}</span>
          <span>{{ user.pegawai.jenisPegawai ? user.pegawai.jenisPegawai.jenispegawai : '-' }}</span>
        </div>
      </div>

      <a role="menuitem" class="dropdown-item is-media" @click="showProfile(item)">
        <div class="icon">
          <i aria-hidden="true" class="lnil lnil-user-alt"></i>
        </div>
        <div class="meta">
          <span>Profile</span>
          <span>View your profile</span>
        </div>
      </a>

      <hr class="dropdown-divider" />

      <a href="#" role="menuitem" class="dropdown-item is-media">
        <div class="icon">
          <i aria-hidden="true" class="lnil lnil-cog"></i>
        </div>
        <div class="meta">
          <span>Settings</span>
          <span>Account settings</span>
        </div>
      </a>

      <hr class="dropdown-divider" />

      <div class="dropdown-item is-media">
        <div class="icon">
          <i aria-hidden="true" class="lnil lnil-signal"></i>
        </div>
        <div class="meta">
          <span>Internet</span>
          <span>
            <strong :style="{ color: net.online ? '#22c55e' : '#ef4444' }">
              {{ net.online ? 'Online' : 'Offline' }}
            </strong>
            • {{ net.speedMbps != null ? net.speedMbps.toFixed(1) + ' Mbps' : '–' }}
            • {{ net.effectiveType || net.type || 'unknown' }}
            <template v-if="net.rtt != null"> • {{ Math.round(net.rtt) }} ms RTT</template>
          </span>
        </div>
      </div>
      <div class="dropdown-item is-button">
        <VButton class="logout-button" icon="feather:log-out" color="primary" role="menuitem" raised fullwidth
          @click="logout">
          Logout
        </VButton>
      </div>
    </template>
  </VDropdown>
</template>

<script setup lang="ts">
import { useUserSession } from '/@src/stores/userSession'
import { ref, computed, watch, reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { useStorage } from '@vueuse/core'
import { publicFileUrl } from '/@src/utils/publicFileUrl'

const user = useUserSession().getUser()
const router = useRouter()
const item: any = ref({})

function logout() {
  useStorage('user_session', '')
  useUserSession().logoutUser()
  window.location.href = '/auth/login'
}

// ===== PERUBAHAN LOGIKA FOTO DI SINI =====
const defaultAvatar =
  user.pegawai.jenisKelamin_id && user.pegawai.jenisKelamin_id == 1
    ? '/images/avatars/svg/vuero-2.svg'
    : '/images/avatars/svg/vuero-4.svg'

// Cek jika ada foto pegawai, gunakan URL berkas-mutu, jika tidak gunakan defaultAvatar
const icon = user.pegawai.fotopegawai
  ? publicFileUrl('berkas-mutu', user.pegawai.fotopegawai)
  : defaultAvatar
// =========================================

// ===== Internet speed & connection info =====
type NetInfo = {
  online: boolean
  speedMbps: number | null
  effectiveType: string
  type: string
  rtt: number | null
}
const net = reactive<NetInfo>({
  online: navigator.onLine,
  speedMbps: null,
  effectiveType: '',
  type: '',
  rtt: null,
})

let speedTimer: number | null = null
let navConn: any =
  // @ts-ignore
  (navigator as any).connection || (navigator as any).mozConnection || (navigator as any).webkitConnection

function updateFromNavigatorConnection() {
  if (!navConn) return
  net.effectiveType = navConn.effectiveType || ''
  net.type = navConn.type || ''
  if (typeof navConn.rtt === 'number') net.rtt = navConn.rtt
  if (typeof navConn.downlink === 'number' && !Number.isNaN(navConn.downlink)) {
    // downlink is in Mbps already
    net.speedMbps = navConn.downlink
  }
}

async function runLightSpeedTest() {
  try {
    // gunakan asset yang PASTI ada di app (avatar SVG); kecil & cepat
    // Catatan: Jika icon berupa URL eksternal (https), fetch mungkin kena CORS jika server tidak mengizinkan.
    // Namun untuk speedtest ringan ini kita biarkan best effort.
    const url = `${icon.startsWith('http') ? defaultAvatar : icon}?t=${Date.now()}`
    const t0 = performance.now()
    const res = await fetch(url, { cache: 'no-store' })
    const blob = await res.blob()
    const t1 = performance.now()
    const dtSec = Math.max((t1 - t0) / 1000, 0.001)
    const bytes = blob.size
    // Mbps = (bytes * 8) / 1e6 / detik
    const mbps = (bytes * 8) / 1_000_000 / dtSec
    // smoothing ringan biar stabil
    if (net.speedMbps == null) {
      net.speedMbps = mbps
    } else {
      net.speedMbps = net.speedMbps * 0.6 + mbps * 0.4
    }
  } catch {
    // abaikan error; jangan ganggu UX
  }
}

function startNetWatch() {
  net.online = navigator.onLine
  updateFromNavigatorConnection()
  runLightSpeedTest()

  window.addEventListener('online', () => (net.online = true))
  window.addEventListener('offline', () => (net.online = false))

  if (navConn && typeof navConn.addEventListener === 'function') {
    navConn.addEventListener('change', () => {
      updateFromNavigatorConnection()
      // saat jaringan berubah, uji lagi
      runLightSpeedTest()
    })
  }

  // refresh speed tiap 10 detik (ringan)
  speedTimer = window.setInterval(() => {
    updateFromNavigatorConnection()
    runLightSpeedTest()
  }, 10000)
}

function stopNetWatch() {
  if (speedTimer) {
    clearInterval(speedTimer)
    speedTimer = null
  }
  if (navConn && typeof navConn.removeEventListener === 'function') {
    navConn.removeEventListener('change', updateFromNavigatorConnection)
  }
  window.removeEventListener('online', () => { })
  window.removeEventListener('offline', () => { })
}

onMounted(startNetWatch)
onUnmounted(stopNetWatch)
// ===========================================

const showProfile = async (_e: any) => {
  router.push({
    name: 'module-sysadmin-detail-pegawai',
    query: {
      id: user.pegawai.id,
    },
  })
}
</script>

<style scoped lang="scss">
/* Opsional: rapikan tampilan baris Internet */
.dropdown-item.is-media .meta span:first-child {
  display: block;
  font-weight: 600;
}

.dropdown-item.is-media .meta span:last-child {
  opacity: 0.85;
}
</style>
