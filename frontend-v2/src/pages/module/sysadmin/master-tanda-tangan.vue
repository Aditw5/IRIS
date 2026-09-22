<template>
  <ConfirmDialog />
  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Tanda tangan pegawai</h3>
    </div>
    <div class="columns is-multiline">
      <div class="column is-8">
        <div class="user-grid-toolbar">

          <VControl icon="feather:search">
            <input v-model="filters" class="input custom-text-filter" placeholder="Cari Data..." />
          </VControl>
          <div class="buttons">
            <VField v-slot="{ id }" class="is-icon-select">
              <VControl>
                <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name" :options="d_View"
                  :searchable="true" track-by="name" mode="single" @select="changeView(selectView)" autocomplete="off">
                  <template #singlelabel="{ value }">
                    <div class="multiselect-single-label">
                      <div class="select-label-icon-wrap">
                        <i :class="value.icon"></i>
                      </div>
                      <span class="select-label-text">
                        {{ value.name }}
                      </span>
                    </div>
                  </template>
                  <template #option="{ option }">
                    <div class="select-option-icon-wrap">
                      <i :class="option.icon"></i>
                    </div>
                    <span class="select-option-text">
                      {{ option.name }}
                    </span>
                  </template>
                </Multiselect>
              </VControl>
            </VField>
            <!-- <VControl class="is-pulled-right">
              <VSwitchBlock v-model="item.isAktif" label="Aktif" color="danger" />
            </VControl> -->
          </div>
        </div>

        <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
          <DataTable :value="dataSourcefiltered" class="p-datatable-sm" :loading="isLoading" :paginator="true" :rows="10"
            :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

            <Column field="no" header="No" style="text-align: center;"></Column>
            <Column field="namaLengkap" header="Pegawai" :sortable="true"></Column>
            <Column field="status" header="Status"></Column>
            <Column :exportable="false" header="Action" style="text-align: center;">
              <template #body="slotProps">
                <VIconButton type="button" icon="fas fa-eye" class="mr-3" color="info" circle outlined raised
                  v-tooltip.top="'Detail'" @click="detail(slotProps.data)">
                </VIconButton>
                <VIconButton icon="feather:edit" color="warning" circle @click="edit(slotProps.data)" class="mr-3" outlined />
                <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                  v-tooltip.top="'Hapus'" @click="dialogConfirm(slotProps.data)">
                </VIconButton>

              </template>
            </Column>
          </DataTable>
        </div>
        <div class="list-view list-view-v3" v-else-if="selectView == 'grid'">
          <TransitionGroup name="list-complete" tag="div">
            <!--Item-->
            <div v-for="item in dataSourcefiltered" :key="item.id" class="list-view-item">
              <div class="list-view-item-inner">
                <VAvatar size="medium" :picture="item.ttd" color="primary" squared bordered />
                <div class="meta-left">
                  <h3> {{ item.namaLengkap }} </h3>
                  <span>
                    <VTag :color="item.status_c" :label="item.status" />
                  </span>
                </div>
                <div class="meta-right">
                  <div class="buttons">
                    <VButton color="warning" outlined raised @click="detail(item)"> Detail </VButton>
                    <VIconButton icon="feather:edit" class="hint--bubble hint--primary hint--top" data-hint="Edit" circle
                      @click="edit(item)" />
                    <VIconButton icon="feather:trash" class="hint--bubble hint--primary hint--top" data-hint="Delete"
                      circle @click="dialogConfirm(item)" />
                  </div>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>

      </div>
      <div class="column is-4">
        <img src="/images/avatars/label/agama.png" alt="" srcset=""
          style="max-width:65%;margin-left: 8rem; margin-top: -5rem;">

        <VCard style="margin-top :-3rem">

          <div class="column">
            <VField label="Nama Pegawai">
              <VControl icon="feather:search" class="prime-auto-select">
                <Dropdown v-model="input.pegawaifk" :options="d_Pegawai" :optionLabel="'label'" class="is-rounded"
                  placeholder="Pilih data" style="width: 100%;" showClear :filter="true" />
                <!-- <AutoComplete v-model="input.pegawaifk" :suggestions="d_Pegawai" @complete="fetchPegawai($event)"
                  :optionLabel="'label'" :dropdown="true" :minLength="3" :appendTo="'body'" :loadingIcon="'pi pi-spinner'"
                  :field="'label'" placeholder="Cari Petugas..." /> -->
              </VControl>
            </VField>
          </div>

          <div class="column">
            <VField label="Tandan Tanggan Pegawai">
              <FileUpload v-model="imgToBase64" mode="basic" url="./upload.php" accept="image/*" :maxFileSize="50000000"
              :chooseLabel="imgToBase64 ? 'data:image/png' :'Unggah'" @select="onAdvancedUpload($event)" :uploadIcon="'pi pi-upload'" :chooseIcon="'pi pi-plus'"  />
            </VField>
          </div>

          <div class="column mt-3">
            <VButton @click="simpan()" :loading="isLoadingTT" type="button" icon="feather:save" class="is-fullwidth"
              color="success" raised>
              {{ input.id ? "Ubah":"Simpan" }}
            </VButton>
          </div>
        </VCard>

      </div>

    </div>
  </VCard>

  <VModal :open="modalDetail" title="Detail Tanda Tangan" actions="right" size="small" @close="modalDetail = false">
    <template #content>
      <div class="column" style="text-align: center;">
        <TandaTangan :elemenID="'signature'" :width="'180'" :height="'180'" class="masterTTD" disabled="disabled" />
        <h1 style="font-weight: bold;">{{ namaPegawai }}</h1>
      </div>
      <!-- <form class="modal-form">
        <div class="columns is-multiline">
          <div class="column is-12">
            <VField label="Kondisi Pasien">
              <VControl icon="feather:bookmark">
                <VInput type="text" v-model="item.kondisipasien" placeholder="Kondisi Pasien" class="is-rounded" readonly
                  style="cursor:pointer" />
              </VControl>
            </VField>
          </div>
          <div class="column is-12">
            <VField label="Jenis Kondisi Pasien">
              <VControl icon="feather:bookmark">
                <VInput type="text" v-model="item.jeniskondisipasien" placeholder="Jenis Kondisi Pasien"
                  class="is-rounded" readonly style="cursor:pointer" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <VField label="Kode External">
              <VControl icon="feather:bookmark">
                <VInput type="text" v-model="item.kodeexternal" placeholder="Kode External" class="is-rounded" readonly
                  style="cursor:pointer" />
              </VControl>
            </VField>
          </div>
          <div class="column is-6">
            <VField label="Status Aktivasi">
              <VControl icon="feather:bookmark">
                <VInput type="text" v-model="item.status" placeholder="Status Aktivasi" class="is-rounded" readonly
                  style="cursor:pointer" />
              </VControl>
            </VField>
          </div>
        </div>
      </form> -->
    </template>

  </VModal>
</template>

<script  setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import AutoComplete from 'primevue/autocomplete';
import DataTable from 'primevue/datatable'
import FileUpload from 'primevue/fileupload';
// import TandaTangan from '../emr/profile-pasien/page-emr-plugins/tanda-tangan.vue'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import * as H from '/@src/utils/appHelper'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'
import Dropdown from 'primevue/dropdown';
useHead({
  title: 'Tanda Tangan Pegawai - ' + import.meta.env.VITE_PROJECT,
})
const item: any = ref({
  isAktif: true
})

let imgToBase64 = ref()
let d_Pegawai = ref([])
let namaPegawai = ref('')
const confirm = useConfirm()

let dataSource: any = ref([])
const d_View = [
  {
    name: 'Grid View',
    value: 'grid',
    icon: 'fas fa-id-card-alt',
  },
  {
    name: 'List View',
    value: 'list',
    icon: 'fas fa-list',
  },
]

const selectView: any = ref()
const input: any = ref({})
selectView.value = 'list'
let isLoading: any = ref(false)
let isLoadingTT: any = ref(false)
let modalDetail: any = ref(false)

const currentPage: any = ref({
  limit: 5,
  rows: 50,
})
const filters = ref('')
const route = useRoute()

const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }

  return dataSource.value.filter((items: any) => {
    return (
      items.namaLengkap.match(new RegExp(filters.value, 'i'))
    )
  })
})


const onAdvancedUpload = (e: any) => {

  const fileReader = new FileReader()
  fileReader.onload = () => {
    imgToBase64.value = fileReader.result;
  };
  fileReader.readAsDataURL(e.files[0]);

}

const fetchData = async () => {

  isLoading.value = true

  await useApi().get('/sysadmin/master-tanda-tangan').then((response) => {
    isLoading.value = false
    response.forEach((element: any, i: any) => {
      // console.log(element.statusenabled)
      element.status = element.statusenabled == true ? 'Tersedia' : 'Tidak Tersedia'
      element.no = i + 1
    });
    dataSource.value = response
    // console.log(dataSource.value)
  }).catch((err) => {
    isLoading.value = false
  })

}

const simpan = async () => {
  if (!input.value.pegawaifk) {
    H.alert('error', 'Pegawai tidak boleh kosong')
    return
  }
  if (imgToBase64.value == undefined) {
    H.alert('error', 'Tanda tangan tidak boleh kosong')
    return
  }

  let objSave = {
    "id": input.value.id ? input.value.id : "",
    "namaLengkap": input.value.pegawaifk.label,
    "collection": "TandaTangan",
    "ttd": imgToBase64.value,
    "pegawaifk": input.value.pegawaifk.value,
  }

  await useApi().post('sysadmin/master-tanda-tangan/save', objSave).then((response: any) => {
    isLoadingTT.value = false
    clear()
    fetchData()
  }, (error) => {
    isLoadingTT.value = false
  })
}

const fetchPegawai = async () => {
  await useApi().get(
    `general/dropdown/pegawai_m?select=id,namalengkap&param_search=namalengkap&query=&limit=10`
  ).then((response) => {
    d_Pegawai.value = response
  })
}


const dialogConfirm = (e: any) => {
  confirm.require({
    message: 'Apakah anda serius menghapus data ini ?',
    header: 'Konfirmasi Hapus Data',
    icon: 'pi pi-info-circle',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteItem(e)
    },
    reject: () => { },
  })
}

const deleteItem = async (e: any) => {

  await useApi().post(`sysadmin/master-tanda-tangan/delete`, { 'id': e.id }).then((response: any) => {
    fetchData()
  }, (error) => {

  })
}
const edit = async (e: any) => {
  input.value.id = e.id
  d_Pegawai.value.forEach((element: any) => {
    if (element.value == e.pegawaifk) {
      input.value.pegawaifk = element
    }
  });

  imgToBase64.value = e.ttd
}
function clear() {
  input.value.id = ''
  input.value.pegawaifk = ''
  imgToBase64.value = undefined
}
function changeView(e: any) {
  selectView.value = e
}

const detail = (e: any) => {
  console.log(e.namaLengkap)
  namaPegawai.value = e.namaLengkap
  H.tandaTangan().set("signature", e.ttd)
  // item.value.id = e.id
  // item.value.kodeexternal = e.kodeexternal
  // item.value.status = e.status
  // item.value.jeniskondisipasien = e.jeniskondisipasien
  modalDetail.value = true
}
fetchPegawai()
fetchData()

watch(
  () => [
    item.value.isAktif
  ], () => {
    fetchData()
  }
)


</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';

.masterTTD {
  .button.is-danger.is-outlined {
    display: none;
  }
}
</style>

