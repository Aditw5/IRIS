<template>
  <div class="induk-page-title">
    <div class="induk-title-text">FMMO-163-14.4.3.b-83.1 - Daftar Induk Dokumen Non Mechanical Workshop</div>
    <div class="induk-title-sub">Master nomor induk dokumen level 1 sampai level 4 dan koneksi ke UDS</div>
  </div>

  <VCard>
    <div :class="[isStuck && 'is-stuck']" class="induk-stuck-toolbar">
      <div class="induk-toolbar">
        <div class="induk-toolbar-left">
          <div>
            <h3 class="title is-5 mb-1">Master Nomor Induk Dokumen</h3>
            <div class="induk-toolbar-sub">
              Klik nomor dokumen untuk membuka dokumen UDS yang terhubung.
            </div>
          </div>

          <div class="induk-search-box">
            <VField>
              <VControl icon="feather:search">
                <input v-model="searchQuery" type="text" class="input is-rounded"
                  placeholder="Cari nomor dokumen / nama dokumen / UDS terkait / level..." />
              </VControl>
            </VField>
          </div>
        </div>

        <div class="buttons">
          <VButton color="info" outlined rounded icon="feather:refresh-cw" :loading="isLoading" @click="loadMaster()">
            Refresh
          </VButton>
          <VButton v-if="isMutuOnly" color="success" outlined rounded icon="feather:plus" @click="addRoot()">
            Tambah Level 1
          </VButton>
        </div>
      </div>
    </div>

    <TreeTable :value="filteredTreeData" class="mt-4" :paginator="false" responsiveLayout="scroll"
      tableStyle="min-width: 100%">
      <Column field="nomordokumen" header="No. Dokumen" expander style="min-width: 260px">
        <template #body="{ node }">
          <button class="link-doc-btn" @click.prevent="openLinkedUdr(node.data)">
            {{ node.data.nomordokumen }}
          </button>
        </template>
      </Column>

      <Column field="namadokumen" header="Nama Dokumen" style="min-width: 320px">
        <template #body="{ node }">
          {{ node.data.namadokumen }}
        </template>
      </Column>

      <Column field="leveldokumen" header="Level" style="width: 90px">
        <template #body="{ node }">
          Level {{ node.data.leveldokumen }}
        </template>
      </Column>

      <Column header="UDS Terkait" style="min-width: 220px">
        <template #body="{ node }">
          <span v-if="node.data.linked_udr_name">{{ node.data.linked_udr_name }}</span>
          <span v-else>-</span>
        </template>
      </Column>

      <Column header="Aksi" style="min-width: 240px">
        <template #body="{ node }">
          <div class="buttons are-small is-flex-wrap-nowrap">
            <VButton v-if="isMutuOnly && Number(node.data.leveldokumen) < 4" color="success" outlined rounded
              icon="feather:plus" @click="addChild(node.data)">
              Child
            </VButton>

            <VButton v-if="isMutuOnly" color="info" outlined rounded icon="feather:edit" @click="editNode(node.data)">
              Ubah
            </VButton>

            <VButton color="primary" outlined rounded icon="feather:external-link" @click="openLinkedUdr(node.data)">
              Buka UDS
            </VButton>

            <VButton v-if="isMutuOnly" color="danger" outlined rounded icon="feather:trash-2"
              @click="deleteNode(node.data)">
              Hapus
            </VButton>
          </div>
        </template>
      </Column>
    </TreeTable>
  </VCard>

  <Dialog v-model:visible="modalForm" modal
    :header="form.id ? 'Ubah Nomor Induk Dokumen' : 'Tambah Nomor Induk Dokumen'" :style="{ width: '38vw' }">
    <div class="columns is-multiline">
      <div class="column is-12">
        <VField label="Parent">
          <VControl icon="feather:folder">
            <Dropdown v-model="form.parent" :options="parentOptions" optionLabel="label" class="is-rounded"
              placeholder="Pilih parent (kosong = level 1)" style="width:100%" :filter="true" showClear
              @change="changeParent()" />
          </VControl>
        </VField>
      </div>

      <div class="column is-6">
        <VField label="Level">
          <VControl icon="feather:layers">
            <Dropdown v-model="form.leveldokumen" :options="levelOptions" optionLabel="label" optionValue="value"
              class="is-rounded" placeholder="Pilih level" style="width:100%" />
          </VControl>
        </VField>
      </div>

      <div class="column is-6">
        <VField label="No Urut">
          <VControl icon="feather:hash">
            <input v-model="form.nourut" type="number" class="input is-rounded" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Nomor Dokumen">
          <VControl icon="feather:file-text">
            <input v-model="form.nomordokumen" type="text" class="input is-rounded"
              placeholder="Contoh: FMMO-163-14.4.3.b-53.1" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Nama Dokumen">
          <VControl icon="feather:bookmark">
            <input v-model="form.namadokumen" type="text" class="input is-rounded" placeholder="Nama dokumen" />
          </VControl>
        </VField>
      </div>

      <div class="column is-6">
        <VField label="Tanggal">
          <VControl icon="feather:calendar">
            <input v-model="form.tanggaldokumen" type="date" class="input is-rounded" />
          </VControl>
        </VField>
      </div>

      <div class="column is-6">
        <VField label="Status">
          <VControl icon="feather:activity">
            <input v-model="form.statusdokumen" type="text" class="input is-rounded" placeholder="Open / Closed" />
          </VControl>
        </VField>
      </div>

      <div class="column is-6">
        <VField label="Target">
          <VControl icon="feather:target">
            <input v-model="form.target" type="text" class="input is-rounded" placeholder="Target" />
          </VControl>
        </VField>
      </div>

      <div class="column is-6">
        <VField label="PIC">
          <VControl icon="feather:user">
            <input v-model="form.pic" type="text" class="input is-rounded" placeholder="PIC" />
          </VControl>
        </VField>
      </div>

      <div class="column is-12">
        <VField label="Keterangan">
          <VControl>
            <VTextarea v-model="form.keterangan" rows="4" placeholder="Keterangan"></VTextarea>
          </VControl>
        </VField>
      </div>
    </div>

    <template #footer>
      <VButton icon="lnir lnir-arrow-left rem-100" light dark-outlined @click="closeForm()">
        Tutup
      </VButton>
      <VButton v-if="isMutuOnly" color="primary" outlined rounded icon="feather:save" :loading="saving"
        @click="saveForm()">
        {{ form.id ? 'Ubah' : 'Simpan' }}
      </VButton>
    </template>
  </Dialog>
</template>

<script setup lang="ts">
import { ref, computed } from 'vue'
import { useHead } from '@vueuse/head'
import { useRouter } from 'vue-router'
import { useWindowScroll } from '@vueuse/core'
import Dialog from 'primevue/dialog'
import TreeTable from 'primevue/treetable'
import Column from 'primevue/column'
import Dropdown from 'primevue/dropdown'
import { useApi } from '/@src/composable/useApi'
import { useToaster } from '/@src/composable/toaster'
import { useViewWrapper } from '/@src/stores/viewWrapper'
import { useUserSession } from '/@src/stores/userSession'
import * as H from '/@src/utils/appHelper'

useHead({ title: 'Daftar Induk Dokumen Non Mechanical Wordshop - ' + import.meta.env.VITE_PROJECT })
useViewWrapper().setFullWidth(true)

const router = useRouter()
const toast = useToaster()
const userSession = useUserSession()
const { y } = useWindowScroll()

const kelompokUser = computed(() =>
  String(userSession.getUser()?.kelompokUser?.kelompokUser ?? '').toLowerCase().trim(),
)

const lokasiKalibrasiFk = computed(() =>
  Number(userSession.getUser()?.kelompokUser?.lokasiKalibrasiFk ?? 0),
)

const isMutuOnly = computed(() =>
  kelompokUser.value === 'mutu' && lokasiKalibrasiFk.value === 1,
)

const isStuck = computed(() => y.value > 30)

const treeData: any = ref([])
const flatData: any = ref([])
const isLoading = ref(false)
const saving = ref(false)
const modalForm = ref(false)
const searchQuery = ref('')

const form: any = ref({
  id: null,
  parent: null,
  parentid: null,
  leveldokumen: 1,
  nourut: 1,
  nomordokumen: '',
  namadokumen: '',
  tanggaldokumen: '',
  statusdokumen: '',
  target: '',
  pic: '',
  keterangan: '',
})

const parentOptions = computed(() => {
  return (flatData.value || [])
    .filter((x: any) => Number(x.leveldokumen) < 4)
    .map((x: any) => ({
      key: x.key,
      label: `${x.nomordokumen} - ${x.namadokumen}`,
      leveldokumen: Number(x.leveldokumen),
    }))
})

const minAllowedLevel = computed(() => {
  const parentLevel = form.value.parent?.leveldokumen ? Number(form.value.parent.leveldokumen) : 0
  return parentLevel > 0 ? parentLevel + 1 : 1
})

const levelOptions = computed(() => {
  const start = minAllowedLevel.value
  const items = []

  for (let i = start; i <= 4; i++) {
    items.push({
      label: `Level ${i}`,
      value: i,
    })
  }

  return items
})

const matchesSearch = (node: any, keyword: string) => {
  const nomor = String(node?.data?.nomordokumen ?? '').toLowerCase()
  const nama = String(node?.data?.namadokumen ?? '').toLowerCase()
  const uds = String(node?.data?.linked_udr_name ?? '').toLowerCase()
  const level = String(node?.data?.leveldokumen ?? '').toLowerCase()

  return (
    nomor.includes(keyword) ||
    nama.includes(keyword) ||
    uds.includes(keyword) ||
    level.includes(keyword)
  )
}

const filterTree = (nodes: any[], keyword: string): any[] => {
  const results: any[] = []

  for (const node of nodes || []) {
    const clonedNode = {
      ...node,
      data: { ...node.data },
      children: [],
    }

    const filteredChildren = filterTree(node.children || [], keyword)
    const selfMatch = matchesSearch(node, keyword)

    if (selfMatch || filteredChildren.length > 0) {
      clonedNode.children = filteredChildren
      results.push(clonedNode)
    }
  }

  return results
}

const filteredTreeData = computed(() => {
  const keyword = String(searchQuery.value || '').trim().toLowerCase()

  if (!keyword) {
    return treeData.value
  }

  return filterTree(treeData.value || [], keyword)
})

const formatDate = (v: any) => {
  if (!v) return '-'
  const d = new Date(v)
  if (isNaN(d as any)) return v
  return d.toLocaleDateString('id-ID')
}

const loadMaster = async () => {
  isLoading.value = true
  try {
    const res = await useApi().get('/udr/master-nomor-induk')
    treeData.value = res?.tree ?? []
    flatData.value = res?.data ?? []
  } catch (e) {
    treeData.value = []
    flatData.value = []
  } finally {
    isLoading.value = false
  }
}

const resetForm = () => {
  form.value = {
    id: null,
    parent: null,
    parentid: null,
    leveldokumen: 1,
    nourut: 1,
    nomordokumen: '',
    namadokumen: '',
    tanggaldokumen: '',
    statusdokumen: '',
    target: '',
    pic: '',
    keterangan: '',
  }
}

const closeForm = () => {
  resetForm()
  modalForm.value = false
}

const setNoUrut = async () => {
  const parentId = form.value.parent?.key ?? form.value.parentid ?? null
  const pid = parentId !== null && parentId !== '' ? `?parentid=${parentId}` : ''
  const res = await useApi().get('/udr/nourut-nomor-induk' + pid)
  form.value.nourut = res?.nourut ?? 1
}

const normalizeLevelByParent = () => {
  const minLevel = minAllowedLevel.value

  if (!form.value.leveldokumen || Number(form.value.leveldokumen) < minLevel) {
    form.value.leveldokumen = minLevel
  }

  if (Number(form.value.leveldokumen) > 4) {
    form.value.leveldokumen = 4
  }
}

const changeParent = async () => {
  form.value.parentid = form.value.parent?.key ?? null
  normalizeLevelByParent()
  await setNoUrut()
}

const addRoot = async () => {
  if (!isMutuOnly.value) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menambah data')
    return
  }

  resetForm()
  form.value.parent = null
  form.value.parentid = null
  form.value.leveldokumen = 1
  await setNoUrut()
  modalForm.value = true
}

const addChild = async (row: any) => {
  if (!isMutuOnly.value) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menambah child')
    return
  }

  resetForm()
  form.value.parent = {
    key: row.key,
    label: `${row.nomordokumen} - ${row.namadokumen}`,
    leveldokumen: Number(row.leveldokumen),
  }
  form.value.parentid = row.key
  form.value.leveldokumen = Math.min(Number(row.leveldokumen) + 1, 4)
  await setNoUrut()
  modalForm.value = true
}

const editNode = (row: any) => {
  if (!isMutuOnly.value) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat mengubah data')
    return
  }

  const parent = row.parentid !== null && row.parentid !== ''
    ? flatData.value.find((x: any) => Number(x.key) === Number(row.parentid))
    : null

  form.value = {
    id: row.key,
    parent: parent
      ? {
        key: parent.key,
        label: `${parent.nomordokumen} - ${parent.namadokumen}`,
        leveldokumen: Number(parent.leveldokumen),
      }
      : null,
    parentid: row.parentid ?? null,
    leveldokumen: Number(row.leveldokumen ?? 1),
    nourut: row.nourut ?? 1,
    nomordokumen: row.nomordokumen ?? '',
    namadokumen: row.namadokumen ?? '',
    tanggaldokumen: row.tanggaldokumen ?? '',
    statusdokumen: row.statusdokumen ?? '',
    target: row.target ?? '',
    pic: row.pic ?? '',
    keterangan: row.keterangan ?? '',
  }

  normalizeLevelByParent()
  modalForm.value = true
}

const saveForm = async () => {
  if (!isMutuOnly.value) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menyimpan data')
    return
  }

  if (!form.value.nomordokumen) {
    H.alert('error', 'Nomor dokumen harus diisi')
    return
  }

  if (!form.value.namadokumen) {
    H.alert('error', 'Nama dokumen harus diisi')
    return
  }

  const finalParentId = form.value.parent?.key ?? form.value.parentid ?? null
  const finalLevel = Number(form.value.leveldokumen ?? 1)

  if (finalLevel < minAllowedLevel.value || finalLevel > 4) {
    H.alert('error', `Level dokumen harus antara ${minAllowedLevel.value} sampai 4`)
    return
  }

  saving.value = true
  try {
    await useApi().post('/udr/save-nomor-induk', {
      id: form.value.id,
      parentid: finalParentId,
      leveldokumen: finalLevel,
      nourut: form.value.nourut,
      nomordokumen: form.value.nomordokumen,
      namadokumen: form.value.namadokumen,
      tanggaldokumen: form.value.tanggaldokumen,
      statusdokumen: form.value.statusdokumen,
      target: form.value.target,
      pic: form.value.pic,
      keterangan: form.value.keterangan,
    })

    toast.success('Data nomor induk berhasil disimpan')
    modalForm.value = false
    resetForm()
    await loadMaster()
  } catch (e: any) {
    H.alert('error', e?.response?.data?.message ?? 'Simpan nomor induk gagal')
  } finally {
    saving.value = false
  }
}

const deleteNode = async (row: any) => {
  if (!isMutuOnly.value) {
    H.alert('warning', 'Hanya user kelompok Mutu dengan lokasi kalibrasi 1 yang dapat menghapus data')
    return
  }

  try {
    await useApi().post('/udr/hapus-nomor-induk', { id: row.key })
    toast.success('Data nomor induk berhasil dihapus')
    await loadMaster()
  } catch (e: any) {
    H.alert('error', e?.response?.data?.message ?? 'Hapus nomor induk gagal')
  }
}

const openLinkedUdr = async (row: any) => {
  try {
    const res = await useApi().get('/udr/resolve-open-udr-by-nomor-induk?nomorindukfk=' + row.key)
    if (res?.restricted) {
      H.alert('warning', 'Folder dokumen terkunci. Akun Anda tidak memiliki akses untuk membukanya.')
      return
    }
    if (!res?.id) {
      H.alert('warning', 'Nomor dokumen ini belum terhubung ke dokumen UDS')
      return
    }

    if (res.target_type === 'file') {
      H.printBlade(`udr/new-drive-open?id=${res.id}`)
      return
    }

    const routeData = router.resolve({
      path: '/module/uds/ulab-digital-storage',
      query: {
        openUdrId: String(res.id),
        targetType: 'folder',
        jenisudr: res.jenisudr,
      },
    })

    window.open(routeData.href, '_blank')
  } catch (e) {
    H.alert('error', 'Gagal membuka dokumen UDS terkait')
  }
}

await loadMaster()
</script>

<style lang="scss">
@import '/@src/scss/abstracts/all';
@import '/@src/scss/custom/config';

.induk-page-title {
  margin-bottom: 14px;
  padding: 14px 16px;
  border-radius: 14px;
  background: linear-gradient(90deg, rgba(255, 193, 7, 0.16), rgba(255, 255, 255, 0));
  border: 1px solid rgba(255, 193, 7, 0.24);
}

.induk-title-text {
  font-size: 1.35rem;
  font-weight: 800;
  color: var(--dark-text);
}

.induk-title-sub {
  margin-top: 4px;
  font-size: 0.9rem;
  color: var(--light-text);
}

.induk-stuck-toolbar {
  position: sticky;
  top: 0;
  z-index: 20;
  background: var(--white);
  border-radius: 14px;
  transition: all 0.2s ease;
  padding-bottom: 8px;
}

.induk-stuck-toolbar.is-stuck {
  box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
}

.induk-toolbar {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 12px;
  padding-top: 4px;
}

.induk-toolbar-left {
  flex: 1;
  min-width: 0;
}

.induk-toolbar-sub {
  font-size: 0.9rem;
  color: var(--light-text);
}

.induk-search-box {
  margin-top: 12px;
  max-width: 520px;
}

.link-doc-btn {
  border: none;
  background: transparent;
  padding: 0;
  cursor: pointer;
  color: var(--primary);
  font-weight: 700;
  text-align: left;
}

.link-doc-btn:hover {
  text-decoration: underline;
}

@media only screen and (max-width: 767px) {
  .induk-toolbar {
    flex-direction: column;
    align-items: stretch;
  }

  .induk-search-box {
    max-width: 100%;
  }

  .induk-stuck-toolbar {
    padding-bottom: 10px;
  }
}
</style>
