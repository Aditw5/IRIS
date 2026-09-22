/* Disable old PWA service worker for U-LAB.
   Cache reset version: 2026-07-25-chatbot-v4.
   File ini sengaja dibuat agar browser yang sudah pernah register /sw.js
   mengganti service worker lama, membersihkan cache, lalu unregister. */

self.addEventListener('install', (event) => {
  self.skipWaiting()
})

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((cacheNames) => Promise.all(cacheNames.map((cacheName) => caches.delete(cacheName))))
      .then(() => self.clients.claim())
      .then(() => self.registration.unregister())
      .then(() => self.clients.matchAll({ type: 'window' }))
      .then((clients) => {
        clients.forEach((client) => {
          if (client.url && 'navigate' in client) {
            client.navigate(client.url)
          }
        })
      })
  )
})
