<template>
  <div class="closed-page">
    <div class="column">
      <VCard class="closed-hero-card">
        <div class="closed-hero-content">
          <div class="closed-hero-copy">
            <img
              src="/@src/assets/illustrations/dashboards/personal/UMRO.png"
              alt="UMRO Laboratory"
              class="closed-hero-logo"
            />
            <div>
              <h3 class="closed-hero-title">CLOSED VERIFICATION AUDITOR</h3>
              <p class="closed-hero-subtitle">FMMO-163-14.4.3.b-88.7 · Penyelesaian Temuan Audit Internal</p>
            </div>
          </div>

          <div class="closed-hero-actions">
            <VButton outlined icon="feather:alert-triangle" @click="goToTemuan()">
              Temuan Ketidaksesuaian
            </VButton>
            <VButton outlined icon="feather:users" @click="goToMapping()">
              Mapping Auditor
            </VButton>
            <VButton outlined icon="feather:folder" @click="openDocumentLibrary()">
              Dokumen Audit Internal
            </VButton>
            <VButton color="warning" raised icon="feather:printer" :disabled="!canPrint"
              @click="cetakLaporan()">
              Cetak Semua Lingkup
            </VButton>
            <VButton color="primary" raised icon="feather:file-text" :disabled="!canPrint"
              @click="cetakLha()">
              Cetak LHA
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
                    <select v-model="selectedYear" @change="loadData()">
                      <option v-for="year in yearOptions" :key="year" :value="year">{{ year }}</option>
                    </select>
                  </div>
                </VControl>
              </VField>
              <VButton color="info" outlined icon="feather:refresh-ccw" :loading="loading" @click="loadData()">
                Muat Ulang
              </VButton>
            </div>
          </div>

          <div class="summary-cards">
            <div class="summary-card">
              <span class="summary-value">{{ summary.jumlahTemuan }}</span>
              <span class="summary-label">Temuan</span>
            </div>
            <div class="summary-card follow-up">
              <span class="summary-value">{{ summary.jumlahTindakLanjut }}</span>
              <span class="summary-label">Ditindaklanjuti</span>
            </div>
            <div class="summary-card verified">
              <span class="summary-value">{{ summary.jumlahTerverifikasi }}</span>
              <span class="summary-label">Diverifikasi</span>
            </div>
          </div>
        </div>
        <div class="lha-setting">
          <div class="lha-setting__copy">
            <i class="iconify" data-icon="feather:file-text"></i>
            <div>
              <b>Tanggal Penetapan Laporan Hasil Audit (LHA)</b>
              <span v-if="bolehMengubahTanggalLha">Dapat diubah oleh Anda sebagai Lead Auditor.</span>
              <span v-else>Hanya Lead Auditor yang dapat mengubah tanggal penetapan.</span>
            </div>
          </div>
          <div class="lha-setting__action">
            <input v-model="tanggalPenetapanLha" class="input date-input-clickable" type="date"
              :disabled="!bolehMengubahTanggalLha" @click="openDatePicker" />
            <VButton v-if="bolehMengubahTanggalLha" color="primary" outlined icon="feather:save"
              :loading="savingLhaDate" @click="saveTanggalPenetapanLha()">
              Simpan Tanggal LHA
            </VButton>
          </div>
        </div>
      </VCard>
    </div>

    <div class="column pt-0">
      <VCard class="role-card">
        <div class="role-icon"><i class="iconify" data-icon="feather:user-check"></i></div>
        <div class="role-copy">
          <span>Peran Anda pada Tahun {{ selectedYear }}</span>
          <div v-if="displayRoles.length" class="role-list">
            <VTag v-for="role in displayRoles" :key="role.displayKey" :color="roleColor(role.peran)" rounded>
              {{ role.tugas }} · {{ role.jenisaudit }}
            </VTag>
          </div>
          <p v-else>Anda belum termapping pada Audit Internal periode ini.</p>
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

    <div v-else-if="audits.length === 0" class="column">
      <VCard class="empty-card">
        <div class="empty-icon"><i class="iconify" data-icon="feather:check-circle"></i></div>
        <h3>Belum ada temuan yang perlu ditindaklanjuti</h3>
        <p>Temuan akan muncul otomatis sesuai jenis audit dan mapping Anda pada tahun {{ selectedYear }}.</p>
      </VCard>
    </div>

    <div v-else v-for="audit in audits" :key="audit.id" class="column">
      <VCard class="audit-card">
        <div class="audit-header">
          <div class="audit-heading">
            <div class="audit-icon"><i class="iconify" data-icon="feather:clipboard"></i></div>
            <div>
              <div class="audit-title-row">
                <h3>{{ audit.jenisaudit }}</h3>
                <VTag color="info" rounded>{{ formatDate(audit.tanggalaudit) }}</VTag>
                <VTag v-if="audit.bolehmengisitindaklanjut" color="warning" rounded>Auditee · Isi Tindak Lanjut</VTag>
                <VTag v-if="audit.bolehmengisistatus" color="primary" rounded>Auditor · Verifikasi Status</VTag>
                <VTag v-if="!audit.bolehmengisitindaklanjut && !audit.bolehmengisistatus" color="light" rounded>
                  Lihat Saja
                </VTag>
              </div>
              <p>{{ audit.lokasi }} · {{ audit.lingkup }}</p>
            </div>
          </div>

          <div class="audit-progress">
            <span><b>{{ audit.jumlahtindaklanjut }}</b>/{{ audit.jumlahtemuan }} tindak lanjut</span>
            <span><b>{{ audit.jumlahterverifikasi }}</b>/{{ audit.jumlahtemuan }} terverifikasi</span>
          </div>
        </div>

        <div class="participant-grid">
          <div>
            <span class="meta-label">Tim Audit</span>
            <div class="participant-list">
              <span v-for="person in audit.timAudit" :key="`auditor-${person.id}`" class="participant-chip">
                {{ person.nama }} <small>· {{ person.tugas }}</small>
              </span>
              <span v-if="!audit.timAudit.length" class="muted-text">Belum tersedia</span>
            </div>
          </div>
          <div>
            <span class="meta-label">Auditee</span>
            <div class="participant-list">
              <span v-for="person in audit.auditee" :key="`auditee-${person.id}`" class="participant-chip auditee">
                {{ person.nama }} <small>· {{ person.tugas }}</small>
              </span>
              <span v-if="!audit.auditee.length" class="muted-text">Belum tersedia</span>
            </div>
          </div>
        </div>

        <div class="table-wrap">
          <div class="status-legend" aria-label="Legenda status verifikasi">
            <span class="status-legend__title">Legenda:</span>
            <span class="status-legend__item">
              <i class="status-legend__swatch is-pending"></i>
              Belum diisi / belum diverifikasi
            </span>
            <span class="status-legend__item">
              <i class="status-legend__swatch is-compliant"></i>
              Memenuhi
            </span>
            <span class="status-legend__item">
              <i class="status-legend__swatch is-not-compliant"></i>
              Belum memenuhi
            </span>
          </div>
          <DataTable :value="audit.temuan" :rowClass="findingRowClass" class="p-datatable-sm closed-table"
            responsiveLayout="scroll" showGridlines>
            <Column header="No" style="width:55px">
              <template #body="slotProps">{{ slotProps.index + 1 }}</template>
            </Column>
            <Column header="Bagian" style="min-width:135px">
              <template #body="slotProps">
                <div>{{ slotProps.data.bagian }}</div>
                <VTag v-if="audit.kodejenisaudit === 'mutu' && slotProps.data.lokasimutu"
                  class="lokasi-mutu-tag" :color="lokasiMutuColor(slotProps.data.lokasimutu)" rounded>
                  {{ lokasiMutuLabel(slotProps.data.lokasimutu) }}
                </VTag>
              </template>
            </Column>
            <Column field="klausul" header="Klausul" style="min-width:90px" />
            <Column header="Kategori" style="min-width:105px">
              <template #body="slotProps">
                <VTag :color="categoryColor(slotProps.data.kategoritemuan)" rounded>
                  {{ categoryLabel(slotProps.data.kategoritemuan) }}
                </VTag>
              </template>
            </Column>
            <Column header="Uraian Ketidaksesuaian" style="min-width:300px">
              <template #body="slotProps"><div class="cell-copy">{{ slotProps.data.uraianketidaksesuaian }}</div></template>
            </Column>
            <Column field="auditor" header="Auditor" style="min-width:155px" />
            <Column field="namaauditee" header="Auditee" style="min-width:170px" />
            <Column header="Analisa Penyebab" style="min-width:220px">
              <template #body="slotProps">
                <div class="cell-copy">{{ slotProps.data.analisapenyebab || '-' }}</div>
                <VButton v-if="hasEvidence(slotProps.data, 'analisa')" class="evidence-button" color="info" outlined
                  icon="feather:external-link" @click="openEvidence(slotProps.data, 'analisa')">
                  {{ evidenceName(slotProps.data, 'analisa') }}
                </VButton>
              </template>
            </Column>
            <Column header="Tindakan Koreksi" style="min-width:220px">
              <template #body="slotProps">
                <div class="cell-copy">{{ slotProps.data.tindakankoreksi || '-' }}</div>
                <VButton v-if="hasEvidence(slotProps.data, 'koreksi')" class="evidence-button" color="info" outlined
                  icon="feather:external-link" @click="openEvidence(slotProps.data, 'koreksi')">
                  {{ evidenceName(slotProps.data, 'koreksi') }}
                </VButton>
              </template>
            </Column>
            <Column header="Tindakan Korektif" style="min-width:220px">
              <template #body="slotProps">
                <div class="cell-copy">{{ slotProps.data.tindakankorektif || '-' }}</div>
                <VButton v-if="hasEvidence(slotProps.data, 'korektif')" class="evidence-button" color="info" outlined
                  icon="feather:external-link" @click="openEvidence(slotProps.data, 'korektif')">
                  {{ evidenceName(slotProps.data, 'korektif') }}
                </VButton>
              </template>
            </Column>
            <Column header="Rencana Penyelesaian" style="min-width:145px">
              <template #body="slotProps">{{ formatDate(slotProps.data.rencanapenyelesaian) }}</template>
            </Column>
            <Column header="Status" style="min-width:145px">
              <template #body="slotProps">
                <VTag v-if="slotProps.data.statusverifikasi" :color="statusColor(slotProps.data.statusverifikasi)" rounded>
                  {{ slotProps.data.statusverifikasi }}
                </VTag>
                <span v-else class="muted-text">Belum diverifikasi</span>
              </template>
            </Column>
            <Column header="Catatan Verifikasi" style="min-width:210px">
              <template #body="slotProps">
                <div class="cell-copy">{{ slotProps.data.catatanverifikasi || '-' }}</div>
              </template>
            </Column>
            <Column v-if="audit.bolehmengisitindaklanjut || audit.bolehmengisistatus" header="Aksi" style="min-width:185px">
              <template #body="slotProps">
                <div class="row-actions">
                  <VButton v-if="audit.bolehmengisitindaklanjut" color="warning" outlined icon="feather:edit-2"
                    title="Isi tindak lanjut oleh Auditee" @click="openFollowUp(audit, slotProps.data)" />
                  <VButton v-if="audit.bolehmengisistatus" color="primary" outlined icon="feather:check-circle"
                    title="Verifikasi status oleh Auditor" :disabled="!slotProps.data.tindaklanjutlengkap"
                    @click="openStatus(audit, slotProps.data)" />
                </div>
              </template>
            </Column>
          </DataTable>
        </div>
      </VCard>
    </div>

    <VModal :open="followUpModalOpen" title="Tindak Lanjut Closed Verification oleh Auditee"
      size="large" actions="right" @close="followUpModalOpen = false">
      <template #content>
        <div v-if="formError" class="notification is-danger is-light py-3">{{ formError }}</div>
        <div class="selected-finding">
          <span>{{ selectedAudit?.jenisaudit || '-' }}</span>
          <b>{{ selectedFinding?.bagian }} · Klausul {{ selectedFinding?.klausul }}</b>
          <VTag v-if="selectedAudit?.kodejenisaudit === 'mutu' && selectedFinding?.lokasimutu"
            class="lokasi-mutu-tag" :color="lokasiMutuColor(selectedFinding.lokasimutu)" rounded>
            {{ lokasiMutuLabel(selectedFinding.lokasimutu) }}
          </VTag>
          <p>{{ selectedFinding?.uraianketidaksesuaian }}</p>
          <VButton class="mt-3" outlined icon="feather:folder"
            @click="openDocumentLibrary(selectedFinding?.folderdokumenauditfk)">
            Buka Folder Temuan
          </VButton>
        </div>
        <div class="columns is-multiline">
          <div class="column is-12">
            <div class="followup-field-card">
              <VField label="Analisa Penyebab"><VControl>
                <textarea v-model="followUpForm.analisapenyebab" class="textarea" rows="4"
                  placeholder="Tuliskan analisa akar penyebab temuan..."></textarea>
              </VControl></VField>
              <div class="evidence-inputs">
                <VField label="Tautan bukti Analisa Penyebab">
                  <VControl icon="feather:link">
                    <VInput v-model="followUpForm.analisatautandokumen" type="url" placeholder="Tempel tautan salinan..."
                      @input="useManualDocumentLink('analisa')" />
                  </VControl>
                </VField>
                <VField label="Atau sambungkan langsung">
                  <Dropdown v-model="selectedDocuments.analisa" :options="documentOptions" optionLabel="path"
                    placeholder="Pilih file atau folder" class="is-input is-fullwidth" appendTo="body" filter
                    :loading="documentsLoading" @show="loadDocumentOptions"
                    @change="useDirectDocument('analisa', $event)" />
                </VField>
              </div>
            </div>
          </div>
          <div class="column is-6">
            <div class="followup-field-card">
              <VField label="Tindakan Koreksi"><VControl>
                <textarea v-model="followUpForm.tindakankoreksi" class="textarea" rows="4"
                  placeholder="Tuliskan tindakan koreksi yang dilakukan..."></textarea>
              </VControl></VField>
              <VField label="Tautan bukti Tindakan Koreksi">
                <VControl icon="feather:link">
                  <VInput v-model="followUpForm.koreksitautandokumen" type="url" placeholder="Tempel tautan salinan..."
                    @input="useManualDocumentLink('koreksi')" />
                </VControl>
              </VField>
              <VField label="Atau sambungkan langsung">
                <Dropdown v-model="selectedDocuments.koreksi" :options="documentOptions" optionLabel="path"
                  placeholder="Pilih file atau folder" class="is-input is-fullwidth" appendTo="body" filter
                  :loading="documentsLoading" @show="loadDocumentOptions"
                  @change="useDirectDocument('koreksi', $event)" />
              </VField>
            </div>
          </div>
          <div class="column is-6">
            <div class="followup-field-card">
              <VField label="Tindakan Korektif"><VControl>
                <textarea v-model="followUpForm.tindakankorektif" class="textarea" rows="4"
                  placeholder="Tuliskan tindakan korektif untuk mencegah pengulangan..."></textarea>
              </VControl></VField>
              <VField label="Tautan bukti Tindakan Korektif">
                <VControl icon="feather:link">
                  <VInput v-model="followUpForm.korektiftautandokumen" type="url" placeholder="Tempel tautan salinan..."
                    @input="useManualDocumentLink('korektif')" />
                </VControl>
              </VField>
              <VField label="Atau sambungkan langsung">
                <Dropdown v-model="selectedDocuments.korektif" :options="documentOptions" optionLabel="path"
                  placeholder="Pilih file atau folder" class="is-input is-fullwidth" appendTo="body" filter
                  :loading="documentsLoading" @show="loadDocumentOptions"
                  @change="useDirectDocument('korektif', $event)" />
              </VField>
            </div>
          </div>
          <div class="column is-6 date-field-wrap">
            <VField label="Rencana Penyelesaian"><VControl icon="feather:calendar">
              <input v-model="followUpForm.rencanapenyelesaian" class="input date-input-clickable" type="date"
                @click="openDatePicker" />
            </VControl></VField>
          </div>
          <div class="column is-12">
            <div class="form-note">
              Isian dapat disimpan secara bertahap. Status baru dapat diverifikasi oleh Auditor atau Lead Auditor setelah tindak lanjut lengkap.
            </div>
          </div>
        </div>
      </template>
      <template #action>
        <VButton color="primary" raised icon="feather:save" :loading="saving" @click="saveFollowUp()">
          Simpan Tindak Lanjut
        </VButton>
      </template>
    </VModal>

    <VModal :open="statusModalOpen" title="Verifikasi Status oleh Auditor"
      size="large" actions="right" @close="statusModalOpen = false">
      <template #content>
        <div v-if="formError" class="notification is-danger is-light py-3">{{ formError }}</div>
        <div class="verification-review">
          <div><span>Temuan</span><p>{{ selectedFinding?.uraianketidaksesuaian || '-' }}</p></div>
          <div><span>Analisa Penyebab</span><p>{{ selectedFinding?.analisapenyebab || '-' }}</p></div>
          <div><span>Tindakan Koreksi</span><p>{{ selectedFinding?.tindakankoreksi || '-' }}</p></div>
          <div><span>Tindakan Korektif</span><p>{{ selectedFinding?.tindakankorektif || '-' }}</p></div>
          <div><span>Rencana Penyelesaian</span><p>{{ formatDate(selectedFinding?.rencanapenyelesaian) }}</p></div>
        </div>
        <div class="columns is-multiline mt-2">
          <div class="column is-6">
            <VField label="Status Verifikasi *">
              <Dropdown v-model="statusForm.statusverifikasi" :options="statusOptions"
                placeholder="Pilih status" class="is-input is-fullwidth" appendTo="body" />
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Catatan Verifikasi (Opsional)">
              <VControl>
                <textarea v-model="statusForm.catatanverifikasi" class="textarea" rows="3"
                  maxlength="2000" placeholder="Tuliskan catatan untuk auditee bila diperlukan..."></textarea>
              </VControl>
            </VField>
          </div>
        </div>
      </template>
      <template #action>
        <VButton color="primary" raised icon="feather:check-circle" :loading="saving" @click="saveStatus()">
          Simpan Status
        </VButton>
      </template>
    </VModal>
  </div>
</template>

<script setup lang="ts">
import { computed, onMounted, reactive, ref } from 'vue'
import { useHead } from '@vueuse/head'
import moment from 'moment'
import Column from 'primevue/column'
import DataTable from 'primevue/datatable'
import Dropdown from 'primevue/dropdown'
import { useRouter } from 'vue-router'
import { useApi } from '/@src/composable/useApi'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'Closed Verification Auditor - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const router = useRouter()
const currentYear = new Date().getFullYear()
const selectedYear = ref(currentYear)
const yearOptions = Array.from({ length: 16 }, (_, index) => currentYear + 5 - index)
const loading = ref(false)
const saving = ref(false)
const savingLhaDate = ref(false)
const audits: any = ref([])
const myAuditRoles: any = ref([])
const statusOptions: any = ref(['Belum Memenuhi', 'Memenuhi'])
const followUpModalOpen = ref(false)
const statusModalOpen = ref(false)
const selectedAudit: any = ref(null)
const selectedFinding: any = ref(null)
const formError = ref('')
const documentOptions: any = ref([])
type EvidenceField = 'analisa' | 'koreksi' | 'korektif'
const selectedDocuments = reactive<Record<EvidenceField, any>>({
  analisa: null,
  koreksi: null,
  korektif: null,
})
const documentsLoading = ref(false)
const tanggalPenetapanLha = ref('')
const bolehMengubahTanggalLha = ref(false)

const summary = reactive({
  jumlahAudit: 0,
  jumlahTemuan: 0,
  jumlahTindakLanjut: 0,
  jumlahTerverifikasi: 0,
})

const message = reactive({
  type: 'success',
  text: '',
})

const followUpForm = reactive({
  analisapenyebab: '',
  tindakankoreksi: '',
  tindakankorektif: '',
  rencanapenyelesaian: '',
  analisadokumenauditfk: null as number | null,
  analisatautandokumen: '',
  koreksidokumenauditfk: null as number | null,
  koreksitautandokumen: '',
  korektifdokumenauditfk: null as number | null,
  korektiftautandokumen: '',
})

const statusForm = reactive({
  statusverifikasi: '',
  catatanverifikasi: '',
})

const canPrint = computed(() => summary.jumlahTemuan > 0)

const displayRoles = computed(() => {
  const globalRoles = new Set<string>()
  return myAuditRoles.value.reduce((result: any[], role: any) => {
    if (role.peran === 'lead_auditor' || role.peran === 'manager') {
      if (globalRoles.has(role.peran)) return result
      globalRoles.add(role.peran)
      result.push({ ...role, displayKey: `${role.peran}-${role.tahun}`, jenisaudit: 'Seluruh Jenis Audit' })
      return result
    }
    result.push({ ...role, displayKey: `mapping-${role.id}` })
    return result
  }, [])
})

const setMessage = (text: string, type: 'success' | 'error' = 'success') => {
  message.text = text
  message.type = type
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const apiError = (error: any, fallback: string) => error?.response?._data?.response?.message
  || error?.response?._data?.message
  || error?.response?.data?.response?.message
  || error?.response?.data?.message
  || error?.message
  || fallback

const formatDate = (date?: string) => date ? moment(date).format('DD MMMM YYYY') : '-'
const categoryColor = (category: number): 'danger' | 'warning' | 'info' => (
  Number(category) === 1 ? 'danger' : Number(category) === 2 ? 'warning' : 'info'
)
const categoryLabel = (category: number) => Number(category) === 1 ? 'Major' : Number(category) === 2 ? 'Minor' : 'Observasi'
const lokasiMutuLabel = (lokasi: number) => Number(lokasi) === 1 ? 'Mutu Jakarta' : 'Mutu Gresik'
const lokasiMutuColor = (lokasi: number): 'info' | 'success' => Number(lokasi) === 1 ? 'info' : 'success'
const statusColor = (status: string): 'success' | 'danger' => status === 'Memenuhi' ? 'success' : 'danger'
const findingRowClass = (finding: any) => {
  if (finding.statusverifikasi === 'Memenuhi') return 'finding-row--compliant'
  if (finding.statusverifikasi === 'Belum Memenuhi') return 'finding-row--not-compliant'
  return 'finding-row--pending'
}
const roleColor = (role: string): 'primary' | 'info' | 'success' | 'warning' => {
  if (role === 'lead_auditor') return 'primary'
  if (role === 'manager' || role === 'auditor_observer') return 'info'
  if (role === 'auditee') return 'warning'
  return 'success'
}

const loadData = async () => {
  loading.value = true
  try {
    const res: any = await useApi().get(`mutu/closed-verification-auditor?tahun=${selectedYear.value}`)
    audits.value = (res?.audits || []).map((audit: any) => ({
      ...audit,
      timAudit: audit.timAudit || [],
      auditee: audit.auditee || [],
      temuan: audit.temuan || [],
    }))
    myAuditRoles.value = res?.mappingPengguna || []
    statusOptions.value = res?.statusOptions || statusOptions.value
    tanggalPenetapanLha.value = res?.lha?.tanggalpenetapan || ''
    bolehMengubahTanggalLha.value = Boolean(res?.lha?.bolehmengubahtanggal)
    Object.assign(summary, res?.ringkasan || {
      jumlahAudit: 0,
      jumlahTemuan: 0,
      jumlahTindakLanjut: 0,
      jumlahTerverifikasi: 0,
    })
  } catch (error: any) {
    audits.value = []
    setMessage(apiError(error, 'Data Closed Verification gagal dimuat.'), 'error')
  } finally {
    loading.value = false
  }
}

const loadDocumentOptions = async () => {
  if (!selectedFinding.value?.id || documentsLoading.value) return
  const temuanId = Number(selectedFinding.value.id)
  documentsLoading.value = true
  try {
    await useApi().post('/udr/new-drive-sync-audit-internal', {})
    const res: any = await useApi().get(
      `mutu/pilihan-dokumen-audit-internal?temuanfk=${temuanId}`,
    )
    if (Number(selectedFinding.value?.id) !== temuanId) return
    documentOptions.value = res?.documents || []
    ;(['analisa', 'koreksi', 'korektif'] as EvidenceField[]).forEach((field) => {
      const documentId = followUpForm[`${field}dokumenauditfk`]
      selectedDocuments[field] = documentOptions.value.find(
        (item: any) => Number(item.id) === Number(documentId),
      ) || null
    })
    if (res?.folderId) selectedFinding.value.folderdokumenauditfk = Number(res.folderId)
  } catch (error: any) {
    formError.value = apiError(error, 'Pilihan Dokumen Audit Internal gagal dimuat.')
  } finally {
    documentsLoading.value = false
  }
}

const openFollowUp = (audit: any, finding: any) => {
  selectedAudit.value = audit
  selectedFinding.value = finding
  formError.value = ''
  followUpForm.analisapenyebab = finding.analisapenyebab || ''
  followUpForm.tindakankoreksi = finding.tindakankoreksi || ''
  followUpForm.tindakankorektif = finding.tindakankorektif || ''
  followUpForm.rencanapenyelesaian = finding.rencanapenyelesaian
    ? moment(finding.rencanapenyelesaian).format('YYYY-MM-DD')
    : ''
  followUpForm.analisadokumenauditfk = finding.analisadokumenauditfk ? Number(finding.analisadokumenauditfk) : null
  followUpForm.analisatautandokumen = finding.analisatautandokumen || ''
  followUpForm.koreksidokumenauditfk = finding.koreksidokumenauditfk ? Number(finding.koreksidokumenauditfk) : null
  followUpForm.koreksitautandokumen = finding.koreksitautandokumen || ''
  followUpForm.korektifdokumenauditfk = finding.korektifdokumenauditfk ? Number(finding.korektifdokumenauditfk) : null
  followUpForm.korektiftautandokumen = finding.korektiftautandokumen || ''
  selectedDocuments.analisa = null
  selectedDocuments.koreksi = null
  selectedDocuments.korektif = null
  documentOptions.value = []
  followUpModalOpen.value = true
}

const useManualDocumentLink = (field: EvidenceField = 'analisa') => {
  const linkKey = `${field}tautandokumen` as keyof typeof followUpForm
  const documentKey = `${field}dokumenauditfk` as keyof typeof followUpForm
  if (!String(followUpForm[linkKey] || '').trim()) return
  ;(followUpForm as any)[documentKey] = null
  selectedDocuments[field] = null
}

const useDirectDocument = (field: EvidenceField = 'analisa', event?: any) => {
  const document = event?.value || selectedDocuments[field]
  const documentKey = `${field}dokumenauditfk` as keyof typeof followUpForm
  const linkKey = `${field}tautandokumen` as keyof typeof followUpForm
  ;(followUpForm as any)[documentKey] = document?.id ? Number(document.id) : null
  if ((followUpForm as any)[documentKey]) (followUpForm as any)[linkKey] = ''
}

const openDatePicker = (event: MouseEvent) => {
  const input = event.currentTarget as HTMLInputElement & { showPicker?: () => void }
  input.focus()
  try {
    input.showPicker?.()
  } catch {
    // Browser lama tetap menggunakan perilaku bawaan input tanggal.
  }
}

const saveFollowUp = async () => {
  formError.value = ''

  saving.value = true
  try {
    await useApi().post('mutu/save-closed-verification-auditor', {
      mode: 'tindak_lanjut',
      temuanfk: selectedFinding.value.id,
      ...followUpForm,
    })
    followUpModalOpen.value = false
    setMessage('Tindak lanjut auditee berhasil disimpan. Status menunggu verifikasi Auditor.')
    await loadData()
  } catch (error: any) {
    formError.value = apiError(error, 'Tindak lanjut gagal disimpan.')
  } finally {
    saving.value = false
  }
}

const openStatus = (audit: any, finding: any) => {
  if (!finding.tindaklanjutlengkap) return
  selectedAudit.value = audit
  selectedFinding.value = finding
  formError.value = ''
  statusForm.statusverifikasi = finding.statusverifikasi || ''
  statusForm.catatanverifikasi = finding.catatanverifikasi || ''
  statusModalOpen.value = true
}

const saveStatus = async () => {
  formError.value = ''
  if (!statusForm.statusverifikasi) {
    formError.value = 'Status verifikasi wajib dipilih.'
    return
  }

  saving.value = true
  try {
    await useApi().post('mutu/save-closed-verification-auditor', {
      mode: 'status',
      temuanfk: selectedFinding.value.id,
      statusverifikasi: statusForm.statusverifikasi,
      catatanverifikasi: statusForm.catatanverifikasi,
    })
    statusModalOpen.value = false
    setMessage('Status Closed Verification berhasil diverifikasi.')
    await loadData()
  } catch (error: any) {
    formError.value = apiError(error, 'Status verifikasi gagal disimpan.')
  } finally {
    saving.value = false
  }
}

const saveTanggalPenetapanLha = async () => {
  if (!tanggalPenetapanLha.value) {
    setMessage('Tanggal penetapan LHA wajib dipilih.', 'error')
    return
  }

  savingLhaDate.value = true
  try {
    await useApi().post('mutu/save-tanggal-penetapan-lha', {
      tahun: selectedYear.value,
      tanggalpenetapan: tanggalPenetapanLha.value,
    })
    setMessage('Tanggal penetapan LHA berhasil disimpan.')
    await loadData()
  } catch (error: any) {
    setMessage(apiError(error, 'Tanggal penetapan LHA gagal disimpan.'), 'error')
  } finally {
    savingLhaDate.value = false
  }
}

const cetakLaporan = () => H.printBlade(
  `mutu/cetak-closed-verification-auditor?pdf=true&tahun=${selectedYear.value}`,
)
const cetakLha = () => H.printBlade(
  `mutu/cetak-laporan-hasil-audit?pdf=true&tahun=${selectedYear.value}`,
)
const goToTemuan = () => router.push({ name: 'module-mutu-temuan-ketidaksesuaian' })
const goToMapping = () => router.push({ name: 'module-mutu-mapping-auditor' })
const openDocumentLibrary = (folderId?: number | null) => {
  const routeData = router.resolve({
    name: 'module-mutu-dokumen-audit-internal',
    query: folderId ? {
      openUdrId: String(folderId),
      targetType: 'folder',
      jenisudr: 'Audit Internal',
    } : {},
  })
  window.open(routeData.href, '_blank', 'noopener,noreferrer')
}

const hasEvidence = (finding: any, field: EvidenceField) => (
  Boolean(finding?.[`${field}dokumenauditfk`] || finding?.[`${field}tautandokumen`])
)

const evidenceName = (finding: any, field: EvidenceField) => (
  finding?.[`${field}namadokumen`] || 'Buka dokumen bukti'
)

const openEvidence = (finding: any, field: EvidenceField) => {
  const documentId = finding?.[`${field}dokumenauditfk`]
  if (documentId) {
    const routeData = router.resolve({
      name: 'module-mutu-dokumen-audit-internal',
      query: {
        openUdrId: String(documentId),
        targetType: finding?.[`${field}tipedokumen`] === 'folder' ? 'folder' : 'file',
        jenisudr: 'Audit Internal',
      },
    })
    window.open(routeData.href, '_blank', 'noopener,noreferrer')
    return
  }
  const link = finding?.[`${field}tautandokumen`]
  if (link) window.open(link, '_blank', 'noopener,noreferrer')
}

onMounted(loadData)
</script>

<style lang="scss" scoped>
@import '/@src/scss/abstracts/all';

.closed-page {
  --closed-primary: #176078;
  --closed-primary-soft: rgba(23, 96, 120, .09);
}

.closed-hero-card {
  padding: 0;
  overflow: hidden;
  border-radius: 12px;
}

.closed-hero-content,
.filter-layout,
.audit-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.closed-hero-content {
  min-height: 0;
  padding: 10px 16px;
  background: var(--white);
  border: 1px solid var(--fade-grey-dark-3);
}

.closed-hero-copy,
.closed-hero-actions,
.year-control,
.row-actions {
  display: flex;
  align-items: center;
  gap: .65rem;
}

.closed-hero-logo {
  width: auto;
  height: 42px;
  object-fit: contain;
}

.closed-hero-title {
  margin: 0;
  color: var(--dark-text);
  font-size: 1.08rem;
  font-weight: 800;
  letter-spacing: .35px;
}

.closed-hero-subtitle {
  margin-top: 3px;
  color: #111;
  font-size: .92rem;
  font-weight: 600;
}

.filter-card,
.audit-card {
  border-radius: 14px;
}

.filter-layout {
  padding: 16px 20px;
}

.eyebrow,
.meta-label,
.role-copy > span {
  color: var(--muted-grey);
  font-size: .72rem;
  font-weight: 750;
  letter-spacing: .06em;
  text-transform: uppercase;
}

.year-control {
  margin-top: 8px;
}

.summary-cards {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-end;
  gap: 10px;
}

.summary-card {
  display: flex;
  flex-direction: column;
  min-width: 128px;
  padding: 10px 14px;
  border: 1px solid var(--fade-grey-dark-3);
  border-left: 4px solid var(--closed-primary);
  border-radius: 11px;
}

.summary-card.follow-up { border-left-color: #f5a623; }
.summary-card.verified { border-left-color: #38a169; }
.summary-value { color: var(--dark-text); font-size: 1.2rem; font-weight: 800; }
.summary-label { color: var(--muted-grey); font-size: .76rem; }

.lha-setting {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  padding-top: 14px;
  margin-top: 14px;
  border-top: 1px solid var(--fade-grey-dark-3);
}

.lha-setting__copy,
.lha-setting__action {
  display: flex;
  align-items: center;
  gap: 10px;
}

.lha-setting__copy > i {
  flex: 0 0 auto;
  color: var(--closed-primary);
  font-size: 22px;
}

.lha-setting__copy b,
.lha-setting__copy span {
  display: block;
}

.lha-setting__copy b {
  color: var(--dark-text);
  font-size: .84rem;
}

.lha-setting__copy span {
  margin-top: 2px;
  color: var(--muted-grey);
  font-size: .74rem;
}

.lha-setting__action .input {
  width: 175px;
}

.role-card {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 16px;
  border-left: 4px solid var(--closed-primary);
  border-radius: 10px;
}

.role-icon,
.audit-icon,
.empty-icon {
  display: inline-flex;
  flex: 0 0 40px;
  align-items: center;
  justify-content: center;
  width: 40px;
  height: 40px;
  color: var(--closed-primary);
  background: var(--closed-primary-soft);
  border-radius: 50%;
  font-size: 19px;
}

.role-copy { min-width: 0; }
.role-copy p { margin-top: 4px; color: var(--muted-grey); font-size: .82rem; }
.role-list,
.participant-list {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 5px;
}

.audit-card { overflow: hidden; }
.audit-header { padding: 16px 20px; }
.audit-heading { display: flex; align-items: flex-start; gap: 12px; }
.audit-title-row { display: flex; flex-wrap: wrap; align-items: center; gap: 7px; }
.audit-title-row h3 { margin: 0; color: var(--dark-text); font-size: 1.04rem; font-weight: 750; }
.audit-heading p { margin-top: 3px; color: var(--muted-grey); font-size: .82rem; }
.audit-progress { display: flex; flex-direction: column; align-items: flex-end; gap: 4px; color: var(--muted-grey); font-size: .8rem; }
.audit-progress b { color: var(--dark-text); }

.participant-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
  padding: 12px 20px;
  border-top: 1px solid var(--fade-grey-dark-3);
  background: var(--fade-grey-light-7);
}

.participant-chip {
  display: inline-flex;
  padding: 5px 9px;
  color: var(--closed-primary);
  background: var(--closed-primary-soft);
  border-radius: 999px;
  font-size: .76rem;
}

.participant-chip.auditee { color: #26734f; background: rgba(38, 155, 103, .1); }
.participant-chip small { margin-left: 3px; opacity: .72; }
.muted-text { color: var(--muted-grey); font-size: .78rem; }
.table-wrap { overflow-x: auto; padding: 16px 20px 20px; }
.closed-table { min-width: 2460px; }
.cell-copy { white-space: pre-line; line-height: 1.45; }
.lokasi-mutu-tag { margin-top: 6px; font-size: .7rem; font-weight: 700; }
.evidence-button { margin-top: 9px; max-width: 100%; }
.row-actions { justify-content: center; }

.status-legend {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px 18px;
  margin-bottom: 12px;
  color: var(--muted-grey);
  font-size: .78rem;
}

.status-legend__title {
  color: var(--dark-text);
  font-weight: 700;
}

.status-legend__item {
  display: inline-flex;
  align-items: center;
  gap: 7px;
}

.status-legend__swatch {
  width: 15px;
  height: 15px;
  border: 1px solid transparent;
  border-radius: 4px;
}

.status-legend__swatch.is-pending {
  background: rgba(255, 193, 7, .16);
  border-color: rgba(210, 155, 0, .28);
}

.status-legend__swatch.is-compliant {
  background: rgba(72, 199, 142, .15);
  border-color: rgba(35, 150, 99, .26);
}

.status-legend__swatch.is-not-compliant {
  background: rgba(241, 70, 104, .13);
  border-color: rgba(200, 45, 75, .24);
}

:deep(.closed-table .p-datatable-tbody > tr.finding-row--pending > td) {
  background: rgba(255, 193, 7, .09);
}

:deep(.closed-table .p-datatable-tbody > tr.finding-row--compliant > td) {
  background: rgba(72, 199, 142, .09);
}

:deep(.closed-table .p-datatable-tbody > tr.finding-row--not-compliant > td) {
  background: rgba(241, 70, 104, .075);
}

.empty-card {
  padding: 45px 24px;
  text-align: center;
  border-radius: 14px;
}

.empty-icon { margin: 0 auto 13px; }
.empty-card h3 { color: var(--dark-text); font-size: 1rem; }
.empty-card p { margin-top: 5px; color: var(--muted-grey); }

.selected-finding {
  margin-bottom: 16px;
  padding: 13px 15px;
  border-left: 4px solid var(--closed-primary);
  background: var(--closed-primary-soft);
  border-radius: 8px;
}

.selected-finding span,
.verification-review span {
  color: var(--muted-grey);
  font-size: .73rem;
  font-weight: 700;
  text-transform: uppercase;
}

.selected-finding b { display: block; margin-top: 3px; color: var(--dark-text); }
.selected-finding p { margin-top: 7px; white-space: pre-line; }
.form-note {
  padding: 11px 13px;
  color: #6b4300;
  background: #fff0bf;
  border: 1px solid #e7bd52;
  border-radius: 7px;
  font-size: .83rem;
  font-weight: 600;
}

.followup-field-card {
  height: 100%;
  padding: 14px;
  border: 1px solid rgba(23, 96, 120, .32);
  background: rgba(224, 244, 250, .58);
  border-radius: 10px;
}

.followup-field-card :deep(.label),
.date-field-wrap :deep(.label) {
  color: #33486d;
  font-weight: 700;
}

.followup-field-card .textarea,
.followup-field-card :deep(.input),
.date-input-clickable {
  color: #1f3152;
  border-color: #b8c7d6;
}

.followup-field-card .textarea::placeholder,
.followup-field-card :deep(.input::placeholder) {
  color: #7b8799;
  opacity: 1;
}

.date-input-clickable {
  cursor: pointer;
  font-weight: 600;
}

.evidence-inputs {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
}

.verification-review {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 10px;
}

.verification-review > div {
  padding: 11px 13px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 8px;
}

.verification-review > div:first-child { grid-column: 1 / -1; }
.verification-review p { margin-top: 4px; color: var(--dark-text); white-space: pre-line; }

.is-dark {
  .closed-hero-content,
  .filter-card,
  .audit-card,
  .role-card,
  .empty-card {
    background: var(--dark-sidebar-light-6);
    border-color: var(--dark-sidebar-light-12);
  }

  .closed-hero-subtitle { color: var(--light-text); }
  .lha-setting { border-color: var(--dark-sidebar-light-12); }
  .lha-setting__copy b { color: var(--dark-dark-text); }
  .participant-grid { background: var(--dark-sidebar-light-4); border-color: var(--dark-sidebar-light-12); }
  .verification-review > div { border-color: var(--dark-sidebar-light-12); }
  .followup-field-card { border-color: var(--dark-sidebar-light-12); }
}

@media (max-width: 1200px) {
  .closed-hero-content,
  .filter-layout,
  .audit-header {
    align-items: flex-start;
    flex-direction: column;
  }

  .closed-hero-actions {
    width: 100%;
    flex-wrap: wrap;
  }

  .lha-setting {
    align-items: flex-start;
    flex-direction: column;
  }

  .audit-progress { align-items: flex-start; }
}

@media (max-width: 768px) {
  .closed-hero-content { padding: 10px 12px; }
  .closed-hero-copy { align-items: flex-start; }
  .closed-hero-actions .button { flex: 1 1 calc(50% - .5rem); }
  .lha-setting__action { width: 100%; flex-wrap: wrap; }
  .lha-setting__action .input { flex: 1 1 160px; }
  .participant-grid,
  .verification-review,
  .evidence-inputs { grid-template-columns: 1fr; }
  .verification-review > div:first-child { grid-column: auto; }
  .summary-cards { justify-content: flex-start; }
  .summary-card { flex: 1 1 110px; }
}
</style>
