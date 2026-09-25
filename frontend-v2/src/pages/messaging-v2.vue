<route lang="yaml">
meta:
  requiresAuth: true
</route>

<script setup lang="ts">
import { useHead } from '@vueuse/head'
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRoute } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { useToaster } from '/@src/composable/toaster'
import { joinNotifPegawai, socket, state as socketState } from '/@src/socket.js'
import { useUserSession } from '/@src/stores/userSession'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { publicFileUrl, resolvePublicFileUrl } from '/@src/utils/publicFileUrl'

type Contact = {
  id: number
  namalengkap: string
  jabatanfk: number | null
  filename_foto: string | null
  room_id?: number | null
  last_message?: string | null
  last_message_at?: string | null
  unread?: number
  online?: boolean
}

type ChatMessage = {
  id: number
  roomfk: number
  senderfk: number
  sender_name: string
  sender_avatar: string | null
  reply_to_message_id: number | null
  reply_statusenabled: boolean | null
  reply_sender_name: string | null
  reply_tipe: 'text' | 'image' | 'file' | null
  reply_message: string | null
  reply_attachment_name: string | null
  tipe: 'text' | 'image' | 'file'
  message: string | null
  attachment_url: string | null
  attachment_name: string | null
  attachment_mime: string | null
  attachment_size: number | null
  created_at: string
}

const api = useApi()
const route = useRoute()
const toaster = useToaster()
const viewWrapper = useViewWrapper()
const fileInput = ref<HTMLInputElement | null>(null)
const messageTextInput = ref<HTMLInputElement | null>(null)
const messageList = ref<HTMLElement | null>(null)
const contacts = ref<Contact[]>([])
const messages = ref<ChatMessage[]>([])
const onlineIds = ref<Set<number>>(new Set())
const selectedContactId = ref<number | null>(null)
const activeRoomId = ref<number | null>(null)
const search = ref('')
const messageInput = ref('')
const selectedAttachment = ref<File | null>(null)
const replyToMessage = ref<ChatMessage | null>(null)
const selectedMessageIds = ref<Set<number>>(new Set())
const highlightedMessageId = ref<number | null>(null)
const loadingContacts = ref(false)
const loadingMessages = ref(false)
const sending = ref(false)
const deletingMessages = ref(false)
const mobileContactsOpen = ref(false)
let conversationLoadSequence = 0
let highlightTimer: ReturnType<typeof setTimeout> | null = null
const previousFullWidth = Boolean(viewWrapper.isFullWidth)
viewWrapper.setFullWidth(true)

const maxAttachmentBytes = 20 * 1024 * 1024
const currentUser = computed(() => {
  try {
    return useUserSession().getUser()
  } catch {
    return null
  }
})

const currentPegawaiId = computed(() => Number(currentUser.value?.pegawai?.id || 0))
const isEmployee = computed(() => Boolean(
  currentPegawaiId.value &&
  String(currentUser.value?.kelompokUser?.kelompokUser || '').toLowerCase() !== 'customer'
))
const selectedContact = computed(() => contacts.value.find((item) => item.id === selectedContactId.value) || null)
const filteredContacts = computed(() => {
  const keyword = search.value.trim().toLowerCase()
  if (!keyword) return contacts.value
  return contacts.value.filter((item) => item.namalengkap.toLowerCase().includes(keyword))
})
const sharedImages = computed(() => messages.value.filter((item) => item.tipe === 'image' && item.attachment_url))
const selectedMessageCount = computed(() => selectedMessageIds.value.size)

function initials(name: string) {
  return name.split(/\s+/).filter(Boolean).slice(0, 2).map((part) => part[0]).join('').toUpperCase() || '?'
}

function publicFile(path?: string | null, folder?: string) {
  if (!path) return ''
  return resolvePublicFileUrl(path, folder || 'chat-attachments')
}

function avatarUrl(filename?: string | null) {
  if (!filename) return ''
  if (/^https?:\/\//i.test(filename)) return filename
  return publicFileUrl('berkas-mutu', filename)
}

function messageAttachmentUrl(message: ChatMessage) {
  return publicFile(message.attachment_url)
}

function formatTime(value?: string | null) {
  if (!value) return ''
  return new Intl.DateTimeFormat('id-ID', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(value))
}

function formatConversationTime(value?: string | null) {
  if (!value) return ''
  const date = new Date(value)
  const today = new Date()
  if (date.toDateString() === today.toDateString()) return formatTime(value)
  return new Intl.DateTimeFormat('id-ID', { day: '2-digit', month: 'short' }).format(date)
}

function formatBytes(bytes?: number | null) {
  if (!bytes) return ''
  if (bytes < 1024 * 1024) return `${Math.ceil(bytes / 1024)} KB`
  return `${(bytes / 1024 / 1024).toFixed(1)} MB`
}

function messageSummary(message: ChatMessage) {
  if (message.tipe === 'image') return message.message || 'Gambar'
  if (message.tipe === 'file') return message.message || message.attachment_name || 'File'
  return message.message || 'Pesan'
}

function replySummary(message: ChatMessage) {
  if (message.reply_statusenabled === false) return 'Pesan telah dihapus'
  if (message.reply_tipe === 'image') return message.reply_message || 'Gambar'
  if (message.reply_tipe === 'file') return message.reply_message || message.reply_attachment_name || 'File'
  return message.reply_message || 'Pesan'
}

async function loadContacts() {
  if (!isEmployee.value) return
  loadingContacts.value = true
  try {
    const [contactResponse, roomResponse]: any[] = await Promise.all([
      api.get('chat/contacts'),
      api.get('chat/rooms'),
    ])
    const roomMap = new Map<number, any>(
      (roomResponse?.rooms || []).map((room: any) => [Number(room.pegawai_id), room])
    )
    contacts.value = (contactResponse?.data || []).map((contact: Contact) => {
      const room = roomMap.get(Number(contact.id))
      return {
        ...contact,
        id: Number(contact.id),
        room_id: room ? Number(room.room_id) : null,
        last_message: room?.last_message || null,
        last_message_at: room?.last_message_at || null,
        unread: Number(room?.unread || 0),
        online: onlineIds.value.has(Number(contact.id)),
      }
    }).sort((a: Contact, b: Contact) => {
      if (a.last_message_at && b.last_message_at) {
        return new Date(b.last_message_at).getTime() - new Date(a.last_message_at).getTime()
      }
      if (a.last_message_at) return -1
      if (b.last_message_at) return 1
      return a.namalengkap.localeCompare(b.namalengkap)
    })

    const queryContact = Number(route.query.pegawaiId || 0)
    if (!selectedContactId.value && contacts.value.length) {
      const initialContactId = contacts.value.some((item) => item.id === queryContact)
        ? queryContact
        : contacts.value[0].id
      const initialContact = contacts.value.find((item) => item.id === initialContactId)
      if (initialContact) {
        await selectContact(initialContact)
      }
    }
  } catch (error: any) {
    toaster.error(error || 'Gagal memuat kontak pegawai')
  } finally {
    loadingContacts.value = false
  }
}

async function ensureRoom(contact: Contact) {
  if (contact.room_id) return Number(contact.room_id)
  const raw: any = await api.postNoMessage('chat/room/upsert', { targetPegawaiId: contact.id })
  const roomId = Number(raw?.response?.room?.id || 0)
  if (!roomId) throw new Error('Ruang chat tidak dapat dibuat')
  contact.room_id = roomId
  return roomId
}

async function selectContact(contact: Contact) {
  const contactId = Number(contact.id)
  const requestSequence = ++conversationLoadSequence
  const isDifferentContact = selectedContactId.value !== contactId

  selectedContactId.value = contactId
  if (isDifferentContact) {
    activeRoomId.value = null
    messages.value = []
    cancelSelection()
    cancelReply()
  }
  mobileContactsOpen.value = false
  loadingMessages.value = true
  try {
    const roomId = await ensureRoom(contact)
    if (requestSequence !== conversationLoadSequence || selectedContactId.value !== contactId) return

    activeRoomId.value = roomId
    const response: any = await api.get(`chat/messages?roomfk=${roomId}&limit=100`)
    if (
      requestSequence !== conversationLoadSequence ||
      selectedContactId.value !== contactId ||
      Number(activeRoomId.value) !== roomId
    ) return

    messages.value = response?.messages || []
    await markRead(roomId)
    if (requestSequence !== conversationLoadSequence) return
    await scrollToBottom()
  } catch (error: any) {
    if (requestSequence === conversationLoadSequence) {
      toaster.error(error?.message || error || 'Gagal memuat percakapan')
    }
  } finally {
    if (requestSequence === conversationLoadSequence) {
      loadingMessages.value = false
    }
  }
}

async function markRead(roomId = Number(activeRoomId.value)) {
  if (!roomId) return
  try {
    await api.postNoMessage('chat/rooms/read', { roomfk: roomId })
    if (Number(activeRoomId.value) === roomId) {
      const contact = selectedContact.value
      if (contact) contact.unread = 0
    }
  } catch {
    // Read receipt akan dicoba kembali saat percakapan dibuka lagi.
  }
}

function chooseAttachment() {
  fileInput.value?.click()
}

function handleAttachment(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0] || null
  if (file && file.size > maxAttachmentBytes) {
    toaster.error('Ukuran file maksimal 20 MB')
    input.value = ''
    selectedAttachment.value = null
    return
  }
  selectedAttachment.value = file
}

function clearAttachment() {
  selectedAttachment.value = null
  if (fileInput.value) fileInput.value.value = ''
}

async function startReply(message: ChatMessage) {
  replyToMessage.value = message
  await nextTick()
  messageTextInput.value?.focus()
}

function cancelReply() {
  replyToMessage.value = null
}

function toggleMessageSelection(message: ChatMessage) {
  if (Number(message.senderfk) !== currentPegawaiId.value) return
  const next = new Set(selectedMessageIds.value)
  if (next.has(Number(message.id))) next.delete(Number(message.id))
  else next.add(Number(message.id))
  selectedMessageIds.value = next
}

function cancelSelection() {
  selectedMessageIds.value = new Set()
}

function removeMessages(messageIds: number[]) {
  const deleted = new Set(messageIds.map(Number))
  messages.value = messages.value
    .filter((message) => !deleted.has(Number(message.id)))
    .map((message) => {
      if (!message.reply_to_message_id || !deleted.has(Number(message.reply_to_message_id))) return message
      return {
        ...message,
        reply_statusenabled: false,
        reply_tipe: null,
        reply_message: null,
        reply_attachment_name: null,
      }
    })

  if (replyToMessage.value && deleted.has(Number(replyToMessage.value.id))) cancelReply()
}

async function deleteSelectedMessages() {
  if (!activeRoomId.value || !selectedMessageIds.value.size || deletingMessages.value) return
  if (!window.confirm(`Hapus ${selectedMessageIds.value.size} pesan terpilih?`)) return

  const roomId = Number(activeRoomId.value)
  const messageIds = Array.from(selectedMessageIds.value)
  deletingMessages.value = true
  try {
    const raw: any = await api.postNoMessage('chat/messages/delete', {
      roomfk: roomId,
      messageIds,
    })
    const deletedIds = (raw?.response?.message_ids || messageIds).map(Number)
    if (Number(activeRoomId.value) === roomId) removeMessages(deletedIds)
    cancelSelection()
    toaster.success('Pesan terpilih dihapus')
    await loadContacts()
  } catch (error: any) {
    toaster.error(error?.data?.metaData?.message || 'Gagal menghapus pesan')
  } finally {
    deletingMessages.value = false
  }
}

async function sendMessage() {
  const text = messageInput.value.trim()
  if ((!text && !selectedAttachment.value) || !activeRoomId.value || sending.value) return

  const roomId = Number(activeRoomId.value)
  sending.value = true
  try {
    const form = new FormData()
    form.append('roomfk', String(roomId))
    if (text) form.append('message', text)
    if (selectedAttachment.value) form.append('attachment', selectedAttachment.value)
    if (replyToMessage.value) form.append('reply_to_message_id', String(replyToMessage.value.id))

    const raw: any = await api.postNoMessage('chat/messages/send', form)
    const saved: ChatMessage | undefined = raw?.response?.message
    if (
      saved &&
      Number(saved.roomfk) === roomId &&
      Number(activeRoomId.value) === roomId &&
      !messages.value.some((item) => Number(item.id) === Number(saved.id))
    ) {
      messages.value.push(saved)
    }
    messageInput.value = ''
    clearAttachment()
    cancelReply()
    if (Number(activeRoomId.value) === roomId) {
      await scrollToBottom()
    }
    await loadContacts()
  } catch (error: any) {
    const message = error?.status === 413
      ? 'Ukuran file melebihi batas upload server'
      : error?.data?.metaData?.message
    toaster.error(message || 'Gagal mengirim pesan')
  } finally {
    sending.value = false
  }
}

async function scrollToMessage(messageId: number) {
  await nextTick()
  const target = messageList.value?.querySelector<HTMLElement>(`[data-message-id="${Number(messageId)}"]`)
  if (!target) {
    toaster.error('Pesan asal tidak ada dalam daftar yang dimuat')
    return
  }

  target.scrollIntoView({ behavior: 'smooth', block: 'center' })
  highlightedMessageId.value = Number(messageId)
  if (highlightTimer) clearTimeout(highlightTimer)
  highlightTimer = setTimeout(() => {
    highlightedMessageId.value = null
  }, 1800)
}

async function scrollToBottom() {
  await nextTick()
  if (messageList.value) {
    messageList.value.scrollTop = messageList.value.scrollHeight
  }
}

function refreshPresence() {
  socket.emit('get-online-pegawai', (ids: number[]) => {
    onlineIds.value = new Set((ids || []).map(Number))
    contacts.value.forEach((contact) => {
      contact.online = onlineIds.value.has(contact.id)
    })
  })
}

function handlePresence(payload: any) {
  const next = new Set(onlineIds.value)
  const pegawaiId = Number(payload?.pegawaiId)
  if (payload?.online) next.add(pegawaiId)
  else next.delete(pegawaiId)
  onlineIds.value = next
  const contact = contacts.value.find((item) => item.id === pegawaiId)
  if (contact) contact.online = Boolean(payload?.online)
}

async function handleIncomingMessage(message: ChatMessage) {
  const roomId = Number(message.roomfk)
  if (roomId === Number(activeRoomId.value)) {
    if (!messages.value.some((item) => Number(item.id) === Number(message.id))) {
      messages.value.push(message)
      await scrollToBottom()
    }
    await markRead(roomId)
  }
  await loadContacts()
}

async function handleDeletedMessages(payload: any) {
  const roomId = Number(payload?.roomfk)
  const messageIds = Array.isArray(payload?.message_ids) ? payload.message_ids.map(Number) : []
  if (roomId === Number(activeRoomId.value) && messageIds.length) {
    removeMessages(messageIds)
  }
  await loadContacts()
}

useHead({ title: 'Chat Pegawai - U-LAB' })

onMounted(async () => {
  if (!isEmployee.value) return
  joinNotifPegawai(currentPegawaiId.value)
  socket.on('pegawai-presence', handlePresence)
  socket.on('chat-message', handleIncomingMessage)
  socket.on('chat-message-deleted', handleDeletedMessages)
  socket.on('connect', refreshPresence)
  refreshPresence()
  await loadContacts()
})

onBeforeUnmount(() => {
  viewWrapper.setFullWidth(previousFullWidth)
  socket.off('pegawai-presence', handlePresence)
  socket.off('chat-message', handleIncomingMessage)
  socket.off('chat-message-deleted', handleDeletedMessages)
  socket.off('connect', refreshPresence)
  if (highlightTimer) clearTimeout(highlightTimer)
})
</script>

<template>
  <NavbarLayout nowrap>
    <div v-if="!isEmployee" class="employee-only">
      <VIconWrap icon="feather:lock" />
      <h2>Chat khusus pegawai</h2>
      <p>Akun customer tidak memiliki akses ke daftar pegawai atau percakapan internal.</p>
    </div>

    <div v-else class="employee-chat">
      <main class="chat-shell">
        <aside class="contacts-panel" :class="{ 'is-mobile-open': mobileContactsOpen }">
          <div class="contacts-heading">
            <span>
              <strong>Percakapan</strong>
              <small :class="{ online: socketState.connected }">
                {{ socketState.connected ? 'Realtime terhubung' : 'Menghubungkan realtime...' }}
              </small>
            </span>
            <button class="mobile-close" aria-label="Tutup daftar kontak" @click="mobileContactsOpen = false">
              <i class="iconify" data-icon="feather:x"></i>
            </button>
          </div>
          <div class="contact-search">
            <i class="iconify" data-icon="feather:search"></i>
            <input v-model="search" type="search" placeholder="Cari pegawai..." />
          </div>
          <div class="contacts-list">
            <div v-if="loadingContacts" class="empty-state">Memuat kontak...</div>
            <button
              v-for="contact in filteredContacts"
              :key="contact.id"
              class="contact-row"
              :class="{ active: contact.id === selectedContactId }"
              @click="selectContact(contact)"
            >
              <VAvatar
                :picture="avatarUrl(contact.filename_foto)"
                :initials="initials(contact.namalengkap)"
                color="h-purple"
                :dot="contact.online"
              />
              <span class="contact-copy">
                <span class="contact-name">{{ contact.namalengkap }}</span>
                <span class="contact-preview">{{ contact.last_message || (contact.online ? 'Online' : 'Belum ada pesan') }}</span>
              </span>
              <span class="contact-meta">
                <small>{{ formatConversationTime(contact.last_message_at) }}</small>
                <b v-if="contact.unread">{{ contact.unread > 99 ? '99+' : contact.unread }}</b>
              </span>
            </button>
            <div v-if="!loadingContacts && filteredContacts.length === 0" class="empty-state">
              Pegawai tidak ditemukan
            </div>
          </div>
        </aside>

        <section class="conversation-panel">
          <div v-if="selectedContact" class="conversation-header">
            <div v-if="selectedMessageCount" class="selection-toolbar">
              <button type="button" aria-label="Batalkan pilihan" @click="cancelSelection">
                <i class="iconify" data-icon="feather:x"></i>
              </button>
              <strong>{{ selectedMessageCount }} pesan dipilih</strong>
              <button
                type="button"
                class="delete-selection"
                :disabled="deletingMessages"
                aria-label="Hapus pesan terpilih"
                @click="deleteSelectedMessages"
              >
                <i class="iconify" data-icon="feather:trash-2"></i>
                <span>Hapus</span>
              </button>
            </div>
            <div v-else class="conversation-header-main">
              <button class="mobile-menu" aria-label="Buka daftar kontak" @click="mobileContactsOpen = true">
                <i class="iconify" data-icon="feather:menu"></i>
              </button>
              <VAvatar
                :picture="avatarUrl(selectedContact.filename_foto)"
                :initials="initials(selectedContact.namalengkap)"
                color="h-purple"
                :dot="selectedContact.online"
              />
              <div>
                <strong>{{ selectedContact.namalengkap }}</strong>
                <span :class="{ online: selectedContact.online }">
                  {{ selectedContact.online ? 'Online' : 'Offline' }}
                </span>
              </div>
            </div>
          </div>

          <div ref="messageList" class="message-list">
            <div v-if="loadingMessages" class="empty-state centered">Memuat pesan...</div>
            <div v-else-if="messages.length === 0" class="empty-state centered">
              <VIconWrap icon="feather:message-circle" />
              <strong>Belum ada pesan</strong>
              <span>Mulai percakapan dengan {{ selectedContact?.namalengkap || 'pegawai' }}.</span>
            </div>
            <div
              v-for="message in messages"
              :key="message.id"
              :data-message-id="message.id"
              class="message-row"
              :class="{
                owner: Number(message.senderfk) === currentPegawaiId,
                selected: selectedMessageIds.has(Number(message.id)),
                highlighted: highlightedMessageId === Number(message.id),
              }"
              @click="selectedMessageCount && toggleMessageSelection(message)"
            >
              <VAvatar
                :picture="avatarUrl(message.sender_avatar)"
                :initials="initials(message.sender_name)"
                color="h-purple"
                size="small"
              />
              <div class="message-content">
                <div class="message-bubble">
                  <button
                    v-if="message.reply_to_message_id"
                    type="button"
                    class="reply-quote"
                    @click.stop="scrollToMessage(message.reply_to_message_id)"
                  >
                    <strong>{{ message.reply_sender_name || 'Pesan' }}</strong>
                    <span>
                      <i
                        v-if="message.reply_tipe === 'image' || message.reply_tipe === 'file'"
                        class="iconify"
                        :data-icon="message.reply_tipe === 'image' ? 'feather:image' : 'feather:file'"
                      ></i>
                      {{ replySummary(message) }}
                    </span>
                  </button>
                  <a
                    v-if="message.tipe === 'image' && message.attachment_url"
                    :href="messageAttachmentUrl(message)"
                    target="_blank"
                    rel="noopener"
                    class="image-attachment"
                  >
                    <img :src="messageAttachmentUrl(message)" :alt="message.attachment_name || 'Gambar chat'" />
                  </a>
                  <a
                    v-else-if="message.tipe === 'file' && message.attachment_url"
                    :href="messageAttachmentUrl(message)"
                    target="_blank"
                    rel="noopener"
                    class="file-attachment"
                  >
                    <i class="iconify" data-icon="feather:file-text"></i>
                    <span>
                      <strong>{{ message.attachment_name || 'Lampiran' }}</strong>
                      <small>{{ formatBytes(message.attachment_size) }}</small>
                    </span>
                    <i class="iconify" data-icon="feather:download"></i>
                  </a>
                  <p v-if="message.message">{{ message.message }}</p>
                </div>
                <time>{{ formatTime(message.created_at) }}</time>
              </div>
              <div class="message-actions" @click.stop>
                <button type="button" aria-label="Balas pesan" title="Balas" @click="startReply(message)">
                  <i class="iconify" data-icon="feather:corner-up-left"></i>
                </button>
                <button
                  v-if="Number(message.senderfk) === currentPegawaiId"
                  type="button"
                  :class="{ active: selectedMessageIds.has(Number(message.id)) }"
                  aria-label="Pilih pesan"
                  title="Pilih untuk dihapus"
                  @click="toggleMessageSelection(message)"
                >
                  <i
                    class="iconify"
                    :data-icon="selectedMessageIds.has(Number(message.id)) ? 'feather:check-circle' : 'feather:circle'"
                  ></i>
                </button>
              </div>
            </div>
          </div>

          <form class="message-composer" @submit.prevent="sendMessage">
            <div v-if="replyToMessage || selectedAttachment" class="composer-previews">
              <div v-if="replyToMessage" class="reply-preview">
                <i class="iconify" data-icon="feather:corner-up-left"></i>
                <span>
                  <strong>Membalas {{ replyToMessage.sender_name }}</strong>
                  <small>{{ messageSummary(replyToMessage) }}</small>
                </span>
                <button type="button" aria-label="Batalkan balasan" @click="cancelReply">
                  <i class="iconify" data-icon="feather:x"></i>
                </button>
              </div>
              <div v-if="selectedAttachment" class="attachment-chip">
                <i class="iconify" :data-icon="selectedAttachment.type.startsWith('image/') ? 'feather:image' : 'feather:file'"></i>
                <span>{{ selectedAttachment.name }}</span>
                <button type="button" aria-label="Hapus lampiran" @click="clearAttachment">
                  <i class="iconify" data-icon="feather:x"></i>
                </button>
              </div>
            </div>
            <input
              ref="fileInput"
              type="file"
              hidden
              accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar,.txt,.csv"
              @change="handleAttachment"
            />
            <button type="button" class="composer-action" aria-label="Lampirkan gambar atau file" @click="chooseAttachment">
              <i class="iconify" data-icon="feather:paperclip"></i>
            </button>
            <input
              ref="messageTextInput"
              v-model="messageInput"
              type="text"
              maxlength="5000"
              placeholder="Tulis pesan..."
              :disabled="sending || !selectedContact"
            />
            <button class="send-button" type="submit" :disabled="sending || (!messageInput.trim() && !selectedAttachment)">
              <i class="iconify" data-icon="feather:send"></i>
            </button>
          </form>
        </section>

        <aside v-if="selectedContact" class="profile-panel">
          <VAvatar
            :picture="avatarUrl(selectedContact.filename_foto)"
            :initials="initials(selectedContact.namalengkap)"
            color="h-purple"
            size="xl"
            :dot="selectedContact.online"
          />
          <h2>{{ selectedContact.namalengkap }}</h2>
          <span :class="{ online: selectedContact.online }">{{ selectedContact.online ? 'Online' : 'Offline' }}</span>
          <div class="profile-divider"></div>
          <div class="shared-title">
            <i class="iconify" data-icon="feather:image"></i>
            <strong>Gambar dibagikan</strong>
          </div>
          <div class="shared-images">
            <a
              v-for="image in sharedImages.slice(-9).reverse()"
              :key="image.id"
              :href="messageAttachmentUrl(image)"
              target="_blank"
              rel="noopener"
            >
              <img :src="messageAttachmentUrl(image)" :alt="image.attachment_name || 'Gambar chat'" />
            </a>
          </div>
          <span v-if="sharedImages.length === 0" class="no-shared">Belum ada gambar dibagikan</span>
        </aside>
      </main>
    </div>
  </NavbarLayout>
</template>

<style lang="scss">
.employee-only {
  min-height: calc(100vh - 110px);
  min-height: calc(100dvh - 110px);
  margin-top: 110px;
  display: grid;
  place-content: center;
  gap: 12px;
  padding: 24px;
  text-align: center;

  .icon-wrapper {
    margin: 0 auto;
  }
}

.employee-chat {
  position: fixed;
  top: 110px;
  right: 0;
  bottom: 0;
  left: 0;
  z-index: 1;
  overflow: hidden;
  background: var(--body-color);

  .online {
    color: var(--success) !important;
  }
}

.chat-shell {
  height: 100%;
  min-height: 0;
  display: grid;
  grid-template-columns: 330px minmax(0, 1fr) 300px;
  overflow: hidden;
  background: var(--white);
}

.contacts-panel,
.profile-panel {
  height: 100%;
  min-width: 0;
  min-height: 0;
  background: var(--white);
}

.contacts-panel {
  border-right: 1px solid var(--fade-grey-dark-3);
}

.contacts-heading {
  height: 68px;
  padding: 0 20px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  color: var(--dark-text);

  span {
    min-width: 0;
    display: flex;
    flex-direction: column;
  }

  small {
    color: var(--light-text);
    font-size: 0.72rem;
    font-weight: 400;
  }
}

.mobile-close,
.mobile-menu {
  display: none;
}

.contact-search {
  margin: 0 16px 14px;
  position: relative;

  svg {
    position: absolute;
    top: 12px;
    left: 12px;
    color: var(--light-text);
  }

  input {
    width: 100%;
    height: 42px;
    padding: 0 12px 0 38px;
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: 12px;
    background: var(--fade-grey-light-5);
  }
}

.contacts-list {
  height: calc(100vh - 110px - 124px);
  height: calc(100dvh - 110px - 124px);
  overflow-y: auto;
}

.contact-row {
  width: 100%;
  padding: 13px 16px;
  display: flex;
  gap: 11px;
  align-items: center;
  border: 0;
  border-left: 3px solid transparent;
  background: transparent;
  text-align: left;
  cursor: pointer;

  &:hover,
  &.active {
    background: var(--fade-grey-light-5);
  }

  &.active {
    border-left-color: var(--primary);
  }
}

.contact-copy {
  min-width: 0;
  flex: 1;
  display: flex;
  flex-direction: column;
}

.contact-name {
  overflow: hidden;
  color: var(--dark-text);
  font-weight: 600;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.contact-preview {
  overflow: hidden;
  color: var(--light-text);
  font-size: 0.78rem;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.contact-meta {
  display: flex;
  align-items: flex-end;
  flex-direction: column;
  gap: 5px;

  small {
    color: var(--light-text);
    font-size: 0.68rem;
  }

  b {
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    display: grid;
    place-content: center;
    border-radius: 999px;
    background: var(--primary);
    color: #fff;
    font-size: 0.65rem;
  }
}

.conversation-panel {
  height: 100%;
  min-width: 0;
  min-height: 0;
  display: grid;
  grid-template-rows: 68px minmax(0, 1fr) auto;
  overflow: hidden;
  background: var(--fade-grey-light-5);
}

.conversation-header {
  padding: 0 20px;
  display: flex;
  align-items: center;
  border-bottom: 1px solid var(--fade-grey-dark-3);
  background: var(--white);

  strong {
    color: var(--dark-text);
  }

  span {
    color: var(--light-text);
    font-size: 0.76rem;
  }
}

.conversation-header-main {
  display: flex;
  align-items: center;
  gap: 10px;

  > div:last-child {
    display: flex;
    flex-direction: column;
  }
}

.selection-toolbar {
  width: 100%;
  display: flex;
  align-items: center;
  gap: 12px;

  button {
    height: 36px;
    padding: 0 10px;
    display: flex;
    align-items: center;
    gap: 7px;
    border: 0;
    border-radius: 9px;
    background: transparent;
    color: var(--light-text);
    cursor: pointer;

    &:disabled {
      opacity: 0.5;
      cursor: default;
    }
  }

  .delete-selection {
    margin-left: auto;
    background: var(--danger);
    color: #fff;
  }
}

.message-list {
  min-height: 0;
  padding: 22px;
  overflow-y: auto;
  overscroll-behavior: contain;
  scrollbar-gutter: stable;
}

.message-row {
  margin-bottom: 16px;
  padding: 4px;
  display: flex;
  align-items: flex-end;
  gap: 9px;
  border-radius: 14px;
  transition: background-color 0.2s ease, box-shadow 0.2s ease;

  &.selected {
    background: var(--primary-light-45);
    box-shadow: inset 0 0 0 1px var(--primary);
  }

  &.highlighted {
    background: var(--primary-light-40);
  }

  &:hover .message-actions,
  &:focus-within .message-actions,
  &.selected .message-actions {
    opacity: 1;
  }

  &.owner {
    flex-direction: row-reverse;

    .message-content {
      align-items: flex-end;
    }

    .message-bubble {
      border-radius: 16px 16px 3px;
      background: var(--primary);
      color: #fff !important;

      &,
      * {
        color: #fff !important;
      }
    }
  }
}

.message-actions {
  display: flex;
  align-items: center;
  gap: 3px;
  opacity: 0;
  transition: opacity 0.18s ease;

  button {
    width: 30px;
    height: 30px;
    display: grid;
    place-content: center;
    border: 0;
    border-radius: 50%;
    background: var(--white);
    color: var(--light-text);
    box-shadow: 0 2px 8px rgb(0 0 0 / 8%);
    cursor: pointer;

    &.active {
      background: var(--primary);
      color: #fff;
    }
  }
}

.message-content {
  max-width: min(70%, 560px);
  display: flex;
  align-items: flex-start;
  flex-direction: column;
  gap: 4px;

  time {
    color: var(--light-text);
    font-size: 0.68rem;
  }
}

.message-bubble {
  padding: 11px 14px;
  border-radius: 16px 16px 16px 3px;
  background: var(--white);
  color: var(--dark-text);
  box-shadow: 0 2px 8px rgb(0 0 0 / 5%);

  p {
    margin: 6px 0 0;
    color: inherit;
    white-space: pre-wrap;
    overflow-wrap: anywhere;
  }
}

.reply-quote {
  width: 100%;
  margin-bottom: 7px;
  padding: 7px 9px;
  display: flex;
  flex-direction: column;
  gap: 2px;
  overflow: hidden;
  border: 0;
  border-left: 3px solid currentColor;
  border-radius: 7px;
  background: rgb(0 0 0 / 7%);
  color: inherit;
  text-align: left;
  cursor: pointer;

  strong,
  span {
    overflow: hidden;
    color: inherit;
    font-size: 0.74rem;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  span {
    display: flex;
    align-items: center;
    gap: 4px;
    opacity: 0.78;
  }
}

.image-attachment {
  display: block;
  cursor: zoom-in;

  img {
    width: auto;
    min-width: 140px;
    display: block;
    max-width: 320px;
    max-height: 300px;
    border-radius: 10px;
    object-fit: cover;
  }
}

.file-attachment {
  min-width: 230px;
  display: flex;
  align-items: center;
  gap: 10px;
  color: inherit;

  > svg {
    width: 22px;
    height: 22px;
  }

  span {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  strong {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.message-composer {
  position: relative;
  padding: 12px 16px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  border-top: 1px solid var(--fade-grey-dark-3);
  background: var(--white);

  > input:not([type='file']) {
    min-width: 0;
    height: 44px;
    flex: 1;
    padding: 0 15px;
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: 14px;
    background: var(--fade-grey-light-5);
  }
}

.composer-previews {
  width: 100%;
  display: flex;
  align-items: stretch;
  flex-direction: column;
  gap: 7px;
}

.composer-action,
.send-button,
.mobile-close,
.mobile-menu,
.reply-preview button,
.attachment-chip button {
  border: 0;
  background: transparent;
  cursor: pointer;
}

.composer-action,
.send-button {
  width: 42px;
  height: 42px;
  display: grid;
  place-content: center;
  border-radius: 50%;

  svg {
    width: 19px;
    height: 19px;
  }
}

.composer-action {
  color: var(--light-text);
}

.send-button {
  background: var(--primary);
  color: #fff;

  &:disabled {
    opacity: 0.45;
    cursor: default;
  }
}

.attachment-chip,
.reply-preview {
  max-width: 100%;
  padding: 8px 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
  background: var(--white);
  box-shadow: var(--light-box-shadow);

  > span {
    min-width: 0;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.reply-preview {
  border-left: 3px solid var(--primary);

  small {
    overflow: hidden;
    color: var(--light-text);
    text-overflow: ellipsis;
    white-space: nowrap;
  }
}

.profile-panel {
  padding: 35px 22px;
  overflow-y: auto;
  border-left: 1px solid var(--fade-grey-dark-3);
  text-align: center;

  h2 {
    margin: 12px 0 2px;
    color: var(--dark-text);
    font-size: 1.05rem;
  }

  > span {
    color: var(--light-text);
    font-size: 0.78rem;
  }
}

.profile-divider {
  margin: 26px 0;
  border-top: 1px solid var(--fade-grey-dark-3);
}

.shared-title {
  margin-bottom: 12px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: var(--dark-text);
  text-align: left;
}

.shared-images {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 6px;

  img {
    width: 100%;
    aspect-ratio: 1;
    display: block;
    border-radius: 8px;
    object-fit: cover;
  }
}

.no-shared,
.empty-state {
  color: var(--light-text);
  font-size: 0.82rem;
}

.empty-state {
  padding: 24px;
  text-align: center;

  &.centered {
    height: 100%;
    display: grid;
    place-content: center;
    gap: 8px;
  }
}

.is-dark {
  .contacts-panel,
  .profile-panel,
  .conversation-header,
  .message-composer,
  .message-bubble,
  .message-actions button,
  .reply-preview,
  .attachment-chip {
    background: var(--dark-sidebar-light-4);
    border-color: var(--dark-sidebar-light-12);
  }

  .chat-shell,
  .conversation-panel,
  .employee-chat {
    background: var(--dark-sidebar-light-7);
  }

  .contact-row:hover,
  .contact-row.active,
  .contact-search input,
  .message-composer > input:not([type='file']) {
    background: var(--dark-sidebar-light-7);
  }

  h1,
  h2,
  strong,
  .contact-name,
  .message-bubble {
    color: var(--smoke-white);
  }
}

@media (max-width: 1024px) {
  .chat-shell {
    grid-template-columns: 300px minmax(0, 1fr);
  }

  .profile-panel {
    display: none;
  }
}

@media (max-width: 767px) {
  .employee-only {
    min-height: calc(100vh - 60px);
    min-height: calc(100dvh - 60px);
    margin-top: 60px;
  }

  .employee-chat {
    top: 60px;
  }

  .chat-shell {
    height: 100%;
    display: block;
  }

  .contacts-panel {
    position: fixed;
    top: 60px;
    bottom: 0;
    left: 0;
    width: min(88vw, 340px);
    z-index: 20;
    transform: translateX(-105%);
    transition: transform 0.25s ease;

    &.is-mobile-open {
      transform: translateX(0);
    }
  }

  .contacts-list {
    height: calc(100vh - 60px - 124px);
    height: calc(100dvh - 60px - 124px);
  }

  .mobile-close,
  .mobile-menu {
    display: grid;
    place-content: center;
  }

  .conversation-panel {
    height: 100%;
  }

  .message-list {
    padding: 14px 10px;
  }

  .message-content {
    max-width: 82%;
  }

  .image-attachment img {
    max-width: 230px;
  }

  .message-actions {
    opacity: 1;
  }

  .selection-toolbar .delete-selection span {
    display: none;
  }
}
</style>
