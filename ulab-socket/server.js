require('dotenv').config()

const express = require('express')
const http = require('http')
const cors = require('cors')
const { Server } = require('socket.io')

const app = express()
const server = http.createServer(app)
const SOCKET_SERVER_VERSION = '1.4.0-employee-chat-reply-delete'

const allowedOrigins = (process.env.CORS_ORIGIN || '')
  .split(',')
  .map(v => v.trim())
  .filter(Boolean)

app.use(cors({
  origin: function (origin, callback) {
    if (!origin) return callback(null, true)
    if (allowedOrigins.includes(origin)) return callback(null, true)
    return callback(new Error('Origin tidak diizinkan oleh CORS'))
  },
  credentials: true
}))

app.use(express.json())

const io = new Server(server, {
  cors: {
    origin: allowedOrigins,
    methods: ['GET', 'POST'],
    credentials: true
  }
})

const cameraRoomName = roomId => `camera:${roomId}`
const cameraRoles = new Set(['asci', 'std', 'uut'])
const pegawaiConnections = new Map()

const normalizePegawaiId = value => {
  const id = Number(value)
  return Number.isInteger(id) && id > 0 ? id : null
}

const emitPegawaiPresence = pegawaiId => {
  const online = (pegawaiConnections.get(pegawaiId) || 0) > 0
  io.emit('pegawai-presence', { pegawaiId, online })
}

const leavePegawaiRoom = socket => {
  const pegawaiId = socket.data.pegawaiId
  if (!pegawaiId) return

  socket.leave(`pegawai:${pegawaiId}`)
  const nextCount = Math.max(0, (pegawaiConnections.get(pegawaiId) || 1) - 1)
  if (nextCount === 0) {
    pegawaiConnections.delete(pegawaiId)
  } else {
    pegawaiConnections.set(pegawaiId, nextCount)
  }
  delete socket.data.pegawaiId
  emitPegawaiPresence(pegawaiId)
}

const emitCameraParticipants = roomId => {
  const roomName = cameraRoomName(roomId)
  const socketIds = io.sockets.adapter.rooms.get(roomName) || new Set()
  const participants = Array.from(socketIds)
    .map(socketId => io.sockets.sockets.get(socketId))
    .filter(Boolean)
    .map(client => ({
      id: client.id,
      role: client.data.cameraRole
    }))

  io.to(roomName).emit('camera-participants', participants)
  return participants
}

const leaveCameraRoom = socket => {
  const roomId = socket.data.cameraRoomId
  if (!roomId) return

  socket.leave(cameraRoomName(roomId))
  delete socket.data.cameraRoomId
  delete socket.data.cameraRole
  emitCameraParticipants(roomId)
}

app.get('/', (req, res) => {
  res.json({
    status: 'ok',
    message: 'U-LAB Socket Server running',
    version: SOCKET_SERVER_VERSION,
    features: {
      notifications: true,
      employeeChat: true,
      employeePresence: true,
      cameraRooms: true,
      cameraSignaling: true
    }
  })
})

io.on('connection', (socket) => {
  console.log('Client connected:', socket.id)

  socket.on('join-pegawai', (pegawaiId) => {
    const normalizedId = normalizePegawaiId(pegawaiId)
    if (!normalizedId) return
    if (socket.data.pegawaiId === normalizedId) return

    leavePegawaiRoom(socket)
    const roomName = `pegawai:${normalizedId}`
    socket.data.pegawaiId = normalizedId
    socket.join(roomName)
    pegawaiConnections.set(normalizedId, (pegawaiConnections.get(normalizedId) || 0) + 1)
    emitPegawaiPresence(normalizedId)
    console.log(`Socket ${socket.id} joined room ${roomName}`)
  })

  socket.on('get-online-pegawai', callback => {
    if (typeof callback !== 'function') return
    callback(Array.from(pegawaiConnections.keys()))
  })

  socket.on('join-camera-room', (payload, callback) => {
    const roomId = String(payload?.roomId || '').trim()
    const role = String(payload?.role || '').trim().toLowerCase()
    if (!roomId || roomId.length > 120 || !cameraRoles.has(role)) {
      if (typeof callback === 'function') {
        callback({
          ok: false,
          message: 'Room atau role kamera tidak valid'
        })
      }
      return
    }

    if (socket.data.cameraRoomId === roomId && socket.data.cameraRole === role) {
      if (typeof callback === 'function') {
        callback({
          ok: true,
          roomId,
          role,
          participants: emitCameraParticipants(roomId),
          serverVersion: SOCKET_SERVER_VERSION
        })
      }
      return
    }

    leaveCameraRoom(socket)
    const roomName = cameraRoomName(roomId)

    if (role === 'std' || role === 'uut') {
      const socketIds = io.sockets.adapter.rooms.get(roomName) || new Set()
      Array.from(socketIds)
        .map(socketId => io.sockets.sockets.get(socketId))
        .filter(client => client && client.id !== socket.id && client.data.cameraRole === role)
        .forEach(client => {
          client.emit('camera-role-replaced', { roomId, role })
          client.leave(roomName)
          delete client.data.cameraRoomId
          delete client.data.cameraRole
        })
    }

    socket.data.cameraRoomId = roomId
    socket.data.cameraRole = role
    socket.join(roomName)
    const participants = emitCameraParticipants(roomId)
    if (typeof callback === 'function') {
      callback({
        ok: true,
        roomId,
        role,
        participants,
        serverVersion: SOCKET_SERVER_VERSION
      })
    }
    console.log(`Socket ${socket.id} joined camera room ${roomId} as ${role}`)
  })

  socket.on('camera-signal', (payload, callback) => {
    const roomId = socket.data.cameraRoomId
    const targetId = String(payload?.targetId || '')
    const target = io.sockets.sockets.get(targetId)

    if (!roomId || payload?.roomId !== roomId || !target || target.data.cameraRoomId !== roomId) {
      if (typeof callback === 'function') {
        callback({
          ok: false,
          message: 'Target signaling tidak ditemukan pada room yang sama'
        })
      }
      return
    }

    target.emit('camera-signal', {
      fromId: socket.id,
      role: socket.data.cameraRole,
      data: payload.data
    })
    if (typeof callback === 'function') {
      callback({ ok: true })
    }
  })

  socket.on('leave-camera-room', () => {
    leaveCameraRoom(socket)
  })

  socket.on('disconnect', () => {
    leavePegawaiRoom(socket)
    const cameraRoomId = socket.data.cameraRoomId
    if (cameraRoomId) {
      delete socket.data.cameraRoomId
      delete socket.data.cameraRole
      emitCameraParticipants(cameraRoomId)
    }
    console.log('Client disconnected:', socket.id)
  })
})

app.post('/emit-notification', (req, res) => {
  const secret = req.headers['x-socket-secret']
  if (!secret || secret !== process.env.SOCKET_SERVER_SECRET) {
    return res.status(401).json({
      status: false,
      message: 'Unauthorized'
    })
  }

  const payload = req.body
  const pegawaiId = payload.idPegawai

  if (!pegawaiId) {
    return res.status(422).json({
      status: false,
      message: 'idPegawai wajib diisi'
    })
  }

  io.to(`pegawai:${pegawaiId}`).emit('notification', payload)

  return res.json({
    status: true,
    message: 'Notification emitted',
    room: `pegawai:${pegawaiId}`,
    payload
  })
})

app.post('/emit-chat', (req, res) => {
  const secret = req.headers['x-socket-secret']
  if (!secret || secret !== process.env.SOCKET_SERVER_SECRET) {
    return res.status(401).json({
      status: false,
      message: 'Unauthorized'
    })
  }

  const recipientIds = Array.isArray(req.body?.recipientIds)
    ? req.body.recipientIds.map(normalizePegawaiId).filter(Boolean)
    : []
  const message = req.body?.message

  if (!recipientIds.length || !message) {
    return res.status(422).json({
      status: false,
      message: 'recipientIds dan message wajib diisi'
    })
  }

  recipientIds.forEach(pegawaiId => {
    io.to(`pegawai:${pegawaiId}`).emit('chat-message', message)
  })

  return res.json({
    status: true,
    message: 'Chat emitted',
    recipients: recipientIds
  })
})

app.post('/emit-chat-delete', (req, res) => {
  const secret = req.headers['x-socket-secret']
  if (!secret || secret !== process.env.SOCKET_SERVER_SECRET) {
    return res.status(401).json({
      status: false,
      message: 'Unauthorized'
    })
  }

  const recipientIds = Array.isArray(req.body?.recipientIds)
    ? req.body.recipientIds.map(normalizePegawaiId).filter(Boolean)
    : []
  const payload = req.body?.payload

  if (!recipientIds.length || !payload?.roomfk || !Array.isArray(payload?.message_ids)) {
    return res.status(422).json({
      status: false,
      message: 'recipientIds dan payload penghapusan wajib diisi'
    })
  }

  recipientIds.forEach(pegawaiId => {
    io.to(`pegawai:${pegawaiId}`).emit('chat-message-deleted', payload)
  })

  return res.json({
    status: true,
    message: 'Chat deletion emitted',
    recipients: recipientIds
  })
})

server.listen(process.env.PORT || 3001, () => {
  console.log(`Socket server running on port ${process.env.PORT || 3001}`)
})
