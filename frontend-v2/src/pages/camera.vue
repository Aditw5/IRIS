<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, reactive, ref } from 'vue'
import { useHead } from '@vueuse/head'
import { useRoute } from 'vue-router'
import QrcodeVue from 'qrcode.vue'
import { socket, state as socketState } from '/@src/socket.js'

type CameraRole = 'asci' | 'std' | 'uut'
type SourceRole = 'std' | 'uut'
type CameraParticipant = {
  id: string
  role: CameraRole
}
type SignalPayload = {
  fromId: string
  role: CameraRole
  data: {
    description?: RTCSessionDescriptionInit
    candidate?: RTCIceCandidateInit
  }
}
type RoomState = 'connecting' | 'joined' | 'unsupported' | 'error'
type PlaybackState = 'waiting' | 'loading' | 'playing' | 'blocked' | 'stalled' | 'error'
type CameraMode = 'calibration' | 'measurement'

const route = useRoute()
const requestedRole = String(route.query.role ?? 'asci').toLowerCase()
const requestedMode = String(route.query.mode ?? 'calibration').toLowerCase()
const role = ref<CameraRole>(['std', 'uut'].includes(requestedRole) ? requestedRole as CameraRole : 'asci')
const activeMode = ref<CameraMode>(requestedMode === 'measurement' ? 'measurement' : 'calibration')
const roomId = ref(String(route.query.room ?? '').trim() || createRoomId())
const noorder = computed(() => String(route.query.noorder ?? '').trim())
const sn = computed(() => String(route.query.sn ?? route.query.serial_number ?? '').trim())
const alat = computed(() => String(route.query.alat ?? '').trim())
const isAsci = computed(() => role.value === 'asci')
const isSource = computed(() => !isAsci.value)
const sourceLabel = computed(() => role.value === 'std' ? 'HP Standar (STD)' : 'HP Unit Under Test (UUT)')
const modeLabel = computed(() => activeMode.value === 'measurement' ? 'Measurement' : 'Kalibrasi')
const monitorDescription = computed(() => activeMode.value === 'measurement'
  ? 'Terima video unit alat ukur untuk pembacaan measurement dan penerapan koreksi.'
  : 'Terima video alat standar dan unit yang diuji secara real-time untuk kalibrasi.'
)
const cameraError = ref('')
const roomState = ref<RoomState>('connecting')
const roomMessage = ref('Menghubungkan ke room kamera...')
const peerStates = reactive<Record<SourceRole, RTCPeerConnectionState | 'waiting'>>({
  std: 'waiting',
  uut: 'waiting',
})
const iceStates = reactive<Record<SourceRole, RTCIceConnectionState | 'waiting'>>({
  std: 'waiting',
  uut: 'waiting',
})
const playbackStates = reactive<Record<SourceRole, PlaybackState>>({
  std: 'waiting',
  uut: 'waiting',
})
const playbackMessages = reactive<Record<SourceRole, string>>({
  std: '',
  uut: '',
})
const copiedRole = ref('')
const localVideo = ref<HTMLVideoElement | null>(null)
const stdVideo = ref<HTMLVideoElement | null>(null)
const uutVideo = ref<HTMLVideoElement | null>(null)
const localStream = ref<MediaStream | null>(null)
const participants = ref<CameraParticipant[]>([])
const remoteStreams = reactive<Record<SourceRole, MediaStream | null>>({
  std: null,
  uut: null,
})
const peerConnections = new Map<string, RTCPeerConnection>()
const peerRoles = new Map<string, CameraRole>()
const pendingCandidates = new Map<string, RTCIceCandidateInit[]>()
const offeredPeers = new Set<string>()
const activeSourcePeers: Record<SourceRole, string | null> = {
  std: null,
  uut: null,
}
const preferredSourcePeers: Record<SourceRole, string | null> = {
  std: null,
  uut: null,
}

useHead({
  title: `Kamera ASCI${sn.value || noorder.value ? ` - ${sn.value || noorder.value}` : ''}`,
})

const iceServers: RTCIceServer[] = [
  { urls: 'stun:stun.l.google.com:19302' },
  { urls: 'stun:stun1.l.google.com:19302' },
]
const turnUrl = String(import.meta.env.VITE_CAMERA_TURN_URL ?? '').trim()
if (turnUrl) {
  iceServers.push({
    urls: turnUrl,
    username: import.meta.env.VITE_CAMERA_TURN_USERNAME,
    credential: import.meta.env.VITE_CAMERA_TURN_CREDENTIAL,
  })
}
const rtcConfig: RTCConfiguration = { iceServers }

const sourceParticipants = computed(() => ({
  std: participants.value.some(item => item.role === 'std'),
  uut: participants.value.some(item => item.role === 'uut'),
}))
const roomJoined = computed(() => roomState.value === 'joined')
const getRemoteVideo = (sourceRole: SourceRole) => sourceRole === 'std' ? stdVideo.value : uutVideo.value
const isRemoteLive = (sourceRole: SourceRole) => {
  const video = getRemoteVideo(sourceRole)
  return playbackStates[sourceRole] === 'playing' && !!video?.videoWidth && !!video?.videoHeight
}

const remoteStatusLabel = (sourceRole: SourceRole) => {
  if (isRemoteLive(sourceRole)) return 'LIVE'
  if (!sourceParticipants.value[sourceRole]) return 'MENUNGGU HP'
  if (iceStates[sourceRole] === 'failed') return 'ICE GAGAL'
  if (playbackStates[sourceRole] === 'blocked') return 'PERLU DIPUTAR'
  if (playbackStates[sourceRole] === 'stalled') return 'VIDEO TERHENTI'
  return 'MENGHUBUNGKAN'
}

const remoteDiagnostic = (sourceRole: SourceRole) => {
  const stream = remoteStreams[sourceRole]
  const track = stream?.getVideoTracks()?.[0]
  const trackState = track
    ? `track ${track.readyState}${track.muted ? ', muted' : ''}${track.enabled ? '' : ', disabled'}`
    : 'track belum ada'
  const video = getRemoteVideo(sourceRole)
  const frameSize = video?.videoWidth && video?.videoHeight
    ? `${video.videoWidth}x${video.videoHeight}`
    : 'belum ada frame'
  return `WebRTC ${peerStates[sourceRole]} | ICE ${iceStates[sourceRole]} | ${trackState} | ${frameSize}`
}

const sourceLinks = computed<Record<SourceRole, string>>(() => {
  const origin = typeof window !== 'undefined' ? window.location.origin : 'https://ulabumro.id'
  const createSourceLink = (sourceRole: SourceRole) => {
    const params = new URLSearchParams({
      room: roomId.value,
      role: sourceRole,
      mode: activeMode.value,
    })
    if (noorder.value) params.set('noorder', noorder.value)
    if (sn.value) params.set('sn', sn.value)
    if (alat.value) params.set('alat', alat.value)
    return `${origin}/camera?${params.toString()}`
  }
  return {
    std: createSourceLink('std'),
    uut: createSourceLink('uut'),
  }
})

function selectMode(mode: CameraMode) {
  activeMode.value = mode
  if (typeof window === 'undefined') return
  const url = new URL(window.location.href)
  url.searchParams.set('mode', mode)
  window.history.replaceState({}, '', url)
}

function createRoomId() {
  const randomPart = typeof crypto !== 'undefined' && crypto.randomUUID
    ? crypto.randomUUID().split('-')[0]
    : Math.random().toString(36).slice(2, 10)
  return `camera-${randomPart}`
}

function joinCameraRoom() {
  roomState.value = 'connecting'
  roomMessage.value = 'Socket terhubung, sedang masuk ke room kamera...'

  if (typeof socket.timeout !== 'function') {
    roomState.value = 'unsupported'
    roomMessage.value = 'Client Socket.IO tidak mendukung handshake room kamera.'
    return
  }

  socket.timeout(6000).emit(
    'join-camera-room',
    {
      roomId: roomId.value,
      role: role.value,
    },
    (error: Error | null, response: any) => {
      if (error) {
        roomState.value = 'unsupported'
        roomMessage.value = 'Server socket belum menjalankan fitur room kamera. Deploy dan restart ulab-socket terbaru.'
        return
      }

      if (!response?.ok) {
        roomState.value = 'error'
        roomMessage.value = response?.message || 'Gagal masuk ke room kamera.'
        return
      }

      roomState.value = 'joined'
      roomMessage.value = `Masuk room sebagai ${role.value.toUpperCase()} (${response.serverVersion || 'server aktif'})`
      handleParticipants(response.participants ?? [])
    }
  )
}

function handleSocketDisconnect() {
  roomState.value = 'connecting'
  roomMessage.value = 'Koneksi socket terputus. Menunggu tersambung kembali...'
  participants.value = []
  closeAllPeers()
}

function sendSignal(targetId: string, data: SignalPayload['data']) {
  socket.emit('camera-signal', {
    roomId: roomId.value,
    targetId,
    data,
  })
}

function resetRemoteRole(remoteRole: SourceRole) {
  remoteStreams[remoteRole] = null
  peerStates[remoteRole] = 'waiting'
  iceStates[remoteRole] = 'waiting'
  playbackStates[remoteRole] = 'waiting'
  playbackMessages[remoteRole] = ''
  const video = getRemoteVideo(remoteRole)
  if (video) video.srcObject = null
}

function clearRemoteStream(peerId: string) {
  const remoteRole = peerRoles.get(peerId)
  if ((remoteRole === 'std' || remoteRole === 'uut') && activeSourcePeers[remoteRole] === peerId) {
    activeSourcePeers[remoteRole] = null
    resetRemoteRole(remoteRole)
  }
}

function closePeer(peerId: string) {
  const peer = peerConnections.get(peerId)
  if (peer) peer.close()
  clearRemoteStream(peerId)
  peerConnections.delete(peerId)
  peerRoles.delete(peerId)
  pendingCandidates.delete(peerId)
  offeredPeers.delete(peerId)
}

function closeAllPeers() {
  Array.from(peerConnections.keys()).forEach(closePeer)
}

async function attachRemoteStream(remoteRole: CameraRole, stream: MediaStream, peerId: string) {
  if (remoteRole !== 'std' && remoteRole !== 'uut') return
  if (activeSourcePeers[remoteRole] !== peerId || peerConnections.get(peerId)?.connectionState === 'closed') return
  if (remoteStreams[remoteRole]?.id === stream.id && getRemoteVideo(remoteRole)?.srcObject === stream) return

  remoteStreams[remoteRole] = stream
  playbackStates[remoteRole] = 'loading'
  playbackMessages[remoteRole] = 'Track diterima, menunggu frame video...'
  await nextTick()
  const video = getRemoteVideo(remoteRole)
  if (video && activeSourcePeers[remoteRole] === peerId) {
    if (video.srcObject !== stream) video.srcObject = stream
    video.muted = true
    video.playsInline = true
    await video.play().catch((error: any) => {
      playbackStates[remoteRole] = 'blocked'
      playbackMessages[remoteRole] = error?.message || 'Autoplay diblokir browser. Tekan Putar Video.'
    })
  }
}

function handleRemotePlaying(sourceRole: SourceRole) {
  playbackStates[sourceRole] = 'playing'
  playbackMessages[sourceRole] = isRemoteLive(sourceRole) ? '' : 'Video berjalan tetapi belum menerima frame gambar.'
}

function handleRemoteWaiting(sourceRole: SourceRole) {
  if (playbackStates[sourceRole] !== 'blocked') {
    playbackStates[sourceRole] = 'stalled'
    playbackMessages[sourceRole] = 'Video belum menerima frame baru.'
  }
}

function handleRemoteError(sourceRole: SourceRole) {
  playbackStates[sourceRole] = 'error'
  playbackMessages[sourceRole] = 'Browser gagal memainkan stream video.'
}

async function retryRemotePlayback(sourceRole: SourceRole) {
  const video = getRemoteVideo(sourceRole)
  if (!video || !remoteStreams[sourceRole]) return

  playbackStates[sourceRole] = 'loading'
  playbackMessages[sourceRole] = 'Mencoba memutar stream...'
  if (video.srcObject !== remoteStreams[sourceRole]) video.srcObject = remoteStreams[sourceRole]
  await video.play().catch((error: any) => {
    playbackStates[sourceRole] = 'blocked'
    playbackMessages[sourceRole] = error?.message || 'Video masih diblokir browser.'
  })
}

function createPeer(peerId: string, remoteRole: CameraRole) {
  const current = peerConnections.get(peerId)
  if (current) return current

  if (isAsci.value && (remoteRole === 'std' || remoteRole === 'uut')) {
    const activePeerId = activeSourcePeers[remoteRole]
    if (activePeerId && activePeerId !== peerId) closePeer(activePeerId)
    activeSourcePeers[remoteRole] = peerId
    resetRemoteRole(remoteRole)
  }

  const peer = new RTCPeerConnection(rtcConfig)
  peerConnections.set(peerId, peer)
  peerRoles.set(peerId, remoteRole)

  if (isSource.value && localStream.value) {
    localStream.value.getTracks().forEach(track => peer.addTrack(track, localStream.value as MediaStream))
  }

  peer.onicecandidate = event => {
    if (event.candidate) {
      sendSignal(peerId, { candidate: event.candidate.toJSON() })
    }
  }

  peer.ontrack = event => {
    const stream = event.streams[0]
    if (stream) attachRemoteStream(remoteRole, stream, peerId)
  }

  peer.oniceconnectionstatechange = () => {
    if (remoteRole === 'std' || remoteRole === 'uut') {
      if (activeSourcePeers[remoteRole] !== peerId) return
      iceStates[remoteRole] = peer.iceConnectionState
      if (peer.iceConnectionState === 'failed') {
        playbackStates[remoteRole] = 'error'
        playbackMessages[remoteRole] = 'Jalur media gagal terbentuk. Server TURN kemungkinan diperlukan.'
      }
    }
  }

  peer.onconnectionstatechange = () => {
    if (remoteRole === 'std' || remoteRole === 'uut') {
      if (activeSourcePeers[remoteRole] === peerId) {
        peerStates[remoteRole] = peer.connectionState
      }
    }
    if (['failed', 'closed'].includes(peer.connectionState)) closePeer(peerId)
  }

  return peer
}

async function flushPendingCandidates(peerId: string, peer: RTCPeerConnection) {
  const candidates = pendingCandidates.get(peerId) ?? []
  for (const candidate of candidates) {
    await peer.addIceCandidate(candidate).catch(() => undefined)
  }
  pendingCandidates.delete(peerId)
}

async function createOfferForViewer(viewer: CameraParticipant) {
  if (!roomJoined.value || !localStream.value || offeredPeers.has(viewer.id)) return
  offeredPeers.add(viewer.id)
  const peer = createPeer(viewer.id, viewer.role)

  try {
    const offer = await peer.createOffer()
    await peer.setLocalDescription(offer)
    sendSignal(viewer.id, { description: peer.localDescription as RTCSessionDescriptionInit })
  } catch (error) {
    console.error('Gagal membuat WebRTC offer:', error)
    closePeer(viewer.id)
  }
}

function syncSourceOffers() {
  if (!roomJoined.value || !isSource.value || !localStream.value) return
  participants.value
    .filter(participant => participant.role === 'asci')
    .forEach(createOfferForViewer)
}

function handleParticipants(items: CameraParticipant[]) {
  participants.value = Array.isArray(items) ? items : []
  const activeIds = new Set(participants.value.map(item => item.id))

  if (isAsci.value) {
    for (const sourceRole of ['std', 'uut'] as SourceRole[]) {
      const matchingSources = participants.value.filter(participant => participant.role === sourceRole)
      preferredSourcePeers[sourceRole] = matchingSources[matchingSources.length - 1]?.id ?? null
      const activePeerId = activeSourcePeers[sourceRole]
      if (activePeerId && activePeerId !== preferredSourcePeers[sourceRole]) closePeer(activePeerId)
    }
  }

  Array.from(peerConnections.keys()).forEach(peerId => {
    if (!activeIds.has(peerId)) closePeer(peerId)
  })
  syncSourceOffers()
}

async function handleSignal(payload: SignalPayload) {
  if (!payload?.fromId || !payload?.data) return
  if (
    isAsci.value &&
    (payload.role === 'std' || payload.role === 'uut') &&
    preferredSourcePeers[payload.role] &&
    preferredSourcePeers[payload.role] !== payload.fromId
  ) {
    return
  }
  const peer = createPeer(payload.fromId, payload.role)

  if (payload.data.description) {
    await peer.setRemoteDescription(payload.data.description)
    await flushPendingCandidates(payload.fromId, peer)

    if (payload.data.description.type === 'offer') {
      const answer = await peer.createAnswer()
      await peer.setLocalDescription(answer)
      sendSignal(payload.fromId, { description: peer.localDescription as RTCSessionDescriptionInit })
    }
  }

  if (payload.data.candidate) {
    if (peer.remoteDescription) {
      await peer.addIceCandidate(payload.data.candidate).catch(() => undefined)
    } else {
      const candidates = pendingCandidates.get(payload.fromId) ?? []
      candidates.push(payload.data.candidate)
      pendingCandidates.set(payload.fromId, candidates)
    }
  }
}

function handleRoleReplaced(payload: { roomId?: string; role?: CameraRole }) {
  if (payload?.roomId !== roomId.value || payload?.role !== role.value) return
  roomState.value = 'error'
  roomMessage.value = `Role ${role.value.toUpperCase()} digantikan oleh perangkat lain. Tutup halaman ini atau scan ulang jika ingin mengambil alih.`
  closeAllPeers()
}

async function startCamera() {
  cameraError.value = ''
  stopCamera()

  if (!navigator.mediaDevices?.getUserMedia) {
    cameraError.value = 'Browser ini tidak mendukung akses kamera.'
    return
  }

  try {
    localStream.value = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: { ideal: 'environment' },
        width: { ideal: 1280 },
        height: { ideal: 720 },
      },
      audio: false,
    })

    await nextTick()
    if (localVideo.value) {
      localVideo.value.srcObject = localStream.value
      await localVideo.value.play().catch(() => undefined)
    }
    closeAllPeers()
    syncSourceOffers()
  } catch (error: any) {
    cameraError.value = error?.name === 'NotAllowedError'
      ? 'Izin kamera ditolak. Izinkan kamera pada browser lalu coba lagi.'
      : 'Kamera tidak dapat dibuka. Pastikan halaman memakai HTTPS dan kamera tidak sedang dipakai aplikasi lain.'
  }
}

function stopCamera() {
  localStream.value?.getTracks().forEach(track => track.stop())
  localStream.value = null
  if (localVideo.value) localVideo.value.srcObject = null
}

async function copyLink(sourceRole: SourceRole) {
  try {
    await navigator.clipboard.writeText(sourceLinks.value[sourceRole])
    copiedRole.value = sourceRole
    window.setTimeout(() => {
      if (copiedRole.value === sourceRole) copiedRole.value = ''
    }, 1800)
  } catch {
    copiedRole.value = ''
  }
}

onMounted(() => {
  socket.on('connect', joinCameraRoom)
  socket.on('disconnect', handleSocketDisconnect)
  socket.on('camera-participants', handleParticipants)
  socket.on('camera-signal', handleSignal)
  socket.on('camera-role-replaced', handleRoleReplaced)
  if (socket.connected) joinCameraRoom()
  else {
    roomMessage.value = 'Menghubungkan ke server socket...'
  }
  if (isSource.value) startCamera()
})

onBeforeUnmount(() => {
  socket.emit('leave-camera-room')
  socket.off('connect', joinCameraRoom)
  socket.off('disconnect', handleSocketDisconnect)
  socket.off('camera-participants', handleParticipants)
  socket.off('camera-signal', handleSignal)
  socket.off('camera-role-replaced', handleRoleReplaced)
  stopCamera()
  closeAllPeers()
})
</script>

<template>
  <main class="camera-page">
    <header class="camera-header">
      <div>
        <div class="brand-line">
          <span class="brand-mark"><i class="iconify" data-icon="feather:camera"></i></span>
          <div>
            <p class="eyebrow">U-LAB CAMERA ASCI</p>
            <h1>{{ isAsci ? 'Ruang Monitoring ASCI' : sourceLabel }}</h1>
          </div>
        </div>
        <p class="subtitle">
          {{ isAsci
            ? monitorDescription
            : 'Arahkan kamera ke display alat dan pertahankan posisi selama proses pembacaan.' }}
        </p>
      </div>

      <div class="connection-pill" :class="{ connected: roomJoined, warning: socketState.connected && !roomJoined }">
        <span class="status-dot"></span>
        {{ roomJoined ? 'Room kamera terhubung' : socketState.connected ? 'Socket terhubung, room belum siap' : 'Menghubungkan socket' }}
      </div>
    </header>

    <section v-if="!roomJoined" class="room-alert" :class="roomState">
      <i class="iconify" :data-icon="roomState === 'unsupported' ? 'feather:alert-triangle' : 'feather:loader'"></i>
      <div>
        <strong>{{ roomState === 'unsupported' ? 'Fitur kamera belum aktif pada server socket' : 'Menyiapkan room kamera' }}</strong>
        <span>{{ roomMessage }}</span>
      </div>
    </section>

    <section class="session-bar">
      <div>
        <span class="session-label">SESSION</span>
        <strong>{{ roomId }}</strong>
      </div>
      <div v-if="noorder">
        <span class="session-label">NO. ORDER</span>
        <strong>{{ noorder }}</strong>
      </div>
      <div v-if="sn">
        <span class="session-label">SN</span>
        <strong>{{ sn }}</strong>
      </div>
      <div v-if="alat">
        <span class="session-label">ALAT</span>
        <strong>{{ alat }}</strong>
      </div>
      <div v-if="isAsci">
        <span class="session-label">MODE</span>
        <strong>{{ modeLabel }}</strong>
      </div>
    </section>

    <template v-if="isAsci">
      <nav class="camera-mode-tabs" aria-label="Mode kamera ASCI">
        <button type="button" :class="{ active: activeMode === 'calibration' }" @click="selectMode('calibration')">
          <i class="iconify" data-icon="feather:sliders"></i>
          Kalibrasi
          <small>STD + UUT</small>
        </button>
        <button type="button" :class="{ active: activeMode === 'measurement' }" @click="selectMode('measurement')">
          <i class="iconify" data-icon="feather:activity"></i>
          Measurement
          <small>UUT saja</small>
        </button>
      </nav>

      <section class="mode-intro">
        <div>
          <span class="source-kicker">MODE AKTIF</span>
          <h2>{{ modeLabel }}</h2>
        </div>
        <p>{{ monitorDescription }}</p>
      </section>

      <section class="monitor-grid" :class="{ single: activeMode === 'measurement' }">
        <article v-if="activeMode === 'calibration'" class="monitor-card">
          <div class="monitor-title">
            <div>
              <span class="source-kicker">SUMBER VIDEO 01</span>
              <h2>Alat Standar (STD)</h2>
            </div>
            <span class="live-badge" :class="{ active: isRemoteLive('std'), failed: iceStates.std === 'failed' }">
              {{ remoteStatusLabel('std') }}
            </span>
          </div>
          <div class="video-stage">
            <video ref="stdVideo" autoplay playsinline muted
              @playing="handleRemotePlaying('std')" @waiting="handleRemoteWaiting('std')"
              @stalled="handleRemoteWaiting('std')" @error="handleRemoteError('std')"></video>
            <div v-if="!remoteStreams.std" class="video-placeholder">
              <i class="iconify" data-icon="feather:video"></i>
              <strong>Video STD belum masuk</strong>
              <span>{{ sourceParticipants.std ? `HP terdeteksi, WebRTC: ${peerStates.std}` : 'Scan QR STD menggunakan HP pertama' }}</span>
            </div>
            <div v-else-if="!isRemoteLive('std')" class="playback-overlay">
              <i class="iconify" :data-icon="iceStates.std === 'failed' ? 'feather:alert-triangle' : 'feather:play-circle'"></i>
              <strong>{{ playbackMessages.std || 'Menunggu frame video STD...' }}</strong>
              <span>{{ remoteDiagnostic('std') }}</span>
              <button type="button" @click="retryRemotePlayback('std')">
                <i class="iconify" data-icon="feather:play"></i>
                Putar Video
              </button>
            </div>
          </div>
          <div class="stream-diagnostic" :class="{ failed: iceStates.std === 'failed' }">
            {{ remoteDiagnostic('std') }}
          </div>
        </article>

        <article class="monitor-card">
          <div class="monitor-title">
            <div>
              <span class="source-kicker">SUMBER VIDEO 02</span>
              <h2>Unit Under Test (UUT)</h2>
            </div>
            <span class="live-badge" :class="{ active: isRemoteLive('uut'), failed: iceStates.uut === 'failed' }">
              {{ remoteStatusLabel('uut') }}
            </span>
          </div>
          <div class="video-stage">
            <video ref="uutVideo" autoplay playsinline muted
              @playing="handleRemotePlaying('uut')" @waiting="handleRemoteWaiting('uut')"
              @stalled="handleRemoteWaiting('uut')" @error="handleRemoteError('uut')"></video>
            <div v-if="!remoteStreams.uut" class="video-placeholder">
              <i class="iconify" data-icon="feather:video"></i>
              <strong>Video UUT belum masuk</strong>
              <span>{{ sourceParticipants.uut ? `HP terdeteksi, WebRTC: ${peerStates.uut}` : 'Scan QR UUT menggunakan HP kedua' }}</span>
            </div>
            <div v-else-if="!isRemoteLive('uut')" class="playback-overlay">
              <i class="iconify" :data-icon="iceStates.uut === 'failed' ? 'feather:alert-triangle' : 'feather:play-circle'"></i>
              <strong>{{ playbackMessages.uut || 'Menunggu frame video UUT...' }}</strong>
              <span>{{ remoteDiagnostic('uut') }}</span>
              <button type="button" @click="retryRemotePlayback('uut')">
                <i class="iconify" data-icon="feather:play"></i>
                Putar Video
              </button>
            </div>
          </div>
          <div class="stream-diagnostic" :class="{ failed: iceStates.uut === 'failed' }">
            {{ remoteDiagnostic('uut') }}
          </div>
        </article>
      </section>

      <section class="source-links">
        <div class="section-heading">
          <div>
            <span class="source-kicker">HUBUNGKAN KAMERA</span>
            <h2>{{ activeMode === 'measurement' ? 'Scan QR UUT untuk Measurement' : 'Scan QR dari masing-masing HP' }}</h2>
          </div>
          <p>{{ activeMode === 'measurement'
            ? 'Measurement hanya memakai kamera UUT. Video diteruskan ke ASCI untuk dibaca dan ditambahkan nilai koreksi.'
            : 'Video dikirim langsung antar perangkat melalui WebRTC. Socket hanya dipakai untuk mempertemukan perangkat.'
          }}</p>
        </div>

        <div class="link-grid" :class="{ single: activeMode === 'measurement' }">
          <article v-if="activeMode === 'calibration'" class="link-card">
            <div class="qr-wrap"><QrcodeVue :value="sourceLinks.std" :size="152" render-as="svg" /></div>
            <div class="link-copy">
              <span class="source-number">01</span>
              <h3>HP Standar (STD)</h3>
              <p>Buka link ini pada HP yang diarahkan ke alat standar.</p>
              <button type="button" @click="copyLink('std')">
                <i class="iconify" data-icon="feather:copy"></i>
                {{ copiedRole === 'std' ? 'Link tersalin' : 'Salin link STD' }}
              </button>
            </div>
          </article>

          <article class="link-card">
            <div class="qr-wrap"><QrcodeVue :value="sourceLinks.uut" :size="152" render-as="svg" /></div>
            <div class="link-copy">
              <span class="source-number">{{ activeMode === 'measurement' ? '01' : '02' }}</span>
              <h3>HP Unit Under Test</h3>
              <p>{{ activeMode === 'measurement'
                ? 'Buka link ini pada HP yang diarahkan ke display alat yang akan diukur.'
                : 'Buka link ini pada HP yang diarahkan ke unit yang diuji.'
              }}</p>
              <button type="button" @click="copyLink('uut')">
                <i class="iconify" data-icon="feather:copy"></i>
                {{ copiedRole === 'uut' ? 'Link tersalin' : 'Salin link UUT' }}
              </button>
            </div>
          </article>
        </div>
      </section>
    </template>

    <template v-else>
      <section class="source-camera-card">
        <div class="source-instruction">
          <span class="source-kicker">POSISI KAMERA</span>
          <h2>{{ sourceLabel }}</h2>
          <p>Pastikan angka pada display tajam, tidak silau, dan memenuhi area panduan.</p>
        </div>

        <div class="source-video-stage">
          <video ref="localVideo" autoplay playsinline muted></video>
          <div class="focus-frame">
            <span class="corner top-left"></span>
            <span class="corner top-right"></span>
            <span class="corner bottom-left"></span>
            <span class="corner bottom-right"></span>
            <small>Tempatkan display alat di area ini</small>
          </div>
          <div v-if="!localStream" class="video-placeholder source-placeholder">
            <i class="iconify" data-icon="feather:camera-off"></i>
            <strong>Kamera belum aktif</strong>
          </div>
        </div>

        <div class="source-actions">
          <div class="source-state">
            <span class="status-dot" :class="{ active: !!localStream }"></span>
            {{ localStream
              ? roomJoined ? 'Kamera aktif dan sedang menyiapkan video ke ASCI' : 'Kamera aktif, tetapi room kamera belum terhubung'
              : cameraError || 'Menunggu izin kamera' }}
          </div>
          <button type="button" @click="startCamera">
            <i class="iconify" data-icon="feather:camera"></i>
            {{ localStream ? 'Ganti / mulai ulang kamera' : 'Aktifkan kamera' }}
          </button>
        </div>
      </section>
    </template>
  </main>
</template>

<style scoped lang="scss">
.camera-page {
  --ink: #10233f;
  --muted: #64748b;
  --line: #dbe4ee;
  --panel: #ffffff;
  --accent: #0799d3;
  --green: #0fb981;
  min-height: 100vh;
  padding: 34px clamp(18px, 4vw, 64px) 56px;
  color: var(--ink);
  background:
    radial-gradient(circle at 10% 0%, rgba(7, 153, 211, .16), transparent 27rem),
    radial-gradient(circle at 95% 12%, rgba(15, 185, 129, .12), transparent 24rem),
    #f3f6f9;
}

.camera-header,
.room-alert,
.session-bar,
.camera-mode-tabs,
.mode-intro,
.monitor-grid,
.source-links,
.source-camera-card {
  width: min(1480px, 100%);
  margin-inline: auto;
}

.camera-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 24px;
}

.brand-line {
  display: flex;
  align-items: center;
  gap: 15px;
}

.brand-mark {
  display: grid;
  width: 54px;
  height: 54px;
  place-items: center;
  border-radius: 17px;
  color: #fff;
  background: linear-gradient(145deg, #0799d3, #0fb981);
  box-shadow: 0 12px 30px rgba(7, 153, 211, .25);
  font-size: 25px;
}

.eyebrow,
.source-kicker,
.session-label {
  display: block;
  margin: 0 0 4px;
  color: var(--accent);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .14em;
}

.camera-header h1 {
  margin: 0;
  font-size: clamp(25px, 3vw, 40px);
  letter-spacing: -.04em;
}

.subtitle {
  max-width: 690px;
  margin: 13px 0 0 69px;
  color: var(--muted);
  line-height: 1.65;
}

.connection-pill,
.live-badge {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #f1c9c9;
  border-radius: 999px;
  padding: 9px 13px;
  color: #b42318;
  background: #fff5f5;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.connection-pill.connected,
.live-badge.active {
  border-color: #b8ead8;
  color: #087a57;
  background: #ecfdf6;
}

.live-badge.failed {
  border-color: #f2b8b5;
  color: #a12622;
  background: #fff3f2;
}

.status-dot {
  display: inline-block;
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #e74c3c;
  box-shadow: 0 0 0 4px rgba(231, 76, 60, .12);
}

.connected .status-dot,
.status-dot.active {
  background: var(--green);
  box-shadow: 0 0 0 4px rgba(15, 185, 129, .14);
}

.connection-pill.warning {
  border-color: #f5d79a;
  color: #9a6700;
  background: #fff9e9;
}

.connection-pill.warning .status-dot {
  background: #e9a61a;
  box-shadow: 0 0 0 4px rgba(233, 166, 26, .14);
}

.room-alert {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-top: 22px;
  border: 1px solid #f5d79a;
  border-radius: 16px;
  padding: 13px 16px;
  color: #805b12;
  background: #fff9e9;
}

.room-alert.unsupported,
.room-alert.error {
  border-color: #f2b8b5;
  color: #a12622;
  background: #fff3f2;
}

.room-alert .iconify {
  flex: 0 0 auto;
  font-size: 22px;
}

.room-alert div {
  display: grid;
  gap: 2px;
}

.room-alert span {
  font-size: 12px;
}

.session-bar {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-top: 26px;
  padding: 12px;
  border: 1px solid rgba(219, 228, 238, .9);
  border-radius: 18px;
  background: rgba(255, 255, 255, .7);
  backdrop-filter: blur(14px);
}

.session-bar > div {
  min-width: 180px;
  padding: 9px 13px;
  border-right: 1px solid var(--line);
}

.session-bar > div:last-child {
  border-right: 0;
}

.session-bar strong {
  font-size: 13px;
}

.camera-mode-tabs {
  display: flex;
  gap: 10px;
  margin-top: 22px;
  padding: 7px;
  border: 1px solid rgba(219, 228, 238, .9);
  border-radius: 18px;
  background: rgba(255, 255, 255, .82);
}

.camera-mode-tabs button {
  display: flex;
  align-items: center;
  gap: 9px;
  border: 0;
  border-radius: 13px;
  padding: 11px 16px;
  color: var(--muted);
  background: transparent;
  cursor: pointer;
  font-weight: 800;
}

.camera-mode-tabs button.active {
  color: #fff;
  background: linear-gradient(145deg, #0799d3, #0fb981);
  box-shadow: 0 8px 20px rgba(7, 153, 211, .22);
}

.camera-mode-tabs small {
  opacity: .75;
  font-size: 10px;
  font-weight: 700;
}

.mode-intro {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 20px;
  margin-top: 16px;
  border-left: 4px solid var(--accent);
  border-radius: 14px;
  padding: 13px 16px;
  background: rgba(255, 255, 255, .72);
}

.mode-intro h2,
.mode-intro p {
  margin: 0;
}

.mode-intro p {
  color: var(--muted);
  font-size: 13px;
}

.monitor-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 22px;
  margin-top: 24px;
}

.monitor-grid.single,
.link-grid.single {
  grid-template-columns: minmax(0, 1fr);
}

.monitor-grid.single .monitor-card,
.link-grid.single .link-card {
  width: min(100%, 920px);
  margin-inline: auto;
}

.monitor-card,
.source-links,
.source-camera-card {
  border: 1px solid rgba(219, 228, 238, .95);
  border-radius: 24px;
  background: var(--panel);
  box-shadow: 0 18px 60px rgba(15, 35, 64, .08);
}

.monitor-card {
  padding: 18px;
}

.monitor-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 15px;
}

.monitor-title h2,
.section-heading h2,
.source-instruction h2 {
  margin: 0;
  font-size: 19px;
}

.live-badge {
  padding: 6px 10px;
  border-color: var(--line);
  color: var(--muted);
  background: #f8fafc;
  font-size: 10px;
}

.video-stage,
.source-video-stage {
  position: relative;
  overflow: hidden;
  aspect-ratio: 16 / 10;
  border-radius: 17px;
  background: #08121f;
}

.video-stage video,
.source-video-stage video {
  width: 100%;
  height: 100%;
  object-fit: contain;
}

.video-placeholder {
  position: absolute;
  inset: 0;
  display: grid;
  place-content: center;
  justify-items: center;
  gap: 8px;
  padding: 24px;
  color: #c9d7e7;
  text-align: center;
  background: radial-gradient(circle at center, #172a40, #08121f 68%);
}

.playback-overlay {
  position: absolute;
  inset: 0;
  display: grid;
  place-content: center;
  justify-items: center;
  gap: 9px;
  padding: 24px;
  color: #d8e6f4;
  text-align: center;
  background: rgba(8, 18, 31, .82);
  backdrop-filter: blur(3px);
}

.playback-overlay > .iconify {
  color: #79a8d2;
  font-size: 38px;
}

.playback-overlay span {
  color: #91abc3;
  font-size: 11px;
}

.playback-overlay button {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  margin-top: 4px;
  border: 0;
  border-radius: 10px;
  padding: 9px 13px;
  color: #fff;
  background: var(--accent);
  cursor: pointer;
  font-weight: 700;
}

.stream-diagnostic {
  margin-top: 10px;
  color: var(--muted);
  font-size: 11px;
}

.stream-diagnostic.failed {
  color: #b42318;
  font-weight: 700;
}

.video-placeholder .iconify {
  margin-bottom: 4px;
  color: #4f789e;
  font-size: 40px;
}

.video-placeholder span {
  color: #7f98b1;
  font-size: 12px;
}

.source-links {
  margin-top: 24px;
  padding: clamp(20px, 3vw, 34px);
}

.section-heading {
  display: flex;
  align-items: flex-end;
  justify-content: space-between;
  gap: 25px;
  margin-bottom: 22px;
}

.section-heading p {
  max-width: 570px;
  margin: 0;
  color: var(--muted);
  font-size: 13px;
  line-height: 1.6;
}

.link-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 18px;
}

.link-card {
  display: flex;
  align-items: center;
  gap: 20px;
  padding: 18px;
  border: 1px solid var(--line);
  border-radius: 18px;
  background: #f8fafc;
}

.qr-wrap {
  flex: 0 0 auto;
  padding: 9px;
  border: 1px solid var(--line);
  border-radius: 14px;
  background: #fff;
}

.link-copy h3 {
  margin: 4px 0 6px;
  font-size: 17px;
}

.link-copy p,
.source-instruction p {
  margin: 0 0 15px;
  color: var(--muted);
  font-size: 12px;
  line-height: 1.55;
}

.source-number {
  color: var(--accent);
  font-size: 11px;
  font-weight: 800;
  letter-spacing: .12em;
}

.link-copy button,
.source-actions button {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border: 0;
  border-radius: 10px;
  padding: 10px 13px;
  color: #fff;
  background: var(--ink);
  cursor: pointer;
  font-weight: 700;
}

.source-camera-card {
  margin-top: 24px;
  padding: clamp(18px, 3vw, 32px);
}

.source-instruction {
  margin-bottom: 18px;
}

.source-video-stage {
  width: min(100%, 1050px);
  margin-inline: auto;
  aspect-ratio: 16 / 9;
}

.focus-frame {
  position: absolute;
  inset: 16%;
  display: grid;
  place-items: end center;
  pointer-events: none;
}

.focus-frame small {
  margin-bottom: 12px;
  border-radius: 999px;
  padding: 7px 11px;
  color: #fff;
  background: rgba(8, 18, 31, .72);
}

.corner {
  position: absolute;
  width: 58px;
  height: 58px;
  border-color: #35e2ab;
  border-style: solid;
}

.corner.top-left {
  top: 0;
  left: 0;
  border-width: 4px 0 0 4px;
}

.corner.top-right {
  top: 0;
  right: 0;
  border-width: 4px 4px 0 0;
}

.corner.bottom-left {
  bottom: 0;
  left: 0;
  border-width: 0 0 4px 4px;
}

.corner.bottom-right {
  right: 0;
  bottom: 0;
  border-width: 0 4px 4px 0;
}

.source-placeholder {
  background: rgba(8, 18, 31, .92);
}

.source-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  width: min(100%, 1050px);
  margin: 18px auto 0;
}

.source-state {
  display: flex;
  align-items: center;
  gap: 10px;
  color: var(--muted);
  font-size: 13px;
}

.source-actions button {
  background: var(--accent);
}

@media (max-width: 900px) {
  .camera-page {
    padding: 22px 14px 38px;
  }

  .camera-header,
  .section-heading,
  .mode-intro,
  .source-actions {
    align-items: stretch;
    flex-direction: column;
  }

  .subtitle {
    margin-left: 0;
  }

  .connection-pill {
    align-self: flex-start;
  }

  .monitor-grid,
  .link-grid {
    grid-template-columns: 1fr;
  }

  .camera-mode-tabs {
    display: grid;
    grid-template-columns: 1fr;
  }

  .link-card {
    align-items: flex-start;
  }
}

@media (max-width: 560px) {
  .session-bar > div {
    width: 100%;
    border-right: 0;
    border-bottom: 1px solid var(--line);
  }

  .session-bar > div:last-child {
    border-bottom: 0;
  }

  .link-card {
    flex-direction: column;
  }

  .qr-wrap {
    align-self: center;
  }

  .video-stage {
    aspect-ratio: 4 / 3;
  }
}
</style>
