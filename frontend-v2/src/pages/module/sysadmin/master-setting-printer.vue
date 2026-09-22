<template>
  <VCard>
    <div class="columns column">
      <h3 class="title is-5 mb-2 mr-1">Setting Printer</h3>
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
          </div>
        </div>

        <div class="user-grid user-grid-v2" v-if="selectView == 'list'">
          <DataTable :value="dataSourcefiltered" :paginator="true" :rows="30" :rowsPerPageOptions="[5, 10, 25]"
            paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
            responsiveLayout="stack" scrollable scrollHeight="600px" sortField="namaexternal" sortMode="single"
            rowGroupMode="subheader" groupRowsBy="namaexternal" ruls
            currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>
            <template #empty> No record found. </template>
            <Column field="no" header="No"></Column>
            <Column field="namaexternal" header="Nama Cetakan" :sortable="true"></Column>
            <Column field="orientation" header="Orientation" :sortable="false"></Column>
            <Column field="size" header="Size" :sortable="false"></Column>
            <Column field="printerdefault" header="Printer" :sortable="false"></Column>
            <Column field="devicename" header="Device" :sortable="false"></Column>
            <Column field="keterangan" header="Keterangan" :sortable="false"></Column>
            <Column :exportable="false" header="Action">
              <template #body="slotProps">
                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined raised
                  v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                </VIconButton>
                <VIconButton type="button" icon="fas fa-trash" class="mr-3" color="danger" circle outlined raised
                  v-tooltip.top="'Hapus'" @click="DialogConfirm(slotProps.data)">
                </VIconButton>
              </template>
            </Column>
            <template #groupheader="slotProps">
              <div class="flex align-items-center gap-2">
                <span style="font-weight:bold">{{ slotProps.data.namaexternal }}</span>
              </div>
            </template>
            <template #groupfooter="slotProps">
              <div class="flex justify-content-end font-bold w-full">Count: {{
                calculateCustomerTotal(slotProps.data.namaexternal) }}</div>
            </template>
          </DataTable>
        </div>
        <div class="list-view list-view-v3" v-else-if="selectView == 'grid'">
          <TransitionGroup name="list-complete" tag="div">
            <div v-for="item in dataSourcefiltered" :key="item.id" class="list-view-item">
              <div class="list-view-item-inner">
                <VIconBox color="danger" rounded>
                  <i class="iconify" data-icon="feather:printer" aria-hidden="true"></i>
                </VIconBox>
                <!-- <VAvatar size="medium" icon="feather:printer" color="primary" squared bordered /> -->
                <div class="meta-left">
                  <h3> {{ item.namaexternal }} </h3>
                  <span>
                    <VTag :color="'success'" :label="item.printerdefault" />
                  </span>
                </div>
                <div class="meta-right">
                  <div class="buttons">

                    <VButton color="warning" outlined raised @click="edit(item)"> Edit </VButton>

                    <VIconButton icon="feather:trash" class="hint--bubble hint--primary hint--top" data-hint="Delete"
                      circle @click="DialogConfirm(item)" />
                  </div>
                </div>
              </div>
            </div>
          </TransitionGroup>
        </div>

      </div>
      <div class="column is-4">
        <img src="/images/avatars/svg/printer.svg" alt="" srcset=""
          style="max-width:65%;margin-left: 8rem; margin-top: -5rem;">
        <VCard style="margin-top :-3rem">
          <div class="columns is-multiline">
            <div class="column is-6">
              <h3 v-if="item.id" class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                Edit Data
              </h3>
              <h3 v-else class="title is-6 mb-2 mr-1">
                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                Tambah Data
              </h3>
            </div>
            <div class="column is-12">
              <VField label="Nama Cetakan ">
                <VControl icon="feather:book" class="prime-auto-select">

                  <Dropdown v-model="item.namaexternal" :options="d_Cetakan" :optionLabel="'name'" class="is-rounded"
                    placeholder="Pilih data" style="width: 100%;" @change="changeCetak(item.namaexternal)" showClear
                    :filter="true" />

                  <!-- <VInput type="text" v-model="item.namaexternal" placeholder="Nama Cetakan " class="is-rounded" /> -->
                </VControl>
              </VField>

              <VField label="Orientation ">
                <VControl>
                  <VRadio v-model="item.orientation" value="portrait" label="Portrait" name="outlined_radio"
                    color="warning" />
                  <VRadio v-model="item.orientation" value="landscape" label="Landscape" name="outlined_radio"
                    color="info" />
                </VControl>
              </VField>
              <VField label="Keterangan">
                <VControl>
                  <VTextarea class="textarea is-rounded" v-model="item.keterangan" rows="2"
                    placeholder="Keterangan Printer..." autocomplete="off" autocapitalize="off" spellcheck="true" />
                </VControl>
              </VField>

              <VField label="Size (mm)">
                <div class="columns is-multiline">
                  <div class="column is-6">
                    <VControl icon="feather:bar-chart">
                      <VInput type="number" v-model="item.width" placeholder="Width " class="is-rounded" />
                    </VControl>
                  </div>
                  <div class="column is-6">
                    <VControl icon="feather:bar-chart">
                      <VInput type="number" v-model="item.height" placeholder="Height " class="is-rounded" />
                    </VControl>
                  </div>
                </div>
                <div class="columns is-multiline">listNetworkDevices
                  <div class="column is-12 p-2">
                    <VField label="Printer ">
                      <VControl icon="feather:printer">
                        <VInput type="text" v-model="item.printerdefault" placeholder="Printer Name" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-10 p-2">
                    <VField label="Device Name ">
                      <VControl icon="fas fa-laptop">
                        <VInput type="text" v-model="item.devicename" placeholder="Device Name" class="is-rounded" />
                      </VControl>
                    </VField>
                  </div>
                  <div class="column is-2 p-0 " style="margin-top: 30px;">
                    <VIconButton type="button" icon="feather:refresh-ccw" class="mr-3" color="warning" circle outlined
                      raised v-tooltip.top="'Find Device Name'" @click="fetchDevice()">
                    </VIconButton>
                  </div>
                </div>
              </VField>

            </div>

            <!-- <div class="column is-3" v-if="item.id">
              <VField label="Aktivasi" class="ml-4">
                <VControl class="is-pulled-right">
                  <VSwitchBlock v-model="item.statusenabled" color="danger" @change="changeSwitch()" />
                </VControl>
              </VField>
            </div> -->
            <div v-if="item.id" class="column is-12">
              <VButton @click="save()" :loading="isLoading" type="button" icon="feather:edit" class="is-fullwidth mr-3"
                color="info" raised>
                Update Data
              </VButton>
              <VButton @click="clear()" type="button" icon="feather:x-circle"
                class="is-fullwidth is-outlined is-warning mt-3" raised>
                Batal Edit
              </VButton>
            </div>
            <div v-else class="column is-12">
              <VButton @click="save()" :loading="isLoadingTT" type="button" icon="feather:save" class="is-fullwidth mr-3"
                color="success" raised>
                Simpan Data
              </VButton>


            </div>
          </div>
        </VCard>
        <VCard class="mt-2">
          <div class="columns is-multiline">
            <div class="column is-12">
              <VButton @click="findPrinters()" :loading="isLoadingPrint" outlined type="button" icon="feather:search"
                class="is-fullwidth mr-3 mt-3" color="info" raised>
                Find All Printers
              </VButton>
            </div>
            <div class="column is-12" v-if="listPrinter.length">
              <VCard>
                <div class="form-section pr-0 mt-0 pt-0">
                  <div class="form-section-inner has-padding-bottom h-700-o ">
                    <h3 class="has-text-centered">Available Printer (<b>{{ listPrinter.length }}</b>)
                      <VIconButton class="mr-2 mt-1-min is-pulled-right" icon="fas fa-times-circle" outlined
                        v-tooltip.danger.bubble="'Clear'" color="danger" raised bold @click="listPrinter = []">
                      </VIconButton>
                    </h3>
                    <div class="columns is-multiline mt-3" style="width: 100%;overflow: auto;">
                      <div class="column is-12 mt-0 p-o" v-for="items in listPrinter" :key="items.name">
                        <div class="columns is-multiline ">
                          <div class="column is-10">
                            <VTag color="purple" :label="items.name" />
                          </div>
                          <div class="column is-2">
                            <VButton color="warning" icon="feather:arrow-right" raised outlined @click="pilih(items)"
                              class="btn-slim">
                              Pilih </VButton>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </VCard>
            </div>
          </div>
        </VCard>
      </div>

    </div>
  </VCard>
</template>

<script  setup lang="ts">
import { useWindowScroll } from '@vueuse/core'
import { useApi } from '/@src/composable/useApi'
import { ref, computed, watch, reactive } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import * as qzService from '/@src/utils/qzTrayService'
import sleep from '/@src/utils/sleep'
import $ from "jquery";
import Dropdown from 'primevue/dropdown';
useHead({
  title: 'Setting Printer - ' + import.meta.env.VITE_PROJECT,
})
const item: any = ref({
  aktif: true
})
const modalInput = ref(false)
const modalDetail = ref(false)
const isLoadingPrint = ref(false)
const listPrinter: any = ref([])
let dataSource: any = ref([])
let isRegistrasi = ref(false)
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

const d_Cetakan = ref([
  { name: 'ANTRIAN LOKET', width: 72, height: 42, orientation: 'portrait', },
  { name: 'ANTRIAN POLI', width: 180, height: 210, orientation: 'portrait', },
  { name: 'SEP', width: 210, height: 100, orientation: 'landscape', },
  { name: 'KARTU PASIEN', width: 42, height: 72, orientation: 'landscape', },
  { name: 'LABEL PASIEN', width: 210, height: 140, orientation: 'landscape', },
  { name: 'LABEL NAMA', width: 67, height: 67 , orientation: 'landscape', },
  { name: 'GELANG PASIEN', width: 60, height: 40, orientation: 'landscape', },
  { name: 'KWITANSI', width: 210, height: 140, orientation: 'portrait', },
  { name: 'RESEP', width: 100, height: 140, orientation: 'portrait', },
  { name: 'TRACER GENAP', width: 100, height: 140, orientation: 'portrait', },
  { name: 'TRACER GANJIL', width: 100, height: 140, orientation: 'portrait', },
  { name: 'LABEL RESEP', width: 60, height: 63, orientation: 'landscape', },
  { name: 'LABEL BIRU RESEP', width: 60, height: 63, orientation: 'landscape', },
  { name: 'LABEL GIZI', width: 100, height: 140, orientation: 'portrait', },
])
const selectView: any = ref()
selectView.value = 'list'
let isLoading: any = ref(false)
let isLoadingTT: any = ref(false)
let ID_Agama = useRoute().query.id as string
let ID_Agama_SET = ref()

const currentPage: any = ref({
  limit: 5,
  rows: 50,
})
const filters = ref('')
const dataSourcefiltered = computed(() => {
  if (!filters.value) {
    return dataSource.value
  }

  return dataSource.value.filter((items: any) => {
    return (
      items.namaexternal.match(new RegExp(filters.value, 'i'))
      ||
      items.printerdefault.match(new RegExp(filters.value, 'i'))
    )
  })
})

const route = useRoute()
isLoading.value = false

async function fetchData() {

  isLoading.value = true

  const response = await useApi().get(`/general/printer`)
  isLoading.value = false
  for (let x = 0; x < response.data.length; x++) {
    const element = response.data[x];
    element.no = x + 1
    element.size = ''
    if (element.width != null && element.height != null) {
      element.size = element.width + 'x' + element.height
    }
  }

  dataSource.value = response.data

}
function loadData() {
  fetchData()
}

function add() {
  clear()
  modalInput.value = true
}

function edit(e: any) {
  item.value.id = e.id
  d_Cetakan.value.forEach((element: any) => {
    if (element.name == e.namaexternal) {
      item.value.namaexternal = element
    }
  });

  item.value.printerdefault = e.printerdefault
  item.value.orientation = e.orientation
  item.value.width = e.width
  item.value.height = e.height
  item.value.devicename = e.devicename
  item.value.keterangan = e.keterangan
}


async function save() {
  if (!item.value.namaexternal) {
    useToaster().error('Nama Cetakan harus di isi')
    return
  }
  if (!item.value.printerdefault) {
    useToaster().error('Nama Printer harus di isi')
    return
  }
  var objSave =
  {

    'id': item.value.id ? item.value.id : '',
    'namaexternal': item.value.namaexternal.name,
    'printerdefault': item.value.printerdefault ? item.value.printerdefault : null,
    'orientation': item.value.orientation ? item.value.orientation : null,
    'width': item.value.width ? item.value.width : null,
    'height': item.value.height ? item.value.height : null,
    'devicename': item.value.devicename ? item.value.devicename : null,
    'keterangan': item.value.keterangan ? item.value.keterangan : null,
  }
  isLoadingTT.value = true
  await useApi().post(
    `/general/save-printer`, objSave).then((response: any) => {
      isLoadingTT.value = false
      clear()
      loadData()
    }, (error) => {
      isLoadingTT.value = false
      // console.log(error)
    })
}

async function deleterow(e: any) {
  await useApi().post(
    `/general/delete-printer`, { 'id': e.id }).then((response: any) => {
      loadData()
    }, (error) => {
    })
}
const DialogConfirm = (e: any) => {
  deleterow(e)
}

function clear() {
  item.value = {}
}
function changeView(e: any) {
  selectView.value = e
}
function changeSwitch(e: any) {
  fetchData()
}
function filter() {
  fetchData()
}
function clearFilter() {
  fetchData()
}
function displayMessage(msg, css, time) {
  if (css == undefined) { css = 'alert-info'; }

  var timeout = setTimeout(function () { $('#' + timeout).alert('close'); }, time ? time : 5000);

  var alert = $("<div/>").addClass('alert alert-dismissible fade in ' + css)
    .css('max-height', '20em').css('overflow', 'auto')
    .attr('id', timeout).attr('role', 'alert');
  alert.html("<button type='button' class='close' data-dismiss='alert'>&times;</button>" + msg);

  $("#qz-alert").append(alert);
}
const findPrinters = () => {
  isLoadingPrint.value = true
  listPrinter.value = []
  qzService.qz.printers.find().then(function (data) {
    var list = '';
    for (var i = 0; i < data.length; i++) {
      listPrinter.value.push({ 'name': data[i] })
    }
    isLoadingPrint.value = false
  }).catch((e) => {
    console.log(e)
    isLoadingPrint.value = false
  });

}
const changeCetak = (e: any) => {
  if (e) {
    item.value.orientation = e.orientation
    item.value.width = e.width
    item.value.height = e.height
  }
}
const pilih = (e: any) => {
  item.value.printerdefault = e.name
}


const fetchDevice = async () => {
  let listItems = function (obj) {
    let html = '';
    let labels = { mac: 'MAC', ip: 'IP', up: 'Up', ip4: 'IPv4', ip6: 'IPv6', primary: 'Primary' };
    let arr = []
    Object.keys(labels).forEach(function (key) {
      if (!obj.hasOwnProperty(key)) { return; }
      if (key !== 'ip' && obj[key] == obj['ip']) { return; }

      let value = obj[key];
      if (key === 'mac') { value = obj[key].match(/.{1,2}/g).join(':'); }
      if (typeof obj[key] === 'object') { value = value.join(', '); }
      arr.push({ label: labels[key], value: value })
      html += '<li><strong>' + labels[key] + ':</strong> <code>' + value + '</code></li>';
    });

    return arr;
  };
  await qzService.qz.networking.devices().then(function (data) {
    let list = '';
    let arr = []
    for (let i = 0; i < data.length; i++) {
      let info = data[i];
      item.value.devicename = info.hostname
      arr = listItems(info)
      break
    }
  }).catch(err => {
    console.log(err)
  });
  // const response = await useApi().get(`/general/device-name`)
  // item.value.devicename = response
}
const calculateCustomerTotal = (name) => {
  let total = 0;

  if (dataSource.value) {
    for (let customer of dataSource.value) {
      if (customer.namaexternal === name) {
        total++;
      }
    }
  }

  return total;
};
qzService.connect()

filter()
// loadData ()
fetchData()
await sleep(2000)
fetchDevice()
</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';
</style>
