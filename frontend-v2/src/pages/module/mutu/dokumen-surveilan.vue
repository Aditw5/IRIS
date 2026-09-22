<template>
  <div class="udr-page-title">
    <div>
      <div class="udr-title-text">Dokumen Surveilan</div>
      <div class="udr-title-sub">Satu pintu dokumen Jakarta • Gresik</div>
    </div>

    <div v-if="queryFolderMode" class="udr-link-mode-actions">
      <VButton type="button" color="info" rounded outlined icon="feather:filter" @click="clearFolderFilter()">
        Tampilkan Semua Dokumen
      </VButton>
    </div>
  </div>

  <div v-if="queryFolderMode" class="udr-link-mode-banner">
    <div class="udr-link-mode-main">
      <div class="udr-link-mode-title">
        Mode Link Folder Aktif
      </div>
      <div class="udr-link-mode-sub">
        Menampilkan folder:
        <b>{{ queryFolderJenis }}</b>
        <span v-if="queryFolderRootLabel">
          / <b>{{ queryFolderRootLabel }}</b>
        </span>
        <span v-if="queryFolderTargetLabel && queryFolderTargetLabel !== queryFolderRootLabel">
          / {{ queryFolderTargetLabel }}
        </span>
      </div>
    </div>

    <VButton type="button" color="success" rounded outlined icon="feather:x" class="udr-reset-filter-btn-success"
      @click="clearFolderFilter()">
      Reset Filter
    </VButton>
  </div>

  <VCard>
    <div class="columns is-multiline">
      <div v-for="jenis in visibleJenisList" :key="jenis" :class="getJenisColumnClass()">
        <VCard>
          <div class="udr-section-head">
            <div>
              <h3 class="title is-5 mb-2 mr-1">{{ jenis }}</h3>
            </div>

            <VButton v-if="canCreate(jenis)" @click="add(jenis)" type="button" icon="feather:plus" color="success"
              raised outlined rounded>
              Tambah
            </VButton>
          </div>

          <div class="udr-hint mt-2">
            Klik <b>nama node</b> untuk lihat detail. Klik <b>Salin Link</b> untuk membagikan tautan langsung ke folder
            tersebut.
            <template v-if="canEdit(jenis)">
              Klik tombol <b>Upload</b> pada node {{ jenis }} untuk menjadikannya target upload massal.
            </template>

            <template v-if="queryFolderMode">
              <br />
              <b>Mode filter link aktif:</b> hanya folder yang dibagikan yang ditampilkan.
            </template>
          </div>

          <Tree v-model:expandedKeys="expandedKeys[jenis]" v-model:selectionKeys="selectionKeys[jenis]"
            :value="getVisibleTreeSource(jenis)" class="w-full md:w-30rem mt-4 custom-tree" :filter="!queryFolderMode"
            filterMode="lenient" @nodeSelect="(node: any) => onNodeSelect(node, jenis)" selectionMode="single"
            :metaKeySelection="false">
            <template #default="{ node }">
              <div class="tree-node-label tree-node-label-with-action" :data-node-key="String(node.key)" :class="{
                'is-root-head': node.children && node.children.length,
                'is-child-node': !node.children || !node.children.length,
                'is-selected-upload-folder': String(selectedUploadFolder[jenis]?.key ?? '') === String(node.key),
                'is-opened-from-query': focusedNodeKey[jenis] === String(node.key),
                'is-query-root': queryFolderMode && queryFolderJenis === jenis && String(queryFolderRootKey) === String(node.key)
              }">
                <div class="tree-node-main">
                  <i v-if="node.children && node.children.length" class="mr-2 head-icon" aria-hidden="true"></i>
                  <i v-else class="mr-2 child-icon" aria-hidden="true"></i>
                  <span class="tree-node-text">
                    {{ node.label }}
                  </span>
                </div>

                <div class="tree-node-actions">
                  <button type="button" class="udr-node-copy-btn" @click.stop="copyFolderLink(node)"
                    :title="`Salin link folder ${node.label}`">
                    <i class="iconify" data-icon="feather:link-2"></i>
                    <span>Salin Link</span>
                  </button>

                  <button v-if="canEdit(jenis)" type="button" class="udr-node-upload-btn"
                    @click.stop="selectUploadFolder(jenis, node)"
                    :title="`Jadikan ${node.label} sebagai target upload massal ${jenis}`">
                    <i class="iconify" data-icon="feather:upload"></i>
                    <span>Upload</span>
                  </button>
                </div>
              </div>
            </template>
          </Tree>

          <div v-if="queryFolderMode && !getVisibleTreeSource(jenis).length" class="udr-empty-filter mt-4">
            Folder dari link tidak ditemukan pada {{ jenis }}.
          </div>

          <div v-if="canEdit(jenis)" class="udr-upload-panel mt-4">
            <div class="udr-upload-panel-head">
              <div class="udr-upload-panel-title">
                Upload Massal {{ jenis }}
              </div>
              <div class="udr-upload-panel-sub">
                Klik tombol <b>Upload</b> pada node {{ jenis }} mana pun, lalu drag & drop banyak file ke area ini.
              </div>
            </div>

            <div v-if="selectedUploadFolder[jenis]" class="udr-active-folder">
              <div class="udr-active-folder-label">Folder aktif</div>
              <div class="udr-active-folder-name">
                {{ selectedUploadFolder[jenis].label }}
              </div>
            </div>

            <div v-else class="udr-active-folder is-empty">
              Klik tombol <b>Upload</b> pada salah satu node di {{ jenis }} terlebih dahulu.
            </div>

            <div class="udr-dropzone" :class="{
              'is-disabled': !selectedUploadFolder[jenis],
              'is-dragover': dragState[jenis]
            }" @dragenter.prevent="onDragEnter(jenis)" @dragover.prevent="onDragOver(jenis)"
              @dragleave.prevent="onDragLeave(jenis)" @drop.prevent="onDrop(jenis, $event)">
              <input :ref="(el: any) => setFileInputRef(jenis, el)" type="file" multiple class="is-hidden"
                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" @change="onPickFiles(jenis, $event)" />

              <div class="udr-dropzone-inner">
                <div class="udr-dropzone-icon">📂</div>
                <div class="udr-dropzone-title">
                  Seret file ke sini untuk upload massal
                </div>
                <div class="udr-dropzone-sub">
                  atau klik tombol pilih file di bawah
                </div>

                <div class="buttons is-centered mt-4">
                  <VButton type="button" color="primary" rounded outlined icon="feather:upload"
                    :disabled="!selectedUploadFolder[jenis] || uploadState[jenis].loading"
                    @click="openFilePicker(jenis)">
                    Pilih Banyak File
                  </VButton>

                  <VButton type="button" color="warning" rounded outlined icon="feather:x-circle"
                    :disabled="!uploadState[jenis].files.length || uploadState[jenis].loading"
                    @click="clearPendingFiles(jenis)">
                    Bersihkan
                  </VButton>

                  <VButton type="button" color="success" rounded outlined icon="feather:send"
                    :loading="uploadState[jenis].loading"
                    :disabled="!selectedUploadFolder[jenis] || !uploadState[jenis].files.length || uploadState[jenis].loading"
                    @click="uploadMulti(jenis)">
                    Upload {{ uploadState[jenis].files.length }} File
                  </VButton>
                </div>
              </div>
            </div>

            <div v-if="uploadState[jenis].files.length" class="udr-pending-list mt-4">
              <div class="udr-pending-head">
                <span>File siap upload</span>
                <span>{{ uploadState[jenis].files.length }} file</span>
              </div>

              <div class="udr-pending-items">
                <div v-for="(f, i) in uploadState[jenis].files" :key="`${f.name}-${i}`" class="udr-pending-item">
                  <div class="udr-pending-main">
                    <div class="udr-pending-name">{{ f.name }}</div>
                    <div class="udr-pending-size">{{ formatFileSize(f.size) }}</div>
                  </div>

                  <VButton type="button" color="danger" icon="feather:trash-2" rounded outlined
                    :disabled="uploadState[jenis].loading" @click="removePendingFile(jenis, i)">
                    Hapus
                  </VButton>
                </div>
              </div>
            </div>
          </div>
        </VCard>
      </div>
    </div>
  </VCard>

  <VCard class="udr-log-card" v-if="!queryFolderMode">
    <div class="udr-log-head">
      <div>
        <h3 class="title is-5 mb-1">Log Perubahan Terbaru</h3>
        <div class="udr-log-sub">
          Menampilkan {{ logLimit }} aktivitas perubahan Dokumen Surveilan terbaru
        </div>
      </div>

      <VButton type="button" icon="feather:refresh-cw" color="info" raised outlined rounded :loading="isLoadingLog"
        @click="loadLogSurveilan()">
        Refresh
      </VButton>
    </div>

    <div class="udr-log-filter">
      <div class="udr-log-filter-item">
        <VField label="Jumlah Data">
          <VControl fullwidth class="prime-auto-select">
            <Dropdown v-model="logLimitObj" :options="logLimitOptions" optionLabel="label" class="is-rounded"
              placeholder="Jumlah data" style="width:100%" @change="onChangeLogLimit()" />
          </VControl>
        </VField>
      </div>

      <div class="udr-log-filter-search">
        <VField label="Pencarian Log">
          <VControl icon="feather:search">
            <input v-model="logSearch" type="text" class="input is-rounded"
              placeholder="Cari nama dokumen, pegawai, nomor dokumen, folder..." @keyup.enter="loadLogSurveilan()" />
          </VControl>
        </VField>
      </div>

      <div class="udr-log-filter-action">
        <VButton type="button" color="primary" rounded outlined icon="feather:search" :loading="isLoadingLog"
          @click="loadLogSurveilan()">
          Cari
        </VButton>

        <VButton type="button" color="warning" rounded outlined icon="feather:x-circle" :disabled="!logSearch"
          @click="resetLogSearch()">
          Reset
        </VButton>
      </div>
    </div>

    <div v-if="logUdr.length" class="udr-log-list">
      <div v-for="(l, i) in logUdr" :key="l.id ?? i" class="udr-log-item">
        <div class="udr-log-left">
          <div class="udr-log-badge">#{{ i + 1 }}</div>
        </div>

        <div class="udr-log-main">
          <div class="udr-log-title">
            <span class="udr-log-doc">{{ l.namaisidokumen ?? l.label ?? l.nama ?? 'Dokumen' }}</span>
            <span v-if="l.jenisudr" class="udr-log-pill">{{ l.jenisudr }}</span>
            <span v-if="l.revisike" class="udr-log-pill is-rev">Revisi {{ pad2(l.revisike) }}</span>
            <span v-if="l.aksi" class="udr-log-pill is-aksi">{{ l.aksi }}</span>
            <span v-if="l.nomordokumen" class="udr-log-pill">{{ l.nomordokumen }}</span>
          </div>

          <div class="udr-log-meta">
            <span class="udr-log-meta-item">{{ l.namapegawai ?? '-' }}</span>
            <span class="udr-log-meta-sep">•</span>
            <span class="udr-log-meta-item">{{ formatDateID(l.tglrevisi ?? l.created_at ?? l.updated_at) }}</span>
          </div>

          <div v-if="l.folder_path" class="udr-log-folder">
            <span class="udr-log-folder-label">Folder induk:</span>
            <span class="udr-log-folder-value">{{ l.folder_path }}</span>
            <span v-if="l.folder_nomordokumen" class="udr-log-folder-pill">{{ l.folder_nomordokumen }}</span>
            <span v-if="l.folder_namadokumen" class="udr-log-folder-pill">{{ l.folder_namadokumen }}</span>
          </div>

          <div v-else class="udr-log-folder is-root">
            <span class="udr-log-folder-label">Posisi:</span>
            <span class="udr-log-folder-value">Dokumen utama / tidak memiliki folder induk</span>
          </div>
        </div>
      </div>
    </div>

    <div v-else class="udr-log-empty">
      Tidak ada data log.
    </div>
  </VCard>

  <Dialog v-model:visible="modalIsiDokumen" modal
    :header="item.id ? 'Ubah Dokumen Surveilan' : 'Tambah Dokumen Surveilan'" :style="{ width: '38vw' }">
    <div class="columns is-multiline">
      <div class="column is-12">
        <VField label="Head">
          <VControl icon="fas fa-archway" fullwidth class="prime-auto-select">
            <Dropdown v-model="item.kdrinciandokumenhead" :options="d_ObjectModulActive" optionLabel="label"
              class="is-rounded" placeholder="Head" style="width:100%" :filter="true" showClear
              :disabled="!canEditActive" @change="changeHead()" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Nomor Induk Dokumen">
          <VControl icon="feather:link" fullwidth class="prime-auto-select">
            <Dropdown v-model="item.nomorindukobj" :options="d_NomorIndukLookup" optionLabel="label" class="is-rounded"
              placeholder="Pilih nomor induk dokumen" style="width:100%" :filter="true" showClear
              :disabled="!canEditActive" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Nama Isi Dokumen">
          <VControl icon="feather:bookmark">
            <input v-model="item.namaisidokumen" type="text" class="input is-rounded" placeholder="Nama Isi Dokumen"
              :disabled="!canEditActive" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Upload Isi Dokumen">
          <FileUpload v-model="fileDokumenMutu" mode="advanced" name="demo"
            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp" :maxFileSize="30000000" outlined
            :invalidFileTypeMessage="'{0}: Format file tidak diizinkan.'"
            :invalidFileSizeMessage="'Ukuran maksimal berkas adalah {1}'"
            style="background-color: transparent; color: var(--danger); border: 1px solid;"
            :chooseLabel="isFile(fileDokumenMutu) ? fileDokumenMutu.name : item.fileLama || 'Unggah File'"
            @select="onSelect($event)" class="is-rounded w-100" :disabled="!canEditActive" />
        </VField>
      </div>

      <div class="column is-12" v-if="riwayat.length">
        <div class="revision-box">
          <div class="revision-title">Riwayat Dokumen</div>

          <div v-for="r in riwayat" :key="r.id" class="revision-item">
            <div>
              <b>Revisi {{ pad2(r.revisike) }}</b> - {{ r.namaisidokumen }}
            </div>
            <div class="revision-meta">
              {{ formatDateID(r.tglrevisi) }}
            </div>
            <div class="mt-2">
              <VButton type="button" color="info" rounded outlined icon="feather:printer"
                @click="cetakDokumenById(r.id)">
                Cetak
              </VButton>
            </div>
          </div>
        </div>
      </div>

      <div class="column is-12">
        <VField label="Keterangan">
          <VControl>
            <VTextarea v-model="item.keterangan" rows="5" placeholder="keterangan" :disabled="!canEditActive" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="No Urut">
          <VControl icon="feather:hash">
            <input v-model="item.nourut" type="number" class="input is-rounded" placeholder="No Urut"
              :disabled="!canEditActive" />
          </VControl>
        </VField>
      </div>
    </div>

    <template #footer>
      <VButton v-if="item.id && canEditActive" type="button" rounded outlined color="danger" raised icon="feather:trash"
        :loading="isLoading" @click="deleteIsiSurveilan(item)">
        Hapus
      </VButton>

      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="tutup()">
        Tutup
      </VButton>

      <VButton v-if="canEditActive" type="button" rounded outlined color="primary" raised icon="feather:save"
        :loading="isLoading" @click="saveIsiSurveilan()">
        {{ item.id ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, nextTick } from 'vue'
import { useHead } from '@vueuse/head'
import { useRoute } from 'vue-router'
import Dialog from 'primevue/dialog'
import Tree from 'primevue/tree'
import Dropdown from 'primevue/dropdown'
import FileUpload from 'primevue/fileupload'
import { useApi } from '/@src/composable/useApi'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'Dokumen Surveilan - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

type JenisSurveilan = 'Jakarta' | 'Gresik'

const route = useRoute()
const toast = useToaster()
const userSession = useUserSession()

const jenisList: JenisSurveilan[] = ['Jakarta', 'Gresik']

const item: any = ref({
  statusenabled: true,
  aktif: true,
  alamaturlform: '',
})

const modalIsiDokumen = ref(false)
const fileDokumenMutu: any = ref()
const isLoading = ref(false)
const isLoadingLog = ref(false)
const riwayat: any = ref([])
const logUdr: any = ref([])
const selectedJenis = ref<JenisSurveilan>('Jakarta')
const selectedIDModul = ref('')
const logLimitOptions = ref([
  { value: 10, label: '10 Data' },
  { value: 25, label: '25 Data' },
  { value: 50, label: '50 Data' },
  { value: 100, label: '100 Data' },
])

const logLimitObj: any = ref({
  value: 10,
  label: '10 Data',
})

const logLimit = computed(() => logLimitObj.value?.value ?? 10)
const logSearch = ref('')

const d_NomorIndukLookup: any = ref([])

const expandedKeys = reactive<Record<JenisSurveilan, any>>({
  Jakarta: {},
  Gresik: {},
})

const selectionKeys = reactive<Record<JenisSurveilan, any>>({
  Jakarta: {},
  Gresik: {},
})

const focusedNodeKey = reactive<Record<JenisSurveilan, string>>({
  Jakarta: '',
  Gresik: '',
})

const treeSource = reactive<Record<JenisSurveilan, any[]>>({
  Jakarta: [],
  Gresik: [],
})

const dataFlat = reactive<Record<JenisSurveilan, any[]>>({
  Jakarta: [],
  Gresik: [],
})

const selectedUploadFolder = reactive<Record<JenisSurveilan, any>>({
  Jakarta: null,
  Gresik: null,
})

const uploadState = reactive<Record<JenisSurveilan, { files: File[]; loading: boolean }>>({
  Jakarta: { files: [], loading: false },
  Gresik: { files: [], loading: false },
})

const dragState = reactive<Record<JenisSurveilan, boolean>>({
  Jakarta: false,
  Gresik: false,
})

const fileInputRefs = reactive<Record<JenisSurveilan, HTMLInputElement | null>>({
  Jakarta: null,
  Gresik: null,
})

const queryFolderMode = ref(false)
const queryFolderJenis = ref<JenisSurveilan | ''>('')
const queryFolderRootKey = ref('')
const queryFolderRootLabel = ref('')
const queryFolderTargetKey = ref('')
const queryFolderTargetLabel = ref('')

const kelompokUser = computed(() =>
  String(userSession.getUser()?.kelompokUser?.kelompokUser ?? '').toLowerCase().trim(),
)

const isAsesor = computed(() => kelompokUser.value === 'asesor')

const visibleJenisList = computed<JenisSurveilan[]>(() => {
  if (queryFolderMode.value && queryFolderJenis.value) {
    return [queryFolderJenis.value as JenisSurveilan]
  }

  return jenisList
})

const canEdit = (_jenis: JenisSurveilan) => {
  return !isAsesor.value
}

const canCreate = (_jenis: JenisSurveilan) => {
  return !isAsesor.value
}

const canEditActive = computed(() => canEdit(selectedJenis.value))
const d_ObjectModulActive = computed(() => dataFlat[selectedJenis.value])

const getJenisColumnClass = () => {
  return queryFolderMode.value ? 'column is-12' : 'column is-6'
}

const getVisibleTreeSource = (jenis: JenisSurveilan) => {
  if (!queryFolderMode.value) {
    return treeSource[jenis]
  }

  if (queryFolderJenis.value !== jenis) {
    return []
  }

  const root = findNodeByKey(treeSource[jenis], queryFolderRootKey.value)

  if (!root) {
    return []
  }

  return [root]
}

const clearFolderFilter = async () => {
  queryFolderMode.value = false
  queryFolderJenis.value = ''
  queryFolderRootKey.value = ''
  queryFolderRootLabel.value = ''
  queryFolderTargetKey.value = ''
  queryFolderTargetLabel.value = ''

  jenisList.forEach((jenis) => {
    focusedNodeKey[jenis] = ''
    selectionKeys[jenis] = {}
  })

  const url = new URL(window.location.href)
  url.searchParams.delete('Folder')
  url.searchParams.delete('openId')
  window.history.replaceState({}, '', url.toString())

  await nextTick()
}

const setFileInputRef = (jenis: JenisSurveilan, el: HTMLInputElement | null) => {
  fileInputRefs[jenis] = el
}

const isFile = (obj: any): obj is File => {
  return obj instanceof File || (obj && typeof obj === 'object' && 'name' in obj && 'size' in obj && 'type' in obj)
}

const allowedTypes = [
  'application/pdf',
  'application/msword',
  'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
  'application/vnd.ms-excel',
  'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  'image/jpeg',
  'image/png',
  'image/webp',
]

const validateSingleFile = (file: File) => {
  if (file.size > 30000000) return `File ${file.name} melebihi 30 MB`
  if (!allowedTypes.includes(file.type)) return `File ${file.name} tidak diizinkan`
  return null
}

const formatDateID = (v: any) => {
  if (!v) return '-'
  const d = new Date(v)
  if (isNaN(d as any)) return v
  return d.toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' })
}

const formatFileSize = (size: number) => {
  if (!size && size !== 0) return '-'
  if (size < 1024) return `${size} B`
  if (size < 1024 * 1024) return `${(size / 1024).toFixed(1)} KB`
  return `${(size / (1024 * 1024)).toFixed(2)} MB`
}

const getStableFolderKey = (node: any) => {
  return String(
    node?.data?.linkfolder ??
    node?.data?.stable_folder_key ??
    node?.data?.root_id ??
    node?.key ??
    '',
  )
}

const buildFolderLink = (node: any) => {
  const url = new URL(window.location.href)
  const stableKey = getStableFolderKey(node)

  url.searchParams.set('Folder', stableKey)
  url.searchParams.delete('openId')

  return url.toString()
}

const copyFolderLink = async (node: any) => {
  try {
    const link = buildFolderLink(node)
    await navigator.clipboard.writeText(link)
    toast.success(`Link folder "${node.label}" berhasil disalin`)
  } catch (error) {
    H.alert('error', 'Gagal menyalin link folder')
  }
}

const isSameFolderNode = (node: any, id: any) => {
  const target = String(id)

  const candidates = [
    node?.key,
    node?.data?.key,
    node?.data?.id,
    node?.data?.root_id,
    node?.data?.linkfolder,
    node?.data?.stable_folder_key,
    node?.data?.kdisidokumen,
  ]
    .filter((x) => x !== null && x !== undefined && x !== '')
    .map((x) => String(x))

  return candidates.includes(target)
}

const findNodePathById = (nodes: any[], id: any, parents: any[] = []): any[] | null => {
  for (const node of nodes || []) {
    const currentPath = [...parents, node]

    if (isSameFolderNode(node, id)) {
      return currentPath
    }

    if (node.children?.length) {
      const childPath = findNodePathById(node.children, id, currentPath)
      if (childPath) return childPath
    }
  }

  return null
}

const findNodeByKey = (nodes: any[], key: any): any | null => {
  const target = String(key)

  for (const node of nodes || []) {
    if (String(node.key) === target) {
      return node
    }

    if (node.children?.length) {
      const found = findNodeByKey(node.children, target)
      if (found) return found
    }
  }

  return null
}

const expandPathNodes = (jenis: JenisSurveilan, path: any[]) => {
  const map: Record<string, boolean> = { ...expandedKeys[jenis] }

  path.forEach((node: any, index: number) => {
    if (index < path.length - 1) {
      map[String(node.key)] = true
    }
  })

  expandedKeys[jenis] = map
}

const highlightAndScrollToNode = async (jenis: JenisSurveilan, nodeKey: any) => {
  focusedNodeKey[jenis] = String(nodeKey)

  await nextTick()
  await nextTick()

  const el = document.querySelector(`.custom-tree [data-node-key="${String(nodeKey)}"]`) as HTMLElement | null

  if (el) {
    el.scrollIntoView({
      behavior: 'smooth',
      block: 'center',
      inline: 'nearest',
    })
  }

  setTimeout(() => {
    if (focusedNodeKey[jenis] === String(nodeKey)) {
      focusedNodeKey[jenis] = ''
    }
  }, 3000)
}

const openFromQuery = async () => {
  const openId = route.query.Folder ?? route.query.openId

  if (!openId) {
    return
  }

  await nextTick()

  let foundAny = false

  for (const jenis of jenisList) {
    const path = findNodePathById(treeSource[jenis], openId)

    if (path && path.length) {
      const root = path[0]
      const found = path[path.length - 1]

      queryFolderMode.value = true
      queryFolderJenis.value = jenis
      queryFolderRootKey.value = String(root.key)
      queryFolderRootLabel.value = root.label ?? ''
      queryFolderTargetKey.value = String(found.key)
      queryFolderTargetLabel.value = found.label ?? ''

      expandedKeys[jenis] = {}
      selectionKeys[jenis] = {}

      expandPathNodes(jenis, path)

      expandedKeys[jenis] = {
        ...expandedKeys[jenis],
        [String(root.key)]: true,
      }

      if (found?.children?.length) {
        expandedKeys[jenis] = {
          ...expandedKeys[jenis],
          [String(found.key)]: true,
        }
      }

      selectionKeys[jenis] = {
        [String(found.key)]: true,
      }

      selectedUploadFolder[jenis] = {
        key: found.data?.key ?? found.data?.id ?? found.key,
        stableKey: getStableFolderKey(found),
        label: found.label,
        nourut: found.nourut,
        data: found.data,
      }

      await nextTick()
      await highlightAndScrollToNode(jenis, found.key)

      await fillFromNode(found, jenis)

      foundAny = true
      break
    }
  }

  if (!foundAny) {
    queryFolderMode.value = false
    queryFolderJenis.value = ''
    queryFolderRootKey.value = ''
    queryFolderRootLabel.value = ''
    queryFolderTargetKey.value = ''
    queryFolderTargetLabel.value = ''

    H.alert('warning', 'Folder dari link tidak ditemukan atau data belum tersedia')
  }
}

const loadNomorIndukLookup = async () => {
  try {
    const res = await useApi().get('/udr/lookup-nomor-induk')
    d_NomorIndukLookup.value = res?.data ?? []
  } catch (e) {
    d_NomorIndukLookup.value = []
  }
}

const loadRiwayat = async (id: any) => {
  try {
    const res = await useApi().get('/udr/riwayat-isi-dokumen?id=' + id)
    riwayat.value = res?.riwayat || []
  } catch (e) {
    riwayat.value = []
  }
}

const loadLogSurveilan = async () => {
  isLoadingLog.value = true

  try {
    const params = new URLSearchParams()
    params.append('limit', String(logLimit.value))

    if (logSearch.value.trim()) {
      params.append('search', logSearch.value.trim())
    }

    const res = await useApi().get('/udr/log-isi-dokumen?' + params.toString())
    logUdr.value = res?.log ?? []
  } catch (e) {
    logUdr.value = []
  } finally {
    isLoadingLog.value = false
  }
}

const onChangeLogLimit = async () => {
  await loadLogSurveilan()
}

const resetLogSearch = async () => {
  logSearch.value = ''
  await loadLogSurveilan()
}

const loadJenis = async (jenis: JenisSurveilan) => {
  const res = await useApi().get('/udr/master-isi-dokumen?jenisudr=' + jenis)
  treeSource[jenis] = res?.tree ?? []
  dataFlat[jenis] = res?.data ?? []
}

const reloadAll = async () => {
  await loadJenis('Jakarta')
  await loadJenis('Gresik')
}

const reloadAllAndReapplyQuery = async () => {
  await reloadAll()

  if (route.query.Folder || route.query.openId) {
    await openFromQuery()
  }
}

const fillFromNode = async (node: any, jenis: JenisSurveilan) => {
  selectedJenis.value = jenis

  item.value = {
    statusenabled: true,
    aktif: true,
    alamaturlform: '',
  }

  item.value.kdrinciandokumenhead = null

  dataFlat[jenis].forEach((element: any) => {
    if (String(element.key) === String(node.parent_id)) {
      item.value.kdrinciandokumenhead = element
    }
  })

  item.value.id = node.data?.key ?? node.data?.id ?? node.key
  item.value.namaisidokumen = node.label
  item.value.nourut = node.nourut
  item.value.keterangan = node.data?.keterangan
  item.value.alamaturlform = node.data?.alamaturlform
  item.value.fileLama = node.data?.isidokumen
  item.value.nomorindukfk = node.data?.nomorindukfk
  item.value.nomorindukobj =
    d_NomorIndukLookup.value.find((x: any) => String(x.key) === String(node.data?.nomorindukfk)) ?? null

  await loadRiwayat(item.value.id)

  modalIsiDokumen.value = true
}

const onNodeSelect = async (node: any, jenis: JenisSurveilan) => {
  await fillFromNode(node, jenis)
}

const selectUploadFolder = (jenis: JenisSurveilan, node: any) => {
  selectedUploadFolder[jenis] = {
    key: node.data?.key ?? node.data?.id ?? node.key,
    stableKey: getStableFolderKey(node),
    label: node.label,
    nourut: node.nourut,
    data: node.data,
  }

  if (node?.children?.length && !expandedKeys[jenis][node.key]) {
    expandedKeys[jenis] = {
      ...expandedKeys[jenis],
      [node.key]: true,
    }
  }

  toast.success(`Folder upload aktif ${jenis}: ${node.label}`)
}

const clear = () => {
  item.value = {
    statusenabled: true,
    aktif: true,
    alamaturlform: '',
  }
  fileDokumenMutu.value = null
  riwayat.value = []
  selectedIDModul.value = ''
}

const tutup = () => {
  clear()
  modalIsiDokumen.value = false
}

const setNoUrut = async () => {
  const res = await useApi().get('/udr/nourut-dokumen?jenisudr=' + selectedJenis.value)
  item.value.nourut = res?.nourut ?? 1
}

const add = async (jenis: JenisSurveilan) => {
  selectedJenis.value = jenis
  clear()
  item.value.nomorindukobj = null
  await setNoUrut()
  modalIsiDokumen.value = true
}

const changeHead = async () => {
  if (!item.value.id) {
    await setNoUrut()
  }
}

const onSelect = async (filez: any) => {
  const file = filez.files[0]
  if (!file) return

  const error = validateSingleFile(file)
  if (error) {
    H.alert('error', error)
    return
  }

  fileDokumenMutu.value = file
}

const cetakDokumenById = (id: any) => {
  if (!id) {
    H.alert('warning', 'Data tidak valid')
    return
  }

  H.printBlade(`udr/cetak-dokumen?id=${id}`)
}

const openFilePicker = (jenis: JenisSurveilan) => {
  if (!selectedUploadFolder[jenis]) {
    H.alert('warning', `Pilih target upload ${jenis} terlebih dahulu`)
    return
  }

  fileInputRefs[jenis]?.click()
}

const pushPendingFiles = (jenis: JenisSurveilan, files: File[]) => {
  const validFiles: File[] = []
  const errors: string[] = []

  files.forEach((file) => {
    const error = validateSingleFile(file)
    if (error) {
      errors.push(error)
      return
    }

    const exists = uploadState[jenis].files.some(
      (f) =>
        f.name === file.name &&
        f.size === file.size &&
        f.type === file.type &&
        f.lastModified === file.lastModified,
    )

    if (!exists) validFiles.push(file)
  })

  if (validFiles.length) {
    uploadState[jenis].files = [...uploadState[jenis].files, ...validFiles]
  }

  if (errors.length) {
    H.alert('warning', errors.join('\n'))
  }
}

const onPickFiles = (jenis: JenisSurveilan, event: Event) => {
  const target = event.target as HTMLInputElement
  const files = Array.from(target.files || [])
  if (!files.length) return

  if (!selectedUploadFolder[jenis]) {
    H.alert('warning', `Pilih target upload ${jenis} terlebih dahulu`)
    target.value = ''
    return
  }

  pushPendingFiles(jenis, files)
  target.value = ''
}

const onDragEnter = (jenis: JenisSurveilan) => {
  if (!selectedUploadFolder[jenis]) return
  dragState[jenis] = true
}

const onDragOver = (jenis: JenisSurveilan) => {
  if (!selectedUploadFolder[jenis]) return
  dragState[jenis] = true
}

const onDragLeave = (jenis: JenisSurveilan) => {
  dragState[jenis] = false
}

const onDrop = (jenis: JenisSurveilan, event: DragEvent) => {
  dragState[jenis] = false

  if (!selectedUploadFolder[jenis]) {
    H.alert('warning', `Pilih target upload ${jenis} terlebih dahulu`)
    return
  }

  const files = Array.from(event.dataTransfer?.files || [])
  if (!files.length) return

  pushPendingFiles(jenis, files)
}

const removePendingFile = (jenis: JenisSurveilan, index: number) => {
  uploadState[jenis].files.splice(index, 1)
}

const clearPendingFiles = (jenis: JenisSurveilan) => {
  uploadState[jenis].files = []
}

const uploadMulti = async (jenis: JenisSurveilan) => {
  if (!selectedUploadFolder[jenis]?.key) {
    H.alert('warning', `Folder upload ${jenis} belum dipilih`)
    return
  }

  if (!uploadState[jenis].files.length) {
    H.alert('warning', 'Belum ada file yang dipilih')
    return
  }

  const formData = new FormData()
  formData.append('jenisudr', jenis)
  formData.append('kdrinciandokumenhead', selectedUploadFolder[jenis].key)

  if (item.value.nomorindukobj?.key) {
    formData.append('nomorindukfk', item.value.nomorindukobj.key)
  }

  uploadState[jenis].files.forEach((file) => formData.append('files[]', file))

  uploadState[jenis].loading = true

  try {
    const res = await useApi().post('/udr/upload-multi-dokumen', formData)
    toast.success(res?.message ?? `${uploadState[jenis].files.length} file berhasil diupload`)
    uploadState[jenis].files = []
    await loadJenis(jenis)
    await loadLogSurveilan()
    await openFromQuery()
  } catch (e: any) {
    H.alert('error', e?.response?.data?.message ?? `Upload massal ${jenis} gagal`)
  } finally {
    uploadState[jenis].loading = false
  }
}

const saveIsiSurveilan = async () => {
  if (!item.value.namaisidokumen) {
    H.alert('error', 'Nama Isi Dokumen harus di isi')
    return
  }

  if (!item.value.nourut) {
    H.alert('error', 'No Urut harus di isi')
    return
  }

  const formData = new FormData()

  if (isFile(fileDokumenMutu.value)) {
    formData.append('fileDokumenMutu', fileDokumenMutu.value)
  } else if (item.value.fileLama) {
    formData.append('namaFileLama', item.value.fileLama)
  }

  formData.append('alamaturlform', item.value.alamaturlform ?? '')
  formData.append('id', item.value.id ? item.value.id : '')
  formData.append('keterangan', item.value.keterangan ? item.value.keterangan : '')
  formData.append('namaisidokumen', item.value.namaisidokumen ? item.value.namaisidokumen : '')
  formData.append('nourut', item.value.nourut ? item.value.nourut : '')
  formData.append('kdrinciandokumenhead', item.value.kdrinciandokumenhead?.key ?? '')
  formData.append('rincianfk', selectedIDModul.value)
  formData.append('jenisudr', selectedJenis.value)
  formData.append('nomorindukfk', item.value.nomorindukobj?.key ?? '')

  isLoading.value = true

  try {
    await useApi().post('/udr/save-isi-dokumen', formData)
    await reloadAllAndReapplyQuery()
    await loadLogSurveilan()
    clear()
    await setNoUrut()
    modalIsiDokumen.value = false
    toast.success('Data berhasil disimpan')
  } catch (e: any) {
    H.alert('error', e?.response?.data?.message ?? 'Simpan gagal')
  } finally {
    isLoading.value = false
  }
}

const deleteIsiSurveilan = async (itemx: any) => {
  isLoading.value = true

  try {
    await useApi().post('/udr/hapus-isi-dokumen', { id: itemx.id })
    await reloadAllAndReapplyQuery()
    await loadLogSurveilan()
    clear()
    await setNoUrut()
    modalIsiDokumen.value = false
    toast.success('Data berhasil dihapus')
  } catch (e: any) {
    H.alert('error', e?.response?.data?.message ?? 'Hapus gagal')
  } finally {
    isLoading.value = false
  }
}

const pad2 = (n: any) => String(n ?? 1).padStart(2, '0')

await loadNomorIndukLookup()
await reloadAll()
await loadLogSurveilan()
await openFromQuery()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';

.udr-page-title {
  margin-bottom: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(90deg, rgba(33, 150, 243, 0.14), rgba(255, 255, 255, 0));
  border: 1px solid rgba(33, 150, 243, 0.18);
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.udr-title-text {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--dark-text);
}

.udr-title-sub {
  margin-top: 4px;
  font-size: 0.9rem;
  color: var(--light-text);
}

.udr-link-mode-actions {
  flex-shrink: 0;
}

.udr-link-mode-banner {
  margin-bottom: 14px;
  padding: 12px 14px;
  border-radius: 14px;
  border: 1px solid rgba(76, 175, 80, .20);
  background: rgba(76, 175, 80, .07);
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 12px;
}

.udr-link-mode-title {
  font-size: .95rem;
  font-weight: 800;
  color: var(--dark-text);
}

.udr-link-mode-sub {
  margin-top: 3px;
  font-size: .86rem;
  color: var(--light-text);
}

.udr-reset-filter-btn-success {
  border-color: #00b894 !important;
  color: #00a878 !important;
  background: #ffffff !important;
  font-weight: 700 !important;
  opacity: 1 !important;
  visibility: visible !important;
  box-shadow: 0 4px 12px rgba(0, 184, 148, 0.12) !important;
}

.udr-reset-filter-btn-success:hover {
  background: rgba(0, 184, 148, 0.08) !important;
  color: #008f72 !important;
  border-color: #00a878 !important;
}

.udr-section-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.udr-hint {
  font-size: 0.85rem;
  color: var(--light-text);
  line-height: 1.5;
}

.custom-tree {
  .p-tree-container {
    padding-left: 0 !important;
  }

  .p-tree-node-icon {
    display: none !important;
  }

  .p-treenode-children {
    margin-left: 1.25rem;
    padding-left: 0.25rem;
    border-left: 1px dashed var(--fade-grey, #dde2eb);
  }

  .p-treenode-content {
    padding: 0.15rem 0.25rem;
    border-radius: 10px;
    transition: .2s ease;
  }

  .p-treenode-content:hover {
    background: rgba(33, 150, 243, .05);
  }

  .tree-node-label {
    display: flex;
    align-items: center;
    font-size: .9rem;
    width: 100%;
    padding: 2px 4px;
    border-radius: 8px;
  }

  .tree-node-label.is-root-head {
    font-weight: 600;
    color: var(--dark-text);
  }

  .tree-node-label.is-child-node {
    font-weight: 400;
    color: var(--light-text);
    margin-left: .1rem;
  }

  .tree-node-label.is-selected-upload-folder {
    background: rgba(76, 175, 80, .12);
    border: 1px solid rgba(76, 175, 80, .18);
  }

  .tree-node-label.is-opened-from-query {
    background: rgba(33, 150, 243, .12);
    border: 1px solid rgba(33, 150, 243, .30);
    box-shadow: 0 0 0 3px rgba(33, 150, 243, .10);
  }

  .tree-node-label.is-query-root {
    background: rgba(76, 175, 80, .10);
    border: 1px solid rgba(76, 175, 80, .20);
  }

  .head-icon {
    font-size: .9rem;
  }

  .child-icon {
    font-size: .85rem;
  }
}

.tree-node-label-with-action {
  justify-content: space-between;
  gap: 10px;
}

.tree-node-main {
  display: flex;
  align-items: center;
  min-width: 0;
  flex: 1;
}

.tree-node-text {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.tree-node-actions {
  flex-shrink: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

.udr-node-copy-btn,
.udr-node-upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  padding: 4px 10px;
  font-size: .76rem;
  font-weight: 600;
  cursor: pointer;
  transition: .2s ease;
  background: #fff;
}

.udr-node-copy-btn {
  border: 1px solid rgba(117, 117, 117, .22);
  color: #5f6368;
}

.udr-node-copy-btn:hover {
  background: rgba(117, 117, 117, .06);
  transform: translateY(-1px);
}

.udr-node-upload-btn {
  border: 1px solid rgba(33, 150, 243, .22);
  color: var(--primary);
}

.udr-node-upload-btn:hover {
  background: rgba(33, 150, 243, .06);
  transform: translateY(-1px);
}

.udr-empty-filter {
  padding: 14px;
  border-radius: 12px;
  background: rgba(255, 152, 0, .07);
  border: 1px dashed rgba(255, 152, 0, .25);
  color: var(--light-text);
  font-size: .9rem;
}

.udr-upload-panel {
  margin-top: 16px;
  padding: 14px;
  border-radius: 16px;
  border: 1px solid rgba(33, 150, 243, .14);
  background: linear-gradient(180deg, rgba(33, 150, 243, .04), rgba(255, 255, 255, .7));
}

.udr-upload-panel-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--dark-text);
}

.udr-upload-panel-sub {
  margin-top: 4px;
  font-size: .85rem;
  color: var(--light-text);
}

.udr-active-folder {
  margin-top: 10px;
  padding: 10px 12px;
  border-radius: 12px;
  background: rgba(76, 175, 80, .08);
  border: 1px solid rgba(76, 175, 80, .18);
}

.udr-active-folder.is-empty {
  background: rgba(255, 152, 0, .06);
  border: 1px dashed rgba(255, 152, 0, .28);
  color: var(--light-text);
}

.udr-active-folder-label {
  font-size: .76rem;
  text-transform: uppercase;
  letter-spacing: .4px;
  color: var(--light-text);
}

.udr-active-folder-name {
  margin-top: 2px;
  font-size: .95rem;
  font-weight: 700;
  color: var(--dark-text);
}

.udr-dropzone {
  margin-top: 14px;
  border: 2px dashed rgba(33, 150, 243, .22);
  border-radius: 16px;
  min-height: 220px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: .2s ease;
  background: rgba(255, 255, 255, .86);
}

.udr-dropzone.is-disabled {
  opacity: .6;
}

.udr-dropzone.is-dragover {
  border-color: rgba(76, 175, 80, .7);
  background: rgba(76, 175, 80, .06);
}

.udr-dropzone-inner {
  text-align: center;
  padding: 20px;
}

.udr-dropzone-icon {
  font-size: 2rem;
}

.udr-dropzone-title {
  font-size: 1rem;
  font-weight: 700;
  color: var(--dark-text);
  margin-top: 8px;
}

.udr-dropzone-sub {
  margin-top: 4px;
  font-size: .85rem;
  color: var(--light-text);
}

.udr-pending-list {
  border-top: 1px solid var(--fade-grey-dark-3);
  padding-top: 14px;
}

.udr-pending-head {
  display: flex;
  justify-content: space-between;
  font-size: .85rem;
  font-weight: 700;
  color: var(--dark-text);
  margin-bottom: 10px;
}

.udr-pending-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 12px;
  margin-bottom: 8px;
}

.udr-pending-name {
  font-weight: 600;
  color: var(--dark-text);
}

.udr-pending-size {
  font-size: .8rem;
  color: var(--light-text);
}

.udr-log-card {
  margin-top: 16px;
}

.udr-log-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.udr-log-sub {
  font-size: .85rem;
  color: var(--light-text);
}

.udr-log-list {
  margin-top: 16px;
}

.udr-log-item {
  display: flex;
  gap: 12px;
  padding: 12px 0;
  border-bottom: 1px solid var(--fade-grey-dark-3);
}

.udr-log-badge {
  width: 34px;
  height: 34px;
  border-radius: 999px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(33, 150, 243, .08);
  font-weight: 700;
  color: var(--primary);
}

.udr-log-title {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  align-items: center;
}

.udr-log-doc {
  font-weight: 700;
  color: var(--dark-text);
}

.udr-log-pill {
  border-radius: 999px;
  padding: 3px 8px;
  font-size: .75rem;
  background: rgba(33, 150, 243, .08);
  color: var(--primary);
}

.udr-log-pill.is-rev {
  background: rgba(255, 193, 7, .12);
  color: #8a6d00;
}

.udr-log-pill.is-aksi {
  background: rgba(76, 175, 80, .12);
  color: #2e7d32;
}

.udr-log-meta {
  margin-top: 6px;
  display: flex;
  gap: 8px;
  flex-wrap: wrap;
  color: var(--light-text);
  font-size: .85rem;
}

.udr-log-empty {
  margin-top: 16px;
  color: var(--light-text);
}

.revision-box {
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 14px;
  padding: 12px;
}

.revision-title {
  font-weight: 700;
  margin-bottom: 10px;
}

.revision-item {
  border-top: 1px dashed var(--fade-grey-dark-3);
  padding-top: 10px;
  margin-top: 10px;
}

.revision-meta {
  font-size: .82rem;
  color: var(--light-text);
  margin-top: 4px;
}

.udr-log-filter {
  margin-top: 16px;
  padding: 14px;
  border-radius: 14px;
  border: 1px solid rgba(33, 150, 243, .12);
  background: rgba(33, 150, 243, .035);
  display: grid;
  grid-template-columns: 180px 1fr auto;
  gap: 12px;
  align-items: end;
}

.udr-log-filter-item,
.udr-log-filter-search {
  min-width: 0;
}

.udr-log-filter-action {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-bottom: 2px;
}

.udr-log-folder {
  margin-top: 8px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 6px;
  font-size: .82rem;
  color: var(--light-text);
}

.udr-log-folder-label {
  font-weight: 700;
  color: var(--dark-text);
}

.udr-log-folder-value {
  color: var(--light-text);
}

.udr-log-folder-pill {
  border-radius: 999px;
  padding: 3px 8px;
  background: rgba(117, 117, 117, .08);
  color: #5f6368;
  font-size: .75rem;
}

.udr-log-folder.is-root {
  color: var(--light-text);
}

@media only screen and (max-width: 768px) {
  .udr-log-filter {
    grid-template-columns: 1fr;
  }

  .udr-log-filter-action {
    justify-content: flex-start;
    flex-wrap: wrap;
  }
}
</style>
