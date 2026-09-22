<template>
  <div class="temuan-page">
    <ConfirmDialog group="temuan-ketidaksesuaian" />
    <div class="column">
      <VCard class="temuan-hero-card">
        <div class="temuan-hero-content">
          <div class="temuan-hero-copy">
            <img
              src="/@src/assets/illustrations/dashboards/personal/UMRO.png"
              alt="UMRO Laboratory"
              class="temuan-hero-logo"
            />
            <div>
              <h3 class="temuan-hero-title">TEMUAN KETIDAKSESUAIAN</h3>
              <p class="temuan-hero-subtitle">FMMO-163-14.4.3.b-88.5 · Laporan Ringkas dan Lembar Temuan</p>
            </div>
          </div>

          <div class="temuan-hero-actions">
            <VButton outlined icon="feather:users" @click="goToMapping()">
              Mapping Auditor
            </VButton>
            <VButton v-if="hasAuditorAssignment" color="info" outlined icon="feather:file-text" @click="openPakta()">
              Pakta Integritas
            </VButton>
            <VButton v-if="hasAuditorAssignment" color="warning" outlined icon="feather:printer" :disabled="!paktaTersimpan"
              @click="cetakPakta()">
              Cetak Pakta
            </VButton>
            <VButton color="warning" raised icon="feather:printer" :disabled="!canPrintAnnualReport"
              @click="cetakLaporan('gresik')">
              Cetak Temuan Gresik
            </VButton>
            <VButton color="warning" raised icon="feather:printer" :disabled="!canPrintAnnualReport"
              @click="cetakLaporan('jakarta')">
              Cetak Temuan Jakarta
            </VButton>
            <VButton color="warning" raised icon="feather:printer" :disabled="!canPrintAnnualReport"
              @click="cetakLaporan('gabungan')">
              Cetak Temuan Gabungan
            </VButton>
          </div>
        </div>
      </VCard>
    </div>

    <div class="column">
      <VCard class="filter-card">
        <div class="filter-layout">
          <div>
            <p class="eyebrow">Periode laporan</p>
            <div class="year-control">
              <VField>
                <VControl icon="feather:calendar">
                  <div class="select is-fullwidth">
                    <select v-model="selectedYear" @change="loadPeriodData()">
                      <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                    </select>
                  </div>
                </VControl>
              </VField>
              <VButton color="info" outlined icon="feather:refresh-ccw" :loading="loading"
                @click="loadPeriodData()">
                Muat Ulang
              </VButton>
            </div>
          </div>

          <div class="summary-cards">
            <div class="summary-card">
              <span class="summary-value">{{ visibleAudits.length }}</span>
              <span class="summary-label">Jenis Audit</span>
            </div>
            <div class="summary-card category-1">
              <span class="summary-value">{{ totals.kategori1 }}</span>
              <span class="summary-label">Major</span>
            </div>
            <div class="summary-card category-2">
              <span class="summary-value">{{ totals.kategori2 }}</span>
              <span class="summary-label">Minor</span>
            </div>
            <div class="summary-card category-3">
              <span class="summary-value">{{ totals.kategori3 }}</span>
              <span class="summary-label">Observasi</span>
            </div>
          </div>
        </div>
      </VCard>
    </div>

    <div class="column pt-0">
      <VCard class="current-role-card">
        <div class="current-role-icon"><i class="iconify" data-icon="feather:user-check"></i></div>
        <div class="current-role-copy">
          <span>Peran Anda pada Tahun {{ selectedYear }}</span>
          <div v-if="displayAuditRoles.length" class="current-role-list">
            <VTag v-for="role in displayAuditRoles" :key="role.displayKey" :color="mappingRoleColor(role.peran)" rounded>
              {{ role.tugas }} · {{ role.jenisaudit }}
            </VTag>
          </div>
          <p v-else>Anda belum termapping sebagai auditor, manager, maupun auditee pada periode ini.</p>
        </div>
      </VCard>
    </div>

    <div v-if="message.text" class="column pt-0">
      <div class="notification" :class="message.type === 'error' ? 'is-danger is-light' : 'is-success is-light'">
        <button class="delete" @click="message.text = ''"></button>
        {{ message.text }}
      </div>
    </div>

    <div v-if="loading" class="column">
      <VCard class="empty-card">
        <VPlaceloadText :lines="4" width="100%" last-line-width="55%" />
      </VCard>
    </div>

    <div v-else-if="visibleAudits.length === 0" class="column">
      <VCard class="empty-card">
        <div class="empty-icon"><i class="iconify" data-icon="feather:clipboard"></i></div>
        <h3>Tidak ada penugasan audit tahun {{ selectedYear }}</h3>
        <p>Jenis audit akan muncul otomatis sesuai mapping Auditor, Manager, atau Auditee Anda.</p>
      </VCard>
    </div>

    <div v-else v-for="audit in visibleAudits" :key="audit.kodejenisaudit" class="column">
      <VCard class="audit-card">
        <div class="audit-header">
          <div class="audit-heading">
            <div class="audit-icon"><i class="iconify" data-icon="feather:check-square"></i></div>
            <div>
              <div class="audit-title-row">
                <h3>{{ audit.jenisaudit }}</h3>
                <VTag v-if="!canManageAudit(audit)" color="warning" rounded>{{ readOnlyModeLabel(audit) }}</VTag>
                <VTag v-if="audit.tanggalaudit" color="info" rounded>{{ formatDate(audit.tanggalaudit) }}</VTag>
                <VTag v-else color="light" rounded>Tanggal otomatis saat temuan disimpan</VTag>
              </div>
              <p>{{ audit.namalpk }}</p>
              <p class="audit-standard">{{ audit.standaracuan }}</p>
            </div>
          </div>

          <div class="audit-actions">
            <VButton v-if="canManageAudit(audit)" color="primary" outlined icon="feather:plus"
              :disabled="!paktaTersimpan || Number(selectedYear) !== currentYear"
              @click="openTemuanForm(audit)">
              Tambah Temuan
            </VButton>
          </div>
        </div>

        <div v-if="canManageAudit(audit)" class="audit-document-strip">
          <span class="audit-document-label">
            <i class="iconify" data-icon="feather:book-open"></i>
            Dokumen Auditor
          </span>
          <div class="audit-document-actions">
            <VButton
              v-for="document in auditDocuments"
              :key="`${audit.kodejenisaudit}-${document.code}`"
              color="info"
              outlined
              icon="feather:file-text"
              @click="viewAuditDocument(document.code)"
            >
              {{ document.name }}
            </VButton>
          </div>
        </div>

        <div class="audit-meta-grid">
          <div class="participant-block">
            <span class="meta-label">Tim Audit</span>
            <div v-if="audit.timAudit.length" class="participant-list">
              <span v-for="person in audit.timAudit" :key="`auditor-${person.id}`" class="participant-chip">
                {{ person.nama }} <small>· {{ person.tugas }}</small>
              </span>
            </div>
            <span v-else class="muted-text">Belum diisi</span>
          </div>
          <div class="participant-block">
            <span class="meta-label">Auditee</span>
            <div v-if="audit.auditee.length" class="participant-list">
              <span v-for="person in audit.auditee" :key="`auditee-${person.id}`" class="participant-chip auditee">
                {{ person.nama }} <small>· {{ person.tugas }}</small>
              </span>
            </div>
            <span v-else class="muted-text">Belum diisi</span>
          </div>
          <div class="count-strip">
            <span><b>{{ audit.kategori1 }}</b> Major</span>
            <span><b>{{ audit.kategori2 }}</b> Minor</span>
            <span><b>{{ audit.kategori3 }}</b> Observasi</span>
            <span class="count-total"><b>{{ audit.jumlahtemuan }}</b> Total</span>
          </div>
        </div>

        <div class="table-wrap">
          <DataTable :value="audit.temuan" class="p-datatable-sm" responsiveLayout="scroll" showGridlines>
            <Column header="No" style="width:60px">
              <template #body="slotProps">{{ slotProps.index + 1 }}</template>
            </Column>
            <Column header="Bagian" style="min-width:140px">
              <template #body="slotProps">
                <div>{{ slotProps.data.bagian }}</div>
                <VTag v-if="audit.kodejenisaudit === 'mutu' && slotProps.data.lokasimutu"
                  class="lokasi-mutu-tag" :color="lokasiMutuColor(slotProps.data.lokasimutu)" rounded>
                  {{ lokasiMutuLabel(slotProps.data.lokasimutu) }}
                </VTag>
              </template>
            </Column>
            <Column field="klausul" header="Klausul" style="min-width:100px" />
            <Column header="Kategori" style="min-width:110px">
              <template #body="slotProps">
                <VTag :color="categoryColor(slotProps.data.kategoritemuan)" rounded>
                  {{ categoryLabel(slotProps.data.kategoritemuan) }}
                </VTag>
              </template>
            </Column>
            <Column field="uraianketidaksesuaian" header="Uraian Ketidaksesuaian" style="min-width:360px">
              <template #body="slotProps">
                <div class="finding-description">{{ slotProps.data.uraianketidaksesuaian }}</div>
              </template>
            </Column>
            <Column field="auditor" header="Auditor/Pengisi" style="min-width:170px" />
            <Column v-if="canManageAudit(audit)" header="Aksi" style="width:120px">
              <template #body="slotProps">
                <div class="row-actions">
                  <VButton color="info" outlined icon="feather:edit-2" @click="openTemuanForm(audit, slotProps.data)" />
                  <VButton color="danger" outlined icon="feather:trash-2" @click="deleteTemuan(slotProps.data)" />
                </div>
              </template>
            </Column>
            <template #empty>
              <div class="table-empty">
                {{ canManageAudit(audit)
                  ? 'Belum ada temuan. Klik “Tambah Temuan” untuk mulai mengisi.'
                  : 'Belum ada temuan yang dicatat oleh Tim Audit.' }}
              </div>
            </template>
          </DataTable>
        </div>
      </VCard>
    </div>

    <VModal
      :open="paktaModalOpen"
      title="FMMO-163-14.4.3.b-88.8 Pakta Integritas Auditor"
      size="large"
      actions="right"
      :noclose="!paktaTersimpan"
      :hide-close="!paktaTersimpan"
      @close="closePakta()"
    >
      <template #content>
        <div class="pakta-notice">
          <i class="iconify" data-icon="feather:shield"></i>
          <div>
            <b>Pakta integritas wajib diisi sebelum mengelola temuan.</b>
            <p>Nama dan NID diambil dari akun login; jabatan diambil dari mapping Tim Audit Internal tahun {{ selectedYear }}.</p>
          </div>
        </div>

        <div v-if="paktaError" class="notification is-danger is-light py-3">
          {{ paktaError }}
        </div>

        <div class="identity-grid">
          <div><span>Nama</span><b>{{ identity.namalengkap || '-' }}</b></div>
          <div><span>NID</span><b>{{ identity.nid || '-' }}</b></div>
          <div><span>Jabatan Tim Audit Internal</span><b>{{ auditTeamPosition }}</b></div>
        </div>

        <div class="pakta-copy">
          <p><b>Bertugas sebagai Auditor pada Laboratorium Kalibrasi KAN 284 IDN</b></p>
          <p>Dengan ini menyatakan bahwa dalam melaksanakan tugas Audit, saya:</p>
          <ol>
            <li>Bersikap adil, bekerja dengan obyektif dan bertanggung jawab serta menjunjung tinggi kejujuran;</li>
            <li>Menjaga kerahasiaan data dan informasi yang diperoleh serta hasil pelaksanaan proses audit.</li>
            <li>Tidak melakukan perjanjian dan/atau kesepakatan yang mengakibatkan hasil audit tidak obyektif atau berpihak;</li>
            <li>Tidak menerima apa pun yang dapat mempengaruhi hasil Audit; dan</li>
            <li>Mematuhi persyaratan umum yang berlaku pada ISO/IEC 17025:2017.</li>
          </ol>
        </div>

        <div class="columns is-multiline mt-2">
          <div class="column is-6">
            <VField label="Tanggal Pernyataan *">
              <VControl icon="feather:calendar">
                <VInput v-model="paktaForm.tanggal" type="date" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <VField label="Lokasi *">
              <VControl>
                <Dropdown v-model="paktaForm.lokasi" :options="lokasiOptions" optionLabel="label" optionValue="value"
                  placeholder="Pilih Jakarta/Gresik" class="is-input is-fullwidth" appendTo="body" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Tanda Tangan Auditor *">
              <div class="signature-shell">
                <canvas
                  ref="signatureCanvas"
                  width="900"
                  height="240"
                  @pointerdown="startSignature"
                  @pointermove="drawSignature"
                  @pointerup="endSignature"
                  @pointercancel="endSignature"
                  @pointerleave="endSignature"
                ></canvas>
                <span v-if="!hasSignature" class="signature-hint">Tanda tangan di area ini dengan mouse, stylus, atau sentuhan</span>
              </div>
              <div class="signature-tools">
                <span>Tanda tangan tersimpan sebagai gambar dan ditampilkan pada cetakan pakta.</span>
                <VButton type="button" color="danger" outlined icon="feather:trash-2" @click="clearSignature()">
                  Bersihkan
                </VButton>
              </div>
            </VField>
          </div>
        </div>
      </template>
      <template #cancel>
        <VButton v-if="paktaTersimpan" outlined @click="closePakta()">Tutup</VButton>
      </template>
      <template #action>
        <VButton color="primary" raised icon="feather:save" :loading="savingPakta" @click="savePakta()">
          Simpan Pakta Integritas
        </VButton>
      </template>
    </VModal>

    <VModal :open="temuanModalOpen" :title="temuanForm.id ? 'Edit Temuan Ketidaksesuaian' : 'Tambah Temuan Ketidaksesuaian'"
      size="large" actions="right" @close="temuanModalOpen = false">
      <template #content>
        <div v-if="temuanError" class="notification is-danger is-light py-3">
          {{ temuanError }}
        </div>
        <div class="selected-audit-info">
          <span>Jenis Audit</span>
          <b>{{ selectedAudit?.jenisaudit || '-' }}</b>
        </div>

        <div class="columns is-multiline">
          <div :class="selectedAudit?.kodejenisaudit === 'mutu' ? 'column is-3' : 'column is-5'">
            <VField label="Bagian *">
              <VControl icon="feather:layers">
                <VInput v-model="temuanForm.bagian" placeholder="Contoh: Mutu / Tekanan Jakarta" />
              </VControl>
            </VField>
          </div>
          <div v-if="selectedAudit?.kodejenisaudit === 'mutu'" class="column is-3">
            <VField label="Lokasi Mutu *">
              <Dropdown v-model="temuanForm.lokasimutu" :options="lokasiMutuOptions" optionLabel="label"
                optionValue="value" placeholder="Pilih lokasi" class="is-input is-fullwidth" appendTo="body" />
            </VField>
          </div>
          <div :class="selectedAudit?.kodejenisaudit === 'mutu' ? 'column is-2' : 'column is-3'">
            <VField label="Klausul *">
              <VControl icon="feather:hash">
                <VInput v-model="temuanForm.klausul" placeholder="Contoh: 6.4.2" />
              </VControl>
            </VField>
          </div>
          <div class="column is-4">
            <VField label="Kategori Temuan *">
              <Dropdown v-model="temuanForm.kategoritemuan" :options="categoryOptions" optionLabel="label"
                optionValue="value" placeholder="Pilih kategori" class="is-input is-fullwidth" appendTo="body" />
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Uraian Ketidaksesuaian *">
              <VControl>
                <textarea v-model="temuanForm.uraianketidaksesuaian" class="textarea" rows="7"
                  placeholder="Tuliskan uraian temuan secara lengkap seperti pada lembar ketidaksesuaian..."></textarea>
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Auditor / Pengisi (otomatis)">
              <VControl icon="feather:user-check">
                <VInput :model-value="identity.namalengkap" readonly />
              </VControl>
            </VField>
          </div>
        </div>
      </template>
      <template #action>
        <VButton color="primary" raised icon="feather:save" :loading="savingTemuan" @click="saveTemuan()">
          Simpan Temuan
        </VButton>
      </template>
    </VModal>
  </div>
</template>

<script setup lang="ts">
import { computed, nextTick, onMounted, reactive, ref } from 'vue'
import { useHead } from '@vueuse/head'
import moment from 'moment'
import Column from 'primevue/column'
import ConfirmDialog from 'primevue/confirmdialog'
import DataTable from 'primevue/datatable'
import Dropdown from 'primevue/dropdown'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'Temuan Ketidaksesuaian - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const router = useRouter()
const confirm = useConfirm()
const currentYear = new Date().getFullYear()
const selectedYear = ref(currentYear)
const yearOptions = Array.from({ length: 16 }, (_, index) => currentYear + 5 - index)
const loading = ref(false)
const savingPakta = ref(false)
const savingTemuan = ref(false)
const audits: any = ref([])
const annualFindingCount = ref(0)
const auditorMappings: any = ref([])
const myAuditRoles: any = ref([])
const jenisAuditOptions: any = ref([])
const paktaTersimpan = ref(false)
const paktaModalOpen = ref(false)
const temuanModalOpen = ref(false)
const selectedAudit: any = ref(null)
const signatureCanvas = ref<HTMLCanvasElement | null>(null)
const signatureDrawing = ref(false)
const hasSignature = ref(false)
const savedSignature = ref('')
const paktaError = ref('')
const temuanError = ref('')

const identity: any = reactive({
  loginuserid: null,
  pegawaiid: null,
  namalengkap: '',
  nid: '',
  jabatan: '',
  jabatanTimAudit: '',
})

const reference = reactive({
  namaLpk: 'Laboratorium Kalibrasi PT PLN NP UMRO',
  standarAcuan: 'SNI ISO/IEC 17025:2017 (ISO/IEC 17025:2017)',
})

const message = reactive({
  type: 'success',
  text: '',
})

const paktaForm = reactive({
  tanggal: moment().format('YYYY-MM-DD'),
  lokasi: 'Jakarta',
})

const createTemuanForm = () => ({
  id: null as number | null,
  auditfk: null as number | null,
  bagian: '',
  klausul: '',
  kategoritemuan: 2,
  uraianketidaksesuaian: '',
  lokasimutu: null as number | null,
})
const temuanForm: any = ref(createTemuanForm())

const lokasiOptions = [
  { value: 'Jakarta', label: 'Jakarta' },
  { value: 'Gresik', label: 'Gresik' },
]

const lokasiMutuOptions = [
  { value: 1, label: 'Mutu Jakarta' },
  { value: 2, label: 'Mutu Gresik' },
]

const categoryOptions = [
  { value: 1, label: '1 - Major' },
  { value: 2, label: '2 - Minor' },
  { value: 3, label: '3 - Observasi' },
]

const auditDocuments = [
  {
    code: 'check-list-audit-internal',
    name: 'FMMO-163-14.4.3.b-88.3 - Formulir Check List audit Internal.docx',
  },
  {
    code: 'daftar-pertanyaan-audit',
    name: 'FMMO-163-14.4.3.b-88.4_Daftar_Pertanyaan_Audit.docx',
  },
]

const writableAuditRoles = ['lead_auditor', 'auditor_observer', 'auditor']
const isWritableAuditRole = (role: string) => writableAuditRoles.includes(role)

const myAuditorRoles = computed(() => {
  const seen = new Set<string>()
  return myAuditRoles.value.filter((role: any) => {
    if (!isWritableAuditRole(role.peran) || seen.has(role.kodejenisaudit)) return false
    seen.add(role.kodejenisaudit)
    return true
  })
})

const hasAuditorAssignment = computed(() => myAuditorRoles.value.length > 0)
const canPrintAnnualReport = computed(() => annualFindingCount.value > 0)

const displayAuditRoles = computed(() => {
  const globalRolesSudahDitampilkan = new Set<string>()
  return myAuditRoles.value.reduce((result: any[], role: any) => {
    if (role.peran === 'lead_auditor' || role.peran === 'manager') {
      if (globalRolesSudahDitampilkan.has(role.peran)) return result
      globalRolesSudahDitampilkan.add(role.peran)
      result.push({
        ...role,
        displayKey: `${role.peran}-${role.tahun}`,
        jenisaudit: 'Seluruh Jenis Audit',
      })
      return result
    }
    result.push({ ...role, displayKey: `mapping-${role.id}` })
    return result
  }, [])
})

const visibleScopeRoles = computed(() => jenisAuditOptions.value
  .reduce((result: any[], jenis: any, referenceOrder: number) => {
    const roles = myAuditRoles.value.filter((role: any) => role.kodejenisaudit === jenis.value)
    if (!roles.length) return result
    const role = roles.find((item: any) => isWritableAuditRole(item.peran))
      || roles.find((item: any) => item.peran === 'manager')
      || roles[0]
    result.push({ ...role, referenceOrder })
    return result
  }, [])
  .sort((left: any, right: any) => {
    const leftAuditee = left.peran === 'auditee' ? 1 : 0
    const rightAuditee = right.peran === 'auditee' ? 1 : 0
    return leftAuditee - rightAuditee || left.referenceOrder - right.referenceOrder
  }))

const canManageAudit = (audit: any) => Boolean(audit?.bolehmengisi) || myAuditRoles.value.some((role: any) => (
  role.kodejenisaudit === audit?.kodejenisaudit && isWritableAuditRole(role.peran)
))

const readOnlyModeLabel = (audit: any) => myAuditRoles.value.some((role: any) => (
  role.kodejenisaudit === audit?.kodejenisaudit && role.peran === 'manager'
)) ? 'Mode Manager · Lihat Saja' : 'Mode Auditee · Lihat Saja'

const visibleAudits = computed(() => visibleScopeRoles.value.map((role: any) => {
  const actual = audits.value.find((audit: any) => audit.kodejenisaudit === role.kodejenisaudit)
  if (actual) {
    return {
      ...actual,
      bolehmengisi: Boolean(actual.bolehmengisi || isWritableAuditRole(role.peran)),
    }
  }

  const jenis = jenisAuditOptions.value.find((item: any) => item.value === role.kodejenisaudit) || role
  const mapping = auditorMappings.value.filter((row: any) => row.kodejenisaudit === role.kodejenisaudit)
  const participant = (row: any) => ({
    id: `mapping-${row.id}`,
    pegawaifk: row.pegawaifk,
    nama: row.namapegawai,
    tugas: row.tugas,
  })

  return {
    id: null,
    kodejenisaudit: role.kodejenisaudit,
    jenisaudit: jenis.label || role.jenisaudit,
    lingkup: jenis.lingkup || role.lingkup,
    lokasi: jenis.lokasi || role.lokasi,
    tanggalaudit: null,
    namalpk: reference.namaLpk,
    standaracuan: reference.standarAcuan,
    timAudit: mapping.filter((row: any) => isWritableAuditRole(row.peran)).map(participant),
    auditee: mapping.filter((row: any) => row.peran === 'auditee').map(participant),
    temuan: [],
    kategori1: 0,
    kategori2: 0,
    kategori3: 0,
    jumlahtemuan: 0,
    bolehmengisi: isWritableAuditRole(role.peran),
  }
}))

const totals = computed(() => visibleAudits.value.reduce((result: any, audit: any) => {
  result.kategori1 += Number(audit.kategori1 || 0)
  result.kategori2 += Number(audit.kategori2 || 0)
  result.kategori3 += Number(audit.kategori3 || 0)
  return result
}, { kategori1: 0, kategori2: 0, kategori3: 0 }))

const setMessage = (text: string, type: 'success' | 'error' = 'success') => {
  message.text = text
  message.type = type
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const apiError = (error: any, fallback: string) => {
  return error?.response?._data?.response?.message
    || error?.response?._data?.message
    || error?.response?.data?.response?.message
    || error?.response?.data?.message
    || error?.message
    || (typeof error === 'string' ? error : '')
    || fallback
}

const assignIdentity = (pegawai: any) => {
  if (!pegawai) return
  identity.loginuserid = pegawai.loginuserid
  identity.pegawaiid = pegawai.pegawaiid
  identity.namalengkap = pegawai.namalengkap || ''
  identity.nid = pegawai.nid || ''
  identity.jabatan = pegawai.jabatan || ''
}

const loadReference = async () => {
  const res: any = await useApi().get('mutu/referensi-temuan-ketidaksesuaian')
  jenisAuditOptions.value = res?.jenisAudit || []
  reference.namaLpk = res?.namaLpk || reference.namaLpk
  reference.standarAcuan = res?.standarAcuan || reference.standarAcuan
  assignIdentity(res?.pegawai)
}

const loadPakta = async () => {
  if (!hasAuditorAssignment.value) {
    paktaTersimpan.value = false
    paktaModalOpen.value = false
    savedSignature.value = ''
    return
  }
  const res: any = await useApi().get(`mutu/pakta-integritas-auditor?tahun=${selectedYear.value}`)
  assignIdentity(res?.pegawai)
  identity.jabatanTimAudit = res?.jabatanTimAudit || ''
  paktaTersimpan.value = Boolean(res?.pakta)

  if (res?.pakta) {
    paktaForm.tanggal = moment(res.pakta.tanggal).format('YYYY-MM-DD')
    paktaForm.lokasi = res.pakta.lokasi || 'Jakarta'
    savedSignature.value = res.pakta.tandatangan || ''
  } else {
    paktaModalOpen.value = true
  }
}

const loadAudits = async () => {
  loading.value = true
  try {
    const res: any = await useApi().get(`mutu/temuan-ketidaksesuaian?tahun=${selectedYear.value}`)
    audits.value = (res?.audits || []).map((audit: any) => ({
      ...audit,
      temuan: audit.temuan || [],
      timAudit: audit.timAudit || [],
      auditee: audit.auditee || [],
    }))
    if (Array.isArray(res?.mappingPengguna)) {
      myAuditRoles.value = res.mappingPengguna
    }
    annualFindingCount.value = Number(res?.jumlahTemuanTahun || 0)
    if (res?.jenisAudit?.length) jenisAuditOptions.value = res.jenisAudit
  } catch (error: any) {
    annualFindingCount.value = 0
    setMessage(apiError(error, 'Data audit gagal dimuat.'), 'error')
  } finally {
    loading.value = false
  }
}

const formatDate = (date: string) => date ? moment(date).format('DD MMMM YYYY') : '-'
const categoryColor = (category: number): 'danger' | 'warning' | 'info' => (
  Number(category) === 1 ? 'danger' : Number(category) === 2 ? 'warning' : 'info'
)
const categoryLabel = (category: number) => Number(category) === 1 ? 'Major' : Number(category) === 2 ? 'Minor' : 'Observasi'
const mappingRoleColor = (role: string): 'primary' | 'info' | 'success' | 'warning' => {
  if (role === 'lead_auditor') return 'primary'
  if (role === 'manager') return 'info'
  if (role === 'auditor_observer') return 'info'
  if (role === 'auditee') return 'warning'
  return 'success'
}

const auditTeamPosition = computed(() => {
  const roles = myAuditRoles.value.map((role: any) => role.peran)
  if (roles.includes('lead_auditor')) return 'Lead Auditor'
  if (roles.includes('auditor_observer')) return 'Auditor Observer'
  if (roles.includes('auditor')) return 'Anggota Auditor'
  return identity.jabatanTimAudit || 'Belum termapping'
})

const fetchAuditorMapping = async (year: number) => {
  return await useApi().get(`mutu/mapping-auditor-internal?tahun=${year}`) as any
}

const loadAuditorMapping = async () => {
  try {
    const res: any = await fetchAuditorMapping(Number(selectedYear.value))
    identity.jabatanTimAudit = ''
    auditorMappings.value = res?.rows || []
    myAuditRoles.value = res?.peranSaya || []
    identity.jabatanTimAudit = auditTeamPosition.value === 'Belum termapping' ? '' : auditTeamPosition.value
  } catch (error: any) {
    identity.jabatanTimAudit = ''
    auditorMappings.value = []
    myAuditRoles.value = []
    setMessage(apiError(error, 'Mapping auditor gagal dimuat.'), 'error')
  }
}

const loadPeriodData = async () => {
  await loadAuditorMapping()
  await loadPakta()
  await loadAudits()
}

const canvasContext = () => {
  const canvas = signatureCanvas.value
  if (!canvas) return null
  const context = canvas.getContext('2d')
  if (context) {
    context.strokeStyle = '#173b6c'
    context.lineWidth = 3
    context.lineCap = 'round'
    context.lineJoin = 'round'
  }
  return context
}

const canvasPoint = (event: PointerEvent) => {
  const canvas = signatureCanvas.value!
  const rect = canvas.getBoundingClientRect()
  return {
    x: (event.clientX - rect.left) * (canvas.width / rect.width),
    y: (event.clientY - rect.top) * (canvas.height / rect.height),
  }
}

const startSignature = (event: PointerEvent) => {
  const canvas = signatureCanvas.value
  const context = canvasContext()
  if (!canvas || !context) return
  event.preventDefault()
  canvas.setPointerCapture(event.pointerId)
  const point = canvasPoint(event)
  context.beginPath()
  context.moveTo(point.x, point.y)
  signatureDrawing.value = true
}

const drawSignature = (event: PointerEvent) => {
  if (!signatureDrawing.value) return
  const context = canvasContext()
  if (!context) return
  event.preventDefault()
  const point = canvasPoint(event)
  context.lineTo(point.x, point.y)
  context.stroke()
  hasSignature.value = true
}

const endSignature = (event: PointerEvent) => {
  const canvas = signatureCanvas.value
  const context = canvasContext()
  if (!signatureDrawing.value || !canvas || !context) return
  event.preventDefault()
  context.closePath()
  signatureDrawing.value = false
  if (canvas.hasPointerCapture(event.pointerId)) canvas.releasePointerCapture(event.pointerId)
}

const clearSignature = () => {
  const canvas = signatureCanvas.value
  const context = canvas?.getContext('2d')
  if (canvas && context) context.clearRect(0, 0, canvas.width, canvas.height)
  hasSignature.value = false
  savedSignature.value = ''
}

const loadSignatureToCanvas = async () => {
  await nextTick()
  if (!signatureCanvas.value) return
  const canvas = signatureCanvas.value
  const context = canvas.getContext('2d')
  context?.clearRect(0, 0, canvas.width, canvas.height)
  hasSignature.value = false
  if (!savedSignature.value) return
  const image = new Image()
  image.onload = () => {
    context?.drawImage(image, 0, 0, canvas.width, canvas.height)
    hasSignature.value = true
  }
  image.src = savedSignature.value
}

const openPakta = async () => {
  paktaError.value = ''
  paktaModalOpen.value = true
  await loadSignatureToCanvas()
}

const closePakta = () => {
  if (paktaTersimpan.value) paktaModalOpen.value = false
}

const savePakta = async () => {
  paktaError.value = ''
  if (!paktaForm.tanggal || !paktaForm.lokasi) {
    paktaError.value = 'Tanggal dan lokasi pakta integritas wajib diisi.'
    return
  }
  if (!hasSignature.value || !signatureCanvas.value) {
    paktaError.value = 'Tanda tangan auditor wajib diisi.'
    return
  }

  savingPakta.value = true
  try {
    const tandatangan = signatureCanvas.value.toDataURL('image/png')
    await useApi().post('mutu/save-pakta-integritas-auditor', {
      tahun: selectedYear.value,
      tanggal: paktaForm.tanggal,
      lokasi: paktaForm.lokasi,
      tandatangan,
    })
    savedSignature.value = tandatangan
    paktaTersimpan.value = true
    paktaModalOpen.value = false
    setMessage('Pakta integritas berhasil disimpan. Form temuan sudah dapat digunakan.')
    await loadPeriodData()
  } catch (error: any) {
    paktaError.value = apiError(error, 'Pakta integritas gagal disimpan.')
  } finally {
    savingPakta.value = false
  }
}

const defaultBagian = (audit: any) => audit.lingkup === 'Mutu'
  ? 'Mutu'
  : `${audit.lingkup} ${audit.lokasi}`

const lokasiMutuLabel = (lokasi: number) => Number(lokasi) === 1 ? 'Mutu Jakarta' : 'Mutu Gresik'
const lokasiMutuColor = (lokasi: number): 'info' | 'success' => Number(lokasi) === 1 ? 'info' : 'success'

const openTemuanForm = (audit: any, temuan: any = null) => {
  if (!canManageAudit(audit)) return
  temuanError.value = ''
  selectedAudit.value = audit
  temuanForm.value = temuan ? {
    id: temuan.id,
    auditfk: audit.id,
    bagian: temuan.bagian,
    klausul: temuan.klausul,
    kategoritemuan: Number(temuan.kategoritemuan),
    uraianketidaksesuaian: temuan.uraianketidaksesuaian,
    lokasimutu: temuan.lokasimutu ? Number(temuan.lokasimutu) : null,
  } : {
    ...createTemuanForm(),
    auditfk: audit.id,
    bagian: defaultBagian(audit),
  }
  temuanModalOpen.value = true
}

const saveTemuan = async () => {
  temuanError.value = ''
  if (!temuanForm.value.bagian?.trim() || !temuanForm.value.klausul?.trim()
    || !temuanForm.value.uraianketidaksesuaian?.trim()) {
    temuanError.value = 'Bagian, klausul, dan uraian ketidaksesuaian wajib diisi.'
    return
  }
  if (selectedAudit.value?.kodejenisaudit === 'mutu' && ![1, 2].includes(Number(temuanForm.value.lokasimutu))) {
    temuanError.value = 'Lokasi Mutu Jakarta atau Gresik wajib dipilih.'
    return
  }

  savingTemuan.value = true
  try {
    await useApi().post('mutu/save-temuan-ketidaksesuaian', {
      ...temuanForm.value,
      tahun: selectedYear.value,
      kodejenisaudit: selectedAudit.value?.kodejenisaudit,
      bagian: temuanForm.value.bagian.trim(),
      klausul: temuanForm.value.klausul.trim(),
      uraianketidaksesuaian: temuanForm.value.uraianketidaksesuaian.trim(),
    })
    temuanModalOpen.value = false
    setMessage('Temuan ketidaksesuaian berhasil disimpan.')
    await loadAudits()
  } catch (error: any) {
    temuanError.value = apiError(error, 'Temuan ketidaksesuaian gagal disimpan.')
  } finally {
    savingTemuan.value = false
  }
}

const deleteTemuan = (temuan: any) => {
  confirm.require({
    group: 'temuan-ketidaksesuaian',
    header: 'Konfirmasi Hapus Temuan',
    message: 'Temuan ini akan dihapus dari laporan. Tindakan ini tidak dapat dibatalkan.',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Hapus Temuan',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-text',
    accept: () => removeTemuan(temuan),
    reject: () => { },
  })
}

const removeTemuan = async (temuan: any) => {
  try {
    await useApi().post('mutu/hapus-temuan-ketidaksesuaian', { id: temuan.id })
    setMessage('Temuan berhasil dihapus.')
    await loadAudits()
  } catch (error: any) {
    setMessage(apiError(error, 'Temuan gagal dihapus.'), 'error')
  }
}

const cetakPakta = () => H.printBlade(`mutu/cetak-pakta-integritas-auditor?pdf=true&tahun=${selectedYear.value}`)
const cetakLaporan = (lokasi: 'gresik' | 'jakarta' | 'gabungan') => H.printBlade(
  `mutu/cetak-temuan-ketidaksesuaian?pdf=true&tahun=${selectedYear.value}&lokasi=${lokasi}`,
)
const viewAuditDocument = (code: string) => H.printBlade(
  `mutu/view-dokumen-audit-internal?kode=${encodeURIComponent(code)}&tahun=${selectedYear.value}`,
)
const goToMapping = () => router.push({ name: 'module-mutu-mapping-auditor' })

onMounted(async () => {
  loading.value = true
  try {
    await loadReference()
    await loadAuditorMapping()
    await loadPakta()
    await loadAudits()
    if (paktaModalOpen.value) await loadSignatureToCanvas()
  } catch (error: any) {
    setMessage(apiError(error, 'Halaman gagal dimuat.'), 'error')
  } finally {
    loading.value = false
  }
})
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

.temuan-page {
  --audit-primary: #165c75;
  --audit-primary-soft: rgba(22, 92, 117, .09);
}

.temuan-hero-card {
  padding: 0;
  overflow: hidden;
  border-radius: 12px;
}

.temuan-hero-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-height: 0;
  padding: 10px 16px;
  background: var(--white);
  border: 1px solid var(--fade-grey-dark-3);
}

.temuan-hero-copy {
  display: flex;
  align-items: center;
  gap: 11px;
}

.temuan-hero-logo {
  width: auto;
  height: 42px;
  object-fit: contain;
}

.temuan-hero-title {
  margin: 0;
  color: var(--dark-text);
  font-size: 1.08rem;
  font-weight: 800;
  letter-spacing: .4px;
}

.temuan-hero-subtitle {
  margin-top: 3px;
  color: #111;
  font-size: 1rem;
  font-weight: 650;
}

.temuan-hero-actions,
.audit-actions,
.row-actions,
.year-control {
  display: flex;
  align-items: center;
  gap: .65rem;
}

.filter-card {
  border-radius: 14px;
}

.current-role-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-left: 4px solid var(--audit-primary);
  border-radius: 10px;
}

.current-role-icon {
  display: inline-flex;
  flex: 0 0 38px;
  align-items: center;
  justify-content: center;
  width: 38px;
  height: 38px;
  color: var(--audit-primary);
  background: var(--audit-primary-soft);
  border-radius: 50%;
  font-size: 18px;
}

.current-role-copy {
  min-width: 0;
}

.current-role-copy > span {
  color: var(--muted-grey);
  font-size: .74rem;
  font-weight: 700;
  letter-spacing: .05em;
  text-transform: uppercase;
}

.current-role-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 5px;
}

.current-role-copy p {
  margin-top: 4px;
  color: var(--muted-grey);
  font-size: .82rem;
}

.filter-layout {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.eyebrow,
.meta-label {
  display: block;
  margin-bottom: 7px;
  color: var(--muted-grey);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.year-control .field {
  min-width: 155px;
  margin-bottom: 0;
}

.summary-cards {
  display: grid;
  grid-template-columns: repeat(4, minmax(100px, 1fr));
  gap: .65rem;
}

.summary-card {
  min-width: 105px;
  padding: 10px 14px;
  border: 1px solid var(--fade-grey-dark-3);  
  border-left: 4px solid var(--audit-primary);  
  border-radius: 10px;
  background: var(--white);
}

.summary-card.category-1 { border-left-color: #e05260; }
.summary-card.category-2 { border-left-color: #f2a93b; }
.summary-card.category-3 { border-left-color: #3e8ed0; }

.summary-value,
.summary-label {
  display: block;
}

.summary-value {
  color: var(--dark-text);
  font-size: 1.25rem;
  font-weight: 800;
  line-height: 1;
}

.summary-label {
  margin-top: 4px;
  color: var(--muted-grey);
  font-size: .76rem;
}

.empty-card {
  padding: 42px 24px;
  border-radius: 14px;
  text-align: center;
}

.empty-card h3 {
  margin: 8px 0 4px;
  font-size: 1.15rem;
  font-weight: 700;
}

.empty-card p,
.muted-text,
.participant-empty {
  color: var(--muted-grey);
}

.empty-icon,
.audit-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  color: var(--audit-primary);
  background: var(--audit-primary-soft);
  border-radius: 50%;
}

.empty-icon {
  width: 54px;
  height: 54px;
  font-size: 25px;
}

.audit-card {
  padding: 0;
  overflow: hidden;
  border-radius: 14px;
}

.audit-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 1rem;
  padding: 18px 20px;
  border-bottom: 1px solid var(--fade-grey-dark-3);
}

.audit-heading {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.audit-icon {
  flex: 0 0 42px;
  width: 42px;
  height: 42px;
  font-size: 20px;
}

.audit-title-row {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: .6rem;
}

.audit-title-row h3 {
  margin: 0;
  color: var(--dark-text);
  font-size: 1.08rem;
  font-weight: 750;
}

.audit-heading p {
  margin-top: 3px;
  color: var(--muted-grey);
  font-size: .82rem;
}

.audit-heading .audit-standard {
  font-size: .76rem;
}

.audit-document-strip {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 10px 20px;
  background: rgba(62, 142, 208, .045);
  border-bottom: 1px solid var(--fade-grey-dark-3);
}

.audit-document-label {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  gap: 6px;
  color: var(--dark-text);
  font-size: .78rem;
  font-weight: 700;
}

.audit-document-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 7px;
}

.audit-document-actions .button {
  height: auto;
  min-height: 34px;
  padding-top: 6px;
  padding-bottom: 6px;
  white-space: normal;
  text-align: left;
  line-height: 1.25;
}

.audit-meta-grid {
  display: grid;
  grid-template-columns: 1fr 1fr auto;
  gap: 1rem;
  padding: 14px 20px;
  background: rgba(125, 125, 125, .035);
  border-bottom: 1px solid var(--fade-grey-dark-3);
}

.participant-list {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
}

.participant-chip {
  display: inline-flex;
  gap: 3px;
  padding: 4px 8px;
  color: #17617a;
  background: rgba(46, 164, 190, .11);
  border-radius: 999px;
  font-size: .76rem;
}

.participant-chip.auditee {
  color: #247455;
  background: rgba(40, 170, 115, .1);
}

.participant-chip small {
  opacity: .78;
}

.count-strip {
  display: grid;
  grid-template-columns: repeat(2, auto);
  align-content: center;
  gap: 4px 12px;
  padding-left: 15px;
  border-left: 1px solid var(--fade-grey-dark-3);
  color: var(--muted-grey);
  font-size: .76rem;
}

.count-strip b {
  color: var(--dark-text);
}

.count-total {
  color: var(--audit-primary) !important;
  font-weight: 700;
}

.table-wrap {
  padding: 16px 20px 20px;
  overflow-x: auto;
}

.finding-description {
  max-width: 640px;
  white-space: pre-line;
  line-height: 1.45;
}

.lokasi-mutu-tag {
  margin-top: 6px;
  font-size: .7rem;
  font-weight: 700;
}

.table-empty {
  padding: 22px;
  color: var(--muted-grey);
  text-align: center;
}

.pakta-notice {
  display: flex;
  gap: 12px;
  padding: 13px 15px;
  margin-bottom: 16px;
  color: #164d63;
  background: rgba(46, 164, 190, .1);
  border: 1px solid rgba(46, 164, 190, .18);
  border-radius: 10px;
}

.pakta-notice svg {
  flex: 0 0 24px;
  width: 24px;
  height: 24px;
}

.pakta-notice p {
  margin-top: 2px;
  font-size: .82rem;
}

.identity-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 10px;
  margin-bottom: 16px;
}

.identity-grid > div {
  padding: 11px 13px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 9px;
}

.identity-grid span,
.selected-audit-info span {
  display: block;
  color: var(--muted-grey);
  font-size: .73rem;
}

.identity-grid b,
.selected-audit-info b {
  display: block;
  margin-top: 3px;
  color: var(--dark-text);
}

.pakta-copy {
  max-height: 225px;
  overflow-y: auto;
  padding: 14px 18px;
  color: var(--dark-text);
  background: rgba(125, 125, 125, .04);
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 9px;
  font-size: .84rem;
  line-height: 1.45;
}

.pakta-copy ol {
  margin: 8px 0 0 20px;
}

.pakta-copy li {
  margin-bottom: 4px;
}

.signature-shell {
  position: relative;
  width: 100%;
  overflow: hidden;
  background: repeating-linear-gradient(0deg, #fff, #fff 31px, #e8edf0 32px);
  border: 2px dashed #9eb4bd;
  border-radius: 10px;
}

.signature-shell canvas {
  display: block;
  width: 100%;
  height: 190px;
  cursor: crosshair;
  touch-action: none;
}

.signature-hint {
  position: absolute;
  top: 50%;
  left: 50%;
  width: 90%;
  transform: translate(-50%, -50%);
  color: #97a8af;
  text-align: center;
  pointer-events: none;
}

.signature-tools {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-top: 8px;
  color: var(--muted-grey);
  font-size: .75rem;
}

.selected-audit-info {
  padding: 11px 14px;
  margin-bottom: 14px;
  background: var(--audit-primary-soft);
  border-radius: 9px;
}

:deep(.p-dropdown.is-fullwidth) {
  width: 100%;
}

.is-dark {
  .temuan-hero-title,
  .audit-title-row h3,
  .summary-value,
  .count-strip b,
  .identity-grid b,
  .selected-audit-info b,
  .pakta-copy {
    color: var(--dark-dark-text);
  }

  .summary-card,
  .signature-shell {
    background-color: var(--dark-sidebar-light-6);
  }
}

@media (max-width: 1100px) {
  .temuan-hero-content,
  .filter-layout,
  .audit-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .temuan-hero-actions,
  .audit-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .audit-meta-grid {
    grid-template-columns: 1fr 1fr;
  }

  .count-strip {
    grid-column: 1 / -1;
    grid-template-columns: repeat(4, 1fr);
    padding: 10px 0 0;
    border-top: 1px solid var(--fade-grey-dark-3);
    border-left: 0;
  }
}

@media (max-width: 768px) {
  .temuan-hero-content {
    padding: 10px 12px;
  }

  .temuan-hero-copy {
    align-items: flex-start;
  }

  .temuan-hero-logo {
    height: 38px;
  }

  .temuan-hero-title {
    font-size: 1rem;
  }

  .temuan-hero-actions .button,
  .audit-actions .button {
    flex: 1 1 calc(50% - .5rem);
  }

  .summary-cards {
    width: 100%;
    grid-template-columns: repeat(2, 1fr);
  }

  .audit-meta-grid,
  .identity-grid {
    grid-template-columns: 1fr;
  }

  .audit-document-strip {
    align-items: flex-start;
    flex-direction: column;
    padding: 10px 14px;
  }

  .audit-document-actions,
  .audit-document-actions .button {
    width: 100%;
  }

  .count-strip {
    grid-column: auto;
    grid-template-columns: repeat(2, 1fr);
  }

  .signature-shell canvas {
    height: 155px;
  }

  .signature-tools {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
