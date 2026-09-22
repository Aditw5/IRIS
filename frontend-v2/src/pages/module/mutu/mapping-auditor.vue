<template>
  <div class="mapping-page">
    <ConfirmDialog group="mapping-auditor" />
    <div class="column">
      <VCard class="mapping-hero-card">
        <div class="mapping-hero-content">
          <div class="mapping-hero-copy">
            <img src="/@src/assets/illustrations/dashboards/personal/UMRO.png" alt="UMRO Laboratory"
              class="mapping-hero-logo" />
            <div>
              <h3>MAPPING AUDITOR INTERNAL</h3>
              <p>FMMO-163-14.4.3.b-88.2 · Penunjukan Auditor Internal per Tahun</p>
            </div>
          </div>
          <div class="mapping-hero-actions">
            <VButton v-if="!isReadOnlyManager" color="info" outlined icon="feather:copy" @click="openCopyModal()">
              Salin Mapping Tahun
            </VButton>
            <VButton v-if="!isReadOnlyManager" color="primary" raised icon="feather:user-plus" @click="openMappingForm()">
              Tambah Mapping
            </VButton>
            <VButton outlined icon="feather:clipboard" @click="goToTemuan()">
              Halaman Temuan
            </VButton>
          </div>
        </div>
      </VCard>
    </div>

    <div class="column">
      <VCard class="mapping-filter-card">
        <div class="mapping-filter-layout">
          <div>
            <span class="mapping-label">Periode Tahun</span>
            <div class="mapping-year-control">
              <VField>
                <VControl icon="feather:calendar">
                  <VInput v-model.number="selectedYear" type="number" min="2000" max="2100" @change="loadMapping()" />
                </VControl>
              </VField>
              <VButton color="info" outlined icon="feather:refresh-ccw" :loading="loading" @click="loadMapping()">
                Muat Ulang
              </VButton>
            </div>
          </div>

          <div class="mapping-summary">
            <div><b>{{ stats.jenisTerisi }}</b><span>Jenis Audit Terisi</span></div>
            <div><b>{{ stats.auditor }}</b><span>Auditor</span></div>
            <div><b>{{ stats.auditee }}</b><span>Auditee</span></div>
            <div><b>{{ stats.paktaTerisi }}/{{ stats.auditor }}</b><span>Pakta Terisi</span></div>
          </div>
        </div>
      </VCard>
    </div>

    <div class="column pt-0">
      <VCard class="my-role-card">
        <div class="my-role-icon"><i class="iconify" data-icon="feather:user-check"></i></div>
        <div class="my-role-copy">
          <span>Peran Anda pada Tahun {{ selectedYear }}</span>
          <div v-if="displayMyRoles.length" class="my-role-list">
            <VTag v-for="role in displayMyRoles" :key="role.displayKey" :color="roleColor(role.peran)" rounded>
              {{ role.tugas }} · {{ role.jenisaudit }}
            </VTag>
          </div>
          <p v-else>Anda belum mendapatkan penunjukan audit pada periode ini.</p>
        </div>
      </VCard>
    </div>

    <div class="column pt-0">
      <VCard class="lead-auditor-card">
        <div class="lead-auditor-icon"><i class="iconify" data-icon="feather:award"></i></div>
        <div class="lead-auditor-copy">
          <span>Lead Auditor Tahun {{ selectedYear }}</span>
          <template v-if="leadAuditor">
            <b>{{ leadAuditor.namapegawai }}</b>
            <small>Berlaku untuk seluruh jenis audit dan tidak perlu dimapping berulang.</small>
          </template>
          <small v-else>Lead Auditor belum ditetapkan untuk periode ini.</small>
        </div>
        <div class="lead-auditor-actions">
          <VButton v-if="leadAuditor?.sudahpakta" color="success" outlined icon="feather:printer"
            title="Cetak Pakta Integritas Lead Auditor" @click="printPakta(leadAuditor)" />
          <VButton v-if="!isReadOnlyManager" color="primary" outlined :icon="leadAuditor ? 'feather:edit-2' : 'feather:user-plus'"
            @click="openLeadForm()">
            {{ leadAuditor ? 'Ubah Lead' : 'Tetapkan Lead' }}
          </VButton>
        </div>
      </VCard>
    </div>

    <div v-if="pageMessage.text" class="column pt-0">
      <div class="notification" :class="pageMessage.type === 'error' ? 'is-danger is-light' : 'is-success is-light'">
        <button class="delete" @click="pageMessage.text = ''"></button>
        {{ pageMessage.text }}
      </div>
    </div>

    <div v-if="loading" class="column">
      <VCard class="mapping-loading"><VPlaceloadText :lines="5" /></VCard>
    </div>

    <div v-else class="mapping-grid">
      <div v-for="group in groups" :key="group.value" class="column is-6">
        <VCard class="mapping-card">
          <div class="mapping-card-head">
            <div>
              <h4>{{ group.label }}</h4>
              <p>{{ group.lingkup }} · {{ group.lokasi }}</p>
            </div>
            <VTag :color="group.mapping.length ? 'success' : 'light'" rounded>
              {{ group.mapping.length }} orang
            </VTag>
          </div>

          <div v-if="group.mapping.length" class="mapping-members">
            <div v-for="member in group.mapping" :key="member.id" class="mapping-member">
              <div class="member-order">{{ member.urutan }}</div>
              <div class="member-info">
                <b>{{ member.namapegawai }}</b>
                <span>{{ member.tugas }}</span>
                <small v-if="requiresPakta(member.peran)" class="pakta-state"
                  :class="member.sudahpakta ? 'is-complete' : 'is-pending'">
                  <i class="iconify" :data-icon="member.sudahpakta ? 'feather:check-circle' : 'feather:clock'"></i>
                  {{ member.sudahpakta ? 'Pakta sudah diisi' : 'Pakta belum diisi' }}
                </small>
                <small v-else class="pakta-state is-not-required">Tidak wajib mengisi Pakta Auditor</small>
              </div>
              <VTag :color="roleColor(member.peran)" rounded>{{ roleLabel(member.peran) }}</VTag>
              <div class="member-actions">
                <VButton v-if="member.peran === 'auditee'" color="success" outlined icon="feather:download"
                  title="Download Form Penilaian Auditor" @click="viewPenilaianAuditor()" />
                <VButton v-if="requiresPakta(member.peran) && member.sudahpakta" color="success" outlined
                  icon="feather:printer" title="Cetak Pakta Integritas" @click="printPakta(member)" />
                <VButton v-if="!isReadOnlyManager" color="info" outlined icon="feather:edit-2" title="Edit mapping"
                  @click="openMappingForm(member)" />
                <VButton v-if="!isReadOnlyManager" color="danger" outlined icon="feather:trash-2" title="Hapus mapping"
                  @click="deleteMapping(member)" />
              </div>
            </div>
          </div>
          <div v-else class="mapping-empty">
            Belum ada mapping untuk jenis audit ini pada tahun {{ selectedYear }}.
          </div>
        </VCard>
      </div>
    </div>

    <VModal :open="mappingModalOpen" :title="mappingForm.id ? 'Edit Mapping Auditor' : 'Tambah Mapping Auditor'"
      size="large" actions="right" @close="mappingModalOpen = false">
      <template #content>
        <div v-if="mappingError" class="notification is-danger is-light py-3">{{ mappingError }}</div>
        <div class="columns is-multiline">
          <div class="column is-4">
            <VField label="Tahun Periode *">
              <VControl icon="feather:calendar">
                <VInput v-model.number="mappingForm.tahun" type="number" min="2000" max="2100" />
              </VControl>
            </VField>
          </div>
          <div v-if="!isGlobalRole(mappingForm.peran)" class="column is-8">
            <VField label="Jenis Audit *">
              <Dropdown v-model="mappingForm.kodejenisaudit" :options="jenisAuditOptions" optionLabel="label"
                optionValue="value" placeholder="Pilih jenis audit" class="is-input is-fullwidth" appendTo="body"
                @change="updateDefaultTask()" />
            </VField>
          </div>
          <div v-else class="column is-8">
            <div class="global-lead-note">
              Satu Lead Auditor ini otomatis berlaku untuk seluruh jenis audit pada tahun {{ mappingForm.tahun }}.
            </div>
          </div>
          <div class="column is-7">
            <VField label="Pegawai *">
              <AutoComplete v-model="mappingForm.pegawai" :suggestions="pegawaiOptions" @complete="fetchPegawai($event)"
                optionLabel="label" :dropdown="true" :minLength="0" class="is-input" appendTo="body"
                placeholder="Pilih pegawai dari pegawai_m" />
            </VField>
          </div>
          <div class="column is-5">
            <VField label="Peran *">
              <VControl v-if="isGlobalRole(mappingForm.peran)" icon="feather:award">
                <VInput model-value="Lead Auditor" readonly />
              </VControl>
              <Dropdown v-else v-model="mappingForm.peran" :options="mappingRoleOptions" optionLabel="label"
                optionValue="value" placeholder="Pilih peran" class="is-input is-fullwidth" appendTo="body"
                @change="updateDefaultTask()" />
            </VField>
          </div>
          <div class="column is-9">
            <VField label="Tugas dalam Tim *">
              <VControl icon="feather:briefcase">
                <VInput v-model="mappingForm.tugas" placeholder="Contoh: Lead Auditor" />
              </VControl>
            </VField>
          </div>
          <div class="column is-3">
            <VField label="Urutan">
              <VControl icon="feather:list">
                <VInput v-model.number="mappingForm.urutan" type="number" min="1" max="999" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Sumber Dokumen">
              <VControl icon="feather:file-text">
                <VInput v-model="mappingForm.sumberdokumen" placeholder="Nomor dokumen penunjukan auditor" />
              </VControl>
            </VField>
          </div>
        </div>
      </template>
      <template #action>
        <VButton color="primary" raised icon="feather:save" :loading="saving" @click="saveMapping()">
          Simpan Mapping
        </VButton>
      </template>
    </VModal>

    <VModal :open="copyModalOpen" title="Salin Mapping Auditor Antar Tahun" size="medium" actions="right"
      @close="copyModalOpen = false">
      <template #content>
        <div v-if="copyError" class="notification is-danger is-light py-3">{{ copyError }}</div>
        <div class="copy-info">
          Mapping yang belum ada di tahun tujuan akan disalin. Data yang sudah ada tetap dipertahankan dan diaktifkan.
        </div>
        <div class="columns is-multiline mt-2">
          <div class="column is-6">
            <VField label="Tahun Sumber *">
              <VControl icon="feather:calendar">
                <VInput v-model.number="copyForm.tahunSumber" type="number" min="2000" max="2100" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <VField label="Tahun Tujuan *">
              <VControl icon="feather:arrow-right-circle">
                <VInput v-model.number="copyForm.tahunTujuan" type="number" min="2000" max="2100" />
              </VControl>
            </VField>
          </div>
        </div>
      </template>
      <template #action>
        <VButton color="info" raised icon="feather:copy" :loading="copying" @click="copyMapping()">
          Salin Mapping
        </VButton>
      </template>
    </VModal>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useHead } from '@vueuse/head'
import AutoComplete from 'primevue/autocomplete'
import ConfirmDialog from 'primevue/confirmdialog'
import Dropdown from 'primevue/dropdown'
import { useConfirm } from 'primevue/useconfirm'
import { useRouter } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'Mapping Auditor Internal - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const router = useRouter()
const confirm = useConfirm()
const selectedYear = ref(new Date().getFullYear())
const loading = ref(false)
const saving = ref(false)
const copying = ref(false)
const mappingModalOpen = ref(false)
const copyModalOpen = ref(false)
const mappingError = ref('')
const copyError = ref('')
const groups: any = ref([])
const rows: any = ref([])
const myRoles: any = ref([])
const leadAuditor: any = ref(null)
const jenisAuditOptions: any = ref([])
const roleOptions: any = ref([])
const pegawaiOptions: any = ref([])
const referenceSource = ref('FMMO-163-14.4.3.b-88.2 Penunjukan Auditor Internal 2026')

const pageMessage = reactive({ type: 'success', text: '' })
const createMappingForm = () => ({
  id: null as number | null,
  tahun: selectedYear.value,
  kodejenisaudit: '',
  pegawai: null as any,
  peran: 'auditor',
  tugas: '',
  urutan: 5,
  sumberdokumen: Number(selectedYear.value) === 2026 ? referenceSource.value : '',
})
const mappingForm: any = ref(createMappingForm())
const copyForm = reactive({ tahunSumber: selectedYear.value, tahunTujuan: selectedYear.value + 1 })

const writableAuditRoles = ['lead_auditor', 'auditor_observer', 'auditor']
const globalRoles = ['lead_auditor']
const isGlobalRole = (role: string) => globalRoles.includes(role)
const requiresPakta = (role: string) => writableAuditRoles.includes(role)
const isReadOnlyManager = computed(() => myRoles.value.some((role: any) => role.peran === 'manager')
  && !myRoles.value.some((role: any) => requiresPakta(role.peran)))
const mappingRoleOptions = computed(() => roleOptions.value.filter((role: any) => !isGlobalRole(role.value)))
const displayMyRoles = computed(() => {
  const globalRolesSudahDitampilkan = new Set<string>()
  return myRoles.value.reduce((result: any[], role: any) => {
    if (isGlobalRole(role.peran) || role.peran === 'manager') {
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

const stats = computed(() => {
  const auditorRows = rows.value.filter((row: any) => requiresPakta(row.peran))
  const auditeeRows = rows.value.filter((row: any) => row.peran === 'auditee')
  return {
    jenisTerisi: groups.value.filter((group: any) => group.mapping?.length).length,
    auditor: new Set(auditorRows.map((row: any) => row.pegawaifk)).size,
    auditee: new Set(auditeeRows.map((row: any) => row.pegawaifk)).size,
    paktaTerisi: new Set(auditorRows
      .filter((row: any) => Boolean(row.sudahpakta))
      .map((row: any) => row.pegawaifk)).size,
  }
})

const apiError = (error: any, fallback: string) => error?.response?._data?.response?.message
  || error?.response?._data?.message
  || error?.response?.data?.response?.message
  || error?.response?.data?.message
  || error?.message
  || (typeof error === 'string' ? error : '')
  || fallback

const setMessage = (text: string, type: 'success' | 'error' = 'success') => {
  pageMessage.text = text
  pageMessage.type = type
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const roleColor = (role: string): 'primary' | 'info' | 'success' | 'warning' => {
  if (role === 'lead_auditor') return 'primary'
  if (role === 'manager') return 'info'
  if (role === 'auditor_observer') return 'info'
  if (role === 'auditee') return 'warning'
  return 'success'
}

const roleLabel = (role: string) => roleOptions.value.find((item: any) => item.value === role)?.label || role

const loadMapping = async () => {
  loading.value = true
  try {
    const res: any = await useApi().get(`mutu/mapping-auditor-internal?tahun=${selectedYear.value}`)
    groups.value = res?.groups || []
    rows.value = res?.rows || []
    myRoles.value = res?.peranSaya || []
    leadAuditor.value = res?.leadAuditor || null
    jenisAuditOptions.value = res?.jenisAudit || []
    roleOptions.value = res?.peran || []
    referenceSource.value = res?.sumberDokumen || referenceSource.value
  } catch (error: any) {
    setMessage(apiError(error, 'Mapping auditor gagal dimuat.'), 'error')
  } finally {
    loading.value = false
  }
}

const openMappingForm = (mapping: any = null) => {
  mappingError.value = ''
  if (mapping) {
    mappingForm.value = {
      id: mapping.id,
      tahun: Number(mapping.tahun),
      kodejenisaudit: mapping.kodejenisaudit,
      pegawai: { value: mapping.pegawaifk, label: mapping.namapegawai },
      peran: mapping.peran,
      tugas: mapping.tugas,
      urutan: Number(mapping.urutan),
      sumberdokumen: mapping.sumberdokumen || '',
    }
  } else {
    mappingForm.value = createMappingForm()
  }
  mappingModalOpen.value = true
}

const openLeadForm = () => {
  mappingError.value = ''
  const mapping = leadAuditor.value
  mappingForm.value = {
    id: mapping?.id || null,
    tahun: Number(mapping?.tahun || selectedYear.value),
    kodejenisaudit: mapping?.kodejenisaudit || jenisAuditOptions.value[0]?.value || 'mutu',
    pegawai: mapping ? { value: mapping.pegawaifk, label: mapping.namapegawai } : null,
    peran: 'lead_auditor',
    tugas: 'Lead Auditor',
    urutan: 1,
    sumberdokumen: mapping?.sumberdokumen
      || (Number(selectedYear.value) === 2026 ? referenceSource.value : ''),
  }
  mappingModalOpen.value = true
}

const fetchPegawai = async (event: any) => {
  const query = encodeURIComponent(event?.query || '')
  try {
    pegawaiOptions.value = await useApi().get(
      `general/dropdown/pegawai_m?select=id,namalengkap&param_search=namalengkap&query=${query}&limit=20`
    ) as any
  } catch (_) {
    pegawaiOptions.value = []
  }
}

const updateDefaultTask = () => {
  const role = roleOptions.value.find((item: any) => item.value === mappingForm.value.peran)
  if (!role) return
  if (isGlobalRole(mappingForm.value.peran)) {
    mappingForm.value.tugas = 'Lead Auditor'
    mappingForm.value.urutan = 1
    return
  }
  const audit = jenisAuditOptions.value.find((item: any) => item.value === mappingForm.value.kodejenisaudit)
  if (!audit) return
  const location = audit.lokasi === 'Jakarta & Gresik' ? '' : ` ${audit.lokasi}`
  if (mappingForm.value.peran === 'auditor_observer') {
    mappingForm.value.tugas = role.label
  } else {
    mappingForm.value.tugas = `${role.label} ${audit.lingkup}${location}`
  }
  mappingForm.value.urutan = mappingForm.value.peran === 'auditor_observer' ? 2
      : mappingForm.value.peran === 'auditor' ? 5 : 10
}

const saveMapping = async () => {
  mappingError.value = ''
  if (!mappingForm.value.tahun
    || (!isGlobalRole(mappingForm.value.peran) && !mappingForm.value.kodejenisaudit)
    || !mappingForm.value.pegawai?.value
    || !mappingForm.value.peran || !mappingForm.value.tugas?.trim()) {
    mappingError.value = 'Tahun, jenis audit, pegawai, peran, dan tugas wajib diisi.'
    return
  }
  saving.value = true
  try {
    await useApi().post('mutu/save-mapping-auditor-internal', {
      id: mappingForm.value.id,
      tahun: mappingForm.value.tahun,
      kodejenisaudit: mappingForm.value.kodejenisaudit,
      pegawaifk: mappingForm.value.pegawai.value,
      peran: mappingForm.value.peran,
      tugas: mappingForm.value.tugas.trim(),
      urutan: mappingForm.value.urutan,
      sumberdokumen: mappingForm.value.sumberdokumen,
    })
    selectedYear.value = Number(mappingForm.value.tahun)
    mappingModalOpen.value = false
    setMessage('Mapping auditor berhasil disimpan.')
    await loadMapping()
  } catch (error: any) {
    mappingError.value = apiError(error, 'Mapping auditor gagal disimpan.')
  } finally {
    saving.value = false
  }
}

const deleteMapping = (mapping: any) => {
  confirm.require({
    group: 'mapping-auditor',
    header: 'Konfirmasi Hapus Mapping',
    message: `Mapping ${mapping.namapegawai} sebagai ${mapping.tugas} akan dihapus.`,
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Hapus Mapping',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-text',
    accept: () => removeMapping(mapping),
    reject: () => { },
  })
}

const removeMapping = async (mapping: any) => {
  try {
    await useApi().post('mutu/hapus-mapping-auditor-internal', { id: mapping.id })
    setMessage('Mapping auditor berhasil dihapus.')
    await loadMapping()
  } catch (error: any) {
    setMessage(apiError(error, 'Mapping auditor gagal dihapus.'), 'error')
  }
}

const openCopyModal = () => {
  copyError.value = ''
  copyForm.tahunSumber = selectedYear.value
  copyForm.tahunTujuan = selectedYear.value + 1
  copyModalOpen.value = true
}

const copyMapping = async () => {
  copyError.value = ''
  if (!copyForm.tahunSumber || !copyForm.tahunTujuan || copyForm.tahunSumber === copyForm.tahunTujuan) {
    copyError.value = 'Tahun sumber dan tujuan wajib diisi dan harus berbeda.'
    return
  }
  copying.value = true
  try {
    await useApi().post('mutu/salin-mapping-auditor-internal', { ...copyForm })
    selectedYear.value = Number(copyForm.tahunTujuan)
    copyModalOpen.value = false
    setMessage(`Mapping berhasil disalin ke tahun ${selectedYear.value}.`)
    await loadMapping()
  } catch (error: any) {
    copyError.value = apiError(error, 'Mapping auditor gagal disalin.')
  } finally {
    copying.value = false
  }
}

const goToTemuan = () => router.push({ name: 'module-mutu-temuan-ketidaksesuaian' })
const printPakta = (mapping: any) => H.printBlade(
  `mutu/cetak-pakta-integritas-auditor?pdf=true&tahun=${selectedYear.value}&pegawaifk=${mapping.pegawaifk}`
)
const viewPenilaianAuditor = () => H.printBlade(
  `mutu/view-dokumen-audit-internal?kode=penilaian-auditor&tahun=${selectedYear.value}`
)
const downloadPenilaianAuditor = () => H.printBlade(
  `mutu/view-dokumen-audit-internal?kode=penilaian-auditor&download=true&tahun=${selectedYear.value}`
)

onMounted(loadMapping)
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';

.mapping-page {
  --mapping-primary: #165c75;
}

.mapping-hero-card {
  padding: 0;
  overflow: hidden;
  border-radius: 12px;
}

.mapping-hero-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  min-height: 0;
  padding: 10px 16px;
  background: var(--white);
  border: 1px solid var(--fade-grey-dark-3);
}

.mapping-hero-copy,
.mapping-hero-actions,
.mapping-year-control {
  display: flex;
  align-items: center;
  gap: 10px;
}

.mapping-hero-logo {
  width: auto;
  height: 42px;
  object-fit: contain;
}

.mapping-hero-copy h3 {
  margin: 0;
  color: var(--dark-text);
  font-size: 1.08rem;
  font-weight: 800;
}

.mapping-hero-copy p {
  margin-top: 3px;
  color: #111;
  font-size: 1rem;
  font-weight: 650;
}

.mapping-filter-card,
.my-role-card,
.lead-auditor-card,
.mapping-card {
  border-radius: 12px;
}

.mapping-filter-layout {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.mapping-label {
  display: block;
  margin-bottom: 6px;
  color: var(--muted-grey);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
}

.mapping-year-control .field {
  width: 170px;
  margin-bottom: 0;
}

.mapping-summary {
  display: grid;
  grid-template-columns: repeat(4, minmax(100px, 1fr));
  gap: 8px;
}

.mapping-summary > div {
  min-width: 110px;
  padding: 9px 12px;
  border: 1px solid var(--fade-grey-dark-3);
  border-left: 4px solid var(--mapping-primary);
  border-radius: 9px;
}

.mapping-summary b,
.mapping-summary span {
  display: block;
}

.mapping-summary b {
  color: var(--dark-text);
  font-size: 1.2rem;
}

.mapping-summary span {
  color: var(--muted-grey);
  font-size: .73rem;
}

.my-role-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 16px;
  border-left: 4px solid var(--mapping-primary);
}

.my-role-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 38px;
  width: 38px;
  height: 38px;
  color: var(--mapping-primary);
  background: rgba(22, 92, 117, .09);
  border-radius: 50%;
}

.my-role-copy > span {
  color: var(--dark-text);
  font-weight: 700;
}

.my-role-copy p {
  margin-top: 3px;
  color: var(--muted-grey);
  font-size: .82rem;
}

.my-role-list {
  display: flex;
  flex-wrap: wrap;
  gap: 5px;
  margin-top: 6px;
}

.lead-auditor-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 13px 16px;
  border-left: 4px solid #6b5bc7;
}

.lead-auditor-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  flex: 0 0 42px;
  width: 42px;
  height: 42px;
  color: #6b5bc7;
  background: rgba(107, 91, 199, .1);
  border-radius: 50%;
}

.lead-auditor-copy {
  flex: 1;
}

.lead-auditor-copy span,
.lead-auditor-copy b,
.lead-auditor-copy small {
  display: block;
}

.lead-auditor-copy span {
  color: var(--muted-grey);
  font-size: .72rem;
  font-weight: 700;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.lead-auditor-copy b {
  margin-top: 2px;
  color: var(--dark-text);
}

.lead-auditor-copy small {
  margin-top: 2px;
  color: var(--muted-grey);
}

.lead-auditor-actions {
  display: flex;
  gap: 6px;
}

.global-lead-note {
  height: 100%;
  padding: 10px 12px;
  color: #51459d;
  background: rgba(107, 91, 199, .08);
  border-radius: 8px;
  font-size: .8rem;
}

.mapping-grid {
  display: flex;
  flex-wrap: wrap;
}

.mapping-card {
  height: 100%;
  padding: 0;
  overflow: hidden;
}

.mapping-card-head {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 10px;
  padding: 13px 15px;
  background: rgba(125, 125, 125, .04);
  border-bottom: 1px solid var(--fade-grey-dark-3);
}

.mapping-card-head h4 {
  color: var(--dark-text);
  font-weight: 750;
}

.mapping-card-head p {
  margin-top: 2px;
  color: var(--muted-grey);
  font-size: .76rem;
}

.mapping-members {
  padding: 7px 14px 12px;
}

.mapping-member {
  display: grid;
  grid-template-columns: 30px minmax(140px, 1fr) auto max-content;
  align-items: center;
  gap: 8px;
  padding: 8px 0;
  border-bottom: 1px solid var(--fade-grey-dark-3);
}

.mapping-member:last-child {
  border-bottom: 0;
}

.member-order {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 27px;
  height: 27px;
  color: var(--mapping-primary);
  background: rgba(22, 92, 117, .09);
  border-radius: 50%;
  font-size: .75rem;
  font-weight: 700;
}

.member-info b,
.member-info span {
  display: block;
}

.member-info b {
  color: var(--dark-text);
  font-size: .86rem;
}

.member-info span {
  color: var(--muted-grey);
  font-size: .73rem;
}

.pakta-state {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  margin-top: 3px;
  font-size: .69rem;
  font-weight: 600;
}

.pakta-state.is-complete { color: #25885f; }
.pakta-state.is-pending { color: #c27a13; }
.pakta-state.is-not-required { color: var(--muted-grey); }

.member-actions {
  display: flex;
  gap: 5px;
}

.member-actions .button {
  width: 38px;
  min-width: 38px;
  height: 36px;
  padding: 0;
}

.mapping-empty,
.mapping-loading {
  padding: 30px 16px;
  color: var(--muted-grey);
  text-align: center;
  font-size: .82rem;
}

.copy-info {
  padding: 11px 13px;
  color: #164d63;
  background: rgba(46, 164, 190, .09);
  border-radius: 9px;
  font-size: .82rem;
}

:deep(.p-dropdown.is-fullwidth),
:deep(.p-autocomplete.is-input),
:deep(.p-autocomplete.is-input .p-inputtext) {
  width: 100%;
}

.is-dark {
  .mapping-hero-copy h3,
  .mapping-summary b,
  .my-role-copy > span,
  .lead-auditor-copy b,
  .mapping-card-head h4,
  .member-info b {
    color: var(--dark-dark-text);
  }
}

@media (max-width: 1100px) {
  .mapping-hero-content,
  .mapping-filter-layout {
    align-items: flex-start;
    flex-direction: column;
  }

  .mapping-hero-actions {
    width: 100%;
    flex-wrap: wrap;
  }
}

@media (max-width: 768px) {
  .mapping-hero-content {
    padding: 10px 12px;
  }

  .mapping-hero-logo {
    height: 38px;
  }

  .mapping-hero-copy h3 {
    font-size: 1rem;
  }

  .mapping-hero-copy p {
    font-size: .86rem;
  }

  .mapping-hero-actions .button {
    flex: 1 1 calc(50% - 5px);
  }

  .mapping-summary {
    width: 100%;
    grid-template-columns: repeat(2, 1fr);
  }

  .mapping-member {
    grid-template-columns: 30px 1fr auto;
  }

  .lead-auditor-card {
    align-items: flex-start;
    flex-wrap: wrap;
  }

  .lead-auditor-actions {
    width: 100%;
    justify-content: flex-end;
  }

  .member-actions {
    grid-column: 2 / -1;
  }
}
</style>
