<template>
  <section :class="['worksheet-attachments', { compact, 'compact-empty': compact && files.length === 0 }]">
    <ConfirmDialog :group="confirmationGroup" />

    <div v-if="uploadEnabled" class="upload-panel">
      <div class="upload-heading">
        <div>
          <h4>Upload File Lembar Kerja</h4>
          <p>Pilih satu atau beberapa file Excel/CSV. Maksimal 10 MB per file.</p>
        </div>
        <VButton icon="feather:save" color="info" :loading="saving" :disabled="selectedFiles.length === 0"
          @click="uploadFiles">
          Simpan {{ selectedFiles.length > 1 ? `${selectedFiles.length} File` : '' }}
        </VButton>
      </div>

      <FileUpload
        :key="uploadKey"
        name="fileMitraExcel[]"
        mode="advanced"
        :multiple="true"
        accept=".csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
        :maxFileSize="10000000"
        :showUploadButton="false"
        chooseLabel="Pilih File"
        cancelLabel="Batal"
        invalidFileTypeMessage="{0}: File harus CSV atau Excel (XLS/XLSX)."
        invalidFileSizeMessage="Ukuran maksimal setiap file adalah {1}."
        @select="onSelect"
        @clear="selectedFiles = []"
        @remove="onRemove"
      />
    </div>

    <div v-if="loading" class="attachment-state">Memuat file terunggah...</div>
    <div v-else-if="compact && files.length > 0" class="attachment-summary">
      <VButton rounded color="warning" icon="feather:download" raised bold @click="downloadOrChoose">
        Download File Terunggah
      </VButton>
    </div>
    <div v-else-if="files.length > 0" class="attachment-browser">
      <div class="attachment-copy">
        <i class="fas fa-file-excel" aria-hidden="true"></i>
        <div>
          <strong>{{ files.length }} file terunggah</strong>
          <span>Semua file ditampilkan di bawah ini.</span>
        </div>
      </div>
      <div class="attachment-list worksheet-file-list">
        <div v-for="file in files" :key="file.id || file.stored_name" class="attachment-row">
          <span class="attachment-icon"><i class="fas fa-file-excel" aria-hidden="true"></i></span>
          <span class="attachment-name">
            <strong>{{ file.name }}</strong>
            <small>{{ formatSize(file.size) }}</small>
          </span>
          <div class="attachment-actions">
            <VButton type="button" color="warning" icon="feather:download" outlined @click="download(file)">
              Download
            </VButton>
            <VButton v-if="canDelete" type="button" color="danger" icon="feather:trash-2" outlined
              :loading="deletingKey === fileKey(file)" @click="confirmRemoveFile(file)">
              Hapus
            </VButton>
          </div>
        </div>
      </div>
    </div>
    <div v-else-if="!uploadEnabled && !compact" class="attachment-state">Belum ada file lembar kerja yang diunggah.</div>

    <Dialog v-if="compact" v-model:visible="chooserOpen" modal header="Pilih File yang Akan Didownload"
      :style="{ width: 'min(620px, 94vw)' }" :draggable="false">
      <p class="chooser-help">Terdapat {{ files.length }} file. Pilih salah satu file di bawah ini.</p>
      <div class="attachment-list">
        <button v-for="file in files" :key="file.id || file.stored_name" type="button" class="chooser-row"
          @click="download(file)">
          <span class="attachment-icon"><i class="fas fa-file-excel" aria-hidden="true"></i></span>
          <span class="attachment-name">
            <strong>{{ file.name }}</strong>
            <small>{{ formatSize(file.size) }}</small>
          </span>
          <i class="iconify" data-icon="feather:download" aria-hidden="true"></i>
        </button>
      </div>
    </Dialog>
  </section>
</template>

<script setup lang="ts">
import { computed, getCurrentInstance, onMounted, ref, watch } from 'vue'
import FileUpload from 'primevue/fileupload'
import Dialog from 'primevue/dialog'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import { useApi } from '/@src/composable/useApi'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'

type Attachment = {
  id?: number | null
  name: string
  stored_name: string
  size?: number | null
}

const props = withDefaults(defineProps<{
  norec: string
  role: 'pelaksana' | 'penyelia' | 'manager' | 'asman'
  uploadEnabled?: boolean
  compact?: boolean
}>(), {
  uploadEnabled: false,
  compact: false,
})

const files = ref<Attachment[]>([])
const selectedFiles = ref<File[]>([])
const loading = ref(false)
const saving = ref(false)
const chooserOpen = ref(false)
const uploadKey = ref(0)
const deletingKey = ref<string | null>(null)
const canDelete = computed(() => !props.compact && (props.role === 'pelaksana' || props.role === 'penyelia'))
const confirm = useConfirm()
const confirmationGroup = `worksheet-attachment-${getCurrentInstance()?.uid ?? props.norec}`

const apiRole = () => props.role === 'manager' || props.role === 'asman' ? props.role : props.role

const loadFiles = async () => {
  if (!props.norec) return
  loading.value = true
  try {
    const response: any = await useApi().get(`/${apiRole()}/excel-length?norec=${encodeURIComponent(props.norec)}`)
    files.value = response?.data?.files || response?.files || []
  } catch {
    files.value = []
  } finally {
    loading.value = false
  }
}

const onSelect = (event: any) => {
  selectedFiles.value = Array.from(event?.files || [])
}

const onRemove = (event: any) => {
  selectedFiles.value = selectedFiles.value.filter((file) => file !== event?.file)
}

const uploadFiles = async () => {
  if (selectedFiles.value.length === 0) return
  const formData = new FormData()
  selectedFiles.value.forEach((file) => formData.append('fileMitraExcel[]', file))
  formData.append('norec', props.norec)
  saving.value = true
  try {
    await useApi().post(`/${props.role}/save-excel-lembar-kerja`, formData)
    selectedFiles.value = []
    uploadKey.value += 1
    H.alert('success', 'Semua file berhasil diunggah')
    await loadFiles()
  } catch (error: any) {
    H.alert('error', error?.response?.data?.message || 'Gagal mengunggah file lembar kerja')
  } finally {
    saving.value = false
  }
}

const downloadOrChoose = async () => {
  if (files.value.length === 0) await loadFiles()
  if (files.value.length === 0) {
    H.alert('warning', 'Belum ada file lembar kerja yang dapat didownload')
    return
  }
  if (files.value.length === 1) {
    download(files.value[0])
    return
  }
  chooserOpen.value = true
}

const fileKey = (file: Attachment) => String(file.id ?? file.stored_name)

const confirmRemoveFile = (file: Attachment) => {
  confirm.require({
    group: confirmationGroup,
    message: `File "${file.name}" akan dihapus dan tidak dapat didownload kembali. Lanjutkan?`,
    header: 'Konfirmasi Hapus File',
    icon: 'pi pi-exclamation-triangle',
    acceptLabel: 'Ya, Hapus',
    rejectLabel: 'Batal',
    acceptClass: 'p-button-danger',
    rejectClass: 'p-button-text',
    accept: () => removeUploadedFile(file),
    reject: () => { },
  })
}

const removeUploadedFile = async (file: Attachment) => {
  deletingKey.value = fileKey(file)
  try {
    const payload: Record<string, any> = { norec: props.norec }
    if (file.id !== null && file.id !== undefined) payload.file_id = file.id
    else payload.filename = file.stored_name
    const response = await useApi().post(`/${props.role}/delete-file-lembar-kerja`, payload)
    if (!response) return
    H.alert('success', 'File lembar kerja berhasil dihapus')
    await loadFiles()
  } catch (error: any) {
    H.alert('error', error?.response?.data?.message || 'Gagal menghapus file lembar kerja')
  } finally {
    deletingKey.value = null
  }
}

const download = (file: Attachment) => {
  const params = new URLSearchParams({
    norec: props.norec,
    token: useUserSession().token || '',
  })
  if (file.id) params.set('file_id', String(file.id))
  else params.set('filename', file.stored_name)
  window.open(`/service/${apiRole()}/download-file-terunggah?${params.toString()}`, '_blank')
  chooserOpen.value = false
}

const formatSize = (value?: number | null) => {
  if (!value) return 'Ukuran tidak tersedia'
  if (value < 1024 * 1024) return `${(value / 1024).toFixed(1)} KB`
  return `${(value / (1024 * 1024)).toFixed(1)} MB`
}

watch(() => props.norec, () => {
  files.value = []
  loadFiles()
})
onMounted(() => {
  loadFiles()
})
</script>

<style scoped lang="scss">
.worksheet-attachments {
  margin: 18px 0 24px;
}

.worksheet-attachments.compact-empty {
  display: none;
  margin: 0;
}

.upload-panel {
  margin-bottom: 18px;
}

.upload-heading,
.attachment-summary {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
}

.attachment-browser {
  display: grid;
  gap: 14px;
  padding: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 12px;
  background: var(--white);
}

.upload-heading {
  margin-bottom: 14px;
}

.upload-heading h4,
.upload-heading p {
  margin: 0;
}

.upload-heading p {
  margin-top: 4px;
  color: var(--light-text);
}

.attachment-summary {
  padding: 14px 16px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 12px;
  background: var(--white);
}

.compact .attachment-summary {
  padding: 0;
  border: 0;
  background: transparent;
}

.attachment-copy {
  display: flex;
  align-items: center;
  gap: 12px;
}

.attachment-copy > i {
  font-size: 25px;
  color: var(--success);
}

.attachment-copy div {
  display: grid;
}

.attachment-copy span,
.attachment-state,
.chooser-help {
  color: var(--light-text);
}

.attachment-list {
  display: grid;
  gap: 10px;
  margin-top: 16px;
}

.attachment-row,
.chooser-row {
  display: grid;
  align-items: center;
  gap: 12px;
  width: 100%;
  padding: 12px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
  background: var(--white);
  color: var(--dark-text);
  text-align: left;
}

.attachment-row {
  grid-template-columns: 42px minmax(0, 1fr) auto;
}

.chooser-row {
  grid-template-columns: 42px minmax(0, 1fr) 24px;
  cursor: pointer;
}

.chooser-row:hover {
  border-color: var(--primary);
}

.attachment-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.attachment-icon {
  display: grid;
  place-items: center;
  width: 42px;
  height: 42px;
  border-radius: 9px;
  background: var(--success-light-48);
  color: var(--success);
}

.attachment-name {
  display: grid;
  min-width: 0;
}

.attachment-name strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.attachment-name small {
  color: var(--light-text);
}

@media (max-width: 700px) {
  .attachment-row {
    grid-template-columns: 42px minmax(0, 1fr);
  }

  .attachment-actions {
    grid-column: 1 / -1;
    justify-content: flex-end;
  }
}
</style>
