<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import { joinNotifPegawai, socket } from '/@src/socket.js'

const router = useRouter()
const api = useApi()
const unread = ref(0)

const sessionUser = computed(() => {
  try {
    return useUserSession().getUser()
  } catch {
    return null
  }
})

const isEmployee = computed(() => {
  const user = sessionUser.value
  return Boolean(
    user?.pegawai?.id &&
    String(user?.kelompokUser?.kelompokUser || '').toLowerCase() !== 'customer'
  )
})

async function loadUnread() {
  if (!isEmployee.value) return
  try {
    const response: any = await api.get('chat/rooms')
    unread.value = Number(response?.unread || 0)
  } catch {
    unread.value = 0
  }
}

function handleChatMessage() {
  loadUnread()
}

function openChat() {
  router.push({ name: 'messaging-v2' })
}

onMounted(() => {
  if (!isEmployee.value) return
  joinNotifPegawai(sessionUser.value.pegawai.id)
  loadUnread()
  socket.on('chat-message', handleChatMessage)
})

onBeforeUnmount(() => {
  socket.off('chat-message', handleChatMessage)
})
</script>

<template>
  <a
    v-if="isEmployee"
    class="toolbar-link chat-toolbar-link"
    aria-label="Buka chat pegawai"
    tabindex="0"
    @keydown.space.prevent="openChat"
    @click="openChat"
  >
    <i aria-hidden="true" class="iconify" data-icon="feather:message-circle"></i>
    <span v-if="unread" class="chat-unread">{{ unread > 99 ? '99+' : unread }}</span>
  </a>
</template>

<style lang="scss" scoped>
.chat-toolbar-link {
  position: relative;
  cursor: pointer;
}

.chat-unread {
  position: absolute;
  top: -7px;
  right: -9px;
  display: flex;
  min-width: 19px;
  height: 19px;
  padding: 0 5px;
  align-items: center;
  justify-content: center;
  border: 2px solid var(--white);
  border-radius: 999px;
  background: #dc3545;
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  line-height: 1;
}
</style>
