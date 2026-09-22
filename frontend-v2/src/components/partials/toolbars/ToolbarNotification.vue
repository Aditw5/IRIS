<script setup lang="ts">
import { computed, ref, onMounted, onBeforeUnmount } from 'vue'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'
import Badge from 'primevue/badge'
import { useRouter } from 'vue-router'
import { socket, joinNotifPegawai } from '/@src/socket.js'

const dropdownElement = ref<HTMLElement | null>(null)
const listNotif: any = ref([])
const router = useRouter()
const isNotifOpen = ref(false)
const deletingNotif = ref<string | null>(null)
const unreadCount = computed(() => listNotif.value.filter((item: any) => !item.isread).length)

const soundNotif = '/audio/iphone_text_message.mp3'
const isChatNotification = (item: any) => item?.jenis === 'Chat Pegawai'

const sortNotif = () => {
  listNotif.value.sort((a: any, b: any) => {
    const ta = new Date(a.tgl).getTime()
    const tb = new Date(b.tgl).getTime()
    return tb - ta
  })
}

const hasUnreadNotif = () => {
  return listNotif.value.some((x: any) => !x.isread)
}

const openNotifIfUnread = () => {
  if (hasUnreadNotif()) {
    isNotifOpen.value = true
  }
}

const toggleNotif = () => {
  isNotifOpen.value = !isNotifOpen.value
}

const closeNotif = () => {
  isNotifOpen.value = false
}

const handleClickOutside = (event: MouseEvent) => {
  if (!dropdownElement.value) return

  const target = event.target as Node
  if (!dropdownElement.value.contains(target)) {
    isNotifOpen.value = false
  }
}

const formatTimeAgo = (timestamp: any) => {
  if (!timestamp) return '-'

  const inputDate = new Date(timestamp)
  const currentDate = new Date()

  const diff = currentDate.getTime() - inputDate.getTime()
  const seconds = Math.floor(diff / 1000)
  const minutes = Math.floor(seconds / 60)
  const hours = Math.floor(minutes / 60)
  const days = Math.floor(hours / 24)

  if (seconds < 60) return 'baru saja'
  if (minutes < 60) return `${minutes} menit lalu`
  if (hours < 24) return `${hours} jam lalu`
  return `${days} hari lalu`
}

const playNotifSound = () => {
  try {
    const audio = new Audio(soundNotif)
    audio.play().catch(() => { })
  } catch (err) { }
}

const normalizeJsonField = (val: any) => {
  if (!val) return null
  if (typeof val === 'object') return val
  try {
    return JSON.parse(val)
  } catch (err) {
    return val
  }
}

const loadNotif = async () => {
  listNotif.value = []

  const idPegawai = H.pegawaiLogin()?.id
  if (!idPegawai) return

  try {
    const e: any = await useApi().postNoMessage('general/store-notif', {
      method: 'get',
      idPegawai: idPegawai
    })

    const rows = e?.response?.data || []

    rows.filter((element: any) => !isChatNotification(element)).forEach((element: any) => {
      listNotif.value.push({
        norec: element.norec,
        norec_trans: element.norec_trans,
        judul: element.judul,
        jenis: element.jenis,
        idPegawai: element.pegawaifk,
        namapegawai: element.namapegawai,
        pesanNotifikasi: element.keterangan,
        tgl: element.tgl,
        tgl_string: element.tgl_string,
        urlForm: element.urlform,
        params: normalizeJsonField(element.params),
        dataArray: normalizeJsonField(element.dataarray),
        isread: element.isread === true || element.isread === 'true' || element.isread === 1 || element.isread === '1'
      })
    })

    sortNotif()
    openNotifIfUnread()
  } catch (err) {
    console.error('Gagal load notif', err)
  }
}

const removeNotifFromList = (norec: string) => {
  listNotif.value = listNotif.value.filter((item: any) => item.norec !== norec)

  if (listNotif.value.length === 0) {
    closeNotif()
  }
}

const deactivateNotif = async (item: any) => {
  if (!item?.norec || deletingNotif.value) return false

  deletingNotif.value = item.norec
  removeNotifFromList(item.norec)

  try {
    await useApi().postNoMessage('general/store-notif', {
      method: 'delete-item',
      norec: item.norec
    })
    return true
  } catch (err) {
    listNotif.value.push(item)
    sortNotif()
    H.alert('error', 'Gagal menghapus notifikasi')
    return false
  } finally {
    deletingNotif.value = null
  }
}

const goto = async (item: any) => {
  const isDeleted = await deactivateNotif(item)
  if (!isDeleted) return

  if (item.urlForm) {
    if (item.params && typeof item.params === 'object') {
      router.push({
        name: item.urlForm,
        query: item.params
      })
    } else {
      router.push({ name: item.urlForm })
    }
  }
}

const deleteNotif = async (item: any) => {
  await deactivateNotif(item)
}

const handleSocketNotification = (payload: any) => {
  const loginPegawaiId = H.pegawaiLogin()?.id
  if (!loginPegawaiId) return
  if (Number(payload.idPegawai) !== Number(loginPegawaiId)) return

  if (payload.action === 'deleted') {
    removeNotifFromList(payload.norec)
    return
  }

  if (isChatNotification(payload)) return

  const exists = listNotif.value.some((x: any) => x.norec === payload.norec)
  if (!exists) {
    listNotif.value.unshift({
      norec: payload.norec,
      norec_trans: payload.norec_trans,
      judul: payload.judul,
      jenis: payload.jenis,
      idPegawai: payload.idPegawai,
      namapegawai: payload.namapegawai,
      pesanNotifikasi: payload.pesanNotifikasi,
      tgl: payload.tgl,
      tgl_string: payload.tgl_string,
      urlForm: payload.urlForm,
      params: normalizeJsonField(payload.params),
      dataArray: normalizeJsonField(payload.dataArray),
      isread: false
    })

    sortNotif()
    playNotifSound()
    isNotifOpen.value = true
  }
}

onMounted(async () => {
  document.addEventListener('click', handleClickOutside)

  const idPegawai = H.pegawaiLogin()?.id
  if (idPegawai) {
    joinNotifPegawai(idPegawai)
  }

  await loadNotif()

  socket.on('notification', handleSocketNotification)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleClickOutside)
  socket.off('notification', handleSocketNotification)
})
</script>

<template>
  <div class="toolbar-notifications app-notification-dropdown is-hidden-mobile">
    <div ref="dropdownElement" class="dropdown is-spaced is-dots is-right dropdown-trigger"
      :class="{ 'is-active': isNotifOpen }">
      <div tabindex="0" class="is-trigger" aria-haspopup="true" @click.stop="toggleNotif"
        @keydown.space.prevent="toggleNotif">
        <i aria-hidden="true" class="iconify" data-icon="feather:bell"></i>
        <span class="badge2 badge2-danger pulsate">
          {{ unreadCount }}
        </span>
      </div>

      <div class="dropdown-menu" role="menu">
        <div class="dropdown-content">
          <div class="heading">
            <div class="heading-left">
              <h6 class="heading-title">
                <Badge
                  v-if="unreadCount"
                  :value="unreadCount > 50 ? '50+' : unreadCount"
                  severity="danger"
                  class="mr-2"
                />
                Notifikasi
              </h6>
            </div>
          </div>

          <TransitionGroup name="notif-list" tag="ul" class="notification-list">
            <li v-if="listNotif.length === 0" key="empty" class="empty-notif">
              Belum ada notifikasi
            </li>

            <li v-for="items in listNotif" :key="items.norec" class="notification-entry">
              <a class="notification-item" :class="{ unread: !items.isread }" @click="goto(items)">
                <div class="img-left">
                  <VAvatar class="user-photo" size="small" color="primary" :initials="H.INITIALS(items.judul)" />
                </div>

                <div class="user-content">
                  <p class="user-info">
                    <span class="name">{{ items.judul }}</span>
                    {{ items.pesanNotifikasi }}
                  </p>
                  <p class="time">{{ formatTimeAgo(items.tgl) }}</p>
                </div>
              </a>

              <button type="button" class="delete-notification" :class="{
                'is-loading': deletingNotif === items.norec
              }" :disabled="deletingNotif !== null" aria-label="Hapus notifikasi" title="Hapus notifikasi"
                @click.stop="deleteNotif(items)">
                <i v-if="deletingNotif !== items.norec" aria-hidden="true" class="iconify"
                  data-icon="feather:trash-2"></i>
              </button>
            </li>
          </TransitionGroup>
        </div>
      </div>
    </div>
  </div>
</template>

<style lang="scss">
.p-badge.p-badge-danger {
  background-color: #D32F2F;
  color: #ffffff;
}

.badge2-danger {
  color: #fff;
  background-color: #dc3545;
}

.badge2 {
  display: inline-block;
  padding: 0.35em 0.6em;
  font-size: 75%;
  font-weight: 700;
  line-height: 1;
  text-align: center;
  white-space: nowrap;
  vertical-align: baseline;
  border-radius: 0.55rem;
  float: right;
}

.empty-notif {
  padding: 14px;
  color: #666;
}

.app-notification-dropdown .dropdown-content {
  overflow: hidden;
}

.app-notification-dropdown .notification-list {
  max-height: min(60vh, 28rem);
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-color: #b8beca transparent;
  scrollbar-width: thin;
}

.app-notification-dropdown .notification-list::-webkit-scrollbar {
  width: 6px;
}

.app-notification-dropdown .notification-list::-webkit-scrollbar-thumb {
  background: #b8beca;
  border-radius: 999px;
}

.app-notification-dropdown .notification-entry {
  display: flex;
  align-items: flex-start;
  max-height: 20rem;
  overflow: hidden;
  border-bottom: 1px solid rgba(160, 166, 180, 0.18);
}

.app-notification-dropdown .notification-entry:last-child {
  border-bottom: 0;
}

.app-notification-dropdown .notification-item {
  flex: 1;
  min-width: 0;
  cursor: pointer;
}

.app-notification-dropdown .notification-item .user-content {
  flex: 1;
  min-width: 0;
}

.app-notification-dropdown .notification-item.unread {
  background: rgba(220, 53, 69, 0.03);
}

.app-notification-dropdown .delete-notification {
  display: inline-flex;
  flex: 0 0 2rem;
  align-items: center;
  justify-content: center;
  width: 2rem;
  height: 2rem;
  padding: 0;
  margin: 0.65rem 0 0 0.35rem;
  color: var(--light-text);
  cursor: pointer;
  background: transparent;
  border: 0;
  border-radius: var(--radius-rounded);
  transition: color 0.2s ease, background-color 0.2s ease;
}

.app-notification-dropdown .delete-notification:hover:not(:disabled),
.app-notification-dropdown .delete-notification:focus-visible {
  color: var(--danger);
  background: rgba(220, 53, 69, 0.1);
}

.app-notification-dropdown .delete-notification:disabled {
  cursor: wait;
  opacity: 0.6;
}

.app-notification-dropdown .notif-list-move,
.app-notification-dropdown .notif-list-enter-active,
.app-notification-dropdown .notif-list-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease, max-height 0.3s ease;
}

.app-notification-dropdown .notif-list-enter-from {
  max-height: 0;
  opacity: 0;
  transform: translateY(-8px);
}

.app-notification-dropdown .notif-list-leave-to {
  max-height: 0;
  opacity: 0;
  transform: translateX(20px);
}
</style>
