<template>
     <ConfirmDialog />
    <VCard>
        <div class="columns column c-title">
            <h3 class="title is-5 mb-2 ml-2" style="z-index:1">Status Pegawai</h3>
        </div>

        <div class="columns is-multiline">
            <div class="column is-4" style="margin-top: 2.5rem;">
                <img src="/images/avatars/label/status-pegawai.png" class="mix">
                <VCard>
                    <div class="columns is-multiline">
                        <div class="column is-6">
                            <h3 class="title is-6 mb-2 mr-1">
                                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                                Tambah Data
                            </h3>
                        </div>
                        <div class="column is-12">
                            <VField label="Status Pegawai">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" v-model="item.statuspegawai" placeholder="Status Keluar"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>


                        <div class="column is-3" v-if="item.id">
                            <VField label="Aktivasi" />
                        </div>
                        <div class="column is-3" v-if="item.id">
                            <VControl class="is-pulled-right">
                                <VSwitchBlock v-model="item.statusenabled" color="danger"/>
                            </VControl>
                        </div>

                        <div class="column is-12">
                            <div v-if="item.id">
                                <VButton @click="save()" :loading="isLoadingButton" type="button" icon="feather:edit"
                                    class="is-fullwidth mr-3" color="info" raised>
                                    Update Data
                                </VButton>
                                <VButton @click="clear()" type="button" icon="feather:x-circle"
                                    class="is-fullwidth is-outlined is-warning mt-3" raised>
                                    Batal Edit
                                </VButton>
                            </div>
                            <div v-else>
                                <VButton @click="save()" :loading="isLoadingButton" type="button" icon="feather:save"
                                    class="is-fullwidth mr-3" color="success" raised>
                                    Simpan Data
                                </VButton>
                            </div>
                        </div>
                    </div>
                </VCard>
            </div>
            <div class="column is-8 mt-3">
                <div class="user-grid-toolbar">
                    <VControl icon="feather:search">
                        <input v-model="filters" class="input custom-text-filter" placeholder="Search..." />
                    </VControl>
                    <div class="column is-6 switch-filter" style="margin-right: 15px;">
                        <VControl>
                            <InputSwitch v-model="item.isAktif" @change="fetchData()" />
                        </VControl>
                        <span>Aktif</span>
                    </div>
                    <div class="buttons">
                        <VField v-slot="{ id }" class="is-icon-select">
                            <VControl>
                                <Multiselect v-model="selectView" :attrs="{ id }" placeholder="Select View" label="name"
                                    :options="d_View" :searchable="true" track-by="name" mode="single"
                                    @select="changeView(selectView)" autocomplete="off">
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
                    <DataTable :value="dataSourcefiltered" :paginator="true" :rows="5" :rowsPerPageOptions="[5, 10, 25]"
                        class="p-datatable-sm"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines
                        :loading="isLoading">
                        <Column field="no" header="#" />
                        <Column field="statuspegawai" header="Status Pegawai" :sortable="true" />
                        <Column field="kodeexternal" header="Kode" :sortable="true" />
                        <Column field="status" header="Status" :sortable="true" />
                        <Column :exportable="false" header="Action" style="text-align: center;">
                            <template #body="slotProps">
                                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined
                                    raised v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                                </VIconButton>
                                <VIconButton type="button" icon="fas fa-trash" v-if="item.isAktif == true" class="mr-3" color="danger" circle outlined
                                    raised v-tooltip.top="'Hapus'" @click="DialogConfirm(slotProps.data)">
                                </VIconButton>
                                <VIconButton type="button" icon="fas fa-trash" v-else disabled class="mr-3" color="danger" circle outlined
                                    raised v-tooltip.top="'Hapus'" @click="DialogConfirm(slotProps.data)">
                                </VIconButton>
                            </template>
                        </Column>
                    </DataTable>
                </div>
                <div class="tile-grid tile-grid-v1" v-else-if="selectView == 'grid'">

                    <TransitionGroup name="list" tag="div" class="columns is-multiline">
                        <!--Grid item-->
                        <div v-for="(item, key) in dataSourcefiltered" :key="key" class="column is-6">
                            <div class="tile-grid-item">
                                <div class="tile-grid-item-inner">
                                    <VAvatar size="medium" picture="/images/avatars/label/keterangan.jpg" color="primary"
                                        squared bordered />

                                    <div class="meta">
                                        <span class="dark-inverted mb-2">{{ item.statuspegawai }}</span>
                                        <span class="dark-inverted">{{ item.jeniskondisipasien }}</span>

                                    </div>
                                    <VTag :color="item.status_c" :label="item.status" style="margin-left:25px" />
                                    <VDropdown icon="feather:more-vertical" spaced right>
                                        <template #content>
                                            <a role="menuitem" class="dropdown-item is-media" @click="detail(item)">
                                                <div class="icon">
                                                    <i class="iconify" data-icon="feather:bookmark" aria-hidden="true"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Detail</span>
                                                    <span>Untuk melihat data </span>
                                                </div>
                                            </a>
                                            <a role="menuitem" class="dropdown-item is-media" @click="edit(item)">
                                                <div class="icon">
                                                    <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Edit</span>
                                                    <span>Untuk merubah data </span>
                                                </div>
                                            </a>
                                            <a role="menuitem" class="dropdown-item is-media" v-if="item.isAktif == true" @click="DialogConfirm(item)">
                                                <div class="icon">
                                                    <i aria-hidden="true" class="lnil lnil-trash-can-alt"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Remove</span>
                                                    <span>Hapus Data dari Daftar</span>
                                                </div>
                                            </a>
                                        </template>
                                    </VDropdown>
                                </div>
                            </div>
                        </div>
                    </TransitionGroup>
                </div>
            </div>

        </div>

        <VModal :open="modalDetail" title="Detail Status Pegawai" actions="right" @close="modalDetail = false, clear()">
            <template #content>
                <form class="modal-form">
                    <div class="columns is-multiline">
                        <div class="column is-12">
                            <VField label="Status Pegawai">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" v-model="item.statuspegawai" placeholder="Status Pegawai"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-6">
                            <VField label="Kode External">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" v-model="item.kodeexternal" placeholder="Kode External"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-6">
                            <VField label="Report Display">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" v-model="item.reportdisplay" placeholder="Report Display"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                    </div>
                </form>
            </template>

        </VModal>
    </VCard>
</template>
  
<script  setup lang="ts">
import { useApi } from '/@src/composable/useApi'
import { ref, computed } from 'vue'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import InputSwitch from 'primevue/inputswitch'
import ConfirmDialog from 'primevue/confirmdialog'
import { useConfirm } from 'primevue/useconfirm'

import { useViewWrapper } from '/@src/stores/viewWrapper'
useHead({
    title: ' Status Pegawai - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const confirm = useConfirm()

const item: any = ref({isAktif: true})
const modalDetail = ref(false)

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
selectView.value = 'list'
let isLoadingButton: any = ref(false)
let isLoading: any = ref(false)
const filters = ref('')

const dataSourcefiltered = computed(() => {
    if (!filters.value) {
        return dataSource.value
    }

    return dataSource.value.filter((items: any) => {
        return (
            items.statuspegawai.match(new RegExp(filters.value, 'i'))
        )
    })
})

const fetchData = async () => {
    isLoading.value = true
    let statusEnabled = item.value.isAktif ? `statusenabled=${item.value.isAktif}` : `statusenabled=${item.value.isAktif}`

    await useApi().get(`/sysadmin/master-status-pegawai?statusenabled=${item.value.isAktif}`).then((response: any) => {
        response.data.forEach((elements: any, i: any) => {
            elements.no = i + 1
        })
        dataSource.value = response.data
    })
    isLoading.value = false
}

const edit = (e: any) => {
    item.value.id = e.id
    item.value.statuspegawai = e.statuspegawai
    item.value.namaexternal = e.namaexternal
    item.value.reportdisplay = e.reportdisplay
    item.value.statusenabled = e.statusenabled
}
const detail = (e: any) => {
    item.value.id = e.id
    item.value.statuspegawai = e.statuspegawai
    item.value.namaexternal = e.namaexternal
    item.value.reportdisplay = e.reportdisplay
    item.value.kodeexternal = e.kodeexternal
    modalDetail.value = true
}

const save = async () => {
    if (!item.value.statuspegawai) {
        useToaster().error('Status Pegawai Harus di Isi')
        return
    }
    var objSave =
    {
        'statuspegawai': {
            'id': item.value.id ? item.value.id : '',
            'statuspegawai': item.value.statuspegawai,
            'statusenabled': item.value.statusenabled,
            'norec': item.value.norec ? item.value.norec : '',
        }
    }
    isLoadingButton.value = true
    await useApi().post(`/sysadmin/master-status-pegawai/save`, objSave).then((response: any) => {
        isLoadingButton.value = false
        clear()
        fetchData()
    }, (error) => {
        isLoadingButton.value = false
        // console.log(error)
    })
}

const deleteItem = async (e: any) => {
    isLoading.value = true
    await useApi().post(`/sysadmin/master-status-pegawai/delete`, { 'id': e.id }).then((response: any) => {
        isLoading.value = false
        fetchData()
    })
}

const clear = () => {
    item.value.id = ''
    item.value.statuspegawai = ''
}

const DialogConfirm = (e: any) => {
  confirm.require({
    message: 'Apakah anda serius menghapus data ini ?',
    header: 'Konfirmasi Hapus Data',
    icon: 'pi pi-info-circle',
    acceptClass: 'p-button-danger',
    accept: () => {
      deleteItem(e)
    
    },
    reject: () => {},
  })
}
const changeView = (e: any )=> {
    selectView.value = e
}

fetchData()

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';
</style>