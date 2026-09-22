<script setup lang="ts">
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useHead } from '@vueuse/head'
import { useRoute, RouterLink } from 'vue-router'
import { useDarkmode } from '/@src/stores/darkmode'
import { useApi } from '/@src/composable/useApi'

const darkmode = useDarkmode()
const route = useRoute()

type SignerFocus = {
  key: string
  label: string
  nama: string | null
  nid: string | null
  jabatan: string | null
  status: string | null
  tanggal: string | null
  is_valid: boolean
  keterangan: string | null
}

type PbjDetailItem = {
  norec: string
  namaitem: string | null
  uraianitem: string | null
  stockcode: string | number | null
  banyak: string | number | null
  satuan: string | null
  hargasatuan: string | number | null
  keterangan: string | null
}

type PbjValidationData = {
  jenis_dokumen: 'pbj'
  ttd_key: string
  signer_focus: SignerFocus
  norec: string
  nomor_dokumen: string | null
  nosuratpbj: string | null
  tglpengajuan: string | null
  judulpermintaan: string | null
  jenispbj: string | null
  kebutuhan: string | null
  bidang: string | null
  kepada: string | null
  user_unit: string | null
  pemohonpbj: string | null
  pengadaanpbj: string | null
  lokasipbj: string | null
  prioritaspbj: string | null
  prk: string | null
  wo: string | null
  project: string | null
  noproyek: string | null
  costcode: string | null
  ppn: string | number | null
  jumlah_item: number
  detail: PbjDetailItem[]
}

type SertifikatValidationData = {
  jenis_dokumen: 'sertifikat'
  ttd_key: string
  signer_focus: SignerFocus
  norec_registrasi: string
  norec_detail: string
  nomor_dokumen: string | null
  nopendaftaran: string | null
  noorderalat: string | null
  nosertifikat: string | null
  tglregistrasi: string | null
  tglkalibrasi: string | null
  tglsetujumanager: string | null
  customer: string | null
  alamat: string | null
  lokasi: string | null
  nama_alat: string | null
  merk: string | null
  tipe: string | null
  serial_number: string | null
  lingkupkalibrasi: string | null
  sublingkup: string | null
  petugaskaji: string | null
}

type SuratJalanDetailItem = {
  norec: string
  nourut: number | string | null
  namabarang: string | null
  namamerk: string | null
  namatipe: string | null
  namaserialnumber: string | null
  jumlah: string | number | null
  satuan: string | null
  ket: string | null
}

type SuratJalanValidationData = {
  jenis_dokumen: 'surat_jalan'
  ttd_key: string
  signer_focus: SignerFocus
  norec: string
  nosuratjalan: string | null
  sumber: string | null
  noregistrasifk: string | null
  nopendaftaran: string | null
  diberikankepada: string | null
  berdasarkan: string | null
  tanggalsurat: string | null
  tujuan: string | null
  barangbarangdari: string | null
  kendaraan: string | null
  nomorpolisi: string | null
  pengemudi: string | null
  statussuratjalan: number | string | null
  status_surat_jalan: string | null
  keteranganstatus: string | null
  tglajukan: string | null
  tglasman: string | null
  namapetugas: string | null
  namapegawaibawa: string | null
  namaasman: string | null
  jumlah_item: number
  detail: SuratJalanDetailItem[]
}

type ValidationData = PbjValidationData | SertifikatValidationData | SuratJalanValidationData

const loading = ref(true)
const valid = ref(false)
const message = ref('')
const data = ref<ValidationData | null>(null)
const errorText = ref('')
const isMobile = ref(false)
const jenisResponse = ref<'pbj' | 'sertifikat' | 'surat_jalan'>('pbj')

const jenis = computed(() => {
  const value = route.query.jenis
  const result = Array.isArray(value) ? value[0] : value

  if (result === 'sertifikat') return 'sertifikat'
  if (result === 'surat_jalan') return 'surat_jalan'

  return 'pbj'
})

const ttdKey = computed(() => {
  const value = route.query.ttd
  const result = Array.isArray(value) ? value[0] : value
  return result ? String(result) : ''
})

const norec = computed(() => {
  const value = route.query.norec
  if (Array.isArray(value)) return value[0] || ''
  return value ? String(value) : ''
})

const norecDetail = computed(() => {
  const value = route.query.norec_detail
  if (Array.isArray(value)) return value[0] || ''
  return value ? String(value) : ''
})

const pbjData = computed(() => {
  if (jenisResponse.value !== 'pbj') return null
  return data.value as PbjValidationData | null
})

const sertifikatData = computed(() => {
  if (jenisResponse.value !== 'sertifikat') return null
  return data.value as SertifikatValidationData | null
})

const suratJalanData = computed(() => {
  if (jenisResponse.value !== 'surat_jalan') return null
  return data.value as SuratJalanValidationData | null
})

const signerFocus = computed(() => {
  return data.value?.signer_focus || null
})

const loginUrl = computed(() => '/auth/login')

const validationTitle = computed(() => {
  if (loading.value) return 'Memvalidasi Tanda Tangan'
  if (!valid.value) return 'Tanda Tangan Tidak Valid'

  if (jenisResponse.value === 'sertifikat') return 'Tanda Tangan Sertifikat Tervalidasi'
  if (jenisResponse.value === 'surat_jalan') return 'Tanda Tangan Surat Jalan Tervalidasi'

  return 'Tanda Tangan PBJ Tervalidasi'
})

const validationSubtitle = computed(() => {
  if (loading.value) return 'Mohon tunggu, sistem sedang memeriksa tanda tangan yang discan.'

  return message.value || (valid.value
    ? 'Tanda tangan ini tercatat pada sistem resmi U-LAB.'
    : 'Tanda tangan tidak ditemukan pada sistem resmi U-LAB.')
})

const heroStatusClass = computed(() => {
  if (loading.value) return 'is-loading'
  return valid.value ? 'is-valid' : 'is-invalid'
})

const nomorDokumen = computed(() => {
  if (!data.value) return '-'

  if (jenisResponse.value === 'sertifikat') {
    return safeValue(sertifikatData.value?.nosertifikat)
  }

  if (jenisResponse.value === 'surat_jalan') {
    return safeValue(suratJalanData.value?.nosuratjalan)
  }

  return safeValue(pbjData.value?.nosuratpbj)
})

const dokumenLabel = computed(() => {
  if (jenisResponse.value === 'sertifikat') return 'Nomor Sertifikat'
  if (jenisResponse.value === 'surat_jalan') return 'Nomor Surat Jalan'
  return 'Nomor Surat'
})

const validationCardTitle = computed(() => {
  if (jenisResponse.value === 'sertifikat') return 'VALIDASI TTD SERTIFIKAT'
  if (jenisResponse.value === 'surat_jalan') return 'VALIDASI TTD SURAT JALAN'
  return 'VALIDASI TTD PBJ'
})

const validationSignerTitle = computed(() => {
  if (jenisResponse.value === 'sertifikat') return 'Tanda Tangan Sertifikat'
  if (jenisResponse.value === 'surat_jalan') return 'Tanda Tangan Surat Jalan'
  return 'Tanda Tangan PBJ'
})

const statusDokumenText = computed(() => {
  if (!signerFocus.value) return '-'
  return signerFocus.value.status || '-'
})

const statusDokumenType = computed(() => {
  if (!signerFocus.value) return 'warning'

  if (signerFocus.value.is_valid) return 'success'

  const text = String(signerFocus.value.status || '').toLowerCase()
  if (text.includes('ditolak')) return 'danger'

  return 'warning'
})

const formatDate = (value?: string | null) => {
  if (!value) return '-'

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '-'

  return new Intl.DateTimeFormat('id-ID', {
    day: '2-digit',
    month: 'long',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(date).replace('.', ':') + ' WIB'
}

const safeValue = (value?: string | number | null) => {
  if (value === null || value === undefined || value === '') return '-'
  return String(value)
}

const updateDevice = () => {
  isMobile.value = window.innerWidth <= 768
}

const fetchValidation = async () => {
  loading.value = true
  valid.value = false
  message.value = ''
  data.value = null
  errorText.value = ''
  jenisResponse.value = jenis.value

  try {
    if (jenis.value === 'sertifikat') {
      if (!norecDetail.value) {
        valid.value = false
        message.value = 'Kode sertifikat tidak ditemukan pada URL validasi.'
        errorText.value = 'Parameter norec_detail tidak ditemukan pada URL.'
        return
      }

      const queryTtd = ttdKey.value ? `&ttd=${encodeURIComponent(ttdKey.value)}` : ''
      const res = await useApi().get(`validasi-sertifikat?norec_detail=${encodeURIComponent(norecDetail.value)}${queryTtd}`)

      valid.value = !!res?.success
      message.value = res?.message || ''
      data.value = res?.data || null
      jenisResponse.value = 'sertifikat'

      if (!res?.success) {
        errorText.value = res?.message || 'Tanda tangan sertifikat tidak ditemukan.'
      }

      return
    }

    if (jenis.value === 'surat_jalan') {
      if (!norec.value) {
        valid.value = false
        message.value = 'Kode surat jalan tidak ditemukan pada URL validasi.'
        errorText.value = 'Parameter norec tidak ditemukan pada URL.'
        return
      }

      const queryTtd = ttdKey.value ? `&ttd=${encodeURIComponent(ttdKey.value)}` : ''
      const res = await useApi().get(`validasi-surat-jalan?norec=${encodeURIComponent(norec.value)}${queryTtd}`)

      valid.value = !!res?.success
      message.value = res?.message || ''
      data.value = res?.data || null
      jenisResponse.value = 'surat_jalan'

      if (!res?.success) {
        errorText.value = res?.message || 'Tanda tangan surat jalan tidak ditemukan.'
      }

      return
    }

    if (!norec.value) {
      valid.value = false
      message.value = 'Kode dokumen tidak ditemukan pada URL validasi.'
      errorText.value = 'Parameter norec tidak ditemukan pada URL.'
      return
    }

    const queryTtd = ttdKey.value ? `&ttd=${encodeURIComponent(ttdKey.value)}` : ''
    const res = await useApi().get(`validasi?norec=${encodeURIComponent(norec.value)}${queryTtd}`)

    valid.value = !!res?.success
    message.value = res?.message || ''
    data.value = res?.data || null
    jenisResponse.value = 'pbj'

    if (!res?.success) {
      errorText.value = res?.message || 'Tanda tangan PBJ tidak ditemukan.'
    }
  } catch (error: any) {
    valid.value = false
    message.value = 'Gagal menghubungi server validasi U-LAB.'
    errorText.value =
      error?.response?.data?.message ||
      error?.message ||
      'Terjadi kesalahan koneksi.'
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  updateDevice()
  window.addEventListener('resize', updateDevice, { passive: true })
  fetchValidation()
})

onBeforeUnmount(() => {
  window.removeEventListener('resize', updateDevice)
})

useHead({
  title: 'Validasi Tanda Tangan | U-LAB',
  meta: [
    {
      name: 'description',
      content: 'Halaman validasi tanda tangan dokumen U-LAB.',
    },
  ],
})
</script>

<template>
  <MinimalLayout :theme="darkmode.isDark ? 'dark' : 'light'">
    <div class="validate-page">
      <div class="validate-bg">
        <div class="orb orb-one"></div>
        <div class="orb orb-two"></div>
        <div class="orb orb-three"></div>
      </div>

      <main class="validate-shell" :class="{ 'is-mobile': isMobile }">
        <section class="validate-hero" :class="heroStatusClass">
          <div class="brand-row">
            <div class="brand-mark">
              <img src="/UMRO.png" alt="U-LAB" />
            </div>
            <div>
              <div class="brand-title">U-LAB</div>
              <div class="brand-subtitle">UMRO Calibration Laboratory</div>
            </div>
          </div>

          <div class="status-icon">
            <i v-if="loading" class="fas fa-spinner fa-spin"></i>
            <i v-else-if="valid" class="fas fa-badge-check"></i>
            <i v-else class="fas fa-triangle-exclamation"></i>
          </div>

          <h1>{{ validationTitle }}</h1>
          <p>{{ validationSubtitle }}</p>

          <div v-if="data" class="hero-summary">
            <div class="summary-item">
              <span>{{ dokumenLabel }}</span>
              <strong>{{ nomorDokumen }}</strong>
            </div>
            <div class="summary-item">
              <span>Bagian yang Discan</span>
              <strong>{{ safeValue(signerFocus?.label) }}</strong>
            </div>
          </div>
        </section>

        <section class="validate-content">
          <div v-if="loading" class="content-card loading-card">
            <div class="skeleton skeleton-title"></div>
            <div class="skeleton skeleton-line"></div>
            <div class="skeleton skeleton-line short"></div>
            <div class="skeleton-grid">
              <div class="skeleton skeleton-box"></div>
              <div class="skeleton skeleton-box"></div>
              <div class="skeleton skeleton-box"></div>
              <div class="skeleton skeleton-box"></div>
            </div>
          </div>

          <div v-else-if="valid && data" class="content-card">
            <div class="card-header">
              <div>
                <span class="eyebrow">{{ validationCardTitle }}</span>
                <h2>{{ validationSignerTitle }}</h2>
              </div>
              <div class="status-pill" :class="statusDokumenType">
                {{ statusDokumenText }}
              </div>
            </div>

            <div v-if="signerFocus" class="signer-focus-card" :class="statusDokumenType">
              <div class="signer-seal">
                <i v-if="signerFocus.is_valid" class="fas fa-check"></i>
                <i v-else class="fas fa-clock"></i>
              </div>

              <div class="signer-main">
                <span class="eyebrow">TANDA TANGAN YANG DIPINDAI</span>
                <h3>{{ safeValue(signerFocus.label) }}</h3>
                <p>{{ safeValue(signerFocus.keterangan) }}</p>
              </div>
            </div>

            <div class="main-info-grid signer-grid">
              <div class="info-item wide">
                <div class="info-icon">
                  <i class="fas fa-user-pen"></i>
                </div>
                <div>
                  <span>Nama Penandatangan</span>
                  <strong>{{ safeValue(signerFocus?.nama) }}</strong>
                </div>
              </div>

              <div class="info-item">
                <div class="info-icon">
                  <i class="fas fa-id-card"></i>
                </div>
                <div>
                  <span>NID</span>
                  <strong>{{ safeValue(signerFocus?.nid) }}</strong>
                </div>
              </div>

              <div class="info-item">
                <div class="info-icon">
                  <i class="fas fa-briefcase"></i>
                </div>
                <div>
                  <span>Jabatan</span>
                  <strong>{{ safeValue(signerFocus?.jabatan) }}</strong>
                </div>
              </div>

              <div class="info-item">
                <div class="info-icon">
                  <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                  <span>Tanggal TTD / Validasi</span>
                  <strong>{{ formatDate(signerFocus?.tanggal) }}</strong>
                </div>
              </div>

              <div class="info-item">
                <div class="info-icon">
                  <i class="fas fa-shield-check"></i>
                </div>
                <div>
                  <span>Status Tanda Tangan</span>
                  <strong>{{ safeValue(signerFocus?.status) }}</strong>
                </div>
              </div>
            </div>

            <div class="divider"></div>

            <template v-if="jenisResponse === 'pbj' && pbjData">
              <div class="document-summary">
                <div>
                  <span class="eyebrow">RINGKASAN DOKUMEN</span>
                  <h2>Informasi Dasar PBJ</h2>
                </div>
              </div>

              <div class="main-info-grid compact-grid">
                <div class="info-item wide">
                  <div class="info-icon">
                    <i class="fas fa-file-signature"></i>
                  </div>
                  <div>
                    <span>Judul Permintaan</span>
                    <strong>{{ safeValue(pbjData.judulpermintaan) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-hashtag"></i>
                  </div>
                  <div>
                    <span>Nomor Surat</span>
                    <strong>{{ safeValue(pbjData.nosuratpbj) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-building"></i>
                  </div>
                  <div>
                    <span>Bidang Pemohon</span>
                    <strong>{{ safeValue(pbjData.pemohonpbj) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-layer-group"></i>
                  </div>
                  <div>
                    <span>Material / Jasa</span>
                    <strong>{{ safeValue(pbjData.jenispbj) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-location-dot"></i>
                  </div>
                  <div>
                    <span>Lokasi</span>
                    <strong>{{ safeValue(pbjData.lokasipbj || 'Jakarta') }}</strong>
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="jenisResponse === 'sertifikat' && sertifikatData">
              <div class="document-summary">
                <div>
                  <span class="eyebrow">RINGKASAN SERTIFIKAT</span>
                  <h2>Informasi Dasar Sertifikat</h2>
                </div>
              </div>

              <div class="main-info-grid compact-grid">
                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-certificate"></i>
                  </div>
                  <div>
                    <span>Nomor Sertifikat</span>
                    <strong>{{ safeValue(sertifikatData.nosertifikat) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-hashtag"></i>
                  </div>
                  <div>
                    <span>No. Pendaftaran</span>
                    <strong>{{ safeValue(sertifikatData.nopendaftaran) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-gauge-high"></i>
                  </div>
                  <div>
                    <span>Nama Alat</span>
                    <strong>{{ safeValue(sertifikatData.nama_alat) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-tag"></i>
                  </div>
                  <div>
                    <span>Merk / Tipe</span>
                    <strong>{{ safeValue(sertifikatData.merk) }} / {{ safeValue(sertifikatData.tipe) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-fingerprint"></i>
                  </div>
                  <div>
                    <span>Serial Number</span>
                    <strong>{{ safeValue(sertifikatData.serial_number) }}</strong>
                  </div>
                </div>
              </div>
            </template>

            <template v-else-if="jenisResponse === 'surat_jalan' && suratJalanData">
              <div class="document-summary">
                <div>
                  <span class="eyebrow">RINGKASAN SURAT JALAN</span>
                  <h2>Informasi Dasar Surat Jalan</h2>
                </div>
              </div>

              <div class="main-info-grid compact-grid">
                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-file-signature"></i>
                  </div>
                  <div>
                    <span>No Surat Jalan</span>
                    <strong>{{ safeValue(suratJalanData.nosuratjalan) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-hashtag"></i>
                  </div>
                  <div>
                    <span>No Pendaftaran</span>
                    <strong>{{ safeValue(suratJalanData.nopendaftaran) }}</strong>
                  </div>
                </div>

                <div class="info-item wide">
                  <div class="info-icon">
                    <i class="fas fa-building"></i>
                  </div>
                  <div>
                    <span>Diberikan Kepada</span>
                    <strong>{{ safeValue(suratJalanData.diberikankepada) }}</strong>
                  </div>
                </div>

                <div class="info-item wide">
                  <div class="info-icon">
                    <i class="fas fa-location-dot"></i>
                  </div>
                  <div>
                    <span>Tujuan</span>
                    <strong>{{ safeValue(suratJalanData.tujuan) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-calendar"></i>
                  </div>
                  <div>
                    <span>Tanggal Surat</span>
                    <strong>{{ formatDate(suratJalanData.tanggalsurat) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-boxes-stacked"></i>
                  </div>
                  <div>
                    <span>Jumlah Barang</span>
                    <strong>{{ safeValue(suratJalanData.jumlah_item) }} barang</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-user"></i>
                  </div>
                  <div>
                    <span>Yang Membuat</span>
                    <strong>{{ safeValue(suratJalanData.namapetugas) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-truck"></i>
                  </div>
                  <div>
                    <span>Yang Membawa</span>
                    <strong>{{ safeValue(suratJalanData.namapegawaibawa) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-user-shield"></i>
                  </div>
                  <div>
                    <span>Asman</span>
                    <strong>{{ safeValue(suratJalanData.namaasman) }}</strong>
                  </div>
                </div>

                <div class="info-item">
                  <div class="info-icon">
                    <i class="fas fa-circle-check"></i>
                  </div>
                  <div>
                    <span>Status Surat Jalan</span>
                    <strong>{{ safeValue(suratJalanData.status_surat_jalan) }}</strong>
                  </div>
                </div>
              </div>
            </template>

            <div class="notice-box">
              <i class="fas fa-shield-check"></i>
              <div>
                <strong>
                  {{
                    jenisResponse === 'sertifikat'
                      ? 'Tanda tangan sertifikat ini tercatat pada sistem resmi U-LAB.'
                      : jenisResponse === 'surat_jalan'
                        ? 'Tanda tangan surat jalan ini tercatat pada sistem resmi U-LAB.'
                        : 'Tanda tangan dokumen PBJ ini tercatat pada sistem resmi U-LAB.'
                  }}
                </strong>
                <span>
                  Halaman ini hanya menampilkan validasi bagian tanda tangan yang discan.
                  Untuk melihat detail lengkap dokumen, silakan masuk ke dashboard.
                </span>
              </div>
            </div>
          </div>

          <div v-else class="content-card invalid-card">
            <div class="invalid-icon">
              <i class="fas fa-file-circle-xmark"></i>
            </div>
            <h2>Data Tanda Tangan Tidak Ditemukan</h2>
            <p>
              {{ errorText || 'Pastikan QR berasal dari dokumen resmi U-LAB dan URL tidak berubah.' }}
            </p>
            <div class="invalid-code">
              <span>KODE VALIDASI</span>
              <strong>
                {{
                  jenisResponse === 'sertifikat'
                    ? (norecDetail || '-')
                    : (norec || '-')
                }}
              </strong>
            </div>
          </div>

          <div class="login-area" :class="{ 'mobile-bottom': isMobile }">
            <RouterLink :to="loginUrl" class="login-btn">
              <span>Login ke Dashboard</span>
              <i class="fas fa-arrow-right"></i>
            </RouterLink>
          </div>
        </section>
      </main>
    </div>
  </MinimalLayout>
</template>

<style lang="scss">
.validate-page {
  position: relative;
  min-height: 100vh;
  overflow: hidden;
  background:
    radial-gradient(900px 420px at 12% -10%, rgba(14, 165, 233, .16), transparent 65%),
    radial-gradient(900px 420px at 92% 112%, rgba(34, 197, 94, .13), transparent 68%),
    #f7fbff;
  color: #0f172a;
}

html.is-dark .validate-page {
  background:
    radial-gradient(900px 420px at 12% -10%, rgba(14, 165, 233, .13), transparent 65%),
    radial-gradient(900px 420px at 92% 112%, rgba(34, 197, 94, .09), transparent 68%),
    #0b1220;
  color: #e5eefb;
}

.validate-bg {
  position: absolute;
  inset: 0;
  pointer-events: none;
  overflow: hidden;
}

.orb {
  position: absolute;
  border-radius: 999px;
  filter: blur(18px);
  opacity: .55;
}

.orb-one {
  width: 220px;
  height: 220px;
  left: -80px;
  top: 90px;
  background: rgba(59, 130, 246, .18);
}

.orb-two {
  width: 260px;
  height: 260px;
  right: -90px;
  bottom: 80px;
  background: rgba(34, 197, 94, .16);
}

.orb-three {
  width: 160px;
  height: 160px;
  right: 18%;
  top: 10%;
  background: rgba(14, 165, 233, .12);
}

.validate-shell {
  position: relative;
  z-index: 1;
  width: min(1180px, calc(100% - 32px));
  min-height: 100vh;
  margin: 0 auto;
  padding: 36px 0;
  display: grid;
  grid-template-columns: minmax(320px, .82fr) minmax(0, 1.18fr);
  gap: 24px;
  align-items: center;
}

.validate-hero {
  min-height: 620px;
  border-radius: 34px;
  padding: 34px;
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  background:
    linear-gradient(155deg, rgba(15, 23, 42, .92), rgba(30, 41, 59, .86)),
    url('/LANDING2.jpg?width=1400&height=1400&format=webp');
  background-size: cover;
  background-position: center;
  color: #fff;
  box-shadow: 0 28px 90px rgba(15, 23, 42, .22);
  overflow: hidden;
}

.validate-hero.is-valid {
  background:
    linear-gradient(155deg, rgba(15, 23, 42, .90), rgba(21, 128, 61, .72)),
    url('/LANDING2.jpg?width=1400&height=1400&format=webp');
  background-size: cover;
  background-position: center;
}

.validate-hero.is-invalid {
  background:
    linear-gradient(155deg, rgba(15, 23, 42, .90), rgba(185, 28, 28, .72)),
    url('/LANDING2.jpg?width=1400&height=1400&format=webp');
  background-size: cover;
  background-position: center;
}

.validate-hero.is-loading {
  background:
    linear-gradient(155deg, rgba(15, 23, 42, .92), rgba(37, 99, 235, .72)),
    url('/LANDING2.jpg?width=1400&height=1400&format=webp');
  background-size: cover;
  background-position: center;
}

.brand-row {
  display: flex;
  align-items: center;
  gap: 12px;
}

.brand-mark {
  width: 54px;
  height: 54px;
  border-radius: 18px;
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, .95);
  box-shadow: 0 14px 32px rgba(0, 0, 0, .16);
  overflow: hidden;
}

.brand-mark img {
  width: 42px;
  height: 42px;
  object-fit: contain;
}

.brand-title {
  font-size: 18px;
  font-weight: 900;
  letter-spacing: .04em;
}

.brand-subtitle {
  font-size: 12px;
  color: rgba(255, 255, 255, .75);
}

.status-icon {
  width: 108px;
  height: 108px;
  border-radius: 34px;
  display: grid;
  place-items: center;
  background: rgba(255, 255, 255, .14);
  border: 1px solid rgba(255, 255, 255, .22);
  backdrop-filter: blur(8px);
  font-size: 48px;
}

.validate-hero h1 {
  margin: 22px 0 10px;
  font-size: clamp(36px, 5vw, 64px);
  line-height: .98;
  letter-spacing: -.055em;
  font-weight: 950;
}

.validate-hero p {
  max-width: 430px;
  margin: 0;
  color: rgba(255, 255, 255, .84);
  font-size: 16px;
  line-height: 1.65;
}

.hero-summary {
  display: grid;
  gap: 12px;
  margin-top: 24px;
}

.summary-item {
  padding: 16px;
  border-radius: 18px;
  background: rgba(255, 255, 255, .13);
  border: 1px solid rgba(255, 255, 255, .16);
  backdrop-filter: blur(8px);
}

.summary-item span {
  display: block;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .12em;
  color: rgba(255, 255, 255, .68);
  margin-bottom: 6px;
}

.summary-item strong {
  font-size: 17px;
  line-height: 1.35;
}

.validate-content {
  min-width: 0;
}

.content-card {
  border-radius: 30px;
  padding: 28px;
  background: rgba(255, 255, 255, .88);
  border: 1px solid rgba(226, 232, 240, .9);
  box-shadow: 0 24px 80px rgba(15, 23, 42, .10);
  backdrop-filter: blur(14px);
}

html.is-dark .content-card {
  background: rgba(15, 23, 42, .86);
  border-color: rgba(51, 65, 85, .85);
  box-shadow: 0 24px 80px rgba(0, 0, 0, .34);
}

.card-header,
.document-summary {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 22px;
}

.eyebrow {
  display: inline-block;
  font-size: 11px;
  letter-spacing: .14em;
  text-transform: uppercase;
  color: #2563eb;
  font-weight: 900;
  margin-bottom: 6px;
}

html.is-dark .eyebrow {
  color: #60a5fa;
}

.card-header h2,
.document-summary h2 {
  margin: 0;
  font-size: 22px;
  line-height: 1.2;
  font-weight: 900;
  letter-spacing: -.025em;
}

.status-pill {
  flex: 0 0 auto;
  border-radius: 999px;
  padding: 9px 13px;
  font-size: 12px;
  font-weight: 900;
  white-space: nowrap;
}

.status-pill.success {
  color: #166534;
  background: #dcfce7;
  border: 1px solid #86efac;
}

.status-pill.warning {
  color: #92400e;
  background: #fef3c7;
  border: 1px solid #fcd34d;
}

.status-pill.danger {
  color: #991b1b;
  background: #fee2e2;
  border: 1px solid #fecaca;
}

.signer-focus-card {
  display: grid;
  grid-template-columns: 72px minmax(0, 1fr);
  gap: 16px;
  align-items: center;
  padding: 18px;
  border-radius: 24px;
  margin-bottom: 18px;
  border: 1px solid #e2e8f0;
  background: #f8fafc;
}

.signer-focus-card.success {
  border-color: #86efac;
  background: #f0fdf4;
}

.signer-focus-card.warning {
  border-color: #fcd34d;
  background: #fffbeb;
}

.signer-focus-card.danger {
  border-color: #fecaca;
  background: #fef2f2;
}

html.is-dark .signer-focus-card {
  background: rgba(30, 41, 59, .78);
  border-color: rgba(51, 65, 85, .88);
}

.signer-seal {
  width: 72px;
  height: 72px;
  border-radius: 24px;
  display: grid;
  place-items: center;
  color: #166534;
  background: #dcfce7;
  font-size: 30px;
}

.signer-focus-card.warning .signer-seal {
  color: #92400e;
  background: #fef3c7;
}

.signer-focus-card.danger .signer-seal {
  color: #991b1b;
  background: #fee2e2;
}

.signer-main h3 {
  margin: 0 0 6px;
  font-size: 24px;
  line-height: 1.1;
  font-weight: 950;
  letter-spacing: -.035em;
}

.signer-main p {
  margin: 0;
  color: #64748b;
  line-height: 1.55;
  font-size: 14px;
}

html.is-dark .signer-main p {
  color: #94a3b8;
}

.main-info-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
}

.signer-grid {
  margin-top: 12px;
}

.compact-grid {
  margin-top: 12px;
}

.info-item {
  min-width: 0;
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  gap: 12px;
  padding: 15px;
  border-radius: 18px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

html.is-dark .info-item {
  background: rgba(30, 41, 59, .78);
  border-color: rgba(51, 65, 85, .88);
}

.info-item.wide {
  grid-column: 1 / -1;
}

.info-icon {
  width: 42px;
  height: 42px;
  border-radius: 15px;
  display: grid;
  place-items: center;
  background: #eff6ff;
  color: #2563eb;
  font-size: 17px;
}

html.is-dark .info-icon {
  background: rgba(37, 99, 235, .16);
  color: #93c5fd;
}

.info-item span {
  display: block;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: .09em;
  color: #64748b;
  font-weight: 900;
  margin-bottom: 5px;
}

html.is-dark .info-item span {
  color: #94a3b8;
}

.info-item strong {
  display: block;
  font-size: 15px;
  line-height: 1.42;
  color: #0f172a;
  word-break: break-word;
}

html.is-dark .info-item strong {
  color: #e5eefb;
}

.divider {
  height: 1px;
  margin: 26px 0;
  background: #e2e8f0;
}

html.is-dark .divider {
  background: rgba(51, 65, 85, .9);
}

.notice-box {
  margin-top: 24px;
  display: grid;
  grid-template-columns: 42px minmax(0, 1fr);
  gap: 12px;
  padding: 16px;
  border-radius: 18px;
  background: #eff6ff;
  border: 1px solid #bfdbfe;
  color: #1e40af;
}

html.is-dark .notice-box {
  background: rgba(37, 99, 235, .13);
  border-color: rgba(59, 130, 246, .35);
  color: #bfdbfe;
}

.notice-box i {
  width: 42px;
  height: 42px;
  display: grid;
  place-items: center;
  font-size: 20px;
}

.notice-box strong {
  display: block;
  font-size: 14px;
  margin-bottom: 4px;
}

.notice-box span {
  display: block;
  font-size: 13px;
  line-height: 1.55;
}

.item-list-card {
  margin-top: 22px;
  padding: 18px;
  border-radius: 22px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
}

html.is-dark .item-list-card {
  background: rgba(30, 41, 59, .78);
  border-color: rgba(51, 65, 85, .88);
}

.item-row {
  display: grid;
  grid-template-columns: minmax(0, 1.5fr) minmax(120px, .7fr) minmax(100px, .5fr);
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid #e2e8f0;
}

.item-row:last-child {
  border-bottom: none;
}

.item-row strong {
  display: block;
  font-size: 14px;
  line-height: 1.35;
}

.item-row span,
.item-row small {
  display: block;
  margin-top: 4px;
  color: #64748b;
  font-size: 12px;
}

.item-row b {
  display: block;
  margin-top: 4px;
  font-size: 13px;
}

.login-area {
  margin-top: 16px;
}

.login-btn {
  width: 100%;
  min-height: 54px;
  border-radius: 18px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 10px;
  text-decoration: none;
  color: #fff;
  background: linear-gradient(90deg, #2563eb, #06b6d4);
  font-weight: 900;
  box-shadow: 0 18px 40px rgba(37, 99, 235, .26);
  transition: transform .15s ease, box-shadow .15s ease;
}

.login-btn:hover {
  transform: translateY(-2px);
  box-shadow: 0 24px 52px rgba(37, 99, 235, .32);
}

.invalid-card {
  text-align: center;
  padding: 38px 28px;
}

.invalid-icon {
  width: 92px;
  height: 92px;
  border-radius: 30px;
  margin: 0 auto 18px;
  display: grid;
  place-items: center;
  background: #fee2e2;
  color: #dc2626;
  font-size: 42px;
}

.invalid-card h2 {
  margin: 0;
  font-size: 26px;
  font-weight: 950;
  letter-spacing: -.03em;
}

.invalid-card p {
  margin: 10px auto 0;
  max-width: 480px;
  color: #64748b;
  line-height: 1.65;
}

html.is-dark .invalid-card p {
  color: #94a3b8;
}

.invalid-code {
  margin-top: 18px;
  padding: 14px;
  border-radius: 16px;
  background: #f8fafc;
  border: 1px dashed #cbd5e1;
  text-align: left;
}

html.is-dark .invalid-code {
  background: rgba(30, 41, 59, .72);
  border-color: rgba(71, 85, 105, .9);
}

.invalid-code span {
  display: block;
  font-size: 11px;
  letter-spacing: .1em;
  text-transform: uppercase;
  color: #64748b;
  font-weight: 900;
  margin-bottom: 6px;
}

.invalid-code strong {
  font-size: 13px;
  word-break: break-all;
}

.loading-card {
  min-height: 520px;
}

.skeleton {
  position: relative;
  overflow: hidden;
  border-radius: 12px;
  background: #e2e8f0;
}

html.is-dark .skeleton {
  background: #334155;
}

.skeleton::after {
  content: '';
  position: absolute;
  inset: 0;
  transform: translateX(-100%);
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .45), transparent);
  animation: shimmer 1.2s infinite;
}

html.is-dark .skeleton::after {
  background: linear-gradient(90deg, transparent, rgba(255, 255, 255, .10), transparent);
}

.skeleton-title {
  width: 55%;
  height: 34px;
  margin-bottom: 18px;
}

.skeleton-line {
  width: 100%;
  height: 18px;
  margin-bottom: 12px;
}

.skeleton-line.short {
  width: 70%;
}

.skeleton-grid {
  margin-top: 30px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 14px;
}

.skeleton-box {
  height: 92px;
}

@keyframes shimmer {
  100% {
    transform: translateX(100%);
  }
}

@media (max-width: 1024px) {
  .validate-shell {
    grid-template-columns: 1fr;
    align-items: start;
    padding: 24px 0 36px;
  }

  .validate-hero {
    min-height: 420px;
  }

  .validate-hero p {
    max-width: 720px;
  }
}

@media (max-width: 768px) {
  .validate-page {
    padding-bottom: 86px;
  }

  .validate-shell {
    width: 100%;
    padding: 0;
    gap: 0;
  }

  .validate-hero {
    min-height: auto;
    border-radius: 0 0 34px 34px;
    padding: 22px 18px 26px;
  }

  .brand-mark {
    width: 48px;
    height: 48px;
    border-radius: 16px;
  }

  .brand-mark img {
    width: 38px;
    height: 38px;
  }

  .status-icon {
    margin-top: 28px;
    width: 82px;
    height: 82px;
    border-radius: 26px;
    font-size: 38px;
  }

  .validate-hero h1 {
    font-size: 34px;
    margin-top: 18px;
  }

  .validate-hero p {
    font-size: 14px;
    line-height: 1.6;
  }

  .hero-summary {
    margin-top: 18px;
  }

  .validate-content {
    padding: 14px 12px 0;
  }

  .content-card {
    border-radius: 24px;
    padding: 18px;
  }

  .card-header,
  .document-summary {
    flex-direction: column;
    gap: 10px;
  }

  .status-pill {
    width: 100%;
    text-align: center;
  }

  .signer-focus-card {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .signer-seal {
    margin: 0 auto;
  }

  .main-info-grid {
    grid-template-columns: 1fr;
    gap: 10px;
  }

  .info-item {
    grid-template-columns: 38px minmax(0, 1fr);
    padding: 13px;
  }

  .info-icon {
    width: 38px;
    height: 38px;
    border-radius: 13px;
  }

  .notice-box {
    grid-template-columns: 1fr;
    text-align: center;
  }

  .notice-box i {
    margin: 0 auto;
  }

  .item-row {
    grid-template-columns: 1fr;
  }

  .login-area.mobile-bottom {
    position: fixed;
    left: 0;
    right: 0;
    bottom: 0;
    z-index: 60;
    padding: 12px 14px calc(12px + env(safe-area-inset-bottom));
    margin: 0;
    background: rgba(248, 250, 252, .86);
    backdrop-filter: blur(14px);
    border-top: 1px solid rgba(226, 232, 240, .9);
  }

  html.is-dark .login-area.mobile-bottom {
    background: rgba(15, 23, 42, .86);
    border-color: rgba(51, 65, 85, .9);
  }

  .login-btn {
    min-height: 52px;
    border-radius: 16px;
  }

  .skeleton-grid {
    grid-template-columns: 1fr;
  }
}
</style>