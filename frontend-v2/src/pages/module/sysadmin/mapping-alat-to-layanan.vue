<template>
  <div class="page-content-inner">
    <div class="form-body mt-5">
      <div class="columns is-multiline">
        <div class="column is-12">
          <div class="service-hero">
            <div>
              <span class="eyebrow">MASTER KATALOG LAYANAN</span>
              <h1>Mapping Alat ke Layanan Laboratorium</h1>
              <p>Kelola layanan KAN dan Non KAN, merk/tipe alat, gambar, serta status tampil layanan.</p>
            </div>
            <VButton color="primary" icon="feather:plus" raised @click="resetForm">
              Tambah Layanan
            </VButton>
          </div>
        </div>

        <div class="column is-4">
          <div class="summary-card is-total">
            <span class="summary-icon"><i class="iconify" data-icon="feather:grid"></i></span>
            <div><strong>{{ summary.total }}</strong><small>Total Layanan</small></div>
          </div>
        </div>
        <div class="column is-4">
          <div class="summary-card is-active">
            <span class="summary-icon"><i class="iconify" data-icon="feather:check-circle"></i></span>
            <div><strong>{{ summary.aktif }}</strong><small>Layanan Aktif</small></div>
          </div>
        </div>
        <div class="column is-4">
          <div class="summary-card is-inactive">
            <span class="summary-icon"><i class="iconify" data-icon="feather:slash"></i></span>
            <div><strong>{{ summary.nonaktif }}</strong><small>Layanan Nonaktif</small></div>
          </div>
        </div>

        <div class="column is-8">
          <VCard class="h-100">
            <div class="card-header-flex mb-4">
              <div>
                <h4 class="title is-5 mb-1">Daftar Layanan</h4>
                <p class="is-size-7 has-text-grey">Data awal diimpor dari Layanan_lab.xlsx.</p>
              </div>
              <span class="tag is-rounded is-primary is-light">
                {{ services.length }} data tampil
              </span>
            </div>

            <div class="columns is-multiline filter-box">
              <div class="column is-5">
                <VField label="Cari Layanan / Merk / Lingkup">
                  <VControl icon="feather:search">
                    <input
                      v-model="filters.search"
                      class="input"
                      placeholder="Contoh: Multimeter atau Fluke"
                      @keyup.enter="loadServices"
                    />
                  </VControl>
                </VField>
              </div>
              <div class="column is-3">
                <VField label="Kategori">
                  <VControl>
                    <div class="select is-fullwidth">
                      <select v-model="filters.kategori" @change="loadServices">
                        <option value="">Semua Kategori</option>
                        <option v-for="option in options.kategori" :key="option.value" :value="option.value">
                          {{ option.label }}
                        </option>
                      </select>
                    </div>
                  </VControl>
                </VField>
              </div>
              <div class="column is-4">
                <VField label="Lingkup">
                  <VControl>
                    <AutoComplete
                      v-model="filters.lingkup"
                      :suggestions="d_lingkup"
                      @complete="fetchLingkup($event)"
                      @item-select="loadServices"
                      optionLabel="label"
                      :dropdown="true"
                      :minLength="3"
                      class="is-input"
                      appendTo="body"
                      loadingIcon="pi pi-spinner"
                      field="label"
                      placeholder="Semua lingkup"
                    />
                  </VControl>
                </VField>
              </div>
              <div class="column is-4">
                <VField label="Status">
                  <VControl>
                    <div class="select is-fullwidth">
                      <select v-model="filters.statusenabled" @change="loadServices">
                        <option value="">Semua Status</option>
                        <option value="true">Aktif</option>
                        <option value="false">Nonaktif</option>
                      </select>
                    </div>
                  </VControl>
                </VField>
              </div>
              <div class="column is-8 filter-actions">
                <VButton color="primary" icon="feather:search" @click="loadServices">Terapkan Filter</VButton>
                <VButton outlined icon="feather:rotate-ccw" @click="resetFilters">Reset</VButton>
              </div>
            </div>

            <div v-if="selectedServices.length" class="bulk-image-toolbar">
              <div class="bulk-selection-info">
                <span class="bulk-selection-count">{{ selectedServices.length }}</span>
                <div>
                  <strong>Layanan terpilih</strong>
                  <small>Pilih satu gambar untuk diterapkan ke seluruh layanan ini.</small>
                </div>
              </div>
              <div class="bulk-actions">
                <button class="button is-light" :disabled="isBulkUploading" @click="selectedServices = []">
                  Batal Pilih
                </button>
                <label class="button is-primary" :class="{ 'is-loading': isBulkUploading }">
                  <i class="iconify mr-2" data-icon="feather:image"></i>
                  Terapkan Gambar yang Sama
                  <input
                    class="bulk-file-input"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    :disabled="isBulkUploading"
                    @change="onBulkImageChange"
                  />
                </label>
              </div>
            </div>

            <DataTable
              v-model:selection="selectedServices"
              :value="services"
              :loading="isLoading"
              dataKey="id"
              :paginator="true"
              :rows="10"
              :rowsPerPageOptions="[10, 25, 50, 100]"
              responsiveLayout="scroll"
              class="p-datatable-sm service-table"
              paginatorTemplate="FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink CurrentPageReport RowsPerPageDropdown"
              currentPageReportTemplate="Menampilkan {first}-{last} dari {totalRecords}"
            >
              <Column selectionMode="multiple" headerStyle="width: 3rem"></Column>
              <Column header="Gambar" headerStyle="width: 82px">
                <template #body="{ data }">
                  <div class="service-image">
                    <img
                      :src="'/mapping-layanan/' + data.gambar"
                      :alt="data.namalayanan"
                      @error.once="(event) => onceImageErrored(event, '80x80')"
                    />
                  </div>
                </template>
              </Column>
              <Column field="namalayanan" header="Layanan" :sortable="true">
                <template #body="{ data }">
                  <strong class="service-name">{{ data.namalayanan }}</strong>
                  <small>{{ data.merktipe }}</small>
                </template>
              </Column>
              <Column field="lingkup" header="Lingkup" :sortable="true">
                <template #body="{ data }">
                  <span class="tag is-info is-light">{{ data.lingkup }}</span>
                </template>
              </Column>
              <Column field="kategori" header="Kategori" :sortable="true">
                <template #body="{ data }">
                  <span class="tag" :class="data.kategori === 'KAN' ? 'is-success is-light' : 'is-warning is-light'">
                    {{ data.kategori }}
                  </span>
                </template>
              </Column>
              <Column field="statusenabled" header="Status" :sortable="true">
                <template #body="{ data }">
                  <span class="status-pill" :class="{ 'is-off': !data.statusenabled }">
                    {{ data.statusenabled ? 'Aktif' : 'Nonaktif' }}
                  </span>
                </template>
              </Column>
              <Column header="Aksi" headerStyle="width: 130px">
                <template #body="{ data }">
                  <div class="action-buttons">
                    <button class="button is-small is-info is-light" title="Edit" @click="editService(data)">
                      <i class="iconify" data-icon="feather:edit-2"></i>
                    </button>
                    <button
                      class="button is-small"
                      :class="data.statusenabled ? 'is-danger is-light' : 'is-success is-light'"
                      :title="data.statusenabled ? 'Nonaktifkan' : 'Aktifkan'"
                      @click="toggleStatus(data)"
                    >
                      <i class="iconify" :data-icon="data.statusenabled ? 'feather:slash' : 'feather:check'"></i>
                    </button>
                  </div>
                </template>
              </Column>
            </DataTable>
          </VCard>
        </div>

        <div class="column is-4">
          <VCard class="editor-card">
            <div class="card-header-flex mb-4">
              <div>
                <span class="eyebrow">{{ form.id ? 'EDIT DATA' : 'DATA BARU' }}</span>
                <h4 class="title is-5 mb-0">{{ form.id ? 'Perbarui Layanan' : 'Tambah Layanan' }}</h4>
              </div>
              <button v-if="form.id" class="delete" aria-label="Batal edit" @click="resetForm"></button>
            </div>

            <VField label="Kategori Layanan">
              <VControl>
                <div class="select is-fullwidth">
                  <select v-model="form.kategori">
                    <option value="KAN">KAN</option>
                    <option value="Non KAN">Non KAN</option>
                  </select>
                </div>
              </VControl>
            </VField>

            <VField label="Lingkup">
              <VControl>
                <AutoComplete
                  v-model="form.objectlingkupfk"
                  :suggestions="d_lingkup"
                  @complete="fetchLingkup($event)"
                  optionLabel="label"
                  :dropdown="true"
                  :minLength="3"
                  class="is-input"
                  appendTo="body"
                  loadingIcon="pi pi-spinner"
                  field="label"
                  placeholder="Ketik untuk mencari lingkup..."
                />
              </VControl>
            </VField>

            <VField label="Nama Layanan / Alat">
              <VControl>
                <input v-model.trim="form.namalayanan" class="input" placeholder="Contoh: Digital Multimeter" />
              </VControl>
            </VField>

            <VField label="Merk / Tipe">
              <VControl>
                <input v-model.trim="form.merktipe" class="input" placeholder="Contoh: Fluke 15B" />
              </VControl>
            </VField>

            <VField label="Gambar Layanan">
              <VControl>
                <label class="image-uploader">
                  <div v-if="previewImage" class="image-preview">
                    <img :src="previewImage" alt="Preview layanan" />
                    <span>Ganti gambar</span>
                  </div>
                  <div v-else class="upload-placeholder">
                    <i class="iconify" data-icon="feather:upload-cloud"></i>
                    <strong>Pilih gambar</strong>
                    <small>JPG, PNG, atau WebP. Maksimal 5 MB.</small>
                  </div>
                  <input type="file" accept="image/jpeg,image/png,image/webp" @change="onImageChange" />
                </label>
              </VControl>
            </VField>

            <VField raw>
              <div class="status-editor">
                <div>
                  <strong>Status Layanan</strong>
                  <small>Layanan nonaktif tetap tersimpan tetapi tidak digunakan.</small>
                </div>
                <VControl raw>
                  <VSwitchBlock v-model="form.statusenabled" color="success" />
                </VControl>
              </div>
            </VField>

            <div class="editor-actions">
              <VButton outlined @click="resetForm">Bersihkan</VButton>
              <VButton color="primary" icon="feather:save" raised :loading="isSaving" @click="saveService">
                {{ form.id ? 'Simpan Perubahan' : 'Tambah Layanan' }}
              </VButton>
            </div>
          </VCard>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref } from 'vue'
import { useHead } from '@vueuse/head'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import AutoComplete from 'primevue/autocomplete'
import { useApi } from '/@src/composable/useApi'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { onceImageErrored } from '/@src/utils/via-placeholder'
import * as H from '/@src/utils/appHelper'

const viewWrapper = useViewWrapper()
viewWrapper.setPageTitle('Mapping Alat ke Layanan')
viewWrapper.setFullWidth(true)

useHead({
  title: 'Mapping Alat ke Layanan - ' + import.meta.env.VITE_PROJECT,
})

const emptyForm = () => ({
  id: null as number | null,
  kategori: 'KAN',
  objectlingkupfk: null as any,
  namalayanan: '',
  merktipe: '',
  statusenabled: true,
})

const isLoading = ref(false)
const isSaving = ref(false)
const isBulkUploading = ref(false)
const services = ref<any[]>([])
const selectedServices = ref<any[]>([])
const selectedFile = ref<File | null>(null)
const previewImage = ref('')
const form = ref(emptyForm())
const summary = ref({ total: 0, aktif: 0, nonaktif: 0 })
const options = ref<any>({ kategori: [] })
const d_lingkup = ref<any[]>([])
const filters = ref({
  search: '',
  kategori: '',
  lingkup: null as any,
  statusenabled: '',
})

async function loadDropdown() {
  const result: any = await useApi().get('/layanan/mapping-layanan-dropdown')
  options.value = {
    kategori: result?.kategori || [],
  }
}

async function fetchLingkup(filter: any) {
  const query = encodeURIComponent(filter?.query || '')
  const response: any = await useApi().get(
    `general/dropdown/lingkupkalibrasi_m?select=id,lingkupkalibrasi&param_search=lingkupkalibrasi&query=${query}&limit=10`
  )
  d_lingkup.value = response || []
}

async function loadServices() {
  isLoading.value = true
  try {
    const params = new URLSearchParams()
    if (filters.value.search) params.append('search', filters.value.search)
    if (filters.value.kategori) params.append('kategori', filters.value.kategori)
    if (filters.value.lingkup?.value) {
      params.append('objectlingkupfk', String(filters.value.lingkup.value))
    }
    if (filters.value.statusenabled !== '') {
      params.append('statusenabled', filters.value.statusenabled)
    }

    const result: any = await useApi().get(`/layanan/list-mapping-layanan?${params.toString()}`)
    services.value = result?.data || []
    selectedServices.value = []
    summary.value = {
      total: Number(result?.summary?.total || 0),
      aktif: Number(result?.summary?.aktif || 0),
      nonaktif: Number(result?.summary?.nonaktif || 0),
    }
  } catch (error) {
    H.alert('error', 'Gagal memuat daftar layanan')
  } finally {
    isLoading.value = false
  }
}

function resetFilters() {
  filters.value = { search: '', kategori: '', lingkup: null, statusenabled: '' }
  loadServices()
}

function resetForm() {
  form.value = emptyForm()
  selectedFile.value = null
  previewImage.value = ''
}

function editService(service: any) {
  form.value = {
    id: service.id,
    kategori: service.kategori,
    objectlingkupfk: {
      value: service.objectlingkupfk,
      label: service.lingkup,
    },
    namalayanan: service.namalayanan,
    merktipe: service.merktipe,
    statusenabled: Boolean(service.statusenabled),
  }
  selectedFile.value = null
  previewImage.value = service.gambar
    ? '/mapping-layanan/' + service.gambar
    : ''
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

function onImageChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  if (!file) return

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    H.alert('warning', 'Gambar harus berformat JPG, PNG, atau WebP')
    input.value = ''
    return
  }
  if (file.size > 5 * 1024 * 1024) {
    H.alert('warning', 'Ukuran gambar maksimal 5 MB')
    input.value = ''
    return
  }

  selectedFile.value = file
  previewImage.value = URL.createObjectURL(file)
}

function onBulkImageChange(event: Event) {
  const input = event.target as HTMLInputElement
  const file = input.files?.[0]
  input.value = ''
  if (!file) return

  if (!['image/jpeg', 'image/png', 'image/webp'].includes(file.type)) {
    return H.alert('warning', 'Gambar harus berformat JPG, PNG, atau WebP')
  }
  if (file.size > 5 * 1024 * 1024) {
    return H.alert('warning', 'Ukuran gambar maksimal 5 MB')
  }
  if (selectedServices.value.length === 0) {
    return H.alert('warning', 'Pilih minimal satu layanan')
  }

  const selectedCount = selectedServices.value.length
  H.confirm(`Terapkan gambar yang sama ke ${selectedCount} layanan terpilih?`, async () => {
    const payload = new FormData()
    selectedServices.value.forEach(service => payload.append('ids[]', String(service.id)))
    payload.append('gambar', file)

    isBulkUploading.value = true
    try {
      await useApi().post('/layanan/upload-bulk-mapping-layanan-image', payload)
      await loadServices()
    } catch (error) {
      H.alert('error', 'Gagal menerapkan gambar ke layanan terpilih')
    } finally {
      isBulkUploading.value = false
    }
  })
}

async function saveService() {
  if (!form.value.objectlingkupfk?.value) return H.alert('warning', 'Lingkup harus dipilih')
  if (!form.value.namalayanan) return H.alert('warning', 'Nama layanan harus diisi')
  if (!form.value.merktipe) return H.alert('warning', 'Merk / tipe harus diisi')

  const payload = new FormData()
  if (form.value.id) payload.append('id', String(form.value.id))
  payload.append('kategori', form.value.kategori)
  payload.append('objectlingkupfk', String(form.value.objectlingkupfk.value))
  payload.append('namalayanan', form.value.namalayanan)
  payload.append('merktipe', form.value.merktipe)
  payload.append('statusenabled', form.value.statusenabled ? '1' : '0')
  if (selectedFile.value) payload.append('gambar', selectedFile.value)

  isSaving.value = true
  try {
    await useApi().post('/layanan/save-mapping-layanan', payload)
    resetForm()
    await Promise.all([loadServices(), loadDropdown()])
  } catch (error) {
    H.alert('error', 'Gagal menyimpan layanan')
  } finally {
    isSaving.value = false
  }
}

function toggleStatus(service: any) {
  const targetStatus = !service.statusenabled
  const action = targetStatus ? 'mengaktifkan' : 'menonaktifkan'

  H.confirm(`Yakin ingin ${action} layanan ${service.namalayanan} - ${service.merktipe}?`, async () => {
    try {
      await useApi().post('/layanan/set-status-mapping-layanan', {
        id: service.id,
        statusenabled: targetStatus,
      })
      await loadServices()
    } catch (error) {
      H.alert('error', 'Gagal mengubah status layanan')
    }
  })
}

onMounted(async () => {
  await Promise.all([loadDropdown(), loadServices()])
})
</script>

<style lang="scss" scoped>
.service-hero {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  padding: 1.5rem 1.75rem;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 14px;
  background: linear-gradient(135deg, var(--white), var(--primary-light-48));

  h1 {
    margin: 0.2rem 0;
    color: var(--dark-text);
    font-size: 1.5rem;
    font-weight: 700;
  }

  p {
    color: var(--light-text);
  }
}

.eyebrow {
  color: var(--primary);
  font-size: 0.68rem;
  font-weight: 800;
  letter-spacing: 0.12em;
}

.summary-card {
  display: flex;
  align-items: center;
  gap: 1rem;
  min-height: 94px;
  padding: 1rem 1.25rem;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 12px;
  background: var(--white);

  .summary-icon {
    display: grid;
    width: 48px;
    height: 48px;
    place-items: center;
    border-radius: 50%;
    font-size: 1.3rem;
  }

  strong,
  small {
    display: block;
  }

  strong {
    color: var(--dark-text);
    font-size: 1.45rem;
  }

  small {
    color: var(--light-text);
  }

  &.is-total .summary-icon {
    color: var(--primary);
    background: var(--primary-light-45);
  }

  &.is-active .summary-icon {
    color: var(--success);
    background: var(--success-light-45);
  }

  &.is-inactive .summary-icon {
    color: var(--danger);
    background: var(--danger-light-45);
  }
}

.card-header-flex,
.filter-actions,
.action-buttons,
.editor-actions,
.status-editor {
  display: flex;
  align-items: center;
}

.card-header-flex,
.status-editor {
  justify-content: space-between;
}

.filter-box {
  margin-bottom: 1rem;
  padding: 0.75rem;
  border-radius: 10px;
  background: var(--fade-grey-light-5);
}

.bulk-image-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 1rem;
  padding: 0.85rem 1rem;
  border: 1px solid var(--primary-light-30);
  border-radius: 12px;
  background: var(--primary-light-48);
}

.bulk-selection-info,
.bulk-actions {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.bulk-selection-info {
  strong,
  small {
    display: block;
  }

  small {
    color: var(--light-text);
  }
}

.bulk-selection-count {
  display: grid;
  min-width: 38px;
  height: 38px;
  padding: 0 0.55rem;
  place-items: center;
  border-radius: 999px;
  color: var(--white);
  background: var(--primary);
  font-weight: 800;
}

.bulk-file-input {
  display: none;
}

.filter-actions {
  justify-content: flex-end;
  gap: 0.5rem;
}

.service-image {
  display: grid;
  width: 58px;
  height: 58px;
  overflow: hidden;
  place-items: center;
  border: 1px solid var(--fade-grey-dark-3);
  border-radius: 10px;
  color: var(--light-text);
  background: var(--fade-grey-light-5);

  img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }
}

.service-name,
.service-name + small {
  display: block;
}

.service-name + small {
  margin-top: 0.2rem;
  color: var(--light-text);
}

.status-pill {
  display: inline-flex;
  padding: 0.25rem 0.65rem;
  border-radius: 999px;
  color: var(--success-dark-4);
  background: var(--success-light-45);
  font-size: 0.72rem;
  font-weight: 700;

  &.is-off {
    color: var(--danger-dark-4);
    background: var(--danger-light-45);
  }
}

.action-buttons {
  gap: 0.4rem;
}

.editor-card {
  position: sticky;
  top: 90px;
}

.image-uploader {
  display: block;
  overflow: hidden;
  border: 1px dashed var(--primary);
  border-radius: 12px;
  cursor: pointer;

  input {
    display: none;
  }
}

.upload-placeholder {
  display: grid;
  min-height: 170px;
  place-items: center;
  align-content: center;
  gap: 0.35rem;
  color: var(--light-text);
  background: var(--primary-light-48);

  .iconify {
    color: var(--primary);
    font-size: 2rem;
  }
}

.image-preview {
  position: relative;
  height: 210px;
  background: var(--fade-grey-light-5);

  img {
    width: 100%;
    height: 100%;
    object-fit: contain;
  }

  span {
    position: absolute;
    right: 0.75rem;
    bottom: 0.75rem;
    padding: 0.25rem 0.65rem;
    border-radius: 999px;
    color: var(--white);
    background: rgb(0 0 0 / 65%);
    font-size: 0.72rem;
  }
}

.status-editor {
  margin: 1.25rem 0;
  padding: 0.85rem;
  border-radius: 10px;
  background: var(--fade-grey-light-5);

  small {
    display: block;
    max-width: 240px;
    color: var(--light-text);
  }
}

.editor-actions {
  justify-content: flex-end;
  gap: 0.5rem;
}

@media only screen and (max-width: 767px) {
  .service-hero,
  .card-header-flex {
    align-items: flex-start;
    flex-direction: column;
  }

  .editor-card {
    position: static;
  }

  .bulk-image-toolbar,
  .bulk-actions {
    align-items: stretch;
    flex-direction: column;
  }
}

:global(.is-dark) {
  .service-hero,
  .summary-card {
    border-color: var(--dark-sidebar-light-12);
    background: var(--dark-sidebar-light-6);
  }

  .filter-box,
  .status-editor,
  .bulk-image-toolbar {
    background: var(--dark-sidebar-light-4);
  }
}
</style>
