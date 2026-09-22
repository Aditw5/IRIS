import { reactive } from 'vue'
import { io } from 'socket.io-client'

export const state = reactive({
  connected: false,
})

const env = import.meta.env.VITE_SOCKET
const enabled = import.meta.env.VITE_SOCKET_ON

const PRODUCTION_URL = 'https://socket.ulabumro.id'
const isLocalSocketUrl = !env || /localhost|127\.0\.0\.1/i.test(env)
const URL = import.meta.env.PROD && isLocalSocketUrl
  ? PRODUCTION_URL
  : env || 'http://127.0.0.1:3001'

let mySocket = null

if (enabled === 'true') {
  mySocket = io(URL, {
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
