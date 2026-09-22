<template>
  <div class="udr-page-title">
    <div>
      <div class="udr-title-text">(UDS) ULAB Digital Storage</div>
      <div class="udr-title-sub">Satu pintu dokumen Mutu • Teknik • Admin</div>
    </div>
    <VButton type="button" color="primary" rounded outlined icon="feather:hard-drive" @click="openDrivePreview">
      Coba Tampilan Drive Baru
    </VButton>
  </div>

  <VCard>
    <div class="columns is-multiline">
      <div v-for="jenis in jenisList" :key="jenis" class="column is-4">
        <VCard>
          <div class="udr-section-head">
            <div>
              <h3 class="title is-5 mb-2 mr-1">Program {{ jenis }}</h3>
            </div>

            <VButton v-if="canCreate(jenis)" @click="add(jenis)" type="button" icon="feather:plus" color="success"
              raised outlined rounded>
              Tambah
            </VButton>
          </div>

          <div class="udr-hint mt-2">
            Klik <b>nama node</b> untuk edit. Klik tombol <b>Upload</b> pada node {{ jenis }} untuk menjadikannya target
            upload massal.
          </div>

          <Tree v-model:expandedKeys="expandedKeys[jenis]" v-model:selectionKeys="selectionKeys[jenis]"
            :value="treeSource[jenis]" class="w-full md:w-30rem mt-4 custom-tree" :filter="true" filterMode="lenient"
            @nodeSelect="(node: any) => onNodeSelect(node, jenis)" selectionMode="single" :metaKeySelection="false">
            <template #default="{ node }">
              <div class="tree-node-label tree-node-label-with-action" :data-node-key="String(node.key)" :class="{
                'is-root-head': node.children && node.children.length,
                'is-child-node': !node.children || !node.children.length,
                'is-selected-upload-folder': selectedUploadFolder[jenis]?.key === node.key,
                'is-opened-from-query': focusedNodeKey[jenis] === String(node.key)
              }">
                <div class="tree-node-main">
                  <i v-if="node.children && node.children.length" class="mr-2 head-icon" aria-hidden="true"></i>
                  <i v-else class="mr-2 child-icon" aria-hidden="true"></i>
                  <span v-tooltip-prime.bottom="node.nourut?.toString()" class="tree-node-text">
                    {{ node.label }}
                  </span>
                </div>

                <div class="tree-node-actions" v-if="canCreate(jenis)">
                  <button type="button" class="udr-node-upload-btn" @click.stop="selectUploadFolder(jenis, node)"
                    :title="`Jadikan ${node.label} sebagai target upload massal ${jenis}`">
                    <i class="iconify" data-icon="feather:upload"></i>
                    <span>Upload</span>
                  </button>
                </div>
              </div>
            </template>
          </Tree>

          <div v-if="canCreate(jenis)" class="udr-upload-panel mt-4">
            <div class="udr-upload-panel-head">
              <div class="udr-upload-panel-title">
                Upload Massal Program {{ jenis }}
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
              Klik tombol <b>Upload</b> pada salah satu node di Program {{ jenis }} terlebih dahulu.
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

  <VCard class="udr-log-card">
    <div class="udr-log-head">
      <div>
        <h3 class="title is-5 mb-1">Log Perubahan Terbaru</h3>
        <div class="udr-log-sub">
          Menampilkan {{ logLimit }} aktivitas perubahan UDR terbaru oleh user
        </div>
      </div>

      <VButton type="button" icon="feather:refresh-cw" color="info" raised outlined rounded :loading="isLoadingLog"
        @click="loadLogUdr()">
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
              placeholder="Cari nama dokumen, pegawai, nomor dokumen, folder..." @keyup.enter="loadLogUdr()" />
          </VControl>
        </VField>
      </div>

      <div class="udr-log-filter-action">
        <VButton type="button" color="primary" rounded outlined icon="feather:search" :loading="isLoadingLog"
          @click="loadLogUdr()">
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
            <span class="udr-log-meta-item">
              <i class="feather-icon" data-feather="user"></i>
              {{ l.namapegawai ?? l.pegawai ?? l.user ?? '-' }}
            </span>
            <span class="udr-log-meta-sep">•</span>
            <span class="udr-log-meta-item">
              <i class="feather-icon" data-feather="clock"></i>
              {{ formatDateID(l.tglrevisi ?? l.created_at ?? l.updated_at) }}
            </span>
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

  <Dialog v-model:visible="modalIsiDokumen" modal :header="item.id ? 'Ubah ' + item.namaisidokumen : 'Tambah'"
    :style="{ width: '38vw' }">
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
              placeholder="Pilih nomor induk dokumen" style="width:100%" :filter="true" showClear />
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

      <div class="column is-12" v-if="isCvHeadSelected">
        <VField label="Link ke Pegawai (CV)">
          <VControl icon="feather:user" fullwidth class="prime-auto-select">
            <Dropdown v-model="item.pegawaiCvObj" :options="d_PegawaiCvLookup" optionLabel="label" class="is-rounded"
              placeholder="Pilih pegawai" style="width:100%" :filter="true" showClear :disabled="!canEditActive" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12" v-if="isCvHeadSelected">
        <div class="notification is-info is-light">
          Dokumen ini akan langsung mencetak CV pegawai yang dipilih. Upload isi dokumen boleh dikosongkan.
        </div>
      </div>

      <div class="column is-12" v-if="showPrintSection">
        <VField :label="printSectionLabel">
          <div class="buttons is-multiline">
            <template v-if="showRevisionButtons">
              <VButton v-for="r in riwayat" :key="r.id" class="mr-2 mb-2" :color="r.id === item.id ? 'info' : 'primary'"
                outlined rounded icon="feather:eye" :title="formatDateID(r.tglrevisi)" @click="cetakDokumenById(r.id)">
                Revisi {{ pad2(r.revisike) }}
                <span class="is-size-7 has-text-grey ml-2">
                  ({{ formatDateID(r.tglrevisi) }})
                </span>
              </VButton>
            </template>

            <template v-else-if="showCvButton">
              <VButton class="mr-2 mb-2" color="info" outlined rounded icon="feather:printer"
                :disabled="!item.pegawaiCvObj?.value" @click="cetakCvPegawai()">
                Cetak CV
                <span v-if="item.pegawaiCvObj?.label" class="is-size-7 has-text-grey ml-2">
                  ({{ item.pegawaiCvObj?.label }})
                </span>
              </VButton>
            </template>
          </div>
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
            @select="onSelect($event)" class="is-rounded w-100" :disabled="!canEditActive || disableUploadDokumen" />
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Keterangan">
          <VControl>
            <VTextarea v-model="item.keterangan" rows="5" placeholder="keterangan"></VTextarea>
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
        :loading="isLoading" @click="deleteIsiUdr(item)">
        Hapus
      </VButton>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="tutup()">
        Tutup
      </VButton>
      <VButton v-if="canEditActive" type="button" rounded outlined color="primary" raised icon="feather:save"
        :loading="isLoading" @click="saveIsiUdr()">
        {{ item.id ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, reactive, computed, nextTick, watch } from 'vue'
import { useHead } from '@vueuse/head'
import { useRoute, useRouter } from 'vue-router'
import Dialog from 'primevue/dialog'
import Tree from 'primevue/tree'
import Dropdown from 'primevue/dropdown'
import FileUpload from 'primevue/fileupload'
import { useApi } from '/@src/composable/useApi'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'UDS (ULAB Digital Storage) - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

type JenisUdr = 'Mutu' | 'Teknik' | 'Admin'

const route = useRoute()
const router = useRouter()
const toast = useToaster()
const userSession = useUserSession()

const openDrivePreview = () => {
  router.push({ name: 'module-uds-ulab-digital-storage' })
}

const jenisList: JenisUdr[] = ['Mutu', 'Teknik', 'Admin']

const item: any = ref({
  statusenabled: true,
  aktif: true,
  pegawaiCvObj: null,
  alamaturlform: '',
})

const modalIsiDokumen = ref(false)
const fileDokumenMutu: any = ref()
const isLoading = ref(false)
const isLoadingLog = ref(false)
const riwayat: any = ref([])
const logUdr: any = ref([])
const selectedJenis = ref<JenisUdr>('Mutu')
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
const d_PegawaiCvLookup: any = ref([])

const kelompokUser = computed(() =>
  String(userSession.getUser()?.kelompokUser?.kelompokUser ?? '').toLowerCase().trim(),
)

const lokasiKalibrasiFk = computed(() =>
  Number(userSession.getUser()?.kelompokUser?.lokasiKalibrasiFk ?? 0),
)

const expandedKeys = reactive<Record<JenisUdr, any>>({
  Mutu: {},
  Teknik: {},
  Admin: {},
})

const selectionKeys = reactive<Record<JenisUdr, any>>({
  Mutu: {},
  Teknik: {},
  Admin: {},
})

const focusedNodeKey = reactive<Record<JenisUdr, string>>({
  Mutu: '',
  Teknik: '',
  Admin: '',
})

const treeSource = reactive<Record<JenisUdr, any[]>>({
  Mutu: [],
  Teknik: [],
  Admin: [],
})

const dataFlat = reactive<Record<JenisUdr, any[]>>({
  Mutu: [],
  Teknik: [],
  Admin: [],
})

const selectedUploadFolder = reactive<Record<JenisUdr, any>>({
  Mutu: null,
  Teknik: null,
  Admin: null,
})

const uploadState = reactive<Record<JenisUdr, { files: File[]; loading: boolean }>>({
  Mutu: { files: [], loading: false },
  Teknik: { files: [], loading: false },
  Admin: { files: [], loading: false },
})

const dragState = reactive<Record<JenisUdr, boolean>>({
  Mutu: false,
  Teknik: false,
  Admin: false,
})

const fileInputRefs = reactive<Record<JenisUdr, HTMLInputElement | null>>({
  Mutu: null,
  Teknik: null,
  Admin: null,
})

const canEdit = (jenis: JenisUdr) => {
  const g = kelompokUser.value
  if (g === 'asman' || g === 'manager') return true
  if (jenis === 'Mutu') return g === 'mutu' && lokasiKalibrasiFk.value === 1
  if (jenis === 'Teknik') return g === 'pelaksana' || g === 'penyelia'
  if (jenis === 'Admin') return g === 'registrasi'
  return false
}

const canCreate = (jenis: JenisUdr) => canEdit(jenis)
const canEditActive = computed(() => canEdit(selectedJenis.value))
const d_ObjectModulActive = computed(() => dataFlat[selectedJenis.value])

const isCvHeadSelected = computed(() => {
  const label = String(item.value?.kdrinciandokumenhead?.label ?? '')
    .trim()
    .toLowerCase()

  return label.startsWith('cv -')
})

const hasExistingFile = computed(() => {
  return !!String(item.value?.fileLama ?? '').trim()
})

const hasRevisionHistory = computed(() => {
  return Array.isArray(riwayat.value) && riwayat.value.length > 0
})

const showRevisionButtons = computed(() => {
  return hasRevisionHistory.value && hasExistingFile.value
})

const showCvButton = computed(() => {
  return isCvHeadSelected.value && !showRevisionButtons.value
})

const showPrintSection = computed(() => {
  return showRevisionButtons.value || showCvButton.value
})

const printSectionLabel = computed(() => {
  if (showRevisionButtons.value) return 'Riwayat Dokumen'
  if (showCvButton.value) return 'Cetak Dokumen'
  return 'Dokumen'
})

const disableUploadDokumen = computed(() => {
  return isCvHeadSelected.value && !hasExistingFile.value
})

const setFileInputRef = (jenis: JenisUdr, el: HTMLInputElement | null) => {
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

const parsePegawaiIdFromUrl = (url: string) => {
  if (!url) return null
  const m = url.match(/[?&]id=(\d+)/)
  return m?.[1] ?? null
}

const buildCvUrl = (pegawaiId: any) => {
  return pegawaiId ? `mutu/cetak-cv-pegawai?pdf=true&id=${pegawaiId}` : ''
}

const loadPegawaiCvLookup = async () => {
  try {
    const res = await useApi().get(
      'general/dropdown/pegawai_m?select=id,namalengkap&param_search=namalengkap&query=&limit=1000',
    )
    d_PegawaiCvLookup.value = res ?? []
  } catch (e) {
    d_PegawaiCvLookup.value = []
  }
}

const findNodePathById = (nodes: any[], id: any, parents: any[] = []): any[] | null => {
  for (const node of nodes || []) {
    const currentPath = [...parents, node]
    if (String(node.key) === String(id)) return currentPath
    if (node.children?.length) {
      const childPath = findNodePathById(node.children, id, currentPath)
      if (childPath) return childPath
    }
  }
  return null
}

const expandPathNodes = (jenis: JenisUdr, path: any[]) => {
  const map: Record<string, boolean> = { ...expandedKeys[jenis] }
  path.forEach((node: any, index: number) => {
    if (index < path.length - 1) map[String(node.key)] = true
  })
  expandedKeys[jenis] = map
}

const highlightAndScrollToNode = async (jenis: JenisUdr, nodeKey: any) => {
  focusedNodeKey[jenis] = String(nodeKey)
  await nextTick()
  await nextTick()

  const el = document.querySelector(
    `.custom-tree [data-node-key="${String(nodeKey)}"]`,
  ) as HTMLElement | null

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

const openUdrFromQuery = async () => {
  const openUdrId = route.query.openUdrId
  if (!openUdrId) return

  await nextTick()

  for (const jenis of jenisList) {
    const path = findNodePathById(treeSource[jenis], openUdrId)

    if (path && path.length) {
      const found = path[path.length - 1]
      expandPathNodes(jenis, path)
      selectionKeys[jenis] = { [String(found.key)]: true }
      await nextTick()
      await highlightAndScrollToNode(jenis, found.key)
      await onNodeSelect(found, jenis)
      break
    }
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
    const res = await useApi().get('/udr/riwayat-isi-udr?id=' + id)
    riwayat.value = res?.riwayat || []
  } catch (e) {
    riwayat.value = []
  }
}

const loadLogUdr = async () => {
  isLoadingLog.value = true

  try {
    const params = new URLSearchParams()
    params.append('limit', String(logLimit.value))

    if (logSearch.value.trim()) {
      params.append('search', logSearch.value.trim())
    }

    const res = await useApi().get('/udr/log-isi-udr?' + params.toString())
    logUdr.value = res?.log ?? []
  } catch (e) {
    logUdr.value = []
  } finally {
    isLoadingLog.value = false
  }
}

const onChangeLogLimit = async () => {
  await loadLogUdr()
}

const resetLogSearch = async () => {
  logSearch.value = ''
  await loadLogUdr()
}

const loadJenis = async (jenis: JenisUdr) => {
  const res = await useApi().get('/udr/master-isi-udr?jenisudr=' + jenis)
  treeSource[jenis] = res?.tree ?? []
  dataFlat[jenis] = res?.data ?? []
}

const reloadAll = async () => {
  await loadJenis('Mutu')
  await loadJenis('Teknik')
  await loadJenis('Admin')
}

const fillFromNode = async (node: any, jenis: JenisUdr) => {
  selectedJenis.value = jenis
  item.value = {
    statusenabled: true,
    aktif: true,
    pegawaiCvObj: null,
    alamaturlform: '',
  }
  item.value.kdrinciandokumenhead = null

  dataFlat[jenis].forEach((element: any) => {
    if (String(element.key) === String(node.parent_id)) {
      item.value.kdrinciandokumenhead = element
    }
  })

  item.value.id = node.key
  item.value.namaisidokumen = node.label
  item.value.nourut = node.nourut
  item.value.keterangan = node.data?.keterangan
  item.value.alamaturlform = node.data?.alamaturlform
  item.value.fileLama = node.data?.isidokumen
  item.value.nomorindukfk = node.data?.nomorindukfk
  item.value.nomorindukobj =
    d_NomorIndukLookup.value.find((x: any) => String(x.key) === String(node.data?.nomorindukfk)) ?? null

  const pegawaiId = node.data?.pegawaicvfk || parsePegawaiIdFromUrl(node.data?.alamaturlform || '')
  if (pegawaiId) {
    item.value.pegawaiCvObj =
      d_PegawaiCvLookup.value.find((x: any) => String(x.value) === String(pegawaiId)) ?? null
  } else {
    item.value.pegawaiCvObj = null
  }

  await loadRiwayat(node.key)
  modalIsiDokumen.value = true
  toast.success(node.label)
}

const onNodeSelect = async (node: any, jenis: JenisUdr) => {
  await fillFromNode(node, jenis)
}

const selectUploadFolder = (jenis: JenisUdr, node: any) => {
  selectedUploadFolder[jenis] = {
    key: node.key,
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
    pegawaiCvObj: null,
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
  const res = await useApi().get('/udr/nourut-udr?jenisudr=' + selectedJenis.value)
  item.value.nourut = res?.nourut ?? 1
}

const add = async (jenis: JenisUdr) => {
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

watch(
  () => item.value?.kdrinciandokumenhead,
  (head) => {
    const label = String(head?.label ?? '').trim().toLowerCase()
    const isCv = label.startsWith('cv -')

    if (isCv) {
      fileDokumenMutu.value = null
    } else {
      item.value.pegawaiCvObj = null
      item.value.alamaturlform = ''
    }
  },
  { deep: true },
)

watch(
  () => item.value?.pegawaiCvObj,
  (pegawai) => {
    if (isCvHeadSelected.value) {
      item.value.alamaturlform = buildCvUrl(pegawai?.value ?? '')
    }
  },
  { deep: true },
)

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
  H.printBlade(`udr/cetak-dokumen-udr?id=${id}`)
}

const cetakCvPegawai = () => {
  const pegawaiId =
    item.value?.pegawaiCvObj?.value || parsePegawaiIdFromUrl(item.value?.alamaturlform || '')

  if (!pegawaiId) {
    H.alert('warning', 'Pegawai belum dipilih')
    return
  }

  H.printBlade(buildCvUrl(pegawaiId))
}

const openFilePicker = (jenis: JenisUdr) => {
  if (!selectedUploadFolder[jenis]) {
    H.alert('warning', `Pilih target upload ${jenis} terlebih dahulu`)
    return
  }
  fileInputRefs[jenis]?.click()
}

const pushPendingFiles = (jenis: JenisUdr, files: File[]) => {
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

const onPickFiles = (jenis: JenisUdr, event: Event) => {
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

const onDragEnter = (jenis: JenisUdr) => {
  if (!selectedUploadFolder[jenis]) return
  dragState[jenis] = true
}

const onDragOver = (jenis: JenisUdr) => {
  if (!selectedUploadFolder[jenis]) return
  dragState[jenis] = true
}

const onDragLeave = (jenis: JenisUdr) => {
  dragState[jenis] = false
}

const onDrop = (jenis: JenisUdr, event: DragEvent) => {
  dragState[jenis] = false
  if (!selectedUploadFolder[jenis]) {
    H.alert('warning', `Pilih target upload ${jenis} terlebih dahulu`)
    return
  }

  const files = Array.from(event.dataTransfer?.files || [])
  if (!files.length) return
  pushPendingFiles(jenis, files)
}

const removePendingFile = (jenis: JenisUdr, index: number) => {
  uploadState[jenis].files.splice(index, 1)
}

const clearPendingFiles = (jenis: JenisUdr) => {
  uploadState[jenis].files = []
}

const uploadMulti = async (jenis: JenisUdr) => {
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
    const res = await useApi().post('/udr/upload-multi-udr', formData)
    toast.success(res?.message ?? `${uploadState[jenis].files.length} file berhasil diupload`)
    uploadState[jenis].files = []
    await loadJenis(jenis)
    await loadLogUdr()
  } catch (e: any) {
    H.alert('error', e?.response?.data?.message ?? `Upload massal ${jenis} gagal`)
  } finally {
    uploadState[jenis].loading = false
  }
}

const saveIsiUdr = async () => {
  if (!item.value.namaisidokumen) {
    H.alert('error', 'Nama Isi Dokumen harus di isi')
    return
  }
  if (!item.value.nourut) {
    H.alert('error', 'No Urut harus di isi')
    return
  }

  if (isCvHeadSelected.value && !hasExistingFile.value && !item.value.pegawaiCvObj?.value) {
    H.alert('error', 'Pegawai untuk link CV harus dipilih')
    return
  }

  const formData = new FormData()

  if (isCvHeadSelected.value && !hasExistingFile.value) {
    formData.append('alamaturlform', buildCvUrl(item.value.pegawaiCvObj?.value))
    formData.append('pegawaicvfk', item.value.pegawaiCvObj?.value ?? '')
    formData.append('namaFileLama', '')
  } else {
    if (isFile(fileDokumenMutu.value)) {
      formData.append('fileDokumenMutu', fileDokumenMutu.value)
    } else if (item.value.fileLama) {
      formData.append('namaFileLama', item.value.fileLama)
    }
    formData.append('alamaturlform', item.value.alamaturlform ?? '')
    formData.append('pegawaicvfk', item.value.pegawaiCvObj?.value ?? '')
  }

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
    await useApi().post('/udr/save-isi-udr', formData)
    await reloadAll()
    await loadLogUdr()
    clear()
    await setNoUrut()
    modalIsiDokumen.value = false
  } catch (e) {
  } finally {
    isLoading.value = false
  }
}

const deleteIsiUdr = async (itemx: any) => {
  isLoading.value = true
  try {
    await useApi().post('/udr/hapus-isi-udr', { id: itemx.id })
    await reloadAll()
    await loadLogUdr()
    clear()
    await setNoUrut()
    modalIsiDokumen.value = false
  } finally {
    isLoading.value = false
  }
}

const pad2 = (n: any) => String(n ?? 1).padStart(2, '0')

await loadPegawaiCvLookup()
await loadNomorIndukLookup()
await reloadAll()
await loadLogUdr()
await openUdrFromQuery()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';

.udr-page-title {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(90deg, rgba(33, 150, 243, 0.14), rgba(255, 255, 255, 0));
  border: 1px solid rgba(33, 150, 243, 0.18);
}

@media only screen and (max-width: 600px) {
  .udr-page-title {
    align-items: flex-start;
    flex-direction: column;
  }
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
}

.udr-node-upload-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  border: 1px solid rgba(33, 150, 243, .22);
  background: #fff;
  color: var(--primary);
  border-radius: 999px;
  padding: 4px 10px;
  font-size: .76rem;
  font-weight: 600;
  cursor: pointer;
  transition: .2s ease;
}

.udr-node-upload-btn:hover {
  background: rgba(33, 150, 243, .06);
  transform: translateY(-1px);
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
  background: rgba(255, 255, 255, .75);
  transition: .2s ease;
}

.udr-dropzone.is-dragover {
  border-color: rgba(76, 175, 80, .55);
  background: rgba(76, 175, 80, .08);
  transform: translateY(-1px);
}

.udr-dropzone.is-disabled {
  opacity: .65;
}

.udr-dropzone-inner {
  width: 100%;
  text-align: center;
  padding: 24px 16px;
}

.udr-dropzone-icon {
  font-size: 2rem;
  line-height: 1;
}

.udr-dropzone-title {
  margin-top: 10px;
  font-size: 1rem;
  font-weight: 700;
  color: var(--dark-text);
}

.udr-dropzone-sub {
  margin-top: 4px;
  color: var(--light-text);
  font-size: .88rem;
}

.udr-pending-list {
  border: 1px solid rgba(0, 0, 0, .08);
  border-radius: 14px;
  overflow: hidden;
  background: rgba(255, 255, 255, .8);
}

.udr-pending-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  background: rgba(33, 150, 243, .06);
  font-weight: 700;
  color: var(--dark-text);
}

.udr-pending-items {
  display: flex;
  flex-direction: column;
}

.udr-pending-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 10px 12px;
  border-top: 1px solid rgba(0, 0, 0, .06);
}

.udr-pending-main {
  min-width: 0;
  flex: 1;
}

.udr-pending-name {
  font-weight: 600;
  color: var(--dark-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.udr-pending-size {
  margin-top: 2px;
  font-size: .82rem;
  color: var(--light-text);
}

.udr-log-card {
  margin-top: 14px;
}

.udr-log-head {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.udr-log-sub {
  font-size: .9rem;
  color: var(--light-text);
}

.udr-log-list {
  margin-top: 12px;
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.udr-log-item {
  display: flex;
  gap: 12px;
  padding: 10px 12px;
  border-radius: 12px;
  border: 1px solid rgba(0, 0, 0, .06);
  background: rgba(255, 255, 255, .7);
}

.udr-log-left {
  display: flex;
  align-items: flex-start;
}

.udr-log-badge {
  min-width: 34px;
  height: 28px;
  border-radius: 999px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  font-size: .85rem;
  color: var(--dark-text);
  background: rgba(33, 150, 243, .12);
  border: 1px solid rgba(33, 150, 243, .18);
}

.udr-log-main {
  flex: 1;
  min-width: 0;
}

.udr-log-title {
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
}

.udr-log-doc {
  font-weight: 700;
  color: var(--dark-text);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 520px;
}

.udr-log-pill {
  display: inline-flex;
  align-items: center;
  padding: 2px 10px;
  border-radius: 999px;
  font-size: .78rem;
  font-weight: 600;
  color: var(--dark-text);
  background: rgba(0, 0, 0, .05);
}

.udr-log-pill.is-rev {
  background: rgba(76, 175, 80, .12);
  border: 1px solid rgba(76, 175, 80, .18);
}

.udr-log-pill.is-aksi {
  background: rgba(255, 152, 0, .12);
  border: 1px solid rgba(255, 152, 0, .18);
}

.udr-log-meta {
  margin-top: 4px;
  display: flex;
  align-items: center;
  flex-wrap: wrap;
  gap: 8px;
  font-size: .88rem;
  color: var(--light-text);
}

.udr-log-meta-item {
  display: inline-flex;
  align-items: center;
  gap: 6px;
}

.udr-log-meta-sep {
  opacity: .6;
}

.udr-log-empty {
  margin-top: 10px;
  padding: 14px;
  border-radius: 12px;
  border: 1px dashed rgba(0, 0, 0, .12);
  color: var(--light-text);
}

.custom-tree .tree-node-label.is-opened-from-query {
  border: 2px solid #22c55e;
  background: #ecfdf5;
  border-radius: 12px;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
  transition: all 0.3s ease;
}

.custom-tree .p-treenode-content:has(.is-opened-from-query) {
  background: transparent !important;
}

.custom-tree .tree-node-label {
  transition: all 0.25s ease;
}

.custom-tree .tree-node-label.is-opened-from-query {
  border: 2px solid #22c55e;
  background: #ecfdf5;
  border-radius: 12px;
  box-shadow: 0 0 0 4px rgba(34, 197, 94, 0.12);
  transition: all 0.3s ease;
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
