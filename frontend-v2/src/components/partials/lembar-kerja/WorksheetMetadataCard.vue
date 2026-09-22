<template>
  <section class="metadata-card">
    <div class="metadata-heading">
      <div>
        <span class="metadata-eyebrow">Informasi Kalibrasi</span>
        <h3>Preview Data Lembar Kerja</h3>
      </div>
      <VButton v-if="editable" :icon="editing ? 'feather:x' : 'feather:edit-2'" :color="editing ? undefined : 'info'"
        outlined @click="editing ? cancelEdit() : startEdit()">
        {{ editing ? 'Batal' : 'Edit Data' }}
      </VButton>
    </div>

    <div v-if="!editing" class="metadata-grid">
      <InfoItem icon="feather:calendar" label="Tanggal Kalibrasi" :value="formattedDate" />
      <InfoItem icon="feather:map-pin" label="Tempat Kalibrasi" :value="placeLabel" />
      <InfoItem icon="feather:thermometer" label="Suhu" :value="withUnit(item?.suhu, '°C')" />
      <InfoItem icon="feather:droplet" label="Kelembaban Relatif" :value="withUnit(item?.kelembabanRelatif, '% RH')" />
      <InfoItem v-if="item?.range" icon="feather:sliders" label="Range" :value="item.range" />
      <InfoItem v-if="item?.resolusi" icon="feather:zoom-in" label="Resolusi" :value="item.resolusi" />
    </div>

    <div v-if="!editing" class="metadata-details">
      <div class="detail-block">
        <span>Catatan/Notes</span>
        <p>{{ item?.notes || '-' }}</p>
      </div>
      <div class="detail-columns">
        <div class="detail-block">
          <span>Instruksi Kerja</span>
          <ol v-if="instructions.length" class="metadata-list">
            <li v-for="(instruction, index) in instructions" :key="`instruction-${index}`">{{ instruction }}</li>
          </ol>
          <p v-else>-</p>
        </div>
        <div class="detail-block">
          <span>Peralatan Standar</span>
          <ol v-if="standards.length" class="metadata-list">
            <li v-for="(standard, index) in standards" :key="`standard-${index}`">{{ standard }}</li>
          </ol>
          <p v-else>-</p>
        </div>
      </div>
      <div v-if="item?.gambarsuhu" class="environment-photo">
        <span>Foto Kondisi Lingkungan</span>
        <img :src="`/gambar-suhu/${item.gambarsuhu}`" alt="Foto kondisi lingkungan kalibrasi" />
      </div>
    </div>

    <form v-else class="metadata-editor" @submit.prevent="save">
      <div class="editor-grid">
        <VField label="Tanggal Kalibrasi">
          <VControl icon="feather:calendar">
            <VInput v-model="draft.tglkalibrasi" type="datetime-local" />
          </VControl>
        </VField>
        <VField label="Tempat Kalibrasi">
          <AutoComplete v-model="draft.tempatKalibrasi" :suggestions="roomOptions" optionLabel="label" field="label"
            dropdown :appendTo="'body'" placeholder="Ketik untuk mencari..." @complete="searchRooms" />
        </VField>
        <VField label="Suhu">
          <VControl>
            <VInput v-model="draft.suhu" placeholder="Contoh: 22,90 ± 0,00 °C" />
          </VControl>
        </VField>
        <VField label="Kelembaban Relatif">
          <VControl>
            <VInput v-model="draft.kelembabanRelatif" placeholder="Contoh: 51,63 ± 0,86 % RH" />
          </VControl>
        </VField>
      </div>

      <VField label="Catatan/Notes">
        <VControl>
          <VTextarea v-model="draft.notes" rows="4" />
        </VControl>
      </VField>

      <div class="editor-lists">
        <div>
          <div class="list-heading">
            <strong>Instruksi Kerja</strong>
            <VIconButton type="button" icon="feather:plus" circle outlined color="info" @click="addInstruction" />
          </div>
          <WorksheetMasterButton kind="ik" />
          <div v-for="(row, index) in draft.detailInstruksiKerja" :key="`ik-${index}`" class="editor-row">
            <WorksheetInstructionPicker v-model="row.daftarInstruksiKerja" />
            <VIconButton type="button" icon="feather:trash" circle outlined color="danger"
              @click="draft.detailInstruksiKerja.splice(index, 1)" />
          </div>
        </div>
        <div>
          <div class="list-heading">
            <strong>Peralatan Standar</strong>
            <VIconButton type="button" icon="feather:plus" circle outlined color="info" @click="addStandard" />
          </div>
          <WorksheetMasterButton kind="standar" />
          <div v-for="(row, index) in draft.detailPeralatanStandar" :key="`std-${index}`" class="editor-row">
            <AutoComplete v-model="row.daftaralatstandar" :suggestions="standardOptions" optionLabel="label"
              field="label" dropdown :appendTo="'body'" placeholder="Cari alat standar..." @complete="searchStandards" />
            <VIconButton type="button" icon="feather:trash" circle outlined color="danger"
              @click="draft.detailPeralatanStandar.splice(index, 1)" />
          </div>
        </div>
      </div>

      <VField label="Ganti Foto Kondisi Lingkungan (opsional)">
        <VControl>
          <input class="input" type="file" accept="image/jpeg,image/jpg,image/png" @change="selectImage" />
        </VControl>
      </VField>

      <div class="editor-actions">
        <VButton type="submit" icon="feather:save" color="info" :loading="saving">Simpan Perubahan</VButton>
      </div>
    </form>
  </section>
</template>

<script setup lang="ts">
import { computed, defineComponent, h, ref } from 'vue'
import AutoComplete from 'primevue/autocomplete'
import WorksheetInstructionPicker from '/@src/components/worksheet/WorksheetInstructionPicker.vue'
import WorksheetMasterButton from '/@src/components/worksheet/WorksheetMasterButton.vue'
import { useApi } from '/@src/composable/useApi'
import * as H from '/@src/utils/appHelper'

const props = withDefaults(defineProps<{
  item: Record<string, any>
  norec: string
  role: 'pelaksana' | 'penyelia' | 'manager' | 'asman'
  editable?: boolean
}>(), { editable: false })

const emit = defineEmits<{ (event: 'saved'): void }>()
const editing = ref(false)
const saving = ref(false)
const draft = ref<any>({})
const roomOptions = ref<any[]>([])
const standardOptions = ref<any[]>([])
const imageFile = ref<File | null>(null)

const InfoItem = defineComponent({
  props: { icon: String, label: String, value: String },
  setup(componentProps) {
    return () => h('div', { class: 'metadata-item' }, [
      h('span', { class: 'metadata-item-icon' }, [h('i', { class: 'iconify', 'data-icon': componentProps.icon })]),
      h('div', {}, [h('small', {}, componentProps.label), h('strong', {}, componentProps.value || '-')]),
    ])
  },
})

const textValue = (value: any): string => {
  if (typeof value === 'string' || typeof value === 'number') return String(value).trim()
  return ''
}

const optionLabel = (value: any): string => {
  if (!value) return ''
  const direct = textValue(value)
  if (direct) return direct
  return textValue(value.label)
    || textValue(value.namainstruksikerja)
    || textValue(value.namaruangan)
}

const standardLabel = (value: any): string => {
  if (!value) return ''
  const direct = textValue(value)
  if (direct) return direct
  const label = textValue(value.label)
  if (label) return label

  const identity = [value.namaalatstandar, value.namamerk, value.namatipe]
    .map(textValue)
    .filter(Boolean)
    .join(' ')
  const serial = textValue(value.namaserialnumber)
  if (identity || serial) return `${identity}${serial ? ` (${serial})` : ''}`.trim()

  return value.daftaralatstandar ? standardLabel(value.daftaralatstandar) : ''
}

const placeLabel = computed(() => optionLabel(props.item?.tempatKalibrasi) || '-')
const instructions = computed(() => (props.item?.detailInstruksiKerja || [])
  .map((row: any) => optionLabel(row?.daftarInstruksiKerja ?? row))
  .filter(Boolean))
const standards = computed(() => (props.item?.detailPeralatanStandar || [])
  .map((row: any) => standardLabel(row?.daftaralatstandar ?? row))
  .filter(Boolean))

const formattedDate = computed(() => {
  if (!props.item?.tglkalibrasi) return '-'
  const date = new Date(props.item.tglkalibrasi)
  return Number.isNaN(date.getTime()) ? String(props.item.tglkalibrasi) : new Intl.DateTimeFormat('id-ID', {
    dateStyle: 'long', timeStyle: 'short',
  }).format(date)
})

const withUnit = (value: any, unit: string) => {
  if (value === undefined || value === null || value === '') return '-'
  const stringValue = String(value)
  return stringValue.includes(unit.replace(' ', '')) || stringValue.includes(unit) ? stringValue : `${stringValue} ${unit}`
}

const toLocalDateTime = (value: any) => {
  const date = value ? new Date(value) : new Date()
  if (Number.isNaN(date.getTime())) return ''
  const offset = date.getTimezoneOffset() * 60000
  return new Date(date.getTime() - offset).toISOString().slice(0, 16)
}

const startEdit = () => {
  draft.value = {
    tglkalibrasi: toLocalDateTime(props.item?.tglkalibrasi),
    tempatKalibrasi: typeof props.item?.tempatKalibrasi === 'object'
      ? { ...props.item.tempatKalibrasi }
      : { value: props.item?.idruangan || '', label: props.item?.tempatKalibrasi || '' },
    suhu: props.item?.suhu || '',
    kelembabanRelatif: props.item?.kelembabanRelatif || '',
    notes: props.item?.notes || '',
    detailInstruksiKerja: JSON.parse(JSON.stringify(props.item?.detailInstruksiKerja || [])),
    detailPeralatanStandar: JSON.parse(JSON.stringify(props.item?.detailPeralatanStandar || [])),
  }
  if (draft.value.detailInstruksiKerja.length === 0) addInstruction()
  if (draft.value.detailPeralatanStandar.length === 0) addStandard()
  imageFile.value = null
  editing.value = true
}

const cancelEdit = () => {
  editing.value = false
  imageFile.value = null
}

const addInstruction = () => draft.value.detailInstruksiKerja.push({ daftarInstruksiKerja: null })
const addStandard = () => draft.value.detailPeralatanStandar.push({ daftaralatstandar: null })
const responseList = (response: any) => Array.isArray(response) ? response : (response?.data || [])

const searchRooms = async (event: any) => {
  const response: any = await useApi().get(`general/dropdown/ruangan_m?select=id,namaruangan&param_search=namaruangan&query=${encodeURIComponent(event.query || '')}&limit=10`)
  roomOptions.value = responseList(response)
}

const searchStandards = async (event: any) => {
  const response: any = await useApi().get(`${props.role}/alat-standar?param_search=namaalatstandar,namamerk,namatipe,namaserialnumber&query=${encodeURIComponent(event.query || '')}`)
  standardOptions.value = responseList(response).map((row: any) => ({
    value: row.id,
    label: `${row.namaalatstandar || ''} - ${row.namamerk || ''} ${row.namatipe || ''} (${row.namaserialnumber || ''})`,
  }))
}

const selectImage = (event: Event) => {
  const input = event.target as HTMLInputElement
  imageFile.value = input.files?.[0] || null
}

const save = async () => {
  const roomId = typeof draft.value.tempatKalibrasi === 'object'
    ? draft.value.tempatKalibrasi?.value
    : draft.value.tempatKalibrasi
  if (!draft.value.tglkalibrasi || !roomId || !draft.value.suhu || !draft.value.kelembabanRelatif) {
    H.alert('warning', 'Tanggal, tempat, suhu, dan kelembaban relatif wajib diisi')
    return
  }

  const formData = new FormData()
  formData.append('norec_detail', props.norec)
  formData.append('tglkalibrasi', draft.value.tglkalibrasi.replace('T', ' '))
  formData.append('tempatKalibrasi', roomId)
  formData.append('suhu', draft.value.suhu)
  formData.append('kelembabanRelatif', draft.value.kelembabanRelatif)
  formData.append('notes', draft.value.notes || '')
  formData.append('daftarinstruksikerja', JSON.stringify(draft.value.detailInstruksiKerja.map((row: any) => ({
    instruksikerja: row?.daftarInstruksiKerja?.value || null,
  }))))
  formData.append('daftarperalatanstandar', JSON.stringify(draft.value.detailPeralatanStandar.map((row: any) => ({
    peralatanstandar: row?.daftaralatstandar?.value || null,
  }))))
  if (imageFile.value) formData.append('fileMeter', imageFile.value)

  saving.value = true
  try {
    const response: any = await useApi().post(`/${props.role}/update-metadata-lembar-kerja`, formData)
    Object.assign(props.item, {
      tglkalibrasi: draft.value.tglkalibrasi,
      tempatKalibrasi: draft.value.tempatKalibrasi,
      suhu: draft.value.suhu,
      kelembabanRelatif: draft.value.kelembabanRelatif,
      notes: draft.value.notes,
      detailInstruksiKerja: draft.value.detailInstruksiKerja,
      detailPeralatanStandar: draft.value.detailPeralatanStandar,
      gambarsuhu: response?.gambarsuhu || response?.data?.gambarsuhu || props.item?.gambarsuhu,
    })
    editing.value = false
    H.alert('success', 'Data kalibrasi berhasil diperbarui')
    emit('saved')
  } catch (error: any) {
    H.alert('error', error?.response?.data?.message || 'Gagal memperbarui data kalibrasi')
  } finally {
    saving.value = false
  }
}
</script>

<style scoped lang="scss">
.metadata-card {
  margin: 0 0 26px;
  padding: 24px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 14px;
  background: var(--white);
  box-shadow: 0 4px 14px rgba(36, 48, 72, 0.05);
  text-align: left;
}

.metadata-heading,
.list-heading,
.editor-actions {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.metadata-heading {
  margin-bottom: 20px;
}

.metadata-heading h3 {
  margin: 3px 0 0;
  color: var(--dark-text);
  font-size: 1.15rem;
}

.metadata-eyebrow,
.detail-block > span {
  color: var(--primary);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

.metadata-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 12px;
}

:deep(.metadata-item) {
  display: flex;
  align-items: center;
  gap: 11px;
  min-width: 0;
  padding: 13px;
  border-radius: 10px;
  background: var(--fade-grey-light-6);
}

:deep(.metadata-item-icon) {
  display: grid;
  flex: 0 0 36px;
  place-items: center;
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: var(--primary);
  color: var(--white);
}

:deep(.metadata-item div) {
  display: grid;
  min-width: 0;
}

:deep(.metadata-item small) {
  color: var(--light-text);
}

:deep(.metadata-item strong) {
  overflow-wrap: anywhere;
  color: var(--dark-text);
}

.metadata-details {
  display: grid;
  gap: 14px;
  margin-top: 14px;
}

.detail-columns,
.editor-lists,
.editor-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.editor-grid {
  grid-template-columns: repeat(4, minmax(0, 1fr));
}

.detail-block {
  padding: 14px 16px;
  border-left: 3px solid var(--primary);
  border-radius: 0 9px 9px 0;
  background: var(--fade-grey-light-6);
}

.detail-block p,
.detail-block ol {
  margin: 8px 0 0;
  color: var(--dark-text);
  white-space: pre-wrap;
}

.metadata-list {
  display: grid;
  gap: 9px;
  padding-left: 24px;
}

.metadata-list li {
  padding-left: 4px;
  line-height: 1.45;
  overflow-wrap: anywhere;
}

.environment-photo {
  display: grid;
  gap: 8px;
  width: min(320px, 100%);
}

.environment-photo span {
  font-weight: 600;
}

.environment-photo img {
  width: 100%;
  max-height: 190px;
  border-radius: 10px;
  object-fit: cover;
}

.metadata-editor,
.editor-lists > div {
  display: grid;
  gap: 14px;
}

.editor-lists {
  margin: 6px 0 14px;
}

.editor-lists > div {
  align-content: start;
  padding: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
}

.editor-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 42px;
  gap: 8px;
  align-items: center;
}

.editor-actions {
  justify-content: flex-end;
}

@media (max-width: 1100px) {
  .metadata-grid,
  .editor-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .metadata-grid,
  .detail-columns,
  .editor-lists,
  .editor-grid {
    grid-template-columns: 1fr;
  }
}
</style>
