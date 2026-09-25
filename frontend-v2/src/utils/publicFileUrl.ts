const publicFileRoots = new Set([
  'berkas-customer',
  'berkas-instruksi-kerja',
  'berkas-laporan-repair',
  'berkas-mitra',
  'berkas-mitra-excel',
  'berkas-mutu',
  'berkas-pbj',
  'berkas-user',
  'berkas-vendor',
  'berkas-verifikasi',
  'chat-attachments',
  'gambar-suhu',
  'laporan-repair',
  'mapping-layanan',
  'produk',
  'sertifikat',
  'sertifikat-standar',
  'storage',
  'surat-jalan',
])

function cleanFolder(folder: string) {
  const segments = String(folder || '')
    .replace(/\\/g, '/')
    .split('/')
    .filter(Boolean)

  if (!segments.length || segments.some((segment) => !/^[A-Za-z0-9_-]+$/.test(segment))) {
    return ''
  }

  return segments.map(encodeURIComponent).join('/')
}

function basename(file: unknown) {
  const normalized = String(file ?? '').trim().replace(/\\/g, '/').split(/[?#]/, 1)[0]
  return normalized.split('/').filter(Boolean).pop() || ''
}

export function publicFileUrl(folder: string, file: unknown) {
  const safeFolder = cleanFolder(folder)
  const filename = basename(file)

  if (!safeFolder || !filename || filename === '.' || filename === '..') return ''

  return `/${safeFolder}/${encodeURIComponent(filename)}`
}

export function resolvePublicFileUrl(file: unknown, fallbackFolder: string) {
  const original = String(file ?? '').trim()
  if (!original) return ''
  if (/^(blob:|data:)/i.test(original)) return original

  let path = original
  if (/^https?:\/\//i.test(original)) {
    try {
      const parsed = new URL(original)
      const root = parsed.pathname.replace(/^\/+/, '').split('/')[0]
      if (!publicFileRoots.has(root)) return original
      path = parsed.pathname
    } catch {
      return original
    }
  }

  const segments = path.replace(/\\/g, '/').replace(/^\/+/, '').split('/').filter(Boolean)
  const filename = segments.pop()
  if (!filename) return ''

  const root = segments[0]
  const folder = root && publicFileRoots.has(root) ? segments.join('/') : fallbackFolder
  return publicFileUrl(folder, filename)
}
