<template>
  <div class="column">
    <div class="business-dashboard hr-dashboard">
      <div class="columns is-multiline">
        <div class="column is-12 p-0">
          <div class="block-header">
            <div class="left column is-3 p-0">
              <div class="current-user">
                <h3>{{ item.namaproduk }}</h3>
              </div>
            </div>

            <div class="Center column is-3 p-0">
              <div>
                <div>
                  <h4 class="block-heading">Merk</h4>
                  <p class="block-hext">{{ item.namamerk }}</p>

                  <h4 class="block-heading">Tipe</h4>
                  <p class="block-hext">{{ item.namatipe }}</p>
                </div>
              </div>
            </div>

            <div class="right column is-6 p-0">
              <div>
                <div>
                  <h4 class="block-heading">S/N</h4>
                  <p class="block-hext">{{ item.namaserialnumber }}</p>

                  <h4 class="block-heading">No Order</h4>
                  <p class="block-hext">{{ item.noorderalat }}</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="column is-12">
    <TabView class="tabview-custom" :scrollable="true" @tab-click="klikTab($event)">
      <TabPanel>
        <template #header>
          <i class="fas fa-tools mr-2" aria-hidden="true"></i>
          <span>Laporan Repair</span>
          <Badge :value="totalData" v-if="totalData > 0" severity="danger" class="ml-2" />
        </template>

        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Laporan Alat</h3>
              </div>
            </div>

            <div class="alat-report-grid">
              <div class="alat-report-copy">
                <span class="section-eyebrow">Keterangan Alat</span>
                <div class="alat-keterangan">
                  {{ item.keterangan || 'Belum ada keterangan dari kajian ulang.' }}
                </div>
              </div>

              <div class="alat-photo-panel">
                <img v-if="MARKINGSITE" :src="MARKINGSITE" alt="Foto alat repair" @error="onMarkingImageError" />
                <div v-else class="alat-photo-placeholder">
                  Foto alat belum tersedia
                </div>
              </div>
            </div>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Status Alat Repair</h3>
              </div>
            </div>

            <div v-if="statusRepair" style="text-align:center; margin: .25rem 0 1rem;">
              <span class="tag is-medium" :class="isRepairBerhasil ? 'is-success' : 'is-danger'">
                {{ statusRepair }}
              </span>
            </div>

            <DataTable :value="dataSourceHasilLaporanRepairStatus" dataKey="norec" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="columns is-multiline is-vcentered is-mobile pt-0 pb-0" style="gap: 0.25rem;">
                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButtons style="justify-content: space-between;">
                      <VButton color="primary" @click="cetakLaporanRepair()" outlined icon="feather:printer">
                        Cetak Laporan
                      </VButton>
                    </VButtons>
                  </div>

                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButton color="info" @click="setujuiLaporan()" outlined icon="feather:save"
                      :loading="isLoadingSave">
                      Setujui Laporan
                    </VButton>
                  </div>

                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButton color="danger" outlined icon="feather:trash" @click="tolakLaporan()">
                      Tolak Laporan
                    </VButton>
                  </div>
                </div>
              </template>

              <Column field="no" header="No" />
              <Column header="Bagian Alat">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.bagianalat)"></div>
                </template>
              </Column>
              <Column header="Kondisi">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.kondisi)"></div>
                </template>
              </Column>

              <Column header="Dokumentasi" style="text-align:center; min-width: 150px">
                <template #body="slotProps">
                  <div v-if="getRowFotos(slotProps.data).length > 0" class="saved-photo-grid">
                    <div v-for="foto in getRowFotos(slotProps.data)" :key="foto.norec || foto.namafile"
                      class="saved-photo-item">
                      <img :src="getFotoUrl(foto.namafile)" class="saved-photo-img" @error="onImageError" />

                      <div class="saved-photo-caption">
                        {{ foto.keterangan_gambar || '-' }}
                      </div>
                    </div>
                  </div>

                  <span v-else class="text-muted">Tidak ada dokumentasi</span>
                </template>
              </Column>

              <Column field="created_at" header="Tanggal Isi Status" />
              <Column field="petugasisistatus" header="Pengisi" />
            </DataTable>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Tindakan Teknis Repair</h3>
              </div>
            </div>

            <div v-if="statusRepair" style="text-align:center; margin: .25rem 0 1rem;">
              <span class="tag is-medium" :class="isRepairBerhasil ? 'is-success' : 'is-danger'">
                {{ statusRepair }}
              </span>
            </div>

            <DataTable :value="dataSourceHasilLaporanRepair" dataKey="norec" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="columns is-multiline is-vcentered is-mobile pt-0 pb-0" style="gap: 0.25rem;">
                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButtons style="justify-content: space-between;">
                      <VButton color="primary" @click="cetakLaporanRepair()" outlined icon="feather:printer">
                        Cetak Laporan
                      </VButton>
                    </VButtons>
                  </div>

                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButton color="info" @click="setujuiLaporan()" outlined icon="feather:save"
                      :loading="isLoadingSave">
                      Setujui Laporan
                    </VButton>
                  </div>

                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButton color="danger" outlined icon="feather:trash" @click="tolakLaporan()">
                      Tolak Laporan
                    </VButton>
                  </div>
                </div>
              </template>

              <Column field="no" header="No" />
              <Column header="Bagian Alat">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.bagianalatlaporan)"></div>
                </template>
              </Column>
              <Column header="Penanganan">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.penanganan)"></div>
                </template>
              </Column>
              <Column header="Status">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.status)"></div>
                </template>
              </Column>
              <Column header="Sparepart">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.sparepart)"></div>
                </template>
              </Column>

              <Column header="Foto Repair" style="text-align:center; min-width: 150px">
                <template #body="slotProps">
                  <div v-if="getRowFotos(slotProps.data).length > 0" class="saved-photo-grid">
                    <div v-for="foto in getRowFotos(slotProps.data)" :key="foto.norec || foto.namafile"
                      class="saved-photo-item">
                      <img :src="getFotoUrl(foto.namafile)" class="saved-photo-img" @error="onImageError" />

                      <div class="saved-photo-caption">
                        {{ foto.keterangan_gambar || '-' }}
                      </div>
                    </div>
                  </div>

                  <span v-else class="text-muted">Tidak ada dokumentasi</span>
                </template>
              </Column>

              <Column field="created_at" header="Tanggal Isi" />
              <Column field="petugasrepair" header="Pengisi" />
            </DataTable>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="column is-12">
            <div class="columns is-multiline" style="text-align:center">
              <div class="column is-12">
                <h3 class="title is-5 mb-2 mr-1">Data Hasil Repair</h3>
              </div>
            </div>

            <div v-if="statusRepair" style="text-align:center; margin: .25rem 0 1rem;">
              <span class="tag is-medium" :class="isRepairBerhasil ? 'is-success' : 'is-danger'">
                {{ statusRepair }}
              </span>
            </div>

            <DataTable :value="dataSourceHasilRepair" dataKey="norec" :paginator="true" :rows="10"
              :rowsPerPageOptions="[5, 10, 25]" class="p-datatable-customers p-datatable-sm" filterDisplay="menu"
              paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
              responsiveLayout="stack" breakpoint="960px" sortMode="multiple" showGridlines
              currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" :loading="isLoading">
              <template #header>
                <div class="columns is-multiline is-vcentered is-mobile pt-0 pb-0" style="gap: 0.25rem;">
                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButtons style="justify-content: space-between;">
                      <VButton color="primary" @click="cetakLaporanRepair()" outlined icon="feather:printer">
                        Cetak Laporan
                      </VButton>
                    </VButtons>
                  </div>

                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButton color="info" @click="setujuiLaporan()" outlined icon="feather:save"
                      :loading="isLoadingSave">
                      Setujui Laporan
                    </VButton>
                  </div>

                  <div class="column is-narrow" v-if="hasPrintableData">
                    <VButton color="danger" outlined icon="feather:trash" @click="tolakLaporan()">
                      Tolak Laporan
                    </VButton>
                  </div>
                </div>
              </template>

              <Column field="no" header="No" />
              <Column header="Hasil Repair">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.hasil)"></div>
                </template>
              </Column>
              <Column header="Status">
                <template #body="slotProps">
                  <div class="rich-preview" v-html="safeDisplayHtml(slotProps.data.status)"></div>
                </template>
              </Column>

              <Column header="Dokumentasi" style="text-align:center; min-width: 150px">
                <template #body="slotProps">
                  <div v-if="getRowFotos(slotProps.data).length > 0" class="saved-photo-grid">
                    <div v-for="foto in getRowFotos(slotProps.data)" :key="foto.norec || foto.namafile"
                      class="saved-photo-item">
                      <img :src="getFotoUrl(foto.namafile)" class="saved-photo-img" @error="onImageError" />

                      <div class="saved-photo-caption">
                        {{ foto.keterangan_gambar || '-' }}
                      </div>
                    </div>
                  </div>

                  <span v-else class="text-muted">Tidak ada dokumentasi</span>
                </template>
              </Column>

              <Column field="created_at" header="Tanggal Isi" />
              <Column field="petugashasilrepair" header="Pengisi" />
            </DataTable>
          </div>
        </VCard>

        <hr />

        <VCard>
          <div class="columns is-multiline" style="text-align:center">
            <div class="column is-12">
              <h3 class="title is-5 mb-2 mr-1">Kesimpulan</h3>
            </div>
          </div>

          <div class="columns is-multiline">
            <div class="column is-12">
              <div class="rich-preview rich-preview-card" v-html="safeDisplayHtml(item.kesimpulan)"></div>
            </div>
          </div>
        </VCard>
      </TabPanel>
    </TabView>
  </div>

  <VModal :open="modalPenolakanLapoaran" title="Tolak Laporan Repair" size="medium" actions="right"
    @close="modalPenolakanLapoaran = false" cancelLabel="Tutup">
    <template #content>
      <div class="columns is-multiline">
        <div class="column is-12">
          <span style="margin-bottom:1rem;font-weight: bold; font-size: 12px; font-family: var(--font-alt);">
            Keterangan Penolakan
          </span>

          <VField>
            <VControl>
              <VTextarea class="textarea is-rounded" v-model="item.alasanpenolakan" rows="4"
                placeholder="Keterangan Penolakan" autocomplete="off" autocapitalize="off" spellcheck="true" />
            </VControl>
          </VField>
        </div>
      </div>
    </template>

    <template #action>
      <VButton icon="feather:plus" color="primary" @click="savePenolakanLaporan" :loading="isLoadingSave" raised>
        Simpan
      </VButton>
    </template>
  </VModal>
</template>

<script setup lang="ts">
import { useRoute, useRouter } from 'vue-router'
import { ref, computed } from 'vue'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import { useApi } from '/@src/composable/useApi'
import TabView from 'primevue/tabview'
import TabPanel from 'primevue/tabpanel'

useHead({
  title: 'Laporan Repair - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(true)

const MARKINGSITE: any = ref('')
const MARKING_CANDIDATES = ref<string[]>([])
const markingCandidateIndex = ref(0)
const NOREC_DETAIL = useRoute().query.norec_detail as string
const WEB_BASE_URL = ''

const isLoading = ref(false)
const isLoadingSave = ref(false)
const loadSearch: any = ref(false)
const router = useRouter()

const dataSourceHasilLaporanRepair: any = ref([])
const dataSourceHasilLaporanRepairStatus: any = ref([])
const dataSourceHasilRepair: any = ref([])

const statusRepair = ref<string>('')
const statusRepairFk = ref<any>(null)
const modalPenolakanLapoaran: any = ref(false)

const item: any = ref({
  tglkalibrasi: new Date(),
  namaproduk: '',
  namamerk: '',
  namatipe: '',
  namaserialnumber: '',
  noorderalat: '',
  keterangan: '',
  namafile: '',
  kesimpulan: '',
  alasanpenolakan: '',
  norecregis: '',
  detailLaporanRepair: [{ no: 1 }],
})

const klikTab = (_e: any) => { }

const stripHtmlToText = (value: any) => {
  if (value === null || value === undefined) return ''

  const raw = String(value)

  if (typeof window === 'undefined' || !raw.includes('<')) {
    return raw.replace(/&nbsp;/gi, ' ').trim()
  }

  const doc = new DOMParser().parseFromString(raw, 'text/html')
  return (doc.body.textContent || '').replace(/\u00a0/g, ' ').trim()
}

const escapeHtml = (value: any) => {
  return String(value ?? '')
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
    .replace(/'/g, '&#039;')
}

const safeDisplayHtml = (value: any) => {
  const raw = String(value ?? '').trim()

  if (!raw) return '<span class="rich-empty">-</span>'

  if (typeof window === 'undefined' || !raw.includes('<')) {
    return escapeHtml(raw).replace(/\n/g, '<br>') || '<span class="rich-empty">-</span>'
  }

  const allowedTags = ['P', 'BR', 'STRONG', 'B', 'EM', 'I', 'U', 'UL', 'OL', 'LI', 'BLOCKQUOTE', 'H2', 'H3', 'H4']
  const doc = new DOMParser().parseFromString(raw, 'text/html')

  const walk = (node: Node) => {
    Array.from(node.childNodes).forEach((child) => {
      if (child.nodeType !== Node.ELEMENT_NODE) return

      const element = child as HTMLElement

      if (!allowedTags.includes(element.tagName)) {
        element.replaceWith(...Array.from(element.childNodes))
        return
      }

      Array.from(element.attributes).forEach((attribute) => element.removeAttribute(attribute.name))
      walk(element)
    })
  }

  walk(doc.body)

  const html = doc.body.innerHTML.trim()
  return stripHtmlToText(html) ? html : '<span class="rich-empty">-</span>'
}

const buildMitraFileCandidates = (files: any) => {
  const list = Array.isArray(files) ? files : [files]
  const candidates: string[] = []

  list
    .filter((file) => file !== null && file !== undefined && String(file).trim() !== '')
    .forEach((file) => {
      const value = String(file).trim()

      if (value.startsWith('http://') || value.startsWith('https://')) {
        candidates.push(value)
        return
      }

      const filename = value.split('/').pop()
      if (!filename) return

      candidates.push(
        `${WEB_BASE_URL}/berkas-mitra/${filename}`,
        `${WEB_BASE_URL}/storage/berkas-mitra/${filename}`,
        `${WEB_BASE_URL}/storage/${filename}`,
        `${WEB_BASE_URL}/berkas-laporan-repair/${filename}`
      )
    })

  return [...new Set(candidates)]
}

const setMarkingCandidates = (files: any) => {
  MARKING_CANDIDATES.value = buildMitraFileCandidates(files)
  markingCandidateIndex.value = 0
  MARKINGSITE.value = MARKING_CANDIDATES.value[0] || ''
}

const onMarkingImageError = () => {
  markingCandidateIndex.value += 1
  MARKINGSITE.value = MARKING_CANDIDATES.value[markingCandidateIndex.value] || ''
}

const isRepairBerhasil = computed(() => {
  const s = (statusRepair.value || '').toLowerCase()
  return s.includes('berhasil') && !s.includes('tidak')
})

const totalData = computed(() => {
  return (
    (dataSourceHasilLaporanRepair.value?.length || 0) +
    (dataSourceHasilLaporanRepairStatus.value?.length || 0) +
    (dataSourceHasilRepair.value?.length || 0)
  )
})

const hasPrintableData = computed(() => {
  return totalData.value > 0
})

const getFotoUrl = (filename: string) => {
  if (!filename) {
    return ''
  }

  const file = String(filename)

  if (file.startsWith('http://') || file.startsWith('https://')) {
    return file
  }

  if (file.startsWith('/')) {
    return WEB_BASE_URL + file
  }

  return WEB_BASE_URL + '/berkas-laporan-repair/' + file
}

const onImageError = (event: Event) => {
  const img = event.target as HTMLImageElement
  img.style.display = 'none'

  const parent = img.parentElement

  if (parent && !parent.querySelector('.image-error-text')) {
    const errorText = document.createElement('div')
    errorText.className = 'image-error-text'
    errorText.innerText = 'Gambar tidak ditemukan'
    parent.insertBefore(errorText, parent.firstChild)
  }
}

const getRowFotos = (row: any) => {
  if (row?.fotos && Array.isArray(row.fotos) && row.fotos.length > 0) {
    return row.fotos
  }

  if (row?.fotoalatstatus) {
    return [
      {
        namafile: row.fotoalatstatus,
        keterangan_gambar: '',
      },
    ]
  }

  if (row?.fotoalatrepair) {
    return [
      {
        namafile: row.fotoalatrepair,
        keterangan_gambar: '',
      },
    ]
  }

  if (row?.fotohasilrepair) {
    return [
      {
        namafile: row.fotohasilrepair,
        keterangan_gambar: '',
      },
    ]
  }

  return []
}

const getNorecReg = () => {
  return (
    dataSourceHasilLaporanRepair.value?.[0]?.norecregis ||
    dataSourceHasilLaporanRepairStatus.value?.[0]?.norecregis ||
    dataSourceHasilRepair.value?.[0]?.norecregis ||
    ''
  )
}

const tolakLaporan = async () => {
  const norecReg = getNorecReg()

  if (!norecReg) {
    H.alert('warning', 'Data laporan belum tersedia untuk ditolak')
    return
  }

  item.value.norecregis = norecReg
  item.value.alasanpenolakan = ''
  modalPenolakanLapoaran.value = true
}

const savePenolakanLaporan = async () => {
  if (!item.value.alasanpenolakan) {
    H.alert('warning', 'Alasan Penolakan harus di isi')
    return
  }

  const json = {
    itemtolak: {
      norecregis: item.value.norecregis,
      alasanpenolakan: item.value.alasanpenolakan,
    },
  }

  isLoadingSave.value = true

  try {
    await useApi().post(`/manager/save-penolakan-laporan-repair`, json)
    H.alert('success', 'Penolakan laporan berhasil disimpan')
    modalPenolakanLapoaran.value = false
    toDashboard()
  } catch (e: any) {
    console.error(e)
    H.alert('error', 'Gagal menyimpan penolakan laporan')
  } finally {
    isLoadingSave.value = false
  }
}

const setujuiLaporan = async () => {
  const json = {
    verif: {
      norec: NOREC_DETAIL,
    },
  }

  isLoadingSave.value = true

  try {
    await useApi().post('/manager/save-setujui-laporan-repair', json)
    H.alert('success', 'Laporan repair berhasil disetujui')
    toDashboard()
  } catch (error: any) {
    console.error('Error saat menyimpan', error)

    if (error.response) {
      H.alert(
        'error',
        `Kesalahan: ${error.response.status} - ${error.response.data.message || 'Gagal menyetujui laporan'}`
      )
    } else if (error.request) {
      H.alert('error', 'Tidak ada respons dari server. Silakan coba lagi.')
    } else {
      H.alert('error', `Terjadi kesalahan: ${error.message}`)
    }
  } finally {
    isLoadingSave.value = false
  }
}

const toDashboard = () => {
  router.push({
    name: 'module-dashboard-manager',
  })
}

const fetchData = async () => {
  loadSearch.value = true
  isLoading.value = true

  try {
    const response: any = await useApi().get(`manager/get-laporan-repair?norecdetail=${NOREC_DETAIL}`)

    const repair = response.data || []
    const status = response.datastatus || []
    const hasil = response.datahasil || []

    repair.forEach((element: any, i: any) => {
      element.no = i + 1
    })

    status.forEach((element: any, i: any) => {
      element.no = i + 1
    })

    hasil.forEach((element: any, i: any) => {
      element.no = i + 1
    })

    dataSourceHasilLaporanRepair.value = repair
    dataSourceHasilLaporanRepairStatus.value = status
    dataSourceHasilRepair.value = hasil

    const detail = response.detail || {}

    statusRepair.value =
      detail?.statusrepair ||
      response?.statusrepair ||
      response?.data?.[0]?.statusrepair ||
      response?.datastatus?.[0]?.statusrepair ||
      response?.datahasil?.[0]?.statusrepair ||
      ''

    statusRepairFk.value =
      detail?.statusrepairfk ||
      response?.statusrepairfk ||
      response?.data?.[0]?.statusrepairfk ||
      response?.datastatus?.[0]?.statusrepairfk ||
      response?.datahasil?.[0]?.statusrepairfk ||
      null

    item.value.kesimpulan =
      detail?.kesimpulanrepair ||
      response?.data?.[0]?.kesimpulanrepair ||
      response?.datastatus?.[0]?.kesimpulanrepair ||
      response?.datahasil?.[0]?.kesimpulanrepair ||
      item.value.kesimpulan ||
      ''
  } catch (err) {
    console.error(err)
    dataSourceHasilLaporanRepair.value = []
    dataSourceHasilLaporanRepairStatus.value = []
    dataSourceHasilRepair.value = []
    statusRepair.value = ''
    statusRepairFk.value = null
  } finally {
    isLoading.value = false
    loadSearch.value = false
  }
}

const detailOrder = async () => {
  try {
    const response = await useApi().get(`/manager/detail-alat-repair?norec_pd=${NOREC_DETAIL}`)
    const data = response.data

    if (!data) {
      setMarkingCandidates([])
      return
    }

    item.value.namaproduk = data.namaproduk
    item.value.namamerk = data.namamerk
    item.value.namatipe = data.namatipe
    item.value.namaserialnumber = data.namaserialnumber
    item.value.noorderalat = data.noorderalat
    item.value.keterangan = data.keterangan || ''
    item.value.namafile = data.namafile
    item.value.kesimpulan = data.kesimpulanrepair || item.value.kesimpulan

    const fotoFiles = Array.isArray(data.foto_files) && data.foto_files.length > 0
      ? data.foto_files
      : [data.namafile]

    setMarkingCandidates(fotoFiles)
  } catch (err) {
    console.error(err)
  }
}

const cetakLaporanRepair = () => {
  const norecReg = getNorecReg()

  if (!norecReg) {
    H.alert('warning', 'Data laporan belum tersedia untuk dicetak')
    return
  }

  H.printBlade(`manager/cetak-laporan-repair?pdf=true&norec=${norecReg}&norec_detail=${NOREC_DETAIL}`)
}

detailOrder()
fetchData()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/dashboard/bedah.scss';

.tabs-wrapper.is-slider .tabs,
.tabs-wrapper-alt.is-slider .tabs {
  position: relative;
  background: var(--fade-grey-light-2);
  border: 1px solid var(--fade-grey);
  max-width: 400px;
  height: 35px;
  border-bottom: none;
}

.tb-order .text-value {
  font-family: var(--font-alt);
  color: var(--dark-text);
  font-weight: 400;
  font-size: 12px;
}

.text-muted {
  font-size: 12px;
  color: var(--light-text);
  font-style: italic;
}

.alat-report-grid {
  display: grid;
  grid-template-columns: minmax(280px, 0.85fr) minmax(320px, 1.15fr);
  gap: 22px;
  align-items: stretch;
  margin-bottom: 18px;
}

.alat-report-copy,
.alat-photo-panel {
  min-height: 260px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 12px;
  background: var(--white);
  box-shadow: var(--light-box-shadow);
}

.alat-report-copy {
  padding: 22px;
}

.section-eyebrow {
  display: block;
  margin-bottom: 10px;
  color: var(--primary);
  font-family: var(--font-alt);
  font-size: 0.78rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
}

.alat-keterangan {
  color: var(--dark-text);
  font-family: var(--font);
  font-size: 1rem;
  line-height: 1.7;
  white-space: pre-wrap;
}

.alat-photo-panel {
  display: flex;
  align-items: center;
  justify-content: center;
  overflow: hidden;
  padding: 14px;
  background: #f8fbff;
}

.alat-photo-panel img {
  width: 100%;
  max-height: 380px;
  object-fit: contain;
}

.alat-photo-placeholder {
  display: flex;
  width: 100%;
  min-height: 220px;
  align-items: center;
  justify-content: center;
  border: 1px dashed var(--fade-grey-dark-4);
  border-radius: 10px;
  color: var(--light-text);
  font-family: var(--font-alt);
  font-weight: 600;
}

.rich-preview {
  color: var(--dark-text);
  font-size: 0.9rem;
  line-height: 1.5;
  min-width: 140px;
}

.rich-preview p {
  margin: 0 0 0.45rem;
}

.rich-preview ul,
.rich-preview ol {
  margin: 0 0 0.45rem 1.1rem;
}

.rich-preview blockquote {
  margin: 0 0 0.45rem;
  padding-left: 0.75rem;
  border-left: 3px solid var(--primary);
  color: var(--light-text);
}

.rich-preview-card {
  padding: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
  background: var(--white);
}

.rich-empty {
  color: var(--light-text);
  font-style: italic;
}

@media (max-width: 960px) {
  .alat-report-grid {
    grid-template-columns: 1fr;
  }
}

.saved-photo-grid {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
  justify-content: center;
}

.saved-photo-item {
  width: 180px;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 8px;
  padding: 6px;
  background: var(--white);
}

.saved-photo-img {
  width: 100%;
  height: 130px;
  object-fit: contain;
  border-radius: 6px;
  background: #f8f8f8;
}

.saved-photo-caption {
  font-size: 12px;
  margin-top: 5px;
  line-height: 1.3;
  text-align: center;
}

.image-error-text {
  min-height: 130px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 12px;
  color: var(--light-text);
  font-style: italic;
  background: #f8f8f8;
  border-radius: 6px;
  text-align: center;
  padding: 8px;
}

.user-grid-v2 {
  .columns {
    margin-left: -0.5rem !important;
    margin-right: -0.5rem !important;
    margin-top: -0.5rem !important;
  }

  .column {
    padding: 0.5rem !important;
  }

  .grid-item {
    @include vuero-s-card;

    text-align: center;

    >.v-avatar {
      display: block;
      margin: 0 auto 4px;
    }

    h3 {
      font-family: var(--font-alt);
      font-size: 0.95rem;
      font-weight: 600;
      color: var(--dark-text);
    }

    p {
      font-size: 0.85rem;
    }

    .people {
      display: flex;
      justify-content: center;
      padding: 8px 0 30px;

      .v-avatar {
        margin: 0 4px;
      }
    }

    .buttons {
      display: flex;
      justify-content: space-between;

      .button {
        width: calc(50% - 4px);
        color: var(--light-text);

        &:hover,
        &:focus {
          border-color: var(--fade-grey-dark-4);
          color: var(--primary);
          box-shadow: var(--light-box-shadow);
        }
      }
    }
  }

  .grid-item-wrap {
    border: 1px solid var(--fade-grey-dark-3);
    border-radius: var(--radius-large);
    transition: all 0.3s;

    .grid-item-head {
      background: #fafafa;
      border-radius: var(--radius-large) 6px 0 0;
      padding: 20px;

      .flex-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 12px;

        .meta {
          span {
            display: flex;

            &:first-child {
              font-family: var(--font-alt);
              font-weight: 600;
              font-size: 0.85rem;
              color: white;
            }

            &:nth-child(2) {
              font-size: 0.8rem;
              color: white;
            }
          }
        }

        .status-icon {
          height: 28px;
          width: 28px;
          min-width: 28px;
          border-radius: var(--radius-rounded);
          border: 1px solid var(--fade-grey-dark-3);
          display: flex;
          align-items: center;
          justify-content: center;

          &.is-success {
            background: var(--success);
            border-color: var(--success);
            color: var(--white);
          }

          &.is-warning {
            background: var(--orange);
            border-color: var(--orange);
            color: var(--white);
          }

          &.is-danger {
            background: var(--danger);
            border-color: var(--danger);
            color: var(--white);
          }

          i {
            font-size: 8px;
          }
        }
      }

      .buttons {
        display: flex;
        justify-content: space-between;
        margin-bottom: 0;

        .button,
        .v-button {
          width: calc(50% - 4px);
          color: var(--light-text);
          margin-bottom: 0;

          &:hover,
          &:focus {
            border-color: var(--fade-grey-dark-4);
            color: var(--primary);
            box-shadow: var(--light-box-shadow);
          }
        }
      }
    }

    .grid-item {
      border-top-left-radius: 0;
      border-top-right-radius: 0;
      border: none;
    }
  }
}

.is-dark {
  .user-grid {
    .grid-item {
      @include vuero-card--dark;
    }
  }

  .user-grid-v2 {
    .grid-item-wrap {
      border-color: var(--dark-sidebar-light-12);

      .grid-item-head {
        background: var(--dark-sidebar-light-4);
      }
    }
  }

  .saved-photo-item {
    background: var(--dark-sidebar-light-4);
    border-color: var(--dark-sidebar-light-12);
  }

  .saved-photo-img,
  .image-error-text {
    background: var(--dark-sidebar-light-2);
  }
}

.user-grid-v2 .grid-item-wrap .grid-item-head.is-registrasi {
  background: var(--success) !important;
}

.user-grid-v2 .grid-item-wrap .grid-item-head {
  padding: 10px;
}

.search-menu-rad {
  height: 56px !important;
  white-space: nowrap;
  display: flex;
  flex-shrink: 0;
  align-items: center;
  background-color: white;
  border-radius: 8px;
  width: 100%;
  padding-left: 0.75rem;

  >div:not(:last-of-type) {
    border-right: 1px solid var(--search-border-color);
  }

  .search-bar {
    height: 55px;
    width: 100%;
    position: relative;
    display: flex;
    align-items: center;
    padding-right: 1.5rem;

    .field {
      width: 100%;
    }

    .multiselect-tags {
      padding-left: 2.5rem;
    }
  }

  .search-location-rad,
  .search-job,
  .search-salary {
    display: flex;
    align-items: center;
    width: 50%;
    font-size: 14px;
    font-weight: 500;
    padding: 0 25px;
    height: 100%;
    font-family: var(--font);

    input {
      width: 100%;
      height: 90%;
      display: block;
      font-family: var(--font);
      color: var(--input-color);
      background-color: transparent;
      border: none;
    }

    svg {
      margin-right: 0.5rem;
      width: 18px;
      color: var(--primary);
      flex-shrink: 0;
    }
  }

  .search-button-rad {
    background-color: var(--primary);
    min-width: 100px;
    height: 56px !important;
    border: none;
    font-weight: 500;
    font-family: var(--font);
    padding: 0 1rem;
    border-radius: 0 0.75rem 0.75rem 0;
    color: white;
    cursor: pointer;
    margin-left: auto;
  }
}

.search-widget {
  flex: 1;
  display: inline-block;
  width: 100%;
  padding: 10px;
  background-color: var(--white);
  border-radius: 16px;
  border: 1px solid var(--fade-grey-dark-3);
  transition: all 0.3s;
}
</style>
