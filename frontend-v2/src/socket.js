import { reactive } from 'vue'
import { io } from 'socket.io-client'

export const state = reactive({
  connected: false,
})

const socketUrl = String(import.meta.env.VITE_SOCKET_URL || '').trim()
const enabled = String(import.meta.env.VITE_SOCKET_ON || 'false').toLowerCase() === 'true'

let mySocket = null

if (enabled) {
  // Empty URL intentionally uses the current origin. In production Nginx
  // proxies the default Socket.IO path (/socket.io/) to iris-socket.
  mySocket = io(socketUrl || undefined, {
    transports: ['websocket', 'polling'],
    withCredentials: true,
    autoConnect: true,
  })
} else {
  mySocket = {
    connected: false,
    on: function () {},
    off: function () {},
    emit: function (event, payload, callback) {
      if (event === 'get-online-pegawai' && typeof payload === 'function') {
        payload([])
      } else if (typeof callback === 'function') {
        callback([])
      }
    },
  }
}

export const socket = mySocket

export const joinNotifPegawai = (pegawaiId) => {
  if (!pegawaiId) return
  socket.emit('join-pegawai', pegawaiId)
}

socket.on('connect', () => {
  state.connected = true
  console.log('Socket connected')
})

socket.on('disconnect', () => {
  state.connected = false
  console.log('Socket disconnected')
})
