<template>
    <ConfirmDialog />
    <VCard>
        <div class="columns column c-title">
            <h3 class="title is-5 mb-2 ml-2" style="z-index:1">Status Keluar</h3>
        </div>

        <div class="columns is-multiline ml-4">
            <div class="column is-4">
                <img src="/images/avatars/label/validasi.png" slt="image status keluar" class="s-keluar">
                <VCard>
                    <div class="columns is-multiline">
                        <div class="column is-6">
                            <h3 class="title is-6 mb-2 mr-1">
                                <i class="iconify" data-icon="feather:edit" aria-hidden="true"> </i>
                                {{ item.id ?'Edit Data':' Tambah Data'}}
                            </h3>
                        </div>
                        <div class="column is-12">
                            <VField label="Status Keluar">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" v-model="item.statuskeluar" placeholder="Status Keluar"
                                        class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-12" v-if="item.id" style="display:flex; padding: 0px;">
                            <VField class="column is-8 is-rounded-select is-autocomplete-select">
                                <VLabel>Jenis Kondisi Pasien</VLabel>
                                <VControl icon="feather:search">
                                    <Multiselect mode="single" v-model="item.objectjeniskondisipasienfk"
                                        :options="d_JenisKondisi" placeholder="Pilih data" :searchable="true" />
                                </VControl>
                            </VField>

                            <VField class="column is-4">
                                <VLabel class="ml-2">Aktivasi</VLabel>
                                <VControl class="is-pulled-right">
                                    <VSwitchBlock style="padding-top:3px" v-model="item.statusenabled" label="Aktif"
                                        color="danger" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-12" v-else>
                            <VField class="is-rounded-select is-autocomplete-select">
                                <VLabel>Jenis Kondisi Pasien</VLabel>
                                <VControl icon="feather:search">
                                    <Multiselect mode="single" v-model="item.objectjeniskondisipasienfk"
                                        :options="d_JenisKondisi" placeholder="Pilih data" :searchable="true" />
                                </VControl>
                            </VField>
                        </div>

                        <div class="column is-12">
                            <div>
                                <VButton @click="save(item)" :loading="isLoadingButton" type="button" :icon="item.id ? 'feather:edit' : 'feather:save'"
                                    class="is-fullwidth mr-3" :color="item.id ?'info' : 'success'" raised>
                                   {{ item.id ? 'Update Data' : 'Simpan Data' }}
                                </VButton>
                                <VButton v-if="item.id" @click="clear()" type="button" icon="feather:x-circle"
                                    class="is-fullwidth is-outlined is-warning mt-3" raised>
                                    Batal Edit
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
                    <div class="column is-6">
                        <VControl class="is-pulled-right">
                            <VSwitchBlock v-model="isAktif" label="Aktif" color="danger"/>
                        </VControl>
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
                    <DataTable :value="dataSourcefiltered" :paginator="true" :rows="10" :rowsPerPageOptions="[5, 10, 25]"
                        :loading="isLoading" class="p-datatable-sm"
                        paginatorTemplate="CurrentPageReport FirstPageLink PrevPageLink PageLinks NextPageLink LastPageLink RowsPerPageDropdown"
                        responsiveLayout="stack" breakpoint="960px" sortMode="multiple"
                        currentPageReportTemplate="Showing {first} to {last} of {totalRecords}" showGridlines>

                        <Column field="no" header="#"></Column>
                        <Column field="statuskeluar" header="Status Keluar" :sortable="true"></Column>
                        <Column field="jeniskondisipasien" header="Jenis Kondisi Pasien" :sortable="true"></Column>
                        <Column header="Status" :exportable="false" style="text-align: center">
                            <template #body="slotProps">
                                <VTag :color="isAktif == true ? 'primary' : 'danger'" :label="slotProps.data.status" rounded /> 
                            </template>
                        </Column>
                        <Column :exportable="false" header="Action" style="text-align: center;">
                            <template #body="slotProps">
                                <VIconButton type="button" icon="pi pi-pencil" class="mr-3" color="info" circle outlined
                                    raised v-tooltip.top="'Edit'" @click="edit(slotProps.data)">
                                </VIconButton>
                                <VIconButton type="button" :disabled="!isAktif"  icon="fas fa-trash" color="danger"
                                    circle outlined raised v-tooltip.top="'Hapus'" @click="DialogConfirm(slotProps.data)">
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
                                <div class="tile-grid-item-inner" @click="edit(item)">
                                    <VIconBox size="medium" color="warning" rounded>
                                        <i class="fas fa-envelope-open"></i>
                                    </VIconBox>

                                    <div class="meta">
                                        <span class="dark-inverted mb-2">{{ item.statuskeluar }}</span>
                                        <span class="dark-inverted">{{ item.jeniskondisipasien }}</span>

                                    </div>
                                    <VTag :color="item.status_c" :label="item.status" style="margin-left:25px" />
                                    <VDropdown icon="feather:more-vertical" spaced right>
                                        <template #content>
                                            <a role="menuitem" class="dropdown-item is-media" @click="edit(item)">
                                                <div class="icon">
                                                    <i class="iconify" data-icon="feather:edit" aria-hidden="true"></i>
                                                </div>
                                                <div class="meta">
                                                    <span>Edit</span>
                                                    <span>Untuk merubah data </span>
                                                </div>
                                            </a>
                                            <a role="menuitem" v-if="item.statusenabled == true"
                                                class="dropdown-item is-media" @click="DialogConfirm(item)">
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

        <VModal :open="modalDetail" title="Detail Status Keluar" noclose actions="right"
            @close="modalDetail = false, clear()">
            <template #content>
                <form class="modal-form">
                    <div class="columns is-multiline">
                        <div class="column is-12">
                            <VField label="Status Keluar">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.statuskeluar"
                                        placeholder="Kelompok Produk" class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-8">
                            <VField label="Kondisi Pasien">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.jeniskondisipasien"
                                        placeholder="Kode External" class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-4">
                            <VField label="Kode External">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.kodeexternal"
                                        placeholder="Kode External" class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-6">
                            <VField label="Status Aktivasi">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.status"
                                        placeholder="Norec" class="is-rounded" />
                                </VControl>
                            </VField>
                        </div>
                        <div class="column is-6">
                            <VField label="Report Display">
                                <VControl icon="feather:bookmark">
                                    <VInput type="text" readonly style="cursor:pointer" v-model="item.reportdisplay"
                                        placeholder="Report Display" class="is-rounded" />
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
import { useRoute } from 'vue-router'
import ConfirmDialog from 'primevue/confirmdialog'
import DataTable from 'primevue/datatable'
import Column from 'primevue/column'
import { useConfirm } from 'primevue/useconfirm'
import { useHead } from '@vueuse/head'
import { useToaster } from '/@src/composable/toaster'

import { useViewWrapper } from '/@src/stores/viewWrapper'
import { watch } from 'vue'
useHead({
    title: 'Status Keluar - ' + import.meta.env.VITE_PROJECT,
})

useViewWrapper().setPageTitle(import.meta.env.VITE_PROJECT)
useViewWrapper().setFullWidth(false)
const item: any = ref({})
const d_JenisKondisi = ref([])
const modalDetail = ref(false)
const isAktif = ref(true)

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
const confirm = useConfirm()
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
            items.statuskeluar.match(new RegExp(filters.value, 'i'))
        )
    })
})

const fetchData = async () => {

    isLoading.value = true
    await useApi().get(`/sysadmin/master-status-keluar?statusenabled=${isAktif.value}`).then((response: any) => {
        response.data.forEach((items: any, i: any) => {
            items.no = i + 1
        })
        isLoading.value = false
        dataSource.value = response.data
    })
}

const save = async (item: any) => {
    if (!item.statuskeluar) {
        useToaster().error('Status Keluar harus di isi')
        return
    }
    if (!item.objectjeniskondisipasienfk) {
        useToaster().error('Kondisi Pasien Wajib Dipilih')
        return
    }

    let objSave =
    {
        'statuskeluar': {
            'id': item.id ? item.id : '',
            'statuskeluar': item.statuskeluar,
            'statusenabled': item.statusenabled,
            'objectjeniskondisipasienfk': item.objectjeniskondisipasienfk ? item.objectjeniskondisipasienfk : '',
        }
    }

    isLoadingButton.value = true
    await useApi().post(
        `/sysadmin/master-status-keluar/save`, objSave).then((response: any) => {
            isLoadingButton.value = false
            clear()
            fetchData()
        }, (error) => {
            isLoadingButton.value = false
            // console.log(error)
        })
}

const deleterow = async (e: any) => {
    isLoading.value = true
    await useApi().post(
        `/sysadmin/master-status-keluar/delete`, { 'id': e.id }).then((response: any) => {
            isLoading.value = false
            clear()
            fetchData()
        }, (error) => {
        })
}

const loadDropdown = () => {
    useApi().get(`/sysadmin/master-status-keluar/select-jenis-kondisi`).then((response: any) => {
        d_JenisKondisi.value = response.jeniskondisipasien.map((e: any) => { return { label: e.jeniskondisipasien, value: e.id } })
    })
}

const edit = (e: any) => {
    item.value.id = e.id
    item.value.statuskeluar = e.statuskeluar
    item.value.namaexternal = e.namaexternal
    item.value.reportdisplay = e.reportdisplay
    item.value.statusenabled = e.statusenabled
    item.value.objectjeniskondisipasienfk = e.objectjeniskondisipasienfk
}

const detail = (e: any) => {
    item.value.id = e.id
    item.value.statuskeluar = e.statuskeluar
    item.value.namaexternal = e.namaexternal
    item.value.kodeexternal = e.kodeexternal
    item.value.status = e.status
    item.value.reportdisplay = e.reportdisplay
    item.value.jeniskondisipasien = e.jeniskondisipasien
    modalDetail.value = true
}

const DialogConfirm = (e: any) => {
    confirm.require({
        message: 'Apakah anda serius menghapus data ini ?',
        header: 'Konfirmasi Hapus Data',
        icon: 'pi pi-info-circle',
        acceptClass: 'p-button-danger',
        accept: () => {
            deleterow(e)

        },
        reject: () => { },
    })
}

const clear = () => {
    item.value.id = ''
    item.value.statuskeluar = ''
    item.value.objectjeniskondisipasienfk = ''
}
const changeView = (e: any) => {
    selectView.value = e
}

loadDropdown()
fetchData()

watch(isAktif,()=>{
    fetchData()
})

</script>
<style lang="scss">
@import '/@src/scss/abstracts/all';

@import '/@src/scss/components/forms-outer';
@import '/@src/scss/custom/config';
@import '/@src/scss/module/sysadmin/master-data.scss';
</style>